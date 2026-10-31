@extends('layouts.app', ['title' => 'Retur Pembelian'])

@section('content')
    <div class="section-heading">
        <div><h1>Retur Pembelian</h1><p>Catat barang yang dikembalikan ke supplier berdasarkan receiving.</p></div>
        <a class="button primary" href="{{ route('purchase-returns.create') }}">＋ Buat Retur Pembelian</a>
    </div>
    <div class="note-panel"><span class="note-icon">i</span><span>Retur ini mengeluarkan barang dari gudang asal dan memperbarui stok. Ini belum membuat jurnal atau mengurangi utang; klaim supplier dicatat terpisah melalui nota debit/kredit sesuai SOP.</span></div>
    <div class="table-card">
        <div class="table-toolbar"><strong>Riwayat Retur Pembelian</strong><span>{{ $returns->total() }} retur</span></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Nomor retur</th><th>Tanggal</th><th>Receiving</th><th>Supplier</th><th>Alasan</th><th>Baris</th><th></th></tr></thead>
            <tbody>
                @forelse ($returns as $return)
                    <tr>
                        <td><strong>{{ $return->return_number }}</strong></td>
                        <td>{{ $return->return_date->format('d/m/Y') }}</td>
                        <td>{{ $return->goodsReceipt->gr_number }}</td>
                        <td>{{ $return->goodsReceipt->purchaseOrder->supplier->name }}</td>
                        <td>{{ $return->reason }}</td>
                        <td>{{ $return->lines_count }}</td>
                        <td>@include('components.cancel-transaction-form', ['type' => 'purchase-return', 'id' => $return->id])</td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="7">Belum ada retur pembelian.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="pagination">{{ $returns->links() }}</div>
    </div>
@endsection
