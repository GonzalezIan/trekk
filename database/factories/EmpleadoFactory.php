<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EmpleadoFactory extends Factory
{
    protected $model = \App\Models\Empleado::class;

    public function definition(): array
    {
        return [
            'dni' => $this->faker->unique()->numerify('########'),
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'fecha_nacimiento' => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'categoria' => $this->faker->randomElement(['GUIA', 'ASISTENTE']),
            'estado' => $this->faker->randomElement(['ACTIVO', 'INACTIVO']),
        ];
    }
}