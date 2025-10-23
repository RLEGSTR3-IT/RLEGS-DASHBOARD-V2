<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Get Revenue CC data with filters
     */
    public function getRevenueCC(Request $request)
    {
        $query = DB::table('cc_revenues as cr')
            ->join('corporate_customers as cc', 'cr.corporate_customer_id', '=', 'cc.id')
            ->join('divisi as d', 'cr.divisi_id', '=', 'd.id')
            ->join('segments as s', 'cr.segment_id', '=', 's.id')
            ->select(
                'cr.id',
                'cc.nama as nama_cc',
                'd.nama as divisi',
                's.lsegment_ho as segment',
                'cr.target_revenue',
                'cr.real_revenue',
                'cr.revenue_source',
                'cr.tipe_revenue',
                'cr.bulan',
                'cr.tahun',
                'd.kode'
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
                        ->paginate($request->get('per_page', 25));

        // Transform items manual
        $items = [];
        foreach ($results->items() as $item) {
            // Tentukan revenue type berdasarkan revenue_source
            $item->revenue_type = $item->revenue_source == 'HO'
                ? 'Revenue Sold (Witel HO)'
                : 'Revenue Bill (Witel Bill)';

            // Format bulan untuk display
            $item->bulan_display = Carbon::createFromDate($item->tahun, $item->bulan, 1)
                ->locale('id')
                ->translatedFormat('M Y');

            $items[] = $item;
        }

        return response()->json([
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
        ]);
    }

    /**
     * Get Revenue AM data with filters
     */
    public function getRevenueAM(Request $request)
    {
        $query = DB::table('am_revenues as ar')
            ->join('account_managers as am', 'ar.account_manager_id', '=', 'am.id')
            ->join('corporate_customers as cc', 'ar.corporate_customer_id', '=', 'cc.id')
            ->leftJoin('divisi as d', 'ar.divisi_id', '=', 'd.id')
            ->leftJoin('teldas as t', 'ar.telda_id', '=', 't.id')
            ->select(
                'ar.id',
                'am.nama as nama_am',
                'd.nama as divisi',
                'cc.nama as corporate_customer',
                'ar.target_revenue',
                'ar.real_revenue',
                'ar.achievement_rate',
                'ar.bulan',
                'ar.tahun',
                'am.role',
                't.nama as nama_telda'
            );

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('am.nama', 'like', "%{$search}%")
                  ->orWhere('am.nik', 'like', "%{$search}%")
                  ->orWhere('cc.nama', 'like', "%{$search}%");
            });
        }

        // Filter by Witel
        if ($request->filled('witel_id') && $request->witel_id != 'all') {
            $query->where('ar.witel_id', $request->witel_id);
        }

        // Filter by Division
        if ($request->filled('divisi_id') && $request->divisi_id != 'all') {
            $query->where('ar.divisi_id', $request->divisi_id);
        }

        // Filter by Segment - perlu join ke cc_revenues untuk dapat segment
        if ($request->filled('segment_id') && $request->segment_id != 'all') {
            $query->join('cc_revenues as ccr', function($join) use ($request) {
                $join->on('ccr.corporate_customer_id', '=', 'ar.corporate_customer_id')
                     ->on('ccr.bulan', '=', 'ar.bulan')
                     ->on('ccr.tahun', '=', 'ar.tahun');
            })->where('ccr.segment_id', $request->segment_id);
        }

        // Filter by Period (Month-Year)
        if ($request->filled('periode')) {
            $periode = Carbon::parse($request->periode);
            $query->where('ar.bulan', $periode->month)
                  ->where('ar.tahun', $periode->year);
        }

        // Filter by AM/HOTDA
        if ($request->filled('role')) {
            $query->where('am.role', $request->role);
        }

        $results = $query->orderBy('ar.tahun', 'desc')
                        ->orderBy('ar.bulan', 'desc')
                        ->paginate($request->get('per_page', 25));

        // Transform items manual
        $items = [];
        foreach ($results->items() as $item) {
            // Format bulan untuk display
            $item->bulan_display = Carbon::createFromDate($item->tahun, $item->bulan, 1)
                ->locale('id')
                ->translatedFormat('M Y');

            // Format achievement dengan warna
            $item->achievement_color = $this->getAchievementColor($item->achievement_rate);

            $items[] = $item;
        }

        return response()->json([
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
        ]);
    }

    /**
     * Get Data AM (Account Managers general data) with filters
     */
    public function getDataAM(Request $request)
    {
        $query = DB::table('account_managers as am')
            ->join('witel as w', 'am.witel_id', '=', 'w.id')
            ->leftJoin('teldas as t', 'am.telda_id', '=', 't.id')
            ->leftJoin('users as u', 'am.id', '=', 'u.account_manager_id')
            ->leftJoin('account_manager_divisi as amd', function($join) {
                $join->on('am.id', '=', 'amd.account_manager_id')
                     ->where('amd.is_primary', '=', 1);
            })
            ->leftJoin('divisi as d', 'amd.divisi_id', '=', 'd.id')
            ->select(
                'am.id',
                'am.nama',
                'am.nik',
                'd.nama as divisi',
                'w.nama as witel',
                'am.role',
                't.nama as nama_telda',
                DB::raw('CASE
                    WHEN u.id IS NOT NULL THEN "Sudah Terdaftar"
                    ELSE "Belum Terdaftar"
                END as status_registrasi')
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
            $query->where('amd.divisi_id', $request->divisi_id);
        }

        // Filter by AM/HOTDA
        if ($request->filled('role') && $request->role != 'all') {
            $query->where('am.role', $request->role);
        }

        $results = $query->orderBy('am.nama', 'asc')
                        ->paginate($request->get('per_page', 25));

        return response()->json([
            'current_page' => $results->currentPage(),
            'data' => $results->items(),
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
        ]);
    }

    /**
     * Get Data CC (Corporate Customers general data) with filters
     */
    public function getDataCC(Request $request)
    {
        $query = DB::table('corporate_customers as cc')
            ->select(
                'cc.id',
                'cc.nama',
                'cc.nipnas'
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

        return response()->json([
            'current_page' => $results->currentPage(),
            'data' => $results->items(),
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
        ]);
    }

    /**
     * Get filter options (Witel, Division, Segment)
     */
    public function getFilterOptions()
    {
        $witels = DB::table('witel')
            ->select('id', 'nama')
            ->orderBy('nama')
            ->get();

        $divisions = DB::table('divisi')
            ->select('id', 'nama', 'kode')
            ->orderBy('nama')
            ->get();

        $segments = DB::table('segments')
            ->select('id', 'lsegment_ho', 'ssegment_ho')
            ->orderBy('lsegment_ho')
            ->get();

        return response()->json([
            'witels' => $witels,
            'divisions' => $divisions,
            'segments' => $segments
        ]);
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
}