<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionEntry extends Model
{
    protected $fillable = ['work_order_id', 'production_date', 'qty', 'cost', 'notes'];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'qty' => 'decimal:4',
            'cost' => 'decimal:4',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function qcInspections(): HasMany
    {
        return $this->hasMany(QcInspection::class);
    }
}
