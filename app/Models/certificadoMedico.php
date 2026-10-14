<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Model;

class certificadoMedico extends Model
{
    protected $table = 'certificados_medicos';

    protected $fillable = [
    'fecha_emision',
    'estado',
    'archivo',
    'fecha_carga',
    'fecha_vencimiento',
    'descripcion',
    'motivo_invalidez'];

    protected $casts = [
    'fecha_emision'=>'date',
    'estado'=>'string',
    'fecha_carga'=>'date',
    'fecha_vencimiento'=>'date'
    ];

    
}
