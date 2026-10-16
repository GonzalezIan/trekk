<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Excursion extends Model
{
    protected $table = 'excursiones';

    protected $fillable = [
        'nombre',
        'descripcion',
        'dificultad',
        'duracion',
        'recorrido',
        'etapas',
        'experiencia_requerida',
        'acepta_menores',
        'equipamiento_obligatorio',
        'requiere_certificado_medico',
        'estado',
    ];

    protected $casts = [
        'dificultad' => 'string',
        'duracion' => 'string',
        'experiencia_requerida' => 'boolean',
        'acepta_menores' => 'boolean',
        'requiere_certificado_medico' => 'boolean',
        'estado' => 'string',
    ];
}
