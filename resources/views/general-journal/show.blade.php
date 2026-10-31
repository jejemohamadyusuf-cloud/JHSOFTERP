@extends('layouts.app', ['title' => $journal->journal_number])

@section('content')
    <div class="section-heading">
        <div><h1>{{ $journal->journal_number }}</h1><p>General Journal · {{ $journal->journal_date->format('d/m/Y') }}</p></div>
        <div class="actions"><a class="button secondary" href="{{ route('general-journal.index') }}">← Daftar jurnal</a><a class="button secondary" href="{{ route('general-ledger.index') }}">General Ledger</a></div>
    </div>
    <div class="order-summary">
        <div class="summary-card"><span>Referensi</span><strong>{{ $journal->reference ?: '—' }}</strong></div>
        <div class="summary-card"><span>Keterangan</span><strong>{{ $journal->description }}</strong></div>
        <div class="summary-card"><span>Diposting oleh</span><strong>{{ $journal->creator?->name ?? '—' }}</strong></div>
        <div class="summary-card"><span>Waktu posting</span><strong>{{ $journal->posted_at->format('d/m/Y H:i') }}</strong></div>
    </div>
    <div class="table-card">
        <div class="table-toolbar"><strong>Rincian jurnal</strong><span>{{ $journal->lines->count() }} baris</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Akun</th><th>Keterangan</th><th>Debit</th><th>Kredit</th></tr></thead>
                <tbody>
                    @php($debitTotal = 0)
                    @php($creditTotal = 0)
                    @foreach ($journal->lines as $line)
                        @php($debitTotal += (float) $line->debit_amount)
                        @php($creditTotal += (float) $line->credit_amount)
                        <tr>
                            <td>{{ $line->account->code }} — {{ $line->account->name }}</td>
                            <td>{{ $line->description ?: '—' }}</td>
                            <td>{{ $line->debit_amount > 0 ? 'Rp '.number_format((float) $line->debit_amount, 2, ',', '.') : '—' }}</td>
                            <td>{{ $line->credit_amount > 0 ? 'Rp '.number_format((float) $line->credit_amount, 2, ',', '.') : '—' }}</td>
                        </tr>
                    @endforeach
                    <tr><td colspan="2"><strong>Total</strong></td><td><strong>Rp {{ number_format($debitTotal, 2, ',', '.') }}</strong></td><td><strong>Rp {{ number_format($creditTotal, 2, ',', '.') }}</strong></td></tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
