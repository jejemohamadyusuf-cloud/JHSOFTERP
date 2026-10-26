@extends('layouts.app', ['title' => 'Stok'])

@section('content')
    <div class="section-heading">
        <div>
            <h1>Stok</h1>
            <p>Saldo persediaan per barang, gudang, dan lokasi. Kuantitas disimpan dalam satuan dasar barang.</p>
        </div>
        @if (auth()->user()->hasRole('admin', 'inventory'))
            <a class="button primary" href="{{ route('stock-takes.create') }}">＋ Mulai Stock Take</a>
        @endif
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
                            <td>{{ $level->item->formatStockQuantity($level->qty_available) }}</td>
                            <td>{{ $level->item->formatStockQuantity($level->qty_reserved) }}</td>
                            <td><strong>{{ $level->item->formatStockQuantity($level->qty_on_hand) }}</strong></td>
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
            <h2>Riwayat stock take</h2>
            <p>Sepuluh dokumen stock take terakhir.</p>
        </div>
    </div>
    <div class="table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Nomor dokumen</th><th>Tanggal</th><th>Baris dihitung</th><th>Catatan</th></tr>
                </thead>
                <tbody>
                    @forelse ($stockTakes as $stockTake)
                        <tr>
                            <td><strong>{{ $stockTake->stock_take_number }}</strong></td>
                            <td>{{ $stockTake->stock_take_date->format('d M Y') }}</td>
                            <td>{{ $stockTake->lines_count }}</td>
                            <td>{{ $stockTake->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="4">Belum ada dokumen stock take.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
