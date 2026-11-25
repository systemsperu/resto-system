@php
    $brand = app(\App\Branding\BrandSettings::class)->get();
    $brandPrimary = preg_match('/^#[0-9A-F]{6}$/', $brand->primaryColor) ? $brand->primaryColor : '#1F2937';
    $brandSecondary = preg_match('/^#[0-9A-F]{6}$/', $brand->secondaryColor) ? $brand->secondaryColor : '#F59E0B';
    $brandIcon = $brand->iconPath ?? $brand->logoPath;
    $brandFavicon = $brand->faviconPath ?? $brandIcon;
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $brand->companyName }}</title>
    <meta name="theme-color" content="{{ $brandPrimary }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="apple-touch-icon" href="{{ route('pwa.icon', ['size' => 192]) }}">
    @if($brandFavicon)
        <link rel="icon" href="{{ asset('storage/'.$brandFavicon) }}">
    @endif
    
    @vite(['resources/css/app.scss', 'resources/js/app.js'])

    <style>
        /* =============================================
           VARIABLES — TEMA PÚRPURA / NARANJA
        ============================================= */
        :root {
            --brand-primary:    {{ $brandPrimary }};
            --brand-secondary:  {{ $brandSecondary }};
            --sidebar-bg:       var(--brand-primary);
            --sidebar-hover:    rgba(255,255,255,0.10);
            --sidebar-active:   var(--brand-secondary);
            --sidebar-width:    260px;

            --primary:          var(--brand-primary);
            --primary-hover:    color-mix(in srgb, var(--brand-primary) 85%, black);
            --accent-pink:      #ff4d7e;

            --body-bg:          #dcdcf0;
            --card-bg:          #ffffff;
            --text-main:        #1e1e2e;
            --text-muted:       #6c6c90;

            --radius-xl:        20px;
            --radius-md:        14px;
            --radius-sm:        10px;

            --shadow-soft:      0 8px 32px rgba(45,27,94,0.10);
            --shadow-card:      0 4px 18px rgba(45,27,94,0.08);
        }

        /* =============================================
           BASE
        ============================================= */
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--body-bg);
            color: var(--text-main);
            font-size: 0.92rem;
            overflow-x: hidden;
            margin: 0;
        }

        /* =============================================
           SIDEBAR
        ============================================= */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            background: var(--sidebar-bg);
            color: white;
            z-index: 1050;
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* --- Logo / Brand --- */
        .sidebar-brand {
            padding: 24px 20px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }

        .logo-box {
            width: 40px; height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(255,140,0,0.45);
        }

        .brand-name {
            font-weight: 700;
            font-size: 15px;
            letter-spacing: -0.3px;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* --- User Profile Block --- */
        .sidebar-user {
            padding: 22px 20px 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            flex-shrink: 0;
        }

        .sidebar-avatar-wrap {
            position: relative;
            display: inline-block;
        }

        .sidebar-avatar {
            width: 68px; height: 68px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            font-weight: 700;
            box-shadow: 0 6px 20px rgba(255,140,0,0.40);
            border: 3px solid rgba(255,255,255,0.15);
        }

        .sidebar-avatar-dot {
            width: 14px; height: 14px;
            background: var(--accent-pink);
            border-radius: 50%;
            border: 2px solid var(--sidebar-bg);
            position: absolute;
            bottom: 2px; right: 2px;
        }

        .sidebar-username {
            color: rgba(255,255,255,0.92);
            font-size: 0.82rem;
            font-weight: 500;
            text-align: center;
        }

        /* --- Navigation Menu --- */
        .sidebar-menu {
            padding: 16px 12px;
            flex-grow: 1;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.15) transparent;
        }

        .sidebar-menu::-webkit-scrollbar { width: 4px; }
        .sidebar-menu::-webkit-scrollbar-track { background: transparent; }
        .sidebar-menu::-webkit-scrollbar-thumb {
            background-color: rgba(255,255,255,0.18);
            border-radius: 20px;
        }

        .menu-category {
            color: rgba(255,255,255,0.35);
            font-size: 0.67rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1.2px;
            margin: 18px 8px 8px;
        }

        .nav-link {
            color: rgba(255,255,255,0.70);
            padding: 11px 14px;
            border-radius: var(--radius-sm);
            margin-bottom: 3px;
            font-weight: 500;
            font-size: 0.88rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .nav-link i {
            font-size: 1.15rem;
            opacity: 0.75;
            transition: opacity 0.2s;
            flex-shrink: 0;
        }

        .nav-link:hover {
            background: var(--sidebar-hover);
            color: white;
        }

        .nav-link.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 6px 18px rgba(255,140,0,0.35);
        }

        .nav-link.active i { opacity: 1; }

        .nav-group { margin-bottom: 3px; }
        .nav-group > summary { list-style: none; cursor: pointer; }
        .nav-group > summary::-webkit-details-marker { display: none; }
        .nav-group > summary .nav-group-chevron { margin-left: auto; font-size: .8rem; transition: transform .2s ease; }
        .nav-group[open] > summary .nav-group-chevron { transform: rotate(90deg); }
        .nav-group[open] > summary { color: white; background: var(--sidebar-hover); }
        .nav-group > summary.active { background: var(--primary); box-shadow: 0 6px 18px rgba(255,140,0,.35); }
        .nav-submenu {
            margin: 2px 0 8px 25px;
            padding: 3px 0 3px 9px;
            border-left: 1px solid rgba(255,255,255,.18);
        }
        .nav-submenu .nav-link { padding: 9px 10px; font-size: .82rem; gap: 9px; }
        .nav-submenu .nav-link i { font-size: .95rem; }

        /* =============================================
           MAIN CONTENT
        ============================================= */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 24px 28px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s;
        }

        /* POS page: sin padding para aprovechar 100% de altura */
        body.pos-page .main-content {
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }

        /* En el POS el aviso no debe cubrir las acciones de cobro. */
        body.pos-page [data-network-status] {
            right: 300px !important;
        }

        /* =============================================
           TOPBAR
        ============================================= */
        .top-navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 14px;
        }

        .topbar-title h4 {
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
            line-height: 1.2;
        }

        .topbar-title p {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin: 3px 0 0;
        }

        .topbar-search {
            position: relative;
        }

        .topbar-search input {
            background: white;
            border: none;
            border-radius: 50px;
            padding: 9px 18px 9px 42px;
            font-size: 0.85rem;
            color: var(--text-main);
            width: 220px;
            box-shadow: var(--shadow-card);
            outline: none;
            transition: box-shadow 0.2s, width 0.3s;
        }

        .topbar-search input:focus {
            box-shadow: 0 0 0 3px rgba(255,140,0,0.18), var(--shadow-card);
            width: 270px;
        }

        .topbar-search i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1rem;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar-user-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            border: none;
            border-radius: 50px;
            padding: 6px 14px 6px 6px;
            cursor: pointer;
            box-shadow: var(--shadow-card);
            transition: box-shadow 0.2s;
        }

        .topbar-user-btn:hover {
            box-shadow: 0 6px 20px rgba(45,27,94,0.14);
        }

        .topbar-avatar {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--primary), #ff6b35);
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        /* =============================================
           CARDS
        ============================================= */
        .card {
            border: none;
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            overflow: hidden;
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid #f0eef8;
            padding: 1.1rem 1.4rem;
        }

        .card-body { padding: 1.4rem; }

        /* Orange Stat Cards */
        .card-orange {
            background: var(--primary) !important;
            color: white !important;
            border-radius: var(--radius-xl);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(255,140,0,0.35);
        }

        .card-orange .card-body { padding: 1.2rem 1.4rem; }

        .card-orange h6 {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            opacity: 0.9;
            margin-bottom: 6px;
        }

        .card-orange h2 {
            font-size: 2rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -1px;
        }

        .card-orange small {
            font-size: 0.72rem;
            opacity: 0.8;
        }

        .card-orange .icon-bg {
            position: absolute;
            right: -8px; top: 50%;
            transform: translateY(-50%);
            font-size: 5.5rem;
            opacity: 0.12;
            pointer-events: none;
        }

        .card-orange .card-minus {
            position: absolute;
            top: 10px; right: 12px;
            background: rgba(255,255,255,0.25);
            border: none;
            color: white;
            width: 22px; height: 22px;
            border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            font-weight: 700;
            cursor: default;
        }

        /* White stat cards */
        .card-stat-white {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            overflow: hidden;
        }

        /* Buttons */
        .btn { padding: 0.55rem 1.3rem; border-radius: 50px; font-weight: 600; border: none; font-size: 0.85rem; }
        .btn-primary { background: var(--primary); box-shadow: 0 4px 14px rgba(255,140,0,0.3); color: white; }
        .btn-primary:hover { background: var(--primary-hover); color: white; }
        .btn-success { background: #22c55e; color: white; }
        .btn-success:hover { background: #16a34a; color: white; }
        .btn-danger { background: var(--accent-pink); color: white; }
        .btn-danger:hover { background: #e03060; color: white; }
        .btn-outline-secondary { border: 1.5px solid #e0ddf0; color: var(--text-muted); background: white; }
        .btn-outline-secondary:hover { background: var(--body-bg); border-color: #c5c0e0; color: var(--text-main); }

        /* Forms */
        .form-control, .form-select {
            background-color: #f7f5fd;
            border: 1px solid #e8e4f4;
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255,140,0,0.15);
            background: white;
        }

        /* Alerts */
        .alert { border-radius: var(--radius-md); }

        /* Tables */
        .table { --bs-table-hover-bg: #faf8ff; }
        .table th { font-weight: 700; color: var(--text-muted); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Badges */
        .badge { font-weight: 600; border-radius: 50px; }

        /* Scrollbar global */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(45,27,94,0.15); border-radius: 10px; }

        /* =============================================
           MOBILE RESPONSIVE
        ============================================= */
        .mobile-overlay {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(20,10,45,0.75);
            z-index: 1040;
            backdrop-filter: blur(4px);
            display: none;
        }

        .mobile-overlay.show { display: block; }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); width: min(280px, calc(100vw - 32px)); height: 100dvh; }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
            .topbar-search input { width: 160px; }
            .topbar-search input:focus { width: 200px; }
            .mobile-menu-button, .mobile-menu-close { min-width: 48px; min-height: 48px; display: inline-flex; align-items: center; justify-content: center; }
            body.pos-page [data-network-status] { right: max(12px, env(safe-area-inset-right)) !important; }
        }

        @media (max-width: 575px) {
            .main-content { padding: 12px; }
            .top-navbar { align-items: flex-start; }
            .top-navbar > :first-child { min-width: 0; }
            .topbar-title { min-width: 0; }
            .topbar-title h4 { font-size: 1.15rem; overflow-wrap: anywhere; }
            .topbar-right { width: 100%; justify-content: space-between; }
            .topbar-search { flex: 1 1 auto; min-width: 0; }
            .topbar-search input, .topbar-search input:focus { width: 100%; min-height: 48px; }
            .topbar-user-btn { min-width: 48px; min-height: 48px; justify-content: center; padding: 6px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .sidebar, .main-content, .nav-link, .btn, .topbar-search input { transition: none !important; }
        }
    </style>
</head>
<body class="{{ request()->routeIs('pos.order') ? 'pos-page' : '' }}">

<div class="mobile-overlay" id="mobileOverlay" onclick="closeMenu()"></div>

<!-- ===================== SIDEBAR ===================== -->
<div class="sidebar" id="sidebar" tabindex="-1">

    {{-- Brand --}}
    <div class="sidebar-brand">
        @if($brandIcon)
            <img src="{{ asset('storage/'.$brandIcon) }}" style="width:40px;height:40px;object-fit:cover;border-radius:12px;" alt="Icono de {{ $brand->companyName }}">
        @else
            <div class="logo-box"><i class="bi bi-shop"></i></div>
        @endif
        <div class="brand-name">{{ $brand->companyName }}</div>
        <button type="button" class="btn btn-sm text-white-50 d-lg-none ms-auto p-0 mobile-menu-close" onclick="closeMenu()" aria-label="Cerrar menú"><i class="bi bi-x-lg fs-5" aria-hidden="true"></i></button>
    </div>

    {{-- User Profile --}}
    <div class="sidebar-user">
        <div class="sidebar-avatar-wrap">
            <div class="sidebar-avatar">
                {{ substr(Auth::user()->name, 0, 1) }}
            </div>
            <div class="sidebar-avatar-dot"></div>
        </div>
        <div class="sidebar-username">{{ Auth::user()->name }}</div>
    </div>

    {{-- Navigation --}}
    <div class="sidebar-menu">
        @php
            $role = Auth::user()->role;
            $moduleManager = app(\App\Modules\ModuleManager::class);
            $restaurantEnabled = $moduleManager->isEnabled('restaurant');
            $inventoryEnabled = $moduleManager->isEnabled('inventory');
            $reservationsEnabled = $moduleManager->isEnabled('reservations');
            $deliveryEnabled = $moduleManager->isEnabled('delivery');
            $reportsEnabled = $moduleManager->isEnabled('reports');
            $sunatEnabled = $moduleManager->isEnabled('sunat');
            $marketingEnabled = $moduleManager->isEnabled('marketing');
            $landingEnabled = $moduleManager->isEnabled('landing');
            $lodgingEnabled = $moduleManager->isEnabled('lodging');
        @endphp

        @if(in_array($role, ['admin', 'cashier']))
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
        @endif

        @if($role === 'admin' && $reportsEnabled)
            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line-fill"></i> Reportes
            </a>
        @endif

        @if($restaurantEnabled || $deliveryEnabled || $reservationsEnabled || $lodgingEnabled)
        <div class="menu-category">Operaciones</div>
        @endif

        @if($restaurantEnabled && Auth::user()->hasPermission('pos.manage'))
        @php($posMenuActive = request()->routeIs('pos.*', 'delivery.*', 'sales.*', 'daily_summaries.*'))
        <details class="nav-group" @if($posMenuActive) open @endif>
            <summary class="nav-link {{ $posMenuActive ? 'active' : '' }}"><i class="bi bi-bag-check-fill"></i><span>POS RES</span><i class="bi bi-chevron-right nav-group-chevron"></i></summary>
            <div class="nav-submenu">
                <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.*') ? 'active' : '' }}"><i class="bi bi-grid-3x3-gap"></i> Salón / POS</a>
                @if($deliveryEnabled && in_array($role, ['admin', 'cashier']))
                <a href="{{ route('delivery.index') }}" class="nav-link {{ request()->routeIs('delivery.*') ? 'active' : '' }}"><i class="bi bi-bicycle"></i> Delivery</a>
                @endif
                @if(in_array($role, ['admin', 'cashier']))
                <a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}"><i class="bi bi-receipt"></i> Historial de ventas</a>
                @endif
                @if($role === 'admin' && $sunatEnabled)
                <a href="{{ route('daily_summaries.index') }}" class="nav-link {{ request()->routeIs('daily_summaries.*') ? 'active' : '' }}"><i class="bi bi-calendar-week"></i> Resumen diario de BV</a>
                @endif
            </div>
        </details>
        <a href="{{ route('table-order.pending') }}" class="nav-link {{ request()->routeIs('table-order.pending') ? 'active' : '' }}"><i class="bi bi-phone-fill"></i> Solicitudes QR</a>
        @endif

        @if($reservationsEnabled && Auth::user()->hasPermission('reservations.manage'))
        <a href="{{ route('reservations.index') }}" class="nav-link {{ request()->routeIs('reservations.*') ? 'active' : '' }}"><i class="bi bi-calendar-event-fill"></i> Reservas</a>
        @endif
        @if($lodgingEnabled && Auth::user()->hasPermission('lodging.view'))
        <a href="{{ route('lodging.index') }}" class="nav-link {{ request()->routeIs('lodging.*') ? 'active' : '' }}"><i class="bi bi-houses-fill"></i> Hospedaje</a>
        @endif

        @if($role === 'admin' && $sunatEnabled)
        @php($billingMenuActive = request()->routeIs('billing.*', 'credit_notes.*', 'daily_summaries.*'))
        <details class="nav-group" @if($billingMenuActive) open @endif>
            <summary class="nav-link {{ $billingMenuActive ? 'active' : '' }}"><i class="bi bi-receipt-cutoff"></i><span>Facturación electrónica</span><i class="bi bi-chevron-right nav-group-chevron"></i></summary>
            <div class="nav-submenu">
                <a href="{{ route('billing.index') }}" class="nav-link {{ request()->routeIs('billing.*') ? 'active' : '' }}"><i class="bi bi-receipt"></i> Comprobantes</a>
                <a href="{{ route('credit_notes.index') }}" class="nav-link {{ request()->routeIs('credit_notes.*') ? 'active' : '' }}"><i class="bi bi-arrow-counterclockwise"></i> Notas de crédito</a>
                <a href="{{ route('daily_summaries.index') }}" class="nav-link {{ request()->routeIs('daily_summaries.*') ? 'active' : '' }}"><i class="bi bi-calendar-week"></i> Resumen diario</a>
            </div>
        </details>
        @endif

        @if($restaurantEnabled && in_array($role, ['admin', 'cashier']))
        @php($cashMenuActive = request()->routeIs('cash_registers.*'))
        <details class="nav-group" @if($cashMenuActive) open @endif>
            <summary class="nav-link {{ $cashMenuActive ? 'active' : '' }}"><i class="bi bi-cash-stack"></i><span>Caja / Arqueo</span><i class="bi bi-chevron-right nav-group-chevron"></i></summary>
            <div class="nav-submenu">
                <a href="{{ route('cash_registers.create') }}" class="nav-link {{ request()->routeIs('cash_registers.create') ? 'active' : '' }}"><i class="bi bi-box-arrow-in-right"></i> Aperturar caja</a>
                <a href="{{ route('cash_registers.close') }}" class="nav-link {{ request()->routeIs('cash_registers.close') ? 'active' : '' }}"><i class="bi bi-box-arrow-left"></i> Cerrar caja</a>
                @if($role === 'admin')<a href="{{ route('cash_registers.index') }}" class="nav-link {{ request()->routeIs('cash_registers.index') ? 'active' : '' }}"><i class="bi bi-clock-history"></i> Historial de turnos</a>@endif
            </div>
        </details>
        @endif

        @if($restaurantEnabled && Auth::user()->hasPermission('kitchen.view'))
        <a href="{{ route('kitchen.index') }}" class="nav-link {{ request()->routeIs('kitchen.*') ? 'active' : '' }}"><i class="bi bi-fire"></i> Cocina (KDS)</a>
        @endif

        @if($role === 'admin')
        @php($managementMenuActive = request()->routeIs('clients.*', 'marketing.*', 'categories.*', 'products.*', 'inventory.*'))
        <details class="nav-group" @if($managementMenuActive) open @endif>
            <summary class="nav-link {{ $managementMenuActive ? 'active' : '' }}"><i class="bi bi-briefcase-fill"></i><span>Gestión</span><i class="bi bi-chevron-right nav-group-chevron"></i></summary>
            <div class="nav-submenu">
                <a href="{{ route('clients.index') }}" class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}"><i class="bi bi-people-fill"></i> Clientes</a>
                @if($marketingEnabled)<a href="{{ route('marketing.index') }}" class="nav-link {{ request()->routeIs('marketing.*') ? 'active' : '' }}"><i class="bi bi-megaphone-fill"></i> Marketing</a>@endif
                @if($restaurantEnabled)<a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"><i class="bi bi-tags-fill"></i> Categorías</a>@endif
                @if($inventoryEnabled)<a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*', 'inventory.*') ? 'active' : '' }}"><i class="bi bi-box-seam-fill"></i> Inventario</a>@endif
            </div>
        </details>

        @if($restaurantEnabled)
        <a href="{{ route('menu.index') }}" target="_blank" class="nav-link text-warning"><i class="bi bi-qr-code-scan"></i> Carta digital <i class="bi bi-box-arrow-up-right ms-auto" style="font-size:.75rem"></i></a>
        @php($tablesMenuActive = request()->routeIs('tables.*'))
        <details class="nav-group" @if($tablesMenuActive) open @endif>
            <summary class="nav-link {{ $tablesMenuActive ? 'active' : '' }}"><i class="bi bi-grid-3x3-gap-fill"></i><span>Mesas</span><i class="bi bi-chevron-right nav-group-chevron"></i></summary>
            <div class="nav-submenu"><a href="{{ route('tables.index') }}" class="nav-link {{ $tablesMenuActive ? 'active' : '' }}"><i class="bi bi-sliders"></i> Configurar</a></div>
        </details>
        @endif

        @php($staffMenuActive = request()->routeIs('users.*', 'employees.*', 'work_shifts.*'))
        <details class="nav-group" @if($staffMenuActive) open @endif>
            <summary class="nav-link {{ $staffMenuActive ? 'active' : '' }}"><i class="bi bi-person-badge-fill"></i><span>Personal / Usuarios</span><i class="bi bi-chevron-right nav-group-chevron"></i></summary>
            <div class="nav-submenu">
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"><i class="bi bi-person-gear"></i> Usuarios</a>
                <a href="{{ route('employees.index') }}" class="nav-link {{ request()->routeIs('employees.*', 'work_shifts.*') ? 'active' : '' }}"><i class="bi bi-calendar2-week-fill"></i> Personal y turnos</a>
            </div>
        </details>

        @php($settingsMenuActive = request()->routeIs('settings.*', 'modules.*', 'site.admin.*'))
        <details class="nav-group" @if($settingsMenuActive) open @endif>
            <summary class="nav-link {{ $settingsMenuActive ? 'active' : '' }}"><i class="bi bi-gear-fill"></i><span>Configuración</span><i class="bi bi-chevron-right nav-group-chevron"></i></summary>
            <div class="nav-submenu">
                <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}"><i class="bi bi-gear"></i> Configuración</a>
                <a href="{{ route('modules.index') }}" class="nav-link {{ request()->routeIs('modules.*') ? 'active' : '' }}"><i class="bi bi-toggles"></i> Módulos</a>
                @if($landingEnabled)
                <a href="{{ route('site.admin.index') }}" class="nav-link {{ request()->routeIs('site.admin.*') && !request()->routeIs('site.admin.analytics') ? 'active' : '' }}"><i class="bi bi-window"></i> Sitio web</a>
                <a href="{{ route('site.admin.analytics') }}" class="nav-link {{ request()->routeIs('site.admin.analytics') ? 'active' : '' }}"><i class="bi bi-graph-up"></i> Analítica web</a>
                @endif
            </div>
        </details>

        <a href="{{ route('system.index') }}" class="nav-link {{ request()->routeIs('system.*') ? 'active' : '' }} text-warning"><i class="bi bi-tools"></i> Mantenimiento</a>
        <a href="{{ route('sync.index') }}" class="nav-link {{ request()->routeIs('sync.*') ? 'active' : '' }}"><i class="bi bi-cloud-arrow-up-fill"></i> Local y nube</a>
        @endif
    </div>
</div>

<!-- ===================== MAIN CONTENT ===================== -->
<div class="main-content">

    @if(!request()->routeIs('pos.order'))
    <div class="top-navbar">

        {{-- Left: Title --}}
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn btn-light border d-lg-none p-0 mobile-menu-button" onclick="openMenu()" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">
                <i class="bi bi-list fs-5" aria-hidden="true"></i>
            </button>
            <div class="topbar-title">
                <h4>Bienvenido de vuelta 👋</h4>
                <p>{{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</p>
            </div>
        </div>

        {{-- Right: Search + User --}}
        <div class="topbar-right">
            <div class="topbar-search">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Buscar...">
            </div>

            <div class="dropdown">
                <div class="topbar-user-btn" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="topbar-avatar">{{ substr(Auth::user()->name, 0, 1) }}</div>
                    <span class="fw-semibold text-dark small d-none d-sm-inline">{{ Auth::user()->name }}</span>
                    <i class="bi bi-chevron-down text-muted small"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2 rounded-4" style="width:210px;">
                    <li class="px-2 py-1 text-muted small fw-bold">MI CUENTA</li>
                    <li>
                        <button class="dropdown-item rounded-3 mb-1" data-bs-toggle="modal" data-bs-target="#profileModal">
                            <i class="bi bi-person-gear me-2 text-primary"></i> Editar Perfil
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item rounded-3 text-danger fw-bold">
                                <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    @endif

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center" style="background:#f0fdf4;border-left:4px solid #22c55e!important;">
            <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
            <div><strong>¡Éxito!</strong> {{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Cerrar aviso"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center" style="background:#fff1f2;border-left:4px solid #f43f5e!important;">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
            <div><strong>Error:</strong> {{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Cerrar aviso"></button>
        </div>
    @endif

    @yield('content')
</div>

<!-- ===================== PROFILE MODAL ===================== -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Mi Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar perfil"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-muted small mb-3">Actualiza tus datos de acceso.</p>
                <form action="{{ route('users.update', Auth::user()->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="profile_name" class="form-label fw-bold small">Nombre</label>
                        <input id="profile_name" type="text" name="name" class="form-control" value="{{ Auth::user()->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="profile_email" class="form-label fw-bold small">Correo (Solo lectura)</label>
                        <input id="profile_email" type="email" class="form-control bg-light" value="{{ Auth::user()->email }}" readonly>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label for="profile_password" class="form-label fw-bold small">Nueva Contraseña (Opcional)</label>
                        <input id="profile_password" type="password" name="password" class="form-control" placeholder="Dejar en blanco para no cambiar">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary fw-bold">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openMenu() {
        document.getElementById('sidebar').classList.add('show');
        document.getElementById('mobileOverlay').classList.add('show');
        document.querySelector('.mobile-menu-button')?.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        document.querySelector('.mobile-menu-close')?.focus();
    }

    function closeMenu() {
        document.getElementById('sidebar').classList.remove('show');
        document.getElementById('mobileOverlay').classList.remove('show');
        document.querySelector('.mobile-menu-button')?.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        document.querySelector('.mobile-menu-button')?.focus();
    }

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && document.getElementById('sidebar').classList.contains('show')) {
            closeMenu();
        }
    });

    // Auto-dismiss alerts
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => { el.style.transition='opacity .5s'; el.style.opacity='0'; setTimeout(()=>el.remove(),500); }, 5000);
    });
</script>

@stack('scripts')
<div data-network-status data-state="checking" role="status" aria-live="polite" style="position:fixed;right:max(12px,env(safe-area-inset-right));bottom:max(12px,env(safe-area-inset-bottom));z-index:1090;background:#fff;border:1px solid #d1d5db;border-radius:999px;padding:8px 14px;font-size:.8rem;box-shadow:0 4px 18px #0002">Comprobando conexión…</div>
<div id="pwa-update" class="alert alert-info" style="display:none;position:fixed;left:50%;bottom:70px;transform:translateX(-50%);z-index:1090;max-width:min(520px,calc(100% - 24px));width:100%"><strong>Actualización disponible.</strong> Termina la operación actual antes de aplicarla. <button id="pwa-update-button" class="btn btn-primary ms-2">Actualizar</button></div>
<button id="pwa-install" class="btn btn-primary" style="display:none;position:fixed;left:max(12px,env(safe-area-inset-left));bottom:max(12px,env(safe-area-inset-bottom));z-index:1090;min-height:48px">Instalar aplicación</button>
<script>
let installPrompt; window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();installPrompt=event;document.getElementById('pwa-install').style.display='block'});document.getElementById('pwa-install')?.addEventListener('click',async()=>{await installPrompt?.prompt();installPrompt=null;document.getElementById('pwa-install').style.display='none'});document.addEventListener('pwa:update',event=>{const box=document.getElementById('pwa-update');box.style.display='block';document.getElementById('pwa-update-button').onclick=()=>event.detail.waiting?.postMessage({type:'SKIP_WAITING'})});navigator.serviceWorker?.addEventListener('controllerchange',()=>location.reload());
</script>
</body>
</html>
