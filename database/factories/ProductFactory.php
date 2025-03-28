<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Unit;
use App\Models\Category;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Vérifie d'abord s'il existe des unités/catégories
        $unitIds = Unit::pluck('id')->toArray();
        $categoryIds = Category::pluck('id')->toArray();

        return [
            'name' => $this->faker->unique()->word,
            'code' => $this->faker->unique()->ean8,
            'selling_price' => $this->faker->randomFloat(2, 1, 1000),
            'alert_quantity' => $this->faker->numberBetween(1, 10),
            'unit_id' => count($unitIds) > 0 
                ? $this->faker->randomElement($unitIds) 
                : Unit::factory(),
            'category_id' => count($categoryIds) > 0 
                ? $this->faker->randomElement($categoryIds) 
                : Category::factory(),
            'quantity_in_stock' => $this->faker->numberBetween(0, 100),
            'purchase_cost' => $this->faker->randomFloat(2, 0.5, 500),
            'cost_price' => $this->faker->randomFloat(2, 0.5, 500),
            'image' => $this->faker->imageUrl(200, 200, 'products'),
            'description' => $this->faker->paragraph,
        ];
    }
}