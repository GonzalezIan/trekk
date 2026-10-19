<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServicioFactory extends Factory
{
    protected $model = \App\Models\Servicio::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->randomElement(['Almuerzo', 'Desayuno', 'Cena', 'Vianda', 'Alquiler de Carpa', 'Alquiler de Bolsa de Dormir']),
            'descripcion' => $this->faker->sentence(),
            'precio_servicio' => $this->faker->randomFloat(2, 500, 5000),
        ];
    }
}