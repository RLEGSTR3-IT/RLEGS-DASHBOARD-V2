<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * ImportCCController - Corporate Customer Import Handler
 *
 * FIXED VERSION - 2025-10-31 23:58
 *
 * ✅ FIXED: Support TARGET_REVENUE (untuk jenis_data='target')
 * ✅ FIXED: Support REVENUE_SOLD/REVENUE_BILL (untuk jenis_data='revenue')
 * ✅ LOGIC:
 *    - jenis_data='revenue' → DPS: REVENUE_BILL, DGS/DSS: REVENUE_SOLD
 *    - jenis_data='target' → Semua divisi: TARGET_REVENUE
 */
class ImportCCController extends Controller
{
    /**
     * Download Template CSV
     */
    public function downloadTemplate($type)
    {
        $templates = [
            'data-cc' => [
                'filename' => 'template_data_cc.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME'],
                'sample' => [
                    ['76590001', 'BANK JATIM'],
                    ['76590002', 'PEMKOT SEMARANG']
                ]
            ],
            'revenue-cc-dgs-real' => [
                'filename' => 'template_revenue_cc_dgs_real.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'REVENUE_SOLD', 'SOURCE_DATA'],
                'sample' => [
                    ['76590002', 'PEMKOT SEMARANG', 'GOVERNMENT PUBLIC SERVICE', 'SEMARANG JATENG UTARA', '195000000', 'HO']
                ]
            ],
            'revenue-cc-dps-real' => [
                'filename' => 'template_revenue_cc_dps_real.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'WITEL_BILL', 'REVENUE_BILL', 'SOURCE_DATA'],
                'sample' => [
                    ['76590021', 'PT TELKOMSEL', 'RETAIL & MEDIA SERVICE', 'SEMARANG JATENG UTARA', 'SEMARANG JATENG UTARA', '920000000', 'BILL']
                ]
            ],
            'revenue-cc-target' => [
                'filename' => 'template_revenue_cc_target.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'TARGET_REVENUE', 'SOURCE_DATA'],
                'sample' => [
                    ['76590002', 'PEMKOT SEMARANG', 'GOVERNMENT PUBLIC SERVICE', 'SEMARANG JATENG UTARA', '200000000', 'HO']
                ]
            ],
            'revenue-cc-dps-target' => [
                'filename' => 'template_revenue_cc_dps_target.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'WITEL_BILL', 'TARGET_REVENUE', 'SOURCE_DATA'],
                'sample' => [
                    ['76590021', 'PT TELKOMSEL', 'RETAIL & MEDIA SERVICE', 'SEMARANG JATENG UTARA', 'SEMARANG JATENG UTARA', '950000000', 'BILL']
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
     * Preview Data CC Import
     */
    public function previewDataCC($tempFilePath)
    {
        try {
            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            $requiredColumns = ['NIPNAS', 'STANDARD_NAME'];
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
            Log::error('Preview Data CC Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Execute Data CC Import
     */
    public function executeDataCC($request)
    {
        DB::beginTransaction();

        try {
            $tempFilePath = $request instanceof Request ? $request->input('temp_file') : $request;

            if (!$tempFilePath || !file_exists($tempFilePath)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File temporary tidak ditemukan'
                ];
            }

            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            $requiredColumns = ['NIPNAS', 'STANDARD_NAME'];
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
                'updated_count' => 0,
                'inserted_count' => 0,
                'failed_rows' => []
            ];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                    $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);

                    if (empty($nipnas) || empty($standardName)) {
                        throw new \Exception('NIPNAS atau STANDARD_NAME kosong');
                    }

                    $existingCC = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();

                    if ($existingCC) {
                        DB::table('corporate_customers')
                            ->where('id', $existingCC->id)
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
     * Preview Revenue CC Import
     */
    public function previewRevenueCC($tempFilePath, $divisiId, $jenisData, $year = null, $month = null)
    {
        try {
            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            // Get divisi info
            $divisi = DB::table('divisi')->where('id', $divisiId)->first();
            if (!$divisi) {
                return [
                    'success' => false,
                    'message' => 'Divisi tidak ditemukan'
                ];
            }

            // ✅ REQUIRED COLUMNS BASED ON jenis_data
            $requiredColumns = ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO'];

            if (strtolower($jenisData) === 'target') {
                // Target revenue: semua divisi pakai TARGET_REVENUE
                $requiredColumns[] = 'TARGET_REVENUE';
                if ($divisi->kode === 'DPS') {
                    $requiredColumns[] = 'WITEL_BILL';
                } else {
                    $requiredColumns[] = 'WITEL_HO';
                }
            } else {
                // Real revenue: DPS pakai REVENUE_BILL, DGS/DSS pakai REVENUE_SOLD
                if ($divisi->kode === 'DPS') {
                    $requiredColumns[] = 'WITEL_BILL';
                    $requiredColumns[] = 'REVENUE_BILL';
                } else {
                    $requiredColumns[] = 'WITEL_HO';
                    $requiredColumns[] = 'REVENUE_SOLD';
                }
            }

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

                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                $lsegmentHO = $this->getColumnValue($row, $columnIndices['LSEGMENT_HO']);

                if (empty($nipnas) || empty($lsegmentHO)) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIPNAS' => $nipnas ?? 'N/A',
                            'LSEGMENT_HO' => $lsegmentHO ?? 'N/A'
                        ],
                        'error' => 'Data tidak lengkap'
                    ];
                    continue;
                }

                // Check if CC exists
                $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                if (!$cc) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIPNAS' => $nipnas,
                            'CUSTOMER' => 'Not found'
                        ],
                        'error' => 'Corporate Customer tidak ditemukan. Import Data CC terlebih dahulu.'
                    ];
                    continue;
                }

                // Check if revenue already exists (if year/month provided)
                if ($year && $month) {
                    $existingRevenue = DB::table('cc_revenues')
                        ->where('corporate_customer_id', $cc->id)
                        ->where('divisi_id', $divisiId)
                        ->where('tahun', $year)
                        ->where('bulan', $month)
                        ->first();

                    if ($existingRevenue) {
                        $updateCount++;
                        $detailedRows[] = [
                            'row_number' => $rowNumber,
                            'status' => 'update',
                            'data' => [
                                'NIPNAS' => $nipnas,
                                'CUSTOMER' => $cc->nama,
                                'SEGMENT' => $lsegmentHO
                            ]
                        ];
                    } else {
                        $newCount++;
                        $detailedRows[] = [
                            'row_number' => $rowNumber,
                            'status' => 'new',
                            'data' => [
                                'NIPNAS' => $nipnas,
                                'CUSTOMER' => $cc->nama,
                                'SEGMENT' => $lsegmentHO
                            ]
                        ];
                    }
                } else {
                    $newCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'new',
                        'data' => [
                            'NIPNAS' => $nipnas,
                            'CUSTOMER' => $cc->nama,
                            'SEGMENT' => $lsegmentHO
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
                        'total_rows' => count($detailedRows)
                    ],
                    'rows' => $detailedRows
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Preview Revenue CC Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ FIXED: Execute Revenue CC Import
     * - jenis_data='revenue': DPS→REVENUE_BILL, DGS/DSS→REVENUE_SOLD
     * - jenis_data='target': Semua divisi→TARGET_REVENUE
     */
    public function executeRevenueCC($request)
    {
        DB::beginTransaction();

        try {
            // Extract parameters
            $tempFilePath = $request instanceof Request ? $request->input('temp_file') : $request;
            $divisiId = $request instanceof Request ? $request->input('divisi_id') : null;
            $jenisData = $request instanceof Request ? $request->input('jenis_data') : null;

            // TAHUN & BULAN DARI FORM
            $year = $request instanceof Request ? $request->input('year') : null;
            $month = $request instanceof Request ? $request->input('month') : null;

            if (!$tempFilePath || !file_exists($tempFilePath)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File temporary tidak ditemukan'
                ];
            }

            if (!$divisiId || !$jenisData || !$year || !$month) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Parameter tidak lengkap (divisi_id, jenis_data, year, month diperlukan)'
                ];
            }

            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            // Get divisi info
            $divisi = DB::table('divisi')->where('id', $divisiId)->first();
            if (!$divisi) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Divisi tidak ditemukan'
                ];
            }

            // ✅ Required columns based on jenis_data
            $requiredColumns = ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO'];

            // ✅ LOGIC: jenis_data='target' → TARGET_REVENUE, jenis_data='revenue' → REVENUE_SOLD/REVENUE_BILL
            if (strtolower($jenisData) === 'target') {
                $requiredColumns[] = 'TARGET_REVENUE';
                $revenueColumn = 'TARGET_REVENUE';
            } else {
                if ($divisi->kode === 'DPS') {
                    $requiredColumns[] = 'REVENUE_BILL';
                    $revenueColumn = 'REVENUE_BILL';
                } else {
                    $requiredColumns[] = 'REVENUE_SOLD';
                    $revenueColumn = 'REVENUE_SOLD';
                }
            }

            // WITEL logic tetap sama
            if ($divisi->kode === 'DPS') {
                $requiredColumns[] = 'WITEL_BILL';
                $witelColumn = 'WITEL_BILL';
                $revenueSource = 'BILL';
            } else {
                $requiredColumns[] = 'WITEL_HO';
                $witelColumn = 'WITEL_HO';
                $revenueSource = 'HO';
            }

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
                'updated_count' => 0,
                'inserted_count' => 0,
                'failed_rows' => []
            ];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                    $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);
                    $lsegmentHO = $this->getColumnValue($row, $columnIndices['LSEGMENT_HO']);
                    $witelName = $this->getColumnValue($row, $columnIndices[$witelColumn]);
                    $revenueValue = $this->getColumnValue($row, $columnIndices[$revenueColumn]);

                    if (empty($nipnas) || empty($lsegmentHO) || empty($witelName)) {
                        throw new \Exception('Data tidak lengkap (NIPNAS, LSEGMENT_HO, atau WITEL kosong)');
                    }

                    // Get or create CC
                    $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                    if (!$cc) {
                        $ccId = DB::table('corporate_customers')->insertGetId([
                            'nipnas' => $nipnas,
                            'nama' => $standardName ?? $nipnas,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $cc = DB::table('corporate_customers')->where('id', $ccId)->first();
                    }

                    // Get Witel by nama
                    $witel = DB::table('witel')
                        ->whereRaw('UPPER(TRIM(nama)) = ?', [strtoupper(trim($witelName))])
                        ->first();

                    if (!$witel) {
                        throw new \Exception("Witel '{$witelName}' tidak ditemukan di database");
                    }

                    // Get Segment by lsegment_ho
                    $segment = DB::table('segments')
                        ->whereRaw('UPPER(TRIM(lsegment_ho)) = ?', [strtoupper(trim($lsegmentHO))])
                        ->where('divisi_id', $divisiId)
                        ->first();

                    if (!$segment) {
                        throw new \Exception("Segment '{$lsegmentHO}' tidak ditemukan untuk divisi {$divisi->nama}");
                    }

                    // Parse revenue value
                    $revenue = (float) str_replace([',', '.00'], ['', ''], $revenueValue ?? 0);

                    // Prepare data
                    $dataToSave = [
                        'corporate_customer_id' => $cc->id,
                        'divisi_id' => $divisiId,
                        'segment_id' => $segment->id,
                        'nama_cc' => $cc->nama,
                        'nipnas' => $cc->nipnas,
                        'revenue_source' => $revenueSource,
                        'tipe_revenue' => 'REGULER',
                        'bulan' => $month,
                        'tahun' => $year,
                        'updated_at' => now()
                    ];

                    // WITEL logic
                    if ($divisi->kode === 'DPS') {
                        $dataToSave['witel_bill_id'] = $witel->id;
                        $dataToSave['witel_ho_id'] = null;
                    } else {
                        $dataToSave['witel_ho_id'] = $witel->id;
                        $dataToSave['witel_bill_id'] = null;
                    }

                    // Check if exists
                    $existingRevenue = DB::table('cc_revenues')
                        ->where('corporate_customer_id', $cc->id)
                        ->where('divisi_id', $divisiId)
                        ->where('tahun', $year)
                        ->where('bulan', $month)
                        ->first();

                    if ($existingRevenue) {
                        // Update existing
                        if (strtolower($jenisData) === 'target') {
                            $dataToSave['target_revenue'] = $revenue;
                            $dataToSave['real_revenue'] = $existingRevenue->real_revenue;
                        } else {
                            $dataToSave['target_revenue'] = $existingRevenue->target_revenue;
                            $dataToSave['real_revenue'] = $revenue;
                        }

                        DB::table('cc_revenues')
                            ->where('id', $existingRevenue->id)
                            ->update($dataToSave);

                        $statistics['updated_count']++;
                    } else {
                        // Insert new
                        if (strtolower($jenisData) === 'target') {
                            $dataToSave['target_revenue'] = $revenue;
                            $dataToSave['real_revenue'] = 0;
                        } else {
                            $dataToSave['real_revenue'] = $revenue;
                            $dataToSave['target_revenue'] = 0;
                        }

                        $dataToSave['created_at'] = now();
                        DB::table('cc_revenues')->insert($dataToSave);

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

    // ==================== HELPER METHODS ====================

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
            return strtoupper(str_replace([' ', '_', '.'], '', trim($h)));
        }, $headers);

        foreach ($requiredColumns as $column) {
            $cleanColumn = strtoupper(str_replace([' ', '_', '.'], '', trim($column)));

            if (!in_array($cleanColumn, $cleanHeaders)) {
                return false;
            }
        }
        return true;
    }

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