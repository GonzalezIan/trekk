<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Paquete;
use App\Models\SalidaProgramada;
use App\Models\Refugio;
use App\Models\Servicio;

class PaqueteSeeder extends Seeder
{
    public function run(): void
    {
        $salidas = SalidaProgramada::all();
        $refugios = Refugio::all();
        $servicios = Servicio::all();

        foreach (range(1, 30) as $index) {
            $paquete = Paquete::factory()->create([
                'salida_programada_id' => $salidas->random()->id,
                'refugio_id' => $refugios->random()->id,
            ]);

            // Asignamos 2 servicios al azar (Llena servicios_incluidos)
            if ($servicios->isNotEmpty()) {
                $serv1 = $servicios->random();
                $serv2 = $servicios->random();
                $paquete->servicios()->attach([
                    $serv1->id => ['cantidad' => rand(1, 2)],
                    $serv2->id => ['cantidad' => rand(1, 3)],
                ]);
            }
        }
    }
}