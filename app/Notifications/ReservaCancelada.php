<?php

namespace App\Notifications;

use App\Models\Reserva;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * RF Notificar eventualidades - Cancelación de la Salida Programada, del paquete o de la reserva:
 * motivo de la cancelación y, cuando corresponde un reembolso, que se gestiona ante la empresa.
 */
class ReservaCancelada extends NotificacionAlCliente
{
    // Qué originó la cancelación de la reserva
    public const ORIGEN_RESERVA = 'RESERVA';   // CU 16
    public const ORIGEN_PAQUETE = 'PAQUETE';   // CU 6
    public const ORIGEN_SALIDA = 'SALIDA';     // CU 11, en cascada

    public function __construct(Reserva $reserva, public string $origen)
    {
        parent::__construct($reserva);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $id = $this->reserva->id;
        $salida = $this->salida();

        $mensaje = $this->mensaje("Tu reserva #{$id} fue cancelada")
            ->line(match ($this->origen) {
                self::ORIGEN_SALIDA => 'La empresa canceló la salida ' . $this->descripcionSalida($salida)
                    . ", por lo que tu reserva #{$id} quedó cancelada.",
                self::ORIGEN_PAQUETE => 'La empresa canceló el paquete “' . $this->reserva->paquete->nombre
                    . "”, por lo que tu reserva #{$id} quedó cancelada.",
                default => "Tu reserva #{$id} para la salida " . $this->descripcionSalida($salida) . ' fue cancelada.',
            })
            ->line('Motivo: ' . $this->reserva->motivo_cancelacion);

        if ($this->reserva->pagos()->exists()) {
            $mensaje->line($this->origen === self::ORIGEN_RESERVA
                // CU 16 - RN-01
                ? 'No corresponde el reintegro de los importes abonados.'
                // CU 11 - RN-04: reembolso del 100%, que el sistema no procesa
                : 'Te corresponde el reembolso del 100% de lo abonado: gestionalo directamente ante la empresa.');
        }

        return $mensaje;
    }
}
