<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use App\Models\Witel;
use App\Models\Divisi;
use App\Models\WitelTargetRevenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use League\Csv\Reader;
use League\Csv\Writer;
use League\Csv\Exception as CsvException;

/**
 * ImportWitelTargetController
 * 
 * Controller untuk handle import Target Revenue BILL Witel (khusus DPS/DSS)
 * 
 * BUSINESS RULES:
 * ===============
 * 1. Witel Target Revenue BILL hanya untuk divisi DPS dan DSS
 * 2. DGS tidak memiliki witel target revenue bill karena witel_ho = witel_bill
 * 3. Template format:
 *    - WITEL_NAME
 *    - DIVISI (DPS/DSS)
 *    - TARGET_REVENUE_BILL
 *    - YEAR
 *    - MONTH
 * 
 * FEATURES:
 * =========
 * ✅ Download template CSV
 * ✅ Preview import data
 * ✅ Execute import with validation
 * ✅ Update or Insert logic (upsert)
 * ✅ Comprehensive error handling
 * ✅ Detailed logging
 * 
 * @version 1.0.0
 * @date 2026-02-06
 */
class ImportWitelTargetController extends Controller
{
    /**
     * Download template CSV untuk import Witel Target Revenue BILL
     * 
     * Template columns:
     * - WITEL_NAME: Nama witel (harus sesuai dengan master witel)
     * - DIVISI: Kode divisi (DPS atau DSS only)
     * - TARGET_REVENUE_BILL: Target revenue dalam rupiah
     * - YEAR: Tahun (4 digit)
     * - MONTH: Bulan (1-12)
     * 
     * @return \Illuminate\Http\Response
     */
    public function downloadTemplate()
    {
        try {
            Log::info('Downloading Witel Target Revenue BILL template');

            // Create CSV in memory
            $csv = Writer::createFromString('');
            
            // Set BOM for Excel UTF-8 compatibility
            $csv->setOutputBOM(Writer::BOM_UTF8);

            // Header row
            $headers = [
                'WITEL_NAME',
                'DIVISI',
                'TARGET_REVENUE_BILL',
                'YEAR',
                'MONTH'
            ];
            
            $csv->insertOne($headers);

            // Get sample data from database
            $sampleWitels = Witel::orderBy('nama')->limit(3)->get();
            $currentYear = date('Y');
            $currentMonth = date('n');

            // Add example rows
            if ($sampleWitels->count() > 0) {
                foreach ($sampleWitels as $index => $witel) {
                    $divisi = $index % 2 === 0 ? 'DPS' : 'DSS';
                    
                    $csv->insertOne([
                        $witel->nama,
                        $divisi,
                        ($index + 1) * 1000000000, // Example: 1M, 2M, 3M
                        $currentYear,
                        $currentMonth
                    ]);
                }
            } else {
                // Fallback examples if no witel data
                $csv->insertOne([
                    'BALI',
                    'DPS',
                    5000000000,
                    $currentYear,
                    $currentMonth
                ]);
                
                $csv->insertOne([
                    'NUSA TENGGARA',
                    'DSS',
                    3000000000,
                    $currentYear,
                    $currentMonth
                ]);
            }

            // Generate filename
            $filename = 'template_witel_target_bill_' . date('Ymd_His') . '.csv';

            Log::info('Witel Target Revenue BILL template generated', [
                'filename' => $filename,
                'sample_rows' => $sampleWitels->count()
            ]);

            // Return as download
            return Response::make($csv->toString(), 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate Witel Target Revenue template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview import data Witel Target Revenue BILL
     * 
     * Validates and shows preview of data to be imported
     * 
     * @param string $tempFilePath Path to uploaded CSV file
     * @return array Preview result
     */
    public function previewWitelTarget($tempFilePath)
    {
        try {
            Log::info('IWTC - Previewing Witel Target Revenue BILL', [
                'file_path' => $tempFilePath
            ]);

            if (!file_exists($tempFilePath)) {
                return [
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ];
            }

            // Read CSV
            $csv = Reader::createFromPath($tempFilePath, 'r');
            $csv->setHeaderOffset(0);
            
            $headers = $csv->getHeader();
            $records = iterator_to_array($csv->getRecords());

            // Validate headers
            $requiredHeaders = ['WITEL_NAME', 'DIVISI', 'TARGET_REVENUE_BILL', 'YEAR', 'MONTH'];
            $missingHeaders = array_diff($requiredHeaders, $headers);

            if (!empty($missingHeaders)) {
                Log::warning('Missing required headers', [
                    'missing' => $missingHeaders
                ]);

                return [
                    'success' => false,
                    'message' => 'Header tidak lengkap. Missing: ' . implode(', ', $missingHeaders),
                    'required_headers' => $requiredHeaders,
                    'found_headers' => $headers
                ];
            }

            // Get master data for validation
            $witels = Witel::pluck('id', 'nama')->toArray();
            $divisiMap = Divisi::pluck('id', 'kode')->toArray();

            // Process records
            $validRecords = [];
            $invalidRecords = [];
            $stats = [
                'total_rows' => count($records),
                'valid_rows' => 0,
                'invalid_rows' => 0,
                'will_insert' => 0,
                'will_update' => 0
            ];

            foreach ($records as $index => $record) {
                $rowNumber = $index + 2; // +2 because header is row 1, data starts at row 2

                // Clean and validate data
                $witelName = trim($record['WITEL_NAME'] ?? '');
                $divisiKode = strtoupper(trim($record['DIVISI'] ?? ''));
                $targetRevenueBill = $this->parseDecimal($record['TARGET_REVENUE_BILL'] ?? '0');
                $year = (int) ($record['YEAR'] ?? 0);
                $month = (int) ($record['MONTH'] ?? 0);

                $errors = [];

                // Validate Witel
                if (empty($witelName)) {
                    $errors[] = 'WITEL_NAME kosong';
                } elseif (!isset($witels[$witelName])) {
                    $errors[] = "WITEL_NAME '{$witelName}' tidak ditemukan di master";
                }

                // Validate Divisi (DPS/DSS only)
                if (empty($divisiKode)) {
                    $errors[] = 'DIVISI kosong';
                } elseif (!in_array($divisiKode, ['DPS', 'DSS'])) {
                    $errors[] = "DIVISI '{$divisiKode}' tidak valid. Harus DPS atau DSS";
                } elseif (!isset($divisiMap[$divisiKode])) {
                    $errors[] = "DIVISI '{$divisiKode}' tidak ditemukan di master";
                }

                // Validate target revenue
                if ($targetRevenueBill < 0) {
                    $errors[] = 'TARGET_REVENUE_BILL tidak boleh negatif';
                }

                // Validate year
                if ($year < 2000 || $year > 2099) {
                    $errors[] = "YEAR '{$year}' tidak valid. Harus 2000-2099";
                }

                // Validate month
                if ($month < 1 || $month > 12) {
                    $errors[] = "MONTH '{$month}' tidak valid. Harus 1-12";
                }

                if (empty($errors)) {
                    $witelId = $witels[$witelName];
                    $divisiId = $divisiMap[$divisiKode];

                    // Check if record exists
                    $existing = WitelTargetRevenue::where('witel_id', $witelId)
                        ->where('divisi_id', $divisiId)
                        ->where('tahun', $year)
                        ->where('bulan', $month)
                        ->first();

                    $action = $existing ? 'update' : 'insert';
                    
                    if ($action === 'update') {
                        $stats['will_update']++;
                    } else {
                        $stats['will_insert']++;
                    }

                    $validRecords[] = [
                        'row' => $rowNumber,
                        'witel_name' => $witelName,
                        'witel_id' => $witelId,
                        'divisi_kode' => $divisiKode,
                        'divisi_id' => $divisiId,
                        'target_revenue_bill' => $targetRevenueBill,
                        'year' => $year,
                        'month' => $month,
                        'action' => $action,
                        'existing_value' => $existing ? $existing->target_revenue_bill : null
                    ];

                    $stats['valid_rows']++;
                } else {
                    $invalidRecords[] = [
                        'row' => $rowNumber,
                        'data' => $record,
                        'errors' => $errors
                    ];

                    $stats['invalid_rows']++;
                }
            }

            Log::info('IWTC - Preview completed', [
                'stats' => $stats
            ]);

            return [
                'success' => true,
                'message' => 'Preview berhasil',
                'stats' => $stats,
                'valid_records' => array_slice($validRecords, 0, 100), // Limit to 100 for preview
                'invalid_records' => array_slice($invalidRecords, 0, 50), // Limit to 50 errors
                'has_more_valid' => count($validRecords) > 100,
                'has_more_invalid' => count($invalidRecords) > 50
            ];

        } catch (CsvException $e) {
            Log::error('IWTC - CSV parsing error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Gagal membaca file CSV: ' . $e->getMessage()
            ];

        } catch (\Exception $e) {
            Log::error('IWTC - Preview error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Execute import Witel Target Revenue BILL
     * 
     * Imports data to witel_target_revenues table with upsert logic
     * 
     * @param string $tempFilePath Path to uploaded CSV file
     * @param string $filterType Filter type: 'all', 'new', or 'update'
     * @return array Import result
     */
    public function executeWitelTarget($tempFilePath, $filterType = 'all')
    {
        DB::beginTransaction();

        try {
            Log::info('IWTC - Executing Witel Target Revenue BILL import', [
                'file_path' => $tempFilePath,
                'filter_type' => $filterType
            ]);

            if (!file_exists($tempFilePath)) {
                return [
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ];
            }

            // Read CSV
            $csv = Reader::createFromPath($tempFilePath, 'r');
            $csv->setHeaderOffset(0);
            $records = iterator_to_array($csv->getRecords());

            // Get master data
            $witels = Witel::pluck('id', 'nama')->toArray();
            $divisiMap = Divisi::pluck('id', 'kode')->toArray();

            // Process records
            $stats = [
                'total_rows' => count($records),
                'processed' => 0,
                'inserted' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0
            ];

            $errors = [];

            foreach ($records as $index => $record) {
                $rowNumber = $index + 2;

                try {
                    // Clean data
                    $witelName = trim($record['WITEL_NAME'] ?? '');
                    $divisiKode = strtoupper(trim($record['DIVISI'] ?? ''));
                    $targetRevenueBill = $this->parseDecimal($record['TARGET_REVENUE_BILL'] ?? '0');
                    $year = (int) ($record['YEAR'] ?? 0);
                    $month = (int) ($record['MONTH'] ?? 0);

                    // Skip if invalid
                    if (empty($witelName) || !isset($witels[$witelName])) {
                        $stats['skipped']++;
                        continue;
                    }

                    if (empty($divisiKode) || !in_array($divisiKode, ['DPS', 'DSS']) || !isset($divisiMap[$divisiKode])) {
                        $stats['skipped']++;
                        continue;
                    }

                    if ($year < 2000 || $year > 2099 || $month < 1 || $month > 12) {
                        $stats['skipped']++;
                        continue;
                    }

                    $witelId = $witels[$witelName];
                    $divisiId = $divisiMap[$divisiKode];

                    // Check if exists
                    $existing = WitelTargetRevenue::where('witel_id', $witelId)
                        ->where('divisi_id', $divisiId)
                        ->where('tahun', $year)
                        ->where('bulan', $month)
                        ->first();

                    $isUpdate = $existing !== null;

                    // Apply filter
                    if ($filterType === 'new' && $isUpdate) {
                        $stats['skipped']++;
                        continue;
                    }

                    if ($filterType === 'update' && !$isUpdate) {
                        $stats['skipped']++;
                        continue;
                    }

                    // Upsert
                    WitelTargetRevenue::updateOrCreate(
                        [
                            'witel_id' => $witelId,
                            'divisi_id' => $divisiId,
                            'tahun' => $year,
                            'bulan' => $month
                        ],
                        [
                            'target_revenue_bill' => $targetRevenueBill
                        ]
                    );

                    if ($isUpdate) {
                        $stats['updated']++;
                    } else {
                        $stats['inserted']++;
                    }

                    $stats['processed']++;

                } catch (\Exception $e) {
                    Log::error('IWTC - Error processing row', [
                        'row' => $rowNumber,
                        'error' => $e->getMessage()
                    ]);

                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => $e->getMessage()
                    ];

                    $stats['errors']++;
                }
            }

            DB::commit();

            Log::info('IWTC - Import completed', [
                'stats' => $stats
            ]);

            $message = sprintf(
                'Import selesai: %d diproses, %d baru, %d diupdate, %d diskip, %d error',
                $stats['processed'],
                $stats['inserted'],
                $stats['updated'],
                $stats['skipped'],
                $stats['errors']
            );

            return [
                'success' => true,
                'message' => $message,
                'stats' => $stats,
                'errors' => array_slice($errors, 0, 20) // Limit to 20 errors
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('IWTC - Execute import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get import summary/statistics
     * 
     * Returns current state of witel target revenues
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSummary(Request $request)
    {
        try {
            $year = $request->input('year', date('Y'));
            $month = $request->input('month', date('n'));

            $summary = WitelTargetRevenue::with(['witel', 'divisi'])
                ->where('tahun', $year)
                ->where('bulan', $month)
                ->get()
                ->groupBy('divisi.kode')
                ->map(function($items, $divisiKode) {
                    return [
                        'divisi' => $divisiKode,
                        'witel_count' => $items->count(),
                        'total_target' => $items->sum('target_revenue_bill'),
                        'avg_target' => $items->avg('target_revenue_bill'),
                        'min_target' => $items->min('target_revenue_bill'),
                        'max_target' => $items->max('target_revenue_bill')
                    ];
                });

            $totalRecords = WitelTargetRevenue::where('tahun', $year)
                ->where('bulan', $month)
                ->count();

            return response()->json([
                'success' => true,
                'year' => $year,
                'month' => $month,
                'total_records' => $totalRecords,
                'summary_by_divisi' => $summary->values()
            ]);

        } catch (\Exception $e) {
            Log::error('IWTC - Get summary error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal get summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete witel target revenues by period
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteByPeriod(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'year' => 'required|integer|min:2000|max:2099',
                'month' => 'required|integer|min:1|max:12',
                'divisi_id' => 'nullable|exists:divisi,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $year = $request->input('year');
            $month = $request->input('month');
            $divisiId = $request->input('divisi_id');

            $query = WitelTargetRevenue::where('tahun', $year)
                ->where('bulan', $month);

            if ($divisiId) {
                $query->where('divisi_id', $divisiId);
            }

            $count = $query->count();
            $query->delete();

            Log::info('IWTC - Deleted witel target revenues', [
                'year' => $year,
                'month' => $month,
                'divisi_id' => $divisiId,
                'deleted_count' => $count
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghapus {$count} record",
                'deleted_count' => $count
            ]);

        } catch (\Exception $e) {
            Log::error('IWTC - Delete error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export witel target revenues to CSV
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportToCSV(Request $request)
    {
        try {
            $year = $request->input('year', date('Y'));
            $month = $request->input('month', date('n'));
            $divisiId = $request->input('divisi_id');

            $query = WitelTargetRevenue::with(['witel', 'divisi'])
                ->where('tahun', $year)
                ->where('bulan', $month);

            if ($divisiId) {
                $query->where('divisi_id', $divisiId);
            }

            $data = $query->orderBy('witel_id')->orderBy('divisi_id')->get();

            // Create CSV
            $csv = Writer::createFromString('');
            $csv->setOutputBOM(Writer::BOM_UTF8);

            // Header
            $csv->insertOne([
                'WITEL_NAME',
                'DIVISI',
                'TARGET_REVENUE_BILL',
                'YEAR',
                'MONTH'
            ]);

            // Data rows
            foreach ($data as $record) {
                $csv->insertOne([
                    $record->witel->nama ?? 'N/A',
                    $record->divisi->kode ?? 'N/A',
                    $record->target_revenue_bill,
                    $record->tahun,
                    $record->bulan
                ]);
            }

            $filename = "witel_target_revenue_{$year}_{$month}_" . date('Ymd_His') . '.csv';

            Log::info('IWTC - Exported witel target revenues', [
                'year' => $year,
                'month' => $month,
                'record_count' => $data->count()
            ]);

            return Response::make($csv->toString(), 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ]);

        } catch (\Exception $e) {
            Log::error('IWTC - Export error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal export data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate import file
     * 
     * Quick validation without full preview
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateFile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:csv,txt|max:10240' // Max 10MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi file gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('file');
            
            // Basic checks
            $csv = Reader::createFromPath($file->getRealPath(), 'r');
            $csv->setHeaderOffset(0);
            
            $headers = $csv->getHeader();
            $recordCount = iterator_count($csv->getRecords());

            $requiredHeaders = ['WITEL_NAME', 'DIVISI', 'TARGET_REVENUE_BILL', 'YEAR', 'MONTH'];
            $missingHeaders = array_diff($requiredHeaders, $headers);

            if (!empty($missingHeaders)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Header tidak lengkap',
                    'missing_headers' => $missingHeaders
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'File valid',
                'record_count' => $recordCount,
                'headers' => $headers
            ]);

        } catch (\Exception $e) {
            Log::error('IWTC - Validate file error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validasi file gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    // =====================================================
    // HELPER METHODS
    // =====================================================

    /**
     * Parse decimal value from string
     * 
     * Handles various formats:
     * - "1000000" → 1000000.00
     * - "1,000,000" → 1000000.00
     * - "1.000.000,00" (European) → 1000000.00
     * - "1000000.50" → 1000000.50
     * 
     * @param string $value
     * @return float
     */
    private function parseDecimal($value)
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        // Remove all non-numeric except dot and comma
        $cleaned = preg_replace('/[^\d.,]/', '', $value);

        // Handle European format (1.000.000,00)
        if (substr_count($cleaned, '.') > 1 || (strpos($cleaned, '.') !== false && strpos($cleaned, ',') !== false && strpos($cleaned, '.') < strrpos($cleaned, ','))) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } else {
            // Handle US format (1,000,000.00)
            $cleaned = str_replace(',', '', $cleaned);
        }

        return (float) $cleaned;
    }

    /**
     * Format number to Indonesian rupiah format
     * 
     * @param float $number
     * @return string
     */
    private function formatRupiah($number)
    {
        return 'Rp ' . number_format($number, 0, ',', '.');
    }

    /**
     * Get month name in Indonesian
     * 
     * @param int $month 1-12
     * @return string
     */
    private function getMonthName($month)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$month] ?? 'Unknown';
    }

    /**
     * Health check endpoint
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function health()
    {
        return response()->json([
            'success' => true,
            'message' => 'Import Witel Target Controller is healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => '1.0.0'
        ]);
    }
}