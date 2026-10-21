<div class="purchase-line transaction-line">
    <div class="form-field">
        <label>Barang <span class="required">*</span></label>
        <select name="lines[{{ $index }}][item_id]" data-item-select required>
            <option value="">Pilih barang</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}" @selected((string) ($line['item_id'] ?? '') === (string) $item->id)>{{ $item->code }} — {{ $item->name }} ({{ $item->unit->code }})</option>
            @endforeach
        </select>
        @error("lines.$index.item_id")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-field">
        <label>Kuantitas <span class="required">*</span></label>
        <input name="lines[{{ $index }}][qty]" type="number" min="0.0001" step="0.0001" value="{{ $line['qty'] ?? '' }}" required>
        @error("lines.$index.qty")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-field">
        <label>Harga beli per unit</label>
        <output class="price-preview" data-price-preview data-ready="false">Pilih barang dan tanggal PO</output>
        @error("lines.$index.item_id")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <button class="remove-line" type="button" aria-label="Hapus barang">×</button>
</div>
