<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Convenio;
use App\Models\Refugio;

class ConvenioSeeder extends Seeder
{
    public function run(): void
    {
        $refugios = Refugio::all();

        foreach (range(1, 30) as $index) {
            Convenio::create([
                'refugio_id' => $refugios->random()->id,
                'nombre_convenio' => 'Convenio ' . fake()->unique()->word(),
                'fecha_inicio' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
                'fecha_fin' => fake()->dateTimeBetween('+2 months', '+1 year')->format('Y-m-d'),
                'camas_totales' => fake()->numberBetween(10, 50),
                'camas_disponibles' => fake()->numberBetween(0, 10),
                'precio_cama' => fake()->randomFloat(2, 1000, 5000),
            ]);
        }
    }
}