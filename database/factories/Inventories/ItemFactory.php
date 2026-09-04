<?php

namespace Database\Factories\Inventories;

use App\Enums\Inventories\ItemType;
use App\Models\Inventories\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'slug' => fn (array $attributes): string => str($attributes['name'])->slug()->toString(),
            'type' => fake()->randomElement([ItemType::RawMaterial, ItemType::Tool]),
            'unit' => fake()->randomElement(['pcs', 'kg', 'liter', 'meter', 'unit']),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function bahanBaku(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ItemType::RawMaterial,
            'name' => 'Bahan '.fake()->word(),
        ]);
    }

    public function alat(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ItemType::Tool,
            'name' => 'Non Bahan Baku '.fake()->word(),
        ]);
    }
}
