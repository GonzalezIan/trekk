<?php

namespace App\Models;

use App\Abstracts\Persona;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Cliente extends Persona
{
    protected $table = 'clientes';

    protected $fillable = [
        'dni',
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'telefono',
        'email',
        'usuario',
        'contrasenia',
    ];

    protected $hidden = ['contrasenia'];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];
}
