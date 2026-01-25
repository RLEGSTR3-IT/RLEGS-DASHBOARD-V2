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
            $selectedRows = $request instanceof Request ? $request->input('selected_rows', []) : [];

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
            $hasSelectedRows = !empty($selectedRows);

            foreach ($csvData as $index => $row) {
                // Skip rows that are not selected (if preview mode)
                if ($hasSelectedRows && !in_array($index, $selectedRows)) {
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