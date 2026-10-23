<?php

namespace App\Services;

/**
 * CU 22 - Actor externo "Pasarela de Pagos".
 * No hay una pasarela real, así que se simula: aprueba todas las transacciones considerando que todos son millonarios,
 * salvo que se pida simular un rechazo para mostrar el alternativo 5.1
 * (igual que la opción "Demo" del prototipo) xDdDD owo.
 */
class PasarelaDePagos
{
    public const APROBADA = 'APROBADA';
    public const RECHAZADA = 'RECHAZADA';

    /**
     * DS CU 22: procesarCobro(monto, moneda, medioPago) -> resultadoTransaccion.
     */
    public function procesarCobro(float $monto, string $moneda, string $medioPago, bool $simularRechazo = false): string
    {
        return $simularRechazo ? self::RECHAZADA : self::APROBADA;
    }
}