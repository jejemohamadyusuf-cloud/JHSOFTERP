@extends('layouts.app', ['title' => 'Stock Take'])

@section('content')
    <div class="section-heading">
        <div>
            <h1>Stock Take</h1>
            <p>Hitung fisik per barang dan lokasi. Selisih baru diterapkan setelah daftar stock take disimpan.</p>
        </div>
        <a class="button secondary" href="{{ route('stock.index') }}">← Daftar Stok</a>
    </div>
    <x-pic-guide module="stock" />

    @if ($items->isEmpty() || $warehouses->isEmpty())
        <div class="note-panel"><span class="note-icon">i</span><span>Barang dan gudang aktif harus tersedia untuk melakukan stock take.</span></div>
    @endif
    @if ($errors->any())
        <div class="flash error">Periksa kembali data stock take. Stok belum direkonsiliasi.</div>
        @foreach ($errors->getMessages() as $key => $messages)
            @if (str_starts_with($key, 'lines.'))
                <div class="field-error">{{ implode(' ', $messages) }}</div>
            @endif
        @endforeach
    @endif

    <form class="form-card stock-take-card" method="POST" action="{{ route('stock-takes.store') }}" id="stock-take-form">
        @csrf
        <div class="stock-take-heading">
            <div><h2>Daftar hitungan</h2><p>Baris yang sudah dikonfirmasi akan muncul di sini. Daftar ini dapat di-scroll.</p></div>
            <span class="stock-take-count"><span id="stock-take-count">{{ count(old('lines', [])) }}</span> barang</span>
        </div>
        <div class="table-wrap stock-take-table-wrap">
            <table class="stock-take-table">
                <thead>
                    <tr>
                        <th>Barang</th><th>Gudang / lokasi</th><th>Stok sistem</th><th>Stok fisik</th><th>Selisih</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="stock-take-rows">
                    @foreach (old('lines', []) as $index => $line)
                        @php($item = $items->firstWhere('id', (int) ($line['item_id'] ?? 0)))
                        @php($warehouse = $warehouses->firstWhere('id', (int) ($line['warehouse_id'] ?? 0)))
                        @php($location = $warehouse?->locations->firstWhere('id', (int) ($line['location_id'] ?? 0)))
                        @php($systemQty = (float) ($line['system_qty'] ?? 0))
                        @php($physicalQty = (float) ($line['physical_qty'] ?? 0))
                        <tr data-position="{{ $line['item_id'] }}|{{ $line['warehouse_id'] }}|{{ $line['location_id'] ?? '' }}">
                            <td>{{ $item?->code }} — {{ $item?->name }}</td>
                            <td>{{ $warehouse?->code }} — {{ $warehouse?->name }} / {{ $location?->code ?? 'Tanpa lokasi' }}</td>
                            <td>{{ $item?->formatStockQuantity($systemQty) }}</td>
                            <td>{{ $item?->formatStockQuantity($physicalQty) }}</td>
                            <td>{{ $item?->formatStockQuantity(abs($physicalQty - $systemQty)) }} ({{ $physicalQty >= $systemQty ? '+' : '−' }})</td>
                            <td>
                                <button class="remove-line" type="button" data-remove-row aria-label="Hapus hitungan">×</button>
                                <div hidden>
                                    <input type="hidden" name="lines[{{ $index }}][item_id]" value="{{ $line['item_id'] }}">
                                    <input type="hidden" name="lines[{{ $index }}][warehouse_id]" value="{{ $line['warehouse_id'] }}">
                                    <input type="hidden" name="lines[{{ $index }}][location_id]" value="{{ $line['location_id'] ?? '' }}">
                                    <input type="hidden" name="lines[{{ $index }}][system_qty]" value="{{ $line['system_qty'] }}">
                                    <input type="hidden" name="lines[{{ $index }}][physical_qty]" value="{{ $line['physical_qty'] }}">
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="empty-stock-take-row" id="stock-take-empty" @if (count(old('lines', []))) hidden @endif>
                        <td colspan="6">Belum ada hitungan. Isi form di bawah, lalu klik OK untuk menambahkannya.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="stock-take-input">
            <div class="stock-take-heading">
                <div><h2>Input hitungan fisik</h2><p>Masukkan jumlah dalam satuan dasar. Contoh: isi 193 pcs untuk 1 dus, 9 pack, dan 3 pcs.</p></div>
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label for="count-item">Barang <span class="required">*</span></label>
                    <select id="count-item">
                        <option value="">Pilih barang</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->code }} — {{ $item->name }} ({{ $item->unit->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label for="count-warehouse">Gudang <span class="required">*</span></label>
                    <select id="count-warehouse">
                        <option value="">Pilih gudang</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label for="count-location">Lokasi</label>
                    <select id="count-location">
                        <option value="">Tanpa lokasi</option>
                        @foreach ($warehouses as $warehouse)
                            @foreach ($warehouse->locations as $location)
                                <option value="{{ $location->id }}" data-warehouse-id="{{ $warehouse->id }}">{{ $location->code }} — {{ $location->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label>Stok sistem</label>
                    <output class="stock-take-preview" id="system-stock-preview">Pilih barang dan gudang</output>
                </div>
                <div class="form-field">
                    <label for="physical-qty">Stok fisik <span class="required">*</span></label>
                    <input id="physical-qty" type="number" min="0" step="0.0001" placeholder="Jumlah dalam satuan dasar" disabled>
                    <span class="muted" id="physical-qty-converted"></span>
                </div>
                <div class="form-field">
                    <label for="discrepancy-qty">Discrepancy / selisih</label>
                    <input id="discrepancy-qty" type="text" value="" placeholder="Terisi otomatis" disabled>
                    <span class="muted" id="discrepancy-converted"></span>
                </div>
            </div>
            <div class="stock-take-input-actions">
                <span class="field-error" id="stock-take-input-error" role="alert"></span>
                <button class="button secondary" type="button" id="add-stock-take-line" disabled>OK · Tambah ke daftar</button>
            </div>
        </div>

        <div class="form-grid stock-take-document">
            <div class="form-field">
                <label for="stock_take_date">Tanggal stock take <span class="required">*</span></label>
                <input id="stock_take_date" name="stock_take_date" type="date" value="{{ old('stock_take_date', now()->format('Y-m-d')) }}" required>
                @error('stock_take_date')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="stock_take_notes">Catatan</label>
                <input id="stock_take_notes" name="notes" value="{{ old('notes') }}" maxlength="5000">
                @error('notes')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>
        @error('lines')<span class="field-error">{{ $message }}</span>@enderror
        <div class="form-actions">
            <a class="button secondary" href="{{ route('stock.index') }}">Batal</a>
            <button class="button primary" type="submit" @disabled($items->isEmpty() || $warehouses->isEmpty())>Simpan Stock Take</button>
        </div>
    </form>

    <script>
        (() => {
            const form = document.getElementById('stock-take-form');
            const item = document.getElementById('count-item');
            const warehouse = document.getElementById('count-warehouse');
            const location = document.getElementById('count-location');
            const physical = document.getElementById('physical-qty');
            const discrepancy = document.getElementById('discrepancy-qty');
            const addButton = document.getElementById('add-stock-take-line');
            const rows = document.getElementById('stock-take-rows');
            const emptyRow = document.getElementById('stock-take-empty');
            const error = document.getElementById('stock-take-input-error');
            const count = document.getElementById('stock-take-count');
            const systemPreview = document.getElementById('system-stock-preview');
            const physicalConverted = document.getElementById('physical-qty-converted');
            const discrepancyConverted = document.getElementById('discrepancy-converted');
            let position = null;
            let nextIndex = [...form.querySelectorAll('input[name^="lines["]')].reduce((highest, input) => {
                const index = Number(input.name.match(/^lines\[(\d+)\]/)?.[1] ?? -1);
                return Math.max(highest, index);
            }, -1) + 1;
            const format = (quantity, data) => {
                let remaining = Math.max(0, Number(quantity));
                const parts = [];
                for (const conversion of data.conversions) {
                    const units = Math.floor((remaining + 0.0000001) / conversion.base_units);
                    if (units > 0) {
                        parts.push(`${units} ${conversion.code}`);
                        remaining -= units * conversion.base_units;
                    }
                }
                const base = new Intl.NumberFormat('id-ID', {maximumFractionDigits: 4}).format(remaining);
                parts.push(`${base} ${data.base_unit}`);
                return parts.join(' · ');
            };
            const updateDifference = () => {
                const valid = position !== null && physical.value !== '' && Number(physical.value) >= 0;
                if (!valid) {
                    discrepancy.value = '';
                    physicalConverted.textContent = '';
                    discrepancyConverted.textContent = '';
                    addButton.disabled = true;
                    return;
                }
                const difference = Number(physical.value) - Number(position.system_qty);
                discrepancy.value = `${difference > 0 ? '+' : ''}${new Intl.NumberFormat('id-ID', {maximumFractionDigits: 4}).format(difference)}`;
                physicalConverted.textContent = format(physical.value, position);
                discrepancyConverted.textContent = `Selisih: ${difference < 0 ? '−' : difference > 0 ? '+' : ''}${format(Math.abs(difference), position)}`;
                addButton.disabled = false;
            };
            const refreshPosition = async () => {
                position = null;
                physical.disabled = true;
                physical.value = '';
                systemPreview.textContent = 'Pilih barang dan gudang';
                updateDifference();
                if (!item.value || !warehouse.value) return;
                const params = new URLSearchParams({item_id: item.value, warehouse_id: warehouse.value, location_id: location.value});
                try {
                    const response = await fetch(`{{ route('stock-takes.position') }}?${params}`, {
                        headers: {'Accept': 'application/json'},
                        credentials: 'same-origin',
                    });
                    if (!response.ok) throw new Error('Saldo stok gagal dimuat. Periksa pilihan barang dan gudang.');
                    position = await response.json();
                    systemPreview.textContent = position.formatted_qty;
                    physical.disabled = false;
                    physical.focus();
                    updateDifference();
                } catch (exception) {
                    error.textContent = exception.message;
                }
            };
            const updateLocations = () => {
                [...location.options].forEach((option) => {
                    if (!option.value) return;
                    option.hidden = option.dataset.warehouseId !== warehouse.value;
                    option.disabled = option.hidden;
                });
                if (location.selectedOptions[0]?.disabled) location.value = '';
            };
            const addHidden = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                return input;
            };
            const positionKey = () => `${item.value}|${warehouse.value}|${location.value}`;
            const addLine = () => {
                error.textContent = '';
                if (!position || !physical.value) return;
                const key = positionKey();
                if ([...rows.querySelectorAll('tr[data-position]')].some((row) => row.dataset.position === key)) {
                    error.textContent = 'Barang, gudang, dan lokasi ini sudah ada di daftar hitungan.';
                    return;
                }
                const difference = Number(physical.value) - Number(position.system_qty);
                const tr = document.createElement('tr');
                tr.dataset.position = key;
                const cells = [
                    `${item.selectedOptions[0].text}`,
                    `${warehouse.selectedOptions[0].text} / ${location.value ? location.selectedOptions[0].text : 'Tanpa lokasi'}`,
                    format(position.system_qty, position),
                    format(physical.value, position),
                    `${difference < 0 ? '−' : difference > 0 ? '+' : ''}${format(Math.abs(difference), position)}`,
                ];
                for (const text of cells) {
                    const td = document.createElement('td');
                    td.textContent = text;
                    tr.appendChild(td);
                }
                const action = document.createElement('td');
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'remove-line';
                remove.dataset.removeRow = '';
                remove.setAttribute('aria-label', 'Hapus hitungan');
                remove.textContent = '×';
                action.appendChild(remove);
                tr.appendChild(action);
                rows.insertBefore(tr, emptyRow);
                const values = {
                    item_id: item.value,
                    warehouse_id: warehouse.value,
                    location_id: location.value,
                    system_qty: position.system_qty,
                    physical_qty: physical.value,
                };
                for (const [keyName, value] of Object.entries(values)) {
                    form.appendChild(addHidden(`lines[${nextIndex}][${keyName}]`, value));
                }
                nextIndex++;
                count.textContent = rows.querySelectorAll('tr[data-position]').length;
                emptyRow.hidden = true;
                item.value = '';
                warehouse.value = '';
                location.value = '';
                updateLocations();
                position = null;
                physical.disabled = true;
                physical.value = '';
                systemPreview.textContent = 'Pilih barang dan gudang';
                updateDifference();
            };
            rows.addEventListener('click', (event) => {
                const button = event.target.closest('[data-remove-row]');
                if (!button) return;
                const row = button.closest('tr');
                const key = row.dataset.position;
                row.remove();
                const fields = [...form.querySelectorAll('input[name^="lines["]')];
                const matching = fields.filter((field) => {
                    const prefix = field.name.match(/^lines\[(\d+)\]/)?.[1];
                    if (prefix === undefined) return false;
                    const lineItem = form.querySelector(`input[name="lines[${prefix}][item_id]"]`)?.value;
                    const lineWarehouse = form.querySelector(`input[name="lines[${prefix}][warehouse_id]"]`)?.value;
                    const lineLocation = form.querySelector(`input[name="lines[${prefix}][location_id]"]`)?.value ?? '';
                    return `${lineItem}|${lineWarehouse}|${lineLocation}` === key;
                });
                matching.forEach((field) => field.remove());
                count.textContent = rows.querySelectorAll('tr[data-position]').length;
                emptyRow.hidden = rows.querySelectorAll('tr[data-position]').length > 0;
            });
            item.addEventListener('change', refreshPosition);
            warehouse.addEventListener('change', () => { updateLocations(); refreshPosition(); });
            location.addEventListener('change', refreshPosition);
            physical.addEventListener('input', updateDifference);
            addButton.addEventListener('click', addLine);
            updateLocations();
        })();
    </script>
@endsection
