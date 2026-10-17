<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'transaction_date', 'item_id', 'warehouse_id', 'location_id',
        'transaction_type', 'qty', 'unit_cost', 'reference_type', 'reference_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
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

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
