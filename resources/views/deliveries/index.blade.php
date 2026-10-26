@extends('layouts.app', ['title' => 'Delivery'])

@section('content')
    <div class="section-heading">
        <div><h1>Delivery</h1><p>Pengiriman parsial maupun penuh dari Sales Order terkonfirmasi.</p></div>
        @if (auth()->user()->hasRole('sales'))
            <a class="button primary" href="{{ route('deliveries.create') }}">＋ Buat Delivery</a>
        @endif
    </div>
    <div class="table-card">
        <div class="table-toolbar"><strong>Riwayat Pengiriman</strong><span>{{ $deliveries->total() }} data</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nomor</th><th>Tanggal</th><th>Sales Order</th><th>Pelanggan</th><th>Gudang</th><th>Baris</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($deliveries as $delivery)
                        <tr>
                            <td><strong>{{ $delivery->delivery_number }}</strong></td>
                            <td>{{ $delivery->delivery_date->format('d/m/Y') }}</td>
                            <td>{{ $delivery->salesOrder->so_number }}</td>
                            <td>{{ $delivery->salesOrder->customer->name }}</td>
                            <td>{{ $delivery->warehouse->name }}</td>
                            <td>{{ $delivery->lines_count }}</td>
                            <td><span class="status status-{{ $delivery->status }}">Terkirim</span></td>
                            <td class="actions"><a class="button small secondary" href="{{ route('deliveries.show', $delivery) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state">Belum ada delivery.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $deliveries->links() }}</div>
    </div>
@endsection
