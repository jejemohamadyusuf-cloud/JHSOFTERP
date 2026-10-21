<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'purchase_order_id', 'item_id', 'qty', 'qty_received', 'unit_price', 'unit_id', 'master_price_id',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:4',
            'qty_received' => 'decimal:4',
            'unit_price' => 'decimal:4',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function masterPrice(): BelongsTo
    {
        return $this->belongsTo(MasterPrice::class);
    }

    public function goodsReceiptLines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }
}
