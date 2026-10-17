<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'JHSOFTERP' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark">J</span><span>JHSOFT<span class="brand-light">ERP</span></span></a>
            <div class="side-label">WORKSPACE</div>
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>⌂</span> Dashboard</a>
            <div class="side-label">DATA MASTER</div>
            @foreach (\App\Support\MasterDataCatalog::all() as $key => $master)
                <a class="nav-link {{ request()->route('master') === $key ? 'active' : '' }}" href="{{ route('master-data.index', $key) }}"><span>▦</span> {{ $master['label'] }}</a>
            @endforeach
            <a class="nav-link {{ request()->routeIs('master-prices.*') ? 'active' : '' }}" href="{{ route('master-prices.index') }}"><span>＄</span> Master Harga</a>
            <a class="nav-link {{ request()->routeIs('boms.*') ? 'active' : '' }}" href="{{ route('boms.index') }}"><span>◫</span> Bill of Materials</a>
            <div class="side-label">PENJUALAN</div>
            <a class="nav-link {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}" href="{{ route('sales-orders.index') }}"><span>▣</span> Sales Order</a>
            <div class="sidebar-foot"><span class="online-dot"></span> Sistem siap digunakan</div>
        </aside>
        <main class="main-panel">
            <header class="topbar">
                <div><div class="crumb">JHSOFTERP <span>/</span> Data Master</div><div class="page-title">{{ $title ?? 'Dashboard Master' }}</div></div>
                <div class="user-pill"><span class="avatar">A</span><span>Administrator</span></div>
            </header>
            <section class="content">
                @if (session('status'))
                    <div class="flash success">{{ session('status') }}</div>
                @endif
                @if ($errors->has('delete'))
                    <div class="flash error">{{ $errors->first('delete') }}</div>
                @endif
                @yield('content')
            </section>
        </main>
    </div>
</body>
</html>
