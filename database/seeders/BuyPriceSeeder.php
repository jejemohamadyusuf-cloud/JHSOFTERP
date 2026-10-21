<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Item;
use App\Models\MasterPrice;
use Illuminate\Database\Seeder;

final class BuyPriceSeeder extends Seeder
{
    private const START_DATE = '2026-10-01';

    public function run(): void
    {
        $created = 0;

        Item::query()
            ->where('is_active', true)
            ->where('standard_cost', '>', 0)
            ->orderBy('id')
            ->each(function (Item $item) use (&$created): void {
                $hasBuyPrice = MasterPrice::query()
                    ->where('item_id', $item->id)
                    ->where('price_type', 'buy')
                    ->exists();

                if ($hasBuyPrice) {
                    return;
                }

                MasterPrice::query()->create([
                    'item_id' => $item->id,
                    'price_type' => 'buy',
                    'price' => $item->standard_cost,
                    'start_date' => self::START_DATE,
                ]);
                $created++;
            });

        $this->command?->info("Harga beli berhasil dibuat untuk {$created} barang.");
    }
}
