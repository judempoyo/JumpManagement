<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => $this->faker->date(),
            'product_id' => \App\Models\Product::factory(),
            'initial_stock' => $this->faker->numberBetween(0, 100),
            'final_stock' => $this->faker->numberBetween(0, 100),
            'notes' => $this->faker->sentence,
        ];
    }
}
