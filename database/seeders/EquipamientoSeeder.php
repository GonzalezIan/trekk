<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipamiento;

class EquipamientoSeeder extends Seeder
{
    public function run(): void
    {
        // 30 equipamientos aleatorios
        Equipamiento::factory()->count(30)->create();
    }
}