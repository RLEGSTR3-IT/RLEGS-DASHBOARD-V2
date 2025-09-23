<?php

namespace App\Services;

use App\Models\AmRevenue;
use App\Models\CcRevenue;
use App\Models\AccountManager;
use App\Models\Witel;
use App\Models\Divisi;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RankingService
{
    /**
     * Get global ranking for Account Manager with date range support
     */
    public function getGlobalRankingWithDateRange($accountManagerId, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Get all AM performance data
        $allAMsQuery = AmRevenue::select('account_manager_id');

        // Date range filtering
        if ($startDate && $endDate) {
            $startYear = Carbon::parse($startDate)->year;
            $endYear = Carbon::parse($endDate)->year;
            $startMonth = Carbon::parse($startDate)->month;
            $endMonth = Carbon::parse($endDate)->month;

            if ($startYear === $endYear) {
                $allAMsQuery->where('tahun', $startYear)
                          ->whereBetween('bulan', [$startMonth, $endMonth]);
            } else {
                $allAMsQuery->where(function($q) use ($startYear, $endYear, $startMonth, $endMonth) {
                    $q->where(function($q1) use ($startYear, $startMonth) {
                        $q1->where('tahun', $startYear)->where('bulan', '>=', $startMonth);
                    })->orWhere(function($q2) use ($endYear, $endMonth) {
                        $q2->where('tahun', $endYear)->where('bulan', '<=', $endMonth);
                    });
                });
            }
        } else {
            $allAMsQuery->where('tahun', $this->getCurrentDataYear());
        }

        // Divisi filtering
        if ($divisiId) {
            $allAMsQuery->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)
                  ->orWhereNull('divisi_id');
            });
        }

        // Revenue source dan tipe revenue filtering
        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $allAMsQuery->whereExists(function($subquery) use ($startDate, $endDate, $revenueSource, $tipeRevenue) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id');

                if ($startDate && $endDate) {
                    $startYear = Carbon::parse($startDate)->year;
                    $startMonth = Carbon::parse($startDate)->month;
                    $endMonth = Carbon::parse($endDate)->month;

                    $subquery->where('tahun', $startYear)
                             ->whereBetween('bulan', [$startMonth, $endMonth]);
                } else {
                    $subquery->where('tahun', $this->getCurrentDataYear());
                }

                if ($revenueSource && $revenueSource !== 'all') {
                    $subquery->where('revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $subquery->where('tipe_revenue', $tipeRevenue);
                }
            });
        }

        // Get aggregated revenue per AM
        $amPerformances = $allAMsQuery->selectRaw('
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
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Find target AM ranking
        $targetPosition = $amPerformances->search(function($am) use ($accountManagerId) {
            return $am->account_manager_id == $accountManagerId;
        });

        $totalAMs = $amPerformances->count();
        $currentPosition = $targetPosition !== false ? $targetPosition + 1 : null;

        // Get previous period ranking for status comparison
        $previousRanking = $this->getPreviousPeriodGlobalRanking(
            $accountManagerId, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );

        $status = $this->determineRankingStatus($currentPosition, $previousRanking['position']);

        return [
            'current_position' => $currentPosition,
            'total_participants' => $totalAMs,
            'previous_position' => $previousRanking['position'],
            'status' => $status,
            'status_icon' => $this->getRankingStatusIcon($status),
            'achievement_rate' => $targetPosition !== false ? round($amPerformances[$targetPosition]->achievement_rate, 2) : 0,
            'period_context' => $this->getPeriodContext($startDate, $endDate)
        ];
    }

    /**
     * Backward compatibility method
     */
    public function getGlobalRanking($accountManagerId, $tahun = null, $bulan = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        if ($bulan) {
            // Single month
            $startDate = Carbon::createFromDate($tahun, $bulan, 1);
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        } else {
            // Full year
            $startDate = Carbon::createFromDate($tahun, 1, 1);
            $endDate = Carbon::createFromDate($tahun, 12, 31);
        }

        return $this->getGlobalRankingWithDateRange(
            $accountManagerId, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get witel ranking for Account Manager with date range support
     */
    public function getWitelRankingWithDateRange($accountManagerId, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Get AM's witel
        $targetAM = AccountManager::find($accountManagerId);
        if (!$targetAM) {
            return null;
        }

        $witelId = $targetAM->witel_id;

        // Get all AMs in the same witel
        $amIds = AccountManager::where('role', 'AM')
            ->where('witel_id', $witelId)
            ->pluck('id');

        $witelAMsQuery = AmRevenue::whereIn('account_manager_id', $amIds);

        // Date range filtering
        if ($startDate && $endDate) {
            $startYear = Carbon::parse($startDate)->year;
            $endYear = Carbon::parse($endDate)->year;
            $startMonth = Carbon::parse($startDate)->month;
            $endMonth = Carbon::parse($endDate)->month;

            if ($startYear === $endYear) {
                $witelAMsQuery->where('tahun', $startYear)
                            ->whereBetween('bulan', [$startMonth, $endMonth]);
            }
        } else {
            $witelAMsQuery->where('tahun', $this->getCurrentDataYear());
        }

        // Divisi filtering
        if ($divisiId) {
            $witelAMsQuery->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)
                  ->orWhereNull('divisi_id');
            });
        }

        // Revenue source dan tipe revenue filtering
        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $witelAMsQuery->whereExists(function($subquery) use ($startDate, $endDate, $revenueSource, $tipeRevenue) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id');

                if ($startDate && $endDate) {
                    $startYear = Carbon::parse($startDate)->year;
                    $startMonth = Carbon::parse($startDate)->month;
                    $endMonth = Carbon::parse($endDate)->month;

                    $subquery->where('tahun', $startYear)
                             ->whereBetween('bulan', [$startMonth, $endMonth]);
                } else {
                    $subquery->where('tahun', $this->getCurrentDataYear());
                }

                if ($revenueSource && $revenueSource !== 'all') {
                    $subquery->where('revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $subquery->where('tipe_revenue', $tipeRevenue);
                }
            });
        }

        // Get aggregated performance
        $witelPerformances = $witelAMsQuery->selectRaw('
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
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Find target AM ranking
        $targetPosition = $witelPerformances->search(function($am) use ($accountManagerId) {
            return $am->account_manager_id == $accountManagerId;
        });

        $totalWitelAMs = $witelPerformances->count();
        $currentPosition = $targetPosition !== false ? $targetPosition + 1 : null;

        // Get previous period ranking
        $previousRanking = $this->getPreviousPeriodWitelRanking(
            $accountManagerId, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );

        $status = $this->determineRankingStatus($currentPosition, $previousRanking['position']);

        return [
            'current_position' => $currentPosition,
            'total_participants' => $totalWitelAMs,
            'previous_position' => $previousRanking['position'],
            'status' => $status,
            'status_icon' => $this->getRankingStatusIcon($status),
            'achievement_rate' => $targetPosition !== false ? round($witelPerformances[$targetPosition]->achievement_rate, 2) : 0,
            'witel_name' => $targetAM->witel->nama ?? 'Unknown',
            'period_context' => $this->getPeriodContext($startDate, $endDate)
        ];
    }

    /**
     * Backward compatibility method
     */
    public function getWitelRanking($accountManagerId, $tahun = null, $bulan = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        if ($bulan) {
            $startDate = Carbon::createFromDate($tahun, $bulan, 1);
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        } else {
            $startDate = Carbon::createFromDate($tahun, 1, 1);
            $endDate = Carbon::createFromDate($tahun, 12, 31);
        }

        return $this->getWitelRankingWithDateRange(
            $accountManagerId, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get divisi ranking for Account Manager with date range support
     */
    public function getDivisiRankingWithDateRange($accountManagerId, $divisiId, $startDate = null, $endDate = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Get all AMs in the same divisi
        $divisiAMsQuery = DB::table('am_revenues')
            ->join('account_managers', 'am_revenues.account_manager_id', '=', 'account_managers.id')
            ->where('account_managers.role', 'AM')
            ->where(function($q) use ($divisiId) {
                $q->where('am_revenues.divisi_id', $divisiId)
                  ->orWhere('account_managers.divisi_id', $divisiId);
            });

        // Date range filtering
        if ($startDate && $endDate) {
            $startYear = Carbon::parse($startDate)->year;
            $endYear = Carbon::parse($endDate)->year;
            $startMonth = Carbon::parse($startDate)->month;
            $endMonth = Carbon::parse($endDate)->month;

            if ($startYear === $endYear) {
                $divisiAMsQuery->where('am_revenues.tahun', $startYear)
                             ->whereBetween('am_revenues.bulan', [$startMonth, $endMonth]);
            }
        } else {
            $divisiAMsQuery->where('am_revenues.tahun', $this->getCurrentDataYear());
        }

        // Revenue source dan tipe revenue filtering
        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $divisiAMsQuery->whereExists(function($subquery) use ($startDate, $endDate, $revenueSource, $tipeRevenue) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id');

                if ($startDate && $endDate) {
                    $startYear = Carbon::parse($startDate)->year;
                    $startMonth = Carbon::parse($startDate)->month;
                    $endMonth = Carbon::parse($endDate)->month;

                    $subquery->where('tahun', $startYear)
                             ->whereBetween('bulan', [$startMonth, $endMonth]);
                } else {
                    $subquery->where('tahun', $this->getCurrentDataYear());
                }

                if ($revenueSource && $revenueSource !== 'all') {
                    $subquery->where('revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $subquery->where('tipe_revenue', $tipeRevenue);
                }
            });
        }

        // Get aggregated performance
        $divisiPerformances = $divisiAMsQuery->selectRaw('
                am_revenues.account_manager_id,
                account_managers.nama as am_name,
                SUM(am_revenues.real_revenue) as total_revenue,
                SUM(am_revenues.target_revenue) as total_target,
                CASE
                    WHEN SUM(am_revenues.target_revenue) > 0
                    THEN (SUM(am_revenues.real_revenue) / SUM(am_revenues.target_revenue)) * 100
                    ELSE 0
                END as achievement_rate
            ')
            ->groupBy('am_revenues.account_manager_id', 'account_managers.nama')
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Find target AM ranking
        $targetPosition = $divisiPerformances->search(function($am) use ($accountManagerId) {
            return $am->account_manager_id == $accountManagerId;
        });

        $totalDivisiAMs = $divisiPerformances->count();
        $currentPosition = $targetPosition !== false ? $targetPosition + 1 : null;

        // Get divisi information
        $divisi = Divisi::find($divisiId);

        // Get previous period ranking
        $previousRanking = $this->getPreviousPeriodDivisiRanking(
            $accountManagerId, $divisiId, $startDate, $endDate, $revenueSource, $tipeRevenue
        );

        $status = $this->determineRankingStatus($currentPosition, $previousRanking['position']);

        return [
            'current_position' => $currentPosition,
            'total_participants' => $totalDivisiAMs,
            'previous_position' => $previousRanking['position'],
            'status' => $status,
            'status_icon' => $this->getRankingStatusIcon($status),
            'achievement_rate' => $targetPosition !== false ? round($divisiPerformances[$targetPosition]->achievement_rate, 2) : 0,
            'divisi_name' => $divisi->nama ?? 'Unknown',
            'divisi_code' => $divisi->kode ?? 'N/A',
            'period_context' => $this->getPeriodContext($startDate, $endDate)
        ];
    }

    /**
     * Backward compatibility method
     */
    public function getDivisiRanking($accountManagerId, $divisiId, $tahun = null, $bulan = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        if ($bulan) {
            $startDate = Carbon::createFromDate($tahun, $bulan, 1);
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        } else {
            $startDate = Carbon::createFromDate($tahun, 1, 1);
            $endDate = Carbon::createFromDate($tahun, 12, 31);
        }

        return $this->getDivisiRankingWithDateRange(
            $accountManagerId, $divisiId, $startDate, $endDate, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get all divisi rankings for multi-divisi AM
     */
    public function getAllDivisiRankingsWithDateRange($accountManagerId, $startDate = null, $endDate = null, $revenueSource = null, $tipeRevenue = null)
    {
        $am = AccountManager::with('divisis')->find($accountManagerId);

        if (!$am || $am->divisis->isEmpty()) {
            return [];
        }

        $rankings = [];

        foreach ($am->divisis as $divisi) {
            $ranking = $this->getDivisiRankingWithDateRange(
                $accountManagerId, $divisi->id, $startDate, $endDate, $revenueSource, $tipeRevenue
            );

            if ($ranking) {
                $rankings[] = $ranking;
            }
        }

        return $rankings;
    }

    /**
     * Backward compatibility method
     */
    public function getAllDivisiRankings($accountManagerId, $tahun = null, $bulan = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        if ($bulan) {
            $startDate = Carbon::createFromDate($tahun, $bulan, 1);
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        } else {
            $startDate = Carbon::createFromDate($tahun, 1, 1);
            $endDate = Carbon::createFromDate($tahun, 12, 31);
        }

        return $this->getAllDivisiRankingsWithDateRange(
            $accountManagerId, $startDate, $endDate, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get AMs by category for Witel dashboard with date range support
     */
    public function getAMsByCategoryWithDateRange($witelId, $category = null, $limit = 20, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Get all AMs in the witel
        $amIds = AccountManager::where('role', 'AM')
            ->where('witel_id', $witelId)
            ->pluck('id');

        $query = AmRevenue::whereIn('account_manager_id', $amIds)
            ->with(['accountManager.witel', 'accountManager.divisis']);

        // Date range filtering
        if ($startDate && $endDate) {
            $startYear = Carbon::parse($startDate)->year;
            $endYear = Carbon::parse($endDate)->year;
            $startMonth = Carbon::parse($startDate)->month;
            $endMonth = Carbon::parse($endDate)->month;

            if ($startYear === $endYear) {
                $query->where('tahun', $startYear)
                      ->whereBetween('bulan', [$startMonth, $endMonth]);
            }
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        // Divisi filtering
        if ($divisiId && $divisiId !== 'all') {
            $query->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)
                  ->orWhereNull('divisi_id');
            });
        }

        // Revenue source dan tipe revenue filtering
        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $query->whereExists(function($subquery) use ($startDate, $endDate, $revenueSource, $tipeRevenue) {
                $subquery->select(DB::raw(1))
                        ->from('cc_revenues')
                        ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id');

                if ($startDate && $endDate) {
                    $startYear = Carbon::parse($startDate)->year;
                    $startMonth = Carbon::parse($startDate)->month;
                    $endMonth = Carbon::parse($endDate)->month;

                    $subquery->where('tahun', $startYear)
                             ->whereBetween('bulan', [$startMonth, $endMonth]);
                } else {
                    $subquery->where('tahun', $this->getCurrentDataYear());
                }

                if ($revenueSource && $revenueSource !== 'all') {
                    $subquery->where('revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $subquery->where('tipe_revenue', $tipeRevenue);
                }
            });
        }

        // Get aggregated data
        $amPerformances = $query->selectRaw('
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
            ->map(function($item) {
                $am = AccountManager::with(['witel', 'divisis'])->find($item->account_manager_id);

                $item->nama = $am->nama ?? 'Unknown';
                $item->nik = $am->nik ?? 'Unknown';
                $item->witel_nama = $am->witel->nama ?? 'Unknown';
                $item->divisi_list = $am->divisis->pluck('nama')->join(', ');
                $item->achievement_color = $this->getAchievementColor($item->achievement_rate);
                $item->category = $this->categorizeAM($item->achievement_rate);

                return $item;
            });

        // Filter by category if specified
        if ($category && $category !== 'All') {
            $amPerformances = $amPerformances->filter(function($am) use ($category) {
                return $am->category === $category;
            });
        }

        // Sort and limit
        return $amPerformances->sortByDesc('total_revenue')->take($limit)->values();
    }

    /**
     * Backward compatibility method
     */
    public function getAMsByCategory($witelId, $category = null, $limit = 20, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        $startDate = Carbon::createFromDate($tahun, 1, 1);
        $endDate = Carbon::createFromDate($tahun, 12, 31);

        return $this->getAMsByCategoryWithDateRange(
            $witelId, $category, $limit, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get category distribution for Witel dashboard
     */
    public function getCategoryDistributionWithDateRange($witelId, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $amPerformances = $this->getAMsByCategoryWithDateRange(
            $witelId, null, 1000, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );

        $distribution = [
            'Excellent' => $amPerformances->filter(fn($am) => $am->category === 'Excellent')->count(),
            'Good' => $amPerformances->filter(fn($am) => $am->category === 'Good')->count(),
            'Poor' => $amPerformances->filter(fn($am) => $am->category === 'Poor')->count(),
            'total' => $amPerformances->count()
        ];

        // Calculate percentages
        $total = $distribution['total'];
        if ($total > 0) {
            $distribution['excellent_percentage'] = round(($distribution['Excellent'] / $total) * 100, 1);
            $distribution['good_percentage'] = round(($distribution['Good'] / $total) * 100, 1);
            $distribution['poor_percentage'] = round(($distribution['Poor'] / $total) * 100, 1);
        } else {
            $distribution['excellent_percentage'] = 0;
            $distribution['good_percentage'] = 0;
            $distribution['poor_percentage'] = 0;
        }

        return $distribution;
    }

    /**
     * Backward compatibility method
     */
    public function getCategoryDistribution($witelId, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        $startDate = Carbon::createFromDate($tahun, 1, 1);
        $endDate = Carbon::createFromDate($tahun, 12, 31);

        return $this->getCategoryDistributionWithDateRange(
            $witelId, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get category performance summary
     */
    public function getCategoryPerformanceWithDateRange($witelId, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $amPerformances = $this->getAMsByCategoryWithDateRange(
            $witelId, null, 1000, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );

        $categoryStats = [];

        foreach (['Excellent', 'Good', 'Poor'] as $category) {
            $categoryAMs = $amPerformances->filter(fn($am) => $am->category === $category);

            $categoryStats[$category] = [
                'count' => $categoryAMs->count(),
                'total_revenue' => $categoryAMs->sum('total_revenue'),
                'total_target' => $categoryAMs->sum('total_target'),
                'avg_achievement' => $categoryAMs->count() > 0 ? round($categoryAMs->avg('achievement_rate'), 2) : 0,
                'color' => $this->getCategoryColor($category)
            ];
        }

        return $categoryStats;
    }

    /**
     * Backward compatibility method
     */
    public function getCategoryPerformance($witelId, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        $startDate = Carbon::createFromDate($tahun, 1, 1);
        $endDate = Carbon::createFromDate($tahun, 12, 31);

        return $this->getCategoryPerformanceWithDateRange(
            $witelId, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get category options for dropdown
     */
    public function getCategoryOptions()
    {
        return [
            'All' => 'Semua Kategori',
            'Excellent' => 'Excellent (≥100%)',
            'Good' => 'Good (80-99%)',
            'Poor' => 'Poor (<80%)'
        ];
    }

    /**
     * ========================================
     * PRIVATE HELPER METHODS
     * ========================================
     */

    private function getPreviousPeriodGlobalRanking($accountManagerId, $startDate, $endDate, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        if (!$startDate || !$endDate) {
            return ['position' => null];
        }

        // Calculate previous period (same duration)
        $currentStart = Carbon::parse($startDate);
        $currentEnd = Carbon::parse($endDate);
        $duration = $currentStart->diffInDays($currentEnd);

        $previousStart = $currentStart->copy()->subDays($duration + 1);
        $previousEnd = $currentStart->copy()->subDay();

        try {
            $previousRanking = $this->getGlobalRankingWithDateRange(
                $accountManagerId, $previousStart, $previousEnd, $divisiId, $revenueSource, $tipeRevenue
            );

            return ['position' => $previousRanking['current_position']];
        } catch (\Exception $e) {
            return ['position' => null];
        }
    }

    private function getPreviousPeriodWitelRanking($accountManagerId, $startDate, $endDate, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        if (!$startDate || !$endDate) {
            return ['position' => null];
        }

        $currentStart = Carbon::parse($startDate);
        $currentEnd = Carbon::parse($endDate);
        $duration = $currentStart->diffInDays($currentEnd);

        $previousStart = $currentStart->copy()->subDays($duration + 1);
        $previousEnd = $currentStart->copy()->subDay();

        try {
            $previousRanking = $this->getWitelRankingWithDateRange(
                $accountManagerId, $previousStart, $previousEnd, $divisiId, $revenueSource, $tipeRevenue
            );

            return ['position' => $previousRanking['current_position']];
        } catch (\Exception $e) {
            return ['position' => null];
        }
    }

    private function getPreviousPeriodDivisiRanking($accountManagerId, $divisiId, $startDate, $endDate, $revenueSource = null, $tipeRevenue = null)
    {
        if (!$startDate || !$endDate) {
            return ['position' => null];
        }

        $currentStart = Carbon::parse($startDate);
        $currentEnd = Carbon::parse($endDate);
        $duration = $currentStart->diffInDays($currentEnd);

        $previousStart = $currentStart->copy()->subDays($duration + 1);
        $previousEnd = $currentStart->copy()->subDay();

        try {
            $previousRanking = $this->getDivisiRankingWithDateRange(
                $accountManagerId, $divisiId, $previousStart, $previousEnd, $revenueSource, $tipeRevenue
            );

            return ['position' => $previousRanking['current_position']];
        } catch (\Exception $e) {
            return ['position' => null];
        }
    }

    private function determineRankingStatus($currentPosition, $previousPosition)
    {
        if (!$currentPosition || !$previousPosition) {
            return 'tetap';
        }

        if ($currentPosition < $previousPosition) {
            return 'naik';
        } elseif ($currentPosition > $previousPosition) {
            return 'turun';
        } else {
            return 'tetap';
        }
    }

    private function getRankingStatusIcon($status)
    {
        switch ($status) {
            case 'naik':
                return 'fas fa-arrow-up text-success';
            case 'turun':
                return 'fas fa-arrow-down text-danger';
            default:
                return 'fas fa-minus text-muted';
        }
    }

    private function categorizeAM($achievementRate)
    {
        if ($achievementRate >= 100) {
            return 'Excellent';
        } elseif ($achievementRate >= 80) {
            return 'Good';
        } else {
            return 'Poor';
        }
    }

    private function getCategoryColor($category)
    {
        switch ($category) {
            case 'Excellent':
                return 'success';
            case 'Good':
                return 'warning';
            case 'Poor':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    private function getPeriodContext($startDate, $endDate)
    {
        if (!$startDate || !$endDate) {
            return $this->getCurrentDataYear();
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->year === $end->year) {
            if ($start->month === $end->month) {
                return $start->format('M Y');
            } else {
                return $start->format('M') . ' - ' . $end->format('M Y');
            }
        } else {
            return $start->format('M Y') . ' - ' . $end->format('M Y');
        }
    }

    private function getCurrentDataYear()
    {
        static $currentYear = null;

        if ($currentYear === null) {
            $currentYear = CcRevenue::max('tahun') ?? 2025;
        }

        return $currentYear;
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
}