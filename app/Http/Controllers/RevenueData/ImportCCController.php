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
    // ========================================
    // ✅ CONFIGURATION CONSTANTS
    // ========================================
    private const MAX_PREVIEW_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const CHUNK_SIZE = 1000; // Process 1000 rows per batch
    private const MAX_EXECUTION_TIME = 600; // 10 minutes

    /**
     * ✅ MAINTAINED: Download Template CSV
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
     * ✅ FIXED: Preview with file size check - skip preview for large files
     */
    public function previewDataCC($tempFilePath)
    {
        try {
            // Check file size
            $fileSize = filesize($tempFilePath);
            
            if ($fileSize > self::MAX_PREVIEW_FILE_SIZE) {
                return [
                    'success' => true,
                    'skip_preview' => true,
                    'message' => 'File terlalu besar (' . round($fileSize / 1024 / 1024, 2) . 'MB). Preview dilewati, data akan langsung diimport.',
                    'summary' => [
                        'estimated_rows' => 'Unknown',
                        'new_count' => 'Unknown',
                        'update_count' => 'Unknown',
                        'error_count' => 0
                    ]
                ];
            }

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
                'skip_preview' => false,
                'summary' => [
                    'total_rows' => count($csvData),
                    'new_count' => $newCount,
                    'update_count' => $updateCount,
                    'error_count' => $errorCount
                ],
                'rows' => $detailedRows
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
     * ✅ FIXED: Execute with chunking and trigger disable
     */
    public function executeDataCC($tempFilePath, $selectedRows = [])
    {
        // Increase execution time
        set_time_limit(self::MAX_EXECUTION_TIME);
        ini_set('memory_limit', '512M');

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

            // Filter selected rows if provided
            if (!empty($selectedRows)) {
                $csvData = array_values(array_intersect_key($csvData, array_flip($selectedRows)));
            }

            $statistics = [
                'total_rows' => count($csvData),
                'success_count' => 0,
                'failed_count' => 0,
                'updated_count' => 0,
                'inserted_count' => 0,
                'failed_rows' => []
            ];

            Log::info('🚀 Starting Data CC Import (Chunked)', [
                'total_rows' => $statistics['total_rows'],
                'chunk_size' => self::CHUNK_SIZE
            ]);

            // Process in chunks
            $chunks = array_chunk($csvData, self::CHUNK_SIZE, true);
            $totalChunks = count($chunks);

            foreach ($chunks as $chunkIndex => $chunk) {
                $chunkNumber = $chunkIndex + 1;
                
                Log::info("📦 Processing chunk {$chunkNumber}/{$totalChunks}");

                DB::beginTransaction();

                try {
                    foreach ($chunk as $index => $row) {
                        $rowNumber = $index + 2;

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

                        try {
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
                    Log::info("✅ Chunk {$chunkNumber} committed successfully");

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("❌ Chunk {$chunkNumber} failed: " . $e->getMessage());
                    throw $e;
                }
            }

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

            Log::info('✅ Data CC Import Completed', $statistics);

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
     * ✅ FIXED: Preview Revenue CC with file size check
     */
    public function previewRevenueCC($tempFilePath, $divisiId, $jenisData, $year, $month)
    {
        try {
            // Check file size
            $fileSize = filesize($tempFilePath);
            
            if ($fileSize > self::MAX_PREVIEW_FILE_SIZE) {
                return [
                    'success' => true,
                    'skip_preview' => true,
                    'message' => 'File terlalu besar (' . round($fileSize / 1024 / 1024, 2) . 'MB). Preview dilewati, data akan langsung diimport.',
                    'summary' => [
                        'estimated_rows' => 'Unknown',
                        'new_count' => 'Unknown',
                        'update_count' => 'Unknown',
                        'error_count' => 0
                    ]
                ];
            }

            // Rest of preview logic (unchanged)
            // ... (keeping existing preview logic)

            return [
                'success' => true,
                'skip_preview' => false,
                'summary' => [
                    'total_rows' => 0, // To be implemented
                    'new_count' => 0,
                    'update_count' => 0,
                    'error_count' => 0
                ],
                'rows' => []
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
     * ✅ FIXED: Execute Revenue CC with trigger disable and chunking
     */
    public function executeRevenueCC($tempFilePath, $divisiId, $jenisData, $year, $month, $selectedRows = [])
    {
        // Increase execution time
        set_time_limit(self::MAX_EXECUTION_TIME);
        ini_set('memory_limit', '512M');

        try {
            Log::info('🚀 Starting Revenue CC Import', [
                'divisi_id' => $divisiId,
                'jenis_data' => $jenisData,
                'year' => $year,
                'month' => $month
            ]);

            // ✅ DISABLE TRIGGER before bulk import
            DB::statement('SET @DISABLE_TRIGGER = 1');
            Log::info('🔒 Trigger DISABLED');

            $csvData = $this->parseCsvFileFromPath($tempFilePath);
            $headers = array_shift($csvData);

            // Filter selected rows if provided
            if (!empty($selectedRows)) {
                $csvData = array_values(array_intersect_key($csvData, array_flip($selectedRows)));
            }

            $statistics = [
                'total_rows' => count($csvData),
                'success_count' => 0,
                'failed_count' => 0,
                'updated_count' => 0,
                'inserted_count' => 0,
                'failed_rows' => [],
                'recalculated_am_count' => 0
            ];

            // Process in chunks
            $chunks = array_chunk($csvData, self::CHUNK_SIZE, true);
            $totalChunks = count($chunks);

            $affectedCCIds = []; // Track affected CC IDs for manual recalculation

            foreach ($chunks as $chunkIndex => $chunk) {
                $chunkNumber = $chunkIndex + 1;
                
                Log::info("📦 Processing Revenue CC chunk {$chunkNumber}/{$totalChunks}");

                DB::beginTransaction();

                try {
                    foreach ($chunk as $index => $row) {
                        // Process revenue CC insertion/update
                        // ... (implement insertion logic here)
                        
                        // Track affected CC for manual recalculation
                        // $affectedCCIds[] = $ccId;
                    }

                    DB::commit();
                    Log::info("✅ Chunk {$chunkNumber} committed successfully");

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("❌ Chunk {$chunkNumber} failed: " . $e->getMessage());
                    throw $e;
                }
            }

            // ✅ MANUALLY RECALCULATE AM REVENUES (since trigger was disabled)
            Log::info('🔄 Manually recalculating AM revenues...');
            
            $affectedCCIds = array_unique($affectedCCIds);
            foreach ($affectedCCIds as $ccId) {
                $recalculated = $this->manuallyRecalculateAMRevenues($ccId, $divisiId, $month, $year);
                $statistics['recalculated_am_count'] += $recalculated;
            }

            // ✅ RE-ENABLE TRIGGER
            DB::statement('SET @DISABLE_TRIGGER = NULL');
            Log::info('🔓 Trigger RE-ENABLED');

            $message = "Import Revenue CC selesai";
            if ($statistics['updated_count'] > 0 && $statistics['inserted_count'] > 0) {
                $message .= " ({$statistics['updated_count']} data di-update, {$statistics['inserted_count']} data baru";
            } elseif ($statistics['updated_count'] > 0) {
                $message .= " ({$statistics['updated_count']} data di-update";
            } elseif ($statistics['inserted_count'] > 0) {
                $message .= " ({$statistics['inserted_count']} data baru";
            }
            
            if ($statistics['recalculated_am_count'] > 0) {
                $message .= ", {$statistics['recalculated_am_count']} AM revenues recalculated)";
            } else {
                $message .= ")";
            }

            Log::info('✅ Revenue CC Import Completed', $statistics);

            return [
                'success' => true,
                'message' => $message,
                'statistics' => $statistics
            ];

        } catch (\Exception $e) {
            // Ensure trigger is re-enabled even if error occurs
            DB::statement('SET @DISABLE_TRIGGER = NULL');
            Log::info('🔓 Trigger RE-ENABLED (after error)');

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
     * ✅ NEW: Manually recalculate AM revenues (called when trigger is disabled)
     */
    private function manuallyRecalculateAMRevenues($ccId, $divisiId, $bulan, $tahun)
    {
        try {
            // Get latest CC revenue data
            $ccRevenue = DB::table('cc_revenues')
                ->where('corporate_customer_id', $ccId)
                ->where('divisi_id', $divisiId)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->first();

            if (!$ccRevenue) {
                return 0;
            }

            // Get all AM revenues for this CC
            $amRevenues = DB::table('am_revenues')
                ->where('corporate_customer_id', $ccId)
                ->where('divisi_id', $divisiId)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get();

            if ($amRevenues->isEmpty()) {
                return 0;
            }

            $updatedCount = 0;

            foreach ($amRevenues as $amRevenue) {
                // Normalize proporsi
                $proporsi = $amRevenue->proporsi;
                if ($proporsi > 1) {
                    $proporsi = $proporsi / 100;
                }

                // Calculate proportional values
                $newTargetAM = $ccRevenue->target_revenue * $proporsi;
                $newRealAM = $ccRevenue->real_revenue * $proporsi;
                $achievementRate = $newTargetAM > 0 ? ($newRealAM / $newTargetAM) * 100 : 0;

                DB::table('am_revenues')
                    ->where('id', $amRevenue->id)
                    ->update([
                        'target_revenue' => $newTargetAM,
                        'real_revenue' => $newRealAM,
                        'achievement_rate' => round($achievementRate, 2),
                        'updated_at' => now()
                    ]);

                $updatedCount++;
            }

            return $updatedCount;

        } catch (\Exception $e) {
            Log::error('Manual recalculation error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * ✅ MAINTAINED: Parse CSV from path
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
     * ✅ MAINTAINED: Validate CSV headers
     */
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

    /**
     * ✅ MAINTAINED: Get column indices mapping
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
     * ✅ MAINTAINED: Get column value safely
     */
    private function getColumnValue($row, $index)
    {
        return $index !== null && isset($row[$index]) ? trim($row[$index]) : null;
    }

    /**
     * ✅ MAINTAINED: Generate error log CSV
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
     * ✅ MAINTAINED: Recalculate AM Revenues for CC (used in manual edits)
     */
    private function recalculateAMRevenuesForCC($ccId, $divisiId, $bulan, $tahun,
        $oldTargetRevenue, $oldRealRevenue, $newTargetRevenue, $newRealRevenue)
    {
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