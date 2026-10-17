<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MasterPrice extends Model
{
    protected $fillable = ['item_id', 'price', 'start_date', 'end_date'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function salesOrderLines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }
}
