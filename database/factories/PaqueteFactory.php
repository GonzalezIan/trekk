<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaqueteFactory extends Factory
{
    protected $model = \App\Models\Paquete::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Paquete ' . $this->faker->word(),
            'descripcion' => $this->faker->sentence(12),
            'precio_calculado' => $this->faker->randomFloat(2, 10000, 50000),
            'estado' => $this->faker->randomElement(['DISPONIBLE', 'CANCELADO']),
        ];
    }
}