<?php

namespace App\Notifications;

use App\Models\Pago;
use Illuminate\Notifications\Messages\MailMessage;

/** RF Notificar eventualidades - Confirmación de reserva: paquete, fecha y monto abonado. */
class ReservaConfirmada extends NotificacionAlCliente
{
    public function toMail(object $notifiable): MailMessage
    {
        return $this->mensaje('Tu reserva #' . $this->reserva->id . ' está confirmada')
            ->line('Tu reserva #' . $this->reserva->id . ' quedó confirmada.')
            ->line('Paquete: ' . $this->reserva->paquete->nombre . '.')
            ->line('Salida: ' . $this->descripcionSalida($this->salida()) . '.')
            ->line('Monto abonado: ' . $this->montoAbonado() . '.');
    }

    /** Lo pagado por moneda, porque cada pago pudo hacerse en USD o en ARS (CU 22, 3.1). */
    private function montoAbonado(): string
    {
        return $this->reserva->pagos()
            ->where('resultado_transaccion', Pago::RESULTADO_APROBADA)
            ->get()
            ->groupBy('moneda')
            ->map(fn ($pagos, $moneda) => $moneda . ' ' . number_format($pagos->sum('monto'), 2, ',', '.'))
            ->join(' + ');
    }
}
