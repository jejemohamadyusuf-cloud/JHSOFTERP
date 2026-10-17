<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Model;

class FinishedGoodsReceipt extends Model
{
    protected $fillable = [
        'work_order_id', 'qc_inspection_id', 'item_id', 'warehouse_id',
        'location_id', 'receipt_date', 'qty', 'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function qcInspection(): BelongsTo
    {
        return $this->belongsTo(QcInspection::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
