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
 * ✅ FIXED VERSION - 2026-01-29
 *
 * CHANGELOG:
 * ✅ FIXED: Flexible column validation - boleh ada kolom extra di CSV
 * ✅ MAINTAINED: All other functionality unchanged (routes, execute, preview, etc.)
 * ✅ MAINTAINED: Year/month handling from request
 * ✅ MAINTAINED: Preview with 5 rows + full data storage
 */
class ImportAMController extends Controller
{
    /**
     * Download Template CSV
     */
    public function downloadTemplate($type)
    {
        $templates = [
            // ========================================
            // DATA AM TEMPLATE
            // ========================================
            'data-am' => [
                'filename' => 'template_data_am.csv',
                'headers' => [
                    'NIK',
                    'NAMA AM',
                    'PROPORSI',
                    'WITEL AM',
                    'NIPNAS',
                    'STANDARD NAME',
                    'GROUP CONGLO',
                    'DIVISI AM',
                    'SEGMEN',
                    'WITEL HO',
                    'REGIONAL',
                    'DIVISI',
                    'TELDA'
                ],
                'sample' => [
                    ['404482', 'I WAYAN AGUS SUANTARA', '1', 'BALI', '2000106', 'ACCOR HOTEL BALI', '', 'AM', 'FWS', 'BALI', 'TREG 3', 'DPS', ''],
                    ['970252', 'DESY CAHYANI LARI', '1', 'NUSA TENGGARA', '19669082', 'AGUSTINUS WAE FOLO-PT MITRA SINAR JAYA', '', 'HOTDA', 'PRS', 'NUSA TENGGARA', 'TREG 3', 'DPS', 'TELDA NUSA TENGGARA']
                ]
            ],

            // ========================================
            // ✅ REVENUE AM TEMPLATE
            // ========================================
            'revenue-am' => [
                'filename' => 'template_revenue_am.csv',
                'headers' => [
                    'YEAR',
                    'MONTH',
                    'NIPNAS',
                    'NIK_AM',
                    'PROPORSI'
                ],
                'sample' => [
                    ['2026', '1', '76590002', '404482', '30'],
                    ['2026', '1', '76590002', '970252', '70']
                ]
            ]
        ];

        if (!isset($templates[$type])) {
            return response()->json([
                'success' => false,
                'message' => 'Template type not found',
                'available_types' => array_keys($templates)
            ], 404);
        }

        $template = $templates[$type];

        // Create CSV
        $csv = fopen('php://temp', 'r+');
        
        // Add headers
        fputcsv($csv, $template['headers']);
        
        // Add sample data
        foreach ($template['sample'] as $row) {
            fputcsv($csv, $row);
        }

        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $template['filename'] . '"',
        ]);
    }

    /**
     * ✅ MAINTAINED: Preview Data AM Import
     */
    public function previewDataAM($tempFilePath)
    {
        try {
            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            $requiredColumns = ['NIK_AM', 'NAMA AM', 'WITEL AM', 'DIVISI AM'];
            $optionalColumns = ['PROPORSI', 'NIPNAS', 'DIVISI', 'TELDA'];

            $headers = array_shift($csvData);

            // ✅ FIXED: Use flexible validation - support both NIK_AM and NIK
            if (!$this->validateHeadersFlexible($headers, ['NIK_AM', 'NIK'], ['NAMA AM', 'WITEL AM', 'DIVISI AM'])) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan. Pastikan ada kolom: NIK_AM (atau NIK), NAMA AM, WITEL AM, DIVISI AM'
                ];
            }

            // Get indices untuk kolom wajib + opsional
            $allColumns = array_merge($requiredColumns, $optionalColumns, ['NIK']); // Add NIK as fallback
            $columnIndices = $this->getColumnIndices($headers, $allColumns);

            $newCount = 0;
            $updateCount = 0;
            $errorCount = 0;
            $detailedRows = [];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                // Try NIK_AM first, then fallback to NIK
                $nik = $this->getColumnValue($row, $columnIndices['NIK_AM'])
                    ?? $this->getColumnValue($row, $columnIndices['NIK']);
                $namaAM = $this->getColumnValue($row, $columnIndices['NAMA AM']);
                $divisiAM = strtoupper(trim($this->getColumnValue($row, $columnIndices['DIVISI AM'])));

                // Validasi kolom wajib tidak boleh kosong
                if (empty($nik) || empty($namaAM) || empty($divisiAM)) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIK_AM' => $nik ?? 'N/A',
                            'NAMA_AM' => $namaAM ?? 'N/A',
                            'ROLE' => $divisiAM ?? 'N/A',
                            'WITEL' => $this->getColumnValue($row, $columnIndices['WITEL AM']) ?? 'N/A'
                        ],
                        'error' => 'NIK_AM, NAMA AM, atau DIVISI AM kosong'
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
                            'NIK_AM' => $nik,
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
                            'NIK_AM' => $nik,
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
                            'NIK_AM' => $nik,
                            'NAMA_AM' => $namaAM,
                            'ROLE' => $divisiAM,
                            'WITEL' => $this->getColumnValue($row, $columnIndices['WITEL AM']) ?? 'N/A',
                            'DIVISI' => $this->getColumnValue($row, $columnIndices['DIVISI']) ?? 'N/A'
                        ]
                    ];
                }
            }

            // Return only 5 rows for preview, store all rows for execute
            $previewRows = array_slice($detailedRows, 0, 5);

            return [
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_rows' => count($detailedRows),
                        'new_count' => $newCount,
                        'update_count' => $updateCount,
                        'error_count' => $errorCount
                    ],
                    'preview_rows' => $previewRows,
                    'all_rows' => $detailedRows,
                    'full_data_stored' => true
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Preview Data AM Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ MAINTAINED: Execute Data AM Import with filter support
     */
    public function executeDataAM($tempFilePath, $filterType = 'all')
    {
        try {
            DB::beginTransaction();

            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            $requiredColumns = ['NIK_AM', 'NAMA AM', 'WITEL AM', 'DIVISI AM'];
            $headers = array_shift($csvData);

            // ✅ FIXED: Use flexible validation
            if (!$this->validateHeadersFlexible($headers, ['NIK_AM', 'NIK'], ['NAMA AM', 'WITEL AM', 'DIVISI AM'])) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan'
                ];
            }

            $allColumns = array_merge($requiredColumns, ['PROPORSI', 'NIPNAS', 'DIVISI', 'TELDA', 'NIK']);
            $columnIndices = $this->getColumnIndices($headers, $allColumns);

            // Analyze rows first to determine status
            $rowsWithStatus = [];
            
            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;
                $nik = $this->getColumnValue($row, $columnIndices['NIK_AM'])
                    ?? $this->getColumnValue($row, $columnIndices['NIK']);
                $namaAM = $this->getColumnValue($row, $columnIndices['NAMA AM']);
                $divisiAM = strtoupper(trim($this->getColumnValue($row, $columnIndices['DIVISI AM'])));
                
                if (empty($nik) || empty($namaAM) || empty($divisiAM) || !in_array($divisiAM, ['AM', 'HOTDA'])) {
                    $rowsWithStatus[] = [
                        'index' => $index,
                        'row' => $row,
                        'status' => 'error',
                        'rowNumber' => $rowNumber
                    ];
                    continue;
                }
                
                $existingAM = DB::table('account_managers')->where('nik', $nik)->first();
                $status = $existingAM ? 'update' : 'new';
                
                $rowsWithStatus[] = [
                    'index' => $index,
                    'row' => $row,
                    'status' => $status,
                    'rowNumber' => $rowNumber
                ];
            }
            
            // Filter by requested type
            $rowsToProcess = array_filter($rowsWithStatus, function($item) use ($filterType) {
                if ($filterType === 'all') {
                    return $item['status'] !== 'error';
                } elseif ($filterType === 'new') {
                    return $item['status'] === 'new';
                } elseif ($filterType === 'update') {
                    return $item['status'] === 'update';
                }
                return true;
            });

            $statistics = [
                'total_rows' => count($rowsToProcess),
                'success_count' => 0,
                'failed_count' => 0,
                'skipped_count' => 0,
                'updated_count' => 0,
                'inserted_count' => 0,
                'failed_rows' => []
            ];

            foreach ($rowsToProcess as $item) {
                $index = $item['index'];
                $row = $item['row'];
                $rowNumber = $item['rowNumber'];

                try {
                    $nik = $this->getColumnValue($row, $columnIndices['NIK_AM'])
                        ?? $this->getColumnValue($row, $columnIndices['NIK']);
                    $namaAM = $this->getColumnValue($row, $columnIndices['NAMA AM']);
                    $witelAM = $this->getColumnValue($row, $columnIndices['WITEL AM']);
                    $divisiAM = strtoupper(trim($this->getColumnValue($row, $columnIndices['DIVISI AM'])));

                    if (empty($nik) || empty($namaAM) || empty($divisiAM)) {
                        $statistics['failed_count']++;
                        $statistics['failed_rows'][] = [
                            'row_number' => $rowNumber,
                            'nik' => $nik ?? 'N/A',
                            'error' => 'NIK_AM, NAMA AM, atau DIVISI AM kosong'
                        ];
                        continue;
                    }

                    if (!in_array($divisiAM, ['AM', 'HOTDA'])) {
                        $statistics['failed_count']++;
                        $statistics['failed_rows'][] = [
                            'row_number' => $rowNumber,
                            'nik' => $nik,
                            'error' => 'DIVISI AM harus AM atau HOTDA'
                        ];
                        continue;
                    }

                    // Get witel
                    $witel = DB::table('witel')->where('nama', $witelAM)->first();
                    if (!$witel) {
                        $statistics['failed_count']++;
                        $statistics['failed_rows'][] = [
                            'row_number' => $rowNumber,
                            'nik' => $nik,
                            'error' => 'Witel tidak ditemukan: ' . $witelAM
                        ];
                        continue;
                    }

                    // Get TELDA if HOTDA
                    $teldaId = null;
                    if ($divisiAM === 'HOTDA') {
                        $teldaName = $this->getColumnValue($row, $columnIndices['TELDA']);
                        if ($teldaName) {
                            $telda = DB::table('teldas')->where('nama', $teldaName)->first();
                            if ($telda) {
                                $teldaId = $telda->id;
                            }
                        }
                    }

                    $existingAM = DB::table('account_managers')->where('nik', $nik)->first();

                    if ($existingAM) {
                        DB::table('account_managers')
                            ->where('nik', $nik)
                            ->update([
                                'nama' => $namaAM,
                                'role' => $divisiAM,
                                'witel_id' => $witel->id,
                                'telda_id' => $teldaId,
                                'updated_at' => now()
                            ]);
                        $statistics['updated_count']++;
                    } else {
                        DB::table('account_managers')->insert([
                            'nik' => $nik,
                            'nama' => $namaAM,
                            'role' => $divisiAM,
                            'witel_id' => $witel->id,
                            'telda_id' => $teldaId,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $statistics['inserted_count']++;
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
                    'skipped_count' => $statistics['skipped_count'],
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
                    'failed_count' => 0,
                    'skipped_count' => 0
                ]
            ];
        }
    }

    /**
     * ✅ MAINTAINED: Preview Revenue AM Import
     */
    public function previewRevenueAM($tempFilePath, $year, $month)
    {
        try {
            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            $requiredColumns = ['NIPNAS', 'NIK_AM', 'PROPORSI'];
            $headers = array_shift($csvData);

            // ✅ FIXED: Use flexible validation
            if (!$this->validateHeadersFlexible($headers, $requiredColumns, [])) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            $columnIndices = $this->getColumnIndices($headers, $requiredColumns);

            // Counters
            $newCount = 0;
            $updateCount = 0;
            $errorCount = 0;
            $allRows = [];
            $uniqueNIKAM = [];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                $nikAM = $this->getColumnValue($row, $columnIndices['NIK_AM']);
                $proporsi = $this->getColumnValue($row, $columnIndices['PROPORSI']);

                // Track unique NIK_AM
                if ($nikAM && !in_array($nikAM, $uniqueNIKAM)) {
                    $uniqueNIKAM[] = $nikAM;
                }

                if (empty($nipnas) || empty($nikAM) || empty($proporsi)) {
                    $errorCount++;
                    $allRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIPNAS' => $nipnas ?? 'N/A',
                            'NIK_AM' => $nikAM ?? 'N/A',
                            'PROPORSI' => $proporsi ?? 'N/A'
                        ],
                        'error' => 'NIPNAS, NIK_AM, atau PROPORSI kosong'
                    ];
                    continue;
                }

                // Validate AM exists
                $am = DB::table('account_managers')->where('nik', $nikAM)->first();
                if (!$am) {
                    $errorCount++;
                    $allRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIPNAS' => $nipnas,
                            'NIK_AM' => $nikAM,
                            'PROPORSI' => $proporsi
                        ],
                        'error' => 'Account Manager dengan NIK ' . $nikAM . ' tidak ditemukan'
                    ];
                    continue;
                }

                // Validate CC exists
                $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                if (!$cc) {
                    $errorCount++;
                    $allRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIPNAS' => $nipnas,
                            'NIK_AM' => $nikAM,
                            'PROPORSI' => $proporsi
                        ],
                        'error' => 'Corporate Customer dengan NIPNAS ' . $nipnas . ' tidak ditemukan'
                    ];
                    continue;
                }

                // Check if mapping already exists
                $existingMapping = DB::table('am_revenues')
                    ->where('account_manager_id', $am->id)
                    ->where('corporate_customer_id', $cc->id)
                    ->where('bulan', $month)
                    ->where('tahun', $year)
                    ->first();

                $rowData = [
                    'row_number' => $rowNumber,
                    'data' => [
                        'NIPNAS' => $nipnas,
                        'NIK_AM' => $nikAM,
                        'PROPORSI' => $proporsi
                    ]
                ];

                if ($existingMapping) {
                    $updateCount++;
                    $rowData['status'] = 'update';
                    $rowData['old_data'] = [
                        'proporsi' => $existingMapping->proporsi
                    ];
                } else {
                    $newCount++;
                    $rowData['status'] = 'new';
                }

                $allRows[] = $rowData;
            }

            // Return only 5 rows for preview, but full summary
            $previewRows = array_slice($allRows, 0, 5);

            return [
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_rows' => count($allRows),
                        'unique_am_count' => count($uniqueNIKAM),
                        'new_count' => $newCount,
                        'update_count' => $updateCount,
                        'error_count' => $errorCount
                    ],
                    'preview_rows' => $previewRows,
                    'all_rows' => $allRows,
                    'full_data_stored' => true
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Preview Revenue AM Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ MAINTAINED: Execute Revenue AM Import
     */
    public function executeRevenueAM(Request $request, $tempFilePath, $filterType = 'all')
    {
        try {
            DB::beginTransaction();

            // Accept year/month from request with fallback logic
            $year = null;
            $month = null;

            // Priority 1: Get from request (frontend payload)
            if ($request->has('year') && $request->has('month')) {
                $year = (int) $request->input('year');
                $month = (int) $request->input('month');
                Log::info('✅ Year/Month from request payload', [
                    'year' => $year,
                    'month' => $month
                ]);
            }

            // Priority 2: Fallback to session metadata (if available)
            if (!$year || !$month) {
                $sessionId = $request->input('session_id');
                if ($sessionId) {
                    $metadata = \Cache::get("chunk_metadata_{$sessionId}");
                    if ($metadata && isset($metadata['year']) && isset($metadata['month'])) {
                        $year = (int) $metadata['year'];
                        $month = (int) $metadata['month'];
                        Log::info('⚠️ Year/Month from session metadata', [
                            'year' => $year,
                            'month' => $month
                        ]);
                    }
                }
            }

            // Priority 3: Error if still not found
            if (!$year || !$month) {
                Log::error('❌ Year/Month not found in request or session', [
                    'request_year' => $request->input('year'),
                    'request_month' => $request->input('month'),
                    'session_id' => $request->input('session_id')
                ]);

                return [
                    'success' => false,
                    'message' => 'Parameter year dan month wajib diisi (dari form input)'
                ];
            }

            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            $requiredColumns = ['NIPNAS', 'NIK_AM', 'PROPORSI'];
            $headers = array_shift($csvData);

            // ✅ FIXED: Use flexible validation
            if (!$this->validateHeadersFlexible($headers, $requiredColumns, [])) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan'
                ];
            }

            $columnIndices = $this->getColumnIndices($headers, $requiredColumns);

            $statistics = [
                'total_rows' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'skipped_count' => 0,
                'updated_count' => 0,
                'inserted_count' => 0,
                'failed_rows' => []
            ];

            // Filter by status instead of selected rows
            // First, analyze all rows to determine their status (new/update)
            $rowsWithStatus = [];
            
            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;
                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                $nikAM = $this->getColumnValue($row, $columnIndices['NIK_AM']);
                $proporsi = (float) $this->getColumnValue($row, $columnIndices['PROPORSI']);
                
                if (empty($nipnas) || empty($nikAM) || $proporsi === 0.0) {
                    $rowsWithStatus[] = [
                        'index' => $index,
                        'row' => $row,
                        'status' => 'error',
                        'rowNumber' => $rowNumber
                    ];
                    continue;
                }
                
                // Check if revenue AM exists
                $am = DB::table('account_managers')->where('nik', $nikAM)->first();
                $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                
                if (!$am || !$cc) {
                    $rowsWithStatus[] = [
                        'index' => $index,
                        'row' => $row,
                        'status' => 'error',
                        'rowNumber' => $rowNumber
                    ];
                    continue;
                }
                
                // Check if revenue record exists
                try {
                    $existingRevenue = DB::table('am_revenues')
                        ->where('account_manager_id', $am->id)
                        ->where('corporate_customer_id', $cc->id)
                        ->where('tahun', $year)
                        ->where('bulan', $month)
                        ->first();
                        
                    $status = $existingRevenue ? 'update' : 'new';
                } catch (\Exception $e) {
                    // If any error, treat all as new
                    Log::warning('⚠️ am_revenues table query error, treating as new', [
                        'error' => $e->getMessage()
                    ]);
                    $status = 'new';
                }
                
                $rowsWithStatus[] = [
                    'index' => $index,
                    'row' => $row,
                    'status' => $status,
                    'rowNumber' => $rowNumber
                ];
            }
            
            // Filter by requested type
            $rowsToProcess = array_filter($rowsWithStatus, function($item) use ($filterType) {
                if ($filterType === 'all') {
                    return $item['status'] !== 'error';
                } elseif ($filterType === 'new') {
                    return $item['status'] === 'new';
                } elseif ($filterType === 'update') {
                    return $item['status'] === 'update';
                }
                return true;
            });

            $statistics['total_rows'] = count($rowsToProcess);

            foreach ($rowsToProcess as $item) {
                $index = $item['index'];
                $row = $item['row'];
                $rowNumber = $item['rowNumber'];

                try {
                    $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                    $nikAM = $this->getColumnValue($row, $columnIndices['NIK_AM']);
                    $proporsi = (float) $this->getColumnValue($row, $columnIndices['PROPORSI']);

                    if (empty($nipnas) || empty($nikAM) || $proporsi === 0.0) {
                        $statistics['failed_count']++;
                        $statistics['failed_rows'][] = [
                            'row_number' => $rowNumber,
                            'nipnas' => $nipnas ?? 'N/A',
                            'nik_am' => $nikAM ?? 'N/A',
                            'error' => 'NIPNAS, NIK_AM, atau PROPORSI kosong'
                        ];
                        continue;
                    }

                    // Get AM
                    $am = DB::table('account_managers')->where('nik', $nikAM)->first();
                    if (!$am) {
                        $statistics['failed_count']++;
                        $statistics['failed_rows'][] = [
                            'row_number' => $rowNumber,
                            'nipnas' => $nipnas,
                            'nik_am' => $nikAM,
                            'error' => 'Account Manager dengan NIK ' . $nikAM . ' tidak ditemukan'
                        ];
                        continue;
                    }

                    // Get CC
                    $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                    if (!$cc) {
                        $statistics['failed_count']++;
                        $statistics['failed_rows'][] = [
                            'row_number' => $rowNumber,
                            'nipnas' => $nipnas,
                            'nik_am' => $nikAM,
                            'error' => 'Corporate Customer dengan NIPNAS ' . $nipnas . ' tidak ditemukan'
                        ];
                        continue;
                    }

                    // Get CC Revenue for this period
                    $ccRevenue = DB::table('cc_revenues')
                        ->where('corporate_customer_id', $cc->id)
                        ->where('bulan', $month)
                        ->where('tahun', $year)
                        ->first();

                    if (!$ccRevenue) {
                        $statistics['skipped_count']++;
                        continue;
                    }

                    // Normalize proporsi
                    if ($proporsi > 1) {
                        $proporsi = $proporsi / 100;
                    }

                    // Calculate AM revenues
                    $targetRevenue = $ccRevenue->target_revenue * $proporsi;
                    $realRevenue = $ccRevenue->real_revenue * $proporsi;
                    $achievementRate = $targetRevenue > 0 ? ($realRevenue / $targetRevenue) * 100 : 0;

                    // Check if mapping already exists
                    $existingMapping = DB::table('am_revenues')
                        ->where('account_manager_id', $am->id)
                        ->where('corporate_customer_id', $cc->id)
                        ->where('bulan', $month)
                        ->where('tahun', $year)
                        ->first();

                    if ($existingMapping) {
                        DB::table('am_revenues')
                            ->where('id', $existingMapping->id)
                            ->update([
                                'proporsi' => $proporsi * 100,
                                'target_revenue' => $targetRevenue,
                                'real_revenue' => $realRevenue,
                                'achievement_rate' => round($achievementRate, 2),
                                'updated_at' => now()
                            ]);
                        $statistics['updated_count']++;
                    } else {
                        DB::table('am_revenues')->insert([
                            'account_manager_id' => $am->id,
                            'corporate_customer_id' => $cc->id,
                            'divisi_id' => $ccRevenue->divisi_id,
                            'witel_id' => $am->witel_id,
                            'telda_id' => $am->telda_id,
                            'proporsi' => $proporsi * 100,
                            'target_revenue' => $targetRevenue,
                            'real_revenue' => $realRevenue,
                            'achievement_rate' => round($achievementRate, 2),
                            'bulan' => $month,
                            'tahun' => $year,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
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

    // ==================== HELPER METHODS ====================

    /**
     * Parse CSV file from uploaded file object
     */
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

    /**
     * Parse CSV file from file path
     */
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

    /**
     * ✅ FIXED: Flexible header validation with alternative column names
     * Support multiple possible column names (e.g., NIK_AM or NIK)
     */
    private function validateHeadersFlexible($headers, $alternativeColumns, $requiredColumns)
    {
        $cleanHeaders = array_map(function ($h) {
            return strtoupper(trim($h));
        }, $headers);

        // If alternativeColumns is a simple array (not nested), treat as required columns
        if (!empty($alternativeColumns) && !is_array($alternativeColumns[0])) {
            // Simple validation - just check if required columns exist
            foreach ($alternativeColumns as $column) {
                $cleanColumn = strtoupper(trim($column));
                if (!in_array($cleanColumn, $cleanHeaders)) {
                    Log::warning('Missing required column', [
                        'required' => $column,
                        'cleaned' => $cleanColumn,
                        'available_headers' => $cleanHeaders
                    ]);
                    return false;
                }
            }
            return true;
        }

        // Check if at least one alternative column exists
        $hasAlternative = false;
        foreach ($alternativeColumns as $altCol) {
            $cleanAltCol = strtoupper(trim($altCol));
            if (in_array($cleanAltCol, $cleanHeaders)) {
                $hasAlternative = true;
                break;
            }
        }

        if (!$hasAlternative) {
            return false;
        }

        // Check all other required columns
        foreach ($requiredColumns as $column) {
            $cleanColumn = strtoupper(trim($column));
            if (!in_array($cleanColumn, $cleanHeaders)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get column indices from headers
     * Returns array with column name as key and index as value
     */
    private function getColumnIndices($headers, $columns)
    {
        $indices = [];

        $cleanHeaders = array_map(function ($h) {
            return strtoupper(trim($h));
        }, $headers);

        foreach ($columns as $column) {
            $cleanColumn = strtoupper(trim($column));
            $index = array_search($cleanColumn, $cleanHeaders);
            $indices[$column] = $index !== false ? $index : null;
        }
        return $indices;
    }

    /**
     * Get column value from row by index
     * Returns null if index is null or value doesn't exist
     */
    private function getColumnValue($row, $index)
    {
        return $index !== null && isset($row[$index]) ? trim($row[$index]) : null;
    }

    /**
     * Generate error log CSV file
     */
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
            fputcsv($handle, ['Baris', 'NIK_AM', 'Error']);
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