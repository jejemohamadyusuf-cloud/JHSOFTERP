@extends('layouts.app', ['title' => 'General Journal'])

@section('content')
    <div class="section-heading">
        <div><h1>General Journal</h1><p>Daftar jurnal umum yang sudah diposting.</p></div>
        <a class="button primary" href="{{ route('general-journal.create') }}">＋ Buat jurnal</a>
    </div>
    <div class="table-card">
        <div class="table-toolbar"><strong>Jurnal umum</strong><span>{{ $journals->total() }} data</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nomor jurnal</th><th>Tanggal</th><th>Referensi</th><th>Keterangan</th><th>Dibuat oleh</th><th>Debit</th><th>Kredit</th><th>Status / aksi</th></tr></thead>
                <tbody>
                    @forelse ($journals as $journal)
                        <tr>
                            <td><strong>{{ $journal->journal_number }}</strong></td>
                            <td>{{ $journal->journal_date->format('d/m/Y') }}</td>
                            <td>{{ $journal->reference ?: '—' }}</td>
                            <td>{{ $journal->description }}</td>
                            <td>{{ $journal->creator?->name ?? '—' }}</td>
                            <td>Rp {{ number_format((float) $journal->debit_total, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $journal->credit_total, 2, ',', '.') }}</td>
                            <td class="actions">
                                <a class="button secondary" href="{{ route('general-journal.show', $journal) }}">Detail</a>
                                @if ($journal->cancellation)
                                    <span class="status status-cancelled">Dibatalkan</span>
                                @elseif ($journal->source_type === null)
                                    @include('components.cancel-transaction-form', ['type' => 'journal', 'id' => $journal->id])
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state">Belum ada jurnal umum.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $journals->links() }}</div>
    </div>
@endsection
