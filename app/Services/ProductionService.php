<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FinishedGoodsReceipt;
use App\Models\InventoryTransaction;
use App\Models\Location;
use App\Models\ProductionEntry;
use App\Models\QcInspection;
use App\Models\WorkOrder;
use App\Models\WorkOrderMaterial;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ProductionService
{
    public function __construct(
        private readonly StockLevelService $stockLevels,
        private readonly AccountingService $accounting,
        private readonly InventoryValuationService $valuation,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function issueMaterial(int $workOrderId, int $materialId, array $attributes): WorkOrderMaterial
    {
        return DB::transaction(function () use ($workOrderId, $materialId, $attributes): WorkOrderMaterial {
            $workOrder = WorkOrder::query()->lockForUpdate()->findOrFail($workOrderId);
            $material = WorkOrderMaterial::query()
                ->where('work_order_id', $workOrder->id)
                ->lockForUpdate()
                ->findOrFail($materialId);

            if (in_array($workOrder->status, ['completed', 'cancelled'], true)) {
                throw ValidationException::withMessages(['qty' => 'Work Order yang selesai atau dibatalkan tidak dapat menerima material.']);
            }

            $remaining = (float) $material->qty_required - (float) $material->qty_issued;
            if ((float) $attributes['qty'] > $remaining + 0.0000001) {
                throw ValidationException::withMessages(['qty' => 'Kuantitas material melebihi kebutuhan Work Order yang tersisa.']);
            }

            $locationId = $attributes['location_id'] ?? null;
            if ((float) $material->qty_issued > 0
                && (int) $material->location_id !== (int) ($locationId ?? 0)) {
                throw ValidationException::withMessages([
                    'location_id' => 'Gunakan lokasi yang sama untuk seluruh pengeluaran material ini.',
                ]);
            }
            if ($locationId !== null && ! Location::query()
                ->whereKey($locationId)
                ->where('warehouse_id', $material->warehouse_id)
                ->where('is_active', true)
                ->exists()) {
                throw ValidationException::withMessages(['location_id' => 'Pilih lokasi aktif di gudang Work Order.']);
            }

            $qty = (string) $attributes['qty'];
            $unitCost = $this->valuation->averageUnitCost((int) $material->item_id) ?? '0.0000';
            $this->stockLevels->decrease(
                (int) $material->item_id,
                (int) $material->warehouse_id,
                $locationId,
                $qty,
            );

            $material->update([
                'location_id' => $locationId,
                'qty_issued' => number_format((float) $material->qty_issued + (float) $qty, 4, '.', ''),
                'unit_cost' => $unitCost,
            ]);

            $transaction = InventoryTransaction::query()->create([
                'transaction_date' => $attributes['issue_date'].' 00:00:00',
                'item_id' => $material->item_id,
                'warehouse_id' => $material->warehouse_id,
                'location_id' => $locationId,
                'transaction_type' => 'out',
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'reference_type' => WorkOrderMaterial::class,
                'reference_id' => $material->id,
                'notes' => "Pengeluaran bahan untuk WO {$workOrder->wo_number}",
            ]);

            $amountCents = AccountingService::valueCents($qty, $unitCost);
            if ($amountCents > 0) {
                $this->accounting->post($transaction, $attributes['issue_date'], $workOrder->wo_number, "Pengeluaran bahan WO {$workOrder->wo_number}", [
                    ['account_code' => '1132', 'debit_cents' => $amountCents, 'credit_cents' => 0, 'description' => 'Barang dalam proses'],
                    ['account_code' => $this->accounting->inventoryAccountCode($material->item->item_type), 'debit_cents' => 0, 'credit_cents' => $amountCents, 'description' => "Bahan baku {$material->item->name}"],
                ]);
            }

            $allIssued = ! $workOrder->materials()
                ->whereRaw('qty_issued < qty_required')
                ->exists();

            if ($allIssued && $workOrder->status === 'planned') {
                $workOrder->update(['status' => 'released']);
            }

            return $material->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function recordProduction(int $workOrderId, array $attributes): ProductionEntry
    {
        return DB::transaction(function () use ($workOrderId, $attributes): ProductionEntry {
            $workOrder = WorkOrder::query()->lockForUpdate()->findOrFail($workOrderId);
            $this->ensureCanProduce($workOrder);

            if ($workOrder->materials()->whereRaw('qty_issued < qty_required')->exists()) {
                throw ValidationException::withMessages([
                    'qty' => 'Seluruh kebutuhan material Work Order harus dikeluarkan sebelum produksi dicatat.',
                ]);
            }

            $remaining = (float) $workOrder->qty_planned - (float) $workOrder->qty_produced;
            if ((float) $attributes['qty'] > $remaining + 0.0000001) {
                throw ValidationException::withMessages(['qty' => 'Kuantitas produksi melebihi sisa rencana Work Order.']);
            }

            $entry = $workOrder->productionEntries()->create([
                'production_date' => $attributes['production_date'],
                'qty' => $attributes['qty'],
                'cost' => $attributes['cost'],
                'notes' => $attributes['notes'] ?? null,
            ]);

            $produced = (float) $workOrder->qty_produced + (float) $attributes['qty'];
            $workOrder->update([
                'qty_produced' => number_format($produced, 4, '.', ''),
                'status' => $produced + 0.0000001 >= (float) $workOrder->qty_planned ? 'completed' : 'in_progress',
            ]);

            return $entry;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function inspect(int $workOrderId, int $entryId, array $attributes): QcInspection
    {
        return DB::transaction(function () use ($workOrderId, $entryId, $attributes): QcInspection {
            $workOrder = WorkOrder::query()->lockForUpdate()->findOrFail($workOrderId);
            $entry = ProductionEntry::query()
                ->where('work_order_id', $workOrder->id)
                ->lockForUpdate()
                ->findOrFail($entryId);
            if ($workOrder->status === 'cancelled') {
                throw ValidationException::withMessages(['qty_pass' => 'Work Order yang dibatalkan tidak dapat diperiksa.']);
            }

            $inspectionQty = (float) $attributes['qty_pass'] + (float) $attributes['qty_reject'];
            $remaining = (float) $entry->qty - (float) $entry->qcInspections()->sum(DB::raw('qty_pass + qty_reject'));
            if ($inspectionQty <= 0 || $inspectionQty > $remaining + 0.0000001) {
                throw ValidationException::withMessages([
                    'qty_pass' => 'Jumlah QC harus lebih dari nol dan tidak boleh melebihi produksi yang belum diperiksa.',
                ]);
            }

            $result = (float) $attributes['qty_reject'] === 0.0
                ? 'passed'
                : ((float) $attributes['qty_pass'] === 0.0 ? 'rejected' : 'partial');

            return $workOrder->qcInspections()->create([
                'production_entry_id' => $entry->id,
                'inspection_date' => $attributes['inspection_date'],
                'qty_pass' => $attributes['qty_pass'],
                'qty_reject' => $attributes['qty_reject'],
                'result' => $result,
                'notes' => $attributes['notes'] ?? null,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function receiveFinishedGoods(int $workOrderId, int $inspectionId, array $attributes): FinishedGoodsReceipt
    {
        return DB::transaction(function () use ($workOrderId, $inspectionId, $attributes): FinishedGoodsReceipt {
            $workOrder = WorkOrder::query()->lockForUpdate()->findOrFail($workOrderId);
            $inspection = QcInspection::query()
                ->where('work_order_id', $workOrder->id)
                ->lockForUpdate()
                ->findOrFail($inspectionId);
            if ($workOrder->status === 'cancelled') {
                throw ValidationException::withMessages(['qty' => 'Work Order yang dibatalkan tidak dapat menerima barang jadi.']);
            }
            $alreadyReceived = (float) $inspection->finishedGoodsReceipts()->sum('qty');
            $remaining = (float) $inspection->qty_pass - $alreadyReceived;

            if ((float) $attributes['qty'] > $remaining + 0.0000001) {
                throw ValidationException::withMessages(['qty' => 'Penerimaan barang jadi melebihi kuantitas QC yang lulus dan belum diterima.']);
            }

            $locationId = $attributes['location_id'] ?? null;
            if ($locationId !== null && ! Location::query()
                ->whereKey($locationId)
                ->where('warehouse_id', $workOrder->warehouse_id)
                ->where('is_active', true)
                ->exists()) {
                throw ValidationException::withMessages(['location_id' => 'Pilih lokasi aktif di gudang hasil Work Order.']);
            }

            $entry = $inspection->productionEntry;
            $receipt = FinishedGoodsReceipt::query()->create([
                'work_order_id' => $workOrder->id,
                'qc_inspection_id' => $inspection->id,
                'item_id' => $workOrder->item_id,
                'warehouse_id' => $workOrder->warehouse_id,
                'location_id' => $locationId,
                'receipt_date' => $attributes['receipt_date'],
                'qty' => $attributes['qty'],
                'unit_cost' => $entry?->cost ?? '0',
            ]);

            $this->stockLevels->increase(
                (int) $workOrder->item_id,
                (int) $workOrder->warehouse_id,
                $locationId,
                (string) $attributes['qty'],
            );

            InventoryTransaction::query()->create([
                'transaction_date' => $attributes['receipt_date'].' 00:00:00',
                'item_id' => $workOrder->item_id,
                'warehouse_id' => $workOrder->warehouse_id,
                'location_id' => $locationId,
                'transaction_type' => 'in',
                'qty' => $attributes['qty'],
                'unit_cost' => $receipt->unit_cost,
                'reference_type' => FinishedGoodsReceipt::class,
                'reference_id' => $receipt->id,
                'notes' => "Penerimaan hasil produksi WO {$workOrder->wo_number}",
            ]);

            $amountCents = AccountingService::valueCents((string) $receipt->qty, (string) $receipt->unit_cost);
            if ($amountCents > 0) {
                $this->accounting->post($receipt, $receipt->receipt_date->toDateString(), $workOrder->wo_number, "Penerimaan hasil produksi WO {$workOrder->wo_number}", [
                    ['account_code' => '1133', 'debit_cents' => $amountCents, 'credit_cents' => 0, 'description' => "Barang jadi {$workOrder->item->name}"],
                    ['account_code' => '1132', 'debit_cents' => 0, 'credit_cents' => $amountCents, 'description' => 'Penyelesaian barang dalam proses'],
                ]);
            }

            return $receipt;
        });
    }

    private function ensureCanProduce(WorkOrder $workOrder): void
    {
        if (in_array($workOrder->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['qty' => 'Work Order yang selesai atau dibatalkan tidak dapat menerima hasil produksi.']);
        }
    }
}
