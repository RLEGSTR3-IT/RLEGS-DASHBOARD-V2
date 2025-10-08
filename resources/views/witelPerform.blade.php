@extends('layouts.main')

@section('title', 'Data Performansi RLEGS')

@section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="{{ asset('css/witel.css') }}">
    <style>
        .filters-row {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .date-filter-container,
        .divisi-filter-container {
            position: relative;
            min-width: 200px;
        }

        /* Date filter styling - with WHITE TEXT color */
        .date-filter {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border: 1px solid #2c5aa0;
            border-radius: 6px;
            background-color: #2c5aa0;
            color: white;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .date-filter:hover {
            background-color: #224785;
            border-color: #224785;
        }

        .date-filter i {
            margin-right: 10px;
            color: white;
        }

        .date-filter .fa-chevron-down {
            margin-left: 10px;
            font-size: 12px;
            color: white;
        }

        #dateRangeText {
            color: white;
        }

        /* Filter button styling */
        .filter-button {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border: 1px solid #2c5aa0;
            border-radius: 6px;
            background-color: #2c5aa0;
            color: white;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .filter-button:hover {
            background-color: #224785;
            border-color: #224785;
        }

        .filter-button i {
            color: white;
        }

        .filter-divisi-value {
            font-weight: 600;
            color: white;
            margin-left: 5px;
        }

        /* Cleaner styling for filter panel */
        #filterPanel {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 5px;
            width: 280px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            z-index: 1050;
            padding: 15px;
            display: none;
        }

        .regions-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            width: 100%;
        }

        .region-row {
            display: flex;
            gap: 30px;
            width: 100%;
            margin-bottom: 10px;
        }

        .region-box {
            flex: 1;
            max-width: 345px;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            font-size: 14px;
            color: #495057;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: all 0.2s ease;
            position: relative;
        }

        .region-box:hover:not([style*="pointer-events: none"]) {
            background-color: #e9ecef;
            border-color: #ced4da;
            transform: translateY(-1px);
        }

        .region-box.active {
            background-color: #1e5bb0;
            color: white;
            border-color: #1e5bb0;
            box-shadow: 0 2px 8px rgba(30, 91, 176, 0.3);
        }

        .region-box.active:hover:not([style*="pointer-events: none"]) {
            background-color: #1a4f9a;
            border-color: #1a4f9a;
        }

        .region-box.active:not([data-region="all"])::after {
            content: "✓";
            position: absolute;
            top: 6px;
            right: 10px;
            font-size: 11px;
            font-weight: bold;
            color: white;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .region-box[style*="pointer-events: none"] {
            cursor: not-allowed !important;
            background-color: #f5f5f5 !important;
            color: #999 !important;
            border-color: #ddd !important;
            position: relative;
        }

        .region-box[style*="pointer-events: none"]:hover {
            background-color: #f5f5f5 !important;
            transform: none !important;
        }

        /*  Add "locked" icon for disabled buttons */
        .region-box[style*="pointer-events: none"]:not(.active)::before {
            content: "🔒";
            position: absolute;
            top: 6px;
            right: 8px;
            font-size: 10px;
            opacity: 0.6;
        }

        .region-box[data-region="all"] {
            font-weight: 600;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .region-box[data-region="all"].active {
            background: linear-gradient(135deg, #1e5bb0 0%, #1a4f9a 100%);
            font-weight: 700;
        }

        .region-box[data-region="all"]:hover:not([style*="pointer-events: none"]) {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        }

        .region-box[data-region="all"].active:hover:not([style*="pointer-events: none"]) {
            background: linear-gradient(135deg, #1a4f9a 0%, #164080 100%);
        }

        /* Achievement percentage styling */
        .achievement-percentage {
            font-size: 1.2em;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .achievement-percentage.success {
            color: #22c55e;
        }

        .achievement-percentage.good {
            color: #3b82f6;
        }

        .achievement-percentage.warning {
            color: #f59e0b;
        }

        .achievement-percentage.danger {
            color: #ef4444;
        }

        .division-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
        }

        /* === Summary Card Color Styling === */
        .summary-card.rlegs {
            border-left: 6px solid #C62828;
        }
        .summary-card.rlegs .summary-icon {
            background-color: #C62828;
        }
        .summary-card.dss {
            border-left: 6px solid #003366; /* Biru Tua */
        }
        .summary-card.dss .summary-icon {
            background-color: #003366;
        }

        /* DSS - Biru Tua */
        .summary-card.dss {
            border-left: 6px solid #003366; /* Biru Tua */
        }
        .summary-card.dss .summary-icon {
            background-color: #003366;
        }

        /* DPS - Biru Muda (lebih kontras, tidak terlalu terang) */
        .summary-card.dps {
            border-left: 6px solid #3399FF; /* Medium Sky Blue */
        }
        .summary-card.dps .summary-icon {
            background-color: #3399FF;
        }


        /* DGS - Dark Orange (selaras dengan JavaScript) */
        .summary-card.dgs {
            border-left: 6px solid #FF8C00; /* Dark Orange */
        }
        .summary-card.dgs .summary-icon {
            background-color: #FF8C00;
        }



        /* Chart container enhancements */
        .chart-container {
            position: relative;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 24px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-title .chart-icon {
            width: 24px;
            height: 24px;
            opacity: 0.7;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }

        .chart-body {
            position: relative;
            min-height: 350px;
        }

        /* Filter overlay */
        .filter-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3);
            z-index: 1040;
        }

        /* Alert styling */
        .alert-container {
            transition: all 0.3s ease;
        }

        /* Filter tabs */
        .filter-tabs {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .filter-tab-btn {
            padding: 8px 15px;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .filter-tab-btn.active {
            color: #2c5aa0;
            border-bottom-color: #2c5aa0;
        }

        /* Filter content */
        .filter-content {
            padding: 10px 0;
        }

        .form-check {
            margin-bottom: 10px;
        }

        /* Empty data state */
        .empty-data-state {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border-radius: 6px;
            z-index: 5;
        }

        .empty-data-state i {
            font-size: 48px;
            color: #6c757d;
            margin-bottom: 16px;
        }

        .empty-data-state .empty-text {
            color: #6c757d;
            font-size: 16px;
            font-weight: 500;
        }

        /* Chart header layout */
        .chart-title-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-bottom: 5px;
        }

        .chart-filters {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .chart-filters .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .chart-filters .filter-group label {
            font-size: 12px;
            font-weight: 500;
            color: #6c757d;
            margin-bottom: 0;
        }

        .chart-canvas-container {
            position: relative;
            height: 350px;
            width: 100%;
        }

        .chart-canvas-container canvas {
            max-height: 350px !important;
        }

        /* Horizontal chart container */
        .horizontal-chart-container {
            height: 500px;
        }

        /* Period label styling */
        .period-label {
            background: linear-gradient(135deg, #2c5aa0, #1e4080);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Bootstrap Select Dropdown Styling */
        .bootstrap-select .dropdown-toggle {
            background-color: #2c5aa0 !important;
            border-color: #2c5aa0 !important;
            color: white !important;
            font-weight: 500 !important;
            padding: 8px 15px !important;
            border-radius: 6px !important;
        }

        .bootstrap-select .dropdown-toggle:hover,
        .bootstrap-select .dropdown-toggle:focus,
        .bootstrap-select.show .dropdown-toggle {
            background-color: #224785 !important;
            border-color: #224785 !important;
            color: white !important;
            box-shadow: none !important;
        }

        .bootstrap-select .dropdown-menu {
            border-radius: 8px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid #e0e6ed !important;
        }

        .bootstrap-select .dropdown-item {
            padding: 8px 15px !important;
            transition: all 0.2s ease !important;
        }

        .bootstrap-select .dropdown-item:hover,
        .bootstrap-select .dropdown-item.active {
            background-color: #2c5aa0 !important;
            color: white !important;
        }

        /* Dynamic achievement colors for summary cards */
        .summary-percentage.achieved {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .summary-percentage.not-achieved {
            background: linear-gradient(135deg, #dc3545, #e74c3c);
            color: white;
        }
    </style>
@endsection

@section('content')
    <div class="main-content">
        <!-- Header Dashboard -->
        <div class="header-dashboard">
            <h1 class="header-title">
                Data Performanasi RLEGS
            </h1>
            <p class="header-subtitle">
                Monitoring Revenue Witel dan Divisi berdasarkan Periode
            </p>
        </div>

        <!-- Summary Cards with dynamic achievement colors -->
        <div class="summary-cards">
            <div class="summary-card rlegs">
                <div class="summary-icon rlegs">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-label">RLEGS</div>
                    <div class="summary-value">
                        Rp {{ isset($summaryData['RLEGS']) ? $summaryData['RLEGS']['total_real_formatted'] : '0' }}
                    </div>
                    <div class="summary-meta {{ isset($summaryData['RLEGS']) && $summaryData['RLEGS']['percentage_change'] >= 0 ? 'up' : 'down' }}">
                        <i class="fas fa-arrow-{{ isset($summaryData['RLEGS']) && $summaryData['RLEGS']['percentage_change'] >= 0 ? 'up' : 'down' }}"></i>
                        {{ isset($summaryData['RLEGS']) ? abs($summaryData['RLEGS']['percentage_change']) : '0.00' }}% dari periode sebelumnya
                    </div>
                </div>
                <div class="summary-percentage {{ isset($summaryData['RLEGS']) && $summaryData['RLEGS']['achievement'] >= 100 ? 'achieved' : 'not-achieved' }}">
                    {{ isset($summaryData['RLEGS']) ? number_format($summaryData['RLEGS']['achievement'], 1) : '0.0' }}%
                </div>
            </div>

            <div class="summary-card dss">
                <div class="summary-icon dss">
                    <i class="fas fa-building"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-label">DSS</div>
                    <div class="summary-value">
                        Rp {{ isset($summaryData['DSS']) ? $summaryData['DSS']['total_real_formatted'] : '0' }}
                    </div>
                    <div class="summary-meta {{ isset($summaryData['DSS']) && $summaryData['DSS']['percentage_change'] >= 0 ? 'up' : 'down' }}">
                        <i class="fas fa-arrow-{{ isset($summaryData['DSS']) && $summaryData['DSS']['percentage_change'] >= 0 ? 'up' : 'down' }}"></i>
                        {{ isset($summaryData['DSS']) ? abs($summaryData['DSS']['percentage_change']) : '0.00' }}% dari periode sebelumnya
                    </div>
                </div>
                <div class="summary-percentage {{ isset($summaryData['DSS']) && $summaryData['DSS']['achievement'] >= 100 ? 'achieved' : 'not-achieved' }}">
                    {{ isset($summaryData['DSS']) ? number_format($summaryData['DSS']['achievement'], 1) : '0.0' }}%
                </div>
            </div>

            <div class="summary-card dps">
                <div class="summary-icon dps">
                    <i class="fas fa-desktop"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-label">DPS</div>
                    <div class="summary-value">
                        Rp {{ isset($summaryData['DPS']) ? $summaryData['DPS']['total_real_formatted'] : '0' }}
                    </div>
                    <div class="summary-meta {{ isset($summaryData['DPS']) && $summaryData['DPS']['percentage_change'] >= 0 ? 'up' : 'down' }}">
                        <i class="fas fa-arrow-{{ isset($summaryData['DPS']) && $summaryData['DPS']['percentage_change'] >= 0 ? 'up' : 'down' }}"></i>
                        {{ isset($summaryData['DPS']) ? abs($summaryData['DPS']['percentage_change']) : '0.00' }}% dari periode sebelumnya
                    </div>
                </div>
                <div class="summary-percentage {{ isset($summaryData['DPS']) && $summaryData['DPS']['achievement'] >= 100 ? 'achieved' : 'not-achieved' }}">
                    {{ isset($summaryData['DPS']) ? number_format($summaryData['DPS']['achievement'], 1) : '0.0' }}%
                </div>
            </div>

            <div class="summary-card dgs">
                <div class="summary-icon dgs">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-label">DGS</div>
                    <div class="summary-value">
                        Rp {{ isset($summaryData['DGS']) ? $summaryData['DGS']['total_real_formatted'] : '0' }}
                    </div>
                    <div class="summary-meta {{ isset($summaryData['DGS']) && $summaryData['DGS']['percentage_change'] >= 0 ? 'up' : 'down' }}">
                        <i class="fas fa-arrow-{{ isset($summaryData['DGS']) && $summaryData['DGS']['percentage_change'] >= 0 ? 'up' : 'down' }}"></i>
                        {{ isset($summaryData['DGS']) ? abs($summaryData['DGS']['percentage_change']) : '0.00' }}% dari periode sebelumnya
                    </div>
                </div>
                <div class="summary-percentage {{ isset($summaryData['DGS']) && $summaryData['DGS']['achievement'] >= 100 ? 'achieved' : 'not-achieved' }}">
                    {{ isset($summaryData['DGS']) ? number_format($summaryData['DGS']['achievement'], 1) : '0.0' }}%
                </div>
            </div>
        </div>

        <!-- Filters Row: Date Picker and Filter Button side by side -->
        <div class="filters-row">
            <!-- Date Filter -->
            <div class="date-filter-container">
                <div class="date-filter" id="dateRangeSelector">
                    <i class="far fa-calendar-alt"></i>
                    <span id="dateRangeText">
                        {{ date('d M Y', strtotime($startDate ?? Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'))) }}
                        -
                        {{ date('d M Y', strtotime($endDate ?? Carbon\Carbon::now()->endOfMonth()->format('Y-m-d'))) }}
                    </span>
                    <i class="fas fa-chevron-down ms-auto"></i>
                </div>
            </div>

            <!-- Divisi Filter Button -->
            <div class="divisi-filter-container">
                <button type="button" class="filter-button" id="filterButton">
                    <i class="fas fa-filter me-2"></i> Filter Divisi
                    <i class="fas fa-chevron-down ms-2"></i>
                </button>

                <!-- Filter Panel - Hidden by default but will be shown with JS -->
                <div class="card filter-panel" id="filterPanel">
                    <!-- Filter Tabs -->
                    <div class="filter-tabs">
                        <button type="button" class="filter-tab-btn active" data-target="divisiContent">Divisi</button>
                        <button type="button" class="filter-tab-btn" data-target="tregContent">Regional</button>
                    </div>

                    <!-- Filter Contents -->
                    <div class="filter-content" id="divisiContent">
                        @php
                            // ✅ FIXED: Remove RLEGS from filter options since it's total of other 3
                            $divisionList = array_filter($divisis ?? ['DSS', 'DPS', 'DGS'], function($div) {
                                return $div !== 'RLEGS';
                            });
                        @endphp

                        @foreach ($divisionList as $index => $divisi)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="divisi{{ $index }}" value="{{ $divisi }}">
                                <label class="form-check-label" for="divisi{{ $index }}">{{ $divisi }}</label>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-sm btn-primary" id="applyDivisiFilter">Terapkan</button>
                        </div>
                    </div>

                    <!-- Regional Content -->
                    <div class="filter-content" id="tregContent" style="display: none;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="treg1" value="TREG 2">
                            <label class="form-check-label" for="treg1">TREG 2</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="treg2" value="TREG 3">
                            <label class="form-check-label" for="treg2">TREG 3</label>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-sm btn-primary" id="applyTregFilter">Terapkan</button>
                        </div>
                    </div>
                </div>

                <!-- Overlay for background when filter is open -->
                <div class="filter-overlay" id="filterOverlay" style="display: none;"></div>
            </div>
        </div>

        <!-- Alert Container -->
        <div class="alert-container mb-4" id="alertContainer" style="display: none;">
            <!-- Alerts will be added dynamically -->
        </div>

        <div class="regions-container">
            <!-- First Row -->
            <div class="region-row">
                <div class="region-box {{ ($selectedRegion ?? 'all') == 'all' ? 'active' : '' }}" data-region="all">
                    Semua Witel
                </div>

                @if (isset($regions) && !empty($regions))
                    @php
                        $regionCount = count($regions);
                        $i = 0;
                    @endphp
                    @foreach ($regions as $region)
                        @if ($i < 3)
                            <div class="region-box {{ ($selectedRegion ?? '') == $region ? 'active' : '' }}" data-region="{{ $region }}">
                                {{ $region }}
                            </div>
                        @endif
                        @php $i++; @endphp
                    @endforeach
                @else
                    @foreach (['Suramadu', 'Nusa Tenggara', 'Jatim Barat'] as $defaultRegion)
                        <div class="region-box {{ ($selectedRegion ?? '') == $defaultRegion ? 'active' : '' }}" data-region="{{ $defaultRegion }}">
                            {{ $defaultRegion }}
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Second Row -->
            <div class="region-row">
                @if (isset($regions) && !empty($regions))
                    @php $i = 0; @endphp
                    @foreach ($regions as $region)
                        @if ($i >= 3 && $i < 7)
                            <div class="region-box {{ ($selectedRegion ?? '') == $region ? 'active' : '' }}" data-region="{{ $region }}">
                                {{ $region }}
                            </div>
                        @endif
                        @php $i++; @endphp
                    @endforeach
                @else
                    @foreach (['Yogya Jateng Selatan', 'Bali', 'Semarang Jateng Utara', 'Solo Jateng Timur'] as $defaultRegion)
                        <div class="region-box {{ ($selectedRegion ?? '') == $defaultRegion ? 'active' : '' }}" data-region="{{ $defaultRegion }}">
                            {{ $defaultRegion }}
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <!-- Charts with better layout -->
        <div class="row">
            <!-- Chart 1: Period Performance Chart -->
            <div class="col-12 chart-container">
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title-container">
                            <h5 class="chart-title">
                                Grafik Performa Witel Periode
                                <span class="period-label" id="periodLabel">{{ $chartData['periodLabel'] ?? 'Mei 2025' }}</span>
                            </h5>
                            <div class="chart-filters">
                                <div class="filter-group">
                                    <label>Tampilan</label>
                                    <select id="chartType" class="selectpicker" data-style="btn-outline-primary">
                                        <option value="combined" selected>Kombinasi</option>
                                        <option value="revenue">Revenue</option>
                                        <option value="achievement">Achievement</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <p class="chart-subtitle">Target vs Realisasi berdasarkan periode yang dipilih</p>
                    </div>
                    <div class="chart-body">
                        <div class="chart-canvas-container">
                            <canvas id="periodPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Horizontal Stacked Division Chart -->
            <div class="col-12 chart-container">
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h5 class="chart-title">Data Revenue Divisi RLEGS</h5>
                            <p class="chart-subtitle">Distribusi Revenue DSS, DPS, DGS berdasarkan Witel yang Anda pilih</p>
                        </div>
                    </div>
                    <div class="chart-body">
                        <div class="chart-canvas-container horizontal-chart-container">
                            <canvas id="stackedDivisionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/js/bootstrap-select.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get Chart.js data from controller
            const chartData = @json($chartData ?? []);
            console.log('Chart.js data loaded:', chartData);

            // Global variables for Chart.js instances and current filters
            let periodPerformanceChartInstance;
            let stackedDivisionChartInstance;

            // ✅ NEW: Track current filter state
            let currentFilterState = {
                selectedRegional: '{{ $selectedRegional ?? 'all' }}',
                selectedWitel: '{{ $selectedWitel ?? 'all' }}',
                selectedDivisi: 'all',
                startDate: '{{ $startDate ?? \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}',
                endDate: '{{ $endDate ?? \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d') }}'
            };

            // Initialize Bootstrap Select
            $('.selectpicker').selectpicker({
                liveSearch: true,
                liveSearchPlaceholder: 'Cari opsi...',
                size: 5,
                actionsBox: false,
                dropupAuto: false,
                mobile: false
            });

            // Initialize date range picker
            const dateRangePicker = flatpickr("#dateRangeSelector", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: [currentFilterState.startDate, currentFilterState.endDate],
                onChange: function(selectedDates, dateStr) {
                    if (selectedDates.length === 2) {
                        const startDate = formatDate(selectedDates[0]);
                        const endDate = formatDate(selectedDates[1]);
                        document.getElementById('dateRangeText').textContent = startDate + ' - ' + endDate;

                        // ✅ UPDATE: Update filter state
                        currentFilterState.startDate = formatDateForApi(selectedDates[0]);
                        currentFilterState.endDate = formatDateForApi(selectedDates[1]);

                        // Update period label
                        updatePeriodLabel(selectedDates[0], selectedDates[1]);

                        // Update charts with new date range
                        updateCharts(selectedDates[0], selectedDates[1]);
                    }
                }
            });

            // ✅ FIXED: Helper function to update period label with proper range format
            function updatePeriodLabel(startDate, endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);

                const startMonth = start.toLocaleString('id-ID', { month: 'short' });
                const endMonth = end.toLocaleString('id-ID', { month: 'short' });
                const startYear = start.getFullYear();
                const endYear = end.getFullYear();

                let periodText;

                // Check if it's the same month and year
                if (start.getMonth() === end.getMonth() && startYear === endYear) {
                    periodText = `${startMonth} ${startYear}`;
                }
                // Check if it's same year but different months
                else if (startYear === endYear) {
                    periodText = `${startMonth} - ${endMonth} ${startYear}`;
                }
                // Different years
                else {
                    periodText = `${startMonth} ${startYear} - ${endMonth} ${endYear}`;
                }

                const periodLabelEl = document.getElementById('periodLabel');
                if (periodLabelEl) {
                    periodLabelEl.textContent = periodText;
                }
            }

            // Helper function to format date
            function formatDate(date) {
                const day = date.getDate();
                const month = date.toLocaleString('default', { month: 'short' });
                const year = date.getFullYear();
                return `${day} ${month} ${year}`;
            }

            // Helper function for formatting numbers (full format: milyar/juta/ribu)
            function formatNumberFull(number, decimals = 2) {
                if (number >= 1000000000) {
                    return (number / 1000000000).toFixed(decimals) + ' milyar';
                } else if (number >= 1000000) {
                    return (number / 1000000).toFixed(decimals) + ' juta';
                } else if (number >= 1000) {
                    return (number / 1000).toFixed(decimals) + ' ribu';
                } else {
                    return number.toFixed(decimals);
                }
            }

            // Keep all existing filter logic
            const filterButton = document.getElementById('filterButton');
            const filterPanel = document.getElementById('filterPanel');
            const filterOverlay = document.getElementById('filterOverlay');

            // ✅ UPDATED: Multi-Select Witel Filter Logic (Free Toggle)
            const witelButtons = document.querySelectorAll('.region-box');
            const semuaWitelButton = document.querySelector('.region-box[data-region="all"]');

            // ✅ NEW: Free toggle witel button click handler
            witelButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const selectedWitel = this.getAttribute('data-region');

                    if (selectedWitel === 'all') {
                        // ✅ CASE 1: "Semua Witel" clicked - can be toggled
                        handleSemuaWitelToggle(this);
                    } else {
                        // ✅ CASE 2: Specific witel clicked - can be toggled
                        handleSpecificWitelToggle(this, selectedWitel);
                    }
                });
            });

            // ✅ NEW: Handle "Semua Witel" toggle (can be deactivated)
            function handleSemuaWitelToggle(button) {
                console.log('🔄 Semua Witel toggled, current state:', button.classList.contains('active'));

                if (button.classList.contains('active')) {
                    // ✅ DEACTIVATE "Semua Witel" - enable other buttons
                    console.log('❌ Deactivating Semua Witel, enabling other buttons');
                    button.classList.remove('active');
                    enableAllWitelButtons();

                    // No filter active, so don't call API - let user choose
                    console.log('✨ All buttons enabled, waiting for user selection');
                } else {
                    // ✅ ACTIVATE "Semua Witel" - disable other buttons
                    console.log('✅ Activating Semua Witel, disabling other buttons');
                    resetAllWitelButtons();
                    button.classList.add('active');
                    disableOtherWitelButtons();

                    // Update filter state and call API
                    currentFilterState.selectedWitel = 'all';
                    updateChartsByWitel('all');
                }
            }

            // ✅ NEW: Handle specific witel toggle (free multi-select)
            function handleSpecificWitelToggle(clickedButton, selectedWitel) {
                console.log('🔄 Specific witel toggled:', selectedWitel, 'current state:', clickedButton.classList.contains('active'));

                // Enable all buttons first (remove any disabled state)
                enableAllWitelButtons();

                // Deactivate "Semua Witel"
                semuaWitelButton.classList.remove('active');

                // Toggle clicked witel
                const wasActive = clickedButton.classList.contains('active');
                clickedButton.classList.toggle('active');
                console.log(`${wasActive ? '❌' : '✅'} ${selectedWitel} ${wasActive ? 'deactivated' : 'activated'}`);

                // Get all active witels (excluding "Semua Witel")
                const activeWitels = getSelectedWitels()
                    .filter(witel => witel !== 'all');

                console.log('📋 Active witels after toggle:', activeWitels);

                // If no witel selected, activate "Semua Witel"
                if (activeWitels.length === 0) {
                    console.log('🔄 No witel selected, auto-activating Semua Witel');
                    semuaWitelButton.classList.add('active');
                    disableOtherWitelButtons();
                    currentFilterState.selectedWitel = 'all';
                    updateChartsByWitel('all');
                    return;
                }

                // Update filter state with active witels
                currentFilterState.selectedWitel = activeWitels.length === 1 ? activeWitels[0] : activeWitels;
                console.log('🎯 Updating charts with:', currentFilterState.selectedWitel);
                updateChartsByWitel(activeWitels);
            }

            // ✅ NEW: Enable all witel buttons
            function enableAllWitelButtons() {
                console.log('🔓 Enabling all witel buttons');
                witelButtons.forEach(btn => {
                    btn.style.opacity = '1';
                    btn.style.pointerEvents = 'auto';
                });
            }

            // ✅ NEW: Disable other witel buttons (except "Semua Witel")
            function disableOtherWitelButtons() {
                console.log('🔒 Disabling other witel buttons (except Semua Witel)');
                witelButtons.forEach(btn => {
                    if (btn.getAttribute('data-region') !== 'all') {
                        btn.style.opacity = '0.5';
                        btn.style.pointerEvents = 'none';
                    }
                });
            }

            // ✅ NEW: Reset all witel buttons to default state
            function resetAllWitelButtons() {
                console.log('🔄 Resetting all witel buttons to default state');
                witelButtons.forEach(btn => {
                    btn.classList.remove('active');
                    btn.style.opacity = '1';
                    btn.style.pointerEvents = 'auto';
                });
            }

            // Toggle filter panel with vanilla JS
            if (filterButton) {
                filterButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (filterPanel.style.display === 'block') {
                        filterPanel.style.display = 'none';
                        filterOverlay.style.display = 'none';
                    } else {
                        filterPanel.style.display = 'block';
                        filterOverlay.style.display = 'block';
                    }
                });
            }

            // Close filter panel when clicking on overlay
            if (filterOverlay) {
                filterOverlay.addEventListener('click', function() {
                    filterPanel.style.display = 'none';
                    filterOverlay.style.display = 'none';
                });
            }

            // Close filter panel when clicking outside
            document.addEventListener('click', function(event) {
                if (
                    filterPanel &&
                    filterPanel.style.display === 'block' &&
                    !filterPanel.contains(event.target) &&
                    event.target !== filterButton &&
                    !(filterButton && filterButton.contains(event.target))
                ) {
                    filterPanel.style.display = 'none';
                    filterOverlay.style.display = 'none';
                }
            });

            // Tab switching in filter panel
            const tabButtons = document.querySelectorAll('.filter-tab-btn');
            const contentPanels = document.querySelectorAll('.filter-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    contentPanels.forEach(panel => panel.style.display = 'none');

                    const targetId = this.getAttribute('data-target');
                    document.getElementById(targetId).style.display = 'block';
                });
            });

            // Apply filter buttons
            const applyDivisiFilter = document.getElementById('applyDivisiFilter');
            if (applyDivisiFilter) {
                applyDivisiFilter.addEventListener('click', function() {
                    const checkedDivisions = Array.from(
                        document.querySelectorAll('#divisiContent input:checked')
                    ).map(cb => cb.value);

                    if (checkedDivisions.length > 0) {
                        // ✅ UPDATE: Update filter state
                        currentFilterState.selectedDivisi = checkedDivisions;
                        applyDivisiFilterFunc(checkedDivisions);
                    } else {
                        showAlert('warning', 'Pilih minimal satu divisi untuk filter');
                    }

                    filterPanel.style.display = 'none';
                    filterOverlay.style.display = 'none';
                });
            }

            // Apply TREG filter
            const applyTregFilter = document.getElementById('applyTregFilter');
            if (applyTregFilter) {
                applyTregFilter.addEventListener('click', function() {
                    const checkedTregs = Array.from(
                        document.querySelectorAll('#tregContent input:checked')
                    ).map(cb => cb.value);

                    if (checkedTregs.length > 0) {
                        // ✅ FIXED: Update REGIONAL filter state (this is actual regional filter)
                        currentFilterState.selectedRegional = checkedTregs[0];
                        updateChartsByRegional(currentFilterState.selectedRegional);

                        // Note: Don't update witel buttons when regional filter changes
                        // because regional and witel are different levels
                    } else {
                        showAlert('warning', 'Pilih minimal satu TREG untuk filter');
                    }

                    filterPanel.style.display = 'none';
                    filterOverlay.style.display = 'none';
                });
            }

            // ✅ FIXED: Chart Type Selector - maintains current filter state
            $('#chartType').on('changed.bs.select', function() {
                const selectedType = $(this).val();
                console.log('🔄 Chart type changed to:', selectedType, 'with current filters:', currentFilterState);

                // Re-render chart with current filter state and NEW COLORS
                if (chartData && chartData.periodPerformance) {
                    console.log('🎨 Re-rendering period chart with new colors for type:', selectedType);
                    renderPeriodPerformanceChart(selectedType, chartData.periodPerformance, chartData.timeSeriesData);
                } else {
                    // If no data, refresh with current filters
                    console.log('🔄 No local data, refreshing with current filters');
                    refreshChartsWithCurrentFilters();
                }
            });

            // ✅ NEW: Function to refresh charts with current filter state
            function refreshChartsWithCurrentFilters() {
                showLoading();

                fetch('{{ route('witel.update-charts') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        witel: currentFilterState.selectedWitel,
                        regional: currentFilterState.selectedRegional,
                        divisi: currentFilterState.selectedDivisi,
                        start_date: currentFilterState.startDate,
                        end_date: currentFilterState.endDate
                    })
                })
                .then(response => response.json())
                .then(data => {
                    updateAllCharts(data.chartData);
                    updateSummaryCards(data.summaryData);
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error refreshing charts:', error);
                    hideLoading();
                });
            }

            // ✅ FIXED: Enhanced Period Performance Chart with REAL time series data from controller
            function renderPeriodPerformanceChart(type, performanceData = null, timeSeriesData = null) {
                const ctx = document.getElementById('periodPerformanceChart');
                if (!ctx) {
                    console.error('Period performance chart canvas not found');
                    return;
                }

                // Use current chart data if no data provided
                if (!performanceData && chartData && chartData.periodPerformance) {
                    performanceData = chartData.periodPerformance;
                }

                if (!timeSeriesData && chartData && chartData.timeSeriesData) {
                    timeSeriesData = chartData.timeSeriesData;
                }

                if (!performanceData) {
                    console.error('No performance data available');
                    return;
                }

                console.log('🎨 Rendering period chart with time series data:', timeSeriesData);
                console.log('🎨 Performance data:', performanceData);

                // Destroy existing chart
                if (periodPerformanceChartInstance) {
                    periodPerformanceChartInstance.destroy();
                }

                // ✅ FIXED: Use time series data from controller if available
                let labels, targetData, realData, achievementData;

                if (timeSeriesData && timeSeriesData.labels && timeSeriesData.labels.length > 0) {
                    // ✅ USE CONTROLLER TIME SERIES DATA
                    console.log('✅ Using controller time series data');
                    labels = timeSeriesData.labels;
                    targetData = timeSeriesData.targetData;
                    realData = timeSeriesData.realData;
                    achievementData = timeSeriesData.achievementData;
                } else {
                    // ✅ FALLBACK: Use period performance data (single point)
                    console.log('⚠️ No time series data, using fallback single period');
                    labels = [currentFilterState.startDate === currentFilterState.endDate ?
                        new Date(currentFilterState.startDate).toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }) :
                        'Periode Terpilih'
                    ];
                    targetData = [performanceData.target_revenue / 1000000]; // Convert to millions
                    realData = [performanceData.real_revenue / 1000000]; // Convert to millions
                    achievementData = [performanceData.achievement];
                }

                console.log('📊 Chart labels:', labels);
                console.log('📊 Target data:', targetData);
                console.log('📊 Real data:', realData);
                console.log('📊 Achievement data:', achievementData);

                // Prepare datasets
                const datasets = [];

                if (type === 'combined' || type === 'revenue') {
                    // Target Revenue - Blue
                    datasets.push({
                        label: 'Target Revenue',
                        data: targetData,
                        backgroundColor: 'rgba(0, 82, 204, 0.2)',
                        borderColor: 'rgba(0, 82, 204, 1)',
                        borderWidth: 2,
                        yAxisID: 'y'
                    });

                    // Real Revenue - Green
                    datasets.push({
                        label: 'Real Revenue',
                        data: realData,
                        backgroundColor: 'rgba(46, 204, 113, 0.6)',
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 2,
                        yAxisID: 'y'
                    });
                }

                if (type === 'combined' || type === 'achievement') {
                    datasets.push({
                        label: 'Achievment (%)',
                        data: achievementData,
                        type: 'line',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: '#ea1d25',
                        borderWidth: 3,
                        pointBackgroundColor: '#ea1d25',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#ea1d25',
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        fill: false,
                        tension: 0.4,
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
                            text: 'Revenue (Juta Rupiah)',
                            font: { weight: 'bold', size: 14 }
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toFixed(1) + ' M';
                            },
                            font: { size: 12 }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
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
                            text: 'Achievment (%)',
                            font: { weight: 'bold', size: 14 }
                        },
                        grid: {
                            drawOnChartArea: type !== 'combined',
                            color: 'rgba(0,0,0,0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1) + '%';
                            },
                            font: { size: 12 }
                        }
                    };
                }

                // Create new chart
                periodPerformanceChartInstance = new Chart(ctx, {
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
                                    padding: 20,
                                    font: { size: 12, weight: '500' }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(28, 41, 85, 0.9)',
                                titleFont: { weight: 'bold', size: 14 },
                                bodyFont: { size: 13 },
                                padding: 15,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }

                                        if (context.dataset.yAxisID === 'y1') {
                                            label += context.parsed.y.toFixed(2) + '%';
                                        } else {
                                            label += 'Rp ' + context.parsed.y.toFixed(2) + ' M';
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });

                console.log('✅ Period performance chart rendered with time series data!');
            }

            // ✅ UPDATED: Enhanced Horizontal Stacked Division Chart with NEW COLORS
            function renderStackedDivisionChart(data) {
                const ctx = document.getElementById('stackedDivisionChart');
                if (!ctx) {
                    console.error('Stacked division chart canvas not found');
                    return;
                }

                console.log('🎨 Rendering stacked division chart with data:', data);

                // Destroy existing chart
                if (stackedDivisionChartInstance) {
                    stackedDivisionChartInstance.destroy();
                }

                if (!data || !data.labels || data.labels.length === 0) {
                    console.log('❌ No stacked division data available');
                    return;
                }

                console.log('📊 Chart labels (witels):', data.labels);
                console.log('📊 Chart datasets:', data.datasets.map(d => ({
                    label: d.label,
                    dataLength: d.data.length,
                    totalRevenue: d.data.reduce((a, b) => a + b, 0)
                })));

            const filteredDatasets = data.datasets
                .filter(dataset => dataset.label !== 'RLEGS')
                .map(dataset => {
                    let backgroundColor, borderColor, hoverBackgroundColor;

                    switch (dataset.label) {
                        case 'DSS':
                            backgroundColor = 'rgba(0, 51, 102, 0.9)';
                            borderColor = '#003366';
                            hoverBackgroundColor = 'rgba(0, 41, 82, 1)';
                            break;
                        case 'DPS':
                            backgroundColor = 'rgba(51, 153, 255, 0.9)';
                            borderColor = '#3399FF';
                            hoverBackgroundColor = 'rgba(51, 153, 255, 1)';
                            break;
                        case 'DGS':
                        backgroundColor = 'rgba(255, 140, 0, 0.9)';
                        borderColor = '#FF8C00';
                        hoverBackgroundColor = 'rgba(230, 120, 0, 1)';
                        break;
                        default:
                            backgroundColor = dataset.backgroundColor;
                            borderColor = dataset.borderColor;
                            hoverBackgroundColor = dataset.backgroundColor;
                    }


                    return {
                        ...dataset,
                        backgroundColor,
                        borderColor,
                        hoverBackgroundColor,
                        borderWidth: 2.5
                    };
                });




                console.log('✅ Filtered datasets count:', filteredDatasets.length);

                // Create horizontal stacked bar chart
                stackedDivisionChartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: filteredDatasets
                    },
                    options: {
                        indexAxis: 'y', // Makes the chart horizontal
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            x: {
                                stacked: true,
                                title: {
                                    display: true,
                                    text: 'Revenue (Juta Rupiah)',
                                    font: { weight: 'bold', size: 14 }
                                },
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + value.toFixed(1) + ' M';
                                    },
                                    font: { size: 12 }
                                },
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                }
                            },
                            y: {
                                stacked: true,
                                title: {
                                    display: true,
                                    text: 'Witel',
                                    font: { weight: 'bold', size: 14 }
                                },
                                ticks: {
                                    font: { size: 12 }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20,
                                    font: { size: 12, weight: '500' },
                                    generateLabels: function(chart) {
                                        const original = Chart.defaults.plugins.legend.labels.generateLabels;
                                        const labels = original.call(this, chart);

                                        // ✅ NEW: Add color descriptions for better UX
                                        labels.forEach(label => {
                                            switch(label.text) {
                                                case 'DSS':
                                                    label.text = 'DSS';
                                                    break;
                                                case 'DPS':
                                                    label.text = 'DPS';
                                                    break;
                                                case 'DGS':
                                                    label.text = 'DGS';
                                                    break;
                                            }
                                        });

                                        return labels;
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(28, 41, 85, 0.9)',
                                titleFont: { weight: 'bold', size: 14 },
                                bodyFont: { size: 13 },
                                padding: 15,
                                cornerRadius: 8,
                                callbacks: {
                                    title: function(context) {
                                        return 'Witel: ' + context[0].label;
                                    },
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += 'Rp ' + context.parsed.x.toFixed(2) + ' M';
                                        return label;
                                    },
                                    afterBody: function(context) {
                                        // Calculate total for this witel
                                        const total = context.reduce((sum, item) => sum + item.parsed.x, 0);
                                        return '\nTotal: Rp ' + total.toFixed(2) + ' M';
                                    }
                                }
                            }
                        }
                    }
                });

                console.log('✅ Stacked division chart rendered successfully');
            }

            // Enhanced summary card updates
            function updateSummaryCards(summaryData) {
                if (!summaryData) return;

                console.log('Updating summary cards with:', summaryData);

                ['RLEGS', 'DSS', 'DPS', 'DGS'].forEach(division => {
                    if (summaryData[division]) {
                        updateSummaryCard(division, summaryData[division]);
                    }
                });
            }

            function updateSummaryCard(division, data) {
                const card = document.querySelector(`.summary-card.${division.toLowerCase()}`);
                if (!card) return;

                // Update value
                const valueEl = card.querySelector('.summary-value');
                if (valueEl) {
                    const formattedValue = data.total_real_formatted || formatNumberFull(data.total_real);
                    valueEl.textContent = `Rp ${formattedValue}`;
                }

                // Update percentage change
                const metaEl = card.querySelector('.summary-meta');
                if (metaEl) {
                    metaEl.className = `summary-meta ${data.percentage_change >= 0 ? 'up' : 'down'}`;
                    const percentText = `${Math.abs(data.percentage_change).toFixed(2)}% dari periode sebelumnya`;
                    metaEl.innerHTML = `<i class="fas fa-arrow-${data.percentage_change >= 0 ? 'up' : 'down'}"></i> ${percentText}`;
                }

                // Dynamic achievement percentage color
                const percentageEl = card.querySelector('.summary-percentage');
                if (percentageEl) {
                    const achievement = data.achievement || 0;
                    percentageEl.textContent = `${achievement.toFixed(1)}%`;

                    // Update color based on achievement
                    percentageEl.className = `summary-percentage ${achievement >= 100 ? 'achieved' : 'not-achieved'}`;
                }
            }

            // Keep all existing AJAX functions
            function updateAllCharts(chartData) {
                if (!chartData) return;

                console.log('Using direct Chart.js data:', chartData);

                if (chartData.isEmpty === true) {
                    const chartContainers = ["#periodPerformanceChart", "#stackedDivisionChart"];

                    chartContainers.forEach(chartId => {
                        const container = document.querySelector(chartId).parentElement;
                        showEmptyDataState(container);

                        if (chartId === "#periodPerformanceChart" && periodPerformanceChartInstance) {
                            periodPerformanceChartInstance.destroy();
                            periodPerformanceChartInstance = null;
                        }
                        if (chartId === "#stackedDivisionChart" && stackedDivisionChartInstance) {
                            stackedDivisionChartInstance.destroy();
                            stackedDivisionChartInstance = null;
                        }
                    });

                    return;
                }

                // Remove empty states if data exists
                const chartContainers = ["#periodPerformanceChart", "#stackedDivisionChart"];
                chartContainers.forEach(chartId => {
                    const container = document.querySelector(chartId).parentElement;
                    const existingEmpty = container.querySelector('.empty-data-state');
                    if (existingEmpty) {
                        existingEmpty.remove();
                    }
                });

                // Update charts
                if (chartData.periodPerformance) {
                    const chartType = $('#chartType').val() || 'combined';
                    console.log('🎯 Updating period chart with type:', chartType, 'and data:', chartData.periodPerformance);
                    console.log('🎯 Time series data:', chartData.timeSeriesData);
                    renderPeriodPerformanceChart(chartType, chartData.periodPerformance, chartData.timeSeriesData);
                }

                if (chartData.stackedDivision) {
                    console.log('🎯 Updating stacked chart with data:', chartData.stackedDivision);
                    renderStackedDivisionChart(chartData.stackedDivision);
                }

                if (chartData.periodLabel) {
                    const periodLabelEl = document.getElementById('periodLabel');
                    if (periodLabelEl) {
                        periodLabelEl.textContent = chartData.periodLabel;
                    }
                }
            }

            // ✅ UPDATED: Update charts by WITEL (supports multiple selection, ensure "all" case)
            function updateChartsByWitel(witel) {
                showLoading();

                const dateRange = dateRangePicker.selectedDates;
                const startDate = dateRange.length > 0 ? dateRange[0] : new Date(currentFilterState.startDate);
                const endDate = dateRange.length > 1 ? dateRange[1] : new Date(currentFilterState.endDate);

                const formattedStartDate = formatDateForApi(startDate);
                const formattedEndDate = formatDateForApi(endDate);

                // ✅ UPDATED: Handle both single and multiple witel selection, ensure "all" is sent as string
                let witelParam;
                let displayText;

                if (witel === 'all') {
                    witelParam = 'all'; // String, not array
                    displayText = 'Semua Witel (7 Witel Individual)';
                } else if (Array.isArray(witel)) {
                    witelParam = witel;
                    displayText = `${witel.length} Witel (${witel.join(', ')})`;
                } else {
                    witelParam = [witel]; // Convert single to array for consistency
                    displayText = witel;
                }

                console.log('🎯 Sending witel parameter:', witelParam, 'Display:', displayText);

                fetch('{{ route('witel.filter-by-witel') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            witel: witelParam,
                            regional: currentFilterState.selectedRegional,
                            divisi: currentFilterState.selectedDivisi,
                            start_date: formattedStartDate,
                            end_date: formattedEndDate
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('📊 Received chart data:', data.chartData);
                        updateAllCharts(data.chartData);
                        updateSummaryCards(data.summaryData);
                        hideLoading();
                        showAlert('success', `Data untuk ${displayText} berhasil dimuat`);
                    })
                    .catch(error => {
                        console.error('Error applying witel filter:', error);
                        hideLoading();
                        showAlert('error', 'Gagal menerapkan filter: ' + error.message);
                    });
            }

            // ✅ UPDATED: Regional filter function (for TREG filtering)
            function updateChartsByRegional(regional) {
                showLoading();

                const dateRange = dateRangePicker.selectedDates;
                const startDate = dateRange.length > 0 ? dateRange[0] : new Date(currentFilterState.startDate);
                const endDate = dateRange.length > 1 ? dateRange[1] : new Date(currentFilterState.endDate);

                const formattedStartDate = formatDateForApi(startDate);
                const formattedEndDate = formatDateForApi(endDate);

                fetch('{{ route('witel.filter-by-regional') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            regional: regional,
                            witel: currentFilterState.selectedWitel,
                            divisi: currentFilterState.selectedDivisi,
                            start_date: formattedStartDate,
                            end_date: formattedEndDate
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        updateAllCharts(data.chartData);
                        updateSummaryCards(data.summaryData);
                        hideLoading();
                        showAlert('success', `Data untuk ${regional === 'all' ? 'Semua Regional' : regional} berhasil dimuat`);
                    })
                    .catch(error => {
                        console.error('Error applying regional filter:', error);
                        hideLoading();
                        showAlert('error', 'Gagal menerapkan filter: ' + error.message);
                    });
            }

            function updateCharts(startDate, endDate) {
                showLoading();
                console.log('🔄 Updating charts with date range:', formatDate(startDate), '-', formatDate(endDate));
                console.log('🎯 Current witel state:', currentFilterState.selectedWitel);

                const formattedStartDate = formatDateForApi(startDate);
                const formattedEndDate = formatDateForApi(endDate);

                // ✅ NEW: Ensure proper witel parameter formatting
                let witelParam = currentFilterState.selectedWitel;
                if (witelParam !== 'all' && !Array.isArray(witelParam)) {
                    witelParam = [witelParam]; // Convert single witel to array
                }

                console.log('📤 Sending parameters:', {
                    witel: witelParam,
                    regional: currentFilterState.selectedRegional,
                    divisi: currentFilterState.selectedDivisi,
                    start_date: formattedStartDate,
                    end_date: formattedEndDate
                });

                fetch('{{ route('witel.update-charts') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            witel: witelParam,
                            regional: currentFilterState.selectedRegional,
                            divisi: currentFilterState.selectedDivisi,
                            start_date: formattedStartDate,
                            end_date: formattedEndDate
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            showAlert('warning', data.error);
                            if (data.chartData) {
                                console.log('📊 Received chart data (with warning):', data.chartData);
                                updateAllCharts(data.chartData);
                                updateSummaryCards(data.summaryData);
                            }
                            hideLoading();
                            return;
                        }

                        console.log('📊 Received chart data (success):', data.chartData);
                        updateAllCharts(data.chartData);
                        updateSummaryCards(data.summaryData);

                        hideLoading();
                        showAlert('success', `Data untuk periode ${formatDate(startDate)} - ${formatDate(endDate)} berhasil dimuat`);
                    })
                    .catch(error => {
                        console.error('Error updating charts:', error);
                        hideLoading();
                        showAlert('error', 'Gagal memuat data: ' + error.message);
                    });
            }

            function applyDivisiFilterFunc(divisionList) {
                showLoading();
                console.log('Applying division filter:', divisionList);

                const dateRange = dateRangePicker.selectedDates;
                const startDate = dateRange.length > 0 ? dateRange[0] : new Date(currentFilterState.startDate);
                const endDate = dateRange.length > 1 ? dateRange[1] : new Date(currentFilterState.endDate);

                const formattedStartDate = formatDateForApi(startDate);
                const formattedEndDate = formatDateForApi(endDate);

                updateFilterButtonText(divisionList);

                fetch('{{ route('witel.filter-by-divisi') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            divisi: divisionList,
                            witel: currentFilterState.selectedWitel,
                            regional: currentFilterState.selectedRegional,
                            start_date: formattedStartDate,
                            end_date: formattedEndDate
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }

                        updateAllCharts(data.chartData);
                        updateSummaryCards(data.summaryData);
                        hideLoading();
                    })
                    .catch(error => {
                        console.error('Error applying division filter:', error);
                        hideLoading();
                        showAlert('error', 'Gagal menerapkan filter: ' + error.message);
                    });
            }

            // Keep all existing helper functions
            function updateFilterButtonText(selectedDivisions) {
                const filterButton = document.getElementById('filterButton');
                if (!filterButton) return;

                if (selectedDivisions.length === 1) {
                    filterButton.innerHTML = `
                        <i class="fas fa-filter me-2"></i>
                        Filter Divisi: <span class="filter-divisi-value">${selectedDivisions[0]}</span>
                        <i class="fas fa-chevron-down ms-auto"></i>
                    `;
                } else if (selectedDivisions.length > 1 && selectedDivisions.length < 3) {
                    filterButton.innerHTML = `
                        <i class="fas fa-filter me-2"></i>
                        Filter Divisi: <span class="filter-divisi-value">${selectedDivisions.length} Divisi</span>
                        <i class="fas fa-chevron-down ms-auto"></i>
                    `;
                } else {
                    filterButton.innerHTML = `
                        <i class="fas fa-filter me-2"></i>
                        Filter Divisi
                        <i class="fas fa-chevron-down ms-auto"></i>
                    `;
                }

                showFilterAlert(selectedDivisions);
            }

            function showFilterAlert(selectedDivisions) {
                if (selectedDivisions.length >= 3 || selectedDivisions.length === 0) {
                    const alertContainer = document.getElementById('alertContainer');
                    if (alertContainer) {
                        alertContainer.style.display = 'none';
                    }
                    return;
                }

                let alertText = selectedDivisions.length === 1 ?
                    `Data difilter untuk divisi: ${selectedDivisions[0]}` :
                    `Data difilter untuk divisi: ${selectedDivisions.join(', ')}`;

                const alertContainer = document.getElementById('alertContainer');
                if (!alertContainer) return;

                const alert = document.createElement('div');
                alert.className = `alert alert-light alert-dismissible fade show`;
                alert.style.backgroundColor = '#e8f5e9';
                alert.style.borderColor = '#c8e6c9';
                alert.style.color = '#2e7d32';
                alert.innerHTML = `
                    ${alertText}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;

                alertContainer.innerHTML = '';
                alertContainer.appendChild(alert);
                alertContainer.style.display = 'block';
            }

            function formatDateForApi(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function showLoading() {
                document.querySelectorAll('.chart-body').forEach(container => {
                    if (!container.querySelector('.loading-overlay')) {
                        const overlay = document.createElement('div');
                        overlay.className = 'loading-overlay';
                        overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
                        container.appendChild(overlay);
                    }
                });
            }

            function hideLoading() {
                document.querySelectorAll('.loading-overlay').forEach(overlay => {
                    overlay.remove();
                });
            }

            function showAlert(type, message) {
                const alertContainer = document.getElementById('alertContainer');
                if (!alertContainer) return;

                const alert = document.createElement('div');
                alert.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
                alert.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;

                alertContainer.innerHTML = '';
                alertContainer.appendChild(alert);
                alertContainer.style.display = 'block';

                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.classList.remove('show');
                        setTimeout(() => {
                            if (alert.parentNode) {
                                alertContainer.style.display = 'none';
                                alert.remove();
                            }
                        }, 300);
                    }
                }, 5000);
            }

            function showEmptyDataState(container) {
                const emptyState = document.createElement('div');
                emptyState.className = 'empty-data-state';
                emptyState.innerHTML = `
                    <i class="fas fa-chart-bar"></i>
                    <div class="empty-text">Maaf, belum ada data yang tercatat</div>
                `;

                if (!container.querySelector('.empty-data-state')) {
                    container.appendChild(emptyState);
                }
            }

            // Initialize charts on page load
            function initializeCharts() {
                console.log('Initializing Chart.js charts with direct data:', chartData);

                if (!chartData) {
                    console.log('No chart data available');
                    showEmptyDataState(document.querySelector("#periodPerformanceChart").parentElement);
                    showEmptyDataState(document.querySelector("#stackedDivisionChart").parentElement);
                    return;
                }

                if (chartData.isEmpty === true) {
                    console.log('Chart data is empty');
                    showEmptyDataState(document.querySelector("#periodPerformanceChart").parentElement);
                    showEmptyDataState(document.querySelector("#stackedDivisionChart").parentElement);
                    return;
                }

                // Initialize charts
                if (chartData.periodPerformance) {
                    console.log('🚀 Initializing period chart with data:', chartData.periodPerformance);
                    console.log('🚀 Time series data:', chartData.timeSeriesData);
                    renderPeriodPerformanceChart('combined', chartData.periodPerformance, chartData.timeSeriesData);
                }

                if (chartData.stackedDivision) {
                    console.log('🚀 Initializing stacked chart with data:', chartData.stackedDivision);
                    renderStackedDivisionChart(chartData.stackedDivision);
                }
            }

            // ✅ NEW: Get currently selected witels
            function getSelectedWitels() {
                return Array.from(witelButtons)
                    .filter(btn => btn.classList.contains('active'))
                    .map(btn => btn.getAttribute('data-region'));
            }

            // ✅ UPDATED: Initialize witel buttons state on page load (with free toggle support)
            function initializeWitelButtons() {
                const currentWitel = currentFilterState.selectedWitel;
                console.log('Initializing witel buttons with state:', currentWitel);

                // Reset all buttons first
                resetAllWitelButtons();

                if (currentWitel === 'all') {
                    // Activate "Semua Witel" and disable others
                    semuaWitelButton.classList.add('active');
                    disableOtherWitelButtons();
                } else if (Array.isArray(currentWitel)) {
                    // Handle multiple selection on page load
                    enableAllWitelButtons();
                    semuaWitelButton.classList.remove('active');
                    currentWitel.forEach(witel => {
                        const button = document.querySelector(`.region-box[data-region="${witel}"]`);
                        if (button) {
                            button.classList.add('active');
                        }
                    });
                } else if (currentWitel && currentWitel !== 'all') {
                    // Handle single specific witel
                    enableAllWitelButtons();
                    semuaWitelButton.classList.remove('active');
                    const button = document.querySelector(`.region-box[data-region="${currentWitel}"]`);
                    if (button) {
                        button.classList.add('active');
                    }
                } else {
                    // Default to "Semua Witel" if no valid state
                    semuaWitelButton.classList.add('active');
                    disableOtherWitelButtons();
                }
            }

            // Initialize witel buttons state
            initializeWitelButtons();

            // Initialize charts on page load
            initializeCharts();
        });
    </script>
@endsection
