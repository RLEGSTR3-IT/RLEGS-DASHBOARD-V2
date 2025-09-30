<?php

namespace App\Http\Controllers\Overview;

use App\Http\Controllers\Controller;
use App\Services\RevenueCalculationService;
use App\Models\User;
use App\Models\AmRevenue;
use App\Models\CcRevenue;
use App\Models\CorporateCustomer;
use App\Models\Divisi;
use App\Models\Witel;
use App\Models\Segment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AmDashboardController extends Controller
{
    protected $revenueService;

    public function __construct(RevenueCalculationService $revenueService)
    {
        $this->revenueService = $revenueService;

        // Middleware untuk memastikan hanya account_manager yang bisa akses
        $this->middleware(function ($request, $next) {
            if (Auth::user()->role !== 'account_manager') {
                abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
            }
            return $next($request);
        });
    }

    /**
     * Display AM dashboard - simple version using users table
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        try {
            // Simple: langsung pakai data user yang login
            $accountManager = (object) [
                'id' => $user->id,
                'nama' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'nik' => 'N/A', // Bisa ditambah kolom di users table
                'witel' => (object) ['nama' => 'Default Witel'], // Bisa ditambah kolom witel_id di users
                'divisis' => collect([(object) ['nama' => 'Default Divisi']]) // Simple default
            ];

            // Extract filters
            $filters = $this->extractFilters($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            // 1. Performance Summary - simplified (bisa pakai dummy data dulu)
            $performanceSummary = [
                'total_revenue' => 1500000000, // 1.5M dummy
                'total_target' => 2000000000,  // 2M dummy
                'achievement_rate' => 75.0,
                'total_customers' => 25,
                'total_months' => 8,
                'achievement_color' => 'warning',
                'period_text' => $this->generatePeriodText($filters['period_type'], $dateRange)
            ];

            // 2. Corporate Customers - simplified (dummy data)
            $corporateCustomers = collect([
                (object) [
                    'id' => 1,
                    'nama' => 'PT. Telkom Indonesia',
                    'nipnas' => '12345',
                    'segment_nama' => 'Enterprise',
                    'divisi_nama' => 'DGS',
                    'total_revenue' => 500000000,
                    'total_target' => 600000000,
                    'achievement_rate' => 83.3,
                    'achievement_color' => 'warning'
                ],
                (object) [
                    'id' => 2,
                    'nama' => 'PT. Bank Mandiri',
                    'nipnas' => '67890',
                    'segment_nama' => 'Financial',
                    'divisi_nama' => 'DPS',
                    'total_revenue' => 300000000,
                    'total_target' => 400000000,
                    'achievement_rate' => 75.0,
                    'achievement_color' => 'danger'
                ]
            ]);

            // 3. Monthly Performance - simplified (dummy data)
            $monthlyPerformance = collect([
                [
                    'bulan' => 1,
                    'month_name' => 'Januari',
                    'real_revenue' => 150000000,
                    'target_revenue' => 200000000,
                    'achievement_rate' => 75.0,
                    'achievement_color' => 'warning'
                ],
                [
                    'bulan' => 2,
                    'month_name' => 'Februari',
                    'real_revenue' => 180000000,
                    'target_revenue' => 200000000,
                    'achievement_rate' => 90.0,
                    'achievement_color' => 'warning'
                ]
            ]);

            // 4. Filter options
            $filterOptions = $this->getFilterOptions();

            Log::info('AM dashboard loaded successfully', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'filters' => $filters
            ]);

            return view('am.detailAM', compact(
                'accountManager',
                'performanceSummary',
                'corporateCustomers',
                'monthlyPerformance',
                'filters',
                'filterOptions',
                'dateRange'
            ));

        } catch (\Exception $e) {
            Log::error('AM dashboard error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return view('am.detailAM', [
                'error' => 'Gagal memuat dashboard Account Manager. Silakan refresh halaman.',
                'accountManager' => (object) [
                    'nama' => $user->name,
                    'email' => $user->email,
                    'witel' => (object) ['nama' => 'N/A']
                ],
                'performanceSummary' => $this->getEmptyPerformanceSummary(),
                'corporateCustomers' => collect([]),
                'monthlyPerformance' => collect([]),
                'filters' => $this->getDefaultFilters(),
                'filterOptions' => $this->getFilterOptions(),
                'dateRange' => $this->calculateDateRange('YTD')
            ]);
        }
    }

    /**
     * Export AM dashboard data
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        try {
            $filters = $this->extractFilters($request);

            // Simple export response
            return response()->json([
                'message' => 'Export functionality for AM: ' . $user->name,
                'user_id' => $user->id,
                'filters' => $filters,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('AM export failed', [
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
            'revenue_source' => 'all',
            'tipe_revenue' => 'all'
        ];
    }

    private function getEmptyPerformanceSummary()
    {
        return [
            'total_revenue' => 0,
            'total_target' => 0,
            'achievement_rate' => 0,
            'total_customers' => 0,
            'total_months' => 0,
            'achievement_color' => 'secondary',
            'period_text' => 'Tidak ada data'
        ];
    }
}