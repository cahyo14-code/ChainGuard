<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ChainGuard — @yield('title', 'Global Supply Chain Risk Monitor')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @stack('styles')
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <div style="width:36px; height:36px; background:var(--accent); border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-link" style="color:white; font-size:16px;"></i>
            </div>
            <div>
                <h4>ChainGuard</h4>
                <span>Supply Chain Monitor</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <p class="nav-section-title">Main Menu</p>

            <div class="nav-item-custom">
                <a href="{{ route('dashboard') }}" class="nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>

            <div class="nav-item-custom">
                <a href="{{ route('countries.index') }}" class="nav-link-custom {{ request()->routeIs('countries.index') || request()->routeIs('countries.show') ? 'active' : '' }}">
                    <i class="fas fa-globe"></i> Global Countries
                </a>
            </div>

            <div class="nav-item-custom">
                <a href="{{ route('risk.index') }}" class="nav-link-custom {{ request()->routeIs('risk.*') ? 'active' : '' }}">
                    <i class="fas fa-exclamation-triangle"></i> Risk Scoring
                </a>
            </div>

            <p class="nav-section-title">Monitoring</p>

            <div class="nav-item-custom">
                <a href="{{ route('weather.index') }}" class="nav-link-custom {{ request()->routeIs('weather.*') ? 'active' : '' }}">
                    <i class="fas fa-cloud-sun"></i> Weather Monitor
                </a>
            </div>

            <div class="nav-item-custom">
                <a href="{{ route('currency.index') }}" class="nav-link-custom {{ request()->routeIs('currency.*') ? 'active' : '' }}">
                    <i class="fas fa-dollar-sign"></i> Currency Impact
                </a>
            </div>

            <div class="nav-item-custom">
                <a href="{{ route('news.index') }}" class="nav-link-custom {{ request()->routeIs('news.*') ? 'active' : '' }}">
                    <i class="fas fa-newspaper"></i> News Intelligence
                </a>
            </div>

            <div class="nav-item-custom">
                <a href="{{ route('ports.index') }}" class="nav-link-custom {{ request()->routeIs('ports.*') ? 'active' : '' }}">
                    <i class="fas fa-anchor"></i> Port Locations
                </a>
            </div>

            <p class="nav-section-title">Tools</p>

            <div class="nav-item-custom">
                <a href="{{ route('watchlist.index') }}" class="nav-link-custom {{ request()->routeIs('watchlist.*') ? 'active' : '' }}">
                    <i class="fas fa-star"></i> Watchlist
                </a>
            </div>

            <div class="nav-item-custom">
                <a href="{{ route('countries.compare') }}" class="nav-link-custom {{ request()->routeIs('countries.compare') ? 'active' : '' }}">
                    <i class="fas fa-balance-scale"></i> Compare Countries
                </a>
            </div>

            @if(auth()->user())
            <p class="nav-section-title">Admin</p>
            <div class="nav-item-custom">
                <a href="{{ route('admin.index') }}" class="nav-link-custom {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i> Admin Panel
                </a>
            </div>
            @endif
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Topbar -->
        <div class="topbar">
            <span class="topbar-title">@yield('page-title', 'Dashboard')</span>
            <div class="d-flex align-items-center gap-3">
                <!-- System status -->
                <div style="display:flex; align-items:center; gap:6px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:20px; padding:5px 12px; font-size:12px; color:#15803d;">
                    <span style="width:7px; height:7px; background:#22c55e; border-radius:50%; display:inline-block;"></span>
                    System Online
                </div>

                <!-- User info -->
                <div style="display:flex; align-items:center; gap:8px;">
                    <div style="width:32px; height:32px; background:var(--accent-soft); border-radius:50%; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-user" style="color:var(--accent); font-size:13px;"></i>
                    </div>
                    <span style="font-size:13px; font-weight:500; color:var(--text-primary);">
                        {{ auth()->user()->name ?? 'Guest' }}
                    </span>
                </div>

                <!-- Logout -->
                <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" style="background:none; border:1px solid var(--border-color); color:var(--text-secondary); font-size:12px; cursor:pointer; padding:6px 12px; border-radius:8px; transition:all 0.2s; font-weight:500;"
                        onmouseover="this.style.borderColor='var(--danger)'; this.style.color='var(--danger)'"
                        onmouseout="this.style.borderColor='var(--border-color)'; this.style.color='var(--text-secondary)'">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- Content -->
        <div class="content-area">
            @yield('content')
        </div>

    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @stack('scripts')
</body>
</html>
