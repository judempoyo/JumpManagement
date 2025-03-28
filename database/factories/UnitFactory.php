<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
       
            $units = ['Pièce', 'Kg', 'Litre', 'Mètre', 'Boîte', 'Carton', 'Paquet', 'Sachet', 'Rouleau', 'Bouteille'];
        
            return [
                'name' => $this->faker->unique()->randomElement($units),
                'description' => $this->faker->sentence,
            ];
       
    }
}
