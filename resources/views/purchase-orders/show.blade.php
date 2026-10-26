@extends('layouts.app', ['title' => $order->po_number])

@section('content')
    <div class="section-heading">
        <div><h1>{{ $order->po_number }}</h1><p>Purchase Order · {{ $order->order_date->format('d M Y') }}</p></div>
        <div class="actions">
            @if (in_array($order->status, ['open', 'ordered', 'partial', 'draft'], true) && $order->lines->contains(fn ($line) => (float) $line->qty > (float) $line->qty_received))
                <a class="button primary" href="{{ route('receivings.create', ['purchase_order_id' => $order->id]) }}">Buat Receiving</a>
            @endif
            <a class="button secondary" href="{{ route('purchase-orders.index') }}">← Daftar PO</a>
        </div>
    </div>
    <x-pic-guide module="purchase-orders" />
    <div class="order-summary">
        <div class="summary-card"><span>Status</span><strong><span class="status status-{{ $order->status }}">{{ ['open' => 'Terbuka', 'partial' => 'Diterima sebagian', 'received' => 'Selesai', 'draft' => 'Draft'][$order->status] ?? $order->status }}</span></strong></div>
        <div class="summary-card"><span>Supplier</span><strong>{{ $order->supplier->code }} — {{ $order->supplier->name }}</strong></div>
        <div class="summary-card"><span>Tanggal PO</span><strong>{{ $order->order_date->format('d M Y') }}</strong></div>
        <div class="summary-card"><span>Estimasi tiba</span><strong>{{ $order->expected_date?->format('d M Y') ?? '—' }}</strong></div>
        <div class="summary-card"><span>Total PO</span><strong>Rp {{ number_format((float) $order->total_amount, 2, ',', '.') }}</strong></div>
    </div>
    <div class="table-card">
        <div class="table-toolbar"><strong>Barang yang dipesan</strong><span>{{ $order->lines->count() }} baris</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Barang</th><th>Qty PO</th><th>Qty diterima</th><th>Sisa</th><th>Harga beli satuan</th><th>Jumlah</th></tr></thead>
                <tbody>
                    @foreach ($order->lines as $line)
                        <tr>
                            <td><strong>{{ $line->item->code }}</strong><br><span class="muted">{{ $line->item->name }}</span></td>
                            <td>{{ number_format((float) $line->qty, 2, ',', '.') }} {{ $line->unit->code }}</td>
                            <td>{{ number_format((float) $line->qty_received, 2, ',', '.') }} {{ $line->unit->code }}</td>
                            <td>{{ number_format(max(0, (float) $line->qty - (float) $line->qty_received), 2, ',', '.') }} {{ $line->unit->code }}</td>
                            <td>Rp {{ number_format((float) $line->unit_price, 4, ',', '.') }}</td>
                            <td>Rp {{ number_format(round((float) $line->qty * (float) $line->unit_price, 2), 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="section-heading"><div><h2>Riwayat receiving</h2><p>Barang masuk dicatat dari menu Receiving dan memperbarui stok.</p></div></div>
    @forelse ($order->goodsReceipts as $receipt)
        <div class="table-card receipt-history">
            <div class="table-toolbar"><strong>Referensi PO: {{ $receipt->reference_number }}</strong><span>{{ $receipt->receipt_date->format('d M Y') }} · {{ $receipt->warehouse->name }}</span></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Barang</th><th>Kuantitas diterima</th><th>Lokasi</th><th>Biaya satuan</th></tr></thead>
                    <tbody>
                        @foreach ($receipt->lines as $line)
                            <tr>
                                <td>{{ $line->item->code }} — {{ $line->item->name }}</td>
                                <td>{{ number_format((float) $line->qty, 2, ',', '.') }} {{ $line->item->unit->code }}</td>
                                <td>{{ $line->location?->code ?? '—' }}</td>
                                <td>Rp {{ number_format((float) $line->unit_cost, 4, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="note-panel"><span class="note-icon">i</span><span>Belum ada receiving untuk PO ini.</span></div>
    @endforelse
    @if ($order->notes)
        <div class="note-panel"><span class="note-icon">i</span><span><strong>Catatan PO:</strong> {{ $order->notes }}</span></div>
    @endif
@endsection
