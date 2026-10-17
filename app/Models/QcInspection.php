<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Model;

class QcInspection extends Model
{
    protected $fillable = [
        'work_order_id', 'production_entry_id', 'inspection_date',
        'qty_pass', 'qty_reject', 'result', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'qty_pass' => 'decimal:4',
            'qty_reject' => 'decimal:4',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function productionEntry(): BelongsTo
    {
        return $this->belongsTo(ProductionEntry::class);
    }

    public function finishedGoodsReceipts(): HasMany
    {
        return $this->hasMany(FinishedGoodsReceipt::class);
    }
}
