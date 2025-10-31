<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>RLEGS – TREG-3 Dashboard</title>

  <!-- Icons & CSS -->
  <link href="https://cdn.lineicons.com/5.0/lineicons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css">
  <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.css">

  <link rel="stylesheet" href="{{ asset('css/sidebarpage.css') }}">
  <link rel="stylesheet" href="{{ asset('css/overview.css') }}">
  @yield('styles')

  {{-- Tailwind + Inertia --}}
  @viteReactRefresh
  @vite(['resources/css/app.css','resources/js/app.jsx'])
  @inertiaHead

  <!-- JS -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="antialiased text-gray-900">
  {{-- ===== Sidebar ===== --}}
  <aside id="sidebar">
    <div class="d-flex align-items-center">
      <button id="toggle-btn" type="button">
        <img src="{{ asset('img/twiyh.png') }}" class="avatar rounded-circle" alt="Logo" width="35" height="35" style="margin-left: 1px">
      </button>
      <div class="sidebar-logo ms-2">
        <a href="#" class="brand-name">RLEGS</a>
      </div>
    </div>

    <ul class="sidebar-nav">
      <li class="sidebar-item">
        <a href="{{ route('dashboard') }}" class="sidebar-link">
          <i class="lni lni-dashboard-square-1"></i><span>Overview Data</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="{{ route('revenue.data') }}" class="sidebar-link">
          <i class="lni lni-file-pencil"></i><span>Data Revenue</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="{{ route('dashboard.treg3') }}" class="sidebar-link">
          <i class="lni lni-buildings-1"></i><span>CC & Witel</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="{{ route('leaderboard') }}" class="sidebar-link">
          <i class="lni lni-hierarchy-1"></i><span>Leaderboard AM</span>
        </a>
      </li>
      {{-- <li class="sidebar-item">
        <a href="{{ route('monitoring-LOP') }}" class="sidebar-link">
          <i class="lni lni-user-multiple-4"></i><span>Top LOP</span>
        </a>
      </li> --}}
      <li class="sidebar-item">
        <a href="{{ route('file-management.index') }}" class="sidebar-link">
          <i class="lni lni-cloud-upload"></i><span>File Management</span>
        </a>
      </li>
    </ul>

    <div class="sidebar-footer">
      <a href="{{ route('profile.index') }}" class="sidebar-link">
        <i class="lni lni-gear-1"></i><span>Settings</span>
      </a>
    </div>
    <div class="sidebar-footer">
      <a href="{{ route('logout') }}" class="sidebar-link">
        <i class="lni lni-exit"></i><span>Logout</span>
      </a>
    </div>
  </aside>

  {{-- ===== Content column (offset by fixed rail) ===== --}}
  <div id="content-wrapper" class="flex min-h-screen flex-col bg-gray-50 pt-4">

    {{-- Top navbar (fixed) --}}
    <header id="top">
      <div class="topbar">
        <div class="container-fluid d-flex align-items-center px-4">
          <!-- Left: sidebar toggle -->
          <button class="navbar-toggler me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
            <span class="navbar-toggler-icon"></span>
          </button>

          <!-- Center: quick links with consistent spacing -->
          <nav class="flex-fill">
            <ul class="navbar-nav flex-row gap-3 mb-0 d-none d-md-flex">
              <li class="nav-item">
                <a href="#trend-revenue" class="nav-link d-flex align-items-center px-3 py-2">
                  <i class="lni lni-bar-chart-4 me-2"></i><span>Trend Revenue</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="#revenue-performance" class="nav-link d-flex align-items-center px-3 py-2">
                  <i class="lni lni-dollar-circle me-2"></i><span>Revenue Performance</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="#witel-performance" class="nav-link d-flex align-items-center px-3 py-2">
                  <i class="lni lni-buildings-1 me-2"></i><span>Witel Performance</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="#top-customers" class="nav-link d-flex align-items-center px-3 py-2">
                  <i class="lni lni-trophy-1 me-2"></i><span>Top Customers</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="#division-overview" class="nav-link d-flex align-items-center px-3 py-2">
                  <i class="lni lni-pie-chart-2 me-2"></i><span>Division Overview</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="#cc-performance" class="nav-link d-flex align-items-center px-3 py-2">
                  <i class="lni lni-search-2 me-2"></i><span>CC Performance</span>
                </a>
              </li>
            </ul>
          </nav>

          <!-- Right: profile with same padding -->
          <nav class="ms-auto">
            <ul class="navbar-nav align-items-center">
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center px-5 py-2" href="#" id="navbarDropdown"
                   role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <div class="avatar-container me-2">
                    @auth
                      @if(Auth::user()->profile_image)
                        <img src="{{ asset('storage/' . Auth::user()->profile_image) }}"
                             class="rounded-circle" width="32" height="32" alt="{{ Auth::user()->name }}">
                      @else
                        <img src="{{ asset('img/profile.png') }}"
                             class="rounded-circle" width="32" height="32" alt="Default Profile">
                      @endif
                    @endauth
                  </div>
                  <span class="fw-medium">
                    @auth {{ Auth::user()->name }} @endauth
                  </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                  <li><a class="dropdown-item" href="{{ route('profile.index') }}">{{ __('Settings') }}</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                      @csrf
                      <button type="submit" class="dropdown-item text-danger">{{ __('Log Out') }}</button>
                    </form>
                  </li>
                </ul>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </header>

    {{-- Inertia page --}}
    <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
      @inertia
    </main>
  </div>

  <script src="{{ asset('sidebar/script.js') }}"></script>
</body>
</html>