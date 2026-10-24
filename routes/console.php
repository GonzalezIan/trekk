<?php

use App\Http\Controllers\ControladorReservas;
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
