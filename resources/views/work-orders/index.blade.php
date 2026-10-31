@extends('layouts.app', ['title' => 'Work Order'])

@section('content')
    <div class="section-heading">
        <div><h1>Work Order</h1><p>Rencana produksi untuk Sales Order maupun persediaan make to stock.</p></div>
    </div>
    <x-pic-guide module="work-orders" />
    <div class="table-card selectable-list" data-selectable-list>
        <div class="table-toolbar"><strong>Daftar Work Order</strong><span>{{ $workOrders->total() }} data</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th class="list-select-cell">Pilih</th><th>Nomor WO</th><th>Jenis</th><th>Sales Order</th><th>Barang</th><th>Qty rencana</th><th>Gudang</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($workOrders as $workOrder)
                        <tr>
                            <td class="list-select-cell">
                                <input type="radio" name="selected_work_order" data-list-select aria-label="Pilih Work Order {{ $workOrder->wo_number }}">
                                <template data-list-actions><a class="button secondary" href="{{ route('work-orders.show', $workOrder) }}">Detail</a>
                                    @include('components.cancel-transaction-form', ['type' => 'work-order', 'id' => $workOrder->id])
                                </template>
                            </td>
                            <td><strong>{{ $workOrder->wo_number }}</strong></td>
                            <td>{{ $workOrder->production_type === 'make_to_stock' ? 'Make to Stock' : 'Make to Order' }}</td>
                            <td>{{ $workOrder->salesOrder?->so_number ?? '—' }}</td>
                            <td><strong>{{ $workOrder->item->code }}</strong><br><span class="muted">{{ $workOrder->item->name }}</span></td>
                            <td>{{ number_format((float) $workOrder->qty_planned, 2, ',', '.') }} {{ $workOrder->item->unit->code }}</td>
                            <td>{{ $workOrder->warehouse->name }}</td>
                            <td><span class="status status-{{ $workOrder->status }}">{{ ['planned' => 'Rencana', 'released' => 'Dirilis', 'in_progress' => 'Dikerjakan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$workOrder->status] ?? $workOrder->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="8">Belum ada Work Order.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('components.list-action-panel', ['staticActions' => [['label' => '＋ Buat Work Order', 'url' => route('work-orders.create'), 'class' => 'primary']]])
        <div class="pagination">{{ $workOrders->links() }}</div>
    </div>
@endsection
