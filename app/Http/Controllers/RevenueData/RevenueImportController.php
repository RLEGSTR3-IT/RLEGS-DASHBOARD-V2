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
 * FIXED VERSION - 2025-10-31 23:00
 *
 * ✅ FIXED: Session menyimpan year, month, divisi_id, jenis_data
 * ✅ FIXED: Execute dapat parameter lengkap dari session
 * ✅ MAINTAINED: Semua fungsi existing (downloadTemplate, validateImport, executeImport, cleanup, dll)
 *
 * CHANGES:
 * - previewImport(): Line 95-102 → Added year, month, divisi_id, jenis_data to session
 * - executeImport(): Line 220-231 → Merge session params to request before execute
 * - ALL OTHER METHODS: Unchanged
 */
class RevenueImportController extends Controller
{
    /**
     * STEP 1: Preview Import - Check for duplicates
     * ✅ FIXED: Save year, month, divisi_id to session
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
                'file' => 'required|file|mimes:csv,txt|max:10240'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $importType = $request->import_type;
            $file = $request->file('file');

            Log::info("Preview Import started", [
                'type' => $importType,
                'filename' => $file->getClientOriginalName(),
                'filesize' => $file->getSize()
            ]);

            // Additional validation for revenue imports
            if (in_array($importType, ['revenue_cc', 'revenue_am'])) {
                $additionalRules = $this->getAdditionalValidationRules($importType);

                $additionalValidator = Validator::make($request->all(), $additionalRules);
                if ($additionalValidator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi parameter tambahan gagal',
                        'errors' => $additionalValidator->errors()
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
                    $previewResult = $controller->previewRevenueAM($tempFullPath);
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
                return response()->json($previewResult);
            }

            // ✅ FIXED: Store session data WITH additional params
            $sessionData = [
                'import_type' => $importType,
                'temp_file' => $tempFullPath,
                'original_filename' => $file->getClientOriginalName(),
                'created_at' => now()->toISOString()
            ];

            // ✅ FIXED: Save additional params to session (year, month, divisi_id, jenis_data)
            if ($importType === 'revenue_cc') {
                $sessionData['additional_params'] = [
                    'divisi_id' => $request->divisi_id,
                    'jenis_data' => $request->jenis_data,
                    'year' => $request->year,
                    'month' => $request->month
                ];
            } elseif ($importType === 'revenue_am') {
                // Revenue AM might have year/month in CSV or form - save if exists
                if ($request->has('year')) {
                    $sessionData['additional_params']['year'] = $request->year;
                }
                if ($request->has('month')) {
                    $sessionData['additional_params']['month'] = $request->month;
                }
            }

            Cache::put($sessionId, $sessionData, now()->addHours(2));

            // Prepare response
            $previewResult['session_id'] = $sessionId;
            $previewResult['expires_at'] = now()->addHours(2)->toISOString();

            Log::info("Preview Import completed", [
                'type' => $importType,
                'session_id' => $sessionId,
                'preview_result' => [
                    'total_rows' => $previewResult['data']['summary']['total_rows'] ?? 0,
                    'new_count' => $previewResult['data']['summary']['new_count'] ?? 0,
                    'update_count' => $previewResult['data']['summary']['update_count'] ?? 0
                ]
            ]);

            return response()->json($previewResult);

        } catch (\Exception $e) {
            Log::error("Preview Import error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat preview import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * STEP 2: Execute Import - Process with user confirmation
     * ✅ FIXED: Merge additional_params from session to request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeImport(Request $request)
    {
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
            $sessionData = Cache::get($sessionId);

            if (!$sessionData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session tidak valid atau sudah expired. Silakan upload ulang file.'
                ], 400);
            }

            $importType = $sessionData['import_type'];
            $tempFile = $sessionData['temp_file'];

            // Validate temp file
            if (empty($tempFile)) {
                Cache::forget($sessionId);
                return response()->json([
                    'success' => false,
                    'message' => 'Path file temporary tidak valid. Silakan upload ulang.'
                ], 400);
            }

            if (!file_exists($tempFile)) {
                Cache::forget($sessionId);
                return response()->json([
                    'success' => false,
                    'message' => 'File temporary tidak ditemukan. Silakan upload ulang.'
                ], 400);
            }

            Log::info("Execute Import started", [
                'type' => $importType,
                'session_id' => $sessionId,
                'temp_file' => $tempFile,
                'confirmed_updates' => count($request->confirmed_updates ?? []),
                'skip_updates' => count($request->skip_updates ?? [])
            ]);

            // Prepare request
            $importRequest = new Request();
            $importRequest->merge([
                'temp_file' => $tempFile,
                'confirmed_updates' => $request->confirmed_updates ?? [],
                'skip_updates' => $request->skip_updates ?? []
            ]);

            // ✅ FIXED: Merge additional params from session (year, month, divisi_id, jenis_data)
            if (!empty($sessionData['additional_params'])) {
                $importRequest->merge($sessionData['additional_params']);

                Log::info("Merged additional params to request", [
                    'params' => $sessionData['additional_params']
                ]);
            }

            // Validate importRequest has temp_file
            if (!$importRequest->has('temp_file') || empty($importRequest->input('temp_file'))) {
                Cache::forget($sessionId);
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter temp_file tidak valid dalam request.'
                ], 400);
            }

            // Route to specific execute handler
            $executeResult = null;
            switch ($importType) {
                case 'data_cc':
                    $controller = new ImportCCController();
                    $executeResult = $controller->executeDataCC($importRequest);
                    break;

                case 'data_am':
                    $controller = new ImportAMController();
                    $executeResult = $controller->executeDataAM($importRequest);
                    break;

                case 'revenue_cc':
                    $controller = new ImportCCController();
                    $executeResult = $controller->executeRevenueCC($importRequest);
                    break;

                case 'revenue_am':
                    $controller = new ImportAMController();
                    $executeResult = $controller->executeRevenueAM($importRequest);
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
            Cache::forget($sessionId);

            Log::info("Execute Import completed", [
                'type' => $importType,
                'session_id' => $sessionId,
                'result' => $executeResult
            ]);

            return response()->json($executeResult);

        } catch (\Exception $e) {
            Log::error("Execute Import error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Legacy single-step import (maintained for backward compatibility)
     */
    public function import(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am',
                'file' => 'required|file|mimes:csv,txt|max:10240'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $importType = $request->import_type;
            $file = $request->file('file');

            // Additional validation for revenue imports
            if (in_array($importType, ['revenue_cc', 'revenue_am'])) {
                $additionalRules = $this->getAdditionalValidationRules($importType);
                $additionalValidator = Validator::make($request->all(), $additionalRules);

                if ($additionalValidator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi parameter tambahan gagal',
                        'errors' => $additionalValidator->errors()
                    ], 422);
                }
            }

            // Create temp file
            $tempPath = storage_path('app/temp_imports');
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            $tempFilename = uniqid('import_') . '_' . $file->getClientOriginalName();
            $tempFullPath = $tempPath . '/' . $tempFilename;
            $file->move($tempPath, $tempFilename);

            // Prepare request
            $importRequest = new Request();
            $importRequest->merge([
                'temp_file' => $tempFullPath
            ]);

            if ($importType === 'revenue_cc') {
                $importRequest->merge([
                    'divisi_id' => $request->divisi_id,
                    'jenis_data' => $request->jenis_data,
                    'year' => $request->year,
                    'month' => $request->month
                ]);
            }

            // Execute import
            $result = null;
            switch ($importType) {
                case 'data_cc':
                    $controller = new ImportCCController();
                    $result = $controller->executeDataCC($importRequest);
                    break;

                case 'data_am':
                    $controller = new ImportAMController();
                    $result = $controller->executeDataAM($importRequest);
                    break;

                case 'revenue_cc':
                    $controller = new ImportCCController();
                    $result = $controller->executeRevenueCC($importRequest);
                    break;

                case 'revenue_am':
                    $controller = new ImportAMController();
                    $result = $controller->executeRevenueAM($importRequest);
                    break;
            }

            // Clean up
            if (file_exists($tempFullPath)) {
                unlink($tempFullPath);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Import error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Legacy validate import (for backward compatibility)
     */
    public function validateImport(Request $request)
    {
        // Redirect to preview
        return $this->previewImport($request);
    }

    /**
     * Download error log file
     */
    public function downloadErrorLog($filename)
    {
        $filepath = public_path('storage/import_logs/' . $filename);

        if (!file_exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan'
            ], 404);
        }

        return response()->download($filepath);
    }

    /**
     * Get import history (placeholder)
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
                $fileTime = filemtime($filepath);

                if ($fileTime < $olderThan->timestamp) {
                    unlink($filepath);
                    $deletedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$deletedCount} old temp files",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Cleanup temp files error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error during cleanup: ' . $e->getMessage()
            ], 500);
        }
    }
}