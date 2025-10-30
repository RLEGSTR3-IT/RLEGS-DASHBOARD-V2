<?php

namespace App\Http\Controllers\Overview;

use App\Exports\AmDashboardExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use App\Services\RevenueCalculationService;
use App\Services\AmPerformanceService;
use App\Models\AccountManager;
use App\Models\AmRevenue;
use App\Models\CcRevenue;
use App\Models\CorporateCustomer;
use App\Models\Divisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AmDashboardController extends Controller
{
    protected $revenueService;
    protected $performanceService;

    public function __construct(
        RevenueCalculationService $revenueService,
        AmPerformanceService $performanceService
    ) {
        $this->revenueService = $revenueService;
        $this->performanceService = $performanceService;
    }

    /**
     * USE CASE 1: Dashboard untuk AM yang login (/dashboard)
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'account_manager') {
            Log::warning('Non-AM user tried to access AM dashboard', [
                'user_id' => $user->id,
                'role' => $user->role
            ]);
            abort(403, 'Unauthorized access to Account Manager dashboard');
        }

        $accountManagerId = $user->account_manager_id;

        if (!$accountManagerId) {
            Log::error('AM user without account_manager_id', [
                'user_id' => $user->id
            ]);
            return redirect()->route('login')
                ->with('error', 'Account Manager ID tidak ditemukan. Hubungi administrator.');
        }

        return $this->renderAmDashboard($accountManagerId, $request);
    }

    /**
     * USE CASE 2: Detail AM dari leaderboard (/account-manager/{id})
     */
    public function show($id, Request $request)
    {
        // Authorization check
        $this->authorizeAccess($id);

        return $this->renderAmDashboard($id, $request);
    }

    /**
     * Core rendering logic - shared by both use cases
     */
    private function renderAmDashboard($amId, Request $request)
    {
        try {
            // Load Account Manager dengan eager loading
            $accountManager = AccountManager::with(['witel', 'divisis'])
                ->findOrFail($amId);

            // Extract filters dari request
            $filters = $this->extractFilters($request, $accountManager);

            // Validate divisi_id filter
            $filters['divisi_id'] = $this->validateDivisiFilter(
                $filters['divisi_id'],
                $accountManager
            );

            // 1. SECTION PROFIL
            $profileData = $this->getProfileData($accountManager, $filters);

            // 2. SECTION PERINGKAT (RANKING)
            $rankingData = $this->getRankingData($amId, $filters);

            // 3. CARD GROUP REVENUE
            $cardData = $this->getCardGroupData($amId, $filters);

            // 4. TAB DATA PELANGGAN
            $customerData = $this->getCustomerTabData($amId, $filters);

            // 5. TAB ANALISIS PERFORMA
            $performanceAnalysis = $this->getPerformanceAnalysisData($amId, $filters);

            // 6. FILTER OPTIONS untuk UI
            $filterOptions = $this->getFilterOptions($accountManager);

            Log::info('AM dashboard loaded successfully', [
                'am_id' => $amId,
                'user_id' => Auth::id(),
                'filters' => $filters,
                'total_customers' => $cardData['total_customers'] ?? 0
            ]);

            return view('am.detailAM', compact(
                'accountManager',
                'profileData',
                'rankingData',
                'cardData',
                'customerData',
                'performanceAnalysis',
                'filterOptions',
                'filters'
            ));
        } catch (\Exception $e) {
            Log::error('AM dashboard rendering failed', [
                'am_id' => $amId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('am.detailAM', [
                'error' => 'Gagal memuat data dashboard Account Manager',
                'accountManager' => AccountManager::find($amId),
                'filters' => $this->getDefaultFilters()
            ]);
        }
    }

    /**
     * Get Profile Data - Section 1
     */
    private function getProfileData($accountManager, $filters)
    {
        $divisiList = $accountManager->divisis;
        $primaryDivisi = $divisiList->where('pivot.is_primary', 1)->first();

        return [
            'nama' => $accountManager->nama,
            'nik' => $accountManager->nik,
            'witel' => [
                'id' => $accountManager->witel->id,
                'nama' => $accountManager->witel->nama
            ],
            'divisis' => $divisiList->map(function($divisi) {
                return [
                    'id' => $divisi->id,
                    'nama' => $divisi->nama,
                    'kode' => $divisi->kode,
                    'is_primary' => $divisi->pivot->is_primary,
                    'color' => $this->getDivisiColor($divisi->kode)
                ];
            }),
            'primary_divisi_id' => $primaryDivisi ? $primaryDivisi->id : null,
            'selected_divisi_id' => $filters['divisi_id'],
            'selected_divisi_name' => $divisiList->where('id', $filters['divisi_id'])->first()->nama ?? 'N/A',
            'is_multi_divisi' => $divisiList->count() > 1
        ];
    }

    /**
     * Get Ranking Data - Section 2
     */
    private function getRankingData($amId, $filters)
    {
        $period = [
            'tahun' => $filters['tahun'],
            'bulan' => date('n') // Current month untuk comparison
        ];

        // Global Ranking
        $globalRanking = $this->performanceService->getGlobalRanking(
            $amId,
            $period,
            $filters['divisi_id']
        );

        // Witel Ranking
        $witelRanking = $this->performanceService->getWitelRanking(
            $amId,
            $period,
            $filters['divisi_id']
        );

        // Divisi Ranking (untuk semua divisi AM)
        $accountManager = AccountManager::with('divisis')->find($amId);
        $divisiRankings = [];

        foreach ($accountManager->divisis as $divisi) {
            $divisiRankings[$divisi->kode] = $this->performanceService->getDivisiRanking(
                $amId,
                $divisi->id,
                $period
            );
        }

        return [
            'global' => $globalRanking,
            'witel' => $witelRanking,
            'divisi' => $divisiRankings,
            'period_text' => $this->getPeriodText($filters['period_type'])
        ];
    }

    /**
     * Get Card Group Data - Section 3
     */
    private function getCardGroupData($amId, $filters)
    {
        $dateRange = $this->calculateDateRange($filters['period_type'], $filters['tahun']);

        $query = AmRevenue::where('account_manager_id', $amId)
            ->where('tahun', $filters['tahun']);

        // Filter by divisi
        if ($filters['divisi_id']) {
            $query->where('divisi_id', $filters['divisi_id']);
        }

        // Apply date range for YTD/MTD
        if ($filters['period_type'] === 'MTD') {
            $query->where('bulan', date('n'));
        } else {
            // YTD = all months up to current month
            $query->where('bulan', '<=', date('n'));
        }

        // Filter by tipe_revenue via cc_revenues
        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->whereHas('corporateCustomer.ccRevenues', function($q) use ($filters) {
                $q->where('tipe_revenue', $filters['tipe_revenue'])
                  ->where('tahun', $filters['tahun']);
            });
        }

        $aggregated = $query->selectRaw('
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target,
                COUNT(DISTINCT corporate_customer_id) as total_customers
            ')
            ->first();

        $totalRevenue = $aggregated->total_revenue ?? 0;
        $totalTarget = $aggregated->total_target ?? 0;
        $achievementRate = $totalTarget > 0
            ? round(($totalRevenue / $totalTarget) * 100, 2)
            : 0;

        return [
            'total_revenue' => floatval($totalRevenue),
            'total_target' => floatval($totalTarget),
            'achievement_rate' => $achievementRate,
            'achievement_color' => $this->getAchievementColor($achievementRate),
            'total_customers' => intval($aggregated->total_customers ?? 0),
            'period_text' => $this->generatePeriodText($filters['period_type'], $dateRange)
        ];
    }

    /**
     * Get Customer Tab Data - Section 4 (Tab 1)
     */
    private function getCustomerTabData($amId, $filters)
    {
        $viewMode = $filters['customer_view_mode'];

        // Get available years untuk dropdown
        $availableYears = AmRevenue::where('account_manager_id', $amId)
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        $customerData = [];

        switch ($viewMode) {
            case 'agregat_cc':
                $customerData = $this->getCustomerDataAggregateByCC($amId, $filters);
                break;

            case 'agregat_bulan':
                $customerData = $this->getCustomerDataAggregateByMonth($amId, $filters);
                break;

            case 'detail':
            default:
                $customerData = $this->getCustomerDataDetail($amId, $filters);
                break;
        }

        return [
            'view_mode' => $viewMode,
            'customers' => $customerData,
            'available_years' => $availableYears,
            'use_year_picker' => count($availableYears) > 10,
            'tahun' => $filters['tahun'],
            'divisi_id' => $filters['divisi_id'],
            'tipe_revenue' => $filters['tipe_revenue']
        ];
    }

    /**
     * Customer Data - Agregat per CC (sepanjang tahun filter)
     */
    private function getCustomerDataAggregateByCC($amId, $filters)
    {
        $query = AmRevenue::where('account_manager_id', $amId)
            ->where('tahun', $filters['tahun']);

        if ($filters['divisi_id']) {
            $query->where('divisi_id', $filters['divisi_id']);
        }

        // Filter by tipe_revenue
        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->whereHas('corporateCustomer.ccRevenues', function($q) use ($filters) {
                $q->where('tipe_revenue', $filters['tipe_revenue'])
                  ->where('tahun', $filters['tahun']);
            });
        }

        $aggregated = $query->selectRaw('
                corporate_customer_id,
                divisi_id,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->groupBy('corporate_customer_id', 'divisi_id')
            ->orderByDesc('total_revenue')
            ->get();

        return $aggregated->map(function($item) {
            $customer = CorporateCustomer::find($item->corporate_customer_id);
            $divisi = Divisi::find($item->divisi_id);

            $achievementRate = $item->total_target > 0
                ? round(($item->total_revenue / $item->total_target) * 100, 2)
                : 0;

            // Get segment dari cc_revenues
            $segment = CcRevenue::where('corporate_customer_id', $item->corporate_customer_id)
                ->with('segment')
                ->first();

            return [
                'customer_id' => $customer->id,
                'customer_name' => $customer->nama,
                'nipnas' => $customer->nipnas,
                'divisi' => $divisi->nama ?? 'N/A',
                'segment' => $segment->segment->lsegment_ho ?? 'N/A',
                'total_revenue' => floatval($item->total_revenue),
                'total_target' => floatval($item->total_target),
                'achievement_rate' => $achievementRate,
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ];
        });
    }

    /**
     * Customer Data - Agregat per Bulan (sum semua CC per bulan)
     * IMPROVED: Dengan filter range bulan
     */
    private function getCustomerDataAggregateByMonth($amId, $filters)
    {
        $query = AmRevenue::where('account_manager_id', $amId)
            ->where('tahun', $filters['tahun']);

        if ($filters['divisi_id']) {
            $query->where('divisi_id', $filters['divisi_id']);
        }

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->whereHas('corporateCustomer.ccRevenues', function($q) use ($filters) {
                $q->where('tipe_revenue', $filters['tipe_revenue'])
                  ->where('tahun', $filters['tahun']);
            });
        }

        // IMPROVED: Filter range bulan (hanya untuk view mode ini)
        $bulanStart = $filters['bulan_start'] ?? 1;
        $bulanEnd = $filters['bulan_end'] ?? 12;

        $query->whereBetween('bulan', [$bulanStart, $bulanEnd]);

        $monthlyData = $query->selectRaw('
                bulan,
                SUM(real_revenue) as monthly_revenue,
                SUM(target_revenue) as monthly_target,
                COUNT(DISTINCT corporate_customer_id) as customer_count
            ')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        return $monthlyData->map(function($item) {
            $achievementRate = $item->monthly_target > 0
                ? round(($item->monthly_revenue / $item->monthly_target) * 100, 2)
                : 0;

            return [
                'bulan' => $item->bulan,
                'bulan_name' => $this->getMonthName($item->bulan),
                'total_revenue' => floatval($item->monthly_revenue),
                'total_target' => floatval($item->monthly_target),
                'achievement_rate' => $achievementRate,
                'achievement_color' => $this->getAchievementColor($achievementRate),
                'customer_count' => intval($item->customer_count)
            ];
        });
    }

    /**
     * Customer Data - Detail (per bulan per CC)
     */
    private function getCustomerDataDetail($amId, $filters)
    {
        $query = AmRevenue::where('account_manager_id', $amId)
            ->where('tahun', $filters['tahun'])
            ->with(['corporateCustomer', 'divisi']);

        if ($filters['divisi_id']) {
            $query->where('divisi_id', $filters['divisi_id']);
        }

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->whereHas('corporateCustomer.ccRevenues', function($q) use ($filters) {
                $q->where('tipe_revenue', $filters['tipe_revenue'])
                  ->where('tahun', $filters['tahun']);
            });
        }

        $detailData = $query->orderBy('bulan')
            ->orderBy('corporate_customer_id')
            ->get();

        return $detailData->map(function($item) {
            $achievementRate = $item->target_revenue > 0
                ? round(($item->real_revenue / $item->target_revenue) * 100, 2)
                : 0;

            // Get segment
            $segment = CcRevenue::where('corporate_customer_id', $item->corporate_customer_id)
                ->with('segment')
                ->first();

            return [
                'customer_id' => $item->corporateCustomer->id,
                'customer_name' => $item->corporateCustomer->nama,
                'nipnas' => $item->corporateCustomer->nipnas,
                'divisi' => $item->divisi->nama ?? 'N/A',
                'segment' => $segment->segment->lsegment_ho ?? 'N/A',
                'bulan' => $item->bulan,
                'bulan_name' => $this->getMonthName($item->bulan),
                'revenue' => floatval($item->real_revenue),
                'target' => floatval($item->target_revenue),
                'achievement_rate' => $achievementRate,
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ];
        });
    }

    /**
     * Get Performance Analysis Data - Section 5 (Tab 2)
     * IMPROVED: Performance Summary dengan filter tahun/range tahun
     */
    private function getPerformanceAnalysisData($amId, $filters)
    {
        // 1. Ringkasan Performa (dengan filter tahun baru)
        $summaryFilters = [
            'divisi_id' => $filters['divisi_id'],
            'tipe_revenue' => $filters['tipe_revenue'],
            'summary_mode' => $filters['summary_mode'] ?? 'all_time', // all_time | specific_year | range_years
            'summary_year' => $filters['summary_year'] ?? null,
            'summary_year_start' => $filters['summary_year_start'] ?? null,
            'summary_year_end' => $filters['summary_year_end'] ?? null
        ];

        $summary = $this->performanceService->getPerformanceSummary($amId, $summaryFilters);

        // 2. Data untuk Grafik Performa Bulanan
        $monthlyChart = $this->performanceService->getMonthlyPerformanceChart(
            $amId,
            $filters['chart_tahun'] ?? $filters['tahun'],
            $filters
        );

        // 3. Tabel Detail (reuse customer data dengan view mode dari analysis)
        $analysisViewMode = $filters['analysis_view_mode'] ?? 'detail';

        $filtersForTable = array_merge($filters, [
            'customer_view_mode' => $analysisViewMode
        ]);

        $detailTable = [];
        switch ($analysisViewMode) {
            case 'agregat_cc':
                $detailTable = $this->getCustomerDataAggregateByCC($amId, $filtersForTable);
                break;
            case 'agregat_bulan':
                $detailTable = $this->getCustomerDataAggregateByMonth($amId, $filtersForTable);
                break;
            case 'detail':
            default:
                $detailTable = $this->getCustomerDataDetail($amId, $filtersForTable);
                break;
        }

        return [
            'summary' => $summary,
            'summary_filters' => $summaryFilters,
            'monthly_chart' => $monthlyChart,
            'chart_filters' => [
                'tahun' => $filters['chart_tahun'] ?? $filters['tahun'],
                'display_mode' => $filters['chart_display'] ?? 'combination'
            ],
            'detail_table' => [
                'view_mode' => $analysisViewMode,
                'data' => $detailTable
            ]
        ];
    }

    /**
     * Extract and validate filters from request
     * IMPROVED: Tambah filter untuk performance summary
     */
    private function extractFilters(Request $request, $accountManager)
    {
        // Get primary divisi sebagai default
        $primaryDivisi = $accountManager->divisis
            ->where('pivot.is_primary', 1)
            ->first();

        $defaultDivisiId = $primaryDivisi ? $primaryDivisi->id :
            ($accountManager->divisis->first()->id ?? null);

        // Smart default untuk bulan_end berdasarkan tahun filter
        $tahunFilter = $request->get('tahun', date('Y'));
        $defaultBulanEnd = $tahunFilter < date('Y') ? 12 : date('n');

        return [
            'period_type' => $request->get('period_type', 'YTD'),
            'tahun' => $tahunFilter,
            'divisi_id' => $request->get('divisi_id', $defaultDivisiId),
            'tipe_revenue' => $request->get('tipe_revenue', 'all'),
            'customer_view_mode' => $request->get('customer_view_mode', 'detail'),
            'analysis_view_mode' => $request->get('analysis_view_mode', 'detail'),

            // Filter range bulan (HANYA untuk view mode agregat_bulan)
            'bulan_start' => $request->get('bulan_start', 1),
            'bulan_end' => $request->get('bulan_end', $defaultBulanEnd),

            // Chart filters
            'chart_tahun' => $request->get('chart_tahun', date('Y')),
            'chart_display' => $request->get('chart_display', 'combination'),

            // BARU: Performance Summary filters
            'summary_mode' => $request->get('summary_mode', 'all_time'), // all_time | specific_year | range_years
            'summary_year' => $request->get('summary_year'),
            'summary_year_start' => $request->get('summary_year_start'),
            'summary_year_end' => $request->get('summary_year_end'),

            'active_tab' => $request->get('active_tab', 'customers')
        ];
    }

    /**
     * Validate divisi filter
     */
    private function validateDivisiFilter($divisiId, $accountManager)
    {
        if (!$divisiId) {
            return null;
        }

        $hasDivisi = $accountManager->divisis->contains('id', $divisiId);

        if (!$hasDivisi) {
            Log::warning('AM tried to access divisi they don\'t belong to', [
                'am_id' => $accountManager->id,
                'requested_divisi' => $divisiId
            ]);

            // Return primary divisi instead
            $primaryDivisi = $accountManager->divisis
                ->where('pivot.is_primary', 1)
                ->first();

            return $primaryDivisi ? $primaryDivisi->id : null;
        }

        return $divisiId;
    }

    /**
     * Get filter options for UI
     * IMPROVED: Tambah options untuk performance summary mode
     */
    private function getFilterOptions($accountManager)
    {
        $availableYears = AmRevenue::where('account_manager_id', $accountManager->id)
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        // Bulan options untuk filter range
        $bulanOptions = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulanOptions[$i] = $this->getMonthName($i);
        }

        return [
            'period_types' => [
                'YTD' => 'Year to Date',
                'MTD' => 'Month to Date'
            ],
            'divisis' => $accountManager->divisis->map(function($divisi) {
                return [
                    'id' => $divisi->id,
                    'nama' => $divisi->nama,
                    'kode' => $divisi->kode,
                    'is_primary' => $divisi->pivot->is_primary
                ];
            }),
            'tipe_revenues' => [
                'all' => 'Semua Tipe',
                'REGULER' => 'Revenue Reguler',
                'NGTMA' => 'Revenue NGTMA'
            ],
            'view_modes' => [
                'agregat_cc' => 'Agregat per Customer',
                'agregat_bulan' => 'Agregat per Bulan',
                'detail' => 'Detail (Per Bulan per Customer)'
            ],
            'chart_displays' => [
                'revenue' => 'Revenue Saja',
                'achievement' => 'Achievement Saja',
                'combination' => 'Kombinasi (Revenue + Achievement)'
            ],
            // BARU: Performance Summary modes
            'summary_modes' => [
                'all_time' => 'Sepanjang Waktu',
                'specific_year' => 'Tahun Tertentu',
                'range_years' => 'Range Tahun'
            ],
            'available_years' => $availableYears,
            'use_year_picker' => count($availableYears) > 10,
            'bulan_options' => $bulanOptions,
            'current_month' => date('n')
        ];
    }

    /**
     * Authorization check
     */
    private function authorizeAccess($amId)
    {
        $user = Auth::user();

        // Admin bisa akses semua AM
        if ($user->role === 'admin') {
            return true;
        }

        // Witel Support bisa akses AM di witel mereka saja
        if ($user->role === 'witel_support') {
            $am = AccountManager::find($amId);
            if ($am && $am->witel_id === $user->witel_id) {
                return true;
            }

            Log::warning('Witel support tried to access AM outside their witel', [
                'user_id' => $user->id,
                'user_witel_id' => $user->witel_id,
                'requested_am_id' => $amId,
                'am_witel_id' => $am ? $am->witel_id : null
            ]);

            abort(403, 'Anda hanya bisa akses Account Manager di Witel Anda');
        }

        // AM bisa akses semua AM (untuk benchmarking/learning)
        if ($user->role === 'account_manager') {
            return true;
        }

        Log::warning('Unknown role tried to access AM detail', [
            'user_id' => $user->id,
            'role' => $user->role,
            'requested_am_id' => $amId
        ]);

        abort(403, 'Role Anda tidak memiliki akses ke halaman ini');
    }

    /**
     * Get default filters
     */
    private function getDefaultFilters()
    {
        return [
            'period_type' => 'YTD',
            'tahun' => date('Y'),
            'divisi_id' => null,
            'tipe_revenue' => 'all',
            'customer_view_mode' => 'detail',
            'analysis_view_mode' => 'detail',
            'bulan_start' => 1,
            'bulan_end' => date('n'),
            'chart_tahun' => date('Y'),
            'chart_display' => 'combination',
            'summary_mode' => 'all_time',
            'summary_year' => null,
            'summary_year_start' => null,
            'summary_year_end' => null,
            'active_tab' => 'customers'
        ];
    }

    /**
     * Helper: Get achievement color based on rate
     */
    private function getAchievementColor($achievementRate)
    {
        if ($achievementRate >= 100) {
            return 'success'; // Hijau
        } elseif ($achievementRate >= 80) {
            return 'warning'; // Oranye
        } else {
            return 'danger';  // Merah
        }
    }

    /**
     * Helper: Get divisi color mapping
     */
    private function getDivisiColor($kode)
    {
        $colorMap = [
            'DGS' => '#4e73df', // Biru
            'DSS' => '#1cc88a', // Hijau
            'DPS' => '#f6c23e'  // Kuning
        ];

        return $colorMap[$kode] ?? '#858796'; // Default gray
    }

    /**
     * Helper: Calculate date range
     */
    private function calculateDateRange($periodType, $tahun)
    {
        $now = Carbon::now();

        if ($periodType === 'MTD') {
            return [
                'start' => Carbon::createFromDate($tahun, date('n'), 1)->startOfMonth(),
                'end' => Carbon::createFromDate($tahun, date('n'), date('j'))->endOfDay(),
                'type' => 'MTD'
            ];
        } else {
            return [
                'start' => Carbon::createFromDate($tahun, 1, 1)->startOfYear(),
                'end' => Carbon::createFromDate($tahun, date('n'), date('j'))->endOfDay(),
                'type' => 'YTD'
            ];
        }
    }

    /**
     * Helper: Generate period text
     */
    private function generatePeriodText($periodType, $dateRange)
    {
        $startDate = $dateRange['start']->format('d M');
        $endDate = $dateRange['end']->format('d M Y');

        return "{$periodType}: {$startDate} - {$endDate}";
    }

    /**
     * Helper: Get period text simple
     */
    private function getPeriodText($periodType)
    {
        if ($periodType === 'MTD') {
            return 'Bulan ' . $this->getMonthName(date('n')) . ' ' . date('Y');
        } else {
            return 'Tahun ' . date('Y') . ' (s.d. ' . $this->getMonthName(date('n')) . ')';
        }
    }

    /**
     * Helper: Get month name
     */
    private function getMonthName($monthNumber)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$monthNumber] ?? 'Unknown';
    }

    /**
     * AJAX Endpoint: Get tab data dynamically
     */
    public function getTabData(Request $request)
    {
        $user = Auth::user();
        $amId = $user->role === 'account_manager'
            ? $user->account_manager_id
            : $request->get('am_id');

        if (!$amId) {
            return response()->json(['error' => 'Account Manager ID required'], 400);
        }

        $this->authorizeAccess($amId);

        $accountManager = AccountManager::with(['witel', 'divisis'])->findOrFail($amId);
        $filters = $this->extractFilters($request, $accountManager);
        $tab = $request->get('tab', 'customers');

        try {
            $data = [];

            if ($tab === 'customers') {
                $data = $this->getCustomerTabData($amId, $filters);
            } elseif ($tab === 'analysis') {
                $data = $this->getPerformanceAnalysisData($amId, $filters);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'tab' => $tab
            ]);

        } catch (\Exception $e) {
            Log::error('AM tab data loading failed', [
                'am_id' => $amId,
                'tab' => $tab,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to load tab data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export AM dashboard data
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $amId = $user->role === 'account_manager'
            ? $user->account_manager_id
            : $request->get('am_id');

        if (!$amId) {
            return redirect()->back()->with('error', 'Account Manager ID required');
        }

        $this->authorizeAccess($amId);

        try {
            $accountManager = AccountManager::with(['witel', 'divisis'])->findOrFail($amId);
            $filters = $this->extractFilters($request, $accountManager);

            // Collect comprehensive export data
            $exportData = $this->prepareExportData($amId, $filters);

            // Generate filename
            $filename = $this->generateExportFilename($accountManager, $filters);

            Log::info('AM dashboard export initiated', [
                'am_id' => $amId,
                'user_id' => Auth::id(),
                'filters' => $filters,
                'filename' => $filename
            ]);

            // Return Excel export with multiple sheets
            return Excel::download(
                new AmDashboardExport($exportData, $accountManager, $filters),
                $filename
            );

        } catch (\Exception $e) {
            Log::error('AM dashboard export failed', [
                'am_id' => $amId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Export gagal: ' . $e->getMessage());
        }
    }

    /**
     * Prepare comprehensive export data
     */
    private function prepareExportData($amId, $filters)
    {
        return [
            // Sheet 1: Profile & Summary
            'profile' => $this->getProfileData(
                AccountManager::with(['witel', 'divisis'])->find($amId),
                $filters
            ),

            // Sheet 2: Card Summary
            'summary' => $this->getCardGroupData($amId, $filters),

            // Sheet 3: Ranking
            'ranking' => $this->getRankingData($amId, $filters),

            // Sheet 4: Customer Data (all view modes)
            'customer_data' => [
                'agregat_cc' => $this->getCustomerDataAggregateByCC($amId, $filters),
                'agregat_bulan' => $this->getCustomerDataAggregateByMonth($amId, $filters),
                'detail' => $this->getCustomerDataDetail($amId, $filters)
            ],

            // Sheet 5: Performance Analysis
            'performance' => $this->performanceService->getPerformanceSummary($amId, $filters),

            // Sheet 6: Monthly Chart Data
            'monthly_chart_data' => $this->performanceService->getMonthlyPerformanceChart(
                $amId,
                $filters['tahun'],
                $filters
            )
        ];
    }

    /**
     * Generate export filename
     */
    private function generateExportFilename($accountManager, $filters)
    {
        $amName = str_replace(' ', '_', $accountManager->nama);
        $periodText = strtolower($filters['period_type']);
        $tahun = $filters['tahun'];
        $divisiText = $filters['divisi_id']
            ? "_divisi{$filters['divisi_id']}"
            : "_all_divisi";
        $timestamp = date('Y-m-d_H-i-s');

        return "am_dashboard_{$amName}_{$periodText}_{$tahun}{$divisiText}_{$timestamp}.xlsx";
    }

    /**
     * AJAX Endpoint: Get card data (for live updates)
     */
    public function getCardData(Request $request)
    {
        $user = Auth::user();
        $amId = $user->role === 'account_manager'
            ? $user->account_manager_id
            : $request->get('am_id');

        if (!$amId) {
            return response()->json(['error' => 'Account Manager ID required'], 400);
        }

        $this->authorizeAccess($amId);

        try {
            $accountManager = AccountManager::with(['witel', 'divisis'])->findOrFail($amId);
            $filters = $this->extractFilters($request, $accountManager);

            $cardData = $this->getCardGroupData($amId, $filters);

            return response()->json([
                'success' => true,
                'data' => $cardData
            ]);

        } catch (\Exception $e) {
            Log::error('AM card data loading failed', [
                'am_id' => $amId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to load card data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX Endpoint: Get ranking data
     */
    public function getRankingDataAjax(Request $request)
    {
        $user = Auth::user();
        $amId = $user->role === 'account_manager'
            ? $user->account_manager_id
            : $request->get('am_id');

        if (!$amId) {
            return response()->json(['error' => 'Account Manager ID required'], 400);
        }

        $this->authorizeAccess($amId);

        try {
            $accountManager = AccountManager::with(['witel', 'divisis'])->findOrFail($amId);
            $filters = $this->extractFilters($request, $accountManager);

            $rankingData = $this->getRankingData($amId, $filters);

            return response()->json([
                'success' => true,
                'data' => $rankingData
            ]);

        } catch (\Exception $e) {
            Log::error('AM ranking data loading failed', [
                'am_id' => $amId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to load ranking data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX Endpoint: Get chart data for performance analysis
     */
    public function getChartData(Request $request)
    {
        $user = Auth::user();
        $amId = $user->role === 'account_manager'
            ? $user->account_manager_id
            : $request->get('am_id');

        if (!$amId) {
            return response()->json(['error' => 'Account Manager ID required'], 400);
        }

        $this->authorizeAccess($amId);

        try {
            $accountManager = AccountManager::with(['witel', 'divisis'])->findOrFail($amId);
            $filters = $this->extractFilters($request, $accountManager);

            $chartData = $this->performanceService->getMonthlyPerformanceChart(
                $amId,
                $filters['chart_tahun'] ?? $filters['tahun'],
                $filters
            );

            return response()->json([
                'success' => true,
                'data' => $chartData
            ]);

        } catch (\Exception $e) {
            Log::error('AM chart data loading failed', [
                'am_id' => $amId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to load chart data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX Endpoint: Get performance summary
     */
    public function getPerformanceSummary(Request $request)
    {
        $user = Auth::user();
        $amId = $user->role === 'account_manager'
            ? $user->account_manager_id
            : $request->get('am_id');

        if (!$amId) {
            return response()->json(['error' => 'Account Manager ID required'], 400);
        }

        $this->authorizeAccess($amId);

        try {
            $accountManager = AccountManager::with(['witel', 'divisis'])->findOrFail($amId);
            $filters = $this->extractFilters($request, $accountManager);

            $summary = $this->performanceService->getPerformanceSummary($amId, $filters);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('AM performance summary loading failed', [
                'am_id' => $amId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to load performance summary',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX Endpoint: Update filters and refresh data
     */
    public function updateFilters(Request $request)
    {
        $user = Auth::user();
        $amId = $user->role === 'account_manager'
            ? $user->account_manager_id
            : $request->get('am_id');

        if (!$amId) {
            return response()->json(['error' => 'Account Manager ID required'], 400);
        }

        $this->authorizeAccess($amId);

        try {
            $accountManager = AccountManager::with(['witel', 'divisis'])->findOrFail($amId);
            $filters = $this->extractFilters($request, $accountManager);

            // Validate divisi filter
            $filters['divisi_id'] = $this->validateDivisiFilter(
                $filters['divisi_id'],
                $accountManager
            );

            // Get updated data based on active section
            $section = $request->get('section', 'all');
            $data = [];

            switch ($section) {
                case 'cards':
                    $data['cards'] = $this->getCardGroupData($amId, $filters);
                    break;

                case 'ranking':
                    $data['ranking'] = $this->getRankingData($amId, $filters);
                    break;

                case 'customers':
                    $data['customers'] = $this->getCustomerTabData($amId, $filters);
                    break;

                case 'analysis':
                    $data['analysis'] = $this->getPerformanceAnalysisData($amId, $filters);
                    break;

                case 'all':
                default:
                    $data = [
                        'cards' => $this->getCardGroupData($amId, $filters),
                        'ranking' => $this->getRankingData($amId, $filters),
                        'customers' => $this->getCustomerTabData($amId, $filters),
                        'analysis' => $this->getPerformanceAnalysisData($amId, $filters)
                    ];
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => $filters
            ]);

        } catch (\Exception $e) {
            Log::error('AM filter update failed', [
                'am_id' => $amId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to update filters',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get AM info for public display (used in leaderboard cards)
     */
    public function getAmInfo($id)
    {
        try {
            $accountManager = AccountManager::with(['witel', 'divisis'])
                ->findOrFail($id);

            // Get basic performance metrics (current year YTD)
            $currentYear = date('Y');
            $basicMetrics = AmRevenue::where('account_manager_id', $id)
                ->where('tahun', $currentYear)
                ->where('bulan', '<=', date('n'))
                ->selectRaw('
                    SUM(real_revenue) as total_revenue,
                    SUM(target_revenue) as total_target,
                    COUNT(DISTINCT corporate_customer_id) as total_customers
                ')
                ->first();

            $achievementRate = $basicMetrics->total_target > 0
                ? round(($basicMetrics->total_revenue / $basicMetrics->total_target) * 100, 2)
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $accountManager->id,
                    'nama' => $accountManager->nama,
                    'nik' => $accountManager->nik,
                    'witel' => [
                        'id' => $accountManager->witel->id,
                        'nama' => $accountManager->witel->nama
                    ],
                    'divisis' => $accountManager->divisis->map(function($divisi) {
                        return [
                            'id' => $divisi->id,
                            'nama' => $divisi->nama,
                            'kode' => $divisi->kode,
                            'color' => $this->getDivisiColor($divisi->kode)
                        ];
                    }),
                    'metrics' => [
                        'total_revenue' => floatval($basicMetrics->total_revenue ?? 0),
                        'total_target' => floatval($basicMetrics->total_target ?? 0),
                        'achievement_rate' => $achievementRate,
                        'achievement_color' => $this->getAchievementColor($achievementRate),
                        'total_customers' => intval($basicMetrics->total_customers ?? 0)
                    ],
                    'detail_url' => route('account-manager.show', $id)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get AM info', [
                'am_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get Account Manager info',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Compare AM with others (for benchmarking)
     */
    public function compareWithOthers(Request $request)
    {
        $user = Auth::user();
        $amId = $user->role === 'account_manager'
            ? $user->account_manager_id
            : $request->get('am_id');

        if (!$amId) {
            return response()->json(['error' => 'Account Manager ID required'], 400);
        }

        $this->authorizeAccess($amId);

        try {
            $accountManager = AccountManager::with(['witel', 'divisis'])->findOrFail($amId);
            $filters = $this->extractFilters($request, $accountManager);

            // Get comparison scope
            $scope = $request->get('scope', 'witel'); // witel|divisi|global

            $comparisonData = $this->performanceService->getComparisonData(
                $amId,
                $scope,
                $filters
            );

            return response()->json([
                'success' => true,
                'data' => $comparisonData
            ]);

        } catch (\Exception $e) {
            Log::error('AM comparison failed', [
                'am_id' => $amId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to generate comparison',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get historical performance trend
     */
    public function getHistoricalTrend(Request $request)
    {
        $user = Auth::user();
        $amId = $user->role === 'account_manager'
            ? $user->account_manager_id
            : $request->get('am_id');

        if (!$amId) {
            return response()->json(['error' => 'Account Manager ID required'], 400);
        }

        $this->authorizeAccess($amId);

        try {
            $years = $request->get('years', 3); // Default: last 3 years
            $metric = $request->get('metric', 'achievement'); // achievement|revenue

            $trendData = $this->performanceService->getHistoricalTrend(
                $amId,
                $years,
                $metric
            );

            return response()->json([
                'success' => true,
                'data' => $trendData
            ]);

        } catch (\Exception $e) {
            Log::error('AM historical trend failed', [
                'am_id' => $amId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get historical trend',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top performing customers for this AM
     */
    public function getTopCustomers(Request $request)
    {
        $user = Auth::user();
        $amId = $user->role === 'account_manager'
            ? $user->account_manager_id
            : $request->get('am_id');

        if (!$amId) {
            return response()->json(['error' => 'Account Manager ID required'], 400);
        }

        $this->authorizeAccess($amId);

        try {
            $accountManager = AccountManager::with(['witel', 'divisis'])->findOrFail($amId);
            $filters = $this->extractFilters($request, $accountManager);
            $limit = $request->get('limit', 10);

            $topCustomers = $this->getCustomerDataAggregateByCC($amId, $filters)
                ->take($limit);

            return response()->json([
                'success' => true,
                'data' => $topCustomers
            ]);

        } catch (\Exception $e) {
            Log::error('AM top customers failed', [
                'am_id' => $amId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get top customers',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug endpoint (development only)
     */
    public function debug($id)
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        $accountManager = AccountManager::with(['witel', 'divisis', 'amRevenues'])
            ->findOrFail($id);

        $debugData = [
            'account_manager' => [
                'id' => $accountManager->id,
                'nama' => $accountManager->nama,
                'nik' => $accountManager->nik,
                'role' => $accountManager->role,
                'witel_id' => $accountManager->witel_id,
                'witel_name' => $accountManager->witel->nama
            ],
            'divisis' => $accountManager->divisis->map(function($divisi) {
                return [
                    'id' => $divisi->id,
                    'nama' => $divisi->nama,
                    'kode' => $divisi->kode,
                    'is_primary' => $divisi->pivot->is_primary
                ];
            }),
            'revenue_summary' => [
                'total_records' => $accountManager->amRevenues->count(),
                'years' => $accountManager->amRevenues->pluck('tahun')->unique()->sort()->values(),
                'total_customers' => $accountManager->amRevenues->pluck('corporate_customer_id')->unique()->count()
            ],
            'database_checks' => [
                'am_revenues_count' => AmRevenue::where('account_manager_id', $id)->count(),
                'cc_revenues_related' => CcRevenue::whereIn('corporate_customer_id',
                    AmRevenue::where('account_manager_id', $id)->pluck('corporate_customer_id')
                )->count()
            ]
        ];

        return response()->json([
            'success' => true,
            'debug_data' => $debugData,
            'timestamp' => now()->toISOString()
        ]);
    }
}

