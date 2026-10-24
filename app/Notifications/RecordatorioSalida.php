<?php

namespace App\Notifications;

use App\Models\Equipamiento;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * RF Notificar eventualidades - Recordatorio de salida:
 * fecha, punto de encuentro y equipamiento obligatorio pendiente, si lo hubiera.
 * El equipamiento obligatorio es un texto libre de la excursión (CU 1), así que no se puede descontar
 * elemento por elemento: se informa junto con lo que ya está cubierto (el incluido de la salida y
 * el alquilado en la reserva) y lo pendiente es lo que quede sin cubrir.
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

        if (! $excursion->equipamiento_obligatorio) {
            return $mensaje;
        }

        $mensaje->line('Equipamiento obligatorio para la excursión: ' . $excursion->equipamiento_obligatorio);

        $incluido = $salida->equipoPrestado->pluck('nombre')->sort()->join(', ');
        if ($incluido) {
            $mensaje->line('La empresa provee sin costo: ' . $incluido . '.');
        }

        $alquilado = $this->reserva->equipos_alquilados
            ->sortBy('nombre')
            ->map(fn (Equipamiento $equipo) => $equipo->nombre . ' x' . $equipo->pivot->cantidad)
            ->join(', ');
        if ($alquilado) {
            $mensaje->line('Alquilaste en tu reserva: ' . $alquilado . '.');
        }

        return $mensaje->line(($incluido || $alquilado)
            ? 'El equipamiento obligatorio que no esté cubierto por lo anterior tenés que llevarlo vos.'
            : 'Tenés que llevarlo vos, porque tu reserva no incluye equipamiento de alquiler.');
    }
}
