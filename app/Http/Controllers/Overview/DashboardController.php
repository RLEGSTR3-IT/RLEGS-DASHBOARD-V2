<?php

namespace App\Http\Controllers\Overview;

use App\Http\Controllers\Controller;
use App\Services\RevenueCalculationService;
use App\Services\RankingService;
use App\Services\PerformanceAnalysisService;
use App\Models\AccountManager;
use App\Models\Divisi;
use App\Models\Witel;
use App\Exports\AdminDashboardExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
                    // Redirect ke AM controller dengan preserved filters
                    return redirect()->route('am.dashboard', $request->all());

                case 'witel_support':
                    // Redirect ke Witel controller dengan preserved filters
                    return redirect()->route('witel.dashboard', $request->all());

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
     * ========================================
     * ADMIN DASHBOARD LOGIC (SESUAI REQUIREMENT PDF)
     * ========================================
     */
    private function handleAdminDashboard(Request $request)
    {
        try {
            // EXTRACT FILTERS WITH YTD/MTD SUPPORT
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            // 1. CARD GROUP SECTION (sesuai requirement)
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

            // 2. PERFORMANCE SECTION (4 tabs sesuai requirement)
            $performanceData = [
                'account_manager' => $this->revenueService->getTopAccountManagersWithDateRange(
                    null, 20, $dateRange['start'], $dateRange['end'],
                    $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
                ),
                'witel' => $this->revenueService->getTopWitelsWithDateRange(
                    20, $dateRange['start'], $dateRange['end'],
                    $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
                ),
                'segment' => $this->revenueService->getTopSegmentsWithDateRange(
                    20, $dateRange['start'], $dateRange['end'],
                    $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
                ),
                'corporate_customer' => $this->revenueService->getTopCorporateCustomersWithDateRange(
                    null, 20, $dateRange['start'], $dateRange['end'],
                    $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
                )
            ];

            // Add clickable URLs for navigation
            $this->addClickableUrls($performanceData);

            // 3. VISUALISASI PENDAPATAN BULANAN (line chart + bar chart)
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
                        'witel' => $performanceData['witel']->count(),
                        'segment' => $performanceData['segment']->count(),
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
     * ========================================
     * AJAX TAB SWITCHING (untuk performance section)
     * ========================================
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

            $data = $this->getAdminTabData($tab, $dateRange, $filters);

            return response()->json([
                'success' => true,
                'data' => $data,
                'tab' => $tab,
                'count' => $data->count(),
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

    private function getAdminTabData($tab, $dateRange, $filters)
    {
        switch ($tab) {
            case 'account_manager':
                $data = $this->revenueService->getTopAccountManagersWithDateRange(
                    null, 20, $dateRange['start'], $dateRange['end'],
                    $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
                );
                break;

            case 'witel':
                $data = $this->revenueService->getTopWitelsWithDateRange(
                    20, $dateRange['start'], $dateRange['end'],
                    $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
                );
                break;

            case 'segment':
                $data = $this->revenueService->getTopSegmentsWithDateRange(
                    20, $dateRange['start'], $dateRange['end'],
                    $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
                );
                break;

            case 'corporate_customer':
                $data = $this->revenueService->getTopCorporateCustomersWithDateRange(
                    null, 20, $dateRange['start'], $dateRange['end'],
                    $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
                );
                break;

            default:
                return response()->json(['error' => 'Invalid tab type'], 400);
        }

        $this->addClickableUrlsToCollection($data, $tab);
        return $data;
    }

    /**
     * ========================================
     * UNIFIED EXPORT FUNCTIONALITY
     * ========================================
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
                'filename' => $filename,
                'data_counts' => [
                    'revenue_table' => count($exportData['revenue_table']),
                    'account_managers' => $exportData['performance']['account_managers']->count(),
                    'witels' => $exportData['performance']['witels']->count(),
                    'segments' => $exportData['performance']['segments']->count(),
                    'corporate_customers' => $exportData['performance']['corporate_customers']->count()
                ]
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
            // Sheet 1: Revenue Table
            'revenue_table' => $this->revenueService->getRevenueTableDataWithDateRange(
                $dateRange['start'], $dateRange['end'], null, $filters['divisi_id'],
                $filters['revenue_source'], $filters['tipe_revenue']
            ),

            // Sheet 2-5: Performance Data (sesuai 4 tabs di dashboard)
            'performance' => [
                'account_managers' => $this->revenueService->getTopAccountManagersWithDateRange(
                    null, 200, $dateRange['start'], $dateRange['end'],
                    $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
                ),
                'witels' => $this->revenueService->getTopWitelsWithDateRange(
                    200, $dateRange['start'], $dateRange['end'],
                    $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
                ),
                'segments' => $this->revenueService->getTopSegmentsWithDateRange(
                    200, $dateRange['start'], $dateRange['end'],
                    $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
                ),
                'corporate_customers' => $this->revenueService->getTopCorporateCustomersWithDateRange(
                    null, 200, $dateRange['start'], $dateRange['end'],
                    $filters['divisi_id'], $filters['revenue_source'], $filters['tipe_revenue']
                )
            ],

            // Sheet 6: Summary Statistics
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
        $timestamp = date('Y-m-d_H-i-s');

        return "admin_dashboard_export_{$periodText}{$divisiText}_{$timestamp}.xlsx";
    }

    /**
     * ========================================
     * HELPER METHODS
     * ========================================
     */
    private function extractFiltersWithYtdMtd(Request $request)
    {
        return [
            'period_type' => $request->get('period_type', 'YTD'),
            'divisi_id' => $request->get('divisi_id'),
            'revenue_source' => $request->get('revenue_source', 'all'),
            'tipe_revenue' => $request->get('tipe_revenue', 'all'),
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

    private function addClickableUrlsToCollection($data, $type)
    {
        $routeMapping = [
            'account_manager' => 'account-manager.show',
            'witel' => 'witel.show',
            'segment' => 'segment.show',
            'corporate_customer' => 'corporate-customer.show'
        ];

        if (isset($routeMapping[$type])) {
            $data->each(function($item) use ($routeMapping, $type) {
                $item->detail_url = route($routeMapping[$type], $item->id);
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
}