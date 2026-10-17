<?php

namespace App\Models;

use App\Abstracts\Persona;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    // ========== RELACIONES ==========

    public function SalidasProgramadas(): BelongsToMany{
        return $this->belongsToMany(SalidaProgramada::class,'empleados_asignados')
        ->withPivot('rol');
    }
}
