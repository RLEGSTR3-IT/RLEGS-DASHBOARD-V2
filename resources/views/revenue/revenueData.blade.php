@extends('layouts.main')

@section('title', 'Revenue RLEGS')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/revenue.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
/* ============================================================
   🎨 REVENUE DATA - COMPLETE CSS (MOBILE OPTIMIZED - NO FAB)
   ============================================================ */

/* ===== BASE RESET ===== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background: #f8f9fa;
    overflow-x: hidden;
}

/* ===== MAIN CONTAINER ===== */
.rlegs-container {
    padding: 1rem;
    max-width: 100%;
    overflow-x: hidden;
}

@media (min-width: 768px) {
    .rlegs-container {
        padding: 1.5rem;
    }
}

@media (min-width: 1024px) {
    .rlegs-container {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }
}

/* ===== CARD SHADOW ===== */
.card-shadow {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* ===== PAGE HEADER ===== */
.page-header {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.page-header .page-title h1 {
    font-size: 1.5rem;
    margin: 0 0 0.5rem 0;
    font-weight: 700;
    color: #2c3e50;
}

.page-header .page-title p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.page-header .page-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

@media (min-width: 768px) {
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 2rem;
    }

    .page-header .page-actions {
        margin-top: 0;
        flex-wrap: nowrap;
    }
}

/* ===== FILTERS (ALWAYS VISIBLE, COMPACT ON MOBILE) ===== */
.filters {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: flex-end;
}

@media (min-width: 1024px) {
    .filters {
        padding: 1.5rem;
        gap: 1rem;
    }
}

/* Searchbar */
.searchbar {
    flex: 1;
    min-width: 200px;
    display: flex;
    gap: 0.5rem;
}

@media (max-width: 767px) {
    .searchbar {
        width: 100%;
        min-width: 100%;
    }
}

.search-input {
    flex: 1;
    padding: 0.625rem 0.875rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: border-color 0.3s;
}

.search-input:focus {
    outline: none;
    border-color: #dc3545;
}

.search-btn {
    padding: 0.625rem 1.25rem;
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 0.875rem;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

/* Filter Groups */
.filter-group {
    flex: 1;
    min-width: 150px;
}

@media (max-width: 767px) {
    .filter-group {
        width: 100%;
        min-width: 100%;
    }
}

.filter-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.4rem;
    color: #2c3e50;
    font-size: 0.8125rem;
}

.filter-group .form-select,
.filter-group .form-control {
    width: 100%;
    padding: 0.625rem 0.75rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: border-color 0.3s;
}

.filter-group .form-select:focus,
.filter-group .form-control:focus {
    outline: none;
    border-color: #dc3545;
}

/* Filter Actions */
.filter-actions {
    display: flex;
    gap: 0.5rem;
    width: 100%;
    margin-top: 0.5rem;
}

@media (min-width: 1024px) {
    .filter-actions {
        width: auto;
        margin-top: 0;
    }
}

.filter-actions button {
    flex: 1;
    padding: 0.625rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    font-size: 0.8125rem;
}

@media (min-width: 1024px) {
    .filter-actions button {
        flex: 0;
        white-space: nowrap;
        padding: 0.75rem 1.25rem;
    }
}

/* ===== TABS ===== */
.tabs {
    background: white;
    border-radius: 12px;
    padding: 0.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    flex-wrap: nowrap;
    scrollbar-width: thin;
}

.tabs::-webkit-scrollbar {
    height: 4px;
}

.tabs::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.tabs::-webkit-scrollbar-thumb {
    background: #dc3545;
    border-radius: 10px;
}

.tab-btn {
    flex-shrink: 0;
    white-space: nowrap;
    padding: 0.75rem 1rem;
    background: transparent;
    border: none;
    color: #6c757d;
    font-weight: 600;
    font-size: 0.8125rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@media (min-width: 768px) {
    .tab-btn {
        padding: 0.875rem 1.25rem;
        font-size: 0.9rem;
    }
}

.tab-btn:hover {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.tab-btn.active {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

.tab-btn .badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    background: rgba(255, 255, 255, 0.2);
}

.tab-btn.active .badge {
    background: rgba(255, 255, 255, 0.3);
}

.badge.neutral {
    background: #6c757d;
    color: white;
}

/* ===== TAB PANELS ===== */
.tab-panel {
    display: none;
    background: white;
    border-radius: 12px;
    padding: 1rem;
}

@media (min-width: 768px) {
    .tab-panel {
        padding: 1.5rem;
    }
}

.tab-panel.active {
    display: block;
}

/* ===== PANEL HEADER ===== */
.panel-header {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f3f5;
}

@media (min-width: 768px) {
    .panel-header {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
}

.panel-header .left h3 {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
    color: #2c3e50;
}

@media (min-width: 768px) {
    .panel-header .left h3 {
        font-size: 1.25rem;
    }
}

.panel-header .left p {
    margin: 0;
    color: #6c757d;
    font-size: 0.8125rem;
}

.panel-header .right {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

@media (min-width: 768px) {
    .panel-header .right {
        flex-direction: row;
        align-items: center;
        gap: 0.75rem;
    }
}

/* Mobile: Stack buttons vertically */
@media (max-width: 767px) {
    .panel-header .right > * {
        width: 100%;
    }

    .panel-header .right .dropdown {
        width: 100%;
    }

    .panel-header .right .dropdown .btn {
        width: 100%;
        justify-content: space-between;
    }
}

/* ===== BUTTONS ===== */
.btn {
    padding: 0.625rem 1.25rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-outline-danger {
    background: white;
    color: #dc3545;
    border: 2px solid #dc3545;
}

.btn-outline-danger:hover {
    background: #dc3545;
    color: white;
}

.btn-outline-primary {
    background: white;
    color: #dc3545;
    border: 2px solid #dc3545;
}

.btn-outline-primary:hover {
    background: #dc3545;
    color: white;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 767px) {
    .btn-danger.btn-sm,
    .btn-outline-danger.btn-sm {
        width: 100%;
    }
}

/* ===== DROPDOWN ===== */
.dropdown {
    position: relative;
}

.dropdown-toggle {
    min-width: 140px;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    min-width: 10rem;
    padding: 0.5rem 0;
    margin: 0.125rem 0 0;
    background: white;
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.dropdown-item {
    display: block;
    width: 100%;
    padding: 0.5rem 1rem;
    clear: both;
    font-weight: 400;
    color: #212529;
    text-align: inherit;
    text-decoration: none;
    white-space: nowrap;
    background-color: transparent;
    border: 0;
    cursor: pointer;
    transition: background-color 0.2s;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-item.active {
    background-color: #dc3545;
    color: white;
}

@media (max-width: 767px) {
    .dropdown-toggle {
        width: 100%;
    }
}

/* ===== TABLES ===== */
.table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 767px) {
    .table-wrap {
        margin: 0 -1rem;
        padding: 0 1rem;
    }
}

.table {
    width: 100%;
    min-width: 800px;
    border-collapse: collapse;
}

.table.modern thead {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.table.modern thead th {
    padding: 1rem;
    color: white;
    font-weight: 600;
    text-align: left;
    white-space: nowrap;
    border: none;
}

.table.modern tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: background 0.2s;
}

.table.modern tbody tr:hover {
    background: #f8f9fa;
}

.table.modern tbody td {
    padding: 1rem;
    vertical-align: middle;
}

.table.modern tbody td:first-child,
.table.modern thead th:first-child {
    width: 48px;
    min-width: 48px;
    text-align: center;
}

.table.modern tbody td:last-child,
.table.modern thead th:last-child {
    width: 150px;
    min-width: 150px;
    text-align: center;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    align-items: center;
}

/* ===== PAGINATION ===== */
.pagination-bar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

@media (min-width: 768px) {
    .pagination-bar {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
}

.pagination-bar .info {
    color: #6c757d;
    font-size: 0.875rem;
}

.pagination-bar .pages {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.pager {
    padding: 0.5rem 0.75rem;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.pager:hover {
    background: #f8f9fa;
    border-color: #dc3545;
}

.pager.active {
    background: #dc3545;
    color: white;
    border-color: #dc3545;
}

.pager:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-bar .perpage {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pagination-bar .perpage label {
    font-size: 0.875rem;
    color: #6c757d;
}

.pagination-bar .perpage .form-select {
    padding: 0.375rem 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 0.875rem;
}

/* ===== BADGES ===== */
.badge-div {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

.badge-div.dps {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.badge-div.dss {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.badge-div.dgs {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.badge-status-registered {
    background: #28a745;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-status-not-registered {
    background: #6c757d;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-role-am {
    background: #007bff;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-role-hotda {
    background: #ffc107;
    color: #000;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* ===== MODALS ===== */
.modal {
    z-index: 1060 !important;
}

.modal-backdrop {
    z-index: 1050 !important;
    opacity: 0.5 !important;
}

.modal-dialog {
    margin: 1.75rem auto;
}

@media (max-width: 767px) {
    .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }

    .modal-xl {
        max-width: calc(100% - 1rem);
    }
}

.modal-header {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    padding: 1.25rem 1.5rem;
    border-bottom: none;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
}

.modal-title {
    font-weight: 700;
    font-size: 1.25rem;
    color: white;
}

.modal-body {
    padding: 1.5rem;
}

@media (max-width: 767px) {
    .modal-body {
        padding: 1rem;
    }
}

.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
}

/* Import Modal Type Selector */
.type-selector {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.type-btn {
    padding: 1rem;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
}

.type-btn:hover {
    border-color: #dc3545;
    background: #fff5f5;
}

.type-btn.active {
    border-color: #dc3545;
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.type-btn i {
    font-size: 1.5rem;
}

.imp-panel {
    display: none;
}

.imp-panel.active {
    display: block;
}

/* ===== LOADING OVERLAY ===== */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-overlay.active {
    display: flex;
}

.loading-spinner {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.loading-spinner .spinner-border {
    width: 3rem;
    height: 3rem;
    border-width: 0.25rem;
}

/* ===== UTILITIES ===== */
.text-muted {
    color: #6c757d !important;
}

.text-end {
    text-align: right !important;
}

.text-center {
    text-align: center !important;
}

.muted {
    color: #6c757d;
}

.me-1 {
    margin-right: 0.25rem !important;
}

.me-2 {
    margin-right: 0.5rem !important;
}

.ms-1 {
    margin-left: 0.25rem !important;
}

.mb-0 {
    margin-bottom: 0 !important;
}

.mb-1 {
    margin-bottom: 0.25rem !important;
}

.mb-2 {
    margin-bottom: 0.5rem !important;
}

.mb-3 {
    margin-bottom: 1rem !important;
}

.mt-2 {
    margin-top: 0.5rem !important;
}

.w-100 {
    width: 100% !important;
}

.d-flex {
    display: flex !important;
}

.d-block {
    display: block !important;
}

.gap-2 {
    gap: 0.5rem !important;
}

.flex-grow-1 {
    flex-grow: 1 !important;
}

.align-items-start {
    align-items: flex-start !important;
}

.fw-semibold {
    font-weight: 600 !important;
}

.fw-bold {
    font-weight: 700 !important;
}

.small {
    font-size: 0.875rem !important;
}
    </style>
@endsection

@section('content')
<div class="rlegs-container">

    {{-- ===== PAGE HEADER ===== --}}
    <div class="page-header card-shadow">
        <div class="page-title">
            <h1>Data Revenue RLEGS</h1>
            <p>Kelola data Corporate Customer dan Account Manager RLEGS.</p>
        </div>

        <div class="page-actions">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fa-solid fa-file-import me-2"></i>Import
            </button>
            <div class="export-group">
                <a href="/export/excel" class="btn btn-primary">
                    <i class="fa-solid fa-file-export me-2"></i> Export
                </a>
            </div>
        </div>
    </div>

    {{-- ===== FILTERS (ALWAYS VISIBLE, COMPACT) ===== --}}
    <div class="filters card-shadow">
        <form class="searchbar" action="#" method="GET" id="searchForm" onsubmit="return false;">
            <input type="search" class="search-input" id="searchInput" placeholder="Cari data...">
            <button class="search-btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
        
        <div class="filter-group">
            <label>Witel</label>
            <select class="form-select" id="filterWitel">
                <option value="all">Semua Witel</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Divisi</label>
            <select class="form-select" id="filterDivisi">
                <option value="all">Semua Divisi</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Segment</label>
            <select class="form-select" id="filter-segment" name="segment">
                <option value="all">Semua Segment</option>
            </select>

            <div class="seg-select" id="segSelect">
                <button type="button" class="seg-select__btn" aria-haspopup="listbox">
                    <span class="seg-select__label">Semua Segment</span>
                    <span class="seg-select__caret"></span>
                </button>
                <div class="seg-menu" id="segMenu" role="listbox">
                    <div class="seg-tabs" id="segTabs" role="tablist"></div>
                    <div class="seg-panels" id="segPanels"></div>
                </div>
            </div>
        </div>

        <div class="filter-group" id="filterPeriodeGroup">
            <label>Periode</label>
            <input type="text" id="filter-date" class="form-control datepicker-control" placeholder="Pilih bulan & tahun" autocomplete="off" readonly>
            <input type="hidden" id="filter-month" name="month" value="{{ date('m') }}">
            <input type="hidden" id="filter-year"  name="year"  value="{{ date('Y') }}">
        </div>

        <div class="filter-actions">
            <button class="btn btn-primary" id="btn-apply-filter">
                <i class="fa-solid fa-check me-1"></i>Terapkan
            </button>
            <button class="btn btn-secondary" id="btn-reset-filter">
                <i class="fa-solid fa-rotate-left me-1"></i>Reset
            </button>
        </div>
    </div>

    {{-- ===== TABS ===== --}}
    <div class="tabs card-shadow">
        <button class="tab-btn active" data-tab="tab-cc-revenue">
            <i class="fa-solid fa-chart-line me-2"></i>Revenue CC
            <span class="badge neutral" id="badge-cc-rev">0</span>
        </button>
        <button class="tab-btn" data-tab="tab-am-revenue">
            <i class="fa-solid fa-user-tie me-2"></i>Revenue AM
            <span class="badge neutral" id="badge-am-rev">0</span>
        </button>
        <button class="tab-btn" data-tab="tab-data-am">
            <i class="fa-solid fa-users me-2"></i>Data AM
            <span class="badge neutral" id="badge-data-am">0</span>
        </button>
        <button class="tab-btn" data-tab="tab-data-cc">
            <i class="fa-solid fa-building me-2"></i>Data CC
            <span class="badge neutral" id="badge-cc">0</span>
        </button>
    </div>

    {{-- ===== TAB: REVENUE CC ===== --}}
    <div id="tab-cc-revenue" class="tab-panel card-shadow active">
        <div class="panel-header">
            <div class="left">
                <h3>Revenue Corporate Customer</h3>
                <p class="muted">Gunakan dropdown untuk melihat kategori Revenue CC</p>
            </div>
            <div class="right">
                {{-- Dropdown Revenue Type (COMPACT) --}}
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="dropdownRevType" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-filter me-1"></i>
                        <span id="currentRevType">Reguler</span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownRevType">
                        <li><a class="dropdown-item active" href="#" data-revtype="REGULER">Reguler</a></li>
                        <li><a class="dropdown-item" href="#" data-revtype="NGTMA">NGTMA</a></li>
                        <li><a class="dropdown-item" href="#" data-revtype="KOMBINASI">Kombinasi</a></li>
                    </ul>
                </div>

                <button class="btn btn-danger btn-sm" id="btnDeleteSelectedCC" disabled>
                    <i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih
                </button>
                <button class="btn btn-outline-danger btn-sm" id="btnBulkDeleteCC">
                    <i class="fa-solid fa-trash-alt me-2"></i>Hapus Semua
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table modern">
                <thead>
                    <tr>
                        <th style="width: 48px; min-width: 48px; text-align: center;"><input type="checkbox" id="selectAllCC"></th>
                        <th>Nama CC</th>
                        <th>Segment</th>
                        <th class="text-end">Target Revenue</th>
                        <th class="text-end">Revenue</th>
                        <th>Bulan</th>
                        <th class="text-center" style="width: 150px; min-width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableRevenueCC">
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar" id="paginationRevenueCC">
            <div class="info">Menampilkan 0 dari 0 hasil</div>
            <div class="pages"></div>
            <div class="perpage">
                <label>Baris</label>
                <select class="form-select small">
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="75">75</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    {{-- ===== TAB: REVENUE AM ===== --}}
    <div id="tab-am-revenue" class="tab-panel card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Revenue Account Manager</h3>
                <p class="muted">Gunakan dropdown untuk filter AM/HOTDA</p>
            </div>
            <div class="right">
                {{-- Dropdown AM Mode (COMPACT) --}}
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="dropdownAMMode" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-user-tie me-1"></i>
                        <span id="currentAMMode">Semua</span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownAMMode">
                        <li><a class="dropdown-item active" href="#" data-mode="all">Semua</a></li>
                        <li><a class="dropdown-item" href="#" data-mode="AM">AM</a></li>
                        <li><a class="dropdown-item" href="#" data-mode="HOTDA">HOTDA</a></li>
                    </ul>
                </div>

                <button class="btn btn-danger btn-sm" id="btnDeleteSelectedAM" disabled>
                    <i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih
                </button>
                <button class="btn btn-outline-danger btn-sm" id="btnBulkDeleteAM">
                    <i class="fa-solid fa-trash-alt me-2"></i>Hapus Semua
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table modern" id="table-am">
                <thead>
                    <tr>
                        <th style="width: 48px; min-width: 48px; text-align: center;"><input type="checkbox" id="selectAllAM"></th>
                        <th>Nama AM</th>
                        <th>Corporate Customer</th>
                        <th class="text-end">Target Revenue</th>
                        <th class="text-end">Revenue</th>
                        <th class="text-end">Achievement</th>
                        <th>Bulan</th>
                        <th>TELDA</th>
                        <th class="text-center" style="width: 150px; min-width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableRevenueAM">
                    <tr>
                        <td colspan="9" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar" id="paginationRevenueAM">
            <div class="info">Menampilkan 0 dari 0 hasil</div>
            <div class="pages"></div>
            <div class="perpage">
                <label>Baris</label>
                <select class="form-select small">
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="75">75</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    {{-- ===== TAB: DATA AM ===== --}}
    <div id="tab-data-am" class="tab-panel card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Data Account Manager</h3>
                <p class="muted">Daftar Account Manager yang terdaftar di sistem</p>
            </div>
            <div class="right">
                <button class="btn btn-danger btn-sm" id="btnDeleteSelectedDataAM" disabled>
                    <i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih
                </button>
                <button class="btn btn-outline-danger btn-sm" id="btnBulkDeleteDataAM">
                    <i class="fa-solid fa-trash-alt me-2"></i>Hapus Semua
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table modern">
                <thead>
                    <tr>
                        <th style="width: 48px; min-width: 48px; text-align: center;"><input type="checkbox" id="selectAllDataAM"></th>
                        <th>Nama AM</th>
                        <th>Witel</th>
                        <th>Role</th>
                        <th>TELDA</th>
                        <th>Status Registrasi</th>
                        <th class="text-center" style="width: 150px; min-width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableDataAM">
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar" id="paginationDataAM">
            <div class="info">Menampilkan 0 dari 0 hasil</div>
            <div class="pages"></div>
            <div class="perpage">
                <label>Baris</label>
                <select class="form-select small">
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="75">75</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    {{-- ===== TAB: DATA CC ===== --}}
    <div id="tab-data-cc" class="tab-panel card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Data Corporate Customer</h3>
                <p class="muted">Detail Corporate Customer</p>
            </div>
            <div class="right">
                <button class="btn btn-danger btn-sm" id="btnDeleteSelectedDataCC" disabled>
                    <i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih
                </button>
                <button class="btn btn-outline-danger btn-sm" id="btnBulkDeleteDataCC">
                    <i class="fa-solid fa-trash-alt me-2"></i>Hapus Semua
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table modern">
                <thead>
                    <tr>
                        <th style="width: 48px; min-width: 48px; text-align: center;"><input type="checkbox" id="selectAllDataCC"></th>
                        <th>Nama CC</th>
                        <th>NIPNAS</th>
                        <th class="text-center" style="width: 150px; min-width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableDataCC">
                    <tr>
                        <td colspan="4" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar" id="paginationDataCC">
            <div class="info">Menampilkan 0 dari 0 hasil</div>
            <div class="pages"></div>
            <div class="perpage">
                <label>Baris</label>
                <select class="form-select small">
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="75">75</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

</div>

{{-- ========================================
     IMPORT MODAL
     ======================================== --}}
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
            <i class="fa-solid fa-file-import"></i>
            Import Data Revenue
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="type-selector">
          <button class="type-btn active" data-imp="imp-data-cc">
              <i class="fa-solid fa-building"></i>
              Data CC
          </button>
          <button class="type-btn" data-imp="imp-data-am">
              <i class="fa-solid fa-users"></i>
              Data AM
          </button>
          <button class="type-btn" data-imp="imp-rev-cc">
              <i class="fa-solid fa-chart-line"></i>
              Revenue CC
          </button>
          <button class="type-btn" data-imp="imp-rev-map">
              <i class="fa-solid fa-user-tie"></i>
              Revenue AM
          </button>
        </div>

        {{-- Form Data CC --}}
        <div id="imp-data-cc" class="imp-panel active">
            <div class="alert alert-light border mb-3 py-2 px-3" style="font-size: 0.875rem;">
                <div class="d-flex gap-2 align-items-start">
                    <i class="fa-solid fa-info-circle text-primary"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-1">Format CSV Data CC</strong>
                        <div class="text-muted" style="font-size: 0.85rem;">
                            <strong>Kolom:</strong> NIPNAS, STANDARD_NAME | 
                            <strong>Catatan:</strong> NIPNAS duplikat = update data lama
                        </div>
                    </div>
                </div>
            </div>

            <form id="formDataCC" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="data_cc">

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fa-solid fa-file-csv me-1"></i>
                        Upload File CSV <span class="text-danger">*</span>
                    </label>
                    <input type="file" class="form-control" name="file" accept=".csv" required>
                    <small class="form-text text-muted">
                        <a href="{{ route('revenue.template', ['type' => 'data-cc']) }}" class="text-decoration-none">
                            <i class="fa-solid fa-download me-1"></i>Download Template
                        </a>
                    </small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload me-2"></i>Import Data CC
                </button>
            </form>
        </div>

        {{-- Form Data AM --}}
        <div id="imp-data-am" class="imp-panel">
            <div class="alert alert-light border mb-3 py-2 px-3" style="font-size: 0.875rem;">
                <div class="d-flex gap-2 align-items-start">
                    <i class="fa-solid fa-info-circle text-primary"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-1">Format CSV Data AM</strong>
                        <div class="text-muted" style="font-size: 0.85rem; line-height: 1.5;">
                            <strong>Kolom:</strong> NIK, NAMA AM, PROPORSI, WITEL AM, NIPNAS, STANDARD NAME, GROUP CONGLO, DIVISI AM, SEGMEN, WITEL HO, REGIONAL, DIVISI, TELDA<br>
                            <strong>Aturan:</strong> HOTDA wajib isi TELDA | AM kosongkan TELDA | Total proporsi per NIPNAS = 1
                        </div>
                    </div>
                </div>
            </div>

            <form id="formDataAM" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="data_am">

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fa-solid fa-file-csv me-1"></i>
                        Upload File CSV <span class="text-danger">*</span>
                    </label>
                    <input type="file" class="form-control" name="file" accept=".csv" required>
                    <small class="form-text text-muted">
                        <a href="{{ route('revenue.template', ['type' => 'data-am']) }}" class="text-decoration-none">
                            <i class="fa-solid fa-download me-1"></i>Download Template
                        </a>
                    </small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload me-2"></i>Import Data AM
                </button>
            </form>
        </div>

        {{-- Form Revenue CC --}}
        <div id="imp-rev-cc" class="imp-panel">
          <div class="alert alert-light border mb-3 py-2 px-3" style="font-size: 0.8rem;">
                <div class="d-flex gap-2">
                    <i class="fa-solid fa-info-circle text-primary"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-1">Format CSV</strong>
                          <div class="text-muted" style="font-size: 0.75rem; line-height: 1.6;">
                              <strong class="text-success">Real:</strong> DGS/DSS → NIPNAS, STANDARD_NAME, LSEGMENT_HO, WITEL_HO, REVENUE_SOLD | DPS → tambah WITEL_BILL, REVENUE_BILL<br>
                              <strong class="text-warning">Target:</strong> DGS/DSS → NIPNAS, STANDARD_NAME, LSEGMENT_HO, WITEL_HO, TARGET_REVENUE | DPS → tambah WITEL_BILL
                          </div>
                    </div>
                </div>
            </div>

            <form id="formRevenueCC" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="revenue_cc">

                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="fa-solid fa-calendar me-1"></i>Periode <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="import-cc-periode" class="form-control form-control-sm datepicker-control" placeholder="Pilih Periode" readonly required>
                        <input type="hidden" name="month" id="import-cc-month">
                        <input type="hidden" name="year" id="import-cc-year">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="fa-solid fa-sitemap me-1"></i>Divisi <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-sm" name="divisi_id" id="revCCDivisiImport" required>
                            <option value="">Pilih Divisi</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="fa-solid fa-tag me-1"></i>Jenis Data <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-sm" name="jenis_data" id="revCCJenisDataImport" required>
                            <option value="">Pilih Jenis</option>
                            <option value="revenue">Real Revenue</option>
                            <option value="target">Target Revenue</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="fa-solid fa-file me-1"></i>Upload CSV <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control form-control-sm" name="file" accept=".csv" required>
                    </div>
                </div>

                <div class="mb-3" style="font-size: 0.82rem;">
                    <span class="text-muted me-2">Template:</span>
                    <a href="#" id="linkTemplateDGSDSS" class="text-decoration-none me-2">
                        <i class="fa-solid fa-download me-1"></i><span id="textTemplateDGSDSS">DGS/DSS</span>
                    </a>
                    <span class="text-muted mx-1">|</span>
                    <a href="#" id="linkTemplateDPS" class="text-decoration-none">
                        <i class="fa-solid fa-download me-1"></i><span id="textTemplateDPS">DPS</span>
                    </a>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-upload me-1"></i>Import Revenue CC
                </button>
            </form>
        </div>

        {{-- Form Revenue AM --}}
        <div id="imp-rev-map" class="imp-panel">
            <div class="alert alert-light border mb-3 py-2 px-3" style="font-size: 0.875rem;">
                <div class="d-flex gap-2 align-items-start">
                    <i class="fa-solid fa-info-circle text-primary"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-1">Format CSV Revenue AM</strong>
                        <div class="text-muted" style="font-size: 0.85rem; line-height: 1.5;">
                            <strong>Kolom:</strong> NIPNAS, NIK_AM, PROPORSI<br>
                            <strong>Aturan:</strong> Revenue CC harus ada dulu | Proporsi dalam persen (60 = 60%) | Total per NIPNAS = 100
                        </div>
                    </div>
                </div>
            </div>

            <form id="formRevenueAM" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="revenue_am">

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-calendar-days me-1"></i>
                            Periode <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="import-am-periode" class="form-control datepicker-control" placeholder="Pilih Bulan & Tahun" autocomplete="off" readonly required>
                        <input type="hidden" name="month" id="import-am-month">
                        <input type="hidden" name="year" id="import-am-year">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-file-csv me-1"></i>
                            Upload File CSV <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control" name="file" accept=".csv" required>
                        <small class="form-text text-muted">
                            <a href="{{ route('revenue.template', ['type' => 'revenue-am']) }}" class="text-decoration-none">
                                <i class="fa-solid fa-download me-1"></i>Download Template
                            </a>
                        </small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload me-2"></i>Import Revenue AM
                </button>
            </form>
        </div>

      </div>
    </div>
  </div>
</div>

{{-- ========================================
     PREVIEW MODAL
     ======================================== --}}
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-eye me-2"></i>Preview Import Data
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="preview-summary" id="previewSummary"></div>
                <div class="preview-table-container">
                    <table class="table">
                        <thead><tr><th>Status</th><th>Data</th></tr></thead>
                        <tbody id="previewTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnExecuteImport">
                    Lanjutkan Import
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ========================================
     RESULT MODAL
     ======================================== --}}
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hasil Import</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="resultModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- ========================================
     EDIT MODALS
     ======================================== --}}
<div class="modal fade" id="modalEditRevenueCC" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Revenue CC</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditRevenueCC">
                <div class="modal-body">
                    <input type="hidden" id="editCCRevenueId">
                    <div class="mb-3">
                        <label class="form-label">Nama CC</label>
                        <input type="text" class="form-control" id="editCCNamaCC" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Revenue</label>
                        <input type="number" class="form-control" id="editCCTargetRevenue" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Real Revenue</label>
                        <input type="number" class="form-control" id="editCCRealRevenue" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditRevenueAM" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Revenue AM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditRevenueAM">
                <div class="modal-body">
                    <input type="hidden" id="editAMRevenueId">
                    <div class="mb-3">
                        <label class="form-label">Nama AM</label>
                        <input type="text" class="form-control" id="editAMNamaAM" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Proporsi (%)</label>
                        <input type="number" class="form-control" id="editAMProporsi" min="0" max="100" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditDataAM" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Data AM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-edit-data">Data AM</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-change-password">Ganti Password</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-edit-data">
                        <form id="formEditDataAM">
                            <input type="hidden" id="editDataAMId">
                            <div class="mb-3">
                                <label class="form-label">Nama AM</label>
                                <input type="text" class="form-control" id="editDataAMNama" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">NIK</label>
                                <input type="text" class="form-control" id="editDataAMNik" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Simpan</button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="tab-change-password">
                        <form id="formChangePasswordAM">
                            <input type="hidden" id="changePasswordAMId">
                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="newPassword" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" class="form-control" id="confirmPassword" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Ganti Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditDataCC" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Data CC</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditDataCC">
                <div class="modal-body">
                    <input type="hidden" id="editDataCCId">
                    <div class="mb-3">
                        <label class="form-label">Nama CC</label>
                        <input type="text" class="form-control" id="editDataCCNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NIPNAS</label>
                        <input type="text" class="form-control" id="editDataCCNipnas" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- LOADING OVERLAY --}}
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <div class="spinner-border"></div>
        <p id="loadingText">Memproses...</p>
    </div>
</div>

@endsection



@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
<script>
$(document).ready(function() {
  // ========================================
  // STATE MANAGEMENT
  // ========================================
  let currentTab = 'tab-cc-revenue';
  let currentPage = 1;
  let perPage = 25;
  let currentFilters = {
    search: '',
    witel_id: 'all',
    divisi_id: 'all',
    segment_id: 'all',
    periode: '',
    tipe_revenue: 'REGULER',
    role: 'all'
  };

  // Store divisi data globally for modal
  let allDivisiData = [];

  // Store TELDA data globally for modal
  let allTeldaData = [];

  // ✨ NEW: Preview Import State
  let previewData = null;
  let currentImportType = null;
  let currentFormData = null;
  let currentSessionId = null;

  // ========================================
  // FLATPICKR MONTH YEAR PICKER
  // ========================================
  (function initMonthYearPicker() {
    const dateInput   = document.getElementById('filter-date');
    const hiddenMonth = document.getElementById('filter-month');
    const hiddenYear  = document.getElementById('filter-year');

    if (!dateInput) return;

    const currentYear = new Date().getFullYear();
    let selectedYear  = currentYear;
    let selectedMonth = new Date().getMonth();

    const YEAR_FLOOR = 2020;
    function getYearWindow() {
      const nowY = new Date().getFullYear();
      const start = nowY;
      const end   = Math.max(YEAR_FLOOR, nowY - 5);
      return { start, end };
    }
    function clampSelectedYear() {
      const { start, end } = getYearWindow();
      if (selectedYear > start) selectedYear = start;
      if (selectedYear < end)   selectedYear = end;
    }

    let isYearView = false;
    let fpInstance = null;

    function getTriggerEl(instance){
      return instance?.altInput || dateInput;
    }
    function syncCalendarWidth(instance){
      try{
        const cal = instance.calendarContainer;
        const trigger = getTriggerEl(instance);
        if (!cal || !trigger) return;

        const rect = trigger.getBoundingClientRect();
        const w = Math.round(rect.width);

        cal.style.boxSizing = 'border-box';
        cal.style.width     = w + 'px';
        cal.style.maxWidth  = w + 'px';
      }catch(e){
        // no-op
      }
    }

    const fp = flatpickr(dateInput, {
      plugins: [ new monthSelectPlugin({
        shorthand: true,
        dateFormat: "Y-m",
        altFormat: "F Y",
        theme: "light"
      })],
      altInput: true,
      defaultDate: new Date(),
      allowInput: false,
      monthSelectorType: 'static',

      onReady(selectedDates, value, instance) {
        fpInstance = instance;
        const d = selectedDates?.[0] || new Date();
        selectedYear  = d.getFullYear();
        selectedMonth = d.getMonth();

        clampSelectedYear();

        hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
        hiddenYear.value  = selectedYear;

        instance.calendarContainer.classList.add('fp-compact');

        syncCalendarWidth(instance);

        setupCustomUI(instance);
      },

      onOpen(selectedDates, value, instance) {
        fpInstance = instance;
        isYearView = false;

        clampSelectedYear();
        renderMonthView(instance);

        syncCalendarWidth(instance);

        setTimeout(() => {
          const activeMonth = instance.calendarContainer.querySelector('.fp-month-option.selected');
          if (activeMonth) {
            activeMonth.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          }
        }, 100);
      }
    });

    window.addEventListener('resize', () => {
      if (fpInstance && fpInstance.isOpen) {
        syncCalendarWidth(fpInstance);
      }
    });

    function setupCustomUI(instance) {
      const cal = instance.calendarContainer;
      const monthsContainer = cal.querySelector('.flatpickr-monthSelect-months, .monthSelect-months');
      if (monthsContainer) {
        monthsContainer.style.display = 'none';
      }
    }

    function renderMonthView(instance) {
      const cal = instance.calendarContainer;
      const header = cal.querySelector('.flatpickr-current-month');

      if (header) {
        header.innerHTML = `
          <button type="button" class="fp-year-toggle" style="background:transparent;border:0;color:#fff;font-size:1.25rem;font-weight:700;cursor:pointer;padding:8px 16px;border-radius:8px;">
            ${selectedYear} <span style="font-size:0.875rem;margin-left:4px;">▼</span>
          </button>
        `;
        const yearToggle = header.querySelector('.fp-year-toggle');
        yearToggle.addEventListener('click', (e) => {
          e.preventDefault(); e.stopPropagation();
          isYearView = true;
          renderYearView(instance);
        });
      }

      let container = cal.querySelector('.fp-month-grid, .fp-year-grid, .flatpickr-monthSelect-months, .monthSelect-months, .flatpickr-innerContainer');
      if (!container) return;

      container.innerHTML = '';
      container.className = 'fp-month-grid';
      container.setAttribute('tabindex', '0');

      const monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

      monthNames.forEach((name, idx) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'fp-month-option';
        btn.textContent = name;

        const currentSelectedDate = fp.selectedDates[0] || new Date();
        if (idx === selectedMonth && selectedYear === currentSelectedDate.getFullYear()) {
          btn.classList.add('selected');
        }

        btn.addEventListener('click', (e) => {
          e.preventDefault(); e.stopPropagation();
          selectedMonth = idx;
          const newDate = new Date(selectedYear, selectedMonth, 1);
          fp.setDate(newDate, true);
          hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
          hiddenYear.value  = selectedYear;

          currentFilters.periode = `${selectedYear}-${String(selectedMonth + 1).padStart(2, '0')}`;
          currentPage = 1;
          loadData();

          setTimeout(() => fp.close(), 150);
        });

        container.appendChild(btn);
      });

      const activeMonth = container.querySelector('.fp-month-option.selected');
      if (activeMonth) {
        activeMonth.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }

    function renderYearView(instance) {
      const cal = instance.calendarContainer;
      const header = cal.querySelector('.flatpickr-current-month');

      if (header) {
        header.innerHTML = `
          <button type="button" class="fp-back-btn" style="background:transparent;border:0;color:#fff;font-size:1.5rem;cursor:pointer;position:absolute;left:16px;top:50%;transform:translateY(-50%);line-height:1;">
            ‹
          </button>
          <span style="color:#fff;font-weight:700;font-size:1.125rem;">Tahun</span>
        `;

        const backBtn = header.querySelector('.fp-back-btn');
        backBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          isYearView = false;
          renderMonthView(instance);
        });
      }

      let container = cal.querySelector('.fp-month-grid, .fp-year-grid, .flatpickr-innerContainer');
      if (!container) return;

      container.innerHTML = '';
      container.className = 'fp-year-grid';

      const { start, end } = getYearWindow();
      for (let y = start; y >= end; y--) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'fp-year-option';
        btn.textContent = y;

        if (y === selectedYear) {
          btn.classList.add('active');
        }

        btn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();

          selectedYear = y;
          hiddenYear.value = selectedYear;

          isYearView = false;
          renderMonthView(instance);
        });

        container.appendChild(btn);
      }

      setTimeout(() => {
        const activeYear = container.querySelector('.fp-year-option.active');
        if (activeYear) {
          activeYear.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }, 100);
    }

    const resetBtn = document.getElementById('btn-reset-filter');
    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        const now = new Date();
        selectedYear  = now.getFullYear();
        selectedMonth = now.getMonth();

        clampSelectedYear();

        fp.setDate(now, true);
        hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
        hiddenYear.value  = selectedYear;
      });
    }
  })();

  // ========================================
  // ✨ FLATPICKR FOR IMPORT MODALS (Revenue CC & AM)
  // ========================================
  (function initImportMonthPickers() {
    const YEAR_FLOOR = 2020;

    function getYearWindow() {
      const nowY = new Date().getFullYear();
      const start = nowY;
      const end = Math.max(YEAR_FLOOR, nowY - 5);
      return { start, end };
    }

    function createMonthPicker(inputId, hiddenMonthId, hiddenYearId) {
      const dateInput = document.getElementById(inputId);
      const hiddenMonth = document.getElementById(hiddenMonthId);
      const hiddenYear = document.getElementById(hiddenYearId);

      if (!dateInput) return null;

      let selectedYear = new Date().getFullYear();
      let selectedMonth = new Date().getMonth();
      let isYearView = false;

      const fp = flatpickr(dateInput, {
        plugins: [ new monthSelectPlugin({
          shorthand: true,
          dateFormat: "Y-m",
          altFormat: "F Y",
          theme: "light"
        })],
        altInput: true,
        defaultDate: new Date(),
        allowInput: false,
        monthSelectorType: 'static',

        onReady(selectedDates, value, instance) {
          const d = selectedDates?.[0] || new Date();
          selectedYear = d.getFullYear();
          selectedMonth = d.getMonth();

          hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
          hiddenYear.value = selectedYear;

          instance.calendarContainer.classList.add('fp-compact');
          setupCustomUI(instance);
        },

        onOpen(selectedDates, value, instance) {
          isYearView = false;
          renderMonthView(instance);
        }
      });

      function setupCustomUI(instance) {
        const cal = instance.calendarContainer;
        const monthsContainer = cal.querySelector('.flatpickr-monthSelect-months, .monthSelect-months');
        if (monthsContainer) {
          monthsContainer.style.display = 'none';
        }
      }

      function renderMonthView(instance) {
        const cal = instance.calendarContainer;
        const header = cal.querySelector('.flatpickr-current-month');

        if (header) {
          header.innerHTML = `
            <button type="button" class="fp-year-toggle" style="background:transparent;border:0;color:#fff;font-size:1.25rem;font-weight:700;cursor:pointer;padding:8px 16px;border-radius:8px;">
              ${selectedYear} <span style="font-size:0.875rem;margin-left:4px;">▼</span>
            </button>
          `;
          const yearToggle = header.querySelector('.fp-year-toggle');
          yearToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            isYearView = true;
            renderYearView(instance);
          });
        }

        let container = cal.querySelector('.fp-month-grid, .fp-year-grid, .flatpickr-monthSelect-months, .monthSelect-months, .flatpickr-innerContainer');
        if (!container) return;

        container.innerHTML = '';
        container.className = 'fp-month-grid';
        container.setAttribute('tabindex', '0');

        const monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

        monthNames.forEach((name, idx) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'fp-month-option';
          btn.textContent = name;

          if (idx === selectedMonth && selectedYear === fp.selectedDates[0].getFullYear()) {
            btn.classList.add('selected');
          }

          btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            selectedMonth = idx;
            const newDate = new Date(selectedYear, selectedMonth, 1);
            fp.setDate(newDate, true);
            hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
            hiddenYear.value = selectedYear;
            setTimeout(() => fp.close(), 150);
          });

          container.appendChild(btn);
        });
      }

      function renderYearView(instance) {
        const cal = instance.calendarContainer;
        const header = cal.querySelector('.flatpickr-current-month');

        if (header) {
          header.innerHTML = `
            <button type="button" class="fp-back-btn" style="background:transparent;border:0;color:#fff;font-size:1.5rem;cursor:pointer;position:absolute;left:16px;top:50%;transform:translateY(-50%);line-height:1;">
              ‹
            </button>
            <span style="color:#fff;font-weight:700;font-size:1.125rem;">Tahun</span>
          `;

          const backBtn = header.querySelector('.fp-back-btn');
          backBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            isYearView = false;
            renderMonthView(instance);
          });
        }

        let container = cal.querySelector('.fp-month-grid, .fp-year-grid, .flatpickr-innerContainer');
        if (!container) return;

        container.innerHTML = '';
        container.className = 'fp-year-grid';

        const { start, end } = getYearWindow();
        for (let y = start; y >= end; y--) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'fp-year-option';
          btn.textContent = y;

          if (y === selectedYear) {
            btn.classList.add('active');
          }

          btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            selectedYear = y;
            hiddenYear.value = selectedYear;
            isYearView = false;
            renderMonthView(instance);
          });

          container.appendChild(btn);
        }
      }

      return fp;
    }

    let importCCPicker = null;
    let importAMPicker = null;

    const importModal = document.getElementById('importModal');
    if (importModal) {
      importModal.addEventListener('shown.bs.modal', function() {
        setTimeout(() => {
          if (importCCPicker) {
            importCCPicker.destroy();
            importCCPicker = null;
          }
          if (importAMPicker) {
            importAMPicker.destroy();
            importAMPicker = null;
          }

          if (document.getElementById('import-cc-periode')) {
            importCCPicker = createMonthPicker('import-cc-periode', 'import-cc-month', 'import-cc-year');
          }

          if (document.getElementById('import-am-periode')) {
            importAMPicker = createMonthPicker('import-am-periode', 'import-am-month', 'import-am-year');
          }
        }, 100);
      });

      importModal.addEventListener('hidden.bs.modal', function() {
        if (importCCPicker) {
          importCCPicker.destroy();
          importCCPicker = null;
        }
        if (importAMPicker) {
          importAMPicker.destroy();
          importAMPicker = null;
        }

        document.querySelectorAll('.imp-panel form').forEach(form => {
          form.reset();
        });
      });
    }
  })();



// ============================================
// REVENUE CC - FIX DIVISI LOADING
// ============================================

/**
 * Update template links
 */
function updateRevenueCCTemplateLinks() {
    const jenisData = $('#revCCJenisDataImport').val() || 'target';
    
    let templateDGSDSS, templateDPS, textDGSDSS, textDPS;
    
    if (jenisData === 'revenue') {
        templateDGSDSS = 'revenue-cc-dgs-real';
        templateDPS = 'revenue-cc-dps-real';
        textDGSDSS = 'Real DGS/DSS';
        textDPS = 'Real DPS';
    } else {
        templateDGSDSS = 'revenue-cc-dgs-target';
        templateDPS = 'revenue-cc-dps-target';
        textDGSDSS = 'Target DGS/DSS';
        textDPS = 'Target DPS';
    }
    
    $('#linkTemplateDGSDSS')
        .attr('href', `/revenue-data/template/${templateDGSDSS}`)
        .find('#textTemplateDGSDSS').text(textDGSDSS);
    
    $('#linkTemplateDPS')
        .attr('href', `/revenue-data/template/${templateDPS}`)
        .find('#textTemplateDPS').text(textDPS);
}

/**
 * Load divisi - MULTIPLE FALLBACK STRATEGIES
 */
function loadDivisiOptionsRevCC() {
    const select = $('#revCCDivisiImport');
    select.empty().append('<option value="">Pilih Divisi</option>');
    
    // Strategy 1: Use global allDivisiData
    if (typeof allDivisiData !== 'undefined' && Array.isArray(allDivisiData) && allDivisiData.length > 0) {
        console.log('Using allDivisiData:', allDivisiData);
        allDivisiData.forEach(function(div) {
            select.append(`<option value="${div.id}">${div.nama} (${div.kode})</option>`);
        });
        return;
    }
    
    // Strategy 2: Use window.allDivisiData
    if (typeof window.allDivisiData !== 'undefined' && Array.isArray(window.allDivisiData) && window.allDivisiData.length > 0) {
        console.log('Using window.allDivisiData:', window.allDivisiData);
        window.allDivisiData.forEach(function(div) {
            select.append(`<option value="${div.id}">${div.nama} (${div.kode})</option>`);
        });
        return;
    }
    
    // Strategy 3: Copy from main filter dropdown
    const mainDivisiOptions = $('#divisiFilter option:not(:first)');
    if (mainDivisiOptions.length > 0) {
        console.log('Cloning from #divisiFilter');
        mainDivisiOptions.each(function() {
            select.append($(this).clone());
        });
        return;
    }
    
    // Strategy 4: AJAX as last resort
    console.log('Loading divisi via AJAX');
    $.ajax({
        url: '/revenue-data/filter-options',
        method: 'GET',
        success: function(response) {
            console.log('AJAX response:', response);
            
            let divisiData = null;
            
            // Try different response structures
            if (response && response.success && response.data && response.data.divisi) {
                divisiData = response.data.divisi;
            } else if (response && response.data && Array.isArray(response.data)) {
                divisiData = response.data;
            } else if (response && response.divisi) {
                divisiData = response.divisi;
            } else if (Array.isArray(response)) {
                divisiData = response;
            }
            
            if (divisiData && Array.isArray(divisiData) && divisiData.length > 0) {
                divisiData.forEach(function(div) {
                    select.append(`<option value="${div.id}">${div.nama} (${div.kode})</option>`);
                });
            } else {
                console.error('No divisi data found in response:', response);
            }
        },
        error: function(xhr) {
            console.error('AJAX error:', xhr);
        }
    });
}

// Event listeners
$(document).on('change', '#revCCJenisDataImport', updateRevenueCCTemplateLinks);

// Load on modal show
$('#importModal').on('shown.bs.modal', function() {
    if ($('#imp-rev-cc').hasClass('active')) {
        loadDivisiOptionsRevCC();
        updateRevenueCCTemplateLinks();
    }
});

// Load on tab click
$(document).on('click', '[data-imp="imp-rev-cc"]', function() {
    setTimeout(function() {
        loadDivisiOptionsRevCC();
        updateRevenueCCTemplateLinks();
    }, 200);
});

// Initialize
$(document).ready(function() {
    updateRevenueCCTemplateLinks();
});


  // ========================================
  // ✅ FIX #1: BUILD SEGMENT DROPDOWN UI + INTERACTIONS
  // ========================================
  function buildSegmentUI(segments) {
    const nativeSelect = document.getElementById('filter-segment');
    const segTabs = document.getElementById('segTabs');
    const segPanels = document.getElementById('segPanels');

    if (!nativeSelect || !segTabs || !segPanels) return;

    // Clear existing content
    segTabs.innerHTML = '';
    segPanels.innerHTML = '';

    // Group segments by divisi
    const groupedSegments = {};
    segments.forEach(segment => {
      const raw = (segment.divisi_kode || segment.divisi || '').toString().trim().toUpperCase();
      const divisiKode = raw || 'OTHER';

      if (!groupedSegments[divisiKode]) groupedSegments[divisiKode] = [];
      groupedSegments[divisiKode].push(segment);

      // Add to native select
      const option = document.createElement('option');
      option.value = segment.id;
      option.textContent = segment.lsegment_ho;
      nativeSelect.appendChild(option);
    });

    // Define tab order
    const ORDER = ['DPS', 'DSS', 'DGS', 'DES'];
    const keys = Object.keys(groupedSegments);
    const mainDivisi = keys.filter(k => k && k.toUpperCase() !== 'OTHER');
    const divisiList = [
      ...ORDER.filter(code => mainDivisi.includes(code)),
      ...mainDivisi.filter(code => !ORDER.includes(code)).sort()
    ];

    let firstTab = true;
    let firstDivisiName = null;

    // Handle case where only OTHER exists
    if (divisiList.length === 0 && groupedSegments['OTHER']?.length) {
      divisiList.push('SEGMENT');
      groupedSegments['SEGMENT'] = [];
    }

    // Build tabs and panels
    divisiList.forEach(divisi => {
      if (firstTab) firstDivisiName = divisi;

      // Create tab button
      const tabBtn = document.createElement('button');
      tabBtn.className = `seg-tab${firstTab ? ' active' : ''}`;
      tabBtn.dataset.tab = divisi;
      tabBtn.setAttribute('role', 'tab');
      tabBtn.setAttribute('aria-selected', firstTab ? 'true' : 'false');
      tabBtn.textContent = divisi;
      segTabs.appendChild(tabBtn);

      // Create panel
      const panel = document.createElement('div');
      panel.className = `seg-panel${firstTab ? ' active' : ''}`;
      panel.dataset.panel = divisi;
      panel.setAttribute('role', 'tabpanel');

      // "Semua Segment" option
      const allOption = document.createElement('button');
      allOption.className = 'seg-option all';
      allOption.dataset.value = 'all';
      allOption.textContent = 'Semua Segment';
      panel.appendChild(allOption);

      // Add segment options for this divisi
      (groupedSegments[divisi] || []).forEach(segment => {
        const optionBtn = document.createElement('button');
        optionBtn.className = 'seg-option';
        optionBtn.dataset.value = segment.id;
        optionBtn.textContent = segment.lsegment_ho;
        panel.appendChild(optionBtn);
      });

      segPanels.appendChild(panel);
      firstTab = false;
    });

    // Insert OTHER items into first panel (without creating OTHER tab)
    const otherItems = groupedSegments['OTHER'];
    if (firstDivisiName && Array.isArray(otherItems) && otherItems.length) {
      const firstPanel = segPanels.querySelector(`.seg-panel[data-panel="${firstDivisiName}"]`);
      if (firstPanel) {
        otherItems.forEach(segment => {
          const optionBtn = document.createElement('button');
          optionBtn.className = 'seg-option';
          optionBtn.dataset.value = segment.id;
          optionBtn.textContent = segment.lsegment_ho;
          firstPanel.appendChild(optionBtn);
        });
      }
    }

    // Initialize interactions
    initSegmentSelectInteractions();
  }

  function initSegmentSelectInteractions() {
    const segSelect = document.getElementById('segSelect');
    if (!segSelect) return;

    const nativeSelect = document.getElementById('filter-segment');
    const triggerBtn = segSelect.querySelector('.seg-select__btn');
    const labelSpan = segSelect.querySelector('.seg-select__label');

    // Get elements after UI is built
    const segTabs = segSelect.querySelectorAll('.seg-tab');
    const segPanels = segSelect.querySelectorAll('.seg-panel');
    const segOptions = segSelect.querySelectorAll('.seg-option');

    // Toggle menu
    triggerBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      segSelect.classList.toggle('open');
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!segSelect.contains(e.target)) {
        segSelect.classList.remove('open');
      }
    });

    // Tab switching
    segTabs.forEach(tab => {
      tab.addEventListener('click', (e) => {
        e.stopPropagation();
        const targetPanel = tab.dataset.tab;

        segTabs.forEach(t => {
          t.classList.remove('active');
          t.setAttribute('aria-selected', 'false');
        });
        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');

        segPanels.forEach(panel => panel.classList.remove('active'));
        const activePanel = segSelect.querySelector(`.seg-panel[data-panel="${targetPanel}"]`);
        if (activePanel) activePanel.classList.add('active');
      });
    });

    // Option selection
    segOptions.forEach(option => {
      option.addEventListener('click', () => {
        const value = option.dataset.value;
        const label = option.textContent.trim();

        // Update native select
        nativeSelect.value = value;
        nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));

        // Update label
        labelSpan.textContent = label;

        // Update visual state
        segOptions.forEach(opt => opt.removeAttribute('aria-selected'));
        option.setAttribute('aria-selected', 'true');

        // Apply styling based on selection
        if (value === 'all') {
          segSelect.classList.add('is-all-selected');
          segSelect.classList.remove('has-value');
        } else {
          segSelect.classList.remove('is-all-selected');
          segSelect.classList.add('has-value');
        }

        // Close dropdown
        setTimeout(() => segSelect.classList.remove('open'), 150);
      });
    });
  }

  // ========================================
  // CUSTOM SELECT ENHANCEMENT
  // ========================================
  function enhanceNativeSelect(native, opts = {}) {
    if (!native || native.dataset.enhanced === '1') return;

    const inModal = opts.inModal || false;
    const wrap = document.createElement('div');
    wrap.className = 'cselect';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'cselect__btn';
    btn.setAttribute('aria-haspopup','listbox');

    const selectedOpt = native.options[native.selectedIndex];
    const labelSpan = document.createElement('span');
    labelSpan.className = 'cselect__label';
    labelSpan.textContent = selectedOpt ? selectedOpt.textContent.trim() : '';
    btn.appendChild(labelSpan);

    const arrow = document.createElement('span');
    arrow.className = 'cselect__arrow';
    arrow.innerHTML = '▼';
    btn.appendChild(arrow);

    const menu = document.createElement('div');
    menu.className = 'cselect__menu';
    menu.setAttribute('role','listbox');

    const list = document.createElement('div');
    list.className = 'cselect__list';

    Array.from(native.options).forEach((opt, idx) => {
      const item = document.createElement('div');
      item.className = 'cselect__option' + (idx === 0 ? ' is-all' : '');
      item.setAttribute('role','option');
      item.dataset.value = opt.value;
      item.textContent = opt.textContent.trim();
      if (opt.selected) item.setAttribute('aria-selected','true');

      item.addEventListener('click', () => {
        native.value = opt.value;
        native.dispatchEvent(new Event('change', { bubbles: true }));

        btn.querySelector('.cselect__label').textContent = opt.textContent.trim();
        list.querySelectorAll('.cselect__option[aria-selected]')
            .forEach(el => el.removeAttribute('aria-selected'));
        item.setAttribute('aria-selected','true');

        wrap.classList.remove('is-open');
      });

      list.appendChild(item);
    });

    menu.appendChild(list);

    native.insertAdjacentElement('afterend', wrap);
    wrap.appendChild(btn);
    wrap.appendChild(menu);

    if (inModal) {
      native.classList.add('visually-hidden-cselect');
    } else {
      native.style.position = 'absolute';
      native.style.inset = '0 auto auto 0';
      native.style.width = '1px';
      native.style.height = '1px';
      native.style.opacity = '0';
      native.style.pointerEvents = 'none';
    }

    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (wrap.classList.contains('is-disabled')) return;
      wrap.classList.toggle('is-open');
    });
    document.addEventListener('click', (e) => {
      if (!wrap.contains(e.target)) wrap.classList.remove('is-open');
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') wrap.classList.remove('is-open');
    });

    native.addEventListener('change', () => {
      const v = native.value;
      const found = list.querySelector(`.cselect__option[data-value="${CSS.escape(v)}"]`);
      if (found) {
        btn.querySelector('.cselect__label').textContent = found.textContent;
        list.querySelectorAll('.cselect__option[aria-selected]').forEach(el => el.removeAttribute('aria-selected'));
        found.setAttribute('aria-selected','true');
      }
    });

    native.dataset.enhanced = '1';
  }

  function enhanceFilterBar(){
    const selects = document.querySelectorAll('.filters .filter-group:nth-of-type(-n+2) .form-select');
    selects.forEach(sel => enhanceNativeSelect(sel, { inModal: false }));
  }

  function enhanceModalDivisi(){
    const selModal = document.querySelector('#imp-rev-cc .filter-group .form-select');
    if (selModal) enhanceNativeSelect(selModal, { inModal: true });

    const modalEl = document.getElementById('importModal');
    if (modalEl) {
      modalEl.addEventListener('shown.bs.modal', () => {
        const sel = document.querySelector('#imp-rev-cc .filter-group .form-select');
        if (sel && sel.dataset.enhanced !== '1') {
          enhanceNativeSelect(sel, { inModal: true });
        }
      });
    }
  }

  // ========================================
  // ✨ DIVISI BUTTON GROUP HANDLER
  // ========================================
  function initDivisiButtonGroup() {
  const buttonGroup = document.getElementById('divisiButtonGroup');
  const hiddenContainer = document.getElementById('divisiHiddenInputs');

  if (!buttonGroup || !hiddenContainer) {
    console.warn('Divisi button group elements not found');
    return;
  }

  buttonGroup.innerHTML = '';
  hiddenContainer.innerHTML = '';

  allDivisiData.forEach(divisi => {
    const btn = document.createElement('button');
    btn.type = 'button';
    const kodeRingkas = divisi.kode.substring(0, 3).toUpperCase();
    btn.className = `divisi-toggle-btn ${kodeRingkas.toLowerCase()}`;
    btn.dataset.divisiId = divisi.id;
    btn.dataset.divisiKode = divisi.kode;
    btn.textContent = kodeRingkas;

    btn.addEventListener('click', function() {
      this.classList.toggle('active');
      updateHiddenInputs();
    });

    buttonGroup.appendChild(btn);
  });
}

function updateHiddenInputs() {
  const hiddenContainer = document.getElementById('divisiHiddenInputs');
  
  // ✅ FIX: Null check
  if (!hiddenContainer) {
    console.warn('hiddenContainer not found, skipping update');
    return;
  }

  const activeButtons = document.querySelectorAll('.divisi-toggle-btn.active');
  hiddenContainer.innerHTML = '';

  activeButtons.forEach(btn => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'divisi_ids[]';
    input.value = btn.dataset.divisiId;
    hiddenContainer.appendChild(input);
  });
}

function setSelectedDivisi(divisiIds) {
  // ✅ FIX: Null check untuk buttons
  const buttons = document.querySelectorAll('.divisi-toggle-btn');
  if (!buttons || buttons.length === 0) {
    console.warn('Divisi buttons not found');
    return;
  }

  // Reset all buttons
  buttons.forEach(btn => btn.classList.remove('active'));

  // Set active buttons
  if (Array.isArray(divisiIds)) {
    divisiIds.forEach(id => {
      const btn = document.querySelector(`.divisi-toggle-btn[data-divisi-id="${id}"]`);
      if (btn) {
        btn.classList.add('active');
      }
    });
  }

  updateHiddenInputs();
}

  // ========================================
  // CHECKBOX & BULK DELETE LOGIC
  // ========================================
  $('#selectAllCC').on('change', function() {
    $('.row-checkbox-cc').prop('checked', this.checked);
    updateBulkDeleteButton('CC');
  });

  $('#selectAllAM').on('change', function() {
    $('.row-checkbox-am').prop('checked', this.checked);
    updateBulkDeleteButton('AM');
  });

  $('#selectAllDataAM').on('change', function() {
    $('.row-checkbox-data-am').prop('checked', this.checked);
    updateBulkDeleteButton('DataAM');
  });

  $('#selectAllDataCC').on('change', function() {
    $('.row-checkbox-data-cc').prop('checked', this.checked);
    updateBulkDeleteButton('DataCC');
  });

  $(document).on('change', '.row-checkbox-cc, .row-checkbox-am, .row-checkbox-data-am, .row-checkbox-data-cc', function() {
    const type = $(this).hasClass('row-checkbox-cc') ? 'CC' :
                 $(this).hasClass('row-checkbox-am') ? 'AM' :
                 $(this).hasClass('row-checkbox-data-am') ? 'DataAM' : 'DataCC';
    updateBulkDeleteButton(type);
  });

  function updateBulkDeleteButton(type) {
    const checked = $(`.row-checkbox-${type === 'DataAM' ? 'data-am' : type === 'DataCC' ? 'data-cc' : type.toLowerCase()}:checked`).length;
    const btn = $(`#btnDeleteSelected${type}`);

    if (checked > 0) {
      btn.prop('disabled', false).html(`<i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih (${checked})`);
    } else {
      btn.prop('disabled', true).html('<i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih');
    }
  }

  $('#btnDeleteSelectedCC').click(function() {
    bulkDeleteSelected('cc-revenue', 'Revenue CC');
  });

  $('#btnDeleteSelectedAM').click(function() {
    bulkDeleteSelected('am-revenue', 'Revenue AM');
  });

  $('#btnDeleteSelectedDataAM').click(function() {
    bulkDeleteSelected('data-am', 'Data AM');
  });

  $('#btnDeleteSelectedDataCC').click(function() {
    bulkDeleteSelected('data-cc', 'Data CC');
  });

  function bulkDeleteSelected(endpoint, name) {
    const checkboxClass = endpoint === 'cc-revenue' ? '.row-checkbox-cc' :
                          endpoint === 'am-revenue' ? '.row-checkbox-am' :
                          endpoint === 'data-am' ? '.row-checkbox-data-am' : '.row-checkbox-data-cc';

    const ids = $(checkboxClass + ':checked').map(function() {
      return $(this).data('id');
    }).get();

    if (ids.length === 0) {
      alert('Pilih minimal 1 data untuk dihapus');
      return;
    }

    if (!confirm(`Hapus ${ids.length} ${name} terpilih?\n\nTindakan ini tidak dapat dibatalkan!`)) {
      return;
    }

    $.ajax({
      url: `/revenue-data/bulk-delete-${endpoint}`,
      method: 'POST',
      data: JSON.stringify({ ids: ids }),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  }

  $('#btnBulkDeleteCC').click(function() {
    bulkDeleteAll('cc-revenue', 'Revenue CC');
  });

  $('#btnBulkDeleteAM').click(function() {
    bulkDeleteAll('am-revenue', 'Revenue AM');
  });

  $('#btnBulkDeleteDataAM').click(function() {
    bulkDeleteAll('data-am', 'Data AM');
  });

  $('#btnBulkDeleteDataCC').click(function() {
    bulkDeleteAll('data-cc', 'Data CC');
  });

  function bulkDeleteAll(endpoint, name) {
    if (!confirm(`Hapus SEMUA ${name}?\n\nTindakan ini tidak dapat dibatalkan!`)) {
      return;
    }

    $.ajax({
      url: `/revenue-data/bulk-delete-all-${endpoint}`,
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  }

  // ========================================
  // LOAD FILTER OPTIONS FROM BACKEND
  // ========================================
  function loadFilterOptions() {
    $.ajax({
      url: '{{ route("revenue.api.filter.options") }}',
      method: 'GET',
      success: function(response) {
        const witelSelect = $('#filterWitel');
        response.witels.forEach(function(witel) {
          witelSelect.append(`<option value="${witel.id}">${witel.nama}</option>`);
        });

        const divisiSelect = $('#filterDivisi');
        const divisiImport = $('#divisiImport');
        response.divisions.forEach(function(divisi) {
          divisiSelect.append(`<option value="${divisi.id}">${divisi.nama}</option>`);
          divisiImport.append(`<option value="${divisi.id}">${divisi.nama}</option>`);
        });

        // Store globally for modals
        allDivisiData = response.divisions;
        initDivisiButtonGroup();

        // ✅ FIX #1: Build Segment UI and init interactions
        if (response.segments && response.segments.length > 0) {
          buildSegmentUI(response.segments);
        }

        // Store TELDA data globally
        if (response.teldas) {
          allTeldaData = response.teldas;
        }

        // Populate edit modal selects
        const editWitelSelect = $('#editDataAMWitel');
        editWitelSelect.empty();
        editWitelSelect.append('<option value="">Pilih Witel</option>');
        response.witels.forEach(function(witel) {
          editWitelSelect.append(`<option value="${witel.id}">${witel.nama}</option>`);
        });

        const editTeldaSelect = $('#editDataAMTelda');
        editTeldaSelect.empty();
        editTeldaSelect.append('<option value="">Pilih TELDA</option>');
        if (response.teldas) {
          response.teldas.forEach(function(telda) {
            editTeldaSelect.append(`<option value="${telda.id}">${telda.nama}</option>`);
          });
        }

        enhanceFilterBar();
        enhanceModalDivisi();
      },
      error: function(xhr) {
        console.error('Error loading filters:', xhr);
      }
    });
  }

  // ========================================
  // LOAD DATA FROM BACKEND
  // ========================================
  function loadData() {
    let url = '';
    const params = {
      page: currentPage,
      per_page: perPage,
      search: currentFilters.search,
      witel_id: currentFilters.witel_id,
      divisi_id: currentFilters.divisi_id,
      segment_id: currentFilters.segment_id
    };

    if (currentTab === 'tab-cc-revenue') {
      url = '{{ route("revenue.api.cc") }}';
      params.periode = currentFilters.periode;
      params.tipe_revenue = currentFilters.tipe_revenue;
    } else if (currentTab === 'tab-am-revenue') {
      url = '{{ route("revenue.api.am") }}';
      params.periode = currentFilters.periode;
      params.role = currentFilters.role;
    } else if (currentTab === 'tab-data-am') {
      url = '{{ route("revenue.api.data.am") }}';
      params.role = currentFilters.role;
    } else if (currentTab === 'tab-data-cc') {
      url = '{{ route("revenue.api.data.cc") }}';
    }

    $.ajax({
      url: url,
      method: 'GET',
      data: params,
      success: function(response) {
        console.log('✅ Data loaded for tab:', currentTab, response);

        if (currentTab === 'tab-cc-revenue') {
          renderRevenueCC(response);
        } else if (currentTab === 'tab-am-revenue') {
          renderRevenueAM(response);
        } else if (currentTab === 'tab-data-am') {
          renderDataAM(response);
        } else if (currentTab === 'tab-data-cc') {
          renderDataCC(response);
        }

        renderPagination(response);
        updateBadge(currentTab, response.total || 0);

        $('[data-bs-toggle="tooltip"]').tooltip();
      },
      error: function(xhr) {
        console.error('❌ Error loading data for tab:', currentTab, xhr);
        showAlert('Gagal memuat data: ' + (xhr.responseJSON?.message || xhr.statusText), 'danger');
      }
    });
  }

  // ========================================
  // RENDER FUNCTIONS
  // ========================================
  function renderRevenueCC(response) {
    const tbody = $('#tableRevenueCC');
    tbody.empty();

    if (!response || !response.data || response.data.length === 0) {
      tbody.append('<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    response.data.forEach(function(item) {
      const divisiKode = item.divisi_kode || item.divisi || '-';
      const divisiDisplay = divisiKode !== '-' ? divisiKode.substring(0, 3).toUpperCase() : '-';
      const nipnas = item.nipnas || '-';
      const divisiClass = divisiDisplay !== '-' ? `badge-div ${divisiDisplay.toLowerCase()}` : '';

      const row = `
        <tr>
          <td><input type="checkbox" class="row-checkbox-cc" data-id="${item.id}"></td>
          <td>
            <strong style="font-size: 1rem; font-weight: 700;">${item.nama_cc}</strong><br>
            <small class="text-muted" style="font-size: 0.85rem;">
              ${divisiDisplay !== '-' ? `<span class="${divisiClass}">${divisiDisplay}</span> | ` : ''}${nipnas}
            </small>
          </td>
          <td>${item.segment || '-'}</td>
          <td class="text-end">${formatCurrency(item.target_revenue)}</td>
          <td class="text-end">
            <span data-bs-toggle="tooltip" title="${item.revenue_type || ''}">
              ${formatCurrency(item.real_revenue)}
            </span>
          </td>
          <td>${item.bulan_display}</td>
          <td class="text-center">
            <div class="action-buttons">
              <button class="btn btn-sm btn-warning" onclick="editRevenueCC(${item.id})" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteRevenueCC(${item.id})" title="Hapus">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
      tbody.append(row);
    });

    $('[data-bs-toggle="tooltip"]').tooltip();
  }

  function renderRevenueAM(response) {
    const tbody = $('#tableRevenueAM');
    tbody.empty();

    if (!response || !response.data || response.data.length === 0) {
      tbody.append('<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    response.data.forEach(function(item) {
      const role = item.role || 'AM';
      const roleClass = role === 'HOTDA' ? 'badge-role-hotda' : 'badge-role-am';
      const divisiKode = item.divisi_kode || item.divisi || '-';
      const divisiDisplay = divisiKode !== '-' ? divisiKode.substring(0, 3).toUpperCase() : '-';
      const divisiClass = divisiDisplay !== '-' ? `badge-div ${divisiDisplay.toLowerCase()}` : '';

      // ✅ FIX #2: TELDA display - show "-" for AM, actual value for HOTDA
      const teldaDisplay = role === 'HOTDA' ? (item.telda_nama || '-') : '-';
      const achievementPercent = item.achievement ? parseFloat(item.achievement).toFixed(2) : '0.00';

      const row = `
        <tr>
          <td><input type="checkbox" class="row-checkbox-am" data-id="${item.id}"></td>
          <td>
            <strong style="font-size: 1rem; font-weight: 700;">${item.nama_am}</strong><br>
            <small>
              <span class="${roleClass}">${role}</span>
              ${divisiDisplay !== '-' ? `<span class="${divisiClass}" style="margin-left: 4px;">${divisiDisplay}</span>` : ''}
            </small>
          </td>
          <td>${item.nama_cc}</td>
          <td class="text-end">${formatCurrency(item.target_revenue)}</td>
          <td class="text-end">${formatCurrency(item.real_revenue)}</td>
          <td class="text-end">${achievementPercent}%</td>
          <td>${item.bulan_display}</td>
          <td class="telda-col">${teldaDisplay}</td>
          <td class="text-center">
            <div class="action-buttons">
              <button class="btn btn-sm btn-warning" onclick="editRevenueAM(${item.id})" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteRevenueAM(${item.id})" title="Hapus">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
      tbody.append(row);
    });

    $('[data-bs-toggle="tooltip"]').tooltip();
  }

  function renderDataAM(response) {
    const tbody = $('#tableDataAM');
    tbody.empty();

    if (!response || !response.data || response.data.length === 0) {
      tbody.append('<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    response.data.forEach(function(item) {
      const roleClass = item.role === 'HOTDA' ? 'badge-role-hotda' : 'badge-role-am';
      const statusClass = item.is_registered ? 'badge-status-registered' : 'badge-status-not-registered';
      const statusText = item.is_registered ? 'Terdaftar' : 'Belum Terdaftar';
      const teldaDisplay = item.role === 'HOTDA' ? (item.telda_nama || '-') : '-';

      let divisiBadges = '';
      if (item.divisi && item.divisi.length > 0) {
        divisiBadges = '<br>';
        item.divisi.forEach((div) => {
          const kodeRingkas = div.kode.substring(0, 3).toUpperCase();
          divisiBadges += `<span class="badge-div ${kodeRingkas.toLowerCase()}">${kodeRingkas}</span> `;
        });
      }

      const row = `
        <tr>
          <td><input type="checkbox" class="row-checkbox-data-am" data-id="${item.id}"></td>
          <td>
            <strong style="font-size: 1rem; font-weight: 700;">${item.nama}</strong><br>
            <small class="text-muted">${item.nik}</small>
            ${divisiBadges}
          </td>
          <td>${item.witel_nama}</td>
          <td><span class="${roleClass}">${item.role}</span></td>
          <td>${teldaDisplay}</td>
          <td><span class="${statusClass}">${statusText}</span></td>
          <td class="text-center">
            <div class="action-buttons">
              <button class="btn btn-sm btn-warning" onclick="editDataAM(${item.id})" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteDataAM(${item.id})" title="Hapus">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
      tbody.append(row);
    });
  }

  function renderDataCC(response) {
    const tbody = $('#tableDataCC');
    tbody.empty();

    if (!response || !response.data || response.data.length === 0) {
      tbody.append('<tr><td colspan="4" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    response.data.forEach(function(item) {
      const row = `
        <tr>
          <td><input type="checkbox" class="row-checkbox-data-cc" data-id="${item.id}"></td>
          <td>${item.nama}</td>
          <td>${item.nipnas}</td>
          <td class="text-center">
            <div class="action-buttons">
              <button class="btn btn-sm btn-warning" onclick="editDataCC(${item.id})" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteDataCC(${item.id})" title="Hapus">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
      tbody.append(row);
    });
  }

  // ========================================
  // PAGINATION
  // ========================================
  function renderPagination(response) {
    const container = currentTab === 'tab-cc-revenue' ? $('#paginationRevenueCC') :
                      currentTab === 'tab-am-revenue' ? $('#paginationRevenueAM') :
                      currentTab === 'tab-data-am' ? $('#paginationDataAM') : $('#paginationDataCC');

    container.empty();

    const from = response.from || 0;
    const to = response.to || 0;
    const total = response.total || 0;
    const currentPageNum = response.current_page || 1;
    const lastPage = response.last_page || 1;

    const info = `<div class="info">Menampilkan ${from}-${to} dari ${total} hasil</div>`;

    let pages = '<div class="pages">';
    if (currentPageNum > 1) {
      pages += `<button class="pager" data-page="${currentPageNum - 1}">‹</button>`;
    }

    const startPage = Math.max(1, currentPageNum - 2);
    const endPage = Math.min(lastPage, currentPageNum + 2);

    for (let i = startPage; i <= endPage; i++) {
      const activeClass = i === currentPageNum ? 'active' : '';
      pages += `<button class="pager ${activeClass}" data-page="${i}">${i}</button>`;
    }

    if (currentPageNum < lastPage) {
      pages += `<button class="pager" data-page="${currentPageNum + 1}">›</button>`;
    }
    pages += '</div>';

    const perPageSelect = `
      <div class="perpage">
        <label>Baris</label>
        <select class="form-select small" id="perPageSelect">
          <option value="25" ${perPage === 25 ? 'selected' : ''}>25</option>
          <option value="50" ${perPage === 50 ? 'selected' : ''}>50</option>
          <option value="75" ${perPage === 75 ? 'selected' : ''}>75</option>
          <option value="100" ${perPage === 100 ? 'selected' : ''}>100</option>
        </select>
      </div>
    `;

    container.append(info + pages + perPageSelect);

    container.find('.pager[data-page]').click(function() {
      const page = parseInt($(this).data('page'));
      if (page > 0 && page <= response.last_page && page !== currentPageNum) {
        currentPage = page;
        loadData();
      }
    });

    container.find('#perPageSelect').change(function() {
      perPage = parseInt($(this).val());
      currentPage = 1;
      loadData();
    });
  }

  // ========================================
  // UPDATE BADGE COUNTER
  // ========================================
  function updateBadge(tabId, count) {
    const badgeMapping = {
      'tab-cc-revenue': 'badge-cc-rev',
      'tab-am-revenue': 'badge-am-rev',
      'tab-data-am': 'badge-data-am',
      'tab-data-cc': 'badge-cc'
    };

    const badgeId = badgeMapping[tabId];
    if (badgeId) {
      $('#' + badgeId).text(count);
    }
  }

  // ========================================
  // TAB SWITCHING
  // ========================================
  $('.tab-btn').click(function() {
    const tabId = $(this).data('tab');
    switchTab(tabId);
  });

  function switchTab(tabId) {
    $('.tab-btn').removeClass('active');
    $(`.tab-btn[data-tab="${tabId}"]`).addClass('active');
    $('.tab-panel').removeClass('active');
    $(`#${tabId}`).addClass('active');

    currentTab = tabId;
    currentPage = 1;

    if (tabId === 'tab-cc-revenue' || tabId === 'tab-am-revenue') {
      $('#filterPeriodeGroup').show();
    } else {
      $('#filterPeriodeGroup').hide();
    }

    loadData();
  }

  // ========================================
  // FILTER HANDLERS
  // ========================================
  $('#searchForm').submit(function(e) {
    e.preventDefault();
    currentFilters.search = $('#searchInput').val();
    currentPage = 1;
    loadData();
  });

  $('#btn-apply-filter').click(function() {
    currentFilters.witel_id = $('#filterWitel').val();
    currentFilters.divisi_id = $('#filterDivisi').val();
    currentFilters.segment_id = $('#filter-segment').val();
    currentPage = 1;
    loadData();
  });

  $('#btn-reset-filter').click(function() {
    $('#searchInput').val('');
    $('#filterWitel').val('all');
    $('#filterDivisi').val('all');
    $('#filter-segment').val('all');
    $('#filter-date').value = '';

    currentFilters = {
      search: '',
      witel_id: 'all',
      divisi_id: 'all',
      segment_id: 'all',
      periode: '',
      tipe_revenue: 'REGULER',
      role: 'all'
    };

    currentPage = 1;
    loadData();
  });

  $('.seg-btn[data-revtype]').click(function() {
    $('.seg-btn[data-revtype]').removeClass('active');
    $(this).addClass('active');
    currentFilters.tipe_revenue = $(this).data('revtype');
    currentPage = 1;
    loadData();
  });

  // ✅ FIX #2: AM Mode Toggle with TELDA Column Visibility
  $('.am-btn[data-mode]').click(function() {
    $('.am-btn[data-mode]').removeClass('active');
    $(this).addClass('active');
    const mode = $(this).data('mode');
    currentFilters.role = mode;

    // ✅ TELDA column always visible (data will show "-" for AM role)
    // No need to hide/show column anymore

    currentPage = 1;
    loadData();
  });

// ========================================
  // ✅ FIXED: 2-STEP IMPORT WITH PREVIEW
  // ========================================

  // Type selector
  $('.type-btn').click(function() {
    $('.type-btn').removeClass('active');
    $(this).addClass('active');

    $('.imp-panel').removeClass('active');
    const target = $(this).data('imp');
    $(`#${target}`).addClass('active');
  });

  // Form submissions
  $('#formDataCC, #formDataAM').submit(function(e) {
    e.preventDefault();
    currentFormData = new FormData($(this)[0]);
    currentImportType = currentFormData.get('import_type');

    console.log('📤 Submitting', currentImportType);
    handleImportPreview(currentFormData, currentImportType);
  });

  // ✅ FIXED: Revenue CC Form Submit Handler
  $('#formRevenueCC').submit(function(e) {
    e.preventDefault();

    currentFormData = new FormData($(this)[0]);
    currentImportType = currentFormData.get('import_type');

    // ✅ FIXED: Menggunakan ID selector yang BENAR
    const year = $('#import-cc-year').val();
    const month = $('#import-cc-month').val();
    const divisi = $('#revCCDivisiImport').val();        // ✅ FIXED: ID yang benar
    const jenisData = $('#revCCJenisDataImport').val();  // ✅ FIXED: ID yang benar

    // ✅ Debug log untuk validasi
    console.log('📋 Revenue CC Form Values:', {
      year: year,
      month: month,
      divisi_id: divisi,
      jenis_data: jenisData,
      file: currentFormData.get('file')?.name
    });

    // Validation 1: Periode
    if (!year || !month) {
      alert('❌ Pilih Periode terlebih dahulu!');
      console.error('Validation failed: Periode kosong');
      return;
    }

    // Validation 2: Divisi
    if (!divisi || divisi === '') {
      alert('❌ Pilih Divisi terlebih dahulu!');
      console.error('Validation failed: Divisi kosong', {
        divisi_value: divisi,
        selector: '#revCCDivisiImport'
      });
      return;
    }

    // Validation 3: Jenis Data
    if (!jenisData || jenisData === '') {
      alert('❌ Pilih Jenis Data (Real Revenue/Target Revenue) terlebih dahulu!');
      console.error('Validation failed: Jenis Data kosong', {
        jenisData_value: jenisData,
        selector: '#revCCJenisDataImport'
      });
      return;
    }

    // Set params to FormData
    currentFormData.set('year', year);
    currentFormData.set('month', month);

    console.log('✅ All validations passed');
    console.log('📤 Submitting Revenue CC with:', {
      year: year,
      month: month,
      divisi_id: divisi,
      jenis_data: jenisData,
      file: currentFormData.get('file')?.name
    });

    handleImportPreview(currentFormData, currentImportType);
  });

  // Revenue AM Form Submit Handler
  $('#formRevenueAM').submit(function(e) {
    e.preventDefault();

    currentFormData = new FormData($(this)[0]);
    currentImportType = currentFormData.get('import_type');

    const year = $('#import-am-year').val();
    const month = $('#import-am-month').val();

    if (!year || !month) {
      alert('❌ Pilih Periode terlebih dahulu!');
      return;
    }

    currentFormData.set('year', year);
    currentFormData.set('month', month);

    console.log('📤 Submitting Revenue AM with:', {
      year: year,
      month: month,
      file: currentFormData.get('file')?.name
    });

    handleImportPreview(currentFormData, currentImportType);
  });

  function handleImportPreview(formData, importType) {
    console.log('📤 Sending to /import/preview:');
    for (let [key, value] of formData.entries()) {
      if (value instanceof File) {
        console.log(`  ${key}: ${value.name} (${value.size} bytes)`);
      } else {
        console.log(`  ${key}: ${value}`);
      }
    }

    showLoading('Memproses file...');

    $.ajax({
      url: '/revenue-data/import/preview',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        hideLoading();

        if (response.success) {
          previewData = response.data;
          currentSessionId = response.session_id;
          console.log('✅ Preview loaded, session_id:', currentSessionId);

          showPreviewModal(previewData, importType);

          bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        hideLoading();
        console.error('❌ Preview failed:', xhr.responseJSON);
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  }

  function showPreviewModal(data, importType) {
    const summaryHTML = `
      <div class="preview-card new">
        <div class="icon"><i class="fa-solid fa-plus"></i></div>
        <h3>${data.summary.new_count || 0}</h3>
        <p>Data Baru</p>
      </div>
      <div class="preview-card update">
        <div class="icon"><i class="fa-solid fa-edit"></i></div>
        <h3>${data.summary.update_count || 0}</h3>
        <p>Akan Di-update</p>
      </div>
      <div class="preview-card conflict">
        <div class="icon"><i class="fa-solid fa-exclamation-triangle"></i></div>
        <h3>${data.summary.error_count || 0}</h3>
        <p>Error/Konflik</p>
      </div>
    `;
    $('#previewSummary').html(summaryHTML);

    const tableBody = $('#previewTableBody');
    tableBody.empty();

    data.rows.forEach((row, index) => {
      const statusClass = row.status || 'new';
      const statusText = {
        'new': 'Baru',
        'update': 'Update',
        'error': 'Error',
        'skip': 'Skip'
      }[statusClass] || 'Baru';

      let dataDisplay = '';
      let valueDisplay = '';

      if (importType === 'data_cc') {
        dataDisplay = `<strong>${row.data.NIPNAS || '-'}</strong><br><small>${row.data.STANDARD_NAME || '-'}</small>`;
        valueDisplay = row.old_data ? `
          <div class="value-comparison">
            <span class="value-old">${row.old_data.nama || '-'}</span>
            <span class="value-new">${row.data.STANDARD_NAME || '-'}</span>
          </div>
        ` : row.data.STANDARD_NAME || '-';
      } else if (importType === 'data_am') {
        dataDisplay = `<strong>${row.data.NIK || '-'}</strong><br><small>${row.data.NAMA_AM || '-'}</small>`;
        valueDisplay = `${row.data.ROLE || '-'} | ${row.data.WITEL || '-'}`;
      } else if (importType === 'revenue_cc') {
        dataDisplay = `<strong>${row.data.NIPNAS || '-'}</strong><br><small>${row.data.LSEGMENT_HO || '-'}</small>`;
        valueDisplay = row.data.REVENUE_SOLD ? `Rp ${parseFloat(row.data.REVENUE_SOLD).toLocaleString('id-ID')}` :
                       row.data.REVENUE_BILL ? `Rp ${parseFloat(row.data.REVENUE_BILL).toLocaleString('id-ID')}` : '-';
      } else if (importType === 'revenue_am') {
        dataDisplay = `<strong>${row.data.NIK_AM || '-'}</strong><br><small>${row.data.NIPNAS || '-'}</small>`;
        valueDisplay = `${row.data.PROPORSI || 0}%`;
      }

      const rowHTML = `
        <tr data-row-index="${index}" data-status="${statusClass}" class="${statusClass === 'error' ? 'table-danger' : ''}">
          <td>
            <input type="checkbox" class="preview-row-checkbox"
                   data-index="${index}"
                   ${statusClass !== 'error' ? 'checked' : ''}
                   ${statusClass === 'error' ? 'disabled' : ''}>
          </td>
          <td><span class="status-badge ${statusClass}">${statusText}</span></td>
          <td>${dataDisplay}</td>
          <td>
            ${valueDisplay}
            ${row.error ? `<br><small class="text-danger"><i class="fa-solid fa-warning me-1"></i>${row.error}</small>` : ''}
          </td>
        </tr>
      `;
      tableBody.append(rowHTML);
    });

    updateSelectedCount();

    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
  }

  $('#selectAllPreview').on('change', function() {
    $('.preview-row-checkbox:not(:disabled)').prop('checked', this.checked);
    updateSelectedCount();
  });

  $(document).on('change', '.preview-row-checkbox', function() {
    updateSelectedCount();
  });

  $('#btnSelectAll').click(function() {
    $('.preview-row-checkbox:not(:disabled)').prop('checked', true);
    $('#selectAllPreview').prop('checked', true);
    updateSelectedCount();
  });

  $('#btnDeselectAll').click(function() {
    $('.preview-row-checkbox:not(:disabled)').prop('checked', false);
    $('#selectAllPreview').prop('checked', false);
    updateSelectedCount();
  });

  $('#btnSelectNew').click(function() {
    $('.preview-row-checkbox:not(:disabled)').prop('checked', false);
    $('tr[data-status="new"] .preview-row-checkbox').prop('checked', true);
    updateSelectedCount();
  });

  $('#btnSelectUpdates').click(function() {
    $('.preview-row-checkbox:not(:disabled)').prop('checked', false);
    $('tr[data-status="update"] .preview-row-checkbox').prop('checked', true);
    updateSelectedCount();
  });

  function updateSelectedCount() {
    const count = $('.preview-row-checkbox:checked').length;
    $('#selectedCount').text(count);
    $('#btnExecuteImport').prop('disabled', count === 0);
  }

  $('#btnExecuteImport').click(function() {
    const selectedIndexes = $('.preview-row-checkbox:checked').map(function() {
      return $(this).data('index');
    }).get();

    if (selectedIndexes.length === 0) {
      alert('Pilih minimal 1 data untuk diimport');
      return;
    }

    if (!confirm(`Import ${selectedIndexes.length} data terpilih?`)) {
      return;
    }

    executeImport(selectedIndexes);
  });

  function executeImport(selectedIndexes) {
    showLoading('Mengimport data...');

    const payload = {
      session_id: currentSessionId,
      selected_rows: selectedIndexes,
      import_type: currentImportType
    };

    console.log('✅ Executing import with payload:', payload);

    $.ajax({
      url: '/revenue-data/import/execute',
      method: 'POST',
      data: JSON.stringify(payload),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        hideLoading();

        if (response.success) {
          console.log('✅ Import executed successfully');

          bootstrap.Modal.getInstance(document.getElementById('previewModal')).hide();

          showImportResult(response);

          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        hideLoading();
        console.error('❌ Import execution failed:', xhr);
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  }

  function showLoading(text) {
    $('#loadingText').text(text || 'Memproses...');
    $('#loadingOverlay').addClass('active');
  }

  function hideLoading() {
    $('#loadingOverlay').removeClass('active');
  }

  function showImportResult(response) {
    const stats = response.statistics || {
      total_rows: 0,
      success_count: 0,
      failed_count: 0,
      skipped_count: 0
    };

    const totalRows = stats.total_rows || 0;
    const successCount = stats.success_count || 0;
    const failedCount = stats.failed_count || 0;
    const skippedCount = stats.skipped_count || 0;
    const updatedCount = stats.updated_count || 0;
    const recalculatedCount = stats.recalculated_am_count || 0;

    const successRate = totalRows > 0 ? ((successCount / totalRows) * 100).toFixed(1) : 0;

    let content = `
      <div class="result-modal-stats-container four-cols">
        <div class="result-modal-stat">
          <div class="icon info">
            <i class="fa-solid fa-file-lines"></i>
          </div>
          <div class="content">
            <h4>${totalRows}</h4>
            <p>Total Baris</p>
          </div>
        </div>

        <div class="result-modal-stat">
          <div class="icon success">
            <i class="fa-solid fa-check"></i>
          </div>
          <div class="content">
            <h4>${successCount}</h4>
            <p>Berhasil</p>
          </div>
        </div>

        <div class="result-modal-stat">
          <div class="icon danger">
            <i class="fa-solid fa-xmark"></i>
          </div>
          <div class="content">
            <h4>${failedCount}</h4>
            <p>Gagal</p>
          </div>
        </div>

        <div class="result-modal-stat">
          <div class="icon warning">
            <i class="fa-solid fa-exclamation"></i>
          </div>
          <div class="content">
            <h4>${skippedCount}</h4>
            <p>Diskip</p>
          </div>
        </div>
      </div>

      <div class="progress-bar-custom">
        <div class="progress-bar-fill-custom" style="width: ${successRate}%">
          ${successRate}% Success
        </div>
      </div>
    `;

    if (updatedCount > 0 || recalculatedCount > 0) {
      content += `
        <div class="result-modal-info">
          <h6><i class="fa-solid fa-info-circle me-2"></i>Informasi Tambahan</h6>
          <ul>
            ${updatedCount > 0 ? `<li><strong>${updatedCount}</strong> data existing di-update</li>` : ''}
            ${recalculatedCount > 0 ? `<li><strong>${recalculatedCount}</strong> AM revenues recalculated</li>` : ''}
          </ul>
        </div>
      `;
    }

    if (response.errors && response.errors.length > 0) {
      content += `
        <div class="alert alert-warning mt-3">
          <strong><i class="fa-solid fa-triangle-exclamation me-2"></i>Detail Error:</strong>
          <ul class="mb-0 mt-2">
      `;
      response.errors.slice(0, 10).forEach(err => {
        content += `<li>${err}</li>`;
      });
      if (response.errors.length > 10) {
        content += `<li><em>... dan ${response.errors.length - 10} error lainnya</em></li>`;
      }
      content += `</ul></div>`;
    }

    if (response.error_log_path) {
      $('#btnDownloadErrorLog').attr('href', response.error_log_path).show();
    } else {
      $('#btnDownloadErrorLog').hide();
    }

    $('#resultModalBody').html(content);
    const modal = new bootstrap.Modal(document.getElementById('resultModal'));
    modal.show();
  }

  // ========================================
  // EDIT & DELETE FUNCTIONS
  // ========================================

  window.editRevenueCC = function(id) {
    $.ajax({
      url: `/revenue-data/revenue-cc/${id}`,
      method: 'GET',
      success: function(response) {
        if (response.success) {
          const data = response.data;
          $('#editCCRevenueId').val(data.id);
          $('#editCCNamaCC').val(data.nama_cc);
          $('#editCCTargetRevenue').val(data.target_revenue);
          $('#editCCRealRevenue').val(data.real_revenue);

          const modal = new bootstrap.Modal(document.getElementById('modalEditRevenueCC'));
          modal.show();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };

  window.deleteRevenueCC = function(id) {
    if (!confirm('Hapus Revenue CC ini?\n\nTindakan ini tidak dapat dibatalkan!')) {
      return;
    }

    $.ajax({
      url: `/revenue-data/revenue-cc/${id}`,
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };

  window.editRevenueAM = function(id) {
    $.ajax({
      url: `/revenue-data/revenue-am/${id}`,
      method: 'GET',
      success: function(response) {
        if (response.success) {
          const data = response.data;
          $('#editAMRevenueId').val(data.id);
          $('#editAMNamaAM').val(data.nama_am);
          $('#editAMProporsi').val(data.proporsi);
          $('#editAMTargetRevenue').val(data.target_revenue);
          $('#editAMRealRevenue').val(data.real_revenue);

          const modal = new bootstrap.Modal(document.getElementById('modalEditRevenueAM'));
          modal.show();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };

  window.deleteRevenueAM = function(id) {
    if (!confirm('Hapus Revenue AM ini?\n\nTindakan ini tidak dapat dibatalkan!')) {
      return;
    }

    $.ajax({
      url: `/revenue-data/revenue-am/${id}`,
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };


// ========================================
// ✅ TOGGLE TELDA FIELD - FIXED
// ========================================
function toggleTeldaField(role) {
  const teldaWrapper = document.getElementById('editDataAMTeldaWrapper');
  
  // ✅ FIX: Null check
  if (!teldaWrapper) {
    console.warn('editDataAMTeldaWrapper not found');
    return;
  }

  if (role === 'HOTDA') {
    teldaWrapper.classList.remove('hidden');
    teldaWrapper.style.display = 'block';
  } else {
    teldaWrapper.classList.add('hidden');
    teldaWrapper.style.display = 'none';
  }
}

// ========================================
// ✅ EVENT LISTENER - ROLE CHANGE
// ========================================
$('#editDataAMRole').on('change', function() {
  const role = $(this).val();
  toggleTeldaField(role);
});


// ========================================
// ✅ EDIT DATA AM - FORCE SHOW CONTENT
// ========================================
window.editDataAM = function(id) {
  $.ajax({
    url: `/revenue-data/data-am/${id}`,
    method: 'GET',
    success: function(response) {
      if (!response.success) {
        alert('Error: ' + response.message);
        return;
      }

      const data = response.data;
      const modalEl = document.getElementById('modalEditDataAM');
      
      if (!modalEl) {
        console.error('❌ Modal not found!');
        return;
      }

      const modal = new bootstrap.Modal(modalEl);

      $(modalEl).one('shown.bs.modal', function() {
        setTimeout(function() {
          console.log('✅ Populating fields with data:', data);

          // ✅ FORCE SHOW TAB CONTENT
          $('#tab-edit-data').addClass('show active');
          $('#tab-change-password').removeClass('show active');
          
          // ✅ FORCE SHOW TABS NAV (jika registered)
          if (data.is_registered) {
            $('#editDataAMTabs').show().css('display', 'flex');
          } else {
            $('#editDataAMTabs').hide();
          }

          // Set values
          $('#editDataAMId').val(data.id);
          $('#changePasswordAMId').val(data.id);
          $('#editDataAMNama').val(data.nama);
          $('#editDataAMNik').val(data.nik);
          $('#editDataAMRole').val(data.role);
          $('#editDataAMWitel').val(data.witel_id);
          $('#editDataAMTelda').val(data.telda_id || '');

          // Divisi
          if ($('#divisiButtonGroup').children().length === 0) {
            initDivisiButtonGroup();
          }
          
          if (data.divisi && Array.isArray(data.divisi)) {
            const divisiIds = data.divisi.map(d => d.id);
            setTimeout(() => setSelectedDivisi(divisiIds), 100);
          }

          // TELDA toggle
          toggleTeldaField(data.role);

          console.log('✅ All fields populated!');
        }, 200);
      });

      modal.show();
    },
    error: function(xhr) {
      console.error('❌ AJAX Error:', xhr);
      alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
    }
  });
};

  window.editDataCC = function(id) {
    $.ajax({
      url: `/revenue-data/data-cc/${id}`,
      method: 'GET',
      success: function(response) {
        if (response.success) {
          const data = response.data;
          $('#editDataCCId').val(data.id);
          $('#editDataCCNama').val(data.nama);
          $('#editDataCCNipnas').val(data.nipnas);

          const modal = new bootstrap.Modal(document.getElementById('modalEditDataCC'));
          modal.show();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };

  window.deleteDataCC = function(id) {
    if (!confirm('Hapus Data CC ini?\n\nTindakan ini tidak dapat dibatalkan!')) {
      return;
    }

    $.ajax({
      url: `/revenue-data/data-cc/${id}`,
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };

  // ========================================
  // FORM SUBMIT HANDLERS
  // ========================================

  $('#formEditRevenueCC').on('submit', function(e) {
    e.preventDefault();
    const id = $('#editCCRevenueId').val();
    const data = {
      target_revenue: $('#editCCTargetRevenue').val(),
      real_revenue: $('#editCCRealRevenue').val()
    };

    $.ajax({
      url: `/revenue-data/revenue-cc/${id}`,
      method: 'PUT',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          bootstrap.Modal.getInstance(document.getElementById('modalEditRevenueCC')).hide();
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  });

  $('#formEditRevenueAM').on('submit', function(e) {
    e.preventDefault();
    const id = $('#editAMRevenueId').val();
    const data = {
      proporsi: $('#editAMProporsi').val(),
      target_revenue: $('#editAMTargetRevenue').val(),
      real_revenue: $('#editAMRealRevenue').val()
    };

    $.ajax({
      url: `/revenue-data/revenue-am/${id}`,
      method: 'PUT',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          bootstrap.Modal.getInstance(document.getElementById('modalEditRevenueAM')).hide();
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  });

  $('#formEditDataAM').on('submit', function(e) {
    e.preventDefault();
    const id = $('#editDataAMId').val();

    const selectedDivisi = [];
    $('#divisiHiddenInputs input[name="divisi_ids[]"]').each(function() {
      selectedDivisi.push($(this).val());
    });

    const data = {
      nama: $('#editDataAMNama').val(),
      nik: $('#editDataAMNik').val(),
      role: $('#editDataAMRole').val(),
      witel_id: $('#editDataAMWitel').val(),
      telda_id: $('#editDataAMTelda').val() || null,
      divisi_ids: selectedDivisi
    };

    $.ajax({
      url: `/revenue-data/data-am/${id}`,
      method: 'PUT',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          bootstrap.Modal.getInstance(document.getElementById('modalEditDataAM')).hide();
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  });

  $('#formChangePasswordAM').on('submit', function(e) {
    e.preventDefault();
    const id = $('#changePasswordAMId').val();
    const password = $('#newPassword').val();
    const confirmPassword = $('#confirmPassword').val();

    if (password !== confirmPassword) {
      alert('Password dan konfirmasi password tidak cocok!');
      return;
    }

    const data = {
      password: password,
      password_confirmation: confirmPassword
    };

    $.ajax({
      url: `/revenue-data/data-am/${id}/change-password`,
      method: 'POST',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          $('#formChangePasswordAM')[0].reset();
          bootstrap.Modal.getInstance(document.getElementById('modalEditDataAM')).hide();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  });

  $('#formEditDataCC').on('submit', function(e) {
    e.preventDefault();
    const id = $('#editDataCCId').val();
    const data = {
      nama: $('#editDataCCNama').val(),
      nipnas: $('#editDataCCNipnas').val()
    };

    $.ajax({
      url: `/revenue-data/data-cc/${id}`,
      method: 'PUT',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          bootstrap.Modal.getInstance(document.getElementById('modalEditDataCC')).hide();
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  });

  // ========================================
  // UTILITY FUNCTIONS
  // ========================================
  function formatCurrency(value) {
    if (!value) return 'Rp 0';
    return 'Rp ' + parseFloat(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });
  }

  function showAlert(message, type) {
    alert(message);
  }

  // ========================================
  // INITIALIZATION
  // ========================================
  loadFilterOptions();
  loadData();

});
</script>
@endpush