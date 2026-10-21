<div class="sales-line">
    <div class="form-field">
        <label>Barang <span class="required">*</span></label>
        <select name="lines[{{ $index }}][item_id]" data-item-select required>
            <option value="">Pilih barang</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}" @selected((string) ($line['item_id'] ?? '') === (string) $item->id)>{{ $item->code }} — {{ $item->name }} ({{ ['raw' => 'Bahan baku', 'semi' => 'Setengah jadi', 'finished' => 'Barang jadi'][$item->item_type] ?? $item->item_type }}, {{ $item->unit->code }})</option>
            @endforeach
        </select>
        @error("lines.$index.item_id")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-field">
        <label>Kuantitas <span class="required">*</span></label>
        <input type="number" name="lines[{{ $index }}][qty]" step="0.01" min="0.01" value="{{ $line['qty'] ?? '' }}" required>
        @error("lines.$index.qty")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-field">
        <label>Harga satuan (Master Harga)</label>
        <output class="price-preview" data-price-preview data-ready="false">Pilih barang dan tanggal pesanan</output>
        @error("lines.$index.item_id")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <button class="remove-line" type="button" aria-label="Hapus barang">×</button>
</div>
