@extends('layouts.app', ['title' => 'Bill of Materials'])

@section('content')
    <div class="section-heading">
        <div><h1>Bill of Materials</h1><p>Daftar struktur komponen untuk barang yang diproduksi.</p></div>
        <a class="button primary" href="{{ route('boms.create') }}">＋ Tambah BOM</a>
    </div>
    <div class="table-card">
        <div class="table-toolbar"><strong>Daftar BOM</strong><span>{{ $boms->total() }} data</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Barang jadi</th><th>Versi</th><th>Jumlah komponen</th><th>Status</th><th class="actions-col">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($boms as $bom)
                        <tr>
                            <td><strong>{{ $bom->item->code }}</strong><br><span class="muted">{{ $bom->item->name }}</span></td>
                            <td>{{ $bom->version }}</td>
                            <td>{{ $bom->lines->count() }} komponen</td>
                            <td><span class="status {{ $bom->is_active ? 'status-active' : 'status-inactive' }}">{{ $bom->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="actions">
                                <a class="button small secondary" href="{{ route('boms.edit', $bom) }}">Ubah</a>
                                <form method="POST" action="{{ route('boms.destroy', $bom) }}" onsubmit="return confirm('Hapus BOM ini?')">
                                    @csrf @method('DELETE')
                                    <button class="button small danger" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="5">Belum ada BOM. Buat BOM untuk mulai mendefinisikan kebutuhan bahan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $boms->links() }}</div>
    </div>
@endsection
