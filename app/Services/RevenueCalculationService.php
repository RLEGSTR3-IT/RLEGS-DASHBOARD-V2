<?php

namespace App\Services;

use App\Models\CcRevenue;
use App\Models\AmRevenue;
use App\Models\AccountManager;
use App\Models\Witel;
use App\Models\Segment;
use App\Models\CorporateCustomer;
use App\Models\Divisi;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevenueCalculationService
{
    /**
     * Get total revenue data for card group section with date range
     */
    public function getTotalRevenueDataWithDateRange($witelId = null, $divisiId = null, $startDate = null, $endDate = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = CcRevenue::query();

        // Witel filtering
        if ($witelId) {
            $query->where(function($q) use ($witelId) {
                $q->where('witel_ho_id', $witelId)
                  ->orWhere('witel_bill_id', $witelId);
            });
        }

        // Divisi filtering
        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        // Date range filtering untuk YTD/MTD
        if ($startDate && $endDate) {
            $query->where(function($q) use ($startDate, $endDate) {
                $startYear = Carbon::parse($startDate)->year;
                $endYear = Carbon::parse($endDate)->year;
                $startMonth = Carbon::parse($startDate)->month;
                $endMonth = Carbon::parse($endDate)->month;

                if ($startYear === $endYear) {
                    // Same year filtering
                    $q->where('tahun', $startYear)
                      ->whereBetween('bulan', [$startMonth, $endMonth]);
                } else {
                    // Cross year filtering (unlikely for YTD/MTD but just in case)
                    $q->where(function($subq) use ($startYear, $endYear, $startMonth, $endMonth) {
                        $subq->where(function($q1) use ($startYear, $startMonth) {
                            $q1->where('tahun', $startYear)->where('bulan', '>=', $startMonth);
                        })->orWhere(function($q2) use ($endYear, $endMonth) {
                            $q2->where('tahun', $endYear)->where('bulan', '<=', $endMonth);
                        });
                    });
                }
            });
        } else {
            // Default ke tahun terkini dari data (2025)
            $query->where('tahun', $this->getCurrentDataYear());
        }

        // Revenue source filtering (fixed after database update)
        if ($revenueSource && $revenueSource !== 'all') {
            $query->where('revenue_source', $revenueSource);
        }

        // Tipe revenue filtering
        if ($tipeRevenue && $tipeRevenue !== 'all') {
            $query->where('tipe_revenue', $tipeRevenue);
        }

        $totals = $query->selectRaw('
            SUM(real_revenue) as total_revenue,
            SUM(target_revenue) as total_target
        ')->first();

        $achievement = $totals->total_target > 0
            ? ($totals->total_revenue / $totals->total_target) * 100
            : 0;

        return [
            'total_revenue' => $totals->total_revenue ?? 0,
            'total_target' => $totals->total_target ?? 0,
            'achievement_rate' => round($achievement, 2),
            'achievement_color' => $this->getAchievementColor($achievement)
        ];
    }

    /**
     * Backward compatibility method
     */
    public function getTotalRevenueData($witelId = null, $divisiId = null, $tahun = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        // Convert tahun to date range (full year)
        $startDate = Carbon::createFromDate($tahun, 1, 1);
        $endDate = Carbon::createFromDate($tahun, 12, 31);

        return $this->getTotalRevenueDataWithDateRange(
            $witelId, $divisiId, $startDate, $endDate, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get top Account Managers with date range filtering
     */
    public function getTopAccountManagersWithDateRange($witelId = null, $limit = 20, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Base query untuk AM
        $amQuery = AccountManager::where('role', 'AM')
            ->with(['witel', 'divisis']);

        if ($witelId) {
            $amQuery->where('witel_id', $witelId);
        }

        // Subquery untuk revenue dengan date range filtering
        $revenueSubquery = AmRevenue::query();

        // Date range filtering
        if ($startDate && $endDate) {
            $startYear = Carbon::parse($startDate)->year;
            $endYear = Carbon::parse($endDate)->year;
            $startMonth = Carbon::parse($startDate)->month;
            $endMonth = Carbon::parse($endDate)->month;

            if ($startYear === $endYear) {
                $revenueSubquery->where('tahun', $startYear)
                                ->whereBetween('bulan', [$startMonth, $endMonth]);
            } else {
                $revenueSubquery->where(function($q) use ($startYear, $endYear, $startMonth, $endMonth) {
                    $q->where(function($q1) use ($startYear, $startMonth) {
                        $q1->where('tahun', $startYear)->where('bulan', '>=', $startMonth);
                    })->orWhere(function($q2) use ($endYear, $endMonth) {
                        $q2->where('tahun', $endYear)->where('bulan', '<=', $endMonth);
                    });
                });
            }
        } else {
            $revenueSubquery->where('tahun', $this->getCurrentDataYear());
        }

        // Divisi filtering
        if ($divisiId) {
            $revenueSubquery->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)
                  ->orWhereNull('divisi_id');
            });
        }

        // Join dengan cc_revenues untuk filter revenue_source dan tipe_revenue
        if ($revenueSource && $revenueSource !== 'all' || $tipeRevenue && $tipeRevenue !== 'all') {
            $revenueSubquery->whereExists(function($query) use ($startDate, $endDate, $revenueSource, $tipeRevenue) {
                $query->select(DB::raw(1))
                      ->from('cc_revenues')
                      ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id');

                // Date filtering for cc_revenues
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

                if ($revenueSource && $revenueSource !== 'all') {
                    $query->where('revenue_source', $revenueSource);
                }

                if ($tipeRevenue && $tipeRevenue !== 'all') {
                    $query->where('tipe_revenue', $tipeRevenue);
                }
            });
        }

        $revenueData = $revenueSubquery->selectRaw('
                account_manager_id,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->groupBy('account_manager_id')
            ->get()
            ->keyBy('account_manager_id');

        $results = $amQuery->get()->map(function($am) use ($revenueData) {
            $revenue = $revenueData->get($am->id);
            $totalRevenue = $revenue->total_revenue ?? 0;
            $totalTarget = $revenue->total_target ?? 0;
            $achievement = $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0;

            $am->total_revenue = $totalRevenue;
            $am->total_target = $totalTarget;
            $am->achievement_rate = round($achievement, 2);
            $am->achievement_color = $this->getAchievementColor($achievement);
            $am->divisi_list = $am->divisis->pluck('nama')->join(', ');

            return $am;
        })->sortByDesc('total_revenue')->take($limit);

        return $results;
    }

    /**
     * Backward compatibility method
     */
    public function getTopAccountManagers($witelId = null, $limit = 20, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        $startDate = Carbon::createFromDate($tahun, 1, 1);
        $endDate = Carbon::createFromDate($tahun, 12, 31);

        return $this->getTopAccountManagersWithDateRange(
            $witelId, $limit, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get top Witels with date range filtering
     */
    public function getTopWitelsWithDateRange($limit = 20, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = CcRevenue::query();

        // Date range filtering
        if ($startDate && $endDate) {
            $startYear = Carbon::parse($startDate)->year;
            $endYear = Carbon::parse($endDate)->year;
            $startMonth = Carbon::parse($startDate)->month;
            $endMonth = Carbon::parse($endDate)->month;

            if ($startYear === $endYear) {
                $query->where('tahun', $startYear)
                      ->whereBetween('bulan', [$startMonth, $endMonth]);
            } else {
                $query->where(function($q) use ($startYear, $endYear, $startMonth, $endMonth) {
                    $q->where(function($q1) use ($startYear, $startMonth) {
                        $q1->where('tahun', $startYear)->where('bulan', '>=', $startMonth);
                    })->orWhere(function($q2) use ($endYear, $endMonth) {
                        $q2->where('tahun', $endYear)->where('bulan', '<=', $endMonth);
                    });
                });
            }
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        if ($revenueSource && $revenueSource !== 'all') {
            $query->where('revenue_source', $revenueSource);
        }

        if ($tipeRevenue && $tipeRevenue !== 'all') {
            $query->where('tipe_revenue', $tipeRevenue);
        }

        $revenueData = $query->selectRaw('
                CASE
                    WHEN witel_ho_id IS NOT NULL THEN witel_ho_id
                    ELSE witel_bill_id
                END as witel_id,
                COUNT(DISTINCT corporate_customer_id) as total_customers,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->groupBy(DB::raw('CASE WHEN witel_ho_id IS NOT NULL THEN witel_ho_id ELSE witel_bill_id END'))
            ->get()
            ->keyBy('witel_id');

        $results = Witel::all()->map(function($witel) use ($revenueData) {
            $revenue = $revenueData->get($witel->id);
            $totalRevenue = $revenue->total_revenue ?? 0;
            $totalTarget = $revenue->total_target ?? 0;
            $achievement = $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0;

            $witel->total_customers = $revenue->total_customers ?? 0;
            $witel->total_revenue = $totalRevenue;
            $witel->total_target = $totalTarget;
            $witel->achievement_rate = round($achievement, 2);
            $witel->achievement_color = $this->getAchievementColor($achievement);

            return $witel;
        })->sortByDesc('total_revenue')->take($limit);

        return $results;
    }

    /**
     * Get top Segments with date range filtering
     */
    public function getTopSegmentsWithDateRange($limit = 20, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = CcRevenue::query();

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

        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        if ($revenueSource && $revenueSource !== 'all') {
            $query->where('revenue_source', $revenueSource);
        }

        if ($tipeRevenue && $tipeRevenue !== 'all') {
            $query->where('tipe_revenue', $tipeRevenue);
        }

        $revenueData = $query->selectRaw('
                segment_id,
                COUNT(DISTINCT corporate_customer_id) as total_customers,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->groupBy('segment_id')
            ->get()
            ->keyBy('segment_id');

        $results = Segment::with('divisi')->get()->map(function($segment) use ($revenueData) {
            $revenue = $revenueData->get($segment->id);
            $totalRevenue = $revenue->total_revenue ?? 0;
            $totalTarget = $revenue->total_target ?? 0;
            $achievement = $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0;

            $segment->total_customers = $revenue->total_customers ?? 0;
            $segment->total_revenue = $totalRevenue;
            $segment->total_target = $totalTarget;
            $segment->achievement_rate = round($achievement, 2);
            $segment->achievement_color = $this->getAchievementColor($achievement);

            return $segment;
        })->sortByDesc('total_revenue')->take($limit);

        return $results;
    }

    /**
     * Get top Corporate Customers with date range filtering
     */
    public function getTopCorporateCustomersWithDateRange($witelId = null, $limit = 20, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = CcRevenue::with(['corporateCustomer', 'divisi', 'segment']);

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

        if ($witelId) {
            $query->where(function($q) use ($witelId) {
                $q->where('witel_ho_id', $witelId)
                  ->orWhere('witel_bill_id', $witelId);
            });
        }

        if ($divisiId && $divisiId !== 'all') {
            $query->where('divisi_id', $divisiId);
        }

        if ($revenueSource && $revenueSource !== 'all') {
            $query->where('revenue_source', $revenueSource);
        }

        if ($tipeRevenue && $tipeRevenue !== 'all') {
            $query->where('tipe_revenue', $tipeRevenue);
        }

        $results = $query->selectRaw('
                corporate_customer_id,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->with(['corporateCustomer', 'divisi', 'segment'])
            ->groupBy('corporate_customer_id')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($revenue) {
                $achievement = $revenue->total_target > 0
                    ? ($revenue->total_revenue / $revenue->total_target) * 100
                    : 0;

                return (object) [
                    'id' => $revenue->corporate_customer_id,
                    'nama' => $revenue->corporateCustomer->nama ?? 'Unknown',
                    'nipnas' => $revenue->corporateCustomer->nipnas ?? 'Unknown',
                    'divisi_nama' => $revenue->divisi->nama ?? 'Unknown',
                    'segment_nama' => $revenue->segment->lsegment_ho ?? 'Unknown',
                    'total_revenue' => $revenue->total_revenue,
                    'total_target' => $revenue->total_target,
                    'achievement_rate' => round($achievement, 2),
                    'achievement_color' => $this->getAchievementColor($achievement)
                ];
            });

        return $results;
    }

    /**
     * Get monthly revenue data for charts with YTD/MTD context
     */
    public function getMonthlyRevenue($tahun = null, $witelId = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        $query = CcRevenue::where('tahun', $tahun);

        if ($witelId) {
            $query->where(function($q) use ($witelId) {
                $q->where('witel_ho_id', $witelId)
                  ->orWhere('witel_bill_id', $witelId);
            });
        }

        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        if ($revenueSource && $revenueSource !== 'all') {
            $query->where('revenue_source', $revenueSource);
        }

        if ($tipeRevenue && $tipeRevenue !== 'all') {
            $query->where('tipe_revenue', $tipeRevenue);
        }

        return $query->selectRaw('
                bulan,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->map(function($item) {
                $achievement = $item->total_target > 0
                    ? ($item->total_revenue / $item->total_target) * 100
                    : 0;

                return [
                    'month' => $item->bulan,
                    'month_name' => date('F', mktime(0, 0, 0, $item->bulan, 1)),
                    'real_revenue' => $item->total_revenue,
                    'target_revenue' => $item->total_target,
                    'achievement' => round($achievement, 2),
                    'achievement_color' => $this->getAchievementColor($achievement)
                ];
            });
    }

    /**
     * Get revenue table data with date range
     */
    public function getRevenueTableDataWithDateRange($startDate = null, $endDate = null, $witelId = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = CcRevenue::query();

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

        if ($witelId) {
            $query->where(function($q) use ($witelId) {
                $q->where('witel_ho_id', $witelId)
                  ->orWhere('witel_bill_id', $witelId);
            });
        }

        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        if ($revenueSource && $revenueSource !== 'all') {
            $query->where('revenue_source', $revenueSource);
        }

        if ($tipeRevenue && $tipeRevenue !== 'all') {
            $query->where('tipe_revenue', $tipeRevenue);
        }

        return $query->selectRaw('
                bulan,
                tahun,
                SUM(real_revenue) as realisasi,
                SUM(target_revenue) as target,
                CASE
                    WHEN SUM(target_revenue) > 0
                    THEN (SUM(real_revenue) / SUM(target_revenue)) * 100
                    ELSE 0
                END as achievement
            ')
            ->groupBy('bulan', 'tahun')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get()
            ->map(function($item) {
                $achievement = round($item->achievement, 2);
                return [
                    'bulan' => date('F Y', mktime(0, 0, 0, $item->bulan, 1, $item->tahun)),
                    'target' => $item->target,
                    'realisasi' => $item->realisasi,
                    'achievement' => $achievement,
                    'achievement_color' => $this->getAchievementColor($achievement)
                ];
            });
    }

    /**
     * Get AM specific card data with date range
     */
    public function getAMCardDataWithDateRange($accountManagerId, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Multi-divisi default logic
        if (!$divisiId) {
            $am = AccountManager::with('divisis')->find($accountManagerId);
            if ($am && $am->divisis->isNotEmpty()) {
                $defaultDivisi = $am->divisis->where('pivot.is_primary', 1)->first()
                    ?? $am->divisis->first();
                $divisiId = $defaultDivisi->id ?? null;
            }
        }

        $query = AmRevenue::where('account_manager_id', $accountManagerId);

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

        if ($divisiId) {
            $query->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)
                  ->orWhereNull('divisi_id');
            });
        }

        // Filter revenue source dan tipe revenue
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

        $revenueData = $query->selectRaw('
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->first();

        $totalRevenue = $revenueData->total_revenue ?? 0;
        $totalTarget = $revenueData->total_target ?? 0;
        $achievementRate = $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_target' => $totalTarget,
            'achievement_rate' => round($achievementRate, 2),
            'achievement_color' => $this->getAchievementColor($achievementRate),
            'default_divisi_id' => $divisiId
        ];
    }

    /**
     * Get AM revenue data with date range
     */
    public function getAMRevenueDataWithDateRange($accountManagerId, $startDate = null, $endDate = null, $divisiId = null, $viewMode = 'detail', $revenueSource = null, $tipeRevenue = null)
    {
        $query = AmRevenue::where('account_manager_id', $accountManagerId);

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

        if ($divisiId) {
            $query->where(function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId)
                  ->orWhereNull('divisi_id');
            });
        }

        // Filter revenue source dan tipe revenue menggunakan whereExists
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

        switch ($viewMode) {
            case 'agregat':
                return $query->with(['corporateCustomer', 'divisi'])
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
                    ->groupBy('corporate_customer_id', 'divisi_id')
                    ->get()
                    ->map(function($item) {
                        $item->achievement_color = $this->getAchievementColor($item->achievement);
                        $item->customer_name = $item->corporateCustomer->nama ?? 'Unknown';
                        $item->nipnas = $item->corporateCustomer->nipnas ?? 'Unknown';
                        $item->divisi_nama = $item->divisi->nama ?? 'Unknown';
                        return $item;
                    });

            case 'agregat_bulan':
                return $query->selectRaw('
                        bulan,
                        tahun,
                        SUM(real_revenue) as total_revenue,
                        SUM(target_revenue) as total_target,
                        CASE
                            WHEN SUM(target_revenue) > 0
                            THEN (SUM(real_revenue) / SUM(target_revenue)) * 100
                            ELSE 0
                        END as achievement
                    ')
                    ->groupBy('bulan', 'tahun')
                    ->orderBy('tahun')
                    ->orderBy('bulan')
                    ->get()
                    ->map(function($item) {
                        $item->achievement_color = $this->getAchievementColor($item->achievement);
                        $item->month_name = date('F Y', mktime(0, 0, 0, $item->bulan, 1, $item->tahun));
                        return $item;
                    });

            default: // detail
                return $query->with(['corporateCustomer', 'divisi'])
                    ->selectRaw('
                        *,
                        CASE
                            WHEN target_revenue > 0
                            THEN (real_revenue / target_revenue) * 100
                            ELSE 0
                        END as achievement
                    ')
                    ->orderBy('tahun')
                    ->orderBy('bulan')
                    ->get()
                    ->map(function($item) {
                        $item->achievement_color = $this->getAchievementColor($item->achievement);
                        $item->month_name = date('F Y', mktime(0, 0, 0, $item->bulan, 1, $item->tahun));
                        $item->customer_name = $item->corporateCustomer->nama ?? 'Unknown';
                        $item->nipnas = $item->corporateCustomer->nipnas ?? 'Unknown';
                        $item->divisi_nama = $item->divisi->nama ?? 'Unknown';
                        return $item;
                    });
        }
    }

    /**
     * Get available years from actual data
     */
    public function getAvailableYears()
    {
        $years = CcRevenue::distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        if (empty($years)) {
            // Fallback ke tahun terkini jika tidak ada data
            $years = [$this->getCurrentDataYear()];
        }

        return [
            'years' => $years,
            'current_year' => $this->getCurrentDataYear(),
            'use_year_picker' => count($years) > 10,
            'min_year' => min($years),
            'max_year' => max($years)
        ];
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
     * Get revenue source options
     */
    public function getRevenueSourceOptions()
    {
        return [
            'all' => 'Semua Source',
            'HO' => 'HO Revenue',
            'BILL' => 'BILL Revenue'
        ];
    }

    /**
     * Get tipe revenue options
     */
    public function getTipeRevenueOptions()
    {
        return [
            'all' => 'Semua Tipe',
            'REGULER' => 'Revenue Reguler',
            'NGTMA' => 'Revenue NGTMA'
        ];
    }

    /**
     * Get achievement color based on rate
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
}