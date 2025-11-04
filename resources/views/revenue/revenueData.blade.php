@extends('layouts.main')

@section('title', 'Revenue RLEGS')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/revenue.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

            <!-- Select asli (hidden, untuk form submit) -->
            <select class="form-select" id="filter-segment" name="segment">
                <option value="all">Semua Segment</option>
                <!-- Options dari database akan diisi via JS -->
            </select>

            <!-- UI custom dengan tabs (akan di-generate via JS) -->
            <div class="seg-select" id="segSelect">
                <!-- Tombol trigger -->
                <button type="button" class="seg-select__btn" aria-haspopup="listbox">
                    <span class="seg-select__label">Semua Segment</span>
                    <span class="seg-select__caret"></span>
                </button>

                <!-- Menu dropdown (akan diisi via JS) -->
                <div class="seg-menu" id="segMenu" role="listbox">
                    <div class="seg-tabs" id="segTabs" role="tablist">
                        <!-- Tabs akan di-generate via JS -->
                    </div>
                    <div class="seg-panels" id="segPanels">
                        <!-- Panels akan di-generate via JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- === Periode: MONTHPICKER (pilih bulan & tahun) === -->
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

    <!-- ===== Tab: Revenue AM ===== -->
    <div id="tab-am-revenue" class="tab-panel card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Revenue Account Manager</h3>
                <p class="muted">Gunakan <i>option button</i> untuk melihat kategori Revenue AM</p>
            </div>
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

    <!-- ===== Tab: Data AM ===== -->
    <div id="tab-data-am" class="tab-panel card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Data Account Manager</h3>
                <p class="muted">Daftar Account Manager yang terdaftar di sistem</p>
            </div>
            <div class="right" style="display: flex; gap: 1rem; align-items: center;">
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

<!-- ========================================
     ✨ MODERN IMPORT MODAL WITH MONTH PICKER
     ======================================== -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="color: white;">
            <i class="fa-solid fa-file-import" style="color: white;"></i>
            Import Data Revenue
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <!-- ✨ Modern Type Selector (SAMA dengan tabs utama) -->
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

        <!-- ✅ FORM 1: Data CC - COMPACT (HAPUS TOMBOL TUTUP) -->
        <div id="imp-data-cc" class="imp-panel active">
            <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                <div class="d-flex align-items-start gap-2">
                    <i class="fa-solid fa-info-circle" style="font-size: 1.25rem; margin-top: 2px;"></i>
                    <div style="flex: 1;">
                        <strong style="display: block; margin-bottom: 0.5rem;">Format CSV:</strong>
                        <div style="font-size: 0.9rem;">
                            <strong>Kolom yang diperlukan:</strong> NIPNAS, STANDARD_NAME &nbsp;|&nbsp;
                            <strong>Update data:</strong> Berlaku jika data revenue dari pelanggan pada periode yang sama sudah ada sebelumnya
                        </div>
                    </div>
                </div>
            </div>

            <form id="formDataCC" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="data_cc">

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fa-solid fa-file-csv"></i>
                        Upload File CSV <span class="required">*</span>
                    </label>
                    <input type="file" class="form-control" name="file" accept=".csv" required>
                    <small class="text-muted">
                        <a href="{{ route('revenue.template', ['type' => 'data-cc']) }}">
                            <i class="fa-solid fa-download me-1"></i>Download Template
                        </a>
                    </small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload"></i>Import Data CC
                </button>
            </form>
        </div>

        <!-- ✅ FORM 2: Data AM - COMPACT (HAPUS TOMBOL TUTUP) -->
        <div id="imp-data-am" class="imp-panel">
            <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                <div class="d-flex align-items-start gap-2">
                    <i class="fa-solid fa-info-circle" style="font-size: 1.25rem; margin-top: 2px;"></i>
                    <div style="flex: 1;">
                        <strong style="display: block; margin-bottom: 0.5rem;">Format CSV:</strong>
                        <div style="font-size: 0.9rem; line-height: 1.6;">
                            <strong>Kolom:</strong> NIK, NAMA_AM, WITEL, ROLE, DIVISI, TELDA<br>
                            <strong>ROLE:</strong> AM atau HOTDA (TELDA wajib untuk HOTDA) &nbsp;|&nbsp;
                            <strong>Update:</strong> Jika NIK sudah ada → data di-update
                        </div>
                    </div>
                </div>
            </div>

            <form id="formDataAM" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="data_am">

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fa-solid fa-file-csv"></i>
                        Upload File CSV <span class="required">*</span>
                    </label>
                    <input type="file" class="form-control" name="file" accept=".csv" required>
                    <small class="text-muted">
                        <a href="{{ route('revenue.template', ['type' => 'data-am']) }}">
                            <i class="fa-solid fa-download me-1"></i>Download Template
                        </a>
                    </small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload"></i>Import Data AM
                </button>
            </form>
        </div>

        <!-- ✅ FORM 3: Revenue CC (WITH MONTH PICKER) - COMPACT (HAPUS TOMBOL TUTUP) -->
        <div id="imp-rev-cc" class="imp-panel">
            <!-- Collapsible Instructions -->
            <div class="alert alert-info" style="cursor: pointer; margin-bottom: 1rem;" data-bs-toggle="collapse" data-bs-target="#infoRevCC">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa-solid fa-info-circle me-2"></i>
                        <strong>Instruksi Format CSV</strong>
                        <small class="ms-2 text-muted">(klik untuk penjelasan lebih lanjut)</small>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
            </div>

            <div class="collapse" id="infoRevCC">
                <div class="alert alert-warning" style="margin-bottom: 1rem;">
                    <strong>Penting:</strong>
                    <ul style="margin: 0.5rem 0 0 0; padding-left: 1.25rem; font-size: 0.9rem;">
                        <li>Pilih <strong>Periode</strong> terlebih dahulu</li>
                        <li>Jika periode + NIPNAS sudah ada → <strong>UPDATE</strong></li>
                        <li>AM revenues otomatis <strong>recalculated</strong></li>
                    </ul>
                </div>

                <div class="card mb-3" style="border: 2px solid #e7f3ff; background: #f8fcff;">
                    <div class="card-body" style="padding: 1rem;">
                        <h6 class="mb-2" style="color: #0066cc; font-weight: 600;">
                            <i class="fa-solid fa-file-lines me-1"></i> Format DGS/DSS:
                        </h6>
                        <small><strong>Kolom:</strong> NIPNAS, LSEGMENT_HO, WITEL_HO, REVENUE_SOLD</small>

                        <hr style="margin: 0.75rem 0;">

                        <h6 class="mb-2" style="color: #0066cc; font-weight: 600;">
                            <i class="fa-solid fa-file-lines me-1"></i> Format DPS:
                        </h6>
                        <small><strong>Kolom:</strong> NIPNAS, LSEGMENT_HO, WITEL_HO, WITEL_BILL, REVENUE_BILL</small>
                    </div>
                </div>
            </div>

            <form id="formRevenueCC" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="revenue_cc">

                <div class="row gx-3 gy-3">
                    <!-- ⭐ MONTH PICKER -->
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fa-solid fa-calendar-days"></i>
                            Periode <span class="required">*</span>
                        </label>
                        <input type="text" id="import-cc-periode" class="form-control datepicker-control" placeholder="Pilih Bulan & Tahun" autocomplete="off" readonly required>
                        <input type="hidden" name="month" id="import-cc-month">
                        <input type="hidden" name="year" id="import-cc-year">
                    </div>

                    <!-- FILE UPLOAD -->
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fa-solid fa-file-csv"></i>
                            Upload File CSV <span class="required">*</span>
                        </label>
                        <input type="file" class="form-control" name="file" accept=".csv" required>
                        <small class="text-muted">
                            <a href="{{ route('revenue.template', ['type' => 'revenue-cc-dgs']) }}">
                                <i class="fa-solid fa-download me-1"></i>Template DGS/DSS
                            </a> |
                            <a href="{{ route('revenue.template', ['type' => 'revenue-cc-dps']) }}">
                                Template DPS
                            </a>
                        </small>
                    </div>

                    <!-- DIVISI -->
                    <div class="col-md-4">
                        <div class="filter-group">
                            <label class="form-label">
                                <i class="fa-solid fa-sitemap"></i>
                                Divisi <span class="required">*</span>
                            </label>
                            <select class="form-select" name="divisi_id" id="divisiImport" required>
                                <option value="">Pilih Divisi</option>
                            </select>
                        </div>
                    </div>

                    <!-- JENIS DATA -->
                    <div class="col-md-12">
                        <label class="form-label">
                            <i class="fa-solid fa-tag"></i>
                            Jenis Data <span class="required">*</span>
                        </label>
                        <select class="form-select" name="jenis_data" required>
                            <option value="">Pilih Jenis Data</option>
                            <option value="revenue">Revenue (Real)</option>
                            <option value="target">Target Revenue</option>
                        </select>
                        <small class="text-muted">
                            Pilih "Revenue" untuk REVENUE_SOLD/BILL, "Target" untuk TARGET_REVENUE
                        </small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload"></i>Import Revenue CC
                </button>
            </form>
        </div>

        <!-- ✅ FORM 4: Revenue AM (WITH MONTH PICKER) - COMPACT (HAPUS TOMBOL TUTUP) -->
        <div id="imp-rev-map" class="imp-panel">
            <!-- Collapsible Instructions -->
            <div class="alert alert-info" style="cursor: pointer; margin-bottom: 1rem;" data-bs-toggle="collapse" data-bs-target="#infoRevAM">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa-solid fa-info-circle me-2"></i>
                        <strong>Instruksi Format CSV</strong>
                        <small class="ms-2 text-muted">(klik untuk penjelasan lebih lanjut)</small>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
            </div>

            <div class="collapse" id="infoRevAM">
                <div class="alert alert-warning" style="margin-bottom: 1rem;">
                    <strong>Penting:</strong>
                    <ul style="margin: 0.5rem 0 0 0; padding-left: 1.25rem; font-size: 0.9rem;">
                        <li>Pilih <strong>Periode</strong> terlebih dahulu</li>
                        <li><strong>Revenue CC harus sudah ada</strong> untuk periode ini</li>
                        <li>PROPORSI disimpan untuk recalculation otomatis</li>
                    </ul>
                </div>

                <div class="card mb-3" style="border: 2px solid #e7f3ff; background: #f8fcff;">
                    <div class="card-body" style="padding: 1rem;">
                        <h6 class="mb-2" style="color: #0066cc; font-weight: 600;">
                            <i class="fa-solid fa-file-lines me-1"></i> Format CSV:
                        </h6>
                        <small><strong>Kolom:</strong> NIPNAS, NIK_AM, PROPORSI (0-100)</small>
                    </div>
                </div>
            </div>

            <form id="formRevenueAM" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="revenue_am">

                <div class="row gx-3 gy-3">
                    <!-- ⭐ MONTH PICKER -->
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fa-solid fa-calendar-days"></i>
                            Periode <span class="required">*</span>
                        </label>
                        <input type="text" id="import-am-periode" class="form-control datepicker-control" placeholder="Pilih Bulan & Tahun" autocomplete="off" readonly required>
                        <input type="hidden" name="month" id="import-am-month">
                        <input type="hidden" name="year" id="import-am-year">
                    </div>

                    <!-- FILE UPLOAD -->
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fa-solid fa-file-csv"></i>
                            Upload File CSV <span class="required">*</span>
                        </label>
                        <input type="file" class="form-control" name="file" accept=".csv" required>
                        <small class="text-muted">
                            <a href="{{ route('revenue.template', ['type' => 'revenue-am']) }}">
                                <i class="fa-solid fa-download me-1"></i>Download Template
                            </a>
                        </small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload"></i>Import Revenue AM
                </button>
            </form>
        </div>

      </div>

      <!-- ✅ HAPUS FOOTER (tombol Tutup dihapus) -->
    </div>
  </div>
</div>

<!-- ==========================================
     ✨ PREVIEW MODAL - FIXED COLORS (MERAH)
     ========================================== -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-eye me-2"></i>
                    Preview Import Data
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Summary Cards -->
                <div class="preview-summary" id="previewSummary">
                    <!-- Filled dynamically via JS -->
                </div>

                <!-- Actions -->
                <div class="preview-actions">
                    <i class="fa-solid fa-info-circle"></i>
                    <div style="flex: 1;">
                        <strong>Pilih data yang akan diimport:</strong>
                        <div class="btn-group mt-2">
                            <button class="btn btn-sm" id="btnSelectAll">
                                <i class="fa-solid fa-check-double me-1"></i>Pilih Semua
                            </button>
                            <button class="btn btn-sm" id="btnDeselectAll">
                                <i class="fa-solid fa-times me-1"></i>Batal Semua
                            </button>
                            <button class="btn btn-sm" id="btnSelectNew">
                                <i class="fa-solid fa-plus me-1"></i>Pilih Baru Saja
                            </button>
                            <button class="btn btn-sm" id="btnSelectUpdates">
                                <i class="fa-solid fa-edit me-1"></i>Pilih Update Saja
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Preview Table -->
                <div class="preview-table-container">
                    <table class="preview-table table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">
                                    <input type="checkbox" id="selectAllPreview">
                                </th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Nilai</th>
                            </tr>
                        </thead>
                        <tbody id="previewTableBody">
                            <!-- Filled dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-execute" id="btnExecuteImport">
                    <i class="fa-solid fa-check me-2"></i>
                    Lanjutkan Import (<span id="selectedCount">0</span> dipilih)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <div class="spinner-border"></div>
        <p id="loadingText">Memproses...</p>
    </div>
</div>

<!-- =================== ✅ RESULT MODAL =================== -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hasil Import</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="resultModalBody">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" class="btn btn-primary" id="btnDownloadErrorLog" style="display: none;" target="_blank">
                    <i class="fa-solid fa-download me-2"></i>Download Error Log
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ========================================
     EDIT MODALS (PRESERVED - NO CHANGES)
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

<!-- Modal Edit Data AM -->
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

                            <!-- ✨ Divisi Button Group -->
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
          t.classList.remove('active');
          t.setAttribute('aria-selected', 'false');
        });
        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');

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
    });
  })();

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

    if (!buttonGroup || !hiddenContainer) return;

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
    document.querySelectorAll('.divisi-toggle-btn').forEach(btn => {
      btn.classList.remove('active');
    });

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

        const segmentSelect = $('#filter-segment');
        response.segments.forEach(function(segment) {
          segmentSelect.append(`<option value="${segment.id}">${segment.lsegment_ho}</option>`);
        });

        const editWitelSelect = $('#editDataAMWitel');
        editWitelSelect.empty();
        editWitelSelect.append('<option value="">Pilih Witel</option>');
        response.witels.forEach(function(witel) {
          editWitelSelect.append(`<option value="${witel.id}">${witel.nama}</option>`);
        });

        allDivisiData = response.divisions;
        initDivisiButtonGroup();

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
      const teldaDisplay = item.telda_nama || '-';
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

  // ✅ CRITICAL FIX: Form submissions - extract year/month manually!
  // Data CC & Data AM - tidak butuh periode
  $('#formDataCC, #formDataAM').submit(function(e) {
    e.preventDefault();
    currentFormData = new FormData($(this)[0]);
    currentImportType = currentFormData.get('import_type');

    console.log('📤 Submitting', currentImportType);
    handleImportPreview(currentFormData, currentImportType);
  });

  // ✅ Revenue CC - butuh periode + divisi + jenis_data
  $('#formRevenueCC').submit(function(e) {
    e.preventDefault();

    // ✅ AMBIL FormData dari form
    currentFormData = new FormData($(this)[0]);
    currentImportType = currentFormData.get('import_type');

    // ✅ EXTRACT year & month dari hidden inputs (CRITICAL!)
    const year = $('#import-cc-year').val();
    const month = $('#import-cc-month').val();
    const divisi = $('#divisiImport').val();
    const jenisData = $('select[name="jenis_data"]', $(this)).val();

    // ✅ VALIDASI
    if (!year || !month) {
      alert('❌ Pilih Periode terlebih dahulu!');
      return;
    }

    if (!divisi) {
      alert('❌ Pilih Divisi terlebih dahulu!');
      return;
    }

    if (!jenisData) {
      alert('❌ Pilih Jenis Data (Revenue/Target) terlebih dahulu!');
      return;
    }

    // ✅ APPEND year & month ke FormData
    currentFormData.set('year', year);
    currentFormData.set('month', month);

    console.log('📤 Submitting Revenue CC with:', {
      year: year,
      month: month,
      divisi_id: divisi,
      jenis_data: jenisData,
      file: currentFormData.get('file')?.name
    });

    handleImportPreview(currentFormData, currentImportType);
  });

  // ✅ Revenue AM - butuh periode
  $('#formRevenueAM').submit(function(e) {
    e.preventDefault();

    currentFormData = new FormData($(this)[0]);
    currentImportType = currentFormData.get('import_type');

    // ✅ EXTRACT year & month dari hidden inputs
    const year = $('#import-am-year').val();
    const month = $('#import-am-month').val();

    // ✅ VALIDASI
    if (!year || !month) {
      alert('❌ Pilih Periode terlebih dahulu!');
      return;
    }

    // ✅ APPEND year & month ke FormData
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
    // ✅ DEBUG: Print all FormData entries
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

  window.editDataAM = function(id) {
    $.ajax({
      url: `/revenue-data/data-am/${id}`,
      method: 'GET',
      success: function(response) {
        if (response.success) {
          const data = response.data;

          $('#editDataAMId').val(data.id);
          $('#changePasswordAMId').val(data.id);
          $('#editDataAMNama').val(data.nama);
          $('#editDataAMNik').val(data.nik);
          $('#editDataAMRole').val(data.role);
          $('#editDataAMWitel').val(data.witel_id);

          const divisiIds = data.divisi.map(d => d.id);
          setSelectedDivisi(divisiIds);

          const modal = new bootstrap.Modal(document.getElementById('modalEditDataAM'));
          modal.show();

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
