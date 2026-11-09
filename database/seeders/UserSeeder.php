<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Creamos el Usuario Administrador
        User::create([
            'name' => 'Admin Trekk',
            'usuario' => 'admin',
            'email' => 'admin@trekk.com',
            'password' => Hash::make('admin123'), // Contraseña: admin123
            'rol' => 'admin', // Columna agregada en add_rol_to_users_table
        ]);
    }
}
