@extends('layouts.app', ['title' => $priceType === 'buy' ? 'Master Harga Beli' : 'Master Harga Jual'])

@section('content')
    <div class="section-heading">
        <div>
            <h1>{{ $priceType === 'buy' ? 'Master Harga Beli' : 'Master Harga Jual' }}</h1>
            <p>{{ $priceType === 'buy' ? 'Harga beli dicatat terpisah untuk setiap supplier dan item.' : 'Harga jual berlaku per item dan digunakan pada Sales Order.' }}</p>
        </div>
        <a class="button primary" href="{{ route('master-prices.create', ['priceType' => $priceType]) }}">＋ Tambah Harga {{ $priceType === 'buy' ? 'Beli' : 'Jual' }}</a>
    </div>
    <x-pic-guide module="master-prices" />
    <div class="table-card">
        <div class="table-toolbar"><strong>Riwayat harga</strong><span>{{ $prices->total() }} periode</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Barang</th>@if ($priceType === 'buy')<th>Supplier</th>@endif<th>Harga</th><th>Mulai berlaku</th><th>Akhir berlaku</th><th>Status hari ini</th><th class="actions-col">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($prices as $price)
                        @php($effectiveToday = $price->start_date->toDateString() <= today()->toDateString() && ($price->end_date === null || $price->end_date->toDateString() >= today()->toDateString()))
                        @php($upcoming = $price->start_date->toDateString() > today()->toDateString())
                        <tr>
                            <td><strong>{{ $price->item->code }}</strong><br><span class="muted">{{ $price->item->name }}</span></td>
                            @if ($priceType === 'buy')
                                <td>{{ $price->supplier?->name ?? 'Supplier tidak tersedia' }}</td>
                            @endif
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
                                <a class="button small secondary" href="{{ route('master-prices.edit', ['priceType' => $priceType, 'masterPrice' => $price]) }}">Ubah periode</a>
                                <form method="POST" action="{{ route('master-prices.destroy', ['priceType' => $priceType, 'masterPrice' => $price]) }}" onsubmit="return confirm('Hapus riwayat harga ini?')">
                                    @csrf @method('DELETE')
                                    <button class="button small danger" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="{{ $priceType === 'buy' ? 7 : 6 }}">Belum ada riwayat harga {{ $priceType === 'buy' ? 'beli' : 'jual' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $prices->links() }}</div>
    </div>
    <div class="note-panel"><span class="note-icon">i</span><span>Periode tidak boleh tumpang tindih {{ $priceType === 'buy' ? 'untuk item dan supplier yang sama' : 'untuk item yang sama' }}. Tutup periode sebelumnya sebelum membuat periode baru.</span></div>
@endsection
