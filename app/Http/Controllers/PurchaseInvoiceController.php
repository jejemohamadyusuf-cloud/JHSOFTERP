<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Services\PurchaseInvoiceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

final class PurchaseInvoiceController extends Controller
{
    public function __construct(
        private readonly PurchaseInvoiceService $purchaseInvoices,
    ) {}

    public function index(): View
    {
        return view('purchase-invoices.index', [
            'purchaseInvoices' => $this->purchaseInvoices->paginate(),
        ]);
    }

    public function show(int $purchaseInvoice): View
    {
        return view('purchase-invoices.show', ['purchaseInvoice' => $this->purchaseInvoices->find($purchaseInvoice)]);
    }

    public function storeFromGoodsReceipt(PaymentRequest $request, int $goodsReceipt): RedirectResponse
    {
        $purchaseInvoice = $this->purchaseInvoices->createFromGoodsReceipt($goodsReceipt, $request->validated());

        return Redirect::route('purchase-invoices.show', $purchaseInvoice)->with('status', 'Purchase invoice berhasil diterbitkan.');
    }

    public function storePayment(PaymentRequest $request, int $purchaseInvoice): RedirectResponse
    {
        $this->purchaseInvoices->recordPayment($purchaseInvoice, $request->validated());

        return Redirect::route('purchase-invoices.show', $purchaseInvoice)->with('status', 'Pembayaran berhasil dicatat.');
    }
}