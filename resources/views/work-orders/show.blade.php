@extends('layouts.app', ['title' => $workOrder->wo_number])

@section('content')
    <div class="section-heading">
        <div><h1>{{ $workOrder->wo_number }}</h1><p>Work Order · Rencana produksi</p></div>
        <div class="actions">
            <a class="button secondary" href="{{ route('work-orders.index') }}">← Daftar</a>
            @if ($workOrder->salesOrder && auth()->user()->hasRole('sales'))
                <a class="button secondary" href="{{ route('sales-orders.show', $workOrder->salesOrder) }}">Sales Order</a>
            @endif
        </div>
    </div>
    <x-pic-guide module="work-orders" />
    <div class="order-summary">
        <div class="summary-card"><span>Status</span><strong><span class="status status-{{ $workOrder->status }}">{{ ['planned' => 'Rencana', 'released' => 'Dirilis', 'in_progress' => 'Dikerjakan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$workOrder->status] ?? $workOrder->status }}</span></strong></div>
        <div class="summary-card"><span>Jenis Work Order</span><strong>{{ $workOrder->production_type === 'make_to_stock' ? 'Make to Stock' : 'Make to Order' }}</strong></div>
        <div class="summary-card"><span>Sales Order</span><strong>{{ $workOrder->salesOrder?->so_number ?? '— (Persediaan)' }}</strong></div>
        <div class="summary-card"><span>Barang</span><strong>{{ $workOrder->item->code }} — {{ $workOrder->item->name }}</strong></div>
        <div class="summary-card"><span>Kuantitas rencana</span><strong>{{ number_format((float) $workOrder->qty_planned, 2, ',', '.') }} {{ $workOrder->item->unit->code }}</strong></div>
        <div class="summary-card"><span>Gudang hasil</span><strong>{{ $workOrder->warehouse->name }}</strong></div>
        <div class="summary-card"><span>BOM</span><strong>{{ $workOrder->bom->version }} — {{ $workOrder->bom->item->name }}</strong></div>
        <div class="summary-card"><span>Rencana mulai</span><strong>{{ $workOrder->start_date?->format('d M Y') ?? '—' }}</strong></div>
        <div class="summary-card"><span>Rencana selesai</span><strong>{{ $workOrder->end_date?->format('d M Y') ?? '—' }}</strong></div>
    </div>
    @if ($workOrder->notes)
        <div class="note-panel"><span class="note-icon">i</span><span><strong>Catatan:</strong> {{ $workOrder->notes }}</span></div>
    @endif
    @include('work-orders.execution', ['workOrder' => $workOrder, 'locations' => $locations])
@endsection
