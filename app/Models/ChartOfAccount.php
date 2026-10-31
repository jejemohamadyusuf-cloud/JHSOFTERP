<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ChartOfAccount extends Model
{
    protected $fillable = [
        'code',
        'name',
        'account_type',
        'normal_balance',
        'parent_id',
        'is_header',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_header' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
