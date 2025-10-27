<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Witel Achievement</title>

    {{-- Include Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    {{-- Your main CSS file (or add styles here) --}}
    <link rel="stylesheet" href="{{ asset('css/ccwitel.css') }}">
</head>

@php
    $currentYear = date('Y');
    $currentMonth = date('n');
@endphp

<body class="bg-gray-100 p-8">
    {{-- TODO: --}}
    {{-- for the filters make three modes: YTD, Annual, and Monthly, for YTD -> itself + source, for Monthly -> itself + year + month + source, for Annual -> itself + year + source --}}
    {{-- on each witel list card, add --in respect to their mode-- YTD Revenue, YTD Target, achievement percentage is als relative to YTD target --}}

    <div class="witel-performance-layout">
        {{-- Witel Leaderboard --}}
        <div class="witel-list-pane witel-layout-card shadow-sm border-gray-200">
            {{-- The card header now contains BOTH title and filters --}}
            <div class="witel-layout-card-header pb-4">
                <div class="header-top-row">
                    <div class="header-title-group flex items-center justify-between">
                        <h2 id="header-title">
                            <i class="fas fa-building" id="witel-title-icon"></i>
                            Witel Achievement – {{ $currentYear }}
                        </h2>
                    </div>

                    {{-- Horizontal Filters --}}
                    <div id="witel-filters">
                        <div class="flex items-center gap-2">
                            <label for="witel-mode" class="text-sm">Range:</label>
                            <select id="witel-mode" class="filter-input">
                                <option value="ytd">YTD</option>
                                <option value="monthly">Monthly</option>
                                <option value="annual">Annual</option>
                            </select>
                        </div>
                        <div id="witel-year-filter" class="flex items-center gap-2">
                            <label for="witel-year" class="text-sm">Year:</label>
                            <select id="witel-year" class="filter-input">
                                @for ($y = $currentYear - 3; $y <= $currentYear; $y++)
                                <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div id="witel-month-filter" class="flex items-center gap-2">
                            <label for="witel-month" class="text-sm">Month:</label>
                            <select id="witel-month" class="filter-input">
                                @foreach (['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Des'] as $i => $m)
                                <option value="{{ $i + 1 }}" {{ ($i + 1) == $currentMonth ? 'selected' : '' }}>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <label for="witel-source" class="text-sm">Source:</label>
                            <select id="witel-source" class="filter-input w-32">
                                <option value="reguler">REGULER</option>
                                <option value="ngtma">NGTMA</option>
                            </select>
                        </div>
                    </div>

                    <div class="header-bottom-row">
                        <div id="witel-total-revenue" class="mt-1 text-sm text-gray-600">
                            Total Revenue: <span class="font-semibold text-gray-900">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- The card content is now the scrollable list container --}}
            <div class="witel-layout-card-content">
                <div id="witel-loading" class="text-sm text-gray-600">Loading...</div>
                <div id="witel-error" class="text-sm text-red-600" style="display: none;"></div>
                <div id="witel-list-container" class="space-y-4">
                    {{-- Witel cards will be injected here --}}
                </div>
            </div>
        </div>

        {{-- Customer Detail --}}
        <div class="witel-detail-pane">
            <div class="witel-layout-card shadow-sm border-gray-200">
                <div class="witel-layout-card-header pb-4">
                    <h4 id="witel-detail-title" class="text-lg font-bold text-gray-900">
                        Customer List
                    </h4>
                </div>
                <div class="witel-customer-layout-card-content">
                    <div id="witel-detail-empty" class="text-sm text-gray-500">
                        Pilih Witel dari daftar di sebelah kiri untuk melihat detail.
                    </div>
                    <div id="witel-detail-loading" class="text-sm text-gray-500" style="display: none;">
                        {{-- This will never be seen, a data is instant --}}
                    </div>
                    <div id="witel-detail-content" style="display: none;">
                        <div class="customer-list-header">
                            <span class="customer-list-header-label customer-list-header-left">Nama CC</span>
                            <div class="customer-list-header-right">
                                <span class="customer-list-header-label">Share</span>
                                <span class="customer-list-header-label">Nominal</span>
                            </div>
                        </div>
                        <ol id="witel-customer-list" class="space-y-2" style="padding-left: 0px; margin-top: 0px; margin-bottom: 0px;"></ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- This is a 'template' for a single Witel row. JavaScript will clone this, fill in the data, and append it to the list. --}}
    <template id="witel-row-template">
        <div class="witel-card">
            {{-- New wrapper for all other content --}}
            <div class="witel-card-content">
                <div class="flex justify-between items-center mb-3">
                    <div class="witel-card-name-group">
                        <div data-el="status-dot" class="witel-status-dot"></div>
                        <span data-el="witel-name" class="font-semibold text-gray-900"></span>
                    </div>
                    <div class="text-right">
                        <div data-el="revenue"></div>
                        <div class="text-xs text-gray-600">
                            Target: Rp<span data-el="target" class="font-medium"></span>
                        </div>
                    </div>
                </div>

                <div class="progress-bar-container">
                    <div class="progress-bar-bg">
                        <div data-el="progress-bar" class="progress-bar" style="width: 0%;"></div>
                    </div>
                    <span data-el="badge" class="badge text-xs"></span>
                </div>
            </div>
        </div>
    </template>

    <template id="customer-row-template">
        <li class="customer-list-row">
            <div class="customer-info">
                <div data-el="rank-badge"></div>
                <span data-el="customer-name" class="truncate"></span>
            </div>
            <div class="customer-stats">
                <span data-el="percentage"></span>
                <span data-el="revenue" class="tabular-nums"></span>
            </div>
        </li>
    </template>

    {{-- FIX: Change JavaScript to the new one because this one is incompatible --}}

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        let witelDataCache = [];

        // --- STATE & DOM ---
        const state = {
            year: document.getElementById('witel-year').value,
            month: document.getElementById('witel-month').value,
            source: document.getElementById('witel-source').value,
            activeWitelId: null,
        };

        const dom = {
            // Left Pane
            title: document.getElementById('header-title'),
            totalRevenue: document.getElementById('witel-total-revenue'),
            loading: document.getElementById('witel-loading'),
            error: document.getElementById('witel-error'),
            listContainer: document.getElementById('witel-list-container'),
            filters: document.querySelectorAll('#witel-filters .filter-input'),
            witelTemplate: document.getElementById('witel-row-template'),

            // Right Pane
            detailTitle: document.getElementById('witel-detail-title'),
            detailEmpty: document.getElementById('witel-detail-empty'),
            detailContent: document.getElementById('witel-detail-content'),
            customerList: document.getElementById('witel-customer-list'),
            customerTemplate: document.getElementById('customer-row-template'),
        };

        // --- HELPER FUNCTIONS (formatIDR, getBadgeStyle, getStatusDot) ---
        const formatIDRCompact = (num) => {
            if (num === null || num === undefined) return "—";
            const n = Number(num);
            const absN = Math.abs(n);
            const sign = n < 0 ? "-" : "";
            if (absN >= 1_000_000_000_000) return `${sign}${(n / 1_000_000_000_000).toFixed(1)} Triliun`;
            if (absN >= 1_000_000_000) return `${sign}${(n / 1_000_000_000).toFixed(1)} Milyar`;
            if (absN >= 1_000_000) return `${sign}${(n / 1_000_000).toFixed(1)} Juta`;
            if (absN >= 1000) return `${sign}${(n / 1000).toFixed(1)} Ribu`;
            return `${sign}${n.toFixed(0)}`;
        };

        // Determines badge style from achievement
        const getBadgeStyle = (ach) => {
            if (ach == null) return { class: 'badge-secondary', text: '—' };
            const text = `${ach.toFixed(1)}%`;
            if (ach >= 100) return { class: 'badge-success', text };
            if (ach >= 80) return { class: 'badge-warning', text };
            return { class: 'badge-danger', text };
        };

        // Determines status dot color
        const getStatusDot = (ach) => {
            if (ach == null) return 'bg-gray-400';
            if (ach >= 100) return 'bg-green-600';
            if (ach >= 80) return 'bg-amber-600';
            return 'bg-red-600';
        };

        function renderCustomerList(witelId, witelName) {
            // Find the data in our cache
            const witel = witelDataCache.find(w => w.id == witelId);
            if (!witel) return;

            const customers = witel.customers;

            // Update UI
            dom.detailTitle.textContent = `Customers — ${witelName}`;
            dom.customerList.innerHTML = ''; // Clear old list

            if (customers.length === 0) {
                dom.detailEmpty.textContent = 'Tidak ada data customer untuk Witel ini pada bulan/tahun yang dipilih.';
                dom.detailEmpty.style.display = 'block';
                dom.detailContent.style.display = 'none';
            } else {
                const total = customers.reduce((sum, row) => sum + (Number(row.total_revenue) || 0), 0);
                const rankClasses = ['rank-gold', 'rank-silver', 'rank-bronze'];

                customers.forEach((row, i) => {
                    const li = dom.customerTemplate.content.cloneNode(true);
                    const val = Number(row.total_revenue) || 0;
                    const pct = total > 0 ? (val / total) * 100 : 0;
                    const rank = i + 1;
                    const rankClass = rankClasses[i] || 'rank-default';

                    const rankBadge = li.querySelector('[data-el="rank-badge"]');
                    rankBadge.classList.add(rankClass);
                    rankBadge.textContent = rank;

                    li.querySelector('[data-el="customer-name"]').textContent = row.nama_cc || 'N/A';
                    li.querySelector('[data-el="percentage"]').textContent = `${pct.toFixed(1)}%`;
                    li.querySelector('[data-el="revenue"]').textContent = `Rp${formatIDRCompact(val)}`;

                    dom.customerList.appendChild(li);
                });

                dom.detailEmpty.style.display = 'none';
                dom.detailContent.style.display = 'block';
            }
        }

        function renderLeaderboard() {
            dom.listContainer.innerHTML = ''; // Clear old list

            let totalRevenue = 0;
            witelDataCache.forEach(witel => {
                totalRevenue += witel.revenueM;
                const card = dom.witelTemplate.content.cloneNode(true).firstElementChild;

                const ach = witel.achievement;
                const badge = getBadgeStyle(ach);
                const dot = getStatusDot(ach);
                const progress = ach == null ? 0 : Math.min(ach, 100);

                // Populate Witel Summary
                card.querySelector('[data-el="status-dot"]').classList.add(dot);
                card.querySelector('[data-el="witel-name"]').textContent = witel.name;
                card.querySelector('[data-el="badge"]').textContent = badge.text;
                card.querySelector('[data-el="badge"]').classList.add(badge.class);
                card.querySelector('[data-el="revenue"]').textContent = `Rp${formatIDRCompact(witel.revenueM)}`;
                card.querySelector('[data-el="target"]').textContent = formatIDRCompact(witel.targetM);

                const progressBar = card.querySelector('[data-el="progress-bar"]');
                progressBar.style.width = `${progress}%`;
                progressBar.classList.add(dot);

                // Add click listener data
                card.dataset.witelId = witel.id;
                card.dataset.witelName = witel.name;

                dom.listContainer.appendChild(card);
            });

            const monthName = document.querySelector(`#witel-month option[value="${state.month}"]`).textContent;
            dom.totalRevenue.innerHTML = `Total Revenue s/d ${monthName} ${state.year}: Rp<span class="font-semibold text-gray-900">${formatIDRCompact(totalRevenue)}</span>`;
        }

        // --- DATA FETCHING ---
        async function fetchPerformanceData() {
            dom.loading.style.display = 'block';
            dom.error.style.display = 'none';
            dom.listContainer.innerHTML = '';
            witelDataCache = [];

            const monthName = document.querySelector(`#witel-month option[value="${state.month}"]`).textContent;

            dom.title.innerHTML = `
                <i class="fas fa-building" id="witel-title-icon"></i>
                Witel Achievement – ${state.year}
            `;

            dom.totalRevenue.innerHTML = `Total Revenue s/d ${monthName} ${state.year}: Rp<span class="font-semibold text-gray-900">Loading...</span>`;

            try {
                const url = `/dashboard/witel-performance-data?year=${state.year}&month=${state.month}&source=${state.source}`;
                const response = await fetch(url);
                if (!response.ok) throw new Error('Failed to fetch leaderboard data.');

                witelDataCache = await response.json(); // Store data in cache

                if (witelDataCache.length === 0) {
                    dom.listContainer.innerHTML = '<p class="text-sm text-gray-500">No data found for these filters.</p>';
                } else {
                    renderLeaderboard(); // Render the left pane
                }
            } catch (err) {
                dom.error.textContent = `Error: ${err.message}`;
                dom.error.style.display = 'block';
            } finally {
                dom.loading.style.display = 'none';
            }
        }

        function clearDetailPane() {
            state.activeWitelId = null;
            dom.detailTitle.textContent = 'Customer List';
            dom.detailEmpty.textContent = 'Pilih Witel dari daftar di sebelah kiri untuk melihat detail.';
            dom.detailEmpty.style.display = 'block';
            dom.detailContent.style.display = 'none';

            // Remove 'selected' from all cards
            dom.listContainer.querySelectorAll('.witel-card.selected').forEach(c => c.classList.remove('selected'));
        }

        // --- EVENT LISTENERS ---
        dom.filters.forEach(input => {
            input.addEventListener('change', (e) => {
                state[e.target.id.replace('witel-', '')] = e.target.value;
                clearDetailPane(); // Clear selection
                fetchPerformanceData(); // Refetch all data
            });
        });

        dom.listContainer.addEventListener('click', (e) => {
            const card = e.target.closest('.witel-card');
            if (!card) return;

            const witelId = card.dataset.witelId;
            const witelName = card.dataset.witelName;

            // If already selected, do nothing
            if (witelId == state.activeWitelId) return;

            // Remove 'selected' from old card
            const oldCard = dom.listContainer.querySelector('.witel-card.selected');
            oldCard?.classList.remove('selected');

            // Add 'selected' to new card
            card.classList.add('selected');
            state.activeWitelId = witelId;

            // Fetch new data
            renderCustomerList(witelId, witelName);
        });

        // --- INITIAL LOAD ---
        fetchPerformanceData();
    });
    </script>
    @endpush
</body>
</html>
