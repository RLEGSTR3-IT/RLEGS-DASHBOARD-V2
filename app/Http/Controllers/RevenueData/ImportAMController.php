<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ImportAMController extends Controller
{
    /**
     * Import Revenue AM Mapping
     * Mapping AM ke CC dengan proporsi pembagian revenue
     * File harus lengkap: NIPNAS CC, Nama CC, Divisi, Segment, NIK AM, Witel HO, Divisi AM, Role, Telda, Proporsi
     */
    public function importRevenueMapping(Request $request)
    {
        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $csvData = $this->parseCsvFile($file);

            $statistics = [
                'total_rows' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'skipped_count' => 0,
                'failed_rows' => []
            ];

            // Validate required columns
            $requiredColumns = [
                'YEAR', 'MONTH', 'NIPNAS', 'NAMA_CC', 'DIVISI', 'SEGMENT',
                'NIK_AM', 'WITEL_HO', 'DIVISI_AM', 'ROLE', 'PROPORSI'
            ];

            $headers = array_shift($csvData);

            if (!$this->validateHeaders($headers, $requiredColumns)) {
                return (object) [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            // Get column indices
            $columnIndices = $this->getColumnIndices($headers, [
                'YEAR', 'MONTH', 'NIPNAS', 'NAMA_CC', 'DIVISI', 'SEGMENT',
                'NIK_AM', 'WITEL_HO', 'DIVISI_AM', 'ROLE', 'TELDA', 'PROPORSI'
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

            // Group by CC untuk validasi proporsi
            $ccGroups = [];
            foreach ($csvData as $index => $row) {
                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                $year = $this->getColumnValue($row, $columnIndices['YEAR']);
                $month = $this->getColumnValue($row, $columnIndices['MONTH']);
                $key = $nipnas . '-' . $year . '-' . $month;

                if (!isset($ccGroups[$key])) {
                    $ccGroups[$key] = [];
                }

                $ccGroups[$key][] = [
                    'index' => $index,
                    'row' => $row,
                    'proporsi' => floatval($this->getColumnValue($row, $columnIndices['PROPORSI']))
                ];
            }

            // Validate proporsi per CC harus = 100%
            $proporsiErrors = [];
            foreach ($ccGroups as $key => $group) {
                $totalProporsi = array_sum(array_column($group, 'proporsi'));
                if (abs($totalProporsi - 100) > 0.01) { // Toleransi 0.01 untuk floating point
                    $proporsiErrors[$key] = [
                        'nipnas' => explode('-', $key)[0],
                        'total_proporsi' => $totalProporsi,
                        'rows' => array_map(function($item) { return $item['index'] + 2; }, $group)
                    ];
                }
            }

            // Jika ada error proporsi, tolak seluruh batch
            if (!empty($proporsiErrors)) {
                $errorMessage = "Proporsi tidak valid untuk beberapa CC:\n";
                foreach ($proporsiErrors as $key => $error) {
                    $errorMessage .= "- NIPNAS {$error['nipnas']}: Total proporsi = {$error['total_proporsi']}% (harus 100%)\n";
                    $errorMessage .= "  Baris: " . implode(', ', $error['rows']) . "\n";
                }

                throw new \Exception($errorMessage);
            }

            // Process each row
            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    // Get basic data
                    $year = $this->getColumnValue($row, $columnIndices['YEAR']);
                    $month = $this->getColumnValue($row, $columnIndices['MONTH']);
                    $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                    $nikAM = $this->getColumnValue($row, $columnIndices['NIK_AM']);

                    if (empty($year) || empty($month) || empty($nipnas) || empty($nikAM)) {
                        throw new \Exception('Data wajib (YEAR, MONTH, NIPNAS, NIK_AM) tidak lengkap');
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
                            'nik_am' => $nikAM,
                            'error' => 'NIPNAS tidak ditemukan di master Corporate Customer. Data baris ini diskip.'
                        ];
                        continue;
                    }

                    // MANDATORY: Find Account Manager by NIK
                    $accountManager = DB::table('account_managers')
                        ->where('nik', $nikAM)
                        ->first();

                    if (!$accountManager) {
                        $statistics['skipped_count']++;
                        $statistics['failed_rows'][] = [
                            'row_number' => $rowNumber,
                            'nipnas' => $nipnas,
                            'nik_am' => $nikAM,
                            'error' => 'NIK AM tidak ditemukan di master Account Manager. Data baris ini diskip.'
                        ];
                        continue;
                    }

                    // Get CC Revenue untuk ambil target & real revenue
                    $divisiName = $this->getColumnValue($row, $columnIndices['DIVISI']);
                    $divisi = DB::table('divisi')
                        ->where('nama', 'LIKE', "%{$divisiName}%")
                        ->orWhere('kode', 'LIKE', "%{$divisiName}%")
                        ->first();

                    if (!$divisi) {
                        throw new \Exception("Divisi '{$divisiName}' tidak ditemukan");
                    }

                    // Cari CC Revenue untuk periode ini
                    $ccRevenue = DB::table('cc_revenues')
                        ->where('corporate_customer_id', $corporateCustomer->id)
                        ->where('divisi_id', $divisi->id)
                        ->where('bulan', intval($month))
                        ->where('tahun', intval($year))
                        ->first();

                    if (!$ccRevenue) {
                        $statistics['skipped_count']++;
                        $statistics['failed_rows'][] = [
                            'row_number' => $rowNumber,
                            'nipnas' => $nipnas,
                            'nik_am' => $nikAM,
                            'error' => 'Data Revenue CC untuk NIPNAS ini pada periode tersebut tidak ditemukan. Import Revenue CC terlebih dahulu.'
                        ];
                        continue;
                    }

                    // Get proporsi
                    $proporsi = floatval($this->getColumnValue($row, $columnIndices['PROPORSI'])) / 100; // Convert to decimal

                    // Get witel_id
                    $witelId = null;
                    $witelName = $this->getColumnValue($row, $columnIndices['WITEL_HO']);
                    if (!empty($witelName)) {
                        $witel = DB::table('witel')
                            ->where('nama', 'LIKE', "%{$witelName}%")
                            ->first();
                        $witelId = $witel ? $witel->id : null;
                    }

                    // Get telda_id jika HOTDA
                    $teldaId = null;
                    $role = strtoupper($this->getColumnValue($row, $columnIndices['ROLE']));
                    if ($role === 'HOTDA') {
                        $teldaName = $this->getColumnValue($row, $columnIndices['TELDA']);
                        if (!empty($teldaName)) {
                            $telda = DB::table('teldas')
                                ->where('nama', 'LIKE', "%{$teldaName}%")
                                ->first();
                            $teldaId = $telda ? $telda->id : null;
                        }
                    }

                    // Calculate revenue berdasarkan proporsi
                    $targetRevenue = $ccRevenue->target_revenue * $proporsi;
                    $realRevenue = $ccRevenue->real_revenue * $proporsi;

                    // Calculate achievement rate
                    $achievementRate = 0;
                    if ($targetRevenue > 0) {
                        $achievementRate = ($realRevenue / $targetRevenue) * 100;
                    }

                    // Prepare data untuk insert/update
                    $dataToSave = [
                        'account_manager_id' => $accountManager->id,
                        'corporate_customer_id' => $corporateCustomer->id,
                        'divisi_id' => $divisi->id,
                        'witel_id' => $witelId,
                        'telda_id' => $teldaId,
                        'proporsi' => $proporsi,
                        'target_revenue' => $targetRevenue,
                        'real_revenue' => $realRevenue,
                        'achievement_rate' => round($achievementRate, 2),
                        'bulan' => intval($month),
                        'tahun' => intval($year),
                        'updated_at' => now()
                    ];

                    // Check if record exists
                    $existingRecord = DB::table('am_revenues')
                        ->where('account_manager_id', $accountManager->id)
                        ->where('corporate_customer_id', $corporateCustomer->id)
                        ->where('bulan', intval($month))
                        ->where('tahun', intval($year))
                        ->first();

                    if ($existingRecord) {
                        // Update existing record
                        DB::table('am_revenues')
                            ->where('id', $existingRecord->id)
                            ->update($dataToSave);
                    } else {
                        // Insert new record
                        $dataToSave['created_at'] = now();
                        DB::table('am_revenues')->insert($dataToSave);
                    }

                    $statistics['success_count']++;

                } catch (\Exception $e) {
                    $statistics['failed_count']++;
                    $statistics['failed_rows'][] = [
                        'row_number' => $rowNumber,
                        'nipnas' => $nipnas ?? 'N/A',
                        'nik_am' => $nikAM ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            // Generate error log if needed
            $errorLogPath = null;
            if (count($statistics['failed_rows']) > 0) {
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'revenue_am');
            }

            return (object) [
                'success' => true,
                'message' => 'Import Revenue AM Mapping selesai',
                'statistics' => [
                    'total_rows' => $statistics['total_rows'],
                    'success_count' => $statistics['success_count'],
                    'failed_count' => $statistics['failed_count'],
                    'skipped_count' => $statistics['skipped_count']
                ],
                'error_log_path' => $errorLogPath
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Revenue AM Mapping Error: ' . $e->getMessage());

            return (object) [
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Parse CSV file to array
     */
    private function parseCsvFile($file)
    {
        $csvData = [];

        // Handle Excel files if needed
        if (in_array($file->getClientOriginalExtension(), ['xlsx', 'xls'])) {
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
        fputcsv($handle, ['Baris', 'NIPNAS', 'NIK AM', 'Error']);

        foreach ($failedRows as $row) {
            fputcsv($handle, [
                $row['row_number'],
                $row['nipnas'] ?? 'N/A',
                $row['nik_am'] ?? 'N/A',
                $row['error']
            ]);
        }

        fclose($handle);

        return asset('storage/import_logs/' . $filename);
    }
}