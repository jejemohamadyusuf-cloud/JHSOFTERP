@extends('layouts.app', ['title' => $definition['label']])

@section('content')
    <div class="section-heading">
        <div><h1>{{ $definition['label'] }}</h1><p>Kelola data referensi {{ strtolower($definition['label']) }}.</p></div>
        <a class="button primary" href="{{ route('master-data.create', $masterKey) }}">＋ Tambah {{ $definition['label'] }}</a>
    </div>
    <x-pic-guide :module="$masterKey" />
    <div class="table-card">
        <div class="table-toolbar"><strong>Daftar {{ $definition['label'] }}</strong><span>{{ $records->total() }} data</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr>@foreach ($definition['columns'] as $label)<th>{{ $label }}</th>@endforeach<th class="actions-col">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            @foreach ($definition['columns'] as $field => $label)
                                @php($value = data_get($record, $field))
                                <td>
                                    @if ($field === 'is_active')
                                        <span class="status {{ $value ? 'status-active' : 'status-inactive' }}">{{ $value ? 'Aktif' : 'Nonaktif' }}</span>
                                    @elseif ($field === 'item_type')
                                        {{ ['raw' => 'Bahan baku', 'semi' => 'Setengah jadi', 'finished' => 'Barang jadi'][$value] ?? $value }}
                                    @else
                                        {{ filled($value) ? $value : '—' }}
                                    @endif
                                </td>
                            @endforeach
                            <td class="actions">
                                <a class="button small secondary" href="{{ route('master-data.edit', [$masterKey, $record->id]) }}">Ubah</a>
                                <form method="POST" action="{{ route('master-data.destroy', [$masterKey, $record->id]) }}" onsubmit="return confirm('Hapus data ini?')">
                                    @csrf @method('DELETE')
                                    <button class="button small danger" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="{{ count($definition['columns']) + 1 }}">Belum ada data. Klik “Tambah” untuk mulai mengisi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $records->links() }}</div>
    </div>
@endsection
