@extends('layouts.main')

@section('title', 'Leaderboard AM')

@section('styles')
<!-- CSS untuk Bootstrap Select -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('css/leaderboardAM.css') }}">
@endsection

@section('content')
<div class="main-content">
    <!-- Header Leaderboard -->
    <div class="header-leaderboard">
        <h1 class="header-title">
            <i class="fas fa-trophy me-3"></i>
            Leaderboard Performa Account Manager
        </h1>
        <p class="header-subtitle">
            Dashboard Performa Revenue dan Achievement Account Manager RLEGS
        </p>
    </div>

    <!-- Modern Date & Period Filter -->
    <div class="date-period-container">
        <!-- Date Filter -->
        <div class="date-filter-container">
            <button type="button" id="datePickerButton" class="date-filter">
                <i class="far fa-calendar-alt"></i>
                <span id="dateRangeText">01 Jan 2025 - 02 Oct 2025</span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <input type="text" id="dateRangeSelector" style="display: none;" />
        </div>

        <!-- Modern Period Tabs -->
        <div class="period-tabs">
            <button class="period-tab active" data-period="year_to_date">
                <i class="fas fa-calendar-day me-2"></i>Year to Date
            </button>
            <button class="period-tab" data-period="current_month">
                <i class="fas fa-calendar-day me-2"></i>Month to Date
            </button>
        </div>

        <div class="period-display">
            Tampilan: <strong id="displayPeriodText">Year to Date</strong>
        </div>
    </div>

    <!-- Search & Filter Area -->
    <div class="search-filter-container">
        <div class="search-box">
            <div class="search-input">
                <input type="search" name="search" placeholder="Telusuri nama AM" value="">
                <button type="button">
                    <i class="fas fa-search"></i> Cari
                </button>
            </div>
        </div>

        <div class="filter-area">
            <div class="filter-selects">
                <!-- Witel Filter -->
                <div class="filter-group">
                    <select class="selectpicker" id="filterSelect2" name="region_filter[]" multiple data-live-search="true" title="Pilih Witel" data-width="100%">
                        <option>Witel Bali</option>
                        <option>Witel Jatim Barat</option>
                        <option>Witel Jatim Timur</option>
                        <option>Witel Nusa Tenggara</option>
                        <option>Witel Semarang Jateng Utara</option>
                        <option>Witel Solo Jateng Timur</option>
                        <option>Witel Suramadu</option>
                        <option>Witel Yogya Jateng Selatan</option>
                    </select>
                </div>

                <!-- Divisi Filter -->
                <div class="filter-group">
                    <select class="selectpicker" id="filterSelect3" name="divisi_filter[]" multiple data-live-search="true" title="Pilih Divisi" data-width="100%">
                        <option value="1">DGS</option>
                        <option value="2">DPS</option>
                        <option value="2">DSS</option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="filter-group">
                    <select class="selectpicker" id="filterSelect4" name="category_filter[]" multiple data-live-search="true" title="Pilih Kategori" data-width="100%">
                        <option value="enterprise">
                            Enterprise
                        </option>
                        <option value="government">
                            Government
                        </option>
                        <option value="multi">
                            Multi Divisi
                        </option>
                    </select>
                </div>

                <!-- Jenis Revenue Filter -->
                <div class="filter-group">
                    <select class="selectpicker" id="filterSelect1" name="filter_by[]" multiple data-live-search="true" title="Jenis Revenue" data-width="100%">
                        <option value="Reguler Revenue">
                            Reguler
                        </option>
                        <option value="NGTMA Revenue">
                            NGTMA
                        </option>
                        <option value="Kombinasi Revenue">
                            Kombinasi
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </div>


    <!-- Enhanced Leaderboard AM Cards -->
    <div class="am-card">
        <div class="am-card-body">
            <div class="am-rank text-gold"> 1</div>

            {{-- FOTO PROFIL + MAHKOTA --}}
            <div class="am-avatar">
                <img
                    src="{{ asset('img/profile.png') }}"
                    class="am-profile-pic"
                    alt="Account Manager"
                >
                <span class="rank-crown rank-1">
                    <i class="fas fa-crown"></i>
                </span>
            </div>

            {{-- Info AM --}}
            <div class="am-info">
                <div class="am-name">John Doe</div>

                <div class="am-detail">
                    <i class="fas fa-map-marker-alt"></i>
                    AM Witel Jakarta
                </div>

                <div class="am-detail">
                    <i class="fas fa-layer-group"></i>
                    Enterprise, Government
                </div>

                <div class="am-category-badge enterprise">
                    ENTERPRISE
                </div>
            </div>

            {{-- Stats --}}
            <div class="am-stats">
                <div class="revenue-stat">
                    <div class="revenue-label">Revenue</div>
                    <div class="revenue-value">Rp 15.750.000.000</div>
                </div>

                <div class="achievement-stat">
                    <div class="achievement-label">Achievement</div>
                    <div class="achievement-value text-success">
                        <div class="achievement-icon">
                            <i class="fas fa-arrow-up"></i>
                            <span>125,50%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="am-card">
        <div class="am-card-body">
            <div class="am-rank text-silver"> 2</div>

            <div class="am-avatar">
                <img
                    src="{{ asset('img/profile.png') }}"
                    class="am-profile-pic"
                    alt="Account Manager"
                >
                <span class="rank-crown rank-2">
                    <i class="fas fa-crown"></i>
                </span>
            </div>

            <div class="am-info">
                <div class="am-name">Jane Smith</div>

                <div class="am-detail">
                    <i class="fas fa-map-marker-alt"></i>
                    AM Witel Bandung
                </div>

                <div class="am-detail">
                    <i class="fas fa-layer-group"></i>
                    Government
                </div>

                <div class="am-category-badge government">
                    GOVERNMENT
                </div>
            </div>

            <div class="am-stats">
                <div class="revenue-stat">
                    <div class="revenue-label">Revenue</div>
                    <div class="revenue-value">Rp 12.500.000.000</div>
                </div>

                <div class="achievement-stat">
                    <div class="achievement-label">Achievement</div>
                    <div class="achievement-value text-success">
                        <div class="achievement-icon">
                            <i class="fas fa-arrow-up"></i>
                            <span>118,75%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="am-card">
        <div class="am-card-body">
            <div class="am-rank text-bronze"> 3</div>

            <div class="am-avatar">
                <img
                    src="{{ asset('img/profile.png') }}"
                    class="am-profile-pic"
                    alt="Account Manager"
                >
                <span class="rank-crown rank-3">
                    <i class="fas fa-crown"></i>
                </span>
            </div>

            <div class="am-info">
                <div class="am-name">Bob Johnson</div>

                <div class="am-detail">
                    <i class="fas fa-map-marker-alt"></i>
                    AM Witel Surabaya
                </div>

                <div class="am-detail">
                    <i class="fas fa-layer-group"></i>
                    Enterprise, Government, Wholesale
                </div>

                <div class="am-category-badge multi">
                    MULTI DIVISI
                </div>
            </div>

            <div class="am-stats">
                <div class="revenue-stat">
                    <div class="revenue-label">Revenue</div>
                    <div class="revenue-value">Rp 10.250.000.000</div>
                </div>

                <div class="achievement-stat">
                    <div class="achievement-label">Achievement</div>
                    <div class="achievement-value text-success">
                        <div class="achievement-icon">
                            <i class="fas fa-arrow-up"></i>
                            <span>110,25%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="am-card">
        <div class="am-card-body">
            <div class="am-rank">4</div>

            <div class="am-avatar">
                <img
                    src="{{ asset('img/profile.png') }}"
                    class="am-profile-pic"
                    alt="Account Manager"
                >
            </div>

            <div class="am-info">
                <div class="am-name">Alice Brown</div>

                <div class="am-detail">
                    <i class="fas fa-map-marker-alt"></i>
                    AM Witel Medan
                </div>

                <div class="am-detail">
                    <i class="fas fa-layer-group"></i>
                    Enterprise
                </div>

                <div class="am-category-badge enterprise">
                    ENTERPRISE
                </div>
            </div>

            <div class="am-stats">
                <div class="revenue-stat">
                    <div class="revenue-label">Revenue</div>
                    <div class="revenue-value">Rp 8.750.000.000</div>
                </div>

                <div class="achievement-stat">
                    <div class="achievement-label">Achievement</div>
                    <div class="achievement-value text-danger">
                        <div class="achievement-icon">
                            <i class="fas fa-arrow-down"></i>
                            <span>95,50%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="am-card">
        <div class="am-card-body">
            <div class="am-rank">5</div>

            <div class="am-avatar">
                <img
                    src="{{ asset('img/profile.png') }}"
                    class="am-profile-pic"
                    alt="Account Manager"
                >
            </div>

            <div class="am-info">
                <div class="am-name">Charlie Davis</div>

                <div class="am-detail">
                    <i class="fas fa-map-marker-alt"></i>
                    AM Witel Makassar
                </div>

                <div class="am-detail">
                    <i class="fas fa-layer-group"></i>
                    Government
                </div>

                <div class="am-category-badge government">
                    GOVERNMENT
                </div>
            </div>

            <div class="am-stats">
                <div class="revenue-stat">
                    <div class="revenue-label">Revenue</div>
                    <div class="revenue-value">Rp 7.500.000.000</div>
                </div>

                <div class="achievement-stat">
                    <div class="achievement-label">Achievement</div>
                    <div class="achievement-value text-success">
                        <div class="achievement-icon">
                            <i class="fas fa-arrow-up"></i>
                            <span>102,25%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Per Page Selection -->
    <div class="per-page-selection">
        <select id="perPage" class="per-page-select">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="75">75</option>
            <option value="100">100</option>
        </select>
    </div>
</div>
@endsection

@section('scripts')
<!-- Script untuk Bootstrap Select -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/js/bootstrap-select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
$(document).ready(function() {
    // Inisialisasi Bootstrap Select
    $('.selectpicker').selectpicker({
        liveSearch: true,
        liveSearchPlaceholder: 'Cari opsi...',
        size: 6,
        actionsBox: false,
        dropupAuto: false,
        mobile: false,
        noneSelectedText: 'Pilih filter',
        style: '',
        styleBase: 'form-control'
    });

    // Initialize period tabs
    $('.period-tab').removeClass('active');
    $('.period-tab[data-period="year_to_date"]').addClass('active');

    // Filter form submissions
    $('#filterSelect1').on('changed.bs.select', function (e) {
        console.log('Filter 1 changed:', $(this).val());
    });

    $('#filterSelect2').on('changed.bs.select', function (e) {
        console.log('Filter 2 changed:', $(this).val());
    });

    $('#filterSelect3').on('changed.bs.select', function (e) {
        console.log('Filter 3 changed:', $(this).val());
    });

    $('#filterSelect4').on('changed.bs.select', function (e) {
        console.log('Filter 4 changed:', $(this).val());
    });

    // Modern Period Tabs with better visual feedback
    $('.period-tab').click(function() {
        const period = $(this).data('period');

        console.log('Period tab clicked:', period);

        // Update active state immediately for better UX
        $('.period-tab').removeClass('active');
        $(this).addClass('active');

        // Update display text
        let displayText = '';
        switch(period) {
            case 'year_to_date':
                displayText = 'Year to Date';
                break;
            case 'current_month':
                displayText = 'Bulan Ini';
                break;
            case 'custom':
                displayText = 'Kustom';
                break;
        }
        $('#displayPeriodText').text(displayText);
    });

    // Date Picker Functionality
    const dateRangeInput = document.getElementById('dateRangeSelector');
    const datePickerButton = document.getElementById('datePickerButton');

    const fp = flatpickr(dateRangeInput, {
        mode: "range",
        dateFormat: "Y-m-d",
        appendTo: document.querySelector('.date-period-container'),
        positionElement: datePickerButton,
        position: "below",
        static: false,
        defaultDate: ["2025-01-01", "2025-10-02"],
        onChange: function(selectedDates, dateStr) {
            if (selectedDates.length === 2) {
                const startDate = formatDate(selectedDates[0]);
                const endDate = formatDate(selectedDates[1]);
                document.getElementById('dateRangeText').textContent = startDate + ' - ' + endDate;

                // Set custom period as active
                $('.period-tab').removeClass('active');
                document.getElementById('displayPeriodText').textContent = 'Kustom';
            }
        },
        onOpen: function() {
            // Ensure proper positioning when opened
            setTimeout(() => {
                const calendar = document.querySelector('.flatpickr-calendar');
                if (calendar) {
                    const buttonRect = datePickerButton.getBoundingClientRect();
                    const containerRect = document.querySelector('.date-period-container').getBoundingClientRect();

                    // Position relative to button
                    calendar.style.position = 'absolute';
                    calendar.style.top = (buttonRect.bottom - containerRect.top + 5) + 'px';
                    calendar.style.left = (buttonRect.left - containerRect.left) + 'px';
                    calendar.style.zIndex = '9999';
                }
            }, 10);
        }
    });

    datePickerButton.addEventListener('click', function() {
        fp.open();
    });

    function formatDate(date) {
        const day = date.getDate();
        const month = date.toLocaleString('default', { month: 'short' });
        const year = date.getFullYear();
        return `${day} ${month} ${year}`;
    }
});
</script>
@endsection