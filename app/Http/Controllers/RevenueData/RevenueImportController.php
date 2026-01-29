<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

/**
 * RevenueImportController - Main Import Router
 *
 * ✅ FIXED VERSION - 2026-01-28
 *
 * ========================================
 * CHANGELOG
 * ========================================
 *
 * ✅ FIXED: Preview data structure - handle all_rows vs preview_rows
 *    - Store all_rows in session for execute
 *    - Return preview_rows (5 rows) to frontend
 *    - Support enhanced summary with unique counts
 *
 * ✅ FIXED: Execute import - pass year/month to executeRevenueAM
 *    - Pass $request object to controller
 *    - Use all_rows from session (not preview_rows)
 *    - Better error handling
 *
 * ✅ ENHANCED: Cache timeout increased to 7200 seconds (2 hours)
 *    - Line 42: CHUNK_TIMEOUT constant
 *    - Prevents session expired errors
 */
class RevenueImportController extends Controller
{
    private const TEMP_CHUNKS_DIR = 'app/temp_chunks';
    private const CHUNK_TIMEOUT = 7200; // ✅ CHANGED: 3600 → 7200 (2 hours)

    public function uploadChunk(Request $request)
    {
        try {
            // Validate chunk upload
            $validator = Validator::make($request->all(), [
                'file_chunk' => 'required|file',
                'chunk_index' => 'required|integer|min:0',
                'total_chunks' => 'required|integer|min:1',
                'session_id' => 'required|string|max:100',
                'file_name' => 'required|string|max:255',
                'import_type' => 'required|string|in:data_cc,data_am,revenue_cc,revenue_am',
                'is_first_chunk' => 'required|in:0,1',
                'rows_in_chunk' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                Log::warning('Chunk upload validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi chunk gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sessionId = $request->input('session_id');
            $chunkIndex = (int) $request->input('chunk_index');
            $totalChunks = (int) $request->input('total_chunks');
            $fileName = $request->input('file_name');
            $importType = $request->input('import_type');
            $isFirstChunk = $request->input('is_first_chunk') === '1';
            $rowsInChunk = (int) $request->input('rows_in_chunk');

            // Create chunks directory
            $chunkDir = storage_path(self::TEMP_CHUNKS_DIR . "/{$sessionId}");
            if (!file_exists($chunkDir)) {
                mkdir($chunkDir, 0755, true);
            }

            // Save chunk
            $chunk = $request->file('file_chunk');
            $paddedIndex = str_pad($chunkIndex, 4, '0', STR_PAD_LEFT);
            $chunkFileName = "chunk_{$paddedIndex}.csv";
            $chunk->move($chunkDir, $chunkFileName);

            Log::info("Chunk uploaded", [
                'session_id' => $sessionId,
                'chunk' => $chunkIndex + 1,
                'total' => $totalChunks,
                'import_type' => $importType,
                'rows' => $rowsInChunk,
                'is_first' => $isFirstChunk
            ]);

            // Store metadata in cache
            $metadata = $this->getChunkMetadata($sessionId) ?? [
                'file_name' => $fileName,
                'import_type' => $importType,
                'total_chunks' => $totalChunks,
                'uploaded_at' => now()->toDateTimeString(),
                'total_rows' => 0,
                'chunks_info' => []
            ];

            // Track this chunk's info
            $metadata['chunks_info'][$chunkIndex] = [
                'rows' => $rowsInChunk,
                'uploaded_at' => now()->toDateTimeString()
            ];
            $metadata['total_rows'] += $rowsInChunk;

            $this->storeChunkMetadata($sessionId, $metadata);

            // Check if all chunks received
            $receivedChunks = $this->countReceivedChunks($chunkDir);

            if ($receivedChunks >= $totalChunks) {
                // Merge chunks into temp_imports directory
                $mergedFilePath = $this->mergeCSVChunks($sessionId, $chunkDir, $fileName, $totalChunks, $metadata);

                Log::info("All chunks merged", [
                    'session_id' => $sessionId,
                    'file_path' => $mergedFilePath,
                    'expected_rows' => $metadata['total_rows'],
                    'merged_file_size' => filesize($mergedFilePath)
                ]);

                // Store merged file path in cache for preview step
                Cache::put("merged_file_{$sessionId}", $mergedFilePath, now()->addHours(2));

                return response()->json([
                    'success' => true,
                    'message' => 'File berhasil diunggah dan digabungkan',
                    'session_id' => $sessionId,
                    'all_chunks_received' => true,
                    'merged_file' => basename($mergedFilePath),
                    'total_rows' => $metadata['total_rows']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Chunk {$chunkIndex} berhasil diunggah",
                'session_id' => $sessionId,
                'received_chunks' => $receivedChunks,
                'total_chunks' => $totalChunks,
                'progress' => round(($receivedChunks / $totalChunks) * 100, 2),
                'rows_so_far' => $metadata['total_rows']
            ]);
        } catch (\Exception $e) {
            Log::error("Chunk upload failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah chunk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ENHANCED: Preview Import - Handle new response structure
     * 
     * CHANGES:
     * - Handle all_rows vs preview_rows from CC/AM controllers
     * - Store all_rows in session for execute
     * - Return preview_rows (5 rows only) to frontend
     * - Support enhanced summary with unique counts
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function previewImport(Request $request)
    {
        Log::info('RIC - Commencing Preview Import');

        try {
            $isChunkedUpload = $request->has('session_id') && !$request->hasFile('file');

            if ($isChunkedUpload) {
                // Chunked upload - validate session_id
                $validator = Validator::make($request->all(), [
                    'session_id' => 'required|string',
                    'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am',
                ]);
            } else {
                // Direct upload - validate file
                $validator = Validator::make($request->all(), [
                    'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am',
                    'file' => 'required|file|mimes:csv,txt|max:102400'
                ]);
            }

            if ($validator->fails()) {
                Log::warning('Preview Import - Basic validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->except(['file'])
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $importType = $request->import_type;

            // ✅ FIX: Cast month/year to integer (frontend sends "05" as string, we need 5 as integer)
            $year = $request->has('year') ? (int) $request->input('year') : null;
            $month = $request->has('month') ? (int) $request->input('month') : null;

            Log::info('Params after casting to integer', [
                'year' => $year,
                'month' => $month,
                'divisi_id' => $request->input('divisi_id')
            ]);

            // Generate validation rules based on import type
            $additionalRules = [];
            if ($importType === 'revenue_cc') {
                $additionalRules = [
                    'divisi_id' => 'required|exists:divisi,id',
                    'jenis_data' => 'required|in:revenue,target',
                    'year' => 'required|integer|min:2020|max:2100',
                    'month' => 'required|integer|min:1|max:12'
                ];
            } elseif ($importType === 'revenue_am') {
                $additionalRules = [
                    'year' => 'required|integer|min:2020|max:2100',
                    'month' => 'required|integer|min:1|max:12'
                ];
            }

            Log::debug('Additional validation rules generated', [
                'import_type' => $importType,
                'rules' => $additionalRules
            ]);

            if (!empty($additionalRules)) {
                $additionalValidator = Validator::make($request->all(), $additionalRules);

                if ($additionalValidator->fails()) {
                    Log::warning('Preview Import - Additional validation failed', [
                        'import_type' => $importType,
                        'errors' => $additionalValidator->errors()->toArray()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi tambahan gagal',
                        'errors' => $additionalValidator->errors()
                    ], 422);
                }
            }

            // Handle file upload
            $tempFilePath = null;
            $sessionId = null;

            if ($isChunkedUpload) {
                // Chunked upload - get merged file path
                $sessionId = $request->input('session_id');
                $tempFilePath = Cache::get("merged_file_{$sessionId}");

                if (!$tempFilePath || !file_exists($tempFilePath)) {
                    Log::error('Merged file not found for chunked upload', [
                        'session_id' => $sessionId,
                        'expected_path' => $tempFilePath
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'File tidak ditemukan. Silakan upload ulang.'
                    ], 404);
                }

                Log::info('Chunked upload - using merged file', [
                    'session_id' => $sessionId,
                    'file_path' => $tempFilePath
                ]);
            } else {
                // Direct upload - store file
                $file = $request->file('file');
                $sessionId = 'import_' . uniqid('', true);
                $fileName = $file->getClientOriginalName();

                $tempDir = storage_path('app/temp_imports');
                if (!file_exists($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }

                $tempFilePath = $tempDir . '/' . $sessionId . '_' . $fileName;
                $file->move($tempDir, $sessionId . '_' . $fileName);

                Log::info('Direct upload file stored', [
                    'filename' => $fileName,
                    'session_id' => $sessionId
                ]);
            }

            // Route to appropriate controller
            $controller = null;
            $previewResult = null;

            if ($importType === 'data_cc') {
                $controller = new ImportCCController();
                $previewResult = $controller->previewDataCC($tempFilePath);
            } elseif ($importType === 'data_am') {
                $controller = new ImportAMController();
                $previewResult = $controller->previewDataAM($tempFilePath);
            } elseif ($importType === 'revenue_cc') {
                $divisiId = (int) $request->input('divisi_id');
                $jenisData = $request->input('jenis_data');

                $controller = new ImportCCController();
                $previewResult = $controller->previewRevenueCC(
                    $tempFilePath,
                    $divisiId,
                    $jenisData,
                    $year,
                    $month
                );
            } elseif ($importType === 'revenue_am') {
                $controller = new ImportAMController();
                $previewResult = $controller->previewRevenueAM(
                    $tempFilePath,
                    $year,
                    $month
                );
            }

            if (!$previewResult['success']) {
                return response()->json($previewResult, 400);
            }

            // ✅ ENHANCED: Store FULL data in cache for execute, return only preview rows to frontend
            $previewData = $previewResult['data'];
            
            // Determine what to store and what to return
            $dataToStore = null;
            $dataToReturn = null;

            if (isset($previewData['all_rows'])) {
                // ✅ NEW STRUCTURE: Controller returned all_rows + preview_rows
                $dataToStore = $previewData['all_rows']; // Store full data for execute
                $dataToReturn = [
                    'summary' => $previewData['summary'],
                    'rows' => $previewData['preview_rows'] // Return only 5 rows to frontend
                ];

                Log::info('Preview using new structure (all_rows)', [
                    'total_rows' => count($previewData['all_rows']),
                    'preview_rows' => count($previewData['preview_rows'])
                ]);
            } else {
                // ✅ OLD STRUCTURE: Controller returned rows directly (backward compatibility)
                $dataToStore = $previewData['rows']; // Store all rows
                $dataToReturn = $previewData; // Return all data (for data_cc, data_am)

                Log::info('Preview using old structure (rows)', [
                    'total_rows' => count($previewData['rows'])
                ]);
            }

            // ✅ FIXED: Store large preview data to file instead of cache to avoid MySQL packet size limit
            $previewDataPath = "temp_previews/preview_{$sessionId}.json";
            Storage::put($previewDataPath, json_encode($dataToStore));
            
            Log::info('Preview data stored to file', [
                'path' => $previewDataPath,
                'size_kb' => Storage::size($previewDataPath) / 1024
            ]);

            // Store metadata in cache (small, safe for MySQL cache)
            $metadata = [
                'import_type' => $importType,
                'temp_file_path' => $tempFilePath,
                'preview_data_path' => $previewDataPath, // ✅ NEW: Path to preview data file
                'uploaded_at' => now()->toDateTimeString()
            ];

            if ($importType === 'revenue_cc') {
                $metadata['divisi_id'] = $request->input('divisi_id');
                $metadata['jenis_data'] = $request->input('jenis_data');
                $metadata['year'] = $year;
                $metadata['month'] = $month;
            } elseif ($importType === 'revenue_am') {
                $metadata['year'] = $year;
                $metadata['month'] = $month;
            }

            Cache::put(
                "import_metadata_{$sessionId}",
                $metadata,
                now()->addSeconds(self::CHUNK_TIMEOUT)
            );

            Log::info('Preview completed successfully', [
                'session_id' => $sessionId,
                'import_type' => $importType,
                'preview_rows' => isset($dataToReturn['rows']) ? count($dataToReturn['rows']) : 0
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'data' => $dataToReturn // ✅ CHANGED: Return preview rows only
            ]);

        } catch (\Exception $e) {
            Log::error('Preview Import Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat preview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ENHANCED: Execute Import - Pass year/month to controller
     * 
     * CHANGES:
     * - Pass $request object to executeRevenueAM (contains year/month)
     * - Use all_rows from session (not preview_rows)
     * - Better error handling and logging
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeImport(Request $request)
    {
        Log::info('RIC - Commencing Import Execution');

        try {
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'filter_type' => 'required|in:all,new,update',
                'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sessionId = $request->input('session_id');
            $filterType = $request->input('filter_type', 'all'); // Default to 'all'
            $importType = $request->input('import_type');

            // Get metadata
            $metadata = Cache::get("import_metadata_{$sessionId}");
            if (!$metadata) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session tidak valid atau sudah expired. Silakan upload ulang file.'
                ], 400);
            }

            $tempFilePath = $metadata['temp_file_path'];
            if (!file_exists($tempFilePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan. Silakan upload ulang.'
                ], 404);
            }

            // ✅ FIXED: Get preview data from file storage (not cache)
            $previewDataPath = $metadata['preview_data_path'] ?? null;
            $previewData = null;
            
            if ($previewDataPath && Storage::exists($previewDataPath)) {
                $previewData = json_decode(Storage::get($previewDataPath), true);
                Log::info('Preview data loaded from file', [
                    'path' => $previewDataPath,
                    'rows_count' => count($previewData)
                ]);
            } else {
                Log::warning('Preview data file not found', [
                    'session_id' => $sessionId,
                    'expected_path' => $previewDataPath
                ]);
            }

            Log::info('Execute Import started', [
                'import_type' => $importType,
                'session_id' => $sessionId,
                'temp_file' => $tempFilePath,
                'filter_type' => $filterType
            ]);

            // Route to appropriate controller
            $result = null;

            if ($importType === 'data_cc') {
                $controller = new ImportCCController();
                $result = $controller->executeDataCC($tempFilePath, $filterType);
            } elseif ($importType === 'data_am') {
                $controller = new ImportAMController();
                $result = $controller->executeDataAM($tempFilePath, $filterType);
            } elseif ($importType === 'revenue_cc') {
                $divisiId = $metadata['divisi_id'];
                $jenisData = $metadata['jenis_data'];
                $year = $metadata['year'];
                $month = $metadata['month'];

                $controller = new ImportCCController();
                $result = $controller->executeRevenueCC(
                    $tempFilePath,
                    $divisiId,
                    $jenisData,
                    $year,
                    $month,
                    $filterType
                );
            } elseif ($importType === 'revenue_am') {
                $controller = new ImportAMController();
                
                $result = $controller->executeRevenueAM(
                    $request, // Contains year/month
                    $tempFilePath,
                    $filterType
                );
            }

            // Cleanup
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }

            // ✅ FIXED: Delete preview data file from storage
            if ($previewDataPath && Storage::exists($previewDataPath)) {
                Storage::delete($previewDataPath);
                Log::info('Preview data file deleted', ['path' => $previewDataPath]);
            }

            Cache::forget("import_metadata_{$sessionId}");
            Cache::forget("merged_file_{$sessionId}");

            Log::info('Execute Import completed', [
                'session_id' => $sessionId,
                'result' => $result
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Execute Import Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat execute import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MAINTAINED: Download Template
     */
    public function downloadTemplate($type)
    {
        try {
            // Route to appropriate controller
            if (str_starts_with($type, 'revenue-cc') || $type === 'data-cc') {
                $controller = new ImportCCController();
                return $controller->downloadTemplate($type);
            } elseif (str_starts_with($type, 'revenue-am') || $type === 'data-am') {
                $controller = new ImportAMController();
                return $controller->downloadTemplate($type);
            }

            return response()->json([
                'success' => false,
                'message' => 'Template type not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Download Template Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunduh template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MAINTAINED: Download Error Log
     */
    public function downloadErrorLog($filename)
    {
        try {
            $filePath = public_path('storage/import_logs/' . $filename);

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            return response()->download($filePath);
        } catch (\Exception $e) {
            Log::error('Download Error Log Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunduh log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MAINTAINED: Get Import History
     */
    public function getImportHistory(Request $request)
    {
        try {
            $logDirectory = public_path('storage/import_logs');

            if (!file_exists($logDirectory)) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $files = array_diff(scandir($logDirectory), ['.', '..']);
            $history = [];

            foreach ($files as $file) {
                $filePath = $logDirectory . '/' . $file;
                if (is_file($filePath)) {
                    $history[] = [
                        'filename' => $file,
                        'size' => filesize($filePath),
                        'created_at' => date('Y-m-d H:i:s', filemtime($filePath)),
                        'download_url' => asset('storage/import_logs/' . $file)
                    ];
                }
            }

            // Sort by created_at descending
            usort($history, function ($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            Log::error('Get Import History Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MAINTAINED: Cleanup Expired Files
     */
    public function cleanupExpiredFiles()
    {
        try {
            $tempDir = storage_path('app/temp_imports');
            $chunksDir = storage_path(self::TEMP_CHUNKS_DIR);

            $deletedCount = 0;
            $expirationTime = now()->subHours(2)->timestamp;

            // Cleanup temp imports
            if (file_exists($tempDir)) {
                $files = array_diff(scandir($tempDir), ['.', '..']);
                foreach ($files as $file) {
                    $filePath = $tempDir . '/' . $file;
                    if (is_file($filePath) && filemtime($filePath) < $expirationTime) {
                        unlink($filePath);
                        $deletedCount++;
                    }
                }
            }

            // Cleanup chunks
            if (file_exists($chunksDir)) {
                $sessions = array_diff(scandir($chunksDir), ['.', '..']);
                foreach ($sessions as $session) {
                    $sessionPath = $chunksDir . '/' . $session;
                    if (is_dir($sessionPath)) {
                        $sessionTime = filemtime($sessionPath);
                        if ($sessionTime < $expirationTime) {
                            $this->recursiveDelete($sessionPath);
                            $deletedCount++;
                        }
                    }
                }
            }

            Log::info('Cleanup completed', [
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Cleanup selesai. {$deletedCount} file/folder dihapus.",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Cleanup Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal cleanup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MAINTAINED: Health Check
     */
    public function healthCheck()
    {
        try {
            $health = [
                'status' => 'ok',
                'timestamp' => now()->toIso8601String(),
                'storage' => [
                    'temp_imports' => [
                        'exists' => file_exists(storage_path('app/temp_imports')),
                        'writable' => is_writable(storage_path('app/temp_imports'))
                    ],
                    'temp_chunks' => [
                        'exists' => file_exists(storage_path(self::TEMP_CHUNKS_DIR)),
                        'writable' => is_writable(storage_path(self::TEMP_CHUNKS_DIR))
                    ]
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                    'working' => Cache::has('health_check_test') || Cache::put('health_check_test', true, 60)
                ],
                'database' => [
                    'connected' => false,
                    'tables_exist' => []
                ]
            ];

            // Check database
            try {
                DB::connection()->getPdo();
                $health['database']['connected'] = true;

                $requiredTables = ['divisi', 'corporate_customers', 'account_managers', 'cc_revenues', 'am_revenues'];
                foreach ($requiredTables as $table) {
                    $health['database']['tables_exist'][$table] = DB::getSchemaBuilder()->hasTable($table);
                }
            } catch (\Exception $e) {
                $health['database']['error'] = $e->getMessage();
            }

            return response()->json($health);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== PRIVATE HELPER METHODS FOR CHUNKED UPLOAD ====================

    /**
     * ✅ MAINTAINED: Merge chunks into temp_imports directory
     */
    private function mergeCSVChunks($sessionId, $chunkDir, $fileName, $totalChunks, $metadata)
    {
        $tempImportsDir = storage_path('app/temp_imports');
        if (!file_exists($tempImportsDir)) {
            mkdir($tempImportsDir, 0755, true);
        }

        $finalPath = $tempImportsDir . "/{$sessionId}_" . basename($fileName);
        $finalFile = fopen($finalPath, 'w');

        if (!$finalFile) {
            throw new \Exception("Could not create merged file");
        }

        try {
            $totalRowsWritten = 0;
            $headersWritten = false;

            // Sort chunk files to ensure correct order
            $chunkFiles = glob($chunkDir . "/chunk_*.csv");
            sort($chunkFiles, SORT_NATURAL); // Natural sort: chunk_0000, chunk_0001, ...

            Log::info("Merging CSV chunks", [
                'session_id' => $sessionId,
                'total_chunks' => $totalChunks,
                'found_files' => count($chunkFiles),
                'expected_rows' => $metadata['total_rows']
            ]);

            foreach ($chunkFiles as $chunkIndex => $chunkPath) {
                if (!file_exists($chunkPath)) {
                    throw new \Exception("Missing chunk file: " . basename($chunkPath));
                }

                $chunkFile = fopen($chunkPath, 'r');
                if (!$chunkFile) {
                    throw new \Exception("Could not open chunk: " . basename($chunkPath));
                }

                $lineNumber = 0;
                while (($line = fgets($chunkFile)) !== false) {
                    $lineNumber++;

                    // First chunk: write everything (headers + data)
                    if ($chunkIndex === 0) {
                        fwrite($finalFile, $line);
                        if ($lineNumber === 1) {
                            $headersWritten = true;
                        } else {
                            $totalRowsWritten++;
                        }
                    } else {
                        // Subsequent chunks: skip first line (headers), write rest
                        if ($lineNumber === 1) {
                            continue; // Skip header line
                        }
                        fwrite($finalFile, $line);
                        $totalRowsWritten++;
                    }
                }

                fclose($chunkFile);

                Log::debug("Merged chunk", [
                    'chunk' => basename($chunkPath),
                    'lines_processed' => $lineNumber,
                    'total_rows_so_far' => $totalRowsWritten
                ]);
            }

            fclose($finalFile);

            // Validate merged file
            $expectedRows = $metadata['total_rows'];
            if ($totalRowsWritten !== $expectedRows) {
                Log::error("Row count mismatch after merge", [
                    'expected' => $expectedRows,
                    'actual' => $totalRowsWritten,
                    'difference' => $totalRowsWritten - $expectedRows
                ]);

                throw new \Exception(
                    "Row count mismatch! Expected {$expectedRows} rows, got {$totalRowsWritten} rows. " .
                        "File may be corrupted."
                );
            }

            Log::info("CSV merge validation passed", [
                'session_id' => $sessionId,
                'headers_written' => $headersWritten,
                'rows_written' => $totalRowsWritten,
                'file_size' => filesize($finalPath)
            ]);

            // Cleanup chunks after successful merge
            $this->cleanupChunks($chunkDir);

            return $finalPath;
        } catch (\Exception $e) {
            if (is_resource($finalFile)) {
                fclose($finalFile);
            }
            if (file_exists($finalPath)) {
                unlink($finalPath);
            }
            throw $e;
        }
    }

    /**
     * ✅ MAINTAINED: Count received chunks
     */
    private function countReceivedChunks($chunkDir)
    {
        if (!file_exists($chunkDir)) {
            return 0;
        }
        return count(glob($chunkDir . "/chunk_*"));
    }

    /**
     * ✅ MAINTAINED: Store chunk metadata in cache
     */
    private function storeChunkMetadata($sessionId, array $metadata)
    {
        Cache::put(
            "chunk_metadata_{$sessionId}",
            $metadata,
            now()->addSeconds(self::CHUNK_TIMEOUT)
        );
    }

    /**
     * ✅ MAINTAINED: Get chunk metadata from cache
     */
    private function getChunkMetadata($sessionId)
    {
        return Cache::get("chunk_metadata_{$sessionId}");
    }

    /**
     * ✅ MAINTAINED: Cleanup chunks directory
     */
    private function cleanupChunks($chunkDir)
    {
        if (!file_exists($chunkDir)) {
            return;
        }

        try {
            $chunks = glob($chunkDir . "/chunk_*");
            foreach ($chunks as $chunk) {
                if (file_exists($chunk) && is_file($chunk)) {
                    unlink($chunk);
                }
            }
            // Remove directory if empty
            if (count(scandir($chunkDir)) <= 2) { // Only . and ..
                rmdir($chunkDir);
            }
        } catch (\Exception $e) {
            Log::warning("Cleanup chunks failed", [
                'dir' => $chunkDir,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * ✅ MAINTAINED: Recursively delete directory
     */
    private function recursiveDelete($dir)
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->recursiveDelete($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}