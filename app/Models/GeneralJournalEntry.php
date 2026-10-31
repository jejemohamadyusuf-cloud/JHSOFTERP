<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class GeneralJournalEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'journal_number',
        'journal_date',
        'reference',
        'description',
        'source_type',
        'source_id',
        'created_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'journal_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GeneralJournalLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancellation(): HasOne
    {
        return $this->hasOne(TransactionCancellation::class, 'transaction_id')
            ->where('transaction_type', 'journal');
    }
}
