<?php

namespace App\Notifications;

use App\Models\Reserva;
use App\Models\SalidaProgramada;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * RF Notificar eventualidades: base de los correos al cliente responsable de una reserva.
 * Se envían por la cola, así el administrativo o el cliente no esperan al servidor de correo.
 */
abstract class NotificacionAlCliente extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Reserva $reserva)
    {
    }

    /** El RF pide la notificación por correo electrónico. */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    abstract public function toMail(object $notifiable): MailMessage;

    /** Encabezado común: asunto, saludo al cliente y firma. */
    protected function mensaje(string $asunto): MailMessage
    {
        return (new MailMessage)
            ->subject('Trekk - ' . $asunto)
            ->greeting('Hola, ' . $this->reserva->cliente->nombre . ':')
            ->salutation("Saludos,\nEl equipo de Trekk");
    }

    /** "Excursión del 10/11/2026 al 12/11/2026, con salida a las 08:30 hs". */
    protected function descripcionSalida(SalidaProgramada $salida): string
    {
        return '“' . $salida->excursion->nombre . '” del ' . $salida->fecha_inicio->format('d/m/Y')
            . ' al ' . $salida->fecha_fin->format('d/m/Y')
            . ', con salida a las ' . $salida->hora_de_salida->format('H:i') . ' hs';
    }

    protected function salida(): SalidaProgramada
    {
        return $this->reserva->getSalidaProgramada();
    }
}
