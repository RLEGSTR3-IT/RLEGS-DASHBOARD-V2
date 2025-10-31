<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * ImportAMController - Account Manager Import Handler
 *
 * FIXED VERSION - 2025-10-31
 *
 * ✅ FIXED: Nama tabel 'witels' → 'witel' (line 273, 284)
 * ✅ FIXED: Response structure untuk preview (data.summary.new_count, data.rows)
 * ✅ MAINTAINED: Semua fungsi existing (downloadTemplate, execute, helper methods)
 *
 * KEY FEATURES:
 * ✅ Hanya validasi kolom WAJIB (NIK, NAMA AM, WITEL AM, DIVISI AM)
 * ✅ Abaikan kolom tambahan (STANDARD NAME, GROUP CONGLO, SEGMEN, dll)
 * ✅ Support kolom opsional (PROPORSI, NIPNAS, DIVISI, TELDA)
 * ✅ Header matching: case-insensitive + trim
 * ✅ Many-to-many divisi support
 * ✅ Preview + Execute pattern with duplicate detection
 *
 * CHANGELOG:
 * - Line 273: DB::table('witels') → DB::table('witel')
 * - Line 284: DB::table('teldas') sudah benar (tidak diubah)
 */
class ImportAMController extends Controller
{
    /**
     * Download Template CSV
     */
    public function downloadTemplate($type)
    {
        $templates = [
            'data-am' => [
                'filename' => 'template_data_am.csv',
                'headers' => ['NIK', 'NAMA AM', 'PROPORSI', 'WITEL AM', 'NIPNAS', 'DIVISI AM', 'DIVISI', 'TELDA'],
                'sample' => [
                    ['123456', 'John Doe', '1', 'BALI', '76590001', 'AM', 'DGS', ''],
                    ['789012', 'Jane Smith', '1', 'JATIM BARAT', '19669082', 'HOTDA', 'DSS', 'TELKOM DAERAH BOJONEGORO'],
                    ['345678', 'Ahmad Multi', '0.5', 'JATIM TIMUR', '4601571', 'AM', 'DGS,DSS', '']
                ]
            ],
            'revenue-am' => [
                'filename' => 'template_revenue_am.csv',
                'headers' => ['YEAR', 'MONTH', 'NIPNAS', 'NIK_AM', 'PROPORSI'],
                'sample' => [
                    ['2025', '12', '76590001', '0001', '60'],
                    ['2025', '12', '76590001', '000', '40'],
                    ['2025', '12', '76590002', 'AM0003', '100']
                ]
            ]
        ];

        if (!isset($templates[$type])) {
            return response()->json([
                'success' => false,
                'message' => 'Template type not found'
            ], 404);
        }

        $template = $templates[$type];

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $template['headers']);
        foreach ($template['sample'] as $row) {
            fputcsv($csv, $row);
        }

        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $template['filename'] . '"',
        ]);
    }

    /**
     * ✅ FIXED: Preview Data AM Import
     */
    public function previewDataAM($tempFilePath)
    {
        try {
            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            // Kolom WAJIB (harus ada)
            $requiredColumns = ['NIK', 'NAMA AM', 'WITEL AM', 'DIVISI AM'];

            // Kolom OPSIONAL (boleh tidak ada)
            $optionalColumns = ['PROPORSI', 'NIPNAS', 'DIVISI', 'TELDA'];

            $headers = array_shift($csvData);

            // Validasi kolom wajib
            if (!$this->validateHeaders($headers, $requiredColumns)) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            // Get indices untuk kolom wajib + opsional
            $allColumns = array_merge($requiredColumns, $optionalColumns);
            $columnIndices = $this->getColumnIndices($headers, $allColumns);

            $newCount = 0;
            $updateCount = 0;
            $errorCount = 0;
            $detailedRows = [];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                // Ambil nilai kolom wajib
                $nik = $this->getColumnValue($row, $columnIndices['NIK']);
                $namaAM = $this->getColumnValue($row, $columnIndices['NAMA AM']);
                $divisiAM = strtoupper(trim($this->getColumnValue($row, $columnIndices['DIVISI AM'])));

                // Validasi kolom wajib tidak boleh kosong
                if (empty($nik) || empty($namaAM) || empty($divisiAM)) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIK' => $nik ?? 'N/A',
                            'NAMA_AM' => $namaAM ?? 'N/A',
                            'ROLE' => $divisiAM ?? 'N/A',
                            'WITEL' => $this->getColumnValue($row, $columnIndices['WITEL AM']) ?? 'N/A'
                        ],
                        'error' => 'NIK, NAMA AM, atau DIVISI AM kosong'
                    ];
                    continue;
                }

                // Validasi DIVISI AM harus AM atau HOTDA
                if (!in_array($divisiAM, ['AM', 'HOTDA'])) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIK' => $nik,
                            'NAMA_AM' => $namaAM,
                            'ROLE' => $divisiAM,
                            'WITEL' => $this->getColumnValue($row, $columnIndices['WITEL AM']) ?? 'N/A'
                        ],
                        'error' => 'DIVISI AM harus AM atau HOTDA'
                    ];
                    continue;
                }

                // Check if AM already exists
                $existingAM = DB::table('account_managers')
                    ->where('nik', $nik)
                    ->first();

                if ($existingAM) {
                    $updateCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'update',
                        'data' => [
                            'NIK' => $nik,
                            'NAMA_AM' => $namaAM,
                            'ROLE' => $divisiAM,
                            'WITEL' => $this->getColumnValue($row, $columnIndices['WITEL AM']) ?? 'N/A',
                            'DIVISI' => $this->getColumnValue($row, $columnIndices['DIVISI']) ?? 'N/A'
                        ],
                        'old_data' => [
                            'nama' => $existingAM->nama,
                            'role' => $existingAM->role
                        ]
                    ];
                } else {
                    $newCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'new',
                        'data' => [
                            'NIK' => $nik,
                            'NAMA_AM' => $namaAM,
                            'ROLE' => $divisiAM,
                            'WITEL' => $this->getColumnValue($row, $columnIndices['WITEL AM']) ?? 'N/A',
                            'DIVISI' => $this->getColumnValue($row, $columnIndices['DIVISI']) ?? 'N/A'
                        ]
                    ];
                }
            }

            // ✅ FIXED: Return structure sesuai ekspektasi frontend
            return [
                'success' => true,
                'message' => 'Preview berhasil',
                'data' => [
                    'summary' => [
                        'new_count' => $newCount,
                        'update_count' => $updateCount,
                        'error_count' => $errorCount,
                        'total_rows' => count($csvData)
                    ],
                    'rows' => $detailedRows
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Preview Data AM Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ FIXED: Execute Data AM Import
     * FIX: Line 273 - Changed 'witels' to 'witel'
     * FIX: Accept Request object instead of string path
     */
    public function executeDataAM($request)
    {
        DB::beginTransaction();

        try {
            // Extract temp file path from request
            $tempFilePath = $request instanceof Request ? $request->input('temp_file') : $request;

            // Validate temp file exists
            if (!$tempFilePath || !file_exists($tempFilePath)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File temporary tidak ditemukan'
                ];
            }

            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            $requiredColumns = ['NIK', 'NAMA AM', 'WITEL AM', 'DIVISI AM'];
            $optionalColumns = ['PROPORSI', 'NIPNAS', 'DIVISI', 'TELDA'];

            $headers = array_shift($csvData);

            if (!$this->validateHeaders($headers, $requiredColumns)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            $allColumns = array_merge($requiredColumns, $optionalColumns);
            $columnIndices = $this->getColumnIndices($headers, $allColumns);

            $statistics = [
                'total_rows' => count($csvData),
                'success_count' => 0,
                'failed_count' => 0,
                'updated_count' => 0,
                'inserted_count' => 0,
                'failed_rows' => []
            ];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    $nik = $this->getColumnValue($row, $columnIndices['NIK']);
                    $namaAM = $this->getColumnValue($row, $columnIndices['NAMA AM']);
                    $witelName = $this->getColumnValue($row, $columnIndices['WITEL AM']);
                    $divisiAM = strtoupper(trim($this->getColumnValue($row, $columnIndices['DIVISI AM'])));

                    $proporsi = $this->getColumnValue($row, $columnIndices['PROPORSI']) ?? '1';
                    $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                    $divisiList = $this->getColumnValue($row, $columnIndices['DIVISI']);
                    $teldaName = $this->getColumnValue($row, $columnIndices['TELDA']);

                    if (empty($nik) || empty($namaAM) || empty($witelName) || empty($divisiAM)) {
                        throw new \Exception('NIK, NAMA AM, WITEL AM, atau DIVISI AM kosong');
                    }

                    // Validasi DIVISI AM
                    if (!in_array($divisiAM, ['AM', 'HOTDA'])) {
                        throw new \Exception('DIVISI AM harus AM atau HOTDA');
                    }

                    // ✅ FIXED: Get Witel ID - Changed table name from 'witels' to 'witel'
                    $witel = DB::table('witel')
                        ->whereRaw('UPPER(nama) = ?', [strtoupper($witelName)])
                        ->first();

                    if (!$witel) {
                        throw new \Exception("Witel '{$witelName}' tidak ditemukan");
                    }

                    // Get TELDA ID (jika ada dan HOTDA)
                    $teldaId = null;
                    if ($divisiAM === 'HOTDA' && !empty($teldaName)) {
                        $telda = DB::table('teldas')
                            ->whereRaw('UPPER(nama) = ?', [strtoupper($teldaName)])
                            ->first();

                        if ($telda) {
                            $teldaId = $telda->id;
                        }
                    }

                    // Check existing AM
                    $existingAM = DB::table('account_managers')
                        ->where('nik', $nik)
                        ->first();

                    $amData = [
                        'nama' => $namaAM,
                        'role' => $divisiAM,
                        'witel_id' => $witel->id,
                        'telda_id' => $teldaId,
                        'updated_at' => now()
                    ];

                    if ($existingAM) {
                        DB::table('account_managers')
                            ->where('id', $existingAM->id)
                            ->update($amData);

                        $amId = $existingAM->id;
                        $statistics['updated_count']++;
                    } else {
                        $amData['nik'] = $nik;
                        $amData['created_at'] = now();

                        $amId = DB::table('account_managers')->insertGetId($amData);
                        $statistics['inserted_count']++;
                    }

                    // Handle many-to-many divisi relationships
                    if (!empty($divisiList)) {
                        DB::table('account_manager_divisi')
                            ->where('account_manager_id', $amId)
                            ->delete();

                        $divisiArray = array_map('trim', explode(',', $divisiList));

                        foreach ($divisiArray as $divisiKode) {
                            $divisi = DB::table('divisi')
                                ->whereRaw('UPPER(kode) = ?', [strtoupper($divisiKode)])
                                ->first();

                            if ($divisi) {
                                DB::table('account_manager_divisi')->insert([
                                    'account_manager_id' => $amId,
                                    'divisi_id' => $divisi->id,
                                    'is_primary' => false,
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);
                            }
                        }
                    }

                    $statistics['success_count']++;

                } catch (\Exception $e) {
                    $statistics['failed_count']++;
                    $statistics['failed_rows'][] = [
                        'row_number' => $rowNumber,
                        'nik' => $nik ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $errorLogPath = null;
            if (count($statistics['failed_rows']) > 0) {
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'data_am');
            }

            $message = 'Import Data AM selesai';
            if ($statistics['updated_count'] > 0 && $statistics['inserted_count'] > 0) {
                $message .= " ({$statistics['updated_count']} data di-update, {$statistics['inserted_count']} data baru)";
            } elseif ($statistics['updated_count'] > 0) {
                $message .= " ({$statistics['updated_count']} data di-update)";
            } elseif ($statistics['inserted_count'] > 0) {
                $message .= " ({$statistics['inserted_count']} data baru)";
            }

            return [
                'success' => true,
                'message' => $message,
                'statistics' => [
                    'total_rows' => $statistics['total_rows'],
                    'success_count' => $statistics['success_count'],
                    'failed_count' => $statistics['failed_count'],
                    'updated_count' => $statistics['updated_count'],
                    'inserted_count' => $statistics['inserted_count']
                ],
                'error_log_path' => $errorLogPath
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Data AM Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage(),
                'statistics' => [
                    'total_rows' => 0,
                    'success_count' => 0,
                    'failed_count' => 0
                ]
            ];
        }
    }

    /**
     * Preview Revenue AM Import
     */
    public function previewRevenueAM($tempFilePath)
    {
        try {
            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            $requiredColumns = ['YEAR', 'MONTH', 'NIPNAS', 'NIK_AM', 'PROPORSI'];
            $headers = array_shift($csvData);

            if (!$this->validateHeaders($headers, $requiredColumns)) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            $columnIndices = $this->getColumnIndices($headers, $requiredColumns);

            $newCount = 0;
            $updateCount = 0;
            $errorCount = 0;
            $detailedRows = [];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                $year = $this->getColumnValue($row, $columnIndices['YEAR']);
                $month = $this->getColumnValue($row, $columnIndices['MONTH']);
                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                $nikAM = $this->getColumnValue($row, $columnIndices['NIK_AM']);
                $proporsi = $this->getColumnValue($row, $columnIndices['PROPORSI']);

                if (empty($year) || empty($month) || empty($nipnas) || empty($nikAM) || empty($proporsi)) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'YEAR' => $year ?? 'N/A',
                            'MONTH' => $month ?? 'N/A',
                            'NIPNAS' => $nipnas ?? 'N/A',
                            'NIK_AM' => $nikAM ?? 'N/A',
                            'PROPORSI' => $proporsi ?? 'N/A'
                        ],
                        'error' => 'Data tidak lengkap'
                    ];
                    continue;
                }

                $am = DB::table('account_managers')->where('nik', $nikAM)->first();
                if (!$am) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'YEAR' => $year,
                            'MONTH' => $month,
                            'NIPNAS' => $nipnas,
                            'NIK_AM' => $nikAM,
                            'PROPORSI' => $proporsi
                        ],
                        'error' => "Account Manager dengan NIK {$nikAM} tidak ditemukan"
                    ];
                    continue;
                }

                $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                if (!$cc) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'YEAR' => $year,
                            'MONTH' => $month,
                            'NIPNAS' => $nipnas,
                            'NIK_AM' => $nikAM,
                            'PROPORSI' => $proporsi
                        ],
                        'error' => "Corporate Customer dengan NIPNAS {$nipnas} tidak ditemukan"
                    ];
                    continue;
                }

                $existingRecord = DB::table('am_revenues')
                    ->where('account_manager_id', $am->id)
                    ->where('corporate_customer_id', $cc->id)
                    ->where('tahun', $year)
                    ->where('bulan', $month)
                    ->first();

                if ($existingRecord) {
                    $updateCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'update',
                        'data' => [
                            'YEAR' => $year,
                            'MONTH' => $month,
                            'NIPNAS' => $nipnas,
                            'NIK_AM' => $nikAM,
                            'PROPORSI' => $proporsi,
                            'AM_NAME' => $am->nama,
                            'CC_NAME' => $cc->nama
                        ]
                    ];
                } else {
                    $newCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'new',
                        'data' => [
                            'YEAR' => $year,
                            'MONTH' => $month,
                            'NIPNAS' => $nipnas,
                            'NIK_AM' => $nikAM,
                            'PROPORSI' => $proporsi,
                            'AM_NAME' => $am->nama,
                            'CC_NAME' => $cc->nama
                        ]
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'Preview berhasil',
                'data' => [
                    'summary' => [
                        'new_count' => $newCount,
                        'update_count' => $updateCount,
                        'error_count' => $errorCount,
                        'total_rows' => count($csvData)
                    ],
                    'rows' => $detailedRows
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Preview Revenue AM Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Execute Revenue AM Import
     * FIX: Accept Request object instead of string path
     */
    public function executeRevenueAM($request)
    {
        DB::beginTransaction();

        try {
            // Extract temp file path from request
            $tempFilePath = $request instanceof Request ? $request->input('temp_file') : $request;

            // Validate temp file exists
            if (!$tempFilePath || !file_exists($tempFilePath)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File temporary tidak ditemukan'
                ];
            }

            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            $requiredColumns = ['YEAR', 'MONTH', 'NIPNAS', 'NIK_AM', 'PROPORSI'];
            $headers = array_shift($csvData);

            if (!$this->validateHeaders($headers, $requiredColumns)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            $columnIndices = $this->getColumnIndices($headers, $requiredColumns);

            $statistics = [
                'total_rows' => count($csvData),
                'success_count' => 0,
                'failed_count' => 0,
                'skipped_count' => 0,
                'updated_count' => 0,
                'inserted_count' => 0,
                'failed_rows' => []
            ];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    $year = $this->getColumnValue($row, $columnIndices['YEAR']);
                    $month = $this->getColumnValue($row, $columnIndices['MONTH']);
                    $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                    $nikAM = $this->getColumnValue($row, $columnIndices['NIK_AM']);
                    $proporsi = floatval($this->getColumnValue($row, $columnIndices['PROPORSI']));

                    if (empty($year) || empty($month) || empty($nipnas) || empty($nikAM) || empty($proporsi)) {
                        throw new \Exception('Data tidak lengkap');
                    }

                    $am = DB::table('account_managers')->where('nik', $nikAM)->first();
                    if (!$am) {
                        throw new \Exception("Account Manager dengan NIK {$nikAM} tidak ditemukan");
                    }

                    $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                    if (!$cc) {
                        throw new \Exception("Corporate Customer dengan NIPNAS {$nipnas} tidak ditemukan");
                    }

                    $ccRevenue = DB::table('cc_revenues')
                        ->where('corporate_customer_id', $cc->id)
                        ->where('tahun', $year)
                        ->where('bulan', $month)
                        ->first();

                    if (!$ccRevenue) {
                        $statistics['skipped_count']++;
                        $statistics['failed_rows'][] = [
                            'row_number' => $rowNumber,
                            'nipnas' => $nipnas,
                            'nik_am' => $nikAM,
                            'error' => "Data Revenue CC untuk periode {$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . " belum ada. Import Revenue CC terlebih dahulu."
                        ];
                        continue;
                    }

                    $targetRevenueAM = ($ccRevenue->target_revenue * $proporsi) / 100;
                    $realRevenueAM = ($ccRevenue->real_revenue * $proporsi) / 100;
                    $achievementRate = $targetRevenueAM > 0 ? ($realRevenueAM / $targetRevenueAM) * 100 : 0;

                    $dataToSave = [
                        'account_manager_id' => $am->id,
                        'corporate_customer_id' => $cc->id,
                        'divisi_id' => $ccRevenue->divisi_id,
                        'witel_id' => $am->witel_id,
                        'telda_id' => $am->telda_id,
                        'proporsi' => $proporsi,
                        'target_revenue' => $targetRevenueAM,
                        'real_revenue' => $realRevenueAM,
                        'achievement_rate' => $achievementRate,
                        'bulan' => $month,
                        'tahun' => $year,
                        'updated_at' => now()
                    ];

                    $existingRecord = DB::table('am_revenues')
                        ->where('account_manager_id', $am->id)
                        ->where('corporate_customer_id', $cc->id)
                        ->where('tahun', $year)
                        ->where('bulan', $month)
                        ->first();

                    if ($existingRecord) {
                        DB::table('am_revenues')
                            ->where('id', $existingRecord->id)
                            ->update($dataToSave);

                        $statistics['updated_count']++;
                    } else {
                        $dataToSave['created_at'] = now();
                        DB::table('am_revenues')->insert($dataToSave);

                        $statistics['inserted_count']++;
                    }

                    $statistics['success_count']++;

                } catch (\Exception $e) {
                    $statistics['failed_count']++;
                    $statistics['failed_rows'][] = [
                        'row_number' => $rowNumber,
                        'nipnas' => $nipnas ?? 'N/A',
                        'nik_am' => $nikAM ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $errorLogPath = null;
            if (count($statistics['failed_rows']) > 0) {
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'revenue_am');
            }

            $message = 'Import Revenue AM selesai';
            if ($statistics['updated_count'] > 0 && $statistics['inserted_count'] > 0) {
                $message .= " ({$statistics['updated_count']} data di-update, {$statistics['inserted_count']} data baru)";
            } elseif ($statistics['updated_count'] > 0) {
                $message .= " ({$statistics['updated_count']} data di-update)";
            } elseif ($statistics['inserted_count'] > 0) {
                $message .= " ({$statistics['inserted_count']} data baru)";
            }

            return [
                'success' => true,
                'message' => $message,
                'statistics' => [
                    'total_rows' => $statistics['total_rows'],
                    'success_count' => $statistics['success_count'],
                    'failed_count' => $statistics['failed_count'],
                    'skipped_count' => $statistics['skipped_count'],
                    'updated_count' => $statistics['updated_count'],
                    'inserted_count' => $statistics['inserted_count']
                ],
                'error_log_path' => $errorLogPath
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Revenue AM Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage(),
                'statistics' => [
                    'total_rows' => 0,
                    'success_count' => 0,
                    'failed_count' => 0,
                    'skipped_count' => 0
                ]
            ];
        }
    }

    // ==================== HELPER METHODS (MAINTAINED) ====================

    private function parseCsvFile($file)
    {
        $csvData = [];
        $handle = fopen($file->getRealPath(), 'r');

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $csvData[] = $row;
        }

        fclose($handle);
        return $csvData;
    }

    private function parseCsvFileFromPath($filepath)
    {
        $csvData = [];
        $handle = fopen($filepath, 'r');

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $csvData[] = $row;
        }

        fclose($handle);
        return $csvData;
    }

    private function validateHeaders($headers, $requiredColumns)
    {
        $cleanHeaders = array_map(function($h) {
            return strtoupper(trim($h));
        }, $headers);

        foreach ($requiredColumns as $column) {
            $cleanColumn = strtoupper(trim($column));
            if (!in_array($cleanColumn, $cleanHeaders)) {
                return false;
            }
        }
        return true;
    }

    private function getColumnIndices($headers, $columns)
    {
        $indices = [];

        $cleanHeaders = array_map(function($h) {
            return strtoupper(trim($h));
        }, $headers);

        foreach ($columns as $column) {
            $cleanColumn = strtoupper(trim($column));
            $index = array_search($cleanColumn, $cleanHeaders);
            $indices[$column] = $index !== false ? $index : null;
        }
        return $indices;
    }

    private function getColumnValue($row, $index)
    {
        return $index !== null && isset($row[$index]) ? trim($row[$index]) : null;
    }

    private function generateErrorLog($failedRows, $type)
    {
        if (empty($failedRows)) {
            return null;
        }

        $filename = 'error_log_' . $type . '_' . time() . '.csv';
        $directory = public_path('storage/import_logs');

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $filepath = $directory . '/' . $filename;
        $handle = fopen($filepath, 'w');

        if ($type === 'revenue_am') {
            fputcsv($handle, ['Baris', 'NIPNAS', 'NIK_AM', 'Error']);
            foreach ($failedRows as $row) {
                fputcsv($handle, [
                    $row['row_number'],
                    $row['nipnas'] ?? 'N/A',
                    $row['nik_am'] ?? 'N/A',
                    $row['error']
                ]);
            }
        } else {
            fputcsv($handle, ['Baris', 'NIK', 'Error']);
            foreach ($failedRows as $row) {
                fputcsv($handle, [
                    $row['row_number'],
                    $row['nik'] ?? 'N/A',
                    $row['error']
                ]);
            }
        }

        fclose($handle);
        return asset('storage/import_logs/' . $filename);
    }
}