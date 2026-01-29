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
 * ✅ FIXED VERSION - 2026-01-29
 *
 * CHANGELOG:
 * ✅ FIXED: Flexible column validation - boleh ada kolom extra di CSV
 * ✅ FIXED: Revenue column logic berbeda untuk DGS/DSS vs DPS:
 *    - DGS/DSS: REVENUE_SOLD wajib, REVENUE_BILL opsional (prioritas kedua)
 *    - DPS: REVENUE_BILL wajib, REVENUE_SOLD opsional
 * ✅ FIXED: Template download updated sesuai requirement
 * ✅ MAINTAINED: All other functionality (routes, execute, preview, etc.)
 */
class ImportCCController extends Controller
{
    /**
     * ✅ FIXED: Download Template CSV - Updated templates
     */
    public function downloadTemplate($type)
    {
        $templates = [
            // ========================================
            // DATA CC TEMPLATE
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
            // REVENUE CC - DGS TEMPLATES
            // ========================================
            'revenue-cc-dgs-real' => [
                'filename' => 'template_revenue_real_dgs.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'REVENUE_SOLD', 'SOURCE_DATA'],
                'sample' => [
                    ['76590002', 'PEMKOT SEMARANG', 'GOVERNMENT PUBLIC SERVICE', 'SEMARANG JATENG UTARA', '195000000', 'HO']
                ]
            ],

            'revenue-cc-dgs-target' => [
                'filename' => 'template_revenue_target_dgs.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'TARGET_REVENUE', 'SOURCE_DATA'],
                'sample' => [
                    ['76590002', 'PEMKOT SEMARANG', 'GOVERNMENT PUBLIC SERVICE', 'SEMARANG JATENG UTARA', '200000000', 'HO']
                ]
            ],

            // ========================================
            // REVENUE CC - DSS TEMPLATES
            // ========================================
            'revenue-cc-dss-real' => [
                'filename' => 'template_revenue_real_dss.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'REVENUE_SOLD', 'SOURCE_DATA'],
                'sample' => [
                    ['76590010', 'PT TELKOM INDONESIA', 'DIGITAL SUSTAINABILITY SERVICE', 'SEMARANG JATENG UTARA', '250000000', 'HO']
                ]
            ],

            'revenue-cc-dss-target' => [
                'filename' => 'template_revenue_target_dss.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'TARGET_REVENUE', 'SOURCE_DATA'],
                'sample' => [
                    ['76590010', 'PT TELKOM INDONESIA', 'DIGITAL SUSTAINABILITY SERVICE', 'SEMARANG JATENG UTARA', '270000000', 'HO']
                ]
            ],

            // ========================================
            // REVENUE CC - DPS TEMPLATES
            // ========================================
            'revenue-cc-dps-real' => [
                'filename' => 'template_revenue_real_dps.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'WITEL_BILL', 'REVENUE_BILL', 'SOURCE_DATA'],
                'sample' => [
                    ['76590001', 'BANK JATIM', 'FINANCIAL SERVICE', 'SEMARANG JATENG UTARA', 'SEMARANG JATENG UTARA', '920000000', 'BILL']
                ]
            ],

            'revenue-cc-dps-target' => [
                'filename' => 'template_revenue_target_dps.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'WITEL_BILL', 'TARGET_REVENUE', 'SOURCE_DATA'],
                'sample' => [
                    ['76590001', 'BANK JATIM', 'FINANCIAL SERVICE', 'SEMARANG JATENG UTARA', 'SEMARANG JATENG UTARA', '1000000000', 'BILL']
                ]
            ],

            // ========================================
            // BACKWARD COMPATIBILITY ALIASES
            // ========================================
            'revenue-cc-dgs' => [
                'filename' => 'template_revenue_real_dgs.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'REVENUE_SOLD', 'SOURCE_DATA'],
                'sample' => [
                    ['76590002', 'PEMKOT SEMARANG', 'GOVERNMENT PUBLIC SERVICE', 'SEMARANG JATENG UTARA', '195000000', 'HO']
                ]
            ],

            'revenue-cc-dss' => [
                'filename' => 'template_revenue_real_dss.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'REVENUE_SOLD', 'SOURCE_DATA'],
                'sample' => [
                    ['76590010', 'PT TELKOM INDONESIA', 'DIGITAL SUSTAINABILITY SERVICE', 'SEMARANG JATENG UTARA', '250000000', 'HO']
                ]
            ],

            'revenue-cc-dps' => [
                'filename' => 'template_revenue_real_dps.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'WITEL_BILL', 'REVENUE_BILL', 'SOURCE_DATA'],
                'sample' => [
                    ['76590001', 'BANK JATIM', 'FINANCIAL SERVICE', 'SEMARANG JATENG UTARA', 'SEMARANG JATENG UTARA', '920000000', 'BILL']
                ]
            ],

            'revenue-cc-target' => [
                'filename' => 'template_revenue_target.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'TARGET_REVENUE', 'SOURCE_DATA'],
                'sample' => [
                    ['76590002', 'PEMKOT SEMARANG', 'GOVERNMENT PUBLIC SERVICE', 'SEMARANG JATENG UTARA', '200000000', 'HO']
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

            // ✅ FIXED: Use flexible validation
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
            Log::error('Preview Data CC Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ MAINTAINED: Execute Data CC Import with filter support
     */
    public function executeDataCC($tempFilePath, $filterType = 'all')
    {
        try {
            DB::beginTransaction();

            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            $requiredColumns = ['NIPNAS', 'STANDARD_NAME'];
            $headers = array_shift($csvData);

            // ✅ FIXED: Use flexible validation
            if (!$this->validateHeadersFlexible($headers, $requiredColumns)) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            $columnIndices = $this->getColumnIndices($headers, $requiredColumns);

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
                $status = $existingCC ? 'update' : 'new';

                $rowsWithStatus[] = [
                    'index' => $index,
                    'row' => $row,
                    'status' => $status,
                    'rowNumber' => $rowNumber
                ];
            }

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
                    'skipped_count' => $statistics['skipped_count'],
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
                    'failed_count' => 0,
                    'skipped_count' => 0
                ]
            ];
        }
    }

    /**
     * ✅ FIXED: Preview Revenue CC Import
     * Enhanced with flexible column validation and proper revenue column logic
     */
    public function previewRevenueCC($tempFilePath, $divisiId, $jenisData, $year, $month)
    {
        try {
            Log::info('ICCC - Commencing previewRevenueCC');

            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            // Get divisi info untuk menentukan required columns
            $divisi = DB::table('divisi')->find($divisiId);
            if (!$divisi) {
                return [
                    'success' => false,
                    'message' => 'Divisi tidak ditemukan'
                ];
            }

            $divisiKode = strtoupper($divisi->kode);

            // ✅ FIXED: Determine required columns based on divisi and jenis_data
            $isTarget = ($jenisData === 'target');

            // Base required columns (always needed)
            $requiredColumns = [
                'NIPNAS',
                'STANDARD_NAME',
                'LSEGMENT_HO',
                'WITEL_HO'
            ];

            // ✅ FIXED: Add revenue columns based on divisi and jenis_data
            if ($isTarget) {
                $requiredColumns[] = 'TARGET_REVENUE';
            } else {
                // Real revenue - different logic for DGS/DSS vs DPS
                if (in_array($divisiKode, ['DGS', 'DSS'])) {
                    // DGS/DSS: REVENUE_SOLD wajib
                    $requiredColumns[] = 'REVENUE_SOLD';
                } else {
                    // DPS: REVENUE_BILL wajib
                    $requiredColumns[] = 'REVENUE_BILL';
                }
            }

            $headers = array_shift($csvData);

            // ✅ FIXED: Use flexible validation
            if (!$this->validateHeadersFlexible($headers, $requiredColumns)) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            // ✅ FIXED: Get all possible columns (wajib + opsional)
            $allPossibleColumns = array_merge(
                $requiredColumns,
                ['WITEL_BILL', 'SOURCE_DATA', 'REVENUE_SOLD', 'REVENUE_BILL', 'TARGET_REVENUE']
            );
            $columnIndices = $this->getColumnIndices($headers, $allPossibleColumns);

            $newCount = 0;
            $updateCount = 0;
            $errorCount = 0;
            $allRows = [];
            $uniqueNIPNAS = [];

            Log::info('ICCC - previewRev: Processing rows', [
                'divisi' => $divisiKode,
                'jenis_data' => $jenisData,
                'required_columns' => $requiredColumns
            ]);

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);
                $lsegmentHO = $this->getColumnValue($row, $columnIndices['LSEGMENT_HO']);
                $witelHO = $this->getColumnValue($row, $columnIndices['WITEL_HO']);

                if ($nipnas && !in_array($nipnas, $uniqueNIPNAS)) {
                    $uniqueNIPNAS[] = $nipnas;
                }

                if (empty($nipnas) || empty($standardName)) {
                    $errorCount++;
                    $allRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIPNAS' => $nipnas ?? 'N/A',
                            'STANDARD_NAME' => $standardName ?? 'N/A',
                            'LSEGMENT_HO' => $lsegmentHO ?? 'N/A'
                        ],
                        'error' => 'NIPNAS atau STANDARD_NAME kosong'
                    ];
                    continue;
                }

                $existingRevenue = DB::table('cc_revenues')
                    ->join('corporate_customers', 'cc_revenues.corporate_customer_id', '=', 'corporate_customers.id')
                    ->where('corporate_customers.nipnas', $nipnas)
                    ->where('cc_revenues.divisi_id', $divisiId)
                    ->where('cc_revenues.bulan', $month)
                    ->where('cc_revenues.tahun', $year)
                    ->first();

                $rowData = [
                    'row_number' => $rowNumber,
                    'data' => [
                        'NIPNAS' => $nipnas,
                        'STANDARD_NAME' => $standardName,
                        'LSEGMENT_HO' => $lsegmentHO,
                        'WITEL_HO' => $witelHO
                    ]
                ];

                // Add revenue data to preview
                if ($isTarget) {
                    $targetRevenue = $this->getColumnValue($row, $columnIndices['TARGET_REVENUE']);
                    $rowData['data']['TARGET_REVENUE'] = $targetRevenue;
                } else {
                    $revenueSold = $this->getColumnValue($row, $columnIndices['REVENUE_SOLD']);
                    $revenueBill = $this->getColumnValue($row, $columnIndices['REVENUE_BILL']);
                    $rowData['data']['REVENUE_SOLD'] = $revenueSold;
                    $rowData['data']['REVENUE_BILL'] = $revenueBill;
                }

                if ($existingRevenue) {
                    $updateCount++;
                    $rowData['status'] = 'update';
                    $rowData['old_data'] = [
                        'target_revenue' => $existingRevenue->target_revenue,
                        'real_revenue' => $existingRevenue->real_revenue
                    ];
                } else {
                    $newCount++;
                    $rowData['status'] = 'new';
                }

                $allRows[] = $rowData;
            }

            Log::info('ICCC - previewRev: Processing complete');

            $previewRows = array_slice($allRows, 0, 5);

            return [
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_rows' => count($allRows),
                        'unique_cc_count' => count($uniqueNIPNAS),
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
            Log::error('Preview Revenue CC Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ FIXED: Execute Revenue CC Import
     * Enhanced with flexible column validation and proper revenue column logic
     */
    public function executeRevenueCC($tempFilePath, $divisiId, $jenisData, $year, $month, $filterType = 'all')
    {
        try {
            DB::beginTransaction();

            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            // Get divisi info
            $divisi = DB::table('divisi')->find($divisiId);
            if (!$divisi) {
                return [
                    'success' => false,
                    'message' => 'Divisi tidak ditemukan'
                ];
            }

            $divisiKode = strtoupper($divisi->kode);
            $isTarget = ($jenisData === 'target');

            // ✅ FIXED: Determine required columns
            $requiredColumns = [
                'NIPNAS',
                'STANDARD_NAME',
                'LSEGMENT_HO',
                'WITEL_HO'
            ];

            if ($isTarget) {
                $requiredColumns[] = 'TARGET_REVENUE';
            } else {
                if (in_array($divisiKode, ['DGS', 'DSS'])) {
                    $requiredColumns[] = 'REVENUE_SOLD';
                } else {
                    $requiredColumns[] = 'REVENUE_BILL';
                }
            }

            $headers = array_shift($csvData);

            // ✅ FIXED: Use flexible validation
            if (!$this->validateHeadersFlexible($headers, $requiredColumns)) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            $allPossibleColumns = array_merge(
                $requiredColumns,
                ['WITEL_BILL', 'SOURCE_DATA', 'REVENUE_SOLD', 'REVENUE_BILL', 'TARGET_REVENUE']
            );
            $columnIndices = $this->getColumnIndices($headers, $allPossibleColumns);

            $statistics = [
                'total_rows' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'skipped_count' => 0,
                'updated_count' => 0,
                'inserted_count' => 0,
                'recalculated_am_count' => 0,
                'failed_rows' => []
            ];

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

                $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                $status = $cc ? 'update' : 'new';

                $rowsWithStatus[] = [
                    'index' => $index,
                    'row' => $row,
                    'status' => $status,
                    'rowNumber' => $rowNumber
                ];
            }

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
                    $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);
                    $lsegmentHO = $this->getColumnValue($row, $columnIndices['LSEGMENT_HO']);
                    $witelHO = $this->getColumnValue($row, $columnIndices['WITEL_HO']);

                    if (empty($nipnas) || empty($standardName)) {
                        $statistics['failed_count']++;
                        $statistics['failed_rows'][] = [
                            'row_number' => $rowNumber,
                            'nipnas' => $nipnas ?? 'N/A',
                            'error' => 'NIPNAS atau STANDARD_NAME kosong'
                        ];
                        continue;
                    }

                    // Get or create CC
                    $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                    if (!$cc) {
                        $ccId = DB::table('corporate_customers')->insertGetId([
                            'nipnas' => $nipnas,
                            'nama' => $standardName,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    } else {
                        $ccId = $cc->id;
                        DB::table('corporate_customers')
                            ->where('id', $ccId)
                            ->update([
                                'nama' => $standardName,
                                'updated_at' => now()
                            ]);
                    }

                    // Get segment
                    $segment = DB::table('segments')
                        ->where('lsegment_ho', $lsegmentHO)
                        ->where('divisi_id', $divisiId)
                        ->first();

                    if (!$segment) {
                        $statistics['failed_count']++;
                        $statistics['failed_rows'][] = [
                            'row_number' => $rowNumber,
                            'nipnas' => $nipnas,
                            'error' => 'Segment tidak ditemukan: ' . $lsegmentHO
                        ];
                        continue;
                    }

                    // Get witel HO
                    $witelHORecord = DB::table('witel')->where('nama', $witelHO)->first();
                    $witelHOId = $witelHORecord ? $witelHORecord->id : null;

                    // ✅ FIXED: Determine revenue source and values based on divisi
                    $revenueSource = 'HO';
                    $targetRevenue = 0;
                    $realRevenue = 0;
                    $witelBillId = null;

                    if ($isTarget) {
                        // Target revenue
                        $targetRevenue = (float) $this->getColumnValue($row, $columnIndices['TARGET_REVENUE']) ?? 0;
                    } else {
                        // Real revenue - different logic for DGS/DSS vs DPS
                        if (in_array($divisiKode, ['DGS', 'DSS'])) {
                            // DGS/DSS: REVENUE_SOLD wajib, REVENUE_BILL opsional (prioritas kedua)
                            $revenueSource = 'HO';
                            $realRevenue = (float) $this->getColumnValue($row, $columnIndices['REVENUE_SOLD']) ?? 0;

                            // ✅ FIXED: Jika REVENUE_BILL ada, gunakan sebagai prioritas kedua
                            $revenueBill = $this->getColumnValue($row, $columnIndices['REVENUE_BILL']);
                            if (!empty($revenueBill)) {
                                $revenueBillValue = (float) $revenueBill;
                                // Jika REVENUE_SOLD kosong tapi REVENUE_BILL ada, gunakan REVENUE_BILL
                                if ($realRevenue == 0 && $revenueBillValue > 0) {
                                    $realRevenue = $revenueBillValue;
                                    $revenueSource = 'BILL';
                                }
                            }
                        } else {
                            // DPS: REVENUE_BILL wajib, REVENUE_SOLD opsional
                            $revenueSource = 'BILL';
                            $realRevenue = (float) $this->getColumnValue($row, $columnIndices['REVENUE_BILL']) ?? 0;

                            // Get WITEL_BILL untuk DPS
                            $witelBillName = $this->getColumnValue($row, $columnIndices['WITEL_BILL']);
                            if ($witelBillName) {
                                $witelBillRecord = DB::table('witel')->where('nama', $witelBillName)->first();
                                $witelBillId = $witelBillRecord ? $witelBillRecord->id : null;
                            }

                            // ✅ FIXED: Jika REVENUE_SOLD ada, catat juga (opsional)
                            $revenueSold = $this->getColumnValue($row, $columnIndices['REVENUE_SOLD']);
                            if (!empty($revenueSold)) {
                                $revenueSoldValue = (float) $revenueSold;
                                // Jika REVENUE_BILL kosong tapi REVENUE_SOLD ada, gunakan REVENUE_SOLD
                                if ($realRevenue == 0 && $revenueSoldValue > 0) {
                                    $realRevenue = $revenueSoldValue;
                                    $revenueSource = 'HO';
                                }
                            }
                        }
                    }

                    // Check if revenue already exists
                    $existingRevenue = DB::table('cc_revenues')
                        ->where('corporate_customer_id', $ccId)
                        ->where('divisi_id', $divisiId)
                        ->where('bulan', $month)
                        ->where('tahun', $year)
                        ->first();

                    if ($existingRevenue) {
                        // Update existing
                        $updateData = [
                            'segment_id' => $segment->id,
                            'witel_ho_id' => $witelHOId,
                            'nama_cc' => $standardName,
                            'nipnas' => $nipnas,
                            'revenue_source' => $revenueSource,
                            'updated_at' => now()
                        ];

                        if ($isTarget) {
                            $updateData['target_revenue'] = $targetRevenue;
                        } else {
                            $updateData['real_revenue'] = $realRevenue;
                            if ($witelBillId) {
                                $updateData['witel_bill_id'] = $witelBillId;
                            }
                        }

                        DB::table('cc_revenues')
                            ->where('id', $existingRevenue->id)
                            ->update($updateData);

                        $statistics['updated_count']++;

                        // Recalculate AM revenues
                        $newTargetRevenue = $isTarget ? $targetRevenue : $existingRevenue->target_revenue;
                        $newRealRevenue = $isTarget ? $existingRevenue->real_revenue : $realRevenue;

                        $amUpdated = $this->recalculateAMRevenuesForCC(
                            $ccId,
                            $divisiId,
                            $month,
                            $year,
                            $existingRevenue->target_revenue,
                            $existingRevenue->real_revenue,
                            $newTargetRevenue,
                            $newRealRevenue
                        );

                        $statistics['recalculated_am_count'] += $amUpdated;
                    } else {
                        // Insert new
                        $insertData = [
                            'corporate_customer_id' => $ccId,
                            'divisi_id' => $divisiId,
                            'segment_id' => $segment->id,
                            'witel_ho_id' => $witelHOId,
                            'nama_cc' => $standardName,
                            'nipnas' => $nipnas,
                            'target_revenue' => $isTarget ? $targetRevenue : 0,
                            'real_revenue' => $isTarget ? 0 : $realRevenue,
                            'revenue_source' => $revenueSource,
                            'tipe_revenue' => 'REGULER',
                            'bulan' => $month,
                            'tahun' => $year,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];

                        if ($witelBillId) {
                            $insertData['witel_bill_id'] = $witelBillId;
                        }

                        DB::table('cc_revenues')->insert($insertData);
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
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'revenue_cc');
            }

            $message = 'Import Revenue CC selesai';
            if ($statistics['updated_count'] > 0 && $statistics['inserted_count'] > 0) {
                $message .= " ({$statistics['updated_count']} data di-update, {$statistics['inserted_count']} data baru";
                if ($statistics['recalculated_am_count'] > 0) {
                    $message .= ", {$statistics['recalculated_am_count']} AM revenues recalculated";
                }
                $message .= ")";
            } elseif ($statistics['updated_count'] > 0) {
                $message .= " ({$statistics['updated_count']} data di-update";
                if ($statistics['recalculated_am_count'] > 0) {
                    $message .= ", {$statistics['recalculated_am_count']} AM revenues recalculated";
                }
                $message .= ")";
            } elseif ($statistics['inserted_count'] > 0) {
                $message .= " ({$statistics['inserted_count']} data baru)";
            }

            return [
                'success' => true,
                'message' => $message,
                'statistics' => $statistics,
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
     * ✅ FIXED: Flexible header validation - hanya cek kolom wajib, abaikan kolom extra
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

    /**
     * Recalculate AM Revenues - ALWAYS UPDATE
     */
    private function recalculateAMRevenuesForCC(
        $ccId,
        $divisiId,
        $bulan,
        $tahun,
        $oldTargetRevenue,
        $oldRealRevenue,
        $newTargetRevenue,
        $newRealRevenue
    ) {
        try {
            Log::info('🔍 recalculateAMRevenuesForCC CALLED', [
                'cc_id' => $ccId,
                'divisi_id' => $divisiId,
                'periode' => "{$tahun}-{$bulan}",
                'new_target' => $newTargetRevenue,
                'new_real' => $newRealRevenue
            ]);

            $amRevenues = DB::table('am_revenues')
                ->where('corporate_customer_id', $ccId)
                ->where('divisi_id', $divisiId)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get();

            if ($amRevenues->isEmpty()) {
                Log::warning('⚠️ NO AM REVENUES FOUND', [
                    'cc_id' => $ccId,
                    'divisi_id' => $divisiId,
                    'periode' => "{$tahun}-{$bulan}"
                ]);
                return 0;
            }

            Log::info('✅ Found AM Revenues', ['count' => $amRevenues->count()]);

            $updatedCount = 0;

            foreach ($amRevenues as $amRevenue) {
                $proporsi = $amRevenue->proporsi;
                if ($proporsi > 1) {
                    $proporsi = $proporsi / 100;
                }

                $newTargetAM = $newTargetRevenue * $proporsi;
                $newRealAM = $newRealRevenue * $proporsi;
                $achievementRate = $newTargetAM > 0 ? ($newRealAM / $newTargetAM) * 100 : 0;

                $updateData = [
                    'target_revenue' => $newTargetAM,
                    'real_revenue' => $newRealAM,
                    'achievement_rate' => round($achievementRate, 2),
                    'updated_at' => now()
                ];

                try {
                    DB::table('am_revenues')
                        ->where('id', $amRevenue->id)
                        ->update($updateData);

                    $updatedCount++;

                    Log::info('✅ AM Updated', [
                        'am_id' => $amRevenue->id,
                        'proporsi' => $proporsi,
                        'new_target' => $newTargetAM,
                        'new_real' => $newRealAM
                    ]);
                } catch (\Exception $updateEx) {
                    Log::error('❌ Failed to update AM', [
                        'am_id' => $amRevenue->id,
                        'error' => $updateEx->getMessage()
                    ]);
                }
            }

            Log::info('✅ Recalculation Complete', [
                'cc_id' => $ccId,
                'updated_count' => $updatedCount
            ]);

            return $updatedCount;
        } catch (\Exception $e) {
            Log::error('❌ Recalculate Error: ' . $e->getMessage(), [
                'cc_id' => $ccId,
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }
}