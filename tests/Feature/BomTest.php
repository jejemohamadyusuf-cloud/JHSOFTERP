<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BomTest extends TestCase
{
    use RefreshDatabase;

    public function test_bom_rejects_component_quantities_with_more_than_two_decimal_places(): void
    {
        $finishedItem = Item::factory()->create(['item_type' => 'finished']);
        $component = Item::factory()->create(['item_type' => 'raw']);

        $this->from(route('boms.create'))
            ->post(route('boms.store'), [
                'item_id' => $finishedItem->id,
                'version' => '1',
                'is_active' => '1',
                'lines' => [[
                    'component_item_id' => $component->id,
                    'qty' => '1.234',
                    'unit_id' => $component->unit_id,
                    'scrap_percent' => '0',
                ]],
            ])
            ->assertSessionHasErrors('lines.0.qty');

        $this->assertDatabaseCount('boms', 0);
    }
}
