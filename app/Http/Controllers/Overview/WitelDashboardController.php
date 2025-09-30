<?php

namespace App\Http\Controllers\Overview;

use App\Http\Controllers\Controller;
use App\Services\RevenueCalculationService;
use App\Services\RankingService;
use App\Services\PerformanceAnalysisService;
use App\Models\AccountManager;
use App\Models\Witel;
use App\Models\Divisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WitelDashboardController extends Controller
{
    protected $revenueService;
    protected $rankingService;
    protected $performanceService;

    public function __construct(
        RevenueCalculationService $revenueService,
        RankingService $rankingService,
        PerformanceAnalysisService $performanceService
    ) {
        $this->revenueService = $revenueService;
        $this->rankingService = $rankingService;
        $this->performanceService = $performanceService;

        // Middleware untuk witel_support only
        $this->middleware(function ($request, $next) {
            if (Auth::user()->role !== 'witel_support') {
                abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
            }
            return $next($request);
        });
    }

    /**
     * Display Witel dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        try {
            // Get Witel dari user
            $witelUser = AccountManager::where('user_id', $user->id)->with('witel')->first();

            if (!$witelUser || !$witelUser->witel) {
                return redirect()->route('dashboard')
                    ->with('error', 'Data Witel tidak ditemukan.');
            }

            $witel = $witelUser->witel;
            $filters = $this->extractFilters($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            // 1. Witel Summary
            $witelSummary = $this->getWitelSummary($witel->id, $dateRange, $filters);

            // 2. Top AMs di Witel ini
            $topAMs = $this->revenueService->getTopAccountManagersWithDateRange(
                $witel->id, 10, $dateRange['start'], $dateRange['end'],
                $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
            );

            // 3. Top Corporate Customers
            $topCustomers = $this->revenueService->getTopCorporateCustomersWithDateRange(
                $witel->id, 10, $dateRange['start'], $dateRange['end'],
                $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
            );

            // 4. Category Distribution
            $categoryDistribution = $this->rankingService->getCategoryDistributionWithDateRange(
                $witel->id, $dateRange['start'], $dateRange['end'],
                $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
            );

            $filterOptions = $this->getFilterOptions();

            return view('witel.dashboard', compact(
                'witel',
                'witelSummary',
                'topAMs',
                'topCustomers',
                'categoryDistribution',
                'filters',
                'filterOptions'
            ));

        } catch (\Exception $e) {
            Log::error('Witel dashboard error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return view('witel.dashboard', [
                'error' => 'Gagal memuat dashboard Witel. Silakan refresh halaman.',
                'witel' => null,
                'witelSummary' => $this->getEmptyWitelSummary(),
                'topAMs' => collect([]),
                'topCustomers' => collect([]),
                'categoryDistribution' => $this->getEmptyCategoryDistribution(),
                'filters' => $this->getDefaultFilters(),
                'filterOptions' => $this->getFilterOptions()
            ]);
        }
    }

    /**
     * Get Witel performance summary
     */
    private function getWitelSummary($witelId, $dateRange, $filters)
    {
        $witelData = $this->revenueService->getTotalRevenueDataWithDateRange(
            $witelId, $filters['divisi_id'], $dateRange['start'], $dateRange['end'],
            $filters['revenue_source'], $filters['tipe_revenue']
        );

        $witelData['period_text'] = $this->generatePeriodText($filters['period_type'], $dateRange);

        return $witelData;
    }

    /**
     * Export Witel data
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        try {
            $witelUser = AccountManager::where('user_id', $user->id)->first();

            if (!$witelUser) {
                return redirect()->route('dashboard')
                    ->with('error', 'Data Witel tidak ditemukan.');
            }

            // Simple export - return JSON for now
            $filters = $this->extractFilters($request);

            return response()->json([
                'message' => 'Witel export functionality will be implemented',
                'witel_id' => $witelUser->witel_id,
                'filters' => $filters
            ]);

        } catch (\Exception $e) {
            Log::error('Witel export failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Export gagal: ' . $e->getMessage());
        }
    }

    /**
     * Helper methods
     */
    private function extractFilters(Request $request)
    {
        return [
            'period_type' => $request->get('period_type', 'YTD'),
            'divisi_id' => $request->get('divisi_id'),
            'revenue_source' => $request->get('revenue_source', 'all'),
            'tipe_revenue' => $request->get('tipe_revenue', 'all')
        ];
    }

    private function calculateDateRange($periodType)
    {
        $now = Carbon::now();

        if ($periodType === 'MTD') {
            return [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfDay(),
                'type' => 'MTD'
            ];
        } else {
            return [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfDay(),
                'type' => 'YTD'
            ];
        }
    }

    private function generatePeriodText($periodType, $dateRange)
    {
        $startDate = $dateRange['start']->format('d M');
        $endDate = $dateRange['end']->format('d M Y');
        return "dari {$startDate} - {$endDate}";
    }

    private function getFilterOptions()
    {
        return [
            'period_types' => [
                'YTD' => 'Year to Date',
                'MTD' => 'Month to Date'
            ],
            'divisis' => Divisi::select('id', 'nama', 'kode')->orderBy('nama')->get(),
            'revenue_sources' => [
                'all' => 'Semua Source',
                'HO' => 'HO Revenue',
                'BILL' => 'BILL Revenue'
            ],
            'tipe_revenues' => [
                'all' => 'Semua Tipe',
                'REGULER' => 'Revenue Reguler',
                'NGTMA' => 'Revenue NGTMA'
            ]
        ];
    }

    private function getDefaultFilters()
    {
        return [
            'period_type' => 'YTD',
            'divisi_id' => null,
            'revenue_source' => 'all',
            'tipe_revenue' => 'all'
        ];
    }

    private function getEmptyWitelSummary()
    {
        return [
            'total_revenue' => 0,
            'total_target' => 0,
            'achievement_rate' => 0,
            'achievement_color' => 'secondary',
            'period_text' => 'Tidak ada data'
        ];
    }

    private function getEmptyCategoryDistribution()
    {
        return [
            'Excellent' => 0,
            'Good' => 0,
            'Poor' => 0,
            'total' => 0,
            'excellent_percentage' => 0,
            'good_percentage' => 0,
            'poor_percentage' => 0
        ];
    }
}