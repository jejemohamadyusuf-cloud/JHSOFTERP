@extends('layouts.app', ['title' => 'Master Harga'])

@section('content')
    <div class="section-heading">
        <div><h1>Master Harga</h1><p>Riwayat harga barang. Sales Order memakai harga yang berlaku pada tanggal pesanan.</p></div>
        <a class="button primary" href="{{ route('master-prices.create') }}">＋ Tambah Harga</a>
    </div>
    <div class="table-card">
        <div class="table-toolbar"><strong>Riwayat harga</strong><span>{{ $prices->total() }} periode</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Barang</th><th>Harga</th><th>Mulai berlaku</th><th>Akhir berlaku</th><th>Status hari ini</th><th class="actions-col">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($prices as $price)
                        @php($effectiveToday = $price->start_date->toDateString() <= today()->toDateString() && ($price->end_date === null || $price->end_date->toDateString() >= today()->toDateString()))
                        @php($upcoming = $price->start_date->toDateString() > today()->toDateString())
                        <tr>
                            <td><strong>{{ $price->item->code }}</strong><br><span class="muted">{{ $price->item->name }}</span></td>
                            <td>Rp {{ number_format((float) $price->price, 4, ',', '.') }}</td>
                            <td>{{ $price->start_date->format('d M Y') }}</td>
                            <td>{{ $price->end_date?->format('d M Y') ?? 'Tidak dibatasi' }}</td>
                            <td>
                                @if ($effectiveToday)
                                    <span class="status status-active">Berlaku</span>
                                @elseif ($upcoming)
                                    <span class="status status-draft">Akan datang</span>
                                @else
                                    <span class="status status-inactive">Berakhir</span>
                                @endif
                            </td>
                            <td class="actions">
                                <a class="button small secondary" href="{{ route('master-prices.edit', $price) }}">Ubah periode</a>
                                <form method="POST" action="{{ route('master-prices.destroy', $price) }}" onsubmit="return confirm('Hapus riwayat harga ini?')">
                                    @csrf @method('DELETE')
                                    <button class="button small danger" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="6">Belum ada harga. Tambahkan harga agar barang dapat digunakan pada Sales Order.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $prices->links() }}</div>
    </div>
    <div class="note-panel"><span class="note-icon">i</span><span>Periode untuk barang yang sama tidak boleh tumpang tindih. Sebelum menambahkan harga baru, tutup harga sebelumnya sampai sehari sebelum tanggal mulai harga baru. Tanggal akhir kosong berarti berlaku sampai ada harga selanjutnya.</span></div>
@endsection
