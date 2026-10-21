<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Item;
use App\Models\MasterPrice;
use Database\Seeders\BuyPriceSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BuyPriceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_buy_price_seeder_creates_prices_from_standard_cost(): void
    {
        $this->seed(DatabaseSeeder::class);

        $items = Item::query()->where('is_active', true)->where('standard_cost', '>', 0)->get();
        $this->assertCount($items->count(), MasterPrice::query()->where('price_type', 'buy')->get());

        foreach ($items as $item) {
            $this->assertDatabaseHas('master_prices', [
                'item_id' => $item->id,
                'price_type' => 'buy',
            ]);
            $price = MasterPrice::query()
                ->where('item_id', $item->id)
                ->where('price_type', 'buy')
                ->firstOrFail();
            $this->assertSame($item->standard_cost, $price->price);
            $this->assertSame('2026-10-01', $price->start_date->toDateString());
        }
    }

    public function test_buy_price_seeder_is_idempotent_and_does_not_overwrite_existing_prices(): void
    {
        $this->seed(MasterDataSeeder::class);

        $item = Item::query()->where('code', 'BB-PLY-001')->firstOrFail();
        MasterPrice::query()->create([
            'item_id' => $item->id,
            'price_type' => 'buy',
            'price' => '300000.0000',
            'start_date' => '2026-10-01',
        ]);

        $this->seed(BuyPriceSeeder::class);
        $this->seed(BuyPriceSeeder::class);

        $this->assertSame(1, MasterPrice::query()->where('item_id', $item->id)->where('price_type', 'buy')->count());
        $this->assertDatabaseHas('master_prices', [
            'item_id' => $item->id,
            'price_type' => 'buy',
            'price' => '300000.0000',
        ]);
    }
}
