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
                <span id="dateRangeText">{{ date('d M Y', strtotime($startDate ?? Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'))) }} -
                {{ date('d M Y', strtotime($endDate ?? Carbon\Carbon::now()->endOfMonth()->format('Y-m-d'))) }}</span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <input type="text" id="dateRangeSelector" style="display: none;" />
        </div>

        <!-- Modern Period Tabs -->
        <div class="period-tabs">
            <button class="period-tab {{ $currentPeriod == 'year_to_date' ? 'active' : '' }}" data-period="year_to_date">
                <i class="fas fa-calendar-day me-2"></i>Year to Date
            </button>
            <button class="period-tab {{ $currentPeriod == 'current_month' ? 'active' : '' }}" data-period="current_month">
                <i class="fas fa-calendar-day me-2"></i>Month to Date
            </button>
        </div>

        <div class="period-display">
            Tampilan: <strong id="displayPeriodText">{{ $displayPeriod }}</strong>
        </div>
    </div>

    <!-- Filter Info Section -->
    @if(request('search') || request('filter_by') || request('region_filter') || request('divisi_filter') || request('category_filter'))
    <div class="filter-info">
        <div class="filter-info-icon">
            <i class="fas fa-filter"></i>
        </div>
        <div class="filter-info-text">
            <strong>Menampilkan hasil peringkat</strong>
            @if(request('search'))
                untuk pencarian "<span class="text-primary fw-bold">{{ request('search') }}</span>"
            @endif

            @if(request('filter_by'))
                @if(request('search')) dengan @endif
                kriteria:
                @foreach(request('filter_by') as $filter)
                    <span class="text-primary fw-bold">{{ $filter }}</span>{{ !$loop->last ? ', ' : '' }}
                @endforeach
            @endif

            @if(request('region_filter'))
                @if(request('search') || request('filter_by')) di @endif
                Witel:
                @foreach(request('region_filter') as $region)
                    <span class="text-primary fw-bold">{{ $region }}</span>{{ !$loop->last ? ', ' : '' }}
                @endforeach
            @endif

            @if(request('divisi_filter'))
                @if(request('search') || request('filter_by') || request('region_filter')) | @endif
                Divisi:
                @foreach(request('divisi_filter') as $divisiId)
                    @php
                        $divisi = $divisis->find($divisiId);
                    @endphp
                    <span class="text-primary fw-bold">{{ $divisi ? $divisi->nama : $divisiId }}</span>{{ !$loop->last ? ', ' : '' }}
                @endforeach
            @endif

            @if(request('category_filter'))
                @if(request('search') || request('filter_by') || request('region_filter') || request('divisi_filter')) | @endif
                Kategori:
                @foreach(request('category_filter') as $category)
                    <span class="text-primary fw-bold">{{ ucfirst($category) }}</span>{{ !$loop->last ? ', ' : '' }}
                @endforeach
            @endif
        </div>
        <div class="filter-info-reset">
            <a href="{{ route('leaderboard', ['period' => request('period')]) }}" class="reset-btn">
                <i class="fas fa-undo"></i> Reset Filter
            </a>
        </div>
    </div>
    @endif


    <!-- Search & Filter Area -->
    <div class="search-filter-container">
        <div class="search-box">
            <form action="{{ route('leaderboard') }}" method="GET" id="searchForm" class="search-input">
                <input type="search" name="search" placeholder="Telusuri nama AM" value="{{ request('search') }}">
                <!-- Preserve all current filters -->
                @if(request('period'))
                    <input type="hidden" name="period" value="{{ request('period') }}">
                @endif
                @if(request('filter_by'))
                    @foreach(request('filter_by') as $filter)
                        <input type="hidden" name="filter_by[]" value="{{ $filter }}">
                    @endforeach
                @endif
                @if(request('region_filter'))
                    @foreach(request('region_filter') as $region)
                        <input type="hidden" name="region_filter[]" value="{{ $region }}">
                    @endforeach
                @endif
                @if(request('divisi_filter'))
                    @foreach(request('divisi_filter') as $divisi)
                        <input type="hidden" name="divisi_filter[]" value="{{ $divisi }}">
                    @endforeach
                @endif
                @if(request('category_filter'))
                    @foreach(request('category_filter') as $category)
                        <input type="hidden" name="category_filter[]" value="{{ $category }}">
                    @endforeach
                @endif
                <button type="submit">
                    <i class="fas fa-search"></i> Cari
                </button>
            </form>
        </div>

        <div class="filter-area">
            <div class="filter-selects">
                <!-- Kriteria Filter -->
                <div class="filter-group">
                    <form id="filterForm1" action="{{ route('leaderboard') }}" method="GET">
                        <select class="selectpicker" id="filterSelect1" name="filter_by[]" multiple data-live-search="true" title="Pilih Kriteria" data-width="100%">
                            <option value="Revenue Realisasi Tertinggi" {{ in_array('Revenue Realisasi Tertinggi', request('filter_by', [])) ? 'selected' : '' }}>
                                Revenue Tertinggi
                            </option>
                            <option value="Achievement Tertinggi" {{ in_array('Achievement Tertinggi', request('filter_by', [])) ? 'selected' : '' }}>
                                Achievement Tertinggi
                            </option>
                        </select>
                        <!-- Preserve other filters -->
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        @if(request('period'))
                            <input type="hidden" name="period" value="{{ request('period') }}">
                        @endif
                        @if(request('region_filter'))
                            @foreach(request('region_filter') as $region)
                                <input type="hidden" name="region_filter[]" value="{{ $region }}">
                            @endforeach
                        @endif
                        @if(request('divisi_filter'))
                            @foreach(request('divisi_filter') as $divisi)
                                <input type="hidden" name="divisi_filter[]" value="{{ $divisi }}">
                            @endforeach
                        @endif
                        @if(request('category_filter'))
                            @foreach(request('category_filter') as $category)
                                <input type="hidden" name="category_filter[]" value="{{ $category }}">
                            @endforeach
                        @endif
                        <button type="submit" class="d-none" id="submitFilter1">Submit</button>
                    </form>
                </div>

                <!-- Witel Filter -->
                <div class="filter-group">
                    <form id="filterForm2" action="{{ route('leaderboard') }}" method="GET">
                        <select class="selectpicker" id="filterSelect2" name="region_filter[]" multiple data-live-search="true" title="Pilih Witel" data-width="100%">
                            @foreach($witels as $witel)
                                <option value="{{ $witel->nama }}" {{ in_array($witel->nama, request('region_filter', [])) ? 'selected' : '' }}>
                                    {{ $witel->nama }}
                                </option>
                            @endforeach
                        </select>
                        <!-- Preserve other filters -->
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        @if(request('period'))
                            <input type="hidden" name="period" value="{{ request('period') }}">
                        @endif
                        @if(request('filter_by'))
                            @foreach(request('filter_by') as $filter)
                                <input type="hidden" name="filter_by[]" value="{{ $filter }}">
                            @endforeach
                        @endif
                        @if(request('divisi_filter'))
                            @foreach(request('divisi_filter') as $divisi)
                                <input type="hidden" name="divisi_filter[]" value="{{ $divisi }}">
                            @endforeach
                        @endif
                        @if(request('category_filter'))
                            @foreach(request('category_filter') as $category)
                                <input type="hidden" name="category_filter[]" value="{{ $category }}">
                            @endforeach
                        @endif
                        <button type="submit" class="d-none" id="submitFilter2">Submit</button>
                    </form>
                </div>

                <!-- NEW: Divisi Filter -->
                <div class="filter-group">
                    <form id="filterForm3" action="{{ route('leaderboard') }}" method="GET">
                        <select class="selectpicker" id="filterSelect3" name="divisi_filter[]" multiple data-live-search="true" title="Pilih Divisi" data-width="100%">
                            @foreach($divisis as $divisi)
                                <option value="{{ $divisi->id }}" {{ in_array($divisi->id, request('divisi_filter', [])) ? 'selected' : '' }}>
                                    {{ $divisi->nama }}
                                </option>
                            @endforeach
                        </select>
                        <!-- Preserve other filters -->
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        @if(request('period'))
                            <input type="hidden" name="period" value="{{ request('period') }}">
                        @endif
                        @if(request('filter_by'))
                            @foreach(request('filter_by') as $filter)
                                <input type="hidden" name="filter_by[]" value="{{ $filter }}">
                            @endforeach
                        @endif
                        @if(request('region_filter'))
                            @foreach(request('region_filter') as $region)
                                <input type="hidden" name="region_filter[]" value="{{ $region }}">
                            @endforeach
                        @endif
                        @if(request('category_filter'))
                            @foreach(request('category_filter') as $category)
                                <input type="hidden" name="category_filter[]" value="{{ $category }}">
                            @endforeach
                        @endif
                        <button type="submit" class="d-none" id="submitFilter3">Submit</button>
                    </form>
                </div>

                <!-- NEW: Category Filter -->
                <div class="filter-group">
                    <form id="filterForm4" action="{{ route('leaderboard') }}" method="GET">
                        <select class="selectpicker" id="filterSelect4" name="category_filter[]" multiple data-live-search="true" title="Pilih Kategori" data-width="100%">
                            <option value="enterprise" {{ in_array('enterprise', request('category_filter', [])) ? 'selected' : '' }}>
                                Enterprise
                            </option>
                            <option value="government" {{ in_array('government', request('category_filter', [])) ? 'selected' : '' }}>
                                Government
                            </option>
                            <option value="multi" {{ in_array('multi', request('category_filter', [])) ? 'selected' : '' }}>
                                Multi Divisi
                            </option>
                        </select>
                        <!-- Preserve other filters -->
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        @if(request('period'))
                            <input type="hidden" name="period" value="{{ request('period') }}">
                        @endif
                        @if(request('filter_by'))
                            @foreach(request('filter_by') as $filter)
                                <input type="hidden" name="filter_by[]" value="{{ $filter }}">
                            @endforeach
                        @endif
                        @if(request('region_filter'))
                            @foreach(request('region_filter') as $region)
                                <input type="hidden" name="region_filter[]" value="{{ $region }}">
                            @endforeach
                        @endif
                        @if(request('divisi_filter'))
                            @foreach(request('divisi_filter') as $divisi)
                                <input type="hidden" name="divisi_filter[]" value="{{ $divisi }}">
                            @endforeach
                        @endif
                        <button type="submit" class="d-none" id="submitFilter4">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Enhanced Leaderboard AM Cards -->
    @forelse($accountManagers as $index => $am)
        <div class="am-card" onclick="window.location.href='{{ route('account_manager.detail', $am->id) }}'">
            <div class="am-card-body">
                @if($am->global_rank == 1)
                    <div class="am-rank text-gold"> 1</div>
                @elseif($am->global_rank == 2)
                    <div class="am-rank text-silver"> 2</div>
                @elseif($am->global_rank == 3)
                    <div class="am-rank text-bronze"> 3</div>
                @else
                    <div class="am-rank">{{ $am->global_rank }}</div>
                @endif

                {{-- FOTO PROFIL + MAHKOTA (hapus IMG yang berdiri sendiri) --}}
                <div class="am-avatar">
                    <img
                        src="{{ asset($am->user && $am->user->profile_image ? 'storage/'.$am->user->profile_image : 'img/profile.png') }}"
                        class="am-profile-pic"
                        alt="{{ $am->nama }}"
                    >
                    @if($am->global_rank && $am->global_rank <= 3)
                        <span class="rank-crown rank-{{ $am->global_rank }}">
                            <i class="fas fa-crown"></i>
                        </span>
                    @endif
                </div>

                {{-- Info AM --}}
                <div class="am-info">
                    <div class="am-name">{{ $am->nama }}</div>

                    <div class="am-detail">
                        <i class="fas fa-map-marker-alt"></i>
                        AM Witel {{ $am->witel->nama ?? 'N/A' }}
                    </div>

                    <div class="am-detail">
                        <i class="fas fa-layer-group"></i>
                        @if($am->divisis->count() > 0)
                            {{ $am->divisis->pluck('nama')->join(', ') }}
                        @else
                            N/A
                        @endif
                    </div>

                    @if(isset($am->category_info))
                        @php
                            $badgeClass = 'enterprise';
                            if($am->category_info['category'] === 'GOVERNMENT') $badgeClass = 'government';
                            elseif($am->category_info['category'] === 'MULTI') $badgeClass = 'multi';
                        @endphp
                        <div class="am-category-badge {{ $badgeClass }}">
                            {{ $am->category_info['label'] }}
                        </div>
                    @endif
                </div>

                {{-- Stats --}}
                <div class="am-stats">
                    <div class="revenue-stat">
                        <div class="revenue-label">Revenue</div>
                        <div class="revenue-value">Rp {{ number_format($am->total_real_revenue, 0, ',', '.') }}</div>
                    </div>

                <div class="achievement-stat">
                    <div class="achievement-label">Achievement</div>
                    <div class="achievement-value {{ $am->achievement_percentage < 100 ? 'text-danger' : 'text-success' }}">
                        <div class="achievement-icon">
                            <i class="fas {{ $am->achievement_percentage < 100 ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
                            <span>{{ number_format($am->achievement_percentage, 2, ',', '.') }}%</span>
                        </div>
            </div>
        </div>
    </div>
</div>

        </div>
    @empty
        <div class="empty-state">
            <i class="fas fa-users-slash"></i>
            <h3>Tidak Ada Data</h3>
            <p>Tidak ada Account Manager yang sesuai dengan kriteria pencarian Anda.</p>
        </div>
    @endforelse
</div>

    <!-- Per Page Selection -->
        <div class="per-page-selection">
            <select id="perPage" class="per-page-select" onchange="changePerPage(this.value)">
                <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ request('per_page', 10) == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50</option>
                <option value="75" {{ request('per_page', 10) == 75 ? 'selected' : '' }}>75</option>
                <option value="100" {{ request('per_page', 10) == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
@endsection

@section('scripts')
<!-- Script untuk Bootstrap Select -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/js/bootstrap-select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        function changePerPage(perPage) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', perPage);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
    </script>

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

    // Initialize period tabs based on URL parameter or default
    function initializePeriodTabs() {
        const urlParams = new URLSearchParams(window.location.search);
        let currentPeriod = urlParams.get('period') || '{{ $currentPeriod ?? "all_time" }}';

        // Remove active class from all tabs
        $('.period-tab').removeClass('active');

        // Add active class to current period
        $(`.period-tab[data-period="${currentPeriod}"]`).addClass('active');

        // If no period is set or period is all_time, default to year_to_date
        if (!currentPeriod || currentPeriod === 'all_time') {
            $('.period-tab[data-period="year_to_date"]').addClass('active');
        }

        console.log('Current period:', currentPeriod); // Debug log
    }

    // Call initialization
    initializePeriodTabs();

    // Filter form submissions
    $('#filterSelect1').on('changed.bs.select', function (e) {
        setTimeout(() => $('#submitFilter1').click(), 300);
    });

    $('#filterSelect2').on('changed.bs.select', function (e) {
        setTimeout(() => $('#submitFilter2').click(), 300);
    });

    $('#filterSelect3').on('changed.bs.select', function (e) {
        setTimeout(() => $('#submitFilter3').click(), 300);
    });

    $('#filterSelect4').on('changed.bs.select', function (e) {
        setTimeout(() => $('#submitFilter4').click(), 300);
    });

    // Modern Period Tabs with better visual feedback
    $('.period-tab').click(function() {
        const period = $(this).data('period');

        console.log('Period tab clicked:', period); // Debug log

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

        // Submit with period
        submitPeriodForm(period);
    });

    function submitPeriodForm(period) {
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = window.location.pathname;

        // Add period parameter
        const periodInput = document.createElement('input');
        periodInput.type = 'hidden';
        periodInput.name = 'period';
        periodInput.value = period;
        form.appendChild(periodInput);

        // Preserve other filters
        @if(request('search'))
            const searchInput = document.createElement('input');
            searchInput.type = 'hidden';
            searchInput.name = 'search';
            searchInput.value = '{{ request('search') }}';
            form.appendChild(searchInput);
        @endif

        @if(request('filter_by'))
            @foreach(request('filter_by') as $filter)
                const filterInput{{ $loop->index }} = document.createElement('input');
                filterInput{{ $loop->index }}.type = 'hidden';
                filterInput{{ $loop->index }}.name = 'filter_by[]';
                filterInput{{ $loop->index }}.value = '{{ $filter }}';
                form.appendChild(filterInput{{ $loop->index }});
            @endforeach
        @endif

        @if(request('region_filter'))
            @foreach(request('region_filter') as $region)
                const regionInput{{ $loop->index }} = document.createElement('input');
                regionInput{{ $loop->index }}.type = 'hidden';
                regionInput{{ $loop->index }}.name = 'region_filter[]';
                regionInput{{ $loop->index }}.value = '{{ $region }}';
                form.appendChild(regionInput{{ $loop->index }});
            @endforeach
        @endif

        @if(request('divisi_filter'))
            @foreach(request('divisi_filter') as $divisi)
                const divisiInput{{ $loop->index }} = document.createElement('input');
                divisiInput{{ $loop->index }}.type = 'hidden';
                divisiInput{{ $loop->index }}.name = 'divisi_filter[]';
                divisiInput{{ $loop->index }}.value = '{{ $divisi }}';
                form.appendChild(divisiInput{{ $loop->index }});
            @endforeach
        @endif

        @if(request('category_filter'))
            @foreach(request('category_filter') as $category)
                const categoryInput{{ $loop->index }} = document.createElement('input');
                categoryInput{{ $loop->index }}.type = 'hidden';
                categoryInput{{ $loop->index }}.name = 'category_filter[]';
                categoryInput{{ $loop->index }}.value = '{{ $category }}';
                form.appendChild(categoryInput{{ $loop->index }});
            @endforeach
        @endif

        document.body.appendChild(form);
        form.submit();
    }

    // Date Picker Functionality
    const dateRangeInput = document.getElementById('dateRangeSelector');
    const datePickerButton = document.getElementById('datePickerButton');

    const fp = flatpickr(dateRangeInput, {
        mode: "range",
        dateFormat: "Y-m-d",
        appendTo: document.querySelector('.date-period-container'), // Append to container
        positionElement: datePickerButton, // Position relative to button
        position: "below", // Always show below
        static: false, // Allow repositioning
        defaultDate: [
            "{{ $startDate ?? \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}",
            "{{ $endDate ?? \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d') }}"
        ],
        onChange: function(selectedDates, dateStr) {
            if (selectedDates.length === 2) {
                const startDate = formatDate(selectedDates[0]);
                const endDate = formatDate(selectedDates[1]);
                document.getElementById('dateRangeText').textContent = startDate + ' - ' + endDate;

                // Set custom period as active
                $('.period-tab').removeClass('active');
                $('.period-tab[data-period="custom"]').addClass('active');
                document.getElementById('displayPeriodText').textContent = 'Kustom';

                // Submit form with custom dates
                submitCustomDateForm(selectedDates[0], selectedDates[1]);
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

    function submitCustomDateForm(startDate, endDate) {
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = window.location.pathname;

        // Add period and dates
        const periodInput = document.createElement('input');
        periodInput.type = 'hidden';
        periodInput.name = 'period';
        periodInput.value = 'custom';
        form.appendChild(periodInput);

        const startDateInput = document.createElement('input');
        startDateInput.type = 'hidden';
        startDateInput.name = 'start_date';
        startDateInput.value = startDate.toISOString().split('T')[0];
        form.appendChild(startDateInput);

        const endDateInput = document.createElement('input');
        endDateInput.type = 'hidden';
        endDateInput.name = 'end_date';
        endDateInput.value = endDate.toISOString().split('T')[0];
        form.appendChild(endDateInput);

        // Preserve other filters
        @if(request('search'))
            const searchInput = document.createElement('input');
            searchInput.type = 'hidden';
            searchInput.name = 'search';
            searchInput.value = '{{ request('search') }}';
            form.appendChild(searchInput);
        @endif

        @if(request('filter_by'))
            @foreach(request('filter_by') as $filter)
                const filterInput{{ $loop->index }} = document.createElement('input');
                filterInput{{ $loop->index }}.type = 'hidden';
                filterInput{{ $loop->index }}.name = 'filter_by[]';
                filterInput{{ $loop->index }}.value = '{{ $filter }}';
                form.appendChild(filterInput{{ $loop->index }});
            @endforeach
        @endif

        @if(request('region_filter'))
            @foreach(request('region_filter') as $region)
                const regionInput{{ $loop->index }} = document.createElement('input');
                regionInput{{ $loop->index }}.type = 'hidden';
                regionInput{{ $loop->index }}.name = 'region_filter[]';
                regionInput{{ $loop->index }}.value = '{{ $region }}';
                form.appendChild(regionInput{{ $loop->index }});
            @endforeach
        @endif

        @if(request('divisi_filter'))
            @foreach(request('divisi_filter') as $divisi)
                const divisiInput{{ $loop->index }} = document.createElement('input');
                divisiInput{{ $loop->index }}.type = 'hidden';
                divisiInput{{ $loop->index }}.name = 'divisi_filter[]';
                divisiInput{{ $loop->index }}.value = '{{ $divisi }}';
                form.appendChild(divisiInput{{ $loop->index }});
            @endforeach
        @endif

        @if(request('category_filter'))
            @foreach(request('category_filter') as $category)
                const categoryInput{{ $loop->index }} = document.createElement('input');
                categoryInput{{ $loop->index }}.type = 'hidden';
                categoryInput{{ $loop->index }}.name = 'category_filter[]';
                categoryInput{{ $loop->index }}.value = '{{ $category }}';
                form.appendChild(categoryInput{{ $loop->index }});
            @endforeach
        @endif

        document.body.appendChild(form);
        form.submit();
    }
});
</script>
@endsection