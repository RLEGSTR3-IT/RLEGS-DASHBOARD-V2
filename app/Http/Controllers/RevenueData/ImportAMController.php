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
     *
     * FIX: Kolom YEAR & MONTH tidak ada di file, periode diambil dari input form (month picker)
     * FIX: Update struktur kolom sesuai file sebenarnya
     * FIX: Return array instead of object
     *
     * Struktur file sebenarnya:
     * NIK | NAMA AM | PROPORSI | WITEL AM | NIPNAS | STANDARD NAME | GROUP CONGLO | DIVISI AM | SEGMEN | WITEL HO | REGIONAL | DIVISI | TELDA
     */
    public function importRevenueMapping(Request $request)
    {
        // FIX: Validate tambahan untuk bulan & tahun dari form input
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'bulan' => 'required|numeric|min:1|max:12',
            'tahun' => 'required|numeric|min:2020|max:2100',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Validasi gagal: ' . $validator->errors()->first(),
                'statistics' => [
                    'total_rows' => 0,
                    'success_count' => 0,
                    'failed_count' => 0,
                    'skipped_count' => 0
                ]
            ];
        }

        try {
            DB::beginTransaction();

            $file = $request->file('file');

            // FIX: Periode diambil dari input form, bukan dari file
            $bulan = intval($request->bulan);
            $tahun = intval($request->tahun);

            $csvData = $this->parseCsvFile($file);

            $statistics = [
                'total_rows' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'skipped_count' => 0,
                'failed_rows' => []
            ];

            // FIX: Update required columns sesuai struktur file sebenarnya
            $requiredColumns = [
                'NIK', 'NAMA AM', 'PROPORSI', 'WITEL AM', 'NIPNAS',
                'STANDARD NAME', 'DIVISI'
            ];

            $headers = array_shift($csvData);

            if (!$this->validateHeaders($headers, $requiredColumns)) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns),
                    'statistics' => [
                        'total_rows' => 0,
                        'success_count' => 0,
                        'failed_count' => 0,
                        'skipped_count' => 0
                    ]
                ];
            }

            // FIX: Get column indices dengan nama kolom yang benar
            $columnIndices = $this->getColumnIndices($headers, [
                'NIK', 'NAMA AM', 'PROPORSI', 'WITEL AM', 'NIPNAS',
                'STANDARD NAME', 'GROUP CONGLO', 'DIVISI AM', 'SEGMEN',
                'WITEL HO', 'REGIONAL', 'DIVISI', 'TELDA'
            ]);

            $statistics['total_rows'] = count($csvData);

            // Group by CC untuk validasi proporsi
            $ccGroups = [];
            foreach ($csvData as $index => $row) {
                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                $key = $nipnas . '-' . $tahun . '-' . $bulan;

                if (!isset($ccGroups[$key])) {
                    $ccGroups[$key] = [];
                }

                $proporsiValue = $this->getColumnValue($row, $columnIndices['PROPORSI']);
                // Handle proporsi dalam bentuk desimal (0.3) atau persentase (30)
                $proporsiFloat = floatval($proporsiValue);
                if ($proporsiFloat > 1) {
                    $proporsiFloat = $proporsiFloat / 100; // Convert dari 30 ke 0.3
                }

                $ccGroups[$key][] = [
                    'index' => $index,
                    'row' => $row,
                    'proporsi' => $proporsiFloat
                ];
            }

            // Validate proporsi per CC harus = 1.0 (100%)
            $proporsiErrors = [];
            foreach ($ccGroups as $key => $group) {
                $totalProporsi = array_sum(array_column($group, 'proporsi'));
                if (abs($totalProporsi - 1.0) > 0.01) { // Toleransi 0.01 untuk floating point
                    $proporsiErrors[$key] = [
                        'nipnas' => explode('-', $key)[0],
                        'total_proporsi' => round($totalProporsi * 100, 2) . '%',
                        'rows' => array_map(function($item) { return $item['index'] + 2; }, $group)
                    ];
                }
            }

            // Jika ada error proporsi, tolak seluruh batch
            if (!empty($proporsiErrors)) {
                $errorMessage = "Proporsi tidak valid untuk beberapa CC:\n";
                foreach ($proporsiErrors as $key => $error) {
                    $errorMessage .= "- NIPNAS {$error['nipnas']}: Total proporsi = {$error['total_proporsi']} (harus 100%)\n";
                    $errorMessage .= "  Baris: " . implode(', ', $error['rows']) . "\n";
                }

                throw new \Exception($errorMessage);
            }

            // Process each row
            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    // FIX: Get data dari kolom yang benar
                    $nikAM = $this->getColumnValue($row, $columnIndices['NIK']);
                    $namaAM = $this->getColumnValue($row, $columnIndices['NAMA AM']);
                    $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                    $namaCC = $this->getColumnValue($row, $columnIndices['STANDARD NAME']);
                    $divisiName = $this->getColumnValue($row, $columnIndices['DIVISI']);

                    if (empty($nikAM) || empty($nipnas) || empty($divisiName)) {
                        throw new \Exception('Data wajib (NIK, NIPNAS, DIVISI) tidak lengkap');
                    }

                    // Validate NIK format
                    if (!is_numeric($nikAM)) {
                        throw new \Exception('NIK harus berupa angka');
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

                    // Find Divisi
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
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
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
                    $proporsiValue = $this->getColumnValue($row, $columnIndices['PROPORSI']);
                    $proporsi = floatval($proporsiValue);

                    // Handle proporsi dalam bentuk persentase atau desimal
                    if ($proporsi > 1) {
                        $proporsi = $proporsi / 100; // Convert dari 30 ke 0.3
                    }

                    // Validate proporsi range
                    if ($proporsi <= 0 || $proporsi > 1) {
                        throw new \Exception('Proporsi harus antara 0-100% atau 0.0-1.0');
                    }

                    // Get witel_id dari WITEL AM
                    $witelId = null;
                    $witelName = $this->getColumnValue($row, $columnIndices['WITEL AM']);
                    if (!empty($witelName)) {
                        $witel = DB::table('witel')
                            ->where('nama', 'LIKE', "%{$witelName}%")
                            ->first();
                        $witelId = $witel ? $witel->id : null;
                    }

                    // Get telda_id jika HOTDA
                    $teldaId = null;
                    if ($accountManager->role === 'HOTDA') {
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
                        'bulan' => $bulan, // FIX: Dari input form
                        'tahun' => $tahun, // FIX: Dari input form
                        'updated_at' => now()
                    ];

                    // Check if record exists
                    $existingRecord = DB::table('am_revenues')
                        ->where('account_manager_id', $accountManager->id)
                        ->where('corporate_customer_id', $corporateCustomer->id)
                        ->where('divisi_id', $divisi->id)
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
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

            // FIX: Return array instead of object
            return [
                'success' => true,
                'message' => 'Import Revenue AM Mapping selesai',
                'statistics' => [
                    'total_rows' => $statistics['total_rows'],
                    'success_count' => $statistics['success_count'],
                    'failed_count' => $statistics['failed_count'],
                    'skipped_count' => $statistics['skipped_count']
                ],
                'error_log_path' => $errorLogPath,
                'periode' => Carbon::createFromDate($tahun, $bulan, 1)->locale('id')->translatedFormat('F Y')
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Revenue AM Mapping Error: ' . $e->getMessage());

            // FIX: Return array instead of object
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage(),
                'statistics' => [
                    'total_rows' => 0,
                    'success_count' => 0,
                    'failed_count' => 0,
                    'skipped_count' => 0
                ]
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