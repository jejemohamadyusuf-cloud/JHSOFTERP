@extends('layouts.app', ['title' => ($record ? 'Ubah ' : 'Tambah ').$definition['label']])

@section('content')
    <div class="section-heading">
        <div><h1>{{ $record ? 'Ubah' : 'Tambah' }} {{ $definition['label'] }}</h1><p>Isi informasi dengan benar agar data konsisten digunakan di seluruh proses.</p></div>
        <a class="button secondary" href="{{ route('master-data.index', $masterKey) }}">← Kembali</a>
    </div>
    <x-pic-guide :module="$masterKey" />
    <form class="form-card" method="POST" action="{{ $record ? route('master-data.update', [$masterKey, $record->id]) : route('master-data.store', $masterKey) }}">
        @csrf
        @if ($record) @method('PUT') @endif
        <div class="form-grid">
            @foreach ($definition['fields'] as $field => $input)
                @php($value = old($field, $record?->{$field} ?? ($field === 'is_active' ? true : '')))
                <div class="form-field {{ $input['type'] === 'textarea' ? 'full-width' : '' }} {{ $input['type'] === 'checkbox' ? 'checkbox-field' : '' }}">
                    @if ($input['type'] === 'checkbox')
                        <input type="hidden" name="{{ $field }}" value="0">
                        <label class="check-label"><input type="checkbox" name="{{ $field }}" value="1" @checked((bool) $value)> <span>{{ $input['label'] }}</span></label>
                    @else
                        <label for="{{ $field }}">{{ $input['label'] }} @if ($input['required']) <span class="required">*</span> @endif</label>
                        @if ($input['type'] === 'relation')
                            <select id="{{ $field }}" name="{{ $field }}" @required($input['required'])>
                                <option value="">Pilih {{ strtolower($input['label']) }}</option>
                                @foreach ($options[$field] as $option)
                                    <option value="{{ $option->id }}" @selected((string) $value === (string) $option->id)>{{ $option->code ?? '' }} {{ $option->name }}</option>
                                @endforeach
                            </select>
                        @elseif ($input['type'] === 'select')
                            <select id="{{ $field }}" name="{{ $field }}" @required($input['required'])>
                                <option value="">Pilih {{ strtolower($input['label']) }}</option>
                                @foreach ($input['options'] as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}" @selected($value === $optionValue)>{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @elseif ($input['type'] === 'textarea')
                            <textarea id="{{ $field }}" name="{{ $field }}" rows="3">{{ $value }}</textarea>
                        @else
                            <input id="{{ $field }}" name="{{ $field }}" type="{{ $input['type'] }}" value="{{ $value }}" @if (isset($input['max'])) maxlength="{{ $input['max'] }}" @endif @if (isset($input['step'])) step="{{ $input['step'] }}" min="{{ $input['min'] }}" @endif @required($input['required'])>
                        @endif
                        @error($field)<span class="field-error">{{ $message }}</span>@enderror
                    @endif
                </div>
            @endforeach
        </div>
        @if ($masterKey === 'items')
            <div class="bom-lines-heading">
                <div><h2>Konversi kemasan stok</h2><p>Simpan jumlah satuan dasar di setiap kemasan. Contoh: 1 Dus = 100 pcs dan 1 Pack = 10 pcs.</p></div>
                <button class="button secondary" type="button" id="add-unit-conversion">＋ Tambah satuan</button>
            </div>
            <div id="item-unit-conversions">
                @php($conversions = old('conversions', $record?->unitConversions?->map(fn ($conversion) => ['unit_id' => $conversion->unit_id, 'base_units' => $conversion->base_units])->all() ?? []))
                @foreach ($conversions as $index => $conversion)
                    @include('master-data._unit-conversion', ['index' => $index, 'conversion' => $conversion])
                @endforeach
            </div>
            <template id="item-unit-conversion-template">@include('master-data._unit-conversion', ['index' => '__INDEX__', 'conversion' => ['unit_id' => '', 'base_units' => '']])</template>
            @error('conversions')<span class="field-error">{{ $message }}</span>@enderror
        @endif
        <div class="form-actions"><a class="button secondary" href="{{ route('master-data.index', $masterKey) }}">Batal</a><button class="button primary" type="submit">Simpan data</button></div>
    </form>
    @if ($masterKey === 'items')
        <script>
            (() => {
                const container = document.getElementById('item-unit-conversions');
                const template = document.getElementById('item-unit-conversion-template').innerHTML;
                let nextIndex = [...container.querySelectorAll('select[name^="conversions["]')].reduce((highest, select) => {
                    const index = Number(select.name.match(/^conversions\[(\d+)\]/)?.[1] ?? -1);
                    return Math.max(highest, index);
                }, -1) + 1;
                document.getElementById('add-unit-conversion').addEventListener('click', () => {
                    container.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', nextIndex++));
                });
                container.addEventListener('click', (event) => {
                    if (event.target.closest('.remove-line')) event.target.closest('.item-unit-conversion').remove();
                });
            })();
        </script>
    @endif
@endsection
