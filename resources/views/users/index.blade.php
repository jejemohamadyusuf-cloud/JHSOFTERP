@extends('layouts.app', ['title' => 'Pengaturan Hak Akses Pengguna'])

@section('content')
    @error('role')
        <div class="flash error">
            {{ $message }}</div>
    @enderror
    @if ($errors->has('permissions') || $errors->has('permissions.*'))
        <div class="flash error">{{ $errors->first('permissions') ?: $errors->first('permissions.*') }}</div>
    @endif
    <div class="note-panel"><span class="note-icon">i</span><span>Administrator dapat mengatur menu untuk setiap pengguna. Pilih “Gunakan akses bawaan peran” untuk mengikuti hak akses default, atau pilih menu khusus sesuai SOP perusahaan.</span></div>
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
                        <th>Hak Akses Menu</th>
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
                                        @if ($user->role !== \App\Enums\UserRole::Admin->value)
                                            <form class="access-form" method="POST" action="{{ route('users.access.update', $user) }}">
                                                @csrf
                                                @method('PUT')
                                                <strong>Menu yang dapat diakses</strong>
                                                <label class="access-default">
                                                    <input type="checkbox" name="use_role_defaults" value="1" @checked($user->permissions === null)>
                                                    Gunakan akses bawaan peran
                                                </label>
                                                <div class="access-options">
                                                    @foreach ($accessPermissions as $permission => $definition)
                                                        <label>
                                                            <input type="checkbox" name="permissions[]" value="{{ $permission }}"
                                                                @checked($user->canAccess($permission))>
                                                            {{ $definition['label'] }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                                <button class="button primary" type="submit">Simpan hak akses</button>
                                            </form>
                                        @else
                                            <span>Administrator memiliki akses penuh.</span>
                                        @endif
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
                            <td>
                                @if ($user->role === \App\Enums\UserRole::Admin->value)
                                    Semua menu (admin)
                                @elseif ($user->permissions === null)
                                    Bawaan peran
                                @else
                                    {{ count($user->permissions) }} menu khusus
                                @endif
                            </td>
                            <td>{{ $user->created_at?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">Belum ada pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('components.list-action-panel')
        <div class="pagination">{{ $users->links() }}</div>
    </div>
    <div class="note-panel"><span class="note-icon">i</span><span>Menu yang tersedia: Data Master, Master Harga, Bill of Materials, Purchase Order & Receiving, Stok & Stock Take, Sales Order, Delivery, Keuangan, dan Work Order. Hak akses menu berlaku untuk tampilan dan URL langsung.</span></div>
@endsection
