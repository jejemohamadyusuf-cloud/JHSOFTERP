@extends('layouts.app', ['title' => 'Purchase Order'])

@section('content')
    <x-pic-guide module="purchase-orders" />
    <div class="table-card selectable-list" data-selectable-list>
        <div class="table-toolbar"><strong>Daftar Purchase Order</strong><span>{{ $orders->total() }} dokumen</span></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="list-select-cell">Pilih</th>
                        <th>Nomor PO</th>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th>Estimasi tiba</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td class="list-select-cell">
                                <input type="radio" name="selected_purchase_order" data-list-select
                                    aria-label="Pilih PO {{ $order->po_number }}">
                                <template data-list-actions><a class="button secondary"
                                        href="{{ route('purchase-orders.show', $order) }}">Detail PO</a></template>
                            </td>
                            <td><strong>{{ $order->po_number }}</strong></td>
                            <td>{{ $order->order_date->format('d M Y') }}</td>
                            <td>{{ $order->supplier->name }}</td>
                            <td>{{ $order->expected_date?->format('d M Y') ?? '—' }}</td>
                            <td>Rp {{ number_format((float) $order->total_amount, 2, ',', '.') }}</td>
                            <td><span
                                    class="status status-{{ $order->status }}">{{ ['open' => 'Terbuka', 'partial' => 'Diterima sebagian', 'received' => 'Selesai', 'draft' => 'Draft'][$order->status] ?? $order->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="7">Belum ada Purchase Order.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('components.list-action-panel', [
            'staticActions' => [
                ['label' => '＋ Buat PO', 'url' => route('purchase-orders.create'), 'class' => 'primary'],
            ],
        ])
        <div class="pagination">{{ $orders->links() }}</div>
    </div>
@endsection
