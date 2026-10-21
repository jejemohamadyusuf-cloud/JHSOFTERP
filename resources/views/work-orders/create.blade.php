@extends('layouts.app', ['title' => 'Buat Work Order'])

@section('content')
    <div class="section-heading">
        <div><h1>Buat Work Order</h1><p>Pilih make to order dari Sales Order atau make to stock untuk persediaan.</p></div>
        <a class="button secondary" href="{{ route('work-orders.index') }}">← Daftar</a>
    </div>
    <x-pic-guide module="work-orders" />
    @if ($boms->isEmpty())
        <div class="note-panel"><span class="note-icon">i</span><span>Belum ada BOM aktif. Buat atau aktifkan BOM sebelum membuat Work Order.</span></div>
    @elseif ($warehouses->isEmpty())
        <div class="note-panel"><span class="note-icon">i</span><span>Belum ada gudang aktif. Tambahkan atau aktifkan gudang terlebih dahulu.</span></div>
    @endif
    @if ($salesOrderLines->isEmpty() && $makeToStockItems->isEmpty())
        <div class="note-panel"><span class="note-icon">i</span><span>Tidak ada sisa Sales Order terkonfirmasi dan belum ada barang jadi dengan BOM aktif untuk make to stock.</span></div>
    @elseif ($salesOrderLines->isEmpty())
        <div class="note-panel"><span class="note-icon">i</span><span>Tidak ada sisa kuantitas dari Sales Order terkonfirmasi. Work Order make to stock tetap dapat dibuat.</span></div>
    @elseif ($makeToStockItems->isEmpty())
        <div class="note-panel"><span class="note-icon">i</span><span>Make to stock memerlukan barang jadi dengan BOM aktif.</span></div>
    @endif
    <form class="form-card" method="POST" action="{{ route('work-orders.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-field full-width">
                <label for="production_type">Jenis Work Order <span class="required">*</span></label>
                <select id="production_type" name="production_type" required>
                    <option value="make_to_order" @selected(old('production_type', 'make_to_order') === 'make_to_order')>Make to Order — berdasarkan Sales Order</option>
                    <option value="make_to_stock" @selected(old('production_type') === 'make_to_stock')>Make to Stock — untuk persediaan</option>
                </select>
                @error('production_type')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field full-width" id="sales-order-field">
                <label for="sales_order_line_id">Barang dari Sales Order <span class="required">*</span></label>
                <select id="sales_order_line_id" name="sales_order_line_id">
                    <option value="">Pilih Sales Order dan barang</option>
                    @foreach ($salesOrderLines as $line)
                        <option value="{{ $line->id }}" data-item-id="{{ $line->item_id }}" @selected((string) old('sales_order_line_id') === (string) $line->id)>
                            {{ $line->salesOrder->so_number }} — {{ $line->item->code }} {{ $line->item->name }}
                            (sisa {{ number_format((float) $line->qty - (float) ($line->qty_work_ordered ?? 0), 2, ',', '.') }} {{ $line->item->unit->code }})
                        </option>
                    @endforeach
                </select>
                @error('sales_order_line_id')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field full-width" id="make-to-stock-item-field">
                <label for="item_id">Barang jadi untuk stok <span class="required">*</span></label>
                <select id="item_id" name="item_id">
                    <option value="">Pilih barang jadi</option>
                    @foreach ($makeToStockItems as $item)
                        <option value="{{ $item->id }}" data-item-id="{{ $item->id }}" @selected((string) old('item_id') === (string) $item->id)>
                            {{ $item->code }} — {{ $item->name }} ({{ $item->unit->code }})
                        </option>
                    @endforeach
                </select>
                @error('item_id')<span class="field-error">{{ $message }}</span>@enderror
                @if ($makeToStockItems->isEmpty())
                    <span class="muted">Aktifkan BOM untuk barang jadi agar dapat dipilih.</span>
                @endif
            </div>
            <div class="form-field full-width">
                <label for="bom_id">BOM aktif <span class="required">*</span></label>
                <select id="bom_id" name="bom_id" required @disabled($boms->isEmpty())>
                    <option value="">Pilih BOM barang</option>
                    @foreach ($boms as $bom)
                        <option value="{{ $bom->id }}" data-item-id="{{ $bom->item_id }}" @selected((string) old('bom_id') === (string) $bom->id)>
                            {{ $bom->item->code }} — {{ $bom->item->name }} (Versi {{ $bom->version }})
                        </option>
                    @endforeach
                </select>
                @error('bom_id')<span class="field-error">{{ $message }}</span>@enderror
                <span id="bom-unavailable" class="field-error" role="status" hidden>Barang yang dipilih belum memiliki BOM aktif.</span>
            </div>
            <div class="form-field">
                <label for="qty_planned">Kuantitas rencana <span class="required">*</span></label>
                <input id="qty_planned" name="qty_planned" type="number" step="0.01" min="0.01" value="{{ old('qty_planned') }}" required>
                @error('qty_planned')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="warehouse_id">Gudang hasil produksi <span class="required">*</span></label>
                <select id="warehouse_id" name="warehouse_id" required @disabled($warehouses->isEmpty())>
                    <option value="">Pilih gudang</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id') === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                    @endforeach
                </select>
                @error('warehouse_id')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="start_date">Rencana mulai</label>
                <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}">
                @error('start_date')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="end_date">Rencana selesai</label>
                <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}">
                @error('end_date')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field full-width">
                <label for="notes">Catatan</label>
                <textarea id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                @error('notes')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="form-actions">
            <a class="button secondary" href="{{ route('work-orders.index') }}">Batal</a>
            <button class="button primary" type="submit" @disabled($boms->isEmpty() || $warehouses->isEmpty())>Simpan Work Order</button>
        </div>
    </form>
    <div class="note-panel"><span class="note-icon">i</span><span>Work Order yang dibuat berstatus <strong>Rencana</strong>. Pembuatan WO tidak memotong atau mencadangkan stok dan belum berarti produksi dapat dimulai.</span></div>
    <script>
        (() => {
            const productionType = document.getElementById('production_type');
            const salesOrderField = document.getElementById('sales-order-field');
            const salesOrderLine = document.getElementById('sales_order_line_id');
            const makeToStockItemField = document.getElementById('make-to-stock-item-field');
            const makeToStockItem = document.getElementById('item_id');
            const bomSelect = document.getElementById('bom_id');
            const bomUnavailable = document.getElementById('bom-unavailable');

            const updateForm = () => {
                const isMakeToStock = productionType.value === 'make_to_stock';
                salesOrderField.hidden = isMakeToStock;
                salesOrderLine.disabled = isMakeToStock;
                salesOrderLine.required = !isMakeToStock;
                makeToStockItemField.hidden = !isMakeToStock;
                makeToStockItem.disabled = !isMakeToStock;
                makeToStockItem.required = isMakeToStock;

                const itemId = isMakeToStock
                    ? makeToStockItem.selectedOptions[0]?.dataset.itemId
                    : salesOrderLine.selectedOptions[0]?.dataset.itemId;
                let hasMatchingBom = false;

                [...bomSelect.options].forEach((option) => {
                    if (!option.value) return;
                    option.hidden = Boolean(itemId) && option.dataset.itemId !== itemId;
                    option.disabled = option.hidden;
                    hasMatchingBom ||= !option.disabled;
                });

                if (bomSelect.selectedOptions[0]?.disabled) bomSelect.value = '';
                if (!bomSelect.value) {
                    const firstVisible = [...bomSelect.options].find((option) => option.value && !option.disabled);
                    if (firstVisible) bomSelect.value = firstVisible.value;
                }

                bomUnavailable.hidden = !itemId || hasMatchingBom;
                bomSelect.disabled = !itemId || !hasMatchingBom || {{ $boms->isEmpty() ? 'true' : 'false' }};
            };

            productionType.addEventListener('change', updateForm);
            salesOrderLine.addEventListener('change', updateForm);
            makeToStockItem.addEventListener('change', updateForm);
            updateForm();
        })();
    </script>
@endsection
