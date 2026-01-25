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
 * ✅ FIXED VERSION - 2026-01-25
 *
 * ========================================
 * CHANGELOG
 * ========================================
 *
 * ✅ FIXED: File size check before preview (skip preview for large files)
 * ✅ FIXED: Increased timeout limits and memory allocation
 * ✅ FIXED: Better error logging and user feedback
 * ✅ MAINTAINED: All existing functionality
 *    - Two-step import (preview + execute)
 *    - Legacy single-step import
 *    - Template downloads
 *    - Error log downloads
 *    - Import history
 *    - Temp file cleanup
 */
class RevenueImportController extends Controller
{
    // ========================================
    // ✅ CONFIGURATION CONSTANTS
    // ========================================
    private const MAX_PREVIEW_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const MAX_EXECUTION_TIME = 600; // 10 minutes
    private const MAX_MEMORY = '512M';

    /**
     * ✅ STEP 1: Preview Import - Check for duplicates
     * FIXED: File size check to skip preview for large files
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function previewImport(Request $request)
    {
        try {
            // Increase limits
            set_time_limit(self::MAX_EXECUTION_TIME);
            ini_set('memory_limit', self::MAX_MEMORY);

            // Validate import_type
            $validator = Validator::make($request->all(), [
                'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am',
                'file' => 'required|file|mimes:csv,txt|max:102400'
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

            // ✅ CHECK FILE SIZE FIRST
            $fileSize = $file->getSize();
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            Log::info("Preview Import started", [
                'type' => $importType,
                'filename' => $file->getClientOriginalName(),
                'filesize' => $fileSize,
                'filesize_mb' => $fileSizeMB,
                'all_params' => $request->except(['file'])
            ]);

            // ✅ SKIP PREVIEW FOR LARGE FILES
            if ($fileSize > self::MAX_PREVIEW_FILE_SIZE) {
                Log::warning("⚠️ File too large for preview, will skip to direct execution", [
                    'filesize_mb' => $fileSizeMB,
                    'max_preview_mb' => round(self::MAX_PREVIEW_FILE_SIZE / 1024 / 1024, 2)
                ]);

                // Store file for direct execution
                $sessionId = uniqid('import_', true);
                $tempPath = storage_path('app/temp_imports');

                if (!file_exists($tempPath)) {
                    mkdir($tempPath, 0755, true);
                }

                $tempFilename = $sessionId . '_' . $file->getClientOriginalName();
                $tempFullPath = $tempPath . '/' . $tempFilename;
                $file->move($tempPath, $tempFilename);

                // Store session data
                $sessionData = [
                    'import_type' => $importType,
                    'temp_file' => $tempFullPath,
                    'original_filename' => $file->getClientOriginalName(),
                    'created_at' => now()->toISOString(),
                    'skip_preview' => true
                ];

                // Save additional params
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

                Cache::put('import_session_' . $sessionId, $sessionData, 3600);

                return response()->json([
                    'success' => true,
                    'skip_preview' => true,
                    'session_id' => $sessionId,
                    'message' => "File terlalu besar ({$fileSizeMB} MB). Preview dilewati. Klik 'Lanjutkan Import' untuk memproses seluruh file.",
                    'data' => [
                        'summary' => [
                            'total_rows' => 'Unknown (file will be processed in background)',
                            'new_count' => 'Unknown',
                            'update_count' => 'Unknown',
                            'error_count' => 0
                        ],
                        'rows' => []
                    ]
                ]);
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
                'created_at' => now()->toISOString(),
                'skip_preview' => $previewResult['skip_preview'] ?? false
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

            Cache::put('import_session_' . $sessionId, $sessionData, 3600);

            Log::info('✅ Preview session created', [
                'session_id' => $sessionId,
                'skip_preview' => $sessionData['skip_preview']
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'skip_preview' => $sessionData['skip_preview'],
                'data' => $previewResult
            ]);

        } catch (\Exception $e) {
            Log::error('Preview Import - Unexpected error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ STEP 2: Execute Import
     * FIXED: Support for skip_preview mode (direct execution)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeImport(Request $request)
    {
        try {
            // Increase limits
            set_time_limit(self::MAX_EXECUTION_TIME);
            ini_set('memory_limit', self::MAX_MEMORY);

            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'import_type' => 'required|string',
                'selected_rows' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sessionId = $request->session_id;
            $sessionData = Cache::get('import_session_' . $sessionId);

            if (!$sessionData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session tidak ditemukan atau sudah kadaluarsa. Silakan upload ulang file.'
                ], 404);
            }

            $tempFile = $sessionData['temp_file'];
            if (!file_exists($tempFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File temporary tidak ditemukan'
                ], 404);
            }

            $importType = $sessionData['import_type'];
            $selectedRows = $request->selected_rows ?? [];
            $skipPreview = $sessionData['skip_preview'] ?? false;

            Log::info('✅ Execute Import started', [
                'session_id' => $sessionId,
                'import_type' => $importType,
                'skip_preview' => $skipPreview,
                'selected_rows_count' => count($selectedRows)
            ]);

            // Route to specific execute handler
            $executeResult = null;
            switch ($importType) {
                case 'data_cc':
                    $controller = new ImportCCController();
                    $executeResult = $controller->executeDataCC($tempFile, $selectedRows);
                    break;

                case 'data_am':
                    $controller = new ImportAMController();
                    $executeResult = $controller->executeDataAM($tempFile, $selectedRows);
                    break;

                case 'revenue_cc':
                    $params = $sessionData['additional_params'];
                    $controller = new ImportCCController();
                    $executeResult = $controller->executeRevenueCC(
                        $tempFile,
                        $params['divisi_id'],
                        $params['jenis_data'],
                        $params['year'],
                        $params['month'],
                        $selectedRows
                    );
                    break;

                case 'revenue_am':
                    $params = $sessionData['additional_params'];
                    $controller = new ImportAMController();
                    $executeResult = $controller->executeRevenueAM(
                        $tempFile,
                        $params['year'],
                        $params['month'],
                        $selectedRows
                    );
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipe import tidak dikenali'
                    ], 400);
            }

            // Clean up temp file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            // Remove session from cache
            Cache::forget('import_session_' . $sessionId);

            Log::info('✅ Execute Import completed', [
                'session_id' => $sessionId,
                'result' => $executeResult
            ]);

            return response()->json($executeResult);

        } catch (\Exception $e) {
            Log::error('Execute Import - Unexpected error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MAINTAINED: Download Template
     */
    public function downloadTemplate($type)
    {
        try {
            $controller = null;

            if (in_array($type, ['data-cc', 'revenue-cc-dgs', 'revenue-cc-dgs-real', 'revenue-cc-dgs-target',
                                 'revenue-cc-dss', 'revenue-cc-dss-real', 'revenue-cc-dss-target',
                                 'revenue-cc-dps', 'revenue-cc-dps-real', 'revenue-cc-dps-target',
                                 'revenue-cc-target'])) {
                $controller = new ImportCCController();
                return $controller->downloadTemplate($type);
            } elseif (in_array($type, ['data-am', 'revenue-am'])) {
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
                'message' => 'Gagal download template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MAINTAINED: Download error log
     */
    public function downloadErrorLog($filename)
    {
        try {
            $path = public_path('storage/import_logs/' . $filename);

            if (!file_exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            return response()->download($path);

        } catch (\Exception $e) {
            Log::error('Download Error Log Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal download log: ' . $e->getMessage()
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
     * ✅ ENHANCED: Get additional validation rules based on import type
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
     * ✅ MAINTAINED: Cleanup old temp files (can be called by cron job)
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
     * ✅ NEW: Get validation rules info (for debugging)
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
     * ✅ NEW: Health check for import system
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
                'configuration' => [
                    'max_preview_file_size_mb' => round(self::MAX_PREVIEW_FILE_SIZE / 1024 / 1024, 2),
                    'max_execution_time' => self::MAX_EXECUTION_TIME,
                    'max_memory' => self::MAX_MEMORY
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