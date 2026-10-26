@extends('layouts.app', ['title' => 'Pengguna & Peran'])

@section('content')
    <div class="section-heading"><div><h1>Pengguna & Peran</h1><p>Atur akses modul. Administrator memiliki akses penuh.</p></div></div>
    @error('role')<div class="flash error">{{ $message }}</div>@enderror
    <div class="note-panel"><span class="note-icon">i</span><span>Pengguna baru mendapat peran Penjualan. Hanya administrator yang dapat mengubah peran pengguna.</span></div>
    <div class="table-card">
        <div class="table-toolbar"><strong>Daftar Pengguna</strong><span>{{ $users->total() }} data</span></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Terdaftar</th><th>Akses</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td><strong>{{ $user->name }}</strong>@if ($user->is(auth()->user())) <span class="muted">(Anda)</span>@endif</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->roleLabel() }}</td>
                        <td>{{ $user->created_at?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <form class="role-form" method="POST" action="{{ route('users.role.update', $user) }}">
                                @csrf
                                @method('PUT')
                                <select name="role" aria-label="Peran {{ $user->name }}" @disabled($user->is(auth()->user()))>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->value }}" @selected($user->role === $role->value)>{{ $role->label() }}</option>
                                    @endforeach
                                </select>
                                @if (! $user->is(auth()->user()))
                                    <button class="button small secondary" type="submit">Simpan</button>
                                @else
                                    <span class="muted">Peran Anda tidak dapat diturunkan.</span>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">Belum ada pengguna.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="pagination">{{ $users->links() }}</div>
    </div>
    <div class="note-panel"><span class="note-icon">i</span><span>Penjualan: data master, Sales Order, dan Delivery. Persediaan & Pembelian: data master, BOM, PO, Receiving, dan stok. Produksi: BOM dan Work Order. Keuangan: Invoice dan pembayaran.</span></div>
@endsection
