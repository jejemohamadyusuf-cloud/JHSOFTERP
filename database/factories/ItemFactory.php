<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Item;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
final class ItemFactory extends Factory
{
    protected $model = Item::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('ITEM-#####'),
            'name' => fake()->words(3, true),
            'item_type' => 'finished',
            'category_id' => null,
            'unit_id' => Unit::factory(),
            'standard_cost' => 0,
            'is_active' => true,
        ];
    }
}
