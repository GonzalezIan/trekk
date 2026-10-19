<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Refugio;

class RefugioSeeder extends Seeder
{
    public function run(): void
    {
        // 30 refugios aleatorios
        Refugio::factory()->count(30)->create();
    }
}