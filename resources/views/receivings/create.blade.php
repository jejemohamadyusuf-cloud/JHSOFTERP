@extends('layouts.app', ['title' => 'Buat Receiving'])

@section('content')
    <div class="section-heading">
        <div><h1>Buat Receiving</h1><p>Pilih PO sebagai referensi lalu masukkan barang yang benar-benar datang.</p></div>
        <a class="button secondary" href="{{ route('receivings.index') }}">← Daftar Receiving</a>
    </div>
    <x-pic-guide module="purchase-orders" />
    <form class="form-card" method="GET" action="{{ route('receivings.create') }}">
        <div class="form-grid">
            <div class="form-field">
                <label for="purchase_order_id">Nomor PO <span class="required">*</span></label>
                <select id="purchase_order_id" name="purchase_order_id" required>
                    <option value="">Pilih PO</option>
                    @foreach ($orders as $purchaseOrder)
                        <option value="{{ $purchaseOrder->id }}" @selected((int) request('purchase_order_id') === $purchaseOrder->id)>
                            {{ $purchaseOrder->po_number }} — {{ $purchaseOrder->supplier->name }}
                        </option>
                    @endforeach
                </select>
                @error('purchase_order_id')<span class="field-error">{{ $message }}</span>@enderror
                @if ($orders->isEmpty())
                    <span class="muted">Tidak ada PO dengan kuantitas yang masih dapat diterima.</span>
                @endif
            </div>
            <div class="form-field form-actions"><button class="button secondary" type="submit">Muat PO</button></div>
        </div>
    </form>

    @if ($order)
        <div class="note-panel"><span class="note-icon">i</span><span>Referensi Receiving: <strong>{{ $order->po_number }}</strong> · {{ $order->supplier->name }}</span></div>
        <form class="form-card" method="POST" action="{{ route('receivings.store') }}">
            @csrf
            <input type="hidden" name="purchase_order_id" value="{{ $order->id }}">
            @error('purchase_order_id')<div class="flash error">{{ $message }}</div>@enderror
            @error('lines')<div class="flash error">{{ $message }}</div>@enderror
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
                            @php($receivableQty = floor(($remaining + 0.0000001) * 100) / 100)
                            @if ($remaining > 0)
                                <tr>
                                    <td>
                                        <strong>{{ $line->item->code }}</strong><br><span class="muted">{{ $line->item->name }}</span>
                                        <input type="hidden" name="lines[{{ $receiptLineIndex }}][purchase_order_line_id]" value="{{ $line->id }}">
                                    </td>
                                    <td>{{ number_format($remaining, 2, ',', '.') }} {{ $line->unit->code }}</td>
                                    <td>
                                        <input class="receipt-qty" name="lines[{{ $receiptLineIndex }}][qty]" type="number" min="0" max="{{ number_format($receivableQty, 2, '.', '') }}" step="0.01" value="{{ old("lines.$receiptLineIndex.qty", number_format($receivableQty, 2, '.', '')) }}" required>
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
                <button class="button primary" type="submit" @disabled($warehouses->isEmpty())>Simpan Receiving</button>
            </div>
        </form>
    @elseif (request()->filled('purchase_order_id'))
        <div class="note-panel"><span class="note-icon">i</span><span>PO tidak tersedia untuk receiving baru atau seluruh kuantitasnya sudah diterima.</span></div>
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
