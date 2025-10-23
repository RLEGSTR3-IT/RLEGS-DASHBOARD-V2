@extends('layouts.main')

@section('title', 'Dashboard RLEGS')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/overview.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
@endsection

@section('content')
<div class="main-content">
    <!-- Header -->
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
                <div class="filter-group">
                    <select id="periodTypeFilter" class="form-select filter-select">
                        @foreach($filterOptions['period_types'] ?? ['YTD' => 'Year to Date', 'MTD' => 'Month to Date'] as $key => $label)
                        <option value="{{ $key }}" {{ $key == ($filters['period_type'] ?? 'YTD') ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select id="divisiFilter" class="form-select filter-select">
                        <option value="">Semua Divisi</option>
                        @foreach($filterOptions['divisis'] ?? [] as $divisi)
                        <option value="{{ $divisi->id }}" {{ $divisi->id == ($filters['divisi_id'] ?? '') ? 'selected' : '' }}>
                            {{ $divisi->kode ?? $divisi->nama }}
                        </option>
                        @endforeach
                    </select>
                    <select id="sortIndicatorFilter" class="form-select filter-select">
                        @foreach($filterOptions['sort_indicators'] ?? ['total_revenue' => 'Total Revenue Tertinggi', 'achievement_rate' => 'Achievement Rate Tertinggi', 'semua' => 'Semua'] as $key => $label)
                        <option value="{{ $key }}" {{ $key == ($filters['sort_indicator'] ?? 'total_revenue') ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select id="tipeRevenueFilter" class="form-select filter-select">
                        @foreach($filterOptions['tipe_revenues'] ?? ['all' => 'Semua Tipe'] as $key => $label)
                        <option value="{{ $key }}" {{ $key == ($filters['tipe_revenue'] ?? 'all') ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="export-btn" onclick="exportData()"><i class="fas fa-download"></i> Export</button>
            </div>
        </div>
    </div>

    <!-- Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-title">Total Revenue</div>
                <div class="stats-value">Rp {{ number_format($cardData['total_revenue'] ?? 0, 0, ',', '.') }}</div>
                <div class="stats-period">Pendapatan yang dihasilkan RLEGS Regional III</div>
                <div class="stats-icon icon-revenue"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-title">Target Revenue</div>
                <div class="stats-value">Rp {{ number_format($cardData['total_target'] ?? 0, 0, ',', '.') }}</div>
                <div class="stats-period">Target yang ditetapkan untuk semua Corporate Customer</div>
                <div class="stats-icon icon-target"><i class="fas fa-bullseye"></i></div>
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
                <div class="stats-icon icon-achievement"><i class="fas fa-medal"></i></div>
            </div>
        </div>
    </div>

    <!-- Performance Section (table tabs) -->
    <div class="performance-section">
        <div class="card-header">
            <div class="card-header-content">
                <h5 class="card-title">Performance Section</h5>
                <p class="text-muted mb-0">Top performers berdasarkan indikator terpilih</p>
            </div>
        </div>
        <ul class="nav nav-tabs performance-tabs" id="performanceTab" role="tablist">
            <li class="nav-item"><button class="nav-link active" id="tab-corporate-customer" data-bs-toggle="tab" data-bs-target="#content-corporate-customer" type="button" role="tab" data-tab="corporate_customer">Top Revenue Corporate Customer</button></li>
            <li class="nav-item"><button class="nav-link" id="tab-account-manager" data-bs-toggle="tab" data-bs-target="#content-account-manager" type="button" role="tab" data-tab="account_manager">Top Account Manager</button></li>
            <li class="nav-item"><button class="nav-link" id="tab-witel" data-bs-toggle="tab" data-bs-target="#content-witel" type="button" role="tab" data-tab="witel">Top Witel</button></li>
            <li class="nav-item"><button class="nav-link" id="tab-segment" data-bs-toggle="tab" data-bs-target="#content-segment" type="button" role="tab" data-tab="segment">Top LSegment</button></li>
        </ul>
        <div class="tab-content" id="performanceTabContent">
            <div class="tab-pane active" id="content-corporate-customer" role="tabpanel">
                <div class="table-container">
                    @if(isset($performanceData['corporate_customer']) && $performanceData['corporate_customer']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern m-0">
                            <thead>
                            <tr>
                                <th>Ranking</th><th>Nama Customer</th><th>Divisi</th><th>LSegment</th>
                                <th class="text-end">Total Revenue</th><th class="text-end">Target Revenue</th>
                                <th class="text-end">Achievement</th><th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($performanceData['corporate_customer'] as $index => $customer)
                            <tr class="clickable-row" data-url="{{ $customer->detail_url ?? route('corporate-customer.show', $customer->id) }}">
                                <td><strong>{{ $index + 1 }}</strong></td>
                                <td>
                                    <div>
                                        <div class="fw-semibold">{{ $customer->nama ?? 'N/A' }}</div>
                                        @if(!empty($customer->nipnas))<small class="text-muted">{{ $customer->nipnas }}</small>@endif
                                    </div>
                                </td>
                                <td>{{ $customer->divisi_nama ?? 'N/A' }}</td>
                                <td>{{ $customer->segment_nama ?? 'N/A' }}</td>
                                <td class="text-end">Rp {{ number_format($customer->total_revenue ?? 0, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($customer->total_target ?? 0, 0, ',', '.') }}</td>
                                <td class="text-end"><span class="status-badge bg-{{ $customer->achievement_color ?? 'secondary' }}-soft">{{ number_format($customer->achievement_rate ?? 0, 2) }}%</span></td>
                                <td><a href="{{ $customer->detail_url ?? route('corporate-customer.show', $customer->id) }}" class="btn btn-sm btn-primary">Detail</a></td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                        <div class="empty-state-enhanced"><i class="fas fa-building-user"></i><h5>Belum Ada Corporate Customer</h5><p>Tidak ada Corporate Customer yang memiliki data pendapatan pada periode dan filter yang dipilih.</p></div>
                    @endif
                </div>
            </div>

            <div class="tab-pane" id="content-account-manager" role="tabpanel">
                <div class="table-container">
                    @if(isset($performanceData['account_manager']) && $performanceData['account_manager']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern m-0">
                            <thead>
                            <tr>
                                <th>Ranking</th><th>Nama</th><th>Witel</th><th>Divisi</th>
                                <th class="text-end">Total Revenue</th><th class="text-end">Target Revenue</th>
                                <th class="text-end">Achievement</th><th>Action</th>
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
                                <td class="text-end"><span class="status-badge bg-{{ $am->achievement_color ?? 'secondary' }}-soft">{{ number_format($am->achievement_rate ?? 0, 2) }}%</span></td>
                                <td><a href="{{ $am->detail_url ?? route('account-manager.show', $am->id) }}" class="btn btn-sm btn-primary">Detail</a></td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                        <div class="empty-state-enhanced"><i class="fas fa-users"></i><h5>Belum Ada Account Manager</h5><p>Tidak ada Account Manager yang memiliki data pendapatan pada periode dan filter yang dipilih.</p></div>
                    @endif
                </div>
            </div>

            <div class="tab-pane" id="content-witel" role="tabpanel"></div>
            <div class="tab-pane" id="content-segment" role="tabpanel"></div>
        </div>
    </div>

    <!-- VISUALISASI -->
    <div class="row mt-4 row-eq">   <!-- tambahkan row-eq -->
        <!-- CHART 1 -->
        <div class="col-md-6 d-flex">  <!-- d-flex -->
            <div class="dashboard-card chart-card w-100 h-100"> <!-- h-100 -->
            ...
            <div class="card-body chart-body">                <!-- biarkan class ini -->
                <div id="chart-revenue-monthly" class="chart-container">
                {!! $monthlyChart ?? '' !!}
                </div>
            </div>
            </div>
        </div>

        <!-- CHART 2 -->
        <div class="col-md-6 d-flex">  <!-- d-flex -->
            <div class="dashboard-card chart-card w-100 h-100"> <!-- h-100 -->
            ...
            <div class="card-body chart-body">
                <div id="chart-am-distrib" class="chart-container">
                {!! $performanceChart ?? '' !!}
                </div>

                <!-- legend di bawah chart ini tetap boleh -->
                <div class="mt-3 d-flex justify-content-center gap-3 chart-badges">
                ...
                </div>
            </div>
            </div>
        </div>
    </div>


    <!-- TABEL -->
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
                        <td class="text-end"><span class="status-badge bg-{{ $row['achievement_color'] ?? 'secondary' }}-soft">{{ number_format($row['achievement'] ?? 0, 2) }}%</span></td>
                        <td class="text-end"><span class="{{ ($row['realisasi'] - $row['target']) >= 0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format(($row['realisasi'] ?? 0) - ($row['target'] ?? 0), 0, ',', '.') }}</span></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @else
                <div class="empty-state-enhanced"><i class="fas fa-table"></i><h5>Belum Ada Data Revenue Bulanan</h5><p>Data revenue bulanan tidak tersedia untuk periode {{ $filters['period_type'] ?? 'YTD' }} dengan filter yang dipilih.</p></div>
            @endif
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display:none;">
    <div class="loading-spinner">
        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
        <p class="mt-2">Memuat data dashboard...</p>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
/**
 * =============================
 *  TOTAL CHART TRANSFORMATION
 *  Perubahan Radikal & Profesional
 * =============================
 */
(function () {
    if (!window.Chart) return;

    // ====== WARNA BARU - VIBRANT & MODERN ======
    const COLORS = {
        // Chart 1 - Revenue Monthly
        target: {
            bg: 'rgba(139, 92, 246, 0.12)',      // Purple soft
            border: '#8B5CF6',                     // Purple vibrant
            gradient: 'rgba(139, 92, 246, 0.25)'
        },
        real: {
            bg: 'rgba(236, 72, 153, 0.12)',       // Pink soft
            border: '#EC4899',                     // Pink vibrant
            gradient: 'rgba(236, 72, 153, 0.25)'
        },
        achievement: {
            line: '#0EA5E9',                       // Sky blue
            point: '#0EA5E9',
            bg: 'rgba(14, 165, 233, 0.1)'
        },
        
        // Chart 2 - AM Distribution
        excellent: {
            bg: 'rgba(34, 197, 94, 0.2)',         // Green vibrant
            border: '#22C55E'
        },
        good: {
            bg: 'rgba(251, 146, 60, 0.2)',        // Orange vibrant
            border: '#FB923C'
        },
        poor: {
            bg: 'rgba(239, 68, 68, 0.2)',         // Red vibrant
            border: '#EF4444'
        }
    };

    // ====== GLOBAL CHART.JS CONFIGURATION ======
    Chart.defaults.font.family = '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto';
    Chart.defaults.font.size = 13;
    Chart.defaults.font.weight = 600;
    Chart.defaults.color = '#64748b';
    
    Chart.defaults.plugins.legend.display = true;
    Chart.defaults.plugins.legend.position = 'bottom';
    Chart.defaults.plugins.legend.align = 'center';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
    Chart.defaults.plugins.legend.labels.boxWidth = 10;
    Chart.defaults.plugins.legend.labels.boxHeight = 10;
    Chart.defaults.plugins.legend.labels.padding = 16;
    Chart.defaults.plugins.legend.labels.font = {
        size: 13,
        weight: 700,
        family: '"Inter", -apple-system, BlinkMacSystemFont'
    };
    
    // Tooltip Enhancement
    Chart.defaults.plugins.tooltip.enabled = true;
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.96)';
    Chart.defaults.plugins.tooltip.titleColor = '#ffffff';
    Chart.defaults.plugins.tooltip.bodyColor = '#f1f5f9';
    Chart.defaults.plugins.tooltip.borderColor = 'rgba(139, 92, 246, 0.6)';
    Chart.defaults.plugins.tooltip.borderWidth = 2;
    Chart.defaults.plugins.tooltip.padding = 14;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;
    Chart.defaults.plugins.tooltip.titleFont = { size: 14, weight: 700 };
    Chart.defaults.plugins.tooltip.bodyFont = { size: 13, weight: 600 };
    Chart.defaults.plugins.tooltip.displayColors = true;
    Chart.defaults.plugins.tooltip.boxWidth = 14;
    Chart.defaults.plugins.tooltip.boxHeight = 14;
    Chart.defaults.plugins.tooltip.boxPadding = 8;
    
    // Point & Line
    Chart.defaults.elements.point.radius = 6;
    Chart.defaults.elements.point.hoverRadius = 9;
    Chart.defaults.elements.point.hitRadius = 12;
    Chart.defaults.elements.point.borderWidth = 3;
    Chart.defaults.elements.line.borderWidth = 4;
    Chart.defaults.elements.line.tension = 0.4;
    Chart.defaults.elements.bar.borderWidth = 0;
    Chart.defaults.elements.bar.borderSkipped = false;

    // ====== HELPER FUNCTIONS ======
    function formatRupiah(value) {
        const v = Number(value) || 0;
        if (Math.abs(v) >= 1000000000) return 'Rp ' + (v / 1000000000).toFixed(1) + 'M';
        if (Math.abs(v) >= 1000000) return 'Rp ' + (v / 1000000).toFixed(1) + 'Jt';
        if (Math.abs(v) >= 1000) return 'Rp ' + (v / 1000).toFixed(0) + 'K';
        return 'Rp ' + v.toFixed(0);
    }

    function findChartByCanvas(canvas) {
        return Chart.getChart ? Chart.getChart(canvas) : Object.values(Chart.instances).find(c => c.canvas === canvas);
    }

    // ====== UNIVERSAL POLISH ======
    function polishChart(chart) {
        if (!chart || !chart.options) return;

        if (chart.options.scales) {
            Object.keys(chart.options.scales).forEach(scaleKey => {
                const scale = chart.options.scales[scaleKey];
                
                scale.grid = Object.assign({}, scale.grid, {
                    display: true,
                    drawBorder: false,
                    color: 'rgba(203, 213, 225, 0.4)',
                    lineWidth: 1.5,
                    drawTicks: false
                });
                
                scale.ticks = Object.assign({}, scale.ticks, {
                    padding: 12,
                    font: { size: 12, weight: 600 },
                    color: '#94a3b8'
                });
                
                if (['y', 'y1', 'left'].includes(scaleKey.toLowerCase())) {
                    scale.ticks.callback = scale.ticks.callback || formatRupiah;
                }
            });
        }

        chart.options.layout = Object.assign({}, chart.options.layout, {
            padding: { top: 24, right: 24, bottom: 24, left: 24 }
        });

        if (chart.options.plugins && chart.options.plugins.title) {
            chart.options.plugins.title.display = false;
        }

        chart.options.animation = {
            duration: 900,
            easing: 'easeInOutCubic'
        };

        chart.options.interaction = {
            mode: 'index',
            intersect: false
        };

        chart.options.responsive = true;
        chart.options.maintainAspectRatio = false;
    }

    // ====== CHART 1: AREA + LINE COMBO (TOTALLY NEW!) ======
    function transformRevenueAreaCombo() {
        const wrapper = document.getElementById('chart-revenue-monthly');
        if (!wrapper) return;
        
        const canvas = wrapper.querySelector('canvas');
        if (!canvas) return;

        const chart = findChartByCanvas(canvas);
        if (!chart) return;

        let dsReal = chart.data.datasets.find(d => (d.label || '').toLowerCase().includes('real')) || chart.data.datasets[0];
        let dsTarget = chart.data.datasets.find(d => (d.label || '').toLowerCase().includes('target')) || chart.data.datasets[1];

        if (!dsReal || !dsTarget) {
            polishChart(chart);
            chart.update('none');
            return;
        }

        // AREA CHART untuk Target (Purple)
        const areaTarget = {
            type: 'line',
            label: '🎯 Target Revenue',
            data: [...(dsTarget.data || [])],
            backgroundColor: COLORS.target.gradient,
            borderColor: COLORS.target.border,
            borderWidth: 4,
            pointBackgroundColor: COLORS.target.border,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 3,
            pointRadius: 6,
            pointHoverRadius: 9,
            pointHoverBorderWidth: 4,
            fill: true,
            tension: 0.4,
            yAxisID: 'y',
            order: 2
        };

        // AREA CHART untuk Real (Pink)
        const areaReal = {
            type: 'line',
            label: '💰 Real Revenue',
            data: [...(dsReal.data || [])],
            backgroundColor: COLORS.real.gradient,
            borderColor: COLORS.real.border,
            borderWidth: 4,
            pointBackgroundColor: COLORS.real.border,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 3,
            pointRadius: 6,
            pointHoverRadius: 9,
            pointHoverBorderWidth: 4,
            fill: true,
            tension: 0.4,
            yAxisID: 'y',
            order: 1
        };

        // LINE Achievement (Sky Blue)
        const achievementData = (areaReal.data || []).map((v, i) => {
            const target = Number(areaTarget.data?.[i] ?? 0);
            const real = Number(v ?? 0);
            return target ? ((real / target) * 100) : 0;
        });

        const lineAchievement = {
            type: 'line',
            label: '📊 Achievement %',
            data: achievementData,
            borderColor: COLORS.achievement.line,
            backgroundColor: COLORS.achievement.bg,
            pointBackgroundColor: COLORS.achievement.point,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 3,
            pointRadius: 7,
            pointHoverRadius: 10,
            pointHoverBorderWidth: 4,
            tension: 0.4,
            borderWidth: 4,
            yAxisID: 'yPercent',
            order: 0,
            fill: false
        };

        chart.config.type = 'line';
        chart.data.datasets = [areaTarget, areaReal, lineAchievement];

        // Scales Configuration
        chart.options.scales = chart.options.scales || {};
        
        chart.options.scales.x = {
            grid: {
                display: false,
                drawBorder: false
            },
            ticks: {
                font: { size: 13, weight: 700 },
                color: '#475569',
                padding: 10
            }
        };

        chart.options.scales.y = {
            position: 'left',
            beginAtZero: false,
            grid: {
                drawBorder: false,
                color: 'rgba(203, 213, 225, 0.4)',
                lineWidth: 1.5
            },
            ticks: {
                callback: formatRupiah,
                padding: 12,
                font: { size: 12, weight: 600 },
                color: '#94a3b8'
            }
        };

        chart.options.scales.yPercent = {
            position: 'right',
            beginAtZero: true,
            max: 120,
            grid: {
                drawOnChartArea: false,
                drawBorder: false
            },
            ticks: {
                callback: v => (v || 0).toFixed(0) + '%',
                padding: 12,
                font: { size: 12, weight: 600 },
                color: '#94a3b8',
                stepSize: 20
            }
        };

        polishChart(chart);
        chart.update();
    }

    // ====== CHART 2: ROUNDED BAR HORIZONTAL (TOTALLY NEW!) ======
    function transformAMRoundedBar() {
        const wrapper = document.getElementById('chart-am-distrib');
        if (!wrapper) return;
        
        const canvas = wrapper.querySelector('canvas');
        if (!canvas) return;

        const chart = findChartByCanvas(canvas);
        if (!chart) return;

        // Apply vibrant colors to datasets
        chart.data.datasets.forEach(ds => {
            ds.type = 'bar';
            ds.borderWidth = 0;
            ds.borderRadius = 12;
            ds.barThickness = 32;
            ds.maxBarThickness = 38;
            
            const labelLower = (ds.label || '').toLowerCase();
            if (labelLower.includes('hijau') || labelLower.includes('green') || labelLower.includes('100')) {
                ds.backgroundColor = COLORS.excellent.bg;
                ds.borderColor = COLORS.excellent.border;
                ds.label = '🟢 Hijau (≥100%)';
                ds.borderWidth = 0;
            } else if (labelLower.includes('oranye') || labelLower.includes('orange') || labelLower.includes('80')) {
                ds.backgroundColor = COLORS.good.bg;
                ds.borderColor = COLORS.good.border;
                ds.label = '🟠 Oranye (80-99%)';
                ds.borderWidth = 0;
            } else if (labelLower.includes('merah') || labelLower.includes('red') || labelLower.includes('0')) {
                ds.backgroundColor = COLORS.poor.bg;
                ds.borderColor = COLORS.poor.border;
                ds.label = '🔴 Merah (<80%)';
                ds.borderWidth = 0;
            }
        });

        chart.config.type = 'bar';
        chart.options.indexAxis = 'y';
        
        chart.options.scales = chart.options.scales || {};
        chart.options.scales.x = {
            stacked: true,
            beginAtZero: true,
            grid: {
                drawBorder: false,
                color: 'rgba(203, 213, 225, 0.4)',
                lineWidth: 1.5
            },
            ticks: {
                padding: 12,
                font: { size: 12, weight: 600 },
                color: '#94a3b8'
            }
        };

        chart.options.scales.y = {
            stacked: true,
            grid: {
                display: false,
                drawBorder: false
            },
            ticks: {
                font: { size: 13, weight: 700 },
                color: '#475569',
                padding: 12
            }
        };

        chart.options.plugins.legend.position = 'bottom';
        chart.options.plugins.legend.labels.padding = 20;

        polishChart(chart);
        chart.update();
    }

    // ====== EXECUTION ======
    function initializeCharts() {
        const instances = Array.from(Chart.instances?.values?.() || Object.values(Chart.instances || {}));
        instances.forEach(polishChart);

        transformRevenueAreaCombo();
        transformAMRoundedBar();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => setTimeout(initializeCharts, 200));
    } else {
        setTimeout(initializeCharts, 200);
    }
})();
</script>

{{-- ====== EXISTING SCRIPTS (Filter, Tab, AJAX) - UNCHANGED ====== --}}
<script>
$(document).ready(function() {
    console.log('Dashboard RLEGS V2 - Working Version with All Tabs');

    function applyFilters() {
        const filters = {
            period_type: $('#periodTypeFilter').val(),
            divisi_id: $('#divisiFilter').val(),
            sort_indicator: $('#sortIndicatorFilter').val(),
            tipe_revenue: $('#tipeRevenueFilter').val()
        };
        const params = new URLSearchParams();
        Object.keys(filters).forEach(k=>{ if(filters[k]) params.append(k, filters[k]); });
        window.location.href = window.location.pathname + '?' + params.toString();
    }
    $('#periodTypeFilter, #divisiFilter, #sortIndicatorFilter, #tipeRevenueFilter').on('change', applyFilters);

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const tabType = $(e.target).attr('data-tab');
        if (tabType === 'witel' || tabType === 'segment') {
            loadTabData(tabType);
        }
    });

    function loadTabData(tabType) {
        const filters = {
            tab: tabType,
            period_type: $('#periodTypeFilter').val() || 'YTD',
            divisi_id: $('#divisiFilter').val() || '',
            sort_indicator: $('#sortIndicatorFilter').val() || 'total_revenue',
            tipe_revenue: $('#tipeRevenueFilter').val() || 'all'
        };
        const tabContent = $(`#content-${tabType}`);
        tabContent.html(`<div class="table-container"><div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Memuat data ${getTabDisplayName(tabType)}...</p></div></div>`);
        $.ajax({
            url: "{{ route('dashboard.tab-data') }}",
            method: 'GET',
            data: filters,
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const dataArray = Array.isArray(response.data) ? response.data : Object.values(response.data);
                    if (dataArray.length > 0) { renderTabContent(tabContent, dataArray, tabType); bindClickableRows(); }
                    else { showEmptyState(tabContent, tabType); }
                } else { showEmptyState(tabContent, tabType); }
            },
            error: function(_, __, error) { showErrorState(tabContent, tabType, error); }
        });
    }
    function renderTabContent(tabContent, data, tabType) {
        let html = `<div class="table-container"><div class="table-responsive"><table class="table table-modern m-0"><thead><tr>${getTableHeaders(tabType)}</tr></thead><tbody>`;
        data.forEach((item, index) => { html += buildTableRow(item, index + 1, tabType); });
        html += `</tbody></table></div></div>`;
        tabContent.html(html);
    }
    function getTableHeaders(tabType) {
        const headers = {
            'witel': '<th>Ranking</th><th>Nama Witel</th><th class="text-center">Total Pelanggan</th><th class="text-end">Total Revenue</th><th class="text-end">Target Revenue</th><th class="text-end">Achievement</th>',
            'segment': '<th>Ranking</th><th>Nama Segmen</th><th>Divisi</th><th class="text-center">Total Pelanggan</th><th class="text-end">Total Revenue</th><th class="text-end">Target Revenue</th><th class="text-end">Achievement</th>'
        };
        return headers[tabType] || '';
    }
    function buildTableRow(item, ranking, tabType) {
        const detailUrl = item.detail_url || getDetailRoute(item.id, tabType);
        let row = `<tr class="clickable-row" data-url="${detailUrl}"><td><strong>${ranking}</strong></td>`;
        if (tabType === 'witel') {
            row += `<td>${item.nama || 'N/A'}</td><td class="text-center">${formatNumber(item.total_customers || 0)}</td>
                    <td class="text-end">Rp ${formatCurrency(item.total_revenue || 0)}</td>
                    <td class="text-end">Rp ${formatCurrency(item.total_target || 0)}</td>
                    <td class="text-end"><span class="status-badge bg-${item.achievement_color || 'secondary'}-soft">${(parseFloat(item.achievement_rate) || 0).toFixed(2)}%</span></td>
                    <td><a href="${detailUrl}"></a></td>`;
        } else if (tabType === 'segment') {
            row += `<td>${item.lsegment_ho || item.nama || 'N/A'}</td>
                    <td>${(item.divisi && item.divisi.nama) || item.divisi_nama || 'N/A'}</td>
                    <td class="text-center">${formatNumber(item.total_customers || 0)}</td>
                    <td class="text-end">Rp ${formatCurrency(item.total_revenue || 0)}</td>
                    <td class="text-end">Rp ${formatCurrency(item.total_target || 0)}</td>
                    <td class="text-end"><span class="status-badge bg-${item.achievement_color || 'secondary'}-soft">${(parseFloat(item.achievement_rate) || 0).toFixed(2)}%</span></td>
                    <td><a href="${detailUrl}"></a></td>`;
        }
        row += '</tr>'; return row;
    }
    function getTabDisplayName(tabType) { return {witel:'Witel',segment:'Segment'}[tabType] || tabType; }
    function getDetailRoute(id, tabType) {
        const baseRoutes = {'witel': "{{ route('witel.show', '') }}", 'segment': "{{ route('segment.show', '') }}"};
        return `${baseRoutes[tabType]}/${id}`;
    }
    function formatCurrency(amount) { return new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(amount)||0)); }
    function formatNumber(num) { return new Intl.NumberFormat('id-ID').format(parseInt(num)||0); }
    function showEmptyState(tabContent, tabType) {
        const icons = {witel:'fas fa-building',segment:'fas fa-chart-pie'};
        const messages = {witel:'Belum Ada Data Witel',segment:'Belum Ada Data Segmen'};
        const descriptions = {witel:'Tidak ada data Witel yang tersedia pada periode dan filter yang dipilih.',segment:'Tidak ada data Segmen yang tersedia pada periode dan filter yang dipilih.'};
        tabContent.html(`<div class="table-container"><div class="empty-state-enhanced"><i class="${icons[tabType]}"></i><h5>${messages[tabType]}</h5><p>${descriptions[tabType]}</p></div></div>`);
    }
    function showErrorState(tabContent, tabType, error) {
        tabContent.html(`<div class="table-container"><div class="empty-state-enhanced"><i class="fas fa-exclamation-triangle text-danger"></i><h5>Gagal Memuat Data</h5><p>Terjadi kesalahan saat memuat data ${getTabDisplayName(tabType)}. Silakan coba lagi.</p><small class="text-muted">Error: ${error}</small></div></div>`);
    }
    function bindClickableRows() {
        $('.clickable-row').off('click.dashboard').on('click.dashboard', function(e) {
            if (e.target.tagName === 'A' || e.target.closest('a') || e.target.tagName === 'BUTTON') return;
            const url = $(this).attr('data-url'); if (url && url !== '#') window.location.href = url;
        });
    }
    bindClickableRows();

    window.exportData = function() {
        const filters = {
            period_type: $('#periodTypeFilter').val(),
            divisi_id: $('#divisiFilter').val(),
            sort_indicator: $('#sortIndicatorFilter').val(),
            tipe_revenue: $('#tipeRevenueFilter').val()
        };
        const params = new URLSearchParams();
        Object.keys(filters).forEach(k=>{ if(filters[k]) params.append(k, filters[k]); });
        window.open("{{ route('dashboard.export') }}?" + params.toString(), '_blank');
    };
});
</script>
@endsection