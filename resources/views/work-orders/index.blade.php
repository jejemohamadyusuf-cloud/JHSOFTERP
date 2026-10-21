@extends('layouts.app', ['title' => 'Work Order'])

@section('content')
    <div class="section-heading">
        <div><h1>Work Order</h1><p>Rencana produksi untuk Sales Order maupun persediaan make to stock.</p></div>
        <a class="button primary" href="{{ route('work-orders.create') }}">＋ Buat Work Order</a>
    </div>
    <x-pic-guide module="work-orders" />
    <div class="table-card">
        <div class="table-toolbar"><strong>Daftar Work Order</strong><span>{{ $workOrders->total() }} data</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nomor WO</th><th>Jenis</th><th>Sales Order</th><th>Barang</th><th>Qty rencana</th><th>Gudang</th><th>Status</th><th class="actions-col">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($workOrders as $workOrder)
                        <tr>
                            <td><strong>{{ $workOrder->wo_number }}</strong></td>
                            <td>{{ $workOrder->production_type === 'make_to_stock' ? 'Make to Stock' : 'Make to Order' }}</td>
                            <td>{{ $workOrder->salesOrder?->so_number ?? '—' }}</td>
                            <td><strong>{{ $workOrder->item->code }}</strong><br><span class="muted">{{ $workOrder->item->name }}</span></td>
                            <td>{{ number_format((float) $workOrder->qty_planned, 2, ',', '.') }} {{ $workOrder->item->unit->code }}</td>
                            <td>{{ $workOrder->warehouse->name }}</td>
                            <td><span class="status status-{{ $workOrder->status }}">{{ ['planned' => 'Rencana', 'released' => 'Dirilis', 'in_progress' => 'Dikerjakan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$workOrder->status] ?? $workOrder->status }}</span></td>
                            <td class="actions"><a class="button small secondary" href="{{ route('work-orders.show', $workOrder) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="8">Belum ada Work Order. Buat dari Sales Order terkonfirmasi atau untuk persediaan make to stock.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $workOrders->links() }}</div>
    </div>
@endsection
