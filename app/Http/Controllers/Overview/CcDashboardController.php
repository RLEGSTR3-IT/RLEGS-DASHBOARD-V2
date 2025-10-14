<?php

namespace App\Http\Controllers\Overview;

use App\Http\Controllers\Controller;
use App\Models\CorporateCustomer;
use App\Models\CcRevenue;
use App\Models\Divisi;
use App\Models\Segment;
use App\Models\Witel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CcDashboardController extends Controller
{
    /**
     * Show Corporate Customer Detail Dashboard
     */
    public function show($id, Request $request)
    {
        try {
            $corporateCustomer = CorporateCustomer::findOrFail($id);

            // Get filters
            $filters = $this->extractFilters($request);

            // Get data terbaru dari cc_revenues untuk info divisi dan segment
            $latestRevenue = CcRevenue::where('corporate_customer_id', $id)
                ->with(['divisi', 'segment'])
                ->orderByDesc('tahun')
                ->orderByDesc('bulan')
                ->first();

            // Profile Data
            $profileData = [
                'id' => $corporateCustomer->id,
                'nama' => $corporateCustomer->nama,
                'nipnas' => $corporateCustomer->nipnas,
                'divisi' => $latestRevenue->divisi ?? null,
                'segment' => $latestRevenue->segment ?? null
            ];

            // Summary Cards
            $cardData = $this->getCardGroupData($id, $filters);

            // Revenue Table Data
            $revenueData = $this->getRevenueTabData($id, $filters);

            // Revenue Analysis (Charts & Metrics)
            $revenueAnalysis = $this->getRevenueAnalysisData($id, $filters);

            // Filter Options
            $filterOptions = $this->getFilterOptions($id);

            Log::info('CC dashboard loaded successfully', [
                'cc_id' => $id,
                'customer_name' => $corporateCustomer->nama,
                'total_revenue' => $cardData['total_revenue'] ?? 0
            ]);

            return view('cc.detailCC', compact(
                'corporateCustomer',
                'profileData',
                'cardData',
                'revenueData',
                'revenueAnalysis',
                'filterOptions',
                'filters'
            ));

        } catch (\Exception $e) {
            Log::error('CC dashboard rendering failed', [
                'cc_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Gagal memuat detail Corporate Customer: ' . $e->getMessage());
        }
    }

    /**
     * Extract filters from request
     */
    private function extractFilters(Request $request)
    {
        // Get tahun dan bulan terakhir dari data
        $defaultTahun = $request->get('tahun', date('Y'));
        $defaultBulanEnd = date('n');

        return [
            'period_type' => $request->get('period_type', 'YTD'),
            'tahun' => $defaultTahun,
            'tipe_revenue' => $request->get('tipe_revenue', 'all'),
            'revenue_source' => $request->get('revenue_source', 'all'),
            'revenue_view_mode' => $request->get('revenue_view_mode', 'detail'),
            'bulan_start' => $request->get('bulan_start', 1),
            'bulan_end' => $request->get('bulan_end', $defaultBulanEnd),
            'chart_tahun' => $request->get('chart_tahun', $defaultTahun),
            'chart_display' => $request->get('chart_display', 'combination'),
            'active_tab' => $request->get('active_tab', 'revenue')
        ];
    }

    /**
     * Get Card Summary Data
     */
    private function getCardGroupData($ccId, $filters)
    {
        $query = CcRevenue::where('corporate_customer_id', $ccId)
            ->where('tahun', $filters['tahun']);

        // Filter tipe revenue
        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        // Filter revenue source
        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        // Filter periode
        if ($filters['period_type'] === 'MTD') {
            $query->where('bulan', $filters['bulan_end']);
        } else { // YTD
            $query->where('bulan', '<=', $filters['bulan_end']);
        }

        $aggregated = $query->selectRaw('
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target,
                COUNT(DISTINCT bulan) as month_count
            ')
            ->first();

        $totalRevenue = $aggregated->total_revenue ?? 0;
        $totalTarget = $aggregated->total_target ?? 0;
        $achievementRate = $totalTarget > 0
            ? round(($totalRevenue / $totalTarget) * 100, 2)
            : 0;

        $periodText = $this->generatePeriodText($filters);

        return [
            'total_revenue' => floatval($totalRevenue),
            'total_target' => floatval($totalTarget),
            'achievement_rate' => $achievementRate,
            'achievement_color' => $this->getAchievementColor($achievementRate),
            'month_count' => intval($aggregated->month_count ?? 0),
            'period_text' => $periodText
        ];
    }

    /**
     * Get Revenue Tab Data
     */
    private function getRevenueTabData($ccId, $filters)
    {
        $viewMode = $filters['revenue_view_mode'];

        $availableYears = CcRevenue::where('corporate_customer_id', $ccId)
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        $revenueData = collect([]);

        switch ($viewMode) {
            case 'agregat_bulan':
                $revenueData = $this->getRevenueDataAggregateByMonth($ccId, $filters);
                break;
            case 'detail':
            default:
                $revenueData = $this->getRevenueDataDetail($ccId, $filters);
                break;
        }

        return [
            'view_mode' => $viewMode,
            'revenues' => $revenueData,
            'available_years' => $availableYears,
            'use_year_picker' => count($availableYears) > 10,
            'tahun' => $filters['tahun'],
            'tipe_revenue' => $filters['tipe_revenue'],
            'revenue_source' => $filters['revenue_source']
        ];
    }

    /**
     * Get Revenue Data Detail (per month)
     */
    private function getRevenueDataDetail($ccId, $filters)
    {
        $query = CcRevenue::where('corporate_customer_id', $ccId)
            ->where('tahun', $filters['tahun'])
            ->with(['divisi', 'segment', 'witelHo', 'witelBill']);

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        $detailData = $query->orderBy('bulan')->get();

        return $detailData->map(function($item) {
            $achievementRate = $item->target_revenue > 0
                ? round(($item->real_revenue / $item->target_revenue) * 100, 2)
                : 0;

            return (object)[
                'bulan' => $item->bulan,
                'bulan_name' => $this->getMonthName($item->bulan),
                'divisi' => $item->divisi->nama ?? 'N/A',
                'segment' => $item->segment->lsegment_ho ?? 'N/A',
                'revenue_source' => $item->revenue_source,
                'tipe_revenue' => $item->tipe_revenue,
                'witel_ho' => $item->witelHo->nama ?? 'N/A',
                'witel_bill' => $item->witelBill->nama ?? 'N/A',
                'revenue' => floatval($item->real_revenue),
                'target' => floatval($item->target_revenue),
                'achievement_rate' => $achievementRate,
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ];
        });
    }

    /**
     * Get Revenue Data Aggregate by Month
     */
    private function getRevenueDataAggregateByMonth($ccId, $filters)
    {
        $query = CcRevenue::where('corporate_customer_id', $ccId)
            ->where('tahun', $filters['tahun']);

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        $bulanStart = $filters['bulan_start'] ?? 1;
        $bulanEnd = $filters['bulan_end'] ?? 12;

        $query->whereBetween('bulan', [$bulanStart, $bulanEnd]);

        $monthlyData = $query->selectRaw('
                bulan,
                SUM(real_revenue) as monthly_revenue,
                SUM(target_revenue) as monthly_target
            ')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        return $monthlyData->map(function($item) {
            $achievementRate = $item->monthly_target > 0
                ? round(($item->monthly_revenue / $item->monthly_target) * 100, 2)
                : 0;

            return (object)[
                'bulan' => $item->bulan,
                'bulan_name' => $this->getMonthName($item->bulan),
                'total_revenue' => floatval($item->monthly_revenue),
                'total_target' => floatval($item->monthly_target),
                'achievement_rate' => $achievementRate,
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ];
        });
    }

    /**
     * Get Revenue Analysis Data
     */
    private function getRevenueAnalysisData($ccId, $filters)
    {
        // Revenue Summary Metrics
        $summary = $this->getRevenueSummary($ccId, $filters);

        // Monthly Chart Data
        $monthlyChart = $this->getMonthlyRevenueChart($ccId, $filters['chart_tahun'] ?? $filters['tahun'], $filters);

        return [
            'summary' => $summary,
            'monthly_chart' => $monthlyChart,
            'chart_filters' => [
                'tahun' => $filters['chart_tahun'] ?? $filters['tahun'],
                'display_mode' => $filters['chart_display'] ?? 'combination'
            ]
        ];
    }

    /**
     * Get Revenue Summary
     */
    private function getRevenueSummary($ccId, $filters)
    {
        $query = CcRevenue::where('corporate_customer_id', $ccId);

        // Apply filters
        if (isset($filters['tipe_revenue']) && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        if (isset($filters['revenue_source']) && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        // All time/scoped summary
        $allTimeData = $query->selectRaw('
                SUM(real_revenue) as total_revenue_all_time,
                SUM(target_revenue) as total_target_all_time
            ')
            ->first();

        // Highest achievement month
        $highestAchievement = (clone $query)->selectRaw('
                tahun,
                bulan,
                real_revenue as revenue,
                target_revenue as target,
                (real_revenue / target_revenue) * 100 as achievement_rate
            ')
            ->having('target', '>', 0)
            ->orderByDesc('achievement_rate')
            ->first();

        // Highest revenue month
        $highestRevenue = (clone $query)->selectRaw('
                tahun,
                bulan,
                real_revenue as revenue
            ')
            ->orderByDesc('revenue')
            ->first();

        // Average achievement
        $monthlyAchievements = (clone $query)->selectRaw('
                tahun,
                bulan,
                (real_revenue / target_revenue) * 100 as achievement_rate
            ')
            ->whereRaw('target_revenue > 0')
            ->get();

        $averageAchievement = $monthlyAchievements->avg('achievement_rate');

        // Trend calculation (last 3 months)
        $trend = $this->calculateTrend($ccId, 3, $filters);

        return [
            'total_revenue_all_time' => floatval($allTimeData->total_revenue_all_time ?? 0),
            'total_target_all_time' => floatval($allTimeData->total_target_all_time ?? 0),
            'overall_achievement_rate' => $allTimeData->total_target_all_time > 0
                ? round(($allTimeData->total_revenue_all_time / $allTimeData->total_target_all_time) * 100, 2)
                : 0,
            'highest_achievement' => [
                'bulan' => $highestAchievement
                    ? $this->getMonthName($highestAchievement->bulan) . ' ' . $highestAchievement->tahun
                    : 'N/A',
                'value' => $highestAchievement ? round($highestAchievement->achievement_rate, 2) : 0
            ],
            'highest_revenue' => [
                'bulan' => $highestRevenue
                    ? $this->getMonthName($highestRevenue->bulan) . ' ' . $highestRevenue->tahun
                    : 'N/A',
                'value' => $highestRevenue ? floatval($highestRevenue->revenue) : 0
            ],
            'average_achievement' => round($averageAchievement ?? 0, 2),
            'trend' => $trend['status'],
            'trend_percentage' => $trend['percentage'],
            'trend_description' => $trend['description']
        ];
    }

    /**
     * Calculate Revenue Trend
     */
    private function calculateTrend($ccId, $months = 3, $filters = [])
    {
        try {
            $query = CcRevenue::where('corporate_customer_id', $ccId);

            if (isset($filters['tipe_revenue']) && $filters['tipe_revenue'] !== 'all') {
                $query->where('tipe_revenue', $filters['tipe_revenue']);
            }

            // Get latest N months
            $latestData = (clone $query)
                ->selectRaw('DISTINCT tahun, bulan')
                ->orderByDesc('tahun')
                ->orderByDesc('bulan')
                ->limit($months)
                ->get();

            if ($latestData->count() < 2) {
                return [
                    'status' => 'insufficient_data',
                    'percentage' => 0,
                    'description' => 'Data tidak cukup untuk analisis tren'
                ];
            }

            $monthFilters = $latestData->map(function($item) {
                return ['tahun' => $item->tahun, 'bulan' => $item->bulan];
            });

            $monthlyData = $query->where(function($q) use ($monthFilters) {
                foreach ($monthFilters as $filter) {
                    $q->orWhere(function($subq) use ($filter) {
                        $subq->where('tahun', $filter['tahun'])
                             ->where('bulan', $filter['bulan']);
                    });
                }
            })
            ->selectRaw('
                tahun,
                bulan,
                (real_revenue / target_revenue) * 100 as achievement_rate
            ')
            ->whereRaw('target_revenue > 0')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get();

            if ($monthlyData->count() < 2) {
                return [
                    'status' => 'insufficient_data',
                    'percentage' => 0,
                    'description' => 'Data tidak cukup'
                ];
            }

            // Calculate percentage change
            $firstValue = $monthlyData->first()->achievement_rate;
            $lastValue = $monthlyData->last()->achievement_rate;
            $percentageChange = $firstValue != 0
                ? (($lastValue - $firstValue) / $firstValue) * 100
                : 0;

            // Determine status
            $status = 'stabil';
            $description = "Revenue relatif stabil dalam {$monthlyData->count()} bulan terakhir";

            if ($percentageChange > 2) {
                $status = 'naik';
                $description = sprintf('Tren meningkat %.1f%% dalam %d bulan terakhir', $percentageChange, $monthlyData->count());
            } elseif ($percentageChange < -2) {
                $status = 'turun';
                $description = sprintf('Tren menurun %.1f%% dalam %d bulan terakhir', abs($percentageChange), $monthlyData->count());
            }

            return [
                'status' => $status,
                'percentage' => round($percentageChange, 2),
                'description' => $description
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate CC trend', [
                'cc_id' => $ccId,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'percentage' => 0,
                'description' => 'Gagal menghitung tren'
            ];
        }
    }

    /**
     * Get Monthly Revenue Chart
     */
    private function getMonthlyRevenueChart($ccId, $tahun, $filters)
    {
        try {
            $query = CcRevenue::where('corporate_customer_id', $ccId)
                ->where('tahun', $tahun);

            if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
                $query->where('tipe_revenue', $filters['tipe_revenue']);
            }

            if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
                $query->where('revenue_source', $filters['revenue_source']);
            }

            $monthlyData = $query->selectRaw('
                    bulan,
                    SUM(real_revenue) as real_revenue,
                    SUM(target_revenue) as target_revenue,
                    CASE
                        WHEN SUM(target_revenue) > 0
                        THEN (SUM(real_revenue) / SUM(target_revenue)) * 100
                        ELSE 0
                    END as achievement_rate
                ')
                ->groupBy('bulan')
                ->orderBy('bulan')
                ->get();

            $labels = [];
            $realRevenue = [];
            $targetRevenue = [];
            $achievementRate = [];

            // Fill all 12 months
            for ($month = 1; $month <= 12; $month++) {
                $monthData = $monthlyData->firstWhere('bulan', $month);

                $labels[] = $this->getShortMonthName($month);
                $realRevenue[] = $monthData ? floatval($monthData->real_revenue) : 0;
                $targetRevenue[] = $monthData ? floatval($monthData->target_revenue) : 0;
                $achievementRate[] = $monthData ? round($monthData->achievement_rate, 2) : 0;
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    'real_revenue' => $realRevenue,
                    'target_revenue' => $targetRevenue,
                    'achievement_rate' => $achievementRate
                ],
                'tahun' => $tahun,
                'display_mode' => $filters['chart_display'] ?? 'combination'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get CC monthly chart data', [
                'cc_id' => $ccId,
                'tahun' => $tahun,
                'error' => $e->getMessage()
            ]);

            return $this->getEmptyChartData();
        }
    }

    /**
     * Get Filter Options
     */
    private function getFilterOptions($ccId)
    {
        $availableYears = CcRevenue::where('corporate_customer_id', $ccId)
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        $bulanOptions = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulanOptions[$i] = $this->getMonthName($i);
        }

        return [
            'period_types' => [
                'YTD' => 'Year to Date',
                'MTD' => 'Month to Date'
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
            ],
            'view_modes' => [
                'agregat_bulan' => 'Agregat per Bulan',
                'detail' => 'Detail (Per Bulan)'
            ],
            'chart_displays' => [
                'revenue' => 'Revenue Saja',
                'achievement' => 'Achievement Saja',
                'combination' => 'Kombinasi (Revenue + Achievement)'
            ],
            'available_years' => $availableYears,
            'use_year_picker' => count($availableYears) > 10,
            'bulan_options' => $bulanOptions,
            'current_month' => date('n')
        ];
    }

    /**
     * HELPER FUNCTIONS
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

    private function generatePeriodText($filters)
    {
        $tahun = $filters['tahun'];
        $bulanEnd = $filters['bulan_end'];
        $monthName = $this->getMonthName($bulanEnd);

        if ($filters['period_type'] === 'MTD') {
            return "Bulan {$monthName} {$tahun}";
        } else {
            return "Januari - {$monthName} {$tahun}";
        }
    }

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

    private function getShortMonthName($monthNumber)
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agt',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        return $months[$monthNumber] ?? 'N/A';
    }

    private function getEmptyChartData()
    {
        $labels = [];
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = $this->getShortMonthName($i);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                'real_revenue' => array_fill(0, 12, 0),
                'target_revenue' => array_fill(0, 12, 0),
                'achievement_rate' => array_fill(0, 12, 0)
            ],
            'tahun' => date('Y'),
            'display_mode' => 'combination'
        ];
    }
}