@extends('layouts.app', ['title' => 'Delivery'])

@section('content')
    <div class="section-heading">
    <div><h1>Delivery</h1><p>Pengiriman parsial maupun penuh dari Sales Order terkonfirmasi.</p></div>
    </div>
    <div class="table-card selectable-list" data-selectable-list>
        <div class="table-toolbar"><strong>Riwayat Pengiriman</strong><span>{{ $deliveries->total() }} data</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th class="list-select-cell">Pilih</th><th>Nomor</th><th>Tanggal</th><th>Sales Order</th><th>Pelanggan</th><th>Gudang</th><th>Baris</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($deliveries as $delivery)
                        <tr>
                            <td class="list-select-cell">
                                <input type="radio" name="selected_delivery" data-list-select aria-label="Pilih delivery {{ $delivery->delivery_number }}">
                                <template data-list-actions><a class="button secondary" href="{{ route('deliveries.show', $delivery) }}">Detail</a></template>
                            </td>
                            <td><strong>{{ $delivery->delivery_number }}</strong></td>
                            <td>{{ $delivery->delivery_date->format('d/m/Y') }}</td>
                            <td>{{ $delivery->salesOrder->so_number }}</td>
                            <td>{{ $delivery->salesOrder->customer->name }}</td>
                            <td>{{ $delivery->warehouse->name }}</td>
                            <td>{{ $delivery->lines_count }}</td>
                            <td><span class="status status-{{ $delivery->status }}">Terkirim</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state">Belum ada delivery.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('components.list-action-panel', ['staticActions' => auth()->user()->hasRole('sales') ? [['label' => '＋ Buat Delivery', 'url' => route('deliveries.create'), 'class' => 'primary']] : []])
        <div class="pagination">{{ $deliveries->links() }}</div>
    </div>
@endsection
