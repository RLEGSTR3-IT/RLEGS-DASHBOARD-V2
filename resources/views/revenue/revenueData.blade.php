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
        .result-modal-stat {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .result-modal-stat .icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .result-modal-stat .icon.success { background: #d4edda; color: #155724; }
        .result-modal-stat .icon.danger { background: #f8d7da; color: #721c24; }
        .result-modal-stat .icon.warning { background: #fff3cd; color: #856404; }
        .result-modal-stat .icon.info { background: #d1ecf1; color: #0c5460; }
        .result-modal-stat .content h4 { margin: 0; font-size: 1.5rem; font-weight: bold; }
        .result-modal-stat .content p { margin: 0; color: #6c757d; }
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


        <!-- === Periode: Datepicker (kalender harian) === -->
        <div class="filter-group" id="filterPeriodeGroup">
            <label>Periode</label>
            <input type="text" id="filter-date" class="form-control datepicker-control" placeholder="Pilih bulan & tahun" autocomplete="off">
            <input type="hidden" id="filter-month" name="month" value="{{ date('m') }}">
            <input type="hidden" id="filter-year"  name="year"  value="{{ date('Y') }}">
        </div>

        <!-- Filter Role (for AM tabs only) -->
        <div class="filter-group" id="filterRoleGroup" style="display: none;">
            <label>Role</label>
            <select class="form-select" id="filterRole">
                <option value="all">Semua</option>
                <option value="AM">AM</option>
                <option value="HOTDA">HOTDA</option>
            </select>
        </div>


        <div class="filter-actions">
            <button class="btn btn-light" id="btn-reset-filter"><i class="fa-solid fa-rotate me-2"></i>Reset</button>
            <button class="btn btn-primary" id="btn-apply-filter"><i class="fa-solid fa-filter me-2"></i>Terapkan</button>
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
    <div id="tab-cc-revenue" class="tab-panel active card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Revenue Corporate Customer</h3>
                <p class="muted">Gunakan <i>option button</i> untuk melihat kategori Revenue CC</p>
            </div>
            <div class="btn-segmentation" role="group" aria-label="Revenue Type">
                <button class="seg-btn active" data-revtype="REGULER">Reguler</button>
                <button class="seg-btn" data-revtype="NGTMA">NGTMA</button>
                <button class="seg-btn" data-revtype="KOMBINASI">Kombinasi</button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table modern">
                <thead>
                    <tr>
                        <th>Nama CC</th>
                        <th>Divisi</th>
                        <th>Segment</th>
                        <th class="text-end">Target Revenue</th>
                        <th class="text-end">
                            Revenue
                            <i class="fa-regular fa-circle-question ms-1 text-muted"
                               data-bs-toggle="tooltip"
                               title="Nilai ini menampilkan Revenue sesuai kategori (Reguler/NGTMA/Kombinasi). Hover pada angka untuk detail: Revenue Sold/Bill."></i>
                        </th>
                        <th>Bulan</th>
                    </tr>
                </thead>
                <tbody id="tableRevenueCC">
                    <tr>
                        <td colspan="6" class="text-center">
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
            <div class="am-toggles">
                <div class="btn-toggle" data-role="amMode">
                    <button class="am-btn active" data-mode="all">Semua</button>
                    <button class="am-btn" data-mode="AM">AM</button>
                    <button class="am-btn" data-mode="HOTDA">HOTDA</button>
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table modern" id="table-am">
                <thead>
                    <tr>
                        <th>Nama AM</th>
                        <th>Divisi</th>
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
                    </tr>
                </thead>
                <tbody id="tableRevenueAM">
                    <tr>
                        <td colspan="8" class="text-center">
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
        </div>

        <div class="table-wrap">
            <table class="table modern">
                <thead>
                    <tr>
                        <th>Nama AM</th>
                        <th>NIK</th>
                        <th>Divisi</th>
                        <th>Witel</th>
                        <th>Role</th>
                        <th class="hotda-col" style="display: none;">TELDA</th>
                        <th>Status Registrasi</th>
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
        </div>

        <div class="table-wrap">
            <table class="table modern">
                <thead>
                    <tr>
                        <th>Nama CC</th>
                        <th>NIPNAS</th>
                    </tr>
                </thead>
                <tbody id="tableDataCC">
                    <tr>
                        <td colspan="2" class="text-center">
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

<!-- =================== IMPORT MODAL (4 opsi) =================== -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content import-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="importModalLabel"><i class="fa-solid fa-file-import me-2"></i>Import Data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <div class="modal-body">
        <!-- Opsi Button Group -->
        <div class="import-type-switch">
            <button class="type-btn active" data-imp="imp-cc">Data CC</button>
            <button class="type-btn" data-imp="imp-am">Data AM</button>
            <button class="type-btn" data-imp="imp-rev-cc">Revenue CC</button>
            <button class="type-btn" data-imp="imp-rev-map">Revenue AM (Mapping)</button>
        </div>

        <!-- ====== Form: Data CC ====== -->
        <div id="imp-cc" class="imp-panel active">
            <form id="formDataCC" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="data_cc">
                <div class="row gx-3 gy-3">
                    <div class="col-md-6">
                        <label class="form-label">Unggah File (.csv)</label>
                        <input type="file" class="form-control" name="file" accept=".csv" required>
                    </div>
                </div>

                <div class="alert note mt-3">
                    <strong>Ketentuan file:</strong>
                    <ul class="mb-0">
                        <li>Format CSV dengan kolom: <strong>STANDARD_NAME, NIP_NAS</strong></li>
                    </ul>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload me-2"></i>Import</button>
                </div>
            </form>
        </div>

        <!-- ====== Form: Data AM ====== -->
        <div id="imp-am" class="imp-panel">
            <form id="formDataAM" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="data_am">
                <div class="row gx-3 gy-3">
                    <div class="col-md-6">
                        <label class="form-label">Unggah File (.csv)</label>
                        <input type="file" class="form-control" name="file" accept=".csv" required>
                        <small class="text-muted">Kolom wajib: <strong>NAMA_AM, NIK, WITEL, DIVISI, ROLE</strong>.</small>
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

                    <!-- tambahkan wrapper kolom di sini -->
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
                        <small class="text-muted">Kolom wajib: </br> <strong>YEAR, MONTH, NIPNAS, NIK_AM, PROPORSI</strong> dll.</small>
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

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

  // ========================================
  // GLOBAL STATE
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
          t.classList.remove('active');
          t.setAttribute('aria-selected', 'false');
        });
        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');

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
    });
  })();

  // ========================================
  // ENHANCE NATIVE SELECTS (Witel & Divisi)
  // ========================================
  function enhanceNativeSelect(native, { inModal = false } = {}) {
    if (!native || native.dataset.enhanced === '1') return;

    const wrap = document.createElement('div');
    wrap.className = 'cselect';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'cselect__btn';
    btn.innerHTML = `
      <span class="cselect__label">${native.options[native.selectedIndex]?.text || 'Pilih'}</span>
      <i class="cselect__caret" aria-hidden="true"></i>
    `;

    const menu = document.createElement('div');
    menu.className = 'cselect__menu';

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
  // INITIALIZATION
  // ========================================
  loadFilterOptions();
  loadData();

});
</script>
@endpush