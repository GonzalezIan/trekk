<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ControladorReservas;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// ---------- Reservas ----------
Route::middleware('auth')->group(function () {
    // CU 15 - Listar reservas (Cliente y Administrativo)
    Route::get('/reservas', [ControladorReservas::class, 'listarReservas'])
        ->name('reservas.listar');

    // CU 21 - Listar participantes de una reserva propia (Cliente)
    Route::get('/reservas/{reserva}/participantes', [ControladorReservas::class, 'listarParticipantesDeReserva'])
        ->name('reservas.participantes');

    // CU 21 - Listar participantes de una Salida Programada (solo Administrativo)
    Route::get('/salidas/{salida}/participantes', [ControladorReservas::class, 'listarParticipantesDeSalida'])
        ->middleware('administrativo')
        ->name('salidas.participantes');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
