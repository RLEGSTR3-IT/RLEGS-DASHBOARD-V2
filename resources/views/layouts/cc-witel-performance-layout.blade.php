<!DOCTYPE html>
<!-- ALERT! this layout is a direct copy of the main layout, so ANY changes done to the main layout must be pasted over here as well for the cc + witel performance page layout to synchronize. see the note in line 93 for more info -->

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'RLEGS Dashboard')</title>

    <!-- CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.lineicons.com/5.0/lineicons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">

    <!-- 1) Font + Global Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/sidebarpage.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ccwitel.css') }}">
    <link rel="stylesheet" href="{{ asset('css/inertia.css') }}">

    @yield('styles')
    <style>[x-cloak]{display:none !important}</style>

    <!-- Core JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script defer src="https://unpkg.com/alpinejs@3.13.10/dist/cdn.min.js"></script>

    <!-- Mobile Responsive Styles -->
    <style>
        /* Avatar styling */
        .avatar-container {
            width: 35px;
            height: 35px;
            overflow: hidden;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .avatar-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        /* ========== DESKTOP DEFAULT ========== */
        @media (min-width: 1025px) {
            /* Hide mobile-only elements on desktop */
            .mobile-menu-btn,
            .sidebar-overlay,
            .mobile-fab-container {
                display: none !important;
            }

            /* Keep original navbar visible on desktop */
            .navbar {
                display: flex !important;
            }
        }

        /* ========== SIDEBAR MOBILE RESPONSIVE ========== */
        @media (max-width: 1024px) {
            /* CRITICAL: Hide original navbar on mobile - we'll use custom mobile navbar */
            .navbar.navbar-expand-lg {
                display: none !important;
            }

            /* CRITICAL: Override external CSS - Force hide sidebar by default */
            body aside#sidebar,
            body #sidebar,
            aside#sidebar,
            #sidebar {
                position: fixed !important;
                top: 0 !important;
                left: -100vw !important;
                width: 100vw !important;
                max-width: 100vw !important;
                height: 100vh !important;
                z-index: 1050 !important;
                transition: left 0.3s ease-in-out !important;
                box-shadow: none !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                display: block !important;
                visibility: visible !important;
                transform: translateX(0) !important;
                margin-left: 0 !important;
                background: white !important;
                border-right: none !important;
            }

            /* CRITICAL: Show sidebar when active - FULL SCREEN */
            body aside#sidebar.show,
            body #sidebar.show,
            aside#sidebar.show,
            #sidebar.show {
                left: 0 !important;
                transform: translateX(0) !important;
                margin-left: 0 !important;
                width: 100vw !important;
            }

            /* OVERRIDE: Disable external sidebar toggle behavior */
            body.toggle-sidebar aside#sidebar,
            body.toggle-sidebar #sidebar {
                left: -100vw !important;
            }

            body.toggle-sidebar aside#sidebar.show,
            body.toggle-sidebar #sidebar.show {
                left: 0 !important;
            }

            /* Full screen sidebar - add padding for better mobile UX */
            #sidebar {
                padding-bottom: 20px !important;
            }

            /* Sidebar header styling */
            #sidebar .d-flex,
            aside#sidebar .d-flex {
                padding: 15px !important;
                display: flex !important;
                align-items: center !important;
                background: transparent !important;
            }

            #sidebar .sidebar-logo,
            aside#sidebar .sidebar-logo {
                margin-left: 10px !important;
                display: block !important;
                opacity: 1 !important;
            }

            #sidebar .sidebar-logo a,
            aside#sidebar .sidebar-logo a {
                font-weight: 600 !important;
                font-size: 18px !important;
                text-decoration: none !important;
                display: block !important;
            }

            /* CRITICAL: Force logo button to not toggle external sidebar */
            #sidebar #toggle-btn,
            aside#sidebar #toggle-btn {
                pointer-events: none !important;
                cursor: default !important;
            }

            /* Sidebar navigation - FORCE SHOW TEXT */
            #sidebar .sidebar-nav,
            aside#sidebar .sidebar-nav {
                list-style: none !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
            }

            /* Force consistent spacing - override any external CSS */
            #sidebar .sidebar-nav > *,
            aside#sidebar .sidebar-nav > * {
                margin: 0 !important;
            }

            #sidebar .sidebar-nav > li,
            aside#sidebar .sidebar-nav > li {
                margin: 0 !important;
                padding: 0 !important;
            }

            #sidebar .sidebar-item,
            aside#sidebar .sidebar-item {
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }

            /* FORCE NO EXTRA SPACING between items */
            #sidebar .sidebar-item + .sidebar-item,
            aside#sidebar .sidebar-item + .sidebar-item {
                margin-top: 0 !important;
                padding-top: 0 !important;
            }

            #sidebar .sidebar-link,
            aside#sidebar .sidebar-link {
                display: flex !important;
                align-items: center !important;
                padding: 15px 20px !important;
                text-decoration: none !important;
                transition: all 0.2s ease !important;
                text-align: left !important;
                justify-content: flex-start !important;
                position: relative !important;
                width: 100% !important;
                border-radius: 8px !important;
                margin: 4px 8px !important;
                width: calc(100% - 16px) !important;
            }

            /* Hover effect sama kayak desktop */
            #sidebar .sidebar-link:hover,
            aside#sidebar .sidebar-link:hover {
                background: rgba(220, 53, 69, 0.1) !important;
                color: #dc3545 !important;
                transform: translateX(5px) !important;
            }

            /* Active state */
            #sidebar .sidebar-link.active,
            aside#sidebar .sidebar-link.active {
                background: rgba(220, 53, 69, 0.15) !important;
                color: #dc3545 !important;
                font-weight: 600 !important;
            }

            #sidebar .sidebar-link i,
            aside#sidebar .sidebar-link i {
                margin-right: 12px !important;
                width: 20px !important;
                text-align: center !important;
                font-size: 18px !important;
                flex-shrink: 0 !important;
                display: inline-block !important;
            }

            /* ULTRA CRITICAL: FORCE SHOW TEXT - Override external CSS */
            #sidebar .sidebar-link span,
            aside#sidebar .sidebar-link span,
            body #sidebar .sidebar-link span,
            body aside#sidebar .sidebar-link span {
                display: inline-block !important;
                text-align: left !important;
                flex-grow: 1 !important;
                font-weight: 400 !important;
                font-size: 15px !important;
                white-space: nowrap !important;
                opacity: 1 !important;
                visibility: visible !important;
                width: auto !important;
                min-width: 150px !important;
                max-width: none !important;
                overflow: visible !important;
                transition: none !important;
                padding-left: 0 !important;
                margin-left: 0 !important;
            }

            /* Force parent container to show overflow */
            #sidebar .sidebar-link,
            aside#sidebar .sidebar-link,
            body #sidebar .sidebar-link,
            body aside#sidebar .sidebar-link {
                overflow: visible !important;
            }

            #sidebar .sidebar-nav,
            aside#sidebar .sidebar-nav,
            body #sidebar .sidebar-nav,
            body aside#sidebar .sidebar-nav {
                overflow: visible !important;
            }

            #sidebar .sidebar-item,
            aside#sidebar .sidebar-item,
            body #sidebar .sidebar-item,
            body aside#sidebar .sidebar-item {
                overflow: visible !important;
            }

            /* Footer styling */
            #sidebar .sidebar-footer,
            aside#sidebar .sidebar-footer {
                margin-top: auto !important;
            }

            #sidebar .sidebar-footer .sidebar-link,
            aside#sidebar .sidebar-footer .sidebar-link {
                padding: 15px 20px !important;
            }

            #sidebar .sidebar-footer .sidebar-link span,
            aside#sidebar .sidebar-footer .sidebar-link span {
                display: inline-block !important;
                opacity: 1 !important;
                visibility: visible !important;
            }

            /* Disable hover expand on mobile */
            #sidebar:hover,
            aside#sidebar:hover {
                width: 100vw !important;
            }

            /* NO OVERLAY - sidebar will be full screen on mobile */
            .sidebar-overlay {
                display: none !important;
            }

            /* Force wrapper to not accommodate sidebar */
            .wrapper {
                display: flex !important;
                padding-left: 0 !important;
                margin-left: 0 !important;
                width: 100vw !important;
                max-width: 100vw !important;
                overflow-x: hidden !important;
            }

            /* Force main content to take full viewport width */
            .main {
                margin-left: 0 !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                width: 100vw !important;
                max-width: 100vw !important;
                min-width: 100vw !important;
                flex: 1 !important;
                position: relative !important;
                padding-top: 10px !important;
            }

            /* ========== MOBILE NAVBAR STYLES ========== */
            /* Mobile navbar - created by JavaScript */
            #mobile-navbar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                width: 100vw !important;
                max-width: 100vw !important;
                z-index: 1030 !important;
                height: 60px !important;
                padding: 0 15px !important;
                margin: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                background: white !important;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
                border-bottom: 1px solid #e9ecef !important;
            }

            /* Hide mobile navbar when sidebar is open */
            #mobile-navbar.sidebar-open {
                display: none !important;
            }

            /* Hamburger menu button */
            .mobile-menu-btn {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: 40px !important;
                height: 40px !important;
                border: none !important;
                background: #dc3545 !important;
                color: white !important;
                border-radius: 8px !important;
                font-size: 18px !important;
                cursor: pointer !important;
                transition: all 0.2s ease !important;
                flex-shrink: 0 !important;
            }

            .mobile-menu-btn:hover {
                background: #bb2d3b !important;
                transform: scale(1.05) !important;
            }

            .mobile-menu-btn:active {
                background: #a02834 !important;
            }

            /* Hide hamburger when sidebar is open */
            .mobile-menu-btn.hide {
                display: none !important;
            }

            /* Mobile navbar container */
            #mobile-navbar .mobile-navbar-container {
                width: 100% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 12px !important;
            }

            /* Left side with hamburger */
            #mobile-navbar .navbar-left {
                display: flex !important;
                align-items: center !important;
                gap: 0 !important;
                flex-shrink: 0 !important;
            }

            /* Right side with profile */
            #mobile-navbar .navbar-right {
                display: flex !important;
                align-items: center !important;
                flex-shrink: 0 !important;
            }

            /* Profile dropdown mobile */
            #mobile-navbar .nav-item.dropdown {
                margin: 0 !important;
            }

            #mobile-navbar .nav-item.dropdown .nav-link {
                padding: 6px !important;
                border-radius: 50% !important;
                transition: background 0.2s ease !important;
                color: #2d3748 !important;
                text-decoration: none !important;
                display: flex !important;
                align-items: center !important;
                gap: 0 !important;
            }

            /* Hide dropdown toggle arrow on mobile */
            #mobile-navbar .nav-link.dropdown-toggle::after {
                display: none !important;
            }

            #mobile-navbar .nav-item.dropdown .nav-link:hover {
                background: #f8f9fa !important;
            }

            /* Avatar container mobile */
            #mobile-navbar .avatar-container {
                width: 32px !important;
                height: 32px !important;
                margin: 0 !important;
                flex-shrink: 0 !important;
            }

            /* Profile name - HIDE ON MOBILE, only show avatar */
            #mobile-navbar .nav-link .user-name {
                display: none !important;
            }

            /* Dropdown menu mobile */
            #mobile-navbar .dropdown-menu {
                right: 0 !important;
                left: auto !important;
                margin-top: 8px !important;
                border: none !important;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
                border-radius: 8px !important;
                min-width: 180px !important;
            }

            /* ========== FLOATING ACTION BUTTON (FAB) FOR QUICK LINKS ========== */
            .mobile-fab-container {
                position: fixed;
                bottom: 80px;
                right: 20px;
                z-index: 1020;
                display: flex;
                flex-direction: column-reverse;
                align-items: flex-end;
                gap: 12px;
            }

            .mobile-fab-menu {
                display: flex;
                flex-direction: column-reverse;
                gap: 12px;
                opacity: 0;
                visibility: hidden;
                transform: translateY(20px);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .mobile-fab-menu.show {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .mobile-fab-item {
                display: flex;
                align-items: center;
                gap: 12px;
                background: white;
                padding: 12px 20px;
                border-radius: 50px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                text-decoration: none;
                color: #2d3748;
                font-weight: 500;
                font-size: 14px;
                transition: all 0.2s ease;
                white-space: nowrap;
                min-width: 200px;
            }

            .mobile-fab-item:hover {
                background: #dc3545;
                color: white;
                transform: translateX(-5px);
                box-shadow: 0 6px 16px rgba(220, 53, 69, 0.3);
            }

            .mobile-fab-item i {
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 16px;
            }

            .mobile-fab-button {
                width: 56px;
                height: 56px;
                border-radius: 50%;
                background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                color: white;
                border: none;
                box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                font-size: 20px;
            }

            .mobile-fab-button:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 16px rgba(220, 53, 69, 0.5);
            }

            .mobile-fab-button:active {
                transform: scale(0.95);
            }

            .mobile-fab-button.active {
                transform: rotate(45deg);
                background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            }

            .mobile-fab-button i {
                transition: transform 0.3s ease;
            }

            .mobile-fab-button.active i {
                transform: rotate(-45deg);
            }

            /* Body and HTML adjustments */
            body {
                overflow-x: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                padding-top: 60px !important;
                padding-bottom: 20px !important;
            }

            html {
                overflow-x: hidden !important;
            }
        }

        @media (max-width: 576px) {
            #mobile-navbar .avatar-container {
                width: 30px !important;
                height: 30px !important;
            }

            .mobile-menu-btn {
                width: 36px !important;
                height: 36px !important;
                font-size: 16px !important;
            }

            #mobile-navbar {
                padding: 0 10px !important;
            }

            .mobile-fab-container {
                right: 16px;
                bottom: 70px;
            }

            .mobile-fab-button {
                width: 50px;
                height: 50px;
                font-size: 18px;
            }

            .mobile-fab-item {
                min-width: 180px;
                padding: 10px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside id="sidebar">
            <div class="d-flex">
                <button id="toggle-btn" type="button">
                    <img src="{{ asset('img/twiyh.png') }}" class="avatar rounded-circle" alt="Logo" width="35" height="35" style="margin-left: 1px">
                </button>
                <div class="sidebar-logo">
                    <a href="#">RLEGS TR3</a>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="{{ route('dashboard') }}" class="sidebar-link">
                        <i class="fas fa-th-large"></i><span>Overview Data</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('revenue.index') }}" class="sidebar-link">
                        <i class="fas fa-file-invoice-dollar"></i><span>Data Revenue</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('witel-cc-index') }}" class="sidebar-link">
                        <i class="fas fa-building"></i><span>CC & Witel</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('leaderboard') }}" class="sidebar-link">
                        <i class="fas fa-trophy"></i><span>Leaderboard AM</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('high-five.index') }}" class="sidebar-link">
                        <i class="fas fa-hand-sparkles"></i><span>High-Five</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <a href="{{ route('profile.index') }}" class="sidebar-link">
                    <i class="fas fa-cog"></i><span>Settings</span>
                </a>
            </div>
            <div class="sidebar-footer">
                <a href="{{ route('logout') }}" class="sidebar-link">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main p-0">
            <!-- Original Desktop Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
                <div class="container-fluid">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <!-- NOTE: this nav element is why this page uses a distinct layout -->
                    <!-- Center: quick links with consistent spacing -->
                    <nav class="flex-fill">
                      <ul class="navbar-nav flex-row gap-3 mb-0 d-none d-md-flex">
                        <li class="nav-item">
                          <a href="#trend-revenue" class="nav-link d-flex align-items-center px-3 py-2">
                            <i class="lni lni-bar-chart-4 me-2"></i><span>Revenue Trend</span>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a href="#witel-performance" class="nav-link d-flex align-items-center px-3 py-2">
                            <i class="lni lni-buildings-1 me-2"></i><span>Witel Achievement</span>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a href="#top-customers" class="nav-link d-flex align-items-center px-3 py-2">
                            <i class="lni lni-trophy-1 me-2"></i><span>Customers Leaderboard</span>
                          </a>
                        </li>
                      </ul>
                    </nav>

                    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                        <ul class="navbar-nav align-items-center">
                            <li class="nav-item dropdown ms-1">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="avatar-container me-2">
                                        @if(Auth::user()->profile_image)
                                            <img src="{{ asset('storage/' . Auth::user()->profile_image) }}" alt="{{ Auth::user()->name }}">
                                        @else
                                            <img src="{{ asset('img/profile.png') }}" alt="Default Profile">
                                        @endif
                                    </div>
                                    <span>{{ Auth::user()->name }}</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profile.index') }}">
                                            {{ __('Settings') }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                {{ __('Log Out') }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            @yield('content')
        </div>
    </div>

    <!-- JavaScript -->
    <script src="{{ asset('sidebar/script.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>

    <!-- Mobile Responsive JavaScript -->
    <script>
        // Global flag to prevent multiple navbar creation
        let mobileNavbarCreated = false;
        let isTogglingInProgress = false;

        // Set initial sidebar position for mobile
        function initializeSidebarPosition() {
            if (window.innerWidth <= 1024) {
                const sidebar = document.querySelector('#sidebar');
                if (sidebar) {
                    // Remove any external classes that might interfere
                    document.body.classList.remove('toggle-sidebar');
                    sidebar.classList.remove('expanded', 'collapsed');

                    // Force initial position
                    sidebar.style.left = '-100vw';
                    sidebar.style.position = 'fixed';
                    sidebar.style.zIndex = '1050';
                    sidebar.style.width = '100vw';
                    sidebar.style.transition = 'left 0.3s ease-in-out';

                    // Disable external toggle button on mobile
                    const toggleBtn = sidebar.querySelector('#toggle-btn');
                    if (toggleBtn) {
                        toggleBtn.style.pointerEvents = 'none';
                        toggleBtn.style.cursor = 'default';

                        // Remove any external event listeners
                        toggleBtn.onclick = null;
                        const newToggleBtn = toggleBtn.cloneNode(true);
                        toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);
                    }

                    console.log('Initial sidebar position set');
                }
            }
        }

        // Mobile responsive enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial position first
            initializeSidebarPosition();

            // Create mobile navbar structure ONCE
            if (window.innerWidth <= 1024 && !mobileNavbarCreated) {
                createMobileNavbar();
                createMobileFAB();
            }

            // Initialize sidebar dropdowns
            initializeSidebarDropdowns();

            // Handle screen orientation changes
            window.addEventListener('orientationchange', function() {
                setTimeout(function() {
                    window.dispatchEvent(new Event('resize'));
                }, 100);
            });

            // Prevent horizontal scroll on mobile
            function preventHorizontalScroll() {
                if (window.innerWidth <= 1024) {
                    document.body.style.overflowX = 'hidden';
                    const wrapper = document.querySelector('.wrapper');
                    if (wrapper) {
                        wrapper.style.overflowX = 'hidden';
                    }
                }
            }

            // Mobile touch improvements
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
            }

            // Run on load and resize
            preventHorizontalScroll();
            window.addEventListener('resize', function() {
                preventHorizontalScroll();
                if (window.innerWidth > 1024) {
                    hideMobileElements();
                    mobileNavbarCreated = false;
                } else if (!mobileNavbarCreated) {
                    createMobileNavbar();
                    createMobileFAB();
                    initializeSidebarDropdowns();
                }
            });
        });

        // Initialize sidebar dropdowns functionality
        function initializeSidebarDropdowns() {
            const dropdownLinks = document.querySelectorAll('#sidebar .sidebar-link.has-dropdown');

            dropdownLinks.forEach(function(link) {
                link.removeEventListener('click', handleDropdownClick);
                link.addEventListener('click', handleDropdownClick);
            });
        }

        // Handle dropdown click
        function handleDropdownClick(e) {
            e.preventDefault();

            const link = e.currentTarget;
            const targetId = link.getAttribute('data-bs-target');
            const target = document.querySelector(targetId);

            if (target) {
                const isExpanded = link.getAttribute('aria-expanded') === 'true';

                const allDropdowns = document.querySelectorAll('#sidebar .sidebar-dropdown');
                const allDropdownLinks = document.querySelectorAll('#sidebar .sidebar-link.has-dropdown');

                allDropdowns.forEach(function(dropdown) {
                    if (dropdown !== target) {
                        dropdown.classList.remove('show');
                        dropdown.style.display = 'none';
                    }
                });

                allDropdownLinks.forEach(function(dropdownLink) {
                    if (dropdownLink !== link) {
                        dropdownLink.setAttribute('aria-expanded', 'false');
                        dropdownLink.classList.add('collapsed');
                    }
                });

                if (isExpanded) {
                    target.classList.remove('show');
                    target.style.display = 'none';
                    link.setAttribute('aria-expanded', 'false');
                    link.classList.add('collapsed');
                } else {
                    target.classList.add('show');
                    target.style.display = 'block';
                    link.setAttribute('aria-expanded', 'true');
                    link.classList.remove('collapsed');
                }
            }
        }

        // Create mobile navbar structure - COMPLETELY NEW IMPLEMENTATION
        function createMobileNavbar() {
            if (window.innerWidth <= 1024 && !mobileNavbarCreated) {
                console.log('Creating mobile navbar...');

                mobileNavbarCreated = true;

                // Remove any existing mobile navbar
                const existingMobileNav = document.querySelector('#mobile-navbar');
                if (existingMobileNav) {
                    existingMobileNav.remove();
                }

                // Create new mobile navbar
                const mobileNav = document.createElement('nav');
                mobileNav.id = 'mobile-navbar';

                // Create container
                const container = document.createElement('div');
                container.className = 'mobile-navbar-container';

                // Left side - Hamburger only
                const navbarLeft = document.createElement('div');
                navbarLeft.className = 'navbar-left';

                const hamburgerBtn = document.createElement('button');
                hamburgerBtn.className = 'mobile-menu-btn';
                hamburgerBtn.innerHTML = '<i class="fas fa-bars"></i>';
                hamburgerBtn.setAttribute('type', 'button');
                hamburgerBtn.setAttribute('aria-label', 'Toggle navigation');

                hamburgerBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Hamburger clicked!');
                    toggleSidebar();
                });

                navbarLeft.appendChild(hamburgerBtn);

                // Right side - Profile dropdown (ONLY AVATAR, NO NAME)
                const navbarRight = document.createElement('div');
                navbarRight.className = 'navbar-right';

                const profileDropdown = document.createElement('div');
                profileDropdown.className = 'nav-item dropdown';

                // Get user data
                const userName = '{{ Auth::user()->name ?? "Admin" }}';
                const profileImage = '{{ Auth::user()->profile_image ? asset("storage/" . Auth::user()->profile_image) : asset("img/profile.png") }}';

                // NO NAME, ONLY AVATAR
                profileDropdown.innerHTML = `
                    <a class="nav-link dropdown-toggle" href="#" id="mobileProfileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-container">
                            <img src="${profileImage}" alt="${userName}">
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="mobileProfileDropdown">
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.index') }}">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                `;

                navbarRight.appendChild(profileDropdown);

                // Append to container
                container.appendChild(navbarLeft);
                container.appendChild(navbarRight);
                mobileNav.appendChild(container);

                // Insert at the beginning of body
                document.body.insertBefore(mobileNav, document.body.firstChild);

                // Initialize Bootstrap dropdown for mobile navbar
                setTimeout(() => {
                    const dropdown = document.querySelector('#mobileProfileDropdown');
                    if (dropdown && typeof bootstrap !== 'undefined') {
                        new bootstrap.Dropdown(dropdown);
                    }
                }, 100);

                console.log('Mobile navbar created successfully!');
            }
        }

        // Create Floating Action Button (FAB) for quick links
        function createMobileFAB() {
            if (window.innerWidth <= 1024) {
                // Remove existing FAB if any
                const existingFAB = document.querySelector('.mobile-fab-container');
                if (existingFAB) {
                    existingFAB.remove();
                }

                // Create FAB container
                const fabContainer = document.createElement('div');
                fabContainer.className = 'mobile-fab-container';

                // Create FAB menu (expandable items)
                const fabMenu = document.createElement('div');
                fabMenu.className = 'mobile-fab-menu';

                // Quick links data
                const quickLinks = [
                    {
                        href: '#trend-revenue',
                        icon: 'lni lni-bar-chart-4',
                        text: 'Revenue Trend'
                    },
                    {
                        href: '#witel-performance',
                        icon: 'lni lni-buildings-1',
                        text: 'Witel Achievement'
                    },
                    {
                        href: '#top-customers',
                        icon: 'lni lni-trophy-1',
                        text: 'Customers Leaderboard'
                    }
                ];

                // Create menu items
                quickLinks.forEach(link => {
                    const item = document.createElement('a');
                    item.className = 'mobile-fab-item';
                    item.href = link.href;
                    item.innerHTML = `
                        <i class="${link.icon}"></i>
                        <span>${link.text}</span>
                    `;
                    
                    // Close menu when item is clicked
                    item.addEventListener('click', function() {
                        toggleFABMenu();
                    });
                    
                    fabMenu.appendChild(item);
                });

                // Create main FAB button
                const fabButton = document.createElement('button');
                fabButton.className = 'mobile-fab-button';
                fabButton.innerHTML = '<i class="fas fa-plus"></i>';
                fabButton.setAttribute('aria-label', 'Quick navigation menu');

                fabButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleFABMenu();
                });

                // Append elements
                fabContainer.appendChild(fabMenu);
                fabContainer.appendChild(fabButton);
                document.body.appendChild(fabContainer);

                console.log('Mobile FAB created successfully!');
            }
        }

        // Toggle FAB menu
        function toggleFABMenu() {
            const fabMenu = document.querySelector('.mobile-fab-menu');
            const fabButton = document.querySelector('.mobile-fab-button');

            if (fabMenu && fabButton) {
                fabMenu.classList.toggle('show');
                fabButton.classList.toggle('active');
            }
        }

        // Close FAB menu when clicking outside
        document.addEventListener('click', function(event) {
            const fabContainer = document.querySelector('.mobile-fab-container');
            const fabMenu = document.querySelector('.mobile-fab-menu');
            const fabButton = document.querySelector('.mobile-fab-button');

            if (fabContainer && fabMenu && fabButton) {
                if (!fabContainer.contains(event.target) && fabMenu.classList.contains('show')) {
                    fabMenu.classList.remove('show');
                    fabButton.classList.remove('active');
                }
            }
        });

        // Toggle sidebar function
        function toggleSidebar() {
            if (isTogglingInProgress) {
                console.log('Toggle already in progress, ignoring...');
                return;
            }

            isTogglingInProgress = true;

            console.log('Toggle sidebar clicked!');
            const sidebar = document.querySelector('#sidebar');

            console.log('Sidebar:', sidebar);

            if (sidebar) {
                const isOpen = sidebar.classList.contains('show');
                console.log('Is open:', isOpen);

                if (isOpen) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            } else {
                console.error('Sidebar not found!');
            }

            setTimeout(() => {
                isTogglingInProgress = false;
            }, 350);
        }

        // Open sidebar
        function openSidebar() {
            console.log('Opening sidebar...');
            const sidebar = document.querySelector('#sidebar');
            const mobileNavbar = document.querySelector('#mobile-navbar');
            const hamburger = document.querySelector('.mobile-menu-btn');
            const fabContainer = document.querySelector('.mobile-fab-container');

            if (sidebar) {
                sidebar.classList.add('show');

                // HIDE HAMBURGER WHEN SIDEBAR OPENS
                if (hamburger) {
                    hamburger.classList.add('hide');
                }
                if (mobileNavbar) {
                    mobileNavbar.classList.add('sidebar-open');
                }

                // Hide FAB when sidebar opens
                if (fabContainer) {
                    fabContainer.style.display = 'none';
                }

                // FULL SCREEN SIDEBAR
                sidebar.style.setProperty('left', '0px', 'important');
                sidebar.style.setProperty('position', 'fixed', 'important');
                sidebar.style.setProperty('transform', 'translateX(0)', 'important');
                sidebar.style.setProperty('z-index', '1050', 'important');
                sidebar.style.setProperty('width', '100vw', 'important');
                sidebar.style.setProperty('max-width', '100vw', 'important');
                sidebar.style.setProperty('overflow', 'visible', 'important');
                sidebar.style.setProperty('overflow-x', 'visible', 'important');
                sidebar.style.setProperty('overflow-y', 'auto', 'important');

                const sidebarNav = sidebar.querySelector('.sidebar-nav');
                if (sidebarNav) {
                    sidebarNav.style.setProperty('overflow', 'visible', 'important');
                }

                const sidebarItems = sidebar.querySelectorAll('.sidebar-item');
                sidebarItems.forEach(item => {
                    item.style.setProperty('overflow', 'visible', 'important');
                    item.style.setProperty('margin', '0', 'important');
                    item.style.setProperty('padding', '0', 'important');
                });

                const allSpans = sidebar.querySelectorAll('.sidebar-link span');
                allSpans.forEach(span => {
                    span.style.setProperty('display', 'inline-block', 'important');
                    span.style.setProperty('opacity', '1', 'important');
                    span.style.setProperty('visibility', 'visible', 'important');
                    span.style.setProperty('width', 'auto', 'important');
                    span.style.setProperty('min-width', '150px', 'important');
                    span.style.setProperty('max-width', 'none', 'important');
                    span.style.setProperty('overflow', 'visible', 'important');
                    span.style.setProperty('white-space', 'nowrap', 'important');
                    span.style.setProperty('transition', 'none', 'important');
                    span.style.setProperty('margin-left', '12px', 'important');
                    span.style.setProperty('padding-left', '0', 'important');
                    span.style.setProperty('font-size', '15px', 'important');
                    span.style.setProperty('font-weight', '400', 'important');
                });

                const allLinks = sidebar.querySelectorAll('.sidebar-link');
                allLinks.forEach(link => {
                    link.style.setProperty('overflow', 'visible', 'important');
                    link.style.setProperty('display', 'flex', 'important');
                    link.style.setProperty('align-items', 'center', 'important');
                });

                const logoText = sidebar.querySelector('.sidebar-logo');
                if (logoText) {
                    logoText.style.setProperty('display', 'block', 'important');
                    logoText.style.setProperty('opacity', '1', 'important');
                    logoText.style.setProperty('visibility', 'visible', 'important');
                }

                document.body.style.overflow = 'hidden';

                console.log('Sidebar opened');

                initializeSidebarDropdowns();

                const sidebarLinks = sidebar.querySelectorAll('.sidebar-link:not(.has-dropdown)');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 1024) {
                            setTimeout(() => {
                                closeSidebar();
                            }, 200);
                        }
                    });
                });
            }
        }

        // Close sidebar
        function closeSidebar() {
            console.log('Closing sidebar...');
            const sidebar = document.querySelector('#sidebar');
            const mobileNavbar = document.querySelector('#mobile-navbar');
            const hamburger = document.querySelector('.mobile-menu-btn');
            const fabContainer = document.querySelector('.mobile-fab-container');

            if (sidebar) {
                sidebar.classList.remove('show');

                // SHOW HAMBURGER WHEN SIDEBAR CLOSES
                if (hamburger) {
                    hamburger.classList.remove('hide');
                }
                if (mobileNavbar) {
                    mobileNavbar.classList.remove('sidebar-open');
                }

                // Show FAB when sidebar closes
                if (fabContainer) {
                    fabContainer.style.display = 'flex';
                }

                sidebar.style.left = '-100vw';

                document.body.style.overflow = '';

                console.log('Sidebar closed');
            }
        }

        // Hide mobile elements on desktop
        function hideMobileElements() {
            const mobileNav = document.querySelector('#mobile-navbar');
            if (mobileNav) {
                mobileNav.remove();
            }

            const fabContainer = document.querySelector('.mobile-fab-container');
            if (fabContainer) {
                fabContainer.remove();
            }

            const sidebar = document.querySelector('#sidebar');
            if (sidebar) {
                sidebar.classList.remove('show');
            }

            document.body.style.overflow = '';
        }

        // Handle clicks outside sidebar to close it
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 1024) {
                const sidebar = document.querySelector('#sidebar');
                const hamburger = document.querySelector('.mobile-menu-btn');

                if (sidebar && sidebar.classList.contains('show')) {
                    if (!sidebar.contains(event.target) && hamburger && !hamburger.contains(event.target)) {
                        closeSidebar();
                    }
                }
            }
        });

        // Handle swipe gestures for mobile
        let touchStartX = 0;
        let touchEndX = 0;

        document.addEventListener('touchstart', function(event) {
            touchStartX = event.changedTouches[0].screenX;
        });

        document.addEventListener('touchend', function(event) {
            touchEndX = event.changedTouches[0].screenX;
            handleSwipe();
        });

        function handleSwipe() {
            if (window.innerWidth <= 1024) {
                const swipeDistance = touchEndX - touchStartX;
                const sidebar = document.querySelector('#sidebar');

                if (swipeDistance > 50 && touchStartX < 50) {
                    openSidebar();
                }

                if (swipeDistance < -50 && sidebar && sidebar.classList.contains('show')) {
                    closeSidebar();
                }
            }
        }

        // Adjust viewport for better mobile rendering
        function adjustViewportForMobile() {
            const viewport = document.querySelector('meta[name=viewport]');
            if (viewport && window.innerWidth <= 1024) {
                viewport.setAttribute('content',
                    'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'
                );
            }
        }

        adjustViewportForMobile();
        window.addEventListener('resize', adjustViewportForMobile);

        // Accessibility improvements
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const sidebar = document.querySelector('#sidebar');
                if (sidebar && sidebar.classList.contains('show')) {
                    closeSidebar();
                }
                
                // Also close FAB menu if open
                const fabMenu = document.querySelector('.mobile-fab-menu');
                const fabButton = document.querySelector('.mobile-fab-button');
                if (fabMenu && fabMenu.classList.contains('show')) {
                    fabMenu.classList.remove('show');
                    fabButton.classList.remove('active');
                }
            }

            if (e.key === 'Enter' || e.key === ' ') {
                const target = e.target;
                if (target.classList.contains('has-dropdown')) {
                    e.preventDefault();
                    handleDropdownClick(e);
                }
            }
        });

        // Enhanced Bootstrap dropdown initialization for mobile
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });

            document.addEventListener('show.bs.dropdown', function (e) {
                const dropdown = e.target.closest('.dropdown');
                if (dropdown && window.innerWidth <= 1024) {
                    setTimeout(function() {
                        const menu = dropdown.querySelector('.dropdown-menu');
                        if (menu) {
                            const rect = dropdown.getBoundingClientRect();
                            const viewportWidth = window.innerWidth;

                            if (rect.right + menu.offsetWidth > viewportWidth) {
                                menu.classList.add('dropdown-menu-end');
                            }
                        }
                    }, 10);
                }
            });
        });

        // Performance optimization for mobile
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                if (window.innerWidth <= 1024 && !mobileNavbarCreated) {
                    createMobileNavbar();
                    createMobileFAB();
                    initializeSidebarDropdowns();
                } else if (window.innerWidth > 1024) {
                    hideMobileElements();
                    mobileNavbarCreated = false;
                }
            }, 150);
        });

        // Optimize for mobile performance
        function optimizeForMobile() {
            if (window.innerWidth <= 1024) {
                document.body.classList.add('mobile-optimized');

                if ('ontouchstart' in window) {
                    document.body.classList.add('touch-device');
                }

                document.body.style.webkitOverflowScrolling = 'touch';
            }
        }

        optimizeForMobile();

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initializeSidebarDropdowns();
                if (window.innerWidth <= 1024 && !mobileNavbarCreated) {
                    createMobileNavbar();
                    createMobileFAB();
                }
            });
        } else {
            initializeSidebarDropdowns();
            if (window.innerWidth <= 1024 && !mobileNavbarCreated) {
                createMobileNavbar();
                createMobileFAB();
            }
        }

    </script>

    @yield('scripts')
    @stack('scripts')
</body>
</html>