<?php

namespace App\Http\Controllers\Overview;

use App\Http\Controllers\Controller;
use App\Services\RevenueCalculationService;
use App\Services\RankingService;
use App\Services\PerformanceAnalysisService;
use App\Models\AccountManager;
use App\Models\Divisi;
use App\Models\Witel;
use App\Models\AmRevenue;
use App\Models\CcRevenue;
use App\Models\Segment;
use App\Models\CorporateCustomer;
use App\Exports\AdminDashboardExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
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
    }

    /**
     * Main dashboard entry point
     */
    public function index(Request $request)
    {
        Log::info("Accessing DashboardController");

        $user = Auth::user();

        try {
            switch ($user->role) {
                case 'admin':
                    Log::info("DashboardController - role is Admin so naturally");
                    return $this->handleAdminDashboard($request);
                case 'account_manager':
                    Log::info("DashboardController - role is AM so naturally");
                    return $this->handleAmDashboard($request);
                case 'witel':
                    Log::info("DashboardController - role is Witel so naturally");
                    return $this->handleWitelDashboard($request, $user->witel_id);
                default:
                    Log::info("DashboardController - ermm don't know what role so, bye");
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect()->route('login')
                        ->with('error', 'Role tidak memiliki akses ke dashboard.');
            }
        } catch (\Exception $e) {
            Log::error('Dashboard access error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan sistem.');
        }
    }

    private function handleAmDashboard(Request $request)
    {
        $amController = app(AmDashboardController::class);
        return $amController->index($request);
    }

    private function handleWitelDashboard(Request $request, $witel_id)
    {
        $witelController = app(WitelDashboardController::class);
        return $witelController->show($witel_id, $request);
    }

    /**
     * Handle Admin Dashboard
     */
    private function handleAdminDashboard(Request $request)
    {
        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            // 1. CARD GROUP
            $cardData = $this->revenueService->getTotalRevenueDataWithDateRange(
                null,
                $filters['divisi_id'],
                $dateRange['start'],
                $dateRange['end'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );
            $cardData['period_text'] = $this->generatePeriodText($filters['period_type'], $dateRange);

            // 2. PERFORMANCE SECTION
            $performanceData = [
                'account_manager' => $this->getTopAccountManagersFixed(null, 20, $dateRange, $filters),
                'corporate_customer' => $this->getTopCorporateCustomersFixed(null, 20, $dateRange, $filters)
            ];
            $this->addClickableUrls($performanceData);

            // 3. CHARTS
            $currentYear = date('Y');
            $monthlyRevenue = $this->revenueService->getMonthlyRevenue(
                $currentYear,
                null,
                $filters['divisi_id'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );
            $performanceDistribution = $this->performanceService->getPerformanceDistribution(
                $currentYear,
                null,
                $filters['divisi_id'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );
            $monthlyChart = $this->generateMonthlyChart($monthlyRevenue, $currentYear);
            $performanceChart = $this->generatePerformanceChart($performanceDistribution);

            // 4. REVENUE TABLE
            $revenueTable = $this->revenueService->getRevenueTableDataWithDateRange(
                $dateRange['start'],
                $dateRange['end'],
                null,
                $filters['divisi_id'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );

            $filterOptions = $this->getFilterOptionsForAdmin();

            return view('dashboard', compact(
                'cardData',
                'performanceData',
                'monthlyRevenue',
                'monthlyChart',
                'performanceChart',
                'revenueTable',
                'filterOptions',
                'filters'
            ));
        } catch (\Exception $e) {
            Log::error('Admin dashboard failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return view('dashboard', [
                'error' => 'Gagal memuat dashboard.',
                'filters' => $this->getDefaultFilters(),
                'filterOptions' => $this->getFilterOptionsForAdmin(),
                'cardData' => $this->getEmptyCardData(),
                'performanceData' => $this->getEmptyPerformanceData(),
                'revenueTable' => collect([]),
                'monthlyChart' => $this->generateEmptyChart('line', 'Data tidak tersedia'),
                'performanceChart' => $this->generateEmptyChart('bar', 'Data tidak tersedia')
            ]);
        }
    }

    /**
     * FIXED: Get top Corporate Customers - Sesuai SQL Structure
     */
    private function getTopCorporateCustomersFixed($witelId = null, $limit = 20, $dateRange, $filters)
    {
        try {
            $query = DB::table('cc_revenues');

            // Date filtering
            if ($dateRange['start'] && $dateRange['end']) {
                $startYear = Carbon::parse($dateRange['start'])->year;
                $startMonth = Carbon::parse($dateRange['start'])->month;
                $endMonth = Carbon::parse($dateRange['end'])->month;

                $query->where('tahun', $startYear)
                    ->whereBetween('bulan', [$startMonth, $endMonth]);
            } else {
                $query->where('tahun', $this->getCurrentDataYear());
            }

            // Witel filtering
            if ($witelId) {
                $query->where(function ($q) use ($witelId) {
                    $q->where('witel_ho_id', $witelId)
                        ->orWhere('witel_bill_id', $witelId);
                });
            }

            // Divisi filtering
            if (isset($filters['divisi_id']) && $filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
                $query->where('divisi_id', $filters['divisi_id']);
            }

            // Revenue source filtering
            if (isset($filters['revenue_source']) && $filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
                $query->where('revenue_source', $filters['revenue_source']);
            }

            // Tipe revenue filtering
            if (isset($filters['tipe_revenue']) && $filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
                $query->where('tipe_revenue', $filters['tipe_revenue']);
            }

            // Get aggregated data - FIXED
            $revenueData = $query
                ->select('corporate_customer_id')
                ->selectRaw('SUM(real_revenue) as total_revenue')
                ->selectRaw('SUM(target_revenue) as total_target')
                ->whereNotNull('corporate_customer_id')
                ->groupBy('corporate_customer_id')
                ->orderByDesc('total_revenue')
                ->limit($limit)
                ->get();

            $results = collect([]);

            foreach ($revenueData as $revenue) {
                $customer = DB::table('corporate_customers')
                    ->where('id', $revenue->corporate_customer_id)
                    ->first();

                if (!$customer) continue;

                // Get latest record
                $latestRecord = DB::table('cc_revenues')
                    ->where('corporate_customer_id', $revenue->corporate_customer_id)
                    ->orderByDesc('tahun')
                    ->orderByDesc('bulan')
                    ->first();

                // Get divisi - FIXED: gunakan nama tabel 'divisi' sesuai SQL
                $divisiName = 'N/A';
                if ($latestRecord && $latestRecord->divisi_id) {
                    $divisi = DB::table('divisi')->where('id', $latestRecord->divisi_id)->first();
                    $divisiName = $divisi ? $divisi->nama : 'N/A';
                }

                // Get segment - FIXED: gunakan nama tabel 'segments' sesuai SQL
                $segmentName = 'N/A';
                if ($latestRecord && $latestRecord->segment_id) {
                    $segment = DB::table('segments')->where('id', $latestRecord->segment_id)->first();
                    $segmentName = $segment ? $segment->lsegment_ho : 'N/A';
                }

                $achievementRate = $revenue->total_target > 0
                    ? ($revenue->total_revenue / $revenue->total_target) * 100
                    : 0;

                $results->push((object) [
                    'id' => $revenue->corporate_customer_id,
                    'nama' => $customer->nama,
                    'nipnas' => $customer->nipnas,
                    'divisi_nama' => $divisiName,
                    'segment_nama' => $segmentName,
                    'total_revenue' => floatval($revenue->total_revenue),
                    'total_target' => floatval($revenue->total_target),
                    'achievement_rate' => round($achievementRate, 2),
                    'achievement_color' => $this->getAchievementColor($achievementRate)
                ]);
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Failed to get top corporate customers', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return collect([]);
        }
    }

    /**
     * Get top Account Managers
     */
    private function getTopAccountManagersFixed($witelId = null, $limit = 20, $dateRange, $filters)
    {
        $query = AmRevenue::query();

        if ($dateRange['start'] && $dateRange['end']) {
            $startYear = Carbon::parse($dateRange['start'])->year;
            $startMonth = Carbon::parse($dateRange['start'])->month;
            $endMonth = Carbon::parse($dateRange['end'])->month;

            $query->where('tahun', $startYear)
                ->whereBetween('bulan', [$startMonth, $endMonth]);
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        if ($witelId) {
            $query->whereHas('accountManager', function ($q) use ($witelId) {
                $q->where('witel_id', $witelId);
            });
        }

        if (isset($filters['divisi_id']) && $filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
            $query->where('divisi_id', $filters['divisi_id']);
        }

        $revenueData = $query->selectRaw('
                account_manager_id,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target,
                CASE
                    WHEN SUM(target_revenue) > 0
                    THEN (SUM(real_revenue) / SUM(target_revenue)) * 100
                    ELSE 0
                END as achievement_rate
            ')
            ->groupBy('account_manager_id')
            ->get()
            ->keyBy('account_manager_id');

        $amQuery = AccountManager::where('role', 'AM')->with(['witel', 'divisis']);

        if ($witelId) {
            $amQuery->where('witel_id', $witelId);
        }

        if (isset($filters['divisi_id']) && $filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
            $amQuery->whereHas('divisis', function ($q) use ($filters) {
                $q->where('divisi.id', $filters['divisi_id']);
            });
        }

        $results = $amQuery->get()->map(function ($am) use ($revenueData) {
            $revenue = $revenueData->get($am->id);
            $totalRevenue = $revenue ? $revenue->total_revenue : 0;
            $totalTarget = $revenue ? $revenue->total_target : 0;
            $achievement = $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0;

            $am->total_revenue = $totalRevenue;
            $am->total_target = $totalTarget;
            $am->achievement_rate = round($achievement, 2);
            $am->achievement_color = $this->getAchievementColor($achievement);
            $am->divisi_list = $am->divisis && $am->divisis->count() > 0
                ? $am->divisis->pluck('nama')->join(', ')
                : 'N/A';

            return $am;
        })
            ->filter(function ($am) {
                return $am->total_revenue > 0 || $am->total_target > 0;
            })
            ->sortByDesc('total_revenue')
            ->take($limit)
            ->values();

        return $results;
    }

    /**
     * AJAX Tab Data
     */
    public function getTabData(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);
            $tab = $request->get('tab');

            $data = $this->getAdminTabDataFixed($tab, $dateRange, $filters);

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => is_countable($data) ? count($data) : 0
            ]);
        } catch (\Exception $e) {
            Log::error('Tab data failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal memuat data'], 500);
        }
    }

    private function getAdminTabDataFixed($tab, $dateRange, $filters)
    {
        switch ($tab) {
            case 'account_manager':
                return $this->getTopAccountManagersFixed(null, 20, $dateRange, $filters);
            case 'witel':
                return $this->getTopWitelsFixed(20, $dateRange, $filters);
            case 'segment':
                return $this->getTopSegmentsFixed(20, $dateRange, $filters);
            case 'corporate_customer':
                return $this->getTopCorporateCustomersFixed(null, 20, $dateRange, $filters);
            default:
                throw new \InvalidArgumentException('Invalid tab');
        }
    }

    /**
     * Get top Witels - FIXED
     */
    private function getTopWitelsFixed($limit = 20, $dateRange, $filters)
    {
        try {
            $query = DB::table('cc_revenues');

            if ($dateRange['start'] && $dateRange['end']) {
                $startYear = Carbon::parse($dateRange['start'])->year;
                $startMonth = Carbon::parse($dateRange['start'])->month;
                $endMonth = Carbon::parse($dateRange['end'])->month;

                $query->where('tahun', $startYear)
                    ->whereBetween('bulan', [$startMonth, $endMonth]);
            } else {
                $query->where('tahun', $this->getCurrentDataYear());
            }

            if (isset($filters['divisi_id']) && $filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
                $query->where('divisi_id', $filters['divisi_id']);
            }

            if (isset($filters['revenue_source']) && $filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
                $query->where('revenue_source', $filters['revenue_source']);
            }

            if (isset($filters['tipe_revenue']) && $filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
                $query->where('tipe_revenue', $filters['tipe_revenue']);
            }

            $revenueData = $query
                ->selectRaw('
                    CASE
                        WHEN witel_ho_id IS NOT NULL THEN witel_ho_id
                        ELSE witel_bill_id
                    END as witel_id,
                    COUNT(DISTINCT corporate_customer_id) as total_customers,
                    SUM(real_revenue) as total_revenue,
                    SUM(target_revenue) as total_target
                ')
                ->whereNotNull(DB::raw('CASE WHEN witel_ho_id IS NOT NULL THEN witel_ho_id ELSE witel_bill_id END'))
                ->groupBy(DB::raw('CASE WHEN witel_ho_id IS NOT NULL THEN witel_ho_id ELSE witel_bill_id END'))
                ->orderByDesc('total_revenue')
                ->limit($limit)
                ->get();

            $results = collect([]);

            foreach ($revenueData as $revenue) {
                // FIXED: Gunakan nama tabel 'witel' sesuai SQL
                $witel = DB::table('witel')->where('id', $revenue->witel_id)->first();
                if (!$witel) continue;

                $achievementRate = $revenue->total_target > 0
                    ? ($revenue->total_revenue / $revenue->total_target) * 100
                    : 0;

                $results->push((object) [
                    'id' => $witel->id,
                    'nama' => $witel->nama,
                    'total_customers' => intval($revenue->total_customers),
                    'total_revenue' => floatval($revenue->total_revenue),
                    'total_target' => floatval($revenue->total_target),
                    'achievement_rate' => round($achievementRate, 2),
                    'achievement_color' => $this->getAchievementColor($achievementRate)
                ]);
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Failed to get top witels', ['error' => $e->getMessage()]);
            return collect([]);
        }
    }

    /**
     * Get top Segments - FIXED
     */
    private function getTopSegmentsFixed($limit = 20, $dateRange, $filters)
    {
        try {
            $query = DB::table('cc_revenues');

            if ($dateRange['start'] && $dateRange['end']) {
                $startYear = Carbon::parse($dateRange['start'])->year;
                $startMonth = Carbon::parse($dateRange['start'])->month;
                $endMonth = Carbon::parse($dateRange['end'])->month;

                $query->where('tahun', $startYear)
                    ->whereBetween('bulan', [$startMonth, $endMonth]);
            } else {
                $query->where('tahun', $this->getCurrentDataYear());
            }

            if (isset($filters['divisi_id']) && $filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
                $query->where('divisi_id', $filters['divisi_id']);
            }

            if (isset($filters['revenue_source']) && $filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
                $query->where('revenue_source', $filters['revenue_source']);
            }

            if (isset($filters['tipe_revenue']) && $filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
                $query->where('tipe_revenue', $filters['tipe_revenue']);
            }

            $revenueData = $query
                ->select('segment_id', 'divisi_id')
                ->selectRaw('COUNT(DISTINCT corporate_customer_id) as total_customers')
                ->selectRaw('SUM(real_revenue) as total_revenue')
                ->selectRaw('SUM(target_revenue) as total_target')
                ->whereNotNull('segment_id')
                ->groupBy('segment_id', 'divisi_id')
                ->orderByDesc('total_revenue')
                ->limit($limit)
                ->get();

            $results = collect([]);

            foreach ($revenueData as $revenue) {
                // FIXED: Gunakan tabel dan kolom sesuai SQL
                $segment = DB::table('segments')->where('id', $revenue->segment_id)->first();
                $divisi = DB::table('divisi')->where('id', $revenue->divisi_id)->first();

                if (!$segment) continue;

                $achievementRate = $revenue->total_target > 0
                    ? ($revenue->total_revenue / $revenue->total_target) * 100
                    : 0;

                $results->push((object) [
                    'id' => $segment->id,
                    'lsegment_ho' => $segment->lsegment_ho,
                    'nama' => $segment->lsegment_ho,
                    'divisi_nama' => $divisi ? $divisi->nama : 'N/A',
                    'total_customers' => intval($revenue->total_customers),
                    'total_revenue' => floatval($revenue->total_revenue),
                    'total_target' => floatval($revenue->total_target),
                    'achievement_rate' => round($achievementRate, 2),
                    'achievement_color' => $this->getAchievementColor($achievementRate)
                ]);
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Failed to get top segments', ['error' => $e->getMessage()]);
            return collect([]);
        }
    }

    /**
     * Export
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            abort(403);
        }

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);
            $exportData = $this->prepareExportData($dateRange, $filters);
            $filename = $this->generateExportFilename($filters);

            return Excel::download(
                new AdminDashboardExport($exportData, $dateRange, $filters),
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Export failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Export gagal');
        }
    }

    private function prepareExportData($dateRange, $filters)
    {
        return [
            'revenue_table' => $this->revenueService->getRevenueTableDataWithDateRange(
                $dateRange['start'],
                $dateRange['end'],
                null,
                $filters['divisi_id'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            ),
            'performance' => [
                'account_managers' => $this->getTopAccountManagersFixed(null, 200, $dateRange, $filters),
                'witels' => $this->getTopWitelsFixed(200, $dateRange, $filters),
                'segments' => $this->getTopSegmentsFixed(200, $dateRange, $filters),
                'corporate_customers' => $this->getTopCorporateCustomersFixed(null, 200, $dateRange, $filters)
            ],
            'summary' => $this->revenueService->getTotalRevenueDataWithDateRange(
                null,
                $filters['divisi_id'],
                $dateRange['start'],
                $dateRange['end'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            )
        ];
    }

    private function generateExportFilename($filters)
    {
        $periodText = strtolower($filters['period_type']);
        $timestamp = date('Y-m-d_H-i-s');
        return "dashboard_export_{$periodText}_{$timestamp}.xlsx";
    }

    /**
     * Detail Pages
     */
    public function showAccountManager($id)
    {
        try {
            $accountManager = AccountManager::with(['witel', 'divisis'])->findOrFail($id);
            $user = Auth::user();

            if ($user->role === 'account_manager' && $user->account_manager_id !== $id) {
                abort(403);
            }

            $currentYear = date('Y');
            $performanceData = $this->performanceService->getAMPerformanceSummary($id, $currentYear);
            $monthlyChart = $this->performanceService->getAMMonthlyChart($id, $currentYear);
            $customerPerformance = $this->performanceService->getAMCustomerPerformance($id, $currentYear);

            return view('am.detailAM', compact(
                'accountManager',
                'performanceData',
                'monthlyChart',
                'customerPerformance'
            ));
        } catch (\Exception $e) {
            Log::error('AM detail failed', ['error' => $e->getMessage()]);
            Log::info("DashboardController - redirect to dashboard about to begin");
            return redirect()->route('dashboard')->with('error', 'Gagal memuat detail AM');
        }
    }

    public function showWitel($id)
    {
        try {
            $witel = Witel::findOrFail($id);
            $user = Auth::user();

            if ($user->role === 'witel_support' && $user->witel_id !== $id) {
                abort(403);
            }

            $currentYear = date('Y');
            $witelData = $this->revenueService->getTotalRevenueData($id, null, $currentYear);
            $topAMs = $this->revenueService->getTopAccountManagers($id, 20, $currentYear);
            $categoryDistribution = $this->rankingService->getCategoryDistribution($id, $currentYear);

            return view('witel.detailWitel', compact('witel', 'witelData', 'topAMs', 'categoryDistribution'));
        } catch (\Exception $e) {
            Log::error('Witel detail failed', ['error' => $e->getMessage()]);
            Log::info("DashboardController - redirect to dashboard about to begin");
            return redirect()->route('dashboard')->with('error', 'Gagal memuat detail Witel');
        }
    }

    public function showCorporateCustomer($id)
    {
        try {
            $ccController = app(CcDashboardController::class);
            return $ccController->show($id, request());
        } catch (\Exception $e) {
            Log::error('CC detail failed', ['error' => $e->getMessage()]);
            Log::info("DashboardController - redirect to dashboard about to begin");
            return redirect()->route('dashboard')->with('error', 'Gagal memuat detail CC');
        }
    }

    public function showSegment($id)
    {
        try {
            $segment = Segment::with('divisi')->findOrFail($id);
            $currentYear = date('Y');

            $segmentData = CcRevenue::where('segment_id', $id)
                ->where('tahun', $currentYear)
                ->selectRaw('
                    COUNT(DISTINCT corporate_customer_id) as total_customers,
                    SUM(real_revenue) as total_revenue,
                    SUM(target_revenue) as total_target
                ')
                ->first();

            $topCustomers = CcRevenue::where('segment_id', $id)
                ->where('tahun', $currentYear)
                ->with('corporateCustomer')
                ->selectRaw('
                    corporate_customer_id,
                    SUM(real_revenue) as total_revenue,
                    SUM(target_revenue) as total_target
                ')
                ->groupBy('corporate_customer_id')
                ->orderByDesc('total_revenue')
                ->limit(10)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'segment' => [
                        'id' => $segment->id,
                        'nama' => $segment->lsegment_ho,
                        'divisi' => $segment->divisi->nama ?? 'N/A'
                    ],
                    'performance' => [
                        'total_customers' => $segmentData->total_customers ?? 0,
                        'total_revenue' => $segmentData->total_revenue ?? 0,
                        'total_target' => $segmentData->total_target ?? 0,
                        'achievement_rate' => $segmentData->total_target > 0
                            ? round(($segmentData->total_revenue / $segmentData->total_target) * 100, 2)
                            : 0
                    ],
                    'top_customers' => $topCustomers
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Segment detail failed', ['error' => $e->getMessage()]);
            Log::info("DashboardController - redirect to dashboard about to begin");
            return redirect()->route('dashboard')->with('error', 'Gagal memuat detail Segment');
        }
    }

    /**
     * HELPER METHODS
     */
    private function extractFiltersWithYtdMtd(Request $request)
    {
        return [
            'period_type' => $request->get('period_type', 'YTD'),
            'divisi_id' => $request->get('divisi_id'),
            'revenue_source' => $request->get('revenue_source', 'all'),
            'tipe_revenue' => $request->get('tipe_revenue', 'all'),
            'sort_indicator' => $request->get('sort_indicator', 'total_revenue'),
            'active_tab' => $request->get('tab', 'account_manager')
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

    private function getFilterOptionsForAdmin()
    {
        return [
            'period_types' => [
                'YTD' => 'Year to Date',
                'MTD' => 'Month to Date'
            ],
            'divisis' => Divisi::select('id', 'nama', 'kode')->orderBy('nama')->get(),
            'sort_indicators' => [
                'total_revenue' => 'Total Revenue Tertinggi',
                'achievement_rate' => 'Achievement Rate Tertinggi',
                'semua' => 'Semua (Revenue + Achievement)'
            ],
            'tipe_revenues' => [
                'all' => 'Semua Tipe',
                'REGULER' => 'Revenue Reguler',
                'NGTMA' => 'Revenue NGTMA'
            ],
            'revenue_sources' => [
                'all' => 'Semua Source',
                'HO' => 'HO Revenue',
                'BILL' => 'BILL Revenue'
            ]
        ];
    }

    private function getDefaultFilters()
    {
        return [
            'period_type' => 'YTD',
            'divisi_id' => null,
            'sort_indicator' => 'total_revenue',
            'tipe_revenue' => 'all',
            'revenue_source' => 'all',
            'active_tab' => 'account_manager'
        ];
    }

    private function getEmptyCardData()
    {
        return [
            'total_revenue' => 0,
            'total_target' => 0,
            'achievement_rate' => 0,
            'achievement_color' => 'secondary',
            'period_text' => 'Tidak ada data'
        ];
    }

    private function getEmptyPerformanceData()
    {
        return [
            'account_manager' => collect([]),
            'witel' => collect([]),
            'segment' => collect([]),
            'corporate_customer' => collect([])
        ];
    }

    private function addClickableUrls(&$performanceData)
    {
        if (isset($performanceData['account_manager'])) {
            $performanceData['account_manager']->each(function ($am) {
                $am->detail_url = route('account-manager.show', $am->id);
            });
        }

        if (isset($performanceData['witel'])) {
            $performanceData['witel']->each(function ($witel) {
                $witel->detail_url = route('witel.show', $witel->id);
            });
        }

        if (isset($performanceData['segment'])) {
            $performanceData['segment']->each(function ($segment) {
                $segment->detail_url = route('segment.show', $segment->id);
            });
        }

        if (isset($performanceData['corporate_customer'])) {
            $performanceData['corporate_customer']->each(function ($customer) {
                $customer->detail_url = route('corporate-customer.show', $customer->id);
            });
        }
    }

    /**
     * CHART GENERATION
     */
    private function generateMonthlyChart($monthlyRevenue, $tahun)
    {
        if (!$monthlyRevenue || $monthlyRevenue->isEmpty()) {
            return $this->generateEmptyChart('line', 'Data revenue bulanan tidak tersedia');
        }

        $labels = [];
        $realRevenue = [];
        $targetRevenue = [];

        foreach ($monthlyRevenue as $data) {
            $labels[] = $data['month_name'] ?? 'Unknown';
            $realRevenue[] = round(($data['real_revenue'] ?? 0) / 1000000, 2);
            $targetRevenue[] = round(($data['target_revenue'] ?? 0) / 1000000, 2);
        }

        $chartId = 'monthlyRevenueChart_' . uniqid();

        return "
        <canvas id='{$chartId}' style='height: 350px; width: 100%;'></canvas>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('{$chartId}');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: " . json_encode($labels) . ",
                        datasets: [
                            {
                                label: 'Real Revenue (Juta Rp)',
                                data: " . json_encode($realRevenue) . ",
                                borderColor: '#dc3545',
                                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Target Revenue (Juta Rp)',
                                data: " . json_encode($targetRevenue) . ",
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Perkembangan Revenue Bulanan ({$tahun})',
                                font: { size: 16 }
                            },
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Revenue (Juta Rp)'
                                }
                            }
                        }
                    }
                });
            }
        });
        </script>
        ";
    }

    private function generatePerformanceChart($performanceData)
    {
        if (!$performanceData || $performanceData->isEmpty()) {
            return $this->generateEmptyChart('bar', 'Data distribusi performance tidak tersedia');
        }

        $labels = [];
        $excellent = [];
        $good = [];
        $poor = [];

        foreach ($performanceData as $month => $data) {
            $labels[] = date('M', mktime(0, 0, 0, $month, 1));
            $excellent[] = $data['excellent'] ?? 0;
            $good[] = $data['good'] ?? 0;
            $poor[] = $data['poor'] ?? 0;
        }

        $chartId = 'performanceChart_' . uniqid();

        return "
        <canvas id='{$chartId}' style='height: 350px; width: 100%;'></canvas>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('{$chartId}');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: " . json_encode($labels) . ",
                        datasets: [
                            {
                                label: 'Hijau (≥100%)',
                                data: " . json_encode($excellent) . ",
                                backgroundColor: '#198754'
                            },
                            {
                                label: 'Oranye (80-99%)',
                                data: " . json_encode($good) . ",
                                backgroundColor: '#fd7e14'
                            },
                            {
                                label: 'Merah (<80%)',
                                data: " . json_encode($poor) . ",
                                backgroundColor: '#dc3545'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Distribusi Pencapaian Target AM per Bulan',
                                font: { size: 16 }
                            },
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            x: { stacked: true },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Jumlah Account Manager'
                                }
                            }
                        }
                    }
                });
            }
        });
        </script>
        ";
    }

    private function generateEmptyChart($type, $message)
    {
        $icon = $type === 'line' ? 'fa-chart-line' : 'fa-chart-bar';

        return "
        <div class='chart-placeholder d-flex align-items-center justify-content-center' style='height: 350px; background: #f8f9fa; border-radius: 8px; border: 2px dashed #dee2e6;'>
            <div class='text-center'>
                <i class='fas {$icon}' style='font-size: 3rem; color: #6c757d; margin-bottom: 16px;'></i>
                <h5 style='color: #495057; margin-bottom: 8px;'>Chart Tidak Tersedia</h5>
                <p style='color: #6c757d; margin: 0; font-size: 0.9rem;'>{$message}</p>
            </div>
        </div>
        ";
    }

    /**
     * Get achievement color
     */
    private function getAchievementColor($achievementRate)
    {
        if ($achievementRate >= 100) {
            return 'success';
        } elseif ($achievementRate >= 80) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    /**
     * Get current data year
     */
    private function getCurrentDataYear()
    {
        static $currentYear = null;

        if ($currentYear === null) {
            $currentYear = CcRevenue::max('tahun') ?? date('Y');
        }

        return $currentYear;
    }

    /**
     * API METHODS
     */
    public function getChartData(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $currentYear = date('Y');

            $monthlyRevenue = $this->revenueService->getMonthlyRevenue(
                $currentYear,
                null,
                $filters['divisi_id'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );

            $performanceDistribution = $this->performanceService->getPerformanceDistribution(
                $currentYear,
                null,
                $filters['divisi_id'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );

            return response()->json([
                'success' => true,
                'monthly_data' => $monthlyRevenue,
                'performance_data' => $performanceDistribution
            ]);
        } catch (\Exception $e) {
            Log::error('Chart data failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load chart data'], 500);
        }
    }

    public function getRevenueTable(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            $revenueTable = $this->revenueService->getRevenueTableDataWithDateRange(
                $dateRange['start'],
                $dateRange['end'],
                null,
                $filters['divisi_id'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );

            return response()->json([
                'success' => true,
                'data' => $revenueTable
            ]);
        } catch (\Exception $e) {
            Log::error('Revenue table failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load revenue table'], 500);
        }
    }

    public function getSummary(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            $summary = $this->revenueService->getTotalRevenueDataWithDateRange(
                null,
                $filters['divisi_id'],
                $dateRange['start'],
                $dateRange['end'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );

            $summary['period_text'] = $this->generatePeriodText($filters['period_type'], $dateRange);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            Log::error('Summary failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load summary'], 500);
        }
    }

    public function getPerformanceInsights(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $insights = [
                'total_insights' => 0,
                'insights' => [],
                'recommendations' => []
            ];

            return response()->json([
                'success' => true,
                'data' => $insights
            ]);
        } catch (\Exception $e) {
            Log::error('Performance insights failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load insights'], 500);
        }
    }

    /**
     * AM DELEGATION METHODS
     */
    public function getAmPerformance(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'account_manager') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $amController = app(AmDashboardController::class);
            $accountManager = AccountManager::where('id', $user->account_manager_id)->first();

            if (!$accountManager) {
                return response()->json(['error' => 'Account Manager not found'], 404);
            }

            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            $performanceSummary = $amController->getAmPerformanceSummary(
                $accountManager->id,
                $dateRange,
                $filters
            );

            return response()->json([
                'success' => true,
                'data' => $performanceSummary
            ]);
        } catch (\Exception $e) {
            Log::error('AM performance failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load AM performance'], 500);
        }
    }

    public function getAmCustomers(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'account_manager') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $amController = app(AmDashboardController::class);
            $accountManager = AccountManager::where('id', $user->account_manager_id)->first();

            if (!$accountManager) {
                return response()->json(['error' => 'Account Manager not found'], 404);
            }

            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            $corporateCustomers = $amController->getAmCorporateCustomers(
                $accountManager->id,
                $dateRange,
                $filters
            );

            return response()->json([
                'success' => true,
                'data' => $corporateCustomers
            ]);
        } catch (\Exception $e) {
            Log::error('AM customers failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load AM customers'], 500);
        }
    }

    public function exportAm(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'account_manager') {
            abort(403, 'Unauthorized export access');
        }

        try {
            $amController = app(AmDashboardController::class);
            return $amController->export($request);
        } catch (\Exception $e) {
            Log::error('AM export failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Export AM gagal');
        }
    }

    /**
     * Extract filters for non-admin users
     */
    private function extractFilters(Request $request)
    {
        return [
            'period_type' => $request->get('period_type', 'YTD'),
            'revenue_source' => $request->get('revenue_source', 'all'),
            'tipe_revenue' => $request->get('tipe_revenue', 'all')
        ];
    }
}
