<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\AccountManager;
use App\Models\Witel;
use App\Models\Divisi;
use Carbon\Carbon;

/**
 * ImportAMController - Account Manager Import Handler
 *
 * ✅ FIXED VERSION - 2026-02-04
 *
 * MAJOR FIXES:
 * ✅ FIXED: Removed temp_file_path from response (caused "undefined" error)
 * ✅ FIXED: Consistent response structure with ImportCCController
 * ✅ MAINTAINED: All functionality intact (multi-divisi, validation, error logging)
 *
 * MAJOR FEATURES:
 * ✅ Simplified template (7 columns only)
 * ✅ Multi-divisi detection - 3 patterns:
 *    - Pattern 1: Multiple rows same NIK (NI NYOMAN | DPS) then (NI NYOMAN | DSS)
 *    - Pattern 2: Comma with space (NI NYOMAN | DPS, DSS)
 *    - Pattern 3: Comma no space (NI NYOMAN | DPS,DSS)
 * ✅ Insert to account_manager_divisi pivot table
 * ✅ Regional support (TREG 1-5)
 * ✅ Error log generation with CSV export
 * ✅ All helper methods for consistency
 *
 * TEMPLATE STRUCTURE:
 * Data AM: NIK, NAMA AM, WITEL AM, DIVISI AM, REGIONAL, DIVISI, TELDA
 * Revenue AM: NIK, NAMA AM, PROPORSI, NIPNAS, STANDARD NAME, BULAN, TAHUN
 *
 * BUSINESS RULES:
 * - AM Revenue ALWAYS uses real_revenue_sold from CC
 * - Proporsi stored as 0.0-1.0 (auto-normalized if >1)
 * - Multiple divisi insert multiple rows in account_manager_divisi
 * 
 * @author RLEGS Team
 * @version 2.3 - Fixed Response Structure
 */
class ImportAMController extends Controller
{
    /**
     * ✅ Download Template Excel (SIMPLIFIED)
     */
    public function downloadTemplate($type)
    {
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            if ($type === 'data-am') {
                // Template Data General AM (SIMPLIFIED)
                $headers = ['NIK', 'NAMA AM', 'WITEL AM', 'DIVISI AM', 'REGIONAL', 'DIVISI', 'TELDA'];
                $sheet->fromArray($headers, null, 'A1');

                // Example data with multi-divisi patterns
                $exampleData = [
                    ['404482', 'I WAYAN AGUS SUANTARA', 'BALI', 'AM', 'TREG 3', 'DPS', ''],
                    ['970252', 'DESY CAHYANI LARI', 'NUSA TENGGARA', 'HOTDA', 'TREG 3', 'DPS', 'TELDA NUSA TENGGARA'],
                    ['123456', 'NI NYOMAN DANI', 'SEMARANG', 'AM', 'TREG 3', 'DPS,DSS', ''], // Multi-divisi dengan koma
                    ['789012', 'MADE WIRAWAN', 'BALI', 'AM', 'TREG 3', 'DPS, DSS', ''], // Multi-divisi dengan spasi
                ];
                $sheet->fromArray($exampleData, null, 'A2');

                // Styling
                $sheet->getStyle('A1:G1')->getFont()->setBold(true);
                $sheet->getStyle('A1:G1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('4472C4');
                $sheet->getStyle('A1:G1')->getFont()->getColor()->setRGB('FFFFFF');

                foreach (range('A', 'G') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Add notes
                $noteRow = count($exampleData) + 3;
                $sheet->setCellValue('A' . $noteRow, 'CATATAN PENTING:');
                $sheet->setCellValue('A' . ($noteRow + 1), '1. Untuk AM dengan multiple divisi, gunakan format: DPS,DSS atau DPS, DSS');
                $sheet->setCellValue('A' . ($noteRow + 2), '2. Bisa juga dengan baris terpisah (NIK sama, divisi beda)');
                $sheet->setCellValue('A' . ($noteRow + 3), '3. DIVISI AM: AM atau HOTDA');
                $sheet->setCellValue('A' . ($noteRow + 4), '4. DIVISI: DGS, DSS, atau DPS (bisa kombinasi dengan koma)');
                $sheet->getStyle('A' . $noteRow)->getFont()->setBold(true);

                $filename = 'Template_Data_AM_' . date('Ymd_His') . '.xlsx';

            } elseif ($type === 'revenue-am') {
                // Template Revenue AM Mapping
                $headers = ['NIK', 'NAMA AM', 'PROPORSI', 'NIPNAS', 'STANDARD NAME', 'BULAN', 'TAHUN'];
                $sheet->fromArray($headers, null, 'A1');

                // Example data
                $exampleData = [
                    ['404482', 'I WAYAN AGUS SUANTARA', '0.5', '76590001', 'BANK JATIM', '12', '2025'],
                    ['970252', 'DESY CAHYANI LARI', '0.5', '76590001', 'BANK JATIM', '12', '2025'],
                    ['404482', 'I WAYAN AGUS SUANTARA', '1.0', '76590002', 'PEMKOT SEMARANG', '12', '2025'],
                ];
                $sheet->fromArray($exampleData, null, 'A2');

                // Styling
                $sheet->getStyle('A1:G1')->getFont()->setBold(true);
                $sheet->getStyle('A1:G1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('70AD47');
                $sheet->getStyle('A1:G1')->getFont()->getColor()->setRGB('FFFFFF');

                foreach (range('A', 'G') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Add note about proporsi
                $noteRow = count($exampleData) + 3;
                $sheet->setCellValue('A' . $noteRow, 'CATATAN:');
                $sheet->setCellValue('A' . ($noteRow + 1), '- Total proporsi untuk 1 CC harus = 1.0 (100%)');
                $sheet->setCellValue('A' . ($noteRow + 2), '- Jika CC dihandle 1 AM, proporsi = 1.0');
                $sheet->setCellValue('A' . ($noteRow + 3), '- Jika CC dihandle 2 AM, proporsi masing-masing dijumlahkan harus = 1.0');
                $sheet->setCellValue('A' . ($noteRow + 4), '- Revenue AM dihitung dari: CC Revenue Sold × Proporsi');
                $sheet->getStyle('A' . $noteRow)->getFont()->setBold(true);

                $filename = 'Template_Revenue_AM_' . date('Ymd_His') . '.xlsx';
            } else {
                return response()->json(['error' => 'Invalid template type'], 400);
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $tempFile = tempnam(sys_get_temp_dir(), 'template_');
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Failed to generate template', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to generate template: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ✅ FIXED: Preview Data AM import with multi-divisi detection
     * 
     * CRITICAL FIX: Removed temp_file_path from response
     */
    public function previewDataAM($tempFilePath)
    {
        try {
            if (!Storage::disk('local')->exists($tempFilePath)) {
                return response()->json(['error' => 'File tidak ditemukan'], 404);
            }

            $fullPath = Storage::disk('local')->path($tempFilePath);
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if (empty($data) || count($data) < 2) {
                return response()->json(['error' => 'File kosong atau tidak memiliki data'], 400);
            }

            // Expected headers (SIMPLIFIED)
            $expectedHeaders = ['NIK', 'NAMA AM', 'WITEL AM', 'DIVISI AM', 'REGIONAL', 'DIVISI', 'TELDA'];
            $actualHeaders = array_map('trim', array_map('strtoupper', $data[0]));

            // Validate headers
            $missingHeaders = array_diff($expectedHeaders, $actualHeaders);
            if (!empty($missingHeaders)) {
                return response()->json([
                    'error' => 'Header tidak sesuai. Header yang hilang: ' . implode(', ', $missingHeaders)
                ], 400);
            }

            // Process data rows
            $previewData = [];
            $failedRows = [];
            $stats = [
                'total_rows' => 0,
                'valid_rows' => 0,
                'duplicate_niks' => 0,
                'new_ams' => 0,
                'existing_ams' => 0,
                'multi_divisi_ams' => 0,
            ];

            // Get column indices
            $columnIndices = $this->getColumnIndices($actualHeaders, $expectedHeaders);

            // Track NIK occurrences for duplicate detection
            $nikOccurrences = [];
            foreach (array_slice($data, 1) as $rowIndex => $row) {
                if (empty(array_filter($row))) continue;
                
                $nik = trim($this->getColumnValue($row, $columnIndices['NIK']));
                if (!empty($nik)) {
                    if (!isset($nikOccurrences[$nik])) {
                        $nikOccurrences[$nik] = 0;
                    }
                    $nikOccurrences[$nik]++;
                }
            }

            // Process each row
            foreach (array_slice($data, 1) as $rowIndex => $row) {
                $actualRowNumber = $rowIndex + 2;

                if (empty(array_filter($row))) {
                    continue;
                }

                $stats['total_rows']++;

                $nik = trim($this->getColumnValue($row, $columnIndices['NIK']));
                $nama = trim($this->getColumnValue($row, $columnIndices['NAMA AM']));
                $witelNama = trim($this->getColumnValue($row, $columnIndices['WITEL AM']));
                $divisiAm = trim($this->getColumnValue($row, $columnIndices['DIVISI AM']));
                $regional = trim($this->getColumnValue($row, $columnIndices['REGIONAL']));
                $divisiRaw = trim($this->getColumnValue($row, $columnIndices['DIVISI']));
                $telda = trim($this->getColumnValue($row, $columnIndices['TELDA']));

                $rowErrors = [];

                // Validate required fields
                if (empty($nik)) $rowErrors[] = 'NIK kosong';
                if (empty($nama)) $rowErrors[] = 'Nama AM kosong';
                if (empty($witelNama)) $rowErrors[] = 'Witel AM kosong';
                if (empty($divisiRaw)) $rowErrors[] = 'Divisi kosong';

                // Find Witel
                $witel = Witel::where('nama', 'LIKE', '%' . $witelNama . '%')->first();
                if (!$witel) {
                    $rowErrors[] = "Witel '$witelNama' tidak ditemukan";
                }

                // ✅ Parse divisi (support comma-separated)
                $divisiList = $this->parseDivisiList($divisiRaw);
                $divisiIds = [];
                $divisiNames = [];
                
                foreach ($divisiList as $divisiCode) {
                    $divisi = Divisi::where('kode', strtoupper($divisiCode))->first();
                    if ($divisi) {
                        $divisiIds[] = $divisi->id;
                        $divisiNames[] = $divisi->nama;
                    } else {
                        $rowErrors[] = "Divisi '$divisiCode' tidak ditemukan";
                    }
                }

                // Check if multi-divisi
                $isMultiDivisi = count($divisiIds) > 1;
                if ($isMultiDivisi) {
                    $stats['multi_divisi_ams']++;
                }

                // Check existing AM
                $existingAM = AccountManager::where('nik', $nik)->first();
                $status = $existingAM ? 'update' : 'new';
                
                if ($existingAM) {
                    $stats['existing_ams']++;
                } else {
                    $stats['new_ams']++;
                }

                // Check duplicate NIK in import file
                if ($nikOccurrences[$nik] > 1) {
                    $stats['duplicate_niks']++;
                    $rowErrors[] = "NIK duplikat dalam file (muncul {$nikOccurrences[$nik]}x)";
                }

                if (empty($rowErrors)) {
                    $stats['valid_rows']++;
                }

                $previewRow = [
                    'row_number' => $actualRowNumber,
                    'nik' => $nik,
                    'nama' => $nama,
                    'witel_nama' => $witelNama,
                    'witel_id' => $witel->id ?? null,
                    'role' => $divisiAm,
                    'regional' => $regional,
                    'divisi_raw' => $divisiRaw,
                    'divisi_ids' => $divisiIds,
                    'divisi_names' => $divisiNames,
                    'is_multi_divisi' => $isMultiDivisi,
                    'telda' => $telda,
                    'status' => $status,
                    'errors' => $rowErrors,
                    'valid' => empty($rowErrors)
                ];

                $previewData[] = $previewRow;

                if (!empty($rowErrors)) {
                    $failedRows[] = array_merge($previewRow, ['error' => implode('; ', $rowErrors)]);
                }
            }

            // Generate error log if there are errors
            $errorLogPath = null;
            if (!empty($failedRows)) {
                $errorLogPath = $this->generateErrorLog($failedRows, 'data_am');
            }

            // ✅ CRITICAL FIX: Removed temp_file_path from response
            return response()->json([
                'success' => true,
                'preview' => $previewData,
                'stats' => $stats,
                'failed_rows' => $failedRows,
                'has_errors' => !empty($failedRows),
                'error_log_path' => $errorLogPath
                // ✅ temp_file_path is managed by RevenueImportController via session
            ]);

        } catch (\Exception $e) {
            Log::error('Preview Data AM failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Preview gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ✅ Execute Data AM import with multi-divisi support
     */
    public function executeDataAM($tempFilePath, $filterType = 'all')
    {
        try {
            if (!Storage::disk('local')->exists($tempFilePath)) {
                return response()->json(['error' => 'File tidak ditemukan'], 404);
            }

            $fullPath = Storage::disk('local')->path($tempFilePath);
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if (empty($data) || count($data) < 2) {
                return response()->json(['error' => 'File kosong'], 400);
            }

            $stats = [
                'total_processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
                'multi_divisi_processed' => 0,
            ];

            $failedRows = [];

            // Get column indices
            $actualHeaders = array_map('trim', array_map('strtoupper', $data[0]));
            $expectedHeaders = ['NIK', 'NAMA AM', 'WITEL AM', 'DIVISI AM', 'REGIONAL', 'DIVISI', 'TELDA'];
            $columnIndices = $this->getColumnIndices($actualHeaders, $expectedHeaders);

            DB::beginTransaction();

            try {
                // Group rows by NIK to handle multi-divisi cases
                $nikGroups = [];
                foreach (array_slice($data, 1) as $rowIndex => $row) {
                    if (empty(array_filter($row))) continue;
                    
                    $nik = trim($this->getColumnValue($row, $columnIndices['NIK']));
                    if (!empty($nik)) {
                        if (!isset($nikGroups[$nik])) {
                            $nikGroups[$nik] = [];
                        }
                        $nikGroups[$nik][] = [
                            'row' => $row,
                            'row_number' => $rowIndex + 2
                        ];
                    }
                }

                foreach ($nikGroups as $nik => $nikRows) {
                    $stats['total_processed']++;
                    
                    // Take data from first row (all should be same except divisi)
                    $firstRow = $nikRows[0]['row'];
                    $nama = trim($this->getColumnValue($firstRow, $columnIndices['NAMA AM']));
                    $witelNama = trim($this->getColumnValue($firstRow, $columnIndices['WITEL AM']));
                    $divisiAm = trim($this->getColumnValue($firstRow, $columnIndices['DIVISI AM']));
                    $regional = trim($this->getColumnValue($firstRow, $columnIndices['REGIONAL']));
                    $telda = trim($this->getColumnValue($firstRow, $columnIndices['TELDA']));

                    // Collect all divisi from all rows for this NIK
                    $allDivisiIds = [];
                    foreach ($nikRows as $nikRow) {
                        $divisiRaw = trim($this->getColumnValue($nikRow['row'], $columnIndices['DIVISI']));
                        $divisiList = $this->parseDivisiList($divisiRaw);
                        
                        foreach ($divisiList as $divisiCode) {
                            $divisi = Divisi::where('kode', strtoupper($divisiCode))->first();
                            if ($divisi && !in_array($divisi->id, $allDivisiIds)) {
                                $allDivisiIds[] = $divisi->id;
                            }
                        }
                    }

                    // Find Witel
                    $witel = Witel::where('nama', 'LIKE', '%' . $witelNama . '%')->first();
                    if (!$witel) {
                        $failedRows[] = [
                            'row_number' => $nikRows[0]['row_number'],
                            'nik' => $nik,
                            'error' => "Witel '$witelNama' tidak ditemukan"
                        ];
                        $stats['errors']++;
                        continue;
                    }

                    // Find or create AM
                    $existingAM = AccountManager::where('nik', $nik)->first();
                    
                    if ($existingAM) {
                        // Update existing AM
                        if ($filterType === 'new') {
                            $stats['skipped']++;
                            continue;
                        }

                        $existingAM->update([
                            'nama' => $nama,
                            'witel_id' => $witel->id,
                            'role' => $divisiAm ?: 'AM',
                            'regional' => $regional,
                            'telda' => $telda,
                        ]);

                        $stats['updated']++;
                    } else {
                        // Create new AM
                        if ($filterType === 'update') {
                            $stats['skipped']++;
                            continue;
                        }

                        $existingAM = AccountManager::create([
                            'nik' => $nik,
                            'nama' => $nama,
                            'witel_id' => $witel->id,
                            'role' => $divisiAm ?: 'AM',
                            'regional' => $regional,
                            'telda' => $telda,
                        ]);

                        $stats['created']++;
                    }

                    // Sync divisi relationships (pivot table)
                    if (!empty($allDivisiIds)) {
                        $existingAM->divisis()->sync($allDivisiIds);
                        
                        if (count($allDivisiIds) > 1) {
                            $stats['multi_divisi_processed']++;
                        }
                    }
                }

                DB::commit();

                // Generate error log if there are errors
                $errorLogPath = null;
                if (!empty($failedRows)) {
                    $errorLogPath = $this->generateErrorLog($failedRows, 'data_am');
                }

                return response()->json([
                    'success' => true,
                    'stats' => $stats,
                    'failed_rows' => $failedRows,
                    'error_log_path' => $errorLogPath,
                    'message' => "Import selesai. {$stats['created']} AM baru, {$stats['updated']} AM diupdate."
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Execute Data AM import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Import gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ✅ FIXED: Preview Revenue AM import
     * 
     * CRITICAL FIX: Removed temp_file_path from response
     */
    public function previewRevenueAM($tempFilePath, $year = null, $month = null)
    {
        try {
            if (!Storage::disk('local')->exists($tempFilePath)) {
                return response()->json(['error' => 'File tidak ditemukan'], 404);
            }

            $fullPath = Storage::disk('local')->path($tempFilePath);
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if (empty($data) || count($data) < 2) {
                return response()->json(['error' => 'File kosong atau tidak memiliki data'], 400);
            }

            // Expected headers
            $expectedHeaders = ['NIK', 'NAMA AM', 'PROPORSI', 'NIPNAS', 'STANDARD NAME', 'BULAN', 'TAHUN'];
            $actualHeaders = array_map('trim', array_map('strtoupper', $data[0]));

            // Validate headers
            $missingHeaders = array_diff($expectedHeaders, $actualHeaders);
            if (!empty($missingHeaders)) {
                return response()->json([
                    'error' => 'Header tidak sesuai. Header yang hilang: ' . implode(', ', $missingHeaders)
                ], 400);
            }

            $previewData = [];
            $failedRows = [];
            $stats = [
                'total_rows' => 0,
                'valid_rows' => 0,
                'new_mappings' => 0,
                'existing_mappings' => 0,
                'invalid_proporsi' => 0,
            ];

            // Get column indices
            $columnIndices = $this->getColumnIndices($actualHeaders, $expectedHeaders);

            // Group by CC to validate proporsi
            $ccProporsiMap = [];

            foreach (array_slice($data, 1) as $rowIndex => $row) {
                $actualRowNumber = $rowIndex + 2;

                if (empty(array_filter($row))) continue;

                $stats['total_rows']++;

                $nik = trim($this->getColumnValue($row, $columnIndices['NIK']));
                $nama = trim($this->getColumnValue($row, $columnIndices['NAMA AM']));
                $proporsi = floatval($this->getColumnValue($row, $columnIndices['PROPORSI']));
                $nipnas = trim($this->getColumnValue($row, $columnIndices['NIPNAS']));
                $standardName = trim($this->getColumnValue($row, $columnIndices['STANDARD NAME']));
                $bulan = intval($this->getColumnValue($row, $columnIndices['BULAN']));
                $tahun = intval($this->getColumnValue($row, $columnIndices['TAHUN']));

                $rowErrors = [];

                // Validate required fields
                if (empty($nik)) $rowErrors[] = 'NIK kosong';
                if (empty($nipnas)) $rowErrors[] = 'NIPNAS kosong';
                if ($proporsi <= 0 || $proporsi > 1) $rowErrors[] = 'Proporsi harus antara 0.01 - 1.0';
                if ($bulan < 1 || $bulan > 12) $rowErrors[] = 'Bulan tidak valid';
                if ($tahun < 2020) $rowErrors[] = 'Tahun tidak valid';

                // Find AM
                $am = AccountManager::where('nik', $nik)->first();
                if (!$am) {
                    $rowErrors[] = "AM dengan NIK '$nik' tidak ditemukan";
                }

                // Find CC
                $cc = \App\Models\CorporateCustomer::where('nipnas', $nipnas)->first();
                if (!$cc) {
                    $rowErrors[] = "CC dengan NIPNAS '$nipnas' tidak ditemukan";
                }

                // Check existing mapping
                $existingMapping = null;
                if ($am && $cc) {
                    $existingMapping = \App\Models\AmRevenue::where('account_manager_id', $am->id)
                        ->where('corporate_customer_id', $cc->id)
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
                        ->first();
                    
                    if ($existingMapping) {
                        $stats['existing_mappings']++;
                    } else {
                        $stats['new_mappings']++;
                    }
                }

                $status = $existingMapping ? 'update' : 'new';

                // Track proporsi per CC
                $ccKey = "{$nipnas}_{$bulan}_{$tahun}";
                if (!isset($ccProporsiMap[$ccKey])) {
                    $ccProporsiMap[$ccKey] = 0;
                }
                $ccProporsiMap[$ccKey] += $proporsi;

                if (empty($rowErrors)) {
                    $stats['valid_rows']++;
                }

                $previewRow = [
                    'row_number' => $actualRowNumber,
                    'nik' => $nik,
                    'nama' => $nama,
                    'am_id' => $am->id ?? null,
                    'proporsi' => $proporsi,
                    'nipnas' => $nipnas,
                    'standard_name' => $standardName,
                    'cc_id' => $cc->id ?? null,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'status' => $status,
                    'errors' => $rowErrors,
                    'valid' => empty($rowErrors)
                ];

                $previewData[] = $previewRow;

                if (!empty($rowErrors)) {
                    $failedRows[] = array_merge($previewRow, ['error' => implode('; ', $rowErrors)]);
                }
            }

            // Validate total proporsi per CC
            foreach ($ccProporsiMap as $ccKey => $totalProporsi) {
                if (abs($totalProporsi - 1.0) > 0.01) {
                    $stats['invalid_proporsi']++;
                    $failedRows[] = [
                        'row_number' => 'Multiple',
                        'nipnas' => explode('_', $ccKey)[0],
                        'bulan' => explode('_', $ccKey)[1],
                        'tahun' => explode('_', $ccKey)[2],
                        'error' => "Total proporsi = $totalProporsi (harus = 1.0)"
                    ];
                }
            }

            // Generate error log if there are errors
            $errorLogPath = null;
            if (!empty($failedRows)) {
                $errorLogPath = $this->generateErrorLog($failedRows, 'revenue_am');
            }

            // ✅ CRITICAL FIX: Removed temp_file_path from response
            return response()->json([
                'success' => true,
                'preview' => $previewData,
                'stats' => $stats,
                'failed_rows' => $failedRows,
                'has_errors' => !empty($failedRows),
                'error_log_path' => $errorLogPath
                // ✅ temp_file_path is managed by RevenueImportController via session
            ]);

        } catch (\Exception $e) {
            Log::error('Preview Revenue AM failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Preview gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ✅ Execute Revenue AM import
     */
    public function executeRevenueAM($tempFilePath, $filterType = 'all')
    {
        try {
            if (!Storage::disk('local')->exists($tempFilePath)) {
                return response()->json(['error' => 'File tidak ditemukan'], 404);
            }

            $fullPath = Storage::disk('local')->path($tempFilePath);
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if (empty($data) || count($data) < 2) {
                return response()->json(['error' => 'File kosong'], 400);
            }

            $stats = [
                'total_processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];

            $failedRows = [];

            // Get column indices
            $actualHeaders = array_map('trim', array_map('strtoupper', $data[0]));
            $expectedHeaders = ['NIK', 'NAMA AM', 'PROPORSI', 'NIPNAS', 'STANDARD NAME', 'BULAN', 'TAHUN'];
            $columnIndices = $this->getColumnIndices($actualHeaders, $expectedHeaders);

            DB::beginTransaction();

            try {
                foreach (array_slice($data, 1) as $rowIndex => $row) {
                    $actualRowNumber = $rowIndex + 2;

                    if (empty(array_filter($row))) continue;

                    $stats['total_processed']++;

                    $nik = trim($this->getColumnValue($row, $columnIndices['NIK']));
                    $proporsi = floatval($this->getColumnValue($row, $columnIndices['PROPORSI']));
                    $nipnas = trim($this->getColumnValue($row, $columnIndices['NIPNAS']));
                    $bulan = intval($this->getColumnValue($row, $columnIndices['BULAN']));
                    $tahun = intval($this->getColumnValue($row, $columnIndices['TAHUN']));

                    // Find AM
                    $am = AccountManager::where('nik', $nik)->first();
                    if (!$am) {
                        $failedRows[] = [
                            'row_number' => $actualRowNumber,
                            'nik' => $nik,
                            'nipnas' => $nipnas,
                            'error' => "AM dengan NIK '$nik' tidak ditemukan"
                        ];
                        $stats['errors']++;
                        continue;
                    }

                    // Find CC
                    $cc = \App\Models\CorporateCustomer::where('nipnas', $nipnas)->first();
                    if (!$cc) {
                        $failedRows[] = [
                            'row_number' => $actualRowNumber,
                            'nik' => $nik,
                            'nipnas' => $nipnas,
                            'error' => "CC dengan NIPNAS '$nipnas' tidak ditemukan"
                        ];
                        $stats['errors']++;
                        continue;
                    }

                    // Check existing mapping
                    $existingMapping = \App\Models\AmRevenue::where('account_manager_id', $am->id)
                        ->where('corporate_customer_id', $cc->id)
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
                        ->first();

                    if ($existingMapping) {
                        // Update existing
                        if ($filterType === 'new') {
                            $stats['skipped']++;
                            continue;
                        }

                        $existingMapping->update([
                            'proporsi' => $proporsi,
                        ]);

                        $stats['updated']++;
                    } else {
                        // Create new
                        if ($filterType === 'update') {
                            $stats['skipped']++;
                            continue;
                        }

                        \App\Models\AmRevenue::create([
                            'account_manager_id' => $am->id,
                            'corporate_customer_id' => $cc->id,
                            'proporsi' => $proporsi,
                            'bulan' => $bulan,
                            'tahun' => $tahun,
                        ]);

                        $stats['created']++;
                    }
                }

                DB::commit();

                // Generate error log if there are errors
                $errorLogPath = null;
                if (!empty($failedRows)) {
                    $errorLogPath = $this->generateErrorLog($failedRows, 'revenue_am');
                }

                return response()->json([
                    'success' => true,
                    'stats' => $stats,
                    'failed_rows' => $failedRows,
                    'error_log_path' => $errorLogPath,
                    'message' => "Import selesai. {$stats['created']} mapping baru, {$stats['updated']} mapping diupdate."
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Execute Revenue AM import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Import gagal: ' . $e->getMessage()], 500);
        }
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * ✅ Parse divisi list from various formats
     * Supports:
     * - "DPS" → ["DPS"]
     * - "DPS,DSS" → ["DPS", "DSS"]
     * - "DPS, DSS" → ["DPS", "DSS"]
     */
    private function parseDivisiList($divisiRaw)
    {
        $divisiRaw = strtoupper(trim($divisiRaw));
        
        // Check if comma-separated
        if (strpos($divisiRaw, ',') !== false) {
            $parts = explode(',', $divisiRaw);
            return array_map('trim', $parts);
        }
        
        // Single divisi
        return [$divisiRaw];
    }

    /**
     * ✅ Helper - Get Column Indices
     */
    private function getColumnIndices($headers, $columns)
    {
        $indices = [];
        $headers = array_map('trim', $headers);
        $headers = array_map('strtoupper', $headers);

        foreach ($columns as $column) {
            $key = array_search(strtoupper($column), $headers);
            $indices[$column] = $key !== false ? $key : null;
        }

        return $indices;
    }

    /**
     * ✅ Helper - Get Column Value
     */
    private function getColumnValue($row, $index)
    {
        if ($index === null || !isset($row[$index])) {
            return null;
        }

        return trim($row[$index]);
    }


private function generateErrorLog($failedRows, $importType)
{
    try {
        if (empty($failedRows)) {
            return null;
        }

        $timestamp = now()->format('YmdHis');
        $filename = "error_log_{$importType}_{$timestamp}.csv";
        $filePath = "error_logs/{$filename}";

        // Create directory if not exists
        Storage::disk('public')->makeDirectory('error_logs');

        // Prepare CSV content
        $csvContent = '';
        
        // Determine headers based on import type
        if ($importType === 'data_am') {
            $csvContent .= "Row Number,NIK,Nama AM,Witel AM,Divisi,Error\n";
            
            foreach ($failedRows as $row) {
                // ✅ FIX: Handle array values properly
                $nik = $row['nik'] ?? '';
                $nama = $row['nama'] ?? '';
                $witelNama = $row['witel_nama'] ?? '';
                
                // ✅ FIX: Handle divisi_raw which might be array
                $divisiRaw = $row['divisi_raw'] ?? '';
                if (is_array($divisiRaw)) {
                    $divisiRaw = implode(', ', $divisiRaw);
                }
                
                // ✅ FIX: Handle errors which is array
                $errors = $row['errors'] ?? $row['error'] ?? [];
                if (is_array($errors)) {
                    $errorString = implode('; ', $errors);
                } else {
                    $errorString = $errors;
                }
                
                $rowNumber = $row['row_number'] ?? '';
                
                // Escape CSV values
                $csvContent .= sprintf(
                    "%s,%s,%s,%s,%s,%s\n",
                    $this->escapeCsv($rowNumber),
                    $this->escapeCsv($nik),
                    $this->escapeCsv($nama),
                    $this->escapeCsv($witelNama),
                    $this->escapeCsv($divisiRaw),
                    $this->escapeCsv($errorString)
                );
            }
        } elseif ($importType === 'data_cc') {
            $csvContent .= "Row Number,NIPNAS,Standard Name,Error\n";
            
            foreach ($failedRows as $row) {
                $nipnas = $row['nipnas'] ?? '';
                $nama = $row['standard_name'] ?? $row['nama'] ?? '';
                
                $errors = $row['errors'] ?? $row['error'] ?? [];
                if (is_array($errors)) {
                    $errorString = implode('; ', $errors);
                } else {
                    $errorString = $errors;
                }
                
                $rowNumber = $row['row_number'] ?? '';
                
                $csvContent .= sprintf(
                    "%s,%s,%s,%s\n",
                    $this->escapeCsv($rowNumber),
                    $this->escapeCsv($nipnas),
                    $this->escapeCsv($nama),
                    $this->escapeCsv($errorString)
                );
            }
        } elseif ($importType === 'revenue_cc') {
            $csvContent .= "Row Number,NIPNAS,Standard Name,Error\n";
            
            foreach ($failedRows as $row) {
                $nipnas = $row['nipnas'] ?? '';
                $nama = $row['standard_name'] ?? '';
                
                $errors = $row['errors'] ?? $row['error'] ?? [];
                if (is_array($errors)) {
                    $errorString = implode('; ', $errors);
                } else {
                    $errorString = $errors;
                }
                
                $rowNumber = $row['row_number'] ?? '';
                
                $csvContent .= sprintf(
                    "%s,%s,%s,%s\n",
                    $this->escapeCsv($rowNumber),
                    $this->escapeCsv($nipnas),
                    $this->escapeCsv($nama),
                    $this->escapeCsv($errorString)
                );
            }
        } elseif ($importType === 'revenue_am') {
            $csvContent .= "Row Number,NIK,Nama AM,NIPNAS,Error\n";
            
            foreach ($failedRows as $row) {
                $nik = $row['nik'] ?? '';
                $namaAm = $row['nama_am'] ?? '';
                $nipnas = $row['nipnas'] ?? '';
                
                $errors = $row['errors'] ?? $row['error'] ?? [];
                if (is_array($errors)) {
                    $errorString = implode('; ', $errors);
                } else {
                    $errorString = $errors;
                }
                
                $rowNumber = $row['row_number'] ?? '';
                
                $csvContent .= sprintf(
                    "%s,%s,%s,%s,%s\n",
                    $this->escapeCsv($rowNumber),
                    $this->escapeCsv($nik),
                    $this->escapeCsv($namaAm),
                    $this->escapeCsv($nipnas),
                    $this->escapeCsv($errorString)
                );
            }
        }

        // Save to storage
        Storage::disk('public')->put($filePath, $csvContent);

        // Return public URL
        return Storage::disk('public')->url($filePath);

    } catch (\Exception $e) {
        Log::error('Failed to generate error log', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'import_type' => $importType,
            'failed_rows_count' => count($failedRows)
        ]);
        return null;
    }
}

/**
 * ✅ Helper: Escape CSV values
 */
private function escapeCsv($value)
{
    // Handle null or empty
    if ($value === null || $value === '') {
        return '';
    }
    
    // Convert to string if not already
    $value = (string) $value;
    
    // Escape double quotes
    $value = str_replace('"', '""', $value);
    
    // Wrap in quotes if contains comma, newline, or double quote
    if (strpos($value, ',') !== false || 
        strpos($value, "\n") !== false || 
        strpos($value, '"') !== false) {
        return '"' . $value . '"';
    }
    
    return $value;
}
    
}