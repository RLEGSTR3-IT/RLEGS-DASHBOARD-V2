@extends('layouts.main')

@section('title', 'Detail Account Manager')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<link rel="stylesheet" href="{{ asset('css/detailAM.css') }}">

@endsection

@section('content')
<div class="main-content">
    <!-- Profile Overview -->
    <div class="profile-overview">
        <div class="profile-avatar-container">
            <img src="{{ asset($accountManager->user && $accountManager->user->profile_image ? 'storage/'.$accountManager->user->profile_image : 'img/profile.png') }}"
                 class="profile-avatar" alt="{{ $accountManager->nama }}">
        </div>
        <div class="profile-details">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="profile-name mb-0">{{ $accountManager->nama }}</h2>

                <!-- Enhanced Category Badge -->
                @php
                    $badgeClass = 'enterprise';
                    if($amCategory['category'] === 'GOVERNMENT') {
                        $badgeClass = 'government';
                    } elseif($amCategory['category'] === 'MULTI') {
                        $badgeClass = 'multi';
                    }
                @endphp
                <span class="category-badge {{ $badgeClass }}">
                    @if($amCategory['category'] === 'ENTERPRISE')
                        <i class="fas fa-building me-2"></i>
                    @elseif($amCategory['category'] === 'GOVERNMENT')
                        <i class="fas fa-university me-2"></i>
                    @else
                        <i class="fas fa-layer-group me-2"></i>
                    @endif
                    {{ $amCategory['label'] }}
                </span>
            </div>
            <div class="profile-meta">
                <div class="meta-item">
                    <i class="lni lni-id-card"></i>
                    <span>NIK: {{ $accountManager->nik }}</span>
                </div>
                <div class="meta-item">
                    <i class="lni lni-map-marker"></i>
                    <span>WITEL: {{ $accountManager->witel->nama ?? 'N/A' }}</span>
                </div>
                <div class="meta-item divisi-item">
                    <i class="lni lni-network"></i>
                    <span>DIVISI:</span>
                    <div class="divisi-list">
                        @forelse($accountManager->divisis as $divisi)
                            @php
                                $divisiClass = '';
                                switch(strtoupper($divisi->nama)) {
                                    case 'DPS':
                                        $divisiClass = 'dps';
                                        break;
                                    case 'DSS':
                                        $divisiClass = 'dss';
                                        break;
                                    case 'DGS':
                                        $divisiClass = 'dgs';
                                        break;
                                    default:
                                        $divisiClass = '';
                                }
                            @endphp
                            <span class="divisi-pill {{ $divisiClass }}">{{ $divisi->nama }}</span>
                        @empty
                            <span class="text-muted">N/A</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Division Selector Section (moved from profile) -->
    @if(count($divisionRankings) > 0)
    <div class="division-selector-section">
        <div class="division-selector-label">
            <i class="fas fa-layer-group"></i>
            Pilih Divisi untuk Peringkat:
        </div>
        <div class="division-selector">
            <select id="division-ranking-select" class="selectpicker" title="Pilih Divisi" data-style="btn-outline-primary">
                @foreach($divisionRankings as $divisiId => $ranking)
                    <option value="{{ $divisiId }}" {{ $selectedDivisiId == $divisiId ? 'selected' : '' }}>
                        {{ $ranking['nama'] }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    @endif

    <!-- Category Filter (Only for Multi Division AM) -->
    @if($needsCategoryFilter)
    <div class="category-filter-container">
        <div class="category-filters">
            <div class="filter-label">
                <i class="fas fa-filter me-2"></i>
                Tampilkan Peringkat Sebagai:
            </div>
            <div class="filter-controls">
                <select id="category-filter-select" class="selectpicker" data-style="btn-outline-primary">
                    <option value="enterprise" {{ $selectedCategoryFilter == 'enterprise' ? 'selected' : '' }}>
                        <i class="fas fa-building me-2"></i>Enterprise
                    </option>
                    <option value="government" {{ $selectedCategoryFilter == 'government' ? 'selected' : '' }}>
                        <i class="fas fa-university me-2"></i>Government
                    </option>
                </select>
                <small class="text-muted">Filter ini mempengaruhi peringkat witel Anda</small>
            </div>
        </div>
    </div>
    @endif

    <!-- Rankings - 3 Cards -->
    <div class="rankings-container">
        @php
            $globalRankIcon = "1-10.svg";
            if ($globalRanking['position'] > 10 && $globalRanking['position'] <= 50) {
                $globalRankIcon = "10-50.svg";
            } elseif ($globalRanking['position'] > 50) {
                $globalRankIcon = "up100.svg";
            }

            $witelRankIcon = "1-10.svg";
            if (is_numeric($witelRanking['position'])) {
                if ($witelRanking['position'] > 10 && $witelRanking['position'] <= 50) {
                    $witelRankIcon = "10-50.svg";
                } elseif ($witelRanking['position'] > 50) {
                    $witelRankIcon = "up100.svg";
                }
            }

            // Calculate ranking changes
            $globalChange = isset($globalRanking['position_change']) ? $globalRanking['position_change'] : 0;
            if ($globalChange > 0) {
                $globalChangeClass = 'text-success';
                $globalChangeBadgeClass = 'up';
                $globalChangeIcon = 'lni-arrow-up';
                $globalChangeText = 'naik ' . $globalChange;
                $globalBadgeText = 'Naik ' . $globalChange;
            } elseif ($globalChange < 0) {
                $globalChangeClass = 'text-danger';
                $globalChangeBadgeClass = 'down';
                $globalChangeIcon = 'lni-arrow-down';
                $globalChangeText = 'turun ' . abs($globalChange);
                $globalBadgeText = 'Turun ' . abs($globalChange);
            } else {
                $globalChangeClass = 'text-muted';
                $globalChangeBadgeClass = 'neutral';
                $globalChangeIcon = 'lni-minus';
                $globalChangeText = 'tetap';
                $globalBadgeText = 'Tetap';
            }

            $witelChange = isset($witelRanking['position_change']) ? $witelRanking['position_change'] : 0;
            if ($witelChange > 0) {
                $witelChangeClass = 'text-success';
                $witelChangeBadgeClass = 'up';
                $witelChangeIcon = 'lni-arrow-up';
                $witelChangeText = 'naik ' . $witelChange;
                $witelBadgeText = 'Naik ' . $witelChange;
            } elseif ($witelChange < 0) {
                $witelChangeClass = 'text-danger';
                $witelChangeBadgeClass = 'down';
                $witelChangeIcon = 'lni-arrow-down';
                $witelChangeText = 'turun ' . abs($witelChange);
                $witelBadgeText = 'Turun ' . abs($witelChange);
            } else {
                $witelChangeClass = 'text-muted';
                $witelChangeBadgeClass = 'neutral';
                $witelChangeIcon = 'lni-minus';
                $witelChangeText = 'tetap';
                $witelBadgeText = 'Tetap';
            }

            $currentMonth = date('F');
            $previousMonth = date('F', strtotime('-1 month'));

            // Translate month names
            $monthNames = [
                'January' => 'Januari',
                'February' => 'Februari',
                'March' => 'Maret',
                'April' => 'April',
                'May' => 'Mei',
                'June' => 'Juni',
                'July' => 'Juli',
                'August' => 'Agustus',
                'September' => 'September',
                'October' => 'Oktober',
                'November' => 'November',
                'December' => 'Desember'
            ];

            $currentMonthID = $monthNames[$currentMonth] ?? $currentMonth;
            $previousMonthID = $monthNames[$previousMonth] ?? $previousMonth;
        @endphp


<div class="row">
    <!-- Card 1: Global Ranking -->
    <div class="col-md-4 mb-3">
        <a href="{{ route('leaderboard') }}" class="ranking-card global">
            <div class="ranking-icon">
                <img src="{{ asset('img/' . $globalRankIcon) }}" alt="Peringkat" width="40" height="40">
            </div>
            <div class="ranking-info">
                <div class="ranking-title">Peringkat Global</div>
                <div class="ranking-value">
                    {{ $globalRanking['position'] }} dari {{ $globalRanking['total'] }}
                    @if ($globalChange != 0)
                        <span class="{{ $globalChangeClass }} ml-2" style="font-size: 14px;">
                            <i class="lni {{ $globalChangeIcon }}"></i>
                        </span>
                    @endif
                </div>
                <span class="rank-change-detail {{ $globalChangeBadgeClass }}">{{ $globalChangeText }} dari {{ $previousMonthID }}</span>
            </div>
            @if($globalChange != 0)
                <div class="rank-badge {{ $globalChangeBadgeClass }}">
                    <i class="lni {{ $globalChangeIcon }}"></i>
                    {{ $globalBadgeText }}
                </div>
            @endif
        </a>
    </div>

    <!-- Card 2: Witel Ranking -->
    <div class="col-md-4 mb-3">
        <div class="ranking-card witel">
            <div class="ranking-icon">
                <img src="{{ asset('img/' . $witelRankIcon) }}" alt="Peringkat" width="40" height="40">
            </div>
            <div class="ranking-info">
                <div class="ranking-title">
                    Peringkat Witel
                    @if($needsCategoryFilter)
                        <span class="divisi-badge">{{ $witelRanking['category_label'] ?? ucfirst($selectedCategoryFilter) }}</span>
                    @endif
                </div>
                <div class="ranking-value">
                    {{ $witelRanking['position'] }} dari {{ $witelRanking['total'] }}
                    @if ($witelChange != 0 && is_numeric($witelRanking['position']))
                        <span class="{{ $witelChangeClass }} ml-2" style="font-size: 14px;">
                            <i class="lni {{ $witelChangeIcon }}"></i>
                        </span>
                    @endif
                </div>
                @if(is_numeric($witelRanking['position']))
                    <span class="rank-change-detail {{ $witelChangeBadgeClass }}">{{ $witelChangeText }} dari {{ $previousMonthID }}</span>
                @else
                    <span class="rank-change-detail text-muted">belum ada data</span>
                @endif
            </div>
            @if($witelChange != 0 && is_numeric($witelRanking['position']))
                <div class="rank-badge {{ $witelChangeBadgeClass }}">
                    <i class="lni {{ $witelChangeIcon }}"></i>
                    {{ $witelBadgeText }}
                </div>
            @endif
        </div>
    </div>

    <!-- Card 3: Division Ranking -->
    <div class="col-md-4 mb-3">
        <div class="division-rankings-container">
            @if(count($divisionRankings) > 0)
                @foreach($divisionRankings as $divisiId => $ranking)
                    @php
                        $divisiRankIcon = "1-10.svg";
                        if (is_numeric($ranking['position'])) {
                            if ($ranking['position'] > 10 && $ranking['position'] <= 50) {
                                $divisiRankIcon = "10-50.svg";
                            } elseif ($ranking['position'] > 50) {
                                $divisiRankIcon = "up100.svg";
                            }
                        }

                        $divisionChange = isset($ranking['position_change']) ? $ranking['position_change'] : 0;
                        if ($divisionChange > 0) {
                            $divisionChangeClass = 'text-success';
                            $divisionChangeBadgeClass = 'up';
                            $divisionChangeIcon = 'lni-arrow-up';
                            $divisionChangeText = 'naik ' . $divisionChange;
                            $divisionBadgeText = 'Naik ' . $divisionChange;
                        } elseif ($divisionChange < 0) {
                            $divisionChangeClass = 'text-danger';
                            $divisionChangeBadgeClass = 'down';
                            $divisionChangeIcon = 'lni-arrow-down';
                            $divisionChangeText = 'turun ' . abs($divisionChange);
                            $divisionBadgeText = 'Turun ' . abs($divisionChange);
                        } else {
                            $divisionChangeClass = 'text-muted';
                            $divisionChangeBadgeClass = 'neutral';
                            $divisionChangeIcon = 'lni-minus';
                            $divisionChangeText = 'tetap';
                            $divisionBadgeText = 'Tetap';
                        }
                    @endphp

                    <div class="ranking-card division division-rank-card" data-divisi-id="{{ $divisiId }}" style="{{ $selectedDivisiId == $divisiId || (empty($selectedDivisiId) && $divisiId == array_key_first($divisionRankings)) ? '' : 'display: none;' }}">
                        <div class="ranking-icon">
                            <img src="{{ asset('img/' . $divisiRankIcon) }}" alt="Peringkat" width="40" height="40">
                        </div>
                        <div class="ranking-info">
                        <div class="ranking-title">Peringkat Divisi
                                <span class="divisi-badge {{
                                    strpos(strtolower($ranking['nama'] ?? ''), 'dgs') !== false ? 'dgs' :
                                    (strpos(strtolower($ranking['nama'] ?? ''), 'dps') !== false ? 'dps' :
                                    (strpos(strtolower($ranking['nama'] ?? ''), 'dss') !== false ? 'dss' : ''))
                                }}">
                                    {{ $ranking['nama'] }}
                                </span>
                            </div>                                    <div class="ranking-value">
                                {{ $ranking['position'] }} dari {{ $ranking['total'] }}
                                @if ($divisionChange != 0 && is_numeric($ranking['position']))
                                    <span class="{{ $divisionChangeClass }} ml-2" style="font-size: 14px;">
                                        <i class="lni {{ $divisionChangeIcon }}"></i>
                                    </span>
                                @endif
                            </div>
                            @if(is_numeric($ranking['position']))
                                <span class="rank-change-detail {{ $divisionChangeBadgeClass }}">{{ $divisionChangeText }} dari {{ $previousMonthID }}</span>
                            @else
                                <span class="rank-change-detail text-muted">belum ada data</span>
                            @endif
                        </div>
                        @if($divisionChange != 0 && is_numeric($ranking['position']))
                            <div class="rank-badge {{ $divisionChangeBadgeClass }}">
                                <i class="lni {{ $divisionChangeIcon }}"></i>
                                {{ $divisionBadgeText }}
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="ranking-card division">
                    <div class="ranking-icon">
                        <img src="{{ asset('img/up100.svg') }}" alt="Peringkat" width="40" height="40">
                    </div>
                    <div class="ranking-info">
                        <div class="ranking-title">Peringkat Divisi</div>
                        <div class="ranking-value">N/A</div>
                        <span class="rank-change-detail text-muted">belum ada data</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
</div>

<!-- Content Tabs -->
<div class="content-wrapper">
<!-- Tab Navigation -->
<div class="tab-navigation">
    <button class="tab-button active" data-tab="customer-data">
        <i class="fas fa-users"></i> Data Customer
    </button>
    <button class="tab-button" data-tab="performance-analysis">
        <i class="fas fa-chart-line"></i> Analisis Performa
    </button>
</div>

<!-- Tab Content - Customer Data -->
<div id="customer-data" class="tab-content active">
    <div class="tab-content-header">
        <div class="tab-content-title">
            <i class="fas fa-users"></i> Data Customer & Revenue
        </div>

        <div class="filters-container">
            <!-- NEW: View Mode Toggle -->
            <div class="view-mode-toggle">
                <button type="button" class="view-mode-btn {{ $viewMode == 'detail' ? 'active' : '' }}" data-mode="detail">
                    <i class="fas fa-list-ul"></i> Detail
                </button>
                <button type="button" class="view-mode-btn {{ $viewMode == 'aggregate' ? 'active' : '' }}" data-mode="aggregate">
                    <i class="fas fa-chart-bar"></i> Agregat
                </button>
            </div>

            <!-- Division Selector -->
            @if(count($accountManager->divisis) > 1)
            <div class="filter-group">
                <select id="divisiFilter" class="selectpicker" title="Pilih Divisi">
                    <option value="all">Semua Divisi</option>
                    @foreach($accountManager->divisis as $divisi)
                        <option value="{{ $divisi->id }}" {{ $selectedDivisiId == $divisi->id ? 'selected' : '' }}>
                            {{ $divisi->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- NEW: Month Filter (only for detail view) -->
            <div class="filter-group" id="monthFilterGroup" style="{{ $viewMode == 'detail' ? '' : 'display: none;' }}">
                <select id="monthFilter" class="selectpicker" title="Pilih Bulan">
                    <option value="all" {{ $selectedMonth == 'all' ? 'selected' : '' }}>Semua Bulan</option>
                    @foreach($monthsList as $month)
                        <option value="{{ $month['number'] }}" {{ $selectedMonth == $month['number'] ? 'selected' : '' }}>
                            {{ $month['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <select id="filterCustomer" class="selectpicker" title="Filter">
                    <option value="all">Semua</option>
                    <option value="highest_achievement">Achievement Tertinggi</option>
                    <option value="highest_revenue">Revenue Tertinggi</option>
                </select>
            </div>

            <div class="filter-group">
                <select name="year" id="year-select" class="selectpicker" data-live-search="true" title="Pilih Tahun">
                    @foreach($yearsList as $year)
                        <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Customer Tables for Each Division -->
    <div id="all-divisions-customer-table" class="data-card division-customer-table active">
        @if(count($customerRevenues) > 0)
            <div class="table-responsive">
                <!-- Detail View Table -->
                <table class="data-table detail-table" id="detail-table" style="{{ $viewMode == 'detail' ? '' : 'display: none;' }}">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Customer</th>
                            <th>NIPNAS</th>
                            <th>Divisi</th>
                            <th>Target Revenue</th>
                            <th>Real Revenue</th>
                            <th>Achievement</th>
                            <th>Bulan</th>
                        </tr>
                    </thead>
                    <tbody id="detail-table-body">
                        @if($viewMode == 'detail')
                            @foreach($customerRevenues as $index => $revenue)
                                <tr>
                                    <td class="row-number">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="customer-name">{{ $revenue->customer_name }}</div>
                                    </td>
                                    <td>
                                        <div class="nipnas">{{ $revenue->nipnas }}</div>
                                    </td>
                                    <td>
                                        <div class="customer-divisi">{{ $revenue->divisi_name ?: 'N/A' }}</div>
                                    </td>
                                    <td>Rp {{ number_format($revenue->target_revenue, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($revenue->real_revenue, 0, ',', '.') }}</td>
                                    <td>
                                        @php
                                            $achievementClass = 'badge-danger';
                                            if ($revenue->achievement >= 100) {
                                                $achievementClass = 'badge-success';
                                            } elseif ($revenue->achievement >= 80) {
                                                $achievementClass = 'badge-warning';
                                            }
                                        @endphp
                                        <span class="achievement-badge {{ $achievementClass }}">
                                            {{ number_format($revenue->achievement, 2, ',', '.') }}%
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $bulanName = date('F Y', strtotime($revenue->bulan));
                                            $indonesianMonth = $monthNames[date('F', strtotime($revenue->bulan))] ?? date('F', strtotime($revenue->bulan));
                                            $year = date('Y', strtotime($revenue->bulan));
                                        @endphp
                                        <span class="month-badge">{{ $indonesianMonth }} {{ $year }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>

                <!-- Aggregate View Table -->
                <table class="data-table aggregate-table" id="aggregate-table" style="{{ $viewMode == 'aggregate' ? '' : 'display: none;' }}">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Customer</th>
                            <th>NIPNAS</th>
                            <th>Divisi</th>
                            <th>Target Revenue</th>
                            <th>Real Revenue</th>
                            <th>Achievement</th>
                        </tr>
                    </thead>
                    <tbody id="aggregate-table-body">
                        @if($viewMode == 'aggregate')
                            @foreach($customerRevenues as $index => $customer)
                                <tr>
                                    <td class="row-number">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="customer-name">{{ $customer->nama }}</div>
                                    </td>
                                    <td>
                                        <div class="nipnas">{{ $customer->nipnas }}</div>
                                    </td>
                                    <td>
                                        <div class="customer-divisi">
                                            @php
                                                $divisiId = $customer->divisi_id ?? null;
                                                $divisiNama = '';
                                                if ($divisiId) {
                                                    foreach($accountManager->divisis as $divisi) {
                                                        if ($divisi->id == $divisiId) {
                                                            $divisiNama = $divisi->nama;
                                                            break;
                                                        }
                                                    }
                                                }
                                            @endphp
                                            {{ $divisiNama ?: 'Semua' }}
                                        </div>
                                    </td>
                                    <td>Rp {{ number_format($customer->total_target, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($customer->total_revenue, 0, ',', '.') }}</td>
                                    <td>
                                        @php
                                            $achievementClass = 'badge-danger';
                                            if ($customer->achievement >= 100) {
                                                $achievementClass = 'badge-success';
                                            } elseif ($customer->achievement >= 80) {
                                                $achievementClass = 'badge-warning';
                                            }
                                        @endphp
                                        <span class="achievement-badge {{ $achievementClass }}">
                                            {{ number_format($customer->achievement, 2, ',', '.') }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <p class="empty-text">Tidak ada data customer untuk tahun {{ $selectedYear }}</p>
            </div>
        @endif
    </div>

    <!-- Individual Division Customer Tables -->
    @foreach($accountManager->divisis as $divisi)
        <div id="divisi-{{ $divisi->id }}-customer-table" class="data-card division-customer-table" style="display: none;">
            @if(isset($customerRevenuesByDivisi[$divisi->id]) && count($customerRevenuesByDivisi[$divisi->id]) > 0)
                <div class="table-responsive">
                    <!-- Detail View for Division -->
                    <table class="data-table detail-table division-detail-table" data-divisi="{{ $divisi->id }}" style="{{ $viewMode == 'detail' ? '' : 'display: none;' }}">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Customer</th>
                                <th>NIPNAS</th>
                                <th>Target Revenue</th>
                                <th>Real Revenue</th>
                                <th>Achievement</th>
                                <th>Bulan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($viewMode == 'detail' && isset($customerRevenuesByDivisi[$divisi->id]))
                                @foreach($customerRevenuesByDivisi[$divisi->id] as $index => $revenue)
                                    <tr>
                                        <td class="row-number">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="customer-name">{{ $revenue->customer_name }}</div>
                                        </td>
                                        <td>
                                            <div class="nipnas">{{ $revenue->nipnas }}</div>
                                        </td>
                                        <td>Rp {{ number_format($revenue->target_revenue, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($revenue->real_revenue, 0, ',', '.') }}</td>
                                        <td>
                                            @php
                                                $achievementClass = 'badge-danger';
                                                if ($revenue->achievement >= 100) {
                                                    $achievementClass = 'badge-success';
                                                } elseif ($revenue->achievement >= 80) {
                                                    $achievementClass = 'badge-warning';
                                                }
                                            @endphp
                                            <span class="achievement-badge {{ $achievementClass }}">
                                                {{ number_format($revenue->achievement, 2, ',', '.') }}%
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $bulanName = date('F Y', strtotime($revenue->bulan));
                                                $indonesianMonth = $monthNames[date('F', strtotime($revenue->bulan))] ?? date('F', strtotime($revenue->bulan));
                                                $year = date('Y', strtotime($revenue->bulan));
                                            @endphp
                                            <span class="month-badge">{{ $indonesianMonth }} {{ $year }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>

                    <!-- Aggregate View for Division -->
                    <table class="data-table aggregate-table division-aggregate-table" data-divisi="{{ $divisi->id }}" style="{{ $viewMode == 'aggregate' ? '' : 'display: none;' }}">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Customer</th>
                                <th>NIPNAS</th>
                                <th>Target Revenue</th>
                                <th>Real Revenue</th>
                                <th>Achievement</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($viewMode == 'aggregate' && isset($customerRevenuesByDivisi[$divisi->id]))
                                @foreach($customerRevenuesByDivisi[$divisi->id] as $index => $customer)
                                    <tr>
                                        <td class="row-number">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="customer-name">{{ $customer->nama ?? $customer->customer_name }}</div>
                                        </td>
                                        <td>
                                            <div class="nipnas">{{ $customer->nipnas }}</div>
                                        </td>
                                        <td>Rp {{ number_format($customer->total_target ?? $customer->target_revenue, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($customer->total_revenue ?? $customer->real_revenue, 0, ',', '.') }}</td>
                                        <td>
                                            @php
                                                $achievementClass = 'badge-danger';
                                                if ($customer->achievement >= 100) {
                                                    $achievementClass = 'badge-success';
                                                } elseif ($customer->achievement >= 80) {
                                                    $achievementClass = 'badge-warning';
                                                }
                                            @endphp
                                            <span class="achievement-badge {{ $achievementClass }}">
                                                {{ number_format($customer->achievement, 2, ',', '.') }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <p class="empty-text">Tidak ada data customer untuk divisi {{ $divisi->nama }} di tahun {{ $selectedYear }}</p>
                </div>
            @endif
        </div>
    @endforeach
</div>

<!-- Tab Content - Performance Analysis -->
<div id="performance-analysis" class="tab-content">
    <div class="tab-content-header">
        <div class="tab-content-title">
            <i class="fas fa-chart-line"></i> Analisis Performa & Insight
        </div>

        <!-- Division Selector for Performance -->
        @if(count($accountManager->divisis) > 1)
        <div class="filters-container">
            <div class="filter-group">
                <select id="performanceDivisiFilter" class="selectpicker" title="Pilih Divisi">
                    <option value="all">Semua Divisi</option>
                    @foreach($accountManager->divisis as $divisi)
                        <option value="{{ $divisi->id }}" {{ $selectedDivisiId == $divisi->id ? 'selected' : '' }}>
                            {{ $divisi->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        @endif
    </div>

    <!-- Total Revenue Summary - Combined -->
    <div id="all-divisions-performance" class="division-performance active">
        <div class="total-revenue-summary">
            <div class="revenue-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="revenue-content">
                <div class="revenue-label">Total Revenue Sepanjang Waktu</div>
                <div class="revenue-value">
                    @php
                        $totalAllTimeRevenue = $accountManager->revenues->sum('real_revenue');
                        if ($totalAllTimeRevenue >= 1000000000) {
                            echo 'Rp ' . number_format($totalAllTimeRevenue / 1000000000, 2, ',', '.') . ' Miliar';
                        } elseif ($totalAllTimeRevenue >= 1000000) {
                            echo 'Rp ' . number_format($totalAllTimeRevenue / 1000000, 2, ',', '.') . ' Juta';
                        } else {
                            echo 'Rp ' . number_format($totalAllTimeRevenue, 0, ',', '.');
                        }
                    @endphp
                </div>
                <div class="revenue-period">Sejak {{ $revenuePeriod['earliest'] }} hingga {{ $revenuePeriod['latest'] }}</div>
            </div>
        </div>

        <!-- Insights Section - Combined for all divisions -->
        <div class="insight-summary-card">
            <div class="insight-header">
                <i class="fas fa-lightbulb"></i>
                <h4>Ringkasan Performa</h4>
            </div>
            <div class="insight-body">
                <p>{{ $insights['message'] }}</p>

                <p>Berdasarkan analisis data selama {{ $selectedYear }}, Account Manager <strong>{{ $accountManager->nama }}</strong>
                menunjukkan achievement yang {{ $insights['avg_achievement'] >= 90 ? 'sangat baik' : ($insights['avg_achievement'] >= 80 ? 'baik' : 'perlu ditingkatkan') }}.
                Dengan rata-rata achievement <strong>{{ number_format($insights['avg_achievement'], 2) }}%</strong> dan
                tren performa yang {{ $insights['trend'] == 'up' ? 'meningkat' : ($insights['trend'] == 'down' ? 'menurun' : 'stabil') }}.</p>
            </div>
        </div>

        <div class="insight-metrics">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-label">Achievement Tertinggi</div>
                    <div class="metric-value">{{ $insights['best_achievement_month'] ? number_format($insights['best_achievement_month']['achievement'], 2) . '%' : 'N/A' }}</div>
                    <div class="metric-period">
                        @if($insights['best_achievement_month'])
                            @php
                                $monthName = $insights['best_achievement_month']['month_name'];
                                echo isset($monthNames[$monthName]) ? $monthNames[$monthName] : $monthName;
                            @endphp
                        @endif
                    </div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-label">Revenue Tertinggi</div>
                    <div class="metric-value">
                        @if($insights['best_revenue_month'])
                            @php
                                $revenue = $insights['best_revenue_month']['real_revenue'];
                                if ($revenue >= 1000000000) {
                                    echo 'Rp ' . number_format($revenue / 1000000000, 2, ',', '.') . ' M';
                                } elseif ($revenue >= 1000000) {
                                    echo 'Rp ' . number_format($revenue / 1000000, 2, ',', '.') . ' Jt';
                                } else {
                                    echo 'Rp ' . number_format($revenue, 0, ',', '.');
                                }
                            @endphp
                        @else
                            N/A
                        @endif
                    </div>
                    <div class="metric-period">
                        @if($insights['best_revenue_month'])
                            @php
                                $monthName = $insights['best_revenue_month']['month_name'];
                                echo isset($monthNames[$monthName]) ? $monthNames[$monthName] : $monthName;
                            @endphp
                        @endif
                    </div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-label">Rata-rata Achievement</div>
                    <div class="metric-value">{{ number_format($insights['avg_achievement'], 2) }}%</div>
                    <div class="metric-period">Sepanjang {{ $selectedYear }}</div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon">
                    @if($insights['trend'] == 'up')
                        <i class="fas fa-arrow-up text-success"></i>
                    @elseif($insights['trend'] == 'down')
                        <i class="fas fa-arrow-down text-danger"></i>
                    @else
                        <i class="fas fa-minus text-muted"></i>
                    @endif
                </div>
                <div class="metric-content">
                    <div class="metric-label">Tren Performa</div>
                    <div class="metric-value">
                        @if($insights['trend'] == 'up')
                            <span class="text-success">Meningkat</span>
                        @elseif($insights['trend'] == 'down')
                            <span class="text-danger">Menurun</span>
                        @else
                            <span class="text-muted">Stabil</span>
                        @endif
                    </div>
                    <div class="metric-period">3 bulan terakhir</div>
                </div>
            </div>
        </div>

        <!-- Performance Chart for Combined Data -->
        <div class="chart-container">
            <div class="chart-header">
                <h4 class="chart-title">
                    <i class="fas fa-chart-bar"></i>
                    Grafik Performa Bulanan {{ $selectedYear }}
                </h4>

                <div class="chart-filters">
                    <div class="filter-group">
                        <select name="performance_year" id="performance-year-select" class="selectpicker" data-live-search="true" title="Pilih Tahun">
                            @foreach($yearsList as $year)
                                <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <select id="chartType" class="selectpicker" title="Tipe Tampilan">
                            <option value="combined" selected>Kombinasi</option>
                            <option value="revenue">Revenue</option>
                            <option value="achievement">Achievement</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="chart-canvas-container">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Individual Division Performance Sections -->
    @foreach($accountManager->divisis as $divisi)
        <div id="divisi-{{ $divisi->id }}-performance" class="division-performance" style="display: none;">
            <!-- Total Revenue Summary for individual division -->
            <div class="total-revenue-summary">
                <div class="revenue-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="revenue-content">
                    <div class="revenue-label">Total Revenue {{ $divisi->nama }} Sepanjang Waktu</div>
                    <div class="revenue-value">
                        @php
                            $totalDivisiRevenue = $accountManager->revenues->where('divisi_id', $divisi->id)->sum('real_revenue');
                            if ($totalDivisiRevenue >= 1000000000) {
                                echo 'Rp ' . number_format($totalDivisiRevenue / 1000000000, 2, ',', '.') . ' Miliar';
                            } elseif ($totalDivisiRevenue >= 1000000) {
                                echo 'Rp ' . number_format($totalDivisiRevenue / 1000000, 2, ',', '.') . ' Juta';
                            } else {
                                echo 'Rp ' . number_format($totalDivisiRevenue, 0, ',', '.');
                            }
                        @endphp
                    </div>
                    <div class="revenue-period">Sejak {{ $revenuePeriod['earliest'] }} hingga {{ $revenuePeriod['latest'] }}</div>
                </div>
            </div>

            <!-- Insights for individual division -->
            @if(isset($insightsByDivisi[$divisi->id]))
                <div class="insight-summary-card">
                    <div class="insight-header">
                        <i class="fas fa-lightbulb"></i>
                        <h4>Ringkasan Performa {{ $divisi->nama }}</h4>
                    </div>
                    <div class="insight-body">
                        <p>{{ $insightsByDivisi[$divisi->id]['message'] }}</p>

                        <p>Berdasarkan analisis data selama {{ $selectedYear }}, Account Manager <strong>{{ $accountManager->nama }}</strong>
                        untuk divisi <strong>{{ $divisi->nama }}</strong> menunjukkan achievement yang
                        {{ $insightsByDivisi[$divisi->id]['avg_achievement'] >= 90 ? 'sangat baik' : ($insightsByDivisi[$divisi->id]['avg_achievement'] >= 80 ? 'baik' : 'perlu ditingkatkan') }}.
                        Dengan rata-rata Achievement <strong>{{ number_format($insightsByDivisi[$divisi->id]['avg_achievement'], 2) }}%</strong> dan
                        tren performa yang {{ $insightsByDivisi[$divisi->id]['trend'] == 'up' ? 'meningkat' : ($insightsByDivisi[$divisi->id]['trend'] == 'down' ? 'menurun' : 'stabil') }}.</p>
                    </div>
                </div>

                <div class="insight-metrics">
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="metric-content">
                            <div class="metric-label">Achievement Tertinggi</div>
                            <div class="metric-value">{{ $insightsByDivisi[$divisi->id]['best_achievement_month'] ? number_format($insightsByDivisi[$divisi->id]['best_achievement_month']['achievement'], 2) . '%' : 'N/A' }}</div>
                            <div class="metric-period">
                                @if($insightsByDivisi[$divisi->id]['best_achievement_month'])
                                    @php
                                        $monthName = $insightsByDivisi[$divisi->id]['best_achievement_month']['month_name'];
                                        echo isset($monthNames[$monthName]) ? $monthNames[$monthName] : $monthName;
                                    @endphp
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="metric-content">
                            <div class="metric-label">Revenue Tertinggi</div>
                            <div class="metric-value">
                                @if($insightsByDivisi[$divisi->id]['best_revenue_month'])
                                    @php
                                        $revenue = $insightsByDivisi[$divisi->id]['best_revenue_month']['real_revenue'];
                                        if ($revenue >= 1000000000) {
                                            echo 'Rp ' . number_format($revenue / 1000000000, 2, ',', '.') . ' M';
                                        } elseif ($revenue >= 1000000) {
                                            echo 'Rp ' . number_format($revenue / 1000000, 2, ',', '.') . ' Jt';
                                        } else {
                                            echo 'Rp ' . number_format($revenue, 0, ',', '.');
                                        }
                                    @endphp
                                @else
                                    N/A
                                @endif
                            </div>
                            <div class="metric-period">
                                @if($insightsByDivisi[$divisi->id]['best_revenue_month'])
                                    @php
                                        $monthName = $insightsByDivisi[$divisi->id]['best_revenue_month']['month_name'];
                                        echo isset($monthNames[$monthName]) ? $monthNames[$monthName] : $monthName;
                                    @endphp
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="metric-content">
                            <div class="metric-label">Rata-rata Achievement</div>
                            <div class="metric-value">{{ number_format($insightsByDivisi[$divisi->id]['avg_achievement'], 2) }}%</div>
                            <div class="metric-period">Sepanjang {{ $selectedYear }}</div>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-icon">
                            @if($insightsByDivisi[$divisi->id]['trend'] == 'up')
                                <i class="fas fa-arrow-up text-success"></i>
                            @elseif($insightsByDivisi[$divisi->id]['trend'] == 'down')
                                <i class="fas fa-arrow-down text-danger"></i>
                            @else
                                <i class="fas fa-minus text-muted"></i>
                            @endif
                        </div>
                        <div class="metric-content">
                            <div class="metric-label">Tren Performa</div>
                            <div class="metric-value">
                                @if($insightsByDivisi[$divisi->id]['trend'] == 'up')
                                    <span class="text-success">Meningkat</span>
                                @elseif($insightsByDivisi[$divisi->id]['trend'] == 'down')
                                    <span class="text-danger">Menurun</span>
                                @else
                                    <span class="text-muted">Stabil</span>
                                @endif
                            </div>
                            <div class="metric-period">3 bulan terakhir</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="insight-summary-card">
                    <div class="insight-header">
                        <i class="fas fa-lightbulb"></i>
                        <h4>Ringkasan Performa {{ $divisi->nama }}</h4>
                    </div>
                    <div class="insight-body">
                        <p>Belum ada data performa yang tersedia untuk Account Manager ini pada divisi {{ $divisi->nama }}.</p>
                    </div>
                </div>
            @endif

            <!-- Performance Chart for Individual Division -->
            <div class="chart-container">
                <div class="chart-header">
                    <h4 class="chart-title">
                        <i class="fas fa-chart-bar"></i>
                        Grafik Performa Bulanan {{ $divisi->nama }} {{ $selectedYear }}
                    </h4>

                    <div class="chart-filters">
                        <div class="filter-group">
                            <select name="divisi_performance_year_{{ $divisi->id }}"
                                    id="divisi-performance-year-select-{{ $divisi->id }}"
                                    class="selectpicker divisi-year-select"
                                    data-divisi-id="{{ $divisi->id }}"
                                    data-live-search="true"
                                    title="Pilih Tahun">
                                @foreach($yearsList as $year)
                                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-group">
                            <select id="divisi-chartType-{{ $divisi->id }}"
                                    class="selectpicker divisi-chart-type"
                                    data-divisi-id="{{ $divisi->id }}"
                                    title="Tipe Tampilan">
                                <option value="combined" selected>Kombinasi</option>
                                <option value="revenue">Revenue</option>
                                <option value="achievement">Achievement</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="chart-canvas-container">
                    <canvas id="performanceChart-{{ $divisi->id }}"></canvas>
                </div>
            </div>
        </div>
    @endforeach
</div>
</div>
</div>

@section('scripts')
<!-- Bootstrap Select -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/js/bootstrap-select.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
// Inisialisasi Bootstrap Select
$('.selectpicker').selectpicker({
liveSearch: true,
liveSearchPlaceholder: 'Cari opsi...',
size: 5,
actionsBox: false,
dropupAuto: false,
mobile: false
});

// NEW: View Mode Toggle functionality
$('.view-mode-btn').on('click', function() {
const mode = $(this).data('mode');

// Update active state
$('.view-mode-btn').removeClass('active');
$(this).addClass('active');

// Show/hide month filter based on mode
if (mode === 'detail') {
    $('#monthFilterGroup').show();
} else {
    $('#monthFilterGroup').hide();
}

// Show/hide appropriate tables
if (mode === 'detail') {
    $('.detail-table').show();
    $('.aggregate-table').hide();
} else {
    $('.detail-table').hide();
    $('.aggregate-table').show();
}

// Redirect with new view mode
let url = "{{ route('account_manager.detail', $accountManager->id) }}?year={{ $selectedYear }}&view_mode=" + mode;

// Add other parameters
const selectedDivisiId = $('#divisiFilter').val();
if (selectedDivisiId && selectedDivisiId !== 'all') {
    url += "&divisi=" + selectedDivisiId;
}

const selectedMonth = $('#monthFilter').val();
if (selectedMonth && selectedMonth !== 'all' && mode === 'detail') {
    url += "&month=" + selectedMonth;
}

@if($needsCategoryFilter)
url += "&category_filter={{ $selectedCategoryFilter }}";
@endif

window.location.href = url;
});

// NEW: Month filter change event
$('#monthFilter').change(function() {
if ($(this).val()) {
    let url = "{{ route('account_manager.detail', $accountManager->id) }}?year={{ $selectedYear }}&view_mode={{ $viewMode }}";

    // Add month parameter
    if ($(this).val() !== 'all') {
        url += "&month=" + $(this).val();
    }

    // Add divisi parameter if exists
    const selectedDivisiId = $('#divisiFilter').val();
    if (selectedDivisiId && selectedDivisiId !== 'all') {
        url += "&divisi=" + selectedDivisiId;
    }

    @if($needsCategoryFilter)
    url += "&category_filter={{ $selectedCategoryFilter }}";
    @endif

    window.location.href = url;
}
});

// Category filter change event
$('#category-filter-select').change(function() {
if ($(this).val()) {
    let url = "{{ route('account_manager.detail', $accountManager->id) }}?year={{ $selectedYear }}&view_mode={{ $viewMode }}";

    // Add category filter parameter
    url += "&category_filter=" + $(this).val();

    // Add divisi parameter if exists
    const selectedDivisiId = $('#divisiFilter').val();
    if (selectedDivisiId && selectedDivisiId !== 'all') {
        url += "&divisi=" + selectedDivisiId;
    }

    // Add month parameter if exists and in detail mode
    @if($viewMode == 'detail')
    const selectedMonth = $('#monthFilter').val();
    if (selectedMonth && selectedMonth !== 'all') {
        url += "&month=" + selectedMonth;
    }
    @endif

    window.location.href = url;
}
});

// Year selector change event
$('#year-select, #performance-year-select').change(function() {
if ($(this).val()) {
    let url = "{{ route('account_manager.detail', $accountManager->id) }}?year=" + $(this).val() + "&view_mode={{ $viewMode }}";

    // Add divisi parameter if exists
    const selectedDivisiId = $('#divisiFilter').val();
    if (selectedDivisiId && selectedDivisiId !== 'all') {
        url += "&divisi=" + selectedDivisiId;
    }

    // Add month parameter if exists and in detail mode
    @if($viewMode == 'detail')
    const selectedMonth = $('#monthFilter').val();
    if (selectedMonth && selectedMonth !== 'all') {
        url += "&month=" + selectedMonth;
    }
    @endif

    // Add category filter parameter if exists
    @if($needsCategoryFilter)
    url += "&category_filter={{ $selectedCategoryFilter }}";
    @endif

    window.location.href = url;
}
});

// Division selector change event - UPDATED to handle table switching
$('#divisiFilter, #performanceDivisiFilter').change(function() {
const selectedDivisi = $(this).val();

// Handle customer data table switching
if ($(this).attr('id') === 'divisiFilter') {
    // Hide all customer tables
    $('.division-customer-table').hide();

    // Show the selected table
    if (selectedDivisi === 'all') {
        $('#all-divisions-customer-table').show();
    } else {
        $('#divisi-' + selectedDivisi + '-customer-table').show();
    }
}

// Handle performance data switching
if ($(this).attr('id') === 'performanceDivisiFilter') {
    // Hide all performance sections
    $('.division-performance').hide();

    // Show the selected performance section
    if (selectedDivisi === 'all') {
        $('#all-divisions-performance').show();
        setTimeout(function() {
            renderPerformanceChart('combined');
        }, 100);
    } else {
        $('#divisi-' + selectedDivisi + '-performance').show();
        setTimeout(function() {
            renderDivisiPerformanceChart(selectedDivisi, 'combined');
        }, 100);
    }
}

// Update URL with new filter
if ($(this).val()) {
    let url = "{{ route('account_manager.detail', $accountManager->id) }}?year={{ $selectedYear }}&view_mode={{ $viewMode }}";

    // Add divisi parameter if not 'all'
    if ($(this).val() !== 'all') {
        url += "&divisi=" + $(this).val();
    }

    // Add month parameter if exists and in detail mode
    @if($viewMode == 'detail')
    const selectedMonth = $('#monthFilter').val();
    if (selectedMonth && selectedMonth !== 'all') {
        url += "&month=" + selectedMonth;
    }
    @endif

    // Add category filter parameter if exists
    @if($needsCategoryFilter)
    url += "&category_filter={{ $selectedCategoryFilter }}";
    @endif

    window.location.href = url;
}
});

// Division ranking selector change event
$('#division-ranking-select').change(function() {
const divisiId = $(this).val();

// Hide all division ranking cards
$('.division-rank-card').hide();

// Show selected division ranking card
$(".division-rank-card[data-divisi-id='" + divisiId + "']").show();
});

// Tab Navigation
$('.tab-button').on('click', function() {
// Remove active class from all buttons and contents
$('.tab-button').removeClass('active');
$('.tab-content').removeClass('active');

// Add active class to clicked button
$(this).addClass('active');

// Show corresponding content
const tabId = $(this).data('tab');
$('#' + tabId).addClass('active');

// If switching to performance tab, render chart
if (tabId === 'performance-analysis') {
    setTimeout(function() {
        const activePerformanceFilter = $('#performanceDivisiFilter').val() || 'all';

        if (activePerformanceFilter === 'all') {
            renderPerformanceChart('combined');
        } else {
            renderDivisiPerformanceChart(activePerformanceFilter, 'combined');
        }
    }, 100);
}
});

// Customer Filters
$('#filterCustomer').on('changed.bs.select', function() {
const filterValue = $(this).val();
const currentDivisiFilter = $('#divisiFilter').val() || 'all';
let tableSelector;

if (currentDivisiFilter === 'all') {
    tableSelector = '#all-divisions-customer-table .data-table:visible';
} else {
    tableSelector = '#divisi-' + currentDivisiFilter + '-customer-table .data-table:visible';
}

if (filterValue === 'all') {
    // Show all rows
    $(tableSelector + ' tbody tr').show();
    // Update row numbers
    $(tableSelector + ' tbody tr:visible').each(function(index) {
        $(this).find('.row-number').text(index + 1);
    });
} else if (filterValue === 'highest_achievement') {
    // Sort by achievement
    const rows = $(tableSelector + ' tbody tr').toArray();
    rows.sort(function(a, b) {
        const aValue = parseFloat($(a).find('.achievement-badge').text().replace(/\./g, '').replace(',', '.').replace('%', ''));
        const bValue = parseFloat($(b).find('.achievement-badge').text().replace(/\./g, '').replace(',', '.').replace('%', ''));
        return bValue - aValue;
    });

    $(tableSelector + ' tbody').empty().append(rows);
    // Show top 5 only
    $(tableSelector + ' tbody tr').hide().slice(0, 5).show();
    // Update row numbers for visible rows
    $(tableSelector + ' tbody tr:visible').each(function(index) {
        $(this).find('.row-number').text(index + 1);
    });
} else if (filterValue === 'highest_revenue') {
    // Sort by revenue - target column index differs between detail and aggregate
    const rows = $(tableSelector + ' tbody tr').toArray();
    rows.sort(function(a, b) {
        let aValue, bValue;
        if ($(tableSelector).hasClass('detail-table')) {
            // Detail table - Real Revenue is at index 5 (after No column)
            aValue = parseInt($(a).find('td:eq(5)').text().replace(/[^\d]/g, ''));
            bValue = parseInt($(b).find('td:eq(5)').text().replace(/[^\d]/g, ''));
        } else {
            // Aggregate table - Real Revenue is at index 5 (after No column)
            aValue = parseInt($(a).find('td:eq(5)').text().replace(/[^\d]/g, ''));
            bValue = parseInt($(b).find('td:eq(5)').text().replace(/[^\d]/g, ''));
        }
        return bValue - aValue;
    });

    $(tableSelector + ' tbody').empty().append(rows);
    // Show top 5 only
    $(tableSelector + ' tbody tr').hide().slice(0, 5).show();
    // Update row numbers for visible rows
    $(tableSelector + ' tbody tr:visible').each(function(index) {
        $(this).find('.row-number').text(index + 1);
    });
}
});

// Chart Type Selector for main chart
$('#chartType').on('changed.bs.select', function() {
renderPerformanceChart($(this).val());
});

// Chart Type Selector for division charts
$('.divisi-chart-type').on('changed.bs.select', function() {
const divisiId = $(this).data('divisi-id');
renderDivisiPerformanceChart(divisiId, $(this).val());
});

// Division-specific year selector
$('.divisi-year-select').on('changed.bs.select', function() {
if ($(this).val()) {
    const divisiId = $(this).data('divisi-id');
    let url = "{{ route('account_manager.detail', $accountManager->id) }}?year=" + $(this).val() + "&divisi=" + divisiId + "&view_mode={{ $viewMode }}";

    // Add category filter parameter if exists
    @if($needsCategoryFilter)
    url += "&category_filter={{ $selectedCategoryFilter }}";
    @endif

    // Add month parameter if exists and in detail mode
    @if($viewMode == 'detail')
    const selectedMonth = $('#monthFilter').val();
    if (selectedMonth && selectedMonth !== 'all') {
        url += "&month=" + selectedMonth;
    }
    @endif

    window.location.href = url;
}
});

// Initialize table display based on current filters
const currentDivisiFilter = $('#divisiFilter').val() || 'all';
if (currentDivisiFilter !== 'all') {
    $('.division-customer-table').hide();
    $('#divisi-' + currentDivisiFilter + '-customer-table').show();
}

const currentPerformanceFilter = $('#performanceDivisiFilter').val() || 'all';
if (currentPerformanceFilter !== 'all') {
    $('.division-performance').hide();
    $('#divisi-' + currentPerformanceFilter + '-performance').show();
}

// Performance Chart functions
function renderPerformanceChart(type) {
const ctx = document.getElementById('performanceChart');
if (!ctx) {
    console.error('Performance chart canvas not found');
    return;
}

// Check if chart exists and destroy it
try {
    const existingChart = Chart.getChart(ctx);
    if (existingChart) {
        existingChart.destroy();
    }
} catch (e) {
    console.warn('Error destroying existing chart:', e);
}

// Convert month names to Indonesian
const monthNames = {
    'January': 'Januari',
    'February': 'Februari',
    'March': 'Maret',
    'April': 'April',
    'May': 'Mei',
    'June': 'Juni',
    'July': 'Juli',
    'August': 'Agustus',
    'September': 'September',
    'October': 'Oktober',
    'November': 'November',
    'December': 'Desember'
};

// Prepare data
const monthlyData = @json($monthlyPerformance);

if (!monthlyData || monthlyData.length === 0) {
    $('.chart-canvas-container').html(
        '<div class="text-center py-5">' +
        '<i class="fas fa-chart-bar fs-1 text-muted mb-3"></i>' +
        '<p class="text-muted">Tidak ada data performa untuk ditampilkan</p>' +
        '</div>'
    );
    return;
}

// Translate month names to Indonesian
const labels = monthlyData.map(item => {
    return monthNames[item.month_name] || item.month_name;
});


const revenueData = monthlyData.map(item => item.real_revenue);
        const targetData = monthlyData.map(item => item.target_revenue);
        const achievementData = monthlyData.map(item => item.achievement);

        // Create datasets based on view type
        let datasets = [];

        if (type === 'combined' || type === 'revenue') {
            datasets.push({
                label: 'Real Revenue',
                data: revenueData,
                backgroundColor: 'rgba(46, 204, 113, 0.6)',
                borderColor: 'rgba(46, 204, 113, 1)',
                borderWidth: 1,
                yAxisID: 'y'
            });

            datasets.push({
                label: 'Target Revenue',
                data: targetData,
                backgroundColor: 'rgba(0, 82, 204, 0.2)',
                borderColor: 'rgba(0, 82, 204, 1)',
                borderWidth: 1,
                yAxisID: 'y'
            });
        }

        if (type === 'combined' || type === 'achievement') {
            datasets.push({
                label: 'Achievement (%)',
                data: achievementData,
                type: 'line',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: ' #ea1d25',
                borderWidth: 2,
                pointBackgroundColor: ' #ea1d25',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: ' #ea1d25',
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: false,
                tension: 0.3,
                yAxisID: 'y1'
            });
        }

        // Configure scales
        const scales = {};

        if (type === 'combined' || type === 'revenue') {
            scales.y = {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Revenue (Rp)',
                    font: {
                        weight: 'bold'
                    }
                },
                ticks: {
                    callback: function(value) {
                        if (value >= 1000000000) {
                            return 'Rp ' + (value / 1000000000).toFixed(1) + ' M';
                        } else if (value >= 1000000) {
                            return 'Rp ' + (value / 1000000).toFixed(1) + ' Jt';
                        } else {
                            return 'Rp ' + value;
                        }
                    }
                }
            };
        }

        if (type === 'combined' || type === 'achievement') {
            scales.y1 = {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Achievement (%)',
                    font: {
                        weight: 'bold'
                    }
                },
                grid: {
                    drawOnChartArea: type !== 'combined',
                },
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            };
        }

        // Create new chart
        try {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: scales,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(28, 41, 85, 0.8)',
                            titleFont: {
                                weight: 'bold',
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            },
                            padding: 12,
                            cornerRadius: 6,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';

                                    if (label) {
                                        label += ': ';
                                    }

                                    if (context.dataset.yAxisID === 'y1') {
                                        label += context.parsed.y.toFixed(2) + '%';
                                    } else {
                                        const value = context.parsed.y;
                                        if (value >= 1000000000) {
                                            label += 'Rp ' + (value / 1000000000).toFixed(2) + ' M';
                                        } else if (value >= 1000000) {
                                            label += 'Rp ' + (value / 1000000).toFixed(2) + ' Jt';
                                        } else {
                                            label += 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                                        }
                                    }

                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        } catch (e) {
            console.error('Error creating chart:', e);
            $('.chart-canvas-container').html(
                '<div class="alert alert-danger mt-3">' +
                '<i class="fas fa-exclamation-triangle me-2"></i>' +
                'Terjadi kesalahan saat membuat grafik: ' + e.message +
                '</div>'
            );
        }
    }

    // Performance Chart for specific division
    function renderDivisiPerformanceChart(divisiId, type) {
        const ctx = document.getElementById('performanceChart-' + divisiId);
        if (!ctx) {
            console.error('Division performance chart canvas not found for divisi ' + divisiId);
            return;
        }

        // Check if chart exists and destroy it
        try {
            const existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }
        } catch (e) {
            console.warn('Error destroying existing chart:', e);
        }

        // Convert month names to Indonesian
        const monthNames = {
            'January': 'Januari',
            'February': 'Februari',
            'March': 'Maret',
            'April': 'April',
            'May': 'Mei',
            'June': 'Juni',
            'July': 'Juli',
            'August': 'Agustus',
            'September': 'September',
            'October': 'Oktober',
            'November': 'November',
            'December': 'Desember'
        };

        // Prepare data
        const monthlyData = @json($monthlyPerformanceByDivisi);

        if (!monthlyData || !monthlyData[divisiId] || monthlyData[divisiId].length === 0) {
            $('#performanceChart-' + divisiId).parent().html(
                '<div class="text-center py-5">' +
                '<i class="fas fa-chart-bar fs-1 text-muted mb-3"></i>' +
                '<p class="text-muted">Tidak ada data performa untuk ditampilkan</p>' +
                '</div>'
            );
            return;
        }

        // Translate month names to Indonesian
        const labels = monthlyData[divisiId].map(item => {
            return monthNames[item.month_name] || item.month_name;
        });

        const revenueData = monthlyData[divisiId].map(item => item.real_revenue);
        const targetData = monthlyData[divisiId].map(item => item.target_revenue);
        const achievementData = monthlyData[divisiId].map(item => item.achievement);

        // Create datasets based on view type
        let datasets = [];

        if (type === 'combined' || type === 'revenue') {
            datasets.push({
                label: 'Real Revenue',
                data: revenueData,
                backgroundColor: 'rgba(59, 125, 221, 0.6)',
                borderColor: 'rgba(59, 125, 221, 1)',
                borderWidth: 1,
                yAxisID: 'y'
            });

            datasets.push({
                label: 'Target Revenue',
                data: targetData,
                backgroundColor: 'rgba(28, 41, 85, 0.2)',
                borderColor: 'rgba(28, 41, 85, 1)',
                borderWidth: 1,
                yAxisID: 'y'
            });
        }

        if (type === 'combined' || type === 'achievement') {
            datasets.push({
                label: 'Achievement (%)',
                data: achievementData,
                type: 'line',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: ' #ea1d25',
                borderWidth: 2,
                pointBackgroundColor: ' #ea1d25',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: ' #ea1d25',
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: false,
                tension: 0.3,
                yAxisID: 'y1'
            });
        }

        // Configure scales
        const scales = {};

        if (type === 'combined' || type === 'revenue') {
            scales.y = {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Revenue (Rp)',
                    font: {
                        weight: 'bold'
                    }
                },
                ticks: {
                    callback: function(value) {
                        if (value >= 1000000000) {
                            return 'Rp ' + (value / 1000000000).toFixed(1) + ' M';
                        } else if (value >= 1000000) {
                            return 'Rp ' + (value / 1000000).toFixed(1) + ' Jt';
                        } else {
                            return 'Rp ' + value;
                        }
                    }
                }
            };
        }

        if (type === 'combined' || type === 'achievement') {
            scales.y1 = {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Achievement (%)',
                    font: {
                        weight: 'bold'
                    }
                },
                grid: {
                    drawOnChartArea: type !== 'combined',
                },
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            };
        }

        // Create new chart
        try {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: scales,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(28, 41, 85, 0.8)',
                            titleFont: {
                                weight: 'bold',
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            },
                            padding: 12,
                            cornerRadius: 6,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';

                                    if (label) {
                                        label += ': ';
                                    }

                                    if (context.dataset.yAxisID === 'y1') {
                                        label += context.parsed.y.toFixed(2) + '%';
                                    } else {
                                        const value = context.parsed.y;
                                        if (value >= 1000000000) {
                                            label += 'Rp ' + (value / 1000000000).toFixed(2) + ' M';
                                        } else if (value >= 1000000) {
                                            label += 'Rp ' + (value / 1000000).toFixed(2) + ' Jt';
                                        } else {
                                            label += 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                                        }
                                    }

                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        } catch (e) {
            console.error('Error creating chart for division ' + divisiId + ':', e);
            $('#performanceChart-' + divisiId).parent().html(
                '<div class="alert alert-danger mt-3">' +
                '<i class="fas fa-exclamation-triangle me-2"></i>' +
                'Terjadi kesalahan saat membuat grafik: ' + e.message +
                '</div>'
            );
        }
    }

    // Initialize chart if performance tab is active
    if ($('#performance-analysis').hasClass('active')) {
        setTimeout(function() {
            const activePerformanceFilter = $('#performanceDivisiFilter').val() || 'all';

            if (activePerformanceFilter === 'all') {
                renderPerformanceChart('combined');
            } else {
                renderDivisiPerformanceChart(activePerformanceFilter, 'combined');
            }
        }, 300);
    }

    // Enhance ranking cards with animation
    $('.ranking-card').hover(
        function() {
            $(this).find('.ranking-icon img').css('transform', 'scale(1.15)');
        },
        function() {
            $(this).find('.ranking-icon img').css('transform', 'scale(1)');
        }
    );

    // Add subtle animation to metric cards
    $('.metric-card').hover(
        function() {
            $(this).find('.metric-value').css('transform', 'scale(1.05)');
            $(this).find('.metric-value').css('transition', 'transform 0.3s');
        },
        function() {
            $(this).find('.metric-value').css('transform', 'scale(1)');
        }
    );

    // Bold text for filter inner text
    $('.filter-option-inner-inner').css('font-weight', '700');
});
</script>
@endsection
@endsection