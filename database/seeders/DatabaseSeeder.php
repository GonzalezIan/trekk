<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Datos base (No dependen de nadie)
            UserSeeder::class,              // Crea al Admin y a Juan
            RefugioSeeder::class,           // 30 refugios
            ServicioSeeder::class,          // 30 servicios
            ExcursionSeeder::class,         // 30 excursiones
            EquipamientoSeeder::class,      // 30 equipamientos
            EmpleadoSeeder::class,          // 31 empleados (1 fijo + 30 random)

            // 2. Primer nivel de dependencias
            ClienteSeeder::class,           // 31 clientes (1 fijo + 30 random, depende de User)
            ConvenioSeeder::class,          // 30 convenios (dependen de Refugio)
            SalidaProgramadaSeeder::class,  // 30 salidas (dependen de Excursión y Empleado)

            // 3. El Nudo (Dependen de los anteriores)
            PaqueteSeeder::class,           // 30 paquetes (dependen de Salida y Refugio)
            ReservaSeeder::class,           // 30 reservas (dependen de Cliente y Paquete)

            // 4. Dependientes de la Reserva
            ParticipanteSeeder::class,      // Participantes para cada reserva
            PagoSeeder::class,              // Pagos para cada reserva
            ConformidadSeeder::class,       // Reseñas para algunas reservas
        ]);
    }
}