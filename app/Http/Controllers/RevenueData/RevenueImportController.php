<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class RevenueImportController extends Controller
{
    private const TEMP_CHUNKS_DIR = 'app/temp_chunks';
    private const CHUNK_TIMEOUT = 7200;

    public function uploadChunk(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file_chunk' => 'required|file',
                'chunk_index' => 'required|integer|min:0',
                'total_chunks' => 'required|integer|min:1',
                'session_id' => 'required|string|max:100',
                'file_name' => 'required|string|max:255',
                'import_type' => 'required|string|in:data_cc,data_am,revenue_cc,revenue_am,target_witel',
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

            $chunkDir = storage_path(self::TEMP_CHUNKS_DIR . "/{$sessionId}");
            if (!file_exists($chunkDir)) {
                mkdir($chunkDir, 0755, true);
            }

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

            $metadata = $this->getChunkMetadata($sessionId) ?? [
                'file_name' => $fileName,
                'import_type' => $importType,
                'total_chunks' => $totalChunks,
                'uploaded_at' => now()->toDateTimeString(),
                'total_rows' => 0,
                'chunks_info' => []
            ];

            $metadata['chunks_info'][$chunkIndex] = [
                'rows' => $rowsInChunk,
                'uploaded_at' => now()->toDateTimeString()
            ];
            $metadata['total_rows'] += $rowsInChunk;

            $this->storeChunkMetadata($sessionId, $metadata);

            $receivedChunks = $this->countReceivedChunks($chunkDir);

            if ($receivedChunks >= $totalChunks) {
                $mergedFilePath = $this->mergeCSVChunks($sessionId, $chunkDir, $fileName, $totalChunks, $metadata);

                Log::info("All chunks merged", [
                    'session_id' => $sessionId,
                    'file_path' => $mergedFilePath,
                    'expected_rows' => $metadata['total_rows'],
                    'merged_file_size' => filesize($mergedFilePath)
                ]);

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

    public function previewImport(Request $request)
    {
        Log::info('RIC - Commencing Preview Import');

        try {
            $isChunkedUpload = $request->has('session_id') && !$request->hasFile('file');

            if ($isChunkedUpload) {
                $validator = Validator::make($request->all(), [
                    'session_id' => 'required|string',
                    'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am,target_witel',
                ]);
            } else {
                $validator = Validator::make($request->all(), [
                    'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am,target_witel',
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

            $year = $request->has('year') ? (int) $request->input('year') : null;
            $month = $request->has('month') ? (int) $request->input('month') : null;

            Log::info('Params after casting to integer', [
                'year' => $year,
                'month' => $month,
                'divisi_id' => $request->input('divisi_id')
            ]);

            $additionalRules = [];
            if ($importType === 'revenue_cc') {
                $additionalRules = [
                    'divisi_id' => 'required|exists:divisi,id',
                    'year' => 'required|integer|min:2000|max:2099',
                    'month' => 'required|integer|min:1|max:12',
                    'tipe_revenue' => 'required|in:HO,BILL',
                    'jenis_data' => 'required|in:revenue,target,lengkap'
                ];
            } elseif ($importType === 'revenue_am') {
                $additionalRules = [
                    'year' => 'required|integer|min:2000|max:2099',
                    'month' => 'required|integer|min:1|max:12'
                ];
            } elseif ($importType === 'target_witel') {
                $additionalRules = [
                    'divisi_id' => 'required|exists:divisi,id',
                    'year' => 'required|integer|min:2000|max:2099',
                    'month' => 'required|integer|min:1|max:12'
                ];
            }

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

            $tempFilePath = null;
            $sessionId = null;

            if ($isChunkedUpload) {
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
                $file = $request->file('file');
                $sessionId = uniqid('import_', true);

                $privateDir = storage_path('app/private/temp_imports');
                if (!file_exists($privateDir)) {
                    mkdir($privateDir, 0755, true);
                }

                $fileName = $sessionId . '_' . $file->getClientOriginalName();
                $absolutePath = $privateDir . '/' . $fileName;
                $file->move($privateDir, $fileName);

                $tempFilePath = $absolutePath;

                Log::info('Saved direct upload file', [
                    'session_id' => $sessionId,
                    'absolute_path' => $absolutePath,
                    'file_exists_check' => file_exists($absolutePath),
                    'file_size' => filesize($absolutePath)
                ]);

                if (!file_exists($absolutePath)) {
                    Log::error('File storage verification failed', [
                        'session_id' => $sessionId,
                        'absolute_path' => $absolutePath
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'File gagal disimpan. Silakan coba lagi.'
                    ], 500);
                }
            }

            $controllerResult = null;

            switch ($importType) {
                case 'data_cc':
                    $controllerResult = app(ImportCCController::class)->previewDataCC($tempFilePath);
                    break;

                case 'revenue_cc':
                    $divisiId = $request->input('divisi_id');
                    $jenisData = $request->input('jenis_data', 'lengkap');

                    $controllerResult = app(ImportCCController::class)->previewRevenueCC(
                        $tempFilePath,
                        $divisiId,
                        $month,
                        $year,
                        $jenisData
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

                case 'target_witel':
                    $controllerResult = app(ImportWitelTargetController::class)->previewWitelTarget($tempFilePath);
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipe import tidak valid'
                    ], 400);
            }

            if ($controllerResult instanceof \Illuminate\Http\JsonResponse) {
                $resultData = json_decode($controllerResult->getContent(), true);
            } else {
                $resultData = $controllerResult;
            }

            session([
                "import_session_{$sessionId}" => [
                    'temp_file_path' => $tempFilePath,
                    'import_type' => $importType,
                    'created_at' => now()->toDateTimeString()
                ]
            ]);

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

    public function executeImport(Request $request)
    {
        Log::info('RIC - Commencing Execute Import');

        try {
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am,target_witel',
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

            $tempFilePath = null;

            $sessionData = session("import_session_{$sessionId}");
            if ($sessionData && isset($sessionData['temp_file_path'])) {
                $tempFilePath = $sessionData['temp_file_path'];
                Log::info('Got temp file path from session', [
                    'session_id' => $sessionId,
                    'file_path' => $tempFilePath
                ]);
            }

            if (!$tempFilePath) {
                $tempFilePath = Cache::get("merged_file_{$sessionId}");
                Log::info('Got temp file path from cache', [
                    'session_id' => $sessionId,
                    'file_path' => $tempFilePath
                ]);
            }

            if (!$tempFilePath) {
                Log::error('Temp file path is null', [
                    'session_id' => $sessionId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'File path tidak ditemukan. Silakan upload dan preview ulang.'
                ], 404);
            }

            $fileExists = false;

            if (file_exists($tempFilePath)) {
                $fileExists = true;
                Log::info('File found via absolute path', [
                    'path' => $tempFilePath
                ]);
            } else {
                $absolutePath = storage_path('app/private/' . $tempFilePath);
                if (file_exists($absolutePath)) {
                    $fileExists = true;
                    $tempFilePath = $absolutePath;
                    Log::info('File found via prepended path', [
                        'relative_path' => $tempFilePath,
                        'absolute_path' => $absolutePath
                    ]);
                }
            }

            if (!$fileExists) {
                Log::error('Temp file not found', [
                    'session_id' => $sessionId,
                    'expected_path' => $tempFilePath,
                    'checked_absolute' => file_exists($tempFilePath),
                    'checked_storage' => file_exists(storage_path('app/private/' . $tempFilePath))
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'File sementara tidak ditemukan. Silakan upload dan preview ulang.'
                ], 404);
            }

            $additionalRules = [];
            if ($importType === 'revenue_cc') {
                $additionalRules = [
                    'divisi_id' => 'required|exists:divisi,id',
                    'year' => 'required|integer|min:2000|max:2099',
                    'month' => 'required|integer|min:1|max:12',
                    'tipe_revenue' => 'required|in:HO,BILL',
                    'jenis_data' => 'required|in:revenue,target,lengkap'
                ];
            } elseif ($importType === 'revenue_am') {
                $additionalRules = [
                    'year' => 'required|integer|min:2000|max:2099',
                    'month' => 'required|integer|min:1|max:12'
                ];
            } elseif ($importType === 'target_witel') {
                $additionalRules = [
                    'divisi_id' => 'required|exists:divisi,id',
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

            $result = null;

            switch ($importType) {
                case 'data_cc':
                    $result = app(ImportCCController::class)->executeDataCC($tempFilePath, $filterType);
                    break;

                case 'revenue_cc':
                    $result = app(ImportCCController::class)->executeRevenueCC($request, $tempFilePath, $filterType);
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

                case 'target_witel':
                    $result = app(ImportWitelTargetController::class)->executeWitelTarget(
                        $tempFilePath,
                        $filterType
                    );
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipe import tidak valid'
                    ], 400);
            }

            if ($result instanceof \Illuminate\Http\JsonResponse) {
                $resultData = json_decode($result->getContent(), true);
            } else {
                $resultData = $result;
            }

            try {
                if (file_exists($tempFilePath)) {
                    unlink($tempFilePath);
                    Log::info('Cleaned up temp file (absolute)', ['file_path' => $tempFilePath]);
                } else {
                    $absolutePath = storage_path('app/private/' . $tempFilePath);
                    if (file_exists($absolutePath)) {
                        unlink($absolutePath);
                        Log::info('Cleaned up temp file (prepended)', ['file_path' => $absolutePath]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to cleanup temp file', [
                    'file_path' => $tempFilePath,
                    'error' => $e->getMessage()
                ]);
            }

            session()->forget("import_session_{$sessionId}");
            Cache::forget("merged_file_{$sessionId}");

            return response()->json($resultData);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Execute Import Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat execute import: ' . $e->getMessage()
            ], 500);
        }
    }

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

            $sessionData = session("import_session_{$sessionId}");
            $tempFilePath = $sessionData['temp_file_path'] ?? Cache::get("merged_file_{$sessionId}");

            if ($tempFilePath) {
                if (file_exists($tempFilePath)) {
                    unlink($tempFilePath);
                    Log::info('Deleted temp file on cancel', ['file_path' => $tempFilePath]);
                } else {
                    $absolutePath = storage_path('app/private/' . $tempFilePath);
                    if (file_exists($absolutePath)) {
                        unlink($absolutePath);
                        Log::info('Deleted temp file on cancel (prepended)', ['file_path' => $absolutePath]);
                    }
                }
            }

            $chunkDir = storage_path(self::TEMP_CHUNKS_DIR . "/{$sessionId}");
            if (file_exists($chunkDir)) {
                $this->recursiveDelete($chunkDir);
                Log::info('Deleted chunk directory on cancel', ['dir' => $chunkDir]);
            }

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

    public function health()
    {
        try {
            $health = [
                'status' => 'ok',
                'timestamp' => now()->toIso8601String(),
                'storage' => [
                    'temp_imports_exists' => file_exists(storage_path('app/private/temp_imports')),
                    'temp_chunks_exists' => file_exists(storage_path(self::TEMP_CHUNKS_DIR)),
                    'writable' => is_writable(storage_path('app'))
                ],
                'database' => [
                    'connected' => false,
                    'tables_exist' => []
                ]
            ];

            try {
                DB::connection()->getPdo();
                $health['database']['connected'] = true;

                $requiredTables = ['divisi', 'corporate_customers', 'account_managers', 'cc_revenues', 'am_revenues', 'witel_target_revenues'];
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

    private function mergeCSVChunks($sessionId, $chunkDir, $fileName, $totalChunks, $metadata)
    {
        $tempImportsDir = storage_path('app/private/temp_imports');
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

                    if ($chunkIndex === 0) {
                        fwrite($finalFile, $line);
                        if ($lineNumber === 1) {
                            $headersWritten = true;
                        } else {
                            $totalRowsWritten++;
                        }
                    } else {
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

    private function countReceivedChunks($chunkDir)
    {
        if (!file_exists($chunkDir)) {
            return 0;
        }
        return count(glob($chunkDir . "/chunk_*"));
    }

    private function storeChunkMetadata($sessionId, array $metadata)
    {
        Cache::put(
            "chunk_metadata_{$sessionId}",
            $metadata,
            now()->addSeconds(self::CHUNK_TIMEOUT)
        );
    }

    private function getChunkMetadata($sessionId)
    {
        return Cache::get("chunk_metadata_{$sessionId}");
    }

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
            if (count(scandir($chunkDir)) <= 2) {
                rmdir($chunkDir);
            }
        } catch (\Exception $e) {
            Log::warning("Cleanup chunks failed", [
                'dir' => $chunkDir,
                'error' => $e->getMessage()
            ]);
        }
    }

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