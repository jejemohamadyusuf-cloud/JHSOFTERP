@extends('layouts.app', ['title' => 'Buat Delivery'])

@section('content')
    <div class="section-heading">
        <div><h1>Buat Delivery</h1><p>Delivery yang disimpan langsung mengurangi stok gudang.</p></div>
        <a class="button secondary" href="{{ route('deliveries.index') }}">← Daftar</a>
    </div>
    <form class="form-card" method="GET" action="{{ route('deliveries.create') }}">
        <div class="form-grid">
            <div class="form-field full-width">
                <label for="sales_order_id">Sales Order terkonfirmasi</label>
                <select id="sales_order_id" name="sales_order_id" required>
                    <option value="">Pilih Sales Order</option>
                    @foreach ($salesOrders as $order)
                        <option value="{{ $order->id }}" @selected($salesOrder?->id === $order->id)>
                            {{ $order->so_number }} — {{ $order->customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-actions"><button class="button secondary" type="submit">Tampilkan barang</button></div>
    </form>

    @if ($salesOrder)
        @if ($warehouses->isEmpty())
            <div class="note-panel"><span class="note-icon">i</span><span>Tambahkan gudang aktif sebelum membuat delivery.</span></div>
        @elseif ($salesOrder->lines->isEmpty())
            <div class="note-panel"><span class="note-icon">i</span><span>Sales Order ini tidak memiliki baris barang untuk dikirim.</span></div>
        @elseif (! $hasRemainingLines)
            <div class="note-panel"><span class="note-icon">i</span><span>Semua kuantitas Sales Order ini sudah terkirim.</span></div>
        @else
            <form class="form-card" method="POST" action="{{ route('deliveries.store') }}">
                @csrf
                <input type="hidden" name="sales_order_id" value="{{ $salesOrder->id }}">
                <div class="form-grid">
                    <div class="form-field"><label for="delivery_date">Tanggal pengiriman</label><input id="delivery_date" name="delivery_date" type="date" value="{{ old('delivery_date', now()->toDateString()) }}" required></div>
                    <div class="form-field">
                        <label for="warehouse_id">Gudang sumber</label>
                        <select id="warehouse_id" name="warehouse_id" required>
                            <option value="">Pilih gudang</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id') === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field full-width"><label for="notes">Catatan</label><textarea id="notes" name="notes" rows="2">{{ old('notes') }}</textarea></div>
                </div>
                @error('warehouse_id')<span class="field-error">{{ $message }}</span>@enderror
                <div class="section-heading"><div><h2>Barang yang dikirim</h2><p>Kosongkan kuantitas untuk melewati barang pada delivery ini.</p></div></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Barang</th><th>Pesanan</th><th>Terkirim</th><th>Sisa</th><th>Kirim</th><th>Lokasi</th></tr></thead>
                        <tbody>
                            @foreach ($salesOrder->lines as $index => $line)
                                @php
                                    $delivered = $line->deliveryLines->filter(fn ($deliveryLine) => $deliveryLine->delivery?->status === 'delivered')->sum('qty');
                                    $remaining = max(0, (float) $line->qty - (float) $delivered);
                                @endphp
                                <tr>
                                    <td><strong>{{ $line->item->code }}</strong><br><span class="muted">{{ $line->item->name }}</span></td>
                                    <td>{{ number_format((float) $line->qty, 2, ',', '.') }} {{ $line->item->unit->code }}</td>
                                    <td>{{ number_format((float) $delivered, 2, ',', '.') }}</td>
                                    <td>{{ number_format($remaining, 2, ',', '.') }}</td>
                                    <td>
                                        @if ($remaining > 0)
                                            <input type="hidden" name="lines[{{ $index }}][sales_order_line_id]" value="{{ $line->id }}">
                                            <input aria-label="Kuantitas {{ $line->item->name }}" name="lines[{{ $index }}][qty]" type="number" min="0.01" max="{{ number_format($remaining, 2, '.', '') }}" step="0.01" value="{{ old("lines.{$index}.qty") }}">
                                            @error("lines.{$index}.qty")<span class="field-error">{{ $message }}</span>@enderror
                                        @else
                                            <span class="muted">Sudah terpenuhi</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($remaining > 0)
                                            <select aria-label="Lokasi {{ $line->item->name }}" name="lines[{{ $index }}][location_id]" class="delivery-location">
                                                <option value="">Tanpa lokasi</option>
                                                @foreach ($warehouses as $warehouse)
                                                    @foreach ($warehouse->locations as $location)
                                                        <option value="{{ $location->id }}" data-warehouse-id="{{ $warehouse->id }}">{{ $location->code }} — {{ $location->name }}</option>
                                                    @endforeach
                                                @endforeach
                                            </select>
                                            @error("lines.{$index}.location_id")<span class="field-error">{{ $message }}</span>@enderror
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('lines')<span class="field-error">{{ $message }}</span>@enderror
                @error('sales_order_id')<span class="field-error">{{ $message }}</span>@enderror
                <div class="form-actions"><button class="button primary" type="submit" @disabled($warehouses->isEmpty())>Posting delivery dan kurangi stok</button></div>
            </form>
        @endif
    @endif
    <script>
        (() => {
            const warehouse = document.getElementById('warehouse_id');
            const updateLocations = () => {
                document.querySelectorAll('.delivery-location option[data-warehouse-id]').forEach((option) => {
                    option.hidden = Boolean(warehouse.value) && option.dataset.warehouseId !== warehouse.value;
                });
                document.querySelectorAll('.delivery-location').forEach((select) => {
                    if (select.selectedOptions[0]?.hidden) select.value = '';
                });
            };
            warehouse?.addEventListener('change', updateLocations);
            updateLocations();
        })();
    </script>
@endsection
