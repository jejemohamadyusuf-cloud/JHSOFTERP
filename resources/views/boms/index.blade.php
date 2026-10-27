@extends('layouts.app', ['title' => 'Bill of Materials'])

@section('content')
    <x-pic-guide module="boms" />
    <div class="table-card selectable-list" data-selectable-list>
        <div class="table-toolbar"><strong>Daftar BOM</strong><span>{{ $boms->total() }} data</span></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="list-select-cell">Pilih</th>
                        <th>Barang jadi</th>
                        <th>Versi</th>
                        <th>Jumlah komponen</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($boms as $bom)
                        <tr>
                            <td class="list-select-cell">
                                <input type="radio" name="selected_bom" data-list-select
                                    aria-label="Pilih BOM {{ $bom->item->code }}">
                                <template data-list-actions>
                                    <a class="button secondary" href="{{ route('boms.edit', $bom) }}">Ubah</a>
                                    <form method="POST" action="{{ route('boms.destroy', $bom) }}"
                                        onsubmit="return confirm('Hapus BOM ini?')">
                                        @csrf @method('DELETE')
                                        <button class="button danger" type="submit">Hapus</button>
                                    </form>
                                </template>
                            </td>
                            <td><strong>{{ $bom->item->code }}</strong><br><span
                                    class="muted">{{ $bom->item->name }}</span></td>
                            <td>{{ $bom->version }}</td>
                            <td>{{ $bom->lines->count() }} komponen</td>
                            <td><span
                                    class="status {{ $bom->is_active ? 'status-active' : 'status-inactive' }}">{{ $bom->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="5">Belum ada BOM.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('components.list-action-panel', [
            'staticActions' => [['label' => '＋ Tambah BOM', 'url' => route('boms.create'), 'class' => 'primary']],
        ])
        <div class="pagination">{{ $boms->links() }}</div>
    </div>
@endsection
