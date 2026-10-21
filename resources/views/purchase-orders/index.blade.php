@extends('layouts.app', ['title' => 'Purchase Order'])

@section('content')
    <div class="section-heading">
        <div><h1>Purchase Order</h1><p>Kelola pesanan ke supplier dan penerimaan barang.</p></div>
        <a class="button primary" href="{{ route('purchase-orders.create') }}">＋ Buat PO</a>
    </div>
    <x-pic-guide module="purchase-orders" />
    <div class="table-card">
        <div class="table-toolbar"><strong>Daftar Purchase Order</strong><span>{{ $orders->total() }} dokumen</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nomor PO</th><th>Tanggal</th><th>Supplier</th><th>Estimasi tiba</th><th>Total</th><th>Status</th><th class="actions-col">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><strong>{{ $order->po_number }}</strong></td>
                            <td>{{ $order->order_date->format('d M Y') }}</td>
                            <td>{{ $order->supplier->name }}</td>
                            <td>{{ $order->expected_date?->format('d M Y') ?? '—' }}</td>
                            <td>Rp {{ number_format((float) $order->total_amount, 2, ',', '.') }}</td>
                            <td><span class="status status-{{ $order->status }}">{{ ['open' => 'Terbuka', 'partial' => 'Diterima sebagian', 'received' => 'Selesai', 'draft' => 'Draft'][$order->status] ?? $order->status }}</span></td>
                            <td class="actions"><a class="button small secondary" href="{{ route('purchase-orders.show', $order) }}">Detail / Terima</a></td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="7">Belum ada Purchase Order. Buat PO untuk supplier untuk mulai menerima barang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $orders->links() }}</div>
    </div>
@endsection
