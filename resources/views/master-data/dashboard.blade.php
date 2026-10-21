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
    <div class="section-heading"><div><h2>Master operasional</h2><p>Data referensi utama untuk proses ERP Anda</p></div></div>
    <div class="master-grid">
        @foreach ($masters as $key => $master)
            <a class="master-card" href="{{ route('master-data.index', $key) }}">
                <span class="card-icon">{{ ['items' => '▧', 'item-categories' => '◈', 'units' => '⚖', 'customers' => '♙', 'suppliers' => '♧', 'warehouses' => '▤', 'locations' => '⌖'][$key] }}</span>
                <span class="card-title">{{ $master['label'] }}</span>
                <span class="card-desc">Lihat dan kelola {{ strtolower($master['label']) }}</span>
                <span class="card-arrow">→</span>
            </a>
        @endforeach
        <a class="master-card" href="{{ route('boms.index') }}">
            <span class="card-icon">◫</span><span class="card-title">Bill of Materials</span>
            <span class="card-desc">Atur komponen dan kebutuhan produk</span><span class="card-arrow">→</span>
        </a>
        <a class="master-card" href="{{ route('master-prices.index') }}">
            <span class="card-icon">＄</span><span class="card-title">Master Harga</span>
            <span class="card-desc">Atur harga dan masa berlakunya</span><span class="card-arrow">→</span>
        </a>
        <a class="master-card" href="{{ route('sales-orders.index') }}">
            <span class="card-icon">▣</span><span class="card-title">Sales Order</span>
            <span class="card-desc">Catat pesanan pelanggan</span><span class="card-arrow">→</span>
        </a>
        <a class="master-card" href="{{ route('purchase-orders.index') }}">
            <span class="card-icon">▣</span><span class="card-title">Purchase Order</span>
            <span class="card-desc">Buat pesanan pembelian dan catat penerimaan</span><span class="card-arrow">→</span>
        </a>
        <a class="master-card" href="{{ route('stock.index') }}">
            <span class="card-icon">▤</span><span class="card-title">Stok</span>
            <span class="card-desc">Lihat saldo stok dan catat adjustment masuk/keluar</span><span class="card-arrow">→</span>
        </a>
    </div>
    <div class="note-panel"><span class="note-icon">i</span><span><strong>Urutan yang disarankan:</strong> isi satuan dan kategori, lalu gudang/lokasi, barang, pelanggan/supplier, dan terakhir BOM.</span></div>
@endsection
