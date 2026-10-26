@extends('layouts.app', ['title' => $delivery->delivery_number])

@section('content')
    <div class="section-heading">
        <div><h1>{{ $delivery->delivery_number }}</h1><p>Delivery · {{ $delivery->delivery_date->format('d/m/Y') }}</p></div>
        <div class="actions"><a class="button secondary" href="{{ route('deliveries.index') }}">← Daftar delivery</a>@if (auth()->user()->hasRole('sales'))<a class="button secondary" href="{{ route('sales-orders.show', $delivery->salesOrder) }}">Sales Order</a>@endif</div>
    </div>
    <div class="order-summary">
        <div class="summary-card"><span>Status</span><strong>Terkirim</strong></div>
        <div class="summary-card"><span>Pelanggan</span><strong>{{ $delivery->salesOrder->customer->name }}</strong></div>
        <div class="summary-card"><span>Gudang</span><strong>{{ $delivery->warehouse->name }}</strong></div>
        <div class="summary-card"><span>Sales Order</span><strong>{{ $delivery->salesOrder->so_number }}</strong></div>
    </div>
    <div class="table-card">
        <div class="table-toolbar"><strong>Barang terkirim</strong><span>{{ $delivery->lines->count() }} baris</span></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Barang</th><th>Kuantitas</th><th>Lokasi</th></tr></thead>
            <tbody>
                @foreach ($delivery->lines as $line)
                    <tr><td>{{ $line->item->code }} — {{ $line->item->name }}</td><td>{{ number_format((float) $line->qty, 2, ',', '.') }} {{ $line->item->unit->code }}</td><td>{{ $line->location?->name ?? 'Tanpa lokasi' }}</td></tr>
                @endforeach
            </tbody>
        </table></div>
    </div>
    @if ($delivery->notes)<div class="note-panel"><span class="note-icon">i</span><span>{{ $delivery->notes }}</span></div>@endif
    @if ($delivery->invoices->isEmpty() && auth()->user()->hasRole('finance'))
        <form class="form-card" method="POST" action="{{ route('deliveries.invoice.store', $delivery) }}">
            @csrf
            <div class="section-heading"><div><h2>Terbitkan Invoice</h2><p>Invoice mengambil kuantitas terkirim dan harga snapshot Sales Order.</p></div></div>
            <div class="form-grid">
                <div class="form-field"><label for="invoice_date">Tanggal invoice</label><input id="invoice_date" name="invoice_date" type="date" value="{{ now()->toDateString() }}" required></div>
                <div class="form-field"><label for="due_date">Jatuh tempo</label><input id="due_date" name="due_date" type="date"></div>
                <div class="form-field full-width"><label for="invoice_notes">Catatan</label><textarea id="invoice_notes" name="notes" rows="2"></textarea></div>
            </div>
            @error('delivery')<span class="field-error">{{ $message }}</span>@enderror
            <div class="form-actions"><button class="button primary" type="submit">Terbitkan invoice</button></div>
        </form>
    @elseif ($delivery->invoices->isEmpty())
        <div class="note-panel"><span class="note-icon">i</span><span>Invoice untuk delivery ini akan dibuat oleh peran Keuangan.</span></div>
    @else
        @if (auth()->user()->hasRole('finance'))
        <div class="note-panel"><span class="note-icon">i</span><span>Invoice: <a class="auth-link" href="{{ route('invoices.show', $delivery->invoices->first()) }}">{{ $delivery->invoices->first()->invoice_number }}</a></span></div>
        @else
            <div class="note-panel"><span class="note-icon">i</span><span>Invoice untuk delivery ini sudah diterbitkan.</span></div>
        @endif
    @endif
@endsection
