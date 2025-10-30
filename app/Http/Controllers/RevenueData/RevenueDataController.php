<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RevenueDataController extends Controller
{
    /**
     * Display the main revenue data page
     */
    public function index(Request $request)
    {
        return view('revenue.revenueData');
    }

    // =====================================================
    // REVENUE CC - READ (GET)
    // =====================================================

    /**
     * Get Revenue CC data with filters
     * FIXED: Kolom divisi sekarang muncul dengan benar menggunakan leftJoin
     */
    public function getRevenueCC(Request $request)
    {
        $startTime = microtime(true);

        try {
            $query = DB::table('cc_revenues as cr')
                ->join('corporate_customers as cc', 'cr.corporate_customer_id', '=', 'cc.id')
                ->leftJoin('divisi as d', 'cr.divisi_id', '=', 'd.id') // FIXED: leftJoin untuk handle NULL
                ->leftJoin('segments as s', 'cr.segment_id', '=', 's.id')
                ->leftJoin('witel as w_ho', 'cr.witel_ho_id', '=', 'w_ho.id')
                ->leftJoin('witel as w_bill', 'cr.witel_bill_id', '=', 'w_bill.id')
                ->select(
                    'cr.id',
                    'cc.nama as nama_cc',
                    'cc.nipnas',
                    'd.nama as divisi_nama',     // FIXED: Pastikan ini terkirim ke frontend
                    'd.kode as divisi_kode',      // FIXED: Pastikan ini terkirim ke frontend
                    's.lsegment_ho as segment',
                    's.ssegment_ho as segment_code',
                    'w_ho.nama as witel_ho',
                    'w_bill.nama as witel_bill',
                    'cr.target_revenue',
                    'cr.real_revenue',
                    'cr.revenue_source',
                    'cr.tipe_revenue',
                    'cr.bulan',
                    'cr.tahun',
                    'cr.divisi_id',               // ADDED: Untuk keperluan edit
                    'cr.segment_id',              // ADDED: Untuk keperluan edit
                    'cr.witel_ho_id',             // ADDED: Untuk keperluan edit
                    'cr.witel_bill_id',           // ADDED: Untuk keperluan edit
                    'cr.corporate_customer_id'    // ADDED: Untuk keperluan identifikasi
                );

            // Filter by search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('cc.nama', 'like', "%{$search}%")
                      ->orWhere('cc.nipnas', 'like', "%{$search}%");
                });
            }

            // Filter by Witel
            if ($request->filled('witel_id') && $request->witel_id != 'all') {
                $query->where(function($q) use ($request) {
                    $q->where('cr.witel_ho_id', $request->witel_id)
                      ->orWhere('cr.witel_bill_id', $request->witel_id);
                });
            }

            // Filter by Division
            if ($request->filled('divisi_id') && $request->divisi_id != 'all') {
                $query->where('cr.divisi_id', $request->divisi_id);
            }

            // Filter by Segment
            if ($request->filled('segment_id') && $request->segment_id != 'all') {
                $query->where('cr.segment_id', $request->segment_id);
            }

            // Filter by Period (Month-Year)
            if ($request->filled('periode')) {
                $periode = Carbon::parse($request->periode);
                $query->where('cr.bulan', $periode->month)
                      ->where('cr.tahun', $periode->year);
            }

            // Filter by Tipe Revenue (Reguler/NGTMA/Kombinasi)
            if ($request->filled('tipe_revenue')) {
                if ($request->tipe_revenue == 'REGULER') {
                    $query->where('cr.tipe_revenue', 'REGULER');
                } elseif ($request->tipe_revenue == 'NGTMA') {
                    $query->where('cr.tipe_revenue', 'NGTMA');
                }
                // Kombinasi = tampilkan semua (tidak perlu where)
            }

            $results = $query->orderBy('cr.tahun', 'desc')
                            ->orderBy('cr.bulan', 'desc')
                            ->orderBy('cc.nama', 'asc')
                            ->paginate($request->get('per_page', 25));

            // Transform items
            $items = [];
            foreach ($results->items() as $item) {
                // FIXED: Pastikan divisi_nama ada, jika NULL berikan default '-'
                $item->divisi_nama = $item->divisi_nama ?? '-';
                $item->divisi_kode = $item->divisi_kode ?? '-';

                // Calculate achievement rate
                $item->achievement_rate = $item->target_revenue > 0
                    ? round(($item->real_revenue / $item->target_revenue) * 100, 2)
                    : 0;

                $item->achievement_color = $this->getAchievementColor($item->achievement_rate);

                // Tentukan revenue type berdasarkan revenue_source
                $item->revenue_type = $item->revenue_source == 'HO'
                    ? 'Revenue Sold (Witel HO)'
                    : ($item->revenue_source == 'BILL'
                        ? 'Revenue Bill (Witel Bill)'
                        : 'Revenue ' . $item->revenue_source);

                // Tentukan witel yang ditampilkan (prioritas HO, fallback BILL)
                $item->witel = $item->witel_ho ?? $item->witel_bill ?? '-';

                // Format bulan untuk display
                $item->bulan_display = Carbon::createFromDate($item->tahun, $item->bulan, 1)
                    ->locale('id')
                    ->translatedFormat('M Y');

                // Format currency
                $item->target_revenue_formatted = 'Rp ' . number_format($item->target_revenue, 0, ',', '.');
                $item->real_revenue_formatted = 'Rp ' . number_format($item->real_revenue, 0, ',', '.');
                $item->achievement_display = number_format($item->achievement_rate, 1) . '%';

                // Check if has related AM revenues
                $item->has_am_revenues = DB::table('am_revenues')
                    ->where('corporate_customer_id', $item->corporate_customer_id)
                    ->where('divisi_id', $item->divisi_id)
                    ->where('bulan', $item->bulan)
                    ->where('tahun', $item->tahun)
                    ->exists();

                $items[] = $item;
            }

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'success' => true,
                'current_page' => $results->currentPage(),
                'data' => $items,
                'first_page_url' => $results->url(1),
                'from' => $results->firstItem(),
                'last_page' => $results->lastPage(),
                'last_page_url' => $results->url($results->lastPage()),
                'next_page_url' => $results->nextPageUrl(),
                'path' => $results->path(),
                'per_page' => $results->perPage(),
                'prev_page_url' => $results->previousPageUrl(),
                'to' => $results->lastItem(),
                'total' => $results->total(),
                '_metadata' => [
                    'query_time_ms' => $queryTime,
                    'timestamp' => now()->toIso8601String(),
                    'filters_applied' => $request->except(['page', 'per_page'])
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get Revenue CC Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Revenue CC: ' . $e->getMessage(),
                'data' => [],
                'total' => 0
            ], 500);
        }
    }

    // =====================================================
    // REVENUE CC - CREATE, UPDATE, DELETE
    // =====================================================

    /**
     * Update Revenue CC
     * ENHANCED: Better validation messages
     */
    public function updateRevenueCC(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'target_revenue' => 'required|numeric|min:0',
            'real_revenue' => 'required|numeric|min:0',
        ], [
            'target_revenue.required' => 'Target revenue harus diisi',
            'target_revenue.numeric' => 'Target revenue harus berupa angka',
            'real_revenue.required' => 'Real revenue harus diisi',
            'real_revenue.numeric' => 'Real revenue harus berupa angka',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $ccRevenue = DB::table('cc_revenues')->where('id', $id)->first();

            if (!$ccRevenue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Revenue CC tidak ditemukan'
                ], 404);
            }

            $updateData = [
                'target_revenue' => $request->target_revenue,
                'real_revenue' => $request->real_revenue,
                'updated_at' => now()
            ];

            DB::table('cc_revenues')->where('id', $id)->update($updateData);

            // Recalculate related AM revenues
            $this->recalculateAMRevenues(
                $ccRevenue->corporate_customer_id,
                $ccRevenue->divisi_id,
                $ccRevenue->bulan,
                $ccRevenue->tahun
            );

            DB::commit();

            Log::info('Revenue CC Updated', [
                'id' => $id,
                'user_id' => Auth::id(),
                'data' => $updateData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Revenue CC berhasil diupdate',
                'data' => $updateData
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update Revenue CC Error: ' . $e->getMessage(), [
                'id' => $id,
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal update Revenue CC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single Revenue CC by ID
     */
    public function showRevenueCC($id)
    {
        try {
            $ccRevenue = DB::table('cc_revenues as cr')
                ->join('corporate_customers as cc', 'cr.corporate_customer_id', '=', 'cc.id')
                ->leftJoin('divisi as d', 'cr.divisi_id', '=', 'd.id')
                ->leftJoin('segments as s', 'cr.segment_id', '=', 's.id')
                ->leftJoin('witel as w_ho', 'cr.witel_ho_id', '=', 'w_ho.id')
                ->leftJoin('witel as w_bill', 'cr.witel_bill_id', '=', 'w_bill.id')
                ->where('cr.id', $id)
                ->select(
                    'cr.*',
                    'cc.nama as nama_cc',
                    'cc.nipnas',
                    'd.nama as divisi_nama',
                    'd.kode as divisi_kode',
                    's.lsegment_ho as segment',
                    'w_ho.nama as witel_ho',
                    'w_bill.nama as witel_bill'
                )
                ->first();

            if (!$ccRevenue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Revenue CC tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $ccRevenue
            ]);

        } catch (\Exception $e) {
            Log::error('Show Revenue CC Error: ' . $e->getMessage(), [
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Revenue CC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Revenue CC
     * FIXED: Cascade delete AM Revenue terlebih dahulu
     */
    public function deleteRevenueCC($id)
    {
        DB::beginTransaction();
        try {
            $ccRevenue = DB::table('cc_revenues')->where('id', $id)->first();

            if (!$ccRevenue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Revenue CC tidak ditemukan'
                ], 404);
            }

            // CASCADE DELETE: Hapus semua AM Revenue yang terkait terlebih dahulu
            $deletedAMRevenues = DB::table('am_revenues')
                ->where('corporate_customer_id', $ccRevenue->corporate_customer_id)
                ->where('divisi_id', $ccRevenue->divisi_id)
                ->where('bulan', $ccRevenue->bulan)
                ->where('tahun', $ccRevenue->tahun)
                ->delete();

            // Delete CC Revenue
            DB::table('cc_revenues')->where('id', $id)->delete();

            DB::commit();

            Log::info('Revenue CC Deleted (CASCADE)', [
                'id' => $id,
                'deleted_am_revenues' => $deletedAMRevenues,
                'user_id' => Auth::id()
            ]);

            $message = 'Revenue CC berhasil dihapus';
            if ($deletedAMRevenues > 0) {
                $message .= " (beserta {$deletedAMRevenues} Revenue AM terkait)";
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete Revenue CC Error: ' . $e->getMessage(), [
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Revenue CC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Delete Revenue CC (Selected IDs)
     * FIXED: Cascade delete AM Revenue terlebih dahulu
     */
    public function bulkDeleteRevenueCC(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:cc_revenues,id'
        ], [
            'ids.required' => 'Pilih minimal 1 data untuk dihapus',
            'ids.array' => 'Format data tidak valid',
            'ids.*.exists' => 'Data tidak ditemukan'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $ids = $request->ids;
            $deletedCount = 0;
            $deletedAMRevenuesTotal = 0;

            foreach ($ids as $id) {
                $ccRevenue = DB::table('cc_revenues')->where('id', $id)->first();

                if (!$ccRevenue) {
                    continue;
                }

                // CASCADE DELETE: Hapus AM Revenue terkait
                $deletedAMRevenues = DB::table('am_revenues')
                    ->where('corporate_customer_id', $ccRevenue->corporate_customer_id)
                    ->where('divisi_id', $ccRevenue->divisi_id)
                    ->where('bulan', $ccRevenue->bulan)
                    ->where('tahun', $ccRevenue->tahun)
                    ->delete();

                $deletedAMRevenuesTotal += $deletedAMRevenues;

                // Delete CC Revenue
                DB::table('cc_revenues')->where('id', $id)->delete();
                $deletedCount++;
            }

            DB::commit();

            Log::info('Bulk Delete Revenue CC (CASCADE)', [
                'total_selected' => count($ids),
                'deleted_count' => $deletedCount,
                'deleted_am_revenues' => $deletedAMRevenuesTotal,
                'user_id' => Auth::id()
            ]);

            $message = "Berhasil menghapus {$deletedCount} Revenue CC";
            if ($deletedAMRevenuesTotal > 0) {
                $message .= " beserta {$deletedAMRevenuesTotal} Revenue AM terkait";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deletedCount,
                'deleted_am_revenues' => $deletedAMRevenuesTotal
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk Delete Revenue CC Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Delete All Revenue CC (with filters)
     * FIXED: Cascade delete AM Revenue terlebih dahulu
     */
    public function bulkDeleteAllRevenueCC(Request $request)
    {
        DB::beginTransaction();
        try {
            $query = DB::table('cc_revenues as cr')
                ->join('corporate_customers as cc', 'cr.corporate_customer_id', '=', 'cc.id');

            // Apply same filters as getRevenueCC
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('cc.nama', 'like', "%{$search}%")
                      ->orWhere('cc.nipnas', 'like', "%{$search}%");
                });
            }

            if ($request->filled('witel_id') && $request->witel_id != 'all') {
                $query->where(function($q) use ($request) {
                    $q->where('cr.witel_ho_id', $request->witel_id)
                      ->orWhere('cr.witel_bill_id', $request->witel_id);
                });
            }

            if ($request->filled('divisi_id') && $request->divisi_id != 'all') {
                $query->where('cr.divisi_id', $request->divisi_id);
            }

            if ($request->filled('segment_id') && $request->segment_id != 'all') {
                $query->where('cr.segment_id', $request->segment_id);
            }

            if ($request->filled('periode')) {
                $periode = Carbon::parse($request->periode);
                $query->where('cr.bulan', $periode->month)
                      ->where('cr.tahun', $periode->year);
            }

            if ($request->filled('tipe_revenue')) {
                if ($request->tipe_revenue == 'REGULER') {
                    $query->where('cr.tipe_revenue', 'REGULER');
                } elseif ($request->tipe_revenue == 'NGTMA') {
                    $query->where('cr.tipe_revenue', 'NGTMA');
                }
            }

            $ccRevenues = $query->select('cr.*')->get();
            $totalCount = $ccRevenues->count();

            if ($totalCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data yang sesuai dengan filter'
                ], 404);
            }

            $deletedAMRevenuesTotal = 0;

            // CASCADE DELETE untuk setiap CC Revenue
            foreach ($ccRevenues as $ccRevenue) {
                $deletedAMRevenues = DB::table('am_revenues')
                    ->where('corporate_customer_id', $ccRevenue->corporate_customer_id)
                    ->where('divisi_id', $ccRevenue->divisi_id)
                    ->where('bulan', $ccRevenue->bulan)
                    ->where('tahun', $ccRevenue->tahun)
                    ->delete();

                $deletedAMRevenuesTotal += $deletedAMRevenues;
            }

            // Hapus semua CC Revenue yang sesuai filter
            $ids = $ccRevenues->pluck('id')->toArray();
            $deletedCount = DB::table('cc_revenues')
                ->whereIn('id', $ids)
                ->delete();

            DB::commit();

            $message = "Bulk delete selesai. Berhasil menghapus {$deletedCount} Revenue CC";
            if ($deletedAMRevenuesTotal > 0) {
                $message .= " beserta {$deletedAMRevenuesTotal} Revenue AM terkait";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'total_count' => $totalCount,
                'deleted_count' => $deletedCount,
                'deleted_am_revenues' => $deletedAMRevenuesTotal
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk Delete All Revenue CC Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    // =====================================================
    // REVENUE AM - READ (GET)
    // =====================================================

    /**
     * Get Revenue AM data with filters
     * FIXED: Achievement rate muncul dengan benar, field role, divisi, telda_nama
     */
    public function getRevenueAM(Request $request)
    {
        $startTime = microtime(true);

        try {
            $query = DB::table('am_revenues as ar')
                ->join('account_managers as am', 'ar.account_manager_id', '=', 'am.id')
                ->join('corporate_customers as cc', 'ar.corporate_customer_id', '=', 'cc.id')
                ->leftJoin('divisi as d', 'ar.divisi_id', '=', 'd.id')
                ->leftJoin('witel as w', 'ar.witel_id', '=', 'w.id')
                ->leftJoin('teldas as t', 'ar.telda_id', '=', 't.id')
                ->select(
                    'ar.id',
                    'am.nama as nama_am',
                    'am.nik as nik_am',
                    'am.role',  // FIXED: Kirim field role
                    'cc.nama as nama_cc',
                    'cc.nipnas',
                    'd.nama as divisi_nama',
                    'd.kode as divisi_kode',
                    'w.nama as witel_nama',
                    't.nama as telda_nama',  // FIXED: Kirim field telda_nama
                    'ar.target_revenue',
                    'ar.real_revenue',
                    'ar.proporsi',
                    'ar.achievement_rate',  // FIXED: Field achievement_rate sudah ada di database
                    'ar.bulan',
                    'ar.tahun',
                    'ar.account_manager_id',
                    'ar.corporate_customer_id',
                    'ar.divisi_id',
                    'ar.witel_id',
                    'ar.telda_id'
                );

            // Filter by search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('am.nama', 'like', "%{$search}%")
                      ->orWhere('am.nik', 'like', "%{$search}%")
                      ->orWhere('cc.nama', 'like', "%{$search}%")
                      ->orWhere('cc.nipnas', 'like', "%{$search}%");
                });
            }

            // Filter by Witel
            if ($request->filled('witel_id') && $request->witel_id != 'all') {
                $query->where('am.witel_id', $request->witel_id);
            }

            // Filter by Division
            if ($request->filled('divisi_id') && $request->divisi_id != 'all') {
                $query->where('ar.divisi_id', $request->divisi_id);
            }

            // Filter by Period (Month-Year)
            if ($request->filled('periode')) {
                $periode = Carbon::parse($request->periode);
                $query->where('ar.bulan', $periode->month)
                      ->where('ar.tahun', $periode->year);
            }

            // Filter by Role
            if ($request->filled('role') && $request->role != 'all') {
                $query->where('am.role', $request->role);
            }

            $results = $query->orderBy('ar.tahun', 'desc')
                            ->orderBy('ar.bulan', 'desc')
                            ->orderBy('am.nama', 'asc')
                            ->paginate($request->get('per_page', 25));

            // Transform items
            $items = [];
            foreach ($results->items() as $item) {
                // FIXED: Handle null values dengan default
                $item->divisi_nama = $item->divisi_nama ?? '-';
                $item->divisi_kode = $item->divisi_kode ?? '-';
                $item->witel_nama = $item->witel_nama ?? '-';
                $item->telda_nama = $item->telda_nama ?? '-';
                $item->role = $item->role ?? 'AM';

                // FIXED: Achievement rate sudah ada dari database
                $item->achievement = $item->achievement_rate ?? 0;

                $item->achievement_color = $this->getAchievementColor($item->achievement_rate);

                // Format bulan untuk display
                $item->bulan_display = Carbon::createFromDate($item->tahun, $item->bulan, 1)
                    ->locale('id')
                    ->translatedFormat('M Y');

                // Format proporsi as percentage
                $item->proporsi_display = number_format($item->proporsi * 100, 1) . '%';

                // Format currency
                $item->target_revenue_formatted = 'Rp ' . number_format($item->target_revenue, 0, ',', '.');
                $item->real_revenue_formatted = 'Rp ' . number_format($item->real_revenue, 0, ',', '.');
                $item->achievement_display = number_format($item->achievement_rate, 1) . '%';

                $items[] = $item;
            }

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'success' => true,
                'current_page' => $results->currentPage(),
                'data' => $items,
                'first_page_url' => $results->url(1),
                'from' => $results->firstItem(),
                'last_page' => $results->lastPage(),
                'last_page_url' => $results->url($results->lastPage()),
                'next_page_url' => $results->nextPageUrl(),
                'path' => $results->path(),
                'per_page' => $results->perPage(),
                'prev_page_url' => $results->previousPageUrl(),
                'to' => $results->lastItem(),
                'total' => $results->total(),
                '_metadata' => [
                    'query_time_ms' => $queryTime,
                    'timestamp' => now()->toIso8601String(),
                    'filters_applied' => $request->except(['page', 'per_page'])
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get Revenue AM Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Revenue AM: ' . $e->getMessage(),
                'data' => [],
                'total' => 0
            ], 500);
        }
    }

    /**
     * Get single Revenue AM by ID
     */
    public function showRevenueAM($id)
    {
        try {
            $amRevenue = DB::table('am_revenues as ar')
                ->join('account_managers as am', 'ar.account_manager_id', '=', 'am.id')
                ->join('corporate_customers as cc', 'ar.corporate_customer_id', '=', 'cc.id')
                ->leftJoin('divisi as d', 'ar.divisi_id', '=', 'd.id')
                ->leftJoin('witel as w', 'ar.witel_id', '=', 'w.id')
                ->leftJoin('teldas as t', 'ar.telda_id', '=', 't.id')
                ->where('ar.id', $id)
                ->select(
                    'ar.*',
                    'am.nama as nama_am',
                    'am.nik as nik_am',
                    'am.role as role_am',
                    'cc.nama as nama_cc',
                    'cc.nipnas',
                    'd.nama as divisi_nama',
                    'd.kode as divisi_kode',
                    'w.nama as witel',
                    't.nama as telda'
                )
                ->first();

            if (!$amRevenue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Revenue AM tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $amRevenue
            ]);

        } catch (\Exception $e) {
            Log::error('Show Revenue AM Error: ' . $e->getMessage(), [
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Revenue AM: ' . $e->getMessage()
            ], 500);
        }
    }

    // =====================================================
    // REVENUE AM - UPDATE, DELETE
    // =====================================================

    /**
     * Update Revenue AM
     */
    public function updateRevenueAM(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'proporsi' => 'required|numeric|min:0|max:100',
            'target_revenue' => 'required|numeric|min:0',
            'real_revenue' => 'required|numeric|min:0',
        ], [
            'proporsi.required' => 'Proporsi harus diisi',
            'proporsi.numeric' => 'Proporsi harus berupa angka',
            'target_revenue.required' => 'Target revenue harus diisi',
            'real_revenue.required' => 'Real revenue harus diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $amRevenue = DB::table('am_revenues')->where('id', $id)->first();

            if (!$amRevenue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Revenue AM tidak ditemukan'
                ], 404);
            }

            // Calculate achievement rate
            $targetRevenue = $request->target_revenue;
            $realRevenue = $request->real_revenue;
            $achievementRate = $targetRevenue > 0 ? ($realRevenue / $targetRevenue) * 100 : 0;

            $updateData = [
                'proporsi' => $request->proporsi / 100, // Convert from percentage to decimal
                'target_revenue' => $targetRevenue,
                'real_revenue' => $realRevenue,
                'achievement_rate' => round($achievementRate, 2),
                'updated_at' => now()
            ];

            DB::table('am_revenues')->where('id', $id)->update($updateData);

            DB::commit();

            Log::info('Revenue AM Updated', [
                'id' => $id,
                'user_id' => Auth::id(),
                'data' => $updateData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Revenue AM berhasil diupdate',
                'data' => $updateData
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update Revenue AM Error: ' . $e->getMessage(), [
                'id' => $id,
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal update Revenue AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Revenue AM
     */
    public function deleteRevenueAM($id)
    {
        DB::beginTransaction();
        try {
            $amRevenue = DB::table('am_revenues')->where('id', $id)->first();

            if (!$amRevenue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Revenue AM tidak ditemukan'
                ], 404);
            }

            DB::table('am_revenues')->where('id', $id)->delete();

            DB::commit();

            Log::info('Revenue AM Deleted', [
                'id' => $id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Revenue AM berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete Revenue AM Error: ' . $e->getMessage(), [
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Revenue AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Delete Revenue AM (Selected IDs)
     */
    public function bulkDeleteRevenueAM(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:am_revenues,id'
        ], [
            'ids.required' => 'Pilih minimal 1 data untuk dihapus',
            'ids.array' => 'Format data tidak valid',
            'ids.*.exists' => 'Data tidak ditemukan'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $ids = $request->ids;
            $deletedCount = DB::table('am_revenues')->whereIn('id', $ids)->delete();

            DB::commit();

            Log::info('Bulk Delete Revenue AM', [
                'total_selected' => count($ids),
                'deleted_count' => $deletedCount,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghapus {$deletedCount} Revenue AM",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk Delete Revenue AM Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Delete All Revenue AM (with filters)
     */
    public function bulkDeleteAllRevenueAM(Request $request)
    {
        DB::beginTransaction();
        try {
            $query = DB::table('am_revenues as ar')
                ->join('account_managers as am', 'ar.account_manager_id', '=', 'am.id')
                ->join('corporate_customers as cc', 'ar.corporate_customer_id', '=', 'cc.id');

            // Apply same filters as getRevenueAM
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('am.nama', 'like', "%{$search}%")
                      ->orWhere('am.nik', 'like', "%{$search}%")
                      ->orWhere('cc.nama', 'like', "%{$search}%")
                      ->orWhere('cc.nipnas', 'like', "%{$search}%");
                });
            }

            if ($request->filled('witel_id') && $request->witel_id != 'all') {
                $query->where('am.witel_id', $request->witel_id);
            }

            if ($request->filled('divisi_id') && $request->divisi_id != 'all') {
                $query->where('ar.divisi_id', $request->divisi_id);
            }

            if ($request->filled('periode')) {
                $periode = Carbon::parse($request->periode);
                $query->where('ar.bulan', $periode->month)
                      ->where('ar.tahun', $periode->year);
            }

            if ($request->filled('role') && $request->role != 'all') {
                $query->where('am.role', $request->role);
            }

            $ids = $query->pluck('ar.id')->toArray();
            $totalCount = count($ids);

            if ($totalCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data yang sesuai dengan filter'
                ], 404);
            }

            $deletedCount = DB::table('am_revenues')->whereIn('id', $ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk delete selesai. Berhasil menghapus {$deletedCount} Revenue AM",
                'total_count' => $totalCount,
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk Delete All Revenue AM Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    // =====================================================
    // DATA AM - READ (GET)
    // =====================================================

    /**
     * Get Data Account Manager with filters
     * FIXED: Witel tidak undefined lagi, status registrasi sudah benar
     */
    public function getDataAM(Request $request)
    {
        $startTime = microtime(true);

        try {
            $query = DB::table('account_managers as am')
                ->leftJoin('witel as w', 'am.witel_id', '=', 'w.id')
                ->leftJoin('users as u', 'u.account_manager_id', '=', 'am.id')
                ->leftJoin('teldas as t', 'am.telda_id', '=', 't.id')
                ->select(
                    'am.id',
                    'am.nama',
                    'am.nik',
                    'am.role',
                    'am.witel_id',
                    'w.nama as witel_nama',  // FIXED: Kirim witel_nama bukan 'witel'
                    'am.telda_id',
                    't.nama as telda_nama',
                    'am.created_at',
                    'am.updated_at',
                    DB::raw('CASE WHEN u.id IS NOT NULL THEN 1 ELSE 0 END as is_registered'),  // FIXED: Return boolean
                    DB::raw('u.id as user_id')
                );

            // Filter by search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('am.nama', 'like', "%{$search}%")
                      ->orWhere('am.nik', 'like', "%{$search}%");
                });
            }

            // Filter by Witel
            if ($request->filled('witel_id') && $request->witel_id != 'all') {
                $query->where('am.witel_id', $request->witel_id);
            }

            // Filter by Division
            if ($request->filled('divisi_id') && $request->divisi_id != 'all') {
                $query->join('account_manager_divisi as amd', 'am.id', '=', 'amd.account_manager_id')
                      ->where('amd.divisi_id', $request->divisi_id);
            }

            // Filter by Role
            if ($request->filled('role') && $request->role != 'all') {
                $query->where('am.role', $request->role);
            }

            $results = $query->orderBy('am.nama', 'asc')
                            ->paginate($request->get('per_page', 25));

            // Transform items - add divisi
            $items = [];
            foreach ($results->items() as $item) {
                // FIXED: Handle null witel dengan default
                $item->witel_nama = $item->witel_nama ?? 'undefined';  // Sesuai requirement
                $item->telda_nama = $item->telda_nama ?? '-';

                // Get divisi for this AM
                $divisi = DB::table('account_manager_divisi as amd')
                    ->join('divisi as d', 'amd.divisi_id', '=', 'd.id')
                    ->where('amd.account_manager_id', $item->id)
                    ->orderBy('amd.is_primary', 'desc')  // Primary divisi first
                    ->select('d.id', 'd.nama', 'd.kode', 'amd.is_primary')
                    ->get();

                $item->divisi = $divisi->toArray();  // Send as array for blade

                // Count related revenues
                $item->revenue_count = DB::table('am_revenues')
                    ->where('account_manager_id', $item->id)
                    ->count();

                $items[] = $item;
            }

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'success' => true,
                'current_page' => $results->currentPage(),
                'data' => $items,
                'first_page_url' => $results->url(1),
                'from' => $results->firstItem(),
                'last_page' => $results->lastPage(),
                'last_page_url' => $results->url($results->lastPage()),
                'next_page_url' => $results->nextPageUrl(),
                'path' => $results->path(),
                'per_page' => $results->perPage(),
                'prev_page_url' => $results->previousPageUrl(),
                'to' => $results->lastItem(),
                'total' => $results->total(),
                '_metadata' => [
                    'query_time_ms' => $queryTime,
                    'timestamp' => now()->toIso8601String(),
                    'filters_applied' => $request->except(['page', 'per_page'])
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get Data AM Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Account Manager: ' . $e->getMessage(),
                'data' => [],
                'total' => 0
            ], 500);
        }
    }

    /**
     * Get single Data AM by ID
     * FIXED: Return data lengkap untuk modal edit
     */
    public function showDataAM($id)
    {
        try {
            $am = DB::table('account_managers as am')
                ->leftJoin('witel as w', 'am.witel_id', '=', 'w.id')
                ->leftJoin('teldas as t', 'am.telda_id', '=', 't.id')
                ->where('am.id', $id)
                ->select(
                    'am.*',
                    'w.nama as witel_nama',
                    't.nama as telda_nama'
                )
                ->first();

            if (!$am) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Account Manager tidak ditemukan'
                ], 404);
            }

            // Get divisi for this AM
            $divisi = DB::table('account_manager_divisi as amd')
                ->join('divisi as d', 'amd.divisi_id', '=', 'd.id')
                ->where('amd.account_manager_id', $id)
                ->orderBy('amd.is_primary', 'desc')
                ->select('d.id', 'd.nama', 'd.kode', 'amd.is_primary')
                ->get();

            $am->divisi = $divisi->toArray();

            return response()->json([
                'success' => true,
                'data' => $am
            ]);

        } catch (\Exception $e) {
            Log::error('Show Data AM Error: ' . $e->getMessage(), [
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Account Manager: ' . $e->getMessage()
            ], 500);
        }
    }

    // =====================================================
    // DATA AM - UPDATE, DELETE
    // =====================================================

    /**
     * Update Data Account Manager
     */
    public function updateDataAM(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:191',
            'nik' => 'required|string|max:50',
            'role' => 'required|in:AM,HOTDA',
            'witel_id' => 'required|exists:witel,id',
            'divisi_ids' => 'required|array|min:1',
            'divisi_ids.*' => 'exists:divisi,id'
        ], [
            'nama.required' => 'Nama harus diisi',
            'nik.required' => 'NIK harus diisi',
            'role.required' => 'Role harus dipilih',
            'witel_id.required' => 'Witel harus dipilih',
            'divisi_ids.required' => 'Minimal 1 divisi harus dipilih',
            'divisi_ids.*.exists' => 'Divisi tidak valid'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $am = DB::table('account_managers')->where('id', $id)->first();

            if (!$am) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Account Manager tidak ditemukan'
                ], 404);
            }

            // Check if NIK already exists for other AM
            $nikExists = DB::table('account_managers')
                ->where('nik', $request->nik)
                ->where('id', '!=', $id)
                ->exists();

            if ($nikExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'NIK sudah digunakan oleh Account Manager lain'
                ], 422);
            }

            $updateData = [
                'nama' => $request->nama,
                'nik' => $request->nik,
                'role' => $request->role,
                'witel_id' => $request->witel_id,
                'updated_at' => now()
            ];

            DB::table('account_managers')->where('id', $id)->update($updateData);

            // Update divisi relationships
            DB::table('account_manager_divisi')->where('account_manager_id', $id)->delete();

            foreach ($request->divisi_ids as $index => $divisiId) {
                DB::table('account_manager_divisi')->insert([
                    'account_manager_id' => $id,
                    'divisi_id' => $divisiId,
                    'is_primary' => ($index === 0) ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            Log::info('Data AM Updated', [
                'id' => $id,
                'user_id' => Auth::id(),
                'data' => $updateData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data Account Manager berhasil diupdate',
                'data' => $updateData
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update Data AM Error: ' . $e->getMessage(), [
                'id' => $id,
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal update Data Account Manager: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change Password for Account Manager User
     */
    public function changePasswordAM(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.required' => 'Password harus diisi',
            'password.min' => 'Password minimal 6 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $user = DB::table('users')->where('account_manager_id', $id)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User account tidak ditemukan untuk Account Manager ini'
                ], 404);
            }

            DB::table('users')
                ->where('account_manager_id', $id)
                ->update([
                    'password' => Hash::make($request->password),
                    'updated_at' => now()
                ]);

            DB::commit();

            Log::info('Password Changed for AM', [
                'am_id' => $id,
                'user_id' => $user->id,
                'changed_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Change Password AM Error: ' . $e->getMessage(), [
                'am_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah password: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Data Account Manager
     * FIXED: Cascade delete AM Revenue terlebih dahulu
     */
    public function deleteDataAM($id)
    {
        DB::beginTransaction();
        try {
            $am = DB::table('account_managers')->where('id', $id)->first();

            if (!$am) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Account Manager tidak ditemukan'
                ], 404);
            }

            // CASCADE DELETE: Hapus semua AM Revenue yang terkait terlebih dahulu
            $deletedRevenues = DB::table('am_revenues')
                ->where('account_manager_id', $id)
                ->delete();

            // Check if has user account - jika ada, hapus juga
            $hasUser = DB::table('users')
                ->where('account_manager_id', $id)
                ->exists();

            if ($hasUser) {
                DB::table('users')
                    ->where('account_manager_id', $id)
                    ->delete();
            }

            // Delete divisi relationships
            DB::table('account_manager_divisi')->where('account_manager_id', $id)->delete();

            // Delete account manager
            DB::table('account_managers')->where('id', $id)->delete();

            DB::commit();

            Log::info('Data AM Deleted (CASCADE)', [
                'id' => $id,
                'deleted_revenues' => $deletedRevenues,
                'deleted_user' => $hasUser,
                'user_id' => Auth::id()
            ]);

            $message = 'Data Account Manager berhasil dihapus';
            if ($deletedRevenues > 0) {
                $message .= " (beserta {$deletedRevenues} Revenue AM terkait)";
            }
            if ($hasUser) {
                $message .= " (beserta akun user terkait)";
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete Data AM Error: ' . $e->getMessage(), [
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Data Account Manager: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Delete Data AM (Selected IDs)
     * FIXED: Cascade delete AM Revenue terlebih dahulu
     */
    public function bulkDeleteDataAM(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:account_managers,id'
        ], [
            'ids.required' => 'Pilih minimal 1 data untuk dihapus',
            'ids.array' => 'Format data tidak valid',
            'ids.*.exists' => 'Data tidak ditemukan'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $ids = $request->ids;
            $deletedCount = 0;
            $deletedRevenuesTotal = 0;
            $deletedUsersTotal = 0;

            foreach ($ids as $id) {
                $am = DB::table('account_managers')->where('id', $id)->first();

                if (!$am) {
                    continue;
                }

                // CASCADE DELETE: Hapus AM Revenue terkait
                $deletedRevenues = DB::table('am_revenues')
                    ->where('account_manager_id', $id)
                    ->delete();

                $deletedRevenuesTotal += $deletedRevenues;

                // Hapus user terkait jika ada
                $hasUser = DB::table('users')
                    ->where('account_manager_id', $id)
                    ->exists();

                if ($hasUser) {
                    DB::table('users')
                        ->where('account_manager_id', $id)
                        ->delete();
                    $deletedUsersTotal++;
                }

                // Delete divisi relationships
                DB::table('account_manager_divisi')
                    ->where('account_manager_id', $id)
                    ->delete();

                // Delete account manager
                DB::table('account_managers')->where('id', $id)->delete();
                $deletedCount++;
            }

            DB::commit();

            Log::info('Bulk Delete Data AM (CASCADE)', [
                'total_selected' => count($ids),
                'deleted_count' => $deletedCount,
                'deleted_revenues' => $deletedRevenuesTotal,
                'deleted_users' => $deletedUsersTotal,
                'user_id' => Auth::id()
            ]);

            $message = "Berhasil menghapus {$deletedCount} Account Manager";
            if ($deletedRevenuesTotal > 0) {
                $message .= " beserta {$deletedRevenuesTotal} Revenue AM terkait";
            }
            if ($deletedUsersTotal > 0) {
                $message .= " dan {$deletedUsersTotal} akun user terkait";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deletedCount,
                'deleted_revenues' => $deletedRevenuesTotal,
                'deleted_users' => $deletedUsersTotal
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk Delete Data AM Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Delete All Data AM (with filters)
     * FIXED: Cascade delete AM Revenue terlebih dahulu
     */
    public function bulkDeleteAllDataAM(Request $request)
    {
        DB::beginTransaction();
        try {
            $query = DB::table('account_managers as am');

            // Apply same filters as getDataAM
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('am.nama', 'like', "%{$search}%")
                      ->orWhere('am.nik', 'like', "%{$search}%");
                });
            }

            if ($request->filled('witel_id') && $request->witel_id != 'all') {
                $query->where('am.witel_id', $request->witel_id);
            }

            if ($request->filled('divisi_id') && $request->divisi_id != 'all') {
                $query->join('account_manager_divisi as amd', 'am.id', '=', 'amd.account_manager_id')
                      ->where('amd.divisi_id', $request->divisi_id);
            }

            if ($request->filled('role') && $request->role != 'all') {
                $query->where('am.role', $request->role);
            }

            $ids = $query->pluck('am.id')->toArray();
            $totalCount = count($ids);

            if ($totalCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data yang sesuai dengan filter'
                ], 404);
            }

            $deletedRevenuesTotal = 0;
            $deletedUsersTotal = 0;

            // CASCADE DELETE untuk setiap AM
            foreach ($ids as $id) {
                // Hapus AM Revenue terkait
                $deletedRevenues = DB::table('am_revenues')
                    ->where('account_manager_id', $id)
                    ->delete();
                $deletedRevenuesTotal += $deletedRevenues;

                // Hapus user terkait
                $hasUser = DB::table('users')
                    ->where('account_manager_id', $id)
                    ->exists();
                if ($hasUser) {
                    DB::table('users')
                        ->where('account_manager_id', $id)
                        ->delete();
                    $deletedUsersTotal++;
                }

                // Hapus divisi relationships
                DB::table('account_manager_divisi')
                    ->where('account_manager_id', $id)
                    ->delete();
            }

            // Delete all account managers
            $deletedCount = DB::table('account_managers')
                ->whereIn('id', $ids)
                ->delete();

            DB::commit();

            $message = "Bulk delete selesai. Berhasil menghapus {$deletedCount} dari {$totalCount} Account Manager";
            if ($deletedRevenuesTotal > 0 || $deletedUsersTotal > 0) {
                $message .= ' beserta';
                $parts = [];
                if ($deletedRevenuesTotal > 0) {
                    $parts[] = "{$deletedRevenuesTotal} Revenue AM";
                }
                if ($deletedUsersTotal > 0) {
                    $parts[] = "{$deletedUsersTotal} akun user";
                }
                $message .= ' ' . implode(' dan ', $parts) . ' terkait';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'total_count' => $totalCount,
                'deleted_count' => $deletedCount,
                'deleted_revenues' => $deletedRevenuesTotal,
                'deleted_users' => $deletedUsersTotal
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk Delete All Data AM Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    // =====================================================
    // DATA CC - READ (GET)
    // =====================================================

    /**
     * Get Data Corporate Customer with filters
     */
    public function getDataCC(Request $request)
    {
        $startTime = microtime(true);

        try {
            $query = DB::table('corporate_customers as cc')
                ->select(
                    'cc.id',
                    'cc.nama',
                    'cc.nipnas',
                    'cc.created_at',
                    'cc.updated_at'
                );

            // Filter by search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('cc.nama', 'like', "%{$search}%")
                      ->orWhere('cc.nipnas', 'like', "%{$search}%");
                });
            }

            $results = $query->orderBy('cc.nama', 'asc')
                            ->paginate($request->get('per_page', 25));

            // Transform items - add revenue count
            $items = [];
            foreach ($results->items() as $item) {
                // Count related revenues
                $item->revenue_count = DB::table('cc_revenues')
                    ->where('corporate_customer_id', $item->id)
                    ->count();

                $items[] = $item;
            }

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'success' => true,
                'current_page' => $results->currentPage(),
                'data' => $items,
                'first_page_url' => $results->url(1),
                'from' => $results->firstItem(),
                'last_page' => $results->lastPage(),
                'last_page_url' => $results->url($results->lastPage()),
                'next_page_url' => $results->nextPageUrl(),
                'path' => $results->path(),
                'per_page' => $results->perPage(),
                'prev_page_url' => $results->previousPageUrl(),
                'to' => $results->lastItem(),
                'total' => $results->total(),
                '_metadata' => [
                    'query_time_ms' => $queryTime,
                    'timestamp' => now()->toIso8601String(),
                    'filters_applied' => $request->except(['page', 'per_page'])
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get Data CC Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Corporate Customer: ' . $e->getMessage(),
                'data' => [],
                'total' => 0
            ], 500);
        }
    }

    /**
     * Get single Data CC by ID
     */
    public function showDataCC($id)
    {
        try {
            $cc = DB::table('corporate_customers')
                ->where('id', $id)
                ->first();

            if (!$cc) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Corporate Customer tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $cc
            ]);

        } catch (\Exception $e) {
            Log::error('Show Data CC Error: ' . $e->getMessage(), [
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Corporate Customer: ' . $e->getMessage()
            ], 500);
        }
    }

    // =====================================================
    // DATA CC - UPDATE, DELETE
    // =====================================================

    /**
     * Update Data Corporate Customer
     */
    public function updateDataCC(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'nipnas' => 'required|string|max:50',
        ], [
            'nama.required' => 'Nama harus diisi',
            'nipnas.required' => 'NIPNAS harus diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $cc = DB::table('corporate_customers')->where('id', $id)->first();

            if (!$cc) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Corporate Customer tidak ditemukan'
                ], 404);
            }

            // Check if NIPNAS already exists for other CC
            $nipnasExists = DB::table('corporate_customers')
                ->where('nipnas', $request->nipnas)
                ->where('id', '!=', $id)
                ->exists();

            if ($nipnasExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'NIPNAS sudah digunakan oleh Corporate Customer lain'
                ], 422);
            }

            $updateData = [
                'nama' => $request->nama,
                'nipnas' => $request->nipnas,
                'updated_at' => now()
            ];

            DB::table('corporate_customers')->where('id', $id)->update($updateData);

            DB::commit();

            Log::info('Data CC Updated', [
                'id' => $id,
                'user_id' => Auth::id(),
                'data' => $updateData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data Corporate Customer berhasil diupdate',
                'data' => $updateData
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update Data CC Error: ' . $e->getMessage(), [
                'id' => $id,
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal update Data Corporate Customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Data Corporate Customer
     * FIXED: Cascade delete CC Revenue dan AM Revenue terlebih dahulu
     */
    public function deleteDataCC($id)
    {
        DB::beginTransaction();
        try {
            $cc = DB::table('corporate_customers')->where('id', $id)->first();

            if (!$cc) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Corporate Customer tidak ditemukan'
                ], 404);
            }

            // CASCADE DELETE: Hapus AM Revenue terlebih dahulu
            $deletedAMRevenues = DB::table('am_revenues')
                ->where('corporate_customer_id', $id)
                ->delete();

            // Hapus CC Revenue
            $deletedCCRevenues = DB::table('cc_revenues')
                ->where('corporate_customer_id', $id)
                ->delete();

            // Delete corporate customer
            DB::table('corporate_customers')->where('id', $id)->delete();

            DB::commit();

            Log::info('Data CC Deleted (CASCADE)', [
                'id' => $id,
                'deleted_cc_revenues' => $deletedCCRevenues,
                'deleted_am_revenues' => $deletedAMRevenues,
                'user_id' => Auth::id()
            ]);

            $message = 'Data Corporate Customer berhasil dihapus';
            $parts = [];
            if ($deletedCCRevenues > 0) {
                $parts[] = "{$deletedCCRevenues} CC Revenue";
            }
            if ($deletedAMRevenues > 0) {
                $parts[] = "{$deletedAMRevenues} AM Revenue";
            }
            if (!empty($parts)) {
                $message .= " (beserta " . implode(' dan ', $parts) . " terkait)";
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete Data CC Error: ' . $e->getMessage(), [
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Data Corporate Customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Delete Data CC (Selected IDs)
     * FIXED: Cascade delete revenues terlebih dahulu
     */
    public function bulkDeleteDataCC(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:corporate_customers,id'
        ], [
            'ids.required' => 'Pilih minimal 1 data untuk dihapus',
            'ids.array' => 'Format data tidak valid',
            'ids.*.exists' => 'Data tidak ditemukan'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $ids = $request->ids;
            $deletedCount = 0;
            $deletedCCRevenuesTotal = 0;
            $deletedAMRevenuesTotal = 0;

            foreach ($ids as $id) {
                $cc = DB::table('corporate_customers')->where('id', $id)->first();

                if (!$cc) {
                    continue;
                }

                // CASCADE DELETE
                $deletedAMRevenues = DB::table('am_revenues')
                    ->where('corporate_customer_id', $id)
                    ->delete();
                $deletedAMRevenuesTotal += $deletedAMRevenues;

                $deletedCCRevenues = DB::table('cc_revenues')
                    ->where('corporate_customer_id', $id)
                    ->delete();
                $deletedCCRevenuesTotal += $deletedCCRevenues;

                // Delete corporate customer
                DB::table('corporate_customers')->where('id', $id)->delete();
                $deletedCount++;
            }

            DB::commit();

            Log::info('Bulk Delete Data CC (CASCADE)', [
                'total_selected' => count($ids),
                'deleted_count' => $deletedCount,
                'deleted_cc_revenues' => $deletedCCRevenuesTotal,
                'deleted_am_revenues' => $deletedAMRevenuesTotal,
                'user_id' => Auth::id()
            ]);

            $message = "Berhasil menghapus {$deletedCount} Corporate Customer";
            $parts = [];
            if ($deletedCCRevenuesTotal > 0) {
                $parts[] = "{$deletedCCRevenuesTotal} CC Revenue";
            }
            if ($deletedAMRevenuesTotal > 0) {
                $parts[] = "{$deletedAMRevenuesTotal} AM Revenue";
            }
            if (!empty($parts)) {
                $message .= " beserta " . implode(' dan ', $parts) . " terkait";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deletedCount,
                'deleted_cc_revenues' => $deletedCCRevenuesTotal,
                'deleted_am_revenues' => $deletedAMRevenuesTotal
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk Delete Data CC Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Delete All Data CC (with filters)
     * FIXED: Cascade delete revenues terlebih dahulu
     */
    public function bulkDeleteAllDataCC(Request $request)
    {
        DB::beginTransaction();
        try {
            $query = DB::table('corporate_customers as cc');

            // Apply same filters as getDataCC
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('cc.nama', 'like', "%{$search}%")
                      ->orWhere('cc.nipnas', 'like', "%{$search}%");
                });
            }

            $ids = $query->pluck('cc.id')->toArray();
            $totalCount = count($ids);

            if ($totalCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data yang sesuai dengan filter'
                ], 404);
            }

            $deletedCCRevenuesTotal = 0;
            $deletedAMRevenuesTotal = 0;

            // CASCADE DELETE untuk setiap CC
            foreach ($ids as $id) {
                // Hapus AM Revenue terkait
                $deletedAMRevenues = DB::table('am_revenues')
                    ->where('corporate_customer_id', $id)
                    ->delete();
                $deletedAMRevenuesTotal += $deletedAMRevenues;

                // Hapus CC Revenue terkait
                $deletedCCRevenues = DB::table('cc_revenues')
                    ->where('corporate_customer_id', $id)
                    ->delete();
                $deletedCCRevenuesTotal += $deletedCCRevenues;
            }

            // Delete all corporate customers
            $deletedCount = DB::table('corporate_customers')
                ->whereIn('id', $ids)
                ->delete();

            DB::commit();

            $message = "Bulk delete selesai. Berhasil menghapus {$deletedCount} dari {$totalCount} data";
            if ($deletedCCRevenuesTotal > 0 || $deletedAMRevenuesTotal > 0) {
                $message .= ' beserta';
                $parts = [];
                if ($deletedCCRevenuesTotal > 0) {
                    $parts[] = "{$deletedCCRevenuesTotal} CC Revenue";
                }
                if ($deletedAMRevenuesTotal > 0) {
                    $parts[] = "{$deletedAMRevenuesTotal} AM Revenue";
                }
                $message .= ' ' . implode(' dan ', $parts) . ' terkait';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'total_count' => $totalCount,
                'deleted_count' => $deletedCount,
                'deleted_cc_revenues' => $deletedCCRevenuesTotal,
                'deleted_am_revenues' => $deletedAMRevenuesTotal
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk Delete All Data CC Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    // =====================================================
    // HELPER METHODS
    // =====================================================

    /**
     * Get filter options (Witel, Division, Segment)
     */
    public function getFilterOptions()
    {
        try {
            $witels = DB::table('witel')
                ->select('id', 'nama')
                ->orderBy('nama')
                ->get();

            $divisions = DB::table('divisi')
                ->select('id', 'nama', 'kode')
                ->orderBy('nama')
                ->get();

            $segments = DB::table('segments')
                ->select('id', 'lsegment_ho', 'ssegment_ho', 'divisi_id')
                ->orderBy('lsegment_ho')
                ->get();

            $accountManagers = DB::table('account_managers')
                ->select('id', 'nama', 'nik')
                ->orderBy('nama')
                ->get();

            $teldas = DB::table('teldas')
                ->select('id', 'nama', 'witel_id', 'divisi_id')
                ->orderBy('nama')
                ->get();

            return response()->json([
                'success' => true,
                'witels' => $witels,
                'divisions' => $divisions,
                'segments' => $segments,
                'account_managers' => $accountManagers,
                'teldas' => $teldas,  // ADDED
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            Log::error('Get Filter Options Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data filter: ' . $e->getMessage(),
                'witels' => [],
                'divisions' => [],
                'segments' => [],
                'account_managers' => [],
                'teldas' => []
            ], 500);
        }
    }

    /**
     * Helper function to determine achievement color
     */
    private function getAchievementColor($achievement)
    {
        if ($achievement >= 100) {
            return 'success'; // Hijau
        } elseif ($achievement >= 80) {
            return 'warning'; // Oranye
        } else {
            return 'danger'; // Merah
        }
    }

    /**
     * Helper function to get achievement badge class
     */
    private function getAchievementBadge($achievement)
    {
        if ($achievement >= 100) {
            return 'badge-success';
        } elseif ($achievement >= 90) {
            return 'badge-info';
        } elseif ($achievement >= 80) {
            return 'badge-warning';
        } else {
            return 'badge-danger';
        }
    }

    /**
     * Helper function to recalculate AM revenues
     */
    private function recalculateAMRevenues($ccId, $divisiId, $bulan, $tahun)
    {
        try {
            // Get updated CC Revenue
            $ccRevenue = DB::table('cc_revenues')
                ->where('corporate_customer_id', $ccId)
                ->where('divisi_id', $divisiId)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->first();

            if (!$ccRevenue) {
                return;
            }

            // Get all related AM revenues
            $amRevenues = DB::table('am_revenues')
                ->where('corporate_customer_id', $ccId)
                ->where('divisi_id', $divisiId)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get();

            foreach ($amRevenues as $amRevenue) {
                $targetRevenue = $ccRevenue->target_revenue * $amRevenue->proporsi;
                $realRevenue = $ccRevenue->real_revenue * $amRevenue->proporsi;
                $achievementRate = $targetRevenue > 0 ? ($realRevenue / $targetRevenue) * 100 : 0;

                DB::table('am_revenues')
                    ->where('id', $amRevenue->id)
                    ->update([
                        'target_revenue' => $targetRevenue,
                        'real_revenue' => $realRevenue,
                        'achievement_rate' => round($achievementRate, 2),
                        'updated_at' => now()
                    ]);
            }

            Log::info('Recalculated AM revenues', [
                'cc_id' => $ccId,
                'divisi_id' => $divisiId,
                'periode' => "{$tahun}-{$bulan}",
                'am_count' => count($amRevenues)
            ]);

        } catch (\Exception $e) {
            Log::error('Recalculate AM Revenues Error: ' . $e->getMessage(), [
                'cc_id' => $ccId,
                'divisi_id' => $divisiId,
                'periode' => "{$tahun}-{$bulan}"
            ]);
        }
    }
}