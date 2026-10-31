@extends('layouts.app', ['title' => $invoice->invoice_number])

@section('content')
    <div class="section-heading">
        <div><h1>{{ $invoice->invoice_number }}</h1><p>Invoice · {{ $invoice->invoice_date->format('d/m/Y') }}</p></div>
        <div class="actions"><a class="button secondary" href="{{ route('invoices.index') }}">← Daftar invoice</a>@if ($invoice->delivery)<a class="button secondary" href="{{ route('deliveries.show', $invoice->delivery) }}">Delivery</a>@endif</div>
    </div>
    <div class="order-summary">
        <div class="summary-card"><span>Pelanggan</span><strong>{{ $invoice->customer->name }}</strong></div>
        <div class="summary-card"><span>Jatuh tempo</span><strong>{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</strong></div>
        <div class="summary-card"><span>Status</span><strong>{{ ['open' => 'Terbuka', 'partial' => 'Dibayar sebagian', 'paid' => 'Lunas'][$invoice->status] ?? $invoice->status }}</strong></div>
        <div class="summary-card"><span>Sisa tagihan</span><strong>Rp {{ number_format(max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount), 2, ',', '.') }}</strong></div>
    </div>
    <div class="table-card">
        <div class="table-toolbar"><strong>Rincian tagihan</strong><span>{{ $invoice->lines->count() }} baris</span></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Barang</th><th>Kuantitas</th><th>Harga satuan</th><th>Jumlah</th></tr></thead>
            <tbody>
                @foreach ($invoice->lines as $line)
                    <tr><td>{{ $line->item?->code ?? '' }} {{ $line->description }}</td><td>{{ number_format((float) $line->qty, 2, ',', '.') }}</td><td>Rp {{ number_format((float) $line->unit_price, 2, ',', '.') }}</td><td>Rp {{ number_format((float) $line->amount, 2, ',', '.') }}</td></tr>
                @endforeach
                <tr><td colspan="3"><strong>Total</strong></td><td><strong>Rp {{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</strong></td></tr>
            </tbody>
        </table></div>
    </div>
    <div class="section-heading"><div><h2>Pembayaran</h2><p>Catat pembayaran parsial atau pelunasan.</p></div></div>
    <div class="table-card">
        <div class="table-wrap"><table>
            <thead><tr><th>Tanggal</th><th>Metode</th><th>Referensi</th><th>Jumlah</th><th>Catatan</th><th></th></tr></thead>
            <tbody>
                @forelse ($invoice->payments as $payment)
                    <tr><td>{{ $payment->payment_date->format('d/m/Y') }}</td><td>{{ ucfirst($payment->payment_method) }}</td><td>{{ $payment->reference ?? '—' }}</td><td>Rp {{ number_format((float) $payment->amount, 2, ',', '.') }}</td><td>{{ $payment->notes ?? '—' }}</td><td>@include('components.cancel-transaction-form', ['type' => 'payment', 'id' => $payment->id])</td></tr>
                @empty
                    <tr><td colspan="6" class="empty-state">Belum ada pembayaran.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
    @if ($invoice->status !== 'paid')
        <form class="form-card" method="POST" action="{{ route('invoices.payments.store', $invoice) }}">
            @csrf
            <div class="form-grid">
                <div class="form-field"><label for="payment_date">Tanggal pembayaran</label><input id="payment_date" name="payment_date" type="date" value="{{ now()->toDateString() }}" required></div>
                <div class="form-field"><label for="amount">Jumlah</label><input id="amount" name="amount" type="number" min="0.01" max="{{ number_format((float) $invoice->total_amount - (float) $invoice->paid_amount, 2, '.', '') }}" step="0.01" required></div>
                <div class="form-field"><label for="payment_method">Metode</label><select id="payment_method" name="payment_method" required><option value="cash">Tunai</option><option value="transfer">Transfer</option></select></div>
                <div class="form-field"><label for="reference">Referensi</label><input id="reference" name="reference" type="text" maxlength="80"></div>
                <div class="form-field full-width"><label for="payment_notes">Catatan</label><textarea id="payment_notes" name="notes" rows="2"></textarea></div>
            </div>
            @error('amount')<span class="field-error">{{ $message }}</span>@enderror
            <div class="form-actions"><button class="button primary" type="submit">Simpan pembayaran</button></div>
        </form>
    @endif
@endsection
