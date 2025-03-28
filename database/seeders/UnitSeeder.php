<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run()
    {
        $units = [
            ['name' => 'Pièce', 'description' => 'Unité de comptage par pièce'],
            ['name' => 'Kg', 'description' => 'Kilogramme'],
            ['name' => 'Litre', 'description' => 'Unité de volume'],
            ['name' => 'Mètre', 'description' => 'Unité de longueur'],
            ['name' => 'Boîte', 'description' => 'Contenu d\'une boîte'],
            ['name' => 'Carton', 'description' => 'Contenu d\'un carton'],
            ['name' => 'Paquet', 'description' => 'Un paquet d\'articles'],
            ['name' => 'Sachet', 'description' => 'Contenu d\'un sachet'],
            ['name' => 'Rouleau', 'description' => 'Article en forme de rouleau'],
            ['name' => 'Bouteille', 'description' => 'Contenu d\'une bouteille'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['name' => $unit['name']], // Vérifie l'unicité par le nom
                ['description' => $unit['description']]
            );
        }
    }
}