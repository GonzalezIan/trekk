<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class reservas extends Model
{
    protected $table = 'reservas';

    protected $fillable = [
        'cliente_id',
        'fecha_registro',
        'fecha_hora_vencimiento',
        'estado',
        'situacion_pago',
        'importe_total',
        'motivo_cancelacion',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
        'fecha_hora_vencimiento' => 'datetime',
        'importe_total' => 'decimal:2',
        'estado' => 'string',
        'situacion_pago' => 'string',
    ];
}
