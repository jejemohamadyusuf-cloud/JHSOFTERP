@extends('layouts.app', ['title' => 'Invoice'])

@section('content')
    <div class="section-heading"><div><h1>Invoice</h1><p>Piutang pelanggan dari pengiriman yang sudah diposting.</p></div></div>
    <div class="table-card selectable-list" data-selectable-list>
        <div class="table-toolbar"><strong>Delivery belum ditagihkan</strong><span>{{ $uninvoicedDeliveries->count() }} data</span></div>
        <div class="table-wrap"><table>
            <thead><tr><th class="list-select-cell">Pilih</th><th>Nomor delivery</th><th>Tanggal</th><th>Sales Order</th><th>Pelanggan</th><th>Baris</th></tr></thead>
            <tbody>
                @forelse ($uninvoicedDeliveries as $delivery)
                    <tr>
                        <td class="list-select-cell">
                            <input type="radio" name="selected_uninvoiced_delivery" data-list-select aria-label="Pilih delivery {{ $delivery->delivery_number }}">
                            <template data-list-actions><a class="button primary" href="{{ route('deliveries.show', $delivery) }}">Buat invoice</a></template>
                        </td>
                        <td><strong>{{ $delivery->delivery_number }}</strong></td>
                        <td>{{ $delivery->delivery_date->format('d/m/Y') }}</td>
                        <td>{{ $delivery->salesOrder->so_number }}</td>
                        <td>{{ $delivery->salesOrder->customer->name }}</td>
                        <td>{{ $delivery->lines_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">Tidak ada delivery yang menunggu invoice.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @include('components.list-action-panel')
    </div>
    <div class="table-card selectable-list" data-selectable-list>
        <div class="table-toolbar"><strong>Daftar Invoice</strong><span>{{ $invoices->total() }} data</span></div>
        <div class="table-wrap"><table>
            <thead><tr><th class="list-select-cell">Pilih</th><th>Nomor</th><th>Tanggal</th><th>Pelanggan</th><th>Delivery</th><th>Total</th><th>Dibayar</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr>
                        <td class="list-select-cell">
                            <input type="radio" name="selected_invoice" data-list-select aria-label="Pilih invoice {{ $invoice->invoice_number }}">
                            <template data-list-actions><a class="button secondary" href="{{ route('invoices.show', $invoice) }}">Detail</a>
                                @include('components.cancel-transaction-form', ['type' => 'invoice', 'id' => $invoice->id])
                            </template>
                        </td>
                        <td><strong>{{ $invoice->invoice_number }}</strong></td>
                        <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                        <td>{{ $invoice->customer->name }}</td>
                        <td>{{ $invoice->delivery?->delivery_number ?? '—' }}</td>
                        <td>Rp {{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</td>
                        <td>Rp {{ number_format((float) $invoice->paid_amount, 2, ',', '.') }}</td>
                        <td><span class="status status-{{ $invoice->status }}">{{ ['open' => 'Terbuka', 'partial' => 'Dibayar sebagian', 'paid' => 'Lunas'][$invoice->status] ?? $invoice->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-state">Belum ada invoice. Terbitkan invoice dari detail delivery.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @include('components.list-action-panel')
        <div class="pagination">{{ $invoices->links() }}</div>
    </div>
@endsection
