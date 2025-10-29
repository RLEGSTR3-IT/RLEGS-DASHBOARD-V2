@extends('layouts.main')

@section('title', 'Revenue RLEGS')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/revenue.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Additional styles for result modal */
<<<<<<< HEAD
        .result-modal-stats-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

=======
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
        .result-modal-stat {
            display: flex;
            align-items: center;
            gap: 1rem;
<<<<<<< HEAD
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

=======
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
        .result-modal-stat .icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
<<<<<<< HEAD
            flex-shrink: 0;
=======
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
        }
        .result-modal-stat .icon.success { background: #d4edda; color: #155724; }
        .result-modal-stat .icon.danger { background: #f8d7da; color: #721c24; }
        .result-modal-stat .icon.warning { background: #fff3cd; color: #856404; }
        .result-modal-stat .icon.info { background: #d1ecf1; color: #0c5460; }
<<<<<<< HEAD
        .result-modal-stat .content { flex: 1; }
        .result-modal-stat .content h4 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: bold;
            white-space: nowrap;
        }
        .result-modal-stat .content p { margin: 0; color: #6c757d; font-size: 0.9rem; }

=======
        .result-modal-stat .content h4 { margin: 0; font-size: 1.5rem; font-weight: bold; }
        .result-modal-stat .content p { margin: 0; color: #6c757d; }
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
        .progress-bar-custom {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin: 1.5rem 0;
        }
        .progress-bar-fill-custom {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: width 0.5s ease;
        }
<<<<<<< HEAD

        /* Better badge colors for Role & Status */
        .badge-role-am {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-role-hotda {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-status-registered {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-status-not-registered {
            background: #6c757d;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Checkbox column */
        .table thead th:first-child,
        .table tbody td:first-child {
            width: 48px !important;
            min-width: 48px !important;
            text-align: center !important;
            padding: 0.5rem !important;
        }

        .table thead th:first-child input[type="checkbox"],
        .table tbody td:first-child input[type="checkbox"] {
            width: 18px !important;
            height: 18px !important;
            cursor: pointer !important;
            display: inline-block !important;
            margin: 0 auto !important;
        }

        /* Aksi column wider for buttons */
        .table thead th:last-child,
        .table tbody td:last-child {
            width: 150px !important;
            min-width: 150px !important;
            text-align: center !important;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }

        /* Tab button active state - MERAH bukan biru */
        .tab-btn.active {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            color: white !important;
            border-color: #dc3545 !important;
        }

        .tab-btn.active .badge {
            background: rgba(255, 255, 255, 0.3) !important;
            color: white !important;
        }

        /* Modal form styling */
        .modal-body .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .modal-body .form-control,
        .modal-body .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .modal-body .form-control:focus,
        .modal-body .form-select:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15);
        }

        .modal-body .nav-tabs {
            border-bottom: 2px solid #e0e0e0;
        }

        .modal-body .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }

        .modal-body .nav-tabs .nav-link.active {
            color: #dc3545;
            border-bottom: 3px solid #dc3545;
            background: transparent;
        }

        /* ✨ NEW: Divisi Button Group Styling */
        .divisi-button-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .divisi-toggle-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .divisi-toggle-btn:hover {
            border-color: #cbd5e0;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .divisi-toggle-btn.active {
            color: white;
            border-width: 2px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .divisi-toggle-btn.active::after {
            content: '✓';
            position: absolute;
            top: -8px;
            right: -8px;
            background: white;
            color: inherit;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            border: 2px solid currentColor;
        }

        .divisi-toggle-btn.dps.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }

        .divisi-toggle-btn.dss.active {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-color: #f093fb;
        }

        .divisi-toggle-btn.dgs.active {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border-color: #4facfe;
        }

        /* Hidden helper for form submission */
        .divisi-hidden-container {
            display: none;
        }
=======
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
    </style>
@endsection

@section('content')
<div class="rlegs-container">

    <!-- ===== Page Header / Action Bar ===== -->
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

    <!-- ===== Filters Line ===== -->
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

            <!-- Select asli (tetap ada untuk submit & nilai) -->
            <select class="form-select" id="filter-segment" name="segment">
                <option value="all">Semua Segment</option>
                <!-- nilai akan diisi via JS; opsi statis ini hanya fallback -->
            </select>

            <!-- UI custom bertab -->
            <div class="seg-select" id="segSelect">
                <div class="seg-menu" id="segMenu" role="listbox" aria-labelledby="segBtn">
                <div class="seg-tabs" role="tablist" aria-label="Divisi">
                    <button class="seg-tab active" data-tab="DPS" role="tab" aria-selected="true">DPS</button>
                    <button class="seg-tab" data-tab="DSS" role="tab" aria-selected="false">DSS</button>
                    <button class="seg-tab" data-tab="DGS" role="tab" aria-selected="false">DGS</button>
                </div>

                <div class="seg-panels">
                    <div class="seg-panel active" data-panel="DPS" role="tabpanel">
                    <button class="seg-option all" data-value="all">Semua Segment</button>
                    <button class="seg-option" data-value="FWS">FWS</button>
                    <button class="seg-option" data-value="LMS">LMS</button>
                    <button class="seg-option" data-value="PBS">PBS</button>
                    <button class="seg-option" data-value="RMS">RMS</button>
                    <button class="seg-option" data-value="PCS">PCS</button>
                    <button class="seg-option" data-value="PRS">PRS</button>
                    </div>

                    <div class="seg-panel" data-panel="DSS" role="tabpanel">
                    <button class="seg-option all" data-value="all">Semua Segment</button>
                    <button class="seg-option" data-value="ERS">ERS</button>
                    <button class="seg-option" data-value="FRBS">FRBS</button>
                    <button class="seg-option" data-value="MIS">MIS</button>
                    <button class="seg-option" data-value="TWS">TWS</button>
                    <button class="seg-option" data-value="SBS">SBS</button>
                    </div>

                    <div class="seg-panel" data-panel="DGS" role="tabpanel">
                    <button class="seg-option all" data-value="all">Semua Segment</button>
                    <button class="seg-option" data-value="GPS">GPS</button>
                    <button class="seg-option" data-value="GDS">GDS</button>
                    <button class="seg-option" data-value="GIS">GIS</button>
                    <button class="seg-option" data-value="GRS">GRS</button>
                    </div>
                </div>
                </div>
            </div>
        </div>


<<<<<<< HEAD
        <!-- === Periode: MONTHPICKER (pilih bulan & tahun) === -->
=======
        <!-- === Periode: Datepicker (kalender harian) === -->
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
        <div class="filter-group" id="filterPeriodeGroup">
            <label>Periode</label>
            <input type="text" id="filter-date" class="form-control datepicker-control" placeholder="Pilih bulan & tahun" autocomplete="off" readonly>
            <input type="hidden" id="filter-month" name="month" value="{{ date('m') }}">
            <input type="hidden" id="filter-year"  name="year"  value="{{ date('Y') }}">
        </div>

<<<<<<< HEAD
=======
        <!-- Filter Role (for AM tabs only) -->
        <div class="filter-group" id="filterRoleGroup" style="display: none;">
            <label>Role</label>
            <select class="form-select" id="filterRole">
                <option value="all">Semua</option>
                <option value="AM">AM</option>
                <option value="HOTDA">HOTDA</option>
            </select>
        </div>


>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
        <div class="filter-actions">
            <button class="btn btn-primary" id="btn-apply-filter">
                <i class="fa-solid fa-check me-1"></i>Terapkan
            </button>
            <button class="btn btn-secondary" id="btn-reset-filter">
                <i class="fa-solid fa-rotate-left me-1"></i>Reset
            </button>
        </div>
    </div>

    <!-- ===== Tabs ===== -->
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

    <!-- ===== Tab: Revenue CC ===== -->
    <div id="tab-cc-revenue" class="tab-panel card-shadow active">
        <div class="panel-header">
            <div class="left">
                <h3>Revenue Corporate Customer</h3>
                <p class="muted">Gunakan <i>option button</i> untuk melihat kategori Revenue CC</p>
            </div>
<<<<<<< HEAD
            <div class="right" style="display: flex; gap: 1rem; align-items: center;">
                <button class="btn btn-danger btn-sm" id="btnDeleteSelectedCC" disabled>
                    <i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih
                </button>
                <button class="btn btn-outline-danger btn-sm" id="btnBulkDeleteCC">
                    <i class="fa-solid fa-trash-alt me-2"></i>Hapus Semua
                </button>
                <div class="btn-segmentation" role="group" aria-label="Revenue Type">
                    <button class="seg-btn active" data-revtype="REGULER">Reguler</button>
                    <button class="seg-btn" data-revtype="NGTMA">NGTMA</button>
                    <button class="seg-btn" data-revtype="KOMBINASI">Kombinasi</button>
                </div>
=======
            <div class="btn-segmentation" role="group" aria-label="Revenue Type">
                <button class="seg-btn active" data-revtype="REGULER">Reguler</button>
                <button class="seg-btn" data-revtype="NGTMA">NGTMA</button>
                <button class="seg-btn" data-revtype="KOMBINASI">Kombinasi</button>
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
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
                        <th class="text-end">
                            Revenue
                            <i class="fa-regular fa-circle-question ms-1 text-muted"
                               data-bs-toggle="tooltip"
                               title="Nilai ini menampilkan Revenue sesuai kategori (Reguler/NGTMA/Kombinasi). Hover pada angka untuk detail: Revenue Sold/Bill."></i>
                        </th>
                        <th>Bulan</th>
<<<<<<< HEAD
                        <th class="text-center" style="width: 150px; min-width: 150px;">Aksi</th>
=======
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
                    </tr>
                </thead>
                <tbody id="tableRevenueCC">
                    <tr>
<<<<<<< HEAD
                        <td colspan="7" class="text-center">
=======
                        <td colspan="6" class="text-center">
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
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

    <!-- ===== Tab: Revenue AM ===== -->
    <div id="tab-am-revenue" class="tab-panel card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Revenue Account Manager</h3>
                <p class="muted">Gunakan <i>option button</i> untuk melihat kategori Revenue AM</p>
            </div>
<<<<<<< HEAD
            <div class="right" style="display: flex; gap: 1rem; align-items: center;">
                <button class="btn btn-danger btn-sm" id="btnDeleteSelectedAM" disabled>
                    <i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih
                </button>
                <button class="btn btn-outline-danger btn-sm" id="btnBulkDeleteAM">
                    <i class="fa-solid fa-trash-alt me-2"></i>Hapus Semua
                </button>
                <div class="am-toggles">
                    <div class="btn-toggle" data-role="amMode">
                        <button class="am-btn active" data-mode="all">Semua</button>
                        <button class="am-btn" data-mode="AM">AM</button>
                        <button class="am-btn" data-mode="HOTDA">HOTDA</button>
                    </div>
=======
            <div class="am-toggles">
                <div class="btn-toggle" data-role="amMode">
                    <button class="am-btn active" data-mode="all">Semua</button>
                    <button class="am-btn" data-mode="AM">AM</button>
                    <button class="am-btn" data-mode="HOTDA">HOTDA</button>
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
                </div>
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
                        <th class="text-end">
                            Revenue
                            <i class="fa-regular fa-circle-question ms-1 text-muted"
                               data-bs-toggle="tooltip"
                               title="Nilai revenue mengikuti mode (AM/HOTDA)."></i>
                        </th>
                        <th class="text-end">Achievement</th>
                        <th>Bulan</th>
                        <th class="hotda-col" style="display: none;">TELDA</th>
<<<<<<< HEAD
                        <th class="text-center" style="width: 150px; min-width: 150px;">Aksi</th>
=======
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
                    </tr>
                </thead>
                <tbody id="tableRevenueAM">
                    <tr>
<<<<<<< HEAD
                        <td colspan="9" class="text-center">
=======
                        <td colspan="8" class="text-center">
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
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

    <!-- ===== Tab: Data AM ===== -->
    <div id="tab-data-am" class="tab-panel card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Data Account Manager</h3>
                <p class="muted">Daftar Account Manager yang terdaftar di sistem</p>
            </div>
<<<<<<< HEAD
            <div class="right" style="display: flex; gap: 1rem; align-items: center;">
                <button class="btn btn-danger btn-sm" id="btnDeleteSelectedDataAM" disabled>
                    <i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih
                </button>
                <button class="btn btn-outline-danger btn-sm" id="btnBulkDeleteDataAM">
                    <i class="fa-solid fa-trash-alt me-2"></i>Hapus Semua
                </button>
            </div>
=======
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
        </div>

        <div class="table-wrap">
            <table class="table modern">
                <thead>
                    <tr>
<<<<<<< HEAD
                        <th style="width: 48px; min-width: 48px; text-align: center;"><input type="checkbox" id="selectAllDataAM"></th>
                        <th>Nama AM</th>
=======
                        <th>Nama AM</th>
                        <th>NIK</th>
                        <th>Divisi</th>
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
                        <th>Witel</th>
                        <th>Role</th>
                        <th class="hotda-col" style="display: none;">TELDA</th>
                        <th>Status Registrasi</th>
<<<<<<< HEAD
                        <th class="text-center" style="width: 150px; min-width: 150px;">Aksi</th>
=======
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
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

    <!-- ===== Tab: Data CC ===== -->
    <div id="tab-data-cc" class="tab-panel card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Data Corporate Customer</h3>
                <p class="muted">Detail Corporate Customer</p>
            </div>
            <div class="right" style="display: flex; gap: 1rem; align-items: center;">
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
<<<<<<< HEAD
                        <th class="text-center" style="width: 150px; min-width: 150px;">Aksi</th>
=======
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
                    </tr>
                </thead>
                <tbody id="tableDataCC">
                    <tr>
<<<<<<< HEAD
                        <td colspan="4" class="text-center">
=======
                        <td colspan="2" class="text-center">
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
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

<!-- ========================================
     IMPORT MODAL
     ======================================== -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-file-import me-2"></i>Import Data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <!-- Type Selector -->
        <div class="type-selector">
          <button class="type-btn active" data-imp="imp-data-cc"><i class="fa-solid fa-building me-2"></i>Data CC</button>
          <button class="type-btn" data-imp="imp-data-am"><i class="fa-solid fa-users me-2"></i>Data AM</button>
          <button class="type-btn" data-imp="imp-rev-cc"><i class="fa-solid fa-chart-line me-2"></i>Revenue CC</button>
          <button class="type-btn" data-imp="imp-rev-map"><i class="fa-solid fa-user-tie me-2"></i>Revenue AM</button>
        </div>

        <!-- ====== Form: Data CC ====== -->
<<<<<<< HEAD
        <div id="imp-data-cc" class="imp-panel active">
=======
        <div id="imp-cc" class="imp-panel active">
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
            <form id="formDataCC" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="data_cc">
                <div class="row gx-3 gy-3">
                    <div class="col-md-12">
                        <label class="form-label">Unggah File (.csv)</label>
                        <input type="file" class="form-control" name="file" accept=".csv" required>
<<<<<<< HEAD
                        <small class="text-muted">Kolom wajib: <strong>NIPNAS, NAMA_PELANGGAN</strong></small>
                    </div>
                </div>

=======
                    </div>
                </div>

                <div class="alert note mt-3">
                    <strong>Ketentuan file:</strong>
                    <ul class="mb-0">
                        <li>Format CSV dengan kolom: <strong>STANDARD_NAME, NIP_NAS</strong></li>
                    </ul>
                </div>

>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload me-2"></i>Import</button>
                </div>
            </form>
        </div>

        <!-- ====== Form: Data AM ====== -->
<<<<<<< HEAD
        <div id="imp-data-am" class="imp-panel">
=======
        <div id="imp-am" class="imp-panel">
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
            <form id="formDataAM" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="data_am">
                <div class="row gx-3 gy-3">
                    <div class="col-md-12">
                        <label class="form-label">Unggah File (.csv)</label>
                        <input type="file" class="form-control" name="file" accept=".csv" required>
<<<<<<< HEAD
                        <small class="text-muted">Kolom wajib: <strong>NIK, NAMA_AM, WITEL, ROLE, DIVISI</strong></small>
=======
                        <small class="text-muted">Kolom wajib: <strong>NAMA_AM, NIK, WITEL, DIVISI, ROLE</strong>.</small>
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload me-2"></i>Import</button>
                </div>
            </form>
        </div>

        <!-- ====== Form: Revenue CC ====== -->
        <div id="imp-rev-cc" class="imp-panel">
            <form id="formRevenueCC" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="revenue_cc">
                <div class="row gx-3 gy-3">
                    <div class="col-md-4">
                        <label class="form-label">Unggah File (.csv)</label>
                        <input type="file" class="form-control" name="file" accept=".csv" required>
                    </div>

<<<<<<< HEAD
=======
                    <!-- tambahkan wrapper kolom di sini -->
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
                    <div class="col-md-4">
                        <div class="filter-group">
                        <label>Divisi</label>
                        <select class="form-select" name="divisi_id" id="divisiImport" required>
                            <option value="">Pilih Divisi</option>
                        </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Jenis Data</label>
                        <select class="form-select" name="jenis_data" required>
                            <option value="">Pilih Jenis</option>
                            <option value="revenue">Revenue</option>
                            <option value="target">Target</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload me-2"></i>Import</button>
                </div>
            </form>
        </div>

        <!-- ====== Form: Revenue AM (Mapping) ====== -->
        <div id="imp-rev-map" class="imp-panel">
            <form id="formRevenueAM" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="revenue_am">
                <div class="row gx-3 gy-3">
                    <div class="col-md-6">
                        <label class="form-label">Unggah File (.csv)</label>
                        <input type="file" class="form-control" name="file" accept=".csv" required>
<<<<<<< HEAD
                        <small class="text-muted">Kolom wajib: <strong>YEAR, MONTH, NIPNAS, NIK_AM, PROPORSI</strong> dll.</small>
=======
                        <small class="text-muted">Kolom wajib: </br> <strong>YEAR, MONTH, NIPNAS, NIK_AM, PROPORSI</strong> dll.</small>
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload me-2"></i>Import</button>
                </div>
            </form>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- =================== RESULT MODAL =================== -->
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
                <a href="#" class="btn btn-primary" id="btnDownloadErrorLog" style="display: none;" target="_blank">
                    <i class="fa-solid fa-download me-2"></i>Download Error Log
                </a>
            </div>
        </div>
    </div>
</div>

<<<<<<< HEAD
<!-- ========================================
     EDIT MODALS
     ======================================== -->
<!-- Modal Edit Revenue CC -->
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

<!-- Modal Edit Revenue AM -->
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
                    <div class="mb-3">
                        <label class="form-label">Target Revenue</label>
                        <input type="number" class="form-control" id="editAMTargetRevenue" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Real Revenue</label>
                        <input type="number" class="form-control" id="editAMRealRevenue" required>
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

<!-- ✨ FIXED: Modal Edit Data AM - Improved with Button Group for Divisi -->
<div class="modal fade" id="modalEditDataAM" tabindex="-1" aria-labelledby="modalEditDataAMLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditDataAMLabel">Edit Data AM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Bootstrap Tabs -->
                <ul class="nav nav-tabs mb-3" role="tablist" id="editDataAMTabs">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active"
                                id="tab-edit-data-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#tab-edit-data"
                                type="button"
                                role="tab"
                                aria-controls="tab-edit-data"
                                aria-selected="true">
                            Data AM
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                id="tab-change-password-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#tab-change-password"
                                type="button"
                                role="tab"
                                aria-controls="tab-change-password"
                                aria-selected="false">
                            Ganti Password
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="editDataAMTabContent">
                    <!-- Tab 1: Edit Data AM -->
                    <div class="tab-pane fade show active"
                         id="tab-edit-data"
                         role="tabpanel"
                         aria-labelledby="tab-edit-data-tab">
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

                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" id="editDataAMRole" required>
                                    <option value="">Pilih Role</option>
                                    <option value="AM">AM</option>
                                    <option value="HOTDA">HOTDA</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Witel</label>
                                <select class="form-select" id="editDataAMWitel" required>
                                    <option value="">Pilih Witel</option>
                                    <!-- Options loaded dynamically -->
                                </select>
                            </div>

                            <!-- ✨ NEW: Button Group for Divisi (replaces multiple select) -->
                            <div class="mb-3">
                                <label class="form-label">Divisi</label>
                                <small class="text-muted d-block mb-2">
                                    <i class="fa-solid fa-info-circle me-1"></i>
                                    Klik button untuk memilih divisi (bisa pilih lebih dari satu)
                                </small>
                                <div class="divisi-button-group" id="divisiButtonGroup">
                                    <!-- Buttons will be populated dynamically -->
                                </div>
                                <!-- Hidden inputs for form submission -->
                                <div class="divisi-hidden-container" id="divisiHiddenInputs"></div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa-solid fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </form>
                    </div>

                    <!-- Tab 2: Change Password -->
                    <div class="tab-pane fade"
                         id="tab-change-password"
                         role="tabpanel"
                         aria-labelledby="tab-change-password-tab">
                        <form id="formChangePasswordAM">
                            <input type="hidden" id="changePasswordAMId">

                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="newPassword" required minlength="6">
                                <small class="text-muted">Minimal 6 karakter</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" class="form-control" id="confirmPassword" required minlength="6">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa-solid fa-key me-2"></i>Ganti Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Data CC -->
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

=======
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
<script>
<<<<<<< HEAD
$(document).ready(function() {
  // ========================================
  // STATE MANAGEMENT
=======
document.addEventListener('DOMContentLoaded', () => {

  // ========================================
  // GLOBAL STATE
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
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
<<<<<<< HEAD

  // Store divisi data globally for modal
  let allDivisiData = [];

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

=======

  // ========================================
  // FLATPICKR INITIALIZATION
  // ========================================
  const dateInput   = document.getElementById('filter-date');
  const hiddenMonth = document.getElementById('filter-month');
  const hiddenYear  = document.getElementById('filter-year');

  if (dateInput) {
    const currentYear = new Date().getFullYear();
    let selectedYear  = currentYear;
    let selectedMonth = new Date().getMonth();

>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
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
<<<<<<< HEAD
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

          // Update current filters and reload data
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
  // SEGMENT SELECT CUSTOM UI
  // ========================================
  (function initSegmentSelect() {
    const segSelect = document.getElementById('segSelect');
    if (!segSelect) return;

    const nativeSelect = document.getElementById('filter-segment');
    const segTabs = segSelect.querySelectorAll('.seg-tab');
    const segPanels = segSelect.querySelectorAll('.seg-panel');
    const segOptions = segSelect.querySelectorAll('.seg-option');

    segTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const targetPanel = tab.dataset.tab;
        segTabs.forEach(t => {
=======
        cal.style.width = rect.width + 'px';
      }catch(e){}
    }

    function buildMonthHTML() {
      const { start, end } = getYearWindow();
      const today = new Date();
      const currentM = today.getMonth();
      const currentY = today.getFullYear();

      let html = `<div class="year-header">${selectedYear}</div>`;
      html += '<div class="month-grid">';
      for (let m = 0; m < 12; m++) {
        const isCurrent = (selectedYear === currentY && m === currentM);
        const isSelected = (m === selectedMonth);
        let cls = 'month-item';
        if (isSelected) cls += ' selected';
        if (isCurrent)  cls += ' current-month';
        const label = new Date(selectedYear, m).toLocaleDateString('id-ID', { month: 'short' });
        html += `<div class="${cls}" data-month="${m}">${label}</div>`;
      }
      html += '</div>';

      html += '<div class="year-nav">';
      html += `<button type="button" class="year-btn" data-dir="prev" ${selectedYear <= end ? 'disabled' : ''}>◀</button>`;
      html += `<button type="button" class="year-select-btn">${selectedYear}</button>`;
      html += `<button type="button" class="year-btn" data-dir="next" ${selectedYear >= start ? 'disabled' : ''}>▶</button>`;
      html += '</div>';

      return html;
    }

    function buildYearHTML() {
      const { start, end } = getYearWindow();
      let html = '<div class="year-picker-grid">';
      for (let y = start; y >= end; y--) {
        const cls = (y === selectedYear) ? 'year-pick-item selected' : 'year-pick-item';
        html += `<div class="${cls}" data-year="${y}">${y}</div>`;
      }
      html += '</div>';
      return html;
    }

    function renderView() {
      clampSelectedYear();
      const cal = fpInstance.calendarContainer;
      if (!cal) return;

      if (!isYearView) {
        cal.innerHTML = buildMonthHTML();
        cal.querySelectorAll('.month-item').forEach(item => {
          item.addEventListener('click', () => {
            selectedMonth = parseInt(item.dataset.month, 10);
            const d = new Date(selectedYear, selectedMonth, 1);
            fpInstance.setDate(d, true);
            fpInstance.close();
            updateHiddenFields();
          });
        });

        cal.querySelectorAll('.year-btn').forEach(btn => {
          btn.addEventListener('click', () => {
            const dir = btn.dataset.dir;
            if (dir === 'prev' && !btn.disabled) selectedYear--;
            if (dir === 'next' && !btn.disabled) selectedYear++;
            renderView();
          });
        });

        const ysBtn = cal.querySelector('.year-select-btn');
        if (ysBtn) {
          ysBtn.addEventListener('click', () => {
            isYearView = true;
            renderView();
          });
        }
      } else {
        cal.innerHTML = buildYearHTML();
        cal.querySelectorAll('.year-pick-item').forEach(item => {
          item.addEventListener('click', () => {
            selectedYear = parseInt(item.dataset.year, 10);
            isYearView = false;
            renderView();
          });
        });
      }
    }

    function updateHiddenFields() {
      if (hiddenMonth) hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
      if (hiddenYear)  hiddenYear.value  = String(selectedYear);

      // Update filter periode
      currentFilters.periode = `${selectedYear}-${String(selectedMonth + 1).padStart(2, '0')}`;
    }

    fpInstance = flatpickr(dateInput, {
      altInput: true,
      altFormat: "F Y",
      dateFormat: "Y-m",
      defaultDate: new Date(selectedYear, selectedMonth, 1),
      onReady(_, __, fp) {
        renderView();
        syncCalendarWidth(fp);
        updateHiddenFields();
      },
      onOpen(_, __, fp) {
        isYearView = false;
        renderView();
        syncCalendarWidth(fp);
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
        // Populate Witel
        const witelSelect = $('#filterWitel');
        response.witels.forEach(function(witel) {
          witelSelect.append(`<option value="${witel.id}">${witel.nama}</option>`);
        });

        // Populate Divisi
        const divisiSelect = $('#filterDivisi');
        const divisiImport = $('#divisiImport');
        response.divisions.forEach(function(divisi) {
          divisiSelect.append(`<option value="${divisi.id}">${divisi.nama}</option>`);
          divisiImport.append(`<option value="${divisi.id}">${divisi.nama}</option>`);
        });

        // Populate Segment
        const segmentSelect = $('#filter-segment');
        response.segments.forEach(function(segment) {
          segmentSelect.append(`<option value="${segment.id}">${segment.lsegment_ho}</option>`);
        });

        // Enhance selects
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

        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
      },
      error: function(xhr) {
        console.error('Error loading data:', xhr);
        showAlert('Gagal memuat data', 'danger');
      }
    });
  }

  // ========================================
  // RENDER FUNCTIONS
  // ========================================
  function renderRevenueCC(response) {
    const tbody = $('#tableRevenueCC');
    tbody.empty();

    if (response.data.length === 0) {
      tbody.append('<tr><td colspan="6" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    response.data.forEach(function(item) {
      const divisiClass = item.kode ? item.kode.toLowerCase() : '';
      const row = `
        <tr>
          <td>${item.nama_cc}</td>
          <td><span class="badge-div ${divisiClass}">${item.divisi}</span></td>
          <td>${item.segment || '-'}</td>
          <td class="text-end">${formatCurrency(item.target_revenue)}</td>
          <td class="text-end">
            <span data-bs-toggle="tooltip" title="${item.revenue_type || ''}">
              ${formatCurrency(item.real_revenue)}
            </span>
          </td>
          <td>${item.bulan_display}</td>
        </tr>
      `;
      tbody.append(row);
    });
  }

  function renderRevenueAM(response) {
    const tbody = $('#tableRevenueAM');
    tbody.empty();

    if (response.data.length === 0) {
      tbody.append('<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    response.data.forEach(function(item) {
      const achievementClass = item.achievement_color || 'secondary';
      const showTelda = currentFilters.role === 'HOTDA';

      const row = `
        <tr>
          <td>${item.nama_am}</td>
          <td>${item.divisi || '-'}</td>
          <td>${item.corporate_customer}</td>
          <td class="text-end">${formatCurrency(item.target_revenue)}</td>
          <td class="text-end">${formatCurrency(item.real_revenue)}</td>
          <td class="text-end"><span class="achv ${achievementClass}">${item.achievement_rate}%</span></td>
          <td>${item.bulan_display}</td>
          <td class="hotda-col" style="display: ${showTelda ? 'table-cell' : 'none'};">
            ${item.nama_telda || '-'}
          </td>
        </tr>
      `;
      tbody.append(row);
    });
  }

  function renderDataAM(response) {
    const tbody = $('#tableDataAM');
    tbody.empty();

    if (response.data.length === 0) {
      tbody.append('<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    response.data.forEach(function(item) {
      const statusClass = item.status_registrasi === 'Sudah Terdaftar' ? 'success' : 'secondary';
      const showTelda = item.role === 'HOTDA';

      const row = `
        <tr>
          <td>${item.nama}</td>
          <td>${item.nik}</td>
          <td>${item.divisi || '-'}</td>
          <td>${item.witel}</td>
          <td><span class="badge bg-primary">${item.role}</span></td>
          <td class="hotda-col" style="display: ${showTelda ? 'table-cell' : 'none'};">
            ${item.nama_telda || '-'}
          </td>
          <td><span class="badge bg-${statusClass}">${item.status_registrasi}</span></td>
        </tr>
      `;
      tbody.append(row);
    });
  }

  function renderDataCC(response) {
    const tbody = $('#tableDataCC');
    tbody.empty();

    if (response.data.length === 0) {
      tbody.append('<tr><td colspan="2" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    response.data.forEach(function(item) {
      const row = `
        <tr>
          <td>${item.nama}</td>
          <td>${item.nipnas}</td>
        </tr>
      `;
      tbody.append(row);
    });
  }

  function renderPagination(response) {
    let paginationId = '';
    if (currentTab === 'tab-cc-revenue') paginationId = 'paginationRevenueCC';
    else if (currentTab === 'tab-am-revenue') paginationId = 'paginationRevenueAM';
    else if (currentTab === 'tab-data-am') paginationId = 'paginationDataAM';
    else if (currentTab === 'tab-data-cc') paginationId = 'paginationDataCC';

    const container = $(`#${paginationId}`);
    container.empty();

    const info = `<div class="info">Menampilkan ${response.from || 0}–${response.to || 0} dari ${response.total} hasil</div>`;

    let pages = '<div class="pages">';
    pages += `<button class="pager ${currentPage === 1 ? 'disabled' : ''}" data-page="${currentPage - 1}">
      <i class="fa-solid fa-chevron-left"></i>
    </button>`;

    for (let i = 1; i <= response.last_page; i++) {
      if (i === 1 || i === response.last_page || (i >= currentPage - 2 && i <= currentPage + 2)) {
        pages += `<button class="pager ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
      } else if (i === currentPage - 3 || i === currentPage + 3) {
        pages += '<span>...</span>';
      }
    }

    pages += `<button class="pager ${currentPage === response.last_page ? 'disabled' : ''}" data-page="${currentPage + 1}">
      <i class="fa-solid fa-chevron-right"></i>
    </button>`;
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

    // Pagination click handlers
    container.find('.pager[data-page]').click(function() {
      const page = parseInt($(this).data('page'));
      if (page > 0 && page <= response.last_page && page !== currentPage) {
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

    // Show/hide filters based on tab
    if (tabId === 'tab-am-revenue' || tabId === 'tab-data-am') {
      $('#filterRoleGroup').show();
      $('#filterPeriodeGroup').toggle(tabId === 'tab-am-revenue');
    } else if (tabId === 'tab-cc-revenue') {
      $('#filterRoleGroup').hide();
      $('#filterPeriodeGroup').show();
    } else {
      $('#filterRoleGroup').hide();
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
    currentFilters.role = $('#filterRole').val();
    currentPage = 1;
    loadData();
  });

  $('#btn-reset-filter').click(function() {
    $('#searchInput').val('');
    $('#filterWitel').val('all');
    $('#filterDivisi').val('all');
    $('#filter-segment').val('all');
    $('#filterRole').val('all');
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

  // Revenue type toggle for Revenue CC
  $('.seg-btn[data-revtype]').click(function() {
    $('.seg-btn[data-revtype]').removeClass('active');
    $(this).addClass('active');
    currentFilters.tipe_revenue = $(this).data('revtype');
    currentPage = 1;
    loadData();
  });

  // AM mode toggle for Revenue AM
  $('.am-btn[data-mode]').click(function() {
    $('.am-btn[data-mode]').removeClass('active');
    $(this).addClass('active');
    const mode = $(this).data('mode');
    currentFilters.role = mode;

    // Show/hide Telda column
    if (mode === 'HOTDA') {
      $('.hotda-col').show();
    } else {
      $('.hotda-col').hide();
    }

    currentPage = 1;
    loadData();
  });

  // ========================================
  // IMPORT FUNCTIONALITY
  // ========================================

  // Import modal tab switching
  $('.type-btn').click(function() {
    $('.type-btn').removeClass('active');
    $(this).addClass('active');

    $('.imp-panel').removeClass('active');
    const target = $(this).data('imp');
    $(`#${target}`).addClass('active');
  });

  // Form submissions
  $('#formDataCC').submit(function(e) {
    e.preventDefault();
    handleImport($(this));
  });

  $('#formDataAM').submit(function(e) {
    e.preventDefault();
    handleImport($(this));
  });

  $('#formRevenueCC').submit(function(e) {
    e.preventDefault();
    handleImport($(this));
  });

  $('#formRevenueAM').submit(function(e) {
    e.preventDefault();
    handleImport($(this));
  });

  function handleImport($form) {
    const formData = new FormData($form[0]);
    const submitBtn = $form.find('button[type="submit"]');
    const originalText = submitBtn.html();

    submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Uploading...');

    $.ajax({
      url: '{{ route("revenue.import") }}',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        $('#importModal').modal('hide');
        submitBtn.prop('disabled', false).html(originalText);
        $form[0].reset();

        showImportResult(response);
        loadData(); // Reload current tab data
      },
      error: function(xhr) {
        submitBtn.prop('disabled', false).html(originalText);

        let errorMessage = 'Terjadi kesalahan saat import';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }
        showAlert(errorMessage, 'danger');
      }
    });
  }

  function showImportResult(response) {
    const stats = response.statistics;
    const successRate = stats.total_rows > 0
      ? ((stats.success_count / stats.total_rows) * 100).toFixed(2)
      : 0;

    let html = `
      <div class="text-center mb-4">
        <h4>${response.import_type_label}</h4>
        <p class="text-muted">Import selesai pada ${response.import_time}</p>
        <p class="text-muted">Durasi: ${response.duration}</p>
      </div>

      <div class="progress-bar-custom">
        <div class="progress-bar-fill-custom" style="width: ${successRate}%">
          ${successRate}%
        </div>
      </div>

      <div class="row g-3">
        <div class="col-md-3">
          <div class="result-modal-stat">
            <div class="icon info">
              <i class="fa-solid fa-file-lines"></i>
            </div>
            <div class="content">
              <h4>${stats.total_rows}</h4>
              <p>Total Baris</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="result-modal-stat">
            <div class="icon success">
              <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="content">
              <h4>${stats.success_count}</h4>
              <p>Berhasil</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="result-modal-stat">
            <div class="icon danger">
              <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div class="content">
              <h4>${stats.failed_count}</h4>
              <p>Gagal</p>
            </div>
          </div>
        </div>
    `;

    if (stats.skipped_count !== undefined && stats.skipped_count > 0) {
      html += `
        <div class="col-md-3">
          <div class="result-modal-stat">
            <div class="icon warning">
              <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div class="content">
              <h4>${stats.skipped_count}</h4>
              <p>Diskip</p>
            </div>
          </div>
        </div>
      `;
    }

    html += '</div>';

    if (stats.failed_count > 0 || (stats.skipped_count && stats.skipped_count > 0)) {
      html += `
        <div class="alert alert-warning mt-3">
          <i class="fa-solid fa-exclamation-triangle me-2"></i>
          Beberapa data gagal diimport. Silakan unduh log error untuk detail.
        </div>
      `;
    }

    $('#resultModalBody').html(html);

    // Show/hide error log button
    if (response.error_log_path) {
      $('#btnDownloadErrorLog').attr('href', response.error_log_path).show();
    } else {
      $('#btnDownloadErrorLog').hide();
    }

    $('#resultModal').modal('show');
  }

  // ========================================
  // UTILITY FUNCTIONS
  // ========================================
  function formatCurrency(amount) {
    if (!amount) return 'Rp 0';
    return 'Rp ' + parseFloat(amount).toLocaleString('id-ID');
  }

  function showAlert(message, type = 'info') {
    alert(message); // Replace with better notification system if available
  }

  // ========================================
  // CUSTOM SEGMENT SELECT
  // ========================================
  (function initSegmentSelect(){
    const wrap = document.getElementById('segSelect');
    if (!wrap) return;

    const menu = wrap.querySelector('#segMenu');
    const segmentSelect = document.getElementById('filter-segment');
    const panels = menu.querySelector('.seg-panels');

    menu.querySelectorAll('.seg-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        menu.querySelectorAll('.seg-tab').forEach(t => {
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
          t.classList.remove('active');
          t.setAttribute('aria-selected', 'false');
        });
        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');

<<<<<<< HEAD
        segPanels.forEach(panel => {
          panel.classList.remove('active');
        });
        const activePanel = segSelect.querySelector(`.seg-panel[data-panel="${targetPanel}"]`);
        if (activePanel) activePanel.classList.add('active');
      });
    });

    segOptions.forEach(option => {
      option.addEventListener('click', () => {
        const value = option.dataset.value;
        nativeSelect.value = value;
        nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
      });
=======
        const targetPanel = tab.dataset.tab;
        panels.querySelectorAll('.seg-panel').forEach(p => p.classList.remove('active'));
        panels.querySelector(`[data-panel="${targetPanel}"]`)?.classList.add('active');
      });
    });

    panels.addEventListener('click', (e) => {
      const opt = e.target.closest('.seg-option');
      if (!opt) return;
      const val = opt.dataset.value ?? 'all';

      segmentSelect.value = val;
      segmentSelect.dispatchEvent(new Event('change', {bubbles:true}));

      panels.querySelectorAll('.seg-option[aria-selected]').forEach(el => el.removeAttribute('aria-selected'));
      opt.setAttribute('aria-selected','true');
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
    });
  })();

  // ========================================
<<<<<<< HEAD
  // CUSTOM SELECT ENHANCEMENT
  // ========================================
  function enhanceNativeSelect(native, opts = {}) {
=======
  // ENHANCE NATIVE SELECTS (Witel & Divisi)
  // ========================================
  function enhanceNativeSelect(native, { inModal = false } = {}) {
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
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
<<<<<<< HEAD
  // ✨ NEW: DIVISI BUTTON GROUP HANDLER
  // ========================================
  function initDivisiButtonGroup() {
    const buttonGroup = document.getElementById('divisiButtonGroup');
    const hiddenContainer = document.getElementById('divisiHiddenInputs');

    if (!buttonGroup || !hiddenContainer) return;

    // Clear existing content
    buttonGroup.innerHTML = '';
    hiddenContainer.innerHTML = '';

    // Create buttons for each divisi
    allDivisiData.forEach(divisi => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = `divisi-toggle-btn ${divisi.kode.toLowerCase()}`;
      btn.dataset.divisiId = divisi.id;
      btn.dataset.divisiKode = divisi.kode;
      btn.textContent = divisi.kode;

      btn.addEventListener('click', function() {
        this.classList.toggle('active');
        updateHiddenInputs();
      });

      buttonGroup.appendChild(btn);
    });
  }

  function updateHiddenInputs() {
    const hiddenContainer = document.getElementById('divisiHiddenInputs');
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
    // Clear all active states
    document.querySelectorAll('.divisi-toggle-btn').forEach(btn => {
      btn.classList.remove('active');
    });

    // Set active states for selected divisi
    divisiIds.forEach(id => {
      const btn = document.querySelector(`.divisi-toggle-btn[data-divisi-id="${id}"]`);
      if (btn) {
        btn.classList.add('active');
      }
    });

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

  // Bulk Delete All Handler
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
        // Populate Witel
        const witelSelect = $('#filterWitel');
        response.witels.forEach(function(witel) {
          witelSelect.append(`<option value="${witel.id}">${witel.nama}</option>`);
        });

        // Populate Divisi
        const divisiSelect = $('#filterDivisi');
        const divisiImport = $('#divisiImport');
        response.divisions.forEach(function(divisi) {
          divisiSelect.append(`<option value="${divisi.id}">${divisi.nama}</option>`);
          divisiImport.append(`<option value="${divisi.id}">${divisi.nama}</option>`);
        });

        // Populate Segment
        const segmentSelect = $('#filter-segment');
        response.segments.forEach(function(segment) {
          segmentSelect.append(`<option value="${segment.id}">${segment.lsegment_ho}</option>`);
        });

        // Populate modal Edit Data AM - Witel
        const editWitelSelect = $('#editDataAMWitel');
        editWitelSelect.empty();
        editWitelSelect.append('<option value="">Pilih Witel</option>');
        response.witels.forEach(function(witel) {
          editWitelSelect.append(`<option value="${witel.id}">${witel.nama}</option>`);
        });

        // ✨ Store divisi data globally for button group
        allDivisiData = response.divisions;

        // Initialize divisi button group
        initDivisiButtonGroup();

        // Enhance selects
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

        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
      },
      error: function(xhr) {
        console.error('❌ Error loading data for tab:', currentTab, xhr);
        console.error('URL:', url);
        console.error('Params:', params);
        showAlert('Gagal memuat data: ' + (xhr.responseJSON?.message || xhr.statusText), 'danger');
      }
    });
  }

  // ========================================
  // RENDER FUNCTIONS
  // ========================================
  function renderRevenueCC(response) {
    console.log('🔍 renderRevenueCC called with:', response);
    const tbody = $('#tableRevenueCC');
    tbody.empty();

    if (!response || !response.data || response.data.length === 0) {
      console.log('⚠️ No data for Revenue CC');
      tbody.append('<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    console.log(`✅ Rendering ${response.data.length} Revenue CC rows`);
    response.data.forEach(function(item, index) {
      console.log(`  Row ${index + 1}:`, item);

      const divisiDisplay = item.divisi_kode || '-';
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

    console.log('✅ Revenue CC rows appended to tbody');
    $('[data-bs-toggle="tooltip"]').tooltip();
  }

  function renderRevenueAM(response) {
    console.log('🔍 renderRevenueAM called with:', response);
    const tbody = $('#tableRevenueAM');
    tbody.empty();

    if (!response || !response.data || response.data.length === 0) {
      console.log('⚠️ No data for Revenue AM');
      tbody.append('<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    console.log(`✅ Rendering ${response.data.length} Revenue AM rows`);
    response.data.forEach(function(item, index) {
      console.log(`  Row ${index + 1}:`, item);

      const role = item.role || 'AM';
      const roleClass = role === 'HOTDA' ? 'badge-role-hotda' : 'badge-role-am';

      const divisiKode = item.divisi_kode || item.divisi || '-';
      const divisiClass = divisiKode !== '-' ? `badge-div ${divisiKode.toLowerCase()}` : '';

      const teldaDisplay = item.telda_nama || '-';
      const achievementPercent = item.achievement ? parseFloat(item.achievement).toFixed(2) : '0.00';

      const row = `
        <tr>
          <td><input type="checkbox" class="row-checkbox-am" data-id="${item.id}"></td>
          <td>
            <strong style="font-size: 1rem; font-weight: 700;">${item.nama_am}</strong><br>
            <small>
              <span class="${roleClass}">${role}</span>
              ${divisiKode !== '-' ? `<span class="${divisiClass}" style="margin-left: 4px;">${divisiKode}</span>` : ''}
            </small>
          </td>
          <td>${item.nama_cc}</td>
          <td class="text-end">${formatCurrency(item.target_revenue)}</td>
          <td class="text-end">${formatCurrency(item.real_revenue)}</td>
          <td class="text-end">${achievementPercent}%</td>
          <td>${item.bulan_display}</td>
          <td class="hotda-col" style="${role === 'HOTDA' ? '' : 'display: none;'}">${teldaDisplay}</td>
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

    console.log('✅ Revenue AM rows appended to tbody');
    $('[data-bs-toggle="tooltip"]').tooltip();
  }

  function renderDataAM(response) {
    console.log('🔍 renderDataAM called with:', response);
    const tbody = $('#tableDataAM');
    tbody.empty();

    if (!response || !response.data || response.data.length === 0) {
      console.log('⚠️ No data for Data AM');
      tbody.append('<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    console.log(`✅ Rendering ${response.data.length} Data AM rows`);
    response.data.forEach(function(item, index) {
      console.log(`  Row ${index + 1}:`, item);

      const roleClass = item.role === 'HOTDA' ? 'badge-role-hotda' : 'badge-role-am';
      const statusClass = item.is_registered ? 'badge-status-registered' : 'badge-status-not-registered';
      const statusText = item.is_registered ? 'Terdaftar' : 'Belum Terdaftar';
      const teldaDisplay = item.telda_nama || '-';

      let divisiBadges = '';
      if (item.divisi && item.divisi.length > 0) {
        divisiBadges = '<br>';
        item.divisi.forEach((div, idx) => {
          divisiBadges += `<span class="badge-div ${div.kode.toLowerCase()}">${div.kode}</span> `;
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
          <td class="hotda-col" style="${item.role === 'HOTDA' ? '' : 'display: none;'}">${teldaDisplay}</td>
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

    console.log('✅ Data AM rows appended to tbody');
  }

  function renderDataCC(response) {
    console.log('🔍 renderDataCC called with:', response);
    const tbody = $('#tableDataCC');
    tbody.empty();

    if (!response || !response.data || response.data.length === 0) {
      console.log('⚠️ No data for Data CC');
      tbody.append('<tr><td colspan="4" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    console.log(`✅ Rendering ${response.data.length} Data CC rows`);
    response.data.forEach(function(item, index) {
      console.log(`  Row ${index + 1}:`, item);

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

    console.log('✅ Data CC rows appended to tbody');
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

    // Show/hide periode filter based on tab
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

  $('.am-btn[data-mode]').click(function() {
    $('.am-btn[data-mode]').removeClass('active');
    $(this).addClass('active');
    const mode = $(this).data('mode');
    currentFilters.role = mode;

    if (mode === 'HOTDA') {
      $('.hotda-col').show();
    } else {
      $('.hotda-col').hide();
    }

    currentPage = 1;
    loadData();
  });

  // ========================================
  // IMPORT FUNCTIONALITY
  // ========================================
  $('.type-btn').click(function() {
    $('.type-btn').removeClass('active');
    $(this).addClass('active');

    $('.imp-panel').removeClass('active');
    const target = $(this).data('imp');
    $(`#${target}`).addClass('active');
  });

  $('#formDataCC').submit(function(e) {
    e.preventDefault();
    handleImport($(this));
  });

  $('#formDataAM').submit(function(e) {
    e.preventDefault();
    handleImport($(this));
  });

  $('#formRevenueCC').submit(function(e) {
    e.preventDefault();
    handleImport($(this));
  });

  $('#formRevenueAM').submit(function(e) {
    e.preventDefault();
    handleImport($(this));
  });

  function handleImport($form) {
    const formData = new FormData($form[0]);
    const formId = $form.attr('id');

    let url = '';
    if (formId === 'formDataCC') url = '/revenue-data/import/data-cc';
    else if (formId === 'formDataAM') url = '/revenue-data/import/data-am';
    else if (formId === 'formRevenueCC') url = '/revenue-data/import/revenue-cc';
    else if (formId === 'formRevenueAM') url = '/revenue-data/import/revenue-am';

    $.ajax({
      url: url,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
          $form[0].reset();
          showImportResult(response);
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

  function showImportResult(response) {
    const successCount = response.success_count || 0;
    const failedCount = response.failed_count || 0;
    const totalCount = successCount + failedCount;
    const successRate = totalCount > 0 ? ((successCount / totalCount) * 100).toFixed(1) : 0;

    let content = `
      <div class="result-modal-stats-container">
        <div class="result-modal-stat">
          <div class="icon success">
            <i class="fa-solid fa-check"></i>
          </div>
          <div class="content">
            <h4>${successCount}</h4>
            <p>Baris Berhasil</p>
          </div>
        </div>
        <div class="result-modal-stat">
          <div class="icon danger">
            <i class="fa-solid fa-xmark"></i>
          </div>
          <div class="content">
            <h4>${failedCount}</h4>
            <p>Baris Gagal</p>
          </div>
        </div>
      </div>

      <div class="progress-bar-custom">
        <div class="progress-bar-fill-custom" style="width: ${successRate}%">
          ${successRate}% Success
        </div>
      </div>
    `;

    if (response.errors && response.errors.length > 0) {
      content += `
        <div class="alert alert-warning">
          <strong>Detail Error:</strong>
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

  // ✨ IMPROVED: Edit Data AM function with button group support
  window.editDataAM = function(id) {
    $.ajax({
      url: `/revenue-data/data-am/${id}`,
      method: 'GET',
      success: function(response) {
        if (response.success) {
          const data = response.data;

          // Populate form fields
          $('#editDataAMId').val(data.id);
          $('#changePasswordAMId').val(data.id);
          $('#editDataAMNama').val(data.nama);
          $('#editDataAMNik').val(data.nik);
          $('#editDataAMRole').val(data.role);
          $('#editDataAMWitel').val(data.witel_id);

          // Set selected divisi using button group
          const divisiIds = data.divisi.map(d => d.id);
          setSelectedDivisi(divisiIds);

          // Show modal
          const modal = new bootstrap.Modal(document.getElementById('modalEditDataAM'));
          modal.show();

          // Ensure first tab is active
          const firstTab = document.querySelector('#tab-edit-data-tab');
          if (firstTab) {
            const bsTab = new bootstrap.Tab(firstTab);
            bsTab.show();
          }
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };

  window.deleteDataAM = function(id) {
    if (!confirm('Hapus Data AM ini?\n\nTindakan ini tidak dapat dibatalkan!')) {
      return;
    }

    $.ajax({
      url: `/revenue-data/data-am/${id}`,
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

  // ✨ IMPROVED: Edit Data AM form submit with button group support
  $('#formEditDataAM').on('submit', function(e) {
    e.preventDefault();
    const id = $('#editDataAMId').val();

    // Get selected divisi IDs from hidden inputs
    const selectedDivisi = [];
    $('#divisiHiddenInputs input[name="divisi_ids[]"]').each(function() {
      selectedDivisi.push($(this).val());
    });

    const data = {
      nama: $('#editDataAMNama').val(),
      nik: $('#editDataAMNik').val(),
      role: $('#editDataAMRole').val(),
      witel_id: $('#editDataAMWitel').val(),
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
=======
>>>>>>> 6d36e98642750b9265a9cdc381b093b6aea773d9
  // INITIALIZATION
  // ========================================
  loadFilterOptions();
  loadData();

});
</script>
@endpush