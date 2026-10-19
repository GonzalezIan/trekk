<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Servicio;

class ServicioSeeder extends Seeder
{
    public function run(): void
    {
        // 30 servicios aleatorios
        Servicio::factory()->count(30)->create();
    }
}