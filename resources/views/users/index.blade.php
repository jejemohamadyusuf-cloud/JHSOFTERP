@extends('layouts.app', ['title' => 'Pengguna & Peran'])

@section('content')
    @error('role')
        <div class="flash error">
            {{ $message }}</div>
    @enderror
    <div class="note-panel"><span class="note-icon">i</span><span>Pengguna baru mendapat peran Penjualan. Hanya administrator
            yang dapat mengubah peran pengguna.</span></div>
    <div class="table-card selectable-list" data-selectable-list>
        <div class="table-toolbar"><strong>Daftar Pengguna</strong><span>{{ $users->total() }} data</span></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="list-select-cell">Pilih</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Terdaftar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="list-select-cell">
                                <input type="radio" name="selected_user" data-list-select
                                    aria-label="Pilih pengguna {{ $user->name }}" @disabled($user->is(auth()->user()))>
                                <template data-list-actions>
                                    @if (!$user->is(auth()->user()))
                                        <form class="role-form" method="POST"
                                            action="{{ route('users.role.update', $user) }}">
                                            @csrf
                                            @method('PUT')
                                            <label for="role-{{ $user->id }}">Ubah peran {{ $user->name }}</label>
                                            <select id="role-{{ $user->id }}" name="role"
                                                aria-label="Peran {{ $user->name }}">
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->value }}" @selected($user->role === $role->value)>
                                                        {{ $role->label() }}</option>
                                                @endforeach
                                            </select>
                                            <button class="button primary" type="submit">Simpan peran</button>
                                        </form>
                                    @endif
                                </template>
                            </td>
                            <td><strong>{{ $user->name }}</strong>
                                @if ($user->is(auth()->user()))
                                    <span class="muted">(Anda)</span>
                                @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->roleLabel() }}</td>
                            <td>{{ $user->created_at?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">Belum ada pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('components.list-action-panel')
        <div class="pagination">{{ $users->links() }}</div>
    </div>
    <div class="note-panel"><span class="note-icon">i</span><span>Penjualan: data master, Sales Order, dan Delivery.
            Persediaan & Pembelian: data master, BOM, PO, Receiving, dan stok. Produksi: BOM dan Work Order. Keuangan:
            Invoice dan pembayaran.</span></div>
@endsection
