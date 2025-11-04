<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Trend</title>

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="{{ asset('css/ccwitel.css') }}">
</head>
<body class="bg-gray-100 p-8">
    <div class="trend-revenue-card">
        <div class="trend-card-header">
            <div class="header-main-content">
                <div class="header-title-section">
                    <div class="header-icon-wrapper">
                        <i class="fas fa-chart-bar text-white text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="header-title">
                            Revenue Trend
                            <i class="fas fa-chart-line text-green-600"></i>
                        </h2>
                        <p id="header-subtitle" class="header-subtitle">
                            Revenue performance
                        </p>
                    </div>
                </div>
                <div class="header-date-range">
                    <i class="fas fa-calendar-alt text-gray-500"></i>
                    <span id="header-date-range"></span>
                </div>
            </div>

            <div class="filters-form">
                {{-- Time Range Selector --}}
                <div>
                    <label for="time_range">Range:</label>
                    <select id="time_range">
                        <option value="ytd">YTD</option>
                        <option value="n_year">N Year</option>
                    </select>
                </div>

                {{-- N-Year Stepper --}}
                <div id="n-year-controls" class="n-year-stepper" style="display: none;">
                    <button type="button" id="n-year-decrement">-</button>
                    <span id="n-year-label">1 Year</span>
                    <button type="button" id="n-year-increment">+</button>
                </div>

                {{-- Start Month Selector --}}
                <div id="start-month-controls" style="display: none;">
                    <label for="start_month">Start Month:</label>
                    <select id="start_month">
                        @foreach ($months as $date)
                            <option value="{{ $date->month }}">{{ $date->format('F') }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Division Filter --}}
                <div>
                    <label for="division">Division:</label>
                    <select id="division">
                        <option value="All">All Division</option>
                        <option value="DPS">DPS</option>
                        <option value="DSS">DSS</option>
                        <option value="DGS">DGS</option>
                    </select>
                </div>

                {{-- Source Filter --}}
                <div>
                    <label for="source">Source:</label>
                    <select id="source">
                        <option value="reguler">REGULER</option>
                        <option value="ngtma">NGTMA</option>
                    </select>
                </div>

                {{-- View Filter --}}
                <div>
                    <label for="view_mode">View:</label>
                    <select id="view_mode">
                        <option value="revenue_and_achievement">Revenue & Achievement</option>
                        <option value="revenue_only">Revenue Only</option>
                        <option value="achievement_only">Achievement Only</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="trend-card-content">
            <div id="chart-error" class="error-message" style="display: none;"></div>
            <div class="chart-container">
                <div class="loading-overlay">
                    <div class="spinner"></div>
                </div>
                {{-- NOTE: `style="height: 400px"` should be temporary apparently, but if not put here, everything is messed up :(--}}
                <canvas id="revenueChart" style="height: 400px"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- STATE MANAGEMENT & CONSTANTS ---
            const state = {
                timeRange: 'ytd',
                nYears: 1,
                startMonth: new Date().getMonth() + 1,
                division: 'All',
                source: 'reguler',
                viewMode: 'revenue_and_achievement',
                isLoading: true,
            };
            const dataCache = {}; // Cache to store fetched revenue data by YYYY-MM
            const MIN_YEAR = 2020;
            let revenueChart = null; // To hold the Chart.js instance

            const dom = {
                timeRange: document.getElementById('time_range'),
                nYearControls: document.getElementById('n-year-controls'),
                nYearLabel: document.getElementById('n-year-label'),
                nYearDecrement: document.getElementById('n-year-decrement'),
                nYearIncrement: document.getElementById('n-year-increment'),
                startMonthControls: document.getElementById('start-month-controls'),
                startMonth: document.getElementById('start_month'),
                division: document.getElementById('division'),
                source: document.getElementById('source'),
                viewMode: document.getElementById('view_mode'),
                chartError: document.getElementById('chart-error'),
                loadingOverlay: document.querySelector('.loading-overlay'),
                chartCanvas: document.getElementById('revenueChart'),
                headerSubtitle: document.getElementById('header-subtitle'),
                headerDateRange: document.getElementById('header-date-range'),
                chartCanvas: document.getElementById('revenueChart'),
            };

            // --- DATA FETCHING & CACHING ---
            async function fetchData(startDate, endDate) {
                const source = state.source;
                const apiUrl = `/dashboard/trend-data?source=${source}&start_date=${startDate.toISOString().split('T')[0]}&end_date=${endDate.toISOString().split('T')[0]}`;

                try {
                    const response = await fetch(apiUrl);
                    if (!response.ok) {
                        throw new Error(`Network response was not ok (${response.status})`);
                    }
                    const data = await response.json();

                    // Process and store fetched data in the cache
                    data.forEach(row => {
                        const key = `${row.tahun}-${row.bulan}`;
                        if (!dataCache[key]) {
                            dataCache[key] = {
                                dgs: { real: 0, target: 0 },
                                dss: { real: 0, target: 0 },
                                dps: { real: 0, target: 0 }
                            };
                        }
                        const DIVISOR = 1_000_000_000;
                        const real_revenue = (row.real_revenue || 0) / DIVISOR;
                        const target_revenue = (row.target_revenue || 0) / DIVISOR;

                        switch (row.divisi_id) {
                            case 1: dataCache[key].dgs.real += real_revenue; dataCache[key].dgs.target += target_revenue; break;
                            case 2: dataCache[key].dss.real += real_revenue; dataCache[key].dss.target += target_revenue; break;
                            case 3: dataCache[key].dps.real += real_revenue; dataCache[key].dps.target += target_revenue; break;
                        }
                    });

                    return true;

                } catch (error) {
                    console.error("Fetch Error:", error);
                    dom.chartError.textContent = `Error: Failed to fetch revenue data. ${error.message}`;
                    dom.chartError.style.display = 'block';
                    return false;
                }
            }

            // --- CHART RENDERING ---
            function renderChart(startDate, endDate) {
                const labels = [];
                const realData = [], targetData = [], achievementData = [];

                let current = new Date(startDate);
                while (current <= endDate) {
                    labels.push(current.toLocaleDateString('en-US', { month: 'short', year: '2-digit' }));

                    const key = `${current.getFullYear()}-${current.getMonth() + 1}`;
                    const monthData = dataCache[key] || {
                        dgs: { real: 0, target: 0 },
                        dss: { real: 0, target: 0 },
                        dps: { real: 0, target: 0 }
                    };

                    let totalReal = 0;
                    let totalTarget = 0;

                    // Aggregate data based on Division filter
                    if (state.division === 'All' || state.division === 'DPS') {
                        totalReal += monthData.dps.real;
                        totalTarget += monthData.dps.target;
                    }
                    if (state.division === 'All' || state.division === 'DSS') {
                        totalReal += monthData.dss.real;
                        totalTarget += monthData.dss.target;
                    }
                    if (state.division === 'All' || state.division === 'DGS') {
                        totalReal += monthData.dgs.real;
                        totalTarget += monthData.dgs.target;
                    }

                    realData.push(totalReal.toFixed(2));
                    targetData.push(totalTarget.toFixed(2));

                    const achievement = (totalTarget === 0) ? 0 : (totalReal / totalTarget) * 100;
                    achievementData.push(achievement.toFixed(2));

                    current.setMonth(current.getMonth() + 1);
                }

                // Define the datasets based on the viewMode filter
                const datasets = [];
                const view = state.viewMode;

                if (view === 'revenue_and_achievement' || view === 'revenue_only') {
                    datasets.push({
                        type: 'bar',
                        label: 'Real Revenue',
                        data: realData,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)', // Blue
                        yAxisID: 'yRevenue', // Assign to left axis
                        order: 2
                    });
                    datasets.push({
                        type: 'bar',
                        label: 'Target Revenue',
                        data: targetData,
                        backgroundColor: 'rgba(201, 203, 207, 0.7)', // Grey
                        yAxisID: 'yRevenue', // Assign to left axis
                        order: 2
                    });
                }

                if (view === 'revenue_and_achievement' || view === 'achievement_only') {
                    datasets.push({
                        type: 'line',
                        label: 'Achievement (%)',
                        data: achievementData,
                        borderColor: 'rgba(255, 99, 132, 1)', // Red
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.4,
                        yAxisID: 'yAchievement', // Assign to right axis
                        order: 1
                    });
                }

                const chartOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        // Left Y-Axis (Revenue)
                        yRevenue: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: { display: true, text: 'Revenue (in Billions)' },
                        },
                        // Right Y-Axis (Achievement %)
                        yAchievement: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            title: { display: true, text: 'Achievement (%)' },
                            grid: { drawOnChartArea: false },
                            ticks: {
                                callback: value => `${value}%`
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        },
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    }
                };

                // Conditionally show/hide axes based on viewMode
                if (state.viewMode === 'revenue_only') {
                    chartOptions.scales.yAchievement.display = false;
                    chartOptions.scales.yRevenue.display = true;
                } else if (state.viewMode === 'achievement_only') {
                    chartOptions.scales.yAchievement.display = true;
                    chartOptions.scales.yRevenue.display = false;
                } else { // 'revenue_and_achievement'
                    chartOptions.scales.yAchievement.display = true;
                    chartOptions.scales.yRevenue.display = true;
                }

                // Create or update the chart
                if (revenueChart) {
                    revenueChart.data.labels = labels;
                    revenueChart.data.datasets = datasets;
                    revenueChart.options.scales = chartOptions.scales; // IMPORTANT: Update scales
                    revenueChart.update();
                } else {
                    revenueChart = new Chart(dom.chartCanvas, {
                        // Default type is bar, but datasets will override
                        type: 'bar',
                        data: { labels, datasets },
                        options: chartOptions
                    });
                }
            }

            function updateHeader(startDate, endDate) {
                // Update the subtitle based on the division and time range
                const divisionText = state.division === 'All' ? 'all divisions' : state.division;
                const timeFrameText = state.timeRange === 'ytd' ? `(YTD ${new Date().getFullYear()})` : `(Custom Range)`;
                dom.headerSubtitle.textContent = `Revenue performance across ${divisionText} ${timeFrameText}`;

                // Update the date range display
                const endText = endDate.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                let startText;
                if (startDate.getFullYear() === endDate.getFullYear()) {
                    // If same year, don't repeat the year (e.g., "Jan – Oct 2025")
                    startText = startDate.toLocaleDateString('en-US', { month: 'short' });
                } else {
                    // If different years, show both (e.g., "Oct 2023 – Oct 2025")
                    startText = startDate.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }
                dom.headerDateRange.textContent = `${startText} – ${endText}`;
            }

            // --- MAIN ORCHESTRATOR FUNCTION ---
            async function updateDashboard() {
                state.isLoading = true;
                dom.loadingOverlay.classList.remove('hidden');
                dom.chartError.style.display = 'none';

                const now = new Date();
                let startDate, endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);

                if (state.timeRange === 'ytd') {
                    startDate = new Date(now.getFullYear(), 0, 1);
                } else {
                    const startYear = now.getFullYear() - state.nYears;
                    startDate = new Date(startYear, state.startMonth - 1, 1);
                }

                // Determine which data is missing from cache
                let firstMissingDate = null;
                let current = new Date(startDate);
                while (current <= endDate) {
                    const key = `${current.getFullYear()}-${current.getMonth() + 1}`;
                    if (!dataCache[key]) {
                        firstMissingDate = new Date(current);
                        break;
                    }
                    current.setMonth(current.getMonth() + 1);
                }

                // Fetch missing data if necessary
                if (firstMissingDate) {
                    const success = await fetchData(firstMissingDate, endDate);
                    if (!success) {
                        state.isLoading = false;
                        dom.loadingOverlay.classList.add('hidden');
                        return;
                    }
                }

                // Render the chart with data from cache
                renderChart(startDate, endDate);
                updateHeader(startDate, endDate);
                state.isLoading = false;
                dom.loadingOverlay.classList.add('hidden');
            }

            // --- EVENT LISTENERS ---
            function setupEventListeners() {
                dom.timeRange.addEventListener('change', (e) => {
                    state.timeRange = e.target.value;
                    updateUiVisibility();
                    updateDashboard();
                });
                dom.division.addEventListener('change', (e) => {
                    state.division = e.target.value;
                    updateDashboard();
                });
                dom.source.addEventListener('change', (e) => {
                    state.source = e.target.value;
                    Object.keys(dataCache).forEach(key => delete dataCache[key]); // Clear cache on source change
                    updateDashboard();
                });
                dom.startMonth.addEventListener('change', (e) => {
                    state.startMonth = parseInt(e.target.value, 10);
                    updateDashboard();
                });
                dom.nYearDecrement.addEventListener('click', () => {
                    if (state.nYears > 1) {
                        state.nYears--;
                        updateNYearsLabel();
                        updateDashboard();
                    }
                });
                dom.nYearIncrement.addEventListener('click', () => {
                    const maxNYears = new Date().getFullYear() - MIN_YEAR;
                    if (state.nYears < maxNYears) {
                        state.nYears++;
                        updateNYearsLabel();
                        updateDashboard();
                    }
                });

                dom.viewMode.addEventListener('change', (e) => {
                    state.viewMode = e.target.value;
                    // Call updateDashboard() which will use cached data.
                    updateDashboard();
                });
            }

            // --- UI HELPER FUNCTIONS ---
            function updateUiVisibility() {
                const showNYear = state.timeRange === 'n_year';
                dom.nYearControls.style.display = showNYear ? 'flex' : 'none';
                dom.startMonthControls.style.display = showNYear ? 'flex' : 'none';
            }
            function updateNYearsLabel() {
                dom.nYearLabel.textContent = `${state.nYears} Year${state.nYears > 1 ? 's' : ''}`;
            }

            // --- INITIALIZATION ---
            dom.startMonth.value = state.startMonth;
            setupEventListeners();
            updateDashboard(); // Initial data load (YTD)
        });
    </script>
    @endpush
</body>
</html>
