<?php

@extends('layouts.app', ['title' => 'Purchase Invoice'])

@section('content')
    <div class="section-heading">
        <div><h1>Invoice Pembelian</h1><p>Purchase invoice · {{ $date }}</p></div>
        <div class="actions">
            <a class="button secondary" href="{{ route('goods-receipts.index') }}">← Daftar Receiving</a>
        </div>
    </div>
    <x-pic-guide module="purchase-invoices" />
    <div class="table-card selectable-list" data-selectable-list>
        <div class="table-toolbar"><strong>Invoice yang belum terbit</strong><span>{{ $uninvoicedGoodsReceipts->count() }} barang</span></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="list-select-cell">Pilih</th>
                        <th>Nomor GR</th>
                        <th>Supplier</th>
                        <th>Tanggal GR</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($uninvoicedGoodsReceipts as $goodsReceipt)
                        @php($canCreateInvoice = $goodsReceipt->status === 'delivered' && ! $goodsReceipt->invoices()->exists())
                        <tr>
                            <td class="list-select-cell">
                                <input type="radio" name="selected_goods_receipt" data-list-select aria-label="Pilih GR {{ $goodsReceipt->gr_number }}">
                                <template data-list-actions>
                                    @if ($canCreateInvoice)
                                        <a class="button primary" href="{{ route('goods-receipts.show', $goodsReceipt) }}">Buat invoice</a>
                                    @endif
                                </template>
                            </td>
                            <td><strong>{{ $goodsReceipt->gr_number }}</strong></td>
                            <td>{{ $goodsReceipt->purchaseOrder->supplier->name ?? '—' }}</td>
                            <td>{{ $goodsReceipt->receipt_date->format('d/m/Y') }}</td>
                            <td>Rp {{ number_format((float) $goodsReceipt->total_amount, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('components.list-action-panel', [
            'staticActions' => [
                [
                    'label' => '＋ Buat Invoice Pembelian',
                    'url' => route('goods-receipts.create'),
                    'class' => 'primary',
                ],
            ],
        ])
        <div class="pagination">{{ $purchaseInvoices->links() }}</div>
    </div>
@endsection