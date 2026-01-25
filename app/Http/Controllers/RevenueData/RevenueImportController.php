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
 * ✅ FIXED VERSION - 2025-01-25
 *
 * ========================================
 * CHANGELOG
 * ========================================
 *
 * ✅ FIXED PROBLEM: 504 Timeout on large files (>10MB)
 *    - Added file size check (MAX 50MB)
 *    - Skip preview for files >10MB → Direct chunk execution
 *    - Improved memory management
 *
 * ✅ MAINTAINED: All existing functionality
 *    - Two-step import (preview + execute) for small files
 *    - Direct execution for large files
 *    - Template downloads
 *    - Error log downloads
 *    - Import history
 *    - Temp file cleanup
 */
class RevenueImportController extends Controller
{
    // ✅ NEW: File size limits (in bytes)
    const MAX_FILE_SIZE = 52428800; // 50MB
    const PREVIEW_THRESHOLD = 10485760; // 10MB - skip preview if larger

    /**
     * ✅ ENHANCED: Preview Import with file size check
     * Skip preview for large files (>10MB) → direct execution
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function previewImport(Request $request)
    {
        try {
            // Validate import_type
            $validator = Validator::make($request->all(), [
                'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am',
                'file' => 'required|file|mimes:csv,txt|max:51200' // 50MB max
            ]);

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
            $file = $request->file('file');
            $fileSize = $file->getSize();

            Log::info("Preview Import started", [
                'type' => $importType,
                'filename' => $file->getClientOriginalName(),
                'filesize' => $fileSize,
                'filesize_mb' => round($fileSize / 1048576, 2),
                'all_params' => $request->except(['file'])
            ]);

            // ✅ NEW: Check if file is too large for preview
            if ($fileSize > self::PREVIEW_THRESHOLD) {
                Log::info("⚠️ File too large for preview, switching to direct execution", [
                    'filesize_mb' => round($fileSize / 1048576, 2),
                    'threshold_mb' => round(self::PREVIEW_THRESHOLD / 1048576, 2)
                ]);

                // Store file and execute directly
                return $this->handleLargeFileImport($request, $file, $importType);
            }

            // ✅ FIX: Cast month/year to integer (frontend sends "05" as string, we need 5 as integer)
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

            // Additional validation for revenue imports
            if (in_array($importType, ['revenue_cc', 'revenue_am'])) {
                $additionalRules = $this->getAdditionalValidationRules($importType);

                $additionalValidator = Validator::make($request->all(), $additionalRules);

                if ($additionalValidator->fails()) {
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
                        'debug' => [
                            'import_type' => $importType,
                            'received_params' => $request->only(['year', 'month', 'divisi_id', 'jenis_data']),
                            'expected_rules' => $additionalRules,
                            'hint' => 'Periksa apakah divisi_id exists di database dan jenis_data valid (revenue/target)'
                        ]
                    ], 422);
                }
            }

            // Store file temporarily with unique session ID
            $sessionId = uniqid('import_', true);
            $tempPath = storage_path('app/temp_imports');

            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            $tempFilename = $sessionId . '_' . $file->getClientOriginalName();
            $tempFullPath = $tempPath . '/' . $tempFilename;
            $file->move($tempPath, $tempFilename);

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
                'original_filename' => $file->getClientOriginalName(),
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
                $sessionData['additional_params'] = [
                    'year' => $request->year,
                    'month' => $request->month
                ];
            }

            Cache::put("import_session_{$sessionId}", $sessionData, now()->addHours(3));

            Log::info('Preview Import - Session created', [
                'session_id' => $sessionId,
                'import_type' => $importType
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'data' => $previewResult['data']
            ]);

        } catch (\Exception $e) {
            Log::error('Preview Import - Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses preview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ FIXED: Handle large file import (skip preview, direct chunk execution)
     */
    private function handleLargeFileImport(Request $request, $file, $importType)
    {
        try {
            Log::info("🚀 Starting direct execution for large file", [
                'import_type' => $importType,
                'filesize_mb' => round($file->getSize() / 1048576, 2)
            ]);

            // Store file temporarily
            $sessionId = uniqid('import_large_', true);
            $tempPath = storage_path('app/temp_imports');

            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            $tempFilename = $sessionId . '_' . $file->getClientOriginalName();
            $tempFullPath = $tempPath . '/' . $tempFilename;
            $file->move($tempPath, $tempFilename);

            // ✅ FIX: Create pseudo-request with temp_file path
            $pseudoRequest = new Request();
            $pseudoRequest->merge([
                'temp_file' => $tempFullPath,
                'divisi_id' => $request->divisi_id,
                'jenis_data' => $request->jenis_data,
                'year' => $request->year,
                'month' => $request->month
            ]);

            // Execute import directly with chunk processing
            $executeResult = null;
            switch ($importType) {
                case 'data_cc':
                    $controller = new ImportCCController();
                    $executeResult = $controller->executeDataCC($pseudoRequest);
                    break;

                case 'data_am':
                    $controller = new ImportAMController();
                    $executeResult = $controller->executeDataAM($pseudoRequest);
                    break;

                case 'revenue_cc':
                    $controller = new ImportCCController();
                    $executeResult = $controller->executeRevenueCC($pseudoRequest);
                    break;

                case 'revenue_am':
                    $controller = new ImportAMController();
                    $executeResult = $controller->executeRevenueAM($pseudoRequest);
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipe import tidak dikenali'
                    ], 400);
            }

            // Clean up temp file
            if (file_exists($tempFullPath)) {
                unlink($tempFullPath);
            }

            return response()->json([
                'success' => true,
                'skip_preview' => true,
                'message' => 'File besar diproses langsung tanpa preview',
                'data' => $executeResult
            ]);

        } catch (\Exception $e) {
            Log::error('Large File Import - Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ STEP 2: Execute Import (from preview selection)
     */
    public function executeImport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'selected_rows' => 'required|array',
                'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am'
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
                return response()->json([
                    'success' => false,
                    'message' => 'Session tidak ditemukan atau sudah kadaluarsa'
                ], 404);
            }

            $tempFilePath = $sessionData['temp_file'];

            if (!file_exists($tempFilePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File temporary tidak ditemukan'
                ], 404);
            }

            Log::info('Execute Import started', [
                'session_id' => $sessionId,
                'import_type' => $request->import_type,
                'selected_rows_count' => count($request->selected_rows)
            ]);

            // ✅ FIX: Create pseudo-Request for all execute methods
            $pseudoRequest = new Request();
            $pseudoRequest->merge([
                'temp_file' => $tempFilePath,
                'selected_rows' => $request->selected_rows
            ]);

            // Add additional params for revenue imports
            if ($request->import_type === 'revenue_cc') {
                $params = $sessionData['additional_params'] ?? [];
                $pseudoRequest->merge([
                    'divisi_id' => $params['divisi_id'] ?? null,
                    'jenis_data' => $params['jenis_data'] ?? null,
                    'year' => $params['year'] ?? null,
                    'month' => $params['month'] ?? null
                ]);
            } elseif ($request->import_type === 'revenue_am') {
                $params = $sessionData['additional_params'] ?? [];
                $pseudoRequest->merge([
                    'year' => $params['year'] ?? null,
                    'month' => $params['month'] ?? null
                ]);
            }

            $result = null;
            switch ($request->import_type) {
                case 'data_cc':
                    $controller = new ImportCCController();
                    $result = $controller->executeDataCC($pseudoRequest);
                    break;

                case 'data_am':
                    $controller = new ImportAMController();
                    $result = $controller->executeDataAM($pseudoRequest);
                    break;

                case 'revenue_cc':
                    $controller = new ImportCCController();
                    $result = $controller->executeRevenueCC($pseudoRequest);
                    break;

                case 'revenue_am':
                    $controller = new ImportAMController();
                    $result = $controller->executeRevenueAM($pseudoRequest);
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipe import tidak dikenali'
                    ], 400);
            }

            // Clean up
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            Cache::forget("import_session_{$sessionId}");

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Execute Import - Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengeksekusi import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ LEGACY: Single-step import (kept for backward compatibility)
     */
    public function importData(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am',
                'file' => 'required|file|mimes:csv,txt|max:51200'
            ]);

            if ($validator->fails()) {
                Log::warning('Legacy Import - Validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $importType = $request->import_type;
            $file = $request->file('file');

            // Additional validation
            if (in_array($importType, ['revenue_cc', 'revenue_am'])) {
                $additionalRules = $this->getAdditionalValidationRules($importType);
                $additionalValidator = Validator::make($request->all(), $additionalRules);

                if ($additionalValidator->fails()) {
                    Log::error('Legacy Import - Additional validation failed', [
                        'errors' => $additionalValidator->errors()->toArray()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi parameter tambahan gagal',
                        'errors' => $additionalValidator->errors()
                    ], 422);
                }
            }

            $tempPath = storage_path('app/temp_imports');
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            $tempFilename = uniqid('legacy_') . '_' . $file->getClientOriginalName();
            $tempFullPath = $tempPath . '/' . $tempFilename;
            $file->move($tempPath, $tempFilename);

            $result = null;
            switch ($importType) {
                case 'data_cc':
                    $controller = new ImportCCController();
                    $result = $controller->executeDataCC($tempFullPath, [], true);
                    break;

                case 'data_am':
                    $controller = new ImportAMController();
                    $result = $controller->executeDataAM($tempFullPath, [], true);
                    break;

                case 'revenue_cc':
                    $controller = new ImportCCController();
                    $result = $controller->executeRevenueCC(
                        $tempFullPath,
                        $request->divisi_id,
                        $request->jenis_data,
                        $request->year,
                        $request->month,
                        [],
                        true
                    );
                    break;

                case 'revenue_am':
                    $controller = new ImportAMController();
                    $result = $controller->executeRevenueAM(
                        $tempFullPath,
                        $request->year,
                        $request->month,
                        [],
                        true
                    );
                    break;
            }

            if (file_exists($tempFullPath)) {
                unlink($tempFullPath);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Legacy Import - Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download error log
     */
    public function downloadErrorLog($filename)
    {
        try {
            $logPath = public_path('storage/import_logs/' . $filename);

            if (!file_exists($logPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            return response()->download($logPath);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal download: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import history
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

            usort($history, function($a, $b) {
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
     * Get additional validation rules based on import type
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
     * Cleanup old temp files (can be called by cron job)
     */
    public function cleanupTempFiles()
    {
        try {
            $tempPath = storage_path('app/temp_imports');

            if (!file_exists($tempPath)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No temp directory found',
                    'deleted_count' => 0
                ]);
            }

            $files = array_diff(scandir($tempPath), ['.', '..']);
            $deletedCount = 0;
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
     * Get validation rules info (for debugging)
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
            'file' => 'required|file|mimes:csv,txt|max:51200'
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
            'all_rules' => array_merge($basicRules, $additionalRules),
            'file_size_limits' => [
                'max_file_size_mb' => round(self::MAX_FILE_SIZE / 1048576, 2),
                'preview_threshold_mb' => round(self::PREVIEW_THRESHOLD / 1048576, 2)
            ]
        ]);
    }

    /**
     * Health check for import system
     */
    public function healthCheck()
    {
        try {
            $tempPath = storage_path('app/temp_imports');
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
                ],
                'file_limits' => [
                    'max_file_size_mb' => round(self::MAX_FILE_SIZE / 1048576, 2),
                    'preview_threshold_mb' => round(self::PREVIEW_THRESHOLD / 1048576, 2)
                ]
            ];

            // Check database connection and tables
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
}