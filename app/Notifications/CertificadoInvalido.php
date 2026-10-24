<?php

namespace App\Notifications;

use App\Models\CertificadoMedico;
use Illuminate\Notifications\Messages\MailMessage;

/** RF Notificar eventualidades - Certificado médico inválido: motivo de invalidez (CU 19, 3.1 y 7.1). */
class CertificadoInvalido extends NotificacionAlCliente
{
    public function __construct(public CertificadoMedico $certificado)
    {
        parent::__construct($certificado->participante->reserva);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $participante = $this->certificado->participante;

        return $this->mensaje('Certificado médico inválido - reserva #' . $this->reserva->id)
            ->line('El certificado médico de ' . $participante->nombre . ' ' . $participante->apellido
                . ' (DNI ' . $participante->dni . ') en tu reserva #' . $this->reserva->id . ' es inválido.')
            ->line('Motivo: ' . $this->certificado->motivo_de_invalidez);
    }
}
