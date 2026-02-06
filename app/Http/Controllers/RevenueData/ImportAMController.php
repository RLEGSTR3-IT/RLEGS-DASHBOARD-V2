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
 * ImportAMController - V3.2 COMPLETE FIXED
 * ============================================================================
 * 
 * Date: 2026-02-06
 * Version: 3.2 - CRITICAL FIXES
 * 
 * CRITICAL FIXES:
 * ✅ Changed Storage::disk('local')->exists() to file_exists() for absolute paths
 * ✅ Fixed file path checking in ALL methods (preview & execute)
 * ✅ Enhanced multi-divisi handling in account_manager_divisi table
 * ✅ Supports 3 divisi input formats:
 *    1. "DPS, DSS" (comma with space)
 *    2. "DPS,DSS" (comma without space)
 *    3. Multiple rows with same NIK but different DIVISI
 * ✅ Auto-sync account_manager_divisi pivot table
 * 
 * BUSINESS RULES:
 * - AM role: telda_id MUST be NULL
 * - HOTDA role: telda_id MUST NOT be NULL
 * - Proporsi: 0.0 - 1.0 decimal
 * - Total proporsi per CC = 1.0
 * - Revenue auto-calculated from CC Revenue
 * - Max 3 divisi per AM (DGS, DPS, DSS)
 * 
 * @author RLEGS Team
 * @version 3.2 - Complete Fixed
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
     * ============================================================================
     * ✅ FIXED: Preview Data AM - Absolute Path Support
     * ============================================================================
     */
    public function previewDataAM($tempFilePath)
    {
        try {
            Log::info('IAMC - Preview Data AM called', [
                'temp_file_path' => $tempFilePath,
                'file_exists' => file_exists($tempFilePath)
            ]);

            // ✅ FIX: Use file_exists() instead of Storage::disk('local')->exists()
            if (!file_exists($tempFilePath)) {
                Log::error('IAMC - File not found', [
                    'expected_path' => $tempFilePath
                ]);
                
                return [
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ];
            }

            // ✅ Use $tempFilePath directly (it's already absolute path)
            $spreadsheet = IOFactory::load($tempFilePath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if (empty($data) || count($data) < 2) {
                return [
                    'success' => false,
                    'message' => 'File kosong atau tidak memiliki data'
                ];
            }

            $expectedHeaders = ['NIK', 'NAMA AM', 'WITEL AM', 'DIVISI AM', 'REGIONAL', 'DIVISI', 'TELDA'];
            $actualHeaders = array_map('trim', array_map('strtoupper', $data[0]));

            $missingHeaders = array_diff($expectedHeaders, $actualHeaders);
            if (!empty($missingHeaders)) {
                return [
                    'success' => false,
                    'message' => 'Header tidak sesuai. Header yang hilang: ' . implode(', ', $missingHeaders)
                ];
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
                'unique_am_count' => 0
            ];

            $columnIndices = $this->getColumnIndices($actualHeaders, $expectedHeaders);

            // ✅ ENHANCED: Group by NIK first to handle multi-divisi
            $nikOccurrences = [];
            $uniqueNiks = [];
            
            foreach (array_slice($data, 1) as $rowIndex => $row) {
                if (empty(array_filter($row))) continue;
                
                $nik = trim($this->getColumnValue($row, $columnIndices['NIK']));
                if (!empty($nik)) {
                    if (!isset($nikOccurrences[$nik])) {
                        $nikOccurrences[$nik] = 0;
                        $uniqueNiks[] = $nik;
                    }
                    $nikOccurrences[$nik]++;
                }
            }

            $stats['unique_am_count'] = count($uniqueNiks);

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

                // ✅ Parse divisi list (supports comma-separated)
                $divisiList = $this->parseDivisiList($divisiRaw);
                $divisiIds = [];
                $divisiNames = [];
                
                // ✅ Validate max 3 divisi
                if (count($divisiList) > 3) {
                    $rowErrors[] = 'Maksimal 3 divisi per AM (DGS, DPS, DSS)';
                }
                
                foreach ($divisiList as $divisiCode) {
                    $divisi = Divisi::where('kode', strtoupper($divisiCode))->first();
                    if ($divisi) {
                        if (!in_array($divisi->id, $divisiIds)) {
                            $divisiIds[] = $divisi->id;
                            $divisiNames[] = $divisi->nama;
                        }
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
                    'divisi_count' => count($divisiIds),
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

            Log::info('IAMC - Preview completed', [
                'stats' => $stats,
                'preview_count' => count($previewData)
            ]);

            return [
                'success' => true,
                'preview' => $previewData,
                'stats' => $stats,
                'failed_rows' => $failedRows,
                'has_errors' => !empty($failedRows),
                'error_log_path' => $errorLogPath
            ];

        } catch (\Exception $e) {
            Log::error('Preview Data AM failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Preview gagal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ============================================================================
     * ✅ FIXED: Execute Data AM - Absolute Path Support + Enhanced Multi-Divisi
     * ============================================================================
     */
    public function executeDataAM($tempFilePath, $filterType = 'all')
    {
        try {
            Log::info('IAMC - Execute Data AM called', [
                'temp_file_path' => $tempFilePath,
                'filter_type' => $filterType,
                'file_exists' => file_exists($tempFilePath)
            ]);

            // ✅ FIX: Use file_exists() instead of Storage::disk('local')->exists()
            if (!file_exists($tempFilePath)) {
                Log::error('IAMC - File not found', [
                    'expected_path' => $tempFilePath
                ]);
                
                return [
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ];
            }

            // ✅ Use $tempFilePath directly (it's already absolute path)
            $spreadsheet = IOFactory::load($tempFilePath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if (empty($data) || count($data) < 2) {
                return [
                    'success' => false,
                    'message' => 'File kosong'
                ];
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
                // ✅ ENHANCED: Group rows by NIK to handle multi-divisi properly
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

                Log::info('IAMC - Grouped by NIK', [
                    'unique_niks' => count($nikGroups),
                    'total_rows' => array_sum(array_map('count', $nikGroups))
                ]);

                // ✅ Process each unique NIK
                foreach ($nikGroups as $nik => $nikRows) {
                    $stats['total_processed']++;
                    
                    // Use first row for AM master data
                    $firstRow = $nikRows[0]['row'];
                    $nama = trim($this->getColumnValue($firstRow, $columnIndices['NAMA AM']));
                    $witelNama = trim($this->getColumnValue($firstRow, $columnIndices['WITEL AM']));
                    $divisiAm = trim($this->getColumnValue($firstRow, $columnIndices['DIVISI AM']));
                    $regional = trim($this->getColumnValue($firstRow, $columnIndices['REGIONAL']));
                    $teldaRaw = trim($this->getColumnValue($firstRow, $columnIndices['TELDA']));

                    // ✅ ENHANCED: Collect ALL divisi from ALL rows with same NIK
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

                    // ✅ Validate max 3 divisi
                    if (count($allDivisiIds) > 3) {
                        $failedRows[] = [
                            'row_number' => $nikRows[0]['row_number'],
                            'nik' => $nik,
                            'errors' => ['AM tidak boleh tergabung lebih dari 3 divisi']
                        ];
                        $stats['errors']++;
                        continue;
                    }

                    Log::info('IAMC - Processing AM', [
                        'nik' => $nik,
                        'nama' => $nama,
                        'divisi_count' => count($allDivisiIds),
                        'divisi_ids' => $allDivisiIds,
                        'rows_count' => count($nikRows)
                    ]);

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

                        // Update existing AM
                        $existingAM->update([
                            'nama' => $nama,
                            'witel_id' => $witel->id,
                            'role' => $divisiAm ?: 'AM',
                            'regional' => $regional,
                            'telda_id' => $teldaId,
                        ]);

                        Log::info('IAMC - Updated AM', [
                            'am_id' => $existingAM->id,
                            'nik' => $nik
                        ]);

                        $stats['updated']++;
                    } else {
                        if ($filterType === 'update') {
                            $stats['skipped']++;
                            continue;
                        }

                        // Create new AM
                        $existingAM = AccountManager::create([
                            'nik' => $nik,
                            'nama' => $nama,
                            'witel_id' => $witel->id,
                            'role' => $divisiAm ?: 'AM',
                            'regional' => $regional,
                            'telda_id' => $teldaId,
                        ]);

                        Log::info('IAMC - Created AM', [
                            'am_id' => $existingAM->id,
                            'nik' => $nik
                        ]);

                        $stats['created']++;
                    }

                    // ✅ CRITICAL: Sync account_manager_divisi pivot table
                    if (!empty($allDivisiIds)) {
                        Log::info('IAMC - Syncing divisi for AM', [
                            'am_id' => $existingAM->id,
                            'nik' => $nik,
                            'divisi_ids_before' => $existingAM->divisis->pluck('id')->toArray(),
                            'divisi_ids_new' => $allDivisiIds
                        ]);

                        // ✅ CRITICAL FIX: Use syncWithoutDetaching() to ADD new divisi WITHOUT removing old ones
                        // This will:
                        // 1. Keep ALL existing divisi (tidak hapus yang lama)
                        // 2. Add ONLY new divisi yang belum ada
                        // 3. Skip divisi yang sudah ada (no duplicate)
                        $existingAM->divisis()->syncWithoutDetaching($allDivisiIds);
                        
                        // Reload to get fresh count
                        $existingAM->load('divisis');
                        
                        if ($existingAM->divisis()->count() > 1) {
                            $stats['multi_divisi_processed']++;
                        }

                        Log::info('IAMC - Divisi synced successfully (WITHOUT DETACHING)', [
                            'am_id' => $existingAM->id,
                            'nik' => $nik,
                            'divisi_ids_added' => $allDivisiIds,
                            'final_divisi_count' => $existingAM->divisis()->count(),
                            'final_divisi_ids' => $existingAM->divisis->pluck('id')->toArray()
                        ]);
                    }
                }

                DB::commit();

                Log::info('IAMC - Execute completed successfully', [
                    'stats' => $stats
                ]);

                $errorLogPath = null;
                if (!empty($failedRows)) {
                    $errorLogPath = $this->generateErrorLog($failedRows, 'data_am');
                }

                return [
                    'success' => true,
                    'stats' => $stats,
                    'failed_rows' => $failedRows,
                    'error_log_path' => $errorLogPath,
                    'message' => "Import selesai. {$stats['created']} AM baru, {$stats['updated']} AM diupdate."
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Execute Data AM import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ============================================================================
     * ✅ FIXED: Preview Revenue AM - Absolute Path Support
     * ============================================================================
     */
    public function previewRevenueAM($tempFilePath, $year = null, $month = null)
    {
        try {
            Log::info('IAMC - Preview Revenue AM called', [
                'temp_file_path' => $tempFilePath,
                'year' => $year,
                'month' => $month,
                'file_exists' => file_exists($tempFilePath)
            ]);

            // ✅ FIX: Use file_exists() instead of Storage::disk('local')->exists()
            if (!file_exists($tempFilePath)) {
                Log::error('IAMC - File not found', [
                    'expected_path' => $tempFilePath
                ]);
                
                return [
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ];
            }

            // ✅ Use $tempFilePath directly (it's already absolute path)
            $spreadsheet = IOFactory::load($tempFilePath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if (empty($data) || count($data) < 2) {
                return [
                    'success' => false,
                    'message' => 'File kosong atau tidak memiliki data'
                ];
            }

            $expectedHeaders = ['NIK', 'NAMA AM', 'PROPORSI', 'NIPNAS', 'STANDARD NAME'];
            $actualHeaders = array_map('trim', array_map('strtoupper', $data[0]));

            $missingHeaders = array_diff($expectedHeaders, $actualHeaders);
            if (!empty($missingHeaders)) {
                return [
                    'success' => false,
                    'message' => 'Header tidak sesuai. Header yang hilang: ' . implode(', ', $missingHeaders)
                ];
            }

            $previewData = [];
            $failedRows = [];
            $stats = [
                'total_rows' => 0,
                'valid_rows' => 0,
                'new_mappings' => 0,
                'existing_mappings' => 0,
                'invalid_proporsi' => 0,
                'unique_am_count' => 0,
                'unique_cc_count' => 0
            ];

            $columnIndices = $this->getColumnIndices($actualHeaders, $expectedHeaders);

            $ccProporsiMap = [];
            $uniqueAMs = [];
            $uniqueCCs = [];

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

                if ($am && !in_array($am->id, $uniqueAMs)) {
                    $uniqueAMs[] = $am->id;
                }

                if ($cc && !in_array($cc->id, $uniqueCCs)) {
                    $uniqueCCs[] = $cc->id;
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

            $stats['unique_am_count'] = count($uniqueAMs);
            $stats['unique_cc_count'] = count($uniqueCCs);

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

            Log::info('IAMC - Preview completed', [
                'stats' => $stats,
                'preview_count' => count($previewData)
            ]);

            return [
                'success' => true,
                'preview' => $previewData,
                'stats' => $stats,
                'failed_rows' => $failedRows,
                'has_errors' => !empty($failedRows),
                'error_log_path' => $errorLogPath
            ];

        } catch (\Exception $e) {
            Log::error('Preview Revenue AM failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Preview gagal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ============================================================================
     * ✅ FIXED: Execute Revenue AM - Absolute Path + Auto Revenue Calculation
     * ============================================================================
     */
    public function executeRevenueAM($tempFilePath, $year, $month, $filterType = 'all')
    {
        try {
            Log::info('IAMC - Execute Revenue AM called', [
                'temp_file_path' => $tempFilePath,
                'year' => $year,
                'month' => $month,
                'filter_type' => $filterType,
                'file_exists' => file_exists($tempFilePath)
            ]);

            // ✅ FIX: Use file_exists() instead of Storage::disk('local')->exists()
            if (!file_exists($tempFilePath)) {
                Log::error('IAMC - File not found', [
                    'expected_path' => $tempFilePath
                ]);
                
                return [
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ];
            }

            // ✅ Use $tempFilePath directly (it's already absolute path)
            $spreadsheet = IOFactory::load($tempFilePath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if (empty($data) || count($data) < 2) {
                return [
                    'success' => false,
                    'message' => 'File kosong'
                ];
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
                    // ✅ CRITICAL: Find CC Revenue untuk periode ini
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
                    // ✅ CRITICAL: Auto-calculate revenue berdasarkan proporsi
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

                Log::info('IAMC - Execute completed successfully', [
                    'stats' => $stats
                ]);

                $errorLogPath = null;
                if (!empty($failedRows)) {
                    $errorLogPath = $this->generateErrorLog($failedRows, 'revenue_am');
                }

                return [
                    'success' => true,
                    'stats' => $stats,
                    'failed_rows' => $failedRows,
                    'error_log_path' => $errorLogPath,
                    'message' => "Import selesai. {$stats['created']} mapping baru, {$stats['updated']} mapping diupdate."
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Execute Revenue AM import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage()
            ];
        }
    }

    // ============================================================================
    // HELPER METHODS
    // ============================================================================

    /**
     * ✅ ENHANCED: Parse divisi list with multiple format support
     * 
     * Supports:
     * 1. "DPS, DSS" (comma with space)
     * 2. "DPS,DSS" (comma without space)
     * 3. Single divisi "DPS"
     */
    private function parseDivisiList($divisiRaw)
    {
        $divisiRaw = strtoupper(trim($divisiRaw));
        
        // Handle comma-separated (with or without space)
        if (strpos($divisiRaw, ',') !== false) {
            $parts = explode(',', $divisiRaw);
            $parts = array_map('trim', $parts);
            $parts = array_filter($parts); // Remove empty strings
            return array_values($parts);
        }
        
        // Single divisi
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