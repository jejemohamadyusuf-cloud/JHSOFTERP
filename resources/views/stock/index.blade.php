@extends('layouts.app', ['title' => 'Stok'])

@section('content')
    <div class="section-heading">
        <div>
            <h1>Stok</h1>
            <p>Saldo persediaan per barang, gudang, dan lokasi.</p>
        </div>
    </div>
    <x-pic-guide module="stock" />
    <form class="table-toolbar" method="GET" action="{{ route('stock.index') }}">
        <strong>Saldo persediaan</strong>
        <div class="actions">
            <input aria-label="Cari barang atau gudang" name="search" type="search" value="{{ $search }}"
                placeholder="Cari barang/gudang">
            <button class="button secondary" type="submit">Cari</button>
            @if ($search !== '')
                <a class="button secondary" href="{{ route('stock.index') }}">Reset</a>
            @endif
        </div>
    </form>
    <div class="table-card">
        <div class="table-toolbar"><strong>Posisi stok</strong><span>{{ $stockLevels->total() }} posisi</span></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Gudang</th>
                        <th>Lokasi</th>
                        <th>Stok tersedia</th>
                        <th>Dicadangkan</th>
                        <th>Total stok</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stockLevels as $level)
                        <tr>
                            <td><strong>{{ $level->item->code }}</strong><br><span
                                    class="muted">{{ $level->item->name }}</span></td>
                            <td>{{ $level->warehouse->code }} — {{ $level->warehouse->name }}</td>
                            <td>{{ $level->location?->code ?? '—' }}</td>
                            <td>{{ number_format((float) $level->qty_available, 4, ',', '.') }}
                                {{ $level->item->unit->code }}</td>
                            <td>{{ number_format((float) $level->qty_reserved, 4, ',', '.') }}
                                {{ $level->item->unit->code }}</td>
                            <td><strong>{{ number_format((float) $level->qty_on_hand, 4, ',', '.') }}
                                    {{ $level->item->unit->code }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="6">Belum ada saldo stok. Catat adjustment in atau terima
                                barang dari Purchase Order.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $stockLevels->links() }}</div>
    </div>
    <div class="section-heading">
        <div>
            <h2>Riwayat adjustment stok</h2>
            <p>Sepuluh dokumen adjustment in dan out terakhir.</p>
        </div>
    </div>
    <div class="table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nomor dokumen</th>
                        <th>Arah</th>
                        <th>Tanggal</th>
                        <th>Baris</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($adjustments as $adjustment)
                        <tr>
                            <td><strong>{{ $adjustment->adjustment_number }}</strong></td>
                            <td><span
                                    class="status status-{{ $adjustment->direction }}">{{ $adjustment->direction === 'in' ? 'Adjustment In' : 'Adjustment Out' }}</span>
                            </td>
                            <td>{{ $adjustment->adjustment_date->format('d M Y') }}</td>
                            <td>{{ $adjustment->lines_count }}</td>
                            <td>{{ $adjustment->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="5">Belum ada dokumen adjustment stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
