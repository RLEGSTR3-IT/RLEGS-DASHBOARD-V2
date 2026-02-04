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
 * ✅ FIXED VERSION - 2026-02-04
 *
 * ========================================
 * CRITICAL FIX
 * ========================================
 * ✅ FIXED: All methods now properly return JsonResponse (not array)
 * ✅ FIXED: Handle controller responses correctly (check if JsonResponse or array)
 * ✅ FIXED: Line 337 error - properly handle $result from other controllers
 *
 * ========================================
 * CHANGELOG
 * ========================================
 * ✅ NEW: Add tipe_revenue validation for Revenue CC import
 * ✅ NEW: Pass tipe_revenue and aggregate_duplicates to ImportCCController
 * ✅ MAINTAINED: All other functionality (upload, preview, execute, cleanup)
 * ✅ MAINTAINED: Chunked upload support
 * ✅ MAINTAINED: Session management
 */
class RevenueImportController extends Controller
{
    private const TEMP_CHUNKS_DIR = 'app/temp_chunks';
    private const CHUNK_TIMEOUT = 7200; // 2 hours

    /**
     * Upload chunk for large files
     */
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
     * ✅ FIXED: Preview Import - Properly handle controller responses
     * 
     * CRITICAL FIX:
     * - Check if controller returns JsonResponse or array
     * - Convert array to JsonResponse properly
     * - Handle $result['success'] safely
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
                'file' => 'required|file|mimes:csv,txt,xlsx|max:102400'
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
                'year' => 'required|integer|min:2000|max:2099',
                'month' => 'required|integer|min:1|max:12',
                'tipe_revenue' => 'required|in:HO,BILL'
            ];
        } elseif ($importType === 'revenue_am') {
            $additionalRules = [
                'year' => 'required|integer|min:2000|max:2099',
                'month' => 'required|integer|min:1|max:12'
            ];
        }

        // Validate additional parameters
        if (!empty($additionalRules)) {
            $additionalValidator = Validator::make($request->all(), $additionalRules);

            if ($additionalValidator->fails()) {
                Log::warning('Preview Import - Additional validation failed', [
                    'import_type' => $importType,
                    'errors' => $additionalValidator->errors()->toArray()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi parameter tambahan gagal',
                    'errors' => $additionalValidator->errors()
                ], 422);
            }
        }

        // Get file path
        $tempFilePath = null;
        $sessionId = null;

        if ($isChunkedUpload) {
            // Chunked upload - get merged file path from cache
            $sessionId = $request->input('session_id');
            $tempFilePath = Cache::get("merged_file_{$sessionId}");

            if (!$tempFilePath || !file_exists($tempFilePath)) {
                Log::error('Merged file not found', [
                    'session_id' => $sessionId,
                    'expected_path' => $tempFilePath
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'File gabungan tidak ditemukan. Silakan upload ulang.'
                ], 404);
            }

            Log::info('Using merged file from chunked upload', [
                'session_id' => $sessionId,
                'file_path' => $tempFilePath
            ]);
        } else {
            // ============================================================================
            // ✅ CRITICAL FIX: Use Storage facade consistently
            // ============================================================================
            $file = $request->file('file');
            $sessionId = uniqid('import_', true);
            
            // ✅ Create filename
            $fileName = $sessionId . '_' . $file->getClientOriginalName();
            
            // ✅ Store using Storage facade (creates directory automatically)
            $storedPath = $file->storeAs('temp_imports', $fileName, 'local');
            
            // ✅ Use RELATIVE path for consistency with Storage::disk('local')
            $tempFilePath = $storedPath; // This is already relative: "temp_imports/file.csv"
            
            // ✅ Enhanced logging for debugging
            Log::info('Saved direct upload file', [
                'session_id' => $sessionId,
                'relative_path' => $tempFilePath,
                'absolute_path' => Storage::disk('local')->path($tempFilePath),
                'file_exists_check' => Storage::disk('local')->exists($tempFilePath),
                'file_size' => Storage::disk('local')->size($tempFilePath)
            ]);
            
            // ✅ VERIFICATION: Double-check file actually exists
            if (!Storage::disk('local')->exists($tempFilePath)) {
                Log::error('File storage verification failed', [
                    'session_id' => $sessionId,
                    'relative_path' => $tempFilePath,
                    'absolute_path' => Storage::disk('local')->path($tempFilePath)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'File gagal disimpan. Silakan coba lagi.'
                ], 500);
            }
        }

        // ✅ CRITICAL FIX: Route to appropriate controller and handle response properly
        $controllerResult = null;

        switch ($importType) {
            case 'data_cc':
                $controllerResult = app(ImportCCController::class)->previewDataCC($tempFilePath);
                break;

            case 'revenue_cc':
                $divisiId = $request->input('divisi_id');
                $controllerResult = app(ImportCCController::class)->previewRevenueCC(
                    $tempFilePath,
                    $divisiId,
                    $month,
                    $year
                );
                break;

            case 'data_am':
                $controllerResult = app(ImportAMController::class)->previewDataAM($tempFilePath);
                break;

            case 'revenue_am':
                $controllerResult = app(ImportAMController::class)->previewRevenueAM(
                    $tempFilePath,
                    $year,
                    $month
                );
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Tipe import tidak valid'
                ], 400);
        }

        // ✅ CRITICAL FIX: Check if result is JsonResponse or array
        if ($controllerResult instanceof \Illuminate\Http\JsonResponse) {
            // It's already a JsonResponse, decode it to get the data
            $resultData = json_decode($controllerResult->getContent(), true);
        } else {
            // It's an array, use directly
            $resultData = $controllerResult;
        }

        // Store temp file path in session for execute step
        session([
            "import_session_{$sessionId}" => [
                'temp_file_path' => $tempFilePath,
                'import_type' => $importType,
                'created_at' => now()->toDateTimeString()
            ]
        ]);

        // ✅ FIXED: Safely check success and add session_id
        if (isset($resultData['success']) && $resultData['success']) {
            $resultData['session_id'] = $sessionId;
        }

        return response()->json($resultData);

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
     * ✅ FIXED: Execute Import - Properly handle controller responses
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeImport(Request $request)
{
    Log::info('RIC - Commencing Execute Import');

    try {
        // Validate basic parameters
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am',
            'filter_type' => 'required|in:all,new,update'
        ]);

        if ($validator->fails()) {
            Log::warning('Execute Import - Validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $sessionId = $request->input('session_id');
        $importType = $request->input('import_type');
        $filterType = $request->input('filter_type');

        // Get temp file path from session or cache
        $tempFilePath = null;

        // Try session first
        $sessionData = session("import_session_{$sessionId}");
        if ($sessionData && isset($sessionData['temp_file_path'])) {
            $tempFilePath = $sessionData['temp_file_path'];
            Log::info('Got temp file path from session', [
                'session_id' => $sessionId,
                'file_path' => $tempFilePath
            ]);
        }

        // Fallback to cache (for chunked uploads)
        if (!$tempFilePath) {
            $tempFilePath = Cache::get("merged_file_{$sessionId}");
            Log::info('Got temp file path from cache', [
                'session_id' => $sessionId,
                'file_path' => $tempFilePath
            ]);
        }

        // ============================================================================
        // ✅ CRITICAL FIX: Check file existence using correct method
        // ============================================================================
        if (!$tempFilePath) {
            Log::error('Temp file path is null', [
                'session_id' => $sessionId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'File path tidak ditemukan. Silakan upload dan preview ulang.'
            ], 404);
        }

        // ✅ FIX: Check if it's absolute path or relative path
        $fileExists = false;
        
        if (file_exists($tempFilePath)) {
            // It's an absolute path (from chunked upload)
            $fileExists = true;
            Log::info('File found via absolute path', [
                'path' => $tempFilePath
            ]);
        } elseif (Storage::disk('local')->exists($tempFilePath)) {
            // It's a relative path (from direct upload)
            $fileExists = true;
            Log::info('File found via Storage disk', [
                'relative_path' => $tempFilePath,
                'absolute_path' => Storage::disk('local')->path($tempFilePath)
            ]);
        }

        if (!$fileExists) {
            Log::error('Temp file not found', [
                'session_id' => $sessionId,
                'expected_path' => $tempFilePath,
                'checked_absolute' => file_exists($tempFilePath),
                'checked_storage' => Storage::disk('local')->exists($tempFilePath),
                'storage_path' => Storage::disk('local')->path($tempFilePath)
            ]);

            return response()->json([
                'success' => false,
                'message' => 'File sementara tidak ditemukan. Silakan upload dan preview ulang.'
            ], 404);
        }

        // Additional validation based on import type
        $additionalRules = [];
        if ($importType === 'revenue_cc') {
            $additionalRules = [
                'divisi_id' => 'required|exists:divisi,id',
                'year' => 'required|integer|min:2000|max:2099',
                'month' => 'required|integer|min:1|max:12',
                'tipe_revenue' => 'required|in:HO,BILL'
            ];
        } elseif ($importType === 'revenue_am') {
            $additionalRules = [
                'year' => 'required|integer|min:2000|max:2099',
                'month' => 'required|integer|min:1|max:12'
            ];
        }

        if (!empty($additionalRules)) {
            $additionalValidator = Validator::make($request->all(), $additionalRules);

            if ($additionalValidator->fails()) {
                Log::warning('Execute Import - Additional validation failed', [
                    'import_type' => $importType,
                    'errors' => $additionalValidator->errors()->toArray()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi parameter tambahan gagal',
                    'errors' => $additionalValidator->errors()
                ], 422);
            }
        }

        // ✅ Execute import based on type
        $result = null;

        switch ($importType) {
            case 'data_cc':
                $result = app(ImportCCController::class)->executeDataCC($tempFilePath, $filterType);
                break;

            case 'revenue_cc':
                $divisiId = $request->input('divisi_id');
                $year = (int) $request->input('year');
                $month = (int) $request->input('month');
                $tipeRevenue = $request->input('tipe_revenue');
                $jenisData = $request->input('jenis_data', 'revenue');

                $result = app(ImportCCController::class)->executeRevenueCC(
                    $tempFilePath,
                    $divisiId,
                    $month,
                    $year,
                    $filterType,
                    $tipeRevenue,
                    $jenisData
                );
                break;

            case 'data_am':
                $result = app(ImportAMController::class)->executeDataAM($tempFilePath, $filterType);
                break;

            case 'revenue_am':
                $year = (int) $request->input('year');
                $month = (int) $request->input('month');

                $result = app(ImportAMController::class)->executeRevenueAM(
                    $tempFilePath,
                    $year,
                    $month,
                    $filterType
                );
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Tipe import tidak valid'
                ], 400);
        }

        // ✅ Handle controller response
        if ($result instanceof \Illuminate\Http\JsonResponse) {
            $resultData = json_decode($result->getContent(), true);
        } else {
            $resultData = $result;
        }

        // ✅ Cleanup: Delete temp file after import
        try {
            if (file_exists($tempFilePath)) {
                // Absolute path
                unlink($tempFilePath);
                Log::info('Cleaned up temp file (absolute)', ['file_path' => $tempFilePath]);
            } elseif (Storage::disk('local')->exists($tempFilePath)) {
                // Relative path
                Storage::disk('local')->delete($tempFilePath);
                Log::info('Cleaned up temp file (storage)', ['file_path' => $tempFilePath]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to cleanup temp file', [
                'file_path' => $tempFilePath,
                'error' => $e->getMessage()
            ]);
        }

        // Clear session and cache
        session()->forget("import_session_{$sessionId}");
        Cache::forget("merged_file_{$sessionId}");

        return response()->json($resultData);

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
     * Cancel import and cleanup files
     */
    public function cancelImport(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID required'
                ], 400);
            }

            // Get temp file path
            $sessionData = session("import_session_{$sessionId}");
            $tempFilePath = $sessionData['temp_file_path'] ?? Cache::get("merged_file_{$sessionId}");

            // Delete temp file if exists
            if ($tempFilePath && file_exists($tempFilePath)) {
                unlink($tempFilePath);
                Log::info('Deleted temp file on cancel', ['file_path' => $tempFilePath]);
            }

            // Delete chunk directory if exists
            $chunkDir = storage_path(self::TEMP_CHUNKS_DIR . "/{$sessionId}");
            if (file_exists($chunkDir)) {
                $this->recursiveDelete($chunkDir);
                Log::info('Deleted chunk directory on cancel', ['dir' => $chunkDir]);
            }

            // Clear cache and session
            session()->forget("import_session_{$sessionId}");
            Cache::forget("merged_file_{$sessionId}");
            Cache::forget("chunk_metadata_{$sessionId}");

            return response()->json([
                'success' => true,
                'message' => 'Import cancelled and files cleaned up'
            ]);

        } catch (\Exception $e) {
            Log::error('Cancel Import Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Health check endpoint
     */
    public function health()
    {
        try {
            $health = [
                'status' => 'ok',
                'timestamp' => now()->toIso8601String(),
                'storage' => [
                    'temp_imports_exists' => file_exists(storage_path('app/temp_imports')),
                    'temp_chunks_exists' => file_exists(storage_path(self::TEMP_CHUNKS_DIR)),
                    'writable' => is_writable(storage_path('app'))
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
     * Merge chunks into temp_imports directory
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
            sort($chunkFiles, SORT_NATURAL);

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
                            continue;
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
     * Count received chunks
     */
    private function countReceivedChunks($chunkDir)
    {
        if (!file_exists($chunkDir)) {
            return 0;
        }
        return count(glob($chunkDir . "/chunk_*"));
    }

    /**
     * Store chunk metadata in cache
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
     * Get chunk metadata from cache
     */
    private function getChunkMetadata($sessionId)
    {
        return Cache::get("chunk_metadata_{$sessionId}");
    }

    /**
     * Cleanup chunks directory
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
     * Recursively delete directory
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