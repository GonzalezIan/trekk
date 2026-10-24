<?php

use App\Http\Controllers\ControladorReservas;
use App\Http\Controllers\ControladorSalidas;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ---------- Procesos automáticos del sistema ----------
// En desarrollo corren con "php artisan schedule:work" (incluido en "composer dev").
// En producción hace falta un cron que ejecute "php artisan schedule:run" cada minuto.

// RF-17 Cancelar reserva automáticamente
Artisan::command('reservas:cancelar-vencidas', function () {
    $canceladas = app()->call([app(ControladorReservas::class), 'cancelarReservasAutomaticamente']);
    $this->info("Reservas canceladas automáticamente: {$canceladas}.");
})->purpose('Cancela las reservas en curso vencidas, las de seña sin saldo a las 24 hs y las de requisito médico pendiente al límite de verificación');

// Cada minuto: las reservas "En curso" vencen a los 20 minutos
Schedule::command('reservas:cancelar-vencidas')->everyMinute()->withoutOverlapping();

// RF Finalizar Salida Programada
Artisan::command('salidas:finalizar', function () {
    $finalizadas = app()->call([app(ControladorSalidas::class), 'finalizarSalidaProgramada']);
    $this->info("Salidas Programadas finalizadas: {$finalizadas}.");
})->purpose('Finaliza las Salidas Programadas cuya fecha de fin ya transcurrió');

// RF Notificar eventualidades - Recordatorio de salida
Artisan::command('salidas:recordatorios', function () {
    $avisadas = app()->call([app(ControladorSalidas::class), 'enviarRecordatorios']);
    $this->info("Recordatorios de salida enviados: {$avisadas}.");
})->purpose('Envía el recordatorio a las reservas de las salidas que empiezan dentro de N días');

Schedule::command('salidas:finalizar')->dailyAt('00:05');
// Una vez por día: cada salida queda a N días de su inicio un solo día, así el recordatorio no se repite
Schedule::command('salidas:recordatorios')->dailyAt('09:00');
