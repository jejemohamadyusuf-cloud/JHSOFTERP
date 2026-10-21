@extends('layouts.app', ['title' => 'Sales Order'])

@section('content')
    <div class="section-heading">
        <div><h1>Sales Order</h1><p>Kelola pesanan pelanggan dan status konfirmasinya.</p></div>
        <a class="button primary" href="{{ route('sales-orders.create') }}">＋ Buat Sales Order</a>
    </div>
    <x-pic-guide module="sales-orders" />
    <div class="table-card">
        <div class="table-toolbar"><strong>Daftar Sales Order</strong><span>{{ $orders->total() }} pesanan</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nomor SO</th><th>Tanggal</th><th>Pelanggan</th><th>Total</th><th>Status</th><th class="actions-col">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><strong>{{ $order->so_number }}</strong></td>
                            <td>{{ $order->order_date->format('d M Y') }}</td>
                            <td>{{ $order->customer->name }}</td>
                            <td>Rp {{ number_format((float) $order->total_amount, 2, ',', '.') }}</td>
                            <td><span class="status status-{{ $order->status }}">{{ ['draft' => 'Draft', 'confirmed' => 'Dikonfirmasi', 'cancelled' => 'Dibatalkan'][$order->status] ?? $order->status }}</span></td>
                            <td class="actions"><a class="button small secondary" href="{{ route('sales-orders.show', $order) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="6">Belum ada Sales Order. Buat pesanan pertama untuk mulai mencatat permintaan pelanggan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $orders->links() }}</div>
    </div>
@endsection
