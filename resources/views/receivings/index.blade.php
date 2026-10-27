@extends('layouts.app', ['title' => 'Receiving'])

@section('content')
    <x-pic-guide module="purchase-orders" />
    <div class="table-card selectable-list" data-selectable-list>
        <div class="table-toolbar"><strong>Riwayat receiving</strong><span>{{ $receipts->total() }} dokumen</span></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="list-select-cell">Pilih</th>
                        <th>Referensi PO</th>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th>Gudang</th>
                        <th>Baris</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($receipts as $receipt)
                        <tr>
                            <td class="list-select-cell">
                                <input type="radio" name="selected_receiving" data-list-select
                                    aria-label="Pilih receiving {{ $receipt->reference_number }}">
                                <template data-list-actions><a class="button secondary"
                                        href="{{ route('purchase-orders.show', $receipt->purchase_order_id) }}">Lihat
                                        PO</a></template>
                            </td>
                            <td><strong>{{ $receipt->reference_number }}</strong></td>
                            <td>{{ $receipt->receipt_date->format('d M Y') }}</td>
                            <td>{{ $receipt->purchaseOrder->supplier->name }}</td>
                            <td>{{ $receipt->warehouse->code }} — {{ $receipt->warehouse->name }}</td>
                            <td>{{ $receipt->lines_count }}</td>
                            <td>{{ $receipt->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="7">Belum ada penerimaan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('components.list-action-panel', [
            'staticActions' => [
                ['label' => 'Buat Receiving', 'url' => route('receivings.create'), 'class' => 'primary'],
            ],
        ])
        <div class="pagination">{{ $receipts->links() }}</div>
    </div>
@endsection
