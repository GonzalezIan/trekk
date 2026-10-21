<?php

use App\Http\Controllers\ControladorSalidas;
use App\Http\Controllers\ControladorExcursiones;
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
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ---------- Excursiones (CU 1 y CU 4) ----------
Route::middleware('auth')->group(function () {
    // CU 4 - Listar excursiones (Cliente y Administrativo)
    Route::get('/excursiones', [ControladorExcursiones::class, 'listarExcursiones'])
        ->name('excursiones.listar');

    // Solo Administrativo
    Route::middleware('administrativo')->group(function () {
        // CU 1 - Alta de excursión
        Route::get('/excursiones/alta', [ControladorExcursiones::class, 'mostrarFormularioAlta'])
            ->name('excursiones.alta');
        Route::post('/excursiones', [ControladorExcursiones::class, 'registrarExcursion'])
            ->name('excursiones.registrar');
    });
});

// ---------- Salidas Programadas (CU 9) ----------
Route::middleware('auth')->group(function () {
    // CU 9 - Listar Salidas Programadas (Cliente y Administrativo)
    Route::get('/salidas', [ControladorSalidas::class, 'listarSalidas'])
        ->name('salidas.listar');
});

require __DIR__.'/auth.php';
