<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use illuminate\Database\Eloquent\Relations\HasOne;

class Participante extends Model
{
    protected $table = 'participantes';

    protected $fillable = [
        'reserva_id',
        'dni',
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'estado_requisito_medico',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'estado_requisito_medico' => 'string',
    ];

    // =========== RELACIONES ==========

    public function reserva():BelongsTo{
        return $this->belongsTo(Reserva::class);
    }

    public function certificadoMedico():HasOne{
        return $this->hasOne(CertificadoMedico::class,'participante_id');
    }
}
