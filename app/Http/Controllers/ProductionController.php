<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FinishedGoodsReceiptRequest;
use App\Http\Requests\IssueWorkOrderMaterialRequest;
use App\Http\Requests\ProductionEntryRequest;
use App\Http\Requests\QcInspectionRequest;
use App\Services\ProductionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

final class ProductionController extends Controller
{
    public function __construct(private readonly ProductionService $production) {}

    public function issueMaterial(
        IssueWorkOrderMaterialRequest $request,
        int $workOrder,
        int $material,
    ): RedirectResponse {
        $this->production->issueMaterial($workOrder, $material, $request->validated());

        return Redirect::route('work-orders.show', $workOrder)->with('status', 'Material berhasil dikeluarkan untuk produksi.');
    }

    public function storeEntry(ProductionEntryRequest $request, int $workOrder): RedirectResponse
    {
        $this->production->recordProduction($workOrder, $request->validated());

        return Redirect::route('work-orders.show', $workOrder)->with('status', 'Hasil produksi berhasil dicatat.');
    }

    public function storeInspection(
        QcInspectionRequest $request,
        int $workOrder,
        int $productionEntry,
    ): RedirectResponse {
        $this->production->inspect($workOrder, $productionEntry, $request->validated());

        return Redirect::route('work-orders.show', $workOrder)->with('status', 'Hasil pemeriksaan QC berhasil dicatat.');
    }

    public function storeReceipt(
        FinishedGoodsReceiptRequest $request,
        int $workOrder,
        int $qcInspection,
    ): RedirectResponse {
        $this->production->receiveFinishedGoods($workOrder, $qcInspection, $request->validated());

        return Redirect::route('work-orders.show', $workOrder)->with('status', 'Barang jadi diterima dan saldo stok diperbarui.');
    }
}
