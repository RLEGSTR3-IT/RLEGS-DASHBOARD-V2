<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * ImportCCController - Corporate Customer Import Handler
 *
 * ✅ UPDATED VERSION - 2026-02-03 (FINAL)
 *
 * MAJOR CHANGES FROM PREVIOUS VERSION:
 * ✅ REMOVED: Redundant column saves (real_revenue, target_revenue, target_revenue_bill)
 * ✅ SIMPLIFIED: Now only saves 5 revenue columns (sold, bill, target_sold, tipe, source)
 * ✅ REMOVED: recalculateAMRevenuesForCC() method (handled by SP/Trigger v2)
 * ✅ MAINTAINED: All other functionality (template, preview, execute, duplicate handling)
 *
 * PREVIOUS CHANGES (2026-02-03):
 * ✅ NEW: Unified template untuk semua divisi (DGS/DSS/DPS pakai template sama)
 * ✅ NEW: Double-check LSEGMENT_HO (cek lsegment_ho + ssegment_ho)
 * ✅ NEW: SOURCE_DATA logic (NGTMA exact → NGTMA, else → REGULER)
 * ✅ NEW: Simpan KEDUA revenue (sold + bill) sekaligus
 * ✅ NEW: Input tipe_revenue dari frontend (HO/BILL)
 * ✅ NEW: Deteksi & agregasi CC duplikat di periode yang sama
 * ✅ MAINTAINED: Flexible column validation
 * ✅ MAINTAINED: Error logging & preview functionality
 */
class ImportCCController extends Controller
{
    /**
     * ✅ UPDATED: Download Template CSV - UNIFIED untuk semua divisi
     */
    public function downloadTemplate($type)
    {
        $templates = [
            // ========================================
            // DATA CC TEMPLATE (No changes)
            // ========================================
            'data-cc' => [
                'filename' => 'template_data_cc.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME'],
                'sample' => [
                    ['76590001', 'BANK JATIM'],
                    ['76590002', 'PEMKOT SEMARANG']
                ]
            ],

            // ========================================
            // ✅ NEW: UNIFIED REVENUE CC TEMPLATE
            // Template yang sama untuk DGS, DSS, DPS
            // ========================================
            'revenue-cc' => [
                'filename' => 'template_revenue_cc.csv',
                'headers' => [
                    'NIPNAS',
                    'STANDARD_NAME',
                    'LSEGMENT_HO',
                    'WITEL_HO',
                    'REVENUE_SOLD',
                    'TARGET_REVENUE_SOLD',
                    'WITEL_BILL',
                    'REVENUE_BILL',
                    'SOURCE_DATA'
                ],
                'sample' => [
                    [
                        '76590002',
                        'PEMKOT SEMARANG',
                        'GOVERNMENT PUBLIC SERVICE',
                        'SEMARANG JATENG UTARA',
                        '195000000',
                        '200000000',
                        'SEMARANG JATENG UTARA',
                        '0',
                        'REGULER'
                    ],
                    [
                        '76590001',
                        'BANK JATIM',
                        'FINANCIAL SERVICE',
                        'SEMARANG JATENG UTARA',
                        '0',
                        '0',
                        'SEMARANG JATENG UTARA',
                        '920000000',
                        'NGTMA'
                    ]
                ]
            ],

            // ========================================
            // BACKWARD COMPATIBILITY ALIASES
            // ========================================
            'revenue-cc-dgs' => [
                'filename' => 'template_revenue_cc.csv',
                'headers' => [
                    'NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO',
                    'REVENUE_SOLD', 'TARGET_REVENUE_SOLD', 'WITEL_BILL', 'REVENUE_BILL', 'SOURCE_DATA'
                ],
                'sample' => [
                    ['76590002', 'PEMKOT SEMARANG', 'GOVERNMENT PUBLIC SERVICE', 'SEMARANG JATENG UTARA', 
                     '195000000', '200000000', 'SEMARANG JATENG UTARA', '0', 'REGULER']
                ]
            ],

            'revenue-cc-dss' => [
                'filename' => 'template_revenue_cc.csv',
                'headers' => [
                    'NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO',
                    'REVENUE_SOLD', 'TARGET_REVENUE_SOLD', 'WITEL_BILL', 'REVENUE_BILL', 'SOURCE_DATA'
                ],
                'sample' => [
                    ['76590010', 'PT TELKOM INDONESIA', 'DIGITAL SUSTAINABILITY SERVICE', 'SEMARANG JATENG UTARA',
                     '250000000', '270000000', 'SEMARANG JATENG UTARA', '0', 'REGULER']
                ]
            ],

            'revenue-cc-dps' => [
                'filename' => 'template_revenue_cc.csv',
                'headers' => [
                    'NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO',
                    'REVENUE_SOLD', 'TARGET_REVENUE_SOLD', 'WITEL_BILL', 'REVENUE_BILL', 'SOURCE_DATA'
                ],
                'sample' => [
                    ['76590001', 'BANK JATIM', 'FINANCIAL SERVICE', 'SEMARANG JATENG UTARA',
                     '0', '0', 'SEMARANG JATENG UTARA', '920000000', 'NGTMA']
                ]
            ]
        ];

        if (!isset($templates[$type])) {
            Log::warning('Template not found', [
                'requested_type' => $type,
                'available_types' => array_keys($templates)
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Template type not found',
                'available_types' => array_keys($templates)
            ], 404);
        }

        $template = $templates[$type];

        // Create CSV
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $template['headers']);
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
     * ✅ MAINTAINED: Preview Data CC Import
     */
    public function previewDataCC($tempFilePath)
    {
        try {
            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            $requiredColumns = ['NIPNAS', 'STANDARD_NAME'];
            $headers = array_shift($csvData);

            if (!$this->validateHeadersFlexible($headers, $requiredColumns)) {
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

                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);

                if (empty($nipnas) || empty($standardName)) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIPNAS' => $nipnas ?? 'N/A',
                            'STANDARD_NAME' => $standardName ?? 'N/A'
                        ],
                        'error' => 'NIPNAS atau STANDARD_NAME kosong'
                    ];
                    continue;
                }

                $existingCC = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();

                if ($existingCC) {
                    $updateCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'update',
                        'data' => [
                            'NIPNAS' => $nipnas,
                            'STANDARD_NAME' => $standardName
                        ],
                        'old_data' => [
                            'nama' => $existingCC->nama
                        ]
                    ];
                } else {
                    $newCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'new',
                        'data' => [
                            'NIPNAS' => $nipnas,
                            'STANDARD_NAME' => $standardName
                        ]
                    ];
                }
            }

            return [
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_rows' => count($detailedRows),
                        'new_count' => $newCount,
                        'update_count' => $updateCount,
                        'error_count' => $errorCount
                    ],
                    'preview_rows' => array_slice($detailedRows, 0, 5),
                    'all_rows' => $detailedRows,
                    'full_data_stored' => true
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Preview Data CC Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ MAINTAINED: Execute Data CC Import
     */
    public function executeDataCC($tempFilePath, $filterType = 'all')
    {
        try {
            DB::beginTransaction();

            $csvData = $this->parseCsvFileFromPath($tempFilePath);
            $requiredColumns = ['NIPNAS', 'STANDARD_NAME'];
            $headers = array_shift($csvData);

            if (!$this->validateHeadersFlexible($headers, $requiredColumns)) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan'
                ];
            }

            $columnIndices = $this->getColumnIndices($headers, $requiredColumns);

            // Filter by status
            $rowsWithStatus = [];
            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;
                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);

                if (empty($nipnas) || empty($standardName)) {
                    $rowsWithStatus[] = [
                        'index' => $index,
                        'row' => $row,
                        'status' => 'error',
                        'rowNumber' => $rowNumber
                    ];
                    continue;
                }

                $existingCC = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                $rowsWithStatus[] = [
                    'index' => $index,
                    'row' => $row,
                    'status' => $existingCC ? 'update' : 'new',
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
                'updated_count' => 0,
                'inserted_count' => 0,
                'failed_rows' => []
            ];

            foreach ($rowsToProcess as $item) {
                $row = $item['row'];
                $rowNumber = $item['rowNumber'];

                try {
                    $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                    $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);

                    if (empty($nipnas) || empty($standardName)) {
                        $statistics['failed_count']++;
                        $statistics['failed_rows'][] = [
                            'row_number' => $rowNumber,
                            'nipnas' => $nipnas ?? 'N/A',
                            'error' => 'NIPNAS atau STANDARD_NAME kosong'
                        ];
                        continue;
                    }

                    $existingCC = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();

                    if ($existingCC) {
                        DB::table('corporate_customers')
                            ->where('nipnas', $nipnas)
                            ->update([
                                'nama' => $standardName,
                                'updated_at' => now()
                            ]);
                        $statistics['updated_count']++;
                    } else {
                        DB::table('corporate_customers')->insert([
                            'nipnas' => $nipnas,
                            'nama' => $standardName,
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
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $errorLogPath = null;
            if (count($statistics['failed_rows']) > 0) {
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'data_cc');
            }

            $message = 'Import Data CC selesai';
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
            Log::error('Import Data CC Error: ' . $e->getMessage());

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
     * ✅ UPDATED: Preview Revenue CC Import - NEW STRUCTURE
     * 
     * CHANGES:
     * - Support unified template (all divisi same)
     * - Double-check LSEGMENT_HO (lsegment_ho + ssegment_ho)
     * - Parse SOURCE_DATA (NGTMA exact → NGTMA, else → REGULER)
     * - Detect CC duplicates in same period
     * - Store both REVENUE_SOLD and REVENUE_BILL
     */
    public function previewRevenueCC($tempFilePath, $divisiId, $bulan, $tahun)
    {
        try {
            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            // ✅ NEW: Unified required columns
            $requiredColumns = [
                'NIPNAS',
                'STANDARD_NAME',
                'LSEGMENT_HO',
                'WITEL_HO'
            ];

            $optionalColumns = [
                'REVENUE_SOLD',
                'TARGET_REVENUE_SOLD',
                'WITEL_BILL',
                'REVENUE_BILL',
                'SOURCE_DATA'
            ];

            $headers = array_shift($csvData);

            if (!$this->validateHeadersFlexible($headers, $requiredColumns)) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            $allColumns = array_merge($requiredColumns, $optionalColumns);
            $columnIndices = $this->getColumnIndices($headers, $allColumns);

            $newCount = 0;
            $updateCount = 0;
            $errorCount = 0;
            $allRows = [];
            $ccTracker = []; // ✅ NEW: Track CC duplicates

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);
                $lsegmentHo = $this->getColumnValue($row, $columnIndices['LSEGMENT_HO']);
                $witelHo = $this->getColumnValue($row, $columnIndices['WITEL_HO']);
                $witelBill = $this->getColumnValue($row, $columnIndices['WITEL_BILL']);
                
                // ✅ NEW: Read both SOLD and BILL
                $revenueSold = $this->getColumnValue($row, $columnIndices['REVENUE_SOLD']) ?? 0;
                $revenueBill = $this->getColumnValue($row, $columnIndices['REVENUE_BILL']) ?? 0;
                $targetRevenueSold = $this->getColumnValue($row, $columnIndices['TARGET_REVENUE_SOLD']) ?? 0;
                
                // ✅ NEW: Parse SOURCE_DATA
                $sourceDataRaw = $this->getColumnValue($row, $columnIndices['SOURCE_DATA']) ?? '';
                $revenueSource = (strtoupper(trim($sourceDataRaw)) === 'NGTMA') ? 'NGTMA' : 'REGULER';

                // Validate required fields
                if (empty($nipnas) || empty($standardName) || empty($lsegmentHo) || empty($witelHo)) {
                    $errorCount++;
                    $allRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIPNAS' => $nipnas ?? 'N/A',
                            'STANDARD_NAME' => $standardName ?? 'N/A',
                            'LSEGMENT_HO' => $lsegmentHo ?? 'N/A',
                            'WITEL_HO' => $witelHo ?? 'N/A'
                        ],
                        'error' => 'Kolom wajib kosong (NIPNAS/STANDARD_NAME/LSEGMENT_HO/WITEL_HO)'
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
                            'STANDARD_NAME' => $standardName
                        ],
                        'error' => 'Corporate Customer dengan NIPNAS ' . $nipnas . ' tidak ditemukan di database'
                    ];
                    continue;
                }

                // ✅ NEW: Double-check LSEGMENT_HO (check both lsegment_ho AND ssegment_ho)
                $segment = DB::table('segments')
                    ->where('divisi_id', $divisiId)
                    ->where(function($query) use ($lsegmentHo) {
                        $query->where('lsegment_ho', $lsegmentHo)
                              ->orWhere('ssegment_ho', $lsegmentHo);
                    })
                    ->first();

                if (!$segment) {
                    $errorCount++;
                    $allRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIPNAS' => $nipnas,
                            'LSEGMENT_HO' => $lsegmentHo
                        ],
                        'error' => 'Segment tidak ditemukan (dicek di kolom lsegment_ho DAN ssegment_ho)'
                    ];
                    continue;
                }

                // Validate Witel HO
                $witelHoRecord = DB::table('witel')->where('nama', $witelHo)->first();
                if (!$witelHoRecord) {
                    $errorCount++;
                    $allRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIPNAS' => $nipnas,
                            'WITEL_HO' => $witelHo
                        ],
                        'error' => 'Witel HO tidak ditemukan: ' . $witelHo
                    ];
                    continue;
                }

                // Validate Witel BILL (optional)
                $witelBillRecord = null;
                if (!empty($witelBill)) {
                    $witelBillRecord = DB::table('witel')->where('nama', $witelBill)->first();
                    if (!$witelBillRecord) {
                        $errorCount++;
                        $allRows[] = [
                            'row_number' => $rowNumber,
                            'status' => 'error',
                            'data' => [
                                'NIPNAS' => $nipnas,
                                'WITEL_BILL' => $witelBill
                            ],
                            'error' => 'Witel BILL tidak ditemukan: ' . $witelBill
                        ];
                        continue;
                    }
                }

                // ✅ NEW: Check for duplicates in same period
                if (!isset($ccTracker[$nipnas])) {
                    $ccTracker[$nipnas] = [];
                }
                $ccTracker[$nipnas][] = $rowNumber;

                // Check if revenue already exists for this CC in this period
                $existingRevenue = DB::table('cc_revenues')
                    ->where('corporate_customer_id', $cc->id)
                    ->where('divisi_id', $divisiId)
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->first();

                $rowData = [
                    'row_number' => $rowNumber,
                    'data' => [
                        'NIPNAS' => $nipnas,
                        'STANDARD_NAME' => $standardName,
                        'SEGMENT' => $segment->lsegment_ho,
                        'WITEL_HO' => $witelHo,
                        'WITEL_BILL' => $witelBill ?? '-',
                        'REVENUE_SOLD' => 'Rp ' . number_format($revenueSold, 0, ',', '.'),
                        'REVENUE_BILL' => 'Rp ' . number_format($revenueBill, 0, ',', '.'),
                        'TARGET_REVENUE_SOLD' => 'Rp ' . number_format($targetRevenueSold, 0, ',', '.'),
                        'SOURCE_DATA' => $revenueSource
                    ]
                ];

                if ($existingRevenue) {
                    $updateCount++;
                    $rowData['status'] = 'update';
                    $rowData['old_data'] = [
                        'real_revenue_sold' => 'Rp ' . number_format($existingRevenue->real_revenue_sold ?? 0, 0, ',', '.'),
                        'real_revenue_bill' => 'Rp ' . number_format($existingRevenue->real_revenue_bill ?? 0, 0, ',', '.'),
                        'revenue_source' => $existingRevenue->revenue_source ?? 'REGULER'
                    ];
                } else {
                    $newCount++;
                    $rowData['status'] = 'new';
                }

                $allRows[] = $rowData;
            }

            // ✅ NEW: Detect duplicates
            $duplicates = array_filter($ccTracker, function($rows) {
                return count($rows) > 1;
            });

            $duplicateCount = count($duplicates);
            $duplicateInfo = [];
            foreach ($duplicates as $nipnas => $rowNumbers) {
                $duplicateInfo[] = [
                    'nipnas' => $nipnas,
                    'rows' => $rowNumbers,
                    'count' => count($rowNumbers)
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_rows' => count($allRows),
                        'new_count' => $newCount,
                        'update_count' => $updateCount,
                        'error_count' => $errorCount,
                        'duplicate_count' => $duplicateCount, // ✅ NEW
                        'duplicates' => $duplicateInfo // ✅ NEW
                    ],
                    'preview_rows' => array_slice($allRows, 0, 5),
                    'all_rows' => $allRows,
                    'full_data_stored' => true
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Preview Revenue CC Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ UPDATED: Execute Revenue CC Import - NEW STRUCTURE
     * 
     * CHANGES:
     * - Accept tipe_revenue from frontend input (HO/BILL)
     * - Save both REVENUE_SOLD and REVENUE_BILL
     * - Set real_revenue based on tipe_revenue choice
     * - Parse SOURCE_DATA → revenue_source (NGTMA/REGULER)
     * - Support duplicate aggregation (sum revenue_sold if duplicate)
     */
    public function executeRevenueCC(Request $request, $tempFilePath, $filterType = 'all')
    {
        try {
            DB::beginTransaction();

            // ✅ NEW: Get tipe_revenue from frontend
            $tipeRevenue = $request->input('tipe_revenue'); // 'HO' or 'BILL'
            $divisiId = $request->input('divisi_id');
            $bulan = (int) $request->input('month');
            $tahun = (int) $request->input('year');
            $aggregateDuplicates = $request->input('aggregate_duplicates', false); // ✅ NEW

            if (!in_array($tipeRevenue, ['HO', 'BILL'])) {
                return [
                    'success' => false,
                    'message' => 'Tipe Revenue harus HO (Revenue Sold) atau BILL (Revenue Bill)'
                ];
            }

            Log::info('✅ Execute Revenue CC with params:', [
                'tipe_revenue' => $tipeRevenue,
                'divisi_id' => $divisiId,
                'periode' => "{$tahun}-{$bulan}",
                'aggregate_duplicates' => $aggregateDuplicates
            ]);

            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            $requiredColumns = ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO'];
            $optionalColumns = ['REVENUE_SOLD', 'TARGET_REVENUE_SOLD', 'WITEL_BILL', 'REVENUE_BILL', 'SOURCE_DATA'];

            $headers = array_shift($csvData);

            if (!$this->validateHeadersFlexible($headers, $requiredColumns)) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan'
                ];
            }

            $allColumns = array_merge($requiredColumns, $optionalColumns);
            $columnIndices = $this->getColumnIndices($headers, $allColumns);

            // ✅ NEW: Group by NIPNAS for duplicate handling
            $ccDataGroups = [];
            
            foreach ($csvData as $index => $row) {
                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                if (!empty($nipnas)) {
                    if (!isset($ccDataGroups[$nipnas])) {
                        $ccDataGroups[$nipnas] = [];
                    }
                    $ccDataGroups[$nipnas][] = [
                        'index' => $index,
                        'row' => $row,
                        'rowNumber' => $index + 2
                    ];
                }
            }

            $statistics = [
                'total_rows' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'updated_count' => 0,
                'inserted_count' => 0,
                'duplicate_aggregated_count' => 0, // ✅ NEW
                'failed_rows' => []
            ];

            foreach ($ccDataGroups as $nipnas => $rowGroup) {
                try {
                    // Validate CC exists
                    $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                    if (!$cc) {
                        $statistics['failed_count']++;
                        foreach ($rowGroup as $rowData) {
                            $statistics['failed_rows'][] = [
                                'row_number' => $rowData['rowNumber'],
                                'nipnas' => $nipnas,
                                'error' => 'Corporate Customer tidak ditemukan'
                            ];
                        }
                        continue;
                    }

                    // ✅ NEW: Handle duplikat
                    $finalRow = null;

                    if (count($rowGroup) > 1 && $aggregateDuplicates) {
                        Log::info("📊 Aggregating duplicate CC: {$nipnas}", [
                            'row_count' => count($rowGroup)
                        ]);

                        // Sum all revenue_sold from duplicates
                        $totalRevenueSold = 0;
                        $totalRevenueBill = 0;
                        $totalTargetRevenueSold = 0;
                        $lastRow = null;

                        foreach ($rowGroup as $item) {
                            $row = $item['row'];
                            $totalRevenueSold += (float) ($this->getColumnValue($row, $columnIndices['REVENUE_SOLD']) ?? 0);
                            $totalRevenueBill += (float) ($this->getColumnValue($row, $columnIndices['REVENUE_BILL']) ?? 0);
                            $totalTargetRevenueSold += (float) ($this->getColumnValue($row, $columnIndices['TARGET_REVENUE_SOLD']) ?? 0);
                            $lastRow = $row; // Use last row for other data
                        }

                        // Process aggregated data
                        $this->processRevenueCC(
                            $lastRow,
                            $columnIndices,
                            $divisiId,
                            $bulan,
                            $tahun,
                            $tipeRevenue,
                            $statistics,
                            $rowGroup[0]['rowNumber'],
                            $totalRevenueSold,
                            $totalRevenueBill,
                            $totalTargetRevenueSold
                        );

                        $statistics['duplicate_aggregated_count']++;
                    } elseif (count($rowGroup) > 1 && !$aggregateDuplicates) {
                        // Use first row only if not aggregating
                        $firstItem = $rowGroup[0];
                        $this->processRevenueCC(
                            $firstItem['row'],
                            $columnIndices,
                            $divisiId,
                            $bulan,
                            $tahun,
                            $tipeRevenue,
                            $statistics,
                            $firstItem['rowNumber']
                        );
                    } else {
                        // Single row - process normally
                        $item = $rowGroup[0];
                        $this->processRevenueCC(
                            $item['row'],
                            $columnIndices,
                            $divisiId,
                            $bulan,
                            $tahun,
                            $tipeRevenue,
                            $statistics,
                            $item['rowNumber']
                        );
                    }
                } catch (\Exception $e) {
                    $statistics['failed_count']++;
                    $statistics['failed_rows'][] = [
                        'row_number' => $rowGroup[0]['rowNumber'],
                        'nipnas' => $nipnas,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $errorLogPath = null;
            if (count($statistics['failed_rows']) > 0) {
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'revenue_cc');
            }

            $message = 'Import Revenue CC selesai';
            if ($statistics['duplicate_aggregated_count'] > 0) {
                $message .= " ({$statistics['duplicate_aggregated_count']} CC duplikat di-agregasi)";
            }
            if ($statistics['updated_count'] > 0 && $statistics['inserted_count'] > 0) {
                $message .= " - {$statistics['updated_count']} data di-update, {$statistics['inserted_count']} data baru";
            } elseif ($statistics['updated_count'] > 0) {
                $message .= " - {$statistics['updated_count']} data di-update";
            } elseif ($statistics['inserted_count'] > 0) {
                $message .= " - {$statistics['inserted_count']} data baru";
            }

            return [
                'success' => true,
                'message' => $message,
                'statistics' => [
                    'total_rows' => $statistics['total_rows'],
                    'success_count' => $statistics['success_count'],
                    'failed_count' => $statistics['failed_count'],
                    'updated_count' => $statistics['updated_count'],
                    'inserted_count' => $statistics['inserted_count'],
                    'duplicate_aggregated_count' => $statistics['duplicate_aggregated_count']
                ],
                'error_log_path' => $errorLogPath
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Revenue CC Error: ' . $e->getMessage());

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
     * ✅ UPDATED: Process single Revenue CC record
     */
    private function processRevenueCC(
        $row,
        $columnIndices,
        $divisiId,
        $bulan,
        $tahun,
        $tipeRevenue,
        &$statistics,
        $rowNumber,
        $overrideRevenueSold = null,
        $overrideRevenueBill = null,
        $overrideTargetRevenueSold = null
    ) {
        $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
        $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);
        $lsegmentHo = $this->getColumnValue($row, $columnIndices['LSEGMENT_HO']);
        $witelHo = $this->getColumnValue($row, $columnIndices['WITEL_HO']);
        $witelBill = $this->getColumnValue($row, $columnIndices['WITEL_BILL']);

        // Use override values if provided (for aggregation), otherwise read from row
        $revenueSold = $overrideRevenueSold ?? (float) ($this->getColumnValue($row, $columnIndices['REVENUE_SOLD']) ?? 0);
        $revenueBill = $overrideRevenueBill ?? (float) ($this->getColumnValue($row, $columnIndices['REVENUE_BILL']) ?? 0);
        $targetRevenueSold = $overrideTargetRevenueSold ?? (float) ($this->getColumnValue($row, $columnIndices['TARGET_REVENUE_SOLD']) ?? 0);

        // ✅ NEW: Parse SOURCE_DATA
        $sourceDataRaw = $this->getColumnValue($row, $columnIndices['SOURCE_DATA']) ?? '';
        $revenueSource = (strtoupper(trim($sourceDataRaw)) === 'NGTMA') ? 'NGTMA' : 'REGULER';

        // Validate required fields
        if (empty($nipnas) || empty($standardName) || empty($lsegmentHo) || empty($witelHo)) {
            $statistics['failed_count']++;
            $statistics['failed_rows'][] = [
                'row_number' => $rowNumber,
                'nipnas' => $nipnas ?? 'N/A',
                'error' => 'Kolom wajib kosong'
            ];
            return;
        }

        // Get CC
        $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
        if (!$cc) {
            $statistics['failed_count']++;
            $statistics['failed_rows'][] = [
                'row_number' => $rowNumber,
                'nipnas' => $nipnas,
                'error' => 'CC tidak ditemukan'
            ];
            return;
        }

        // ✅ Double-check segment
        $segment = DB::table('segments')
            ->where('divisi_id', $divisiId)
            ->where(function($query) use ($lsegmentHo) {
                $query->where('lsegment_ho', $lsegmentHo)
                      ->orWhere('ssegment_ho', $lsegmentHo);
            })
            ->first();

        if (!$segment) {
            $statistics['failed_count']++;
            $statistics['failed_rows'][] = [
                'row_number' => $rowNumber,
                'nipnas' => $nipnas,
                'error' => 'Segment tidak ditemukan: ' . $lsegmentHo
            ];
            return;
        }

        // Get Witels
        $witelHoRecord = DB::table('witel')->where('nama', $witelHo)->first();
        $witelBillRecord = !empty($witelBill) ? DB::table('witel')->where('nama', $witelBill)->first() : null;

        if (!$witelHoRecord) {
            $statistics['failed_count']++;
            $statistics['failed_rows'][] = [
                'row_number' => $rowNumber,
                'nipnas' => $nipnas,
                'error' => 'Witel HO tidak ditemukan: ' . $witelHo
            ];
            return;
        }

        // Check if record exists
        $existingRevenue = DB::table('cc_revenues')
            ->where('corporate_customer_id', $cc->id)
            ->where('divisi_id', $divisiId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        // ✅ UPDATED: Only save 5 revenue columns (removed redundant columns)
        $dataToSave = [
            'corporate_customer_id' => $cc->id,
            'divisi_id' => $divisiId,
            'segment_id' => $segment->id,
            'witel_ho_id' => $witelHoRecord->id,
            'witel_bill_id' => $witelBillRecord?->id,
            'nama_cc' => $standardName,
            'nipnas' => $nipnas,
            // ✅ ONLY 5 revenue columns (no real_revenue, target_revenue, target_revenue_bill)
            'real_revenue_sold' => $revenueSold,
            'real_revenue_bill' => $revenueBill,
            'target_revenue_sold' => $targetRevenueSold,
            'tipe_revenue' => $tipeRevenue, // HO or BILL
            'revenue_source' => $revenueSource, // REGULER or NGTMA
            'bulan' => $bulan,
            'tahun' => $tahun,
            'updated_at' => now()
        ];

        if ($existingRevenue) {
            // Update
            DB::table('cc_revenues')
                ->where('id', $existingRevenue->id)
                ->update($dataToSave);

            $statistics['updated_count']++;

            Log::info('✅ Updated CC Revenue', [
                'nipnas' => $nipnas,
                'tipe_revenue' => $tipeRevenue,
                'revenue_sold' => $revenueSold,
                'revenue_bill' => $revenueBill
            ]);
        } else {
            // Insert
            $dataToSave['created_at'] = now();
            DB::table('cc_revenues')->insert($dataToSave);

            $statistics['inserted_count']++;

            Log::info('✅ Inserted CC Revenue', [
                'nipnas' => $nipnas,
                'tipe_revenue' => $tipeRevenue
            ]);
        }

        // ✅ NOTE: No need to call recalculateAMRevenuesForCC()
        // AM Revenue recalculation is handled automatically by SP/Trigger v2
        // Trigger detects changes in real_revenue_sold and calls SP automatically

        $statistics['success_count']++;
        $statistics['total_rows']++;
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
     * Flexible header validation - hanya cek kolom wajib, abaikan kolom extra
     */
    private function validateHeadersFlexible($headers, $requiredColumns)
    {
        $cleanHeaders = array_map(function ($h) {
            return strtoupper(str_replace([' ', '_', '.'], '', trim($h)));
        }, $headers);

        foreach ($requiredColumns as $column) {
            $cleanColumn = strtoupper(str_replace([' ', '_', '.'], '', trim($column)));

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

    /**
     * Get column indices mapping
     */
    private function getColumnIndices($headers, $columns)
    {
        $indices = [];

        $normalizedMap = [];
        foreach ($headers as $index => $header) {
            $normalized = strtoupper(str_replace([' ', '_', '.'], '', trim($header)));
            $normalizedMap[$normalized] = $index;
        }

        foreach ($columns as $column) {
            $normalized = strtoupper(str_replace([' ', '_', '.'], '', trim($column)));
            $indices[$column] = $normalizedMap[$normalized] ?? null;
        }

        return $indices;
    }

    /**
     * Get column value safely
     */
    private function getColumnValue($row, $index)
    {
        return $index !== null && isset($row[$index]) ? trim($row[$index]) : null;
    }

    /**
     * Generate error log CSV
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

        fputcsv($handle, ['Baris', 'NIPNAS', 'Error']);
        foreach ($failedRows as $row) {
            fputcsv($handle, [
                $row['row_number'],
                $row['nipnas'] ?? 'N/A',
                $row['error']
            ]);
        }

        fclose($handle);
        return asset('storage/import_logs/' . $filename);
    }
}