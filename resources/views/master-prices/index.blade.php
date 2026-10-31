@extends('layouts.app', ['title' => $priceType === 'buy' ? 'Master Harga Beli' : 'Master Harga Jual'])

@section('content')
    <x-pic-guide module="master-prices" />
    <div class="table-card selectable-list" data-selectable-list>
        <div class="table-toolbar"><strong>Riwayat harga</strong><span>{{ $prices->total() }} periode</span></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="list-select-cell">Pilih</th>
                        <th>Barang</th>
                        @if ($priceType === 'buy')
                            <th>Supplier</th>
                        @endif
                        <th>Harga</th>
                        <th>Mulai berlaku</th>
                        <th>Akhir berlaku</th>
                        <th>Status hari ini</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prices as $price)
                        @php($effectiveToday = $price->start_date->toDateString() <= today()->toDateString() && ($price->end_date === null || $price->end_date->toDateString() >= today()->toDateString()))
                        @php($upcoming = $price->start_date->toDateString() > today()->toDateString())
                        <tr>
                            <td class="list-select-cell">
                                <input type="radio" name="selected_master_price" data-list-select
                                    aria-label="Pilih harga {{ $price->item->code }}">
                                <template data-list-actions>
                                    <a class="button secondary"
                                        href="{{ route('master-prices.edit', ['priceType' => $priceType, 'masterPrice' => $price]) }}">Ubah
                                        periode</a>
                                    <form method="POST"
                                        action="{{ route('master-prices.destroy', ['priceType' => $priceType, 'masterPrice' => $price]) }}"
                                        onsubmit="return confirm('Hapus riwayat harga ini?')">
                                        @csrf @method('DELETE')
                                        <button class="button danger" type="submit">Hapus</button>
                                    </form>
                                </template>
                            </td>
                            <td><strong>{{ $price->item->code }}</strong><br><span
                                    class="muted">{{ $price->item->name }}</span></td>
                            @if ($priceType === 'buy')
                                <td>{{ $price->supplier?->name ?? 'Supplier tidak tersedia' }}</td>
                            @endif
                            <td>Rp {{ number_format((float) $price->price, 2, ',', '.') }}</td>
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
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="{{ $priceType === 'buy' ? 7 : 6 }}">Belum ada riwayat harga
                                {{ $priceType === 'buy' ? 'beli' : 'jual' }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('components.list-action-panel', [
            'staticActions' => [
                [
                    'label' => '＋ Tambah Harga ' . ($priceType === 'buy' ? 'Beli' : 'Jual'),
                    'url' => route('master-prices.create', ['priceType' => $priceType]),
                    'class' => 'primary',
                ],
            ],
        ])
        <div class="pagination">{{ $prices->links() }}</div>
    </div>
    <div class="note-panel"><span class="note-icon">i</span><span>Periode tidak boleh tumpang tindih
            {{ $priceType === 'buy' ? 'untuk item dan supplier yang sama' : 'untuk item yang sama' }}. Tutup periode
            sebelumnya sebelum membuat periode baru.</span></div>
@endsection
