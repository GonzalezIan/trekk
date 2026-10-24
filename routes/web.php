<?php

use App\Http\Controllers\ControladorPaquetes;
use App\Http\Controllers\ControladorSalidas;
use App\Http\Controllers\ControladorExcursiones;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ControladorReservas;
use App\Http\Controllers\ControladorReportes;
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

    // CU 14 - Registrar reserva (Cliente). Se invoca desde el CU 7, al elegir un paquete.
    Route::get('/paquetes/{paquete}/reservar', [ControladorReservas::class, 'registrarReserva'])
        ->whereNumber('paquete')
        ->name('reservas.registrar');
    Route::post('/paquetes/{paquete}/reservar', [ControladorReservas::class, 'ingresarCantidadParticipantes'])
        ->whereNumber('paquete')
        ->name('reservas.registrar.cantidad');
    // Si la reserva en curso ya no existe, se descartó al vencer (*a, RF-17) o al cancelarse (17.1)
    Route::prefix('/reservas/{reserva}/registro')->missing(
        fn () => abort(410, ControladorReservas::MENSAJE_RESERVA_VENCIDA)
    )->group(function () {
        Route::post('/participantes', [ControladorReservas::class, 'ingresarDatosParticipantes'])
            ->name('reservas.registrar.participantes');
        // 12.1 - CU 25 Listar equipamientos, para el período de la salida
        Route::get('/equipamiento', [ControladorReservas::class, 'listarEquipamientosParaReserva'])
            ->name('reservas.registrar.equipamiento');
        Route::post('/equipamiento', [ControladorReservas::class, 'agregarEquipamiento'])
            ->name('reservas.registrar.equipamiento.agregar');
        Route::post('/continuar', [ControladorReservas::class, 'continuar'])
            ->name('reservas.registrar.continuar');
        Route::post('/confirmar', [ControladorReservas::class, 'confirmarReserva'])
            ->name('reservas.registrar.confirmar');
        // 17.1 - El cliente cancela antes de efectuar el pago
        Route::post('/cancelar', [ControladorReservas::class, 'cancelarRegistroReserva'])
            ->name('reservas.registrar.cancelar');
    });

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
    // CU 18 listar certificados medicos pendientes
    Route::get('/certificados/pendientes', [ControladorReservas::class, 'listarCertificadosPendientes'])
        ->name('certificados.pendientes');

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
    Route::get('/reservas/{reserva}/conformidad', [ControladorReservas::class, 'solicitarConformidad'])->name('reservas.conformidad');
    Route::post('/reservas/{reserva}/conformidad', [ControladorReservas::class, 'guardarConformidad'])->name('reservas.conformidad.guardar');
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

        // CU 27 - Generar reporte periódico
        Route::get('/admin/reportes', [ControladorReportes::class, 'mostrarPantallaReporte'])
            ->name('admin.reportes.index');
        Route::get('/admin/reportes/periodico', [ControladorReportes::class, 'generarReportePeriodico'])
            ->name('admin.reportes.generar');
        Route::get('/admin/reportes/periodico/exportar', [ControladorReportes::class, 'exportarReporte'])
            ->name('admin.reportes.exportar');
    });
});

// ---------- Salidas Programadas (CU 8, CU 9 y CU 10) ----------
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

        // CU 10 - Modificar Salida Programada
        Route::get('/salidas/{salida}/modificar', [ControladorSalidas::class, 'mostrarFormularioModificacion'])
            ->whereNumber('salida')
            ->name('salidas.modificar');
        Route::get('/salidas/{salida}/modificar/empleados', [ControladorSalidas::class, 'listarEmpleadosParaModificar'])
            ->whereNumber('salida')
            ->name('salidas.modificar.empleados');
        Route::get('/salidas/{salida}/modificar/equipamiento', [ControladorSalidas::class, 'listarEquipamientoParaModificar'])
            ->whereNumber('salida')
            ->name('salidas.modificar.equipamiento');
        Route::put('/salidas/{salida}', [ControladorSalidas::class, 'modificarSalida'])
            ->whereNumber('salida')
            ->name('salidas.actualizar');

        // CU 11 - Cancelar Salida Programada (solo Administrativo)
        Route::post('/salidas/{salida}/cancelar', [ControladorSalidas::class, 'cancelar'])
            ->whereNumber('salida')
            ->name('salidas.cancelar');
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
            // CU - Cancelar Paquete (solo Administrativo)
        
    Route::middleware('administrativo')->group(function () {
        Route::get('/paquetes/{idPaquete}/cancelar', [ControladorPaquetes::class, 'solicitarCancelacion'])
            ->name('paquetes.cancelar');
        Route::post('/paquetes/{idPaquete}/cancelar', [ControladorPaquetes::class, 'confirmarCancelacion'])
            ->name('paquetes.cancelar.confirmar');
    });
    });
});

// CU 25 - Listar equipamientos (cliente y Administrativo)
Route::get('/equipamientos', [ControladorReservas::class, 'listarEquipamientos'])
    ->middleware('auth')
    ->name('equipamientos.listar');

require __DIR__.'/auth.php';
