@extends('layouts.app', ['title' => 'Buat General Journal'])

@section('content')
    <div class="section-heading">
        <div><h1>Buat General Journal</h1><p>Jurnal akan langsung diposting setelah debit dan kredit seimbang.</p></div>
        <a class="button secondary" href="{{ route('general-journal.index') }}">← Kembali</a>
    </div>
    <form class="form-card" method="POST" action="{{ route('general-journal.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-field">
                <label for="journal_date">Tanggal jurnal <span class="required">*</span></label>
                <input id="journal_date" name="journal_date" type="date" value="{{ old('journal_date', now()->toDateString()) }}" required>
                @error('journal_date')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field">
                <label for="reference">Referensi</label>
                <input id="reference" name="reference" type="text" maxlength="100" value="{{ old('reference') }}">
                @error('reference')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field full-width">
                <label for="description">Keterangan jurnal <span class="required">*</span></label>
                <textarea id="description" name="description" rows="2" maxlength="255" required>{{ old('description') }}</textarea>
                @error('description')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="section-heading">
            <div><h2>Baris jurnal</h2><p>Gunakan akun transaksi aktif dari COA. Setiap baris hanya boleh memiliki debit atau kredit.</p></div>
            <button class="button secondary" type="button" id="add-journal-line">＋ Tambah baris</button>
        </div>
        @error('lines')<span class="field-error">{{ $message }}</span>@enderror
        <div class="table-wrap">
            <table>
                <thead><tr><th>Akun COA</th><th>Keterangan baris</th><th>Debit (Rp)</th><th>Kredit (Rp)</th><th></th></tr></thead>
                <tbody id="journal-lines">
                    @foreach (old('lines', [['chart_of_account_id' => '', 'description' => '', 'debit_amount' => '0.00', 'credit_amount' => '0.00'], ['chart_of_account_id' => '', 'description' => '', 'debit_amount' => '0.00', 'credit_amount' => '0.00']]) as $index => $line)
                        <tr class="journal-line">
                            <td>
                                <select name="lines[{{ $index }}][chart_of_account_id]" required>
                                    <option value="">Pilih akun</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}" @selected((string) ($line['chart_of_account_id'] ?? '') === (string) $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                                    @endforeach
                                </select>
                                @error("lines.$index.chart_of_account_id")<span class="field-error">{{ $message }}</span>@enderror
                            </td>
                            <td><input name="lines[{{ $index }}][description]" type="text" maxlength="255" value="{{ $line['description'] ?? '' }}"></td>
                            <td>
                                <input name="lines[{{ $index }}][debit_amount]" type="number" min="0" step="0.01" value="{{ $line['debit_amount'] ?? '0.00' }}" required>
                                @error("lines.$index.debit_amount")<span class="field-error">{{ $message }}</span>@enderror
                            </td>
                            <td>
                                <input name="lines[{{ $index }}][credit_amount]" type="number" min="0" step="0.01" value="{{ $line['credit_amount'] ?? '0.00' }}" required>
                                @error("lines.$index.credit_amount")<span class="field-error">{{ $message }}</span>@enderror
                            </td>
                            <td><button class="button danger remove-journal-line" type="button" aria-label="Hapus baris">Hapus</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="form-actions">
            <a class="button secondary" href="{{ route('general-journal.index') }}">Batal</a>
            <button class="button primary" type="submit">Posting jurnal</button>
        </div>
    </form>
    <template id="journal-line-template">
        <tr class="journal-line">
            <td>
                <select name="lines[__INDEX__][chart_of_account_id]" required>
                    <option value="">Pilih akun</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input name="lines[__INDEX__][description]" type="text" maxlength="255"></td>
            <td><input name="lines[__INDEX__][debit_amount]" type="number" min="0" step="0.01" value="0.00" required></td>
            <td><input name="lines[__INDEX__][credit_amount]" type="number" min="0" step="0.01" value="0.00" required></td>
            <td><button class="button danger remove-journal-line" type="button" aria-label="Hapus baris">Hapus</button></td>
        </tr>
    </template>
    <script>
        (() => {
            const container = document.getElementById('journal-lines');
            const template = document.getElementById('journal-line-template').innerHTML;
            let nextIndex = [...container.querySelectorAll('select[name^="lines["]')].reduce((highest, select) => {
                const index = Number(select.name.match(/^lines\[(\d+)\]/)?.[1] ?? -1);
                return Math.max(highest, index);
            }, -1) + 1;
            document.getElementById('add-journal-line').addEventListener('click', () => {
                container.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', nextIndex++));
            });
            container.addEventListener('click', (event) => {
                if (event.target.closest('.remove-journal-line') && container.querySelectorAll('.journal-line').length > 2) {
                    event.target.closest('.journal-line').remove();
                }
            });
        })();
    </script>
@endsection
