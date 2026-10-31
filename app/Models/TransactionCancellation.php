<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransactionCancellation extends Model
{
    protected $fillable = ['transaction_type', 'transaction_id', 'reason', 'cancelled_by'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function reversalJournal(): HasOne
    {
        return $this->hasOne(GeneralJournalEntry::class, 'source_id')
            ->where('source_type', self::class);
    }
}
