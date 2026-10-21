<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ExcursionFactory extends Factory
{
    protected $model = \App\Models\Excursion::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Excursión ' . $this->faker->unique()->word(),
            'descripcion' => $this->faker->sentence(15),
            'dificultad' => $this->faker->randomElement(['BAJA', 'MEDIO', 'ALTA']),
            'duracion' => $this->faker->numberBetween(1, 7), // en días (RN-02 del CU 1)
            'recorrido' => $this->faker->sentence(),
            'etapas' => $this->faker->sentence(),
            'experiencia_requerida' => $this->faker->randomElement(['SI', 'NO']), // Lo dejamos string como pediste
            'acepta_menores' => $this->faker->boolean(),
            'equipamiento_obligatorio' => $this->faker->sentence(),
            'requiere_certificado_medico' => $this->faker->boolean(30), // 30% de probabilidad de que sea true
            'estado' => $this->faker->randomElement(['ACTIVA', 'INACTIVA']),
        ];
    }
}
