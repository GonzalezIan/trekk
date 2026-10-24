<?php

namespace App\Services;

use App\Models\CertificadoMedico;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use App\Notifications\CertificadoInvalido;
use App\Notifications\NotificacionAlCliente;
use App\Notifications\RecordatorioSalida;
use App\Notifications\ReservaCancelada;
use App\Notifications\ReservaConfirmada;
use App\Notifications\SalidaModificada;

/**
 * RF Notificar eventualidades: avisa por correo al cliente responsable de la reserva.
 * Los correos salen recién cuando se confirma la transacción en curso, así no se avisa
 * de un cambio que después se deshizo.
 */
class Notificador
{
    /** Confirmación de reserva (CU 19 y CU 22; más adelante, CU 14). */
    public function notificarConfirmacion(Reserva $reserva): void
    {
        $this->enviar(new ReservaConfirmada($reserva));
    }

    /** Recordatorio de salida, a cada reserva activa. Devuelve cuántas se avisaron. */
    public function notificarRecordatorio(SalidaProgramada $salida): int
    {
        $reservas = $salida->reservasActivas();
        $reservas->each(fn (Reserva $reserva) => $this->enviar(new RecordatorioSalida($reserva)));

        return $reservas->count();
    }

    /**
     * Modificación o reprogramación de la salida (CU 10, RN-08), a cada reserva activa.
     * Devuelve a cuántos clientes se avisó (0 si no cambió ningún dato que se notifique).
     */
    public function notificarModificacion(SalidaProgramada $salida, array $cambios): int
    {
        if (! $cambios) {
            return 0;
        }

        $reservas = $salida->reservasActivas();
        $reservas->each(fn (Reserva $reserva) => $this->enviar(new SalidaModificada($reserva, $cambios)));

        return $reservas->pluck('cliente_id')->unique()->count();
    }

    /** Cancelación de la reserva, por sí misma o por su paquete o su salida (ReservaCancelada::ORIGEN_*). */
    public function notificarCancelacion(Reserva $reserva, string $origen): void
    {
        $this->enviar(new ReservaCancelada($reserva, $origen));
    }

    /** Certificado médico inválido (CU 19, 3.1 y 7.1). */
    public function notificarCertificadoInvalido(CertificadoMedico $certificado): void
    {
        $this->enviar(new CertificadoInvalido($certificado));
    }

    private function enviar(NotificacionAlCliente $notificacion): void
    {
        $notificacion->reserva->cliente->user->notify($notificacion->afterCommit());
    }
}
