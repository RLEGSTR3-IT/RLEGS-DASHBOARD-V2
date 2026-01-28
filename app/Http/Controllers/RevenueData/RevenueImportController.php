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
 * ✅ FIXED VERSION - 2025-11-06
 *
 * ========================================
 * CHANGELOG
 * ========================================
 *
 * ✅ FIXED PROBLEM: Enhanced validation error debugging
 *    - Line 75-89: Added detailed error logging and debug info in response
 *    - Line 351-365: Added detailed error logging in legacy import
 *    - Now shows exact validation errors to help debugging
 *
 * ✅ MAINTAINED: All existing functionality
 *    - Two-step import (preview + execute)
 *    - Legacy single-step import
 *    - Template downloads
 *    - Error log downloads
 *    - Import history
 *    - Temp file cleanup
 *
 * ✅ ENHANCED: Better error messages
 *    - Shows which field failed validation
 *    - Shows received vs expected values
 *    - Logs to Laravel log for debugging
 */
class RevenueImportController extends Controller
{
    private const TEMP_CHUNKS_DIR = 'app/temp_chunks';
    private const CHUNK_TIMEOUT = 3600;

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
                //$mergedFilePath = $this->mergeChunksToTempImports($sessionId, $chunkDir, $fileName, $totalChunks);
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
     * ✅ STEP 1: Preview Import - Check for duplicates
     * ENHANCED: Better validation error messages with debug info
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

            // Validate import_type
            // $validator = Validator::make($request->all(), [
            //     'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am',
            //     'file' => 'required|file|mimes:csv,txt|max:102400'
            // ]);

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
            // $file = $request->file('file');

            // Log::info("Preview Import started", [
            //     'type' => $importType,
            //     'filename' => $file->getClientOriginalName(),
            //     'filesize' => $file->getSize(),
            //     'all_params' => $request->except(['file'])
            // ]);

            // ✅ FIX: Cast month/year to integer (frontend sends "05" as string, we need 5 as integer)

            // Log::info("Lookie here RIC: ", $request->all());

            if (in_array($importType, ['revenue_cc', 'revenue_am'])) {
                if ($request->has('month')) {
                    $request->merge(['month' => (int) $request->input('month')]);
                }
                if ($request->has('year')) {
                    $request->merge(['year' => (int) $request->input('year')]);
                }
                if ($request->has('divisi_id')) {
                    $request->merge(['divisi_id' => (int) $request->input('divisi_id')]);
                }

                Log::info("Params after casting to integer", [
                    'year' => $request->input('year'),
                    'month' => $request->input('month'),
                    'divisi_id' => $request->input('divisi_id')
                ]);
            }

            // ✅ ENHANCED: Additional validation for revenue imports with detailed error info
            if (in_array($importType, ['revenue_cc', 'revenue_am'])) {
                $additionalRules = $this->getAdditionalValidationRules($importType);

                $additionalValidator = Validator::make($request->all(), $additionalRules);

                if ($additionalValidator->fails()) {
                    // ✅ ENHANCED: Detailed error logging
                    Log::error('Preview Import - Additional validation failed', [
                        'import_type' => $importType,
                        'request_data' => $request->except(['file']),
                        'validation_rules' => $additionalRules,
                        'failed_rules' => $additionalValidator->errors()->toArray()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi parameter tambahan gagal',
                        'errors' => $additionalValidator->errors(),
                        // ✅ ENHANCED: Add debug info to help identify the problem
                        'debug' => [
                            'import_type' => $importType,
                            'received_params' => $request->only(['year', 'month', 'divisi_id', 'jenis_data']),
                            'expected_rules' => $additionalRules,
                            'hint' => 'Periksa apakah divisi_id exists di database dan jenis_data valid (revenue/target)'
                        ]
                    ], 422);
                }
            }

            // Get temp file path
            if ($isChunkedUpload) {
                // For chunked upload, get merged file from cache
                $sessionId = $request->input('session_id');
                $tempFullPath = Cache::get("merged_file_{$sessionId}");

                if (!$tempFullPath || !file_exists($tempFullPath)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File yang diunggah tidak ditemukan. Silakan unggah ulang.'
                    ], 404);
                }

                Log::info("Using chunked upload file", [
                    'session_id' => $sessionId,
                    'file_path' => $tempFullPath
                ]);
            } else {
                // For direct upload, store file in temp_imports
                $file = $request->file('file');
                $sessionId = uniqid('import_', true);
                $tempPath = storage_path('app/temp_imports');

                if (!file_exists($tempPath)) {
                    mkdir($tempPath, 0755, true);
                }

                $tempFilename = $sessionId . '_' . $file->getClientOriginalName();
                $tempFullPath = $tempPath . '/' . $tempFilename;
                $file->move($tempPath, $tempFilename);

                Log::info("Direct upload file stored", [
                    'filename' => $file->getClientOriginalName(),
                    // 'filesize' => $file->filesize(),
                    'session_id' => $sessionId
                ]);
            }

            // Store file temporarily with unique session ID
            // $sessionId = uniqid('import_', true);
            // $tempPath = storage_path('app/temp_imports');

            // if (!file_exists($tempPath)) {
            //     mkdir($tempPath, 0755, true);
            // }

            // $tempFilename = $sessionId . '_' . $file->getClientOriginalName();
            // $tempFullPath = $tempPath . '/' . $tempFilename;
            // $file->move($tempPath, $tempFilename);

            // Route to specific preview handler
            $previewResult = null;
            switch ($importType) {
                case 'data_cc':
                    $controller = new ImportCCController();
                    $previewResult = $controller->previewDataCC($tempFullPath);
                    break;

                case 'data_am':
                    $controller = new ImportAMController();
                    $previewResult = $controller->previewDataAM($tempFullPath);
                    break;

                case 'revenue_cc':
                    $controller = new ImportCCController();
                    $previewResult = $controller->previewRevenueCC(
                        $tempFullPath,
                        $request->divisi_id,
                        $request->jenis_data,
                        $request->year,
                        $request->month
                    );
                    break;

                case 'revenue_am':
                    // Pass year & month from form to preview
                    $controller = new ImportAMController();
                    $previewResult = $controller->previewRevenueAM(
                        $tempFullPath,
                        $request->year,
                        $request->month
                    );
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipe import tidak dikenali'
                    ], 400);
            }

            if (!$previewResult['success']) {
                // Clean up temp file on error
                if (file_exists($tempFullPath)) {
                    unlink($tempFullPath);
                }

                Log::warning('Preview Import - Controller returned error', [
                    'import_type' => $importType,
                    'error' => $previewResult
                ]);

                return response()->json($previewResult);
            }

            // Store session data WITH additional params
            $sessionData = [
                'import_type' => $importType,
                'temp_file' => $tempFullPath,
                'original_filename' => basename($tempFullPath),
                'created_at' => now()->toISOString()
            ];

            // Save additional params to session (year, month, divisi_id, jenis_data)
            if ($importType === 'revenue_cc') {
                $sessionData['additional_params'] = [
                    'divisi_id' => $request->divisi_id,
                    'jenis_data' => $request->jenis_data,
                    'year' => $request->year,
                    'month' => $request->month
                ];
            } elseif ($importType === 'revenue_am') {
                // Save year/month for revenue_am
                $sessionData['additional_params'] = [
                    'year' => $request->year,
                    'month' => $request->month
                ];
            }

            Cache::put("import_session_{$sessionId}", $sessionData, now()->addHours(2));

            // Prepare response
            // $previewResult['session_id'] = $sessionId;
            // $previewResult['expires_at'] = now()->addHours(2)->toISOString();

            // Log::info("Preview Import completed successfully", [
            //     'type' => $importType,
            //     'session_id' => $sessionId,
            //     'additional_params' => $sessionData['additional_params'] ?? null,
            //     'preview_result' => [
            //         'total_rows' => $previewResult['data']['summary']['total_rows'] ?? 0,
            //         'new_count' => $previewResult['data']['summary']['new_count'] ?? 0,
            //         'update_count' => $previewResult['data']['summary']['update_count'] ?? 0
            //     ]
            // ]);
            //

            Log::info('Preview completed successfully', [
                'session_id' => $sessionId,
                'import_type' => $importType,
                'preview_rows' => count($previewResult['data']['preview'] ?? [])
            ]);

            // return response()->json($previewResult);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'data' => $previewResult['data'],
                'message' => $previewResult['message'] ?? 'Preview berhasil dibuat'
            ]);
        } catch (\Exception $e) {
            Log::error("Preview Import exception caught", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                // 'request_data' => $request->except(['file'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat preview import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ STEP 2: Execute Import - Process with user confirmation
     * MAINTAINED: Merge additional_params from session to request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeImport(Request $request)
    {
        Log::info('RIC - Commencing Import Execution');

        try {
            // Validate session
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'confirmed_updates' => 'array',
                'confirmed_updates.*' => 'string',
                'skip_updates' => 'array',
                'skip_updates.*' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sessionId = $request->session_id;

            $sessionData = Cache::get("import_session_{$sessionId}");

            if (!$sessionData) {
                // Log::warning('Execute Import - Session not found or expired', [
                //     'session_id' => $sessionId
                // ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Session tidak valid atau sudah expired. Silakan upload ulang file.'
                ], 404);
            }

            $importType = $sessionData['import_type'];
            $tempFile = $sessionData['temp_file'];

            // Validate temp file
            // if (empty($tempFile)) {
            //     Cache::forget($sessionId);
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Path file temporary tidak valid. Silakan upload ulang.'
            //     ], 400);
            // }

            if (!file_exists($tempFile)) {
                // Cache::forget($sessionId);
                return response()->json([
                    'success' => false,
                    'message' => 'File temporary tidak ditemukan. Silakan upload ulang.'
                ], 404);
            }

            Log::info("Execute Import started", [
                'import_type' => $importType,
                'session_id' => $sessionId,
                'temp_file' => $tempFile,
                'confirmed_updates' => count($request->confirmed_updates ?? []),
                'skip_updates' => count($request->skip_updates ?? [])
            ]);

            // NOTE: HUH HUH HUH
            // // Prepare request
            // $importRequest = new Request();
            // $importRequest->merge([
            //     'temp_file' => $tempFile,
            //     'confirmed_updates' => $request->confirmed_updates ?? [],
            //     'skip_updates' => $request->skip_updates ?? []
            // ]);

            // // Merge additional params from session (year, month, divisi_id, jenis_data)
            // if (!empty($sessionData['additional_params'])) {
            //     $importRequest->merge($sessionData['additional_params']);

            //     Log::info("Merged additional params to request", [
            //         'params' => $sessionData['additional_params']
            //     ]);
            // }

            // // Validate importRequest has temp_file
            // if (!$importRequest->has('temp_file') || empty($importRequest->input('temp_file'))) {
            //     Cache::forget($sessionId);
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Parameter temp_file tidak valid dalam request.'
            //     ], 400);
            // }

            // Route to specific execute handler
            $executeResult = null;
            switch ($importType) {
                case 'data_cc':
                    $controller = new ImportCCController();
                    $executeResult = $controller->executeDataCC($tempFile);
                    break;

                case 'data_am':
                    $controller = new ImportAMController();
                    $executeResult = $controller->executeDataAM($tempFile);
                    break;

                case 'revenue_cc':
                    $controller = new ImportCCController();
                    $additionalParams = $sessionData['additional_params'];
                    $executeResult = $controller->executeRevenueCC(
                        $tempFile,
                        $additionalParams['divisi_id'],
                        $additionalParams['jenis_data'],
                        $additionalParams['year'],
                        $additionalParams['month']
                    );
                    break;

                case 'revenue_am':
                    // Year & month sudah ada di $importRequest dari session merge
                    $controller = new ImportAMController();
                    $additionalParams = $sessionData['additional_params'];
                    $executeResult = $controller->executeRevenueAM(
                        $tempFile,
                        $additionalParams['year'],
                        $additionalParams['month']
                    );
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipe import tidak dikenali'
                    ], 400);
            }

            // Clean up temp file and cache
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            Cache::forget("import_session_{$sessionId}");

            Log::info("Execute Import completed", [
                // 'type' => $importType,
                'session_id' => $sessionId,
                'result' => $executeResult
            ]);

            return response()->json($executeResult);
        } catch (\Exception $e) {
            Log::error("Execute Import error: " . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage()
            ], 500);
        }
    }

    public function legacyImport(Request $request) // for backwards compatibility apparently
    {
        Log::info('RIC - Commencing Legacy Import (single-step)');

        try {
            $validator = Validator::make($request->all(), [
                'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am',
                'file' => 'required|file|mimes:csv,txt|max:102400'
            ]);

            if ($validator->fails()) {
                Log::warning('Legacy Import - Basic validation failed', [
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
            $file = $request->file('file');

            Log::info("Legacy Import started", [
                'type' => $importType,
                'filename' => $file->getClientOriginalName()
            ]);

            // Cast params
            if (in_array($importType, ['revenue_cc', 'revenue_am'])) {
                if ($request->has('month')) {
                    $request->merge(['month' => (int) $request->input('month')]);
                }
                if ($request->has('year')) {
                    $request->merge(['year' => (int) $request->input('year')]);
                }
                if ($request->has('divisi_id')) {
                    $request->merge(['divisi_id' => (int) $request->input('divisi_id')]);
                }
            }

            // Additional validation
            if (in_array($importType, ['revenue_cc', 'revenue_am'])) {
                $additionalRules = $this->getAdditionalValidationRules($importType);
                $additionalValidator = Validator::make($request->all(), $additionalRules);

                if ($additionalValidator->fails()) {
                    Log::error('Legacy Import - Additional validation failed', [
                        'import_type' => $importType,
                        'request_data' => $request->except(['file']),
                        'validation_rules' => $additionalRules,
                        'failed_rules' => $additionalValidator->errors()->toArray()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi parameter tambahan gagal',
                        'errors' => $additionalValidator->errors(),
                        'debug' => [
                            'import_type' => $importType,
                            'received_params' => $request->only(['year', 'month', 'divisi_id', 'jenis_data']),
                            'expected_rules' => $additionalRules
                        ]
                    ], 422);
                }
            }

            // Store temp file
            $sessionId = uniqid('legacy_', true);
            $tempPath = storage_path('app/temp_imports');

            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            $tempFilename = $sessionId . '_' . $file->getClientOriginalName();
            $tempFullPath = $tempPath . '/' . $tempFilename;
            $file->move($tempPath, $tempFilename);

            // Execute import directly
            $result = null;
            switch ($importType) {
                case 'data_cc':
                    $controller = new ImportCCController();
                    $result = $controller->executeDataCC($tempFullPath);
                    break;

                case 'data_am':
                    $controller = new ImportAMController();
                    $result = $controller->executeDataAM($tempFullPath);
                    break;

                case 'revenue_cc':
                    $controller = new ImportCCController();
                    $result = $controller->executeRevenueCC(
                        $tempFullPath,
                        $request->divisi_id,
                        $request->jenis_data,
                        $request->year,
                        $request->month
                    );
                    break;

                case 'revenue_am':
                    $controller = new ImportAMController();
                    $result = $controller->executeRevenueAM(
                        $tempFullPath,
                        $request->year,
                        $request->month
                    );
                    break;
            }

            // Cleanup
            if (file_exists($tempFullPath)) {
                unlink($tempFullPath);
            }

            Log::info('Legacy import completed', [
                'session_id' => $sessionId,
                'result' => $result
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Legacy Import - Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MAINTAINED: Download error log
     */
    public function downloadErrorLog($filename)
    {
        try {
            $logPath = public_path('storage/import_logs/' . $filename);

            if (!file_exists($logPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log file tidak ditemukan'
                ], 404);
            }

            return response()->download($logPath);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunduh log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MAINTAINED: Get import history
     */
    public function getImportHistory()
    {
        try {
            $logPath = public_path('storage/import_logs');

            if (!file_exists($logPath)) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $files = array_diff(scandir($logPath), ['.', '..']);
            $history = [];

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
                    $history[] = [
                        'filename' => $file,
                        'created_at' => date('Y-m-d H:i:s', filemtime($logPath . '/' . $file)),
                        'download_url' => route('revenue.download.error.log', ['filename' => $file])
                    ];
                }
            }

            // Sort by newest first
            usort($history, function ($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ENHANCED: Get additional validation rules based on import type
     * Better organized and documented
     *
     * @param string $importType
     * @return array
     */
    private function getAdditionalValidationRules($importType)
    {
        $rules = [];

        if ($importType === 'revenue_cc') {
            $rules['divisi_id'] = 'required|exists:divisi,id';
            $rules['jenis_data'] = 'required|in:revenue,target';
            $rules['year'] = 'required|integer|min:2020|max:2100';
            $rules['month'] = 'required|integer|min:1|max:12';
        }

        if ($importType === 'revenue_am') {
            $rules['year'] = 'required|integer|min:2020|max:2100';
            $rules['month'] = 'required|integer|min:1|max:12';
        }

        Log::debug('Additional validation rules generated', [
            'import_type' => $importType,
            'rules' => $rules
        ]);

        return $rules;
    }

    /**
     * ✅ MAINTAINED: Cleanup old temp files
     */
    public function cleanupTempFiles()
    {
        try {
            $tempPath = storage_path('app/temp_imports');
            $chunkPath = storage_path(self::TEMP_CHUNKS_DIR);
            $deletedCount = 0;

            // Cleanup temp_imports
            if (file_exists($tempPath)) {
                $files = array_diff(scandir($tempPath), ['.', '..']);
                $olderThan = now()->subHours(3);

                foreach ($files as $file) {
                    $filepath = $tempPath . '/' . $file;

                    if (!is_file($filepath)) {
                        continue;
                    }

                    $fileTime = filemtime($filepath);

                    if ($fileTime < $olderThan->timestamp) {
                        unlink($filepath);
                        $deletedCount++;
                    }
                }
            }

            // Cleanup temp_chunks
            if (file_exists($chunkPath)) {
                $sessions = array_diff(scandir($chunkPath), ['.', '..']);
                $olderThan = now()->subHours(3);

                foreach ($sessions as $session) {
                    $sessionPath = $chunkPath . '/' . $session;

                    if (!is_dir($sessionPath)) {
                        continue;
                    }

                    $sessionTime = filemtime($sessionPath);

                    if ($sessionTime < $olderThan->timestamp) {
                        $this->recursiveDelete($sessionPath);
                        $deletedCount++;
                    }
                }
            }

            Log::info('Temp files cleanup completed', [
                'deleted_count' => $deletedCount,
                'older_than' => $olderThan->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$deletedCount} old temp files",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Cleanup temp files error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error during cleanup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW: Get validation rules info (for debugging)
     * Endpoint untuk melihat aturan validasi yang berlaku
     */
    public function getValidationRules(Request $request)
    {
        $importType = $request->input('import_type');

        if (!$importType) {
            return response()->json([
                'success' => false,
                'message' => 'import_type parameter required'
            ], 400);
        }

        $basicRules = [
            'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am',
            'file' => 'required|file|mimes:csv,txt|max:102400'
        ];

        $additionalRules = [];
        if (in_array($importType, ['revenue_cc', 'revenue_am'])) {
            $additionalRules = $this->getAdditionalValidationRules($importType);
        }

        return response()->json([
            'success' => true,
            'import_type' => $importType,
            'basic_rules' => $basicRules,
            'additional_rules' => $additionalRules,
            'all_rules' => array_merge($basicRules, $additionalRules)
        ]);
    }

    /**
     * ✅ MAINTAINED: Health check
     */
    public function healthCheck()
    {
        try {
            $tempPath = storage_path('app/temp_imports');
            $chunkPath = storage_path(self::TEMP_CHUNKS_DIR);
            $logPath = public_path('storage/import_logs');

            $health = [
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'directories' => [
                    'temp_imports' => [
                        'exists' => file_exists($tempPath),
                        'writable' => file_exists($tempPath) && is_writable($tempPath),
                        'file_count' => file_exists($tempPath) ? count(array_diff(scandir($tempPath), ['.', '..'])) : 0
                    ],
                    'temp_chunks' => [
                        'exists' => file_exists($chunkPath),
                        'writable' => file_exists($chunkPath) && is_writable($chunkPath),
                        'session_count' => file_exists($chunkPath) ? count(array_diff(scandir($chunkPath), ['.', '..'])) : 0
                    ],
                    'import_logs' => [
                        'exists' => file_exists($logPath),
                        'writable' => file_exists($logPath) && is_writable($logPath),
                        'file_count' => file_exists($logPath) ? count(array_diff(scandir($logPath), ['.', '..'])) : 0
                    ]
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                    'working' => Cache::has('__health_check__') || Cache::put('__health_check__', true, 10)
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
     * Merge chunks into temp_imports directory (same location as direct uploads)
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
