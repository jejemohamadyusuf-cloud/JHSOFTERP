<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTake extends Model
{
    use SoftDeletes;

    protected $fillable = ['stock_take_number', 'stock_take_date', 'notes'];

    protected function casts(): array
    {
        return ['stock_take_date' => 'date'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockTakeLine::class);
    }

    public function inventoryTransactions(): MorphMany
    {
        return $this->morphMany(InventoryTransaction::class, 'reference');
    }
}
