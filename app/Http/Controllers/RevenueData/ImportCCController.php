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
 * ✅ FIXED VERSION - 2025-01-25
 *
 * ========================================
 * CHANGELOG
 * ========================================
 *
 * ✅ FIXED: Chunk processing for large files
 *    - Preview now limits to first 1000 rows (with sample indicator)
 *    - Execute uses chunk processing (1000 rows per batch)
 *    - Memory efficient - no loading entire file to array
 *
 * ✅ MAINTAINED: All existing functionality
 *    - Template downloads
 *    - Data CC import
 *    - Revenue CC import (Real/Target, DGS/DSS/DPS)
 *    - Validation and error handling
 */
class ImportCCController extends Controller
{
    // ✅ NEW: Chunk size for large file processing
    const CHUNK_SIZE = 1000;
    const PREVIEW_LIMIT = 1000; // Only show first 1000 rows in preview

    /**
     * Download Template CSV
     */
    public function downloadTemplate($type)
    {
        $templates = [
            // DATA CC TEMPLATE
            'data-cc' => [
                'filename' => 'template_data_cc.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME'],
                'sample' => [
                    ['76590001', 'BANK JATIM'],
                    ['76590002', 'PEMKOT SEMARANG']
                ]
            ],

            // REVENUE CC - DGS TEMPLATES
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

            // REVENUE CC - DSS TEMPLATES
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

            // REVENUE CC - DPS TEMPLATES
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

            // BACKWARD COMPATIBILITY ALIASES
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

                $existingCC = DB::table('corporate_customers')
                    ->where('nipnas', $nipnas)
                    ->first();

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
     * ✅ ENHANCED: Preview Revenue CC with chunk limit
     * Only processes first 1000 rows for preview to avoid timeout
     */
    public function previewRevenueCC($tempFilePath, $divisiId, $jenisData, $year, $month)
    {
        try {
            Log::info('🔍 Preview Revenue CC Started', [
                'divisi_id' => $divisiId,
                'jenis_data' => $jenisData,
                'periode' => "{$year}-{$month}"
            ]);

            // ✅ NEW: Use chunk reading for large files
            $handle = fopen($tempFilePath, 'r');
            if (!$handle) {
                throw new \Exception('Cannot open file');
            }

            // Get headers
            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                throw new \Exception('File is empty');
            }

            // Determine columns based on jenis_data
            $requiredColumns = $this->getRequiredColumnsForRevenueCC($jenisData);
            
            if (!$this->validateHeaders($headers, $requiredColumns['mandatory'])) {
                fclose($handle);
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan. Kolom wajib: ' . implode(', ', $requiredColumns['mandatory'])
                ];
            }

            $columnIndices = $this->getColumnIndices($headers, array_merge(
                $requiredColumns['mandatory'],
                $requiredColumns['optional']
            ));

            $newCount = 0;
            $updateCount = 0;
            $errorCount = 0;
            $detailedRows = [];
            $rowNumber = 1; // Start from 1 (header)
            $processedCount = 0;

            // ✅ NEW: Only process first PREVIEW_LIMIT rows
            while (($row = fgetcsv($handle)) !== false && $processedCount < self::PREVIEW_LIMIT) {
                $rowNumber++;
                $processedCount++;

                $result = $this->processRevenueRow(
                    $row,
                    $columnIndices,
                    $divisiId,
                    $jenisData,
                    $year,
                    $month,
                    $rowNumber
                );

                if ($result['status'] === 'error') {
                    $errorCount++;
                } elseif ($result['status'] === 'update') {
                    $updateCount++;
                } else {
                    $newCount++;
                }

                $detailedRows[] = $result;
            }

            // ✅ NEW: Check if there are more rows
            $hasMoreRows = (fgetcsv($handle) !== false);
            fclose($handle);

            $response = [
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_rows' => $processedCount,
                        'new_count' => $newCount,
                        'update_count' => $updateCount,
                        'error_count' => $errorCount,
                        'is_preview' => $hasMoreRows,
                        'preview_limit' => self::PREVIEW_LIMIT
                    ],
                    'rows' => $detailedRows
                ]
            ];

            if ($hasMoreRows) {
                $response['data']['summary']['warning'] = "File memiliki lebih dari " . self::PREVIEW_LIMIT . " baris. Preview hanya menampilkan " . self::PREVIEW_LIMIT . " baris pertama.";
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('❌ Preview Revenue CC Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => 'Gagal memproses preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ NEW: Process single revenue row (extracted for reuse)
     */
    private function processRevenueRow($row, $columnIndices, $divisiId, $jenisData, $year, $month, $rowNumber)
    {
        $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
        $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);
        $lsegmentHO = $this->getColumnValue($row, $columnIndices['LSEGMENT_HO']);
        $witelHO = $this->getColumnValue($row, $columnIndices['WITEL_HO']);

        // Validation
        if (empty($nipnas)) {
            return [
                'row_number' => $rowNumber,
                'status' => 'error',
                'data' => ['NIPNAS' => 'KOSONG'],
                'error' => 'NIPNAS tidak boleh kosong'
            ];
        }

        // Get CC
        $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
        if (!$cc) {
            return [
                'row_number' => $rowNumber,
                'status' => 'error',
                'data' => ['NIPNAS' => $nipnas],
                'error' => 'Corporate Customer tidak ditemukan'
            ];
        }

        // Get segment
        $segment = DB::table('segments')->where('lsegment_ho', $lsegmentHO)->first();
        if (!$segment) {
            return [
                'row_number' => $rowNumber,
                'status' => 'error',
                'data' => ['NIPNAS' => $nipnas, 'LSEGMENT_HO' => $lsegmentHO],
                'error' => 'Segment tidak ditemukan'
            ];
        }

        // Determine revenue source and get revenue value
        $revenueSource = $this->getColumnValue($row, $columnIndices['SOURCE_DATA']) ?: 'HO';
        $revenueValue = 0;

        if ($jenisData === 'revenue') {
            if ($revenueSource === 'HO') {
                $revenueValue = $this->getColumnValue($row, $columnIndices['REVENUE_SOLD']) ?: 0;
            } else {
                $revenueValue = $this->getColumnValue($row, $columnIndices['REVENUE_BILL']) ?: 0;
            }
        } else {
            $revenueValue = $this->getColumnValue($row, $columnIndices['TARGET_REVENUE']) ?: 0;
        }

        // Check for existing record
        $existing = DB::table('cc_revenues')
            ->where('corporate_customer_id', $cc->id)
            ->where('divisi_id', $divisiId)
            ->where('bulan', $month)
            ->where('tahun', $year)
            ->where('tipe_revenue', 'REGULER')
            ->first();

        if ($existing) {
            return [
                'row_number' => $rowNumber,
                'status' => 'update',
                'data' => [
                    'NIPNAS' => $nipnas,
                    'STANDARD_NAME' => $standardName,
                    'LSEGMENT_HO' => $lsegmentHO,
                    'REVENUE' => $revenueValue
                ],
                'old_data' => [
                    'id' => $existing->id,
                    'target_revenue' => $existing->target_revenue,
                    'real_revenue' => $existing->real_revenue
                ]
            ];
        } else {
            return [
                'row_number' => $rowNumber,
                'status' => 'new',
                'data' => [
                    'NIPNAS' => $nipnas,
                    'STANDARD_NAME' => $standardName,
                    'LSEGMENT_HO' => $lsegmentHO,
                    'REVENUE' => $revenueValue
                ]
            ];
        }
    }

    /**
     * ✅ ENHANCED: Execute Revenue CC with chunk processing
     */
    public function executeRevenueCC($tempFilePath, $divisiId, $jenisData, $year, $month, $selectedRows = [], $skipPreview = false)
    {
        DB::beginTransaction();

        try {
            Log::info('🚀 Execute Revenue CC Started', [
                'divisi_id' => $divisiId,
                'jenis_data' => $jenisData,
                'periode' => "{$year}-{$month}",
                'skip_preview' => $skipPreview,
                'selected_rows_count' => count($selectedRows)
            ]);

            // ✅ NEW: Chunk processing
            $handle = fopen($tempFilePath, 'r');
            if (!$handle) {
                throw new \Exception('Cannot open file');
            }

            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                throw new \Exception('File is empty');
            }

            $requiredColumns = $this->getRequiredColumnsForRevenueCC($jenisData);
            $columnIndices = $this->getColumnIndices($headers, array_merge(
                $requiredColumns['mandatory'],
                $requiredColumns['optional']
            ));

            $successCount = 0;
            $failedCount = 0;
            $updatedCount = 0;
            $errors = [];
            $rowNumber = 1;
            $batchData = [];
            $batchCount = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // Skip if not in selected rows (when preview was used)
                if (!$skipPreview && !empty($selectedRows) && !in_array($rowNumber - 2, $selectedRows)) {
                    continue;
                }

                try {
                    $rowData = $this->prepareRevenueRowData(
                        $row,
                        $columnIndices,
                        $divisiId,
                        $jenisData,
                        $year,
                        $month
                    );

                    if ($rowData) {
                        $batchData[] = $rowData;
                        $batchCount++;

                        // ✅ NEW: Process in chunks
                        if ($batchCount >= self::CHUNK_SIZE) {
                            $result = $this->processBatch($batchData, $jenisData);
                            $successCount += $result['success'];
                            $updatedCount += $result['updated'];
                            $failedCount += $result['failed'];
                            $errors = array_merge($errors, $result['errors']);

                            $batchData = [];
                            $batchCount = 0;

                            // Clear memory
                            gc_collect_cycles();
                        }
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                    Log::warning("Row {$rowNumber} failed", ['error' => $e->getMessage()]);
                }
            }

            // Process remaining batch
            if (!empty($batchData)) {
                $result = $this->processBatch($batchData, $jenisData);
                $successCount += $result['success'];
                $updatedCount += $result['updated'];
                $failedCount += $result['failed'];
                $errors = array_merge($errors, $result['errors']);
            }

            fclose($handle);

            DB::commit();

            Log::info('✅ Execute Revenue CC Completed', [
                'success_count' => $successCount,
                'updated_count' => $updatedCount,
                'failed_count' => $failedCount
            ]);

            $response = [
                'success' => true,
                'message' => "Import selesai: {$successCount} berhasil, {$updatedCount} di-update, {$failedCount} gagal",
                'statistics' => [
                    'total_rows' => $successCount + $updatedCount + $failedCount,
                    'success_count' => $successCount,
                    'updated_count' => $updatedCount,
                    'failed_count' => $failedCount
                ]
            ];

            if (!empty($errors)) {
                $response['errors'] = array_slice($errors, 0, 100); // Limit errors
            }

            return $response;

        } catch (\Exception $e) {
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
     * ✅ NEW: Prepare row data for batch insert/update
     */
    private function prepareRevenueRowData($row, $columnIndices, $divisiId, $jenisData, $year, $month)
    {
        $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
        
        if (empty($nipnas)) {
            return null;
        }

        $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
        if (!$cc) {
            throw new \Exception("CC tidak ditemukan untuk NIPNAS: {$nipnas}");
        }

        $lsegmentHO = $this->getColumnValue($row, $columnIndices['LSEGMENT_HO']);
        $segment = DB::table('segments')->where('lsegment_ho', $lsegmentHO)->first();
        if (!$segment) {
            throw new \Exception("Segment tidak ditemukan: {$lsegmentHO}");
        }

        $witelHO = $this->getColumnValue($row, $columnIndices['WITEL_HO']);
        $witelHOId = null;
        if ($witelHO) {
            $witelHORecord = DB::table('witel')->where('nama', $witelHO)->first();
            $witelHOId = $witelHORecord ? $witelHORecord->id : null;
        }

        $witelBill = $this->getColumnValue($row, $columnIndices['WITEL_BILL']);
        $witelBillId = null;
        if ($witelBill) {
            $witelBillRecord = DB::table('witel')->where('nama', $witelBill)->first();
            $witelBillId = $witelBillRecord ? $witelBillRecord->id : null;
        }

        $revenueSource = $this->getColumnValue($row, $columnIndices['SOURCE_DATA']) ?: 'HO';
        $revenueValue = 0;

        if ($jenisData === 'revenue') {
            if ($revenueSource === 'HO') {
                $revenueValue = $this->getColumnValue($row, $columnIndices['REVENUE_SOLD']) ?: 0;
            } else {
                $revenueValue = $this->getColumnValue($row, $columnIndices['REVENUE_BILL']) ?: 0;
            }
        } else {
            $revenueValue = $this->getColumnValue($row, $columnIndices['TARGET_REVENUE']) ?: 0;
        }

        return [
            'corporate_customer_id' => $cc->id,
            'divisi_id' => $divisiId,
            'segment_id' => $segment->id,
            'witel_ho_id' => $witelHOId,
            'witel_bill_id' => $witelBillId,
            'nama_cc' => $cc->nama,
            'nipnas' => $nipnas,
            'revenue_source' => $revenueSource,
            'tipe_revenue' => 'REGULER',
            'bulan' => $month,
            'tahun' => $year,
            'target_revenue' => $jenisData === 'target' ? $revenueValue : 0,
            'real_revenue' => $jenisData === 'revenue' ? $revenueValue : 0,
        ];
    }

    /**
     * ✅ NEW: Process batch insert/update
     */
    private function processBatch($batchData, $jenisData)
    {
        $successCount = 0;
        $updatedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($batchData as $data) {
            try {
                $existing = DB::table('cc_revenues')
                    ->where('corporate_customer_id', $data['corporate_customer_id'])
                    ->where('divisi_id', $data['divisi_id'])
                    ->where('bulan', $data['bulan'])
                    ->where('tahun', $data['tahun'])
                    ->where('tipe_revenue', 'REGULER')
                    ->first();

                if ($existing) {
                    // Update
                    $updateData = [];
                    if ($jenisData === 'target') {
                        $updateData['target_revenue'] = $data['target_revenue'];
                    } else {
                        $updateData['real_revenue'] = $data['real_revenue'];
                    }
                    $updateData['updated_at'] = now();

                    DB::table('cc_revenues')
                        ->where('id', $existing->id)
                        ->update($updateData);

                    $updatedCount++;
                } else {
                    // Insert
                    $data['created_at'] = now();
                    $data['updated_at'] = now();
                    
                    DB::table('cc_revenues')->insert($data);
                    $successCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Failed to process NIPNAS {$data['nipnas']}: " . $e->getMessage();
            }
        }

        return [
            'success' => $successCount,
            'updated' => $updatedCount,
            'failed' => $failedCount,
            'errors' => $errors
        ];
    }

    /**
     * Execute Data CC Import
     */
    public function executeDataCC($tempFilePath, $selectedRows = [], $importAll = false)
    {
        DB::beginTransaction();

        try {
            $csvData = $this->parseCsvFileFromPath($tempFilePath);
            $headers = array_shift($csvData);
            $columnIndices = $this->getColumnIndices($headers, ['NIPNAS', 'STANDARD_NAME']);

            $successCount = 0;
            $failedCount = 0;
            $updatedCount = 0;
            $errors = [];

            foreach ($csvData as $index => $row) {
                if (!$importAll && !in_array($index, $selectedRows)) {
                    continue;
                }

                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);

                if (empty($nipnas) || empty($standardName)) {
                    $failedCount++;
                    $errors[] = "Baris " . ($index + 2) . ": Data tidak lengkap";
                    continue;
                }

                try {
                    $existing = DB::table('corporate_customers')
                        ->where('nipnas', $nipnas)
                        ->first();

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
     * Get required columns based on jenis_data
     */
    private function getRequiredColumnsForRevenueCC($jenisData)
    {
        $mandatory = ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO'];
        $optional = ['WITEL_BILL', 'SOURCE_DATA'];

        if ($jenisData === 'revenue') {
            $optional[] = 'REVENUE_SOLD';
            $optional[] = 'REVENUE_BILL';
        } else {
            $optional[] = 'TARGET_REVENUE';
        }

        return [
            'mandatory' => $mandatory,
            'optional' => $optional
        ];
    }

    /**
     * Parse CSV file from path
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

    /**
     * Validate headers
     */
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

    /**
     * Get column indices
     */
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

    /**
     * Get column value
     */
    private function getColumnValue($row, $index)
    {
        if ($index === null || !isset($row[$index])) {
            return null;
        }

        return trim($row[$index]);
    }
}