<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'item_type', 'category_id', 'unit_id', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function unitConversions(): HasMany
    {
        return $this->hasMany(ItemUnitConversion::class)->with('unit')->orderByDesc('base_units');
    }

    public function formatStockQuantity(float|string $quantity): string
    {
        $remaining = (float) $quantity;
        $parts = [];
        $conversions = $this->relationLoaded('unitConversions')
            ? $this->unitConversions
            : $this->unitConversions()->get();

        foreach ($conversions as $conversion) {
            $count = (int) floor(($remaining + 0.0000001) / $conversion->base_units);
            if ($count > 0) {
                $parts[] = $count.' '.$conversion->unit->code;
                $remaining -= $count * $conversion->base_units;
            }
        }

        $baseQuantity = rtrim(rtrim(number_format(max(0, $remaining), 2, ',', '.'), '0'), ',');
        if ($baseQuantity === '') {
            $baseQuantity = '0';
        }
        $parts[] = $baseQuantity.' '.$this->unit->code;

        return implode(' · ', $parts);
    }

    public function boms(): HasMany
    {
        return $this->hasMany(Bom::class);
    }

    public function componentLines(): HasMany
    {
        return $this->hasMany(BomLine::class, 'component_item_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function masterPrices(): HasMany
    {
        return $this->hasMany(MasterPrice::class);
    }

    public function supplierPrices(): HasMany
    {
        return $this->hasMany(MasterPrice::class)->where('price_type', 'buy');
    }
}
