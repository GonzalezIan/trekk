<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    // =========== RELACIONES ==========

    public function reserva():BelongsTo{
        return $this->belongsTo(Reserva::class);
    }

     // ========== CU 22 - REGISTRAR PAGO DE RESERVA ==========

    public const TIPO_SENA = 'SEÑA';
    public const TIPO_TOTAL = 'TOTAL';
    public const TIPO_SALDO_RESTANTE = 'SALDO_RESTANTE';

    public const MONEDAS = ['USD', 'ARS'];
    public const MEDIOS_PAGO = ['Tarjeta de crédito', 'Tarjeta de débito', 'Mercado Pago'];

    public const RESULTADO_APROBADA = 'APROBADA';
}
