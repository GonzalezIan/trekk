<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Excursion extends Model
{
    use HasFactory;
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
        'experiencia_requerida' => 'string',
        'acepta_menores' => 'boolean',
        'requiere_certificado_medico' => 'boolean',
        'estado' => 'string',
    ];
    // ========== RELACIONES ==========
    
    public function salidasProgramadas():HasMany{
        return $this->hasMany(SalidaProgramada::class);
    }
}
