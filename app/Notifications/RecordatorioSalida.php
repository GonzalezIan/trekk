<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * RF Notificar eventualidades - Recordatorio de salida:
 * fecha, punto de encuentro y equipamiento obligatorio pendiente, si lo hubiera.
 */
class RecordatorioSalida extends NotificacionAlCliente
{
    public function toMail(object $notifiable): MailMessage
    {
        $salida = $this->salida();
        $excursion = $salida->excursion;

        $mensaje = $this->mensaje('Recordatorio de tu salida: ' . $excursion->nombre)
            ->line('Te recordamos que se acerca tu salida: ' . $this->descripcionSalida($salida) . '.')
            ->line('Punto de encuentro: ' . ($excursion->punto_de_encuentro
                ?: 'la empresa te lo va a confirmar antes de la salida') . '.');

        if ($excursion->equipamiento_obligatorio) {
            $mensaje->line('Equipamiento obligatorio que tenés que llevar: ' . $excursion->equipamiento_obligatorio);
        }

        return $mensaje;
    }
}
