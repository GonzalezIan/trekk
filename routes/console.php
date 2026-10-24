<?php

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
