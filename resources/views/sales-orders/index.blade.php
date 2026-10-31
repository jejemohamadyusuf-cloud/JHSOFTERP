@extends('layouts.app', ['title' => 'Sales Order'])

@section('content')
    <x-pic-guide module="sales-orders" />
    <div class="table-card selectable-list" data-selectable-list>
        <div class="table-toolbar"><strong>Daftar Sales Order</strong><span>{{ $orders->total() }} pesanan</span></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="list-select-cell">Pilih</th>
                        <th>Nomor SO</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td class="list-select-cell">
                                <input type="radio" name="selected_sales_order" data-list-select
                                    aria-label="Pilih SO {{ $order->so_number }}">
                                <template data-list-actions><a class="button secondary"
                                        href="{{ route('sales-orders.show', $order) }}">Detail</a>
                                    @include('components.cancel-transaction-form', ['type' => 'sales-order', 'id' => $order->id])
                                </template>
                            </td>
                            <td><strong>{{ $order->so_number }}</strong></td>
                            <td>{{ $order->order_date->format('d M Y') }}</td>
                            <td>{{ $order->customer->name }}</td>
                            <td>Rp {{ number_format((float) $order->total_amount, 2, ',', '.') }}</td>
                            <td><span
                                    class="status status-{{ $order->status }}">{{ ['draft' => 'Draft', 'confirmed' => 'Dikonfirmasi', 'cancelled' => 'Dibatalkan'][$order->status] ?? $order->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="6">Belum ada Sales Order.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('components.list-action-panel', [
            'staticActions' => [
                ['label' => '＋ Buat Sales Order', 'url' => route('sales-orders.create'), 'class' => 'primary'],
            ],
        ])
        <div class="pagination">{{ $orders->links() }}</div>
    </div>
@endsection
