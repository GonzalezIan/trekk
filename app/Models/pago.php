<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'reserva_id',
        'monto',
        'moneda',
        'medio_pago',
        'fecha_hora_pago',
        'tipo_pago',
        'resultado_transaccion',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'moneda' => 'string',
        'fecha_hora_pago' => 'datetime',
        'tipo_pago' => 'string',
        'resultado_transaccion' => 'string',
    ];
}
