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
     * Main dashboard entry point with conditional rendering
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        try {
            // CONDITIONAL RENDERING LOGIC BERDASARKAN REQUIREMENT
            switch ($user->role) {
                case 'admin':
                    return $this->handleAdminDashboard($request);

                case 'account_manager':
                    return $this->handleAmDashboard($request);

                case 'witel_support':
                    return $this->handleWitelDashboard($request);

                default:
                    Log::warning('Unauthorized dashboard access attempt', [
                        'user_id' => $user->id,
                        'role' => $user->role,
                        'ip' => $request->ip(),
                        'timestamp' => now()
                    ]);

                    return redirect()->route('login')
                        ->with('error', 'Role Anda tidak dikenali atau tidak memiliki akses ke dashboard.')
                        ->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Dashboard access critical error', [
                'user_id' => $user->id,
                'role' => $user->role,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', '_token'])
            ]);

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi administrator.')
                ->withInput();
        }
    }

    /**
     * Handle Account Manager Dashboard
     */
    private function handleAmDashboard(Request $request)
    {
        $amController = new AmDashboardController($this->revenueService);
        return $amController->index($request);
    }

    /**
     * Handle Witel Dashboard
     */
    private function handleWitelDashboard(Request $request)
    {
        $witelController = new WitelDashboardController(
            $this->revenueService,
            $this->rankingService,
            $this->performanceService
        );
        return $witelController->index($request);
    }

    /**
     * Handle Admin Dashboard
     */
    private function handleAdminDashboard(Request $request)
    {
        try {
            // EXTRACT FILTERS WITH YTD/MTD SUPPORT
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            // 1. CARD GROUP SECTION
            $cardData = $this->revenueService->getTotalRevenueDataWithDateRange(
                null, // Admin sees all witels
                $filters['divisi_id'],
                $dateRange['start'],
                $dateRange['end'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );

            // Add dynamic period text untuk stats card
            $cardData['period_text'] = $this->generatePeriodText($filters['period_type'], $dateRange);

            // 2. PERFORMANCE SECTION
            $performanceData = [
                'account_manager' => $this->getTopAccountManagersFixed(
                    null, 20, $dateRange, $filters
                ),
                'corporate_customer' => $this->getTopCorporateCustomersFixed(
                    null, 20, $dateRange, $filters
                )
            ];

            // Add clickable URLs for navigation
            $this->addClickableUrls($performanceData);

            // 3. VISUALISASI PENDAPATAN BULANAN
            $currentYear = date('Y');
            $monthlyRevenue = $this->revenueService->getMonthlyRevenue(
                $currentYear, null, $filters['divisi_id'],
                $filters['revenue_source'], $filters['tipe_revenue']
            );

            $performanceDistribution = $this->performanceService->getPerformanceDistribution(
                $currentYear, null, $filters['divisi_id'],
                $filters['revenue_source'], $filters['tipe_revenue']
            );

            // Generate Charts dengan Chart.js
            $monthlyChart = $this->generateMonthlyChart($monthlyRevenue, $currentYear);
            $performanceChart = $this->generatePerformanceChart($performanceDistribution);

            // 4. TABEL TOTAL PENDAPATAN BULANAN
            $revenueTable = $this->revenueService->getRevenueTableDataWithDateRange(
                $dateRange['start'], $dateRange['end'], null, $filters['divisi_id'],
                $filters['revenue_source'], $filters['tipe_revenue']
            );

            // Filter options untuk dropdown UI
            $filterOptions = $this->getFilterOptionsForAdmin();

            // Log successful access untuk monitoring
            Log::info('Admin dashboard loaded successfully', [
                'user_id' => Auth::id(),
                'filters' => $filters,
                'data_summary' => [
                    'total_revenue' => $cardData['total_revenue'] ?? 0,
                    'performance_count' => [
                        'am' => $performanceData['account_manager']->count(),
                        'corporate' => $performanceData['corporate_customer']->count()
                    ]
                ]
            ]);

            // RETURN dashboard.blade.php (admin view)
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
            Log::error('Admin dashboard rendering failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Graceful degradation dengan empty data
            return view('dashboard', [
                'error' => 'Gagal memuat data dashboard admin. Silakan refresh halaman atau hubungi administrator.',
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
     * Get top Corporate Customers - proper data loading and calculation
     */
    private function getTopCorporateCustomersFixed($witelId = null, $limit = 20, $dateRange, $filters)
    {
        $query = CcRevenue::query();

        // Date range filtering
        if ($dateRange['start'] && $dateRange['end']) {
            $startYear = Carbon::parse($dateRange['start'])->year;
            $endYear = Carbon::parse($dateRange['end'])->year;
            $startMonth = Carbon::parse($dateRange['start'])->month;
            $endMonth = Carbon::parse($dateRange['end'])->month;

            if ($startYear === $endYear) {
                $query->where('tahun', $startYear)
                      ->whereBetween('bulan', [$startMonth, $endMonth]);
            }
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        if ($witelId) {
            $query->where(function($q) use ($witelId) {
                $q->where('witel_ho_id', $witelId)
                  ->orWhere('witel_bill_id', $witelId);
            });
        }

        if ($filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
            $query->where('divisi_id', $filters['divisi_id']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        // Get aggregated data with proper calculation
        $revenueData = $query->selectRaw('
                corporate_customer_id,
                divisi_id,
                segment_id,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->groupBy('corporate_customer_id', 'divisi_id', 'segment_id')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

        // Load related data and calculate achievement
        $results = collect([]);

        foreach ($revenueData as $revenue) {
            $customer = CorporateCustomer::find($revenue->corporate_customer_id);
            $divisi = Divisi::find($revenue->divisi_id);
            $segment = Segment::find($revenue->segment_id);

            $achievementRate = $revenue->total_target > 0
                ? ($revenue->total_revenue / $revenue->total_target) * 100
                : 0;

            $results->push((object) [
                'id' => $revenue->corporate_customer_id,
                'nama' => $customer->nama ?? 'Unknown',
                'nipnas' => $customer->nipnas ?? 'Unknown',
                'divisi_nama' => $divisi->nama ?? 'Unknown',
                'segment_nama' => $segment->lsegment_ho ?? 'Unknown',
                'total_revenue' => floatval($revenue->total_revenue),
                'total_target' => floatval($revenue->total_target),
                'achievement_rate' => round($achievementRate, 2),
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ]);
        }

        return $results;
    }

    /**
     * Get top Account Managers with proper ranking and divisi display
     */
    private function getTopAccountManagersFixed($witelId = null, $limit = 20, $dateRange, $filters)
    {
        // Get AM revenue data dengan proper aggregation
        $query = AmRevenue::query();

        // Date range filtering
        if ($dateRange['start'] && $dateRange['end']) {
            $startYear = Carbon::parse($dateRange['start'])->year;
            $endYear = Carbon::parse($dateRange['end'])->year;
            $startMonth = Carbon::parse($dateRange['start'])->month;
            $endMonth = Carbon::parse($dateRange['end'])->month;

            if ($startYear === $endYear) {
                $query->where('tahun', $startYear)
                      ->whereBetween('bulan', [$startMonth, $endMonth]);
            }
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        // Witel filtering
        if ($witelId) {
            $query->whereHas('accountManager', function($q) use ($witelId) {
                $q->where('witel_id', $witelId);
            });
        }

        // Divisi filtering
        if ($filters['divisi_id']) {
            $query->where(function($q) use ($filters) {
                $q->where('divisi_id', $filters['divisi_id'])
                  ->orWhereHas('accountManager.divisis', function($subq) use ($filters) {
                      $subq->where('divisi.id', $filters['divisi_id']);
                  });
            });
        }

        // Revenue source dan tipe revenue filtering
        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all' ||
            $filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->whereExists(function($subquery) use ($dateRange, $filters) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id');

                // Date filtering for cc_revenues
                if ($dateRange['start'] && $dateRange['end']) {
                    $startYear = Carbon::parse($dateRange['start'])->year;
                    $startMonth = Carbon::parse($dateRange['start'])->month;
                    $endMonth = Carbon::parse($dateRange['end'])->month;

                    $subquery->where('tahun', $startYear)
                             ->whereBetween('bulan', [$startMonth, $endMonth]);
                } else {
                    $subquery->where('tahun', $this->getCurrentDataYear());
                }

                if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
                    $subquery->where('revenue_source', $filters['revenue_source']);
                }

                if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
                    $subquery->where('tipe_revenue', $filters['tipe_revenue']);
                }
            });
        }

        // Get aggregated revenue per AM
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

        // Get Account Manager data dengan eager loading yang benar
        $amQuery = AccountManager::where('role', 'AM')
            ->with(['witel', 'divisis']);

        if ($witelId) {
            $amQuery->where('witel_id', $witelId);
        }

        // Filter berdasarkan divisi jika diperlukan
        if ($filters['divisi_id']) {
            $amQuery->whereHas('divisis', function($q) use ($filters) {
                $q->where('divisi.id', $filters['divisi_id']);
            });
        }

        $results = $amQuery->get()->map(function($am) use ($revenueData) {
            $revenue = $revenueData->get($am->id);
            $totalRevenue = $revenue->total_revenue ?? 0;
            $totalTarget = $revenue->total_target ?? 0;
            $achievement = $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0;

            $am->total_revenue = $totalRevenue;
            $am->total_target = $totalTarget;
            $am->achievement_rate = round($achievement, 2);
            $am->achievement_color = $this->getAchievementColor($achievement);

            // Proper divisi display
            $am->divisi_list = $am->divisis && $am->divisis->count() > 0
                ? $am->divisis->pluck('nama')->join(', ')
                : 'N/A';

            return $am;
        })
        ->sort(function($a, $b) use ($filters) {
            $sortBy = $filters['sort_indicator'] ?? 'total_revenue';

            if ($sortBy === 'achievement_rate') {
                return $b->achievement_rate <=> $a->achievement_rate;
            } else {
                return $b->total_revenue <=> $a->total_revenue;
            }
        })
        ->take($limit)
        ->values();

        return $results;
    }

    /**
     * AJAX TAB SWITCHING
     */
    public function getTabData(Request $request)
    {
        $user = Auth::user();

        // Authorization check
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        $tab = $request->get('tab');

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            $data = $this->getAdminTabDataFixed($tab, $dateRange, $filters);

            return response()->json([
                'success' => true,
                'data' => $data,
                'tab' => $tab,
                'count' => is_countable($data) ? count($data) : 0,
                'period_text' => $this->generatePeriodText($filters['period_type'], $dateRange)
            ]);

        } catch (\Exception $e) {
            Log::error('Admin tab data loading failed', [
                'tab' => $tab,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Gagal memuat data tab',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tab data with proper structure and calculations
     */
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
                throw new \InvalidArgumentException('Invalid tab type: ' . $tab);
        }
    }

    /**
     * Get top Witels with proper calculation
     */
    private function getTopWitelsFixed($limit = 20, $dateRange, $filters)
    {
        $query = CcRevenue::query();

        // Date range filtering
        if ($dateRange['start'] && $dateRange['end']) {
            $startYear = Carbon::parse($dateRange['start'])->year;
            $endYear = Carbon::parse($dateRange['end'])->year;
            $startMonth = Carbon::parse($dateRange['start'])->month;
            $endMonth = Carbon::parse($dateRange['end'])->month;

            if ($startYear === $endYear) {
                $query->where('tahun', $startYear)
                      ->whereBetween('bulan', [$startMonth, $endMonth]);
            }
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        if ($filters['divisi_id']) {
            $query->where('divisi_id', $filters['divisi_id']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        // Get aggregated data by witel
        $revenueData = $query->selectRaw('
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
            $witel = Witel::find($revenue->witel_id);
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
    }

    /**
     * Get top Segments with proper calculation
     */
    private function getTopSegmentsFixed($limit = 20, $dateRange, $filters)
    {
        $query = CcRevenue::query();

        // Date range filtering
        if ($dateRange['start'] && $dateRange['end']) {
            $startYear = Carbon::parse($dateRange['start'])->year;
            $endYear = Carbon::parse($dateRange['end'])->year;
            $startMonth = Carbon::parse($dateRange['start'])->month;
            $endMonth = Carbon::parse($dateRange['end'])->month;

            if ($startYear === $endYear) {
                $query->where('tahun', $startYear)
                      ->whereBetween('bulan', [$startMonth, $endMonth]);
            }
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        if ($filters['divisi_id']) {
            $query->where('divisi_id', $filters['divisi_id']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        // Get aggregated data by segment
        $revenueData = $query->selectRaw('
                segment_id,
                divisi_id,
                COUNT(DISTINCT corporate_customer_id) as total_customers,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->whereNotNull('segment_id')
            ->groupBy('segment_id', 'divisi_id')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

        $results = collect([]);

        foreach ($revenueData as $revenue) {
            $segment = Segment::find($revenue->segment_id);
            $divisi = Divisi::find($revenue->divisi_id);

            if (!$segment) continue;

            $achievementRate = $revenue->total_target > 0
                ? ($revenue->total_revenue / $revenue->total_target) * 100
                : 0;

            $results->push((object) [
                'id' => $segment->id,
                'lsegment_ho' => $segment->lsegment_ho ?? 'Unknown',
                'nama' => $segment->lsegment_ho ?? 'Unknown',
                'divisi_nama' => $divisi->nama ?? 'Unknown',
                'divisi' => (object) ['nama' => $divisi->nama ?? 'Unknown'],
                'total_customers' => intval($revenue->total_customers),
                'total_revenue' => floatval($revenue->total_revenue),
                'total_target' => floatval($revenue->total_target),
                'achievement_rate' => round($achievementRate, 2),
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ]);
        }

        return $results;
    }

    /**
     * Export functionality
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        // Authorization check
        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized export access');
        }

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            // Collect ALL data untuk comprehensive export
            $exportData = $this->prepareExportData($dateRange, $filters);

            // Generate filename dengan timestamp dan filter info
            $filename = $this->generateExportFilename($filters);

            // Log export activity
            Log::info('Admin dashboard export initiated', [
                'user_id' => Auth::id(),
                'filters' => $filters,
                'filename' => $filename
            ]);

            // Return comprehensive Excel dengan multiple sheets
            return Excel::download(
                new AdminDashboardExport($exportData, $dateRange, $filters),
                $filename
            );

        } catch (\Exception $e) {
            Log::error('Admin dashboard export failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Export gagal: ' . $e->getMessage());
        }
    }

    private function prepareExportData($dateRange, $filters)
    {
        return [
            'revenue_table' => $this->revenueService->getRevenueTableDataWithDateRange(
                $dateRange['start'], $dateRange['end'], null, $filters['divisi_id'],
                $filters['revenue_source'], $filters['tipe_revenue']
            ),
            'performance' => [
                'account_managers' => $this->getTopAccountManagersFixed(
                    null, 200, $dateRange, $filters
                ),
                'witels' => $this->getTopWitelsFixed(
                    200, $dateRange, $filters
                ),
                'segments' => $this->getTopSegmentsFixed(
                    200, $dateRange, $filters
                ),
                'corporate_customers' => $this->getTopCorporateCustomersFixed(
                    null, 200, $dateRange, $filters
                )
            ],
            'summary' => $this->revenueService->getTotalRevenueDataWithDateRange(
                null, $filters['divisi_id'], $dateRange['start'], $dateRange['end'],
                $filters['revenue_source'], $filters['tipe_revenue']
            )
        ];
    }

    private function generateExportFilename($filters)
    {
        $periodText = strtolower($filters['period_type']);
        $divisiText = $filters['divisi_id'] ? "_divisi{$filters['divisi_id']}" : "_all_divisi";
        $sortText = isset($filters['sort_indicator']) ? "_{$filters['sort_indicator']}" : "";
        $timestamp = date('Y-m-d_H-i-s');

        return "admin_dashboard_export_{$periodText}{$divisiText}{$sortText}_{$timestamp}.xlsx";
    }

    /**
     * Helper Methods
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
            $performanceData['account_manager']->each(function($am) {
                $am->detail_url = route('account-manager.show', $am->id);
            });
        }

        if (isset($performanceData['witel'])) {
            $performanceData['witel']->each(function($witel) {
                $witel->detail_url = route('witel.show', $witel->id);
            });
        }

        if (isset($performanceData['segment'])) {
            $performanceData['segment']->each(function($segment) {
                $segment->detail_url = route('segment.show', $segment->id);
            });
        }

        if (isset($performanceData['corporate_customer'])) {
            $performanceData['corporate_customer']->each(function($customer) {
                $customer->detail_url = route('corporate-customer.show', $customer->id);
            });
        }
    }

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
                                text: 'Perkembangan Revenue Bulanan ({$tahun})'
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
                                text: 'Distribusi Pencapaian Target AM per Bulan'
                            }
                        },
                        scales: {
                            x: { stacked: true },
                            y: { stacked: true, beginAtZero: true }
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
     * Get achievement color based on rate - sesuai requirement PDF
     */
    private function getAchievementColor($achievementRate)
    {
        if ($achievementRate >= 100) {
            return 'success'; // Hijau: ≥100%
        } elseif ($achievementRate >= 80) {
            return 'warning'; // Oranye: 80-99%
        } else {
            return 'danger';  // Merah: 0-80%
        }
    }

    /**
     * Get current data year (tahun terkini dari data aktual)
     */
    private function getCurrentDataYear()
    {
        static $currentYear = null;

        if ($currentYear === null) {
            $currentYear = CcRevenue::max('tahun') ?? 2025;
        }

        return $currentYear;
    }

    /**
     * AM Dashboard Delegation Methods
     */
    public function getAmPerformance(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'account_manager') {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        try {
            $amController = new \App\Http\Controllers\Overview\AmDashboardController($this->revenueService);

            // Get AM's performance summary
            $accountManager = AccountManager::where('user_id', $user->id)->first();
            if (!$accountManager) {
                return response()->json(['error' => 'Account Manager not found'], 404);
            }

            $filters = $this->extractFilters($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            $performanceSummary = $amController->getAmPerformanceSummary(
                $accountManager->id, $dateRange, $filters
            );

            return response()->json([
                'success' => true,
                'data' => $performanceSummary
            ]);

        } catch (\Exception $e) {
            Log::error('AM performance data failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to load AM performance data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAmCustomers(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'account_manager') {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        try {
            $amController = new \App\Http\Controllers\Overview\AmDashboardController($this->revenueService);

            $accountManager = AccountManager::where('user_id', $user->id)->first();
            if (!$accountManager) {
                return response()->json(['error' => 'Account Manager not found'], 404);
            }

            $filters = $this->extractFilters($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            $corporateCustomers = $amController->getAmCorporateCustomers(
                $accountManager->id, $dateRange, $filters
            );

            return response()->json([
                'success' => true,
                'data' => $corporateCustomers
            ]);

        } catch (\Exception $e) {
            Log::error('AM customers data failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to load AM customers data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function exportAm(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'account_manager') {
            abort(403, 'Unauthorized export access');
        }

        try {
            $amController = new \App\Http\Controllers\Overview\AmDashboardController($this->revenueService);
            return $amController->export($request);

        } catch (\Exception $e) {
            Log::error('AM export failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Export AM gagal: ' . $e->getMessage());
        }
    }

    /**
     * Detail Page Methods
     */
    public function showAccountManager($id)
    {
        try {
            $accountManager = AccountManager::with(['witel', 'divisis'])
                ->findOrFail($id);

            // Check authorization
            $user = Auth::user();
            if ($user->role === 'account_manager' && $accountManager->user_id !== $user->id) {
                abort(403, 'Unauthorized access to this Account Manager data');
            }

            // Get performance data
            $currentYear = date('Y');
            $performanceData = $this->performanceService->getAMPerformanceSummary($id, $currentYear);
            $monthlyChart = $this->performanceService->getAMMonthlyChart($id, $currentYear);
            $customerPerformance = $this->performanceService->getAMCustomerPerformance($id, $currentYear);

            return view('details.account-manager', compact(
                'accountManager',
                'performanceData',
                'monthlyChart',
                'customerPerformance'
            ));

        } catch (\Exception $e) {
            Log::error('Account Manager detail page failed', [
                'am_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Gagal memuat detail Account Manager');
        }
    }

    public function showWitel($id)
    {
        try {
            $witel = Witel::findOrFail($id);

            // Check authorization
            $user = Auth::user();
            if ($user->role === 'witel_support') {
                $userWitel = AccountManager::where('user_id', $user->id)->first();
                if (!$userWitel || $userWitel->witel_id !== $id) {
                    abort(403, 'Unauthorized access to this Witel data');
                }
            }

            // Get witel performance data
            $currentYear = date('Y');
            $witelData = $this->revenueService->getTotalRevenueData($id, null, $currentYear);
            $topAMs = $this->revenueService->getTopAccountManagers($id, 20, $currentYear);
            $categoryDistribution = $this->rankingService->getCategoryDistribution($id, $currentYear);

            return view('details.witel', compact(
                'witel',
                'witelData',
                'topAMs',
                'categoryDistribution'
            ));

        } catch (\Exception $e) {
            Log::error('Witel detail page failed', [
                'witel_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Gagal memuat detail Witel');
        }
    }

    public function showCorporateCustomer($id)
    {
        try {
            $customer = CorporateCustomer::with(['segment', 'divisi'])->findOrFail($id);

            // Get customer revenue data
            $currentYear = date('Y');
            $revenueData = CcRevenue::where('corporate_customer_id', $id)
                ->where('tahun', $currentYear)
                ->selectRaw('
                    SUM(real_revenue) as total_revenue,
                    SUM(target_revenue) as total_target
                ')
                ->first();

            $monthlyData = CcRevenue::where('corporate_customer_id', $id)
                ->where('tahun', $currentYear)
                ->selectRaw('
                    bulan,
                    SUM(real_revenue) as monthly_revenue,
                    SUM(target_revenue) as monthly_target
                ')
                ->groupBy('bulan')
                ->orderBy('bulan')
                ->get();

            return view('details.corporate-customer', compact(
                'customer',
                'revenueData',
                'monthlyData'
            ));

        } catch (\Exception $e) {
            Log::error('Corporate Customer detail page failed', [
                'customer_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Gagal memuat detail Corporate Customer');
        }
    }

    public function showSegment($id)
    {
        try {
            $segment = Segment::with('divisi')->findOrFail($id);

            // Get segment performance data
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

            return view('details.segment', compact(
                'segment',
                'segmentData',
                'topCustomers'
            ));

        } catch (\Exception $e) {
            Log::error('Segment detail page failed', [
                'segment_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Gagal memuat detail Segment');
        }
    }

    /**
     * Additional API Methods for Dashboard
     */
    public function getChartData(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $currentYear = date('Y');

            $monthlyRevenue = $this->revenueService->getMonthlyRevenue(
                $currentYear, null, $filters['divisi_id'],
                $filters['revenue_source'], $filters['tipe_revenue']
            );

            $performanceDistribution = $this->performanceService->getPerformanceDistribution(
                $currentYear, null, $filters['divisi_id'],
                $filters['revenue_source'], $filters['tipe_revenue']
            );

            return response()->json([
                'success' => true,
                'monthly_data' => $monthlyRevenue,
                'performance_data' => $performanceDistribution
            ]);

        } catch (\Exception $e) {
            Log::error('Chart data loading failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to load chart data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getRevenueTable(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            $revenueTable = $this->revenueService->getRevenueTableDataWithDateRange(
                $dateRange['start'], $dateRange['end'], null, $filters['divisi_id'],
                $filters['revenue_source'], $filters['tipe_revenue']
            );

            return response()->json([
                'success' => true,
                'data' => $revenueTable
            ]);

        } catch (\Exception $e) {
            Log::error('Revenue table loading failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to load revenue table',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getSummary(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            $summary = $this->revenueService->getTotalRevenueDataWithDateRange(
                null, $filters['divisi_id'], $dateRange['start'], $dateRange['end'],
                $filters['revenue_source'], $filters['tipe_revenue']
            );

            $summary['period_text'] = $this->generatePeriodText($filters['period_type'], $dateRange);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('Summary loading failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to load summary',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getPerformanceInsights(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        try {
            // Generate performance insights for admin dashboard
            $insights = [
                'total_insights' => 0,
                'insights' => [],
                'recommendations' => []
            ];

            // This would be implemented based on specific business requirements
            // For now, return a placeholder response

            return response()->json([
                'success' => true,
                'data' => $insights
            ]);

        } catch (\Exception $e) {
            Log::error('Performance insights loading failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to load performance insights',
                'message' => $e->getMessage()
            ], 500);
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