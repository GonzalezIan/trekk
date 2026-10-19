<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RefugioFactory extends Factory
{
    protected $model = \App\Models\Refugio::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Refugio ' . $this->faker->unique()->word(),
            'descripcion' => $this->faker->sentence(10),
            'ubicacion' => $this->faker->city(),
        ];
    }
}