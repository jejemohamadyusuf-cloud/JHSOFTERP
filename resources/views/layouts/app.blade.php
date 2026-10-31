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
            <button class="menu-toggle" type="button" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="primary-navigation">
                <span></span><span></span><span></span>
            </button>
            <nav class="sidebar-menu" id="primary-navigation" aria-label="Navigasi utama">
                <div class="side-label">WORKSPACE</div>
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>⌂</span> Dashboard</a>
                @if (auth()->user()->hasRole('admin'))
                    <div class="side-label">ADMINISTRASI</div>
                    <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><span>♙</span> Pengaturan Pengguna & Hak Akses</a>
                    <a class="nav-link {{ request()->routeIs('pic-guides.*') ? 'active' : '' }}" href="{{ route('pic-guides.index') }}"><span>☷</span> Pengaturan PIC</a>
                @endif
                @if (auth()->user()->canAccess('master-data') || auth()->user()->canAccess('master-prices'))
                    <div class="side-label">DATA MASTER</div>
                    @if (auth()->user()->canAccess('master-data'))
                        @foreach (\App\Support\MasterDataCatalog::all() as $key => $master)
                            @if (($key !== 'chart-of-accounts' || auth()->user()->canAccess('finance')) && (auth()->user()->role !== 'finance' || $key === 'chart-of-accounts'))
                                <a class="nav-link {{ request()->route('master') === $key ? 'active' : '' }}" href="{{ route('master-data.index', $key) }}"><span>▦</span> {{ $master['label'] }}</a>
                            @endif
                        @endforeach
                    @endif
                    @if (auth()->user()->canAccess('master-prices'))
                        <a class="nav-link {{ request()->route('priceType') === 'sell' && request()->routeIs('master-prices.*') ? 'active' : '' }}" href="{{ route('master-prices.index', ['priceType' => 'sell']) }}"><span>＄</span> Master Harga Jual</a>
                        <a class="nav-link {{ request()->route('priceType') === 'buy' && request()->routeIs('master-prices.*') ? 'active' : '' }}" href="{{ route('master-prices.index', ['priceType' => 'buy']) }}"><span>＄</span> Master Harga Beli</a>
                    @endif
                @endif
                @if (auth()->user()->canAccess('boms'))
                    <div class="side-label">PERSEDIAAN & PRODUKSI</div>
                    <a class="nav-link {{ request()->routeIs('boms.*') ? 'active' : '' }}" href="{{ route('boms.index') }}"><span>◫</span> Bill of Materials</a>
                @endif
                @if (auth()->user()->canAccess('purchasing'))
                    <a class="nav-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}"><span>▣</span> Purchase Order</a>
                    <a class="nav-link {{ request()->routeIs('receivings.*') ? 'active' : '' }}" href="{{ route('receivings.index') }}"><span>⇩</span> Receiving</a>
                @endif
                @if (auth()->user()->canAccess('purchase-returns'))
                    <a class="nav-link {{ request()->routeIs('purchase-returns.*') ? 'active' : '' }}" href="{{ route('purchase-returns.index') }}"><span>↶</span> Retur Pembelian</a>
                @endif
                @if (auth()->user()->canAccess('stock'))
                    <a class="nav-link {{ request()->routeIs('stock.*') ? 'active' : '' }}" href="{{ route('stock.index') }}"><span>▤</span> Stok</a>
                    <a class="nav-link {{ request()->routeIs('stock-takes.*') ? 'active' : '' }}" href="{{ route('stock-takes.create') }}"><span>✓</span> Stock Take</a>
                    <a class="nav-link {{ request()->route('direction') === 'in' && request()->routeIs('stock.adjustments.*') ? 'active' : '' }}" href="{{ route('stock.adjustments.create', ['direction' => 'in']) }}"><span>＋</span> Adjustment In Stock</a>
                    <a class="nav-link {{ request()->route('direction') === 'out' && request()->routeIs('stock.adjustments.*') ? 'active' : '' }}" href="{{ route('stock.adjustments.create', ['direction' => 'out']) }}"><span>−</span> Adjustment Out Stock</a>
                @endif
                @if (auth()->user()->canAccess('sales-orders') || auth()->user()->canAccess('deliveries'))
                    <div class="side-label">PENJUALAN</div>
                    @if (auth()->user()->canAccess('sales-orders'))
                        <a class="nav-link {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}" href="{{ route('sales-orders.index') }}"><span>▣</span> Sales Order</a>
                    @endif
                    @if (auth()->user()->canAccess('sales-returns'))
                        <a class="nav-link {{ request()->routeIs('sales-returns.*') ? 'active' : '' }}" href="{{ route('sales-returns.index') }}"><span>↶</span> Retur Penjualan</a>
                    @endif
                    @if (auth()->user()->canAccess('deliveries'))
                        <a class="nav-link {{ request()->routeIs('deliveries.*') ? 'active' : '' }}" href="{{ route('deliveries.index') }}"><span>⇧</span> Delivery</a>
                    @endif
                @endif
                @if (auth()->user()->canAccess('finance'))
                    <div class="side-label">KEUANGAN</div>
                    @if (auth()->user()->canAccess('master-data') && (auth()->user()->role === 'finance' || auth()->user()->role === 'admin'))
                        <a class="nav-link {{ request()->route('master') === 'chart-of-accounts' ? 'active' : '' }}" href="{{ route('master-data.index', 'chart-of-accounts') }}"><span>＄</span> Chart of Accounts (COA)</a>
                    @endif
                    <a class="nav-link {{ request()->routeIs('general-journal.*') ? 'active' : '' }}" href="{{ route('general-journal.index') }}"><span>▤</span> General Journal</a>
                    <a class="nav-link {{ request()->routeIs('general-ledger.*') ? 'active' : '' }}" href="{{ route('general-ledger.index') }}"><span>▥</span> General Ledger</a>
                    <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}"><span>＄</span> Invoice & Pembayaran</a>
                @endif
                @if (auth()->user()->canAccess('production'))
                    <div class="side-label">PRODUKSI</div>
                    <a class="nav-link {{ request()->routeIs('work-orders.*') ? 'active' : '' }}" href="{{ route('work-orders.index') }}"><span>⚙</span> Work Order</a>
                @endif
                <div class="sidebar-foot"><span class="online-dot"></span> Sistem siap digunakan</div>
            </nav>
        </aside>
        <main class="main-panel">
            <header class="topbar">
                <div><div class="crumb">JHSOFTERP <span>/</span> Data Master</div><div class="page-title">{{ $title ?? 'Dashboard Master' }}</div></div>
                <div class="user-pill">
                    <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <span>{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="logout-button" type="submit">Keluar</button>
                    </form>
                </div>
            </header>
            <section class="content">
                @if (session('status'))
                    <div class="flash success">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="flash error">{{ $errors->first() }}</div>
                @endif
                @yield('content')
            </section>
        </main>
    </div>
    <script>
        (() => {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.querySelector('.menu-toggle');
            const closeMenu = () => {
                sidebar.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Buka menu navigasi');
            };

            toggle.addEventListener('click', () => {
                const isOpen = sidebar.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', String(isOpen));
                toggle.setAttribute('aria-label', isOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi');
            });

            sidebar.querySelectorAll('.sidebar-menu .nav-link').forEach((link) => {
                link.addEventListener('click', closeMenu);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeMenu();
                }
            });

            window.matchMedia('(min-width: 761px)').addEventListener('change', closeMenu);
        })();
    </script>
</body>
</html>
