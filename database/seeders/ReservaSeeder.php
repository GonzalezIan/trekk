<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\Paquete;
use App\Models\Equipamiento;

class ReservaSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = Cliente::all();
        $paquetes = Paquete::all();
        $equipos = Equipamiento::all();

        foreach (range(1, 30) as $index) {
            $reserva = Reserva::factory()->create([
                'cliente_id' => $clientes->random()->id,
                'paquete_id' => $paquetes->random()->id,
            ]);

            // Alquilamos equipamiento extra en la reserva (Llena equipos_alquilados)
            if ($equipos->isNotEmpty()) {
                $equipo = $equipos->random();
                $reserva->equipos_alquilados()->attach($equipo->id, ['cantidad' => rand(1, 2)]);
            }
        }
    }
}