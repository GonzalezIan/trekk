<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalidaProgramada;
use App\Models\Excursion;
use App\Models\Empleado;
use App\Models\Equipamiento;

class SalidaProgramadaSeeder extends Seeder
{
    public function run(): void
    {
        $excursiones = Excursion::all();
        $guias = Empleado::where('categoria', 'GUIA')->get();
        $equipos = Equipamiento::all();

        // Creamos 30 salidas
        foreach (range(1, 30) as $index) {
            $salida = SalidaProgramada::factory()->create([
                'excursion_id' => $excursiones->random()->id,
            ]);

            // 1. Asignamos un Guía (Llena empleados_asignados)
            if ($guias->isNotEmpty()) {
                $salida->empleados()->attach($guias->random()->id, ['rol' => 'GUIA']);
            }

            // 2. Asignamos 2 equipamientos al azar (Llena equipos_prestados)
            if ($equipos->isNotEmpty()) {
                $equipo1 = $equipos->random();
                $equipo2 = $equipos->random();
                $salida->equipoPrestado()->attach([
                    $equipo1->id => ['cantidad' => rand(1, 3)],
                    $equipo2->id => ['cantidad' => rand(1, 2)],
                ]);
            }
        }
    }
}
