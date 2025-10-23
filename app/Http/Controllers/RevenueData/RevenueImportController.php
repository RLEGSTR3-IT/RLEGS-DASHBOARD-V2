<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RevenueImportController extends Controller
{
    /**
     * Main import handler - routes to specific import method
     */
    public function import(Request $request)
    {
        // Validate basic request
        $validator = Validator::make($request->all(), [
            'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am',
            'file' => 'required|file|mimes:csv,txt,xlsx|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $importType = $request->import_type;
        $startTime = now();

        // Route to appropriate import method based on type
        try {
            $result = null;

            switch ($importType) {
                case 'data_cc':
                    $result = $this->importDataCC($request);
                    break;

                case 'data_am':
                    $result = $this->importDataAM($request);
                    break;

                case 'revenue_cc':
                    $controller = new ImportCCController();
                    $result = $controller->importRevenueCC($request);
                    break;

                case 'revenue_am':
                    $controller = new ImportAMController();
                    $result = $controller->importRevenueMapping($request);
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipe import tidak valid'
                    ], 400);
            }

            // Format standardized result with popup data
            return $this->formatImportResult($result, $importType, $startTime);

        } catch (\Exception $e) {
            Log::error("Import Error [{$importType}]: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat import',
                'error' => $e->getMessage(),
                'import_type' => $importType,
                'import_time' => $startTime->format('Y-m-d H:i:s'),
                'duration' => now()->diffInSeconds($startTime) . ' detik'
            ], 500);
        }
    }

    /**
     * Format import result untuk popup
     */
    private function formatImportResult($result, $importType, $startTime)
    {
        // Get original response data
        $originalData = $result->getData(true);

        // Calculate duration
        $duration = now()->diffInSeconds($startTime);

        // Get import type label
        $importTypeLabels = [
            'data_cc' => 'Data Corporate Customer',
            'data_am' => 'Data Account Manager',
            'revenue_cc' => 'Revenue Corporate Customer',
            'revenue_am' => 'Revenue Mapping Account Manager'
        ];

        // Add metadata for popup display
        $formattedResult = array_merge($originalData, [
            'import_type' => $importType,
            'import_type_label' => $importTypeLabels[$importType] ?? $importType,
            'import_time' => $startTime->format('Y-m-d H:i:s'),
            'duration' => $duration . ' detik',
            'timestamp' => time()
        ]);

        // Add summary message for popup
        if ($originalData['success']) {
            $stats = $originalData['statistics'];
            $successRate = $stats['total_rows'] > 0
                ? round(($stats['success_count'] / $stats['total_rows']) * 100, 2)
                : 0;

            $formattedResult['summary'] = [
                'title' => 'Import Berhasil!',
                'success_rate' => $successRate,
                'details' => [
                    [
                        'label' => 'Total Baris',
                        'value' => $stats['total_rows'],
                        'icon' => 'file-text'
                    ],
                    [
                        'label' => 'Berhasil',
                        'value' => $stats['success_count'],
                        'icon' => 'check-circle',
                        'color' => 'success'
                    ],
                    [
                        'label' => 'Gagal',
                        'value' => $stats['failed_count'],
                        'icon' => 'x-circle',
                        'color' => 'danger'
                    ]
                ]
            ];

            // Add skipped count if exists
            if (isset($stats['skipped_count']) && $stats['skipped_count'] > 0) {
                $formattedResult['summary']['details'][] = [
                    'label' => 'Diskip',
                    'value' => $stats['skipped_count'],
                    'icon' => 'alert-circle',
                    'color' => 'warning'
                ];
            }

            // Add warning message if there are failures
            if ($stats['failed_count'] > 0 || (isset($stats['skipped_count']) && $stats['skipped_count'] > 0)) {
                $formattedResult['summary']['warning'] = 'Beberapa data gagal diimport. Silakan unduh log error untuk detail.';
            }
        } else {
            $formattedResult['summary'] = [
                'title' => 'Import Gagal!',
                'message' => $originalData['message']
            ];
        }

        return response()->json($formattedResult, $originalData['success'] ? 200 : 500);
    }

    /**
     * Import Data CC (General Corporate Customer)
     * Hanya menyimpan NAMA dan NIPNAS ke tabel corporate_customers
     */
    private function importDataCC(Request $request)
    {
        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $csvData = $this->parseCsvFile($file);

            $statistics = [
                'total_rows' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'failed_rows' => []
            ];

            // Validate required columns
            $requiredColumns = ['STANDARD_NAME', 'NIP_NAS'];
            $headers = array_shift($csvData); // Get headers

            if (!$this->validateHeaders($headers, $requiredColumns)) {
                throw new \Exception('File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns));
            }

            // Get column indices
            $columnIndices = $this->getColumnIndices($headers, [
                'STANDARD_NAME', 'NIP_NAS'
            ]);

            $statistics['total_rows'] = count($csvData);

            // Process each row
            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2; // +2 karena index dimulai dari 0 dan ada header

                try {
                    $nipnas = $this->getColumnValue($row, $columnIndices['NIP_NAS']);
                    $namaCC = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);

                    // Validate required fields
                    if (empty($nipnas) || empty($namaCC)) {
                        throw new \Exception('NIPNAS atau Nama CC kosong');
                    }

                    // Check if CC already exists
                    $existingCC = DB::table('corporate_customers')
                        ->where('nipnas', $nipnas)
                        ->first();

                    if ($existingCC) {
                        // Update existing CC
                        DB::table('corporate_customers')
                            ->where('nipnas', $nipnas)
                            ->update([
                                'nama' => $namaCC,
                                'updated_at' => now()
                            ]);
                    } else {
                        // Insert new CC
                        DB::table('corporate_customers')->insert([
                            'nipnas' => $nipnas,
                            'nama' => $namaCC,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }

                    $statistics['success_count']++;

                } catch (\Exception $e) {
                    $statistics['failed_count']++;
                    $statistics['failed_rows'][] = [
                        'row_number' => $rowNumber,
                        'nipnas' => $nipnas ?? 'N/A',
                        'nama_cc' => $namaCC ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            // Generate error log file if there are failed rows
            $errorLogPath = null;
            if ($statistics['failed_count'] > 0) {
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'data_cc');
            }

            return (object) [
                'success' => true,
                'message' => 'Import Data CC selesai',
                'statistics' => [
                    'total_rows' => $statistics['total_rows'],
                    'success_count' => $statistics['success_count'],
                    'failed_count' => $statistics['failed_count']
                ],
                'error_log_path' => $errorLogPath
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Data CC Error: ' . $e->getMessage());

            return (object) [
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Import Data AM (General Account Manager)
     * AM bisa punya multiple divisi via account_manager_divisi
     */
    private function importDataAM(Request $request)
    {
        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $csvData = $this->parseCsvFile($file);

            $statistics = [
                'total_rows' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'failed_rows' => []
            ];

            // Validate required columns
            $requiredColumns = ['NAMA_AM', 'NIK', 'WITEL', 'DIVISI'];
            $headers = array_shift($csvData);

            if (!$this->validateHeaders($headers, $requiredColumns)) {
                throw new \Exception('File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns));
            }

            $columnIndices = $this->getColumnIndices($headers, [
                'NAMA_AM', 'NIK', 'WITEL', 'DIVISI', 'ROLE', 'TELDA'
            ]);

            $statistics['total_rows'] = count($csvData);

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    $nik = $this->getColumnValue($row, $columnIndices['NIK']);
                    $namaAM = $this->getColumnValue($row, $columnIndices['NAMA_AM']);

                    if (empty($nik) || empty($namaAM)) {
                        throw new \Exception('NIK atau Nama AM kosong');
                    }

                    // Get witel_id (WAJIB)
                    $witelName = $this->getColumnValue($row, $columnIndices['WITEL']);
                    $witel = DB::table('witel')
                        ->where('nama', 'LIKE', "%{$witelName}%")
                        ->first();

                    if (!$witel) {
                        throw new \Exception("Witel '{$witelName}' tidak ditemukan");
                    }

                    // Get role (AM/HOTDA)
                    $role = $this->getColumnValue($row, $columnIndices['ROLE']);
                    if (empty($role)) {
                        $role = 'AM'; // Default role
                    }
                    $role = strtoupper($role);

                    // Get telda_id if HOTDA
                    $teldaId = null;
                    if ($role === 'HOTDA') {
                        $teldaName = $this->getColumnValue($row, $columnIndices['TELDA']);
                        if (!empty($teldaName)) {
                            $telda = DB::table('teldas')
                                ->where('nama', 'LIKE', "%{$teldaName}%")
                                ->where('witel_id', $witel->id)
                                ->first();
                            $teldaId = $telda ? $telda->id : null;
                        }
                    }

                    // Check if AM exists
                    $existingAM = DB::table('account_managers')
                        ->where('nik', $nik)
                        ->first();

                    if ($existingAM) {
                        // Update existing AM
                        DB::table('account_managers')
                            ->where('nik', $nik)
                            ->update([
                                'nama' => $namaAM,
                                'witel_id' => $witel->id,
                                'role' => $role,
                                'telda_id' => $teldaId,
                                'updated_at' => now()
                            ]);

                        $amId = $existingAM->id;
                    } else {
                        // Insert new AM
                        $amId = DB::table('account_managers')->insertGetId([
                            'nik' => $nik,
                            'nama' => $namaAM,
                            'witel_id' => $witel->id,
                            'role' => $role,
                            'telda_id' => $teldaId,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }

                    // Handle divisi mapping (account_manager_divisi)
                    $divisiName = $this->getColumnValue($row, $columnIndices['DIVISI']);
                    $divisiList = explode(',', $divisiName); // Support multiple divisi separated by comma

                    foreach ($divisiList as $idx => $divName) {
                        $divName = trim($divName);

                        $divisi = DB::table('divisi')
                            ->where('nama', 'LIKE', "%{$divName}%")
                            ->orWhere('kode', 'LIKE', "%{$divName}%")
                            ->first();

                        if ($divisi) {
                            // Check if mapping already exists
                            $existingMapping = DB::table('account_manager_divisi')
                                ->where('account_manager_id', $amId)
                                ->where('divisi_id', $divisi->id)
                                ->first();

                            if (!$existingMapping) {
                                // First divisi is primary
                                $isPrimary = ($idx === 0) ? 1 : 0;

                                DB::table('account_manager_divisi')->insert([
                                    'account_manager_id' => $amId,
                                    'divisi_id' => $divisi->id,
                                    'is_primary' => $isPrimary,
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
                        'nama_am' => $namaAM ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $errorLogPath = null;
            if ($statistics['failed_count'] > 0) {
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'data_am');
            }

            return (object) [
                'success' => true,
                'message' => 'Import Data AM selesai',
                'statistics' => [
                    'total_rows' => $statistics['total_rows'],
                    'success_count' => $statistics['success_count'],
                    'failed_count' => $statistics['failed_count']
                ],
                'error_log_path' => $errorLogPath
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Data AM Error: ' . $e->getMessage());

            return (object) [
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Parse CSV file to array
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
     * Validate if CSV has required columns
     */
    private function validateHeaders($headers, $requiredColumns)
    {
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $headers)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get column indices from headers
     */
    private function getColumnIndices($headers, $columns)
    {
        $indices = [];
        foreach ($columns as $column) {
            $index = array_search($column, $headers);
            $indices[$column] = $index !== false ? $index : null;
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
     * Generate error log CSV file
     */
    private function generateErrorLog($failedRows, $type)
    {
        if (empty($failedRows)) {
            return null;
        }

        $filename = 'error_log_' . $type . '_' . time() . '.csv';
        $filepath = storage_path('app/public/import_logs/' . $filename);

        // Create directory if not exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $handle = fopen($filepath, 'w');

        // Write headers based on type
        if ($type === 'data_cc') {
            fputcsv($handle, ['Baris', 'NIPNAS', 'Nama CC', 'Error']);
        } elseif ($type === 'data_am') {
            fputcsv($handle, ['Baris', 'NIK', 'Nama AM', 'Error']);
        } else {
            fputcsv($handle, ['Baris', 'Data', 'Error']);
        }

        // Write failed rows
        foreach ($failedRows as $row) {
            if ($type === 'data_cc') {
                fputcsv($handle, [
                    $row['row_number'],
                    $row['nipnas'],
                    $row['nama_cc'],
                    $row['error']
                ]);
            } elseif ($type === 'data_am') {
                fputcsv($handle, [
                    $row['row_number'],
                    $row['nik'],
                    $row['nama_am'],
                    $row['error']
                ]);
            } else {
                fputcsv($handle, [
                    $row['row_number'],
                    json_encode($row),
                    $row['error']
                ]);
            }
        }

        fclose($handle);

        return asset('storage/import_logs/' . $filename);
    }

    /**
     * Download error log file
     */
    public function downloadErrorLog($filename)
    {
        $filepath = storage_path('app/public/import_logs/' . $filename);

        if (!file_exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan'
            ], 404);
        }

        return response()->download($filepath);
    }
}