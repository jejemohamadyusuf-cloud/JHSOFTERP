@extends('layouts.app', ['title' => $order->so_number])

@section('content')
    <div class="section-heading">
        <div><h1>{{ $order->so_number }}</h1><p>Sales Order · {{ $order->order_date->format('d M Y') }}</p></div>
        <div class="actions">
            <a class="button secondary" href="{{ route('sales-orders.index') }}">← Daftar</a>
            @if ($order->status === 'draft')
                <a class="button secondary" href="{{ route('sales-orders.edit', $order) }}">Ubah</a>
                <form method="POST" action="{{ route('sales-orders.confirm', $order) }}" onsubmit="return confirm('Konfirmasi Sales Order ini?')">
                    @csrf
                    <button class="button primary" type="submit">Konfirmasi</button>
                </form>
            @elseif ($order->status === 'confirmed')
                @if (auth()->user()->hasRole('production'))
                    <a class="button primary" href="{{ route('work-orders.create', ['sales_order_id' => $order->id]) }}">Buat Work Order</a>
                @endif
                @if (auth()->user()->hasRole('sales'))
                    <a class="button secondary" href="{{ route('deliveries.create', ['sales_order_id' => $order->id]) }}">Buat Delivery</a>
                @endif
            @endif
        </div>
    </div>
    @error('status')<div class="flash error">{{ $message }}</div>@enderror
    <x-pic-guide module="sales-orders" />
    <div class="order-summary">
        <div class="summary-card"><span>Status</span><strong><span class="status status-{{ $order->status }}">{{ ['draft' => 'Draft', 'confirmed' => 'Dikonfirmasi', 'cancelled' => 'Dibatalkan'][$order->status] ?? $order->status }}</span></strong></div>
        <div class="summary-card"><span>Pelanggan</span><strong>{{ $order->customer->code }} — {{ $order->customer->name }}</strong></div>
        <div class="summary-card"><span>Tanggal pesanan</span><strong>{{ $order->order_date->format('d M Y') }}</strong></div>
    </div>
    <div class="table-card">
        <div class="table-toolbar"><strong>Rincian barang</strong><span>{{ $order->lines->count() }} baris</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Barang</th><th>Kuantitas</th><th>Harga satuan</th><th class="amount-col">Jumlah</th></tr></thead>
                <tbody>
                    @foreach ($order->lines as $line)
                        <tr>
                            <td><strong>{{ $line->item->code }}</strong><br><span class="muted">{{ $line->item->name }}</span></td>
                            <td>{{ number_format((float) $line->qty, 2, ',', '.') }} {{ $line->unit->code }}</td>
                            <td>Rp {{ number_format((float) $line->unit_price, 2, ',', '.') }}</td>
                            <td class="amount-col">Rp {{ number_format(round((float) $line->qty * (float) $line->unit_price, 2), 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr><th colspan="3" class="amount-col">Total pesanan</th><th class="amount-col">Rp {{ number_format((float) $order->total_amount, 2, ',', '.') }}</th></tr></tfoot>
            </table>
        </div>
    </div>
    @if ($order->notes)
        <div class="note-panel"><span class="note-icon">i</span><span><strong>Catatan:</strong> {{ $order->notes }}</span></div>
    @endif
    <div class="note-panel"><span class="note-icon">i</span><span>Sales Order hanya mencatat permintaan pelanggan. Konfirmasi pesanan tidak mengurangi stok dan belum membentuk piutang; stok berubah saat delivery dan piutang saat invoice.</span></div>
@endsection
