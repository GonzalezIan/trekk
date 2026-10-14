<?php

namespace App\Models;

use App\Abstracts\Persona;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participante extends Persona
{
    protected $table = 'participantes';

    protected $fillable = [
        'dni',
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'certificado_medico_id',
        'estado_requisito_medico',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'estado_requisito_medico' => 'string',
    ];
}
