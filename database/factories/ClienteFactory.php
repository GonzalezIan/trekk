<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = \App\Models\Cliente::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(), 
            'dni' => $this->faker->unique()->numerify('########'),
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'fecha_nacimiento' => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'telefono' => $this->faker->numerify('##########'),
        ];
    }
}