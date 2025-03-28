<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'code' => $this->faker->unique()->ean8,
            'selling_price' => $this->faker->randomFloat(2, 1, 1000),
            'alert_quantity' => $this->faker->numberBetween(1, 10),
            'unit_id' => \App\Models\Unit::factory(),
            'category_id' => \App\Models\Category::factory(),
            'quantity_in_stock' => $this->faker->numberBetween(0, 100),
            'purchase_cost' => $this->faker->randomFloat(2, 0.5, 500),
            'cost_price' => $this->faker->randomFloat(2, 0.5, 500),
            'image' => $this->faker->imageUrl(200, 200, 'products'),
            'description' => $this->faker->paragraph,
        ];
    }
}
