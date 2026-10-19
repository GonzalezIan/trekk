<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Conformidad;
use App\Models\Reserva;

class ConformidadSeeder extends Seeder
{
    public function run(): void
    {
        // Agarramos solo 15 reservas para dejar algunas sin reseñas
        $reservas = Reserva::take(15)->get(); 
        
        foreach ($reservas as $reserva) {
            Conformidad::create([
                'reserva_id' => $reserva->id,
                'valoracion' => fake()->numberBetween(1, 5),
                'comentario' => fake()->sentence(),
            ]);
        }
    }
}
