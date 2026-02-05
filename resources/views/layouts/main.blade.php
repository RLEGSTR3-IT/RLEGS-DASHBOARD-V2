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

    <!-- ========================================
         MOBILE RESPONSIVE STYLES - REBUILT
         ======================================== -->
    <style>
        /* ========================================
           AVATAR STYLING - GLOBAL
           ======================================== */
        .avatar-container {
            width: 35px;
            height: 35px;
            overflow: hidden;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            flex-shrink: 0;
            background-color: #e9ecef;
        }

        .avatar-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
            opacity: 1;
            visibility: visible;
        }

        /* ========================================
           DESKTOP DEFAULT (> 1024px)
           ======================================== */
        @media (min-width: 1025px) {
            .mobile-menu-btn,
            .sidebar-overlay,
            #mobile-navbar {
                display: none !important;
            }

            .navbar {
                display: flex !important;
            }
        }

        /* ========================================
           MOBILE RESPONSIVE (<= 1024px)
           ======================================== */
        @media (max-width: 1024px) {
            /* HIDE ORIGINAL NAVBAR ON MOBILE */
            .navbar.navbar-expand-lg {
                display: none !important;
            }

            /* ========================================
               SIDEBAR MOBILE CONFIGURATION
               ======================================== */
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

            /* SIDEBAR SHOW STATE */
            body aside#sidebar.show,
            body #sidebar.show,
            aside#sidebar.show,
            #sidebar.show {
                left: 0 !important;
                transform: translateX(0) !important;
                margin-left: 0 !important;
                width: 100vw !important;
            }

            /* OVERRIDE EXTERNAL SIDEBAR TOGGLE */
            body.toggle-sidebar aside#sidebar,
            body.toggle-sidebar #sidebar {
                left: -100vw !important;
            }

            body.toggle-sidebar aside#sidebar.show,
            body.toggle-sidebar #sidebar.show {
                left: 0 !important;
            }

            /* SIDEBAR PADDING */
            #sidebar {
                padding: 20px !important;
            }

            #sidebar .sidebar-nav,
            aside#sidebar .sidebar-nav {
                margin-top: 20px !important;
            }

            /* SIDEBAR HEADER */
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

            /* DISABLE LOGO BUTTON TOGGLE */
            #sidebar #toggle-btn,
            aside#sidebar #toggle-btn {
                pointer-events: none !important;
                cursor: default !important;
            }

            /* ========================================
               SIDEBAR NAVIGATION
               ======================================== */
            #sidebar .sidebar-nav,
            aside#sidebar .sidebar-nav {
                list-style: none !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
            }

            #sidebar .sidebar-nav > *,
            aside#sidebar .sidebar-nav > * {
                margin: 0 !important;
            }

            #sidebar .sidebar-item,
            aside#sidebar .sidebar-item {
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
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
                width: calc(100% - 16px) !important;
                border-radius: 8px !important;
                margin: 4px 8px !important;
            }

            #sidebar .sidebar-link:hover,
            aside#sidebar .sidebar-link:hover {
                background: rgba(220, 53, 69, 0.1) !important;
                color: #dc3545 !important;
                transform: translateX(5px) !important;
            }

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

            /* FORCE SHOW TEXT */
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
            }

            /* SIDEBAR FOOTER */
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

            /* NO OVERLAY */
            .sidebar-overlay {
                display: none !important;
            }

            /* ========================================
               MAIN CONTENT WRAPPER
               ======================================== */
            .wrapper {
                display: flex !important;
                padding-left: 0 !important;
                margin-left: 0 !important;
                width: 100vw !important;
                max-width: 100vw !important;
                overflow-x: hidden !important;
            }

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

            /* ========================================
               MOBILE NAVBAR
               ======================================== */
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
                overflow: visible !important;
            }

            #mobile-navbar.sidebar-open {
                display: none !important;
            }

            /* HAMBURGER BUTTON */
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

            .mobile-menu-btn.hide {
                display: none !important;
            }

            /* MOBILE NAVBAR CONTAINER */
            #mobile-navbar .mobile-navbar-container {
                width: 100% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 12px !important;
                overflow: visible !important;
            }

            #mobile-navbar .navbar-left {
                display: flex !important;
                align-items: center !important;
                gap: 0 !important;
                flex-shrink: 0 !important;
            }

            #mobile-navbar .navbar-right {
                display: flex !important;
                align-items: center !important;
                flex-shrink: 0 !important;
                overflow: visible !important;
                position: relative !important;
            }

            /* ========================================
               MOBILE DROPDOWN - CRITICAL FIX
               ======================================== */
            #mobile-navbar .nav-item.dropdown {
                margin: 0 !important;
                position: relative !important;
                overflow: visible !important;
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
                overflow: visible !important;
            }

            #mobile-navbar .nav-link.dropdown-toggle::after {
                display: none !important;
            }

            #mobile-navbar .nav-item.dropdown .nav-link:hover {
                background: #f8f9fa !important;
            }

            /* AVATAR MOBILE */
            #mobile-navbar .avatar-container {
                width: 32px !important;
                height: 32px !important;
                margin: 0 !important;
                flex-shrink: 0 !important;
                background-color: #e9ecef !important;
                border: 2px solid #fff !important;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            }

            #mobile-navbar .avatar-container img {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
                display: block !important;
            }

            /* HIDE USER NAME ON MOBILE */
            #mobile-navbar .nav-link .user-name {
                display: none !important;
            }

            /* ✅ CRITICAL: DROPDOWN MENU PROPER POSITIONING */
            #mobile-navbar .dropdown-menu {
                position: absolute !important;
                top: 100% !important;
                right: auto !important;
                left: 0 !important;
                margin-top: 8px !important;
                border: 1px solid rgba(0,0,0,0.1) !important;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
                border-radius: 8px !important;
                min-width: 200px !important;
                z-index: 1050 !important;
                background: white !important;
                transform: none !important;
                inset: auto !important;
            }
            
            /* Override dropdown-menu-end on mobile */
            #mobile-navbar .dropdown-menu.dropdown-menu-end {
                right: auto !important;
                left: 0 !important;
            }

            #mobile-navbar .dropdown-menu.show {
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
            }

            #mobile-navbar .dropdown-menu .dropdown-item {
                padding: 10px 16px !important;
                color: #2d3748 !important;
                transition: background 0.2s ease !important;
                display: flex !important;
                align-items: center !important;
            }

            #mobile-navbar .dropdown-menu .dropdown-item:hover {
                background: #f8f9fa !important;
            }

            #mobile-navbar .dropdown-menu .dropdown-item.text-danger {
                color: #dc3545 !important;
            }

            #mobile-navbar .dropdown-menu .dropdown-item.text-danger:hover {
                background: rgba(220, 53, 69, 0.1) !important;
            }

            #mobile-navbar .dropdown-divider {
                margin: 8px 0 !important;
                opacity: 0.1 !important;
            }

            /* ========================================
               BODY ADJUSTMENTS
               ======================================== */
            body {
                overflow-x: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                padding-top: 60px !important;
            }

            html {
                overflow-x: hidden !important;
            }
        }

        /* ========================================
           EXTRA SMALL DEVICES
           ======================================== */
        @media (max-width: 576px) {
            #sidebar {
                width: 100vw !important;
                max-width: 100vw !important;
            }

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

            #mobile-navbar .dropdown-menu {
                min-width: 180px !important;
                font-size: 14px !important;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- ========================================
             SIDEBAR
             ======================================== -->
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

        <!-- ========================================
             MAIN CONTENT
             ======================================== -->
        <div class="main p-0">
            <!-- Original Desktop Navbar (hidden on mobile) -->
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

    <!-- ========================================
         JAVASCRIPT
         ======================================== -->
    <script src="{{ asset('sidebar/script.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>

    <!-- ========================================
         MOBILE RESPONSIVE JAVASCRIPT - REBUILT
         ======================================== -->
    <script>
        // Global variables
        let mobileNavbarCreated = false;
        let isTogglingInProgress = false;

        // ========================================
        // INITIALIZE
        // ========================================
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
                    sidebar.style.transition = 'left 0.3s ease-in-out';

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

        document.addEventListener('DOMContentLoaded', function() {
            initializeSidebarPosition();

            if (window.innerWidth <= 1024 && !mobileNavbarCreated) {
                createMobileNavbar();
            }

            initializeSidebarDropdowns();

            window.addEventListener('orientationchange', function() {
                setTimeout(function() {
                    window.dispatchEvent(new Event('resize'));
                }, 100);
            });

            preventHorizontalScroll();
            window.addEventListener('resize', function() {
                preventHorizontalScroll();
                if (window.innerWidth > 1024) {
                    hideMobileElements();
                    mobileNavbarCreated = false;
                } else if (!mobileNavbarCreated) {
                    createMobileNavbar();
                    initializeSidebarDropdowns();
                }
            });

            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
            }
        });

        function preventHorizontalScroll() {
            if (window.innerWidth <= 1024) {
                document.body.style.overflowX = 'hidden';
                const wrapper = document.querySelector('.wrapper');
                if (wrapper) {
                    wrapper.style.overflowX = 'hidden';
                }
            }
        }

        // ========================================
        // SIDEBAR DROPDOWNS
        // ========================================
        function initializeSidebarDropdowns() {
            const dropdownLinks = document.querySelectorAll('#sidebar .sidebar-link.has-dropdown');
            dropdownLinks.forEach(function(link) {
                link.removeEventListener('click', handleDropdownClick);
                link.addEventListener('click', handleDropdownClick);
            });
        }

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

        // ========================================
        // CREATE MOBILE NAVBAR - REBUILT
        // ========================================
        function createMobileNavbar() {
            if (window.innerWidth <= 1024 && !mobileNavbarCreated) {
                console.log('Creating mobile navbar...');
                mobileNavbarCreated = true;

                const existingMobileNav = document.querySelector('#mobile-navbar');
                if (existingMobileNav) {
                    existingMobileNav.remove();
                }

                // Get user data
                const userName = '{{ Auth::user()->name ?? "User" }}';
                let profileImageSrc = '{{ Auth::user()->profile_image ? asset("storage/" . Auth::user()->profile_image) : "" }}';
                
                // If no custom profile image, use default immediately
                if (!profileImageSrc || profileImageSrc.trim() === '') {
                    profileImageSrc = '{{ asset("img/profile.png") }}';
                }

                // Create mobile navbar
                const mobileNav = document.createElement('nav');
                mobileNav.id = 'mobile-navbar';
                mobileNav.style.overflow = 'visible';

                mobileNav.innerHTML = `
                    <div class="mobile-navbar-container" style="overflow: visible;">
                        <div class="navbar-left">
                            <button class="mobile-menu-btn" type="button" aria-label="Toggle navigation">
                                <i class="fas fa-bars"></i>
                            </button>
                        </div>
                        <div class="navbar-right" style="overflow: visible; position: relative;">
                            <div class="nav-item dropdown" style="overflow: visible; position: relative;">
                                <a class="nav-link" href="#" id="mobileProfileDropdown" role="button" aria-expanded="false">
                                    <div class="avatar-container">
                                        <img src="${profileImageSrc}" 
                                             alt="${userName}" 
                                             style="display: block; opacity: 1; visibility: visible; width: 100%; height: 100%; object-fit: cover;" 
                                             onerror="console.log('Image error, using fallback'); this.onerror=null; this.src='{{ asset('img/profile.png') }}';">
                                    </div>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="mobileProfileDropdown">
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
                            </div>
                        </div>
                    </div>
                `;

                document.body.insertBefore(mobileNav, document.body.firstChild);

                // Setup hamburger
                const hamburger = mobileNav.querySelector('.mobile-menu-btn');
                if (hamburger) {
                    hamburger.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        toggleSidebar();
                    });
                }

                // Setup custom dropdown (no Bootstrap Popper)
                setTimeout(function() {
                    setupMobileDropdown();
                    fixProfileImages();
                }, 100);

                console.log('Mobile navbar created!');
            }
        }

        // ========================================
        // MOBILE DROPDOWN - CUSTOM (NO POPPER)
        // ========================================
        function setupMobileDropdown() {
            const mobileDropdown = document.querySelector('#mobile-navbar #mobileProfileDropdown');
            
            if (!mobileDropdown) {
                console.log('Mobile dropdown not found');
                return;
            }

            console.log('Setting up mobile dropdown...');

            // Custom dropdown toggle (NO Bootstrap)
            mobileDropdown.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const dropdownMenu = this.nextElementSibling;
                
                if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                    const isShown = dropdownMenu.classList.contains('show');
                    
                    if (isShown) {
                        dropdownMenu.classList.remove('show');
                        this.setAttribute('aria-expanded', 'false');
                    } else {
                        dropdownMenu.classList.add('show');
                        this.setAttribute('aria-expanded', 'true');
                        
                        // Force proper positioning
                        dropdownMenu.style.position = 'absolute';
                        dropdownMenu.style.top = '100%';
                        dropdownMenu.style.right = 'auto';
                        dropdownMenu.style.left = '0';
                        dropdownMenu.style.marginTop = '8px';
                        dropdownMenu.style.zIndex = '1050';
                        dropdownMenu.style.transform = 'none';
                    }
                }
            });

            // Close on click outside
            document.addEventListener('click', function(e) {
                const dropdown = document.querySelector('#mobile-navbar .dropdown-menu.show');
                const trigger = document.querySelector('#mobile-navbar #mobileProfileDropdown');
                
                if (dropdown && trigger) {
                    if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
                        dropdown.classList.remove('show');
                        trigger.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            console.log('Mobile dropdown setup complete!');
        }

        // ========================================
        // PROFILE IMAGE FIX
        // ========================================
        function fixProfileImages() {
            const avatarImages = document.querySelectorAll('.avatar-container img');
            
            avatarImages.forEach(function(img) {
                img.style.display = 'block';
                img.style.opacity = '1';
                img.style.visibility = 'visible';

                // Fallback handler
                img.onerror = function() {
                    console.log('Image failed to load, using fallback');
                    
                    const fallbackPath = '{{ asset('img/profile.png') }}';
                    
                    // Prevent infinite loop
                    if (this.src !== fallbackPath) {
                        this.src = fallbackPath;
                    } else {
                        // Show initials if fallback also fails
                        const container = this.closest('.avatar-container');
                        if (container) {
                            const userName = this.alt || 'User';
                            const initials = userName.split(' ')
                                .map(n => n[0])
                                .join('')
                                .toUpperCase()
                                .substring(0, 2);
                            
                            const placeholder = document.createElement('div');
                            placeholder.style.cssText = `
                                width: 100%;
                                height: 100%;
                                background: linear-gradient(135deg, #dc3545 0%, #bb2d3b 100%);
                                color: white;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-weight: 600;
                                font-size: 14px;
                                border-radius: 50%;
                            `;
                            placeholder.textContent = initials;
                            
                            container.innerHTML = '';
                            container.appendChild(placeholder);
                        }
                    }
                };
            });
        }

        // ========================================
        // SIDEBAR TOGGLE
        // ========================================
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

        function openSidebar() {
            const sidebar = document.querySelector('#sidebar');
            const mobileNavbar = document.querySelector('#mobile-navbar');
            const hamburger = document.querySelector('.mobile-menu-btn');

            if (sidebar) {
                sidebar.classList.add('show');

                if (hamburger) hamburger.classList.add('hide');
                if (mobileNavbar) mobileNavbar.classList.add('sidebar-open');

                sidebar.style.setProperty('left', '0px', 'important');
                document.body.style.overflow = 'hidden';

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

        function hideMobileElements() {
            const mobileNav = document.querySelector('#mobile-navbar');
            if (mobileNav) mobileNav.remove();
            const sidebar = document.querySelector('#sidebar');
            if (sidebar) sidebar.classList.remove('show');
            document.body.style.overflow = '';
        }

        // ========================================
        // CLICK OUTSIDE TO CLOSE
        // ========================================
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

        // ========================================
        // SWIPE GESTURES
        // ========================================
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

        // ========================================
        // KEYBOARD ACCESSIBILITY
        // ========================================
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const sidebar = document.querySelector('#sidebar');
                if (sidebar && sidebar.classList.contains('show')) {
                    closeSidebar();
                }
            }
        });

        // ========================================
        // RUN ON LOAD
        // ========================================
        window.addEventListener('load', function() {
            setTimeout(fixProfileImages, 500);
        });
    </script>

    @yield('scripts')
    @stack('scripts')
</body>
</html>