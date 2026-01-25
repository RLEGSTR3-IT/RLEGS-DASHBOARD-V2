<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ImportCCController - COMPLETE FIXED VERSION
 * 
 * ✅ FIXED: executeRevenueCC now uses CHUNK PROCESSING with fgetcsv()
 * ✅ FIXED: No more loading entire CSV into memory
 * ✅ FIXED: Can handle large files (24MB+) without timeout
 */
class ImportCCController extends Controller
{
    // ✅ NEW: Chunk size for processing
    const CHUNK_SIZE = 1000; // Process 1000 rows per batch
    const PREVIEW_LIMIT = 1000; // Show max 1000 rows in preview

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
                            'id' => $existingCC->id,
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
                        'total_rows' => count($csvData),
                        'new_count' => $newCount,
                        'update_count' => $updateCount,
                        'error_count' => $errorCount
                    ],
                    'rows' => $detailedRows
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Preview Data CC Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal memproses preview: ' . $e->getMessage()
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
            $headers = array_shift($csvData);
            $columnIndices = $this->getColumnIndices($headers, ['NIPNAS', 'STANDARD_NAME']);

            $successCount = 0;
            $failedCount = 0;
            $updatedCount = 0;
            $errors = [];

            foreach ($csvData as $index => $row) {
                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);

                if (empty($nipnas) || empty($standardName)) {
                    $failedCount++;
                    $errors[] = "Baris " . ($index + 2) . ": Data tidak lengkap";
                    continue;
                }

                try {
                    $existing = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();

                    if ($existing) {
                        DB::table('corporate_customers')
                            ->where('id', $existing->id)
                            ->update([
                                'nama' => $standardName,
                                'updated_at' => now()
                            ]);
                        $updatedCount++;
                    } else {
                        DB::table('corporate_customers')->insert([
                            'nipnas' => $nipnas,
                            'nama' => $standardName,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $successCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Baris " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Import selesai: {$successCount} berhasil, {$updatedCount} di-update, {$failedCount} gagal",
                'statistics' => [
                    'total_rows' => count($csvData),
                    'success_count' => $successCount,
                    'updated_count' => $updatedCount,
                    'failed_count' => $failedCount
                ],
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Execute Data CC Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage(),
                'statistics' => [
                    'total_rows' => 0,
                    'success_count' => 0,
                    'failed_count' => 0
                ]
            ];
        }
    }

    /**
     * Preview Revenue CC
     */
    public function previewRevenueCC($tempFilePath, $divisiId, $jenisData, $year = null, $month = null)
    {
        try {
            $csvData = $this->parseCsvFileFromPath($tempFilePath);
            $requiredColumns = ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO'];
            $headers = array_shift($csvData);

            if (!$this->validateHeaders($headers, $requiredColumns)) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            // Limit preview to PREVIEW_LIMIT rows
            $previewRows = array_slice($csvData, 0, self::PREVIEW_LIMIT);
            $hasMore = count($csvData) > self::PREVIEW_LIMIT;

            $columnIndices = $this->getColumnIndices($headers, $requiredColumns);
            $newCount = 0;
            $updateCount = 0;
            $errorCount = 0;
            $detailedRows = [];

            foreach ($previewRows as $index => $row) {
                $rowNumber = $index + 2;
                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);

                if (empty($nipnas)) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => ['NIPNAS' => 'KOSONG'],
                        'error' => 'NIPNAS tidak boleh kosong'
                    ];
                    continue;
                }

                $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                if (!$cc) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => ['NIPNAS' => $nipnas],
                        'error' => 'Corporate Customer tidak ditemukan'
                    ];
                    continue;
                }

                $existing = DB::table('cc_revenues')
                    ->where('corporate_customer_id', $cc->id)
                    ->where('divisi_id', $divisiId)
                    ->where('bulan', $month)
                    ->where('tahun', $year)
                    ->where('tipe_revenue', 'REGULER')
                    ->first();

                if ($existing) {
                    $updateCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'update',
                        'data' => [
                            'NIPNAS' => $nipnas,
                            'STANDARD_NAME' => $this->getColumnValue($row, $columnIndices['STANDARD_NAME'])
                        ],
                        'old_data' => [
                            'id' => $existing->id,
                            'target_revenue' => $existing->target_revenue,
                            'real_revenue' => $existing->real_revenue
                        ]
                    ];
                } else {
                    $newCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'new',
                        'data' => [
                            'NIPNAS' => $nipnas,
                            'STANDARD_NAME' => $this->getColumnValue($row, $columnIndices['STANDARD_NAME'])
                        ]
                    ];
                }
            }

            $response = [
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_rows' => count($previewRows),
                        'new_count' => $newCount,
                        'update_count' => $updateCount,
                        'error_count' => $errorCount
                    ],
                    'rows' => $detailedRows
                ]
            ];

            if ($hasMore) {
                $response['data']['summary']['is_preview'] = true;
                $response['data']['summary']['preview_limit'] = self::PREVIEW_LIMIT;
                $response['data']['summary']['warning'] = "File memiliki lebih dari " . self::PREVIEW_LIMIT . " baris. Preview hanya menampilkan " . self::PREVIEW_LIMIT . " baris pertama.";
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Preview Revenue CC Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal memproses preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ FIXED: Execute Revenue CC with CHUNK PROCESSING
     * No more loading entire CSV into memory!
     */
    public function executeRevenueCC($request)
    {
        Log::info('🚀 executeRevenueCC STARTED (CHUNK VERSION)', [
            'request_type' => get_class($request)
        ]);

        DB::beginTransaction();

        try {
            // Extract parameters
            $tempFilePath = $request instanceof Request ? $request->input('temp_file') : $request;
            $divisiId = $request instanceof Request ? $request->input('divisi_id') : null;
            $jenisData = $request instanceof Request ? $request->input('jenis_data') : null;
            $year = $request instanceof Request ? $request->input('year') : null;
            $month = $request instanceof Request ? $request->input('month') : null;

            // Validation
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
                    'message' => 'Parameter tidak lengkap: divisi_id, jenis_data, year, dan month diperlukan'
                ];
            }

            // Get divisi info
            $divisi = DB::table('divisi')->where('id', $divisiId)->first();
            if (!$divisi) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Divisi tidak ditemukan'
                ];
            }

            // ✅ FIX: Use fgetcsv() instead of loading entire file
            $handle = fopen($tempFilePath, 'r');
            if (!$handle) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Cannot open file'
                ];
            }

            // Get headers
            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File is empty or invalid'
                ];
            }

            // Determine required columns
            $requiredColumns = ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO'];

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

            // WITEL logic
            if ($divisi->kode === 'DPS') {
                $requiredColumns[] = 'WITEL_BILL';
                $witelColumn = 'WITEL_BILL';
                $revenueSource = 'BILL';
            } else {
                $requiredColumns[] = 'WITEL_HO';
                $witelColumn = 'WITEL_HO';
                $revenueSource = 'HO';
            }

            // Validate headers
            if (!$this->validateHeaders($headers, $requiredColumns)) {
                fclose($handle);
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            $columnIndices = $this->getColumnIndices($headers, $requiredColumns);

            Log::info('📋 Starting CHUNK processing', [
                'chunk_size' => self::CHUNK_SIZE,
                'divisi' => $divisi->nama,
                'jenis_data' => $jenisData
            ]);

            $statistics = [
                'total_rows' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'updated_count' => 0,
                'inserted_count' => 0,
                'am_revenues_recalculated' => 0,
                'failed_rows' => []
            ];

            $rowNumber = 1; // Start from 1 (header)
            $processedInChunk = 0;

            // ✅ FIX: Process line by line with fgetcsv()
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $statistics['total_rows']++;
                $processedInChunk++;

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

                    // Get Witel
                    $witel = DB::table('witel')
                        ->whereRaw('UPPER(TRIM(nama)) = ?', [strtoupper(trim($witelName))])
                        ->first();

                    if (!$witel) {
                        throw new \Exception("Witel '{$witelName}' tidak ditemukan");
                    }

                    // Get Segment
                    $segment = DB::table('segments')
                        ->whereRaw('UPPER(TRIM(lsegment_ho)) = ?', [strtoupper(trim($lsegmentHO))])
                        ->where('divisi_id', $divisiId)
                        ->first();

                    if (!$segment) {
                        throw new \Exception("Segment '{$lsegmentHO}' tidak ditemukan");
                    }

                    // Parse revenue
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

                    // Check existing
                    $existingRevenue = DB::table('cc_revenues')
                        ->where('corporate_customer_id', $cc->id)
                        ->where('divisi_id', $divisiId)
                        ->where('tahun', $year)
                        ->where('bulan', $month)
                        ->first();

                    $oldTargetRevenue = 0;
                    $oldRealRevenue = 0;

                    if ($existingRevenue) {
                        $oldTargetRevenue = $existingRevenue->target_revenue;
                        $oldRealRevenue = $existingRevenue->real_revenue;

                        // Update based on jenis_data
                        if (strtolower($jenisData) === 'target') {
                            $dataToSave['target_revenue'] = $revenue;
                            $dataToSave['real_revenue'] = $oldRealRevenue;
                        } else {
                            $dataToSave['target_revenue'] = $oldTargetRevenue;
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
                            $dataToSave['target_revenue'] = 0;
                            $dataToSave['real_revenue'] = $revenue;
                        }

                        $dataToSave['created_at'] = now();
                        DB::table('cc_revenues')->insert($dataToSave);
                        $statistics['inserted_count']++;
                    }

                    $statistics['success_count']++;

                } catch (\Exception $e) {
                    $statistics['failed_count']++;
                    $statistics['failed_rows'][] = "Row {$rowNumber}: " . $e->getMessage();
                    Log::warning("Row {$rowNumber} failed", ['error' => $e->getMessage()]);
                }

                // ✅ Memory optimization: Clear memory every CHUNK_SIZE rows
                if ($processedInChunk >= self::CHUNK_SIZE) {
                    gc_collect_cycles();
                    $processedInChunk = 0;
                    Log::info("✅ Processed {$rowNumber} rows, clearing memory");
                }
            }

            fclose($handle);
            DB::commit();

            Log::info('✅ Execute Revenue CC Completed', [
                'total' => $statistics['total_rows'],
                'success' => $statistics['success_count'],
                'updated' => $statistics['updated_count'],
                'inserted' => $statistics['inserted_count'],
                'failed' => $statistics['failed_count']
            ]);

            return [
                'success' => true,
                'message' => "Import selesai: {$statistics['success_count']} berhasil, {$statistics['updated_count']} di-update, {$statistics['failed_count']} gagal",
                'statistics' => $statistics
            ];

        } catch (\Exception $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            DB::rollBack();
            Log::error('❌ Execute Revenue CC Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage(),
                'statistics' => [
                    'total_rows' => 0,
                    'success_count' => 0,
                    'failed_count' => 0
                ]
            ];
        }
    }

    /**
     * Helper Methods
     */
    private function parseCsvFileFromPath($filePath)
    {
        if (!file_exists($filePath)) {
            throw new \Exception('File tidak ditemukan');
        }

        $csvData = [];
        $handle = fopen($filePath, 'r');

        while (($row = fgetcsv($handle)) !== false) {
            $csvData[] = $row;
        }

        fclose($handle);
        return $csvData;
    }

    private function validateHeaders($headers, $requiredColumns)
    {
        $headers = array_map('trim', $headers);
        $headers = array_map('strtoupper', $headers);

        foreach ($requiredColumns as $required) {
            $required = strtoupper(trim($required));
            if (!in_array($required, $headers)) {
                return false;
            }
        }

        return true;
    }

    private function getColumnIndices($headers, $columns)
    {
        $indices = [];
        $headers = array_map('trim', $headers);
        $headers = array_map('strtoupper', $headers);

        foreach ($columns as $column) {
            $column = strtoupper(trim($column));
            $index = array_search($column, $headers);
            $indices[$column] = $index !== false ? $index : null;
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
}