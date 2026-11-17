@echo off
rem Levanta Trekk en desarrollo, cada proceso en su ventana.
rem No se usa "composer dev" porque incluye "php artisan pail", que en Windows no anda (necesita pcntl).
start "Trekk - servidor" cmd /k "php artisan serve"
start "Trekk - vite" cmd /k "npm run dev"
rem Cola: sin ella no sale ningun correo (las notificaciones se encolan)
start "Trekk - cola" cmd /k "php artisan queue:listen --tries=1 --timeout=0"
rem Scheduler: cancela reservas vencidas (RF-17), finaliza salidas y manda recordatorios
start "Trekk - scheduler" cmd /k "php artisan schedule:work"
