@extends('layouts.app', ['title' => ($bom ? 'Ubah' : 'Tambah').' BOM'])

@section('content')
    <div class="section-heading">
        <div><h1>{{ $bom ? 'Ubah BOM' : 'Tambah BOM' }}</h1><p>Tentukan produk, versi, dan kuantitas setiap bahan penyusun.</p></div>
        <a class="button secondary" href="{{ route('boms.index') }}">← Kembali</a>
    </div>
    <x-pic-guide module="boms" />
    <form class="form-card" method="POST" action="{{ $bom ? route('boms.update', $bom) : route('boms.store') }}">
        @csrf
        @if ($bom) @method('PUT') @endif
        <div class="form-grid">
            <div class="form-field">
                <label for="item_id">Barang yang diproduksi <span class="required">*</span></label>
                <select id="item_id" name="item_id" required>
                    <option value="">Pilih barang jadi</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}" @selected((string) old('item_id', $bom?->item_id) === (string) $item->id)>{{ $item->code }} — {{ $item->name }}</option>
                    @endforeach
                </select>
                @error('item_id')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="version">Versi <span class="required">*</span></label>
                <input id="version" name="version" type="text" maxlength="20" value="{{ old('version', $bom?->version ?? '1') }}" required>
                @error('version')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field checkbox-field">
                <input type="hidden" name="is_active" value="0">
                <label class="check-label"><input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $bom?->is_active ?? true))> <span>Aktif</span></label>
            </div>
        </div>
        <div class="bom-lines-heading"><div><h2>Komponen bahan</h2><p>Tambahkan bahan baku atau barang setengah jadi yang digunakan.</p></div><button class="button secondary" type="button" id="add-line">＋ Tambah komponen</button></div>
        @error('lines')<div class="field-error line-error">{{ $message }}</div>@enderror
        <div class="bom-lines" id="bom-lines">
            @php($lines = old('lines', $bom?->lines->map(fn ($line) => ['component_item_id' => $line->component_item_id, 'qty' => number_format((float) $line->qty, 2, '.', ''), 'unit_id' => $line->unit_id, 'scrap_percent' => $line->scrap_percent])->all() ?? [['component_item_id' => '', 'qty' => '', 'unit_id' => '', 'scrap_percent' => '0']]))
            @foreach ($lines as $index => $line)
                @include('boms._line', ['index' => $index, 'line' => $line, 'items' => $items, 'units' => $units])
            @endforeach
        </div>
        <template id="line-template">
            @include('boms._line', ['index' => '__INDEX__', 'line' => ['component_item_id' => '', 'qty' => '', 'unit_id' => '', 'scrap_percent' => '0'], 'items' => $items, 'units' => $units])
        </template>
        <div class="form-actions"><a class="button secondary" href="{{ route('boms.index') }}">Batal</a><button class="button primary" type="submit">Simpan BOM</button></div>
    </form>
    <script>
        (() => {
            const container = document.getElementById('bom-lines');
            const template = document.getElementById('line-template').innerHTML;
            document.getElementById('add-line').addEventListener('click', () => {
                const index = container.querySelectorAll('.bom-line').length;
                container.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', index));
            });
            container.addEventListener('click', (event) => {
                if (event.target.closest('.remove-line')) {
                    event.target.closest('.bom-line').remove();
                }
            });
        })();
    </script>
@endsection
