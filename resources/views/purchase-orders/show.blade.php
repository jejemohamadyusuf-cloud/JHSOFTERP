@extends('layouts.app', ['title' => $order->po_number])

@section('content')
    <div class="section-heading">
        <div><h1>{{ $order->po_number }}</h1><p>Purchase Order · {{ $order->order_date->format('d M Y') }}</p></div>
        <a class="button secondary" href="{{ route('purchase-orders.index') }}">← Daftar PO</a>
    </div>
    <x-pic-guide module="purchase-orders" />
    @error('purchase_order')<div class="flash error">{{ $message }}</div>@enderror
    @error('lines')<div class="flash error">{{ $message }}</div>@enderror
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
                            <td>{{ number_format((float) $line->qty, 4, ',', '.') }} {{ $line->unit->code }}</td>
                            <td>{{ number_format((float) $line->qty_received, 4, ',', '.') }} {{ $line->unit->code }}</td>
                            <td>{{ number_format(max(0, (float) $line->qty - (float) $line->qty_received), 4, ',', '.') }} {{ $line->unit->code }}</td>
                            <td>Rp {{ number_format((float) $line->unit_price, 4, ',', '.') }}</td>
                            <td>Rp {{ number_format(round((float) $line->qty * (float) $line->unit_price, 2), 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if (in_array($order->status, ['open', 'ordered', 'partial', 'draft'], true) && $order->lines->contains(fn ($line) => (float) $line->qty > (float) $line->qty_received))
        <div class="section-heading"><div><h2>Terima barang</h2><p>Isi kuantitas pada baris yang diterima; nilai 0 akan melewati baris tersebut.</p></div></div>
        <form class="form-card" method="POST" action="{{ route('purchase-orders.receipts.store', $order) }}">
            @csrf
            <div class="form-grid">
                <div class="form-field">
                    <label for="warehouse_id">Gudang penerimaan <span class="required">*</span></label>
                    <select id="warehouse_id" name="warehouse_id" required>
                        <option value="">Pilih gudang</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id') === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    @error('warehouse_id')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-field">
                    <label for="receipt_date">Tanggal penerimaan <span class="required">*</span></label>
                    <input id="receipt_date" name="receipt_date" type="date" value="{{ old('receipt_date', now()->format('Y-m-d')) }}" required>
                    @error('receipt_date')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-field full-width">
                    <label for="receipt_notes">Catatan</label>
                    <input id="receipt_notes" name="notes" value="{{ old('notes') }}" maxlength="5000">
                    @error('notes')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="table-wrap receipt-table">
                <table>
                    <thead><tr><th>Barang PO</th><th>Sisa PO</th><th>Qty diterima sekarang</th><th>Lokasi</th></tr></thead>
                    <tbody>
                        @php($receiptLineIndex = 0)
                        @foreach ($order->lines as $line)
                            @php($remaining = max(0, (float) $line->qty - (float) $line->qty_received))
                            @if ($remaining > 0)
                                <tr>
                                    <td>
                                        <strong>{{ $line->item->code }}</strong><br><span class="muted">{{ $line->item->name }}</span>
                                        <input type="hidden" name="lines[{{ $receiptLineIndex }}][purchase_order_line_id]" value="{{ $line->id }}">
                                    </td>
                                    <td>{{ number_format($remaining, 4, ',', '.') }} {{ $line->unit->code }}</td>
                                    <td>
                                        <input class="receipt-qty" name="lines[{{ $receiptLineIndex }}][qty]" type="number" min="0" max="{{ number_format($remaining, 4, '.', '') }}" step="0.0001" value="{{ old("lines.$receiptLineIndex.qty", number_format($remaining, 4, '.', '')) }}" required>
                                        @error("lines.$receiptLineIndex.qty")<span class="field-error">{{ $message }}</span>@enderror
                                    </td>
                                    <td>
                                        <select name="lines[{{ $receiptLineIndex }}][location_id]" data-location-select>
                                            <option value="">Tanpa lokasi</option>
                                            @foreach ($warehouses as $warehouse)
                                                @foreach ($warehouse->locations as $location)
                                                    <option value="{{ $location->id }}" data-warehouse-id="{{ $warehouse->id }}" @selected((string) old("lines.$receiptLineIndex.location_id") === (string) $location->id)>{{ $location->code }} — {{ $location->name }}</option>
                                                @endforeach
                                            @endforeach
                                        </select>
                                        @error("lines.$receiptLineIndex.location_id")<span class="field-error">{{ $message }}</span>@enderror
                                    </td>
                                </tr>
                                @php($receiptLineIndex++)
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <button class="button primary" type="submit" @disabled($warehouses->isEmpty())>Simpan Penerimaan</button>
            </div>
        </form>
    @endif
    <div class="section-heading"><div><h2>Riwayat penerimaan</h2><p>Setiap penerimaan menambah stok dan memperbarui kuantitas PO.</p></div></div>
    @forelse ($order->goodsReceipts as $receipt)
        <div class="table-card receipt-history">
            <div class="table-toolbar"><strong>{{ $receipt->gr_number }}</strong><span>{{ $receipt->receipt_date->format('d M Y') }} · {{ $receipt->warehouse->name }}</span></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Barang</th><th>Kuantitas diterima</th><th>Lokasi</th><th>Biaya satuan</th></tr></thead>
                    <tbody>
                        @foreach ($receipt->lines as $line)
                            <tr>
                                <td>{{ $line->item->code }} — {{ $line->item->name }}</td>
                                <td>{{ number_format((float) $line->qty, 4, ',', '.') }} {{ $line->item->unit->code }}</td>
                                <td>{{ $line->location?->code ?? '—' }}</td>
                                <td>Rp {{ number_format((float) $line->unit_cost, 4, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="note-panel"><span class="note-icon">i</span><span>Belum ada penerimaan untuk PO ini.</span></div>
    @endforelse
    @if ($order->notes)
        <div class="note-panel"><span class="note-icon">i</span><span><strong>Catatan PO:</strong> {{ $order->notes }}</span></div>
    @endif
    <script>
        (() => {
            const warehouse = document.getElementById('warehouse_id');
            if (!warehouse) return;
            const updateLocations = () => document.querySelectorAll('[data-location-select]').forEach((select) => {
                [...select.options].forEach((option) => {
                    if (!option.value) return;
                    option.hidden = option.dataset.warehouseId !== warehouse.value;
                    option.disabled = option.hidden;
                });
                if (select.selectedOptions[0]?.disabled) select.value = '';
            });
            warehouse.addEventListener('change', updateLocations);
            updateLocations();
        })();
    </script>
@endsection
