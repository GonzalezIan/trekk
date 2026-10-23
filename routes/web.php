<?php

use App\Http\Controllers\ControladorPaquetes;
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

    // CU 16 - Cancelar reserva (Cliente: propias; Administrativo: cualquiera)
    Route::get('/reservas/{reserva}/cancelar', [ControladorReservas::class, 'cancelarReserva'])
        ->name('reservas.cancelar');
    Route::post('/reservas/{reserva}/cancelar', [ControladorReservas::class, 'ingresarMotivoCancelacion'])
        ->name('reservas.cancelar.confirmar');

    // CU 22 - Registrar pago de reserva (Cliente: reservas propias)
    Route::get('/reservas/{reserva}/pago', [ControladorReservas::class, 'registrarPagoReserva'])
        ->name('reservas.pago');
    Route::post('/reservas/{reserva}/pago', [ControladorReservas::class, 'confirmarPago'])
        ->name('reservas.pago.confirmar');
    
    // CU 21 - Listar participantes de una reserva propia (Cliente)
    Route::get('/reservas/{reserva}/participantes', [ControladorReservas::class, 'listarParticipantesDeReserva'])
        ->name('reservas.participantes');

    // CU 17 - Cargar certificado médico de un participante (Cliente)
    Route::get('/reservas/{reserva}/participantes/{dniParticipante}/certificado', [ControladorReservas::class, 'cargarCertificadoMedico'])
        ->name('reservas.certificado.cargar');
    Route::post('/reservas/{reserva}/participantes/{dniParticipante}/certificado', [ControladorReservas::class, 'registrarCertificado'])
        ->name('reservas.certificado.registrar');

    // CU 19 - Verificar certificado médico de un participante (solo Administrativo)
    Route::middleware('administrativo')
        ->prefix('/reservas/{reserva}/participantes/{dniParticipante}/certificado')
        ->group(function () {
            Route::get('/verificar', [ControladorReservas::class, 'verificarCertificadoMedico'])
                ->name('reservas.certificado.verificar');
            Route::get('/archivo', [ControladorReservas::class, 'mostrarArchivoCertificado'])
                ->name('reservas.certificado.archivo');
            Route::post('/fecha-emision', [ControladorReservas::class, 'ingresarFechaEmision'])
                ->name('reservas.certificado.fecha-emision');
            Route::post('/invalidez', [ControladorReservas::class, 'ingresarMotivoInvalidez'])
                ->name('reservas.certificado.invalidez');
        });

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
        
        // CU 3 - Modificar excursión
        Route::get('/excursiones/{excursion}/modificar', [ControladorExcursiones::class, 'mostrarFormularioModificacion'])
            ->whereNumber('excursion')
            ->name('excursiones.modificar');
        Route::put('/excursiones/{excursion}', [ControladorExcursiones::class, 'modificarExcursion'])
            ->whereNumber('excursion')
            ->name('excursiones.actualizar');

        // CU 2 - Baja de excursión
        Route::patch('/excursiones/{excursion}/baja', [ControladorExcursiones::class, 'darDeBajaExcursion'])
            ->whereNumber('excursion')
            ->name('excursiones.baja');
    });
});

// ---------- Salidas Programadas (CU 8 y CU 9) ----------
Route::middleware('auth')->group(function () {
    // CU 9 - Listar Salidas Programadas (Cliente y Administrativo)
    Route::get('/salidas', [ControladorSalidas::class, 'listarSalidas'])
        ->name('salidas.listar');

    // CU 8 - Registrar Salida Programada (solo Administrativo)
    Route::middleware('administrativo')->group(function () {
        Route::get('/excursiones/{excursion}/salidas/registrar', [ControladorSalidas::class, 'mostrarFormularioSalida'])
            ->whereNumber('excursion')
            ->name('salidas.registrar');
        Route::post('/excursiones/{excursion}/salidas/datos', [ControladorSalidas::class, 'ingresarDatosSalida'])
            ->whereNumber('excursion')
            ->name('salidas.registrar.datos');
        // CU 23 - Listar empleados disponibles en el período de la salida
        Route::get('/salidas/registrar/empleados', [ControladorSalidas::class, 'listarEmpleadosDisponibles'])
            ->name('salidas.registrar.empleados');
        // CU 25 - Listar equipamientos con stock en el período de la salida
        Route::get('/salidas/registrar/equipamiento', [ControladorSalidas::class, 'listarEquipamientoDisponible'])
            ->name('salidas.registrar.equipamiento');
        Route::post('/salidas', [ControladorSalidas::class, 'registrarSalida'])
            ->name('salidas.registrar.confirmar');
    });
});

// ---------- Paquetes (CU 5 y CU 7) ----------
Route::middleware('auth')->group(function () {
    // CU 7 - Listar paquetes de una Salida Programada (Cliente y Administrativo)
    Route::get('/salidas/{idSalida}/paquetes', [ControladorPaquetes::class, 'listarPaquetes'])
        ->whereNumber('idSalida')
        ->name('paquetes.listar');

    // CU 5 - Registrar paquete (solo Administrativo)
    Route::middleware('administrativo')->group(function () {
        Route::get('/salidas/{idSalida}/paquetes/registrar', [ControladorPaquetes::class, 'solicitarRegistroPaquete'])
            ->whereNumber('idSalida')
            ->name('paquetes.registrar');
        // CU 24 - Listar servicios
        Route::get('/paquetes/registrar/servicios', [ControladorPaquetes::class, 'listarServicios'])
            ->name('paquetes.registrar.servicios');
        // CU 26 - Listar refugios admisibles para el período de la salida
        Route::get('/salidas/{idSalida}/paquetes/registrar/refugios', [ControladorPaquetes::class, 'listarRefugiosAdmisibles'])
            ->whereNumber('idSalida')
            ->name('paquetes.registrar.refugios');
        Route::post('/salidas/{idSalida}/paquetes', [ControladorPaquetes::class, 'registrarPaquete'])
            ->whereNumber('idSalida')
            ->name('paquetes.registrar.confirmar');
    });
});

// CU 25 - Listar equipamientos (cliente y Administrativo)
Route::get('/equipamientos', [ControladorReservas::class, 'listarEquipamientos'])
    ->middleware('auth')
    ->name('equipamientos.listar');

require __DIR__.'/auth.php';
