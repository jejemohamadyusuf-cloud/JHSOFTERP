@extends('layouts.app', ['title' => 'Stok'])

@section('content')
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
    <div class="table-card selectable-list" data-selectable-list>
        <div class="table-toolbar"><strong>Posisi stok</strong><span>{{ $stockLevels->total() }} posisi</span></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="list-select-cell">Pilih</th>
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
                            <td class="list-select-cell">
                                <input type="radio" name="selected_stock_position" data-list-select
                                    aria-label="Pilih stok {{ $level->item->code }} di {{ $level->warehouse->code }}">
                                <template data-list-actions>
                                    @if (auth()->user()->hasRole('admin', 'inventory'))
                                        <a class="button primary"
                                            href="{{ route('stock-takes.create', ['item_id' => $level->item_id, 'warehouse_id' => $level->warehouse_id, 'location_id' => $level->location_id]) }}">Stock
                                            Take</a>
                                        <a class="button secondary"
                                            href="{{ route('stock.adjustments.create', ['direction' => 'in', 'item_id' => $level->item_id, 'warehouse_id' => $level->warehouse_id, 'location_id' => $level->location_id]) }}">Adjustment
                                            In</a>
                                        <a class="button secondary"
                                            href="{{ route('stock.adjustments.create', ['direction' => 'out', 'item_id' => $level->item_id, 'warehouse_id' => $level->warehouse_id, 'location_id' => $level->location_id]) }}">Adjustment
                                            Out</a>
                                    @endif
                                </template>
                            </td>
                            <td><strong>{{ $level->item->code }}</strong><br><span
                                    class="muted">{{ $level->item->name }}</span></td>
                            <td>{{ $level->warehouse->code }} — {{ $level->warehouse->name }}</td>
                            <td>{{ $level->location?->code ?? '—' }}</td>
                            <td>{{ $level->item->formatStockQuantity($level->qty_available) }}</td>
                            <td>{{ $level->item->formatStockQuantity($level->qty_reserved) }}</td>
                            <td><strong>{{ $level->item->formatStockQuantity($level->qty_on_hand) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="7">Belum ada saldo stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('components.list-action-panel', [
            'staticActions' =>
                $stockLevels->isEmpty() && auth()->user()->hasRole('admin', 'inventory')
                    ? [
                        ['label' => 'Stock Take', 'url' => route('stock-takes.create'), 'class' => 'primary'],
                        [
                            'label' => 'Adjustment In',
                            'url' => route('stock.adjustments.create', ['direction' => 'in']),
                            'class' => 'secondary',
                        ],
                        [
                            'label' => 'Adjustment Out',
                            'url' => route('stock.adjustments.create', ['direction' => 'out']),
                            'class' => 'secondary',
                        ],
                    ]
                    : [],
        ])
        <div class="pagination">{{ $stockLevels->links() }}</div>
    </div>
@endsection
