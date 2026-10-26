@extends('layouts.app', ['title' => 'Receiving'])

@section('content')
    <div class="section-heading">
        <div><h1>Receiving</h1><p>Catat dan pantau barang yang sudah diterima berdasarkan nomor PO.</p></div>
        <a class="button primary" href="{{ route('receivings.create') }}">Buat Receiving</a>
    </div>
    <x-pic-guide module="purchase-orders" />
    <div class="table-card">
        <div class="table-toolbar"><strong>Riwayat receiving</strong><span>{{ $receipts->total() }} dokumen</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Referensi PO</th><th>Tanggal</th><th>Supplier</th><th>Gudang</th><th>Baris</th><th>Catatan</th><th></th></tr></thead>
                <tbody>
                    @forelse ($receipts as $receipt)
                        <tr>
                            <td><strong>{{ $receipt->reference_number }}</strong></td>
                            <td>{{ $receipt->receipt_date->format('d M Y') }}</td>
                            <td>{{ $receipt->purchaseOrder->supplier->name }}</td>
                            <td>{{ $receipt->warehouse->code }} — {{ $receipt->warehouse->name }}</td>
                            <td>{{ $receipt->lines_count }}</td>
                            <td>{{ $receipt->notes ?: '—' }}</td>
                            <td class="actions"><a class="button small secondary" href="{{ route('purchase-orders.show', $receipt->purchase_order_id) }}">Lihat PO</a></td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="7">Belum ada penerimaan. Pilih PO terbuka untuk mencatat barang yang datang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $receipts->links() }}</div>
    </div>
@endsection
