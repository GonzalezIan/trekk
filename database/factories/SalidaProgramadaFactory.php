<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SalidaProgramadaFactory extends Factory
{
    protected $model = \App\Models\SalidaProgramada::class;

    public function definition(): array
    {
        $fechaInicio = $this->faker->dateTimeBetween('+1 week', '+3 months');
        $fechaFin = (clone $fechaInicio)->modify('+' . $this->faker->numberBetween(1, 5) . ' days');
        $cupo = $this->faker->numberBetween(5, 20);

        return [
            'fecha_inicio' => $fechaInicio->format('Y-m-d'),
            'hora_de_salida' => $this->faker->time('H:i:s'),
            'fecha_fin' => $fechaFin->format('Y-m-d'),
            'cupo_maximo' => $cupo,
            'cupo_disponible' => $cupo, // Al principio está vacío
            'precio_base' => $this->faker->randomFloat(2, 2000, 8000),
            'estado' => 'PROGRAMADA',
            'motivo_cancelacion' => null,
        ];
    }
}
