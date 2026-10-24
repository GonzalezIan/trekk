<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Cliente fijo de prueba (Juan)
        $user = User::create([
            'name' => 'Juan Pérez',
            'usuario' => 'juan',
            'email' => 'juan@test.com',
            'password' => Hash::make('password123'),
            'rol' => 'cliente',
        ]);
        
        Cliente::create([
            'user_id' => $user->id,
            'dni' => '11223344',
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'fecha_nacimiento' => '1990-05-15',
            'telefono' => '1122334455',
        ]);

        // 2. 30 clientes aleatorios (el Factory ya se encarga de crearles su User)
        Cliente::factory()->count(30)->create();
    }
}