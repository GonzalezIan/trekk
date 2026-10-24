<?php

namespace App\Notifications;

use App\Models\Reserva;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * RF Notificar eventualidades - Modificación o reprogramación de la Salida Programada (CU 10, RN-08):
 * datos modificados (fecha, hora de salida, guía, asistentes o equipamiento incluido).
 */
class SalidaModificada extends NotificacionAlCliente
{
    /** @param array<int, array{dato: string, anterior: string, nuevo: string}> $cambios */
    public function __construct(Reserva $reserva, public array $cambios)
    {
        parent::__construct($reserva);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mensaje = $this->mensaje('Cambios en tu salida: ' . $this->salida()->excursion->nombre)
            ->line('La salida de tu reserva #' . $this->reserva->id . ' tuvo los siguientes cambios:');

        foreach ($this->cambios as $cambio) {
            $mensaje->line("- {$cambio['dato']}: {$cambio['anterior']} → {$cambio['nuevo']}");
        }

        return $mensaje->line('Así queda tu salida: ' . $this->descripcionSalida($this->salida()) . '.');
    }
}
