<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Http\Requests\PaymentRequest;
use App\Services\DeliveryService;
use App\Services\InvoiceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

final class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly DeliveryService $deliveries,
    ) {}

    public function index(): View
    {
        return view('invoices.index', [
            'invoices' => $this->invoices->paginate(),
            'uninvoicedDeliveries' => $this->deliveries->uninvoiced(),
        ]);
    }

    public function show(int $invoice): View
    {
        return view('invoices.show', ['invoice' => $this->invoices->find($invoice)]);
    }

    public function storeFromDelivery(InvoiceRequest $request, int $delivery): RedirectResponse
    {
        $invoice = $this->invoices->createFromDelivery($delivery, $request->validated());

        return Redirect::route('invoices.show', $invoice)->with('status', 'Invoice berhasil diterbitkan.');
    }

    public function storePayment(PaymentRequest $request, int $invoice): RedirectResponse
    {
        $this->invoices->recordPayment($invoice, $request->validated());

        return Redirect::route('invoices.show', $invoice)->with('status', 'Pembayaran berhasil dicatat.');
    }
}
