@extends('layouts.app', ['title' => 'Retur Penjualan'])

@section('content')
    <div class="section-heading">
        <div><h1>Retur Penjualan</h1><p>Catat barang yang dikembalikan pelanggan berdasarkan delivery.</p></div>
        <a class="button primary" href="{{ route('sales-returns.create') }}">＋ Buat Retur Penjualan</a>
    </div>
    <div class="note-panel"><span class="note-icon">i</span><span>Retur ini menerima kembali barang ke gudang asal dan memperbarui stok. Ini belum membuat jurnal atau mengurangi piutang; nota kredit dan pengembalian uang dicatat terpisah.</span></div>
    <div class="table-card">
        <div class="table-toolbar"><strong>Riwayat Retur Penjualan</strong><span>{{ $returns->total() }} retur</span></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Nomor retur</th><th>Tanggal</th><th>Delivery</th><th>Pelanggan</th><th>Alasan</th><th>Baris</th><th></th></tr></thead>
            <tbody>
                @forelse ($returns as $return)
                    <tr>
                        <td><strong>{{ $return->return_number }}</strong></td>
                        <td>{{ $return->return_date->format('d/m/Y') }}</td>
                        <td>{{ $return->delivery->delivery_number }}</td>
                        <td>{{ $return->delivery->salesOrder->customer->name }}</td>
                        <td>{{ $return->reason }}</td>
                        <td>{{ $return->lines_count }}</td>
                        <td>@include('components.cancel-transaction-form', ['type' => 'sales-return', 'id' => $return->id])</td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="7">Belum ada retur penjualan.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="pagination">{{ $returns->links() }}</div>
    </div>
@endsection
