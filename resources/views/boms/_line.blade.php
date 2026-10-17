<div class="bom-line">
    <div class="form-field">
        <label>Komponen <span class="required">*</span></label>
        <select name="lines[{{ $index }}][component_item_id]" required>
            <option value="">Pilih komponen</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}" @selected((string) ($line['component_item_id'] ?? '') === (string) $item->id)>{{ $item->code }} — {{ $item->name }}</option>
            @endforeach
        </select>
        @error("lines.$index.component_item_id")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-field">
        <label>Kuantitas <span class="required">*</span></label>
        <input type="number" name="lines[{{ $index }}][qty]" step="0.0001" min="0.0001" value="{{ $line['qty'] ?? '' }}" required>
        @error("lines.$index.qty")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-field">
        <label>Satuan <span class="required">*</span></label>
        <select name="lines[{{ $index }}][unit_id]" required>
            <option value="">Pilih satuan</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected((string) ($line['unit_id'] ?? '') === (string) $unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>
            @endforeach
        </select>
        @error("lines.$index.unit_id")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-field">
        <label>Scrap (%)</label>
        <input type="number" name="lines[{{ $index }}][scrap_percent]" step="0.0001" min="0" max="100" value="{{ $line['scrap_percent'] ?? '0' }}" required>
        @error("lines.$index.scrap_percent")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <button class="remove-line" type="button" aria-label="Hapus komponen">×</button>
</div>
