<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ReservaFactory extends Factory
{
    protected $model = \App\Models\Reserva::class;

    public function definition(): array
    {
        return [
            'fecha_hora_registro' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'estado' => $this->faker->randomElement(['PENDIENTE', 'CONFIRMADA', 'EN_CURSO', 'REALIZADA', 'CANCELADA']),
            'situacion_de_pago' => $this->faker->randomElement(['PENDIENTE_DE_PAGO_TOTAL', 'PAGO_COMPLETO']),
            'importe_total' => $this->faker->randomFloat(2, 15000, 100000),
            'motivo_cancelacion' => null,
            'fecha_hora_vencimiento' => $this->faker->dateTimeBetween('now', '+1 week'),
        ];
    }
}