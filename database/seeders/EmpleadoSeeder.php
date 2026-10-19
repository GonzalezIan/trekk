<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empleado;

class EmpleadoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Empleado fijo (Pedro, el Guía)
        Empleado::create([
            'dni' => '99887766',
            'nombre' => 'Pedro',
            'apellido' => 'Guía',
            'fecha_nacimiento' => '1985-01-01',
            'categoria' => 'GUIA',
            'estado' => 'ACTIVO',
        ]);
        
        // 2. 30 empleados aleatorios
        Empleado::factory()->count(30)->create();
    }
}
