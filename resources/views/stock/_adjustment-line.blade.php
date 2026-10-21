<div class="adjustment-line transaction-line {{ $isIn ? 'adjustment-in' : 'adjustment-out' }}">
    <div class="form-field">
        <label>Barang <span class="required">*</span></label>
        <select name="lines[{{ $index }}][item_id]" required>
            <option value="">Pilih barang</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}" @selected((string) ($line['item_id'] ?? '') === (string) $item->id)>{{ $item->code }} — {{ $item->name }} ({{ $item->unit->code }})</option>
            @endforeach
        </select>
        @error("lines.$index.item_id")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-field">
        <label>Gudang <span class="required">*</span></label>
        <select name="lines[{{ $index }}][warehouse_id]" data-warehouse-select required>
            <option value="">Pilih gudang</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) ($line['warehouse_id'] ?? '') === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
        @error("lines.$index.warehouse_id")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-field">
        <label>Lokasi</label>
        <select name="lines[{{ $index }}][location_id]" data-location-select>
            <option value="">Tanpa lokasi</option>
            @foreach ($warehouses as $warehouse)
                @foreach ($warehouse->locations as $location)
                    <option value="{{ $location->id }}" data-warehouse-id="{{ $warehouse->id }}" @selected((string) ($line['location_id'] ?? '') === (string) $location->id)>{{ $location->code }} — {{ $location->name }}</option>
                @endforeach
            @endforeach
        </select>
        @error("lines.$index.location_id")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-field">
        <label>{{ $isIn ? 'Kuantitas masuk' : 'Kuantitas keluar' }} <span class="required">*</span></label>
        <input name="lines[{{ $index }}][qty]" type="number" min="0.0001" step="0.0001" value="{{ $line['qty'] ?? '' }}" required>
        @error("lines.$index.qty")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    @if ($isIn)
        <div class="form-field">
            <label>Harga pokok per unit <span class="required">*</span></label>
            <input name="lines[{{ $index }}][unit_cost]" type="number" min="0" step="0.0001" value="{{ $line['unit_cost'] ?? '0' }}" required>
            @error("lines.$index.unit_cost")<span class="field-error">{{ $message }}</span>@enderror
        </div>
    @endif
    <button class="remove-line" type="button" aria-label="Hapus baris">×</button>
</div>
