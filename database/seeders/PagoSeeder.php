<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pago;
use App\Models\Reserva;

class PagoSeeder extends Seeder
{
    public function run(): void
    {
        $reservas = Reserva::all();
        foreach ($reservas as $reserva) {
            // A cada reserva le damos 1 pago
            Pago::create([
                'reserva_id' => $reserva->id,
                'monto' => $reserva->importe_total,
                'moneda' => 'ARS',
                'medio_pago' => fake()->randomElement(['Tarjeta', 'Transferencia', 'Efectivo']),
                'fecha_hora_pago' => now(),
                'tipo_pago' => 'TOTAL',
                'resultado_transaccion' => fake()->randomElement(['APROBADO', 'RECHAZADO']),
            ]);
        }
    }
}