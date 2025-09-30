<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Overview\DashboardController;
// TODO: Import AM and Witel Dashboard Controllers when ready
// use App\Http\Controllers\Overview\AmDashboardController;
// use App\Http\Controllers\Overview\WitelDashboardController;

// Laravel Core
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
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

    // ===== MAIN DASHBOARD (CONDITIONAL RENDERING) =====
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== DASHBOARD API ROUTES =====
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        // Core admin functionality
        Route::get('tab-data', [DashboardController::class, 'getTabData'])->name('tab-data');
        Route::get('export', [DashboardController::class, 'export'])->name('export');

        // Additional endpoints (add as needed)
        Route::get('chart-data', [DashboardController::class, 'getChartData'])->name('chart-data');
        Route::get('revenue-table', [DashboardController::class, 'getRevenueTable'])->name('revenue-table');
        Route::get('summary', [DashboardController::class, 'getSummary'])->name('summary');
        Route::get('insights', [DashboardController::class, 'getPerformanceInsights'])->name('insights');
    });

    // ===== API ROUTES =====
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
                    'can_export' => in_array($user->role, ['admin', 'witel_support']),
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

    // ===== LEGACY EXPORT =====
    Route::get('export', function() {
        return redirect()->route('dashboard.export', request()->all());
    })->name('export');

    // ===== PROFILE ROUTES =====
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // ===== FUTURE ROLE-BASED ROUTES =====

    // TODO: Uncomment when AmDashboardController is ready
    // Route::prefix('am-dashboard')->name('am.')->middleware('role:account_manager')->group(function () {
    //     Route::get('/', [AmDashboardController::class, 'index'])->name('dashboard');
    //     Route::get('export', [AmDashboardController::class, 'export'])->name('export');
    // });

    // TODO: Uncomment when WitelDashboardController is ready
    // Route::prefix('witel-dashboard')->name('witel.')->middleware('role:witel_support')->group(function () {
    //     Route::get('/', [WitelDashboardController::class, 'index'])->name('dashboard');
    //     Route::get('export', [WitelDashboardController::class, 'export'])->name('export');
    // });
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

// ===== FALLBACK =====
Route::fallback(function () {
    if (request()->wantsJson()) {
        return response()->json(['error' => 'Route not found'], 404);
    }
    return view('errors.404');
});

require __DIR__.'/auth.php';

// ===== SIDEBAR ROUTES =====
Route::view('/leaderboardAM', 'leaderboardAM')->name('leaderboard');
Route::view('/revenue', 'revenueData')->name('revenue.index');
Route::view('/treg3', 'treg3.index')->name('dashboard.treg3');
Route::view('/witel-perform', 'performansi.witel')->name('witel.perform');
Route::view('/leaderboardAM', 'leaderboardAM')->name('leaderboard');
Route::view('/profile', 'profile.edit')->name('profile.edit');