@extends('layouts.main')

@section('title', 'Data Revenue Account Manager')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/revenue.css') }}">
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- ✅ CRITICAL: CSRF Meta Tag -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* ========== ROOT VARIABLES ========== */
        :root {
            --primary-blue: #0e223e;
            --secondary-blue: #1e3c72;
            --accent-blue: #2a5298;
            --light-blue: #e7f1ff;
            --blue-gradient: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 50%, var(--accent-blue) 100%);

            --success-green: #10b981;
            --template-green: #059669;
            --export-green: #16a085;
            --warning-orange: #f59e0b;
            --error-red: #ef4444;
            --info-blue: #3b82f6;
            --dark-blue: #ea1d25;

            --light-gray: #f8f9fa;
            --border-gray: #e3e6f0;
            --text-gray: #6c757d;
            --white: #ffffff;

            --shadow-sm: 0 2px 4px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 25px rgba(0,0,0,0.15);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
        }

        /* ========== DASHBOARD LAYOUT ========== */
        .main-content {
            padding: 24px;
            background: #ffffff;
            min-height: 100vh;
        }

        .header-dashboard {
            background: var(--blue-gradient);
            color: white;
            padding: 32px;
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            box-shadow: var(--shadow-lg);
        }

        .header-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
        }

        .dashboard-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
            border: 1px solid var(--border-gray);
        }

        .card-header {
            padding: 24px;
            border-bottom: 1px solid var(--border-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-blue);
            margin: 0;
        }

        /* ========== 🔥 CRITICAL FIX: FORM LAYOUT - FORCED 2 ROWS × 3 COLUMNS ========== */
        .form-section {
            padding: 32px;
        }

        /* 🚀 ULTIMATE FIX: Force Grid Layout dengan specificity tinggi */
        .dashboard-card .form-section .form-row {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 24px !important;
            margin-bottom: 24px !important;
            width: 100% !important;
            /* Override any Bootstrap or external CSS */
            flex-direction: unset !important;
            flex-wrap: unset !important;
        }

        /* 🔥 Ensure form-group fills the grid cell properly */
        .dashboard-card .form-section .form-row > .form-group {
            width: 100% !important;
            margin-bottom: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            /* Prevent any float or inline behavior */
            float: none !important;
            position: relative !important;
        }

        /* 🔥 CRITICAL: Responsive dengan breakpoint yang lebih ketat */
        @media (min-width: 1200px) {
            .dashboard-card .form-section .form-row {
                grid-template-columns: repeat(3, 1fr) !important;
            }
        }

        @media (max-width: 1199px) and (min-width: 768px) {
            .dashboard-card .form-section .form-row {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 20px !important;
            }
        }

        @media (max-width: 767px) {
            .dashboard-card .form-section .form-row {
                grid-template-columns: 1fr !important;
                gap: 16px !important;
            }
            .form-section {
                padding: 20px;
            }
        }

        .form-group {
            width: 100%;
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-blue);
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 14px px;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius-md);
            font-size: 15px;
            line-height: 1.4;
            transition: all 0.3s ease;
            background: white;
            color: #2d3748;
        }

        .form-control:focus {
            border-color: var(--secondary-blue);
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
            outline: none;
            background: #fafbfc;
        }

        .form-control:hover {
            border-color: #cbd5e0;
        }

        .form-control::placeholder {
            color: #a0aec0;
            font-size: 14px;
        }

        .input-group {
            display: flex;
            width: 100%;
        }

        .input-group-text {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-right: none;
            color: var(--text-gray);
            font-weight: 600;
            padding: 14px 16px;
            border-radius: var(--radius-md) 0 0 var(--radius-md);
            font-size: 15px;
            line-height: 1.4;
        }

        .input-group .form-control {
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
            border-left: none;
        }

        .input-group .form-control:focus {
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
        }

        /* ========== 🔥 FIXED: MONTH PICKER - REDUCED HEIGHT ========== */
        .month-picker-container {
            position: relative;
            z-index: 10000;
            width: 100%;
            height: auto !important;
            min-height: unset !important;
            max-height: none !important;
        }

        .month-picker {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius-lg);
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            z-index: 10001 !important;
            display: none;
            padding: 20px;
            margin-top: 4px;
            min-width: 320px;
        }

        .month-picker.show {
            display: block;
            animation: slideDown 0.3s ease;
            z-index: 10001 !important;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .month-picker-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .year-selector {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .year-nav-btn {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 6px 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--text-gray);
            font-size: 14px;
            min-width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .year-nav-btn:hover {
            background: var(--secondary-blue);
            color: white;
            border-color: var(--secondary-blue);
            transform: translateY(-1px);
        }

        .current-year {
            font-weight: 700;
            font-size: 18px;
            color: var(--secondary-blue);
            min-width: 80px;
            text-align: center;
            background: var(--light-blue);
            padding: 8px 14px;
            border-radius: var(--radius-md);
            border: 2px solid var(--secondary-blue);
        }

        .month-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .month-option {
            padding: 12px 10px;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius-md);
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
            background: white;
            color: var(--text-gray);
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .month-option:hover {
            background: #f7fafc;
            border-color: var(--secondary-blue);
            transform: translateY(-1px);
        }

        .month-option.selected {
            background: var(--secondary-blue);
            color: white;
            border-color: var(--secondary-blue);
            font-weight: 600;
        }

        .month-picker-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
        }

        .month-picker-footer .btn {
            padding: 8px 16px;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid;
        }

        /* ========== 🆕 ENHANCED: BULK ACTIONS TOOLBAR ========== */
        .bulk-actions-toolbar {
            display: none;
            position: sticky;
            top: 0;
            z-index: 100;
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
            border-radius: var(--radius-md);
            padding: 16px 24px;
            margin: 0 24px 20px 24px;
            box-shadow: var(--shadow-md);
        }

        .bulk-actions-toolbar.show {
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideDown 0.3s ease;
        }

        .selection-info {
            font-weight: 600;
            color: #856404;
            font-size: 15px;
        }

        .selection-info i {
            color: #ffc107;
            margin-right: 8px;
            font-size: 16px;
        }

        .bulk-action-buttons {
            display: flex;
            gap: 12px;
        }

        .btn-bulk {
            padding: 10px 18px;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            border: 2px solid;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-bulk-danger {
            background: var(--error-red);
            color: white;
            border-color: var(--error-red);
        }

        .btn-bulk-danger:hover {
            background: #dc2626;
            border-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-bulk-light {
            background: white;
            color: var(--text-gray);
            border-color: #dee2e6;
        }

        .btn-bulk-light:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
        }

        /* 🆕 ENHANCED: Bulk Delete All Styles */
        .bulk-delete-all-section {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #f87171;
            border-radius: var(--radius-md);
            padding: 16px 24px;
            margin: 0 24px 20px 24px;
            text-align: center;
        }

        .btn-bulk-delete-all {
            background: #dc2626;
            color: white;
            border: 2px solid #dc2626;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-bulk-delete-all:hover {
            background: #b91c1c;
            border-color: #b91c1c;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* ========== 🔥 ULTIMATE FIX: PERFECT CHECKBOX ALIGNMENT ========== */
        .table-container {
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-gray);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table-modern {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            background: white;
            table-layout: fixed;
        }

        .table-modern thead th {
            background: var(--secondary-blue);
            color: white;
            font-weight: 600;
            border: none;
            padding: 16px 12px;
            font-size: 14px;
            text-align: left;
            white-space: nowrap;
            vertical-align: middle;
        }

        .table-modern tbody td {
            padding: 14px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .table-modern tbody tr:hover {
            background-color: #f8fafc;
        }

        .table-modern tbody tr.selected {
            background-color: #e3f2fd !important;
            border-left: 4px solid var(--secondary-blue);
        }

        /* 🚀 ULTIMATE CHECKBOX ALIGNMENT FIX - Force Perfect Centering */
        .table-select-header {
            width: 60px !important;
            min-width: 60px !important;
            max-width: 60px !important;
            text-align: center !important;
            padding: 16px 8px !important;
            vertical-align: middle !important;
            position: relative !important;
        }

        .table-select-cell {
            width: 60px !important;
            min-width: 60px !important;
            max-width: 60px !important;
            text-align: center !important;
            padding: 14px 8px !important;
            vertical-align: middle !important;
            position: relative !important;
        }

        /* 🔥 CRITICAL: Perfect checkbox styling and positioning */
        .row-checkbox,
        .table-modern input[type="checkbox"] {
            width: 18px !important;
            height: 18px !important;
            cursor: pointer !important;
            accent-color: var(--secondary-blue) !important;
            margin: 0 auto !important;
            display: block !important;
            position: relative !important;
            transform: none !important;
            /* Remove any Bootstrap or external styling */
            -webkit-appearance: checkbox !important;
            -moz-appearance: checkbox !important;
            appearance: checkbox !important;
        }

        /* 🔥 Force checkbox hover effects */
        .row-checkbox:hover,
        .table-modern input[type="checkbox"]:hover {
            transform: scale(1.1) !important;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.2) !important;
        }

        /* 🔥 Override any Bootstrap checkbox styling */
        .table-modern .form-check-input {
            width: 18px !important;
            height: 18px !important;
            margin: 0 auto !important;
            display: block !important;
            position: relative !important;
            top: auto !important;
            left: auto !important;
            transform: none !important;
            border: 2px solid #e2e8f0 !important;
            border-radius: 4px !important;
        }

        .table-modern .form-check-input:checked {
            background-color: var(--secondary-blue) !important;
            border-color: var(--secondary-blue) !important;
        }

        .table-modern .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.2) !important;
        }

        /* ========== BUTTONS ========== */
        .btn-save {
            background: var(--secondary-blue);
            color: white;
            border: 2px solid var(--secondary-blue);
            padding: 14px 28px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .btn-save:hover {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-import {
            background: var(--accent-blue);
            color: white;
            border: 2px solid var(--accent-blue);
            padding: 12px 22px;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .btn-import:hover {
            background: #1e40af;
            border-color: #1e40af;
            color: white;
            transform: translateY(-2px);
        }

        .btn-export {
            background: var(--export-green);
            color: white;
            border: 2px solid var(--export-green);
            padding: 12px 22px;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .btn-export:hover {
            background: #138d75;
            border-color: #138d75;
            color: white;
            transform: translateY(-2px);
        }

        /* ========== ACTION BUTTONS ========== */
        .action-btn {
            background: none;
            border: 2px solid;
            padding: 8px 10px;
            margin: 0 3px;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
        }

        .action-btn.edit-btn {
            color: var(--info-blue);
            border-color: var(--info-blue);
            background: rgba(59, 130, 246, 0.1);
        }

        .action-btn.edit-btn:hover {
            background: var(--info-blue);
            color: white;
            transform: translateY(-2px);
        }

        .action-btn.delete-btn {
            color: var(--error-red);
            border-color: var(--error-red);
            background: rgba(239, 68, 68, 0.1);
        }

        .action-btn.delete-btn:hover {
            background: var(--error-red);
            color: white;
            transform: translateY(-2px);
        }

        .action-btn.warning-btn {
            color: var(--warning-orange);
            border-color: var(--warning-orange);
            background: rgba(245, 158, 11, 0.1);
        }

        .action-btn.warning-btn:hover {
            background: var(--warning-orange);
            color: white;
            transform: translateY(-2px);
        }

        /* ========== FORM ACTIONS ========== */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border-gray);
        }

        /* ========== TABS SYSTEM ========== */
        .tab-menu-container {
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 0;
            background: white;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .tabs {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 8px;
            padding: 16px 24px 0;
        }

        .tab-item {
            padding: 14px 24px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            color: var(--text-gray);
            font-weight: 500;
            font-size: 14px;
            border-radius: var(--radius-md) var(--radius-md) 0 0;
            position: relative;
            background: #f8fafc;
        }

        .tab-item:hover {
            background: #e2e8f0;
            color: var(--dark-blue);
        }

        .tab-item.active {
            color: var(--secondary-blue);
            border-bottom-color: var(--secondary-blue);
            background: white;
            font-weight: 600;
        }

        .tab-item .badge {
            background: rgba(108, 117, 125, 0.1);
            color: var(--text-gray);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }

        .tab-item.active .badge {
            background: rgba(30, 60, 114, 0.1);
            color: var(--secondary-blue);
        }

        .tab-content {
            display: none;
            padding: 24px;
            background: white;
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        }

        .tab-content.active {
            display: block;
        }

        /* ========== 🆕 NOTIFICATIONS ENHANCED ========== */
        .notification-persistent {
            position: fixed;
            top: 20px;
            right: 20px;
            max-width: 400px;
            background: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            padding: 16px 24px;
            z-index: 1050;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            border-left: 4px solid #007bff;
            display: none;
        }

        .notification-persistent.show {
            display: block;
            transform: translateX(0);
        }

        .notification-persistent.success {
            border-left-color: var(--success-green);
        }

        .notification-persistent.error {
            border-left-color: var(--error-red);
        }

        .notification-persistent.warning {
            border-left-color: var(--warning-orange);
        }

        .notification-persistent .content {
            margin-right: 30px;
        }

        .notification-persistent .title {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 4px;
            color: #2d3748;
        }

        .notification-persistent .message {
            color: var(--text-gray);
            font-size: 14px;
            margin: 0;
            line-height: 1.4;
        }

        .notification-persistent .close-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: var(--text-gray);
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .notification-persistent .close-btn:hover {
            background: #f1f5f9;
            color: #2d3748;
        }

        /* ========== BADGES & STATUS ========== */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 2px solid;
        }

        .bg-success-soft {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-color: #c3e6cb;
        }

        .bg-warning-soft {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border-color: #ffeaa7;
        }

        .bg-danger-soft {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-color: #f5c6cb;
        }

        .divisi-badge {
            background: var(--light-blue);
            color: var(--primary-blue);
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 500;
            margin-right: 6px;
            display: inline-block;
            margin-bottom: 3px;
            border: 1px solid rgba(14, 34, 62, 0.2);
        }

        .user-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid;
        }

        .user-status-badge.registered {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .user-status-badge.not-registered {
            background: #fff3cd;
            color: #856404;
            border-color: #ffeaa7;
        }

        .user-status-badge i {
            margin-right: 4px;
            font-size: 10px;
        }

        /* ========== PROFILE PICTURES ========== */
        .am-profile-pic {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border-gray);
            margin-right: 10px;
        }

        /* ========== PAGINATION ========== */
        .pagination-container {
            padding: 20px 24px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        }

        .pagination-simple {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .pagination-info {
            color: var(--text-gray);
            font-size: 14px;
            font-weight: 500;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .page-btn {
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            background: white;
            color: var(--text-gray);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            height: 42px;
        }

        .page-btn:hover {
            background: #f8fafc;
            color: var(--dark-blue);
            text-decoration: none;
            border-color: #cbd5e0;
        }

        .page-btn.active {
            background: var(--secondary-blue);
            color: white;
            border-color: var(--secondary-blue);
        }

        .page-btn.disabled {
            background: #f1f5f9;
            color: #a0aec0;
            cursor: not-allowed;
            pointer-events: none;
        }

        .per-page-select {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius-md);
            font-size: 14px;
            background: white;
            min-width: 70px;
            color: var(--text-gray);
        }

        /* ========== EMPTY STATES ========== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: var(--radius-lg);
            border: 2px dashed #cbd5e0;
            margin: 20px 0;
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #a0aec0;
            margin-bottom: 20px;
        }

        .empty-state-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .empty-state-description {
            color: #718096;
            font-size: 0.95rem;
            line-height: 1.5;
            max-width: 400px;
            margin: 0 auto 25px;
        }

        .empty-state-action {
            background: var(--secondary-blue);
            color: white;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid var(--secondary-blue);
        }

        .empty-state-action:hover {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        /* ========== TEMPLATE CARDS ========== */
        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .template-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius-lg);
            padding: 24px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .template-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary-blue);
        }

        .template-icon {
            font-size: 3rem;
            color: var(--secondary-blue);
            margin-bottom: 16px;
        }

        .template-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 12px;
        }

        .template-description {
            color: #718096;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .template-btn {
            padding: 10px 18px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 2px solid;
        }

        .template-download {
            background: var(--template-green);
            color: white;
            border-color: var(--template-green);
        }

        .template-download:hover {
            background: #047857;
            border-color: #047857;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .template-export {
            background: var(--export-green);
            color: white;
            border-color: var(--export-green);
        }

        .template-export:hover {
            background: #138d75;
            border-color: #138d75;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        /* ========== SUGGESTIONS ========== */
        .suggestions-container {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 var(--radius-md) var(--radius-md);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: var(--shadow-md);
        }

        .suggestions-container.show {
            display: block;
        }

        .suggestion-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s ease;
        }

        .suggestion-item:hover,
        .suggestion-item.active {
            background-color: #f8fafc;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
        }

        .suggestion-detail {
            font-size: 12px;
            color: var(--text-gray);
            margin-top: 2px;
        }

        /* ========== SEARCH ENHANCEMENTS ========== */
        .search-form {
            position: relative;
        }

        .search-results-overlay {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            margin-top: 4px;
        }

        .search-results-overlay.show {
            display: block;
        }

        .search-description {
            background: linear-gradient(135deg, #e7f1ff 0%, #bee5eb 100%);
            border: 1px solid #bee5eb;
            border-radius: var(--radius-md);
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        /* ========== DIVISI BUTTONS ========== */
        .divisi-btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .divisi-btn {
            padding: 8px 16px;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius-md);
            background-color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            color: var(--text-gray) !important;
        }

        .divisi-btn:hover {
            border-color: var(--secondary-blue);
            background-color: #f8fafc;
        }

        .divisi-btn.active {
            background-color: var(--secondary-blue);
            color: white !important;
            border-color: var(--secondary-blue);
        }

        /* ========== ADD LINKS ========== */
        .add-link {
            color: var(--secondary-blue);
            text-decoration: none;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 6px;
            transition: color 0.2s ease;
            font-weight: 500;
        }

        .add-link:hover {
            color: var(--primary-blue);
            text-decoration: none;
        }

        /* ========== VALIDATION FEEDBACK ========== */
        .validation-feedback {
            display: block;
            font-size: 12px;
            margin-top: 4px;
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            transition: all 0.3s ease;
        }

        .validation-feedback.valid {
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }

        .validation-feedback.invalid {
            color: #721c24;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }

        .validation-feedback.pending {
            color: #856404;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
        }

        .validation-spinner {
            display: none;
            font-size: 12px;
            color: var(--text-gray);
            margin-top: 4px;
        }

        .validation-spinner.show {
            display: block;
        }

        /* ========== LOADING STATES ========== */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 16px;
            height: 16px;
            margin: -8px 0 0 -8px;
            border: 2px solid transparent;
            border-top: 2px solid #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-content {
            text-align: center;
            color: white;
        }

        /* ========== MODAL ENHANCEMENTS ========== */
        .modal-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 1000;
            border-radius: var(--radius-lg);
        }

        .modal-loading-overlay.show {
            display: flex;
        }

        .modal-loading-overlay .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--secondary-blue);
        }

        .modal-loading-overlay p {
            margin-top: 16px;
            color: var(--text-gray);
            font-weight: 500;
        }

        /* ========== 🆕 IMPORT RESULT MODAL STYLES ========== */
        .import-result-modal .modal-xl {
            max-width: 90%;
        }

        .import-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .summary-card.info {
            border-color: var(--info-blue);
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        }

        .summary-card.success {
            border-color: var(--success-green);
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        }

        .summary-card.warning {
            border-color: var(--warning-orange);
            background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
        }

        .summary-card.error {
            border-color: var(--error-red);
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
        }

        .summary-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
            line-height: 1;
        }

        .summary-label {
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .import-details {
            max-height: 300px;
            overflow-y: auto;
            padding: 16px;
            background: #f8fafc;
            border-radius: var(--radius-md);
            border: 1px solid #e2e8f0;
        }

        /* ========== PROGRESS STEPS FOR IMPORT ========== */
        .progress-container {
            background: #f8fafc;
            border-radius: var(--radius-lg);
            padding: 24px;
            margin: 20px 0;
        }

        .progress-step {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            opacity: 0.5;
            transition: all 0.3s ease;
        }

        .progress-step.active {
            opacity: 1;
        }

        .progress-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .progress-step.active .progress-icon {
            background: var(--secondary-blue);
            color: white;
        }

        /* ========== RESPONSIVE DESIGN ========== */
        @media (max-width: 576px) {
            .action-btn {
                padding: 6px 8px;
                font-size: 12px;
                min-width: 32px;
                height: 32px;
                margin: 0 2px;
            }

            .am-profile-pic {
                width: 28px;
                height: 28px;
            }

            .table-modern {
                font-size: 13px;
            }

            .table-modern thead th,
            .table-modern tbody td {
                padding: 10px 8px;
            }

            .table-select-header,
            .table-select-cell {
                width: 50px !important;
                min-width: 50px !important;
                max-width: 50px !important;
                padding: 12px 4px !important;
            }

            .row-checkbox,
            .table-modern input[type="checkbox"] {
                width: 16px !important;
                height: 16px !important;
            }

            .divisi-btn {
                padding: 6px 12px;
                font-size: 12px;
            }

            .notification-persistent {
                left: 10px;
                right: 10px;
                max-width: none;
            }

            .bulk-actions-toolbar {
                flex-direction: column;
                gap: 12px;
                text-align: center;
                margin: 0 10px 20px 10px;
            }

            .template-grid {
                grid-template-columns: 1fr;
                gap: 15px;
                padding: 15px;
            }

            .form-control {
                padding: 12px 14px;
                font-size: 14px;
            }

            .month-picker {
                min-width: 300px;
            }
        }

        /* ========== UTILITY CLASSES ========== */
        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .position-relative {
            position: relative;
        }

        .d-flex {
            display: flex;
        }

        .align-items-center {
            align-items: center;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .justify-content-center {
            justify-content: center;
        }

        .gap-2 {
            gap: 8px;
        }

        .me-2 {
            margin-right: 8px;
        }

        .ms-2 {
            margin-left: 8px;
        }

        .mt-2 {
            margin-top: 8px;
        }

        .mb-2 {
            margin-bottom: 8px;
        }

        .p-3 {
            padding: 16px;
        }

        .fw-bold {
            font-weight: 600;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: var(--text-gray);
        }

        .small {
            font-size: 0.875rem;
        }

        /* ========== ERROR BOUNDARY ========== */
        .js-error-boundary {
            display: none;
            background: #fef2f2;
            border: 2px solid #fecaca;
            border-radius: var(--radius-lg);
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .js-error-boundary.show {
            display: block;
        }

        .js-error-boundary h3 {
            color: #991b1b;
            margin-bottom: 10px;
        }

        .js-error-boundary p {
            color: #7f1d1d;
            margin-bottom: 15px;
        }

        .fallback-message {
            display: none;
            background: #fff3cd;
            border: 2px solid #ffeaa7;
            border-radius: var(--radius-md);
            padding: 15px;
            margin: 15px 0;
            color: #856404;
            font-size: 14px;
        }

        .no-js .fallback-message {
            display: block;
        }

        .no-js .js-dependent {
            display: none;
        }


        /* ========== ENHANCED MOBILE RESPONSIVE DESIGN ========== */

        /* Mobile Small (320px - 480px) */
        @media (max-width: 480px) {
            .main-content {
                padding: 12px 8px;
            }

            .header-dashboard {
                padding: 16px 12px;
                margin-bottom: 12px;
            }

            .header-title {
                font-size: 1.3rem;
                line-height: 1.2;
            }

            .header-subtitle {
                font-size: 0.9rem;
            }

            .card-header {
                padding: 12px;
                flex-direction: column;
                gap: 10px;
            }

            .card-header-actions {
                width: 100%;
                display: flex;
                gap: 6px;
            }

            .btn-export, .btn-import {
                flex: 1;
                padding: 8px 10px;
                font-size: 12px;
            }

            .form-section {
                padding: 12px;
            }

            /* Critical: Form stays in rows but stacks on very small screens */
            .dashboard-card .form-section .form-row {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }

            .form-control, .input-group-text {
                padding: 10px 12px;
                font-size: 14px;
            }

            .btn-save {
                width: 100%;
                padding: 12px 20px;
            }
        }

        /* Mobile Medium (481px - 576px) */
        @media (min-width: 481px) and (max-width: 576px) {
            .main-content {
                padding: 16px 12px;
            }

            .dashboard-card .form-section .form-row {
                grid-template-columns: 1fr 1fr !important;
                gap: 14px !important;
            }

            /* Last row item spans full width if odd number */
            .dashboard-card .form-section .form-row .form-group:nth-child(3n) {
                grid-column: 1 / -1;
            }
        }

        /* Tablet Portrait (577px - 768px) */
        @media (min-width: 577px) and (max-width: 768px) {
            .dashboard-card .form-section .form-row {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 16px !important;
            }

            .dashboard-card .form-section .form-row .form-group:nth-child(3) {
                grid-column: 1 / -1;
            }
        }

        /* Common Mobile Styles (up to 768px) */
        @media (max-width: 768px) {
            .action-btn {
                padding: 6px 8px;
                font-size: 12px;
                min-width: 32px;
                height: 32px;
                margin: 0 2px;
            }

            .am-profile-pic {
                width: 28px;
                height: 28px;
            }

            .table-modern {
                font-size: 13px;
            }

            .table-modern thead th,
            .table-modern tbody td {
                padding: 8px 6px;
            }

            .table-select-header,
            .table-select-cell {
                width: 45px !important;
                min-width: 45px !important;
                max-width: 45px !important;
                padding: 8px 4px !important;
            }

            .row-checkbox,
            .table-modern input[type="checkbox"] {
                width: 16px !important;
                height: 16px !important;
            }

            .divisi-btn {
                padding: 6px 10px;
                font-size: 11px;
                margin: 2px;
            }

            .notification-persistent {
                left: 8px;
                right: 8px;
                max-width: none;
                padding: 12px 16px;
            }

            .bulk-actions-toolbar {
                flex-direction: column;
                gap: 10px;
                text-align: center;
                margin: 0 8px 16px 8px;
                padding: 12px 16px;
            }

            .bulk-action-buttons {
                flex-direction: column;
                gap: 8px;
            }

            .btn-bulk {
                width: 100%;
                justify-content: center;
            }

            .template-grid {
                grid-template-columns: 1fr;
                gap: 12px;
                padding: 12px;
            }

            .template-card {
                padding: 16px;
            }

            .month-picker {
                min-width: 280px;
                left: 50%;
                transform: translateX(-50%);
            }

            .month-picker-header {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }

            .year-selector {
                justify-content: center;
            }

            .month-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }

            .month-option {
                padding: 8px 6px;
                font-size: 12px;
                min-height: 36px;
            }

            /* Search and Filter Mobile */
            .search-form {
                margin-bottom: 8px;
            }

            .input-group {
                flex-wrap: nowrap;
            }

            .filter-toggle-container {
                flex-direction: column;
                gap: 8px;
                align-items: stretch;
            }

            .filter-area .form-row {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }

            .filter-area .form-group {
                margin-bottom: 8px;
            }

            /* Tab System Mobile */
            .tabs {
                flex-wrap: wrap;
                gap: 4px;
                padding: 12px 16px 0;
            }

            .tab-item {
                padding: 10px 16px;
                font-size: 13px;
                flex: 1;
                min-width: calc(33.333% - 8px);
                text-align: center;
            }

            .tab-item i {
                display: none; /* Hide icons on mobile to save space */
            }

            .tab-content {
                padding: 16px 12px;
            }

            /* Pagination Mobile */
            .pagination-simple {
                flex-direction: column;
                gap: 12px;
            }

            .pagination-info {
                text-align: center;
                order: 1;
            }

            .pagination-controls {
                order: 2;
                justify-content: center;
                flex-wrap: wrap;
            }

            .page-btn {
                min-width: 36px;
                height: 36px;
                padding: 8px 10px;
                font-size: 13px;
            }

            .per-page-select {
                order: 3;
                text-align: center;
                margin: 0 auto;
            }

            /* Modal Mobile */
            .modal-dialog {
                margin: 8px;
                max-width: calc(100% - 16px);
            }

            .modal-header {
                padding: 12px 16px;
            }

            .modal-body {
                padding: 16px;
            }

            .modal-title {
                font-size: 1.1rem;
            }

            /* Empty State Mobile */
            .empty-state {
                padding: 40px 16px;
                margin: 12px 0;
            }

            .empty-state-icon {
                font-size: 3rem;
            }

            .empty-state-title {
                font-size: 1.1rem;
            }

            .empty-state-description {
                font-size: 0.9rem;
            }

            /* Status Badge Mobile */
            .status-badge, .divisi-badge, .user-status-badge {
                font-size: 10px;
                padding: 3px 8px;
            }

            /* Form Actions Mobile */
            .form-actions {
                margin-top: 16px;
                padding-top: 16px;
            }

            .form-actions .btn-save {
                width: 100%;
            }
        }

        /* Very Small Mobile Fixes (up to 360px) */
        @media (max-width: 360px) {
            .dashboard-card .form-section .form-row {
                gap: 10px !important;
            }

            .form-control {
                font-size: 13px;
            }

            .tab-item {
                min-width: calc(50% - 4px);
                font-size: 12px;
            }

            .month-picker {
                min-width: 260px;
                padding: 16px 12px;
            }

            .month-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }
        }

        /* ========== CRITICAL MOBILE TABLE IMPROVEMENTS ========== */
        @media (max-width: 768px) {
            /* Force table to take minimum required width */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
                scrollbar-color: #cbd5e0 transparent;
                width: 100%;
            }

            .table-responsive::-webkit-scrollbar {
                height: 6px;
            }

            .table-responsive::-webkit-scrollbar-track {
                background: #f1f5f9;
            }

            .table-responsive::-webkit-scrollbar-thumb {
                background: #cbd5e0;
                border-radius: 3px;
            }

            /* Force table minimum width and proper column sizing */
            .table-modern {
                min-width: 900px !important;
                width: 900px !important;
                table-layout: fixed !important;
                white-space: nowrap;
            }

            /* Fixed column widths for proper spacing */
            .table-modern th:nth-child(1), /* Checkbox */
            .table-modern td:nth-child(1) {
                width: 50px !important;
                min-width: 50px !important;
                max-width: 50px !important;
            }

            .table-modern th:nth-child(2), /* Name/AM */
            .table-modern td:nth-child(2) {
                width: 140px !important;
                min-width: 140px !important;
                max-width: 140px !important;
            }

            .table-modern th:nth-child(3), /* Divisi */
            .table-modern td:nth-child(3) {
                width: 90px !important;
                min-width: 90px !important;
                max-width: 90px !important;
            }

            .table-modern th:nth-child(4), /* Customer */
            .table-modern td:nth-child(4) {
                width: 150px !important;
                min-width: 150px !important;
                max-width: 150px !important;
            }

            .table-modern th:nth-child(5), /* Target */
            .table-modern td:nth-child(5) {
                width: 110px !important;
                min-width: 110px !important;
                max-width: 110px !important;
            }

            .table-modern th:nth-child(6), /* Real */
            .table-modern td:nth-child(6) {
                width: 110px !important;
                min-width: 110px !important;
                max-width: 110px !important;
            }

            .table-modern th:nth-child(7), /* Achievement */
            .table-modern td:nth-child(7) {
                width: 90px !important;
                min-width: 90px !important;
                max-width: 90px !important;
            }

            .table-modern th:nth-child(8), /* Month */
            .table-modern td:nth-child(8) {
                width: 100px !important;
                min-width: 100px !important;
                max-width: 100px !important;
            }

            .table-modern th:last-child, /* Actions */
            .table-modern td:last-child {
                width: 80px !important;
                min-width: 80px !important;
                max-width: 80px !important;
                position: sticky;
                right: 0;
                background: white;
                z-index: 10;
                box-shadow: -3px 0 6px rgba(0,0,0,0.1);
            }

            .table-modern thead th:last-child {
                background: var(--secondary-blue) !important;
                color: white !important;
                box-shadow: -3px 0 6px rgba(0,0,0,0.2);
            }

            /* Prevent text wrapping and overflow */
            .table-modern th,
            .table-modern td {
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
                padding: 8px 6px !important;
                font-size: 12px !important;
                line-height: 1.3 !important;
            }

            /* AM Profile pic smaller */
            .am-profile-pic {
                width: 24px !important;
                height: 24px !important;
                margin-right: 6px !important;
            }

            /* Status badges smaller */
            .status-badge,
            .divisi-badge,
            .user-status-badge {
                font-size: 9px !important;
                padding: 2px 6px !important;
                border-radius: 10px !important;
            }

            /* Action buttons more compact */
            .action-btn {
                padding: 4px 6px !important;
                font-size: 11px !important;
                min-width: 28px !important;
                height: 28px !important;
                margin: 0 1px !important;
            }

            /* Revenue number formatting for mobile */
            .table-modern td:nth-child(5),
            .table-modern td:nth-child(6) {
                font-size: 10px !important;
            }

            /* Better Mobile Form Spacing */
            .input-group-text {
                padding: 10px 12px;
                font-size: 13px;
            }

            /* Suggestions Container Mobile */
            .suggestions-container {
                max-height: 180px;
                font-size: 13px;
            }

            .suggestion-item {
                padding: 10px 12px;
            }

            .suggestion-name {
                font-size: 13px;
            }

            .suggestion-detail {
                font-size: 11px;
            }

            /* Mobile Search Results */
            .search-results-overlay {
                max-height: 250px;
                margin-top: 2px;
            }

            /* Better Mobile Modals */
            .modal-body .form-row {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }

            .modal-body .input-group {
                margin-bottom: 12px;
            }

            /* Mobile Alert Improvements */
            .alert {
                padding: 12px 16px;
                font-size: 14px;
                border-radius: 6px;
                margin-bottom: 12px;
            }

            .alert .alert-heading {
                font-size: 15px;
                margin-bottom: 8px;
            }

            /* Mobile Template Cards */
            .template-card {
                text-align: center;
                padding: 20px 16px;
            }

            .template-icon {
                font-size: 2.5rem;
                margin-bottom: 12px;
            }

            .template-title {
                font-size: 1.1rem;
                margin-bottom: 8px;
            }

            .template-description {
                font-size: 0.85rem;
                margin-bottom: 16px;
            }

            .template-btn {
                padding: 8px 14px;
                font-size: 13px;
            }

            /* Mobile Import Summary */
            .import-summary-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                margin-bottom: 16px;
            }

            .summary-card {
                padding: 16px 12px;
            }

            .summary-number {
                font-size: 2rem;
                margin-bottom: 6px;
            }

            .summary-label {
                font-size: 12px;
            }

            /* Mobile Bulk Delete Section */
            .bulk-delete-all-section {
                margin: 0 8px 16px 8px;
                padding: 12px 16px;
            }

            .bulk-delete-all-section h6 {
                font-size: 14px;
                margin-bottom: 6px;
            }

            .bulk-delete-all-section p {
                font-size: 12px;
            }

            .btn-bulk-delete-all {
                padding: 8px 16px;
                font-size: 13px;
            }

            /* Mobile Progress Steps */
            .progress-step {
                flex-direction: column;
                text-align: center;
                gap: 8px;
                padding: 8px 0;
            }

            .progress-icon {
                width: 32px;
                height: 32px;
                font-size: 12px;
                margin: 0 auto;
            }

            .progress-step strong {
                font-size: 13px;
            }

            .progress-step small {
                font-size: 11px;
            }

            /* Mobile Search Description */
            .search-description {
                padding: 10px 12px;
                margin-bottom: 12px;
                font-size: 13px;
            }

            /* Touch-friendly interactive elements */
            .cursor-pointer,
            .divisi-btn,
            .month-option,
            .page-btn,
            .tab-item {
                touch-action: manipulation;
            }

            /* Better mobile touch targets */
            .add-link {
                padding: 4px 0;
                min-height: 44px;
                display: inline-flex;
                align-items: center;
            }
        }

        /* ========== LANDSCAPE MOBILE OPTIMIZATION ========== */
        @media (max-width: 768px) and (orientation: landscape) {
            .header-dashboard {
                padding: 12px 16px;
                margin-bottom: 12px;
            }

            .header-title {
                font-size: 1.4rem;
            }

            .header-subtitle {
                font-size: 0.9rem;
            }

            .form-section {
                padding: 12px 16px;
            }

            .dashboard-card .form-section .form-row {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 12px !important;
            }

            .dashboard-card .form-section .form-row .form-group:nth-child(3n) {
                grid-column: span 1;
            }
        }

        /* ========== HIGH DPI MOBILE SCREENS ========== */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .table-modern {
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }

            .action-btn,
            .btn-save,
            .btn-import,
            .btn-export {
                -webkit-font-smoothing: antialiased;
            }
        }
    </style>
@endsection

@section('content')
    <div class="main-content">
        <!-- Progressive Enhancement Fallback -->
        <div class="fallback-message">
            <i class="fas fa-info-circle me-2"></i>
            <strong>JavaScript Dinonaktifkan:</strong> Beberapa fitur interaktif mungkin tidak berfungsi optimal.
            Silakan aktifkan JavaScript untuk pengalaman terbaik.
        </div>

        <!-- ✅ CRITICAL: Error Boundary for JavaScript Errors -->
        <div class="js-error-boundary" id="js-error-boundary">
            <h3><i class="fas fa-exclamation-triangle me-2"></i>Sistem Error</h3>
            <p>Terjadi kesalahan saat memuat sistem. Silakan muat ulang halaman.</p>
            <button class="btn btn-warning" onclick="window.location.reload()">
                <i class="fas fa-sync-alt me-1"></i> Muat Ulang Halaman
            </button>
        </div>

        <!-- Header Dashboard -->
        <div class="header-dashboard">
            <h1 class="header-title">
                Data Revenue Account Manager
            </h1>
            <p class="header-subtitle">
                Kelola dan pantau data revenue Account Manager RLEGS secara efisien
            </p>
        </div>

        <!-- Alert Messages -->
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- 🔥 FIXED: Form Tambah Data Revenue - PERFECT MOBILE-RESPONSIVE LAYOUT -->
        <div class="dashboard-card">
            <div class="card-header">
                <div>
                    <h5 class="card-title">Tambah Data Revenue</h5>
                    <p class="text-muted small mb-0">Tambahkan data revenue baru untuk Account Manager</p>
                </div>
                <div class="card-header-actions">
                    <a href="{{ route('revenue.export') }}" class="btn-export">
                        <i class="fas fa-download me-1"></i> Export Data
                    </a>
                    <button class="btn-import" data-bs-toggle="modal" data-bs-target="#importRevenueModal">
                        <i class="fas fa-upload me-1"></i> Import Excel
                    </button>
                </div>
            </div>
            <div class="form-section">
                <form action="{{ route('revenue.store') }}" method="POST" id="revenueForm" data-form-reset="true">
                    @csrf
                    <!-- 🚀 FIXED: ROW 1 - Nama Account Manager | Divisi | Nama Corporate Customer -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="account_manager" class="form-label"><strong>Nama Account Manager</strong></label>
                            <div class="position-relative">
                                <input type="text" id="account_manager" class="form-control"
                                    placeholder="Cari Account Manager..." required>
                                <input type="hidden" name="account_manager_id" id="account_manager_id">
                                <div id="account_manager_suggestions" class="suggestions-container"></div>
                                <div class="validation-feedback" id="am_validation"></div>
                                <div class="validation-spinner" id="am_spinner">
                                    <i class="fas fa-spinner fa-spin"></i> Mencari...
                                </div>
                            </div>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#addAccountManagerModal" class="add-link">
                                <i class="fas fa-plus-circle"></i> Tambah Account Manager Baru
                            </a>
                        </div>

                        <div class="form-group">
                            <label for="divisi_id" class="form-label"><strong>Divisi</strong></label>
                            <select id="divisi_id" name="divisi_id" class="form-control" required disabled>
                                <option value="">Pilih Divisi</option>
                            </select>
                            <small class="text-muted">Pilih Account Manager terlebih dahulu</small>
                        </div>

                        <div class="form-group">
                            <label for="corporate_customer" class="form-label"><strong>Nama Corporate Customer</strong></label>
                            <div class="position-relative">
                                <input type="text" id="corporate_customer" class="form-control"
                                    placeholder="Cari Corporate Customer..." required>
                                <input type="hidden" name="corporate_customer_id" id="corporate_customer_id">
                                <div id="corporate_customer_suggestions" class="suggestions-container"></div>
                                <div class="validation-feedback" id="cc_validation"></div>
                                <div class="validation-spinner" id="cc_spinner">
                                    <i class="fas fa-spinner fa-spin"></i> Mencari...
                                </div>
                            </div>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#addCorporateCustomerModal" class="add-link">
                                <i class="fas fa-plus-circle"></i> Tambah Corporate Customer Baru
                            </a>
                        </div>
                    </div>

                    <!-- 🚀 FIXED: ROW 2 - Target Revenue | Real Revenue | Bulan Capaian -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="target_revenue" class="form-label"><strong>Target Revenue</strong></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="target_revenue" id="target_revenue"
                                    placeholder="Masukkan target revenue" required>
                            </div>
                            <small class="text-muted">Nilai negatif diperbolehkan</small>
                        </div>

                        <div class="form-group">
                            <label for="real_revenue" class="form-label"><strong>Real Revenue</strong></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="real_revenue" id="real_revenue"
                                    placeholder="Masukkan real revenue" required>
                            </div>
                            <small class="text-muted">Nilai negatif diperbolehkan</small>
                        </div>

                        <div class="form-group">
                            <label for="month_year_picker" class="form-label"><strong>Bulan Capaian</strong></label>
                            <div class="month-picker-container">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="text" id="month_year_picker" class="form-control input-date"
                                        placeholder="Pilih Bulan dan Tahun" readonly>
                                    <span class="input-group-text cursor-pointer" id="open_month_picker">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                </div>
                                <input type="hidden" name="bulan_month" id="bulan_month" value="{{ date('m') }}">
                                <input type="hidden" name="bulan_year" id="bulan_year" value="{{ date('Y') }}">
                                <input type="hidden" name="bulan" id="bulan" value="{{ date('Y-m') }}">

                                <!-- FIXED: Mobile-Responsive Month Picker Layout -->
                                <div id="global_month_picker" class="month-picker">
                                    <div class="month-picker-header">
                                        <div class="year-selector">
                                            <button type="button" class="year-nav-btn" id="prev_year">
                                                <i class="fas fa-chevron-left"></i>
                                            </button>
                                            <span class="current-year" id="current_year">{{ date('Y') }}</span>
                                            <button type="button" class="year-nav-btn" id="next_year">
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="month-grid" id="month_grid"></div>
                                    <div class="month-picker-footer">
                                        <button type="button" class="btn btn-light" id="cancel_month">Batal</button>
                                        <button type="button" class="btn btn-primary" id="apply_month">Pilih</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save" id="save-revenue-btn">
                            <i class="fas fa-save"></i> Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Template & Export Section -->
        <div class="dashboard-card">
            <div class="card-header">
                <div>
                    <h5 class="card-title">Template & Export Data</h5>
                    <p class="text-muted small mb-0">Unduh template dan export data untuk berbagai kebutuhan</p>
                </div>
            </div>
            <div class="template-grid">
                <div class="template-card">
                    <div class="template-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="template-title">Account Manager</div>
                    <div class="template-description">Template dan export data Account Manager beserta divisi dan regional</div>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('account-manager.template') }}" class="template-btn template-download">
                            <i class="fas fa-download"></i> Template
                        </a>
                        <a href="{{ route('account-manager.export') }}" class="template-btn template-export">
                            <i class="fas fa-file-export"></i> Export
                        </a>
                    </div>
                </div>

                <div class="template-card">
                    <div class="template-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="template-title">Corporate Customer</div>
                    <div class="template-description">Template dan export data Corporate Customer dengan NIPNAS</div>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('corporate-customer.template') }}" class="template-btn template-download">
                            <i class="fas fa-download"></i> Template
                        </a>
                        <a href="{{ route('corporate-customer.export') }}" class="template-btn template-export">
                            <i class="fas fa-file-export"></i> Export
                        </a>
                    </div>
                </div>

                <div class="template-card">
                    <div class="template-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="template-title">Revenue Data</div>
                    <div class="template-description">Template fleksibel untuk import data revenue bulanan (Real + Target)</div>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('revenue.template') }}" class="template-btn template-download">
                            <i class="fas fa-download"></i> Template
                        </a>
                        <a href="{{ route('revenue.export') }}" class="template-btn template-export">
                            <i class="fas fa-file-export"></i> Export
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🔥 FIXED: Enhanced Raw Data RLEGS dengan PERFECT MOBILE-RESPONSIVE DESIGN -->
        <div class="dashboard-card">
            <div class="card-header">
                <div>
                    <h5 class="card-title">Raw Data RLEGS Telkom</h5>
                    <p class="text-muted small mb-0">Data revenue lengkap Account Manager Telkom</p>
                </div>
                <div class="d-flex align-items-center filter-toggle-container">
                    <!-- Enhanced Search Form -->
                    <form action="{{ route('revenue.data') }}" method="GET" class="search-form" id="global-search-form">
                        <div class="input-group">
                            <input class="form-control" type="search" name="search" id="globalSearch"
                                   placeholder="Cari data..." autocomplete="off" value="{{ request('search') }}">
                            <button class="btn btn-primary px-3 py-1" type="submit" id="searchButton">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <!-- Search Results Overlay -->
                        <div id="searchResultsContainer" class="search-results-overlay">
                            <div class="search-results-content">
                                <div class="search-summary p-3">
                                    <p class="mb-0">Hasil pencarian untuk "<span id="search-term-display" class="fw-bold"></span>"</p>
                                </div>
                                <div id="search-results-loading" class="search-loading p-3 text-center">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p class="mt-2 mb-0">Sedang mencari...</p>
                                </div>
                                <div id="search-results-content" style="display:none;">
                                    <div class="p-3">
                                        <p class="mb-2 fw-bold">Ditemukan:</p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge bg-primary" id="total-am-count">AM: 0</span>
                                            <span class="badge bg-info" id="total-cc-count">CC: 0</span>
                                            <span class="badge bg-success" id="total-rev-count">Revenue: 0</span>
                                        </div>
                                        <p class="mt-2 mb-0 small text-muted">Hasil telah ditampilkan pada tab terkait</p>
                                    </div>
                                </div>
                                <div id="search-no-results" style="display:none;" class="p-3">
                                    <div class="text-center py-3">
                                        <i class="fas fa-search fa-2x mb-2 text-muted"></i>
                                        <p class="mb-0">Tidak ada hasil yang ditemukan</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <button class="btn btn-light" id="filterToggle" style="height: 38px;">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>

            <!-- 🆕 CRITICAL: Bulk Delete All Section - Above Bulk Actions Toolbar -->
            <div class="bulk-delete-all-section" style="display: none;" id="bulk-delete-all-section">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1 text-danger fw-bold">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Hapus Massal Keseluruhan Data
                        </h6>
                        <p class="mb-0 small">Menghapus SEMUA data pada tab aktif (dengan filter jika ada)</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn-bulk-delete-all" id="bulk-delete-all-btn">
                            <i class="fas fa-trash-alt me-1"></i> Hapus Semua Data
                        </button>
                        <button class="btn btn-light" id="hide-bulk-delete-all">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- FIXED: Bulk Actions Toolbar Outside Table -->
            <div class="bulk-actions-toolbar" id="bulk-actions-toolbar">
                <div class="selection-info">
                    <i class="fas fa-check-square me-2"></i>
                    <span id="selected-count">0</span> item terpilih
                </div>
                <div class="bulk-action-buttons">
                    <button class="btn-bulk btn-bulk-danger" id="bulk-delete-btn">
                        <i class="fas fa-trash me-1"></i> Hapus Terpilih
                    </button>
                    <button class="btn-bulk btn-bulk-light" id="clear-selection-btn">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button class="btn-bulk btn-bulk-light" id="show-bulk-delete-all">
                        <i class="fas fa-trash-alt me-1"></i> Hapus Massal
                    </button>
                </div>
            </div>

            <!-- Filter Area -->
            <div class="filter-area" id="filterArea" style="display:none;">
                <form action="{{ route('revenue.data') }}" method="GET" id="filter-form">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label small">Witel</label>
                            <select name="witel" class="form-control">
                                <option value="">Semua Witel</option>
                                @foreach ($witels as $witel)
                                    <option value="{{ $witel->id }}" {{ request('witel') == $witel->id ? 'selected' : '' }}>
                                        {{ $witel->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label small">Regional</label>
                            <select name="regional" class="form-control">
                                <option value="">Semua Regional</option>
                                @foreach ($regionals as $regional)
                                    <option value="{{ $regional->id }}" {{ request('regional') == $regional->id ? 'selected' : '' }}>
                                        {{ $regional->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label small">Divisi</label>
                            <select name="divisi" class="form-control">
                                <option value="">Semua Divisi</option>
                                @if(isset($divisis) && $divisis->count() > 0)
                                    @foreach ($divisis as $div)
                                        <option value="{{ $div->id }}" {{ request('divisi') == $div->id ? 'selected' : '' }}>
                                            {{ $div->nama }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label small">Bulan</label>
                            <select name="month" class="form-control">
                                <option value="">Semua Bulan</option>
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label small">Tahun</label>
                            <select name="year" class="form-control">
                                <option value="">Semua Tahun</option>
                                @if(isset($yearRange) && !empty($yearRange))
                                    @foreach ($yearRange as $year)
                                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                @else
                                    @for($y = 2020; $y <= 2030; $y++)
                                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                            {{ $y }}
                                        </option>
                                    @endfor
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <a href="{{ route('revenue.data') }}" class="btn btn-light me-2">
                            <i class="fas fa-redo me-1"></i> Reset
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Terapkan Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Enhanced Search/Filter Description -->
            @if (request('search') || request('witel') || request('regional') || request('divisi') || request('month') || request('year'))
                <div class="search-description">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <strong>Menampilkan hasil:</strong>
                            @if (request('search'))
                                Pencarian "<span class="text-primary fw-bold">{{ request('search') }}</span>"
                            @endif

                            @if (request('witel'))
                                @php $witelInfo = $witels->where('id', request('witel'))->first(); @endphp
                                @if (request('search')) dengan @endif
                                Filter Witel: <span class="text-primary fw-bold">{{ $witelInfo ? $witelInfo->nama : '' }}</span>
                            @endif

                            @if (request('regional'))
                                @php $regionalInfo = $regionals->where('id', request('regional'))->first(); @endphp
                                @if (request('search') || request('witel')) dan @endif
                                Regional: <span class="text-primary fw-bold">{{ $regionalInfo ? $regionalInfo->nama : '' }}</span>
                            @endif

                            @if (request('divisi') && isset($divisis))
                                @php $divisiInfo = $divisis->where('id', request('divisi'))->first(); @endphp
                                @if (request('search') || request('witel') || request('regional')) dan @endif
                                Divisi: <span class="text-primary fw-bold">{{ $divisiInfo ? $divisiInfo->nama : '' }}</span>
                            @endif

                            @if (request('month'))
                                @if (request('search') || request('witel') || request('regional') || request('divisi')) dan @endif
                                Bulan: <span class="text-primary fw-bold">{{ date('F', mktime(0, 0, 0, request('month'), 1)) }}</span>
                            @endif

                            @if (request('year'))
                                @if (request('search') || request('witel') || request('regional') || request('divisi') || request('month')) dan @endif
                                Tahun: <span class="text-primary fw-bold">{{ request('year') }}</span>
                            @endif
                        </div>
                        <a href="{{ route('revenue.data') }}" class="btn btn-sm btn-light ms-auto">
                            <i class="fas fa-times me-1"></i> Reset Filter
                        </a>
                    </div>
                </div>
            @endif

            <!-- Enhanced Tab Menu dengan Counter -->
            <div class="tab-menu-container">
                <ul class="tabs">
                    <li class="tab-item active" data-tab="revenueTab">
                        <i class="fas fa-chart-line me-2"></i> Revenue Data
                        <span class="badge bg-light text-dark ms-1" id="revenue-count">{{ $revenues->total() ?? 0 }}</span>
                    </li>
                    <li class="tab-item" data-tab="amTab">
                        <i class="fas fa-user-tie me-2"></i> Account Manager
                        <span class="badge bg-light text-dark ms-1" id="am-count">{{ $accountManagers->total() ?? 0 }}</span>
                    </li>
                    <li class="tab-item" data-tab="ccTab">
                        <i class="fas fa-building me-2"></i> Corporate Customer
                        <span class="badge bg-light text-dark ms-1" id="cc-count">{{ $corporateCustomers->total() ?? 0 }}</span>
                    </li>
                </ul>
            </div>

            <!-- Enhanced Revenue Tab Content -->
            <div id="revenueTab" class="tab-content active">
                @if ($revenues->isEmpty())
                    <!-- Enhanced Empty State -->
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="empty-state-title">Belum Ada Data Revenue</div>
                        <div class="empty-state-description">
                            Saat ini belum ada data revenue yang tersedia. Mulai dengan menambahkan data revenue baru
                            atau import data dari file Excel untuk memulai monitoring performa Account Manager.
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#importRevenueModal" class="empty-state-action">
                            <i class="fas fa-upload"></i>
                            Import Data Revenue
                        </a>
                    </div>
                @else
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table-modern">
                                <thead>
                                    <tr>
                                        <!-- 🚀 PERFECT MOBILE-RESPONSIVE CHECKBOX COLUMN HEADER -->
                                        <th class="table-select-header">
                                            <input type="checkbox" id="select-all-revenue" class="form-check-input">
                                        </th>
                                        <th>Nama AM</th>
                                        <th>Divisi</th>
                                        <th>Nama Customer</th>
                                        <th>Target Revenue</th>
                                        <th>Real Revenue</th>
                                        <th>Achievement</th>
                                        <th>Bulan</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($revenues as $revenue)
                                        @php
                                            $achievement = $revenue->target_revenue > 0
                                                ? round(($revenue->real_revenue / $revenue->target_revenue) * 100, 1)
                                                : 0;

                                            $statusClass = $achievement >= 100
                                                ? 'bg-success-soft'
                                                : ($achievement >= 80 ? 'bg-warning-soft' : 'bg-danger-soft');
                                        @endphp
                                        <tr class="table-row" data-id="{{ $revenue->id }}" data-type="revenue">
                                            <!-- 🚀 PERFECT MOBILE-RESPONSIVE CHECKBOX CELL -->
                                            <td class="table-select-cell">
                                                <input type="checkbox" class="form-check-input row-checkbox"
                                                       name="selected_ids[]" value="{{ $revenue->id }}">
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="{{ asset('img/profile.png') }}" class="am-profile-pic"
                                                        alt="{{ $revenue->accountManager->nama }}">
                                                    <span class="ms-2">{{ $revenue->accountManager->nama }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="divisi-badge">
                                                    {{ $revenue->divisi->nama ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>{{ $revenue->corporateCustomer->nama }}</td>
                                            <td>Rp {{ number_format($revenue->target_revenue, 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format($revenue->real_revenue, 0, ',', '.') }}</td>
                                            <td>
                                                <span class="status-badge {{ $statusClass }}">
                                                    {{ $achievement }}%
                                                </span>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($revenue->bulan . '-01')->format('F Y') }}</td>
                                            <td class="text-center">
                                                <button type="button" class="action-btn edit-btn edit-revenue"
                                                    data-id="{{ $revenue->id }}"
                                                    data-name="{{ $revenue->accountManager->nama }}"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('revenue.destroy', $revenue->id) }}"
                                                    method="POST" style="display:inline;" class="delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn delete-btn" title="Hapus">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Enhanced Pagination -->
                        @if (method_exists($revenues, 'hasPages') && $revenues->hasPages())
                            <div class="pagination-container">
                                <div class="pagination-simple">
                                    <div class="pagination-info">
                                        Menampilkan {{ $revenues->firstItem() ?? 0 }} sampai {{ $revenues->lastItem() ?? 0 }} dari
                                        {{ $revenues->total() ?? 0 }} hasil
                                    </div>

                                    <div class="pagination-controls">
                                        @if ($revenues->onFirstPage())
                                            <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                                        @else
                                            <a href="{{ $revenues->previousPageUrl() }}" class="page-btn">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        @endif

                                        @php
                                            $currentPage = $revenues->currentPage();
                                            $lastPage = $revenues->lastPage();
                                            $range = 2;
                                        @endphp

                                        @if ($currentPage > $range + 1)
                                            <a href="{{ $revenues->url(1) }}" class="page-btn">1</a>
                                            @if ($currentPage > $range + 2)
                                                <span class="page-btn disabled">...</span>
                                            @endif
                                        @endif

                                        @for ($i = max(1, $currentPage - $range); $i <= min($lastPage, $currentPage + $range); $i++)
                                            <a href="{{ $revenues->url($i) }}" class="page-btn {{ $i == $currentPage ? 'active' : '' }}">
                                                {{ $i }}
                                            </a>
                                        @endfor

                                        @if ($currentPage < $lastPage - $range)
                                            @if ($currentPage < $lastPage - $range - 1)
                                                <span class="page-btn disabled">...</span>
                                            @endif
                                            <a href="{{ $revenues->url($lastPage) }}" class="page-btn">{{ $lastPage }}</a>
                                        @endif

                                        @if ($revenues->hasMorePages())
                                            <a href="{{ $revenues->nextPageUrl() }}" class="page-btn">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        @else
                                            <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                                        @endif
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <label for="perPage" class="small">Baris:</label>
                                        <select id="perPage" class="per-page-select" onchange="changePerPage(this.value)">
                                            <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
                                            <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                                            <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                                            <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                                            <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Enhanced Account Manager Tab Content -->
            <div id="amTab" class="tab-content">
                @if ($accountManagers->isEmpty())
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="empty-state-title">Belum Ada Data Account Manager</div>
                        <div class="empty-state-description">
                            Saat ini belum ada data Account Manager yang tersedia. Tambahkan Account Manager baru
                            atau import data untuk memulai pengelolaan tim sales.
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#addAccountManagerModal" class="empty-state-action">
                            <i class="fas fa-user-plus"></i>
                            Tambah Account Manager
                        </a>
                    </div>
                @else
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table-modern">
                                <thead>
                                    <tr>
                                        <th class="table-select-header">
                                            <input type="checkbox" id="select-all-am" class="form-check-input">
                                        </th>
                                        <th>Nama</th>
                                        <th>NIK</th>
                                        <th>Witel</th>
                                        <th>Regional</th>
                                        <th>Divisi</th>
                                        <th>Status User</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($accountManagers as $am)
                                        <tr class="table-row" data-id="{{ $am->id }}" data-type="account-manager">
                                            <td class="table-select-cell">
                                                <input type="checkbox" class="form-check-input row-checkbox"
                                                       name="selected_ids[]" value="{{ $am->id }}">
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="{{ asset('img/profile.png') }}" class="am-profile-pic" alt="{{ $am->nama }}">
                                                    <span class="ms-2">{{ $am->nama }}</span>
                                                </div>
                                            </td>
                                            <td>{{ $am->nik }}</td>
                                            <td>{{ $am->witel->nama }}</td>
                                            <td>{{ $am->regional->nama ?? 'N/A' }}</td>
                                            <td>
                                                @if ($am->divisis->count() > 0)
                                                    @foreach ($am->divisis as $divisi)
                                                        <span class="divisi-badge">{{ $divisi->nama }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($am->user)
                                                    <span class="user-status-badge registered">
                                                        <i class="fas fa-user-check"></i> Terdaftar
                                                    </span>
                                                @else
                                                    <span class="user-status-badge not-registered">
                                                        <i class="fas fa-user-times"></i> Belum Terdaftar
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="action-btn edit-btn edit-account-manager"
                                                    data-id="{{ $am->id }}"
                                                    data-name="{{ $am->nama }}"
                                                    data-has-user="{{ $am->user ? 'true' : 'false' }}"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                @if($am->user)
                                                    <button type="button" class="action-btn warning-btn change-password-btn"
                                                        data-id="{{ $am->id }}"
                                                        data-name="{{ $am->nama }}"
                                                        title="Ubah Password">
                                                        <i class="fas fa-key"></i>
                                                    </button>
                                                @endif
                                                <form action="{{ route('account-manager.destroy', $am->id) }}"
                                                    method="POST" style="display:inline;" class="delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn delete-btn" title="Hapus">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- 🔥 FIXED: Complete Pagination for Account Manager -->
                        @if (isset($accountManagers) && method_exists($accountManagers, 'hasPages') && $accountManagers->hasPages())
                            <div class="pagination-container">
                                <div class="pagination-simple">
                                    <div class="pagination-info">
                                        Menampilkan {{ $accountManagers->firstItem() ?? 0 }} sampai
                                        {{ $accountManagers->lastItem() ?? 0 }} dari {{ $accountManagers->total() ?? 0 }} hasil
                                    </div>

                                    <!-- 🚀 ADDED: Missing pagination controls -->
                                    <div class="pagination-controls">
                                        @if ($accountManagers->onFirstPage())
                                            <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                                        @else
                                            <a href="{{ $accountManagers->previousPageUrl() }}" class="page-btn">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        @endif

                                        @php
                                            $currentPageAM = $accountManagers->currentPage();
                                            $lastPageAM = $accountManagers->lastPage();
                                            $rangeAM = 2;
                                        @endphp

                                        @if ($currentPageAM > $rangeAM + 1)
                                            <a href="{{ $accountManagers->url(1) }}" class="page-btn">1</a>
                                            @if ($currentPageAM > $rangeAM + 2)
                                                <span class="page-btn disabled">...</span>
                                            @endif
                                        @endif

                                        @for ($i = max(1, $currentPageAM - $rangeAM); $i <= min($lastPageAM, $currentPageAM + $rangeAM); $i++)
                                            <a href="{{ $accountManagers->url($i) }}" class="page-btn {{ $i == $currentPageAM ? 'active' : '' }}">
                                                {{ $i }}
                                            </a>
                                        @endfor

                                        @if ($currentPageAM < $lastPageAM - $rangeAM)
                                            @if ($currentPageAM < $lastPageAM - $rangeAM - 1)
                                                <span class="page-btn disabled">...</span>
                                            @endif
                                            <a href="{{ $accountManagers->url($lastPageAM) }}" class="page-btn">{{ $lastPageAM }}</a>
                                        @endif

                                        @if ($accountManagers->hasMorePages())
                                            <a href="{{ $accountManagers->nextPageUrl() }}" class="page-btn">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        @else
                                            <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                                        @endif
                                    </div>

                                    <!-- 🚀 ADDED: Missing per page selector -->
                                    <div class="d-flex align-items-center gap-2">
                                        <label for="perPageAM" class="small">Baris:</label>
                                        <select id="perPageAM" class="per-page-select" onchange="changePerPage(this.value)">
                                            <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
                                            <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                                            <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                                            <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                                            <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Enhanced Corporate Customer Tab Content -->
            <div id="ccTab" class="tab-content">
                @if ($corporateCustomers->isEmpty())
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="empty-state-title">Belum Ada Data Corporate Customer</div>
                        <div class="empty-state-description">
                            Saat ini belum ada data Corporate Customer yang tersedia. Tambahkan Corporate Customer baru
                            atau import data untuk memulai manajemen pelanggan korporat.
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#addCorporateCustomerModal" class="empty-state-action">
                            <i class="fas fa-building"></i>
                            Tambah Corporate Customer
                        </a>
                    </div>
                @else
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table-modern">
                                <thead>
                                    <tr>
                                        <th class="table-select-header">
                                            <input type="checkbox" id="select-all-cc" class="form-check-input">
                                        </th>
                                        <th>Nama</th>
                                        <th>NIPNAS</th>
                                        <th>Tanggal Dibuat</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($corporateCustomers as $cc)
                                        <tr class="table-row" data-id="{{ $cc->id }}" data-type="corporate-customer">
                                            <td class="table-select-cell">
                                                <input type="checkbox" class="form-check-input row-checkbox"
                                                       name="selected_ids[]" value="{{ $cc->id }}">
                                            </td>
                                            <td>{{ $cc->nama }}</td>
                                            <td>{{ $cc->nipnas }}</td>
                                            <td>{{ $cc->created_at ? $cc->created_at->format('d M Y') : '-' }}</td>
                                            <td class="text-center">
                                                <button type="button" class="action-btn edit-btn edit-corporate-customer"
                                                    data-id="{{ $cc->id }}"
                                                    data-name="{{ $cc->nama }}"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('corporate-customer.destroy', $cc->id) }}"
                                                    method="POST" style="display:inline;" class="delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn delete-btn" title="Hapus">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Enhanced Pagination for Corporate Customer -->
                        @if (isset($corporateCustomers) && method_exists($corporateCustomers, 'hasPages') && $corporateCustomers->hasPages())
                            <div class="pagination-container">
                                <div class="pagination-simple">
                                    <div class="pagination-info">
                                        Menampilkan {{ $corporateCustomers->firstItem() ?? 0 }} sampai
                                        {{ $corporateCustomers->lastItem() ?? 0 }} dari {{ $corporateCustomers->total() ?? 0 }} hasil
                                    </div>
                                    <div class="pagination-controls">
                                        @if ($corporateCustomers->onFirstPage())
                                            <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                                        @else
                                            <a href="{{ $corporateCustomers->previousPageUrl() }}" class="page-btn">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        @endif

                                        @php
                                            $currentPageCC = $corporateCustomers->currentPage();
                                            $lastPageCC = $corporateCustomers->lastPage();
                                            $rangeCC = 2;
                                        @endphp

                                        @if ($currentPageCC > $rangeCC + 1)
                                            <a href="{{ $corporateCustomers->url(1) }}" class="page-btn">1</a>
                                            @if ($currentPageCC > $rangeCC + 2)
                                                <span class="page-btn disabled">...</span>
                                            @endif
                                        @endif

                                        @for ($i = max(1, $currentPageCC - $rangeCC); $i <= min($lastPageCC, $currentPageCC + $rangeCC); $i++)
                                            <a href="{{ $corporateCustomers->url($i) }}" class="page-btn {{ $i == $currentPageCC ? 'active' : '' }}">
                                                {{ $i }}
                                            </a>
                                        @endfor

                                        @if ($currentPageCC < $lastPageCC - $rangeCC)
                                            @if ($currentPageCC < $lastPageCC - $rangeCC - 1)
                                                <span class="page-btn disabled">...</span>
                                            @endif
                                            <a href="{{ $corporateCustomers->url($lastPageCC) }}" class="page-btn">{{ $lastPageCC }}</a>
                                        @endif

                                        @if ($corporateCustomers->hasMorePages())
                                            <a href="{{ $corporateCustomers->nextPageUrl() }}" class="page-btn">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        @else
                                            <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                                        @endif
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <label for="perPageCC" class="small">Baris:</label>
                                        <select id="perPageCC" class="per-page-select" onchange="changePerPage(this.value)">
                                            <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
                                            <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                                            <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                                            <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                                            <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- ✅ ALL MODALS SECTION -->

        <!-- ✅ FIXED: Import Result Modal dengan Enhanced Display -->
        <div class="modal fade import-result-modal" id="ImportResultModal" tabindex="-1" aria-labelledby="ImportResultModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ImportResultModalLabel">
                            <i class="fas fa-chart-bar me-2"></i>
                            <span id="import-result-title">Hasil Import Data</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Loading State dengan Progress -->
                        <div id="import-loading" class="import-loading">
                            <div class="d-flex justify-content-center mb-4">
                                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <h5 class="text-center text-primary mb-3">Memproses Import Data</h5>
                            <p class="text-center text-muted mb-4">Mohon tunggu, sistem sedang memproses file Anda...</p>

                            <!-- Progress Steps -->
                            <div class="progress-container">
                                <div class="progress-step active" id="step-upload">
                                    <div class="progress-icon">1</div>
                                    <div>
                                        <strong>Upload File</strong>
                                        <small class="d-block text-muted">File berhasil diunggah</small>
                                    </div>
                                </div>
                                <div class="progress-step active" id="step-validate">
                                    <div class="progress-icon">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </div>
                                    <div>
                                        <strong>Validasi Data</strong>
                                        <small class="d-block text-muted">Memeriksa format dan konsistensi data</small>
                                    </div>
                                </div>
                                <div class="progress-step" id="step-process">
                                    <div class="progress-icon">3</div>
                                    <div>
                                        <strong>Proses Import</strong>
                                        <small class="d-block text-muted">Menyimpan data ke database</small>
                                    </div>
                                </div>
                                <div class="progress-step" id="step-complete">
                                    <div class="progress-icon">4</div>
                                    <div>
                                        <strong>Selesai</strong>
                                        <small class="d-block text-muted">Import data berhasil diselesaikan</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Success State akan diisi via JavaScript -->
                        <div id="import-success" class="import-result" style="display: none;">
                            <!-- Content akan diisi via JavaScript -->
                        </div>

                        <!-- Error State -->
                        <div id="import-error" class="import-result text-center" style="display: none;">
                            <div class="mb-4">
                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                <h5 class="text-danger">Import Gagal</h5>
                                <p id="import-error-message" class="text-muted"></p>
                            </div>
                            <div class="empty-state-action">
                                <button type="button" class="btn btn-primary" id="retry-import">
                                    <i class="fas fa-sync-alt me-1"></i> Coba Lagi
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            Tutup
                        </button>
                        <button type="button" class="btn btn-warning" id="download-error-log" style="display: none;">
                            <i class="fas fa-download me-1"></i> Unduh Log Error
                        </button>
                        <button type="button" class="btn btn-primary" id="refresh-page" style="display: none;">
                            <i class="fas fa-sync-alt me-1"></i> Muat Ulang Halaman
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ FIXED: Bulk Delete Confirmation Modal -->
        <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkDeleteModalLabel">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Konfirmasi Hapus Data
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Peringatan!
                            </h6>
                            <p class="mb-2">
                                Anda akan menghapus <strong id="bulk-delete-count">0</strong> item yang terpilih.
                                Tindakan ini tidak dapat dibatalkan.
                            </p>
                            <hr>
                            <p class="mb-0 small">
                                <strong>Pastikan Anda yakin</strong> sebelum melanjutkan proses penghapusan.
                            </p>
                        </div>

                        <!-- Selected Items Preview -->
                        <div class="selected-items-preview">
                            <h6>Item yang akan dihapus:</h6>
                            <div id="selected-items-list" class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                <!-- Items akan diisi oleh JavaScript -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Batal
                        </button>
                        <button type="button" class="btn btn-danger" id="confirm-bulk-delete">
                            <i class="fas fa-trash me-1"></i> Ya, Hapus Semua
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ FIXED: Change Password Modal -->
        <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changePasswordModalLabel">
                            <i class="fas fa-key me-2"></i>
                            Ubah Password Account Manager
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Loading overlay -->
                        <div class="modal-loading-overlay" id="change-password-loading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Memproses perubahan password...</p>
                        </div>

                        <form id="changePasswordForm" data-form-reset="true">
                            <input type="hidden" id="change_password_am_id" name="am_id">

                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Informasi Account Manager
                                </h6>
                                <p class="mb-2">
                                    <strong>Nama:</strong> <span id="change_password_am_name">-</span><br>
                                    <strong>Email:</strong> <span id="change_password_am_email">-</span>
                                </p>
                                <hr>
                                <p class="mb-0 small">
                                    Password baru akan berlaku segera setelah disimpan.
                                </p>
                            </div>

                            <div class="form-group">
                                <label for="new_password" class="form-label">Password Baru</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="new_password" id="new_password"
                                           placeholder="Masukkan password baru" required minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Password minimal 8 karakter</div>
                                <div class="validation-feedback" id="password_validation"></div>
                            </div>

                            <div class="form-group">
                                <label for="new_password_confirmation" class="form-label">Konfirmasi Password</label>
                                <input type="password" class="form-control" name="new_password_confirmation"
                                       id="new_password_confirmation" placeholder="Ulangi password baru" required>
                                <div class="validation-feedback" id="password_confirm_validation"></div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Simpan Password Baru
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ FIXED: Modal Tambah Account Manager -->
        <div class="modal fade" id="addAccountManagerModal" tabindex="-1" aria-labelledby="addAccountManagerModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAccountManagerModalLabel">
                            <i class="fas fa-user-plus me-2"></i>
                            Tambah Account Manager Baru
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="tab-menu-container">
                            <ul class="tabs">
                                <li class="tab-item active" data-tab="formTabAM">
                                    <i class="fas fa-edit me-2"></i> Form Manual
                                </li>
                                <li class="tab-item" data-tab="importTabAM">
                                    <i class="fas fa-file-import me-2"></i> Import Excel
                                </li>
                            </ul>
                        </div>

                        <!-- Form Manual Tab -->
                        <div id="formTabAM" class="tab-content active">
                            <form id="amForm" action="{{ route('account-manager.store') }}" method="POST" data-form-reset="true">
                                @csrf
                                <div class="form-group">
                                    <label for="nama" class="form-label">Nama Account Manager</label>
                                    <input type="text" id="nama" name="nama" class="form-control"
                                        placeholder="Masukkan Nama Account Manager" required>
                                    <div class="validation-feedback" id="nama_am_validation"></div>
                                </div>
                                <div class="form-group">
                                    <label for="nik" class="form-label">Nomor Induk Karyawan</label>
                                    <input type="text" id="nik" name="nik" class="form-control"
                                        placeholder="Masukkan 4-10 digit Nomor Induk Karyawan" pattern="^\d{4,10}$" required>
                                    <div class="validation-feedback" id="nik_validation"></div>
                                    <div class="validation-spinner" id="nik_spinner">
                                        <i class="fas fa-spinner fa-spin"></i> Memvalidasi NIK...
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="witel_id" class="form-label">Witel</label>
                                    <select name="witel_id" id="witel_id" class="form-control" required>
                                        <option value="">Pilih Witel</option>
                                        @if (isset($witels) && (is_object($witels) || is_array($witels)))
                                            @foreach ($witels as $witel)
                                                @if (is_object($witel) && isset($witel->id) && isset($witel->nama))
                                                    <option value="{{ $witel->id }}">{{ $witel->nama }}</option>
                                                @endif
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="regional_id" class="form-label">Regional</label>
                                    <select name="regional_id" id="regional_id" class="form-control" required>
                                        <option value="">Pilih Regional</option>
                                        @if (isset($regionals) && (is_object($regionals) || is_array($regionals)))
                                            @foreach ($regionals as $regional)
                                                @if (is_object($regional) && isset($regional->id) && isset($regional->nama))
                                                    <option value="{{ $regional->id }}">{{ $regional->nama }}</option>
                                                @endif
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Divisi</label>
                                    <div class="divisi-btn-group">
                                        @if (isset($divisis) && $divisis->count() > 0)
                                            @foreach ($divisis as $div)
                                                <button type="button" class="divisi-btn" data-divisi-id="{{ $div->id }}">
                                                    {{ $div->nama }}
                                                </button>
                                            @endforeach
                                        @endif
                                    </div>
                                    <input type="hidden" name="divisi_ids" id="divisi_ids" value="">
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Simpan
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Import Excel Tab -->
                        <div id="importTabAM" class="tab-content">
                            <form id="amImportForm" action="{{ route('account-manager.import') }}" method="POST" enctype="multipart/form-data" data-form-reset="true">
                                @csrf
                                <div class="form-group">
                                    <label for="file_upload_am" class="form-label">Unggah File Excel/CSV</label>
                                    <input type="file" name="file" id="file_upload_am" accept=".xlsx, .xls, .csv" required class="form-control">
                                </div>
                                <div class="alert alert-info mt-3">
                                    <h6 class="alert-heading mb-2">
                                        <i class="fas fa-info-circle me-2"></i> Format Data
                                    </h6>
                                    <p class="mb-2 small">File harus memiliki kolom-kolom berikut:</p>
                                    <ul class="small mb-0">
                                        <li><strong>NIK</strong>: Nomor Induk Karyawan (4-10 digit)</li>
                                        <li><strong>NAMA AM</strong>: Nama Account Manager</li>
                                        <li><strong>WITEL HO</strong>: Nama Witel</li>
                                        <li><strong>REGIONAL</strong>: Nama Regional (TREG 1-7)</li>
                                        <li><strong>DIVISI</strong>: Nama Divisi</li>
                                    </ul>
                                </div>
                                <div class="mt-3 d-flex">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i> Unggah Data
                                    </button>
                                    <a href="{{ route('account-manager.template') }}" class="btn btn-light ms-2">
                                        <i class="fas fa-download me-2"></i> Unduh Template
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ FIXED: Modal Tambah Corporate Customer -->
        <div class="modal fade" id="addCorporateCustomerModal" tabindex="-1" aria-labelledby="addCorporateCustomerModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCorporateCustomerModalLabel">
                            <i class="fas fa-building me-2"></i>
                            Tambah Corporate Customer Baru
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="tab-menu-container">
                            <ul class="tabs">
                                <li class="tab-item active" data-tab="formTabCC">
                                    <i class="fas fa-edit me-2"></i> Form Manual
                                </li>
                                <li class="tab-item" data-tab="importTabCC">
                                    <i class="fas fa-file-import me-2"></i> Import Excel
                                </li>
                            </ul>
                        </div>

                        <!-- Form Manual Tab -->
                        <div id="formTabCC" class="tab-content active">
                            <form id="ccForm" action="{{ route('corporate-customer.store') }}" method="POST" data-form-reset="true">
                                @csrf
                                <div class="form-group">
                                    <label for="nama_cc" class="form-label">Nama Corporate Customer</label>
                                    <input type="text" id="nama_cc" name="nama" class="form-control"
                                        placeholder="Masukkan Nama Corporate Customer" required>
                                    <div class="validation-feedback" id="nama_cc_validation"></div>
                                </div>
                                <div class="form-group">
                                    <label for="nipnas" class="form-label">NIPNAS</label>
                                    <input type="text" id="nipnas" name="nipnas" class="form-control"
                                        placeholder="Masukkan NIPNAS (3-20 digit)" pattern="^\d{3,20}$" required>
                                    <div class="validation-feedback" id="nipnas_validation"></div>
                                    <div class="validation-spinner" id="nipnas_spinner">
                                        <i class="fas fa-spinner fa-spin"></i> Memvalidasi NIPNAS...
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Simpan
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Import Excel Tab -->
                        <div id="importTabCC" class="tab-content">
                            <form id="ccImportForm" action="{{ route('corporate-customer.import') }}" method="POST" enctype="multipart/form-data" data-form-reset="true">
                                @csrf
                                <div class="form-group">
                                    <label for="file_upload_cc" class="form-label">Unggah File Excel/CSV</label>
                                    <input type="file" name="file" id="file_upload_cc" accept=".xlsx, .xls, .csv" required class="form-control">
                                </div>
                                <div class="alert alert-info mt-3">
                                    <h6 class="alert-heading mb-2">
                                        <i class="fas fa-info-circle me-2"></i> Format Data
                                    </h6>
                                    <p class="mb-2 small">File harus memiliki kolom-kolom berikut:</p>
                                    <ul class="small mb-0">
                                        <li><strong>NIPNAS</strong>: Nomor identifikasi (3-20 digit)</li>
                                        <li><strong>STANDARD NAME</strong>: Nama Corporate Customer</li>
                                    </ul>
                                </div>
                                <div class="mt-3 d-flex">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i> Unggah Data
                                    </button>
                                    <a href="{{ route('corporate-customer.template') }}" class="btn btn-light ms-2">
                                        <i class="fas fa-download me-2"></i> Unduh Template
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ FIXED: Import Revenue Modal -->
        <div class="modal fade" id="importRevenueModal" tabindex="-1" aria-labelledby="importRevenueModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="importRevenueModalLabel">
                            <i class="fas fa-upload me-2"></i>
                            Import Data Revenue
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Loading overlay -->
                        <div class="modal-loading-overlay" id="import-loading-overlay">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Memproses import data...</p>
                        </div>

                        <form id="importRevenueForm" action="{{ route('revenue.import') }}" method="POST" enctype="multipart/form-data" data-form-reset="true">
                            @csrf
                            <div class="form-group">
                                <label for="file_upload_revenue" class="form-label">Unggah File Excel/CSV</label>
                                <input type="file" name="file" id="file_upload_revenue" accept=".xlsx, .xls, .csv" required class="form-control">
                                <div class="form-text">Maksimal ukuran file: 10MB</div>
                            </div>

                            <div class="form-group">
                                <label for="import_year" class="form-label">Tahun Data</label>
                                <select name="year" id="import_year" class="form-control">
                                    <option value="{{ date('Y') }}" selected>{{ date('Y') }}</option>
                                    @for($year = date('Y') - 2; $year <= date('Y') + 1; $year++)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="overwrite_mode" class="form-label">Mode Overwrite</label>
                                <select name="overwrite_mode" id="overwrite_mode" class="form-control">
                                    <option value="update">Update - Perbarui data yang sudah ada</option>
                                    <option value="skip">Skip - Lewati data yang sudah ada</option>
                                    <option value="ask">Ask - Konfirmasi setiap perubahan</option>
                                </select>
                                <div class="form-text">Pilih bagaimana menangani data yang sudah ada</div>
                            </div>

                            <div class="alert alert-info">
                                <h6 class="alert-heading mb-2">
                                    <i class="fas fa-info-circle me-2"></i> Format Data Revenue
                                </h6>
                                <p class="mb-2 small">File harus memiliki kolom-kolom berikut:</p>
                                <ul class="small mb-2">
                                    <li><strong>NAMA AM</strong>: Nama Account Manager (harus sudah terdaftar)</li>
                                    <li><strong>NIK</strong>: NIK Account Manager (opsional)</li>
                                    <li><strong>STANDARD NAME</strong>: Nama Corporate Customer (harus sudah terdaftar)</li>
                                    <li><strong>NIPNAS</strong>: NIPNAS Corporate Customer (opsional)</li>
                                    <li><strong>DIVISI</strong>: Nama Divisi (opsional)</li>
                                    <li><strong>Target_[Bulan]</strong>: Target revenue bulanan (Jan, Feb, Mar, dll)</li>
                                    <li><strong>Real_[Bulan]</strong>: Real revenue bulanan (Jan, Feb, Mar, dll)</li>
                                </ul>
                                <hr>
                                <p class="mb-0 small text-muted">
                                    💡 <strong>Tips:</strong> Download template untuk melihat format yang benar.
                                </p>
                            </div>

                            <div class="mt-3 d-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-2"></i> Import Data
                                </button>
                                <a href="{{ route('revenue.template') }}" class="btn btn-light ms-2">
                                    <i class="fas fa-download me-2"></i> Unduh Template
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ FIXED: Edit Revenue Modal -->
        <div class="modal fade" id="editRevenueModal" tabindex="-1" aria-labelledby="editRevenueModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editRevenueModalLabel">
                            <i class="fas fa-edit me-2"></i>
                            Edit Data Revenue
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Loading overlay -->
                        <div class="modal-loading-overlay" id="edit-loading-overlay">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Memuat data revenue...</p>
                        </div>

                        <form id="editRevenueForm" method="POST" data-form-reset="true">
                            @csrf
                            @method('PUT')
                            <input type="hidden" id="edit_revenue_id" name="revenue_id">

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit_account_manager" class="form-label">Account Manager</label>
                                    <div class="position-relative">
                                        <!-- 🔥 FIXED: Account Manager field disabled pada edit mode -->
                                        <input type="text" id="edit_account_manager" class="form-control"
                                            placeholder="Account Manager..." disabled required>
                                        <input type="hidden" name="account_manager_id" id="edit_account_manager_id">
                                        <div id="edit_account_manager_suggestions" class="suggestions-container"></div>
                                        <div class="validation-feedback" id="edit_am_validation"></div>
                                    </div>
                                    <small class="text-muted">Account Manager tidak dapat diubah saat edit</small>
                                </div>

                                <div class="form-group">
                                    <label for="edit_divisi_id" class="form-label">Divisi</label>
                                    <!-- 🔥 FIXED: Divisi field disabled pada edit mode -->
                                    <select id="edit_divisi_id" name="divisi_id" class="form-control" required disabled>
                                        <option value="">Pilih Divisi</option>
                                    </select>
                                    <small class="text-muted">Divisi tidak dapat diubah saat edit</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_corporate_customer" class="form-label">Corporate Customer</label>
                                <div class="position-relative">
                                    <!-- 🔥 FIXED: Corporate Customer field disabled pada edit mode -->
                                    <input type="text" id="edit_corporate_customer" class="form-control"
                                        placeholder="Corporate Customer..." disabled required>
                                    <input type="hidden" name="corporate_customer_id" id="edit_corporate_customer_id">
                                    <div id="edit_corporate_customer_suggestions" class="suggestions-container"></div>
                                    <div class="validation-feedback" id="edit_cc_validation"></div>
                                </div>
                                <small class="text-muted">Corporate Customer tidak dapat diubah saat edit</small>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit_target_revenue" class="form-label">Target Revenue</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <!-- 🔥 FIXED: Remove min="0" untuk allow negative values -->
                                        <input type="number" class="form-control" name="target_revenue" id="edit_target_revenue"
                                            placeholder="Masukkan target revenue" required>
                                    </div>
                                    <small class="text-muted">Nilai negatif diperbolehkan</small>
                                </div>
                                <div class="form-group">
                                    <label for="edit_real_revenue" class="form-label">Real Revenue</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <!-- 🔥 FIXED: Remove min="0" untuk allow negative values -->
                                        <input type="number" class="form-control" name="real_revenue" id="edit_real_revenue"
                                            placeholder="Masukkan real revenue" required>
                                    </div>
                                    <small class="text-muted">Nilai negatif diperbolehkan</small>
                                </div>
                                <div class="form-group">
                                    <label for="edit_bulan" class="form-label">Bulan Capaian</label>
                                    <input type="month" class="form-control" name="bulan" id="edit_bulan" required>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Simpan Perubahan
                                </button>
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    Batal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ FIXED: Edit Account Manager Modal -->
        <div class="modal fade" id="editAccountManagerModal" tabindex="-1" aria-labelledby="editAccountManagerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editAccountManagerModalLabel">
                            <i class="fas fa-user-edit me-2"></i>
                            Edit Account Manager
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Loading overlay -->
                        <div class="modal-loading-overlay" id="edit-am-loading-overlay">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Memuat data Account Manager...</p>
                        </div>

                        <form id="editAccountManagerForm" method="POST" data-form-reset="true">
                            @csrf
                            @method('PUT')
                            <input type="hidden" id="edit_am_id" name="am_id">

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit_am_nama" class="form-label">Nama Account Manager</label>
                                    <input type="text" id="edit_am_nama" name="nama" class="form-control"
                                        placeholder="Masukkan Nama Account Manager" required>
                                    <div class="validation-feedback" id="edit_nama_am_validation"></div>
                                </div>

                                <div class="form-group">
                                    <label for="edit_am_nik" class="form-label">NIK</label>
                                    <input type="text" id="edit_am_nik" name="nik" class="form-control"
                                        placeholder="Masukkan NIK (4-10 digit)" pattern="^\d{4,10}$" required>
                                    <div class="validation-feedback" id="edit_nik_validation"></div>
                                    <div class="validation-spinner" id="edit_nik_spinner">
                                        <i class="fas fa-spinner fa-spin"></i> Memvalidasi NIK...
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit_am_witel_id" class="form-label">Witel</label>
                                    <select name="witel_id" id="edit_am_witel_id" class="form-control" required>
                                        <option value="">Pilih Witel</option>
                                        @if (isset($witels))
                                            @foreach ($witels as $witel)
                                                <option value="{{ $witel->id }}">{{ $witel->nama }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="edit_am_regional_id" class="form-label">Regional</label>
                                    <select name="regional_id" id="edit_am_regional_id" class="form-control" required>
                                        <option value="">Pilih Regional</option>
                                        @if (isset($regionals))
                                            @foreach ($regionals as $regional)
                                                <option value="{{ $regional->id }}">{{ $regional->nama }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Divisi</label>
                                <div class="divisi-btn-group" id="edit-divisi-btn-group">
                                    @if (isset($divisis))
                                        @foreach ($divisis as $div)
                                            <button type="button" class="divisi-btn" data-divisi-id="{{ $div->id }}">
                                                {{ $div->nama }}
                                            </button>
                                        @endforeach
                                    @endif
                                </div>
                                <input type="hidden" name="divisi_ids" id="edit_divisi_ids" value="">
                            </div>

                            <!-- Password Section (conditional) -->
                            <div id="password-section" class="form-group" style="display: none;">
                                <hr>
                                <h6><i class="fas fa-key me-2"></i> Account User Settings</h6>
                                <div class="alert alert-warning">
                                    <small>Account Manager ini memiliki akun user terdaftar. Gunakan tombol "Ubah Password" untuk mengubah password.</small>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Simpan Perubahan
                                </button>
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    Batal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ FIXED: Edit Corporate Customer Modal -->
        <div class="modal fade" id="editCorporateCustomerModal" tabindex="-1" aria-labelledby="editCorporateCustomerModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCorporateCustomerModalLabel">
                            <i class="fas fa-building me-2"></i>
                            Edit Corporate Customer
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Loading overlay -->
                        <div class="modal-loading-overlay" id="edit-cc-loading-overlay">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Memuat data Corporate Customer...</p>
                        </div>

                        <form id="editCorporateCustomerForm" method="POST" data-form-reset="true">
                            @csrf
                            @method('PUT')
                            <input type="hidden" id="edit_cc_id" name="cc_id">

                            <div class="form-group">
                                <label for="edit_cc_nama" class="form-label">Nama Corporate Customer</label>
                                <input type="text" id="edit_cc_nama" name="nama" class="form-control"
                                    placeholder="Masukkan Nama Corporate Customer" required>
                                <div class="validation-feedback" id="edit_nama_cc_validation"></div>
                            </div>

                            <div class="form-group">
                                <label for="edit_cc_nipnas" class="form-label">NIPNAS</label>
                                <input type="text" id="edit_cc_nipnas" name="nipnas" class="form-control"
                                    placeholder="Masukkan NIPNAS (3-20 digit)" pattern="^\d{3,20}$" required>
                                <div class="validation-feedback" id="edit_nipnas_validation"></div>
                                <div class="validation-spinner" id="edit_nipnas_spinner">
                                    <i class="fas fa-spinner fa-spin"></i> Memvalidasi NIPNAS...
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Simpan Perubahan
                                </button>
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    Batal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ FIXED: Loading Components -->
        <div id="loading-overlay" class="loading-overlay" style="display: none;">
            <div class="loading-content">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 mb-0">Memproses permintaan...</p>
            </div>
        </div>

        <!-- ✅ NOTIFICATION CONTAINER -->
        <div id="notification-container" class="notification-persistent" style="display: none;">
            <div class="content">
                <div class="title" id="notification-title">Notification</div>
                <div class="message" id="notification-message">Message</div>
            </div>
            <button type="button" class="close-btn" id="notification-close">
                <i class="fas fa-times"></i>
            </button>
        </div>

    </div>

    <!-- ✅ CRITICAL: JavaScript Configuration Variables -->
    <script>
        // ✅ FIXED: Complete Global configuration dan URLs untuk revenue.js
        window.revenueConfig = {
            baseUrl: "{{ url('/') }}",
            csrfToken: "{{ csrf_token() }}",
            routes: {
                // Revenue routes
                revenueStore: "{{ route('revenue.store') }}",
                revenueSearch: "{{ route('revenue.search') }}",
                revenueUpdate: "{{ route('revenue.update', ':id') }}",
                revenueDestroy: "{{ route('revenue.destroy', ':id') }}",
                revenueEdit: "{{ url('/api/revenue') }}/:id/edit",
                revenueBulkDelete: "{{ url('/revenue/bulk-delete') }}",
                revenueBulkDeleteAll: "{{ url('/revenue/bulk-delete-all') }}",
                revenueImport: "{{ route('revenue.import') }}",
                revenueExport: "{{ route('revenue.export') }}",
                revenueTemplate: "{{ route('revenue.template') }}",

                // Account Manager routes
                accountManagerStore: "{{ route('account-manager.store') }}",
                accountManagerSearch: "{{ route('account-manager.search') }}",
                accountManagerUpdate: "{{ route('account-manager.update', ':id') }}",
                accountManagerEdit: "{{ url('/api/account-manager') }}/:id/edit",
                accountManagerDivisions: "{{ url('/api/account-manager') }}/:id/divisi",
                accountManagerValidateNik: "{{ route('account-manager.validate-nik') }}",
                accountManagerChangePassword: "{{ url('/account-manager') }}/:id/change-password",
                accountManagerUserStatus: "{{ url('/api/account-manager') }}/:id/user-status",
                accountManagerBulkDelete: "{{ url('/account-manager/bulk-delete') }}",
                accountManagerBulkDeleteAll: "{{ url('/account-manager/bulk-delete-all') }}",
                accountManagerImport: "{{ route('account-manager.import') }}",
                accountManagerExport: "{{ route('account-manager.export') }}",
                accountManagerTemplate: "{{ route('account-manager.template') }}",

                // Corporate Customer routes
                corporateCustomerStore: "{{ route('corporate-customer.store') }}",
                corporateCustomerSearch: "{{ route('corporate-customer.search') }}",
                corporateCustomerUpdate: "{{ route('corporate-customer.update', ':id') }}",
                corporateCustomerEdit: "{{ url('/api/corporate-customer') }}/:id/edit",
                corporateCustomerValidateNipnas: "{{ route('corporate-customer.validate-nipnas') }}",
                corporateCustomerBulkDelete: "{{ url('/corporate-customer/bulk-delete') }}",
                corporateCustomerBulkDeleteAll: "{{ url('/corporate-customer/bulk-delete-all') }}",
                corporateCustomerImport: "{{ route('corporate-customer.import') }}",
                corporateCustomerExport: "{{ route('corporate-customer.export') }}",
                corporateCustomerTemplate: "{{ route('corporate-customer.template') }}",

                // Statistics routes
                revenueStats: "{{ url('/api/revenue/stats') }}",
            },
            currentUser: {
                role: "{{ auth()->user()->role ?? 'user' }}",
                name: "{{ auth()->user()->name ?? '' }}",
                canEdit: {{ auth()->user()->role === 'admin' ? 'true' : 'false' }},
                canDelete: {{ auth()->user()->role === 'admin' ? 'true' : 'false' }},
                canImport: {{ auth()->user()->role === 'admin' ? 'true' : 'false' }}
            },
            settings: {
                debounceDelay: 300,
                paginationPerPage: 15,
                searchMinLength: 2,
                autoRefreshInterval: 30000, // 30 seconds
                maxFileSize: 10485760, // 10MB
                allowedFileTypes: ['.xlsx', '.xls', '.csv']
            }
        };

        // ✅ FIXED: Current data dari server untuk revenue.js
        window.currentData = {
            revenues: @json($revenues ?? collect()),
            accountManagers: @json($accountManagers ?? collect()),
            corporateCustomers: @json($corporateCustomers ?? collect()),
            statistics: @json($statistics ?? []),
            witels: @json($witels ?? collect()),
            regionals: @json($regionals ?? collect()),
            divisis: @json($divisis ?? collect()),
            filters: {
                search: "{{ request('search') }}",
                witel: "{{ request('witel') }}",
                regional: "{{ request('regional') }}",
                divisi: "{{ request('divisi') }}",
                month: "{{ request('month') }}",
                year: "{{ request('year') }}"
            }
        };

        // ✅ FIXED: Enhanced Error Boundary and Progressive Enhancement
        window.addEventListener('error', function(event) {
            console.error('JavaScript Error:', event.error);
            const errorBoundary = document.getElementById('js-error-boundary');
            if (errorBoundary) {
                errorBoundary.classList.add('show');
            }
        });

        // ✅ FIXED: Modal Form Reset Functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Reset forms when modals are hidden
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('hidden.bs.modal', function() {
                    const forms = modal.querySelectorAll('form[data-form-reset="true"]');
                    forms.forEach(form => {
                        form.reset();
                        // Clear validation feedback
                        const feedbacks = form.querySelectorAll('.validation-feedback');
                        feedbacks.forEach(feedback => {
                            feedback.textContent = '';
                            feedback.className = 'validation-feedback';
                        });
                        // Clear suggestions
                        const suggestions = form.querySelectorAll('.suggestions-container');
                        suggestions.forEach(suggestion => {
                            suggestion.classList.remove('show');
                        });
                        // Reset divisi buttons
                        const divisiButtons = form.querySelectorAll('.divisi-btn');
                        divisiButtons.forEach(btn => {
                            btn.classList.remove('active');
                        });
                        // Clear hidden inputs
                        const hiddenInputs = form.querySelectorAll('input[type="hidden"]');
                        hiddenInputs.forEach(input => {
                            if (!input.name.includes('_token') && !input.name.includes('_method')) {
                                input.value = '';
                            }
                        });
                    });
                });
            });

            // Add no-js class detection for progressive enhancement
            document.documentElement.classList.remove('no-js');

            // ✅ FIXED: Month Picker Initialization
            initializeMonthPicker();

            // ✅ FIXED: Tab Switching
            initializeTabSwitching();

            // ✅ FIXED: Bulk Selection
            initializeBulkSelection();

            // ✅ FIXED: Form Submissions
            initializeFormSubmissions();

            // ✅ FIXED: Bulk Delete All Functionality
            initializeBulkDeleteAll();
        });

        // ✅ FIXED: Month Picker Functions
        function initializeMonthPicker() {
            const monthPicker = document.getElementById('global_month_picker');
            const monthInput = document.getElementById('month_year_picker');
            const openButton = document.getElementById('open_month_picker');
            const currentYearSpan = document.getElementById('current_year');
            const monthGrid = document.getElementById('month_grid');
            const prevYearBtn = document.getElementById('prev_year');
            const nextYearBtn = document.getElementById('next_year');
            const cancelBtn = document.getElementById('cancel_month');
            const applyBtn = document.getElementById('apply_month');

            let currentYear = new Date().getFullYear();
            let selectedMonth = new Date().getMonth() + 1;

            const months = [
                'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
            ];

            function updateYearDisplay() {
                if (currentYearSpan) {
                    currentYearSpan.textContent = currentYear;
                }
            }

            function generateMonthGrid() {
                if (!monthGrid) return;
                monthGrid.innerHTML = '';
                months.forEach((month, index) => {
                    const monthButton = document.createElement('button');
                    monthButton.type = 'button';
                    monthButton.className = 'month-option';
                    monthButton.textContent = month;
                    monthButton.dataset.month = index + 1;

                    if (index + 1 === selectedMonth) {
                        monthButton.classList.add('selected');
                    }

                    monthButton.addEventListener('click', function() {
                        // Remove selected from all months
                        monthGrid.querySelectorAll('.month-option').forEach(btn => {
                            btn.classList.remove('selected');
                        });
                        // Add selected to clicked month
                        this.classList.add('selected');
                        selectedMonth = parseInt(this.dataset.month);
                    });

                    monthGrid.appendChild(monthButton);
                });
            }

            function showPicker() {
                if (monthPicker) {
                    monthPicker.classList.add('show');
                    generateMonthGrid();
                    updateYearDisplay();
                }
            }

            function hidePicker() {
                if (monthPicker) {
                    monthPicker.classList.remove('show');
                }
            }

            function applySelection() {
                const monthName = months[selectedMonth - 1];
                const formattedValue = `${monthName} ${currentYear}`;
                if (monthInput) {
                    monthInput.value = formattedValue;
                }

                // Update hidden inputs
                const bulanMonthInput = document.getElementById('bulan_month');
                const bulanYearInput = document.getElementById('bulan_year');
                const bulanInput = document.getElementById('bulan');

                if (bulanMonthInput) bulanMonthInput.value = selectedMonth.toString().padStart(2, '0');
                if (bulanYearInput) bulanYearInput.value = currentYear;
                if (bulanInput) bulanInput.value = `${currentYear}-${selectedMonth.toString().padStart(2, '0')}`;

                hidePicker();
            }

            // Event listeners
            if (openButton) {
                openButton.addEventListener('click', showPicker);
            }
            if (monthInput) {
                monthInput.addEventListener('click', showPicker);
            }
            if (prevYearBtn) {
                prevYearBtn.addEventListener('click', () => {
                    currentYear--;
                    updateYearDisplay();
                });
            }
            if (nextYearBtn) {
                nextYearBtn.addEventListener('click', () => {
                    currentYear++;
                    updateYearDisplay();
                });
            }
            if (cancelBtn) {
                cancelBtn.addEventListener('click', hidePicker);
            }
            if (applyBtn) {
                applyBtn.addEventListener('click', applySelection);
            }

            // Close picker when clicking outside
            document.addEventListener('click', function(event) {
                if (monthPicker && !monthPicker.contains(event.target) &&
                    monthInput && !monthInput.contains(event.target) &&
                    openButton && !openButton.contains(event.target)) {
                    hidePicker();
                }
            });

            // Initialize with current date
            const now = new Date();
            currentYear = now.getFullYear();
            selectedMonth = now.getMonth() + 1;
            applySelection();
        }

        // ✅ FIXED: Tab Switching
        function initializeTabSwitching() {
            const tabItems = document.querySelectorAll('.tab-item');
            const tabContents = document.querySelectorAll('.tab-content');

            tabItems.forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');

                    // Remove active class from all tabs and contents
                    tabItems.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    const targetContent = document.getElementById(targetTab);
                    if (targetContent) {
                        targetContent.classList.add('active');
                    }
                });
            });
        }

        // ✅ FIXED: Bulk Selection
        function initializeBulkSelection() {
            const bulkToolbar = document.getElementById('bulk-actions-toolbar');
            const selectedCountSpan = document.getElementById('selected-count');

            // Select all checkboxes
            const selectAllCheckboxes = document.querySelectorAll('#select-all-revenue, #select-all-am, #select-all-cc');
            selectAllCheckboxes.forEach(selectAll => {
                selectAll.addEventListener('change', function() {
                    const isChecked = this.checked;
                    const tableRows = this.closest('.tab-content').querySelectorAll('.row-checkbox');

                    tableRows.forEach(checkbox => {
                        checkbox.checked = isChecked;
                        const row = checkbox.closest('tr');
                        if (isChecked) {
                            row.classList.add('selected');
                        } else {
                            row.classList.remove('selected');
                        }
                    });

                    updateBulkActions();
                });
            });

            // Individual row checkboxes
            const rowCheckboxes = document.querySelectorAll('.row-checkbox');
            rowCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const row = this.closest('tr');
                    if (this.checked) {
                        row.classList.add('selected');
                    } else {
                        row.classList.remove('selected');
                    }
                    updateBulkActions();
                });
            });

            function updateBulkActions() {
                const selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
                const selectedCount = selectedCheckboxes.length;

                if (selectedCountSpan) {
                    selectedCountSpan.textContent = selectedCount;
                }

                if (bulkToolbar) {
                    if (selectedCount > 0) {
                        bulkToolbar.classList.add('show');
                    } else {
                        bulkToolbar.classList.remove('show');
                    }
                }
            }

            // Clear selection button
            const clearSelectionBtn = document.getElementById('clear-selection-btn');
            if (clearSelectionBtn) {
                clearSelectionBtn.addEventListener('click', function() {
                    rowCheckboxes.forEach(checkbox => {
                        checkbox.checked = false;
                        const row = checkbox.closest('tr');
                        row.classList.remove('selected');
                    });
                    selectAllCheckboxes.forEach(selectAll => {
                        selectAll.checked = false;
                    });
                    updateBulkActions();
                });
            }
        }

        // ✅ FIXED: Bulk Delete All Functionality
        function initializeBulkDeleteAll() {
            const showBulkDeleteAllBtn = document.getElementById('show-bulk-delete-all');
            const hideBulkDeleteAllBtn = document.getElementById('hide-bulk-delete-all');
            const bulkDeleteAllSection = document.getElementById('bulk-delete-all-section');
            const bulkDeleteAllBtn = document.getElementById('bulk-delete-all-btn');

            // Show bulk delete all section
            if (showBulkDeleteAllBtn && bulkDeleteAllSection) {
                showBulkDeleteAllBtn.addEventListener('click', function() {
                    bulkDeleteAllSection.style.display = 'block';
                });
            }

            // Hide bulk delete all section
            if (hideBulkDeleteAllBtn && bulkDeleteAllSection) {
                hideBulkDeleteAllBtn.addEventListener('click', function() {
                    bulkDeleteAllSection.style.display = 'none';
                });
            }

            // Handle bulk delete all click
            if (bulkDeleteAllBtn) {
                bulkDeleteAllBtn.addEventListener('click', function() {
                    // Show confirmation modal
                    const modal = document.getElementById('bulkDeleteAllModal');
                    if (modal) {
                        const bsModal = new bootstrap.Modal(modal);
                        bsModal.show();
                    }
                });
            }
        }

        // ✅ FIXED: Form Submissions
        function initializeFormSubmissions() {
            // Prevent double submission
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('btn-loading');
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('btn-loading');
                        }, 3000);
                    }
                });
            });
        }

        // ✅ FIXED: Page utilities
        function changePerPage(value) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', value);
            window.location.href = url.toString();
        }

        // ✅ FIXED: Close notification
        document.addEventListener('click', function(event) {
            if (event.target && event.target.id === 'notification-close') {
                const notification = document.getElementById('notification-container');
                if (notification) {
                    notification.classList.remove('show');
                }
            }
        });

        // ✅ FIXED: Filter Toggle
        document.addEventListener('click', function(event) {
            if (event.target && event.target.id === 'filterToggle') {
                const filterArea = document.getElementById('filterArea');
                if (filterArea) {
                    if (filterArea.style.display === 'none' || filterArea.style.display === '') {
                        filterArea.style.display = 'block';
                        event.target.classList.add('active');
                        const icon = event.target.querySelector('i');
                        if (icon) {
                            icon.className = 'fas fa-filter-circle-xmark';
                        }
                    } else {
                        filterArea.style.display = 'none';
                        event.target.classList.remove('active');
                        const icon = event.target.querySelector('i');
                        if (icon) {
                            icon.className = 'fas fa-filter';
                        }
                    }
                }
            }
        });

        // ✅ FIXED: Enhanced Error Handling
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Unhandled Promise Rejection:', event.reason);

            // Show user-friendly error notification
            const notification = document.getElementById('notification-container');
            const title = document.getElementById('notification-title');
            const message = document.getElementById('notification-message');

            if (notification && title && message) {
                title.textContent = 'Koneksi Bermasalah';
                message.textContent = 'Terjadi masalah koneksi. Beberapa fitur mungkin tidak berfungsi normal.';
                notification.className = 'notification-persistent warning show';

                // Auto hide after 5 seconds
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 5000);
            }
        });

        // ✅ FIXED: Loading State Management
        function showLoading() {
            const loadingOverlay = document.getElementById('loading-overlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
            }
        }

        function hideLoading() {
            const loadingOverlay = document.getElementById('loading-overlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
        }

        // ✅ FIXED: Global form validation helper
        function validateForm(form) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            return isValid;
        }

        // ✅ FIXED: Notification helper function
        function showNotification(title, message, type = 'info', duration = 5000) {
            const notification = document.getElementById('notification-container');
            const titleElement = document.getElementById('notification-title');
            const messageElement = document.getElementById('notification-message');

            if (notification && titleElement && messageElement) {
                titleElement.textContent = title;
                messageElement.textContent = message;
                notification.className = `notification-persistent ${type} show`;

                if (duration > 0) {
                    setTimeout(() => {
                        notification.classList.remove('show');
                    }, duration);
                }
            }
        }

        // ✅ FIXED: Mobile responsive touch improvements
        function initializeMobileEnhancements() {
            // Improve mobile touch experience
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');

                // Better tap targets for mobile
                const actionButtons = document.querySelectorAll('.action-btn');
                actionButtons.forEach(btn => {
                    btn.style.minHeight = '44px';
                    btn.style.minWidth = '44px';
                });

                // Smooth scrolling for mobile
                document.documentElement.style.scrollBehavior = 'smooth';

                // Prevent zoom on input focus (mobile)
                const inputs = document.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.addEventListener('focus', function() {
                        if (input.style.fontSize < '16px') {
                            input.style.fontSize = '16px';
                        }
                    });
                });
            }
        }

        // ✅ FIXED: Expose global functions for external use
        window.revenueHelpers = {
            showLoading: showLoading,
            hideLoading: hideLoading,
            validateForm: validateForm,
            showNotification: showNotification,
            changePerPage: changePerPage,
            initializeMobileEnhancements: initializeMobileEnhancements
        };

        // Initialize mobile enhancements on load
        document.addEventListener('DOMContentLoaded', function() {
            initializeMobileEnhancements();
        });

        console.log('✅ Revenue Management System - Configuration loaded successfully');
    </script>

    <!-- ✅ Load revenue.js - All JavaScript functionality will be handled here -->
    <script src="{{ asset('js/revenue.js?v=' . time()) }}"></script>
    <script src="{{ asset('js/dashboard.js?v=' . time()) }}"></script>

@endsection
