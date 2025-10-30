<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WitelPerformController;
use App\Http\Controllers\CCWitelPerformController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Overview\DashboardController;
use App\Http\Controllers\Overview\AmDashboardController;
use App\Http\Controllers\Overview\WitelDashboardController;
use App\Http\Controllers\Overview\CcDashboardController;
use App\Http\Controllers\RevenueData\RevenueDataController;
use App\Http\Controllers\RevenueData\RevenueImportController;
use App\Http\Controllers\RevenueData\ImportCCController;
use App\Http\Controllers\RevenueData\ImportAMController;


// Laravel Core
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// Models
use App\Models\Divisi;
use App\Models\Witel;
use App\Models\Segment;
use App\Models\CcRevenue;
use App\Models\AccountManager;
use App\Models\CorporateCustomer;

/*
|--------------------------------------------------------------------------
| Web Routes - RLEGS Dashboard V2
|--------------------------------------------------------------------------
*/

// NOTE: Use this command for closure error (Windows): `del bootstrap\cache\routes-*.php`

// ===== BASIC ROUTES =====
Route::get('/', function () {
    return view('auth.login');
});
// no closure version
// Route::view('/', 'auth.login');

// NOTE: ??? wat dis
Route::get('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('guest.logout');

// ===== AUTH ROUTES =====
Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store');

Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
    ->middleware('guest')
    ->name('password.reset');

Route::get('/search-account-managers', [RegisteredUserController::class, 'searchAccountManagers'])
    ->middleware('guest')
    ->name('search.account-managers');

// ===== AUTHENTICATED ROUTES =====
Route::middleware(['auth', 'verified'])->group(function () {

    // ===== MAIN DASHBOARD ROUTE (CONDITIONAL RENDERING) =====
    // Handles admin, account_manager, witel_support roles
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== SIDEBAR ROUTES =====
    Route::view('/revenue', 'revenueData')->name('revenue.index');

    // Witel + CC Performance Routes
    Route::get('/witel-perform', [WitelPerformController::class, 'index'])->name('witel.perform');
    Route::get('/treg3', [CCWitelPerformController::class, 'index'])->name('witel-cc-index');

    // ===== DASHBOARD API ROUTES =====
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        // CC + Witel data fetch
        Route::get('/trend-data', [CCWitelPerformController::class, 'fetchTrendData']);
        Route::get('/witel-performance-data', [CCWitelPerformController::class, 'fetchWitelPerformanceData']);
        Route::get('/customers-leaderboard', [CCWitelPerformController::class, 'fetchOverallCustomersLeaderboard']);

        // Core admin functionality
        Route::get('tab-data', [DashboardController::class, 'getTabData'])->name('tab-data');
        Route::get('export', [DashboardController::class, 'export'])->name('export');
        Route::get('chart-data', [DashboardController::class, 'getChartData'])->name('chart-data');
        Route::get('revenue-table', [DashboardController::class, 'getRevenueTable'])->name('revenue-table');
        Route::get('summary', [DashboardController::class, 'getSummary'])->name('summary');
        Route::get('insights', [DashboardController::class, 'getPerformanceInsights'])->name('insights');

        // AM specific endpoints (when AM is logged in at /dashboard)
        Route::get('am-performance', [DashboardController::class, 'getAmPerformance'])->name('am-performance');
        Route::get('am-customers', [DashboardController::class, 'getAmCustomers'])->name('am-customers');
        Route::get('am-export', [DashboardController::class, 'exportAm'])->name('am-export');
    });

    // ===== ACCOUNT MANAGER ROUTES =====
    Route::prefix('account-manager')->name('account-manager.')->group(function () {
        Route::get('{id}', [AmDashboardController::class, 'show'])
            ->name('show')
            ->where('id', '[0-9]+');

        Route::get('{id}/tab-data', [AmDashboardController::class, 'getTabData'])
            ->name('tab-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/card-data', [AmDashboardController::class, 'getCardData'])
            ->name('card-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/ranking', [AmDashboardController::class, 'getRankingDataAjax'])
            ->name('ranking')
            ->where('id', '[0-9]+');

        Route::get('{id}/chart-data', [AmDashboardController::class, 'getChartData'])
            ->name('chart-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/performance-summary', [AmDashboardController::class, 'getPerformanceSummary'])
            ->name('performance-summary')
            ->where('id', '[0-9]+');

        Route::get('{id}/update-filters', [AmDashboardController::class, 'updateFilters'])
            ->name('update-filters')
            ->where('id', '[0-9]+');

        Route::get('{id}/export', [AmDashboardController::class, 'export'])
            ->name('export')
            ->where('id', '[0-9]+');

        Route::get('{id}/info', [AmDashboardController::class, 'getAmInfo'])
            ->name('info')
            ->where('id', '[0-9]+');

        Route::get('{id}/compare', [AmDashboardController::class, 'compareWithOthers'])
            ->name('compare')
            ->where('id', '[0-9]+');

        Route::get('{id}/trend', [AmDashboardController::class, 'getHistoricalTrend'])
            ->name('trend')
            ->where('id', '[0-9]+');

        Route::get('{id}/top-customers', [AmDashboardController::class, 'getTopCustomers'])
            ->name('top-customers')
            ->where('id', '[0-9]+');

        if (app()->environment('local')) {
            Route::get('{id}/debug', [AmDashboardController::class, 'debug'])
                ->name('debug')
                ->where('id', '[0-9]+');
        }
    });

    // ===== CORPORATE CUSTOMER ROUTES =====
    Route::prefix('corporate-customer')->name('corporate-customer.')->group(function () {
        // Main detail page
        Route::get('{id}', [CcDashboardController::class, 'show'])
            ->name('show')
            ->where('id', '[0-9]+');

        // AJAX endpoints (placeholder untuk future features)
        Route::get('{id}/tab-data', function ($id) {
            return response()->json([
                'message' => 'CC tab data endpoint - coming soon',
                'cc_id' => $id
            ]);
        })->name('tab-data')->where('id', '[0-9]+');

        Route::get('{id}/card-data', function ($id) {
            return response()->json([
                'message' => 'CC card data endpoint - coming soon',
                'cc_id' => $id
            ]);
        })->name('card-data')->where('id', '[0-9]+');

        Route::get('{id}/chart-data', function ($id) {
            return response()->json([
                'message' => 'CC chart data endpoint - coming soon',
                'cc_id' => $id
            ]);
        })->name('chart-data')->where('id', '[0-9]+');

        Route::get('{id}/export', function ($id) {
            return response()->json([
                'message' => 'CC export endpoint - coming soon',
                'cc_id' => $id
            ]);
        })->name('export')->where('id', '[0-9]+');

        Route::get('{id}/info', function ($id) {
            $customer = CorporateCustomer::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'nama' => $customer->nama,
                    'nipnas' => $customer->nipnas
                ]
            ]);
        })->name('info')->where('id', '[0-9]+');
    });

    // ===== WITEL ROUTES (UPDATED) =====
    Route::prefix('witel')->name('witel.')->group(function () {
        // Main detail page
        Route::get('{id}', [WitelDashboardController::class, 'show'])
            ->name('show')
            ->where('id', '[0-9]+');

        // AJAX endpoints (placeholder untuk future features)
        Route::get('{id}/tab-data', function ($id) {
            return response()->json([
                'message' => 'Witel tab data endpoint - coming soon',
                'witel_id' => $id
            ]);
        })->name('tab-data')->where('id', '[0-9]+');

        Route::get('{id}/card-data', function ($id) {
            return response()->json([
                'message' => 'Witel card data endpoint - coming soon',
                'witel_id' => $id
            ]);
        })->name('card-data')->where('id', '[0-9]+');

        Route::get('{id}/chart-data', function ($id) {
            return response()->json([
                'message' => 'Witel chart data endpoint - coming soon',
                'witel_id' => $id
            ]);
        })->name('chart-data')->where('id', '[0-9]+');

        Route::get('{id}/export', function ($id) {
            return response()->json([
                'message' => 'Witel export endpoint - coming soon',
                'witel_id' => $id
            ]);
        })->name('export')->where('id', '[0-9]+');

        Route::get('{id}/info', function ($id) {
            $witel = Witel::findOrFail($id);

            // Count AMs in this witel
            $totalAM = AccountManager::where('witel_id', $id)
                ->where('role', 'AM')
                ->count();

            $totalHOTDA = AccountManager::where('witel_id', $id)
                ->where('role', 'HOTDA')
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $witel->id,
                    'nama' => $witel->nama,
                    'total_am' => $totalAM,
                    'total_hotda' => $totalHOTDA,
                    'total_account_managers' => $totalAM + $totalHOTDA
                ]
            ]);
        })->name('info')->where('id', '[0-9]+');

        Route::get('{id}/performance-summary', function ($id) {
            return response()->json([
                'message' => 'Witel performance summary - coming soon',
                'witel_id' => $id
            ]);
        })->name('performance-summary')->where('id', '[0-9]+');

        Route::get('{id}/top-ams', function ($id) {
            return response()->json([
                'message' => 'Witel top AMs - coming soon',
                'witel_id' => $id
            ]);
        })->name('top-ams')->where('id', '[0-9]+');

        Route::get('{id}/top-customers', function ($id) {
            return response()->json([
                'message' => 'Witel top customers - coming soon',
                'witel_id' => $id
            ]);
        })->name('top-customers')->where('id', '[0-9]+');
    });

    // ===== SEGMENT ROUTES =====
    Route::get('segment/{id}', [DashboardController::class, 'showSegment'])
        ->name('segment.show')
        ->where('id', '[0-9]+');

    // Witel routes, TODO: add gate so only admin can access these routes
    Route::post('/witel-perform/update-charts', [WitelPerformController::class, 'updateCharts'])->name('witel.update-charts');
    Route::post('/witel-perform/filter-by-divisi', [WitelPerformController::class, 'filterByDivisi'])->name('witel.filter-by-divisi');
    Route::post('/witel-perform/filter-by-witel', [WitelPerformController::class, 'filterByWitel'])->name('witel.filter-by-witel');
    Route::post('/witel-perform/filter-by-regional', [WitelPerformController::class, 'filterByRegional'])->name('witel.filter-by-regional');

    // ===== ACCOUNT MANAGER ROUTES =====
    Route::prefix('account-manager')->name('account-manager.')->group(function () {
        // Detail AM page - accessible from leaderboard or direct link
        // URL: /account-manager/{id}
        Route::get('{id}', [AmDashboardController::class, 'show'])
            ->name('show')
            ->where('id', '[0-9]+');

        // AJAX endpoints for AM detail page
        Route::get('{id}/tab-data', [AmDashboardController::class, 'getTabData'])
            ->name('tab-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/card-data', [AmDashboardController::class, 'getCardData'])
            ->name('card-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/ranking', [AmDashboardController::class, 'getRankingDataAjax'])
            ->name('ranking')
            ->where('id', '[0-9]+');

        Route::get('{id}/chart-data', [AmDashboardController::class, 'getChartData'])
            ->name('chart-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/performance-summary', [AmDashboardController::class, 'getPerformanceSummary'])
            ->name('performance-summary')
            ->where('id', '[0-9]+');

        Route::get('{id}/update-filters', [AmDashboardController::class, 'updateFilters'])
            ->name('update-filters')
            ->where('id', '[0-9]+');

        Route::get('{id}/export', [AmDashboardController::class, 'export'])
            ->name('export')
            ->where('id', '[0-9]+');

        // Additional AM endpoints
        Route::get('{id}/info', [AmDashboardController::class, 'getAmInfo'])
            ->name('info')
            ->where('id', '[0-9]+');

        Route::get('{id}/compare', [AmDashboardController::class, 'compareWithOthers'])
            ->name('compare')
            ->where('id', '[0-9]+');

        Route::get('{id}/trend', [AmDashboardController::class, 'getHistoricalTrend'])
            ->name('trend')
            ->where('id', '[0-9]+');

        Route::get('{id}/top-customers', [AmDashboardController::class, 'getTopCustomers'])
            ->name('top-customers')
            ->where('id', '[0-9]+');

        // Debug endpoint (local only)
        if (app()->environment('local')) {
            Route::get('{id}/debug', [AmDashboardController::class, 'debug'])
                ->name('debug')
                ->where('id', '[0-9]+');
        }
    });

    // ===== DETAIL PAGES (OTHER ENTITIES) =====
    Route::get('witel/{id}', [DashboardController::class, 'showWitel'])
        ->name('witel.show')
        ->where('id', '[0-9]+');

    Route::get('corporate-customer/{id}', [DashboardController::class, 'showCorporateCustomer'])
        ->name('corporate-customer.show')
        ->where('id', '[0-9]+');

    Route::get('segment/{id}', [DashboardController::class, 'showSegment'])
        ->name('segment.show')
        ->where('id', '[0-9]+');

    // ===== GENERAL API ROUTES =====
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('divisi', function () {
            return response()->json(
                Divisi::select('id', 'nama', 'kode')
                    ->orderBy('nama')
                    ->get()
            );
        })->name('divisi');

        Route::get('witels', function () {
            return response()->json(
                Witel::select('id', 'nama')
                    ->orderBy('nama')
                    ->get()
            );
        })->name('witels');

        Route::get('segments', function () {
            return response()->json(
                Segment::select('id', 'lsegment_ho')
                    ->distinct()
                    ->orderBy('lsegment_ho')
                    ->get()
            );
        })->name('segments');

        Route::get('revenue-sources', function () {
            return response()->json([
                'all' => 'Semua Source',
                'HO' => 'HO Revenue',
                'BILL' => 'BILL Revenue'
            ]);
        })->name('revenue-sources');

        Route::get('tipe-revenues', function () {
            return response()->json([
                'all' => 'Semua Tipe',
                'REGULER' => 'Revenue Reguler',
                'NGTMA' => 'Revenue NGTMA'
            ]);
        })->name('tipe-revenues');

        Route::get('period-types', function () {
            return response()->json([
                'YTD' => 'Year to Date',
                'MTD' => 'Month to Date'
            ]);
        })->name('period-types');

        Route::get('available-years', function () {
            try {
                $years = CcRevenue::distinct()
                    ->orderBy('tahun', 'desc')
                    ->pluck('tahun')
                    ->filter()
                    ->values()
                    ->toArray();

                if (empty($years)) {
                    $years = [date('Y')];
                }

                return response()->json([
                    'years' => $years,
                    'use_year_picker' => count($years) > 10,
                    'min_year' => min($years),
                    'max_year' => max($years),
                    'current_year' => date('Y')
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to get available years', ['error' => $e->getMessage()]);

                return response()->json([
                    'years' => [date('Y')],
                    'use_year_picker' => false,
                    'min_year' => date('Y'),
                    'max_year' => date('Y'),
                    'current_year' => date('Y')
                ]);
            }
        })->name('available-years');

        Route::get('health', function () {
            try {
                DB::connection()->getPdo();
                $dbStatus = 'connected';
            } catch (\Exception $e) {
                $dbStatus = 'error: ' . $e->getMessage();
            }

            return response()->json([
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'database' => $dbStatus,
                'app_version' => config('app.version', '2.0'),
                'memory_usage' => memory_get_usage(true)
            ]);
        })->name('health');

        Route::get('user-info', function () {
            $user = Auth::user();
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email' => $user->email,
                'role' => $user->role,
                'account_manager_id' => $user->account_manager_id,
                'witel_id' => $user->witel_id,
                'account_manager_id' => $user->account_manager_id,
                'witel_id' => $user->witel_id,
                'permissions' => [
                    'can_export' => in_array($user->role, ['admin', 'witel_support', 'account_manager']),
                    'can_view_all_data' => $user->role === 'admin',
                    'can_view_witel_data' => in_array($user->role, ['admin', 'witel_support']),
                    'can_view_am_data' => in_array($user->role, ['admin', 'account_manager'])
                ]
            ]);
        })->name('user-info');
    });

    // ===== LEGACY EXPORT COMPATIBILITY =====
    Route::get('export', function () {
        return redirect()->route('dashboard.export', request()->all());
    })->name('export');

    // ===== PROFILE ROUTES =====
    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile/photo', [ProfileController::class, 'removePhoto'])->name('profile.remove-photo');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        Route::post('/email/verification-notification', function () {
            request()->user()->sendEmailVerificationNotification();
            return back()->with('verification-link-sent', true);
        })->middleware(['throttle:6,1'])->name('verification.send');
    });


    // ===== SIDEBAR ROUTES =====
    Route::get('/leaderboard', function () {
        return view('leaderboardAM');
    })->name('leaderboard');

    // ===== REVENUE DATA ROUTES (EXTENDED WITH CRUD) =====
    Route::prefix('revenue-data')->name('revenue.')->group(function () {
        // Main Revenue Data Page
        Route::get('/', [RevenueDataController::class, 'index'])->name('data');

        // ===== GET DATA APIs =====
        Route::get('revenue-cc', [RevenueDataController::class, 'getRevenueCC'])->name('api.cc');
        Route::get('revenue-am', [RevenueDataController::class, 'getRevenueAM'])->name('api.am');
        Route::get('data-am', [RevenueDataController::class, 'getDataAM'])->name('api.data.am');
        Route::get('data-cc', [RevenueDataController::class, 'getDataCC'])->name('api.data.cc');
        Route::get('filter-options', [RevenueDataController::class, 'getFilterOptions'])->name('api.filter.options');

        // ===== REVENUE CC CRUD =====
        Route::get('revenue-cc/{id}', [RevenueDataController::class, 'showRevenueCC'])->name('api.show-cc');
        Route::put('revenue-cc/{id}', [RevenueDataController::class, 'updateRevenueCC'])->name('api.update-cc');
        Route::delete('revenue-cc/{id}', [RevenueDataController::class, 'deleteRevenueCC'])->name('api.delete-cc');
        Route::post('bulk-delete-cc-revenue', [RevenueDataController::class, 'bulkDeleteRevenueCC'])->name('api.bulk-delete-cc');
        Route::post('bulk-delete-all-cc-revenue', [RevenueDataController::class, 'bulkDeleteAllRevenueCC'])->name('api.bulk-delete-all-cc');

        // ===== REVENUE AM CRUD =====
        Route::get('revenue-am/{id}', [RevenueDataController::class, 'showRevenueAM'])->name('api.show-am');
        Route::put('revenue-am/{id}', [RevenueDataController::class, 'updateRevenueAM'])->name('api.update-am');
        Route::delete('revenue-am/{id}', [RevenueDataController::class, 'deleteRevenueAM'])->name('api.delete-am');
        Route::post('bulk-delete-am-revenue', [RevenueDataController::class, 'bulkDeleteRevenueAM'])->name('api.bulk-delete-am');
        Route::post('bulk-delete-all-am-revenue', [RevenueDataController::class, 'bulkDeleteAllRevenueAM'])->name('api.bulk-delete-all-am');

        // ===== DATA AM CRUD =====
        Route::get('data-am/{id}', [RevenueDataController::class, 'showDataAM'])->name('api.show-data-am');
        Route::put('data-am/{id}', [RevenueDataController::class, 'updateDataAM'])->name('api.update-data-am');
        Route::delete('data-am/{id}', [RevenueDataController::class, 'deleteDataAM'])->name('api.delete-data-am');
        Route::post('data-am/{id}/change-password', [RevenueDataController::class, 'changePasswordAM'])->name('api.change-password-am');
        Route::post('bulk-delete-data-am', [RevenueDataController::class, 'bulkDeleteDataAM'])->name('api.bulk-delete-data-am');
        Route::post('bulk-delete-all-data-am', [RevenueDataController::class, 'bulkDeleteAllDataAM'])->name('api.bulk-delete-all-data-am');

        // ===== DATA CC CRUD =====
        Route::get('data-cc/{id}', [RevenueDataController::class, 'showDataCC'])->name('api.show-data-cc');
        Route::put('data-cc/{id}', [RevenueDataController::class, 'updateDataCC'])->name('api.update-data-cc');
        Route::delete('data-cc/{id}', [RevenueDataController::class, 'deleteDataCC'])->name('api.delete-data-cc');
        Route::post('bulk-delete-data-cc', [RevenueDataController::class, 'bulkDeleteDataCC'])->name('api.bulk-delete-data-cc');
        Route::post('bulk-delete-all-data-cc', [RevenueDataController::class, 'bulkDeleteAllDataCC'])->name('api.bulk-delete-all-data-cc');

        // ===== IMPORT ROUTES =====
        Route::post('import', [RevenueImportController::class, 'import'])->name('import');
        Route::post('import-revenue-cc', [ImportCCController::class, 'importRevenueCC'])->name('import.cc');
        Route::get('download-error-log/{filename}', [RevenueImportController::class, 'downloadErrorLog'])->name('download.error.log');
    });

    // Legacy route for backward compatibility
    Route::get('/revenue', function () {
        return redirect()->route('revenue.data');
    })->name('revenue.index');
});

// ===== UTILITY ROUTES =====
Route::get('health-check', function () {
    try {
        $dbCheck = DB::connection()->getPdo() ? 'OK' : 'Failed';

        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'app' => [
                'name' => config('app.name'),
                'version' => config('app.version', '2.0'),
                'environment' => app()->environment()
            ],
            'services' => [
                'database' => $dbCheck
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'error' => $e->getMessage()
        ], 500);
    }
})->name('health-check');

// ===== DEBUG ROUTES (DEVELOPMENT ONLY) =====
if (app()->environment('local')) {
    Route::get('debug/routes', function () {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName()
            ];
        });

        return response()->json([
            'total_routes' => $routes->count(),
            'routes' => $routes->sortBy('uri')->values()
        ]);
    })->name('debug.routes');

    Route::get('debug/user', function () {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $dashboardInfo = [];

        switch ($user->role) {
            case 'admin':
                $dashboardInfo = [
                    'view' => 'dashboard.blade.php',
                    'controller' => 'DashboardController::handleAdminDashboard'
                ];
                break;
            case 'account_manager':
                $dashboardInfo = [
                    'view' => 'am.detailAM.blade.php',
                    'controller' => 'AmDashboardController::index',
                    'account_manager_id' => $user->account_manager_id
                ];
                break;
            case 'witel_support':
                $dashboardInfo = [
                    'view' => 'witel.detailWitel.blade.php',
                    'controller' => 'WitelDashboardController::index',
                    'witel_id' => $user->witel_id
                ];
                break;
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'account_manager_id' => $user->account_manager_id,
                'witel_id' => $user->witel_id
            ],
            'dashboard_route' => route('dashboard'),
            'dashboard_info' => $dashboardInfo
        ]);
    })->name('debug.user');

    Route::get('debug/witel-routes', function () {
        return response()->json([
            'main_route' => 'GET /witel/{id}',
            'description' => 'Witel detail page with revenue data from CC and AM sources',
            'example_url' => url('/witel/5'),
            'available_endpoints' => [
                'detail' => '/witel/{id}',
                'info' => '/witel/{id}/info',
                'tab_data' => '/witel/{id}/tab-data (placeholder)',
                'card_data' => '/witel/{id}/card-data (placeholder)',
                'chart_data' => '/witel/{id}/chart-data (placeholder)',
                'export' => '/witel/{id}/export (placeholder)',
                'performance_summary' => '/witel/{id}/performance-summary (placeholder)',
                'top_ams' => '/witel/{id}/top-ams (placeholder)',
                'top_customers' => '/witel/{id}/top-customers (placeholder)'
            ],
            'filters_available' => [
                'tahun' => 'Year filter',
                'tipe_revenue' => 'Revenue type (REGULER/NGTMA)',
                'revenue_source' => 'Revenue source (HO/BILL)',
                'revenue_view_mode' => 'View mode (detail/agregat_bulan)',
                'granularity' => 'Data granularity (account_manager/divisi/corporate_customer)',
                'role_filter' => 'AM role filter (all/AM/HOTDA)',
                'chart_tahun' => 'Chart year',
                'chart_display' => 'Chart display mode (combination/revenue/achievement)'
            ],
            'revenue_logic' => [
                'DPS' => 'Uses witel_bill_id from cc_revenues',
                'DGS_DSS' => 'Uses witel_ho_id from cc_revenues',
                'AM_Revenue' => 'Uses witel_id from am_revenues',
                'combined' => 'Total = CC Revenue + AM Revenue'
            ]
        ]);
    })->name('debug.witel-routes');

    Route::get('debug/cc-routes', function () {
        return response()->json([
            'main_route' => 'GET /corporate-customer/{id}',
            'description' => 'Corporate Customer detail page with revenue data and analysis',
            'example_url' => url('/corporate-customer/1'),
            'available_endpoints' => [
                'detail' => '/corporate-customer/{id}',
                'info' => '/corporate-customer/{id}/info',
                'tab_data' => '/corporate-customer/{id}/tab-data (placeholder)',
                'card_data' => '/corporate-customer/{id}/card-data (placeholder)',
                'chart_data' => '/corporate-customer/{id}/chart-data (placeholder)',
                'export' => '/corporate-customer/{id}/export (placeholder)'
            ],
            'filters_available' => [
                'tahun' => 'Year filter',
                'tipe_revenue' => 'Revenue type (REGULER/NGTMA)',
                'revenue_source' => 'Revenue source (HO/BILL)',
                'revenue_view_mode' => 'View mode (detail/agregat_bulan)',
                'granularity' => 'Data granularity (divisi/segment/account_manager)',
                'chart_tahun' => 'Chart year',
                'chart_display' => 'Chart display mode (combination/revenue/achievement)'
            ]
        ]);
    })->name('debug.cc-routes');

    Route::get('debug/am-routes', function () {
        return response()->json([
            'main_routes' => [
                'dashboard_am' => 'GET /dashboard (when logged in as AM)',
                'detail_am_from_leaderboard' => 'GET /account-manager/{id}'
            ],
            'ajax_endpoints' => [
                'tab_data' => 'GET /account-manager/{id}/tab-data',
                'card_data' => 'GET /account-manager/{id}/card-data',
                'ranking' => 'GET /account-manager/{id}/ranking',
                'chart_data' => 'GET /account-manager/{id}/chart-data',
                'performance_summary' => 'GET /account-manager/{id}/performance-summary',
                'update_filters' => 'GET /account-manager/{id}/update-filters',
                'export' => 'GET /account-manager/{id}/export'
            ],
            'additional_endpoints' => [
                'info' => 'GET /account-manager/{id}/info',
                'compare' => 'GET /account-manager/{id}/compare',
                'trend' => 'GET /account-manager/{id}/trend',
                'top_customers' => 'GET /account-manager/{id}/top-customers'
            ]
        ]);
    })->name('debug.am-routes');

    Route::get('debug/database', function () {
        try {
            $stats = [
                'account_managers' => AccountManager::count(),
                'corporate_customers' => CorporateCustomer::count(),
                'cc_revenues' => CcRevenue::count(),
                'am_revenues' => DB::table('am_revenues')->count(),
                'divisi' => Divisi::count(),
                'witel' => Witel::count(),
                'segments' => Segment::count(),
                'users' => DB::table('users')->count()
            ];

            $latestData = [
                'latest_cc_revenue_year' => CcRevenue::max('tahun'),
                'latest_cc_revenue_month' => CcRevenue::where('tahun', CcRevenue::max('tahun'))->max('bulan'),
                'total_revenue_ytd' => CcRevenue::where('tahun', date('Y'))->sum('real_revenue'),
                'total_target_ytd' => CcRevenue::where('tahun', date('Y'))->sum('target_revenue')
            ];

            return response()->json([
                'status' => 'success',
                'database_stats' => $stats,
                'latest_data' => $latestData,
                'achievement_ytd' => $latestData['total_target_ytd'] > 0
                    ? round(($latestData['total_revenue_ytd'] / $latestData['total_target_ytd']) * 100, 2) . '%'
                    : '0%'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('debug.database');
}

// ===== FALLBACK =====
Route::fallback(function () {
    if (request()->wantsJson()) {
        return response()->json([
            'error' => 'Route not found',
            'available_routes' => [
                'dashboard' => route('dashboard'),
                'health_check' => route('health-check')
            ]
        ], 404);
    }

    return view('errors.404');
});

require __DIR__ . '/auth.php';
