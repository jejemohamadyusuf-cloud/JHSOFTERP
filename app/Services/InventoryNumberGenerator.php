<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InventoryNumberGenerator
{
    public function next(string $prefix, string $date): string
    {
        $period = $prefix.Carbon::parse($date)->format('ym');

        DB::table('transaction_sequences')->insertOrIgnore([
            'sequence_key' => $period,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('transaction_sequences')
            ->where('sequence_key', $period)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            throw new \RuntimeException('Nomor urut transaksi stok gagal dikunci.');
        }

        $nextNumber = (int) $sequence->last_number + 1;

        if ($nextNumber > 99999) {
            throw ValidationException::withMessages([
                'number' => "Nomor transaksi {$period} sudah mencapai batas 99.999.",
            ]);
        }

        DB::table('transaction_sequences')
            ->where('sequence_key', $period)
            ->update([
                'last_number' => $nextNumber,
                'updated_at' => now(),
            ]);

        return $period.str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
