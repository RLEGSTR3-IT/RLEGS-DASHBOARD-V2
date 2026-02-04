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
 * ✅ UPDATED VERSION - 2026-02-04
 *
 * ========================================
 * MAJOR CHANGES
 * ========================================
 *
 * ✅ NEW: Proporsi validation after Revenue AM import
 *    - Validate sum(proporsi) per CC = 1.0 (100%)
 *    - Return warnings for invalid proporsi (but allow import)
 *    - Auto-calculate proporsi = 1.0 if only 1 AM per CC
 *
 * ✅ NEW: Enhanced statistics reporting
 *    - Report proporsi_warnings count
 *    - Report auto_calculated_proporsi count
 *    - Detail list of invalid proporsi CCs
 *
 * ✅ MAINTAINED: All existing functionality
 *    - Chunked upload support
 *    - Session management
 *    - Preview functionality
 *    - Execute routing to specific controllers
 */
class RevenueImportController extends Controller
{
    private const TEMP_CHUNKS_DIR = 'app/temp_chunks';
    private const CHUNK_TIMEOUT = 7200; // 2 hours

    /**
     * ✅ MAINTAINED: Upload chunk for large files
     */
    public function uploadChunk(Request $request)
    {
        try {
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

            $metadata['chunks_info'][$chunkIndex] = [
                'uploaded_at' => now()->toDateTimeString(),
                'rows' => $rowsInChunk,
                'is_first' => $isFirstChunk
            ];

            $metadata['total_rows'] += $rowsInChunk;
            $this->saveChunkMetadata($sessionId, $metadata);

            // Check if all chunks uploaded
            $uploadedChunks = count($metadata['chunks_info']);
            $allChunksUploaded = $uploadedChunks === $totalChunks;

            if ($allChunksUploaded) {
                Log::info('All chunks uploaded, merging...', [
                    'session_id' => $sessionId,
                    'total_chunks' => $totalChunks
                ]);

                $mergedFilePath = $this->mergeChunks($sessionId, $totalChunks, $metadata);

                if ($mergedFilePath) {
                    Cache::put("merged_file_{$sessionId}", $mergedFilePath, now()->addSeconds(self::CHUNK_TIMEOUT));
                    Log::info('Chunks merged successfully', [
                        'session_id' => $sessionId,
                        'merged_file' => $mergedFilePath
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Chunk {$chunkIndex} uploaded",
                'uploaded_chunks' => $uploadedChunks,
                'total_chunks' => $totalChunks,
                'all_chunks_uploaded' => $allChunksUploaded,
                'total_rows' => $metadata['total_rows']
            ]);

        } catch (\Exception $e) {
            Log::error('Upload chunk error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal upload chunk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MAINTAINED: Preview import data
     */
    public function previewImport(Request $request)
    {
        Log::info('RIC - Commencing Preview Import');

        try {
            $validator = Validator::make($request->all(), [
                'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $importType = $request->input('import_type');
            $sessionId = $request->input('session_id');
            $tempFilePath = null;

            // Check if this is a chunked upload
            if ($sessionId) {
                $tempFilePath = Cache::get("merged_file_{$sessionId}");
                Log::info('Preview: Got merged file from cache', [
                    'session_id' => $sessionId,
                    'file_path' => $tempFilePath
                ]);
            }

            // Otherwise, handle regular file upload
            if (!$tempFilePath && $request->hasFile('file')) {
                $file = $request->file('file');
                $sessionId = uniqid('import_', true);
                $tempFilePath = storage_path('app/temp/' . $sessionId . '_' . $file->getClientOriginalName());

                if (!file_exists(storage_path('app/temp'))) {
                    mkdir(storage_path('app/temp'), 0755, true);
                }

                $file->move(storage_path('app/temp'), basename($tempFilePath));

                session([
                    "import_session_{$sessionId}" => [
                        'temp_file_path' => $tempFilePath,
                        'import_type' => $importType,
                        'created_at' => now()->toDateTimeString()
                    ]
                ]);

                Log::info('Preview: Stored temp file in session', [
                    'session_id' => $sessionId,
                    'file_path' => $tempFilePath
                ]);
            }

            if (!$tempFilePath || !file_exists($tempFilePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan. Pastikan file sudah diupload.'
                ], 404);
            }

            // Additional validation for revenue imports
            if ($importType === 'revenue_cc') {
                $additionalValidator = Validator::make($request->all(), [
                    'divisi_id' => 'required|exists:divisi,id',
                    'year' => 'required|integer|min:2000|max:2099',
                    'month' => 'required|integer|min:1|max:12',
                    'tipe_revenue' => 'required|in:HO,BILL'
                ]);

                if ($additionalValidator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi parameter gagal',
                        'errors' => $additionalValidator->errors()
                    ], 422);
                }
            }

            // Route to appropriate preview method
            $previewResult = null;

            switch ($importType) {
                case 'data_cc':
                    $previewResult = app(ImportCCController::class)->previewDataCC($tempFilePath);
                    break;

                case 'revenue_cc':
                    $previewResult = app(ImportCCController::class)->previewRevenueCC(
                        $request,
                        $tempFilePath
                    );
                    break;

                case 'data_am':
                    $previewResult = app(ImportAMController::class)->previewDataAM($tempFilePath);
                    break;

                case 'revenue_am':
                    $previewResult = app(ImportAMController::class)->previewRevenueAM($tempFilePath);
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipe import tidak valid'
                    ], 400);
            }

            if (!$previewResult['success']) {
                return response()->json($previewResult, 422);
            }

            $previewResult['session_id'] = $sessionId;
            $previewResult['temp_file_path'] = $tempFilePath;

            return response()->json($previewResult);

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
     * ✅ UPDATED: Execute import with proporsi validation for Revenue AM
     */
    public function executeImport(Request $request)
    {
        Log::info('RIC - Commencing Execute Import');

        try {
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

            // Get temp file path
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

            if (!$tempFilePath || !file_exists($tempFilePath)) {
                Log::error('Temp file not found', [
                    'session_id' => $sessionId,
                    'expected_path' => $tempFilePath
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

            // Route to appropriate controller
            $result = null;

            switch ($importType) {
                case 'data_cc':
                    $result = app(ImportCCController::class)->executeDataCC(
                        $tempFilePath,
                        $filterType
                    );
                    break;

                case 'revenue_cc':
                    $result = app(ImportCCController::class)->executeRevenueCC(
                        $request,
                        $tempFilePath,
                        $filterType
                    );
                    break;

                case 'data_am':
                    $result = app(ImportAMController::class)->executeDataAM(
                        $tempFilePath,
                        $filterType
                    );
                    break;

                case 'revenue_am':
                    $result = app(ImportAMController::class)->executeRevenueAM(
                        $request,
                        $tempFilePath,
                        $filterType
                    );

                    // ✅ NEW: Validate proporsi after Revenue AM import
                    if ($result['success']) {
                        $year = $request->input('year');
                        $month = $request->input('month');
                        
                        $proporsiValidation = $this->validateProporsiAfterImport($year, $month);
                        
                        // Add warnings to result
                        $result['proporsi_validation'] = $proporsiValidation;
                        
                        if ($proporsiValidation['has_warnings']) {
                            $result['message'] .= ' | ⚠️ ' . $proporsiValidation['summary'];
                        }
                    }
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipe import tidak valid'
                    ], 400);
            }

            // Cleanup temp file after successful import
            if ($result['success'] && file_exists($tempFilePath)) {
                try {
                    unlink($tempFilePath);
                    Log::info('Cleaned up temp file', ['file_path' => $tempFilePath]);
                } catch (\Exception $e) {
                    Log::warning('Failed to cleanup temp file', [
                        'file_path' => $tempFilePath,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Clear session data
            session()->forget("import_session_{$sessionId}");
            Cache::forget("merged_file_{$sessionId}");

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Execute Import Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat execute: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW: Validate proporsi after Revenue AM import
     * 
     * Checks:
     * 1. Sum(proporsi) per CC = 1.0 (100%)
     * 2. Auto-calculate proporsi = 1.0 if only 1 AM
     * 
     * @param int $year
     * @param int $month
     * @return array Validation results with warnings
     */
    private function validateProporsiAfterImport($year, $month)
    {
        try {
            // Get all CCs with AM revenues in this period
            $ccGroups = DB::table('am_revenues')
                ->select(
                    'corporate_customer_id',
                    'divisi_id',
                    DB::raw('COUNT(*) as am_count'),
                    DB::raw('SUM(proporsi) as total_proporsi')
                )
                ->where('tahun', $year)
                ->where('bulan', $month)
                ->groupBy('corporate_customer_id', 'divisi_id')
                ->get();

            $warnings = [];
            $autoFixedCount = 0;

            foreach ($ccGroups as $group) {
                $ccId = $group->corporate_customer_id;
                $divisiId = $group->divisi_id;
                $amCount = $group->am_count;
                $totalProporsi = (float) $group->total_proporsi;

                // ✅ AUTO-FIX: If only 1 AM and proporsi != 1.0, set to 1.0
                if ($amCount === 1 && abs($totalProporsi - 1.0) > 0.001) {
                    DB::table('am_revenues')
                        ->where('corporate_customer_id', $ccId)
                        ->where('divisi_id', $divisiId)
                        ->where('tahun', $year)
                        ->where('bulan', $month)
                        ->update(['proporsi' => 1.0]);

                    $autoFixedCount++;
                    
                    Log::info('Auto-fixed proporsi for single AM', [
                        'cc_id' => $ccId,
                        'divisi_id' => $divisiId,
                        'old_proporsi' => $totalProporsi,
                        'new_proporsi' => 1.0
                    ]);
                    
                    continue; // Skip warning for this CC
                }

                // Check if total proporsi is valid (≈ 1.0)
                if (abs($totalProporsi - 1.0) > 0.001) {
                    // Get CC info
                    $ccInfo = DB::table('corporate_customers')
                        ->select('nipnas', 'nama')
                        ->where('id', $ccId)
                        ->first();

                    $divisiInfo = DB::table('divisi')
                        ->select('kode')
                        ->where('id', $divisiId)
                        ->first();

                    $warnings[] = [
                        'cc_id' => $ccId,
                        'cc_nipnas' => $ccInfo->nipnas ?? 'N/A',
                        'cc_nama' => $ccInfo->nama ?? 'N/A',
                        'divisi_kode' => $divisiInfo->kode ?? 'N/A',
                        'am_count' => $amCount,
                        'total_proporsi' => $totalProporsi,
                        'total_proporsi_percent' => round($totalProporsi * 100, 2),
                        'difference' => round((1.0 - $totalProporsi) * 100, 2),
                        'status' => $totalProporsi < 1.0 ? 'kurang' : 'berlebih'
                    ];
                }
            }

            // Generate summary message
            $summary = '';
            if ($autoFixedCount > 0) {
                $summary .= "{$autoFixedCount} CC dengan 1 AM di-set proporsi 100%";
            }
            
            if (!empty($warnings)) {
                if ($summary) {
                    $summary .= ' | ';
                }
                $summary .= count($warnings) . ' CC memiliki total proporsi tidak valid';
            }

            return [
                'has_warnings' => !empty($warnings),
                'warning_count' => count($warnings),
                'auto_fixed_count' => $autoFixedCount,
                'warnings' => $warnings,
                'summary' => $summary ?: 'Semua proporsi valid'
            ];

        } catch (\Exception $e) {
            Log::error('Proporsi validation error: ' . $e->getMessage());
            
            return [
                'has_warnings' => false,
                'warning_count' => 0,
                'auto_fixed_count' => 0,
                'warnings' => [],
                'summary' => 'Gagal validasi proporsi: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ MAINTAINED: Cancel import and cleanup
     */
    public function cancelImport(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID tidak ditemukan'
                ], 400);
            }

            // Get temp file path
            $sessionData = session("import_session_{$sessionId}");
            $tempFilePath = $sessionData['temp_file_path'] ?? Cache::get("merged_file_{$sessionId}");

            // Delete temp file
            if ($tempFilePath && file_exists($tempFilePath)) {
                unlink($tempFilePath);
                Log::info('Deleted temp file on cancel', ['file_path' => $tempFilePath]);
            }

            // Delete chunks directory
            $chunkDir = storage_path(self::TEMP_CHUNKS_DIR . "/{$sessionId}");
            if (is_dir($chunkDir)) {
                $this->deleteDirectory($chunkDir);
                Log::info('Deleted chunk directory', ['dir' => $chunkDir]);
            }

            // Clear session & cache
            session()->forget("import_session_{$sessionId}");
            Cache::forget("merged_file_{$sessionId}");
            Cache::forget("chunk_metadata_{$sessionId}");

            return response()->json([
                'success' => true,
                'message' => 'Import dibatalkan dan file temporary dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Cancel import error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MAINTAINED: Health check
     */
    public function health()
    {
        return response()->json([
            'success' => true,
            'message' => 'Revenue Import Controller is healthy',
            'timestamp' => now()->toIso8601String()
        ]);
    }

    // =====================================================
    // HELPER METHODS (MAINTAINED)
    // =====================================================

    private function getChunkMetadata($sessionId)
    {
        return Cache::get("chunk_metadata_{$sessionId}");
    }

    private function saveChunkMetadata($sessionId, $metadata)
    {
        Cache::put("chunk_metadata_{$sessionId}", $metadata, now()->addSeconds(self::CHUNK_TIMEOUT));
    }

    private function mergeChunks($sessionId, $totalChunks, $metadata)
    {
        try {
            $chunkDir = storage_path(self::TEMP_CHUNKS_DIR . "/{$sessionId}");
            $mergedFilePath = storage_path("app/temp/merged_{$sessionId}.csv");

            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $mergedFile = fopen($mergedFilePath, 'w');
            $headerWritten = false;

            for ($i = 0; $i < $totalChunks; $i++) {
                $paddedIndex = str_pad($i, 4, '0', STR_PAD_LEFT);
                $chunkPath = "{$chunkDir}/chunk_{$paddedIndex}.csv";

                if (!file_exists($chunkPath)) {
                    Log::error("Chunk file not found", ['chunk' => $i, 'path' => $chunkPath]);
                    fclose($mergedFile);
                    return null;
                }

                $chunkFile = fopen($chunkPath, 'r');
                $isFirstChunk = $metadata['chunks_info'][$i]['is_first'] ?? false;

                while (($line = fgets($chunkFile)) !== false) {
                    // Write header only once (from first chunk)
                    if (!$headerWritten && $isFirstChunk) {
                        fwrite($mergedFile, $line);
                        $headerWritten = true;
                    } elseif ($headerWritten) {
                        // Skip header rows from subsequent chunks
                        $trimmed = trim($line);
                        if (empty($trimmed)) continue;
                        
                        // Simple heuristic: if line contains common header keywords, skip
                        $upperLine = strtoupper($line);
                        if (strpos($upperLine, 'NIPNAS') !== false || 
                            strpos($upperLine, 'NIK') !== false ||
                            strpos($upperLine, 'YEAR') !== false) {
                            continue;
                        }
                        
                        fwrite($mergedFile, $line);
                    }
                }

                fclose($chunkFile);
            }

            fclose($mergedFile);

            // Cleanup chunks directory
            $this->deleteDirectory($chunkDir);
            Cache::forget("chunk_metadata_{$sessionId}");

            Log::info('Chunks merged successfully', [
                'session_id' => $sessionId,
                'merged_file' => $mergedFilePath,
                'total_chunks' => $totalChunks
            ]);

            return $mergedFilePath;

        } catch (\Exception $e) {
            Log::error('Merge chunks error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        return rmdir($dir);
    }
}