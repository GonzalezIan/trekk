<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Excursion;

class ExcursionSeeder extends Seeder
{
    public function run(): void
    {
        // 30 excursiones aleatorias
        Excursion::factory()->count(30)->create();
    }
}
