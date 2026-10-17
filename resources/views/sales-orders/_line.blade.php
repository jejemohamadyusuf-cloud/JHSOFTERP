<div class="sales-line">
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
        <label>Kuantitas <span class="required">*</span></label>
        <input type="number" name="lines[{{ $index }}][qty]" step="0.0001" min="0.0001" value="{{ $line['qty'] ?? '' }}" required>
        @error("lines.$index.qty")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-field">
        <label>Harga satuan (Rp) <span class="required">*</span></label>
        <input type="number" name="lines[{{ $index }}][unit_price]" step="0.0001" min="0" value="{{ $line['unit_price'] ?? '' }}" required>
        @error("lines.$index.unit_price")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <button class="remove-line" type="button" aria-label="Hapus barang">×</button>
</div>
