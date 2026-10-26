<div class="item-unit-conversion">
    <div class="form-field">
        <label>Satuan kemasan</label>
        <select name="conversions[{{ $index }}][unit_id]">
            <option value="">Pilih satuan</option>
            @foreach ($conversionUnits as $unit)
                <option value="{{ $unit->id }}" @selected((string) ($conversion['unit_id'] ?? '') === (string) $unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>
            @endforeach
        </select>
        @error("conversions.$index.unit_id")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-field">
        <label>Jumlah satuan dasar per kemasan</label>
        <input name="conversions[{{ $index }}][base_units]" type="number" min="2" step="1" value="{{ $conversion['base_units'] ?? '' }}">
        @error("conversions.$index.base_units")<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <button class="remove-line" type="button" aria-label="Hapus satuan kemasan">×</button>
</div>
