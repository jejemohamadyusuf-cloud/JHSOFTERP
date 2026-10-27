@extends('layouts.app', ['title' => 'Pengaturan Panduan PIC'])

@section('content')
    <div class="note-panel"><span class="note-icon">i</span><span>Pengaturan ini hanya mengubah panduan informatif pada UI.
            Ini belum menentukan user yang berwenang, hak akses, atau alur persetujuan transaksi.</span></div>
    <form class="form-card" method="POST" action="{{ route('pic-guides.update') }}">
        @csrf
        @method('PUT')
        <div class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Menu</th>
                            <th>Divisi</th>
                            <th>Jabatan penginput</th>
                            <th>Pemeriksa / penanggung jawab</th>
                            <th>Aktif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($modules as $module => $label)
                            @php($guide = $guides->get($module))
                            <tr>
                                <td><strong>{{ $label }}</strong></td>
                                <td>
                                    <input name="guides[{{ $module }}][department]"
                                        value="{{ old("guides.{$module}.department", $guide?->department) }}" maxlength="120"
                                        required>
                                    @error("guides.{$module}.department")
                                        <span class="field-error">{{ $message }}</span>
                                    @enderror
                                </td>
                                <td>
                                    <input name="guides[{{ $module }}][input_role]"
                                        value="{{ old("guides.{$module}.input_role", $guide?->input_role) }}"
                                        maxlength="120" required>
                                    @error("guides.{$module}.input_role")
                                        <span class="field-error">{{ $message }}</span>
                                    @enderror
                                </td>
                                <td>
                                    <input name="guides[{{ $module }}][review_role]"
                                        value="{{ old("guides.{$module}.review_role", $guide?->review_role) }}"
                                        maxlength="120" required>
                                    @error("guides.{$module}.review_role")
                                        <span class="field-error">{{ $message }}</span>
                                    @enderror
                                </td>
                                <td>
                                    <input type="hidden" name="guides[{{ $module }}][is_enabled]" value="0">
                                    <label class="check-label"><input type="checkbox"
                                            name="guides[{{ $module }}][is_enabled]" value="1"
                                            @checked((bool) old("guides.{$module}.is_enabled", $guide?->is_enabled))> <span>Tampilkan</span></label>
                                    @error("guides.{$module}.is_enabled")
                                        <span class="field-error">{{ $message }}</span>
                                    @enderror
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @error('guides')
            <span class="field-error">{{ $message }}</span>
        @enderror
        <div class="form-actions"><button class="button primary" type="submit">Simpan pengaturan PIC</button></div>
    </form>
@endsection
