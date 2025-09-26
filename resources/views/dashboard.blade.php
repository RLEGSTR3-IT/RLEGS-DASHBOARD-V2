@extends('layouts.main')

@section('title', 'Admin Dashboard RLEGS V2')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/overview.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* Override font dengan Poppins */
body, .main-content, .header-title, .card-title, .stats-title, .stats-value {
    font-family: 'Poppins', sans-serif !important;
}

/* Performance Section Tab Styling - FIXED CONTAINMENT */
.performance-section {
    background: var(--white);
    border-radius: var(--radius-2xl);
    box-shadow: var(--shadow-card);
    border: 1px solid var(--gray-200);
    transition: var(--transition);
    margin-bottom: 24px;
    overflow: hidden;
}

.performance-section:hover {
    box-shadow: var(--shadow-card-hover);
    border-color: var(--gray-300);
}

.performance-section .card-header {
    padding: 28px 32px 16px 32px;
    border-bottom: none;
    background: var(--gradient-subtle);
}

/* FIXED: Tab navigation contained within section */
.performance-tabs {
    border-bottom: 2px solid var(--gray-200);
    padding: 0 32px;
    background: var(--gray-50);
    margin: 0;
    width: 100%;
}

.performance-tabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    padding: 16px 24px;
    font-weight: 600;
    color: var(--gray-600);
    background: none;
    font-family: 'Poppins', sans-serif;
    transition: var(--transition);
    font-size: 0.9rem;
}

.performance-tabs .nav-link:hover {
    color: var(--telkom-red);
    background: var(--telkom-red-soft);
}

.performance-tabs .nav-link.active {
    color: var(--telkom-red);
    border-bottom-color: var(--telkom-red);
    background: var(--white);
}

/* Tab Content */
.tab-content {
    display: block !important;
}

.tab-pane {
    display: none;
    opacity: 1;
    min-height: 400px;
}

.tab-pane.active {
    display: block !important;
}

/* Table Container */
.table-container {
    min-height: 400px;
    position: relative;
}

.table-container .table-responsive {
    padding: 32px;
}

/* Enhanced Empty State */
.empty-state-enhanced {
    text-align: center;
    padding: 60px 24px;
    color: var(--gray-500);
    background: var(--gray-25);
    border-radius: var(--radius-lg);
    margin: 20px 0;
    border: 1px solid var(--gray-200);
}

.empty-state-enhanced i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
    color: var(--gray-400);
    display: block;
}

.empty-state-enhanced h5 {
    margin-bottom: 12px;
    font-weight: 700;
    color: var(--gray-700);
    font-size: 1.25rem;
    font-family: 'Poppins', sans-serif;
}

.empty-state-enhanced p {
    margin: 0;
    font-size: 1rem;
    color: var(--gray-500);
    line-height: 1.6;
    max-width: 400px;
    margin: 0 auto;
    font-family: 'Poppins', sans-serif;
}

/* Filter group */
.filter-group {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: nowrap;
    justify-content: flex-end;
}

.filter-select {
    min-width: 120px;
    max-width: 180px;
    font-size: 0.875rem;
    border: 1px solid var(--gray-300);
    border-radius: var(--radius-lg);
    padding: 8px 12px;
    background: var(--white);
    color: var(--gray-700);
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    transition: var(--transition);
}

.filter-select:focus {
    border-color: var(--telkom-red);
    box-shadow: 0 0 0 3px var(--telkom-red-subtle);
    outline: none;
}

/* Achievement indicators */
.achievement-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 8px;
}

.achievement-excellent { background-color: #198754; }
.achievement-good { background-color: #fd7e14; }
.achievement-poor { background-color: #dc3545; }

/* Period text */
.period-text {
    font-weight: 600;
    color: var(--telkom-red);
    font-size: 0.9rem;
}

/* AM Profile Picture */
.am-profile-pic {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

/* Divisi Pills */
.divisi-pills {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}

.divisi-pill {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-dgs { background-color: #e3f2fd; color: #1976d2; }
.badge-dss { background-color: #e8f5e8; color: #388e3c; }
.badge-dps { background-color: #fff3e0; color: #f57c00; }
.badge-government { background-color: #e3f2fd; color: #1976d2; }
.badge-soe { background-color: #e8f5e8; color: #388e3c; }
.badge-private { background-color: #fff3e0; color: #f57c00; }

/* Table hover effects */
.clickable-row:hover {
    background-color: var(--gray-50) !important;
    cursor: pointer;
}

.table-hover-effect {
    background-color: var(--telkom-red-subtle) !important;
}

/* Chart Container */
.chart-container {
    height: 350px !important;
    width: 100% !important;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chart-container canvas {
    max-height: 350px !important;
    width: 100% !important;
    height: 100% !important;
}

/* Responsive */
@media (max-width: 768px) {
    .filter-group {
        flex-wrap: wrap;
        justify-content: stretch;
    }

    .filter-select {
        min-width: auto;
        flex: 1;
    }

    .performance-tabs {
        padding: 0 16px;
    }

    .performance-tabs .nav-link {
        padding: 12px 16px;
        font-size: 0.8rem;
    }

    .table-container .table-responsive {
        padding: 16px;
    }
}
</style>
@endsection

@section('content')
<div class="main-content">
    <!-- Enhanced Header dengan YTD/MTD Filter -->
    <div class="header-dashboard">
        <div class="header-content">
            <div class="header-text">
                <h1 class="header-title">Overview Data for Admin</h1>
                <p class="header-subtitle">
                    Monitoring Pendapatan RLEGS TREG 3
                    @if(isset($cardData['period_text']))
                        <span class="period-text">{{ $cardData['period_text'] }}</span>
                    @endif
                </p>
            </div>
            <div class="header-actions">
                <!-- Filter Group -->
                <div class="filter-group">
                    <!-- Period Type Filter (YTD/MTD) -->
                    <select id="periodTypeFilter" class="form-select filter-select">
                        @foreach($filterOptions['period_types'] ?? ['YTD' => 'Year to Date', 'MTD' => 'Month to Date'] as $key => $label)
                        <option value="{{ $key }}" {{ $key == ($filters['period_type'] ?? 'YTD') ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>

                    <!-- Divisi Filter dengan Kode -->
                    <select id="divisiFilter" class="form-select filter-select">
                        <option value="">Semua Divisi</option>
                        @foreach($filterOptions['divisis'] ?? [] as $divisi)
                        <option value="{{ $divisi->id }}" {{ $divisi->id == ($filters['divisi_id'] ?? '') ? 'selected' : '' }}>
                            {{ $divisi->kode ?? $divisi->nama }}
                        </option>
                        @endforeach
                    </select>

                    <!-- Sort Indicator Filter -->
                    <select id="sortIndicatorFilter" class="form-select filter-select">
                        @foreach($filterOptions['sort_indicators'] ?? ['total_revenue' => 'Total Revenue Tertinggi', 'achievement_rate' => 'Achievement Rate Tertinggi', 'semua' => 'Semua'] as $key => $label)
                        <option value="{{ $key }}" {{ $key == ($filters['sort_indicator'] ?? 'total_revenue') ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>

                    <!-- Tipe Revenue Filter -->
                    <select id="tipeRevenueFilter" class="form-select filter-select">
                        @foreach($filterOptions['tipe_revenues'] ?? ['all' => 'Semua Tipe'] as $key => $label)
                        <option value="{{ $key }}" {{ $key == ($filters['tipe_revenue'] ?? 'all') ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <button class="export-btn" onclick="exportData()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
    </div>

    <!-- Alert Section -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <p class="mb-0">{{ session('error') }}</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(isset($error))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <p class="mb-0">{{ $error }}</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 1. CARD GROUP SECTION - SIMPLIFIED -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-title">Total Revenue</div>
                <div class="stats-value">Rp {{ number_format($cardData['total_revenue'] ?? 0, 0, ',', '.') }}</div>
                <div class="stats-period">Pendapatan yang dihasilkan RLEGS Regional III</div>
                <div class="stats-icon icon-revenue">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-title">Target Revenue</div>
                <div class="stats-value">Rp {{ number_format($cardData['total_target'] ?? 0, 0, ',', '.') }}</div>
                <div class="stats-period">Target yang ditetapkan untuk semua Corporate Customer</div>
                <div class="stats-icon icon-target">
                    <i class="fas fa-bullseye"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-title">Achievement Rate</div>
                <div class="stats-value">
                    <span class="achievement-indicator achievement-{{ $cardData['achievement_color'] ?? 'poor' }}"></span>
                    {{ number_format($cardData['achievement_rate'] ?? 0, 2) }}%
                </div>
                <div class="stats-period">Persentase pencapaian target pendapatan</div>
                <div class="stats-icon icon-achievement">
                    <i class="fas fa-medal"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. PERFORMANCE SECTION - Corporate Customer First, Account Manager Second -->
    <div class="performance-section">
        <div class="card-header">
            <div class="card-header-content">
                <h5 class="card-title">Performance Section</h5>
                <p class="text-muted mb-0">Top performers berdasarkan indikator terpilih</p>
            </div>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs performance-tabs" id="performanceTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-corporate-customer" data-bs-toggle="tab"
                        data-bs-target="#content-corporate-customer" type="button" role="tab"
                        data-tab="corporate_customer">
                    Top Revenue Corporate Customer
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-account-manager" data-bs-toggle="tab"
                        data-bs-target="#content-account-manager" type="button" role="tab"
                        data-tab="account_manager">
                    Top Account Manager
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-witel" data-bs-toggle="tab"
                        data-bs-target="#content-witel" type="button" role="tab"
                        data-tab="witel">
                    Top Witel
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-segment" data-bs-toggle="tab"
                        data-bs-target="#content-segment" type="button" role="tab"
                        data-tab="segment">
                    Top LSegment
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="performanceTabContent">
            <!-- Corporate Customer Tab - DEFAULT ACTIVE -->
            <div class="tab-pane active" id="content-corporate-customer" role="tabpanel">
                <div class="table-container">
                    @if(isset($performanceData['corporate_customer']) && $performanceData['corporate_customer']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern m-0">
                            <thead>
                                <tr>
                                    <th>Ranking</th>
                                    <th>Nama Customer</th>
                                    <th>Divisi</th>
                                    <th>LSegment</th>
                                    <th class="text-end">Total Revenue</th>
                                    <th class="text-end">Target Revenue</th>
                                    <th class="text-end">Achievement</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($performanceData['corporate_customer'] as $index => $customer)
                                <tr class="clickable-row" data-url="{{ $customer->detail_url ?? route('corporate-customer.show', $customer->id) }}">
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td>
                                        <div>
                                            <div class="fw-semibold">{{ $customer->nama ?? 'N/A' }}</div>
                                            @if(!empty($customer->nipnas))
                                                <small class="text-muted">{{ $customer->nipnas }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $customer->divisi_nama ?? 'N/A' }}</td>
                                    <td>{{ $customer->segment_nama ?? 'N/A' }}</td>
                                    <td class="text-end">Rp {{ number_format($customer->total_revenue ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($customer->total_target ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        <span class="status-badge bg-{{ $customer->achievement_color ?? 'secondary' }}-soft">
                                            {{ number_format($customer->achievement_rate ?? 0, 2) }}%
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ $customer->detail_url ?? route('corporate-customer.show', $customer->id) }}"
                                           class="btn btn-sm btn-primary">Detail</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="empty-state-enhanced">
                        <i class="fas fa-building-user"></i>
                        <h5>Belum Ada Corporate Customer</h5>
                        <p>Tidak ada Corporate Customer yang memiliki data pendapatan pada periode dan filter yang dipilih.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Account Manager Tab - SERVER-SIDE DATA -->
            <div class="tab-pane" id="content-account-manager" role="tabpanel">
                <div class="table-container">
                    @if(isset($performanceData['account_manager']) && $performanceData['account_manager']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern m-0">
                            <thead>
                                <tr>
                                    <th>Ranking</th>
                                    <th>Nama</th>
                                    <th>Witel</th>
                                    <th>Divisi</th>
                                    <th class="text-end">Total Revenue</th>
                                    <th class="text-end">Target Revenue</th>
                                    <th class="text-end">Achievement</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($performanceData['account_manager'] as $index => $am)
                                <tr class="clickable-row" data-url="{{ $am->detail_url ?? route('account-manager.show', $am->id) }}">
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ asset('img/profile.png') }}" class="am-profile-pic" alt="{{ $am->nama }}">
                                            <span class="ms-2 clickable-name">{{ $am->nama }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $am->witel->nama ?? 'N/A' }}</td>
                                    <td>
                                        <div class="divisi-pills">
                                            @if(!empty($am->divisi_list) && $am->divisi_list !== 'N/A')
                                                @foreach(explode(', ', $am->divisi_list) as $divisi)
                                                <span class="divisi-pill badge-{{ strtolower(str_replace(' ', '-', $divisi)) }}">{{ $divisi }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">Rp {{ number_format($am->total_revenue ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($am->total_target ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        <span class="status-badge bg-{{ $am->achievement_color ?? 'secondary' }}-soft">
                                            {{ number_format($am->achievement_rate ?? 0, 2) }}%
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ $am->detail_url ?? route('account-manager.show', $am->id) }}"
                                           class="btn btn-sm btn-primary">Detail</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="empty-state-enhanced">
                        <i class="fas fa-users"></i>
                        <h5>Belum Ada Account Manager</h5>
                        <p>Tidak ada Account Manager yang memiliki data pendapatan pada periode dan filter yang dipilih.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Witel Tab - AJAX LOADED -->
            <div class="tab-pane" id="content-witel" role="tabpanel">
                <!-- Content loaded via AJAX -->
            </div>

            <!-- Segment Tab - AJAX LOADED -->
            <div class="tab-pane" id="content-segment" role="tabpanel">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- 3. VISUALISASI PENDAPATAN BULANAN -->
    <div class="row mt-4">
        <!-- Line Chart Total Revenue Bulanan -->
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-header-content">
                        <h5 class="card-title">Perkembangan Revenue Bulanan</h5>
                        <p class="text-muted mb-0">Total pendapatan RLEGS per bulan ({{ date('Y') }})</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        @if(isset($monthlyChart) && !empty($monthlyChart))
                            {!! $monthlyChart !!}
                        @else
                            <div class="empty-state-enhanced" style="margin: 0; background: transparent; border: none;">
                                <i class="fas fa-chart-line"></i>
                                <h5>Chart Tidak Tersedia</h5>
                                <p>Data chart sedang dimuat atau tidak tersedia untuk periode ini</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Bar Chart Performance Distribution -->
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-header-content">
                        <h5 class="card-title">Distribusi Pencapaian Target AM</h5>
                        <p class="text-muted mb-0">Kuantitas performa Account Manager per bulan</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        @if(isset($performanceChart) && !empty($performanceChart))
                            {!! $performanceChart !!}
                        @else
                            <div class="empty-state-enhanced" style="margin: 0; background: transparent; border: none;">
                                <i class="fas fa-chart-bar"></i>
                                <h5>Chart Tidak Tersedia</h5>
                                <p>Data chart sedang dimuat atau tidak tersedia untuk periode ini</p>
                            </div>
                        @endif
                    </div>
                    <div class="mt-3 d-flex justify-content-center gap-3">
                        <span class="badge bg-success-soft">
                            <i class="fas fa-circle me-1"></i> Hijau: ≥100%
                        </span>
                        <span class="badge bg-warning-soft">
                            <i class="fas fa-circle me-1"></i> Oranye: 80-99%
                        </span>
                        <span class="badge bg-danger-soft">
                            <i class="fas fa-circle me-1"></i> Merah: 0-80%
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. TABEL TOTAL PENDAPATAN BULANAN -->
    <div class="dashboard-card mt-4">
        <div class="card-header">
            <div class="card-header-content">
                <h5 class="card-title">Total Pendapatan Bulanan RLEGS ({{ date('Y') }})</h5>
                <p class="text-muted mb-0">Ringkasan bulanan Target, Realisasi, Achievement dengan filter {{ $filters['period_type'] ?? 'YTD' }}</p>
            </div>
        </div>
        <div class="card-body p-0">
            @if(isset($revenueTable) && count($revenueTable) > 0)
            <div class="table-responsive">
                <table class="table table-modern m-0">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th class="text-end">Target</th>
                            <th class="text-end">Realisasi</th>
                            <th class="text-end">Achievement</th>
                            <th class="text-end">Gap</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($revenueTable as $row)
                        <tr>
                            <td>{{ $row['bulan'] }}</td>
                            <td class="text-end">Rp {{ number_format($row['target'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($row['realisasi'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">
                                <span class="status-badge bg-{{ $row['achievement_color'] ?? 'secondary' }}-soft">
                                    {{ number_format($row['achievement'] ?? 0, 2) }}%
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="{{ ($row['realisasi'] - $row['target']) >= 0 ? 'text-success' : 'text-danger' }}">
                                    Rp {{ number_format(($row['realisasi'] ?? 0) - ($row['target'] ?? 0), 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="empty-state-enhanced">
                <i class="fas fa-table"></i>
                <h5>Belum Ada Data Revenue Bulanan</h5>
                <p>Data revenue bulanan tidak tersedia untuk periode {{ $filters['period_type'] ?? 'YTD' }} dengan filter yang dipilih.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-spinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Memuat data dashboard...</p>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
$(document).ready(function() {
    console.log('Dashboard RLEGS V2 - Working Version with All Tabs');


    // =====================================
    // FILTER HANDLING - WORKING VERSION
    // =====================================
    function applyFilters() {
        const filters = {
            period_type: $('#periodTypeFilter').val(),
            divisi_id: $('#divisiFilter').val(),
            sort_indicator: $('#sortIndicatorFilter').val(),
            tipe_revenue: $('#tipeRevenueFilter').val()
        };

        console.log('Applying filters:', filters);
        showLoading();

        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                params.append(key, filters[key]);
            }
        });

        const newUrl = window.location.pathname + '?' + params.toString();
        console.log('Redirecting to:', newUrl);
        window.location.href = newUrl;
    }

    $('#periodTypeFilter, #divisiFilter, #sortIndicatorFilter, #tipeRevenueFilter').on('change', function() {
        console.log('Filter changed:', this.id, this.value);
        applyFilters();
    });

    // =====================================
    // TAB SWITCHING - FIXED VERSION
    // =====================================
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const tabType = $(e.target).attr('data-tab');
        console.log('Tab switched to:', tabType);

        // Load AJAX data only for Witel and Segment tabs
        // Corporate Customer and Account Manager use server-side data
        if (tabType === 'witel' || tabType === 'segment') {
            loadTabData(tabType);
        }
    });

    // =====================================
    // AJAX LOAD TAB DATA - ONLY FOR WITEL & SEGMENT
    // =====================================
    function loadTabData(tabType) {
        console.log('Loading AJAX data for:', tabType);

        const filters = {
            tab: tabType,
            period_type: $('#periodTypeFilter').val() || 'YTD',
            divisi_id: $('#divisiFilter').val() || '',
            sort_indicator: $('#sortIndicatorFilter').val() || 'total_revenue',
            tipe_revenue: $('#tipeRevenueFilter').val() || 'all'
        };

        const tabContent = $(`#content-${tabType}`);

        // Show loading state
        tabContent.html(`
            <div class="table-container">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat data ${getTabDisplayName(tabType)}...</p>
                </div>
            </div>
        `);

        $.ajax({
            url: "{{ route('dashboard.tab-data') }}",
            method: 'GET',
            data: filters,
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                console.log(`AJAX success for ${tabType}:`, response);

                if (response.success && response.data) {
                    const dataArray = Array.isArray(response.data) ? response.data : Object.values(response.data);

                    if (dataArray.length > 0) {
                        console.log(`Rendering ${dataArray.length} items for ${tabType}`);
                        renderTabContent(tabContent, dataArray, tabType);
                        bindClickableRows();
                    } else {
                        console.log(`No data for ${tabType}`);
                        showEmptyState(tabContent, tabType);
                    }
                } else {
                    console.warn(`Invalid response for ${tabType}:`, response);
                    showEmptyState(tabContent, tabType);
                }
            },
            error: function(xhr, status, error) {
                console.error(`AJAX error for ${tabType}:`, {
                    status: xhr.status,
                    error: error,
                    response: xhr.responseText
                });
                showErrorState(tabContent, tabType, error);
            }
        });
    }

    // =====================================
    // RENDER TAB CONTENT
    // =====================================
    function renderTabContent(tabContent, data, tabType) {
        console.log(`Rendering ${tabType} with ${data.length} records`);

        let html = `
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-modern m-0">
                        <thead>
                            <tr>${getTableHeaders(tabType)}</tr>
                        </thead>
                        <tbody>
        `;

        data.forEach((item, index) => {
            html += buildTableRow(item, index + 1, tabType);
        });

        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        tabContent.html(html);
        console.log(`Content rendered for ${tabType}`);
    }

    // =====================================
    // TABLE BUILDERS
    // =====================================
    function getTableHeaders(tabType) {
        const headers = {
            'witel': '<th>Ranking</th><th>Nama Witel</th><th class="text-center">Total Pelanggan</th><th class="text-end">Total Revenue</th><th class="text-end">Target Revenue</th><th class="text-end">Achievement</th><th>Action</th>',
            'segment': '<th>Ranking</th><th>Nama Segmen</th><th>Divisi</th><th class="text-center">Total Pelanggan</th><th class="text-end">Total Revenue</th><th class="text-end">Target Revenue</th><th class="text-end">Achievement</th><th>Action</th>'
        };
        return headers[tabType] || '';
    }

    function buildTableRow(item, ranking, tabType) {
        const detailUrl = item.detail_url || getDetailRoute(item.id, tabType);
        let row = `<tr class="clickable-row" data-url="${detailUrl}"><td><strong>${ranking}</strong></td>`;

        console.log(`Building row ${ranking} for ${tabType}:`, item);

        switch(tabType) {
            case 'witel':
                row += `
                    <td>${item.nama || 'N/A'}</td>
                    <td class="text-center">${formatNumber(item.total_customers || 0)}</td>
                    <td class="text-end">Rp ${formatCurrency(item.total_revenue || 0)}</td>
                    <td class="text-end">Rp ${formatCurrency(item.total_target || 0)}</td>
                    <td class="text-end">
                        <span class="status-badge bg-${item.achievement_color || 'secondary'}-soft">
                            ${(parseFloat(item.achievement_rate) || 0).toFixed(2)}%
                        </span>
                    </td>
                    <td><a href="${detailUrl}" class="btn btn-sm btn-primary">Detail</a></td>
                `;
                break;

            case 'segment':
                row += `
                    <td>${item.lsegment_ho || item.nama || 'N/A'}</td>
                    <td>${(item.divisi && item.divisi.nama) || item.divisi_nama || 'N/A'}</td>
                    <td class="text-center">${formatNumber(item.total_customers || 0)}</td>
                    <td class="text-end">Rp ${formatCurrency(item.total_revenue || 0)}</td>
                    <td class="text-end">Rp ${formatCurrency(item.total_target || 0)}</td>
                    <td class="text-end">
                        <span class="status-badge bg-${item.achievement_color || 'secondary'}-soft">
                            ${(parseFloat(item.achievement_rate) || 0).toFixed(2)}%
                        </span>
                    </td>
                    <td><a href="${detailUrl}" class="btn btn-sm btn-primary">Detail</a></td>
                `;
                break;

            default:
                console.error('Unknown tab type:', tabType);
                return '';
        }

        row += '</tr>';
        return row;
    }

    // =====================================
    // HELPER FUNCTIONS
    // =====================================
    function getTabDisplayName(tabType) {
        const names = {
            'witel': 'Witel',
            'segment': 'Segment'
        };
        return names[tabType] || tabType;
    }

    function getDetailRoute(id, tabType) {
        const baseRoutes = {
            'witel': "{{ route('witel.show', '') }}",
            'segment': "{{ route('segment.show', '') }}"
        };
        return `${baseRoutes[tabType]}/${id}`;
    }

    function formatCurrency(amount) {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('id-ID').format(Math.round(num));
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(parseInt(num) || 0);
    }

    function showEmptyState(tabContent, tabType) {
        const icons = {
            'witel': 'fas fa-building',
            'segment': 'fas fa-chart-pie'
        };

        const messages = {
            'witel': 'Belum Ada Data Witel',
            'segment': 'Belum Ada Data Segmen'
        };

        const descriptions = {
            'witel': 'Tidak ada data Witel yang tersedia pada periode dan filter yang dipilih.',
            'segment': 'Tidak ada data Segmen yang tersedia pada periode dan filter yang dipilih.'
        };

        tabContent.html(`
            <div class="table-container">
                <div class="empty-state-enhanced">
                    <i class="${icons[tabType]}"></i>
                    <h5>${messages[tabType]}</h5>
                    <p>${descriptions[tabType]}</p>
                </div>
            </div>
        `);
    }

    function showErrorState(tabContent, tabType, error) {
        console.error(`Error loading ${tabType}:`, error);

        tabContent.html(`
            <div class="table-container">
                <div class="empty-state-enhanced">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    <h5>Gagal Memuat Data</h5>
                    <p>Terjadi kesalahan saat memuat data ${getTabDisplayName(tabType)}. Silakan coba lagi.</p>
                    <small class="text-muted">Error: ${error}</small>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-primary" onclick="loadTabData('${tabType}')">
                            <i class="fas fa-redo"></i> Coba Lagi
                        </button>
                    </div>
                </div>
            </div>
        `);
    }

    // =====================================
    // CLICKABLE ROWS AND INTERACTIONS
    // =====================================
    function bindClickableRows() {
        $('.clickable-row').off('click.dashboard').off('mouseenter.dashboard').off('mouseleave.dashboard');

        $('.clickable-row').on('click.dashboard', function(e) {
            if (e.target.tagName === 'A' || e.target.closest('a') || e.target.tagName === 'BUTTON') {
                return;
            }

            const url = $(this).attr('data-url');
            if (url && url !== '#') {
                console.log('Navigating to:', url);
                window.location.href = url;
            }
        });

        $('.clickable-row').on('mouseenter.dashboard', function() {
            $(this).addClass('table-hover-effect');
        });

        $('.clickable-row').on('mouseleave.dashboard', function() {
            $(this).removeClass('table-hover-effect');
        });

        console.log('Clickable rows bound:', $('.clickable-row').length, 'rows');
    }

    // Initial bind for existing rows (Corporate Customer and Account Manager)
    bindClickableRows();

    // =====================================
    // EXPORT FUNCTION
    // =====================================
    window.exportData = function() {
        const filters = {
            period_type: $('#periodTypeFilter').val(),
            divisi_id: $('#divisiFilter').val(),
            sort_indicator: $('#sortIndicatorFilter').val(),
            tipe_revenue: $('#tipeRevenueFilter').val()
        };

        console.log('Exporting with filters:', filters);

        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                params.append(key, filters[key]);
            }
        });

        const exportUrl = "{{ route('dashboard.export') }}?" + params.toString();
        console.log('Export URL:', exportUrl);

        window.open(exportUrl, '_blank');
    };

    // =====================================
    // LOADING FUNCTIONS
    // =====================================
    function showLoading() {
        $('#loadingOverlay').show();
    }

    function hideLoading() {
        $('#loadingOverlay').hide();
    }

    $(window).on('beforeunload', showLoading);
    $(window).on('load', hideLoading);

    // =====================================
    // FILTER INDICATOR UPDATES
    // =====================================
    function updateFilterIndicators() {
        const sortIndicator = $('#sortIndicatorFilter').val();
        const sortName = $('#sortIndicatorFilter option:selected').text().toLowerCase();
        $('.performance-section .card-header p').text(`Top performers berdasarkan ${sortName}`);

        console.log('Filter indicators updated');
    }

    $('#periodTypeFilter, #divisiFilter, #sortIndicatorFilter, #tipeRevenueFilter').on('change', function() {
        updateFilterIndicators();
    });

    // =====================================
    // KEYBOARD SHORTCUTS
    // =====================================
    $(document).on('keydown', function(e) {
        // Ctrl + E untuk export
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            exportData();
        }

        // Ctrl + R untuk refresh
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            location.reload();
        }

        // Tab navigation dengan angka 1-4
        if (e.altKey && e.key >= '1' && e.key <= '4') {
            e.preventDefault();
            const tabs = ['corporate_customer', 'account_manager', 'witel', 'segment'];
            const tabIndex = parseInt(e.key) - 1;
            if (tabs[tabIndex]) {
                $(`#tab-${tabs[tabIndex]}`).click();
            }
        }
    });

    // =====================================
    // AUTO REFRESH FOR AJAX TABS ONLY
    // =====================================
    setInterval(function() {
        const activeTab = $('.performance-tabs .nav-link.active').attr('data-tab');
        if (activeTab === 'witel' || activeTab === 'segment') {
            console.log('Auto-refreshing AJAX tab:', activeTab);
            loadTabData(activeTab);
        }
    }, 300000); // 5 minutes

    // =====================================
    // ERROR HANDLING
    // =====================================
    window.addEventListener('error', function(e) {
        console.error('Global error:', e.error);

        if (!document.querySelector('.error-notification')) {
            const notification = $(`
                <div class="alert alert-danger error-notification position-fixed top-0 end-0 m-3" style="z-index: 9999;">
                    <strong>Error:</strong> Terjadi kesalahan sistem. <a href="#" onclick="location.reload()">Refresh halaman</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);

            $('body').append(notification);

            setTimeout(() => {
                notification.fadeOut();
            }, 5000);
        }
    });

    // =====================================
    // NETWORK STATUS MONITORING
    // =====================================
    window.addEventListener('online', function() {
        console.log('Network connection restored');
        $('.network-status').fadeOut();
    });

    window.addEventListener('offline', function() {
        console.log('Network connection lost');

        const networkAlert = $(`
            <div class="alert alert-warning network-status position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;">
                <i class="fas fa-wifi-slash"></i> Koneksi internet terputus. Data mungkin tidak terupdate.
            </div>
        `);

        $('body').append(networkAlert);
    });

    // =====================================
    // TOOLTIPS
    // =====================================
    $(document).on('mouseenter', '.status-badge', function() {
        const achievement = parseFloat($(this).text());
        let tooltipText = 'Achievement Rate';

        if (achievement >= 100) {
            tooltipText = 'Excellent: Target tercapai dengan baik!';
        } else if (achievement >= 80) {
            tooltipText = 'Good: Mendekati target, perlu sedikit peningkatan';
        } else {
            tooltipText = 'Poor: Perlu peningkatan signifikan';
        }

        $(this).attr('title', tooltipText).tooltip('show');
    });

    // =====================================
    // DEBUGGING HELPERS (Development mode)
    // =====================================
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.log('🔧 Development mode active');

        window.debugDashboard = {
            loadTab: loadTabData,
            showFilters: () => {
                console.log('Current filters:', {
                    period_type: $('#periodTypeFilter').val(),
                    divisi_id: $('#divisiFilter').val(),
                    sort_indicator: $('#sortIndicatorFilter').val(),
                    tipe_revenue: $('#tipeRevenueFilter').val()
                });
            },
            testAjaxTabs: () => {
                ['witel', 'segment'].forEach((tab, index) => {
                    setTimeout(() => {
                        console.log(`Testing AJAX tab: ${tab}`);
                        loadTabData(tab);
                    }, index * 2000);
                });
            },
            getCurrentTab: () => {
                return $('.performance-tabs .nav-link.active').attr('data-tab');
            },
            simulateError: (tabType) => {
                const tabContent = $(`#content-${tabType}`);
                showErrorState(tabContent, tabType, 'Simulated error for testing');
            }
        };
    }

    // =====================================
    // INITIALIZATION COMPLETE
    // =====================================
    console.log('🎯 Dashboard initialization completed');
    console.log('📊 Server-side tabs: Corporate Customer, Account Manager');
    console.log('🔄 AJAX tabs: Witel, Segment');
    console.log('🎛️ Active tab:', $('.performance-tabs .nav-link.active').attr('data-tab'));

    // Final setup
    hideLoading();
    updateFilterIndicators();

    // Success message
    console.log('✅ Dashboard RLEGS V2 - Final Working Version Ready');
    console.log('📋 Features: Filters ✓, Tab switching ✓, AJAX loading ✓, Export ✓, Keyboard shortcuts ✓');

    // Show ready indicator in console
    console.log('%c🚀 All systems operational!', 'color: #28a745; font-weight: bold; font-size: 16px;');
});
</script>
@endsection