<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificadoMedico extends Model
{
    protected $table = 'certificados_medicos';

    protected $fillable = [
    'participante_id',
    'fecha_emision',
    'estado',
    'archivo',
    'fecha_de_carga',
    'fecha_de_vencimiento',
    'descripcion',
    'motivo_de_invalidez'];

    protected $casts = [
    'fecha_emision'=>'date',
    'estado'=>'string',
    'fecha_de_carga'=>'dateTime',
    'fecha_de_vencimiento'=>'date'
    ];

    // =========== RELACIONES ==========
    public function participante():BelongsTo{
        return $this->belongsTo(Participante::class);
    }
}
