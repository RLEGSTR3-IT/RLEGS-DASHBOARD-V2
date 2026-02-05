<?php

namespace App\Http\Controllers\Overview;

use App\Http\Controllers\Controller;
use App\Models\AccountManager;
use App\Models\Divisi;
use App\Models\Witel;
use App\Models\AmRevenue;
use App\Models\CcRevenue;
use App\Models\Segment;
use App\Models\CorporateCustomer;
use App\Models\WitelTargetRevenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * ========================================
     * MAIN DASHBOARD ENTRY POINT
     * ========================================
     */
    public function index(Request $request)
    {
        Log::info("Accessing DashboardController");

        $user = Auth::user();

        try {
            switch ($user->role) {
                case 'admin':
                    Log::info("DashboardController - role is Admin");
                    return $this->handleAdminDashboard($request);
                case 'account_manager':
                    Log::info("DashboardController - role is AM");
                    return $this->handleAmDashboard($request);
                case 'witel':
                    Log::info("DashboardController - role is Witel");
                    return $this->handleWitelDashboard($request, $user->witel_id);
                default:
                    Log::info("DashboardController - unknown role, logout");
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

            return redirect()->back()
                ->with('error', 'Gagal memuat dashboard.');
        }
    }

    /**
     * ========================================
     * HANDLE ADMIN DASHBOARD
     * ========================================
     */
    private function handleAdminDashboard(Request $request)
    {
        try {
            $filters = $this->extractFilters($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            // 1. CARD METRICS - REVENUE SOLD & BILL
            $cardData = $this->calculateCardMetrics($dateRange, $filters);

            // 2. TOP PERFORMANCE SECTION
            $performanceData = [
                'corporate_customer' => $this->getTopCorporateCustomers($dateRange, $filters),
                'account_manager' => $this->getTopAccountManagers($dateRange, $filters),
                'witel' => $this->getTopWitels($dateRange, $filters),
                'segment' => $this->getTopSegments($dateRange, $filters)
            ];

            // 3. MONTHLY REVENUE CHART DATA
            $monthlyRevenueData = $this->getMonthlyRevenueData($dateRange, $filters);

            // 4. AM PERFORMANCE DISTRIBUTION
            $amPerformanceDistribution = $this->getAmPerformanceDistribution($dateRange, $filters);

            // 5. REVENUE TABLE
            $revenueTable = $this->getRevenueTableData($dateRange, $filters);

            // 6. FILTER OPTIONS
            $filterOptions = $this->getFilterOptionsForAdmin();

            return view('dashboard', [
                'cardData' => $cardData,
                'performanceData' => $performanceData,
                'monthlyLabels' => $monthlyRevenueData['labels'],
                'monthlyReal' => $monthlyRevenueData['real'],
                'monthlyTarget' => $monthlyRevenueData['target'],
                'amPerformanceDistribution' => $amPerformanceDistribution,
                'revenueTable' => $revenueTable,
                'filterOptions' => $filterOptions,
                'filters' => $filters
            ]);
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
                'monthlyLabels' => [],
                'monthlyReal' => [],
                'monthlyTarget' => [],
                'amPerformanceDistribution' => ['Hijau' => 0, 'Oranye' => 0, 'Merah' => 0],
                'revenueTable' => []
            ]);
        }
    }

    /**
     * ========================================
     * CALCULATE CARD METRICS
     * ========================================
     */
    private function calculateCardMetrics($dateRange, $filters)
    {
        $startYear = Carbon::parse($dateRange['start'])->year;
        $startMonth = Carbon::parse($dateRange['start'])->month;
        $endYear = Carbon::parse($dateRange['end'])->year;
        $endMonth = Carbon::parse($dateRange['end'])->month;

        // Build base query
        $query = CcRevenue::query();

        // Date filter
        $query->where(function($q) use ($startYear, $startMonth, $endYear, $endMonth) {
            if ($startYear === $endYear) {
                $q->where('tahun', $startYear)
                  ->whereBetween('bulan', [$startMonth, $endMonth]);
            } else {
                $q->where(function($subQ) use ($startYear, $startMonth, $endYear, $endMonth) {
                    $subQ->where('tahun', $startYear)->where('bulan', '>=', $startMonth)
                         ->orWhere(function($innerQ) use ($endYear, $endMonth) {
                             $innerQ->where('tahun', $endYear)->where('bulan', '<=', $endMonth);
                         });
                });
            }
        });

        // Divisi filter
        if ($filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
            $query->where('divisi_id', $filters['divisi_id']);
        }

        // Source data filter (REGULER / NGTMA)
        if ($filters['source_data'] && $filters['source_data'] !== 'all') {
            $query->where('source_data', $filters['source_data']);
        }

        // Calculate metrics
        $metrics = $query->selectRaw('
            SUM(real_revenue_sold) as total_real_sold,
            SUM(real_revenue_bill) as total_real_bill,
            SUM(target_revenue_sold) as total_target_sold
        ')->first();

        // Get target_revenue_bill dari witel_target_revenues
        $targetBillQuery = WitelTargetRevenue::query();
        
        $targetBillQuery->where(function($q) use ($startYear, $startMonth, $endYear, $endMonth) {
            if ($startYear === $endYear) {
                $q->where('tahun', $startYear)
                  ->whereBetween('bulan', [$startMonth, $endMonth]);
            } else {
                $q->where(function($subQ) use ($startYear, $startMonth, $endYear, $endMonth) {
                    $subQ->where('tahun', $startYear)->where('bulan', '>=', $startMonth)
                         ->orWhere(function($innerQ) use ($endYear, $endMonth) {
                             $innerQ->where('tahun', $endYear)->where('bulan', '<=', $endMonth);
                         });
                });
            }
        });

        if ($filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
            $targetBillQuery->where('divisi_id', $filters['divisi_id']);
        }

        $totalTargetBill = $targetBillQuery->sum('target_revenue_bill');

        // Calculate achievement rates
        $achievementSold = ($metrics->total_target_sold > 0) 
            ? ($metrics->total_real_sold / $metrics->total_target_sold) * 100 
            : 0;

        $achievementBill = ($totalTargetBill > 0) 
            ? ($metrics->total_real_bill / $totalTargetBill) * 100 
            : 0;

        return [
            // Revenue Sold
            'total_real_sold' => $metrics->total_real_sold ?? 0,
            'total_real_sold_formatted' => $this->formatCurrencyShort($metrics->total_real_sold ?? 0),
            'total_target_sold' => $metrics->total_target_sold ?? 0,
            'total_target_sold_formatted' => $this->formatCurrencyShort($metrics->total_target_sold ?? 0),
            'achievement_sold' => round($achievementSold, 2),
            'achievement_sold_color' => $this->getAchievementColor($achievementSold),

            // Revenue Bill
            'total_real_bill' => $metrics->total_real_bill ?? 0,
            'total_real_bill_formatted' => $this->formatCurrencyShort($metrics->total_real_bill ?? 0),
            'total_target_bill' => $totalTargetBill,
            'total_target_bill_formatted' => $this->formatCurrencyShort($totalTargetBill),
            'achievement_bill' => round($achievementBill, 2),
            'achievement_bill_color' => $this->getAchievementColor($achievementBill),

            // Period info
            'period_text' => $this->generatePeriodText($filters['period_type'], $dateRange)
        ];
    }

    /**
     * ========================================
     * GET TOP CORPORATE CUSTOMERS
     * ========================================
     * Revenue sesuai tipe_revenue di cc_revenues per bulan
     */
    private function getTopCorporateCustomers($dateRange, $filters, $limit = 10)
    {
        try {
            $startYear = Carbon::parse($dateRange['start'])->year;
            $startMonth = Carbon::parse($dateRange['start'])->month;
            $endYear = Carbon::parse($dateRange['end'])->year;
            $endMonth = Carbon::parse($dateRange['end'])->month;

            $query = DB::table('cc_revenues')
                ->join('corporate_customers', 'cc_revenues.corporate_customer_id', '=', 'corporate_customers.id')
                ->leftJoin('divisi', 'cc_revenues.divisi_id', '=', 'divisi.id')
                ->leftJoin('segments', 'cc_revenues.segment_id', '=', 'segments.id');

            // Date filter
            $query->where(function($q) use ($startYear, $startMonth, $endYear, $endMonth) {
                if ($startYear === $endYear) {
                    $q->where('cc_revenues.tahun', $startYear)
                      ->whereBetween('cc_revenues.bulan', [$startMonth, $endMonth]);
                } else {
                    $q->where(function($subQ) use ($startYear, $startMonth, $endYear, $endMonth) {
                        $subQ->where('cc_revenues.tahun', $startYear)->where('cc_revenues.bulan', '>=', $startMonth)
                             ->orWhere(function($innerQ) use ($endYear, $endMonth) {
                                 $innerQ->where('cc_revenues.tahun', $endYear)->where('cc_revenues.bulan', '<=', $endMonth);
                             });
                    });
                }
            });

            // Divisi filter
            if ($filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
                $query->where('cc_revenues.divisi_id', $filters['divisi_id']);
            }

            // Source data filter
            if ($filters['source_data'] && $filters['source_data'] !== 'all') {
                $query->where('cc_revenues.source_data', $filters['source_data']);
            }

            // Tipe revenue filter (HO/BILL)
            if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
                $query->where('cc_revenues.tipe_revenue', $filters['tipe_revenue']);
            }

            // Calculate revenue berdasarkan tipe_revenue
            $results = $query->selectRaw("
                corporate_customers.id,
                corporate_customers.nama,
                corporate_customers.nipnas,
                divisi.nama as divisi_nama,
                segments.lsegment_ho as segment_nama,
                SUM(CASE 
                    WHEN cc_revenues.tipe_revenue = 'HO' THEN cc_revenues.real_revenue_sold
                    WHEN cc_revenues.tipe_revenue = 'BILL' THEN cc_revenues.real_revenue_bill
                    ELSE 0
                END) as total_revenue,
                SUM(cc_revenues.target_revenue_sold) as total_target
            ")
            ->groupBy('corporate_customers.id', 'corporate_customers.nama', 'corporate_customers.nipnas', 'divisi.nama', 'segments.lsegment_ho')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

            return $results->map(function($item) {
                $achievementRate = $item->total_target > 0 
                    ? ($item->total_revenue / $item->total_target) * 100 
                    : 0;

                return (object) [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'nipnas' => $item->nipnas ?? '-',
                    'divisi_nama' => $item->divisi_nama ?? 'N/A',
                    'segment_nama' => $item->segment_nama ?? 'N/A',
                    'total_revenue' => floatval($item->total_revenue),
                    'total_revenue_formatted' => $this->formatCurrencyShort($item->total_revenue),
                    'total_target' => floatval($item->total_target),
                    'achievement_rate' => round($achievementRate, 2),
                    'achievement_color' => $this->getAchievementColor($achievementRate)
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to get top corporate customers', [
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * ========================================
     * GET TOP ACCOUNT MANAGERS
     * ========================================
     */
    private function getTopAccountManagers($dateRange, $filters, $limit = 10)
    {
        try {
            $startYear = Carbon::parse($dateRange['start'])->year;
            $startMonth = Carbon::parse($dateRange['start'])->month;
            $endYear = Carbon::parse($dateRange['end'])->year;
            $endMonth = Carbon::parse($dateRange['end'])->month;

            $query = DB::table('am_revenues')
                ->join('account_managers', 'am_revenues.account_manager_id', '=', 'account_managers.id')
                ->leftJoin('witel', 'account_managers.witel_id', '=', 'witel.id');

            // Date filter
            $query->where(function($q) use ($startYear, $startMonth, $endYear, $endMonth) {
                if ($startYear === $endYear) {
                    $q->where('am_revenues.tahun', $startYear)
                      ->whereBetween('am_revenues.bulan', [$startMonth, $endMonth]);
                } else {
                    $q->where(function($subQ) use ($startYear, $startMonth, $endYear, $endMonth) {
                        $subQ->where('am_revenues.tahun', $startYear)->where('am_revenues.bulan', '>=', $startMonth)
                             ->orWhere(function($innerQ) use ($endYear, $endMonth) {
                                 $innerQ->where('am_revenues.tahun', $endYear)->where('am_revenues.bulan', '<=', $endMonth);
                             });
                    });
                }
            });

            // Divisi filter
            if ($filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
                $query->where('am_revenues.divisi_id', $filters['divisi_id']);
            }

            $results = $query->selectRaw("
                account_managers.id,
                account_managers.nama,
                account_managers.nik,
                witel.nama as witel_nama,
                SUM(am_revenues.real_revenue) as total_revenue,
                SUM(am_revenues.target_revenue) as total_target
            ")
            ->where('account_managers.role', 'AM')
            ->groupBy('account_managers.id', 'account_managers.nama', 'account_managers.nik', 'witel.nama')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

            return $results->map(function($item) {
                $achievementRate = $item->total_target > 0 
                    ? ($item->total_revenue / $item->total_target) * 100 
                    : 0;

                return (object) [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'nik' => $item->nik,
                    'witel_nama' => $item->witel_nama ?? 'N/A',
                    'total_revenue' => floatval($item->total_revenue),
                    'total_revenue_formatted' => $this->formatCurrencyShort($item->total_revenue),
                    'total_target' => floatval($item->total_target),
                    'achievement_rate' => round($achievementRate, 2),
                    'achievement_color' => $this->getAchievementColor($achievementRate)
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to get top account managers', [
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * ========================================
     * GET TOP WITELS
     * ========================================
     * Real revenue dari real_revenue_bill (witel_bill_id)
     */
    private function getTopWitels($dateRange, $filters, $limit = 10)
    {
        try {
            $startYear = Carbon::parse($dateRange['start'])->year;
            $startMonth = Carbon::parse($dateRange['start'])->month;
            $endYear = Carbon::parse($dateRange['end'])->year;
            $endMonth = Carbon::parse($dateRange['end'])->month;

            // Real revenue dari cc_revenues.real_revenue_bill
            $revenueQuery = DB::table('cc_revenues')
                ->join('witel', 'cc_revenues.witel_bill_id', '=', 'witel.id');

            $revenueQuery->where(function($q) use ($startYear, $startMonth, $endYear, $endMonth) {
                if ($startYear === $endYear) {
                    $q->where('cc_revenues.tahun', $startYear)
                      ->whereBetween('cc_revenues.bulan', [$startMonth, $endMonth]);
                } else {
                    $q->where(function($subQ) use ($startYear, $startMonth, $endYear, $endMonth) {
                        $subQ->where('cc_revenues.tahun', $startYear)->where('cc_revenues.bulan', '>=', $startMonth)
                             ->orWhere(function($innerQ) use ($endYear, $endMonth) {
                                 $innerQ->where('cc_revenues.tahun', $endYear)->where('cc_revenues.bulan', '<=', $endMonth);
                             });
                    });
                }
            });

            if ($filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
                $revenueQuery->where('cc_revenues.divisi_id', $filters['divisi_id']);
            }

            if ($filters['source_data'] && $filters['source_data'] !== 'all') {
                $revenueQuery->where('cc_revenues.source_data', $filters['source_data']);
            }

            $revenueData = $revenueQuery->selectRaw("
                witel.id,
                witel.nama,
                SUM(cc_revenues.real_revenue_bill) as total_real_bill,
                SUM(cc_revenues.target_revenue_sold) as total_target_sold
            ")
            ->groupBy('witel.id', 'witel.nama')
            ->get()
            ->keyBy('id');

            // Target revenue bill dari witel_target_revenues
            $targetBillQuery = DB::table('witel_target_revenues')
                ->where(function($q) use ($startYear, $startMonth, $endYear, $endMonth) {
                    if ($startYear === $endYear) {
                        $q->where('tahun', $startYear)
                          ->whereBetween('bulan', [$startMonth, $endMonth]);
                    } else {
                        $q->where(function($subQ) use ($startYear, $startMonth, $endYear, $endMonth) {
                            $subQ->where('tahun', $startYear)->where('bulan', '>=', $startMonth)
                                 ->orWhere(function($innerQ) use ($endYear, $endMonth) {
                                     $innerQ->where('tahun', $endYear)->where('bulan', '<=', $endMonth);
                                 });
                        });
                    }
                });

            if ($filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
                $targetBillQuery->where('divisi_id', $filters['divisi_id']);
            }

            $targetBillData = $targetBillQuery->selectRaw("
                witel_id,
                SUM(target_revenue_bill) as total_target_bill
            ")
            ->groupBy('witel_id')
            ->get()
            ->keyBy('witel_id');

            // Merge data
            $results = collect([]);
            foreach ($revenueData as $witelId => $revenue) {
                $targetBill = $targetBillData->get($witelId);
                $totalTarget = $revenue->total_target_sold + ($targetBill ? $targetBill->total_target_bill : 0);

                $achievementRate = $totalTarget > 0 
                    ? ($revenue->total_real_bill / $totalTarget) * 100 
                    : 0;

                $results->push((object) [
                    'id' => $witelId,
                    'nama' => $revenue->nama,
                    'total_revenue' => floatval($revenue->total_real_bill),
                    'total_revenue_formatted' => $this->formatCurrencyShort($revenue->total_real_bill),
                    'total_target' => floatval($totalTarget),
                    'achievement_rate' => round($achievementRate, 2),
                    'achievement_color' => $this->getAchievementColor($achievementRate)
                ]);
            }

            return $results->sortByDesc('total_revenue')->take($limit)->values();
        } catch (\Exception $e) {
            Log::error('Failed to get top witels', [
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * ========================================
     * GET TOP SEGMENTS
     * ========================================
     */
    private function getTopSegments($dateRange, $filters, $limit = 10)
    {
        try {
            $startYear = Carbon::parse($dateRange['start'])->year;
            $startMonth = Carbon::parse($dateRange['start'])->month;
            $endYear = Carbon::parse($dateRange['end'])->year;
            $endMonth = Carbon::parse($dateRange['end'])->month;

            $query = DB::table('cc_revenues')
                ->join('segments', 'cc_revenues.segment_id', '=', 'segments.id');

            $query->where(function($q) use ($startYear, $startMonth, $endYear, $endMonth) {
                if ($startYear === $endYear) {
                    $q->where('cc_revenues.tahun', $startYear)
                      ->whereBetween('cc_revenues.bulan', [$startMonth, $endMonth]);
                } else {
                    $q->where(function($subQ) use ($startYear, $startMonth, $endYear, $endMonth) {
                        $subQ->where('cc_revenues.tahun', $startYear)->where('cc_revenues.bulan', '>=', $startMonth)
                             ->orWhere(function($innerQ) use ($endYear, $endMonth) {
                                 $innerQ->where('cc_revenues.tahun', $endYear)->where('cc_revenues.bulan', '<=', $endMonth);
                             });
                    });
                }
            });

            if ($filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
                $query->where('cc_revenues.divisi_id', $filters['divisi_id']);
            }

            if ($filters['source_data'] && $filters['source_data'] !== 'all') {
                $query->where('cc_revenues.source_data', $filters['source_data']);
            }

            if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
                $query->where('cc_revenues.tipe_revenue', $filters['tipe_revenue']);
            }

            $results = $query->selectRaw("
                segments.id,
                segments.lsegment_ho,
                segments.ssegment_ho,
                SUM(CASE 
                    WHEN cc_revenues.tipe_revenue = 'HO' THEN cc_revenues.real_revenue_sold
                    WHEN cc_revenues.tipe_revenue = 'BILL' THEN cc_revenues.real_revenue_bill
                    ELSE 0
                END) as total_revenue,
                SUM(cc_revenues.target_revenue_sold) as total_target
            ")
            ->groupBy('segments.id', 'segments.lsegment_ho', 'segments.ssegment_ho')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

            return $results->map(function($item) {
                $achievementRate = $item->total_target > 0 
                    ? ($item->total_revenue / $item->total_target) * 100 
                    : 0;

                return (object) [
                    'id' => $item->id,
                    'lsegment_ho' => $item->lsegment_ho,
                    'ssegment_ho' => $item->ssegment_ho ?? '-',
                    'total_revenue' => floatval($item->total_revenue),
                    'total_revenue_formatted' => $this->formatCurrencyShort($item->total_revenue),
                    'total_target' => floatval($item->total_target),
                    'achievement_rate' => round($achievementRate, 2),
                    'achievement_color' => $this->getAchievementColor($achievementRate)
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to get top segments', [
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * ========================================
     * GET MONTHLY REVENUE DATA (FOR CHARTS)
     * ========================================
     */
    private function getMonthlyRevenueData($dateRange, $filters)
    {
        $year = Carbon::parse($dateRange['start'])->year;

        $query = CcRevenue::where('tahun', $year);

        // Divisi filter
        if ($filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
            $query->where('divisi_id', $filters['divisi_id']);
        }

        // Source data filter
        if ($filters['source_data'] && $filters['source_data'] !== 'all') {
            $query->where('source_data', $filters['source_data']);
        }

        // Group by month and calculate revenue based on tipe_revenue
        $monthlyData = $query->selectRaw("
            bulan,
            SUM(CASE 
                WHEN tipe_revenue = 'HO' THEN real_revenue_sold
                WHEN tipe_revenue = 'BILL' THEN real_revenue_bill
                ELSE 0
            END) as total_revenue,
            SUM(target_revenue_sold) as total_target
        ")
        ->groupBy('bulan')
        ->orderBy('bulan')
        ->get();

        $monthlyLabels = [];
        $monthlyReal = [];
        $monthlyTarget = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthlyLabels[] = $this->getShortMonthName($month);
            
            $data = $monthlyData->firstWhere('bulan', $month);
            
            // Convert to millions for chart display
            $monthlyReal[] = $data ? round($data->total_revenue / 1000000, 2) : 0;
            $monthlyTarget[] = $data ? round($data->total_target / 1000000, 2) : 0;
        }

        return [
            'labels' => $monthlyLabels,
            'real' => $monthlyReal,
            'target' => $monthlyTarget
        ];
    }

    /**
     * ========================================
     * GET AM PERFORMANCE DISTRIBUTION
     * ========================================
     */
    private function getAmPerformanceDistribution($dateRange, $filters)
    {
        $year = Carbon::parse($dateRange['start'])->year;
        $currentMonth = Carbon::parse($dateRange['end'])->month;

        $query = DB::table('am_revenues')
            ->join('account_managers', 'am_revenues.account_manager_id', '=', 'account_managers.id')
            ->where('am_revenues.tahun', $year)
            ->where('am_revenues.bulan', '<=', $currentMonth)
            ->where('account_managers.role', 'AM');

        // Divisi filter
        if ($filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
            $query->where('am_revenues.divisi_id', $filters['divisi_id']);
        }

        $amAchievements = $query->selectRaw("
            account_managers.id as am_id,
            SUM(am_revenues.real_revenue) as total_revenue,
            SUM(am_revenues.target_revenue) as total_target,
            CASE 
                WHEN SUM(am_revenues.target_revenue) > 0 
                THEN (SUM(am_revenues.real_revenue) / SUM(am_revenues.target_revenue)) * 100
                ELSE 0
            END as achievement_rate
        ")
        ->groupBy('account_managers.id')
        ->get();

        $distribution = [
            'Hijau' => 0,   // >= 100%
            'Oranye' => 0,  // 80-99%
            'Merah' => 0    // < 80%
        ];

        foreach ($amAchievements as $am) {
            $rate = $am->achievement_rate;
            
            if ($rate >= 100) {
                $distribution['Hijau']++;
            } elseif ($rate >= 80) {
                $distribution['Oranye']++;
            } else {
                $distribution['Merah']++;
            }
        }

        return $distribution;
    }

    /**
     * ========================================
     * GET REVENUE TABLE DATA
     * ========================================
     */
    private function getRevenueTableData($dateRange, $filters)
    {
        $year = Carbon::parse($dateRange['start'])->year;
        $startMonth = Carbon::parse($dateRange['start'])->month;
        $endMonth = Carbon::parse($dateRange['end'])->month;

        $query = CcRevenue::where('tahun', $year)
            ->whereBetween('bulan', [$startMonth, $endMonth]);

        // Divisi filter
        if ($filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
            $query->where('divisi_id', $filters['divisi_id']);
        }

        // Source data filter
        if ($filters['source_data'] && $filters['source_data'] !== 'all') {
            $query->where('source_data', $filters['source_data']);
        }

        $monthlyData = $query->selectRaw("
            bulan,
            SUM(CASE 
                WHEN tipe_revenue = 'HO' THEN real_revenue_sold
                WHEN tipe_revenue = 'BILL' THEN real_revenue_bill
                ELSE 0
            END) as realisasi,
            SUM(target_revenue_sold) as target
        ")
        ->groupBy('bulan')
        ->orderBy('bulan')
        ->get();

        // Get target bill from witel_target_revenues
        $targetBillQuery = DB::table('witel_target_revenues')
            ->where('tahun', $year)
            ->whereBetween('bulan', [$startMonth, $endMonth]);

        if ($filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
            $targetBillQuery->where('divisi_id', $filters['divisi_id']);
        }

        $targetBillData = $targetBillQuery->selectRaw("
            bulan,
            SUM(target_revenue_bill) as target_bill
        ")
        ->groupBy('bulan')
        ->get()
        ->keyBy('bulan');

        $tableData = [];

        foreach ($monthlyData as $data) {
            $targetBill = $targetBillData->get($data->bulan);
            $totalTarget = $data->target + ($targetBill ? $targetBill->target_bill : 0);
            
            $achievement = $totalTarget > 0 
                ? ($data->realisasi / $totalTarget) * 100 
                : 0;

            $tableData[] = [
                'bulan' => $this->getMonthName($data->bulan),
                'realisasi' => $data->realisasi,
                'target' => $totalTarget,
                'achievement' => round($achievement, 2),
                'achievement_color' => $this->getAchievementColor($achievement)
            ];
        }

        return $tableData;
    }

    /**
     * ========================================
     * HELPER: Get Month Name
     * ========================================
     */
    private function getMonthName($monthNumber)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$monthNumber] ?? 'Unknown';
    }

    /**
     * ========================================
     * HELPER: Get Short Month Name
     * ========================================
     */
    private function getShortMonthName($monthNumber)
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agt',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        return $months[$monthNumber] ?? 'N/A';
    }

    /**
     * ========================================
     * DELEGATION METHODS
     * ========================================
     */
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
     * ========================================
     * HELPER METHODS
     * ========================================
     */
    private function extractFilters(Request $request)
    {
        return [
            'period_type' => $request->get('period_type', 'YTD'), // YTD, MTD, ALL
            'divisi_id' => $request->get('divisi_id', 'all'),
            'source_data' => $request->get('source_data', 'all'), // REGULER, NGTMA, all
            'tipe_revenue' => $request->get('tipe_revenue', 'all') // HO, BILL, all
        ];
    }

    private function calculateDateRange($periodType)
    {
        $now = Carbon::now();

        switch ($periodType) {
            case 'MTD': // Month to Date
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now
                ];

            case 'YTD': // Year to Date
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now
                ];

            case 'ALL':
            default:
                return [
                    'start' => Carbon::create(2020, 1, 1), // Dari tahun 2020
                    'end' => $now
                ];
        }
    }

    private function generatePeriodText($periodType, $dateRange)
    {
        switch ($periodType) {
            case 'MTD':
                return 'Month to Date (' . Carbon::parse($dateRange['start'])->format('d M Y') . ' - ' . Carbon::parse($dateRange['end'])->format('d M Y') . ')';
            case 'YTD':
                return 'Year to Date (' . Carbon::parse($dateRange['start'])->format('d M Y') . ' - ' . Carbon::parse($dateRange['end'])->format('d M Y') . ')';
            case 'ALL':
                return 'Semua Periode';
            default:
                return 'Unknown Period';
        }
    }

    private function formatCurrencyShort($value)
    {
        $absValue = abs($value);

        if ($absValue >= 1000000000000) { // >= 1 Triliun
            return 'Rp ' . number_format($absValue / 1000000000000, 2, ',', '.') . ' T';
        } elseif ($absValue >= 1000000000) { // >= 1 Miliar
            return 'Rp ' . number_format($absValue / 1000000000, 2, ',', '.') . ' M';
        } elseif ($absValue >= 1000000) { // >= 1 Juta
            return 'Rp ' . number_format($absValue / 1000000, 2, ',', '.') . ' Jt';
        } else {
            return 'Rp ' . number_format($absValue, 0, ',', '.');
        }
    }

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

    private function getFilterOptionsForAdmin()
    {
        return [
            'divisi' => Divisi::orderBy('kode')->get(['id', 'kode', 'nama']),
            'period_types' => [
                'YTD' => 'Year to Date',
                'MTD' => 'Month to Date',
                'ALL' => 'Semua Periode'
            ],
            'source_data' => [
                'all' => 'Semua Tipe',
                'REGULER' => 'REGULER',
                'NGTMA' => 'NGTMA'
            ],
            'tipe_revenue' => [
                'all' => 'Semua',
                'HO' => 'Revenue Sold (HO)',
                'BILL' => 'Revenue Bill'
            ]
        ];
    }

    private function getDefaultFilters()
    {
        return [
            'period_type' => 'YTD',
            'divisi_id' => 'all',
            'source_data' => 'all',
            'tipe_revenue' => 'all'
        ];
    }

    private function getEmptyCardData()
    {
        return [
            'total_real_sold' => 0,
            'total_real_sold_formatted' => 'Rp 0',
            'total_target_sold' => 0,
            'total_target_sold_formatted' => 'Rp 0',
            'achievement_sold' => 0,
            'achievement_sold_color' => 'secondary',
            'total_real_bill' => 0,
            'total_real_bill_formatted' => 'Rp 0',
            'total_target_bill' => 0,
            'total_target_bill_formatted' => 'Rp 0',
            'achievement_bill' => 0,
            'achievement_bill_color' => 'secondary',
            'period_text' => '-'
        ];
    }

    private function getEmptyPerformanceData()
    {
        return [
            'corporate_customer' => collect([]),
            'account_manager' => collect([]),
            'witel' => collect([]),
            'segment' => collect([])
        ];
    }
}