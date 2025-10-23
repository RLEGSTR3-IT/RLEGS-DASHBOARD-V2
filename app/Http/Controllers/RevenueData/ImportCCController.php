<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ImportCCController extends Controller
{
    /**
     * Import Revenue CC
     * File per divisi (1 file = 1 divisi)
     * Ada input dropdown: Divisi (DGS/DSS/DPS)
     * Ada input dropdown: Jenis Data (Revenue/Target)
     */
    public function importRevenueCC(Request $request)
    {
        // Validate additional fields for Revenue CC
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt,xlsx|max:10240',
            'divisi_id' => 'required|exists:divisi,id',
            'jenis_data' => 'required|in:revenue,target',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $divisiId = $request->divisi_id;
            $jenisData = $request->jenis_data;

            // Get divisi info
            $divisi = DB::table('divisi')->where('id', $divisiId)->first();
            if (!$divisi) {
                throw new \Exception('Divisi tidak ditemukan');
            }

            $csvData = $this->parseCsvFile($file);

            $statistics = [
                'total_rows' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'skipped_count' => 0,
                'failed_rows' => []
            ];

            // Validate required columns
            $requiredColumns = ['YEAR', 'MONTH', 'NIP_NAS', 'LSEGMENT_HO', 'WITEL_HO', 'SOURCE_DATA'];

            // Add revenue columns based on divisi
            if ($divisi->kode === 'DPS') {
                $requiredColumns[] = 'REVENUE_BILL';
                $requiredColumns[] = 'WITEL_BILL';
            } else {
                $requiredColumns[] = 'REVENUE_SOLD';
            }

            $headers = array_shift($csvData);

            if (!$this->validateHeaders($headers, $requiredColumns)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ], 422);
            }

            // Get column indices
            $columnIndices = $this->getColumnIndices($headers, [
                'YEAR', 'MONTH', 'NIP_NAS', 'LSEGMENT_HO', 'WITEL_HO', 'WITEL_BILL',
                'REVENUE_SOLD', 'REVENUE_BILL', 'SOURCE_DATA'
            ]);

            $statistics['total_rows'] = count($csvData);

            // Validate periode consistency
            $periodeSet = [];
            foreach ($csvData as $row) {
                $year = $this->getColumnValue($row, $columnIndices['YEAR']);
                $month = $this->getColumnValue($row, $columnIndices['MONTH']);
                $periodeSet[] = $year . '-' . $month;
            }
            $uniquePeriode = array_unique($periodeSet);
            if (count($uniquePeriode) > 1) {
                throw new \Exception('File mengandung data dari beberapa periode berbeda. Satu file harus mewakili satu periode.');
            }

            // Process each row
            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    // Get basic data
                    $year = $this->getColumnValue($row, $columnIndices['YEAR']);
                    $month = $this->getColumnValue($row, $columnIndices['MONTH']);
                    $nipnas = $this->getColumnValue($row, $columnIndices['NIP_NAS']);

                    if (empty($year) || empty($month) || empty($nipnas)) {
                        throw new \Exception('YEAR, MONTH, atau NIP_NAS kosong');
                    }

                    // MANDATORY: Find Corporate Customer by NIPNAS
                    $corporateCustomer = DB::table('corporate_customers')
                        ->where('nipnas', $nipnas)
                        ->first();

                    if (!$corporateCustomer) {
                        $statistics['skipped_count']++;
                        $statistics['failed_rows'][] = [
                            'row_number' => $rowNumber,
                            'nipnas' => $nipnas,
                            'error' => 'NIPNAS tidak ditemukan di master Corporate Customer. Data baris ini diskip.'
                        ];
                        continue; // Skip this row
                    }

                    // Get segment_id (boleh null)
                    $segmentId = null;
                    $segmentName = $this->getColumnValue($row, $columnIndices['LSEGMENT_HO']);
                    if (!empty($segmentName)) {
                        $segment = DB::table('segments')
                            ->where('lsegment_ho', 'LIKE', "%{$segmentName}%")
                            ->where('divisi_id', $divisiId)
                            ->first();
                        $segmentId = $segment ? $segment->id : null;
                    }

                    // Get witel_ho_id (boleh null)
                    $witelHoId = null;
                    $witelHoName = $this->getColumnValue($row, $columnIndices['WITEL_HO']);
                    if (!empty($witelHoName)) {
                        $witelHo = DB::table('witel')
                            ->where('nama', 'LIKE', "%{$witelHoName}%")
                            ->first();
                        $witelHoId = $witelHo ? $witelHo->id : null;
                    }

                    // Get witel_bill_id (boleh null, khusus untuk DPS atau jika ada kolom WITEL_BILL)
                    $witelBillId = null;
                    $witelBillName = $this->getColumnValue($row, $columnIndices['WITEL_BILL']);
                    if (!empty($witelBillName)) {
                        $witelBill = DB::table('witel')
                            ->where('nama', 'LIKE', "%{$witelBillName}%")
                            ->first();
                        $witelBillId = $witelBill ? $witelBill->id : null;
                    }

                    // Determine revenue value based on divisi
                    $revenueValue = 0;
                    $revenueSource = 'HO'; // Default

                    if (in_array($divisi->kode, ['DGS', 'DSS'])) {
                        // DGS/DSS menggunakan REVENUE_SOLD
                        $revenueValue = $this->getColumnValue($row, $columnIndices['REVENUE_SOLD']);
                        $revenueSource = 'HO';
                    } else if ($divisi->kode === 'DPS') {
                        // DPS menggunakan REVENUE_BILL
                        $revenueValue = $this->getColumnValue($row, $columnIndices['REVENUE_BILL']);
                        $revenueSource = 'BILL';
                    }

                    // Get SOURCE_DATA from file
                    $sourceData = $this->getColumnValue($row, $columnIndices['SOURCE_DATA']);

                    // Clean revenue value (remove non-numeric characters)
                    $revenueValue = preg_replace('/[^0-9.]/', '', $revenueValue);
                    $revenueValue = floatval($revenueValue);

                    // Prepare data for insert/update
                    $dataToSave = [
                        'corporate_customer_id' => $corporateCustomer->id,
                        'divisi_id' => $divisiId,
                        'segment_id' => $segmentId,
                        'witel_ho_id' => $witelHoId,
                        'witel_bill_id' => $witelBillId,
                        'nama_cc' => $corporateCustomer->nama,
                        'nipnas' => $nipnas,
                        'revenue_source' => $revenueSource,
                        'tipe_revenue' => 'REGULER', // Default, bisa disesuaikan
                        'bulan' => intval($month),
                        'tahun' => intval($year),
                        'updated_at' => now()
                    ];

                    // Set target_revenue or real_revenue based on jenis_data
                    if ($jenisData === 'target') {
                        $dataToSave['target_revenue'] = $revenueValue;
                    } else {
                        $dataToSave['real_revenue'] = $revenueValue;
                    }

                    // Check if record exists
                    $existingRecord = DB::table('cc_revenues')
                        ->where('corporate_customer_id', $corporateCustomer->id)
                        ->where('divisi_id', $divisiId)
                        ->where('bulan', intval($month))
                        ->where('tahun', intval($year))
                        ->where('tipe_revenue', 'REGULER')
                        ->first();

                    if ($existingRecord) {
                        // Update existing record
                        DB::table('cc_revenues')
                            ->where('id', $existingRecord->id)
                            ->update($dataToSave);
                    } else {
                        // Insert new record
                        $dataToSave['created_at'] = now();

                        // Set default values for columns not being updated
                        if ($jenisData === 'target') {
                            $dataToSave['real_revenue'] = 0;
                        } else {
                            $dataToSave['target_revenue'] = 0;
                        }

                        DB::table('cc_revenues')->insert($dataToSave);
                    }

                    $statistics['success_count']++;

                } catch (\Exception $e) {
                    $statistics['failed_count']++;
                    $statistics['failed_rows'][] = [
                        'row_number' => $rowNumber,
                        'nipnas' => $nipnas ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            // Generate error log if needed
            $errorLogPath = null;
            if (count($statistics['failed_rows']) > 0) {
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'revenue_cc');
            }

            return response()->json([
                'success' => true,
                'message' => 'Import Revenue CC selesai',
                'statistics' => [
                    'total_rows' => $statistics['total_rows'],
                    'success_count' => $statistics['success_count'],
                    'failed_count' => $statistics['failed_count'],
                    'skipped_count' => $statistics['skipped_count']
                ],
                'error_log_path' => $errorLogPath
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Revenue CC Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse CSV file to array
     */
    private function parseCsvFile($file)
    {
        $csvData = [];

        // Handle Excel files if needed (you can use PhpSpreadsheet)
        if (in_array($file->getClientOriginalExtension(), ['xlsx', 'xls'])) {
            // For now, only support CSV
            throw new \Exception('Saat ini hanya mendukung file CSV. Silakan convert file Excel ke CSV terlebih dahulu.');
        }

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
     * Generate error log CSV
     */
    private function generateErrorLog($failedRows, $type)
    {
        if (empty($failedRows)) {
            return null;
        }

        $filename = 'error_log_' . $type . '_' . time() . '.csv';
        $filepath = storage_path('app/public/import_logs/' . $filename);

        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $handle = fopen($filepath, 'w');
        fputcsv($handle, ['Baris', 'NIPNAS', 'Error']);

        foreach ($failedRows as $row) {
            fputcsv($handle, [
                $row['row_number'],
                $row['nipnas'] ?? 'N/A',
                $row['error']
            ]);
        }

        fclose($handle);

        return asset('storage/import_logs/' . $filename);
    }
}