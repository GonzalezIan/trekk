<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = [
        'cliente_id',
        'fecha_hora_registro',
        'fecha_hora_vencimiento',
        'estado',
        'situacion_de_pago',
        'importe_total',
        'motivo_cancelacion',
    ];

    protected $casts = [
        'fecha_hora_registro' => 'date',
        'fecha_hora_vencimiento' => 'datetime',
        'importe_total' => 'decimal:2',
        'estado' => 'string',
        'situacion_de_pago' => 'string',
    ];
}
