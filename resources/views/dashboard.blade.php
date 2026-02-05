@extends('layouts.main')

@section('title', 'Dashboard RLEGS')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/overview.css') }}">
@endsection

@section('content')
<div class="main-content">
    <!-- Enhanced Header dengan YTD/MTD Filter -->
    <div class="header-dashboard">
        <div class="header-content">
            <div class="header-text">
                <h1 class="header-title">Overview Data</h1>
                <p class="header-subtitle">
                    Monitoring Pendapatan RLEGS
                    @if(isset($cardData['period_text']))
                        <span class="period-text">{{ $cardData['period_text'] }}</span>
                    @endif
                </p>
            </div>
            <div class="header-actions">
                <!-- Filter Group -->
                <div class="filter-group">
                    <!-- Period Type Filter (YTD/MTD) -->
                    <select id="periodTypeFilter" class="form-select filter-select js-enhance">
                        @foreach($filterOptions['period_types'] ?? ['YTD' => 'Year to Date', 'MTD' => 'Month to Date', 'ALL' => 'Semua Periode'] as $key => $label)
                        <option value="{{ $key }}" {{ $key == ($filters['period_type'] ?? 'YTD') ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>

                    <!-- Divisi Filter dengan Kode -->
                    <select id="divisiFilter" class="form-select filter-select js-enhance">
                        <option value="all">Semua Divisi</option>
                        @foreach($filterOptions['divisi'] ?? [] as $divisi)
                        <option value="{{ $divisi->id }}" {{ $divisi->id == ($filters['divisi_id'] ?? '') ? 'selected' : '' }}>
                            {{ $divisi->kode ?? $divisi->nama }}
                        </option>
                        @endforeach
                    </select>

                    <!-- Source Data Filter (REGULER/NGTMA) -->
                    <select id="sourceDataFilter" class="form-select filter-select js-enhance">
                        @foreach($filterOptions['source_data'] ?? ['all' => 'Semua Tipe'] as $key => $label)
                        <option value="{{ $key }}" {{ $key == ($filters['source_data'] ?? 'all') ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>

                    <!-- Tipe Revenue Filter (HO/BILL) -->
                    <select id="tipeRevenueFilter" class="form-select filter-select js-enhance">
                        @foreach($filterOptions['tipe_revenue'] ?? ['all' => 'Semua'] as $key => $label)
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

    <!-- 1. CARD GROUP SECTION - DYNAMIC BASED ON FILTER -->
    <div class="row g-4 mb-4">
        @php
            $showSold = !isset($filters['tipe_revenue']) || $filters['tipe_revenue'] === 'all' || $filters['tipe_revenue'] === 'HO';
            $showBill = !isset($filters['tipe_revenue']) || $filters['tipe_revenue'] === 'all' || $filters['tipe_revenue'] === 'BILL';
        @endphp

        @if($showSold)
        <!-- Revenue Sold Section -->
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-title">Total Real Revenue Sold</div>
                <div class="stats-value">{{ $cardData['total_real_sold_formatted'] ?? 'Rp 0' }}</div>
                <div class="stats-period">Revenue dari HO (Sold)</div>
                <div class="stats-icon icon-revenue">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-title">Target Revenue Sold</div>
                <div class="stats-value">{{ $cardData['total_target_sold_formatted'] ?? 'Rp 0' }}</div>
                <div class="stats-period">Target dari HO (Sold)</div>
                <div class="stats-icon icon-target">
                    <i class="fas fa-bullseye"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card is-achievement">
                <div class="stats-title">Achievement Rate Sold</div>
                <div class="stats-value">{{ number_format($cardData['achievement_sold'] ?? 0, 2) }}%<span class="achievement-indicator achievement-{{ $cardData['achievement_sold_color'] ?? 'secondary' }}"></span></div>
                <div class="stats-period">Pencapaian Revenue Sold</div>
                <div class="stats-icon icon-achievement">
                    <i class="fas fa-medal"></i>
                </div>
            </div>
        </div>
        @endif

        @if($showBill)
        <!-- Revenue Bill Section -->
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-title">Total Real Revenue Bill</div>
                <div class="stats-value">{{ $cardData['total_real_bill_formatted'] ?? 'Rp 0' }}</div>
                <div class="stats-period">Revenue dari Bill (Invoice)</div>
                <div class="stats-icon icon-revenue">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-title">Target Revenue Bill</div>
                <div class="stats-value">{{ $cardData['total_target_bill_formatted'] ?? 'Rp 0' }}</div>
                <div class="stats-period">Target dari Bill (Invoice)</div>
                <div class="stats-icon icon-target">
                    <i class="fas fa-bullseye"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card is-achievement">
                <div class="stats-title">Achievement Rate Bill</div>
                <div class="stats-value">{{ number_format($cardData['achievement_bill'] ?? 0, 2) }}%<span class="achievement-indicator achievement-{{ $cardData['achievement_bill_color'] ?? 'secondary' }}"></span></div>
                <div class="stats-period">Pencapaian Revenue Bill</div>
                <div class="stats-icon icon-achievement">
                    <i class="fas fa-medal"></i>
                </div>
            </div>
        </div>
        @endif
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
                                <tr class="clickable-row {{ $customer->has_revenue ?? true ? '' : 'row-no-revenue' }}" data-url="{{ route('corporate-customer.show', $customer->id) }}">
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td>
                                        <div>
                                            <div class="fw-semibold">{{ $customer->nama ?? '-' }}</div>
                                            @if(!empty($customer->nipnas))
                                                <small class="text-muted">{{ $customer->nipnas }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ ($customer->divisi_nama && $customer->divisi_nama !== 'N/A') ? $customer->divisi_nama : '-' }}</td>
                                    <td>{{ ($customer->segment_nama && $customer->segment_nama !== 'N/A') ? $customer->segment_nama : '-' }}</td>
                                    <td class="text-end">
                                        @if(($customer->total_revenue ?? 0) > 0)
                                            {{ $customer->total_revenue_formatted ?? 'Rp ' . number_format($customer->total_revenue, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if(($customer->total_target ?? 0) > 0)
                                            Rp {{ number_format($customer->total_target, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @php
                                            $rate = $customer->achievement_rate ?? 0;
                                            $color = $customer->achievement_color ?? 'secondary';
                                            if ($rate >= 100) {
                                                $tooltip = 'Excellent: Target tercapai dengan baik!';
                                            } elseif ($rate >= 80) {
                                                $tooltip = 'Good: Mendekati target, perlu sedikit peningkatan';
                                            } elseif ($rate > 0) {
                                                $tooltip = 'Poor: Perlu peningkatan signifikan';
                                            } else {
                                                $tooltip = 'Belum ada data revenue';
                                            }
                                        @endphp
                                        <span class="status-badge bg-{{ $color }}-soft" data-tooltip="{{ $tooltip }}">
                                            {{ $rate > 0 ? number_format($rate, 2) . '%' : '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('corporate-customer.show', $customer->id) }}"
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
                                    <th class="text-end">Total Revenue</th>
                                    <th class="text-end">Target Revenue</th>
                                    <th class="text-end">Achievement</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($performanceData['account_manager'] as $index => $am)
                                <tr class="clickable-row {{ $am->has_revenue ?? true ? '' : 'row-no-revenue' }}" data-url="{{ route('account-manager.show', $am->id) }}">
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ asset('img/profile.png') }}" class="am-profile-pic" alt="{{ $am->nama }}">
                                            <span class="ms-2 clickable-name">{{ $am->nama }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $am->witel_nama ?? '-' }}</td>
                                    <td class="text-end">
                                        @if(($am->total_revenue ?? 0) > 0)
                                            {{ $am->total_revenue_formatted ?? 'Rp ' . number_format($am->total_revenue, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if(($am->total_target ?? 0) > 0)
                                            Rp {{ number_format($am->total_target, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @php
                                            $rate = $am->achievement_rate ?? 0;
                                            $color = $am->achievement_color ?? 'secondary';
                                        @endphp
                                        <span class="status-badge bg-{{ $color }}-soft">
                                            {{ $rate > 0 ? number_format($rate, 2) . '%' : '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('account-manager.show', $am->id) }}"
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

            <!-- Witel Tab - SERVER-SIDE DATA -->
            <div class="tab-pane" id="content-witel" role="tabpanel">
                <div class="table-container">
                    @if(isset($performanceData['witel']) && $performanceData['witel']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern m-0">
                            <thead>
                                <tr>
                                    <th>Ranking</th>
                                    <th>Nama Witel</th>
                                    <th class="text-end">Total Revenue</th>
                                    <th class="text-end">Target Revenue</th>
                                    <th class="text-end">Achievement</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($performanceData['witel'] as $index => $witel)
                                <tr class="clickable-row" data-url="{{ route('witel.show', $witel->id) }}">
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td>{{ $witel->nama ?? '-' }}</td>
                                    <td class="text-end">
                                        @if(($witel->total_revenue ?? 0) > 0)
                                            {{ $witel->total_revenue_formatted ?? 'Rp ' . number_format($witel->total_revenue, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if(($witel->total_target ?? 0) > 0)
                                            Rp {{ number_format($witel->total_target, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @php
                                            $rate = $witel->achievement_rate ?? 0;
                                            $color = $witel->achievement_color ?? 'secondary';
                                        @endphp
                                        <span class="status-badge bg-{{ $color }}-soft">
                                            {{ $rate > 0 ? number_format($rate, 2) . '%' : '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('witel.show', $witel->id) }}"
                                           class="btn btn-sm btn-primary">Detail</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="empty-state-enhanced">
                        <i class="fas fa-building"></i>
                        <h5>Belum Ada Data Witel</h5>
                        <p>Tidak ada Witel yang memiliki data pendapatan pada periode dan filter yang dipilih.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Segment Tab - SERVER-SIDE DATA -->
            <div class="tab-pane" id="content-segment" role="tabpanel">
                <div class="table-container">
                    @if(isset($performanceData['segment']) && $performanceData['segment']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern m-0">
                            <thead>
                                <tr>
                                    <th>Ranking</th>
                                    <th>Nama Segmen</th>
                                    <th class="text-end">Total Revenue</th>
                                    <th class="text-end">Target Revenue</th>
                                    <th class="text-end">Achievement</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($performanceData['segment'] as $index => $segment)
                                <tr>
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td>{{ $segment->lsegment_ho ?? '-' }}</td>
                                    <td class="text-end">
                                        @if(($segment->total_revenue ?? 0) > 0)
                                            {{ $segment->total_revenue_formatted ?? 'Rp ' . number_format($segment->total_revenue, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if(($segment->total_target ?? 0) > 0)
                                            Rp {{ number_format($segment->total_target, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @php
                                            $rate = $segment->achievement_rate ?? 0;
                                            $color = $segment->achievement_color ?? 'secondary';
                                        @endphp
                                        <span class="status-badge bg-{{ $color }}-soft">
                                            {{ $rate > 0 ? number_format($rate, 2) . '%' : '-' }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="empty-state-enhanced">
                        <i class="fas fa-chart-pie"></i>
                        <h5>Belum Ada Data Segmen</h5>
                        <p>Tidak ada Segmen yang memiliki data pendapatan pada periode dan filter yang dipilih.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 3. VISUALISASI PENDAPATAN BULANAN -->
    <div class="row mt-4 equal-cards">
        <!-- Line Chart Total Revenue Bulanan - DENGAN FORMAT LABEL Y-AXIS -->
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
                        <canvas id="monthlyRevenueChart"></canvas>
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
                        <canvas id="amDistributionChart" style="max-height:320px"></canvas>
                    </div>
                    <div id="amDistributionLegend" class="am-legend-grid mt-3"></div>
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

<!-- Custom Select Enhancement -->
<script>
(function(){
  function enhanceSelect(sel){
    const wrapper = document.createElement('div');
    wrapper.className = 'select-pill';
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'select-pill__btn';
    const menu = document.createElement('div');
    menu.className = 'select-menu';
    btn.textContent = sel.options[sel.selectedIndex]?.text || sel.options[0]?.text || 'Pilih';

    [...sel.options].forEach((opt,i)=>{
      const a = document.createElement('button');
      a.type='button';
      a.className='select-item';
      a.textContent = opt.text;
      a.setAttribute('role','option');
      if(opt.selected) a.setAttribute('aria-selected','true');
      a.addEventListener('click', (e)=>{
        e.stopPropagation();
        sel.selectedIndex = i;
        btn.textContent = opt.text;
        menu.querySelectorAll('.select-item').forEach(x=>x.removeAttribute('aria-selected'));
        a.setAttribute('aria-selected','true');
        sel.dispatchEvent(new Event('change', {bubbles:true}));
        menu.classList.remove('is-open');
      });
      menu.appendChild(a);
    });

    btn.addEventListener('click', (e)=>{
      e.stopPropagation();
      document.querySelectorAll('.select-menu.is-open').forEach(m=>m.classList.remove('is-open'));
      menu.classList.toggle('is-open');
    });

    document.addEventListener('click', ()=> menu.classList.remove('is-open'));

    sel.style.display='none';
    sel.parentNode.insertBefore(wrapper, sel);
    wrapper.appendChild(sel);
    wrapper.appendChild(btn);
    wrapper.appendChild(menu);
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('select.filter-select.js-enhance').forEach(enhanceSelect);
  });
})();
</script>

<!-- Monthly Revenue Chart - DENGAN FORMAT Y-AXIS -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('monthlyRevenueChart');
  if (!el) return;

  // Data dari controller (sudah dalam format juta)
  let labels = @json($monthlyLabels ?? []);
  let real   = @json($monthlyReal ?? []);
  let target = @json($monthlyTarget ?? []);

  // Fallback ke tabel jika tidak ada data dari controller
  const fallback = @json($revenueTable ?? []);
  if ((!labels || !labels.length) && Array.isArray(fallback) && fallback.length) {
    labels = fallback.map(r => r.bulan ?? '');
    real   = fallback.map(r => Number(r.realisasi ?? 0) / 1000000);
    target = fallback.map(r => Number(r.target ?? 0) / 1000000);
  }

  const n = Math.min(labels.length, real.length, target.length);
  if (!n) return;

  labels = labels.slice(0, n);
  real   = real.slice(0, n);
  target = target.slice(0, n);

  const achieve = real.map((v, i) => (target[i] ? (v / target[i]) * 100 : 0));

  if (window._monthlyRevenueChart) window._monthlyRevenueChart.destroy();

  const CAT = 0.50;
  const BAR = 0.85;

  window._monthlyRevenueChart = new Chart(el.getContext('2d'), {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Real Revenue',
          type: 'bar',
          yAxisID: 'y',
          data: real,
          backgroundColor: 'rgba(84,167,248,0.95)',
          borderColor: 'rgba(84,167,248,1)',
          borderWidth: 1,
          borderRadius: 6,
          categoryPercentage: CAT,
          barPercentage: BAR,
          order: 2
        },
        {
          label: 'Target Revenue',
          type: 'bar',
          yAxisID: 'y',
          data: target,
          backgroundColor: 'rgba(217,221,231,0.95)',
          borderColor: 'rgba(217,221,231,1)',
          borderWidth: 1,
          borderRadius: 6,
          categoryPercentage: CAT,
          barPercentage: BAR,
          order: 2
        },
        {
          label: 'Achievement (%)',
          type: 'line',
          yAxisID: 'y1',
          data: achieve,
          borderColor: '#ff4d73',
          backgroundColor: '#ff4d73',
          borderWidth: 3,
          pointRadius: 4,
          pointHoverRadius: 5,
          pointBackgroundColor: '#fff',
          pointBorderColor: '#ff4d73',
          pointBorderWidth: 2,
          tension: 0,
          fill: false,
          spanGaps: false,
          order: 1
        }
      ]
    },
    options: {
      interaction: { mode: 'index', intersect: false },
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: { usePointStyle: true, boxWidth: 10, boxHeight: 10, padding: 15 }
        },
        tooltip: {
          callbacks: {
            label(ctx) {
              if (ctx.dataset.yAxisID === 'y1') {
                return ` ${ctx.dataset.label}: ${ctx.formattedValue}%`;
              }
              const val = Number(ctx.raw || 0);
              let formatted;
              if (val >= 1000000) {
                formatted = (val / 1000000).toFixed(2) + ' Triliun';
              } else if (val >= 1000) {
                formatted = (val / 1000).toFixed(2) + ' Miliar';
              } else {
                formatted = val.toFixed(2) + ' Juta';
              }
              return ` ${ctx.dataset.label}: ${formatted}`;
            }
          }
        }
      },
      scales: {
        x: {
          offset: true,
          grid: { color: 'rgba(0,0,0,0.04)' },
          ticks: { color: '#6b7280', font: { size: 11 } }
        },
        y: {
          position: 'left',
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,0.05)' },
          ticks: {
            color: '#6b7280',
            callback: function(value) {
              if (value >= 1000000) {
                return (value / 1000000).toFixed(1) + ' Triliun';
              } else if (value >= 1000) {
                return (value / 1000).toFixed(1) + ' Miliar';
              } else if (value >= 1) {
                return value.toFixed(1) + ' Juta';
              } else {
                return value.toFixed(2);
              }
            },
            font: { size: 11 }
          },
          title: {
            display: true,
            text: 'Revenue (Juta Rp)',
            color: '#6b7280',
            font: { size: 12 }
          }
        },
        y1: {
          position: 'right',
          beginAtZero: true,
          suggestedMax: 120,
          grid: { drawOnChartArea: false },
          ticks: {
            color: '#6b7280',
            callback: v => `${v}%`,
            font: { size: 11 }
          },
          title: {
            display: true,
            text: 'Achievement (%)',
            color: '#6b7280',
            font: { size: 12 }
          }
        }
      },
      layout: { padding: { top: 10, right: 12, left: 4, bottom: 0 } }
    }
  });
});
</script>

<!-- AM Distribution Chart (Doughnut) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('amDistributionChart');
  if (!el) return;

  const distribution = @json($amPerformanceDistribution ?? ['Hijau' => 0, 'Oranye' => 0, 'Merah' => 0]);

  const rows = [
    { status:'Hijau',  count: distribution.Hijau || 0,  color:'#10b981', label:'≥ 100%' },
    { status:'Oranye', count: distribution.Oranye || 0, color:'#f59e0b', label:'80–99%' },
    { status:'Merah',  count: distribution.Merah || 0,  color:'#ef4444', label:'< 80%'  },
  ];

  const labels = rows.map(r=>r.status);
  const data   = rows.map(r=>r.count);
  const colors = rows.map(r=>r.color);
  const total  = data.reduce((a,b)=>a+b,0);

  if (window._amDistributionChart) window._amDistributionChart.destroy();

  const centerTextPlugin = {
    id:'centerText',
    beforeDraw(chart){
      const {ctx, chartArea} = chart;
      if(!chartArea) return;
      const {width, top, height} = chartArea;
      const cx = width/2, cy = top + height/2 - 6;
      ctx.save();
      ctx.textAlign='center';
      ctx.textBaseline='middle';
      ctx.fillStyle='#111827';
      ctx.font='700 30px Poppins,system-ui,sans-serif';
      ctx.fillText(String(total), cx, cy);
      ctx.fillStyle='#6b7280';
      ctx.font='500 12px Poppins,system-ui,sans-serif';
      ctx.fillText('Total AM', cx, cy+20);
      ctx.restore();
    }
  };

  window._amDistributionChart = new Chart(el.getContext('2d'), {
    type:'doughnut',
    data:{
      labels,
      datasets:[{
        data,
        backgroundColor:colors,
        borderColor:'#fff',
        borderWidth:4,
        hoverOffset:10,
        hoverBorderWidth:4
      }]
    },
    options:{
      responsive:true,
      maintainAspectRatio:false,
      cutout:'70%',
      plugins:{
        legend:{display:false},
        tooltip:{
          backgroundColor:'rgba(17,24,39,.92)',
          padding:12,
          callbacks:{
            title:(items)=> `${rows[items[0].dataIndex].status} • ${rows[items[0].dataIndex].label}`,
            label:(ctx)=> {
              const cnt = ctx.parsed||0;
              const pct = total ? (cnt/total*100).toFixed(1) : '0.0';
              return ` ${cnt} AM (${pct}%)`;
            }
          }
        }
      }
    },
    plugins:[centerTextPlugin]
  });

  const legendEl = document.getElementById('amDistributionLegend');
  if (legendEl) {
    const toRGBA = (hex, a=0.15) => {
      const m = String(hex).replace('#','');
      const [r,g,b] = m.length===3
        ? m.split('').map(x=>parseInt(x+x,16))
        : [m.slice(0,2),m.slice(2,4),m.slice(4,6)].map(x=>parseInt(x,16));
      return `rgba(${r},${g},${b},${a})`;
    };

    legendEl.classList.add('am-legend-grid');
    legendEl.innerHTML = rows.map(r => {
      const pct = total ? (r.count/total*100).toFixed(1) : '0.0';
      return `
        <div class="am-legend-card2">
          <div class="am-legend-head">
            <span class="dot" style="background:${r.color}"></span>
            <span class="label">${r.status}</span>
            <span class="range">• ${r.label}</span>
          </div>
          <div class="am-legend-body">
            <div class="count"><strong>${r.count}</strong> AM</div>
            <div class="pct-chip" style="background:${toRGBA(r.color)}; color:${r.color}">
              ${pct}%
            </div>
          </div>
        </div>
      `;
    }).join('');
  }
});
</script>

<!-- Main Dashboard JavaScript -->
<script>
$(document).ready(function() {
    console.log('Dashboard RLEGS V2 - Updated with Revenue Sold & Bill');

    // =====================================
    // FILTER HANDLING
    // =====================================
    function applyFilters() {
        const filters = {
            period_type: $('#periodTypeFilter').val(),
            divisi_id: $('#divisiFilter').val(),
            source_data: $('#sourceDataFilter').val(),
            tipe_revenue: $('#tipeRevenueFilter').val()
        };

        console.log('Applying filters:', filters);
        showLoading();

        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key] && filters[key] !== 'all') {
                params.append(key, filters[key]);
            }
        });

        const newUrl = window.location.pathname + '?' + params.toString();
        console.log('Redirecting to:', newUrl);
        window.location.href = newUrl;
    }

    $('#periodTypeFilter, #divisiFilter, #sourceDataFilter, #tipeRevenueFilter').on('change', function() {
        console.log('Filter changed:', this.id, this.value);
        applyFilters();
    });

    // =====================================
    // CLICKABLE ROWS
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

    bindClickableRows();

    // =====================================
    // EXPORT FUNCTION
    // =====================================
    window.exportData = function() {
        const filters = {
            period_type: $('#periodTypeFilter').val(),
            divisi_id: $('#divisiFilter').val(),
            source_data: $('#sourceDataFilter').val(),
            tipe_revenue: $('#tipeRevenueFilter').val()
        };

        console.log('Exporting with filters:', filters);

        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key] && filters[key] !== 'all') {
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
    // TOOLTIPS
    // =====================================
    $(document).off('mouseenter.statusBadge mouseleave.statusBadge');

    $(document).on('mouseenter.statusBadge', '.status-badge', function () {
        const $el = $(this);
        const raw = ($el.text() || '').replace('%','');
        const val = parseFloat(raw);
        let msg = 'Achievement Rate';
        if (!isNaN(val)) {
            if (val >= 100) msg = 'Excellent: Target tercapai dengan baik!';
            else if (val >= 80) msg = 'Good: Mendekati target, perlu sedikit peningkatan';
            else if (val > 0) msg = 'Poor: Perlu peningkatan signifikan';
            else msg = 'Belum ada data revenue';
        }
        $el.attr('data-tooltip', msg);

        const rect = this.getBoundingClientRect();
        const table = this.closest('table');
        const thead = table ? table.querySelector('thead') : null;
        const headBottom = thead ? thead.getBoundingClientRect().bottom : 0;

        const nearTop = rect.top < 140;
        const underStickyHead = rect.top < (headBottom + 12);

        const tabPane = this.closest('.tab-pane');
        const forceBottomInAjax = tabPane && ['content-account-manager','content-witel','content-segment']
            .includes(tabPane.id);

        $el.toggleClass('tooltip-bottom', (nearTop || underStickyHead || forceBottomInAjax));

        const rightOverflow = (rect.left + 260) > window.innerWidth;
        $el.toggleClass('tooltip-left', rightOverflow);
    }).on('mouseleave.statusBadge', '.status-badge', function () {
        $(this).removeAttr('data-tooltip').removeClass('tooltip-bottom tooltip-left');
    });

    // =====================================
    // INITIALIZATION COMPLETE
    // =====================================
    console.log('✅ Dashboard RLEGS V2 - Ready with Revenue Sold & Bill Metrics');
    hideLoading();
});
</script>
@endsection