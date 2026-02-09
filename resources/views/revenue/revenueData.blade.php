@extends('layouts.main')

@section('title', 'Revenue RLEGS')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/revenue.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* MODAL RENDERING FIX - CRITICAL */
        #modalEditRevenueCC .tab-content {
            display: block !important;
        }
        #modalEditRevenueCC .tab-pane {
            display: none !important;
        }
        #modalEditRevenueCC .tab-pane.active.show {
            display: block !important;
        }
        #modalEditRevenueAM .tab-content {
            display: block !important;
        }
        #modalEditDataAM .tab-content {
            display: block !important;
        }
        #modalEditDataAM .tab-pane {
            display: none;
        }
        #modalEditDataAM .tab-pane.active {
            display: block !important;
        }

        /* REVENUE CELL LAYOUT */
        .revenue-cell {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
            width: 100%;
        }
        .revenue-cell .revenue-value {
            font-weight: 700;
            font-size: 1rem;
            color: #212529;
        }
        .revenue-cell .revenue-badges {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            justify-content: flex-end;
            width: 100%;
            align-items: center;
        }
        td.text-end {
            text-align: right !important;
        }

        /* MODAL STYLING */
        #modalEditRevenueAM .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        #modalEditRevenueAM .info-section {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
        }
        #modalEditRevenueAM .edit-section {
            background-color: #fff8e1;
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid #ffc107;
        }
        #modalEditRevenueAM .calculated-section {
            background-color: #f0f8ff;
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid #0dcaf0;
        }
        #modalEditRevenueAM .form-control[readonly] {
            cursor: not-allowed !important;
        }
        #modalEditRevenueAM .other-ams-table {
            font-size: 0.9rem;
        }
        #modalEditRevenueAM .other-ams-table th {
            background-color: #e9ecef;
            font-weight: 600;
        }
        #modalEditRevenueAM .other-ams-table td {
            vertical-align: middle;
        }
        #proporsiValidationAlert.alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        #proporsiValidationAlert.alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        #proporsiValidationAlert.alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        /* MONTH PICKER */
        .mp-month-btn.active:hover {
            background: white !important;
            color: #dc3545 !important;
            border-color: #dc3545 !important;
            font-weight: 600;
        }
        .mp-header-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 0 8px;
        }
        .mp-year-input {
            flex: 1;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1.125rem;
            font-weight: 700;
            text-align: center;
            cursor: text;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.2s;
            width: 80px;
            min-width: 80px;
            max-width: 100px;
        }
        .mp-year-input:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .mp-year-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .mp-nav-btn {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .mp-nav-btn:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }
        .mp-nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        .flatpickr-current-month .numInputWrapper,
        .flatpickr-current-month .cur-year,
        .flatpickr-current-month .numInput,
        .flatpickr-current-month .arrowUp,
        .flatpickr-current-month .arrowDown {
            display: none !important;
        }
        .mp-year-display {
            font-weight: 600;
            font-size: 1.1rem;
            flex: 1;
            text-align: center;
            user-select: none;
        }
        .month-picker-body {
            padding: 1rem;
        }
        .mp-month-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
        }
        .mp-month-btn {
            padding: 0.75rem 0.5rem;
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            text-align: center;
            font-size: 0.875rem;
        }
        .mp-month-btn:hover:not(:disabled) {
            background: #e9ecef;
            border-color: #dc3545;
        }
        .mp-month-btn.active {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-color: #dc3545;
            font-weight: 600;
        }
        .mp-month-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* TRANSITIONS */
        .table tbody tr {
            transition: background-color 0.2s ease, transform 0.2s ease;
        }
        .form-control:focus,
        .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.15);
        }
        .btn {
            transition: all 0.2s ease;
        }
        .btn:hover:not(:disabled) {
            transform: translateY(-1px);
        }
        .btn:active:not(:disabled) {
            transform: translateY(0);
        }

        /* RESULT MODAL */
        .result-modal-stats-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .result-modal-stats-container.four-cols {
            grid-template-columns: repeat(4, 1fr);
        }
        .result-modal-stat {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .result-modal-stat .icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .result-modal-stat .icon.success { background: #d4edda; color: #155724; }
        .result-modal-stat .icon.danger { background: #f8d7da; color: #721c24; }
        .result-modal-stat .icon.warning { background: #fff3cd; color: #856404; }
        .result-modal-stat .icon.info { background: #d1ecf1; color: #0c5460; }
        .result-modal-stat .content { flex: 1; }
        .result-modal-stat .content h4 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: bold;
            white-space: nowrap;
            color: #212529;
        }
        .result-modal-stat .content p { margin: 0; color: #6c757d; font-size: 0.9rem; }
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
        .result-modal-info {
            background: #e7f3ff;
            border-left: 4px solid #0066cc;
            padding: 1rem 1.5rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
        .result-modal-info h6 {
            margin: 0 0 0.5rem 0;
            color: #0066cc;
            font-weight: 600;
        }
        .result-modal-info ul {
            margin: 0;
            padding-left: 1.25rem;
        }
        .result-modal-info li {
            color: #495057;
            margin-bottom: 0.25rem;
        }

        /* BADGES */
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
        .badge-revenue-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-left: 6px;
        }
        .badge-revenue-type.ho {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .badge-revenue-type.bill {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .badge-revenue-source {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-left: 6px;
        }
        .badge-revenue-source.reguler {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        .badge-revenue-source.ngtma {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        /* TABLE CONTROLS */
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

        /* TAB ACTIVE STATE */
        .tab-btn.active {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            color: white !important;
            border-color: #dc3545 !important;
        }
        .tab-btn.active .badge {
            background: rgba(255, 255, 255, 0.3) !important;
            color: white !important;
        }
        #editRevenueCCTabs .nav-link.active {
            color: #dc3545 !important;
            border-bottom-color: #dc3545 !important;
            background: transparent;
        }
        #editRevenueCCTabs .nav-link:hover {
            color: #dc3545;
            background: rgba(220, 53, 69, 0.05);
        }

        /* SEGMENT DROPDOWN */
        .seg-select {
            position: relative;
            width: 100%;
        }
        .seg-select__btn {
            width: 100%;
            padding: 0.625rem 1rem;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        .seg-select__btn:hover {
            border-color: #dc3545;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.1);
        }
        .seg-select__label {
            flex: 1;
            text-align: left;
            color: #333;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        .seg-select__caret {
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid #666;
            margin-left: 0.5rem;
            transition: transform 0.2s;
        }
        .seg-menu.open .seg-select__caret {
            transform: rotate(180deg);
        }
        .seg-menu {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            width: 100%;
            min-width: 300px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            z-index: 1000;
            overflow: hidden;
            display: none;
            animation: fadeInScale 0.2s ease;
        }
        .seg-menu.open {
            display: block;
        }
        .seg-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 2px solid #e0e0e0;
            overflow-x: auto;
        }
        .seg-tab {
            padding: 0.75rem 1rem;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.875rem;
            color: #666;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .seg-tab:hover {
            background: rgba(220, 53, 69, 0.05);
            color: #dc3545;
        }
        .seg-tab.active {
            background: white;
            border-bottom-color: #dc3545;
            color: #dc3545;
            font-weight: 600;
        }
        .seg-panels {
            max-height: 250px;
            overflow-y: auto;
        }
        .seg-panel {
            display: none;
            padding: 0.5rem;
        }
        .seg-panel.active {
            display: block;
        }
        .seg-option {
            width: 100%;
            padding: 0.75rem 1rem;
            background: white;
            border: none;
            border-radius: 6px;
            text-align: left;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .seg-option:hover {
            background: #f8f9fa;
            color: #dc3545;
            font-weight: 500;
        }
        #filter-segment {
            display: none !important;
        }
        .seg-select {
            display: block !important;
        }

        /* IMPORT MODAL */
        #importModal .modal-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-bottom: none;
        }
        #importModal .modal-header .modal-title {
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        #importModal .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }
        #importModal .modal-header .btn-close:hover {
            opacity: 1;
        }
        #importModal .modal-body {
            padding: 2rem;
            background: #f8f9fa;
        }
        .type-selector {
            display: flex;
            gap: 0;
            background: white;
            padding: 6px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        .type-btn {
            flex: 1;
            padding: 1rem 1.5rem;
            border: none;
            background: transparent;
            color: #6c757d;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 8px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .type-btn:hover:not(.active) {
            background: rgba(220, 53, 69, 0.08);
            color: #dc3545;
            transform: translateY(-2px);
        }
        .type-btn.active {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(220, 53, 69, 0.35);
            transform: scale(1.02);
        }
        .type-btn i {
            font-size: 1.1rem;
        }
        .imp-panel {
            display: none;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            animation: fadeInUp 0.4s ease;
        }
        .imp-panel.active {
            display: block;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .imp-panel .alert {
            border: none;
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        .imp-panel .alert-info {
            background: linear-gradient(135deg, #e7f3ff 0%, #d4e9ff 100%);
            border-left-color: #0066cc;
            color: #004080;
        }
        .imp-panel .alert-warning {
            background: linear-gradient(135deg, #fff8e6 0%, #fff0cc 100%);
            border-left-color: #ff9800;
            color: #995c00;
        }
        .imp-panel .alert ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.5rem;
        }
        .imp-panel .alert li {
            margin-bottom: 0.35rem;
            line-height: 1.6;
        }
        .imp-panel .alert strong {
            font-weight: 700;
            display: block;
            margin-bottom: 0.5rem;
        }
        .imp-panel .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .imp-panel .form-label .required {
            color: #dc3545;
            font-weight: 700;
        }
        .imp-panel .form-control,
        .imp-panel .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.875rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .imp-panel .form-control:focus,
        .imp-panel .form-select:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.12);
            background: white;
        }
        .imp-panel .form-control:hover,
        .imp-panel .form-select:hover {
            border-color: #dee2e6;
        }
        .imp-panel .datepicker-control {
            background: #f8f9fa url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23dc3545' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E") no-repeat right 1rem center;
            background-size: 20px;
            padding-right: 3rem;
            cursor: pointer;
            font-weight: 500;
        }
        .imp-panel .text-muted {
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: inline-block;
        }
        .imp-panel .text-muted a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        .imp-panel .text-muted a:hover {
            color: #c82333;
            text-decoration: underline;
        }
        .imp-panel .btn-primary {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            padding: 0.875rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(220, 53, 69, 0.3);
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }
        .imp-panel .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }
        .imp-panel .btn-primary:active {
            transform: translateY(0);
        }
        .imp-panel .btn-primary i {
            margin-right: 0.5rem;
        }
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

        /* DIVISI BUTTON GROUP */
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
            background: var(--gradient-blue, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
            border-color: var(--dps-primary, #667eea);
        }
        .divisi-toggle-btn.dss.active {
            background: var(--gradient-pink, linear-gradient(135deg, #f093fb 0%, #f5576c 100%));
            border-color: var(--dss-primary, #f093fb);
        }
        .divisi-toggle-btn.dgs.active {
            background: var(--gradient-cyan, linear-gradient(135deg, #4facfe 0%, #00f2fe 100%));
            border-color: var(--dgs-primary, #4facfe);
        }
        .divisi-toggle-btn.des.active {
            background: var(--gradient-yellow, linear-gradient(135deg, #fa709a 0%, #fee140 100%));
            border-color: var(--des-primary, #fa709a);
        }
        .divisi-hidden-container {
            display: none;
        }
        #editDataAMTeldaWrapper {
            transition: all 0.3s ease;
        }
        #editDataAMTeldaWrapper.hidden {
            display: none;
        }

        /* PREVIEW MODAL */
        #previewModal .modal-dialog {
            max-width: 900px;
            margin: 1.75rem auto;
        }
        #previewModal .modal-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-bottom: none;
        }
        #previewModal .modal-title {
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
        }
        #previewModal .modal-title i {
            color: white;
        }
        #previewModal .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }
        #previewModal .btn-close:hover {
            opacity: 1;
        }
        #previewModal .modal-body {
            padding: 2rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        .preview-summary {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.875rem;
            margin-bottom: 2rem;
        }
        .preview-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            transition: all 0.2s ease;
            min-width: 0;
        }
        .preview-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .preview-card .icon {
            font-size: 1.5rem;
            margin-bottom: 0.625rem;
            color: #6c757d;
        }
        .preview-card.total .icon { color: #495057; }
        .preview-card.unique .icon { color: #6c757d; }
        .preview-card.update .icon { color: #ffc107; }
        .preview-card.new .icon { color: #28a745; }
        .preview-card.conflict .icon { color: #dc3545; }
        .preview-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0.375rem 0 0.25rem 0;
            color: #212529;
        }
        .preview-card p {
            margin: 0;
            color: #6c757d;
            font-size: 0.8rem;
            font-weight: 500;
            line-height: 1.3;
        }
        .aggregate-warning {
            background: #fff8e6;
            border: 2px solid #ffc107;
            border-left: 4px solid #ff9800;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .aggregate-warning .warning-text {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #995c00;
            font-weight: 600;
        }
        .aggregate-warning .warning-text i {
            font-size: 1.25rem;
            color: #ff9800;
        }
        .aggregate-warning label {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            color: #995c00;
        }
        .aggregate-warning input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .import-actions {
            margin-top: 1.5rem;
        }
        .import-actions .alert {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-left: 4px solid #dc3545;
            color: #495057;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        .import-actions .alert i {
            color: #dc3545;
            margin-right: 0.5rem;
        }
        .import-actions .d-grid {
            gap: 0.75rem;
        }
        .import-actions .btn {
            padding: 0.875rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        #btnImportAll {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-color: #dc3545;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.25);
        }
        #btnImportAll:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 53, 69, 0.35);
        }
        #btnImportNew,
        #btnImportUpdate {
            background: white;
            color: #dc3545;
            border: 2px solid #dc3545;
        }
        #btnImportNew:hover:not(:disabled),
        #btnImportUpdate:hover:not(:disabled) {
            background: #fff5f5;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.15);
        }
        .import-actions .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .import-actions .badge {
            background: rgba(0, 0, 0, 0.1);
            color: inherit;
            padding: 0.375rem 0.75rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        #btnImportAll .badge {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }
        .import-actions .alert-danger {
            background: #fff5f5;
            border: 2px solid #ffc9c9;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        #previewModal .modal-footer {
            border-top: 2px solid #e9ecef;
            padding: 1.5rem 2rem;
            background: #f8f9fa;
        }
        #previewModal .modal-footer .btn-light {
            background: white;
            border: 2px solid #e9ecef;
            color: #6c757d;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
        }
        #previewModal .modal-footer .btn-light:hover {
            border-color: #dc3545;
            color: #dc3545;
            background: #fff5f5;
        }

        /* PROGRESS SNACKBAR */
        .progress-snackbar {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 420px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9999;
            display: none;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }
        .progress-snackbar.active {
            display: block;
            animation: slideInRight 0.3s ease;
        }
        @keyframes slideInRight {
            from {
                transform: translateX(450px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .progress-snackbar.minimized {
            width: auto;
        }
        .progress-snackbar.minimized .snackbar-body {
            display: none;
        }
        .snackbar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            cursor: pointer;
            user-select: none;
        }
        .progress-snackbar.minimized .snackbar-header {
            border-radius: 12px;
        }
        .snackbar-title {
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .snackbar-title i {
            font-size: 1rem;
        }
        .snackbar-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-minimize,
        .btn-close-snackbar {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        .btn-minimize:hover,
        .btn-close-snackbar:hover {
            background: rgba(255,255,255,0.3);
        }
        .snackbar-body {
            padding: 1.25rem;
        }
        .progress-container {
            width: 100%;
            height: 32px;
            background: #f1f3f5;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 0.75rem;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            transition: width 0.3s ease;
            min-width: 32px;
        }
        .progress-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
        }
        .progress-status {
            font-size: 0.875rem;
            color: #6c757d;
            font-weight: 500;
        }
        .progress-percentage {
            font-size: 1.125rem;
            font-weight: 700;
            color: #212529;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9998;
        }
        .loading-overlay.active {
            display: flex;
        }
        .loading-spinner {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .loading-spinner .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3rem;
            color: #dc3545;
        }
        .loading-spinner p {
            margin-top: 1rem;
            color: #212529;
            font-weight: 600;
        }

        /* DELETE MODAL */
        .delete-modal-backdrop {
            backdrop-filter: blur(4px);
            background-color: rgba(0, 0, 0, 0.5);
        }
        .delete-confirm-modal .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .delete-confirm-modal .modal-header {
            background: #ea1d25;
            border: none;
            padding: 20px 24px;
            position: relative;
        }
        .delete-confirm-modal .modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        .delete-confirm-modal .modal-title {
            color: #ffffff !important;
            font-weight: 600;
            font-size: 1.125rem;
            display: flex;
            align-items: center;
            margin: 0;
        }
        .delete-confirm-modal .modal-title i {
            font-size: 1.25rem;
            margin-right: 10px;
            animation: pulse-warning 2s ease-in-out infinite;
        }
        @keyframes pulse-warning {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.1); }
        }
        .delete-confirm-modal .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
            transition: all 0.2s ease;
        }
        .delete-confirm-modal .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }
        .delete-confirm-modal .modal-body {
            padding: 24px;
            background: #ffffff;
        }
        .delete-warning-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: none;
            border-left: 4px solid #f59e0b;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.15);
        }
        .delete-warning-box .warning-icon {
            color: #f59e0b;
            font-size: 1.25rem;
            margin-right: 12px;
        }
        .delete-warning-box .warning-title {
            color: #92400e;
            font-weight: 600;
            font-size: 1rem;
            margin: 0;
        }
        .delete-warning-box .warning-text {
            color: #78350f;
            font-size: 0.9rem;
            margin: 8px 0 0 0;
            line-height: 1.5;
        }
        .related-data-card {
            background: #f9fafb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
        }
        .related-data-card h6 {
            color: #374151;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .related-data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .related-data-table tbody tr {
            transition: background-color 0.2s ease;
        }
        .related-data-table tbody tr:hover {
            background-color: #f3f4f6;
        }
        .related-data-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.875rem;
        }
        .related-data-table td:first-child {
            color: #6b7280;
            font-weight: 500;
        }
        .related-data-table td:last-child {
            text-align: right;
            color: #111827;
            font-weight: 600;
        }
        .related-data-table tbody tr:last-child td {
            border-bottom: none;
        }
        .related-data-table tbody tr.total-row {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        }
        .related-data-table tbody tr.total-row td {
            font-weight: 700;
            color: #111827;
        }
        .delete-options-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #e5e7eb;
        }
        .delete-options-card h6 {
            color: #374151;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .delete-option-radio {
            position: relative;
            margin-bottom: 12px;
        }
        .delete-option-radio input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .delete-option-radio label {
            display: block;
            padding: 16px 20px 16px 52px;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            margin: 0;
        }
        .delete-option-radio label::before {
            content: '';
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 50%;
            background: white;
            transition: all 0.3s ease;
        }
        .delete-option-radio label::after {
            content: '';
            position: absolute;
            left: 24px;
            top: 50%;
            transform: translateY(-50%) scale(0);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: white;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        .delete-option-radio input[type="radio"]:checked + label {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        .delete-option-radio input[type="radio"]:checked + label::before {
            background: #3b82f6;
            border-color: #3b82f6;
        }
        .delete-option-radio input[type="radio"]:checked + label::after {
            transform: translateY(-50%) scale(1);
        }
        .delete-option-radio label:hover {
            border-color: #9ca3af;
            background: #f3f4f6;
            transform: translateX(2px);
        }
        .delete-option-radio input[type="radio"]:checked + label:hover {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-color: #3b82f6;
        }
        .delete-option-radio .option-title {
            display: block;
            color: #111827;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }
        .delete-option-radio .option-description {
            display: block;
            color: #6b7280;
            font-size: 0.8rem;
            line-height: 1.4;
        }
        .delete-option-radio input[type="radio"]:checked + label .option-title {
            color: #1e40af;
        }
        .delete-option-radio input[type="radio"]:checked + label .option-description {
            color: #1e40af;
        }
        .danger-zone-card {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #dc2626;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);
            animation: pulse-danger 2s ease-in-out infinite;
        }
        @keyframes pulse-danger {
            0%, 100% { box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15); }
            50% { box-shadow: 0 4px 20px rgba(220, 38, 38, 0.25); }
        }
        .danger-zone-card .danger-icon {
            color: #dc2626;
            font-size: 1.5rem;
            margin-right: 12px;
        }
        .danger-zone-card .danger-title {
            color: #991b1b;
            font-weight: 700;
            font-size: 1rem;
            margin: 0 0 12px 0;
        }
        .danger-zone-card .danger-text {
            color: #7f1d1d;
            font-size: 0.875rem;
            margin: 8px 0;
            line-height: 1.5;
        }
        .danger-zone-card .confirmation-input {
            margin-top: 12px;
            padding: 12px 16px;
            border: 2px solid #dc2626;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        .danger-zone-card .confirmation-input:focus {
            outline: none;
            border-color: #991b1b;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
            background: white;
        }
        .danger-zone-card .confirmation-input::placeholder {
            color: #dc2626;
            opacity: 0.5;
        }
        .delete-confirm-modal .modal-footer {
            border: none;
            padding: 16px 24px 20px;
            background: #f9fafb;
            display: flex;
            gap: 12px;
        }
        .delete-confirm-modal .btn-cancel {
            flex: 1;
            padding: 12px 24px;
            background: white;
            border: 2px solid #e5e7eb;
            color: #6b7280;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .delete-confirm-modal .btn-cancel:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
            color: #374151;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .delete-confirm-modal .btn-delete-confirm {
            flex: 1;
            padding: 12px 24px;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        .delete-confirm-modal .btn-delete-confirm:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.4);
        }
        .delete-confirm-modal .btn-delete-confirm:active {
            transform: translateY(0);
        }
        .delete-confirm-modal .btn-delete-confirm i,
        .delete-confirm-modal .btn-cancel i {
            margin-right: 8px;
        }
        .delete-confirm-modal.fade .modal-dialog {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .delete-confirm-modal.show .modal-dialog {
            transform: scale(1);
        }

        /* VIEW MODE TOGGLE */
        .view-mode-toggle {
            display: inline-flex;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 4px;
            gap: 4px;
        }
        .view-mode-toggle button {
            padding: 0.5rem 1rem;
            border: none;
            background: transparent;
            color: #6c757d;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        .view-mode-toggle button:hover:not(.active) {
            background: #f8f9fa;
            color: #495057;
        }
        .view-mode-toggle button.active {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.25);
        }
        .table-wrap.all-columns {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table-wrap.all-columns table {
            min-width: 1200px;
        }
        .tipe-revenue-select {
            position: relative;
        }
        .tipe-revenue-select select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background: #f8f9fa url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23dc3545' d='M6 9L1 4h10z'/%3E%3C/svg%3E") no-repeat right 1rem center;
            background-size: 12px;
            padding-right: 2.5rem;
        }
        .currency-short {
            font-weight: 600;
        }
        .currency-short .unit {
            font-size: 0.85em;
            font-weight: 500;
            color: #6c757d;
            margin-left: 2px;
        }

        /* AM MAPPING TABLE */
        .am-mapping-row.editing {
            background: linear-gradient(135deg, #fff8e6 0%, #fff0cc 100%) !important;
            border-left: 4px solid #ff9800;
        }
        .am-mapping-row.editing input {
            border: 2px solid #ff9800 !important;
            background: white !important;
        }
        .am-mapping-row.readonly {
            background: #f8f9fa;
        }
        .am-mapping-row.readonly input {
            cursor: not-allowed;
        }
        .proporsi-input {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.5rem;
            font-weight: 600;
            text-align: center;
            font-size: 1rem;
        }
        .proporsi-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .proporsi-alert-success {
            background: #d4edda !important;
            border-left-color: #28a745 !important;
            color: #155724 !important;
        }
        .proporsi-alert-warning {
            background: #fff3cd !important;
            border-left-color: #ffc107 !important;
            color: #856404 !important;
        }
        .proporsi-alert-danger {
            background: #f8d7da !important;
            border-left-color: #dc3545 !important;
            color: #721c24 !important;
        }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .result-modal-stats-container.four-cols {
                grid-template-columns: repeat(2, 1fr);
            }
            #previewModal .modal-dialog {
                max-width: 720px;
            }
            .preview-summary {
                grid-template-columns: repeat(3, 1fr);
            }
            .preview-card {
                padding: 0.875rem;
            }
            .preview-card .icon {
                font-size: 1.35rem;
            }
            .preview-card h3 {
                font-size: 1.35rem;
            }
            .preview-card p {
                font-size: 0.75rem;
            }
        }
        @media (max-width: 768px) {
            .table-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .view-mode-toggle {
                width: 100%;
            }
            .view-mode-toggle button {
                flex: 1;
            }
            .badge-revenue-type,
            .badge-revenue-source {
                font-size: 0.65rem;
                padding: 2px 6px;
            }
            .progress-snackbar {
                width: calc(100% - 40px);
                right: 20px;
                left: 20px;
            }
            .preview-summary {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            .preview-card {
                padding: 0.75rem;
            }
        }
        @media (max-width: 576px) {
            .result-modal-stats-container.four-cols {
                grid-template-columns: 1fr;
            }
            .delete-confirm-modal .modal-dialog {
                margin: 0.5rem;
            }
            .delete-confirm-modal .modal-body {
                padding: 16px;
            }
            .delete-option-radio label {
                padding: 12px 16px 12px 44px;
            }
            .delete-option-radio label::before {
                left: 14px;
                width: 18px;
                height: 18px;
            }
            .delete-option-radio label::after {
                left: 20px;
                width: 6px;
                height: 6px;
            }
            .preview-summary {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 480px) {
            .progress-snackbar {
                bottom: 16px;
                right: 16px;
                left: 16px;
                width: calc(100% - 32px);
            }
            .import-actions .btn {
                font-size: 0.875rem;
                padding: 0.75rem 1rem;
            }
            .aggregate-warning {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endsection

@section('content')
<div class="rlegs-container">
    <!-- Page Header -->
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

    <!-- Filters -->
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
            <select class="form-select" id="filter-segment" name="segment" style="display: none;">
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
            <input type="hidden" id="filter-year" name="year" value="{{ date('Y') }}">
        </div>
        <div class="filter-actions">
            <button class="btn btn-secondary" id="btn-reset-filter">
                <i class="fa-solid fa-rotate-left me-1"></i>Reset
            </button>
        </div>
    </div>

    <!-- Tabs -->
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

    <!-- Tab: Revenue CC -->
    <div id="tab-cc-revenue" class="tab-panel card-shadow active">
        <div class="panel-header">
            <div class="left">
                <h3>Revenue Corporate Customer</h3>
                <p class="muted">Gunakan <i>option button</i> untuk melihat kategori Revenue CC</p>
            </div>
            <div class="right" style="display: flex; gap: 1rem; align-items: center;">
                <div class="view-mode-toggle" id="viewModeToggle">
                    <button class="active" data-mode="default">
                        <i class="fa-solid fa-table-cells me-1"></i>Default
                    </button>
                    <button data-mode="all">
                        <i class="fa-solid fa-table-columns me-1"></i>All Columns
                    </button>
                </div>
                <button class="btn btn-danger btn-sm" id="btnDeleteSelectedCC" disabled>
                    <i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih
                </button>
                <button class="btn btn-outline-danger btn-sm" id="btnBulkDeleteCC">
                    <i class="fa-solid fa-trash-alt me-2"></i>Hapus Semua
                </button>
                <div class="btn-segmentation" role="group">
                    <button class="seg-btn active" data-revtype="REGULER">Reguler</button>
                    <button class="seg-btn" data-revtype="NGTMA">NGTMA</button>
                    <button class="seg-btn" data-revtype="KOMBINASI">Kombinasi</button>
                </div>
            </div>
        </div>

        <div class="table-wrap" id="revenueTableWrap">
            <table class="table modern">
                <thead>
                    <tr>
                        <th style="width: 48px;"><input type="checkbox" id="selectAllCC"></th>
                        <th>Nama CC</th>
                        <th>Segment</th>
                        <th class="text-end">Target Revenue</th>
                        <th class="text-end revenue-col-default">
                            Revenue
                            <i class="fa-regular fa-circle-question ms-1 text-muted" data-bs-toggle="tooltip" title="Nilai menampilkan Revenue sesuai kategori (Reguler/NGTMA/Kombinasi)"></i>
                        </th>
                        <th class="text-end revenue-col-all" style="display: none;">Revenue Sold</th>
                        <th class="text-end revenue-col-all" style="display: none;">Revenue Bill</th>
                        <th>Bulan</th>
                        <th class="text-center" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableRevenueCC">
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

    <!-- Tab: Revenue AM -->
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
                        <th style="width: 48px;"><input type="checkbox" id="selectAllAM"></th>
                        <th>Nama AM</th>
                        <th>Corporate Customer</th>
                        <th class="text-end">Target Revenue</th>
                        <th class="text-end">Revenue</th>
                        <th class="text-end">Achievement</th>
                        <th>Bulan</th>
                        <th class="telda-col">TELDA</th>
                        <th class="text-center" style="width: 150px;">Aksi</th>
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

    <!-- Tab: Data AM -->
    <div id="tab-data-am" class="tab-panel card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Data Account Manager</h3>
                <p class="muted">Daftar Account Manager yang terdaftar di sistem</p>
            </div>
            <div class="right" style="display: flex; gap: 1rem;">
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
                        <th style="width: 48px;"><input type="checkbox" id="selectAllDataAM"></th>
                        <th>Nama AM</th>
                        <th>Witel</th>
                        <th>Role</th>
                        <th>TELDA</th>
                        <th>Status Registrasi</th>
                        <th class="text-center" style="width: 150px;">Aksi</th>
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

    <!-- Tab: Data CC -->
    <div id="tab-data-cc" class="tab-panel card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Data Corporate Customer</h3>
                <p class="muted">Detail Corporate Customer</p>
            </div>
            <div class="right" style="display: flex; gap: 1rem;">
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
                        <th style="width: 48px;"><input type="checkbox" id="selectAllDataCC"></th>
                        <th>Nama CC</th>
                        <th>NIPNAS</th>
                        <th class="text-center" style="width: 150px;">Aksi</th>
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

<!-- ===================================================
     🎯 IMPORT MODAL - WITH 5 TABS
     =================================================== -->
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
          <button class="type-btn" data-imp="imp-target-witel">
              <i class="fa-solid fa-chart-column"></i>
              Target Bill Witel
          </button>
        </div>

        <!-- FORM 1: DATA CC -->
        <div id="imp-data-cc" class="imp-panel active">
            <div class="alert alert-light border mb-3 py-2 px-3" style="font-size: 0.875rem;">
                <div class="d-flex gap-2 align-items-start">
                    <i class="fa-solid fa-info-circle text-primary" style="margin-top: 2px;"></i>
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
                    <input type="file" class="form-control" name="file" accept=".csv,.txt" required>
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

        <!-- FORM 2: DATA AM -->
        <div id="imp-data-am" class="imp-panel">
            <div class="alert alert-light border mb-3 py-2 px-3" style="font-size: 0.875rem;">
                <div class="d-flex gap-2 align-items-start">
                    <i class="fa-solid fa-info-circle text-primary" style="margin-top: 2px;"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-1">Format CSV Data AM</strong>
                        <div class="text-muted" style="font-size: 0.85rem; line-height: 1.5;">
                            <strong>Kolom:</strong> NIK, NAMA AM, WITEL AM, DIVISI AM, REGIONAL, DIVISI, TELDA<br>
                            <strong>Aturan:</strong> HOTDA wajib isi TELDA | AM kosongkan TELDA
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
                    <input type="file" class="form-control" name="file" accept=".csv,.txt" required>
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

        <!-- FORM 3: REVENUE CC - UNIFIED TEMPLATE -->
        <div id="imp-rev-cc" class="imp-panel">
            <div class="alert alert-light border mb-3 py-2 px-3" style="font-size: 0.8rem;">
                <div class="d-flex gap-2">
                    <i class="fa-solid fa-info-circle text-primary"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-1">Format CSV (Sama untuk DGS/DSS/DPS)</strong>
                        <div class="text-muted" style="font-size: 0.75rem; line-height: 1.6;">
                            <strong>Kolom:</strong> NIPNAS, STANDARD_NAME, LSEGMENT_HO, WITEL_HO, REVENUE_SOLD, TARGET_REVENUE_SOLD, WITEL_BILL, REVENUE_BILL, SOURCE_DATA<br>
                            <strong>Pilih Tipe:</strong> HO (pakai REVENUE_SOLD) atau BILL (pakai REVENUE_BILL)
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

                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">
                            <i class="fa-solid fa-sitemap me-1"></i>Divisi <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-sm" name="divisi_id" id="revCCDivisiImport" required>
                            <option value="">Pilih Divisi</option>
                        </select>
                    </div>

                    <div class="col-md-2 tipe-revenue-select">
                        <label class="form-label fw-semibold small">
                            <i class="fa-solid fa-tags me-1"></i>Tipe Revenue <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-sm" name="tipe_revenue" id="revCCTipeRevenueImport" required>
                            <option value="">Pilih Tipe</option>
                            <option value="HO">Revenue Sold (HO)</option>
                            <option value="BILL">Revenue Bill</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">
                            <i class="fa-solid fa-tag me-1"></i>Jenis Data <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-sm" name="jenis_data" id="revCCJenisDataImport" required>
                            <option value="">Pilih Jenis</option>
                            <option value="revenue">Real Revenue</option>
                            <option value="target">Target Revenue</option>
                            <option value="lengkap">Lengkap (Revenue + Target)</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="fa-solid fa-file me-1"></i>Upload CSV <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control form-control-sm" name="file" accept=".csv,.txt" required>
                    </div>
                </div>

                <!-- ✅ FIX: Dynamic Template Links (2 buttons) -->
                <div class="mb-3" style="font-size: 0.82rem; display: flex; gap: 1rem;">
                    <span class="text-muted">Template:</span>
                    <a href="{{ route('revenue.template', ['type' => 'revenue-cc']) }}" class="text-decoration-none" target="_blank" id="linkTemplateRealRevCC">
                        <i class="fa-solid fa-download me-1"></i>Unduh Template Real Revenue CC
                    </a>
                    <a href="{{ route('revenue.template', ['type' => 'revenue-cc']) }}" class="text-decoration-none" target="_blank" id="linkTemplateTargetRevCC">
                        <i class="fa-solid fa-download me-1"></i>Unduh Template Target Revenue CC
                    </a>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-upload me-1"></i>Import Revenue CC
                </button>
            </form>
        </div>

        <!-- FORM 4: REVENUE AM -->
        <div id="imp-rev-map" class="imp-panel">
            <div class="alert alert-light border mb-3 py-2 px-3" style="font-size: 0.875rem;">
                <div class="d-flex gap-2 align-items-start">
                    <i class="fa-solid fa-info-circle text-primary" style="margin-top: 2px;"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-1">Format CSV Revenue AM</strong>
                        <div class="text-muted" style="font-size: 0.85rem; line-height: 1.5;">
                            <strong>Kolom:</strong> NIPNAS, NIK_AM, PROPORSI<br>
                            <strong>Aturan:</strong> Revenue CC harus ada dulu | Total per NIPNAS = 100
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
                        <input type="file" class="form-control" name="file" accept=".csv,.txt" required>
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

        <!-- FORM 5: TARGET BILL WITEL -->
        <div id="imp-target-witel" class="imp-panel">
            <div class="alert alert-light border mb-3 py-2 px-3" style="font-size: 0.875rem;">
                <div class="d-flex gap-2 align-items-start">
                    <i class="fa-solid fa-info-circle text-primary" style="margin-top: 2px;"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-1">Format CSV Target Bill Witel</strong>
                        <div class="text-muted" style="font-size: 0.85rem; line-height: 1.5;">
                            <strong>Kolom:</strong> WITEL, TARGET_REVENUE<br>
                            <strong>Catatan:</strong> Hanya untuk divisi DPS/DSS
                        </div>
                    </div>
                </div>
            </div>

            <form id="formTargetWitel" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="target_witel">

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-calendar-days me-1"></i>
                            Periode <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="import-witel-periode" class="form-control datepicker-control" placeholder="Pilih Bulan & Tahun" autocomplete="off" readonly required>
                        <input type="hidden" name="month" id="import-witel-month">
                        <input type="hidden" name="year" id="import-witel-year">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-sitemap me-1"></i>
                            Divisi <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="divisi_id" id="targetWitelDivisiImport" required>
                            <option value="">Pilih Divisi</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-file-csv me-1"></i>
                            Upload File CSV <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control" name="file" accept=".csv,.txt" required>
                        <small class="form-text text-muted">
                            <a href="{{ route('revenue.template', ['type' => 'witel-target-bill']) }}" class="text-decoration-none">
                                <i class="fa-solid fa-download me-1"></i>Download Template
                            </a>
                        </small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload me-2"></i>Import Target Bill Witel
                </button>
            </form>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- PREVIEW MODAL -->
<div class="modal fade" id="previewModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-eye me-2"></i>
                    Preview Import Data
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="preview-summary" id="previewSummary"></div>

                <div class="aggregate-warning" id="aggregateWarning" style="display: none;">
                    <div class="warning-text">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        <span id="aggregateWarningText">10 CC duplikat ditemukan</span>
                    </div>
                    <label>
                        <input type="checkbox" id="aggregateToggle">
                        <span>Agregasi (sum revenue)</span>
                    </label>
                </div>

                <div id="previewInfo" style="display: none;"></div>

                <div class="import-actions">
                    <div class="alert">
                        <i class="fa-solid fa-info-circle"></i>
                        <strong>Pilih jenis data yang akan diimport:</strong>
                    </div>
                    
                    <div class="d-grid">
                        <button type="button" class="btn" id="btnImportAll">
                            <span>
                                <i class="fa-solid fa-check-double me-2"></i>
                                <strong>Import Semua Data</strong>
                            </span>
                            <span class="badge" id="badgeAllCount">0 data</span>
                        </button>

                        <button type="button" class="btn" id="btnImportNew">
                            <span>
                                <i class="fa-solid fa-plus me-2"></i>
                                <strong>Import Data Baru Saja</strong>
                            </span>
                            <span class="badge" id="badgeNewCount">0 data</span>
                        </button>

                        <button type="button" class="btn" id="btnImportUpdate">
                            <span>
                                <i class="fa-solid fa-edit me-2"></i>
                                <strong>Import Data Update Saja</strong>
                            </span>
                            <span class="badge" id="badgeUpdateCount">0 data</span>
                        </button>
                    </div>

                    <div class="alert alert-danger" id="errorInfo" style="display: none;">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>
                        <span id="errorMessage"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times me-2"></i>Batal
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

<!-- RESULT MODAL -->
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



<!-- ✅ UPDATED: Modal Edit Revenue CC - 3 Separate Revenue Fields -->
<div class="modal fade" id="modalEditRevenueCC" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none;">
                <h5 class="modal-title" style="color: white;">
                    <i class="fa-solid fa-pen-to-square me-2"></i>Edit Revenue Corporate Customer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: brightness(0) invert(1);"></button>
            </div>
            
            <!-- ✅ TABS NAVIGATION -->
            <div style="background: white; border-bottom: 2px solid #e9ecef;">
                <ul class="nav nav-tabs" id="editRevenueCCTabs" role="tablist" style="border: none; padding: 0 2rem;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" 
                                id="tab-revenue-data-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#tab-revenue-data" 
                                type="button"
                                style="border: none; border-bottom: 3px solid transparent; padding: 1rem 1.5rem; font-weight: 600; color: #6c757d;">
                            <i class="fa-solid fa-chart-line me-2"></i>Data Revenue
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" 
                                id="tab-mapping-am-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#tab-mapping-am" 
                                type="button"
                                style="border: none; border-bottom: 3px solid transparent; padding: 1rem 1.5rem; font-weight: 600; color: #6c757d;">
                            <i class="fa-solid fa-users-gear me-2"></i>Mapping AM
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="tab-content" id="editRevenueCCTabContent" style="background: #f8f9fa;">
                <!-- TAB 1: Data Revenue -->
                <div class="tab-pane fade show active" id="tab-revenue-data" role="tabpanel">
                    <form id="formEditRevenueCC">
                        <div class="modal-body" style="padding: 2rem;">
                            <input type="hidden" id="editCCRevenueId">
                            
                            <!-- CC Info Section -->
                            <div class="card mb-4" style="border: 2px solid #e9ecef; border-radius: 12px;">
                                <div class="card-header" style="background: white; border-bottom: 2px solid #e9ecef; padding: 1rem 1.5rem;">
                                    <h6 class="mb-0" style="color: #495057; font-weight: 600;">
                                        <i class="fa-solid fa-info-circle me-2 text-primary"></i>
                                        Informasi Corporate Customer
                                    </h6>
                                </div>
                                <div class="card-body" style="padding: 1.5rem;">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label" style="font-weight: 600; color: #6c757d; font-size: 0.85rem;">Standard Name</label>
                                            <input type="text" class="form-control" id="editCCNamaCC" readonly style="background: #f8f9fa; border: 2px solid #e9ecef; font-weight: 600;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" style="font-weight: 600; color: #6c757d; font-size: 0.85rem;">NIPNAS</label>
                                            <input type="text" class="form-control" id="editCCNipnas" readonly style="background: #f8f9fa; border: 2px solid #e9ecef; font-weight: 600;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" style="font-weight: 600; color: #6c757d; font-size: 0.85rem;">Segment</label>
                                            <input type="text" class="form-control" id="editCCSegment" readonly style="background: #f8f9fa; border: 2px solid #e9ecef; font-weight: 600;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" style="font-weight: 600; color: #6c757d; font-size: 0.85rem;">Divisi</label>
                                            <input type="text" class="form-control" id="editCCDivisi" readonly style="background: #f8f9fa; border: 2px solid #e9ecef; font-weight: 600;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" style="font-weight: 600; color: #6c757d; font-size: 0.85rem;">Periode</label>
                                            <input type="text" class="form-control" id="editCCPeriode" readonly style="background: #f8f9fa; border: 2px solid #e9ecef; font-weight: 600;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" style="font-weight: 600; color: #6c757d; font-size: 0.85rem;">Tipe Revenue</label>
                                            <input type="text" class="form-control" id="editCCTipeRevenue" readonly style="background: #f8f9fa; border: 2px solid #e9ecef; font-weight: 600;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ✅ UPDATED: Edit Revenue Section - 3 Separate Fields -->
                            <div class="card" style="border: 2px solid #ffc107; border-radius: 12px; box-shadow: 0 4px 12px rgba(255,193,7,0.15);">
                                <div class="card-header" style="background: linear-gradient(135deg, #fff8e6 0%, #fff0cc 100%); border-bottom: 2px solid #ffc107; padding: 1rem 1.5rem;">
                                    <h6 class="mb-0" style="color: #856404; font-weight: 700;">
                                        <i class="fa-solid fa-edit me-2"></i>Edit Revenue
                                    </h6>
                                </div>
                                <div class="card-body" style="padding: 1.5rem;">
                                    <div class="row g-3">
                                        <!-- Target Revenue Sold -->
                                        <div class="col-md-4">
                                            <label class="form-label" style="font-weight: 600; color: #495057;">
                                                Target Revenue Sold <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="editCCTargetRevenueSold" 
                                                   required
                                                   min="0"
                                                   step="0.01"
                                                   style="border: 2px solid #e9ecef; border-radius: 10px; padding: 0.75rem; font-weight: 600; font-size: 1.1rem;">
                                        </div>
                                        
                                        <!-- Real Revenue Sold -->
                                        <div class="col-md-4">
                                            <label class="form-label" style="font-weight: 600; color: #495057;">
                                                Real Revenue Sold <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="editCCRealRevenueSold" 
                                                   required
                                                   min="0"
                                                   step="0.01"
                                                   style="border: 2px solid #e9ecef; border-radius: 10px; padding: 0.75rem; font-weight: 600; font-size: 1.1rem;">
                                        </div>
                                        
                                        <!-- Real Revenue Bill -->
                                        <div class="col-md-4">
                                            <label class="form-label" style="font-weight: 600; color: #495057;">
                                                Real Revenue Bill <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="editCCRealRevenueBill" 
                                                   required
                                                   min="0"
                                                   step="0.01"
                                                   style="border: 2px solid #e9ecef; border-radius: 10px; padding: 0.75rem; font-weight: 600; font-size: 1.1rem;">
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info mt-3" style="border-left: 4px solid #0066cc; background: #e7f3ff; border-radius: 10px;">
                                        <i class="fa-solid fa-calculator me-2"></i>
                                        <strong>Info:</strong> Perubahan revenue akan otomatis recalculate proporsi ke semua AM terkait (via database trigger)
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-footer" style="border-top: 2px solid #e9ecef; padding: 1.25rem 2rem; background: white;">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border: 2px solid #e9ecef; border-radius: 10px; padding: 0.75rem 1.5rem; font-weight: 600;">
                                <i class="fa-solid fa-times me-2"></i>Batal
                            </button>
                            <button type="submit" class="btn btn-danger" style="border: none; border-radius: 10px; padding: 0.75rem 2rem; font-weight: 600; box-shadow: 0 4px 12px rgba(220,53,69,0.3);">
                                <i class="fa-solid fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- TAB 2: Mapping AM -->
                <div class="tab-pane fade" id="tab-mapping-am" role="tabpanel">
                    <div class="modal-body" style="padding: 2rem;">
                        <div id="mappingAmContent">
                            <!-- Will be populated by JavaScript when tab is clicked -->
                            <div class="text-center text-muted py-5">
                                <i class="fa-solid fa-spinner fa-spin fa-3x mb-3"></i>
                                <p>Loading mapping AM...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Other Edit Modals (Revenue AM, Data AM, Data CC) -->
<!-- Modal Edit Revenue AM -->
<div class="modal fade" id="modalEditRevenueAM" tabindex="-1" aria-labelledby="modalEditRevenueAMLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
            <!-- Header -->
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none;">
                <h5 class="modal-title" id="modalEditRevenueAMLabel" style="color: white;">
                    <i class="fa-solid fa-pen-to-square me-2"></i>Edit Revenue AM
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
            </div>
            
            <form id="formEditRevenueAM">
                <div class="modal-body" style="padding: 2rem; background: #f8f9fa;">
                    <input type="hidden" id="editAMRevenueId">
                    <input type="hidden" id="editAMCCRevenueId">
                    <input type="hidden" id="editAMCCTargetSold">
                    <input type="hidden" id="editAMCCRealSold">
                    
                    <!-- ============================================
                         📋 HEADER SECTION
                         ============================================ -->
                    <div class="alert" style="background: linear-gradient(135deg, #e7f3ff 0%, #d4e9ff 100%); border-left: 4px solid #0066cc; border-radius: 10px; margin-bottom: 1.5rem;">
                        <h6 style="margin: 0; color: #004080; font-size: 1rem; line-height: 1.6;">
                            <i class="fa-solid fa-user-tie me-2"></i>
                            <strong>AM:</strong> <span id="editAMHeaderNama">Loading...</span>
                            <span class="mx-2">•</span>
                            <i class="fa-solid fa-building me-2"></i>
                            <strong>Customer:</strong> <span id="editAMHeaderCC">Loading...</span>
                            <span class="mx-2">•</span>
                            <i class="fa-solid fa-calendar-days me-2"></i>
                            <strong>Periode:</strong> <span id="editAMHeaderPeriod">Loading...</span>
                        </h6>
                    </div>
                    
                    <!-- ============================================
                         📊 CC INFO SECTION
                         ============================================ -->
                    <div class="card mb-4" style="border: 2px solid #e9ecef; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <div class="card-header" style="background: white; border-bottom: 2px solid #e9ecef; padding: 1rem 1.5rem;">
                            <h6 class="mb-0" style="color: #495057; font-weight: 600;">
                                <i class="fa-solid fa-info-circle me-2 text-primary"></i>
                                Informasi Corporate Customer
                            </h6>
                        </div>
                        <div class="card-body" style="padding: 1.5rem;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 600; color: #6c757d; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                        Standard Name
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="editAMCCName" 
                                           readonly 
                                           style="background: #f8f9fa; border: 2px solid #e9ecef; font-weight: 600; color: #212529;">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 600; color: #6c757d; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                        NIPNAS
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="editAMCCNipnas" 
                                           readonly 
                                           style="background: #f8f9fa; border: 2px solid #e9ecef; font-weight: 600; color: #212529;">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 600; color: #6c757d; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                        Real Revenue (BASE)
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="editAMCCRealDisplay" 
                                           readonly 
                                           style="background: #e7ffe7; border: 2px solid #28a745; font-weight: 700; color: #155724; font-size: 1.1rem;">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 600; color: #6c757d; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                        Target Revenue (BASE)
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="editAMCCTargetDisplay" 
                                           readonly 
                                           style="background: #e7f3ff; border: 2px solid #0056b3; font-weight: 700; color: #004085; font-size: 1.1rem;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ============================================
                         ✏️ DATA MAPPING AM SECTION
                         ============================================ -->
                    <div class="card" style="border: 2px solid #ffc107; border-radius: 12px; box-shadow: 0 4px 12px rgba(255,193,7,0.15);">
                        <div class="card-header" style="background: linear-gradient(135deg, #fff8e6 0%, #fff0cc 100%); border-bottom: 2px solid #ffc107; padding: 1rem 1.5rem;">
                            <h6 class="mb-0" style="color: #856404; font-weight: 700;">
                                <i class="fa-solid fa-users-gear me-2"></i>
                                Data Mapping Account Manager
                            </h6>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <div class="table-responsive">
                                <table class="table mb-0" style="border-collapse: separate; border-spacing: 0;">
                                    <thead>
                                        <tr style="background: #f8f9fa;">
                                            <th style="padding: 1rem; border-bottom: 2px solid #dee2e6; font-weight: 700; color: #495057; width: 120px;">NIK</th>
                                            <th style="padding: 1rem; border-bottom: 2px solid #dee2e6; font-weight: 700; color: #495057;">Nama AM</th>
                                            <th style="padding: 1rem; border-bottom: 2px solid #dee2e6; font-weight: 700; color: #495057; width: 180px;">
                                                Proporsi (%)
                                                <i class="fa-solid fa-circle-question ms-1" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Total proporsi semua AM harus = 100%"
                                                   style="color: #6c757d; cursor: help;"></i>
                                            </th>
                                            <th style="padding: 1rem; border-bottom: 2px solid #dee2e6; font-weight: 700; color: #495057; text-align: right; width: 180px;">Target Revenue</th>
                                            <th style="padding: 1rem; border-bottom: 2px solid #dee2e6; font-weight: 700; color: #495057; text-align: right; width: 180px;">Real Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody id="editAMTableBody">
                                        <!-- Will be populated by JavaScript -->
                                        <tr>
                                            <td colspan="5" class="text-center" style="padding: 2rem;">
                                                <i class="fa-solid fa-spinner fa-spin fa-2x text-muted"></i>
                                                <p class="mt-2 text-muted">Loading mapping data...</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-top: 3px solid #dee2e6;">
                                            <th colspan="2" style="padding: 1rem; font-weight: 700; color: #212529; font-size: 1rem;">
                                                <i class="fa-solid fa-calculator me-2"></i>Total Proporsi
                                            </th>
                                            <th style="padding: 1rem;">
                                                <span id="editAMTotalProporsi" style="font-size: 1.2rem; font-weight: 700;">0%</span>
                                            </th>
                                            <th colspan="2" style="padding: 1rem;"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ✅ Validation Alert -->
                    <div id="editAMProporsiAlert" class="alert mt-3" style="display: none; border-radius: 10px; border-left-width: 4px;"></div>
                    
                </div>
                
                <div class="modal-footer" style="border-top: 2px solid #e9ecef; padding: 1.25rem 2rem; background: white;">
                <button type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                        style="background: #f1f3f5; border: 2px solid #dee2e6; color: #495057; border-radius: 10px; padding: 0.75rem 1.5rem; font-weight: 600;">
                    <i class="fa-solid fa-times me-2"></i>Batal
                </button>

                <button type="submit"
                        class="btn btn-primary"
                        id="btnSaveEditAM"
                        style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border: none; border-radius: 10px; padding: 0.75rem 2rem; font-weight: 600; box-shadow: 0 4px 12px rgba(220,53,69,0.3);">
                    <i class="fa-solid fa-save me-2"></i>Simpan Perubahan
                </button>

                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal Edit Data AM -->
<div class="modal fade" id="modalEditDataAM" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Data AM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- ✅ TABS NAV -->
                <ul class="nav nav-tabs mb-3" id="editDataAMTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-edit-data-tab" data-bs-toggle="tab" data-bs-target="#tab-edit-data" type="button">Data AM</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-change-password-tab" data-bs-toggle="tab" data-bs-target="#tab-change-password" type="button">Ganti Password</button>
                    </li>
                </ul>

                <!-- TAB CONTENT -->
                <div class="tab-content">
                    <!-- Tab 1: Edit Data -->
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
                                <select class="form-select" id="editDataAMWitel" required></select>
                            </div>

                            <!-- ✅ TELDA WRAPPER -->
                            <div class="mb-3" id="editDataAMTeldaWrapper">
                                <label class="form-label">TELDA</label>
                                <select class="form-select" id="editDataAMTelda"></select>
                            </div>

                            <!-- ✅ DIVISI BUTTON GROUP -->
                            <div class="mb-3">
                                <label class="form-label">Divisi</label>
                                <div class="divisi-button-group" id="divisiButtonGroup"></div>
                                <div class="divisi-hidden-container" id="divisiHiddenInputs"></div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Simpan</button>
                        </form>
                    </div>

                    <!-- Tab 2: Change Password -->
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

<!-- ✨ Progress Snackbar (Pojok Kanan Bawah) -->
<div id="progressSnackbar" class="progress-snackbar">
    <div class="snackbar-header" onclick="toggleSnackbar()">
        <span class="snackbar-title">
            <i class="fa-solid fa-upload"></i>
            <span id="snackbarTitleText">Importing Data...</span>
        </span>
        <div class="snackbar-actions">
            <button class="btn-minimize" onclick="event.stopPropagation(); toggleSnackbar()">
                <i class="fa-solid fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="snackbar-body">
        <div class="progress-container">
            <div class="progress-bar-fill" id="progressBarFill" style="width: 0%">
                <span id="progressText">0%</span>
            </div>
        </div>
        <div class="progress-details">
            <small class="progress-status" id="progressStatus">Memulai import...</small>
        </div>
    </div>
</div>

<!-- Variant A: 2 AMs (Auto-adjust confirmation) -->
<div class="modal fade delete-confirm-modal" id="modalDeleteRevenueAMAutoAdjust" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Konfirmasi Hapus Revenue AM</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <div class="delete-warning-box">
                    <div class="d-flex align-items-start">
                        <i class="fa-solid fa-info-circle warning-icon"></i>
                        <div class="flex-grow-1">
                            <div class="warning-title">Proporsi Otomatis Disesuaikan</div>
                            <div class="warning-text" id="deleteAMAutoAdjustMessage">
                                <!-- Dynamic content -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-success" style="border-left: 4px solid #28a745; background: #d4edda;">
                    <i class="fa-solid fa-check-circle me-2"></i>
                    <strong>AM lain akan otomatis menerima 100% proporsi</strong> untuk CC ini.
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times"></i> Batal
                </button>
                <button type="button" class="btn btn-delete-confirm" id="btnConfirmDeleteAMAutoAdjust">
                    <i class="fa-solid fa-trash"></i> Ya, Hapus!
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Variant B: 3+ AMs (Manual redistribution) -->
<div class="modal fade" id="modalDeleteRevenueAMManualRedistribute" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none;">
                <h5 class="modal-title" style="color: white;">
                    <i class="fa-solid fa-users-gear me-2"></i>Atur Ulang Proporsi AM
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: brightness(0) invert(1);"></button>
            </div>
            
            <div class="modal-body" style="padding: 2rem; background: #f8f9fa;">
                <div class="alert" style="background: linear-gradient(135deg, #fff8e6 0%, #fff0cc 100%); border-left: 4px solid #ff9800; border-radius: 10px;">
                    <div class="d-flex align-items-start">
                        <i class="fa-solid fa-exclamation-triangle" style="color: #ff9800; font-size: 1.5rem; margin-right: 1rem;"></i>
                        <div>
                            <strong style="color: #995c00; display: block; margin-bottom: 0.5rem;">Atur Proporsi Ulang</strong>
                            <p style="color: #856404; margin: 0;">
                                Setelah <strong id="deleteAMNameToDelete"></strong> dihapus, 
                                atur proporsi untuk <strong id="deleteAMRemainingCount"></strong> AM lainnya:
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="card" style="border: 2px solid #e9ecef; border-radius: 12px;">
                    <div class="card-body" style="padding: 0;">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr style="background: #f8f9fa;">
                                        <th style="padding: 1rem; border-bottom: 2px solid #dee2e6;">NIK</th>
                                        <th style="padding: 1rem; border-bottom: 2px solid #dee2e6;">Nama AM</th>
                                        <th style="padding: 1rem; border-bottom: 2px solid #dee2e6; width: 200px;">
                                            Proporsi Baru (%)
                                            <i class="fa-solid fa-circle-question ms-1" data-bs-toggle="tooltip" title="Total harus = 100%"></i>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="deleteAMRedistributeTableBody">
                                    <!-- Will be populated by JavaScript -->
                                </tbody>
                                <tfoot>
                                    <tr style="background: #f8f9fa; border-top: 3px solid #dee2e6;">
                                        <th colspan="2" style="padding: 1rem;">
                                            <i class="fa-solid fa-calculator me-2"></i>Total Proporsi
                                        </th>
                                        <th style="padding: 1rem;">
                                            <span id="deleteAMRedistributeTotalProporsi" style="font-size: 1.2rem; font-weight: 700;">0%</span>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div id="deleteAMRedistributeAlert" class="alert mt-3" style="display: none; border-radius: 10px; border-left-width: 4px;"></div>
            </div>
            
            <div class="modal-footer" style="border-top: 2px solid #e9ecef; padding: 1.25rem 2rem;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border: 2px solid #e9ecef; border-radius: 10px; padding: 0.75rem 1.5rem; font-weight: 600;">
                    <i class="fa-solid fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmDeleteAMWithRedistribute" disabled style="border: none; border-radius: 10px; padding: 0.75rem 2rem; font-weight: 600;">
                    <i class="fa-solid fa-trash me-2"></i>Hapus & Simpan Proporsi
                </button>
            </div>
        </div>
    </div>
</div>




<!-- Delete Confirmation Modal - Modern Design -->
<div class="modal fade delete-confirm-modal" id="deleteConfirmModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Konfirmasi Hapus Data</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- ✅ HEADER SECTION - Show AM, CC, Period -->
            <div class="modal-header-info" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem; border-radius: 12px 12px 0 0; color: white; margin: -1rem -1rem 1.5rem -1rem;">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-user-tie fa-2x me-3"></i>
                            <div>
                                <small style="opacity: 0.9; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Account Manager</small>
                                <h6 class="mb-0" id="editAMHeaderNama" style="font-weight: 700; font-size: 1.1rem;">Loading...</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-building fa-2x me-3"></i>
                            <div>
                                <small style="opacity: 0.9; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Corporate Customer</small>
                                <h6 class="mb-0" id="editAMHeaderCC" style="font-weight: 700; font-size: 1.1rem;">Loading...</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-calendar-alt fa-2x me-3"></i>
                            <div>
                                <small style="opacity: 0.9; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Periode</small>
                                <h6 class="mb-0" id="editAMHeaderPeriod" style="font-weight: 700; font-size: 1.1rem;">Loading...</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <!-- Warning Message -->
                <div class="delete-warning-box">
                    <div class="d-flex align-items-start">
                        <i class="fa-solid fa-exclamation-circle warning-icon"></i>
                        <div class="flex-grow-1">
                            <div class="warning-title" id="warningTitle">Peringatan!</div>
                            <div class="warning-text" id="warningText"></div>
                        </div>
                    </div>
                </div>

                <!-- Related Data Info -->
                <div id="relatedDataInfo" class="related-data-card" style="display: none;">
                    <h6>Data yang akan ikut terhapus:</h6>
                    <table class="related-data-table">
                        <tbody>
                            <tr>
                                <td>Revenue CC</td>
                                <td><span id="relatedCcRevenues">0</span> data</td>
                            </tr>
                            <tr>
                                <td>Revenue AM</td>
                                <td><span id="relatedAmRevenues">0</span> data</td>
                            </tr>
                            <tr class="total-row">
                                <td>Total</td>
                                <td><span id="relatedTotal">0</span> data</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Log Option (only for Data CC) -->
                <div id="logOptionSection" class="delete-options-card" style="display: none;">
                    <h6>Pilih Metode Hapus:</h6>
                    
                    <div class="delete-option-radio">
                        <input type="radio" name="deleteType" id="deletePermanent" value="permanent" checked>
                        <label for="deletePermanent">
                            <span class="option-title">Hapus Permanen</span>
                            <span class="option-description">Data langsung dihapus dari database tanpa backup</span>
                        </label>
                    </div>

                    <div class="delete-option-radio">
                        <input type="radio" name="deleteType" id="deleteWithLog" value="with_log">
                        <label for="deleteWithLog">
                            <span class="option-title">Hapus dengan Simpan Log</span>
                            <span class="option-description">Data disimpan sebagai log JSON sebelum dihapus</span>
                        </label>
                    </div>
                </div>

                <!-- Confirmation Text for DELETE ALL -->
                <div id="confirmTextSection" class="danger-zone-card" style="display: none;">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fa-solid fa-skull-crossbones danger-icon"></i>
                        <div class="danger-title">PERINGATAN SERIUS!</div>
                    </div>
                    <p class="danger-text">Anda akan menghapus SEMUA data. Tindakan ini sangat berbahaya!</p>
                    <p class="danger-text mb-3">Ketik <strong>DELETE ALL</strong> untuk konfirmasi:</p>
                    <input type="text" class="form-control confirmation-input" id="confirmationText" placeholder="DELETE ALL" autocomplete="off">
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times"></i>
                    Batal
                </button>
                <button type="button" class="btn btn-delete-confirm" id="btnConfirmDelete">
                    <i class="fa-solid fa-trash"></i>
                    Ya, Hapus!
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
<script>
/**
 * ================================================================
 * REVENUE DATA MANAGEMENT - COMPLETE JAVASCRIPT
 * ================================================================
 * Version: 2.1 - FIXED
 * Date: 2026-02-04
 * 
 * FIXES APPLIED:
 * ✅ Badge counter now uses filtered results (recordsFiltered)
 * ✅ Revenue badge labels: HO → REVENUE SOLD, BILL → REVENUE BILL
 * ✅ Removed REGULER/NGTMA badges completely
 * ✅ showPreviewModal() handles multiple response structures (summary/stats/statistics)
 * ✅ Month picker active state properly managed (remove all before add)
 * ✅ Target Witel dropdown includes DGS
 * ✅ All routes match web.php exactly
 * ================================================================
 */

$(document).ready(function() {
  // ========================================
  // 🎯 STATE MANAGEMENT - COMPLETE
  // ========================================
  let pendingDeleteAction = null; // Stores delete action info
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

  // ✨ Preview Import State
  let previewData = null;
  let currentImportType = null;
  let currentFormData = null;
  let currentSessionId = null;

  // ✅ Year/month for revenue imports
  let currentImportYear = null;
  let currentImportMonth = null;

  // ✅ NEW: Revenue CC import parameters (CRITICAL FIX)
  let currentImportDivisiId = null;
  let currentImportTipeRevenue = null;
  let currentImportJenisData = null;

  // ✅ View mode state for Revenue CC
  let currentViewMode = 'default'; // 'default' or 'all'

  const ROWS_PER_CHUNK = 5000;
  const SIZE_THRESHOLD = 5 * 1024 * 1024;

  // ========================================
  // 🎨 CUSTOM SELECT ENHANCEMENT
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
  // 📅 ENHANCED FLATPICKR MONTH YEAR PICKER
  // UNLIMITED YEAR NAVIGATION (NO LIMITS)
  // ✅ FIXED: Active state properly managed
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
    const YEAR_CEILING = 2100;

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
      onMonthChange: function() {},
      onYearChange: function() {},

      onReady(selectedDates, value, instance) {
        fpInstance = instance;
        const d = selectedDates?.[0] || new Date();
        selectedYear  = d.getFullYear();
        selectedMonth = d.getMonth();

        hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
        hiddenYear.value  = selectedYear;

        instance.calendarContainer.classList.add('fp-compact');
        syncCalendarWidth(instance);
        setupCustomUI(instance);
      },

      onOpen(selectedDates, value, instance) {
        fpInstance = instance;
        renderMonthView(instance);
        syncCalendarWidth(instance);

        setTimeout(() => {
          const activeMonth = instance.calendarContainer.querySelector('.mp-month-btn.active');
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

      const numInputWrapper = cal.querySelector('.numInputWrapper');
      if (numInputWrapper) {
        numInputWrapper.style.display = 'none';
      }

      const currentYearElement = cal.querySelector('.cur-year, .numInput');
      if (currentYearElement) {
        currentYearElement.style.display = 'none';
      }
    }

    // ========================================
    // ✅ FIXED: RENDER MONTH VIEW - Active State Management
    // ========================================
    function renderMonthView(instance) {
      const cal = instance.calendarContainer;
      const header = cal.querySelector('.flatpickr-current-month');

      if (header) {
        header.innerHTML = `
          <div class="mp-header-wrapper">
            <button type="button" class="mp-nav-btn mp-prev">
              <i class="fa-solid fa-chevron-left"></i>
            </button>
            
            <input type="text" 
                   class="mp-year-input" 
                   id="mp-year-input"
                   value="${selectedYear}" 
                   maxlength="4"
                   inputmode="numeric"
                   pattern="[0-9]*">
            
            <button type="button" class="mp-nav-btn mp-next">
              <i class="fa-solid fa-chevron-right"></i>
            </button>
          </div>
        `;

        const prevBtn = header.querySelector('.mp-prev');
        const nextBtn = header.querySelector('.mp-next');
        const yearInput = header.querySelector('#mp-year-input');

        if (prevBtn) {
          prevBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            selectedYear--;
            hiddenYear.value = selectedYear;
            renderMonthView(instance);
          });
        }

        if (nextBtn) {
          nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            selectedYear++;
            hiddenYear.value = selectedYear;
            renderMonthView(instance);
          });
        }

        if (yearInput) {
          yearInput.addEventListener('click', (e) => {
            e.stopPropagation();
          });

          yearInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
          });

          yearInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
              e.preventDefault();
              yearInput.blur();
            }
          });

          yearInput.addEventListener('blur', (e) => {
            const newYear = parseInt(e.target.value);
            
            if (isNaN(newYear) || newYear < YEAR_FLOOR || newYear > YEAR_CEILING) {
              e.target.value = selectedYear;
              alert(`Tahun harus antara ${YEAR_FLOOR} - ${YEAR_CEILING}`);
              return;
            }

            if (newYear !== selectedYear) {
              selectedYear = newYear;
              hiddenYear.value = selectedYear;
              renderMonthView(instance);
            }
          });

          yearInput.addEventListener('focus', (e) => {
            e.target.select();
          });
        }
      }

      // ========================================
      // ✅ FIXED: RENDER MONTH GRID WITH PROPER ACTIVE STATE
      // ========================================
      let container = cal.querySelector('.fp-month-grid, .fp-year-grid, .flatpickr-monthSelect-months, .monthSelect-months, .flatpickr-innerContainer');
      if (!container) return;

      container.innerHTML = '';
      container.className = 'mp-month-grid';
      container.setAttribute('tabindex', '0');

      const monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

      monthNames.forEach((name, idx) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mp-month-btn';
        btn.textContent = name;

        const currentSelectedDate = fp.selectedDates[0] || new Date();
        if (idx === selectedMonth && selectedYear === currentSelectedDate.getFullYear()) {
          btn.classList.add('active');
        }

        btn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          
          // ✅ FIX: Remove active class from ALL buttons first
          container.querySelectorAll('.mp-month-btn').forEach(b => b.classList.remove('active'));
          
          // Then add active to clicked button
          btn.classList.add('active');
          
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

      const activeMonth = container.querySelector('.mp-month-btn.active');
      if (activeMonth) {
        activeMonth.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }

    // ========================================
    // RESET FILTER HANDLER
    // ========================================
    const resetBtn = document.getElementById('btn-reset-filter');
    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        const now = new Date();
        selectedYear  = now.getFullYear();
        selectedMonth = now.getMonth();

        fp.setDate(now, true);
        hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
        hiddenYear.value  = selectedYear;
      });
    }
  })();

  // ========================================
  // 📅 FLATPICKR FOR IMPORT MODALS - UNLIMITED NAVIGATION
  // ✅ FIXED: Active state properly managed
  // ========================================
  (function initImportMonthPickers() {
    const YEAR_FLOOR = 2020;
    const YEAR_CEILING = 2100;

    function createMonthPicker(inputId, hiddenMonthId, hiddenYearId) {
      const dateInput = document.getElementById(inputId);
      const hiddenMonth = document.getElementById(hiddenMonthId);
      const hiddenYear = document.getElementById(hiddenYearId);

      if (!dateInput) return null;

      let selectedYear = new Date().getFullYear();
      let selectedMonth = new Date().getMonth();

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
        onMonthChange: function() {},
        onYearChange: function() {},

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
          renderMonthView(instance);
        }
      });

      function setupCustomUI(instance) {
        const cal = instance.calendarContainer;
        
        const monthsContainer = cal.querySelector('.flatpickr-monthSelect-months, .monthSelect-months');
        if (monthsContainer) {
          monthsContainer.style.display = 'none';
        }

        const numInputWrapper = cal.querySelector('.numInputWrapper');
        if (numInputWrapper) {
          numInputWrapper.style.display = 'none';
        }

        const currentYearElement = cal.querySelector('.cur-year, .numInput');
        if (currentYearElement) {
          currentYearElement.style.display = 'none';
        }
      }

      function renderMonthView(instance) {
        const cal = instance.calendarContainer;
        const header = cal.querySelector('.flatpickr-current-month');

        if (header) {
          header.innerHTML = `
            <div class="mp-header-wrapper">
              <button type="button" class="mp-nav-btn mp-prev">
                <i class="fa-solid fa-chevron-left"></i>
              </button>
              
              <input type="text" 
                     class="mp-year-input" 
                     value="${selectedYear}" 
                     maxlength="4"
                     inputmode="numeric"
                     pattern="[0-9]*">
              
              <button type="button" class="mp-nav-btn mp-next">
                <i class="fa-solid fa-chevron-right"></i>
              </button>
            </div>
          `;

          const prevBtn = header.querySelector('.mp-prev');
          const nextBtn = header.querySelector('.mp-next');
          const yearInput = header.querySelector('.mp-year-input');

          if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
              e.preventDefault();
              e.stopPropagation();
              selectedYear--;
              hiddenYear.value = selectedYear;
              renderMonthView(instance);
            });
          }

          if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
              e.preventDefault();
              e.stopPropagation();
              selectedYear++;
              hiddenYear.value = selectedYear;
              renderMonthView(instance);
            });
          }

          if (yearInput) {
            yearInput.addEventListener('click', (e) => {
              e.stopPropagation();
            });

            yearInput.addEventListener('input', (e) => {
              e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });

            yearInput.addEventListener('keydown', (e) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                yearInput.blur();
              }
            });

            yearInput.addEventListener('blur', (e) => {
              const newYear = parseInt(e.target.value);
              
              if (isNaN(newYear) || newYear < YEAR_FLOOR || newYear > YEAR_CEILING) {
                e.target.value = selectedYear;
                alert(`Tahun harus antara ${YEAR_FLOOR} - ${YEAR_CEILING}`);
                return;
              }

              if (newYear !== selectedYear) {
                selectedYear = newYear;
                hiddenYear.value = selectedYear;
                renderMonthView(instance);
              }
            });

            yearInput.addEventListener('focus', (e) => {
              e.target.select();
            });
          }
        }

        let container = cal.querySelector('.fp-month-grid, .fp-year-grid, .flatpickr-monthSelect-months, .monthSelect-months, .flatpickr-innerContainer');
        if (!container) return;

        container.innerHTML = '';
        container.className = 'mp-month-grid';

        const monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

        monthNames.forEach((name, idx) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'mp-month-btn';
          btn.textContent = name;

          if (idx === selectedMonth && selectedYear === fp.selectedDates[0].getFullYear()) {
            btn.classList.add('active');
          }

          btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            // ✅ FIX: Remove active from all buttons first
            container.querySelectorAll('.mp-month-btn').forEach(b => b.classList.remove('active'));
            
            // Then add to clicked button
            btn.classList.add('active');
            
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

      return fp;
    }

    let importCCPicker = null;
    let importAMPicker = null;
    let importWitelPicker = null;

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
          if (importWitelPicker) {
            importWitelPicker.destroy();
            importWitelPicker = null;
          }

          if (document.getElementById('import-cc-periode')) {
            importCCPicker = createMonthPicker('import-cc-periode', 'import-cc-month', 'import-cc-year');
          }

          if (document.getElementById('import-am-periode')) {
            importAMPicker = createMonthPicker('import-am-periode', 'import-am-month', 'import-am-year');
          }

          if (document.getElementById('import-witel-periode')) {
            importWitelPicker = createMonthPicker('import-witel-periode', 'import-witel-month', 'import-witel-year');
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
        if (importWitelPicker) {
          importWitelPicker.destroy();
          importWitelPicker = null;
        }

        document.querySelectorAll('.imp-panel form').forEach(form => {
          form.reset();
        });
      });
    }
  })();

  // ========================================
  // 📋 BUILD SEGMENT DROPDOWN UI
  // ========================================
  function buildSegmentUI(segments) {
    const nativeSelect = document.getElementById('filter-segment');
    const segTabs = document.getElementById('segTabs');
    const segPanels = document.getElementById('segPanels');

    if (!nativeSelect || !segTabs || !segPanels) {
      console.error('❌ Required elements not found:', {
        nativeSelect: !!nativeSelect,
        segTabs: !!segTabs,
        segPanels: !!segPanels
      });
      return;
    }

    segTabs.innerHTML = '';
    segPanels.innerHTML = '';
    nativeSelect.innerHTML = '<option value="">Semua Segment</option>';

    const groupedSegments = {};
    segments.forEach(segment => {
      const raw = (segment.divisi_kode || segment.divisi || '').toString().trim().toUpperCase();
      const divisiKode = raw || 'OTHER';

      if (!groupedSegments[divisiKode]) {
        groupedSegments[divisiKode] = [];
      }
      groupedSegments[divisiKode].push(segment);

      const option = document.createElement('option');
      option.value = segment.id;
      option.textContent = segment.lsegment_ho;
      nativeSelect.appendChild(option);
    });

    const ORDER = ['DPS', 'DSS', 'DGS', 'DES'];
    const keys = Object.keys(groupedSegments);
    const mainDivisi = keys.filter(k => k && k.toUpperCase() !== 'OTHER');
    const divisiList = [
      ...ORDER.filter(code => mainDivisi.includes(code)),
      ...mainDivisi.filter(code => !ORDER.includes(code)).sort()
    ];

    if (divisiList.length === 0) {
      console.warn('⚠️ No valid divisi found for segments');
      if (segments.length > 0) {
        console.log('Creating default tab for segments without divisi');
        const firstKey = Object.keys(groupedSegments)[0];
        if (firstKey && groupedSegments[firstKey].length > 0) {
          const panel = document.createElement('div');
          panel.className = 'seg-panel active';
          panel.dataset.panel = 'default';
          panel.setAttribute('role', 'tabpanel');

          const allOption = document.createElement('button');
          allOption.className = 'seg-option all';
          allOption.dataset.value = '';
          allOption.textContent = 'Semua Segment';
          allOption.type = 'button';
          panel.appendChild(allOption);

          segments.forEach(segment => {
            const optionBtn = document.createElement('button');
            optionBtn.className = 'seg-option';
            optionBtn.dataset.value = segment.id;
            optionBtn.textContent = segment.lsegment_ho;
            optionBtn.type = 'button';
            panel.appendChild(optionBtn);
          });

          segPanels.appendChild(panel);
          
          setTimeout(() => {
            initSegmentSelectInteractions();
          }, 100);
          
          console.log('✅ Default segment panel created');
          return;
        }
      }
      return;
    }

    let firstTab = true;
    let firstDivisiName = null;

    divisiList.forEach(divisi => {
      if (firstTab) {
        firstDivisiName = divisi;
      }

      const tabBtn = document.createElement('button');
      tabBtn.className = `seg-tab${firstTab ? ' active' : ''}`;
      tabBtn.dataset.tab = divisi;
      tabBtn.setAttribute('role', 'tab');
      tabBtn.setAttribute('aria-selected', firstTab ? 'true' : 'false');
      tabBtn.textContent = divisi;
      tabBtn.type = 'button';
      segTabs.appendChild(tabBtn);

      const panel = document.createElement('div');
      panel.className = `seg-panel${firstTab ? ' active' : ''}`;
      panel.dataset.panel = divisi;
      panel.setAttribute('role', 'tabpanel');

      const allOption = document.createElement('button');
      allOption.className = 'seg-option all';
      allOption.dataset.value = '';
      allOption.textContent = 'Semua Segment';
      allOption.type = 'button';
      panel.appendChild(allOption);

      const segmentList = groupedSegments[divisi] || [];
      segmentList.forEach(segment => {
        const optionBtn = document.createElement('button');
        optionBtn.className = 'seg-option';
        optionBtn.dataset.value = segment.id;
        optionBtn.textContent = segment.lsegment_ho;
        optionBtn.type = 'button';
        panel.appendChild(optionBtn);
      });

      segPanels.appendChild(panel);
      firstTab = false;
    });

    const otherItems = groupedSegments['OTHER'];
    if (firstDivisiName && Array.isArray(otherItems) && otherItems.length > 0) {
      const firstPanel = segPanels.querySelector(`.seg-panel[data-panel="${firstDivisiName}"]`);
      if (firstPanel) {
        otherItems.forEach(segment => {
          const optionBtn = document.createElement('button');
          optionBtn.className = 'seg-option';
          optionBtn.dataset.value = segment.id;
          optionBtn.textContent = segment.lsegment_ho;
          optionBtn.type = 'button';
          firstPanel.appendChild(optionBtn);
        });
      }
    }

    setTimeout(() => {
      initSegmentSelectInteractions();
    }, 100);

    console.log('✅ Segment UI built successfully:', {
      totalSegments: segments.length,
      divisiTabs: divisiList.length,
      divisiList: divisiList
    });
  }

  function initSegmentSelectInteractions() {
    const segSelect = document.getElementById('segSelect');
    
    if (!segSelect) {
      console.error('❌ segSelect element not found');
      return;
    }

    const nativeSelect = document.getElementById('filter-segment');
    const triggerBtn = segSelect.querySelector('.seg-select__btn');
    const labelSpan = segSelect.querySelector('.seg-select__label');

    if (!triggerBtn || !labelSpan || !nativeSelect) {
      console.error('❌ Required segment select elements not found:', {
        triggerBtn: !!triggerBtn,
        labelSpan: !!labelSpan,
        nativeSelect: !!nativeSelect
      });
      return;
    }

    triggerBtn.onclick = function(e) {
      e.preventDefault();
      e.stopPropagation();
      const isOpen = segSelect.classList.contains('open');
      segSelect.classList.toggle('open');
      console.log('🔽 Segment dropdown toggled:', !isOpen);
    };

    document.addEventListener('click', function(e) {
      if (!segSelect.contains(e.target)) {
        segSelect.classList.remove('open');
      }
    });

    const segTabs = segSelect.querySelectorAll('.seg-tab');
    const segPanels = segSelect.querySelectorAll('.seg-panel');

    segTabs.forEach(tab => {
      tab.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
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
        if (activePanel) {
          activePanel.classList.add('active');
        }

        console.log('📑 Tab switched to:', targetPanel);
      };
    });

    const segOptions = segSelect.querySelectorAll('.seg-option');

    segOptions.forEach(option => {
      option.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const value = option.dataset.value;
        const label = option.textContent.trim();

        console.log('✔️ Option selected:', { value, label });

        nativeSelect.value = value;
        nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));

        labelSpan.textContent = label;

        segOptions.forEach(opt => opt.removeAttribute('aria-selected'));
        option.setAttribute('aria-selected', 'true');

        if (value === '' || value === 'all') {
          segSelect.classList.add('is-all-selected');
          segSelect.classList.remove('has-value');
        } else {
          segSelect.classList.remove('is-all-selected');
          segSelect.classList.add('has-value');
        }

        setTimeout(function() {
          segSelect.classList.remove('open');
        }, 150);
      };
    });

    console.log('✅ Segment select interactions initialized:', {
      tabs: segTabs.length,
      panels: segPanels.length,
      options: segOptions.length
    });
  }

  // ========================================
  // 🎨 DIVISI BUTTON GROUP HANDLER
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
    const buttons = document.querySelectorAll('.divisi-toggle-btn');
    if (!buttons || buttons.length === 0) {
      console.warn('Divisi buttons not found');
      return;
    }

    buttons.forEach(btn => btn.classList.remove('active'));

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
  // ✅ CHECKBOX & BULK DELETE LOGIC
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
  // ✅ LOAD FILTER OPTIONS FROM BACKEND
  // ✅ FIXED: Target Witel dropdown includes DGS
  // ========================================
  function loadFilterOptions() {
    $.ajax({
      url: '/revenue-data/filter-options',
      method: 'GET',
      success: function(response) {
        console.log('✅ Filter options loaded:', response);

        // POPULATE WITEL FILTERS
        const witelSelect = $('#filterWitel');
        if (response.witels) {
          response.witels.forEach(function(witel) {
            witelSelect.append(`<option value="${witel.id}">${witel.nama}</option>`);
          });
        }

        // POPULATE DIVISI FILTERS
        const divisiSelect = $('#filterDivisi');
        const revCCDivisiImport = $('#revCCDivisiImport');
        const targetWitelDivisiImport = $('#targetWitelDivisiImport');
        
        if (response.divisions) {
          response.divisions.forEach(function(divisi) {
            divisiSelect.append(`<option value="${divisi.id}">${divisi.nama}</option>`);
            
            revCCDivisiImport.append(`<option value="${divisi.id}">${divisi.nama} (${divisi.kode})</option>`);
            
            // ✅ FIX: Include DGS in Target Witel dropdown
            if (divisi.kode === 'DGS' || divisi.kode === 'DPS' || divisi.kode === 'DSS') {
              targetWitelDivisiImport.append(`<option value="${divisi.id}">${divisi.nama} (${divisi.kode})</option>`);
            }
          });

          allDivisiData = response.divisions;
          initDivisiButtonGroup();
        }

        // POPULATE SEGMENT FILTERS
        if (response.segments && response.segments.length > 0) {
          buildSegmentUI(response.segments);
        }

        // STORE TELDA DATA GLOBALLY
        if (response.teldas) {
          allTeldaData = response.teldas;
        }

        // POPULATE EDIT DATA AM MODAL SELECTS
        const editWitelSelect = $('#editDataAMWitel');
        editWitelSelect.empty();
        editWitelSelect.append('<option value="">Pilih Witel</option>');
        if (response.witels) {
          response.witels.forEach(function(witel) {
            editWitelSelect.append(`<option value="${witel.id}">${witel.nama}</option>`);
          });
        }

        const editTeldaSelect = $('#editDataAMTelda');
        editTeldaSelect.empty();
        editTeldaSelect.append('<option value="">Pilih TELDA</option>');
        if (response.teldas) {
          response.teldas.forEach(function(telda) {
            editTeldaSelect.append(`<option value="${telda.id}">${telda.nama}</option>`);
          });
        }

        console.log('🎨 Enhancing custom selects...');
        
        setTimeout(() => {
          enhanceFilterBar();
          console.log('✅ Filter bar enhanced');
        }, 100);

        setTimeout(() => {
          enhanceModalDivisi();
          console.log('✅ Modal divisi enhanced');
        }, 100);

        console.log('✅ All filter options populated and enhanced');
      },
      error: function(xhr) {
        console.error('❌ Error loading filters:', xhr);
        alert('Gagal memuat filter options: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  }

  // ========================================
  // ✅ LOAD DATA FROM BACKEND - FIXED
  // ========================================
  function loadData() {
    let url = '';
    const params = {
      page: currentPage,
      per_page: perPage,
      search: currentFilters.search
    };

    // ✅ FIX: Only send filter params if NOT 'all'
    if (currentFilters.witel_id && currentFilters.witel_id !== 'all') {
      params.witel_id = currentFilters.witel_id;
    }
    if (currentFilters.divisi_id && currentFilters.divisi_id !== 'all') {
      params.divisi_id = currentFilters.divisi_id;
    }
    if (currentFilters.segment_id && currentFilters.segment_id !== 'all' && currentFilters.segment_id !== '') {
      params.segment_id = currentFilters.segment_id;
    }

    if (currentTab === 'tab-cc-revenue') {
      url = '/revenue-data/revenue-cc';
      if (currentFilters.periode) {
        params.periode = currentFilters.periode;
      }
      if (currentFilters.tipe_revenue && currentFilters.tipe_revenue !== 'all') {
        params.tipe_revenue = currentFilters.tipe_revenue;
      }
      params.display_mode = currentViewMode;
    } else if (currentTab === 'tab-am-revenue') {
      url = '/revenue-data/revenue-am';
      if (currentFilters.periode) {
        params.periode = currentFilters.periode;
      }
      if (currentFilters.role && currentFilters.role !== 'all') {
        params.role = currentFilters.role;
      }
    } else if (currentTab === 'tab-data-am') {
      url = '/revenue-data/data-am';
      if (currentFilters.role && currentFilters.role !== 'all') {
        params.role = currentFilters.role;
      }
    } else if (currentTab === 'tab-data-cc') {
      url = '/revenue-data/data-cc';
    }

    console.log('📤 Loading data:', { tab: currentTab, url, params });

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
        
        // ✅ FIX: Use recordsFiltered for accurate badge count
        const total = response.recordsFiltered || response.data?.length || 0;
        updateBadge(currentTab, total);

        $('[data-bs-toggle="tooltip"]').tooltip();
      },
      error: function(xhr) {
        console.error('❌ Error loading data for tab:', currentTab, xhr);
        const errorMsg = xhr.responseJSON?.message || xhr.statusText || 'Unknown error';
        showAlert('Gagal memuat data: ' + errorMsg, 'danger');
      }
    });
  }

  // ========================================
  // ✅ RENDER FUNCTIONS
  // ✅ FIXED: Revenue badge labels changed
  // ========================================
  function renderRevenueCC(response) {
    const tbody = $('#tableRevenueCC');
    tbody.empty();

    if (!response || !response.data || response.data.length === 0) {
      const colCount = currentViewMode === 'all' ? 9 : 8;
      tbody.append(`<tr><td colspan="${colCount}" class="text-center">Tidak ada data</td></tr>`);
      return;
    }

    response.data.forEach(function(item) {
      const divisiKode = item.divisi_kode || item.divisi || '-';
      const divisiDisplay = divisiKode !== '-' ? divisiKode.substring(0, 3).toUpperCase() : '-';
      const nipnas = item.nipnas || '-';
      const divisiClass = divisiDisplay !== '-' ? `badge-div ${divisiDisplay.toLowerCase()}` : '';

      const tipeRevenue = item.tipe_revenue || 'HO';
      const tipeClass = tipeRevenue.toLowerCase();

      let revenueDisplay = '';
      
      if (currentViewMode === 'default') {
        const revenueValue = tipeRevenue === 'HO' ? item.real_revenue_sold : item.real_revenue_bill;
        
        // ✅ FIXED: Changed badge labels
        const tipeLabel = tipeRevenue === 'HO' ? 'REVENUE SOLD' : 'REVENUE BILL';
        
        revenueDisplay = `
          <div class="revenue-cell">
            <span class="revenue-value">${formatCurrency(revenueValue)}</span>
            <div class="revenue-badges">
              <span class="badge-revenue-type ${tipeClass}">${tipeLabel}</span>
            </div>
          </div>
        `;
      }

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
          <td class="text-end">${formatCurrency(item.target_revenue_sold || 0)}</td>
          ${currentViewMode === 'default' ? `<td class="text-end revenue-col-default">${revenueDisplay}</td>` : ''}
          ${currentViewMode === 'all' ? `
            <td class="text-end revenue-col-all">${formatCurrency(item.real_revenue_sold || 0)}</td>
            <td class="text-end revenue-col-all">${formatCurrency(item.real_revenue_bill || 0)}</td>
          ` : ''}
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

  // ================================================================
// ✅ FIXED: renderRevenueAM() - CORRECT FIELD NAMES
// Replace this function in your blade file (around line 6270)
// ================================================================

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
        
        // ✅ FIX #1: Use correct field names from backend response
        const divisiKode = item.divisi_kode || '-';
        const divisiDisplay = divisiKode !== '-' ? divisiKode.substring(0, 3).toUpperCase() : '-';
        const divisiClass = divisiDisplay !== '-' ? `badge-div ${divisiDisplay.toLowerCase()}` : '';

        const teldaDisplay = role === 'HOTDA' ? (item.telda_nama || '-') : '-';
        const achievementPercent = item.achievement ? parseFloat(item.achievement).toFixed(2) : '0.00';

        const namaCC = item.corporate_customer_nama || item.nama_cc || 'N/A';
        const namaAM = item.account_manager_nama || item.nama_am || 'N/A';

        const row = `
            <tr>
                <td><input type="checkbox" class="row-checkbox-am" data-id="${item.id}"></td>
                <td>
                    <strong style="font-size: 1rem; font-weight: 700;">${namaAM}</strong><br>
                    <small>
                        <span class="${roleClass}">${role}</span>
                        ${divisiDisplay !== '-' ? `<span class="${divisiClass}" style="margin-left: 4px;">${divisiDisplay}</span>` : ''}
                    </small>
                </td>
                <td>${namaCC}</td>
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
  // ✅ PAGINATION
  // ========================================
  function renderPagination(response) {
    const container = currentTab === 'tab-cc-revenue' ? $('#paginationRevenueCC') :
                      currentTab === 'tab-am-revenue' ? $('#paginationRevenueAM') :
                      currentTab === 'tab-data-am' ? $('#paginationDataAM') : $('#paginationDataCC');

    container.empty();

    const from = response.from || 0;
    const to = response.to || 0;
    const total = response.recordsFiltered || response.recordsTotal || response.total || 0;
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
  // ✅ UPDATE BADGE COUNTER - FIXED
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
      // ✅ FIX: Ensure count is never undefined
      const displayCount = count || 0;
      $('#' + badgeId).text(displayCount);
    }
  }

  // ========================================
  // ✅ TAB SWITCHING
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
  // ✅ FILTER HANDLERS
  // ========================================
  $('#searchForm').submit(function(e) {
    e.preventDefault();
    currentFilters.search = $('#searchInput').val();
    currentPage = 1;
    loadData();
  });

  $('#filterWitel, #filterDivisi, #filter-segment').on('change', function() {
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
    
    const segLabel = $('.seg-select__label');
    if (segLabel) {
      segLabel.text('Semua Segment');
    }

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
    currentPage = 1;
    loadData();
  });

  // ========================================
  // ✅ VIEW MODE TOGGLE (Revenue CC)
  // ========================================
  $('#viewModeToggle button').click(function() {
    const mode = $(this).data('mode');
    
    if (currentViewMode === mode) return;

    $('#viewModeToggle button').removeClass('active');
    $(this).addClass('active');
    currentViewMode = mode;

    const tableWrap = $('#revenueTableWrap');
    const defaultCols = $('.revenue-col-default');
    const allCols = $('.revenue-col-all');

    if (mode === 'default') {
      tableWrap.removeClass('all-columns');
      defaultCols.show();
      allCols.hide();
    } else {
      tableWrap.addClass('all-columns');
      defaultCols.hide();
      allCols.show();
    }

    loadData();
  });

  // ========================================
  // ✅ PROGRESS SNACKBAR FUNCTIONS
  // ========================================
  let progressSnackbar = null;

  function showProgressSnackbar(title) {
    progressSnackbar = document.getElementById('progressSnackbar');
    if (!progressSnackbar) {
      console.warn('Progress snackbar element not found');
      return;
    }

    const titleText = document.getElementById('snackbarTitleText');
    if (titleText) {
      titleText.textContent = title || 'Importing Data...';
    }

    progressSnackbar.classList.add('active');
    progressSnackbar.classList.remove('minimized');
    updateProgress(0, 'Memulai import...');
  }

  function updateProgress(percent, status) {
    const progressBarFill = document.getElementById('progressBarFill');
    const progressText = document.getElementById('progressText');
    const progressStatus = document.getElementById('progressStatus');

    if (progressBarFill) {
      progressBarFill.style.width = percent + '%';
    }

    if (progressText) {
      progressText.textContent = Math.round(percent) + '%';
    }

    if (progressStatus) {
      progressStatus.textContent = status || '';
    }
  }

  function hideProgressSnackbar() {
    if (progressSnackbar) {
      progressSnackbar.classList.remove('active');
    }
  }

  window.toggleSnackbar = function() {
    if (progressSnackbar) {
      progressSnackbar.classList.toggle('minimized');
    }
  };

  // ========================================
  // ✅ 2-STEP IMPORT WITH PREVIEW - FIXED
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
    
    const formElement = $(this);
    const formId = formElement.attr('id');
    
    try {
        currentFormData = new FormData(formElement[0]);
        currentImportType = currentFormData.get('import_type');

        console.log(`📤 Submitting ${formId}:`, {
            import_type: currentImportType,
            file: currentFormData.get('file')?.name,
            fileSize: currentFormData.get('file')?.size
        });

        // ✅ FIX #1: Validate file exists before submit
        const file = currentFormData.get('file');
        if (!file || !(file instanceof File)) {
            console.error(`❌ ${formId}: No valid file selected`);
            alert('Silakan pilih file terlebih dahulu');
            return;
        }

        // ✅ FIX #2: Validate file size
        if (file.size <= 0) {
            console.error(`❌ ${formId}: File is empty`);
            alert('File yang dipilih kosong atau tidak valid');
            return;
        }

        console.log(`✅ ${formId} validation passed, calling handleImportPreview`);
        handleImportPreview(currentFormData, currentImportType);

    } catch (error) {
        // ✅ FIX #3: Catch any unexpected errors
        console.error(`❌ Error in ${formId} submit:`, error);
        console.error('Error stack:', error.stack);
        alert('Error: Terjadi kesalahan - ' + error.message);
    }
});

$('#formRevenueCC').submit(function(e) {
    e.preventDefault();

    try {
        currentFormData = new FormData($(this)[0]);
        currentImportType = currentFormData.get('import_type');

        const year = $('#import-cc-year').val();
        const month = $('#import-cc-month').val();
        const divisi = $('#revCCDivisiImport').val();
        const tipeRevenue = $('#revCCTipeRevenueImport').val();
        const jenisData = $('#revCCJenisDataImport').val();
        const file = currentFormData.get('file');

        console.log('📋 Revenue CC Form Values:', {
            year: year,
            month: month,
            divisi_id: divisi,
            tipe_revenue: tipeRevenue,
            jenis_data: jenisData,
            file: file?.name,
            fileSize: file?.size
        });

        // ✅ VALIDATION 1: Periode
        if (!year || !month) {
            console.error('❌ Periode not selected');
            alert('❌ PERIODE BELUM DIPILIH!\n\nSilakan pilih bulan dan tahun terlebih dahulu.');
            $('#import-cc-periode').focus();
            return;
        }

        // ✅ VALIDATION 2: Divisi
        if (!divisi || divisi === '') {
            console.error('❌ Divisi not selected');
            alert('❌ DIVISI BELUM DIPILIH!\n\nSilakan pilih divisi (DGS/DSS/DPS) terlebih dahulu.');
            $('#revCCDivisiImport').focus();
            return;
        }

        // ✅ VALIDATION 3: Tipe Revenue
        if (!tipeRevenue || tipeRevenue === '') {
            console.error('❌ Tipe Revenue not selected');
            alert('❌ TIPE REVENUE BELUM DIPILIH!\n\nSilakan pilih:\n• Revenue Sold (HO) - untuk kolom REVENUE_SOLD\n• Revenue Bill - untuk kolom REVENUE_BILL');
            $('#revCCTipeRevenueImport').focus();
            return;
        }

        // ✅ VALIDATION 4: Jenis Data (CRITICAL - New validation)
        if (!jenisData || jenisData === '') {
            console.error('❌ Jenis Data not selected');
            alert('❌ JENIS DATA BELUM DIPILIH!\n\nSilakan pilih salah satu:\n\n• Real Revenue - hanya import REVENUE_SOLD\n• Target Revenue - hanya import TARGET_REVENUE_SOLD\n• Lengkap - import REVENUE_SOLD + TARGET_REVENUE_SOLD');
            $('#revCCJenisDataImport').focus();
            return;
        }

        // ✅ VALIDATION 5: File
        if (!file || !(file instanceof File)) {
            console.error('❌ File not selected or invalid');
            alert('❌ FILE CSV BELUM DIPILIH!\n\nSilakan pilih file CSV yang akan diimport.');
            return;
        }

        // ✅ VALIDATION 6: File size
        if (file.size <= 0) {
            console.error('❌ File is empty');
            alert('❌ FILE KOSONG!\n\nFile yang dipilih tidak memiliki data.\nSilakan pilih file yang valid.');
            return;
        }

        // ✅ VALIDATION 7: File type
        const fileName = file.name.toLowerCase();
        if (!fileName.endsWith('.csv') && !fileName.endsWith('.txt')) {
            console.error('❌ Invalid file type:', fileName);
            alert('❌ TIPE FILE TIDAK VALID!\n\nHanya file CSV (.csv atau .txt) yang diperbolehkan.\nFile Anda: ' + file.name);
            return;
        }

        // ✅ Set form data with validated values
        currentFormData.set('year', parseInt(year));
        currentFormData.set('month', parseInt(month));

        currentImportYear = parseInt(year);
        currentImportMonth = parseInt(month);
        
        currentImportDivisiId = divisi;
        currentImportTipeRevenue = tipeRevenue;
        currentImportJenisData = jenisData;

        console.log('✅ All validations passed');
        console.log('📤 Submitting Revenue CC with:', {
            year: currentImportYear,
            month: currentImportMonth,
            divisi_id: currentImportDivisiId,
            tipe_revenue: currentImportTipeRevenue,
            jenis_data: currentImportJenisData,
            file: file.name
        });

        // ✅ PROCEED TO IMPORT
        handleImportPreview(currentFormData, currentImportType);

    } catch (error) {
        console.error('❌ Error in formRevenueCC submit:', error);
        console.error('Error stack:', error.stack);
        alert('❌ TERJADI KESALAHAN!\n\n' + error.message + '\n\nSilakan coba lagi atau hubungi admin.');
    }
});


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

    currentFormData.set('year', parseInt(year));
    currentFormData.set('month', parseInt(month));

    currentImportYear = parseInt(year);
    currentImportMonth = parseInt(month);

    console.log('📤 Submitting Revenue AM with:', {
      year: currentImportYear,
      month: currentImportMonth,
      file: currentFormData.get('file')?.name
    });

    handleImportPreview(currentFormData, currentImportType);
  });


$('#formTargetWitel').submit(function(e) {
    e.preventDefault();

    currentFormData = new FormData($(this)[0]);
    currentImportType = currentFormData.get('import_type');

    const year = $('#import-witel-year').val();
    const month = $('#import-witel-month').val();
    const divisi = $('#targetWitelDivisiImport').val();

    if (!year || !month) {
      alert('❌ Pilih Periode terlebih dahulu!');
      return;
    }

    if (!divisi || divisi === '') {
      alert('❌ Pilih Divisi terlebih dahulu!');
      return;
    }

    // ✅ FIX #1: Set ke FormData
    currentFormData.set('year', parseInt(year));
    currentFormData.set('month', parseInt(month));
    currentFormData.set('divisi_id', divisi);  // ← TAMBAHKAN INI!

    // ✅ FIX #2: Simpan ke global variable
    currentImportYear = parseInt(year);
    currentImportMonth = parseInt(month);
    currentImportDivisiId = divisi;  // ← TAMBAHKAN INI!

    console.log('📤 Submitting Target Witel with:', {
      year: currentImportYear,
      month: currentImportMonth,
      divisi_id: currentImportDivisiId,  // ← SEKARANG ADA!
      file: currentFormData.get('file')?.name
    });

    handleImportPreview(currentFormData, currentImportType);
});


function handleImportPreview(formData, importType) {
    console.log('📤 handleImportPreview called with:', {
        importType: importType,
        formDataKeys: Array.from(formData.keys())
    });

    // Log all form data for debugging
    console.log('📤 Sending to /import/preview:');
    for (let [key, value] of formData.entries()) {
        if (value instanceof File) {
            console.log(`  ${key}: ${value.name} (${value.size} bytes)`);
        } else {
            console.log(`  ${key}: ${value}`);
        }
    }

    try {
        showProgressSnackbar('Memproses file...');
        updateProgress(5, 'Memulai...');

        const file = formData.get('file');

        // ✅ FIX #1: Validate file exists
        if (!file) {
            console.error('❌ No file found in FormData');
            hideProgressSnackbar();
            alert('Error: File tidak ditemukan. Silakan pilih file terlebih dahulu.');
            return;
        }

        // ✅ FIX #2: Validate file is actually a File object
        if (!(file instanceof File)) {
            console.error('❌ file is not a File object:', typeof file);
            hideProgressSnackbar();
            alert('Error: File tidak valid. Silakan pilih ulang file.');
            return;
        }

        console.log('📊 File info:', {
            name: file.name,
            size: file.size,
            type: file.type
        });

        // ✅ FIX #3: Validate file size is positive
        if (file.size <= 0) {
            console.error('❌ File size is 0 or negative:', file.size);
            hideProgressSnackbar();
            alert('Error: File kosong atau tidak valid');
            return;
        }

        // Determine upload strategy based on file size
        if (file.size > SIZE_THRESHOLD) {
            console.log('📦 Large file detected, using row-based chunked upload');
            
            try {
                uploadCSVInRowChunks(file, importType, formData);
            } catch (error) {
                console.error('❌ Error in uploadCSVInRowChunks:', error);
                hideProgressSnackbar();
                alert('Error: Gagal memproses file besar - ' + error.message);
            }
        } else {
            console.log('📤 Small file, using direct upload');
            
            try {
                uploadFileDirect(formData, importType);
            } catch (error) {
                console.error('❌ Error in uploadFileDirect:', error);
                hideProgressSnackbar();
                alert('Error: Gagal upload file - ' + error.message);
            }
        }

    } catch (error) {
        // ✅ FIX #4: Catch any unexpected errors
        console.error('❌ Unexpected error in handleImportPreview:', error);
        console.error('Error stack:', error.stack);
        hideProgressSnackbar();
        alert('Error: Terjadi kesalahan tidak terduga - ' + error.message);
    }
}

  async function uploadCSVInRowChunks(file, importType, originalFormData) {
    updateProgress(10, 'Membaca file CSV...');

    try {
      const csvText = await readFileAsText(file);
      const lines = csvText.split('\n');

      while (lines.length > 0 && lines[lines.length - 1].trim() === '') {
        lines.pop();
      }

      if (lines.length === 0) {
        throw new Error('File CSV kosong');
      }

      const headers = lines[0];
      const dataRows = lines.slice(1);

      console.log('📊 CSV Analysis:', {
        totalLines: lines.length,
        headers: headers.substring(0, 100) + '...',
        dataRowCount: dataRows.length,
        firstDataRow: dataRows[0]?.substring(0, 100) + '...'
      });

      const totalChunks = Math.ceil(dataRows.length / ROWS_PER_CHUNK);
      const sessionId = generateSessionId();

      console.log(`📦 Will upload ${totalChunks} chunks (${ROWS_PER_CHUNK} rows each)`);

      for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
        const start = chunkIndex * ROWS_PER_CHUNK;
        const end = Math.min(start + ROWS_PER_CHUNK, dataRows.length);
        const chunkRows = dataRows.slice(start, end);

        let chunkCSV;
        if (chunkIndex === 0) {
          chunkCSV = headers + '\n' + chunkRows.join('\n');
        } else {
          chunkCSV = headers + '\n' + chunkRows.join('\n');
        }

        const progress = 10 + Math.round(((chunkIndex + 1) / totalChunks) * 70);
        updateProgress(progress, `Mengunggah chunk ${chunkIndex + 1}/${totalChunks}...`);

        console.log(`📤 Sending chunk ${chunkIndex + 1}/${totalChunks}:`, {
          rowsInChunk: chunkRows.length,
          chunkSize: chunkCSV.length
        });

        const chunkBlob = new Blob([chunkCSV], { type: 'text/csv' });

        const chunkFormData = new FormData();
        chunkFormData.append('file_chunk', chunkBlob, `chunk_${chunkIndex}.csv`);
        chunkFormData.append('chunk_index', chunkIndex);
        chunkFormData.append('total_chunks', totalChunks);
        chunkFormData.append('session_id', sessionId);
        chunkFormData.append('file_name', file.name);
        chunkFormData.append('import_type', importType);
        chunkFormData.append('is_first_chunk', chunkIndex === 0 ? '1' : '0');
        chunkFormData.append('rows_in_chunk', chunkRows.length);

        for (let [key, value] of originalFormData.entries()) {
          if (!(value instanceof File) && key !== 'file') {
            chunkFormData.append(key, value);
          }
        }

        await sendChunk(chunkFormData);
      }

      console.log('✅ All chunks uploaded successfully');
      updateProgress(85, 'Memproses data...');
      await requestPreviewAfterChunks(sessionId, importType, originalFormData);

    } catch (error) {
      hideProgressSnackbar();
      console.error('❌ Upload failed:', error);
      alert('Terjadi kesalahan saat mengunggah file: ' + error.message);
    }
  }

  function readFileAsText(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();

      reader.onload = function(e) {
        resolve(e.target.result);
      };

      reader.onerror = function(e) {
        reject(new Error('Gagal membaca file: ' + e.target.error));
      };

      reader.readAsText(file, 'UTF-8');
    });
  }

  function sendChunk(chunkFormData) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: '/revenue-data/import/upload-chunk',
        method: 'POST',
        data: chunkFormData,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        timeout: 60000,
        success: function(response) {
          if (response.success) {
            console.log('✅ Chunk uploaded:', response);
            resolve(response);
          } else {
            reject(new Error(response.message || 'Chunk upload failed'));
          }
        },
        error: function(xhr) {
          console.error('❌ Chunk upload error:', xhr);
          reject(new Error(xhr.responseJSON?.message || xhr.statusText));
        }
      });
    });
  }

  
function requestPreviewAfterChunks(sessionId, importType, originalFormData) {
    return new Promise((resolve, reject) => {
        const requestData = {
            session_id: sessionId,
            import_type: importType
        };

        // Add year/month if available
        if (originalFormData.get('year')) {
            requestData.year = parseInt(originalFormData.get('year'));
        }
        if (originalFormData.get('month')) {
            requestData.month = parseInt(originalFormData.get('month'));
        }
        
        // Add divisi_id if available
        if (originalFormData.get('divisi_id')) {
            requestData.divisi_id = originalFormData.get('divisi_id');
        }
        
        // Add tipe_revenue if available
        if (originalFormData.get('tipe_revenue')) {
            requestData.tipe_revenue = originalFormData.get('tipe_revenue');
        }
        
        // Add jenis_data if available
        if (originalFormData.get('jenis_data')) {
            requestData.jenis_data = originalFormData.get('jenis_data');
        }

        // Add any other non-file fields
        for (let [key, value] of originalFormData.entries()) {
            if (!(value instanceof File) && key !== 'file' && !requestData[key]) {
                requestData[key] = value;
            }
        }

        console.log('📊 Requesting preview after chunks with:', requestData);
        updateProgress(90, 'Generating preview...');

        $.ajax({
            url: '/revenue-data/import/preview',
            method: 'POST',
            data: requestData,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            timeout: 120000,
            success: function(response) {
                console.log('📥 Chunked upload preview response:', response);

                // ✅ FIX #1: Validate response exists
                if (!response) {
                    console.error('❌ Response is null or undefined');
                    hideProgressSnackbar();
                    reject(new Error('Server tidak memberikan response'));
                    return;
                }

                // ✅ FIX #2: Validate response.success
                if (!response.success) {
                    console.error('❌ Response success = false:', response);
                    hideProgressSnackbar();
                    const errorMsg = response.message || response.error || 'Preview gagal setelah upload chunks';
                    reject(new Error(errorMsg));
                    return;
                }

                // ✅ FIX #3: Handle different response structures
                let previewDataToStore;
                
                if (response.data) {
                    console.log('📦 Response has .data wrapper');
                    previewDataToStore = response.data;
                } else if (response.preview || response.stats || response.statistics) {
                    console.log('📦 Response is direct (no .data wrapper)');
                    previewDataToStore = response;
                } else {
                    console.error('❌ Response has no recognizable data structure:', response);
                    hideProgressSnackbar();
                    reject(new Error('Format data preview tidak dikenali'));
                    return;
                }

                // ✅ FIX #4: Validate session_id exists (with fallback)
                if (!response.session_id) {
                    console.warn('⚠️ Warning: session_id missing, using original sessionId');
                }

                // ✅ All validations passed
                console.log('✅ Chunked preview validations passed');
                hideProgressSnackbar();

                previewData = previewDataToStore;
                currentSessionId = response.session_id || sessionId; // Fallback to original
                
                console.log('✅ Preview loaded after chunks:', {
                    session_id: currentSessionId,
                    hasPreview: !!(previewDataToStore.preview),
                    previewCount: previewDataToStore.preview?.length || 0,
                    hasStats: !!(previewDataToStore.stats || previewDataToStore.summary || previewDataToStore.statistics)
                });

                showPreviewModal(previewData, importType);

                // Close import modal
                const importModal = document.getElementById('importModal');
                if (importModal) {
                    const modalInstance = bootstrap.Modal.getInstance(importModal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }

                resolve(response);
            },
            error: function(xhr, status, error) {
                hideProgressSnackbar();
                
                console.error('❌ Chunked preview AJAX error:', {
                    status: status,
                    error: error,
                    statusCode: xhr.status,
                    responseText: xhr.responseText,
                    response: xhr.responseJSON
                });

                let errorMessage = 'Preview gagal setelah upload chunks';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                } else if (xhr.responseText) {
                    try {
                        const parsed = JSON.parse(xhr.responseText);
                        errorMessage = parsed.message || parsed.error || errorMessage;
                    } catch (e) {
                        errorMessage = xhr.statusText || errorMessage;
                    }
                }

                reject(new Error(errorMessage));
            }
        });
    });
}

// ================================================================
// ✅ FIXED: Input listener for proporsi changes - PROPER ATTACHMENT
// ================================================================
$(document).on('input', '#editAMProporsiInput', function() {
    const newProporsiPercent = parseFloat($(this).val()) || 0;
    
    console.log('📊 Proporsi input changed:', newProporsiPercent);
    
    // Validate range
    if (newProporsiPercent < 0 || newProporsiPercent > 100) {
        showProporsiAlert('danger', '<i class="fa-solid fa-exclamation-triangle me-2"></i><strong>Error:</strong> Proporsi harus antara 0-100%');
        $('#btnSaveEditAM').prop('disabled', true);
        return;
    }
    
    // Convert to decimal for calculation
    const newProporsiDecimal = newProporsiPercent / 100;
    
    // ✅ FIX: Get CC Revenue base from HIDDEN inputs (already populated by controller)
    const ccTargetSold = parseFloat($('#editAMCCTargetSold').val()) || 0;
    const ccRealSold = parseFloat($('#editAMCCRealSold').val()) || 0;
    
    console.log('📊 CC Base Values:', {
        ccTargetSold: ccTargetSold,
        ccRealSold: ccRealSold,
        newProporsiPercent: newProporsiPercent,
        newProporsiDecimal: newProporsiDecimal
    });
    
    // ✅ FIX: Check if CC base values are valid
    if (ccTargetSold === 0 && ccRealSold === 0) {
        console.error('❌ CC Revenue base values are ZERO!');
        showProporsiAlert('danger', '<i class="fa-solid fa-exclamation-triangle me-2"></i><strong>Error:</strong> Data CC Revenue tidak tersedia. Silakan cek database.');
        $('#btnSaveEditAM').prop('disabled', true);
        return;
    }
    
    // Calculate new revenues
    const newTargetRevenue = ccTargetSold * newProporsiDecimal;
    const newRealRevenue = ccRealSold * newProporsiDecimal;
    
    console.log('💰 Calculated Revenues:', {
        newTargetRevenue: newTargetRevenue,
        newRealRevenue: newRealRevenue
    });
    
    // ✅ FIX: Update display (format as currency)
    $('#editAMTargetRevenueDisplay').text(formatCurrency(newTargetRevenue));
    $('#editAMRealRevenueDisplay').text(formatCurrency(newRealRevenue));
    
    // Calculate total proporsi (current + others)
    const currentAmRevenueId = parseInt($('#editAMRevenueId').val());
    let otherAMsProporsi = 0;
    
    // Sum proporsi from OTHER AMs (read-only rows)
    $('.am-mapping-row.readonly').each(function() {
        const proporsiText = $(this).find('input[readonly]').val();
        if (proporsiText) {
            const proporsiValue = parseFloat(proporsiText.replace('%', '')) || 0;
            otherAMsProporsi += (proporsiValue / 100);
        }
    });
    
    const totalProporsi = newProporsiDecimal + otherAMsProporsi;
    
    console.log('📊 Total Proporsi:', {
        current: newProporsiDecimal,
        others: otherAMsProporsi,
        total: totalProporsi
    });
    
    // Update total display
    $('#editAMTotalProporsi').text((totalProporsi * 100).toFixed(2) + '%');
    
    // Validate total
    validateProporsiTotal(totalProporsi);
});
  

function renderOtherAMs(otherAMs, totalProporsi) {
    let html = `
        <table class="table table-bordered table-sm other-ams-table">
            <thead>
                <tr>
                    <th>Nama AM</th>
                    <th>NIK</th>
                    <th class="text-end">Proporsi</th>
                    <th class="text-end">Target Revenue</th>
                    <th class="text-end">Real Revenue</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    otherAMs.forEach(am => {
        const proporsiPercent = (parseFloat(am.proporsi) * 100).toFixed(2);
        html += `
            <tr>
                <td>${am.account_manager_nama}</td>
                <td><small>${am.account_manager_nik}</small></td>
                <td class="text-end">${proporsiPercent}%</td>
                <td class="text-end">${formatCurrency(am.target_revenue)}</td>
                <td class="text-end">${formatCurrency(am.real_revenue)}</td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
            <tfoot>
                <tr class="table-secondary">
                    <th colspan="2">Total Proporsi</th>
                    <th class="text-end">${(totalProporsi * 100).toFixed(2)}%</th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>
    `;
    
    $('#otherAMsList').html(html);
}

function validateProporsiTotal(totalProporsi) {
    const totalPercent = (totalProporsi * 100).toFixed(2);
    
    if (Math.abs(totalProporsi - 1.0) < 0.01) {
        // Valid
        $('#proporsiValidationAlert')
            .removeClass('alert-warning alert-danger')
            .addClass('alert-success')
            .html(`<i class="fa-solid fa-check-circle me-2"></i><strong>Valid:</strong> Total proporsi = ${totalPercent}%`)
            .show();
    } else if (totalProporsi < 1.0) {
        // Under 100%
        const remaining = ((1.0 - totalProporsi) * 100).toFixed(2);
        $('#proporsiValidationAlert')
            .removeClass('alert-success alert-danger')
            .addClass('alert-warning')
            .html(`<i class="fa-solid fa-exclamation-triangle me-2"></i><strong>Warning:</strong> Total proporsi = ${totalPercent}% (sisa ${remaining}%)`)
            .show();
    } else {
        // Over 100%
        const excess = ((totalProporsi - 1.0) * 100).toFixed(2);
        $('#proporsiValidationAlert')
            .removeClass('alert-success alert-warning')
            .addClass('alert-danger')
            .html(`<i class="fa-solid fa-times-circle me-2"></i><strong>Error:</strong> Total proporsi = ${totalPercent}% (kelebihan ${excess}%)`)
            .show();
    }
}


// ================================================================
// ✅ UPDATED: Form submit handler for Edit Revenue AM
// ================================================================
$('#formEditRevenueAM').on('submit', function(e) {
    e.preventDefault();
    
    const id = $('#editAMRevenueId').val();
    const proporsiPercent = parseFloat($('#editAMProporsiInput').val()) || 0;
    
    // Validate
    if (proporsiPercent <= 0 || proporsiPercent > 100) {
        alert('Proporsi harus antara 0-100%');
        return;
    }
    
    // Convert to decimal (50% → 0.5000)
    const proporsiDecimal = proporsiPercent / 100;
    
    const data = {
        proporsi: proporsiDecimal
        // ✅ Note: target_revenue & real_revenue will be calculated server-side
    };
    
    console.log('📤 Submitting AM Revenue update:', data);
    
    $.ajax({
        url: `/revenue-data/revenue-am/${id}`,
        method: 'PUT',
        data: JSON.stringify(data),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            console.log('✅ Update response:', response);
            
            if (response.success) {
                alert(response.message || 'Revenue AM berhasil diupdate!');
                
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('modalEditRevenueAM')).hide();
                
                // Reload data
                loadData();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            console.error('❌ Update failed:', xhr);
            alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
        }
    });
});


// ================================================================
// ✅ UPDATED: deleteRevenueAM() - SMART DELETE
// ================================================================
window.deleteRevenueAM = function(id) {
    console.log('🗑️ Delete Revenue AM initiated:', id);
    
    $.ajax({
        url: `/revenue-data/revenue-am/${id}`,
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            console.log('📥 Delete response:', response);
            
            if (response.success) {
                // ✅ CASE A: Auto-adjusted (2 AMs)
                alert(response.message);
                loadData();
            } else if (response.requires_redistribution) {
                // ✅ CASE B: Manual redistribution required (3+ AMs)
                handleDeleteRevenueAMManualRedistribute(response.data);
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            console.error('❌ Delete failed:', xhr);
            alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
        }
    });
};

// ================================================================
// ✅ NEW: handleDeleteRevenueAMManualRedistribute()
// ================================================================
function handleDeleteRevenueAMManualRedistribute(data) {
    console.log('📋 Handling manual redistribution:', data);
    
    const remainingAMs = data.remaining_ams || [];
    const amToDeleteId = data.am_to_delete_id;
    
    // Populate modal
    $('#deleteAMRemainingCount').text(remainingAMs.length);
    
    // Render table
    const tbody = $('#deleteAMRedistributeTableBody');
    tbody.empty();
    
    remainingAMs.forEach(function(am) {
        const row = `
            <tr>
                <td style="padding: 1rem; border-bottom: 1px solid #dee2e6; font-weight: 600;">${am.nik}</td>
                <td style="padding: 1rem; border-bottom: 1px solid #dee2e6;">${am.nama}</td>
                <td style="padding: 1rem; border-bottom: 1px solid #dee2e6;">
                    <input type="number" 
                           class="form-control proporsi-input redistribute-proporsi-input" 
                           data-am-revenue-id="${am.id}"
                           value="${am.current_proporsi_percent}" 
                           min="0" 
                           max="100" 
                           step="0.01"
                           placeholder="0.00">
                </td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Attach input listeners
    $('.redistribute-proporsi-input').on('input', function() {
        let totalProporsi = 0;
        
        $('.redistribute-proporsi-input').each(function() {
            totalProporsi += parseFloat($(this).val()) || 0;
        });
        
        $('#deleteAMRedistributeTotalProporsi').text(totalProporsi.toFixed(2) + '%');
        
        // Validate
        const isValid = Math.abs(totalProporsi - 100.0) < 0.01;
        
        if (isValid) {
            $('#deleteAMRedistributeAlert')
                .removeClass('alert-warning alert-danger')
                .addClass('alert alert-success proporsi-alert-success')
                .html('<i class="fa-solid fa-check-circle me-2"></i><strong>Valid:</strong> Total proporsi = 100%')
                .show();
            $('#btnConfirmDeleteAMWithRedistribute').prop('disabled', false);
        } else if (totalProporsi < 100) {
            const remaining = (100 - totalProporsi).toFixed(2);
            $('#deleteAMRedistributeAlert')
                .removeClass('alert-success alert-danger')
                .addClass('alert alert-warning proporsi-alert-warning')
                .html('<i class="fa-solid fa-exclamation-triangle me-2"></i><strong>Warning:</strong> Total = ' + totalProporsi.toFixed(2) + '% (sisa ' + remaining + '%)')
                .show();
            $('#btnConfirmDeleteAMWithRedistribute').prop('disabled', true);
        } else {
            const excess = (totalProporsi - 100).toFixed(2);
            $('#deleteAMRedistributeAlert')
                .removeClass('alert-success alert-warning')
                .addClass('alert alert-danger proporsi-alert-danger')
                .html('<i class="fa-solid fa-times-circle me-2"></i><strong>Error:</strong> Total = ' + totalProporsi.toFixed(2) + '% (kelebihan ' + excess + '%)')
                .show();
            $('#btnConfirmDeleteAMWithRedistribute').prop('disabled', true);
        }
    });
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('modalDeleteRevenueAMManualRedistribute'));
    modal.show();
    
    // Handle confirm button
    $('#btnConfirmDeleteAMWithRedistribute').off('click').on('click', function() {
        confirmDeleteRevenueAMWithRedistribute(amToDeleteId);
    });
}

// ================================================================
// ✅ NEW: confirmDeleteRevenueAMWithRedistribute()
// ================================================================
function confirmDeleteRevenueAMWithRedistribute(amToDeleteId) {
    const newProporsi = [];
    
    $('.redistribute-proporsi-input').each(function() {
        const amRevenueId = $(this).data('am-revenue-id');
        const proporsiPercent = parseFloat($(this).val()) || 0;
        const proporsiDecimal = proporsiPercent / 100;
        
        newProporsi.push({
            am_revenue_id: amRevenueId,
            proporsi: proporsiDecimal
        });
    });
    
    console.log('📤 Confirming delete with redistribution:', {
        am_to_delete_id: amToDeleteId,
        new_proporsi: newProporsi
    });
    
    $.ajax({
        url: `/revenue-data/revenue-am/${amToDeleteId}/confirm-delete`,
        method: 'POST',
        data: JSON.stringify({ new_proporsi: newProporsi }),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            console.log('✅ Delete confirmed:', response);
            
            if (response.success) {
                alert(response.message);
                
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('modalDeleteRevenueAMManualRedistribute')).hide();
                
                // Reload data
                loadData();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            console.error('❌ Confirm delete failed:', xhr);
            alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
        }
    });
}


/**
 * ✅ Helper: Format currency
 */
function formatCurrency(value) {
    if (!value) return 'Rp 0';
    return 'Rp ' + parseFloat(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });
}


function uploadFileDirect(formData, importType) {
    console.log('📤 Sending to /import/preview:');
    for (let [key, value] of formData.entries()) {
        if (value instanceof File) {
            console.log(`  ${key}: ${value.name} (${value.size} bytes)`);
        } else {
            console.log(`  ${key}: ${value}`);
        }
    }

    updateProgress(20, 'Mengunggah file...');

    $.ajax({
        url: '/revenue-data/import/preview',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        timeout: 120000,
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                    const percentComplete = 20 + (evt.loaded / evt.total) * 60;
                    updateProgress(percentComplete, 'Mengunggah file...');
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            console.log('📥 Response received:', response);

            // ✅ FIX #1: Validate response exists
            if (!response) {
                console.error('❌ Response is null or undefined');
                hideProgressSnackbar();
                alert('Error: Server tidak memberikan response');
                return;
            }

            // ✅ FIX #2: Validate response.success
            if (!response.success) {
                console.error('❌ Response success = false:', response);
                hideProgressSnackbar();
                alert('Error: ' + (response.message || response.error || 'Import preview gagal'));
                return;
            }

            // ✅ FIX #3: Handle different response structures
            // Backend might return data directly OR wrapped in .data
            let previewDataToStore;
            
            if (response.data) {
                // Response structure: { success: true, data: {...}, session_id: "..." }
                console.log('📦 Response has .data wrapper');
                previewDataToStore = response.data;
            } else if (response.preview || response.stats || response.statistics) {
                // Response structure: { success: true, preview: [...], stats: {...}, session_id: "..." }
                console.log('📦 Response is direct (no .data wrapper)');
                previewDataToStore = response;
            } else {
                console.error('❌ Response has no recognizable data structure:', response);
                hideProgressSnackbar();
                alert('Error: Format data preview tidak dikenali');
                return;
            }

            // ✅ FIX #4: Validate session_id exists
            if (!response.session_id) {
                console.error('❌ Response session_id is missing:', response);
                hideProgressSnackbar();
                alert('Error: Session ID tidak tersedia. Silakan coba lagi.');
                return;
            }

            // ✅ All validations passed
            console.log('✅ All validations passed');
            updateProgress(100, 'Preview siap!');

            setTimeout(() => {
                hideProgressSnackbar();

                previewData = previewDataToStore;
                currentSessionId = response.session_id;
                
                console.log('✅ Preview data stored:', {
                    session_id: currentSessionId,
                    hasData: !!previewData,
                    dataKeys: previewData ? Object.keys(previewData) : [],
                    hasPreview: !!(previewData.preview),
                    hasStats: !!(previewData.stats || previewData.summary || previewData.statistics)
                });

                showPreviewModal(previewData, importType);

                // Close import modal
                const importModal = document.getElementById('importModal');
                if (importModal) {
                    const modalInstance = bootstrap.Modal.getInstance(importModal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
            }, 500);
        },
        error: function(xhr, status, error) {
            hideProgressSnackbar();
            
            console.error('❌ AJAX Error:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                statusCode: xhr.status,
                response: xhr.responseJSON
            });

            let errorMessage = 'Terjadi kesalahan saat preview';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMessage = xhr.responseJSON.error;
            } else if (xhr.responseText) {
                try {
                    const parsed = JSON.parse(xhr.responseText);
                    errorMessage = parsed.message || parsed.error || errorMessage;
                } catch (e) {
                    errorMessage = xhr.statusText || errorMessage;
                }
            }

            alert('Error: ' + errorMessage);
        }
    });
}

function updateTemplateLinks() {
    const jenisData = $('#revCCJenisDataImport').val();
    const linkReal = $('#linkTemplateRealRevCC');
    const linkTarget = $('#linkTemplateTargetRevCC');
    
    // Hide both initially
    linkReal.hide();
    linkTarget.hide();
    
    // Show appropriate link based on selection
    if (jenisData === 'revenue') {
        // Show only Real Revenue template
        linkReal.show();
    } else if (jenisData === 'target') {
        // Show only Target Revenue template
        linkTarget.show();
    } else if (jenisData === 'lengkap') {
        // Show both templates
        linkReal.show();
        linkTarget.show();
    }
    // If empty/not selected, hide both (default)
}

// ✅ ATTACH EVENT LISTENER
$(document).ready(function() {
    // Listen for changes on Jenis Data dropdown
    $('#revCCJenisDataImport').on('change', function() {
        updateTemplateLinks();
    });
    
    // Initial call on page load
    updateTemplateLinks();
    
    // Re-initialize when import modal is shown
    $('#importModal').on('shown.bs.modal', function() {
        updateTemplateLinks();
    });
});



/**
 * ================================================================
 * ✅ FINAL COMPLETE: showPreviewModal() - ALL IMPORT TYPES
 * ================================================================
 * Supports: data_am, data_cc, revenue_am, revenue_cc, target_witel
 * Replace BOTH duplicate functions with this single version
 * ================================================================
 */

function showPreviewModal(data, importType) {
    console.log('📊 showPreviewModal called with:', {
        importType: importType,
        hasData: !!data,
        dataKeys: data ? Object.keys(data) : []
    });

    // ✅ FIX #1: Validate data exists
    if (!data) {
        console.error('❌ Preview data is undefined or null');
        alert('Terjadi kesalahan: Data preview tidak tersedia');
        return;
    }

    // ✅ FIX #2: Handle multiple response structures (summary/stats/statistics)
    const summary = data.summary || data.stats || data.statistics || {};
    
    console.log('📊 Summary extracted:', summary);

    // ✅ FIX #3: Validate summary is not empty
    if (!summary || Object.keys(summary).length === 0) {
        console.error('❌ No valid summary found in response:', {
            hasSummary: !!data.summary,
            hasStats: !!data.stats,
            hasStatistics: !!data.statistics,
            dataKeys: Object.keys(data)
        });
        alert('Terjadi kesalahan: Data summary tidak tersedia atau kosong');
        return;
    }

    // ✅ FIX #4: Normalize field names based on import type
    let normalizedSummary = {
        total_rows: summary.total_rows || 0,
        new_count: 0,
        update_count: 0,
        error_count: 0,
        unique_count: 0
    };

    // ✅ CRITICAL: Debug import type matching
    console.log('🔍 Import type check:', {
        importType: importType,
        typeOf: typeof importType,
        is_target_witel: importType === 'target_witel'
    });

    // ✅ FIX #5: Handle different field names per import type
    if (importType === 'data_am') {
        console.log('✅ Matched: data_am');
        // Data AM uses: new_ams, existing_ams, duplicate_niks, multi_divisi_ams
        normalizedSummary.new_count = summary.new_ams || summary.new_count || summary.created_count || 0;
        normalizedSummary.update_count = summary.existing_ams || summary.update_count || summary.updated_count || 0;
        normalizedSummary.error_count = summary.error_count || summary.failed_count || 0;
        normalizedSummary.unique_count = summary.unique_am_count || 0;
        normalizedSummary.multi_divisi_count = summary.multi_divisi_ams || 0;
        normalizedSummary.duplicate_count = summary.duplicate_niks || 0;
        
    } else if (importType === 'data_cc') {
        console.log('✅ Matched: data_cc');
        // Data CC uses: new_cc, existing_cc
        normalizedSummary.new_count = summary.new_cc || summary.new_count || summary.created_count || 0;
        normalizedSummary.update_count = summary.existing_cc || summary.update_count || summary.updated_count || 0;
        normalizedSummary.error_count = summary.error_count || summary.failed_count || 0;
        normalizedSummary.unique_count = summary.unique_cc_count || 0;
        
    } else if (importType === 'revenue_am') {
        console.log('✅ Matched: revenue_am');
        // Revenue AM uses: new_mappings, existing_mappings, invalid_proporsi
        normalizedSummary.new_count = summary.new_mappings || summary.new_count || summary.created_count || 0;
        normalizedSummary.update_count = summary.existing_mappings || summary.update_count || summary.updated_count || 0;
        normalizedSummary.error_count = summary.error_count || summary.failed_count || summary.invalid_proporsi || 0;
        normalizedSummary.unique_count = summary.unique_am_count || summary.total_am_count || 0;
        normalizedSummary.unique_cc_count = summary.unique_cc_count || summary.total_cc_count || 0;
        
    } else if (importType === 'revenue_cc') {
        console.log('✅ Matched: revenue_cc');
        // Revenue CC uses: new_count, update_count
        normalizedSummary.new_count = summary.new_count || summary.created_count || 0;
        normalizedSummary.update_count = summary.update_count || summary.updated_count || 0;
        normalizedSummary.error_count = summary.error_count || summary.failed_count || 0;
        normalizedSummary.unique_count = summary.unique_cc_count || 0;
        
    } else if (importType === 'target_witel') {
        console.log('🎯 Matched: target_witel');
        // ✅ CRITICAL FIX: Target Witel uses: will_insert, will_update, valid_rows, invalid_rows
        normalizedSummary.new_count = summary.will_insert || summary.new_count || summary.created_count || 0;
        normalizedSummary.update_count = summary.will_update || summary.update_count || summary.updated_count || 0;
        normalizedSummary.error_count = summary.invalid_rows || summary.error_count || summary.failed_count || 0;
        normalizedSummary.unique_count = summary.valid_rows || 0;
        
        console.log('🎯 target_witel mapping:', {
            will_insert: summary.will_insert,
            will_update: summary.will_update,
            invalid_rows: summary.invalid_rows,
            valid_rows: summary.valid_rows,
            new_count: normalizedSummary.new_count,
            update_count: normalizedSummary.update_count
        });
        
    } else {
        console.warn('⚠️ No match, using generic fallback for:', importType);
        // Generic fallback
        normalizedSummary.new_count = summary.new_count || summary.created_count || 0;
        normalizedSummary.update_count = summary.update_count || summary.updated_count || 0;
        normalizedSummary.error_count = summary.error_count || summary.failed_count || 0;
    }

    console.log('✅ Summary normalized:', normalizedSummary);

    // Store preview data globally
    previewData = data;

    let summaryHTML = '';

    // Total Rows Card
    summaryHTML += `
      <div class="preview-card total">
        <div class="icon"><i class="fa-solid fa-file-lines"></i></div>
        <h3>${normalizedSummary.total_rows}</h3>
        <p>Total Baris Data</p>
      </div>
    `;

    // Unique Count Card (varies by import type)
    if (importType === 'revenue_am' && (normalizedSummary.unique_count > 0 || normalizedSummary.unique_cc_count > 0)) {
        // For Revenue AM, show both AM and CC counts if available
        if (normalizedSummary.unique_count > 0) {
            summaryHTML += `
              <div class="preview-card unique">
                <div class="icon"><i class="fa-solid fa-user-tie"></i></div>
                <h3>${normalizedSummary.unique_count}</h3>
                <p>Account Manager</p>
              </div>
            `;
        }
        if (normalizedSummary.unique_cc_count > 0) {
            summaryHTML += `
              <div class="preview-card unique">
                <div class="icon"><i class="fa-solid fa-building"></i></div>
                <h3>${normalizedSummary.unique_cc_count}</h3>
                <p>Corporate Customer</p>
              </div>
            `;
        }
    } else if (normalizedSummary.unique_count > 0) {
        let uniqueLabel = 'Unique Records';
        let uniqueIcon = 'fa-solid fa-fingerprint';
        
        if (importType === 'revenue_cc' || importType === 'data_cc') {
            uniqueLabel = 'Corporate Customer';
            uniqueIcon = 'fa-solid fa-building';
        } else if (importType === 'data_am') {
            uniqueLabel = 'Account Manager';
            uniqueIcon = 'fa-solid fa-user-tie';
        } else if (importType === 'target_witel') {
            uniqueLabel = 'Valid Rows';
            uniqueIcon = 'fa-solid fa-check-circle';
        }
        
        summaryHTML += `
          <div class="preview-card unique">
            <div class="icon"><i class="${uniqueIcon}"></i></div>
            <h3>${normalizedSummary.unique_count}</h3>
            <p>${uniqueLabel}</p>
          </div>
        `;
    }

    // New Count Card
    summaryHTML += `
      <div class="preview-card new">
        <div class="icon"><i class="fa-solid fa-plus"></i></div>
        <h3>${normalizedSummary.new_count}</h3>
        <p>Data Baru</p>
      </div>
    `;

    // Update Count Card
    summaryHTML += `
      <div class="preview-card update">
        <div class="icon"><i class="fa-solid fa-edit"></i></div>
        <h3>${normalizedSummary.update_count}</h3>
        <p>Akan Di-update</p>
      </div>
    `;

    // Error/Conflict Card
    summaryHTML += `
      <div class="preview-card conflict">
        <div class="icon"><i class="fa-solid fa-exclamation-triangle"></i></div>
        <h3>${normalizedSummary.error_count}</h3>
        <p>Error/Konflik</p>
      </div>
    `;

    $('#previewSummary').html(summaryHTML);

    // Calculate counts safely
    const totalCount = normalizedSummary.new_count + normalizedSummary.update_count;
    const newCount = normalizedSummary.new_count;
    const updateCount = normalizedSummary.update_count;
    const errorCount = normalizedSummary.error_count;

    console.log('📊 Final counts:', {
        totalCount: totalCount,
        newCount: newCount,
        updateCount: updateCount,
        errorCount: errorCount
    });

    // Update badge counts
    $('#badgeAllCount').text(`${totalCount} data`);
    $('#badgeNewCount').text(`${newCount} data`);
    $('#badgeUpdateCount').text(`${updateCount} data`);

    // Enable/disable import buttons
    $('#btnImportAll').prop('disabled', totalCount === 0);
    $('#btnImportNew').prop('disabled', newCount === 0);
    $('#btnImportUpdate').prop('disabled', updateCount === 0);

    console.log('🔘 Button states:', {
        btnImportAll_disabled: totalCount === 0,
        btnImportNew_disabled: newCount === 0,
        btnImportUpdate_disabled: updateCount === 0
    });

    // Show/hide error info
    if (errorCount > 0) {
        $('#errorMessage').text(`${errorCount} baris data mengandung error dan akan diskip.`);
        $('#errorInfo').show();
    } else {
        $('#errorInfo').hide();
    }

    // Handle large dataset info
    const previewInfo = $('#previewInfo');
    if (data.full_data_stored && totalCount > 100) {
        previewInfo.html(`
          <div class="alert alert-info">
            <i class="fa-solid fa-info-circle me-2"></i>
            <strong>Info:</strong> Dataset besar terdeteksi (${totalCount} data). 
            Import akan memproses semua data sesuai filter yang dipilih.
          </div>
        `);
        previewInfo.show();
    } else {
        previewInfo.hide();
    }

    // Additional info for Data AM
    if (importType === 'data_am' && normalizedSummary.multi_divisi_count > 0) {
        const existingInfo = previewInfo.html() || '';
        previewInfo.html(existingInfo + `
          <div class="alert alert-success mt-2">
            <i class="fa-solid fa-sitemap me-2"></i>
            <strong>Info:</strong> ${normalizedSummary.multi_divisi_count} AM dengan multiple divisi terdeteksi.
          </div>
        `);
        previewInfo.show();
    }

    // Show modal
    try {
        const modal = new bootstrap.Modal(document.getElementById('previewModal'));
        modal.show();

        console.log('✅ Preview modal shown successfully', {
            importType,
            totalCount,
            newCount,
            updateCount,
            errorCount,
            originalSummary: summary,
            normalizedSummary: normalizedSummary
        });
    } catch (error) {
        console.error('❌ Error showing modal:', error);
        alert('Error: Gagal menampilkan preview modal - ' + error.message);
    }
}


function showImportResult(response) {
    console.log('📊 Showing import result:', response);

    // ✅ Handle different response structures
    const stats = response.statistics || response.stats || response.data?.stats || {
        total_rows: 0,
        success_count: 0,
        failed_count: 0,
        skipped_count: 0
    };

    // ✅ FIX: Normalize field names - SUPPORT ALL VARIANTS
    let totalRows = 0;
    let successCount = 0;
    let failedCount = 0;
    let skippedCount = 0;
    let updatedCount = 0;
    let createdCount = 0;
    let recalculatedCount = 0;

    // ✅ CRITICAL FIX: Check "processed" first (Target Witel pattern)
    if (stats.processed !== undefined) {
        // Pattern: Target Witel, Import AM
        totalRows = stats.total_rows || stats.processed || 0;
        createdCount = stats.inserted || stats.created || 0;  // ← FIX: "inserted" FIRST!
        updatedCount = stats.updated || 0;
        skippedCount = stats.skipped || 0;
        failedCount = stats.errors || stats.failed_count || 0;
        successCount = createdCount + updatedCount;
        
        if (stats.multi_divisi_processed) {
            recalculatedCount = stats.multi_divisi_processed;
        }
        
        console.log('✅ Using "processed" pattern (Target Witel/Import AM):', {
            totalRows, createdCount, updatedCount, skippedCount, failedCount
        });
        
    } else if (stats.total_processed !== undefined) {
        // Pattern: ImportAMController style (old)
        totalRows = stats.total_processed || 0;
        createdCount = stats.created || 0;
        updatedCount = stats.updated || 0;
        skippedCount = stats.skipped || 0;
        failedCount = stats.errors || 0;
        successCount = createdCount + updatedCount;
        
        if (stats.multi_divisi_processed) {
            recalculatedCount = stats.multi_divisi_processed;
        }
        
        console.log('✅ Using "total_processed" pattern:', {
            totalRows, createdCount, updatedCount
        });
        
    } else if (stats.total_rows !== undefined) {
        // Pattern: Standard style
        totalRows = stats.total_rows || 0;
        successCount = stats.success_count || stats.successful || 0;
        failedCount = stats.failed_count || stats.errors || 0;
        skippedCount = stats.skipped_count || stats.skipped || 0;
        updatedCount = stats.updated_count || stats.updated || 0;
        createdCount = stats.created_count || stats.created || stats.inserted || 0;
        recalculatedCount = stats.recalculated_am_count || 0;
        
        console.log('✅ Using "total_rows" pattern:', {
            totalRows, successCount, failedCount
        });
        
    } else {
        // Pattern: Fallback - try to parse from message
        console.warn('⚠️ Using fallback stats extraction');
        
        const message = response.message || '';
        const newMatch = message.match(/(\d+)\s+(AM|CC|baru|diproses)/i);
        const updateMatch = message.match(/(\d+)\s*(diupdate|updated)/i);
        
        if (newMatch) createdCount = parseInt(newMatch[1]) || 0;
        if (updateMatch) updatedCount = parseInt(updateMatch[1]) || 0;
        
        successCount = createdCount + updatedCount;
        totalRows = successCount;
    }

    const successRate = totalRows > 0 ? ((successCount / totalRows) * 100).toFixed(1) : 0;

    console.log('📊 Final normalized stats:', {
        totalRows, successCount, createdCount, updatedCount, skippedCount, failedCount, successRate
    });

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

    // ✅ Show breakdown if we have created/updated counts
    if (createdCount > 0 || updatedCount > 0 || recalculatedCount > 0) {
        content += `
          <div class="result-modal-info">
            <h6><i class="fa-solid fa-info-circle me-2"></i>Informasi Tambahan</h6>
            <ul>
        `;
        
        if (createdCount > 0) {
            content += `<li><strong>${createdCount}</strong> data baru ditambahkan</li>`;
        }
        if (updatedCount > 0) {
            content += `<li><strong>${updatedCount}</strong> data existing di-update</li>`;
        }
        if (recalculatedCount > 0) {
            content += `<li><strong>${recalculatedCount}</strong> multi-divisi AM diproses</li>`;
        }
        
        content += `
            </ul>
          </div>
        `;
    }

    // Show detailed message if available
    if (response.message && response.message !== 'Import berhasil') {
        content += `
          <div class="alert alert-success mt-3">
            <i class="fa-solid fa-circle-check me-2"></i>
            <strong>${response.message}</strong>
          </div>
        `;
    }

    // Show errors if any
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

    // Show failed rows if any
    if (response.failed_rows && response.failed_rows.length > 0) {
        content += `
          <div class="alert alert-danger mt-3">
            <strong><i class="fa-solid fa-circle-xmark me-2"></i>Data Yang Gagal:</strong>
            <ul class="mb-0 mt-2">
        `;
        response.failed_rows.slice(0, 5).forEach(row => {
            const rowInfo = row.nik || row.nipnas || row.row_number || 'Unknown';
            const error = Array.isArray(row.errors) ? row.errors.join('; ') : (row.error || 'Unknown error');
            content += `<li><strong>${rowInfo}</strong>: ${error}</li>`;
        });
        if (response.failed_rows.length > 5) {
            content += `<li><em>... dan ${response.failed_rows.length - 5} baris lainnya</em></li>`;
        }
        content += `</ul></div>`;
    }

    // Show error log download link if available
    if (response.error_log_path) {
        $('#btnDownloadErrorLog').attr('href', response.error_log_path).show();
    } else {
        $('#btnDownloadErrorLog').hide();
    }

    $('#resultModalBody').html(content);
    
    const modal = new bootstrap.Modal(document.getElementById('resultModal'));
    modal.show();

    console.log('✅ Result modal shown with stats:', {
        totalRows,
        successCount,
        failedCount,
        skippedCount,
        createdCount,
        updatedCount,
        recalculatedCount,
        successRate
    });
}


// ========================================
// ✅ EXECUTE IMPORT WITH FILTER - COMPLETE & FINAL
// ========================================
function executeImportWithFilter(filterType) {
    console.log('🎯 executeImportWithFilter called with:', filterType);

    try {
        if (!['all', 'new', 'update'].includes(filterType)) {
            console.error('❌ Invalid filter type:', filterType);
            alert('Error: Filter type tidak valid');
            return;
        }

        if (!previewData) {
            console.error('❌ previewData is null or undefined');
            alert('Error: Data preview tidak tersedia. Silakan upload ulang file.');
            return;
        }

        const summary = previewData.summary || previewData.stats || previewData.statistics || {};
        
        if (!summary || Object.keys(summary).length === 0) {
            console.error('❌ Summary data is empty:', previewData);
            alert('Error: Data summary tidak tersedia. Silakan upload ulang file.');
            return;
        }

        let newCount = 0;
        let updateCount = 0;
        
        // ✅ Handle different field names per import type
        if (currentImportType === 'data_am') {
            newCount = summary.new_ams || summary.new_count || summary.created_count || 0;
            updateCount = summary.existing_ams || summary.update_count || summary.updated_count || 0;
        } else if (currentImportType === 'data_cc') {
            newCount = summary.new_cc || summary.new_count || summary.created_count || 0;
            updateCount = summary.existing_cc || summary.update_count || summary.updated_count || 0;
        } else if (currentImportType === 'revenue_am') {
            newCount = summary.new_mappings || summary.new_count || summary.created_count || 0;
            updateCount = summary.existing_mappings || summary.update_count || summary.updated_count || 0;
        } else if (currentImportType === 'revenue_cc') {
            newCount = summary.new_count || summary.created_count || 0;
            updateCount = summary.update_count || summary.updated_count || 0;
        } else if (currentImportType === 'target_witel') {
            // ✅ NEW: Target Witel uses will_insert and will_update
            newCount = summary.will_insert || summary.new_count || summary.created_count || 0;
            updateCount = summary.will_update || summary.update_count || summary.updated_count || 0;
            console.log('🎯 target_witel counts extracted:', {
                will_insert: summary.will_insert,
                will_update: summary.will_update,
                newCount: newCount,
                updateCount: updateCount
            });
        } else {
            newCount = summary.new_count || summary.created_count || 0;
            updateCount = summary.update_count || summary.updated_count || 0;
        }

        let count = 0;
        let filterLabel = '';
        
        if (filterType === 'all') {
            count = newCount + updateCount;
            filterLabel = 'semua';
        } else if (filterType === 'new') {
            count = newCount;
            filterLabel = 'data baru';
        } else if (filterType === 'update') {
            count = updateCount;
            filterLabel = 'data update';
        }

        console.log(`📊 Filter stats:`, {
            filterType: filterType,
            count: count,
            filterLabel: filterLabel,
            newCount: newCount,
            updateCount: updateCount,
            importType: currentImportType
        });

        if (count === 0) {
            console.warn(`⚠️ No ${filterLabel} to import`);
            alert(`Tidak ada ${filterLabel} untuk diimport`);
            return;
        }

        if (!currentSessionId) {
            console.error('❌ currentSessionId is missing');
            alert('Error: Session ID tidak tersedia. Silakan upload ulang file.');
            return;
        }

        if (!currentImportType) {
            console.error('❌ currentImportType is missing');
            alert('Error: Import type tidak tersedia. Silakan upload ulang file.');
            return;
        }

        if (!confirm(`Import ${count} ${filterLabel}?`)) {
            console.log('❌ User cancelled import');
            return;
        }

        const previewModal = bootstrap.Modal.getInstance(document.getElementById('previewModal'));
        if (previewModal) {
            previewModal.hide();
        }

        showProgressSnackbar(`Mengimport ${count} data...`);
        updateProgress(10, 'Mengirim data ke server...');

        const payload = {
            session_id: currentSessionId,
            filter_type: filterType,
            import_type: currentImportType
        };

        // ✅ Add year/month for revenue imports and target_witel
        if (currentImportType === 'revenue_am' || currentImportType === 'revenue_cc' || currentImportType === 'target_witel') {
            if (!currentImportYear || !currentImportMonth) {
                console.error('❌ Year/Month missing for revenue import:', {
                    year: currentImportYear,
                    month: currentImportMonth,
                    importType: currentImportType
                });
                hideProgressSnackbar();
                alert('Error: Data periode tidak lengkap. Silakan upload ulang.');
                return;
            }
            payload.year = currentImportYear;
            payload.month = currentImportMonth;
            
            console.log('✅ Added year/month to payload:', {
                year: payload.year,
                month: payload.month
            });
        }

        // ✅ Add Revenue CC specific params
        if (currentImportType === 'revenue_cc') {
            if (!currentImportDivisiId || !currentImportTipeRevenue || !currentImportJenisData) {
                console.error('❌ Revenue CC parameters missing:', {
                    divisi_id: currentImportDivisiId,
                    tipe_revenue: currentImportTipeRevenue,
                    jenis_data: currentImportJenisData
                });
                hideProgressSnackbar();
                alert('Error: Parameter Revenue CC tidak lengkap. Silakan upload ulang.');
                return;
            }
            payload.divisi_id = currentImportDivisiId;
            payload.tipe_revenue = currentImportTipeRevenue;
            payload.jenis_data = currentImportJenisData;
            
            console.log('✅ Added Revenue CC params to payload:', {
                divisi_id: payload.divisi_id,
                tipe_revenue: payload.tipe_revenue,
                jenis_data: payload.jenis_data
            });
        }

        // ✅ Add Target Witel specific params
        if (currentImportType === 'target_witel') {
            if (!currentImportDivisiId) {
                console.error('❌ Target Witel divisi_id missing:', {
                    divisi_id: currentImportDivisiId
                });
                hideProgressSnackbar();
                alert('Error: Divisi ID tidak tersedia. Silakan upload ulang.');
                return;
            }
            payload.divisi_id = currentImportDivisiId;
            
            console.log('✅ Added Target Witel params to payload:', {
                divisi_id: payload.divisi_id,
                year: payload.year,
                month: payload.month
            });
        }

        console.log('✅ Executing import with complete payload:', payload);

        $.ajax({
            url: '/revenue-data/import/execute',
            method: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            timeout: 300000,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = 10 + (evt.loaded / evt.total) * 80;
                        updateProgress(percentComplete, 'Memproses import...');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                console.log('✅ Import execute response:', response);

                updateProgress(100, 'Import selesai!');

                setTimeout(() => {
                    hideProgressSnackbar();

                    if (!response) {
                        console.error('❌ Execute response is null');
                        alert('Error: Server tidak memberikan response');
                        return;
                    }

                    if (response.success) {
                        console.log('✅ Import completed successfully');
                        showImportResult(response);
                        loadData();
                    } else {
                        console.error('❌ Import failed:', response);
                        alert('Import gagal: ' + (response.message || response.error || 'Unknown error'));
                    }
                }, 500);
            },
            error: function(xhr, status, error) {
                hideProgressSnackbar();
                
                console.error('❌ Import execute error:', {
                    status: status,
                    error: error,
                    statusCode: xhr.status,
                    response: xhr.responseJSON,
                    responseText: xhr.responseText
                });
                
                let errorMsg = 'Terjadi kesalahan saat import';
                
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    } else if (xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        if (typeof errors === 'object') {
                            const errorMessages = Object.values(errors).flat();
                            errorMsg = errorMessages.join(', ');
                        } else {
                            errorMsg = errors;
                        }
                    }
                } else if (xhr.responseText) {
                    try {
                        const parsed = JSON.parse(xhr.responseText);
                        errorMsg = parsed.message || parsed.error || errorMsg;
                    } catch (e) {
                        errorMsg = xhr.statusText || errorMsg;
                    }
                }
                
                alert('Error: ' + errorMsg);
            }
        });

    } catch (error) {
        console.error('❌ Unexpected error in executeImportWithFilter:', error);
        console.error('Error stack:', error.stack);
        hideProgressSnackbar();
        alert('Error: Terjadi kesalahan tidak terduga - ' + error.message);
    }
}


// ========================================
// ✅ BUTTON EVENT HANDLERS FOR IMPORT
// ========================================
$('#btnImportAll').click(function() {
    executeImportWithFilter('all');
});

$('#btnImportNew').click(function() {
    executeImportWithFilter('new');
});

$('#btnImportUpdate').click(function() {
    executeImportWithFilter('update');
});


// ================================================================
// ✅ FIXED: editRevenueCC() - Populate form with 3 revenue fields
// ================================================================
window.editRevenueCC = function(id) {
    console.log('📝 Opening edit Revenue CC modal:', id);
    
    $.ajax({
        url: `/revenue-data/revenue-cc/${id}`,
        method: 'GET',
        success: function(response) {
            console.log('✅ Revenue CC data loaded:', response);
            
            if (response.success) {
                const data = response.data;
                
                // Populate form fields
                $('#editCCRevenueId').val(data.id);
                $('#editCCNamaCC').val(data.nama_cc);
                $('#editCCNipnas').val(data.nipnas);
                $('#editCCSegment').val(data.segment);
                $('#editCCDivisi').val(data.divisi);
                $('#editCCPeriode').val(data.period_name || `${data.bulan}/${data.tahun}`);
                $('#editCCTipeRevenue').val(data.tipe_revenue);
                
                // ✅ FIX: Populate all 3 revenue fields
                $('#editCCTargetRevenueSold').val(data.target_revenue_sold);
                $('#editCCRealRevenueSold').val(data.real_revenue_sold);
                $('#editCCRealRevenueBill').val(data.real_revenue_bill);

                // ✅ Reset to Tab 1 (Data Revenue)
                $('#tab-revenue-data-tab').addClass('active');
                $('#tab-mapping-am-tab').removeClass('active');
                $('#tab-revenue-data').addClass('show active');
                $('#tab-mapping-am').removeClass('show active');

                console.log('✅ Reset to Tab 1 - Data Revenue');

                // ✅ Attach tab click handler for Mapping AM
                $('#tab-mapping-am-tab').off('click').on('click', function(e) {
                    console.log('🔄 Tab Mapping AM clicked');
                    e.preventDefault();
                    
                    $('#tab-revenue-data-tab').removeClass('active');
                    $('#tab-mapping-am-tab').addClass('active');
                    $('#tab-revenue-data').removeClass('show active');
                    $('#tab-mapping-am').addClass('show active');
                    
                    console.log('✅ Switched to Tab 2 - Mapping AM');
                    loadMappingAMTab(id);
                });

                // ✅ Attach tab 1 click handler
                $('#tab-revenue-data-tab').off('click').on('click', function(e) {
                    console.log('🔄 Tab Data Revenue clicked');
                    e.preventDefault();
                    
                    $('#tab-revenue-data-tab').addClass('active');
                    $('#tab-mapping-am-tab').removeClass('active');
                    $('#tab-revenue-data').addClass('show active');
                    $('#tab-mapping-am').removeClass('show active');
                    
                    console.log('✅ Switched to Tab 1 - Data Revenue');
                });

                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('modalEditRevenueCC'));
                modal.show();

                console.log('✅ Modal shown successfully');
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            console.error('❌ Error loading Revenue CC:', xhr);
            alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
        }
    });
};

// ================================================================
// ✅ FIXED: Form submit handler - Send all 3 revenue fields
// ================================================================
$('#formEditRevenueCC').on('submit', function(e) {
    e.preventDefault();
    
    const id = $('#editCCRevenueId').val();
    
    // ✅ FIX: Send all 3 revenue fields
    const data = {
        target_revenue_sold: $('#editCCTargetRevenueSold').val(),
        real_revenue_sold: $('#editCCRealRevenueSold').val(),
        real_revenue_bill: $('#editCCRealRevenueBill').val()
    };

    console.log('📤 Submitting Revenue CC update:', data);

    $.ajax({
        url: `/revenue-data/revenue-cc/${id}`,
        method: 'PUT',
        data: JSON.stringify(data),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            console.log('✅ Update response:', response);
            
            if (response.success) {
                alert(response.message);
                bootstrap.Modal.getInstance(document.getElementById('modalEditRevenueCC')).hide();
                loadData(); // Reload table
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            console.error('❌ Update failed:', xhr);
            alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
        }
    });
});





// ================================================================
// ✅ NEW: recalculateMappingAM() - Real-time recalculation
// ================================================================
function recalculateMappingAM(data) {
    let totalProporsi = 0;
    const baseSold = data.cc_revenue.real_revenue_sold;
    const baseTarget = data.cc_revenue.target_revenue_sold;

    $('.mapping-proporsi-input').each(function() {
        const proporsi = parseFloat($(this).val()) || 0;
        totalProporsi += proporsi;

        const row = $(this).closest('tr');
        const realRevenue = (baseSold * proporsi) / 100;
        const targetRevenue = (baseTarget * proporsi) / 100;

        row.find('.real-revenue-display').text(formatCurrency(realRevenue));
        row.find('.target-revenue-display').text(formatCurrency(targetRevenue));
    });

    $('#mappingTotalProporsi').text(totalProporsi.toFixed(2) + '%');

    const isValid = Math.abs(totalProporsi - 100) < 0.01;
    $('#btnSaveMappingAM').prop('disabled', !isValid);

    if (isValid) {
        $('#mappingProporsiAlert')
            .removeClass('alert-warning alert-danger')
            .addClass('alert alert-success proporsi-alert-success')
            .html('<i class="fa-solid fa-check-circle me-2"></i><strong>Valid:</strong> Total proporsi = 100%')
            .show();
    } else if (totalProporsi < 100) {
        const remaining = (100 - totalProporsi).toFixed(2);
        $('#mappingProporsiAlert')
            .removeClass('alert-success alert-danger')
            .addClass('alert alert-warning proporsi-alert-warning')
            .html('<i class="fa-solid fa-exclamation-triangle me-2"></i><strong>Warning:</strong> Total = ' + totalProporsi.toFixed(2) + '% (sisa ' + remaining + '%)')
            .show();
    } else {
        const excess = (totalProporsi - 100).toFixed(2);
        $('#mappingProporsiAlert')
            .removeClass('alert-success alert-warning')
            .addClass('alert alert-danger proporsi-alert-danger')
            .html('<i class="fa-solid fa-times-circle me-2"></i><strong>Error:</strong> Total = ' + totalProporsi.toFixed(2) + '% (kelebihan ' + excess + '%)')
            .show();
    }
}

// ================================================================
// ✅ NEW: saveMappingAM() - Save proporsi changes
// ================================================================
function saveMappingAM(ccRevenueId) {
    const mappings = [];
    
    $('.mapping-proporsi-input').each(function() {
        mappings.push({
            am_revenue_id: $(this).data('am-revenue-id'),
            proporsi: parseFloat($(this).val()) / 100
        });
    });

    console.log('📤 Saving mapping AM:', mappings);

    $.ajax({
        url: `/revenue-data/revenue-cc/${ccRevenueId}/am-mappings`,
        method: 'PUT',
        data: JSON.stringify({ am_mappings: mappings }),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            if (response.success) {
                alert('Mapping AM berhasil disimpan!');
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



function loadMappingAMTab(ccRevenueId) {
    const container = $('#mappingAmContent');

    container.html(`
        <div class="text-center text-muted py-5">
            <i class="fa-solid fa-spinner fa-spin fa-3x mb-3"></i>
            <p>Loading mapping AM...</p>
        </div>
    `);

    $.ajax({
        url: `/revenue-data/revenue-cc/${ccRevenueId}/am-mappings/edit`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderMappingAMContent(response.data);
            } else {
                container.html(`
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>
                        ${response.message || 'Gagal memuat data'}
                    </div>
                `);
            }
        },
        error: function(xhr) {
            container.html(`
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                    ${xhr.responseJSON?.message || 'Terjadi kesalahan server'}
                </div>
            `);
        }
    });
}


function renderMappingAMContent(data) {
    const container = $('#mappingAmContent');

    if (!data.am_mappings || data.am_mappings.length === 0) {
        container.html(`
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-info-circle fa-3x mb-3"></i>
                <h5>Belum ada Account Manager</h5>
                <p>Belum ada Account Manager yang dikaitkan dengan Revenue CC ini</p>
                <small class="text-muted">Import Revenue AM untuk menambahkan mapping</small>
            </div>
        `);
        return;
    }

    let html = `
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>Nama AM</th>
                        <th>Proporsi (%)</th>
                        <th class="text-end">Real Revenue</th>
                        <th class="text-end">Target Revenue</th>
                    </tr>
                </thead>
                <tbody>
    `;

    data.am_mappings.forEach(function(am) {
        const val = parseFloat(am.proporsi_percent_display) || 0;

        html += `
            <tr>
                <td>${am.nik}</td>
                <td>${am.nama}</td>
                <td>
                    <input type="number"
                           class="form-control form-control-sm proporsi-input"
                           data-am-revenue-id="${am.am_revenue_id}"
                           value="${val}"
                           min="0"
                           max="100"
                           step="0.01">
                </td>
                <td class="text-end real-revenue-display">${formatCurrency(am.real_revenue_display)}</td>
                <td class="text-end target-revenue-display">${formatCurrency(am.target_revenue_display)}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Total</th>
                        <th id="totalProporsi">0%</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <button type="button" class="btn btn-primary w-100" id="btnSaveMappingAM" disabled>
            <i class="fa-solid fa-save me-2"></i>Simpan Mapping
        </button>
    `;

    container.html(html);

    $('.proporsi-input').on('input', function() {
        recalculateMappingAM(data);
    });

    $('#btnSaveMappingAM').off().on('click', function() {
        saveMappingAM(data.cc_revenue.id);
    });

    recalculateMappingAM(data);
}

function recalculateMappingAM(data) {
    let totalProporsi = 0;

    const baseSold = parseFloat(data.cc_revenue.real_revenue_sold) || 0;
    const baseTarget = parseFloat(data.cc_revenue.target_revenue_sold) || 0;

    $('.proporsi-input').each(function() {
        let proporsi = parseFloat($(this).val());

        if (isNaN(proporsi)) proporsi = 0;

        // Clamp 0–100
        proporsi = Math.max(0, Math.min(100, proporsi));
        $(this).val(proporsi);

        totalProporsi += proporsi;

        const row = $(this).closest('tr');

        const realRevenue = (baseSold * proporsi) / 100;
        const targetRevenue = (baseTarget * proporsi) / 100;

        row.find('.real-revenue-display').text(formatCurrency(realRevenue));
        row.find('.target-revenue-display').text(formatCurrency(targetRevenue));
    });

    $('#totalProporsi').text(totalProporsi.toFixed(2) + '%');

    const isValid = Math.abs(totalProporsi - 100) < 0.01;

    $('#btnSaveMappingAM').prop('disabled', !isValid);

    $('#totalProporsi')
        .toggleClass('text-success', isValid)
        .toggleClass('text-danger', !isValid);
}


function saveMappingAM(ccRevenueId) {
    const btn = $('#btnSaveMappingAM');

    btn.prop('disabled', true)
       .html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Menyimpan...');

    const mappings = [];

    $('.proporsi-input').each(function() {
        const val = parseFloat($(this).val()) || 0;

        mappings.push({
            am_revenue_id: $(this).data('am-revenue-id'),
            proporsi: val / 100
        });
    });

    $.ajax({
        url: `/revenue-data/revenue-cc/${ccRevenueId}/am-mappings`,
        method: 'PUT',
        data: JSON.stringify({ am_mappings: mappings }),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },

        success: function(response) {
            if (response.success) {
                alert('Mapping AM berhasil disimpan!');
                loadData();
            } else {
                alert(response.message || 'Gagal menyimpan');
            }
        },

        error: function(xhr) {
            alert(xhr.responseJSON?.message || 'Terjadi kesalahan server');
        },

        complete: function() {
            btn.prop('disabled', false)
               .html('<i class="fa-solid fa-save me-2"></i>Simpan Mapping');
        }
    });
}




// ================================================================
// ✅ NEW: renderAMTableForEdit() - Render AM mapping table
// ================================================================
function renderAMTableForEdit(currentAM, otherAMs) {
    const tbody = $('#editAMTableBody');
    tbody.empty();
    
    // Calculate current proporsi percent
    const currentProporsiPercent = (parseFloat(currentAM.proporsi) * 100).toFixed(2);
    
    let html = '';
    
    // Row 1: Current AM (EDITABLE - highlighted)
    html += `
        <tr class="am-mapping-row editing">
            <td style="padding: 1rem; border-bottom: 1px solid #dee2e6; font-weight: 600;">${currentAM.account_manager_nik || 'N/A'}</td>
            <td style="padding: 1rem; border-bottom: 1px solid #dee2e6;">
                <strong>${currentAM.account_manager_nama || 'N/A'}</strong>
                <br><small class="text-muted"><i class="fa-solid fa-pen me-1"></i>Sedang diedit</small>
            </td>
            <td style="padding: 1rem; border-bottom: 1px solid #dee2e6;">
                <input type="number" 
                       class="form-control proporsi-input" 
                       id="editAMProporsiInput"
                       data-am-revenue-id="${currentAM.id}"
                       value="${currentProporsiPercent}" 
                       min="0" 
                       max="100" 
                       step="0.01"
                       placeholder="0.00">
            </td>
            <td style="padding: 1rem; border-bottom: 1px solid #dee2e6; text-align: right;">
                <span class="target-revenue-display" id="editAMTargetRevenueDisplay" style="font-weight: 600; color: #004085;">
                    ${formatCurrency(currentAM.target_revenue || 0)}
                </span>
            </td>
            <td style="padding: 1rem; border-bottom: 1px solid #dee2e6; text-align: right;">
                <span class="real-revenue-display" id="editAMRealRevenueDisplay" style="font-weight: 600; color: #155724;">
                    ${formatCurrency(currentAM.real_revenue || 0)}
                </span>
            </td>
        </tr>
    `;
    
    // Rows 2+: Other AMs (READ-ONLY)
    if (otherAMs && otherAMs.length > 0) {
        otherAMs.forEach(function(otherAM) {
            const proporsiPercent = (parseFloat(otherAM.proporsi) * 100).toFixed(2);
            
            html += `
                <tr class="am-mapping-row readonly">
                    <td style="padding: 1rem; border-bottom: 1px solid #dee2e6; font-weight: 600; color: #6c757d;">${otherAM.account_manager_nik || 'N/A'}</td>
                    <td style="padding: 1rem; border-bottom: 1px solid #dee2e6; color: #6c757d;">${otherAM.account_manager_nama || 'N/A'}</td>
                    <td style="padding: 1rem; border-bottom: 1px solid #dee2e6;">
                        <input type="text" 
                               class="form-control" 
                               value="${proporsiPercent}%" 
                               readonly 
                               style="background: #e9ecef; border: 2px solid #dee2e6; text-align: center; font-weight: 600; cursor: not-allowed;">
                    </td>
                    <td style="padding: 1rem; border-bottom: 1px solid #dee2e6; text-align: right; color: #6c757d;">
                        ${formatCurrency(otherAM.target_revenue || 0)}
                    </td>
                    <td style="padding: 1rem; border-bottom: 1px solid #dee2e6; text-align: right; color: #6c757d;">
                        ${formatCurrency(otherAM.real_revenue || 0)}
                    </td>
                </tr>
            `;
        });
    }
    
    tbody.html(html);
    
    // ============================================
    // ATTACH INPUT LISTENER FOR REAL-TIME CALCULATION
    // ============================================
    $('#editAMProporsiInput').on('input', function() {
        const newProporsiPercent = parseFloat($(this).val()) || 0;
        
        // Validate range
        if (newProporsiPercent < 0 || newProporsiPercent > 100) {
            showProporsiAlert('danger', '<i class="fa-solid fa-exclamation-triangle me-2"></i><strong>Error:</strong> Proporsi harus antara 0-100%');
            $('#btnSaveEditAM').prop('disabled', true);
            return;
        }
        
        // Convert to decimal for calculation
        const newProporsiDecimal = newProporsiPercent / 100;
        
        // Get CC Revenue base
        const ccTargetSold = parseFloat($('#editAMCCTargetSold').val()) || 0;
        const ccRealSold = parseFloat($('#editAMCCRealSold').val()) || 0;
        
        // Calculate new revenues
        const newTargetRevenue = ccTargetSold * newProporsiDecimal;
        const newRealRevenue = ccRealSold * newProporsiDecimal;
        
        // Update display
        $('#editAMTargetRevenueDisplay').text(formatCurrency(newTargetRevenue));
        $('#editAMRealRevenueDisplay').text(formatCurrency(newRealRevenue));
        
        // Calculate total proporsi (current + others)
        let otherAMsProporsi = 0;
        if (otherAMs && otherAMs.length > 0) {
            otherAMsProporsi = otherAMs.reduce((sum, am) => sum + parseFloat(am.proporsi), 0);
        }
        
        const totalProporsi = newProporsiDecimal + otherAMsProporsi;
        
        // Update total display
        $('#editAMTotalProporsi').text((totalProporsi * 100).toFixed(2) + '%');
        
        // Validate total
        validateProporsiTotal(totalProporsi);
        
        console.log('📊 Proporsi changed:', {
            proporsi_percent: newProporsiPercent,
            proporsi_decimal: newProporsiDecimal,
            cc_target_sold: ccTargetSold,
            cc_real_sold: ccRealSold,
            calculated_target: newTargetRevenue,
            calculated_real: newRealRevenue,
            total_proporsi: totalProporsi
        });
    });
}

// ================================================================
// ✅ NEW: validateProporsiTotal() - Validate 100% proporsi
// ================================================================
function validateProporsiTotal(totalProporsi) {
    const totalPercent = (totalProporsi * 100).toFixed(2);
    const alertDiv = $('#editAMProporsiAlert');
    const saveBtn = $('#btnSaveEditAM');
    
    if (Math.abs(totalProporsi - 1.0) < 0.0001) {
        // Valid: exactly 100%
        showProporsiAlert('success', 
            '<i class="fa-solid fa-check-circle me-2"></i><strong>Valid:</strong> Total proporsi = ' + totalPercent + '%'
        );
        saveBtn.prop('disabled', false);
    } else if (totalProporsi < 1.0) {
        // Under 100%
        const remaining = ((1.0 - totalProporsi) * 100).toFixed(2);
        showProporsiAlert('warning', 
            '<i class="fa-solid fa-exclamation-triangle me-2"></i><strong>Warning:</strong> Total proporsi = ' + totalPercent + '% (sisa ' + remaining + '%)'
        );
        saveBtn.prop('disabled', true);
    } else {
        // Over 100%
        const excess = ((totalProporsi - 1.0) * 100).toFixed(2);
        showProporsiAlert('danger', 
            '<i class="fa-solid fa-times-circle me-2"></i><strong>Error:</strong> Total proporsi = ' + totalPercent + '% (kelebihan ' + excess + '%)'
        );
        saveBtn.prop('disabled', true);
    }
}

// ================================================================
// ✅ NEW: showProporsiAlert() - Show validation alert
// ================================================================
function showProporsiAlert(type, message) {
    const alertDiv = $('#editAMProporsiAlert');
    
    alertDiv.removeClass('proporsi-alert-success proporsi-alert-warning proporsi-alert-danger');
    
    if (type === 'success') {
        alertDiv.addClass('alert proporsi-alert-success');
    } else if (type === 'warning') {
        alertDiv.addClass('alert proporsi-alert-warning');
    } else {
        alertDiv.addClass('alert proporsi-alert-danger');
    }
    
    alertDiv.html(message).show();
}


  function toggleTeldaField(role) {
    const teldaWrapper = document.getElementById('editDataAMTeldaWrapper');

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

  $('#editDataAMRole').on('change', function() {
    const role = $(this).val();
    toggleTeldaField(role);
  });

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

            $('#tab-edit-data').addClass('show active');
            $('#tab-change-password').removeClass('show active');

            if (data.is_registered) {
              $('#editDataAMTabs').show().css('display', 'flex');
            } else {
              $('#editDataAMTabs').hide();
            }

            $('#editDataAMId').val(data.id);
            $('#changePasswordAMId').val(data.id);
            $('#editDataAMNama').val(data.nama);
            $('#editDataAMNik').val(data.nik);
            $('#editDataAMRole').val(data.role);
            $('#editDataAMWitel').val(data.witel_id);
            $('#editDataAMTelda').val(data.telda_id || '');

            if ($('#divisiButtonGroup').children().length === 0) {
              initDivisiButtonGroup();
            }

            if (data.divisi && Array.isArray(data.divisi)) {
              const divisiIds = data.divisi.map(d => d.id);
              setTimeout(() => setSelectedDivisi(divisiIds), 100);
            }

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


  // ========================================
// FUNCTION: Show Delete Confirmation Modal
// ========================================
function showDeleteConfirmation(action) {
    pendingDeleteAction = action;

    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    
    // Set title and message
    $('#warningTitle').text(action.warningTitle || 'Peringatan!');
    $('#warningText').html(action.warningMessage);

    // Show/hide related data info
    if (action.relatedData) {
        $('#relatedCcRevenues').text(action.relatedData.cc_revenues_count || 0);
        $('#relatedAmRevenues').text(action.relatedData.am_revenues_count || 0);
        $('#relatedTotal').text(action.relatedData.total_related || action.relatedData.total_all || 0);
        $('#relatedDataInfo').show();
    } else {
        $('#relatedDataInfo').hide();
    }

    // Show/hide log option (only for Data CC)
    if (action.showLogOption) {
        $('#logOptionSection').show();
        $('input[name="deleteType"][value="permanent"]').prop('checked', true);
    } else {
        $('#logOptionSection').hide();
    }

    // Show/hide confirmation text (for DELETE ALL)
    if (action.requireConfirmText) {
        $('#confirmTextSection').show();
        $('#confirmationText').val('');
    } else {
        $('#confirmTextSection').hide();
    }

    modal.show();
}

// ========================================
// HANDLE CONFIRM BUTTON CLICK
// ========================================
$(document).on('click', '#btnConfirmDelete', function() {
    if (!pendingDeleteAction) {
        alert('Error: No pending action');
        return;
    }

    // Validate confirmation text if required
    if (pendingDeleteAction.requireConfirmText) {
        const confirmText = $('#confirmationText').val().trim();
        if (confirmText !== 'DELETE ALL') {
            alert('Ketik "DELETE ALL" untuk konfirmasi!');
            $('#confirmationText').focus();
            return;
        }
    }

    // Get delete type (only for Data CC)
    const deleteType = pendingDeleteAction.showLogOption 
        ? $('input[name="deleteType"]:checked').val() 
        : 'permanent';

    console.log('Confirm delete action:', {
        action: pendingDeleteAction.action,
        deleteType: deleteType,
        requireConfirmText: pendingDeleteAction.requireConfirmText
    });

    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();

    // Execute the action
    if (pendingDeleteAction.action === 'deleteSingleCC') {
        confirmDeleteSingleDataCC(pendingDeleteAction.id, deleteType);
    } else if (pendingDeleteAction.action === 'bulkDeleteCC') {
        confirmBulkDeleteDataCC(pendingDeleteAction.ids, deleteType);
    } else if (pendingDeleteAction.action === 'bulkDeleteAllCC') {
        confirmBulkDeleteAllDataCC(deleteType);
    }

    pendingDeleteAction = null;
});

// ========================================
// DELETE SINGLE DATA CC (WITH LOG OPTION)
// ========================================
window.deleteDataCC = function(id) {
    console.log('Delete Data CC:', id);

    $.ajax({
        url: `/revenue-data/data-cc/${id}`,
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            console.log('Delete response:', response);

            if (response.requires_confirmation) {
                // Show confirmation modal with related data info
                showDeleteConfirmation({
                    action: 'deleteSingleCC',
                    id: id,
                    warningTitle: 'Hapus Data CC',
                    warningMessage: `
                        <strong>${response.warning_data.cc_name}</strong> (${response.warning_data.nipnas})<br>
                        memiliki <strong>${response.warning_data.total_related} data terkait</strong> yang akan ikut terhapus!
                    `,
                    relatedData: response.warning_data,
                    showLogOption: true,
                    requireConfirmText: false
                });
            } else if (response.success) {
                // No related data, deleted successfully
                alert(response.message);
                loadData();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            console.error('Delete error:', xhr);
            alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
        }
    });
};

function confirmDeleteSingleDataCC(id, deleteType) {
    console.log('Confirming delete Data CC:', { id, deleteType });

    $.ajax({
        url: `/revenue-data/data-cc/${id}/confirm-delete`,
        method: 'POST',
        data: JSON.stringify({ delete_type: deleteType }),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            console.log('Confirm delete response:', response);

            if (response.success) {
                alert(response.message);
                loadData();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            console.error('Confirm delete error:', xhr);
            alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
        }
    });
}

// ========================================
// BULK DELETE DATA CC (WITH LOG OPTION)
// ========================================
function bulkDeleteDataCC(ids) {
    console.log('Bulk delete Data CC:', ids);

    $.ajax({
        url: '/revenue-data/bulk-delete-data-cc',
        method: 'POST',
        data: JSON.stringify({ ids: ids }),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            console.log('Bulk delete response:', response);

            if (response.requires_confirmation) {
                // Show confirmation modal
                showDeleteConfirmation({
                    action: 'bulkDeleteCC',
                    ids: ids,
                    warningTitle: 'Hapus Multiple Data CC',
                    warningMessage: `
                        Anda akan menghapus <strong>${response.warning_data.selected_cc} Data CC</strong><br>
                        yang memiliki <strong>${response.warning_data.total_related} data terkait</strong>!
                    `,
                    relatedData: response.warning_data,
                    showLogOption: true,
                    requireConfirmText: false
                });
            } else if (response.success) {
                // No related data, deleted successfully
                alert(response.message);
                loadData();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            console.error('Bulk delete error:', xhr);
            alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
        }
    });
}

function confirmBulkDeleteDataCC(ids, deleteType) {
    console.log('Confirming bulk delete Data CC:', { ids, deleteType });

    $.ajax({
        url: '/revenue-data/data-cc/confirm-bulk-delete',
        method: 'POST',
        data: JSON.stringify({ ids: ids, delete_type: deleteType }),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            console.log('Confirm bulk delete response:', response);

            if (response.success) {
                alert(response.message);
                loadData();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            console.error('Confirm bulk delete error:', xhr);
            alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
        }
    });
}

// ========================================
// BULK DELETE ALL DATA CC (WITH LOG OPTION)
// ========================================
function bulkDeleteAllDataCC() {
    console.log('Bulk delete ALL Data CC');

    $.ajax({
        url: '/revenue-data/bulk-delete-all-data-cc',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            console.log('Bulk delete all response:', response);

            if (response.requires_confirmation) {
                // Show confirmation modal with confirmation text
                showDeleteConfirmation({
                    action: 'bulkDeleteAllCC',
                    warningTitle: 'HAPUS SEMUA DATA CC',
                    warningMessage: `
                        <strong class="text-danger">PERINGATAN EKSTREM!</strong><br>
                        Anda akan menghapus <strong>SEMUA ${response.warning_data.total_cc} Data CC</strong><br>
                        beserta <strong>${response.warning_data.total_all} data terkait</strong>!
                    `,
                    relatedData: response.warning_data,
                    showLogOption: true,
                    requireConfirmText: true
                });
            } else if (response.success) {
                alert(response.message);
                loadData();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            console.error('Bulk delete all error:', xhr);
            alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
        }
    });
}

function confirmBulkDeleteAllDataCC(deleteType) {
    console.log('Confirming bulk delete ALL Data CC:', { deleteType });

    $.ajax({
        url: '/revenue-data/data-cc/confirm-bulk-delete-all',
        method: 'POST',
        data: JSON.stringify({ 
            delete_type: deleteType,
            confirmation_text: 'DELETE ALL'
        }),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            console.log('Confirm delete all response:', response);

            if (response.success) {
                alert(response.message);
                loadData();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            console.error('Confirm delete all error:', xhr);
            alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
        }
    });
}

// ========================================
// CONNECT EXISTING BUTTONS TO NEW FLOW
// ========================================
$('#btnDeleteSelectedDataCC').off('click').on('click', function() {
    const ids = $('.row-checkbox-data-cc:checked').map(function() {
        return $(this).data('id');
    }).get();

    if (ids.length === 0) {
        alert('Pilih minimal 1 data untuk dihapus');
        return;
    }

    bulkDeleteDataCC(ids);
});

$('#btnBulkDeleteDataCC').off('click').on('click', function() {
    bulkDeleteAllDataCC();
});

// ========================================
// DELETE REVENUE CC (NO LOG OPTION - DIRECT DELETE WITH WARNING)
// ========================================
window.deleteRevenueCC = function(id) {
    // Simple confirmation without log option
    if (!confirm('Hapus Revenue CC ini?\n\nData Revenue AM terkait juga akan ikut terhapus.\nTindakan ini tidak dapat dibatalkan!')) {
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


// ================================================================
// ✅ FIXED: Form submit handler - Parse float & validate before submit
// ================================================================
$('#formEditRevenueCC').on('submit', function(e) {
    e.preventDefault();
    
    const id = $('#editCCRevenueId').val();
    
    // ✅ FIX: Parse values as FLOAT (remove formatting)
    const targetRevenueSold = parseFloat($('#editCCTargetRevenueSold').val()) || 0;
    const realRevenueSold = parseFloat($('#editCCRealRevenueSold').val()) || 0;
    const realRevenueBill = parseFloat($('#editCCRealRevenueBill').val()) || 0;
    
    // ✅ VALIDATION: Check if values are valid numbers
    if (isNaN(targetRevenueSold) || isNaN(realRevenueSold) || isNaN(realRevenueBill)) {
        alert('❌ Error: Nilai revenue harus berupa angka valid!');
        return;
    }
    
    if (targetRevenueSold < 0 || realRevenueSold < 0 || realRevenueBill < 0) {
        alert('❌ Error: Nilai revenue tidak boleh negatif!');
        return;
    }
    
    const data = {
        target_revenue_sold: targetRevenueSold,
        real_revenue_sold: realRevenueSold,
        real_revenue_bill: realRevenueBill
    };

    console.log('📤 Submitting Revenue CC update:', data);

    $.ajax({
        url: `/revenue-data/revenue-cc/${id}`,
        method: 'PUT',
        data: JSON.stringify(data),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            console.log('✅ Update response:', response);
            
            if (response.success) {
                alert('✅ ' + response.message);
                
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('modalEditRevenueCC')).hide();
                
                // Reload table data
                loadData();
            } else {
                alert('❌ Error: ' + response.message);
            }
        },
        error: function(xhr) {
            console.error('❌ Update failed:', xhr);
            
            let errorMsg = 'Terjadi kesalahan';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.statusText) {
                errorMsg = xhr.statusText;
            }
            
            alert('❌ Error: ' + errorMsg);
        }
    });
});

// ================================================================
// ✅ OPTIONAL: Add input formatting (show with thousand separator)
// ================================================================
function formatNumberInput(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    input.addEventListener('blur', function() {
        let value = parseFloat(this.value.replace(/,/g, ''));
        if (!isNaN(value)) {
            // Format with thousand separator for display
            this.value = value.toLocaleString('id-ID', { maximumFractionDigits: 2 });
        }
    });
    
    input.addEventListener('focus', function() {
        // Remove formatting for editing
        this.value = this.value.replace(/,/g, '');
    });
}

// Apply formatting to all revenue inputs
$(document).ready(function() {
    formatNumberInput('editCCTargetRevenueSold');
    formatNumberInput('editCCRealRevenueSold');
    formatNumberInput('editCCRealRevenueBill');
});

// ================================================================
// ✅ HELPER: formatCurrency() - Already exists, just ensure it's available
// ================================================================
function formatCurrency(value) {
    if (!value) return 'Rp 0';
    return 'Rp ' + parseFloat(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });
}



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
  // ✅ HELPER FUNCTIONS
  // ========================================
  function formatCurrency(value) {
    if (!value) return 'Rp 0';
    return 'Rp ' + parseFloat(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });
  }

  function showAlert(message, type) {
    alert(message);
  }

  // ========================================
  // 🚀 INITIALIZE APPLICATION
  // ========================================
  console.log('🚀 Initializing Revenue Data Application...');
  loadFilterOptions();
  loadData();
  console.log('✅ Application initialized successfully!');

});
</script>

@endpush