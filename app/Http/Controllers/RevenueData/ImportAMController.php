<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\AccountManager;
use App\Models\CorporateCustomer;
use App\Models\CcRevenue;
use App\Models\AmRevenue;
use App\Models\Witel;
use App\Models\Divisi;
use App\Models\Telda;
use Carbon\Carbon;

/**
 * ============================================================================
 * ImportAMController - V2 WITH AUTO REVENUE CALCULATION
 * ============================================================================
 * 
 * Date: 2026-02-05
 * Version: 3.1 - AUTO REVENUE CALCULATION
 * 
 * NEW FEATURES:
 * ✅ AUTO-CALCULATE target_revenue & real_revenue from CC Revenue
 * ✅ Formula: CC.real_revenue_sold × proporsi = AM.real_revenue
 * ✅ Formula: CC.target_revenue_sold × proporsi = AM.target_revenue
 * 
 * CRITICAL FIXES (from V3.0):
 * ✅ Template downloads as CSV (not Excel)
 * ✅ Revenue AM template has NO BULAN/TAHUN columns
 * ✅ executeRevenueAM() accepts year/month from form parameters
 * ✅ Business rule validation for telda_id (AM vs HOTDA)
 * ✅ Consistent error response format
 * 
 * BUSINESS RULES:
 * - AM role: telda_id MUST be NULL
 * - HOTDA role: telda_id MUST NOT be NULL
 * - Proporsi: 0.0 - 1.0 decimal
 * - Total proporsi per CC = 1.0
 * - Revenue auto-calculated from CC Revenue
 * 
 * @author RLEGS Team
 * @version 3.1 - Auto Revenue Calculation
 * ============================================================================
 */
class ImportAMController extends Controller
{
    /**
     * Download Template as CSV (NOT EXCEL)
     */
    public function downloadTemplate($type)
    {
        try {
            if ($type === 'data-am') {
                $filename = 'Template_Data_AM_' . date('Ymd_His') . '.csv';
                
                $csvContent = "NIK,NAMA AM,WITEL AM,DIVISI AM,REGIONAL,DIVISI,TELDA\n";
                $csvContent .= "404482,I WAYAN AGUS SUANTARA,BALI,AM,TREG 3,DPS,\n";
                $csvContent .= "970252,DESY CAHYANI LARI,NUSA TENGGARA,HOTDA,TREG 3,DPS,TELDA NUSA TENGGARA\n";
                $csvContent .= "123456,NI NYOMAN DANI,SEMARANG,AM,TREG 3,\"DPS,DSS\",\n";
                $csvContent .= "789012,MADE WIRAWAN,BALI,AM,TREG 3,\"DPS, DSS\",\n";
                $csvContent .= "\n";
                $csvContent .= "CATATAN PENTING:\n";
                $csvContent .= "1. Untuk AM dengan multiple divisi gunakan format: DPS,DSS atau DPS, DSS\n";
                $csvContent .= "2. Bisa juga dengan baris terpisah (NIK sama divisi beda)\n";
                $csvContent .= "3. DIVISI AM: AM atau HOTDA\n";
                $csvContent .= "4. DIVISI: DGS, DSS, atau DPS (bisa kombinasi dengan koma)\n";
                $csvContent .= "5. TELDA: Wajib diisi untuk HOTDA, kosongkan untuk AM\n";
                
                return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                    
            } elseif ($type === 'revenue-am') {
                $filename = 'Template_Revenue_AM_' . date('Ymd_His') . '.csv';
                
                $csvContent = "NIK,NAMA AM,PROPORSI,NIPNAS,STANDARD NAME\n";
                $csvContent .= "404482,I WAYAN AGUS SUANTARA,0.5,76590001,BANK JATIM\n";
                $csvContent .= "970252,DESY CAHYANI LARI,0.5,76590001,BANK JATIM\n";
                $csvContent .= "404482,I WAYAN AGUS SUANTARA,1.0,76590002,PEMKOT SEMARANG\n";
                $csvContent .= "\n";
                $csvContent .= "CATATAN PENTING:\n";
                $csvContent .= "1. Pilih Bulan & Tahun dari form import (TIDAK PERLU di file CSV)\n";
                $csvContent .= "2. Total proporsi untuk 1 CC harus = 1.0 (100%)\n";
                $csvContent .= "3. Jika CC dihandle 1 AM proporsi = 1.0\n";
                $csvContent .= "4. Jika CC dihandle 2 AM proporsi masing-masing dijumlahkan harus = 1.0\n";
                $csvContent .= "5. Revenue AM dihitung OTOMATIS dari: CC Revenue Sold x Proporsi\n";
                $csvContent .= "6. Tidak perlu isi Target Revenue / Real Revenue (auto-calculated)\n";
                
                return response($csvContent, 200)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                    
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid template type'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to generate CSV template', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview Data AM import (NO CHANGES - Same as V3.0)
     */
    public function previewDataAM($tempFilePath)
    {
        try {
            if (!Storage::disk('local')->exists($tempFilePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            $fullPath = Storage::disk('local')->path($tempFilePath);
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if (empty($data) || count($data) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'File kosong atau tidak memiliki data'
                ], 400);
            }

            $expectedHeaders = ['NIK', 'NAMA AM', 'WITEL AM', 'DIVISI AM', 'REGIONAL', 'DIVISI', 'TELDA'];
            $actualHeaders = array_map('trim', array_map('strtoupper', $data[0]));

            $missingHeaders = array_diff($expectedHeaders, $actualHeaders);
            if (!empty($missingHeaders)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Header tidak sesuai. Header yang hilang: ' . implode(', ', $missingHeaders)
                ], 400);
            }

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

            $columnIndices = $this->getColumnIndices($actualHeaders, $expectedHeaders);

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

                if (empty($nik)) $rowErrors[] = 'NIK kosong';
                if (empty($nama)) $rowErrors[] = 'Nama AM kosong';
                if (empty($witelNama)) $rowErrors[] = 'Witel AM kosong';
                if (empty($divisiRaw)) $rowErrors[] = 'Divisi kosong';

                $witel = Witel::where('nama', 'LIKE', '%' . $witelNama . '%')->first();
                if (!$witel) {
                    $rowErrors[] = "Witel '$witelNama' tidak ditemukan";
                }

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

                $isMultiDivisi = count($divisiIds) > 1;
                if ($isMultiDivisi) {
                    $stats['multi_divisi_ams']++;
                }

                $existingAM = AccountManager::where('nik', $nik)->first();
                $status = $existingAM ? 'update' : 'new';
                
                if ($existingAM) {
                    $stats['existing_ams']++;
                } else {
                    $stats['new_ams']++;
                }

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

            $errorLogPath = null;
            if (!empty($failedRows)) {
                $errorLogPath = $this->generateErrorLog($failedRows, 'data_am');
            }

            return response()->json([
                'success' => true,
                'preview' => $previewData,
                'stats' => $stats,
                'failed_rows' => $failedRows,
                'has_errors' => !empty($failedRows),
                'error_log_path' => $errorLogPath
            ]);

        } catch (\Exception $e) {
            Log::error('Preview Data AM failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Preview gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute Data AM import (NO CHANGES - Same as V3.0)
     * Keeping this method unchanged - already working correctly
     */
    public function executeDataAM($tempFilePath, $filterType = 'all')
    {
        try {
            if (!Storage::disk('local')->exists($tempFilePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            $fullPath = Storage::disk('local')->path($tempFilePath);
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if (empty($data) || count($data) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'File kosong'
                ], 400);
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

            $actualHeaders = array_map('trim', array_map('strtoupper', $data[0]));
            $expectedHeaders = ['NIK', 'NAMA AM', 'WITEL AM', 'DIVISI AM', 'REGIONAL', 'DIVISI', 'TELDA'];
            $columnIndices = $this->getColumnIndices($actualHeaders, $expectedHeaders);

            DB::beginTransaction();

            try {
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
                    
                    $firstRow = $nikRows[0]['row'];
                    $nama = trim($this->getColumnValue($firstRow, $columnIndices['NAMA AM']));
                    $witelNama = trim($this->getColumnValue($firstRow, $columnIndices['WITEL AM']));
                    $divisiAm = trim($this->getColumnValue($firstRow, $columnIndices['DIVISI AM']));
                    $regional = trim($this->getColumnValue($firstRow, $columnIndices['REGIONAL']));
                    $teldaRaw = trim($this->getColumnValue($firstRow, $columnIndices['TELDA']));

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

                    $witel = Witel::where('nama', 'LIKE', '%' . $witelNama . '%')->first();
                    if (!$witel) {
                        $failedRows[] = [
                            'row_number' => $nikRows[0]['row_number'],
                            'nik' => $nik,
                            'errors' => ["Witel '$witelNama' tidak ditemukan"]
                        ];
                        $stats['errors']++;
                        continue;
                    }

                    $teldaId = null;
                    if (!empty($teldaRaw)) {
                        $telda = Telda::where('nama', 'LIKE', '%' . $teldaRaw . '%')->first();
                        if ($telda) {
                            $teldaId = $telda->id;
                        }
                    }

                    $existingAM = AccountManager::where('nik', $nik)->first();
                    
                    if ($existingAM) {
                        if ($filterType === 'new') {
                            $stats['skipped']++;
                            continue;
                        }

                        $existingAM->update([
                            'nama' => $nama,
                            'witel_id' => $witel->id,
                            'role' => $divisiAm ?: 'AM',
                            'regional' => $regional,
                            'telda_id' => $teldaId,
                        ]);

                        $stats['updated']++;
                    } else {
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
                            'telda_id' => $teldaId,
                        ]);

                        $stats['created']++;
                    }

                    if (!empty($allDivisiIds)) {
                        $existingAM->divisis()->sync($allDivisiIds);
                        
                        if (count($allDivisiIds) > 1) {
                            $stats['multi_divisi_processed']++;
                        }
                    }
                }

                DB::commit();

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
            
            return response()->json([
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview Revenue AM import (NO CHANGES)
     */
    public function previewRevenueAM($tempFilePath, $year = null, $month = null)
    {
        try {
            if (!Storage::disk('local')->exists($tempFilePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            $fullPath = Storage::disk('local')->path($tempFilePath);
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if (empty($data) || count($data) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'File kosong atau tidak memiliki data'
                ], 400);
            }

            $expectedHeaders = ['NIK', 'NAMA AM', 'PROPORSI', 'NIPNAS', 'STANDARD NAME'];
            $actualHeaders = array_map('trim', array_map('strtoupper', $data[0]));

            $missingHeaders = array_diff($expectedHeaders, $actualHeaders);
            if (!empty($missingHeaders)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Header tidak sesuai. Header yang hilang: ' . implode(', ', $missingHeaders)
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

            $columnIndices = $this->getColumnIndices($actualHeaders, $expectedHeaders);

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
                
                $bulan = $month;
                $tahun = $year;

                $rowErrors = [];

                if (empty($nik)) $rowErrors[] = 'NIK kosong';
                if (empty($nipnas)) $rowErrors[] = 'NIPNAS kosong';
                if ($proporsi <= 0 || $proporsi > 1) $rowErrors[] = 'Proporsi harus antara 0.01 - 1.0';

                if (!$year || $year < 2020 || $year > 2100) {
                    $rowErrors[] = 'Tahun tidak valid (harus antara 2020-2100)';
                }
                if (!$month || $month < 1 || $month > 12) {
                    $rowErrors[] = 'Bulan tidak valid (harus antara 1-12)';
                }

                $am = AccountManager::where('nik', $nik)->first();
                if (!$am) {
                    $rowErrors[] = "AM dengan NIK '$nik' tidak ditemukan";
                }

                $cc = CorporateCustomer::where('nipnas', $nipnas)->first();
                if (!$cc) {
                    $rowErrors[] = "CC dengan NIPNAS '$nipnas' tidak ditemukan";
                }

                $existingMapping = null;
                if ($am && $cc && $year && $month) {
                    $existingMapping = AmRevenue::where('account_manager_id', $am->id)
                        ->where('corporate_customer_id', $cc->id)
                        ->where('bulan', $month)
                        ->where('tahun', $year)
                        ->first();
                    
                    if ($existingMapping) {
                        $stats['existing_mappings']++;
                    } else {
                        $stats['new_mappings']++;
                    }
                }

                $status = $existingMapping ? 'update' : 'new';

                $ccKey = "{$nipnas}_{$month}_{$year}";
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
                    'bulan' => $month,
                    'tahun' => $year,
                    'status' => $status,
                    'errors' => $rowErrors,
                    'valid' => empty($rowErrors)
                ];

                $previewData[] = $previewRow;

                if (!empty($rowErrors)) {
                    $failedRows[] = array_merge($previewRow, ['error' => implode('; ', $rowErrors)]);
                }
            }

            foreach ($ccProporsiMap as $ccKey => $totalProporsi) {
                if (abs($totalProporsi - 1.0) > 0.01) {
                    $stats['invalid_proporsi']++;
                    list($nipnas, $bulan, $tahun) = explode('_', $ccKey);
                    
                    Log::warning("Proporsi total for CC {$nipnas} in {$tahun}-{$bulan} is {$totalProporsi} (should be 1.0)");
                    
                    $failedRows[] = [
                        'row_number' => 'Multiple',
                        'nipnas' => $nipnas,
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'errors' => ["Total proporsi = $totalProporsi (harus = 1.0)"]
                    ];
                }
            }

            $errorLogPath = null;
            if (!empty($failedRows)) {
                $errorLogPath = $this->generateErrorLog($failedRows, 'revenue_am');
            }

            return response()->json([
                'success' => true,
                'preview' => $previewData,
                'stats' => $stats,
                'failed_rows' => $failedRows,
                'has_errors' => !empty($failedRows),
                'error_log_path' => $errorLogPath
            ]);

        } catch (\Exception $e) {
            Log::error('Preview Revenue AM failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Preview gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ============================================================================
     * ✅ NEW: Execute Revenue AM with AUTO REVENUE CALCULATION
     * ============================================================================
     * 
     * CRITICAL NEW FEATURE:
     * - Auto-calculate target_revenue = CC.target_revenue_sold × proporsi
     * - Auto-calculate real_revenue = CC.real_revenue_sold × proporsi
     * - No need to input revenue manually in CSV
     * 
     * @param string $tempFilePath
     * @param int $year
     * @param int $month
     * @param string $filterType
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeRevenueAM($tempFilePath, $year, $month, $filterType = 'all')
    {
        try {
            if (!Storage::disk('local')->exists($tempFilePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            $fullPath = Storage::disk('local')->path($tempFilePath);
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if (empty($data) || count($data) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'File kosong'
                ], 400);
            }

            $stats = [
                'total_processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];

            $failedRows = [];

            $actualHeaders = array_map('trim', array_map('strtoupper', $data[0]));
            $expectedHeaders = ['NIK', 'NAMA AM', 'PROPORSI', 'NIPNAS', 'STANDARD NAME'];
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
                    
                    $bulan = $month;
                    $tahun = $year;

                    // Find AM
                    $am = AccountManager::where('nik', $nik)->first();
                    if (!$am) {
                        $failedRows[] = [
                            'row_number' => $actualRowNumber,
                            'nik' => $nik,
                            'nipnas' => $nipnas,
                            'errors' => ["AM dengan NIK '$nik' tidak ditemukan"]
                        ];
                        $stats['errors']++;
                        continue;
                    }

                    // Find CC
                    $cc = CorporateCustomer::where('nipnas', $nipnas)->first();
                    if (!$cc) {
                        $failedRows[] = [
                            'row_number' => $actualRowNumber,
                            'nik' => $nik,
                            'nipnas' => $nipnas,
                            'errors' => ["CC dengan NIPNAS '$nipnas' tidak ditemukan"]
                        ];
                        $stats['errors']++;
                        continue;
                    }

                    // ============================================================================
                    // ✅ CRITICAL NEW: Find CC Revenue untuk periode ini
                    // ============================================================================
                    $ccRevenue = CcRevenue::where('corporate_customer_id', $cc->id)
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
                        ->first();

                    if (!$ccRevenue) {
                        $failedRows[] = [
                            'row_number' => $actualRowNumber,
                            'nik' => $nik,
                            'nipnas' => $nipnas,
                            'errors' => ["CC Revenue untuk NIPNAS '$nipnas' periode {$tahun}-{$bulan} tidak ditemukan. Import Revenue CC terlebih dahulu."]
                        ];
                        $stats['errors']++;
                        continue;
                    }

                    // ============================================================================
                    // ✅ CRITICAL NEW: Auto-calculate revenue berdasarkan proporsi
                    // ============================================================================
                    $calculatedTargetRevenue = $ccRevenue->target_revenue_sold * $proporsi;
                    $calculatedRealRevenue = $ccRevenue->real_revenue_sold * $proporsi;

                    Log::info('✅ Calculated revenues for AM mapping', [
                        'am_id' => $am->id,
                        'am_nik' => $nik,
                        'cc_id' => $cc->id,
                        'cc_nipnas' => $nipnas,
                        'cc_target_sold' => $ccRevenue->target_revenue_sold,
                        'cc_real_sold' => $ccRevenue->real_revenue_sold,
                        'proporsi' => $proporsi,
                        'calculated_target' => $calculatedTargetRevenue,
                        'calculated_real' => $calculatedRealRevenue
                    ]);

                    // ============================================================================
                    // ✅ BUSINESS RULE: telda_id validation
                    // ============================================================================
                    $teldaId = null;
                    
                    if ($am->role === 'HOTDA') {
                        $teldaId = $am->telda_id;
                        
                        if (!$teldaId) {
                            Log::warning('HOTDA without telda_id - SKIPPING', [
                                'am_id' => $am->id,
                                'nik' => $am->nik,
                                'nama' => $am->nama,
                                'role' => $am->role
                            ]);
                            
                            $failedRows[] = [
                                'row_number' => $actualRowNumber,
                                'nik' => $nik,
                                'nipnas' => $nipnas,
                                'errors' => ['HOTDA harus memiliki TELDA assignment. Update data AM terlebih dahulu.']
                            ];
                            $stats['errors']++;
                            continue;
                        }
                        
                        Log::info('HOTDA mapping with telda_id', [
                            'am_id' => $am->id,
                            'nik' => $nik,
                            'telda_id' => $teldaId,
                            'cc_id' => $cc->id
                        ]);
                        
                    } else if ($am->role === 'AM') {
                        $teldaId = null;
                        
                        Log::info('AM mapping without telda_id', [
                            'am_id' => $am->id,
                            'nik' => $nik,
                            'role' => 'AM',
                            'cc_id' => $cc->id
                        ]);
                    } else {
                        $teldaId = null;
                        
                        Log::warning('Unknown role, treating as AM (no telda)', [
                            'am_id' => $am->id,
                            'nik' => $nik,
                            'role' => $am->role
                        ]);
                    }

                    // Check existing mapping
                    $existingMapping = AmRevenue::where('account_manager_id', $am->id)
                        ->where('corporate_customer_id', $cc->id)
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
                        ->first();

                    if ($existingMapping) {
                        // Update existing mapping
                        if ($filterType === 'new') {
                            $stats['skipped']++;
                            continue;
                        }

                        try {
                            $existingMapping->update([
                                'proporsi' => $proporsi,
                                'telda_id' => $teldaId,
                                'target_revenue' => $calculatedTargetRevenue,  // ✅ AUTO-CALCULATED
                                'real_revenue' => $calculatedRealRevenue,      // ✅ AUTO-CALCULATED
                            ]);

                            $stats['updated']++;
                            
                        } catch (\Exception $e) {
                            Log::error('Failed to update AmRevenue', [
                                'am_id' => $am->id,
                                'cc_id' => $cc->id,
                                'error' => $e->getMessage()
                            ]);
                            
                            $failedRows[] = [
                                'row_number' => $actualRowNumber,
                                'nik' => $nik,
                                'nipnas' => $nipnas,
                                'errors' => ['Gagal update mapping: ' . $e->getMessage()]
                            ];
                            $stats['errors']++;
                            continue;
                        }
                        
                    } else {
                        // Create new mapping
                        if ($filterType === 'update') {
                            $stats['skipped']++;
                            continue;
                        }

                        try {
                            AmRevenue::create([
                                'account_manager_id' => $am->id,
                                'corporate_customer_id' => $cc->id,
                                'proporsi' => $proporsi,
                                'bulan' => $bulan,
                                'tahun' => $tahun,
                                'telda_id' => $teldaId,
                                'target_revenue' => $calculatedTargetRevenue,  // ✅ AUTO-CALCULATED
                                'real_revenue' => $calculatedRealRevenue,      // ✅ AUTO-CALCULATED
                            ]);

                            $stats['created']++;
                            
                        } catch (\Exception $e) {
                            Log::error('Failed to create AmRevenue', [
                                'am_id' => $am->id,
                                'cc_id' => $cc->id,
                                'telda_id' => $teldaId,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                            
                            $failedRows[] = [
                                'row_number' => $actualRowNumber,
                                'nik' => $nik,
                                'nipnas' => $nipnas,
                                'errors' => ['Gagal membuat mapping: ' . $e->getMessage()]
                            ];
                            $stats['errors']++;
                            continue;
                        }
                    }
                }

                DB::commit();

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
            
            return response()->json([
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    // ============================================================================
    // HELPER METHODS (NO CHANGES)
    // ============================================================================

    private function parseDivisiList($divisiRaw)
    {
        $divisiRaw = strtoupper(trim($divisiRaw));
        
        if (strpos($divisiRaw, ',') !== false) {
            $parts = explode(',', $divisiRaw);
            return array_map('trim', $parts);
        }
        
        return [$divisiRaw];
    }

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

            Storage::disk('public')->makeDirectory('error_logs');

            $csvContent = '';
            
            if ($importType === 'data_am') {
                $csvContent .= "Row Number,NIK,Nama AM,Witel AM,Divisi,Error\n";
                
                foreach ($failedRows as $row) {
                    $nik = $row['nik'] ?? '';
                    $nama = $row['nama'] ?? '';
                    $witelNama = $row['witel_nama'] ?? '';
                    
                    $divisiRaw = $row['divisi_raw'] ?? '';
                    if (is_array($divisiRaw)) {
                        $divisiRaw = implode(', ', $divisiRaw);
                    }
                    
                    $errors = $row['errors'] ?? $row['error'] ?? [];
                    if (is_array($errors)) {
                        $errorString = implode('; ', $errors);
                    } else {
                        $errorString = $errors;
                    }
                    
                    $rowNumber = $row['row_number'] ?? '';
                    
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
                
            } elseif ($importType === 'revenue_am') {
                $csvContent .= "Row Number,NIK,Nama AM,NIPNAS,Error\n";
                
                foreach ($failedRows as $row) {
                    $nik = $row['nik'] ?? '';
                    $namaAm = $row['nama'] ?? '';
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

            Storage::disk('public')->put($filePath, $csvContent);

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

    private function escapeCsv($value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        
        $value = (string) $value;
        $value = str_replace('"', '""', $value);
        
        if (strpos($value, ',') !== false || 
            strpos($value, "\n") !== false || 
            strpos($value, '"') !== false) {
            return '"' . $value . '"';
        }
        
        return $value;
    }
}