@extends('layouts.app', ['title' => 'General Ledger'])

@section('content')
    <div class="section-heading">
        <div><h1>General Ledger</h1><p>Telusuri mutasi debit, kredit, dan saldo berjalan setiap akun.</p></div>
        <a class="button secondary" href="{{ route('general-journal.index') }}">General Journal</a>
    </div>
    <form class="form-card" method="GET" action="{{ route('general-ledger.index') }}">
        <div class="form-grid">
            <div class="form-field">
                <label for="account_id">Akun COA</label>
                <select id="account_id" name="account_id">
                    <option value="">Pilih akun transaksi</option>
                    @foreach ($accounts as $option)
                        <option value="{{ $option->id }}" @selected((string) ($filters['account_id'] ?? '') === (string) $option->id)>{{ $option->code }} — {{ $option->name }}</option>
                    @endforeach
                </select>
                @error('account_id')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-field"><label for="from">Dari tanggal</label><input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}">@error('from')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="form-field"><label for="to">Sampai tanggal</label><input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}">@error('to')<span class="field-error">{{ $message }}</span>@enderror</div>
        </div>
        <div class="form-actions"><a class="button secondary" href="{{ route('general-ledger.index') }}">Reset</a><button class="button primary" type="submit">Tampilkan buku besar</button></div>
    </form>
    @if ($account)
        <div class="section-heading"><div><h2>{{ $account->code }} — {{ $account->name }}</h2><p>Saldo normal {{ $account->normal_balance === 'debit' ? 'Debit' : 'Kredit' }}</p></div></div>
        <div class="order-summary">
            <div class="summary-card"><span>Saldo awal</span><strong>Rp {{ number_format((float) $account->opening_balance_amount, 2, ',', '.') }} {{ $account->opening_balance_side }}</strong></div>
            <div class="summary-card"><span>Total debit periode</span><strong>Rp {{ number_format((float) $debitTotal, 2, ',', '.') }}</strong></div>
            <div class="summary-card"><span>Total kredit periode</span><strong>Rp {{ number_format((float) $creditTotal, 2, ',', '.') }}</strong></div>
        </div>
        <div class="table-card">
            <div class="table-toolbar"><strong>Mutasi akun</strong><span>{{ $lines->total() }} baris</span></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Tanggal</th><th>Nomor jurnal</th><th>Referensi</th><th>Keterangan</th><th>Debit</th><th>Kredit</th><th>Saldo berjalan</th></tr></thead>
                    <tbody>
                        @forelse ($lines as $line)
                            <tr>
                                <td>{{ $line->journalEntry->journal_date->format('d/m/Y') }}</td>
                                <td><a href="{{ route('general-journal.show', $line->journalEntry) }}">{{ $line->journalEntry->journal_number }}</a></td>
                                <td>{{ $line->journalEntry->reference ?: '—' }}</td>
                                <td>{{ $line->description ?: $line->journalEntry->description }}</td>
                                <td>{{ $line->debit_amount > 0 ? 'Rp '.number_format((float) $line->debit_amount, 2, ',', '.') : '—' }}</td>
                                <td>{{ $line->credit_amount > 0 ? 'Rp '.number_format((float) $line->credit_amount, 2, ',', '.') : '—' }}</td>
                                <td>Rp {{ number_format((float) $line->running_balance_amount, 2, ',', '.') }} {{ $line->running_balance_side }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty-state">Belum ada mutasi untuk akun dan periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $lines->links() }}</div>
        </div>
    @else
        <div class="note-panel"><span class="note-icon">i</span><span>Pilih akun COA untuk menampilkan buku besarnya.</span></div>
    @endif
@endsection
