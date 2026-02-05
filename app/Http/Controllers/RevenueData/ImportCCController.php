<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ImportCCController extends Controller
{
    public function downloadTemplate($type)
    {
        $templates = [
            'data-cc' => [
                'filename' => 'template_data_cc.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME'],
                'sample' => [
                    ['76590001', 'KANTOR SIPIL SEMARANG'],
                    ['76590002', 'PEMKOT SEMARANG']
                ]
            ],

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
                        'KKANTOR SIPIL SEMARANG',
                        'GOVERNMENT PUBLIC SERVICE',
                        'SEMARANG JATENG UTARA',
                        '0',
                        '0',
                        'SEMARANG JATENG UTARA',
                        '920000000',
                        'NGTMA'
                    ]
                ]
            ],

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
                    ['76590001', 'KANTOR SIPIL SEMARANG', 'GOVERNMENT PUBLIC SERVICE', 'SEMARANG JATENG UTARA',
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

    public function previewDataCC($tempFilePath)
    {
        try {
            $fullPath = $this->resolveFilePath($tempFilePath);
            
            if (!file_exists($fullPath)) {
                Log::error('Preview Data CC - File not found', [
                    'temp_file_path' => $tempFilePath,
                    'resolved_path' => $fullPath
                ]);
                
                return [
                    'success' => false,
                    'message' => 'File tidak ditemukan: ' . $tempFilePath
                ];
            }

            $csvData = $this->parseCsvFileFromPath($fullPath);

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

            $uniqueCcCount = DB::table('corporate_customers')
                ->whereIn('nipnas', array_column(array_column($detailedRows, 'data'), 'NIPNAS'))
                ->count();

            return [
                'success' => true,
                'summary' => [
                    'total_rows' => count($csvData),
                    'new_cc' => $newCount,
                    'existing_cc' => $updateCount,
                    'error_count' => $errorCount,
                    'unique_cc_count' => $uniqueCcCount
                ],
                'preview' => array_slice($detailedRows, 0, 100),
                'full_data_stored' => count($detailedRows) > 100
            ];

        } catch (\Exception $e) {
            Log::error('Preview Data CC Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat preview: ' . $e->getMessage()
            ];
        }
    }

    public function executeDataCC($tempFilePath, $filterType = 'all')
    {
        try {
            $fullPath = $this->resolveFilePath($tempFilePath);
            
            if (!file_exists($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            $csvData = $this->parseCsvFileFromPath($fullPath);

            $requiredColumns = ['NIPNAS', 'STANDARD_NAME'];
            $headers = array_shift($csvData);

            if (!$this->validateHeadersFlexible($headers, $requiredColumns)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ], 400);
            }

            $columnIndices = $this->getColumnIndices($headers, $requiredColumns);

            $statistics = [
                'total_rows' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'inserted_count' => 0,
                'updated_count' => 0,
                'skipped_count' => 0,
                'failed_rows' => []
            ];

            DB::beginTransaction();

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;
                $statistics['total_rows']++;

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
                    if ($filterType === 'new') {
                        $statistics['skipped_count']++;
                        continue;
                    }

                    DB::table('corporate_customers')
                        ->where('id', $existingCC->id)
                        ->update([
                            'nama' => $standardName,
                            'updated_at' => now()
                        ]);

                    $statistics['updated_count']++;
                } else {
                    if ($filterType === 'update') {
                        $statistics['skipped_count']++;
                        continue;
                    }

                    DB::table('corporate_customers')->insert([
                        'nipnas' => $nipnas,
                        'nama' => $standardName,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    $statistics['inserted_count']++;
                }

                $statistics['success_count']++;
            }

            DB::commit();

            $errorLogPath = null;
            if (!empty($statistics['failed_rows'])) {
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'data_cc');
            }

            return response()->json([
                'success' => true,
                'message' => "Import Data CC selesai. {$statistics['inserted_count']} baru, {$statistics['updated_count']} diupdate.",
                'statistics' => $statistics,
                'error_log_path' => $errorLogPath
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Execute Data CC Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage()
            ], 500);
        }
    }

    public function previewRevenueCC($tempFilePath, $divisiId, $bulan, $tahun, $jenisData = 'lengkap')
    {
        try {
            $fullPath = $this->resolveFilePath($tempFilePath);
            
            if (!file_exists($fullPath)) {
                Log::error('Preview Revenue CC - File not found', [
                    'temp_file_path' => $tempFilePath,
                    'resolved_path' => $fullPath
                ]);
                
                return [
                    'success' => false,
                    'message' => 'File tidak ditemukan: ' . $tempFilePath
                ];
            }

            $csvData = $this->parseCsvFileFromPath($fullPath);

            $requiredColumns = ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO'];
            
            if ($jenisData === 'revenue' || $jenisData === 'lengkap') {
                $requiredColumns[] = 'REVENUE_SOLD';
            }
            if ($jenisData === 'target' || $jenisData === 'lengkap') {
                $requiredColumns[] = 'TARGET_REVENUE_SOLD';
            }

            $headers = array_shift($csvData);

            if (!$this->validateHeadersFlexible($headers, $requiredColumns)) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            $columnIndices = $this->getColumnIndices($headers, [
                'NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO',
                'REVENUE_SOLD', 'TARGET_REVENUE_SOLD', 'WITEL_BILL', 'REVENUE_BILL', 'SOURCE_DATA'
            ]);

            $newCount = 0;
            $updateCount = 0;
            $errorCount = 0;
            $detailedRows = [];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);
                $lsegmentHo = $this->getColumnValue($row, $columnIndices['LSEGMENT_HO']);
                $witelHo = $this->getColumnValue($row, $columnIndices['WITEL_HO']);

                if (empty($nipnas) || empty($standardName) || empty($lsegmentHo) || empty($witelHo)) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => compact('nipnas', 'standardName'),
                        'error' => 'Kolom wajib kosong'
                    ];
                    continue;
                }

                $revenueSold = $this->getColumnValue($row, $columnIndices['REVENUE_SOLD']) ?? 0;
                $targetRevenueSold = $this->getColumnValue($row, $columnIndices['TARGET_REVENUE_SOLD']) ?? 0;

                if ($jenisData === 'revenue' && (empty($revenueSold) || $revenueSold == 0)) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => compact('nipnas', 'standardName'),
                        'error' => 'REVENUE_SOLD wajib diisi untuk jenis data revenue'
                    ];
                    continue;
                }

                if ($jenisData === 'target' && (empty($targetRevenueSold) || $targetRevenueSold == 0)) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => compact('nipnas', 'standardName'),
                        'error' => 'TARGET_REVENUE_SOLD wajib diisi untuk jenis data target'
                    ];
                    continue;
                }

                if ($jenisData === 'lengkap' && ((empty($revenueSold) || $revenueSold == 0) || (empty($targetRevenueSold) || $targetRevenueSold == 0))) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => compact('nipnas', 'standardName'),
                        'error' => 'REVENUE_SOLD dan TARGET_REVENUE_SOLD wajib diisi untuk jenis data lengkap'
                    ];
                    continue;
                }

                $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                if (!$cc) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => compact('nipnas', 'standardName'),
                        'error' => 'Corporate Customer tidak ditemukan'
                    ];
                    continue;
                }

                $existingRevenue = DB::table('cc_revenues')
                    ->where('corporate_customer_id', $cc->id)
                    ->where('divisi_id', $divisiId)
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->first();

                if ($existingRevenue) {
                    $updateCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'update',
                        'data' => compact('nipnas', 'standardName')
                    ];
                } else {
                    $newCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'new',
                        'data' => compact('nipnas', 'standardName')
                    ];
                }
            }

            return [
                'success' => true,
                'summary' => [
                    'total_rows' => count($csvData),
                    'new_count' => $newCount,
                    'update_count' => $updateCount,
                    'error_count' => $errorCount,
                    'unique_cc_count' => count(array_unique(array_column(array_column($detailedRows, 'data'), 'nipnas')))
                ],
                'preview' => array_slice($detailedRows, 0, 100),
                'full_data_stored' => count($detailedRows) > 100
            ];

        } catch (\Exception $e) {
            Log::error('Preview Revenue CC Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat preview: ' . $e->getMessage()
            ];
        }
    }

    public function executeRevenueCC(Request $request, $tempFilePath, $filterType = 'all')
    {
        try {
            $fullPath = $this->resolveFilePath($tempFilePath);
            
            if (!file_exists($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            $divisiId = $request->input('divisi_id');
            $bulan = (int) $request->input('month');
            $tahun = (int) $request->input('year');
            $tipeRevenue = $request->input('tipe_revenue', 'HO');
            $jenisData = $request->input('jenis_data', 'lengkap');

            $csvData = $this->parseCsvFileFromPath($fullPath);

            $requiredColumns = ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO'];
            
            if ($jenisData === 'revenue' || $jenisData === 'lengkap') {
                $requiredColumns[] = 'REVENUE_SOLD';
            }
            if ($jenisData === 'target' || $jenisData === 'lengkap') {
                $requiredColumns[] = 'TARGET_REVENUE_SOLD';
            }

            $headers = array_shift($csvData);

            if (!$this->validateHeadersFlexible($headers, $requiredColumns)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ], 400);
            }

            $columnIndices = $this->getColumnIndices($headers, [
                'NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO',
                'REVENUE_SOLD', 'TARGET_REVENUE_SOLD', 'WITEL_BILL', 'REVENUE_BILL', 'SOURCE_DATA'
            ]);

            $statistics = [
                'total_rows' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'inserted_count' => 0,
                'updated_count' => 0,
                'failed_rows' => []
            ];

            DB::beginTransaction();

            foreach ($csvData as $index => $row) {
                $this->processRevenueRow(
                    $row, 
                    $index + 2, 
                    $columnIndices, 
                    $divisiId, 
                    $bulan, 
                    $tahun, 
                    $tipeRevenue,
                    $jenisData,
                    $filterType,
                    $statistics
                );
            }

            DB::commit();

            $errorLogPath = null;
            if (!empty($statistics['failed_rows'])) {
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'revenue_cc');
            }

            return response()->json([
                'success' => true,
                'message' => "Import Revenue CC selesai. {$statistics['inserted_count']} baru, {$statistics['updated_count']} diupdate.",
                'statistics' => $statistics,
                'error_log_path' => $errorLogPath
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Execute Revenue CC Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processRevenueRow($row, $rowNumber, $columnIndices, $divisiId, $bulan, $tahun, $tipeRevenue, $jenisData, $filterType, &$statistics)
    {
        $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
        $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);
        $lsegmentHo = $this->getColumnValue($row, $columnIndices['LSEGMENT_HO']);
        $witelHo = $this->getColumnValue($row, $columnIndices['WITEL_HO']);
        $witelBill = $this->getColumnValue($row, $columnIndices['WITEL_BILL']) ?? $witelHo;
        $sourceData = $this->getColumnValue($row, $columnIndices['SOURCE_DATA']) ?? 'REGULER';

        $revenueSold = floatval($this->getColumnValue($row, $columnIndices['REVENUE_SOLD']) ?? 0);
        $targetRevenueSold = floatval($this->getColumnValue($row, $columnIndices['TARGET_REVENUE_SOLD']) ?? 0);
        $revenueBill = floatval($this->getColumnValue($row, $columnIndices['REVENUE_BILL']) ?? 0);

        if (empty($nipnas) || empty($standardName) || empty($lsegmentHo) || empty($witelHo)) {
            $statistics['failed_count']++;
            $statistics['failed_rows'][] = [
                'row_number' => $rowNumber,
                'nipnas' => $nipnas,
                'error' => 'Kolom wajib kosong'
            ];
            return;
        }

        if ($jenisData === 'revenue' && $revenueSold == 0) {
            $statistics['failed_count']++;
            $statistics['failed_rows'][] = [
                'row_number' => $rowNumber,
                'nipnas' => $nipnas,
                'error' => 'REVENUE_SOLD wajib diisi untuk jenis data revenue'
            ];
            return;
        }

        if ($jenisData === 'target' && $targetRevenueSold == 0) {
            $statistics['failed_count']++;
            $statistics['failed_rows'][] = [
                'row_number' => $rowNumber,
                'nipnas' => $nipnas,
                'error' => 'TARGET_REVENUE_SOLD wajib diisi untuk jenis data target'
            ];
            return;
        }

        if ($jenisData === 'lengkap' && ($revenueSold == 0 || $targetRevenueSold == 0)) {
            $statistics['failed_count']++;
            $statistics['failed_rows'][] = [
                'row_number' => $rowNumber,
                'nipnas' => $nipnas,
                'error' => 'REVENUE_SOLD dan TARGET_REVENUE_SOLD wajib diisi untuk jenis data lengkap'
            ];
            return;
        }

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

        $revenueSource = (strtoupper(trim($sourceData)) === 'NGTMA') ? 'NGTMA' : 'REGULER';

        $existingRevenue = DB::table('cc_revenues')
            ->where('corporate_customer_id', $cc->id)
            ->where('divisi_id', $divisiId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        if ($existingRevenue) {
            if ($filterType === 'new') {
                return;
            }

            $updateData = [
                'nama_cc' => $standardName,
                'segment_id' => $segment->id,
                'witel_ho_id' => $witelHoRecord->id,
                'witel_bill_id' => $witelBillRecord?->id,
                'tipe_revenue' => $tipeRevenue,
                'revenue_source' => $revenueSource,
                'updated_at' => now()
            ];

            if ($jenisData === 'revenue' || $jenisData === 'lengkap') {
                $updateData['real_revenue_sold'] = $revenueSold;
                $updateData['real_revenue_bill'] = $revenueBill;
            }

            if ($jenisData === 'target' || $jenisData === 'lengkap') {
                $updateData['target_revenue_sold'] = $targetRevenueSold;
            }

            DB::table('cc_revenues')
                ->where('id', $existingRevenue->id)
                ->update($updateData);

            $statistics['updated_count']++;
        } else {
            if ($filterType === 'update') {
                return;
            }

            $insertData = [
                'corporate_customer_id' => $cc->id,
                'divisi_id' => $divisiId,
                'segment_id' => $segment->id,
                'witel_ho_id' => $witelHoRecord->id,
                'witel_bill_id' => $witelBillRecord?->id,
                'nama_cc' => $standardName,
                'nipnas' => $nipnas,
                'tipe_revenue' => $tipeRevenue,
                'revenue_source' => $revenueSource,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'created_at' => now(),
                'updated_at' => now()
            ];

            if ($jenisData === 'revenue' || $jenisData === 'lengkap') {
                $insertData['real_revenue_sold'] = $revenueSold;
                $insertData['real_revenue_bill'] = $revenueBill;
            } else {
                $insertData['real_revenue_sold'] = 0;
                $insertData['real_revenue_bill'] = 0;
            }

            if ($jenisData === 'target' || $jenisData === 'lengkap') {
                $insertData['target_revenue_sold'] = $targetRevenueSold;
            } else {
                $insertData['target_revenue_sold'] = 0;
            }

            DB::table('cc_revenues')->insert($insertData);

            $statistics['inserted_count']++;
        }

        $statistics['success_count']++;
        $statistics['total_rows']++;
    }

    private function resolveFilePath($tempFilePath)
    {
        if (strpos($tempFilePath, 'storage/app') !== false || strpos($tempFilePath, 'storage\\app') !== false) {
            return $tempFilePath;
        }

        if (file_exists($tempFilePath)) {
            return $tempFilePath;
        }

        $possiblePaths = [
            storage_path('app/private/' . $tempFilePath),
            storage_path('app/' . $tempFilePath),
            storage_path($tempFilePath),
            $tempFilePath
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return storage_path('app/private/' . $tempFilePath);
    }

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
        if (!file_exists($filepath)) {
            throw new \Exception("File not found: {$filepath}");
        }

        $csvData = [];
        $handle = fopen($filepath, 'r');

        if (!$handle) {
            throw new \Exception("Cannot open file: {$filepath}");
        }

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $csvData[] = $row;
        }

        fclose($handle);
        return $csvData;
    }

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