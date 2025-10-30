@php
    $currentYear = date('Y');
    $currentMonth = date('n');
    $startYear = 2020;
    $divisions = [
        ['id' => 3, 'code' => 'DPS', 'name' => 'DPS (Private)', 'icon' => 'fa-building'],
        ['id' => 2, 'code' => 'DSS', 'name' => 'DSS (BUMN/Korporasi)', 'icon' => 'fa-building-columns'],
        ['id' => 1, 'code' => 'DGS', 'name' => 'DGS (Government)', 'icon' => 'fa-landmark'],
    ];
@endphp

<div class="witel-layout-card shadow-sm border-gray-200 bg-white h-full flex flex-col" id="top-customers-card">
    {{-- Card Header --}}
    <div class="flex witel-layout-card-header pb-0 border-b border-gray-200 flex-shrink-0">
        <div class="header-top-row flex items-start justify-between gap-3 mb-4">
            <div class="header-title-group flex-1">
                <div id="top-cust-title-icon-wrapper">
                     <i class="fas fa-trophy"></i>
                </div>
                <div>
                    <h2 id="top-cust-title" class="header-title text-lg font-bold text-gray-900">Overall Customers Leaderboard</h2>
                    <p id="top-cust-subtitle" class="header-subtitle text-sm text-gray-500">
                        Peringkat Revenue Pelanggan (YTD)
                        <span id="top-cust-source-label">- REGULER</span>
                    </p>
                </div>
            </div>
            <div id="witel-filters" class="top-cust-filter-container filters-form">
                 {{-- Mode Filter --}}
                <div>
                    <label for="top-cust-mode" class="text-sm">Range:</label>
                    <select id="top-cust-mode" class="filter-input">
                        <option value="ytd" selected>YTD</option>
                        <option value="monthly">Monthly</option>
                        <option value="annual">Annual</option>
                    </select>
                </div>

                {{-- Year Filter (Conditional) --}}
                <div id="top-cust-year-filter">
                    <label for="top-cust-year" class="text-sm">Year:</label>
                    <select id="top-cust-year" class="filter-input">
                        @for ($y = $currentYear; $y >= $startYear; $y--)
                        <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Month Filter (Conditional) --}}
                <div id="top-cust-month-filter">
                    <label for="top-cust-month" class="text-sm">Month:</label>
                    <select id="top-cust-month" class="filter-input">
                        @foreach (['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Des'] as $i => $m)
                        <option value="{{ $i + 1 }}" {{ ($i + 1) == $currentMonth ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs text-gray-600 mr-2" for="top-cust-source">Source:</label>
                    <select id="top-cust-source" class="filter-input border rounded-lg px-2 py-1 text-xs w-28">
                        <option value="reguler" selected>REGULER</option>
                        <option value="ngtma">NGTMA</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Card Content with Tabs --}}
    <div class="card-content flex-1 flex flex-col p-0"> {{-- Remove padding from card-content --}}
        {{-- Tabs List --}}
        <div class="top-cust-tabs-list px-1 mb-0 flex-shrink-0">
            @foreach ($divisions as $div)
            <button type="button" class="top-cust-tab" data-division-id="{{ $div['id'] }}" data-division-code="{{ $div['code'] }}">
                {{ $div['name'] }}
            </button>
            @endforeach
        </div>

        <div id="top-cust-search-container" class="flex-shrink-0 pt-3">
             <input type="search" id="top-cust-search" placeholder="Cari nama customer...">
        </div>

        {{-- Tab Content Area --}}
        <div class="flex-1 overflow-y-auto px-6 pb-6">
            <div id="top-cust-loading" class="placeholder-text">Loading...</div>
            <div id="top-cust-error" class="placeholder-text error" style="display: none;"></div>
            <div id="top-cust-list-container" class="space-y-3 pt-3">
                {{-- Customer rows and other messages will be injected here --}}
            </div>
        </div>
    </div>
</div>

{{-- Template for Customer Row --}}
<template id="top-cust-row-template">
    <div class="customers-leaderboard-list-row top-cust-row flex items-center justify-between p-3 rounded-lg border hover:shadow-sm transition-all duration-200 mx-4 my-2">
        <div class="customer-info-group flex items-center space-x-3 min-w-0">
            <div data-el="rank" class="rank-badge flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-sm font-bold flex-shrink-0"></div>
            <i data-el="crown" class="crown-icon fas fa-crown h-4 w-4 text-yellow-500 flex-shrink-0" style="display: none;"></i>
            <i data-el="icon" class="division-icon fas h-4 w-4 flex-shrink-0"></i> {{-- Division Icon --}}

            <div class="customer-text-details min-w-0 flex-1">
                <div data-el="name" class="font-medium text-sm text-gray-900 truncate"></div>
                <div data-el="witel" class="text-xs text-gray-500 truncate"></div>
            </div>
        </div>

        <div class="customer-progress-group">
            <div class="progress-bar-bg">
                <div data-el="progress-bar" class="progress-bar" style="width: 0%;"></div>
            </div>
            <span data-el="achievement-badge" class="badge text-xs">--%</span>
        </div>

        <div class="customer-revenue-group">
            <span data-el="revenue" class="tabular-nums">--</span>
            <div> {{-- Target Line --}}
                <span data-el="target-label" class="target-label">Target: </span>
                <span data-el="target-value" class="tabular-nums">--</span>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currentYear = new Date().getFullYear();
    const currentMonth = new Date().getMonth() + 1;

    // --- STATE & DOM ---
    const divisionMapping = {
        '3': { code: 'DPS', icon: 'fa-building', iconClass: 'icon-dps' },
        '2': { code: 'DSS', icon: 'fa-building-columns', iconClass: 'icon-dss' },
        '1': { code: 'DGS', icon: 'fa-landmark', iconClass: 'icon-dgs' }
    };

    const state = {
        source: 'reguler',
        divisionId: 3, // Default to DPS (ID 3)
        searchTerm: '',
        mode: 'ytd',
        year: currentYear,
        month: currentMonth,
    };

    const dataCache = {}; // Cache: key = "source:divisionId"
    let currentAbortController = null;

    const dom = {
        sourceFilter: document.getElementById('top-cust-source'),
        sourceLabel: document.getElementById('top-cust-source-label'),
        modeFilter: document.getElementById('top-cust-mode'),
        yearFilterContainer: document.getElementById('top-cust-year-filter'),
        monthFilterContainer: document.getElementById('top-cust-month-filter'),
        yearSelect: document.getElementById('top-cust-year'),
        monthSelect: document.getElementById('top-cust-month'),
        title: document.getElementById('top-cust-title'),
        subtitle: document.getElementById('top-cust-subtitle'),

        tabs: document.querySelectorAll('.top-cust-tab'),
        searchBar: document.getElementById('top-cust-search'),
        loading: document.getElementById('top-cust-loading'),
        error: document.getElementById('top-cust-error'),
        listContainer: document.getElementById('top-cust-list-container'),
        template: document.getElementById('top-cust-row-template'),
    };

    // --- HELPER FUNCTION (formatIDRCompact - unchanged) ---
    const formatIDRCompact = (num, flag="compact") => {
        if (num === null || num === undefined) return "—";
        const n = Number(num);
        const absN = Math.abs(n);
        const sign = n < 0 ? "-" : "";
        if (absN >= 1_000_000_000_000) return `${sign}${(n / 1_000_000_000_000).toFixed(2)} ${flag === "less" ? "Triliun" : "T"}`;
        if (absN >= 1_000_000_000) return `${sign}${(n / 1_000_000_000).toFixed(2)} ${flag === "less" ? "Milyar" : "M"}`;
        if (absN >= 1_000_000) return `${sign}${(n / 1_000_000).toFixed(2)} ${flag === "less" ? "Juta" : "Jt"}`;
        if (absN >= 1000) return `${sign}${(n / 1000).toFixed(2)} ${flag === "less" ? "Ribu" : "Rb"}`;
        return `${sign}${n.toFixed(0)}`;
    };

    function updateFilterVisibility() {
        const mode = state.mode;
        dom.yearFilterContainer.style.display = (mode === 'monthly' || mode === 'annual') ? 'flex' : 'none';
        dom.monthFilterContainer.style.display = (mode === 'monthly') ? 'flex' : 'none';
    }

    function updateHeaderText() {
        const year = state.year;
        const monthName = dom.monthSelect.options[state.month - 1].text;
        let subtitleText = '';

        if (state.mode === 'ytd') {
            subtitleText = `Peringkat Revenue Pelanggan (YTD ${monthName} ${year})`;
        } else if (state.mode === 'monthly') {
            subtitleText = `Peringkat Revenue Pelanggan (${monthName} ${year})`;
        } else if (state.mode === 'annual') {
            subtitleText = `Peringkat Revenue Pelanggan (${year})`;
        }
        subtitleText += ` <span id="top-cust-source-label">- ${state.source === 'ngtma' ? 'NGTMA' : 'REGULER'}</span>`;
        dom.subtitle.innerHTML = subtitleText; // Use innerHTML because of span
    }

    // --- DATA FETCHING ---
    async function fetchTopCustomers() {
        dom.loading.style.display = 'block';
        dom.error.style.display = 'none';
        dom.listContainer.innerHTML = '';

        // Abort previous request if any
        currentAbortController?.abort();
        currentAbortController = new AbortController();

        const cacheKey = `${state.source}:${state.divisionId}:${state.mode}:${state.year}:${state.month}`;

        // 1. Check Cache
        if (dataCache[cacheKey]) {
            renderCustomerList(filterCustomers(dataCache[cacheKey]));
            dom.loading.style.display = 'none';
            return;
        }

        let url = `/dashboard/customers-leaderboard?source=${state.source}&division_id=${state.divisionId}&mode=${state.mode}`;
        if (state.mode === 'monthly') {
            url += `&year=${state.year}&month=${state.month}`;
        } else if (state.mode === 'annual') {
            url += `&year=${state.year}`;
        }

        // 2. Fetch from API
        try {
            updateHeaderText();
            const response = await fetch(url, { signal: currentAbortController.signal });
            if (!response.ok) throw new Error('Failed to fetch data.');

            const customers = await response.json();
            dataCache[cacheKey] = customers; // Store in cache
            renderCustomerList(filterCustomers(customers));

        } catch (err) {
            if (err.name === 'AbortError') {
                console.log('Fetch aborted');
                return; // Don't show error if aborted
            }
            dom.error.textContent = `Error: ${err.message}`;
            dom.error.style.display = 'block';
        } finally {
            dom.loading.style.display = 'none';
        }
    }

    function filterCustomers(customers) {
        if (!state.searchTerm) {
            return customers; // No search term, return all
        }
        const lowerSearchTerm = state.searchTerm.toLowerCase();
        return customers.filter(customer =>
            customer.nama_cc && customer.nama_cc.toLowerCase().includes(lowerSearchTerm)
        );
    }

    const getStatusDot = (ach) => {
        if (ach == null) return 'bg-gray-400';
        if (ach >= 100) return 'bg-green-600';
        if (ach >= 80) return 'bg-amber-600';
        return 'bg-red-600';
    }

    // --- RENDERING ---
    function renderCustomerList(customers) {
        dom.listContainer.innerHTML = '';
        if (!customers || customers.length === 0) {
            const message = state.searchTerm
                ? 'No customers found matching your search.'
                : 'No data found for this division and source.';
            dom.listContainer.innerHTML = `<p class="placeholder-text">${message}</p>`;
            return;
        }

        const divisionInfo = divisionMapping[state.divisionId];
        const isSearching = !!state.searchTerm;

        customers.forEach((customer, idx) => {
            const row = dom.template.content.cloneNode(true);

            let rank, rankClass;
            const originalRank = dataCache[`${state.source}:${state.divisionId}:${state.mode}:${state.year}:${state.month}`].findIndex(c => c.nama_cc === customer.nama_cc) + 1;

            if (isSearching) {
                rank = originalRank > 0 ? originalRank : '?';
                rankClass = 'rank-default';
            } else {
                rank = idx + 1;
                const rankClasses = ['rank-gold', 'rank-silver', 'rank-bronze'];
                rankClass = rankClasses[idx] || 'rank-default';
            }

            const rankBadge = row.querySelector('[data-el="rank"]');
            rankBadge.textContent = rank;
            rankBadge.className = 'rank-badge';
            rankBadge.classList.add(rankClass);

            //row.querySelector('[data-el="rank"]').textContent = rank;
            //if (idx >= 3) { // Ranks 4+ use default badge style
            //     row.querySelector('[data-el="rank"]').classList.add('rank-default');
            //} else { // Top 3 might get special styling later if needed
            //     row.querySelector('[data-el="rank"]').classList.add('rank-default'); // Default for now
            //}

            if (!isSearching && idx < 3) {
                row.querySelector('[data-el="crown"]').style.display = 'inline-block';
            } else {
                 row.querySelector('[data-el="crown"]').style.display = 'none';
            }

            const iconEl = row.querySelector('[data-el="icon"]');
            iconEl.className = 'fas division-icon';
            iconEl.classList.add(divisionInfo.icon, divisionInfo.iconClass); // Add FA class and color class

            row.querySelector('[data-el="name"]').textContent = customer.nama_cc || 'N/A';
            row.querySelector('[data-el="witel"]').textContent = customer.witel_name || 'Unknown Witel';

            row.querySelector('[data-el="revenue"]').textContent = `Rp${formatIDRCompact(customer.total_revenue)}`;
            row.querySelector('[data-el="target-value"]').textContent = `Rp${formatIDRCompact(customer.target_revenue)}`;

            const achievement = customer.achievement;
            const progressBar = row.querySelector('[data-el="progress-bar"]');
            const achievementBadge = row.querySelector('[data-el="achievement-badge"]');

            if (achievement !== null) {
                const progressPercent = Math.min(Math.max(achievement, 0), 100);
                const colorClass = getStatusDot(achievement);

                progressBar.style.width = `${progressPercent}%`;
                progressBar.className = 'progress-bar';
                progressBar.classList.add(colorClass);

                achievementBadge.textContent = `${achievement.toFixed(1)}%`;
            } else {
                progressBar.style.width = '0%';
                progressBar.className = 'progress-bar bg-gray-400';
                achievementBadge.textContent = 'N/A';
            }

            dom.listContainer.appendChild(row.firstElementChild);
        });
    }

    // --- EVENT LISTENERS ---
    function setupEventListeners() {
        dom.sourceFilter.addEventListener('change', (e) => {
            state.source = e.target.value;
            dom.sourceLabel.textContent = `- ${(state.source === 'ngtma' ? 'NGTMA' : 'REGULER')}`;
            state.searchTerm = '';
            dom.searchBar.value = '';
            fetchTopCustomers();
            updateHeaderText();
        });

        dom.tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Update state
                state.divisionId = tab.dataset.divisionId;

                // Update active tab style
                dom.tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                 // Reset search on tab change
                state.searchTerm = '';
                dom.searchBar.value = '';

                // Fetch data
                fetchTopCustomers();
            });
        });

        // Mode Filter
        dom.modeFilter.addEventListener('change', (e) => {
            state.mode = e.target.value;
             // Reset year/month state if switching TO YTD
            if (state.mode === 'ytd') {
                state.year = currentYear;
                state.month = currentMonth;
            }
            // Ensure state matches dropdown if switching FROM YTD
            else {
                state.year = dom.yearSelect.value;
                state.month = dom.monthSelect.value;
            }
             // Always update UI selections to match state
            dom.yearSelect.value = state.year;
            dom.monthSelect.value = state.month;

            updateHeaderText();
            updateFilterVisibility();
            state.searchTerm = '';
            dom.searchBar.value = '';
            fetchTopCustomers();
        });

        // Year Filter
        dom.yearSelect.addEventListener('change', (e) => {
            state.year = e.target.value;
            state.searchTerm = '';
            dom.searchBar.value = '';
            fetchTopCustomers();
        });

        // Month Filter
        dom.monthSelect.addEventListener('change', (e) => {
            state.month = e.target.value;
            state.searchTerm = '';
            dom.searchBar.value = '';
            fetchTopCustomers();
        });

        let searchTimeout;
        dom.searchBar.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            state.searchTerm = e.target.value;

            // Debounce: Wait 300ms after user stops typing before filtering
            searchTimeout = setTimeout(() => {
                const cacheKey = `${state.source}:${state.divisionId}:${state.mode}:${state.year}:${state.month}`;
                if (dataCache[cacheKey]) {
                    // We have data, just re-render the filtered list
                    renderCustomerList(filterCustomers(dataCache[cacheKey]));
                } else {
                    // Data hasn't loaded yet, fetch will handle filtering later
                    fetchTopCustomers();
                }
            }, 300);
        });
    }

    // --- INITIALIZATION ---
    function initialize() {
        // Set initial active tab
        const initialTab = document.querySelector(`.top-cust-tab[data-division-id="${state.divisionId}"]`);
        initialTab?.classList.add('active');

        updateFilterVisibility();

        // Initial fetch
        fetchTopCustomers();
    }

    setupEventListeners()
    initialize();
});
</script>
@endpush
