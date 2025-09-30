<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Overview\DashboardController;
use App\Http\Controllers\Overview\AmDashboardController;
use App\Http\Controllers\Overview\WitelDashboardController;

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

// ===== BASIC ROUTES =====
Route::get('/', function () {
    return view('auth.login');
});

Route::get('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');

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

    // ===== SINGLE DASHBOARD ROUTE (CONDITIONAL RENDERING) =====
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== DASHBOARD API ROUTES =====
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        // Core admin functionality
        Route::get('tab-data', [DashboardController::class, 'getTabData'])->name('tab-data');
        Route::get('export', [DashboardController::class, 'export'])->name('export');

        // Additional endpoints
        Route::get('chart-data', [DashboardController::class, 'getChartData'])->name('chart-data');
        Route::get('revenue-table', [DashboardController::class, 'getRevenueTable'])->name('revenue-table');
        Route::get('summary', [DashboardController::class, 'getSummary'])->name('summary');
        Route::get('insights', [DashboardController::class, 'getPerformanceInsights'])->name('insights');

        // AM specific endpoints
        Route::get('am-performance', [DashboardController::class, 'getAmPerformance'])->name('am-performance');
        Route::get('am-customers', [DashboardController::class, 'getAmCustomers'])->name('am-customers');
        Route::get('am-export', [DashboardController::class, 'exportAm'])->name('am-export');
    });

    // ===== GENERAL API ROUTES =====
    Route::prefix('api')->name('api.')->group(function () {

        Route::get('divisi', function() {
            return response()->json(Divisi::select('id', 'nama', 'kode')->orderBy('nama')->get());
        })->name('divisi');

        Route::get('witel', function() {
            return response()->json(Witel::select('id', 'nama', 'kode')->orderBy('nama')->get());
        })->name('witel');

        Route::get('segments', function() {
            return response()->json(
                Segment::with('divisi:id,nama')
                    ->select('id', 'lsegment_ho', 'ssegment_ho', 'divisi_id')
                    ->orderBy('lsegment_ho')
                    ->get()
            );
        })->name('segments');

        Route::get('revenue-sources', function() {
            return response()->json([
                'all' => 'Semua Source',
                'HO' => 'HO Revenue',
                'BILL' => 'BILL Revenue'
            ]);
        })->name('revenue-sources');

        Route::get('tipe-revenues', function() {
            return response()->json([
                'all' => 'Semua Tipe',
                'REGULER' => 'Revenue Reguler',
                'NGTMA' => 'Revenue NGTMA'
            ]);
        })->name('tipe-revenues');

        Route::get('period-types', function() {
            return response()->json([
                'YTD' => 'Year to Date',
                'MTD' => 'Month to Date'
            ]);
        })->name('period-types');

        Route::get('available-years', function() {
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

        Route::get('health', function() {
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

        Route::get('user-info', function() {
            $user = Auth::user();
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'permissions' => [
                    'can_export' => in_array($user->role, ['admin', 'witel_support', 'account_manager']),
                    'can_view_all_data' => $user->role === 'admin',
                    'can_view_witel_data' => in_array($user->role, ['admin', 'witel_support']),
                    'can_view_am_data' => in_array($user->role, ['admin', 'account_manager'])
                ]
            ]);
        })->name('user-info');
    });

    // ===== DETAIL PAGES =====
    Route::get('account-manager/{id}', [DashboardController::class, 'showAccountManager'])
        ->name('account-manager.show')
        ->where('id', '[0-9]+');

    Route::get('witel/{id}', [DashboardController::class, 'showWitel'])
        ->name('witel.show')
        ->where('id', '[0-9]+');

    Route::get('corporate-customer/{id}', [DashboardController::class, 'showCorporateCustomer'])
        ->name('corporate-customer.show')
        ->where('id', '[0-9]+');

    Route::get('segment/{id}', [DashboardController::class, 'showSegment'])
        ->name('segment.show')
        ->where('id', '[0-9]+');

    // ===== LEGACY EXPORT COMPATIBILITY =====
    Route::get('export', function() {
        return redirect()->route('dashboard.export', request()->all());
    })->name('export');

    // ===== PROFILE ROUTES =====
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
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
    Route::get('debug/routes', function() {
        $routes = collect(Route::getRoutes())->map(function($route) {
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

    Route::get('debug/user', function() {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ],
            'dashboard_route' => route('dashboard'),
            'role_specific_view' => $user->role
        ]);
    })->name('debug.user');
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

require __DIR__.'/auth.php';