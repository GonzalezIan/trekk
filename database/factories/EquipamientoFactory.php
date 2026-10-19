<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EquipamientoFactory extends Factory
{
    protected $model = \App\Models\Equipamiento::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->randomElement(['Carpa', 'Mochila', 'Bolsa de dormir', 'Bastón', 'Linterna']) . ' ' . $this->faker->word(),
            'descripcion' => $this->faker->sentence(),
            'precio_alquiler' => $this->faker->randomFloat(2, 500, 3000),
            'stock' => $this->faker->numberBetween(5, 50),
        ];
    }
}