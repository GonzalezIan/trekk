<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Participante;
use App\Models\Reserva;

class ParticipanteSeeder extends Seeder
{
    public function run(): void
    {
        $reservas = Reserva::all();

        foreach ($reservas as $reserva) {
            // A cada reserva le agregamos entre 1 y 3 participantes
            $cantidad = rand(1, 3);
            
            for ($i = 0; $i < $cantidad; $i++) {
                Participante::create([
                    'reserva_id' => $reserva->id,
                    'dni' => fake()->unique()->numerify('########'),
                    'nombre' => fake()->firstName(),
                    'apellido' => fake()->lastName(),
                    'fecha_nacimiento' => fake()->dateTimeBetween('-70 years', '-10 years')->format('Y-m-d'),
                    'estado_requisito_medico' => fake()->randomElement(['NO_REQUERIDO','PENDIENTE', 'PENDIENTE_DE_VERIFICACION', 'VALIDO','INVALIDO']),
                ]);
            }
        }
    }
}