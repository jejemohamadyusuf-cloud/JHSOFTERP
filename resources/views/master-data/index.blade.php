@extends('layouts.app', ['title' => $definition['label']])

@section('content')
    <x-pic-guide :module="$masterKey" />
    <div class="table-card selectable-list" data-selectable-list>
        <div class="table-toolbar"><strong>Daftar {{ $definition['label'] }}</strong><span>{{ $records->total() }}
                data</span></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="list-select-cell">Pilih</th>
                        @foreach ($definition['columns'] as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td class="list-select-cell">
                                <input type="radio" name="selected_master_record" data-list-select
                                    aria-label="Pilih {{ $definition['label'] }}">
                                <template data-list-actions>
                                    <a class="button secondary"
                                        href="{{ route('master-data.edit', [$masterKey, $record->id]) }}">Ubah</a>
                                    <form method="POST"
                                        action="{{ route('master-data.destroy', [$masterKey, $record->id]) }}"
                                        onsubmit="return confirm('Hapus data ini?')">
                                        @csrf @method('DELETE')
                                        <button class="button danger" type="submit">Hapus</button>
                                    </form>
                                </template>
                            </td>
                            @foreach ($definition['columns'] as $field => $label)
                                @php($value = data_get($record, $field))
                                <td>
                                    @if ($field === 'is_active')
                                        <span
                                            class="status {{ $value ? 'status-active' : 'status-inactive' }}">{{ $value ? 'Aktif' : 'Nonaktif' }}</span>
                                    @elseif ($field === 'item_type')
                                        {{ ['raw' => 'Bahan baku', 'semi' => 'Setengah jadi', 'finished' => 'Barang jadi'][$value] ?? $value }}
                                    @else
                                        {{ filled($value) ? $value : '—' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="{{ count($definition['columns']) + 1 }}">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('components.list-action-panel', [
            'staticActions' => [
                [
                    'label' => '＋ Tambah ' . $definition['label'],
                    'url' => route('master-data.create', $masterKey),
                    'class' => 'primary',
                ],
            ],
        ])
        <div class="pagination">{{ $records->links() }}</div>
    </div>
@endsection
