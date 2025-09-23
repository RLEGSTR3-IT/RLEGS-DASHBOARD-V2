<?php

namespace App\Services;

use App\Models\AmRevenue;
use App\Models\CcRevenue;
use App\Models\AccountManager;
use Illuminate\Support\Facades\DB;

class PerformanceAnalysisService
{
    /**
     * Get AM performance summary
     */
    public function getAMPerformanceSummary($accountManagerId, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? date('Y');

        // Multi-divisi default logic
        if (!$divisiId) {
            $am = AccountManager::with('divisis')->find($accountManagerId);
            if ($am && $am->divisis->isNotEmpty()) {
                $defaultDivisi = $am->divisis->where('pivot.is_primary', 1)->first()
                    ?? $am->divisis->first();
                $divisiId = $defaultDivisi->id ?? null;
            }
        }

        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun);

        if ($divisiId) {
            $query->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)
                  ->orWhereNull('divisi_id');
            });
        }

        // Filter revenue source dan tipe revenue
        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $query->whereExists(function($subquery) use ($tahun, $revenueSource, $tipeRevenue) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id')
                        ->where('cc_revenues.tahun', $tahun);

                if ($revenueSource && $revenueSource !== 'all') {
                    $subquery->where('cc_revenues.revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $subquery->where('cc_revenues.tipe_revenue', $tipeRevenue);
                }
            });
        }

        $data = $query->selectRaw('
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target,
                MAX(real_revenue) as max_revenue,
                MIN(real_revenue) as min_revenue,
                AVG(real_revenue) as avg_revenue,
                COUNT(*) as total_months,
                MAX(CASE
                    WHEN target_revenue > 0
                    THEN (real_revenue / target_revenue) * 100
                    ELSE 0
                END) as max_achievement,
                AVG(CASE
                    WHEN target_revenue > 0
                    THEN (real_revenue / target_revenue) * 100
                    ELSE 0
                END) as avg_achievement
            ')
            ->first();

        // Get months dengan filtering yang sama
        $maxAchievementMonth = $this->getMaxAchievementMonth($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);
        $maxRevenueMonth = $this->getMaxRevenueMonth($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);

        // Calculate trend
        $trendData = $this->getTrendData($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);
        $trend = $this->calculateTrend($trendData);

        $achievementRate = $data->total_target > 0 ? ($data->total_revenue / $data->total_target) * 100 : 0;

        return [
            'total_revenue' => $data->total_revenue ?? 0,
            'total_target' => $data->total_target ?? 0,
            'achievement_rate' => round($achievementRate, 2),
            'achievement_color' => $this->getAchievementColor($achievementRate),
            'max_achievement' => round($data->max_achievement ?? 0, 2),
            'max_achievement_month' => $maxAchievementMonth,
            'max_revenue' => $data->max_revenue ?? 0,
            'max_revenue_month' => $maxRevenueMonth,
            'avg_achievement' => round($data->avg_achievement ?? 0, 2),
            'avg_revenue' => round($data->avg_revenue ?? 0, 2),
            'trend' => $trend,
            'trend_icon' => $this->getTrendIcon($trend),
            'default_divisi_id' => $divisiId
        ];
    }

    /**
     * Get AM monthly chart data
     */
    public function getAMMonthlyChart($accountManagerId, $tahun = null, $chartMode = 'kombinasi', $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? date('Y');

        // Multi-divisi default logic
        if (!$divisiId) {
            $am = AccountManager::with('divisis')->find($accountManagerId);
            if ($am && $am->divisis->isNotEmpty()) {
                $defaultDivisi = $am->divisis->where('pivot.is_primary', 1)->first()
                    ?? $am->divisis->first();
                $divisiId = $defaultDivisi->id ?? null;
            }
        }

        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun);

        if ($divisiId) {
            $query->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)
                  ->orWhereNull('divisi_id');
            });
        }

        // Filter revenue source dan tipe revenue
        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $query->whereExists(function($subquery) use ($tahun, $revenueSource, $tipeRevenue) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id')
                        ->where('cc_revenues.tahun', $tahun);

                if ($revenueSource && $revenueSource !== 'all') {
                    $subquery->where('cc_revenues.revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $subquery->where('cc_revenues.tipe_revenue', $tipeRevenue);
                }
            });
        }

        $data = $query->selectRaw('
                bulan,
                SUM(real_revenue) as real_revenue,
                SUM(target_revenue) as target_revenue,
                CASE
                    WHEN SUM(target_revenue) > 0
                    THEN (SUM(real_revenue) / SUM(target_revenue)) * 100
                    ELSE 0
                END as achievement
            ')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->map(function($item) use ($chartMode) {
                $achievement = round($item->achievement, 2);
                $result = [
                    'month' => $item->bulan,
                    'month_name' => date('F', mktime(0, 0, 0, $item->bulan, 1)),
                    'achievement_color' => $this->getAchievementColor($achievement)
                ];

                switch ($chartMode) {
                    case 'revenue':
                        $result['real_revenue'] = $item->real_revenue;
                        $result['target_revenue'] = $item->target_revenue;
                        break;

                    case 'achievement':
                        $result['achievement'] = $achievement;
                        break;

                    default: // kombinasi
                        $result['real_revenue'] = $item->real_revenue;
                        $result['target_revenue'] = $item->target_revenue;
                        $result['achievement'] = $achievement;
                        break;
                }

                return $result;
            });

        return $data;
    }

    /**
     * Get performance distribution chart for Admin/Witel
     */
    public function getPerformanceDistribution($tahun = null, $witelId = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? date('Y');

        // Get valid AM IDs
        $amQuery = AccountManager::where('role', 'AM');

        if ($witelId) {
            $amQuery->where('witel_id', $witelId);
        }

        $validAMIds = $amQuery->pluck('id');

        $revenueQuery = AmRevenue::where('tahun', $tahun)
            ->whereIn('account_manager_id', $validAMIds);

        if ($divisiId && $divisiId !== 'all') {
            $revenueQuery->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)
                  ->orWhereNull('divisi_id');
            });
        }

        // Filter revenue source dan tipe revenue
        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $revenueQuery->whereExists(function($subquery) use ($tahun, $revenueSource, $tipeRevenue) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id')
                        ->where('cc_revenues.tahun', $tahun);

                if ($revenueSource && $revenueSource !== 'all') {
                    $subquery->where('cc_revenues.revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $subquery->where('cc_revenues.tipe_revenue', $tipeRevenue);
                }
            });
        }

        $monthlyData = $revenueQuery->selectRaw('
                bulan,
                account_manager_id,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->groupBy('bulan', 'account_manager_id')
            ->get()
            ->groupBy('bulan')
            ->map(function($monthData) {
                $achievements = $monthData->map(function($am) {
                    return $am->total_target > 0
                        ? ($am->total_revenue / $am->total_target) * 100
                        : 0;
                });

                return [
                    'excellent' => $achievements->filter(fn($a) => $a >= 100)->count(), // Hijau
                    'good' => $achievements->filter(fn($a) => $a >= 80 && $a < 100)->count(), // Oranye
                    'poor' => $achievements->filter(fn($a) => $a < 80)->count() // Merah
                ];
            });

        return $monthlyData;
    }

    /**
     * Get AM customer performance data
     */
    public function getAMCustomerPerformance($accountManagerId, $tahun = null, $mode = 'top', $limit = 10, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? date('Y');

        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun);

        if ($divisiId) {
            $query->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)
                  ->orWhereNull('divisi_id');
            });
        }

        // Filter revenue source dan tipe revenue
        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $query->whereExists(function($subquery) use ($tahun, $revenueSource, $tipeRevenue) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id')
                        ->where('cc_revenues.tahun', $tahun);

                if ($revenueSource && $revenueSource !== 'all') {
                    $subquery->where('cc_revenues.revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $subquery->where('cc_revenues.tipe_revenue', $tipeRevenue);
                }
            });
        }

        // Get customer data with aggregation
        $customerData = $query->with(['corporateCustomer', 'divisi'])
            ->selectRaw('
                corporate_customer_id,
                divisi_id,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target,
                CASE
                    WHEN SUM(target_revenue) > 0
                    THEN (SUM(real_revenue) / SUM(target_revenue)) * 100
                    ELSE 0
                END as achievement
            ')
            ->groupBy('corporate_customer_id', 'divisi_id');

        if ($mode === 'top') {
            $customerData = $customerData->orderBy('total_revenue', 'desc');
        } else {
            $customerData = $customerData->orderBy('achievement', 'asc');
        }

        return $customerData->limit($limit)->get()->map(function($item) {
            return (object) [
                'customer_name' => $item->corporateCustomer->nama ?? 'Unknown',
                'nipnas' => $item->corporateCustomer->nipnas ?? 'Unknown',
                'divisi_name' => $item->divisi->nama ?? 'Unknown',
                'total_revenue' => $item->total_revenue,
                'total_target' => $item->total_target,
                'achievement' => round($item->achievement, 2),
                'achievement_color' => $this->getAchievementColor($item->achievement)
            ];
        });
    }

    /**
     * Get comparative analysis
     */
    public function getComparativeAnalysis($accountManagerId, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? date('Y');

        $am = AccountManager::find($accountManagerId);
        if (!$am) {
            return null;
        }

        // AM performance
        $amPerformance = $this->getAMRevenueData($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);

        // Witel average
        $witelAverage = $this->getWitelAverage($am->witel_id, $tahun, $divisiId, $revenueSource, $tipeRevenue);

        // Global average
        $globalAverage = $this->getGlobalAverage($tahun, $divisiId, $revenueSource, $tipeRevenue);

        $amAchievement = $amPerformance->total_target > 0
            ? ($amPerformance->total_revenue / $amPerformance->total_target) * 100
            : 0;

        return [
            'am' => [
                'total_revenue' => $amPerformance->total_revenue ?? 0,
                'total_target' => $amPerformance->total_target ?? 0,
                'achievement' => round($amAchievement, 2),
                'achievement_color' => $this->getAchievementColor($amAchievement)
            ],
            'witel_average' => $witelAverage,
            'global_average' => $globalAverage
        ];
    }

    /**
     * Get year-over-year growth analysis
     */
    public function getYearOverYearGrowth($accountManagerId, $currentYear = null, $previousYear = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $currentYear = $currentYear ?? date('Y');
        $previousYear = $previousYear ?? ($currentYear - 1);

        $currentYearData = $this->getYearRevenueData($accountManagerId, $currentYear, $divisiId, $revenueSource, $tipeRevenue);
        $previousYearData = $this->getYearRevenueData($accountManagerId, $previousYear, $divisiId, $revenueSource, $tipeRevenue);

        $currentRevenue = $currentYearData->total_revenue ?? 0;
        $previousRevenue = $previousYearData->total_revenue ?? 0;

        $growth = $currentRevenue - $previousRevenue;
        $growthPercentage = $previousRevenue > 0 ? ($growth / $previousRevenue) * 100 : 0;

        return [
            'current_year' => $currentYear,
            'previous_year' => $previousYear,
            'current_revenue' => $currentRevenue,
            'previous_revenue' => $previousRevenue,
            'growth_amount' => $growth,
            'growth_percentage' => round($growthPercentage, 2),
            'trend' => $growth > 0 ? 'positive' : ($growth < 0 ? 'negative' : 'flat'),
            'trend_icon' => $this->getTrendIcon($growth > 0 ? 'naik' : ($growth < 0 ? 'turun' : 'stabil'))
        ];
    }

    /**
     * Get performance insights
     */
    public function getPerformanceInsights($accountManagerId, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $summary = $this->getAMPerformanceSummary($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);
        $comparative = $this->getComparativeAnalysis($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);

        $insights = [];

        // Achievement analysis
        if ($summary['achievement_rate'] >= 100) {
            $insights[] = [
                'type' => 'success',
                'message' => 'Target tercapai dengan baik! Achievement rate: ' . $summary['achievement_rate'] . '%',
                'icon' => 'fas fa-trophy'
            ];
        } elseif ($summary['achievement_rate'] >= 80) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Mendekati target. Perlu sedikit peningkatan untuk mencapai 100%',
                'icon' => 'fas fa-chart-line'
            ];
        } else {
            $insights[] = [
                'type' => 'danger',
                'message' => 'Performa di bawah target. Diperlukan strategi peningkatan yang signifikan',
                'icon' => 'fas fa-exclamation-triangle'
            ];
        }

        // Trend analysis
        switch ($summary['trend']) {
            case 'naik':
                $insights[] = [
                    'type' => 'info',
                    'message' => 'Trend positif! Performance menunjukkan peningkatan dalam 3 bulan terakhir',
                    'icon' => 'fas fa-arrow-up'
                ];
                break;
            case 'turun':
                $insights[] = [
                    'type' => 'warning',
                    'message' => 'Trend menurun. Perlu evaluasi strategi dan pendekatan',
                    'icon' => 'fas fa-arrow-down'
                ];
                break;
            default:
                $insights[] = [
                    'type' => 'info',
                    'message' => 'Performance stabil dalam 3 bulan terakhir',
                    'icon' => 'fas fa-minus'
                ];
                break;
        }

        // Comparative analysis
        if ($comparative && isset($comparative['witel_average'])) {
            if ($summary['achievement_rate'] > $comparative['witel_average']['avg_achievement']) {
                $insights[] = [
                    'type' => 'success',
                    'message' => 'Performance di atas rata-rata Witel',
                    'icon' => 'fas fa-star'
                ];
            } else {
                $insights[] = [
                    'type' => 'info',
                    'message' => 'Performance di bawah rata-rata Witel. Ada potensi untuk improvement',
                    'icon' => 'fas fa-info-circle'
                ];
            }
        }

        return $insights;
    }

    /**
     * Get chart mode options
     */
    public function getChartModeOptions()
    {
        return [
            'kombinasi' => 'Revenue + Achievement',
            'revenue' => 'Revenue Saja',
            'achievement' => 'Achievement Saja'
        ];
    }

    /**
     * Private helper methods
     */
    private function getMaxAchievementMonth($accountManagerId, $tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun)
            ->where('target_revenue', '>', 0);

        if ($divisiId) {
            $query->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)->orWhereNull('divisi_id');
            });
        }

        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $query->whereExists(function($subquery) use ($tahun, $revenueSource, $tipeRevenue) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id')
                        ->where('cc_revenues.tahun', $tahun);

                if ($revenueSource && $revenueSource !== 'all') {
                    $subquery->where('cc_revenues.revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $subquery->where('cc_revenues.tipe_revenue', $tipeRevenue);
                }
            });
        }

        $result = $query->selectRaw('bulan, (real_revenue / target_revenue) * 100 as achievement')
            ->orderBy('achievement', 'desc')
            ->first();

        return $result ? date('F', mktime(0, 0, 0, $result->bulan, 1)) : null;
    }

    private function getMaxRevenueMonth($accountManagerId, $tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun);

        if ($divisiId) {
            $query->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)->orWhereNull('divisi_id');
            });
        }

        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $query->whereExists(function($subquery) use ($tahun, $revenueSource, $tipeRevenue) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id')
                        ->where('cc_revenues.tahun', $tahun);

                if ($revenueSource && $revenueSource !== 'all') {
                    $subquery->where('cc_revenues.revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $subquery->where('cc_revenues.tipe_revenue', $tipeRevenue);
                }
            });
        }

        $result = $query->orderBy('real_revenue', 'desc')->first();
        return $result ? date('F', mktime(0, 0, 0, $result->bulan, 1)) : null;
    }

    private function getTrendData($accountManagerId, $tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun)
            ->where('bulan', '>=', max(1, date('n') - 2));

        if ($divisiId) {
            $query->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)->orWhereNull('divisi_id');
            });
        }

        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $query->whereExists(function($subquery) use ($tahun, $revenueSource, $tipeRevenue) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id')
                        ->where('cc_revenues.tahun', $tahun);

                if ($revenueSource && $revenueSource !== 'all') {
                    $subquery->where('cc_revenues.revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $subquery->where('cc_revenues.tipe_revenue', $tipeRevenue);
                }
            });
        }

        return $query->orderBy('bulan')->pluck('real_revenue');
    }

    private function getAMRevenueData($accountManagerId, $tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun);

        if ($divisiId) {
            $query->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)->orWhereNull('divisi_id');
            });
        }

        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $query->whereExists(function($subquery) use ($tahun, $revenueSource, $tipeRevenue) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id')
                        ->where('cc_revenues.tahun', $tahun);

                if ($revenueSource && $revenueSource !== 'all') {
                    $subquery->where('cc_revenues.revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $subquery->where('cc_revenues.tipe_revenue', $tipeRevenue);
                }
            });
        }

        return $query->selectRaw('SUM(real_revenue) as total_revenue, SUM(target_revenue) as total_target')->first();
    }

    private function getWitelAverage($witelId, $tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $amIds = AccountManager::where('role', 'AM')
            ->where('witel_id', $witelId)
            ->pluck('id');

        $query = AmRevenue::whereIn('account_manager_id', $amIds)
            ->where('tahun', $tahun);

        if ($divisiId) {
            $query->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)->orWhereNull('divisi_id');
            });
        }

        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $query->whereExists(function($subquery) use ($tahun, $revenueSource, $tipeRevenue) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id')
                        ->where('cc_revenues.tahun', $tahun);

                if ($revenueSource && $revenueSource !== 'all') {
                    $subquery->where('cc_revenues.revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $subquery->where('cc_revenues.tipe_revenue', $tipeRevenue);
                }
            });
        }

        $result = $query->selectRaw('
                AVG(real_revenue) as avg_revenue,
                AVG(CASE
                    WHEN target_revenue > 0
                    THEN (real_revenue / target_revenue) * 100
                    ELSE 0
                END) as avg_achievement
            ')
            ->first();

        return [
            'avg_revenue' => round($result->avg_revenue ?? 0, 2),
            'avg_achievement' => round($result->avg_achievement ?? 0, 2),
            'achievement_color' => $this->getAchievementColor($result->avg_achievement ?? 0)
        ];
    }

    private function getGlobalAverage($tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $amIds = AccountManager::where('role', 'AM')->pluck('id');

        $query = AmRevenue::whereIn('account_manager_id', $amIds)
            ->where('tahun', $tahun);

        if ($divisiId) {
            $query->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)->orWhereNull('divisi_id');
            });
        }

        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $query->whereExists(function($subquery) use ($tahun, $revenueSource, $tipeRevenue) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id')
                        ->where('cc_revenues.tahun', $tahun);

                if ($revenueSource && $revenueSource !== 'all') {
                    $subquery->where('cc_revenues.revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $subquery->where('cc_revenues.tipe_revenue', $tipeRevenue);
                }
            });
        }

        $result = $query->selectRaw('
                AVG(real_revenue) as avg_revenue,
                AVG(CASE
                    WHEN target_revenue > 0
                    THEN (real_revenue / target_revenue) * 100
                    ELSE 0
                END) as avg_achievement
            ')
            ->first();

        return [
            'avg_revenue' => round($result->avg_revenue ?? 0, 2),
            'avg_achievement' => round($result->avg_achievement ?? 0, 2),
            'achievement_color' => $this->getAchievementColor($result->avg_achievement ?? 0)
        ];
    }

    private function getYearRevenueData($accountManagerId, $tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        return $this->getAMRevenueData($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);
    }

    private function calculateTrend($dataPoints)
    {
        if ($dataPoints->count() < 2) {
            return 'stabil';
        }

        $first = $dataPoints->first();
        $last = $dataPoints->last();

        if ($last > $first * 1.1) {
            return 'naik';
        } elseif ($last < $first * 0.9) {
            return 'turun';
        } else {
            return 'stabil';
        }
    }

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

    private function getTrendIcon($trend)
    {
        switch ($trend) {
            case 'naik':
                return 'fas fa-arrow-up text-success';
            case 'turun':
                return 'fas fa-arrow-down text-danger';
            default:
                return 'fas fa-minus text-muted';
        }
    }
}
