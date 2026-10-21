@php($isIn = $direction === 'in')
@php($title = $isIn ? 'Adjustment In Stock' : 'Adjustment Out Stock')

@extends('layouts.app', ['title' => $title])

@section('content')
    <div class="section-heading">
        <div>
            <h1>{{ $title }}</h1>
            <p>{{ $isIn ? 'Catat penambahan stok yang bukan berasal dari penerimaan Purchase Order.' : 'Catat pengurangan stok yang bukan berasal dari transaksi pengeluaran lain.' }}</p>
        </div>
        <a class="button secondary" href="{{ route('stock.index') }}">← Daftar Stok</a>
    </div>
    <x-pic-guide module="stock" />
    @if ($items->isEmpty() || $warehouses->isEmpty())
        <div class="note-panel"><span class="note-icon">i</span><span>Barang dan gudang aktif harus tersedia sebelum adjustment dapat dicatat.</span></div>
    @endif
    @error('lines')<div class="flash error">{{ $message }}</div>@enderror
    <form class="form-card" method="POST" action="{{ route('stock.adjustments.store', ['direction' => $direction]) }}">
        @csrf
        <div class="form-grid">
            <div class="form-field">
                <label for="adjustment_date">Tanggal adjustment <span class="required">*</span></label>
                <input id="adjustment_date" name="adjustment_date" type="date" value="{{ old('adjustment_date', now()->format('Y-m-d')) }}" required>
                @error('adjustment_date')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="notes">Catatan</label>
                <input id="notes" name="notes" value="{{ old('notes') }}" maxlength="5000">
                @error('notes')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="bom-lines-heading">
            <div><h2>Barang untuk adjustment {{ $isIn ? 'masuk' : 'keluar' }}</h2><p>Setiap kombinasi barang, gudang, dan lokasi hanya boleh dicatat sekali per dokumen.</p></div>
            <button class="button secondary" type="button" id="add-adjustment-line">＋ Tambah baris</button>
        </div>
        <div id="adjustment-lines">
            @php($lines = old('lines', [['item_id' => '', 'warehouse_id' => '', 'location_id' => '', 'qty' => '', 'unit_cost' => '']]))
            @foreach ($lines as $index => $line)
                @include('stock._adjustment-line', ['index' => $index, 'line' => $line, 'isIn' => $isIn])
            @endforeach
        </div>
        <template id="adjustment-line-template">@include('stock._adjustment-line', ['index' => '__INDEX__', 'line' => ['item_id' => '', 'warehouse_id' => '', 'location_id' => '', 'qty' => '', 'unit_cost' => ''], 'isIn' => $isIn])</template>
        <div class="form-actions">
            <a class="button secondary" href="{{ route('stock.index') }}">Batal</a>
            <button class="button primary" type="submit" @disabled($items->isEmpty() || $warehouses->isEmpty())>Simpan {{ $title }}</button>
        </div>
    </form>
    <script>
        (() => {
            const container = document.getElementById('adjustment-lines');
            const template = document.getElementById('adjustment-line-template').innerHTML;
            const updateLocations = (row) => {
                const warehouseId = row.querySelector('[data-warehouse-select]').value;
                const select = row.querySelector('[data-location-select]');
                [...select.options].forEach((option) => {
                    if (!option.value) return;
                    option.hidden = option.dataset.warehouseId !== warehouseId;
                    option.disabled = option.hidden;
                });
                if (select.selectedOptions[0]?.disabled) select.value = '';
            };
            container.addEventListener('change', (event) => {
                if (event.target.matches('[data-warehouse-select]')) updateLocations(event.target.closest('.adjustment-line'));
            });
            document.getElementById('add-adjustment-line').addEventListener('click', () => {
                const index = container.querySelectorAll('.adjustment-line').length;
                container.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', index));
            });
            container.addEventListener('click', (event) => {
                if (event.target.closest('.remove-line')) event.target.closest('.adjustment-line').remove();
            });
            container.querySelectorAll('.adjustment-line').forEach(updateLocations);
        })();
    </script>
@endsection
