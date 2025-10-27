<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CCWitelPerformController extends Controller
{
    public function index()
    {
        $months = Carbon::now()->startOfYear()->toPeriod(Carbon::now()->endOfYear(), '1 month');

        Log::info("CCW Controller - Accessing CC W Page");

        return view('cc_witel.cc-witel-performance', [
            'months' => $months
        ]);
    }

    public function fetchTrendData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'source' => 'required|in:reguler,ngtma',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $revenueData = DB::table('cc_revenues')
                ->select('tahun', 'bulan', 'divisi_id', 'real_revenue', 'target_revenue') // in the future might want to fetch all
                ->where('tipe_revenue', strtoupper($request->source))
                ->where(DB::raw("CONCAT(tahun, '-', LPAD(bulan, 2, '0'), '-01')"), '>=', $request->start_date)
                ->where(DB::raw("CONCAT(tahun, '-', LPAD(bulan, 2, '0'), '-01')"), '<=', $request->end_date)
                ->get();

            Log::info("CCW Controller", ['fetched' => $revenueData]);

            return response()->json($revenueData);
        } catch (\Exception $e) {
            Log::info("CCW Controller", ['error' => $e]);
            return response()->json(['error' => 'A server error occurred while fetching data.'], 500);
        }
    }

    private function applyDateFilters($query, $mode, $year, $month)
    {
        switch ($mode) {
            case 'ytd':
                // YTD: From Jan 1 to the end of the current month of the current year
                $query->where('tahun', $year)->where('bulan', '<=', $month);
                break;
            case 'monthly':
                // Monthly: For a specific year and month
                $query->where('tahun', $year)->where('bulan', $month);
                break;
            case 'annual':
                // Annual: For all 12 months of a specific year
                $query->where('tahun', $year);
                break;
        }
    }

    public function fetchWitelPerformanceData(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'mode' => 'required|in:ytd,monthly,annual',
            'year' => 'required|integer|min:2020',
            'month' => 'required|integer|min:1|max:12',
            'source' => 'required|in:reguler,ngtma',
        ]);
        if ($validator->fails()) return response()->json(['error' => $validator->errors()], 422);

        $dbSource = $request->source;
        $mode = $request->mode;

        $now = Carbon::now();
        $year = $request->input('year', $now->year);
        $month = $request->input('month', $now->month);

        if ($mode === 'ytd') {
            $year = $now->year;
            $month = $now->month;
        }

        // Get all Witels
        $witels = DB::table('witel')->select('id', 'nama')->get()->keyBy('id');

        // Get Annual Target Revenue for all Witels
        $targetsSubquery = DB::table('cc_revenues')
            ->select(DB::raw('CASE WHEN divisi_id IN (1, 2) THEN witel_ho_id WHEN divisi_id = 3 THEN witel_bill_id END as witel_id'), DB::raw('SUM(target_revenue) as targetM'))
            ->where('tipe_revenue', $dbSource)
            ->groupBy('witel_id');
        $this->applyDateFilters($targetsSubquery, $mode, $year, $month);
        //$targets = DB::query()->fromSub($targetsSubquery, 't')->whereNotNull('witel_id')->pluck('targetM', 'witel_id');
        $targets = $targetsSubquery->whereNotNull('witel_id')->pluck('targetM', 'witel_id');

        // Get YTD Real Revenue for all Witels
        $revenueSubquery = DB::table('cc_revenues')
            ->select(DB::raw('CASE WHEN divisi_id IN (1, 2) THEN witel_ho_id WHEN divisi_id = 3 THEN witel_bill_id END as witel_id'), DB::raw('SUM(real_revenue) as revenueM'))
            ->where('tipe_revenue', $dbSource)
            ->groupBy('witel_id');
        $this->applyDateFilters($revenueSubquery, $mode, $year, $month);
        //$revenues = DB::query()->fromSub($revenueSubquery, 'r')->whereNotNull('witel_id')->pluck('revenueM', 'witel_id');
        $revenues = $revenueSubquery->whereNotNull('witel_id')->pluck('revenueM', 'witel_id');

        // Get ALL Customers for ALL Witels for the selected MONTH
        //$allCustomers = DB::table('cc_revenues')
        //    ->select(
        //        'nama_cc',
        //        DB::raw('SUM(real_revenue) as total_revenue'),
        //        DB::raw('CASE
        //                    WHEN divisi_id IN (1, 2) THEN witel_ho_id
        //                    WHEN divisi_id = 3 THEN witel_bill_id
        //                END as witel_id')
        //    )
        //    ->where('tipe_revenue', $dbSource)
        //    ->where('tahun', $year)
        //    ->where('bulan', $month)
        //    ->whereNotNull(DB::raw('CASE WHEN divisi_id IN (1, 2) THEN witel_ho_id WHEN divisi_id = 3 THEN witel_bill_id END'))
        //    ->groupBy('witel_id', 'nama_cc')
        //    ->orderBy('witel_id')
        //    ->orderByDesc('total_revenue')
        //    ->get();

        $customersQuery = DB::table('cc_revenues')
            ->select('nama_cc', DB::raw('SUM(real_revenue) as total_revenue'), DB::raw('CASE WHEN division_id IN (1, 2) THEN witel_ho_id WHEN division_id = 3 THEN witel_bill_id END as witel_id'))
            ->where('tipe_revenue', $dbSource)
            ->groupBy('witel_id', 'nama_cc')
            ->orderBy('witel_id')
            ->orderByDesc('total_revenue');
        $this->applyDateFilters($customersQuery, $mode, $year, $month);
        $allCustomers = $customersQuery->whereNotNull(DB::raw('CASE WHEN division_id IN (1, 2) THEN witel_ho_id WHEN division_id = 3 THEN witel_bill_id END'))->get();

        // Process customers into a map for easy lookup
        $customerMap = [];
        foreach ($allCustomers as $row) {
            $customerMap[$row->witel_id][] = $row;
        }

        // Build, Calculate, and Sort the final combined data
        $leaderboard = [];
        foreach ($witels as $id => $witel) {
            $revenue = $revenues->get($id) ?? 0;
            $target = $targets->get($id) ?? 0;
            $achievement = ($target > 0) ? ($revenue / $target) * 100 : null;

            $leaderboard[] = [
                'id' => $id,
                'name' => $witel->nama,
                'revenueM' => $revenue,
                'targetM' => $target,
                'achievement' => $achievement,
                'customers' => $customerMap[$id] ?? [],
            ];
        }

        // Sort the final leaderboard
        usort($leaderboard, function ($a, $b) {
            $aAch = $a['achievement'] ?? -1;
            $bAch = $b['achievement'] ?? -1;
            if ($aAch !== $bAch) return $bAch <=> $aAch;
            return $b['revenueM'] <=> $a['revenueM'];
        });

        Log::info("CCW Controller", ['returned' => $leaderboard]);

        return response()->json($leaderboard);
    }
}
