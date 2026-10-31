@extends('layouts.app', ['title' => 'Data Master'])

@section('content')
    <div class="welcome-block">
        <div>
            <span class="eyebrow">PERSIAPAN SISTEM</span>
            <h1>Kelola data master</h1>
            <p>Lengkapi data dasar terlebih dahulu sebelum menjalankan proses pembelian, produksi, dan penjualan.</p>
        </div>
        <div class="welcome-icon">✦</div>
    </div>
    @if (auth()->user()->canAccess('master-data') || auth()->user()->canAccess('master-prices'))
        <div class="master-grid">
            @if (auth()->user()->canAccess('master-data'))
                @foreach ($masters as $key => $master)
                    @if (($key !== 'chart-of-accounts' || auth()->user()->canAccess('finance')) && (auth()->user()->role !== 'finance' || $key === 'chart-of-accounts'))
                        <a class="master-card" href="{{ route('master-data.index', $key) }}">
                            <span class="card-icon">{{ ['chart-of-accounts' => '＄', 'items' => '▧', 'item-categories' => '◈', 'units' => '⚖', 'customers' => '♙', 'suppliers' => '♧', 'warehouses' => '▤', 'locations' => '⌖'][$key] }}</span>
                            <span class="card-title">{{ $master['label'] }}</span>
                            <span class="card-desc">Lihat dan kelola {{ strtolower($master['label']) }}</span>
                            <span class="card-arrow">→</span>
                        </a>
                    @endif
                @endforeach
            @endif
            @if (auth()->user()->canAccess('master-prices'))
                <a class="master-card" href="{{ route('master-prices.index', ['priceType' => 'sell']) }}">
                    <span class="card-icon">＄</span><span class="card-title">Master Harga Jual</span>
                    <span class="card-desc">Atur harga jual per item dan masa berlakunya</span><span class="card-arrow">→</span>
                </a>
                <a class="master-card" href="{{ route('master-prices.index', ['priceType' => 'buy']) }}">
                    <span class="card-icon">＄</span><span class="card-title">Master Harga Beli</span>
                    <span class="card-desc">Atur harga beli item per supplier</span><span class="card-arrow">→</span>
                </a>
            @endif
        </div>
    @endif
    <div class="master-grid">
        @if (auth()->user()->canAccess('boms'))
            <a class="master-card" href="{{ route('boms.index') }}"><span class="card-icon">◫</span><span class="card-title">Bill of Materials</span><span class="card-desc">Atur komponen dan kebutuhan produk</span><span class="card-arrow">→</span></a>
        @endif
        @if (auth()->user()->canAccess('sales-orders'))
            <a class="master-card" href="{{ route('sales-orders.index') }}"><span class="card-icon">▣</span><span class="card-title">Sales Order</span><span class="card-desc">Catat pesanan pelanggan</span><span class="card-arrow">→</span></a>
        @endif
        @if (auth()->user()->canAccess('sales-returns'))
            <a class="master-card" href="{{ route('sales-returns.index') }}"><span class="card-icon">↶</span><span class="card-title">Retur Penjualan</span><span class="card-desc">Catat barang yang dikembalikan pelanggan</span><span class="card-arrow">→</span></a>
        @endif
        @if (auth()->user()->canAccess('deliveries'))
            <a class="master-card" href="{{ route('deliveries.index') }}"><span class="card-icon">⇧</span><span class="card-title">Delivery</span><span class="card-desc">Lihat pengiriman terkait pesanan pelanggan</span><span class="card-arrow">→</span></a>
        @endif
        @if (auth()->user()->canAccess('purchasing'))
            <a class="master-card" href="{{ route('purchase-orders.index') }}"><span class="card-icon">▣</span><span class="card-title">Purchase Order</span><span class="card-desc">Buat dan pantau pesanan pembelian supplier</span><span class="card-arrow">→</span></a>
            <a class="master-card" href="{{ route('receivings.index') }}"><span class="card-icon">⇩</span><span class="card-title">Receiving</span><span class="card-desc">Catat barang datang berdasarkan nomor PO</span><span class="card-arrow">→</span></a>
        @endif
        @if (auth()->user()->canAccess('purchase-returns'))
            <a class="master-card" href="{{ route('purchase-returns.index') }}"><span class="card-icon">↶</span><span class="card-title">Retur Pembelian</span><span class="card-desc">Catat barang yang dikembalikan ke supplier</span><span class="card-arrow">→</span></a>
        @endif
        @if (auth()->user()->canAccess('stock'))
            <a class="master-card" href="{{ route('stock.index') }}"><span class="card-icon">▤</span><span class="card-title">Stok</span><span class="card-desc">Lihat saldo stok dan catat adjustment masuk/keluar</span><span class="card-arrow">→</span></a>
            <a class="master-card" href="{{ route('stock-takes.create') }}"><span class="card-icon">✓</span><span class="card-title">Stock Take</span><span class="card-desc">Hitung stok fisik dan rekonsiliasi selisih</span><span class="card-arrow">→</span></a>
        @endif
        @if (auth()->user()->canAccess('production'))
            <a class="master-card" href="{{ route('work-orders.index') }}"><span class="card-icon">⚙</span><span class="card-title">Work Order</span><span class="card-desc">Catat produksi, QC, dan penerimaan barang jadi</span><span class="card-arrow">→</span></a>
        @endif
        @if (auth()->user()->canAccess('finance'))
            <a class="master-card" href="{{ route('general-journal.index') }}"><span class="card-icon">▤</span><span class="card-title">General Journal</span><span class="card-desc">Buat dan lihat jurnal umum yang seimbang</span><span class="card-arrow">→</span></a>
            <a class="master-card" href="{{ route('general-ledger.index') }}"><span class="card-icon">▥</span><span class="card-title">General Ledger</span><span class="card-desc">Telusuri mutasi dan saldo per akun COA</span><span class="card-arrow">→</span></a>
            <a class="master-card" href="{{ route('invoices.index') }}"><span class="card-icon">＄</span><span class="card-title">Invoice & Pembayaran</span><span class="card-desc">Kelola piutang dan pembayaran pelanggan</span><span class="card-arrow">→</span></a>
        @endif
    </div>
    @if (auth()->user()->canAccess('master-data'))
        <div class="note-panel"><span class="note-icon">i</span><span><strong>Urutan yang disarankan:</strong> isi satuan dan kategori, lalu gudang/lokasi, barang, pelanggan/supplier, dan terakhir BOM.</span></div>
    @endif
@endsection
