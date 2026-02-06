<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'RLEGS Dashboard')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.lineicons.com/5.0/lineicons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">

    <!-- Font + Global Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">

    <!-- Komponen/layout spesifik -->
    <link rel="stylesheet" href="{{ asset('css/sidebarpage.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ccwitel.css') }}">
    <link rel="stylesheet" href="{{ asset('css/inertia.css') }}">

    @yield('styles')
    <style>[x-cloak]{display:none !important}</style>

    <!-- Core JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- ENHANCED MOBILE RESPONSIVE STYLES -->
    <style>
        /* ========== GLOBAL AVATAR STYLING ========== */
        .avatar-container {
            width: 36px;
            height: 36px;
            min-width: 36px;
            min-height: 36px;
            overflow: hidden;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: #e9ecef;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .avatar-container:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .avatar-container img {
            width: 100%;
            height: 100%;
            min-width: 36px;
            min-height: 36px;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        /* ========== DESKTOP DEFAULT ========== */
        @media (min-width: 1025px) {
            .mobile-menu-btn,
            .sidebar-overlay,
            #mobile-navbar {
                display: none !important;
            }

            .navbar {
                display: flex !important;
            }

            /* Desktop Navbar Enhancements */
            .navbar {
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                background: #ffffff !important;
                border-bottom: 1px solid #e9ecef;
            }

            .navbar .dropdown-menu {
                border: none;
                box-shadow: 0 4px 12px rgba(0,0,0,0.12);
                border-radius: 8px;
                margin-top: 8px;
                min-width: 200px;
            }

            .navbar .dropdown-item {
                padding: 12px 20px;
                font-size: 14px;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
            }

            .navbar .dropdown-item i {
                margin-right: 10px;
                width: 18px;
                text-align: center;
            }

            .navbar .dropdown-item:hover {
                background: rgba(220, 53, 69, 0.1);
                color: #dc3545;
                transform: none;
            }

            .navbar .dropdown-item.text-danger:hover {
                background: rgba(220, 53, 69, 0.12);
                color: #dc3545 !important;
            }

            .navbar .nav-link {
                transition: all 0.2s ease;
                border-radius: 8px;
                padding: 8px 12px;
            }

            .navbar .nav-link:hover {
                background: #f8f9fa;
            }
        }

        /* ========== SIDEBAR MOBILE RESPONSIVE ========== */
        @media (max-width: 1024px) {
            /* HIDE DESKTOP NAVBAR */
            .navbar.navbar-expand-lg {
                display: none !important;
            }

            /* SIDEBAR BASE STYLES */
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
                z-index: 1040 !important;
                transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                box-shadow: none !important;
                overflow-y: auto !important;
                overflow-x: visible !important;
                display: block !important;
                visibility: visible !important;
                transform: translateX(0) !important;
                margin-left: 0 !important;
                background: white !important;
                border-right: none !important;
            }

            /* CRITICAL: Override any external CSS that might hide text */
            body.toggle-sidebar #sidebar .sidebar-link span,
            body #sidebar .sidebar-link span,
            #sidebar .sidebar-link span,
            aside#sidebar .sidebar-link span {
                display: inline-block !important;
                opacity: 1 !important;
                visibility: visible !important;
            }

            /* SIDEBAR OPEN STATE */
            body aside#sidebar.show,
            body #sidebar.show,
            aside#sidebar.show,
            #sidebar.show {
                left: 0 !important;
                transform: translateX(0) !important;
                width: 100vw !important;
            }

            /* OVERRIDE EXTERNAL TOGGLE */
            body.toggle-sidebar aside#sidebar,
            body.toggle-sidebar #sidebar {
                left: -100vw !important;
            }

            body.toggle-sidebar aside#sidebar.show,
            body.toggle-sidebar #sidebar.show {
                left: 0 !important;
            }

            /* SIDEBAR CONTENT */
            #sidebar {
                padding: 20px 15px !important;
            }

            #sidebar .sidebar-nav {
                margin-top: 20px !important;
                list-style: none !important;
                padding: 0 !important;
            }

            /* SIDEBAR HEADER - FIXED ALIGNMENT */
            #sidebar .d-flex {
                padding: 15px 10px !important;
                display: flex !important;
                align-items: center !important;
                background: transparent !important;
                gap: 12px !important;
            }

            #sidebar #toggle-btn {
                pointer-events: none !important;
                cursor: default !important;
                background: none !important;
                border: none !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 !important;
            }

            #sidebar #toggle-btn img {
                width: 40px !important;
                height: 40px !important;
                object-fit: contain !important;
            }

            #sidebar .sidebar-logo {
                display: flex !important;
                align-items: center !important;
                opacity: 1 !important;
                height: 40px !important;
            }

            #sidebar .sidebar-logo a {
                font-weight: 700 !important;
                font-size: 20px !important;
                text-decoration: none !important;
                color: #2d3748 !important;
                display: flex !important;
                align-items: center !important;
                line-height: 1 !important;
            }

            /* SIDEBAR NAVIGATION ITEMS */
            #sidebar .sidebar-item {
                margin: 0 !important;
                padding: 0 !important;
            }

            #sidebar .sidebar-link {
                display: flex !important;
                align-items: center !important;
                padding: 14px 18px !important;
                text-decoration: none !important;
                transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
                text-align: left !important;
                border-radius: 10px !important;
                margin: 4px 0 !important;
                color: #4a5568 !important;
                font-size: 15px !important;
                font-weight: 500 !important;
                position: relative !important;
            }

            /* SIDEBAR LINK HOVER - SIMPLE PINK */
            #sidebar .sidebar-link:hover {
                background: rgba(220, 53, 69, 0.1) !important;
                color: #dc3545 !important;
                transform: none !important;
                box-shadow: none !important;
            }

            /* SIDEBAR LINK ACTIVE - SIMPLE PINK */
            #sidebar .sidebar-link.active {
                background: rgba(220, 53, 69, 0.15) !important;
                color: #dc3545 !important;
                font-weight: 600 !important;
                box-shadow: none !important;
            }

            #sidebar .sidebar-link.active::before {
                content: '';
                position: absolute;
                left: 0;
                top: 50%;
                transform: translateY(-50%);
                width: 4px;
                height: 60%;
                background: #dc3545;
                border-radius: 0 4px 4px 0;
            }

            /* SIDEBAR ICONS */
            #sidebar .sidebar-link i {
                margin-right: 14px !important;
                width: 22px !important;
                text-align: center !important;
                font-size: 18px !important;
                flex-shrink: 0 !important;
                transition: none !important;
            }

            /* SIDEBAR TEXT - ALWAYS VISIBLE */
            #sidebar .sidebar-link span,
            aside#sidebar .sidebar-link span,
            body #sidebar .sidebar-link span,
            body aside#sidebar .sidebar-link span {
                display: inline-block !important;
                opacity: 1 !important;
                visibility: visible !important;
                white-space: nowrap !important;
                overflow: visible !important;
                font-weight: 500 !important;
                width: auto !important;
                min-width: 120px !important;
                max-width: none !important;
                text-align: left !important;
                margin-left: 0 !important;
                padding-left: 0 !important;
            }

            /* FORCE PARENT CONTAINERS TO SHOW OVERFLOW */
            #sidebar,
            aside#sidebar,
            body #sidebar,
            body aside#sidebar {
                overflow-x: visible !important;
                overflow-y: auto !important;
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

            #sidebar .sidebar-link,
            aside#sidebar .sidebar-link,
            body #sidebar .sidebar-link,
            body aside#sidebar .sidebar-link {
                overflow: visible !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            /* SIDEBAR FOOTER */
            #sidebar .sidebar-footer {
                margin-top: auto !important;
                padding-top: 20px !important;
                border-top: 1px solid #e9ecef !important;
            }

            #sidebar .sidebar-footer .sidebar-link {
                margin-top: 8px !important;
            }

            /* WRAPPER & MAIN */
            .wrapper {
                display: flex !important;
                padding-left: 0 !important;
                margin-left: 0 !important;
                width: 100vw !important;
                overflow-x: hidden !important;
            }

            .main {
                margin-left: 0 !important;
                padding: 0 !important;
                width: 100vw !important;
                max-width: 100vw !important;
                flex: 1 !important;
                padding-top: 30px !important;
            }

            /* ========== MOBILE NAVBAR - ENHANCED ========== */
            #mobile-navbar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                width: 100vw !important;
                z-index: 1030 !important;
                height: 64px !important;
                padding: 0 16px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                background: #ffffff !important;
                box-shadow: 0 2px 10px rgba(0,0,0,0.08) !important;
                border-bottom: 1px solid #e9ecef !important;
                overflow: visible !important;
            }

            #mobile-navbar.sidebar-open {
                display: none !important;
            }

            /* MOBILE NAVBAR CONTAINER */
            #mobile-navbar .mobile-navbar-container {
                width: 100% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                overflow: visible !important;
            }

            /* LEFT SIDE - HAMBURGER */
            #mobile-navbar .navbar-left {
                display: flex !important;
                align-items: center !important;
                flex-shrink: 0 !important;
            }

            /* HAMBURGER BUTTON - ENHANCED */
            .mobile-menu-btn {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: 44px !important;
                height: 44px !important;
                border: none !important;
                background: linear-gradient(135deg, #dc3545, #c82333) !important;
                color: white !important;
                border-radius: 10px !important;
                font-size: 20px !important;
                cursor: pointer !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                flex-shrink: 0 !important;
                box-shadow: 0 4px 8px rgba(220, 53, 69, 0.25) !important;
            }

            .mobile-menu-btn:hover {
                background: linear-gradient(135deg, #bb2d3b, #a02834) !important;
                transform: scale(1.05) translateY(-1px) !important;
                box-shadow: 0 6px 12px rgba(220, 53, 69, 0.35) !important;
            }

            .mobile-menu-btn:active {
                transform: scale(0.98) !important;
                box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3) !important;
            }

            .mobile-menu-btn.hide {
                display: none !important;
            }

            /* RIGHT SIDE - PROFILE */
            #mobile-navbar .navbar-right {
                display: flex !important;
                align-items: center !important;
                flex-shrink: 0 !important;
                position: relative !important;
                overflow: visible !important;
            }

            /* PROFILE DROPDOWN */
            #mobile-navbar .nav-item.dropdown {
                margin: 0 !important;
                position: relative !important;
            }

            #mobile-navbar .nav-item.dropdown .nav-link {
                padding: 6px !important;
                border-radius: 50% !important;
                transition: all 0.3s ease !important;
                color: #2d3748 !important;
                text-decoration: none !important;
                display: flex !important;
                align-items: center !important;
                background: transparent !important;
                border: none !important;
            }

            #mobile-navbar .nav-link.dropdown-toggle::after {
                display: none !important;
            }

            #mobile-navbar .nav-item.dropdown .nav-link:hover {
                background: #f8f9fa !important;
                transform: scale(1.02) !important;
            }

            /* AVATAR IN MOBILE NAVBAR */
            #mobile-navbar .avatar-container {
                width: 38px !important;
                height: 38px !important;
                min-width: 38px !important;
                min-height: 38px !important;
            }

            #mobile-navbar .avatar-container img {
                min-width: 38px !important;
                min-height: 38px !important;
            }

            /* DROPDOWN MENU - FIXED POSITIONING */
            #mobile-navbar .dropdown-menu {
                position: absolute !important;
                top: calc(100% + 8px) !important;
                right: 0 !important;
                left: auto !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
                border-radius: 12px !important;
                min-width: 200px !important;
                max-width: 220px !important;
                z-index: 1050 !important;
                background: white !important;
                overflow: hidden !important;
                transform-origin: top right !important;
                animation: dropdownFadeIn 0.2s ease !important;
            }

            @keyframes dropdownFadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px) scale(0.95);
                }
                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }

            #mobile-navbar .dropdown-menu.show {
                display: block !important;
            }

            /* DROPDOWN ITEMS - SIMPLE STYLE */
            #mobile-navbar .dropdown-item {
                padding: 12px 18px !important;
                font-size: 14.5px !important;
                color: #2d3748 !important;
                display: flex !important;
                align-items: center !important;
                white-space: nowrap !important;
                transition: all 0.2s ease !important;
                font-weight: 500 !important;
            }

            #mobile-navbar .dropdown-item:first-child {
                border-radius: 12px 12px 0 0;
            }

            #mobile-navbar .dropdown-item:last-child {
                border-radius: 0 0 12px 12px;
            }

            #mobile-navbar .dropdown-item:hover {
                background: rgba(220, 53, 69, 0.1) !important;
                color: #dc3545 !important;
                transform: none !important;
            }

            #mobile-navbar .dropdown-item.text-danger:hover {
                background: rgba(220, 53, 69, 0.12) !important;
                color: #dc3545 !important;
            }

            #mobile-navbar .dropdown-item i {
                margin-right: 10px !important;
                width: 18px !important;
                text-align: center !important;
                transition: none !important;
            }

            /* DIVIDER */
            #mobile-navbar .dropdown-divider {
                margin: 6px 0 !important;
                border-color: #e9ecef !important;
            }

            /* BODY ADJUSTMENTS */
            body {
                overflow-x: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                padding-top: 64px !important;
            }

            html {
                overflow-x: hidden !important;
            }
        }

        /* ========== SMALL MOBILE DEVICES ========== */
        @media (max-width: 576px) {
            #sidebar {
                padding: 15px 10px !important;
            }

            #mobile-navbar {
                height: 60px !important;
                padding: 0 12px !important;
            }

            .mobile-menu-btn {
                width: 40px !important;
                height: 40px !important;
                font-size: 18px !important;
            }

            #mobile-navbar .avatar-container {
                width: 34px !important;
                height: 34px !important;
                min-width: 34px !important;
                min-height: 34px !important;
            }

            #mobile-navbar .avatar-container img {
                min-width: 34px !important;
                min-height: 34px !important;
            }

            #mobile-navbar .dropdown-menu {
                min-width: 180px !important;
            }

            body {
                padding-top: 60px !important;
            }

            .main {
                padding-top: 30px !important;
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
                    <img src="{{ asset('img/twiyh.png') }}" class="avatar rounded-circle" alt="Logo" width="35" height="35">
                </button>
                <div class="sidebar-logo">
                    <a href="#">RLEGS TR3</a>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-th-large"></i><span>Overview Data</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('revenue.index') }}" class="sidebar-link {{ request()->routeIs('revenue.*') ? 'active' : '' }}">
                        <i class="fas fa-file-invoice-dollar"></i><span>Data Revenue</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('witel-cc-index') }}" class="sidebar-link {{ request()->routeIs('witel-cc-index') ? 'active' : '' }}">
                        <i class="fas fa-building"></i><span>CC & Witel</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('leaderboard') }}" class="sidebar-link {{ request()->routeIs('leaderboard') ? 'active' : '' }}">
                        <i class="fas fa-trophy"></i><span>Leaderboard AM</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('high-five.index') }}" class="sidebar-link {{ request()->routeIs('high-five.*') ? 'active' : '' }}">
                        <i class="fas fa-hand-sparkles"></i><span>High-Five</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <a href="{{ route('profile.index') }}" class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
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
            <!-- Desktop Navbar (hidden on mobile) -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
                <div class="container-fluid">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
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
                                            <i class="fas fa-cog"></i>{{ __('Settings') }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fas fa-sign-out-alt"></i>{{ __('Log Out') }}
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

    <!-- Enhanced Mobile Responsive JavaScript -->
    <script>
        let mobileNavbarCreated = false;
        let isTogglingInProgress = false;

        // Initialize sidebar position
        function initializeSidebarPosition() {
            if (window.innerWidth <= 1024) {
                const sidebar = document.querySelector('#sidebar');
                if (sidebar) {
                    document.body.classList.remove('toggle-sidebar');
                    sidebar.classList.remove('expanded', 'collapsed');
                    sidebar.style.left = '-100vw';
                    sidebar.style.position = 'fixed';
                    sidebar.style.zIndex = '1040';
                    sidebar.style.width = '100vw';
                    sidebar.style.maxWidth = '100vw';
                    sidebar.style.transition = 'left 0.3s cubic-bezier(0.4, 0, 0.2, 1)';

                    const toggleBtn = sidebar.querySelector('#toggle-btn');
                    if (toggleBtn) {
                        toggleBtn.style.pointerEvents = 'none';
                        toggleBtn.style.cursor = 'default';
                        toggleBtn.onclick = null;
                        const newToggleBtn = toggleBtn.cloneNode(true);
                        toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);
                    }
                }
            }
        }

        // Create mobile navbar
        function createMobileNavbar() {
            if (window.innerWidth <= 1024 && !mobileNavbarCreated) {
                mobileNavbarCreated = true;

                const existingMobileNav = document.querySelector('#mobile-navbar');
                if (existingMobileNav) {
                    existingMobileNav.remove();
                }

                const mobileNav = document.createElement('nav');
                mobileNav.id = 'mobile-navbar';

                const container = document.createElement('div');
                container.className = 'mobile-navbar-container';

                // Left - Hamburger
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
                    toggleSidebar();
                });

                navbarLeft.appendChild(hamburgerBtn);

                // Right - Profile
                const navbarRight = document.createElement('div');
                navbarRight.className = 'navbar-right';

                const profileDropdown = document.createElement('div');
                profileDropdown.className = 'nav-item dropdown';

                const userName = '{{ Auth::user()->name ?? "Admin" }}';
                const profileImage = '{{ Auth::user()->profile_image ? asset("storage/" . Auth::user()->profile_image) : asset("img/profile.png") }}';

                profileDropdown.innerHTML = `
                    <a class="nav-link dropdown-toggle" href="#" id="mobileProfileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-container">
                            <img src="${profileImage}" alt="${userName}" onerror="this.src='{{ asset('img/profile.png') }}'">
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="mobileProfileDropdown">
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.index') }}">
                                <i class="fas fa-cog"></i>Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                `;

                navbarRight.appendChild(profileDropdown);

                container.appendChild(navbarLeft);
                container.appendChild(navbarRight);
                mobileNav.appendChild(container);

                document.body.insertBefore(mobileNav, document.body.firstChild);

                // Initialize Bootstrap dropdown
                setTimeout(() => {
                    const dropdown = document.querySelector('#mobileProfileDropdown');
                    if (dropdown && typeof bootstrap !== 'undefined') {
                        new bootstrap.Dropdown(dropdown);
                    }
                }, 100);
            }
        }

        // Toggle sidebar
        function toggleSidebar() {
            if (isTogglingInProgress) return;
            isTogglingInProgress = true;

            const sidebar = document.querySelector('#sidebar');
            if (sidebar) {
                const isOpen = sidebar.classList.contains('show');
                if (isOpen) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            }

            setTimeout(() => {
                isTogglingInProgress = false;
            }, 350);
        }

        // Open sidebar
        function openSidebar() {
            const sidebar = document.querySelector('#sidebar');
            const mobileNavbar = document.querySelector('#mobile-navbar');
            const hamburger = document.querySelector('.mobile-menu-btn');

            if (sidebar) {
                sidebar.classList.add('show');

                if (hamburger) hamburger.classList.add('hide');
                if (mobileNavbar) mobileNavbar.classList.add('sidebar-open');

                sidebar.style.setProperty('left', '0px', 'important');
                sidebar.style.setProperty('overflow-x', 'visible', 'important');
                sidebar.style.setProperty('overflow-y', 'auto', 'important');
                
                document.body.style.overflow = 'hidden';

                // CRITICAL: Force all text spans to be visible
                const allSpans = sidebar.querySelectorAll('.sidebar-link span');
                allSpans.forEach(span => {
                    span.style.setProperty('display', 'inline-block', 'important');
                    span.style.setProperty('opacity', '1', 'important');
                    span.style.setProperty('visibility', 'visible', 'important');
                    span.style.setProperty('width', 'auto', 'important');
                    span.style.setProperty('min-width', '120px', 'important');
                    span.style.setProperty('max-width', 'none', 'important');
                    span.style.setProperty('overflow', 'visible', 'important');
                    span.style.setProperty('white-space', 'nowrap', 'important');
                });

                // Force parent containers to show overflow
                const sidebarNav = sidebar.querySelector('.sidebar-nav');
                if (sidebarNav) {
                    sidebarNav.style.setProperty('overflow', 'visible', 'important');
                }

                const sidebarItems = sidebar.querySelectorAll('.sidebar-item');
                sidebarItems.forEach(item => {
                    item.style.setProperty('overflow', 'visible', 'important');
                });

                const allLinks = sidebar.querySelectorAll('.sidebar-link');
                allLinks.forEach(link => {
                    link.style.setProperty('overflow', 'visible', 'important');
                    link.style.setProperty('display', 'flex', 'important');
                    link.style.setProperty('align-items', 'center', 'important');
                });

                // Auto-close on link click
                const sidebarLinks = sidebar.querySelectorAll('.sidebar-link:not(.has-dropdown)');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 1024) {
                            setTimeout(() => closeSidebar(), 200);
                        }
                    });
                });
            }
        }

        // Close sidebar
        function closeSidebar() {
            const sidebar = document.querySelector('#sidebar');
            const mobileNavbar = document.querySelector('#mobile-navbar');
            const hamburger = document.querySelector('.mobile-menu-btn');

            if (sidebar) {
                sidebar.classList.remove('show');

                if (hamburger) hamburger.classList.remove('hide');
                if (mobileNavbar) mobileNavbar.classList.remove('sidebar-open');

                sidebar.style.left = '-100vw';
                document.body.style.overflow = '';
            }
        }

        // Hide mobile elements on desktop
        function hideMobileElements() {
            const mobileNav = document.querySelector('#mobile-navbar');
            if (mobileNav) mobileNav.remove();

            const sidebar = document.querySelector('#sidebar');
            if (sidebar) sidebar.classList.remove('show');

            document.body.style.overflow = '';
        }

        // Click outside to close
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

        // Swipe gestures
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

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const sidebar = document.querySelector('#sidebar');
                if (sidebar && sidebar.classList.contains('show')) {
                    closeSidebar();
                }
            }
        });

        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            initializeSidebarPosition();
            if (window.innerWidth <= 1024) {
                createMobileNavbar();
            }
        });

        // Handle resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                if (window.innerWidth <= 1024 && !mobileNavbarCreated) {
                    createMobileNavbar();
                } else if (window.innerWidth > 1024) {
                    hideMobileElements();
                    mobileNavbarCreated = false;
                }
            }, 150);
        });

        // Bootstrap dropdown initialization
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });
    </script>

    @yield('scripts')
    @stack('scripts')
</body>
</html>