<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTakeLine extends Model
{
    protected $fillable = [
        'stock_take_id', 'item_id', 'warehouse_id', 'location_id',
        'system_qty', 'physical_qty', 'discrepancy_qty',
    ];

    protected function casts(): array
    {
        return [
            'system_qty' => 'decimal:4',
            'physical_qty' => 'decimal:4',
            'discrepancy_qty' => 'decimal:4',
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
}
