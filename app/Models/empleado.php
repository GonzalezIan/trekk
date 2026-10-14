<?php

namespace App\Models;

use App\Abstracts\Persona;

class Empleado extends Persona
{
    protected $table = 'empleados';

    protected $fillable = [
        'dni',
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'categoria',
        'estado',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'categoria' => 'string',
        'estado' => 'string',
    ];
}
