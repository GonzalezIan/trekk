<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Equipamiento;
use App\Models\Excursion;
use App\Models\SalidaProgramada;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ControladorSalidas extends Controller
{
    /**
     * CU 9 - Listar Salidas Programadas.
     * Administrativo -> DS: listarSalidas(filtros).
     * Cliente        -> DS: listarSalidas(idExcursion).
     */
    public function listarSalidas(Request $request)
    {
        if ($request->user()->esAdministrativo()) {
            $filtros = $request->validate([
                'excursion' => ['nullable', 'integer'],
                'estado' => ['nullable', 'string'],
                'desde' => ['nullable', 'date'],
                'hasta' => ['nullable', 'date'],
            ]);

            return Inertia::render('Salidas/Listar', [
                'salidas' => SalidaProgramada::listar($filtros),
                'filtros' => $filtros,
                'excursiones' => Excursion::orderBy('nombre')->get(['id', 'nombre']),
                'estados' => SalidaProgramada::ESTADOS,
                'esAdministrativo' => true,
            ]);
        }

        // Cliente: la precondición es haber elegido una excursión en el CU 4
        if (! $request->filled('excursion')) {
            return redirect()->route('excursiones.listar');
        }

        $excursion = Excursion::buscar((int) $request->query('excursion'));

        return Inertia::render('Salidas/Listar', [
            'salidas' => $excursion->getSalidasReservables(),
            'excursion' => $excursion->only(['id', 'nombre']),
            'esAdministrativo' => false,
        ]);
    }

    // ========== CU 8 - REGISTRAR SALIDA PROGRAMADA ==========

    /** "exc y datosSalida se obtuvieron en ingresarDatosSalida": se guardan en la sesión entre pasos. */
    private const SESION_DATOS_SALIDA = 'cu8.datosSalida';

    private const ERROR_EXCURSION_NO_ACTIVA =
        'Solo pueden registrarse Salidas Programadas sobre excursiones en estado “Activa”.';

    /**
     * CU 8 - pasos 1 y 2: el administrativo solicita registrar una salida sobre la
     * excursión seleccionada y el sistema despliega el formulario.
     */
    public function mostrarFormularioSalida(Request $request, Excursion $excursion)
    {
        // RN-03
        if (! $excursion->estaActiva()) {
            return redirect()->route('excursiones.listar')->with('error', self::ERROR_EXCURSION_NO_ACTIVA);
        }

        $request->session()->forget(self::SESION_DATOS_SALIDA);

        return Inertia::render('Salidas/Registrar', [
            'excursion' => $excursion->only(['id', 'nombre', 'dificultad', 'duracion']),
        ]);
    }

    /**
     * DSS CU 8 - 1: ingresarDatosSalida(idExcursion, datosSalida): datosValidos.
     * Pasos 3 y 4. Si la validación falla se responde 422 con los errores
     * (alternativos 4.1, 4.2 y 4.3) y el formulario conserva lo ingresado.
     */
    public function ingresarDatosSalida(Request $request, Excursion $excursion)
    {
        abort_unless($excursion->estaActiva(), 422, self::ERROR_EXCURSION_NO_ACTIVA);

        $datosSalida = $excursion->validarDatosSalida($request->only([
            'fecha_inicio', 'fecha_fin', 'hora_de_salida', 'cupo_maximo', 'precio_base',
        ]));

        $request->session()->put(self::SESION_DATOS_SALIDA, [
            ...$datosSalida,
            'excursion_id' => $excursion->id,
        ]);

        return response()->json(['datosValidos' => true]);
    }

    /**
     * DSS CU 8 - 3 y 5: listarEmpleadosDisponibles(categoria, fechaInicio, fechaFin).
     * Paso 5 -> CU 23 - Listar empleados. El período es el de los datos ya validados.
     */
    public function listarEmpleadosDisponibles(Request $request)
    {
        $datosSalida = $this->datosSalidaEnCurso($request);
        abort_unless($datosSalida, 409, 'Primero ingresá los datos de la salida.');

        $categoria = $request->validate([
            'categoria' => ['required', Rule::in(Empleado::CATEGORIAS)],
        ])['categoria'];

        return response()->json([
            'empleados' => Empleado::listarDisponibles(
                $categoria, $datosSalida['fecha_inicio'], $datosSalida['fecha_fin']),
        ]);
    }

    /**
     * DSS CU 8 - 7: listarEquipamientoDisponible().
     * Alternativo 6.2 -> CU 25 - Listar equipamientos (perfil Administrativo).
     */
    public function listarEquipamientoDisponible(Request $request)
    {
        $datosSalida = $this->datosSalidaEnCurso($request);
        abort_unless($datosSalida, 409, 'Primero ingresá los datos de la salida.');

        return response()->json([
            'equipamientos' => Equipamiento::listarConStock(
                $datosSalida['fecha_inicio'], $datosSalida['fecha_fin']),
        ]);
    }

    /**
     * DSS CU 8 - 9: registrarSalida(idGuia, idsAsistentes, idsEquipamiento): idSalida, cupoMaximo.
     * Pasos 6 a 9. Antes de registrar se vuelve a verificar el personal y el equipamiento,
     * por si cambió su disponibilidad mientras se completaba el registro.
     */
    public function registrarSalida(Request $request)
    {
        $datosSalida = $this->datosSalidaEnCurso($request);
        if (! $datosSalida) {
            return redirect()->route('excursiones.listar')
                ->with('error', 'Se perdieron los datos de la salida. Volvé a iniciar el registro.');
        }

        $seleccion = $this->validarSeleccion($request);

        $excursion = Excursion::buscar($datosSalida['excursion_id']);
        if (! $excursion->estaActiva()) {
            return redirect()->route('excursiones.listar')->with('error', self::ERROR_EXCURSION_NO_ACTIVA);
        }

        $guia = Empleado::buscar((int) $seleccion['id_guia']);
        $asistentes = Empleado::buscar($seleccion['ids_asistentes'] ?? []);
        $equipamientos = Equipamiento::buscar($seleccion['ids_equipamiento'] ?? []);

        $this->verificarSeleccion($datosSalida, $guia, $asistentes, $equipamientos);

        ['cupoMaximo' => $cupoMaximo] = $excursion->crearSalida(
            Arr::except($datosSalida, 'excursion_id'), $guia, $asistentes, $equipamientos);

        $request->session()->forget(self::SESION_DATOS_SALIDA);

        $mensaje = 'La Salida Programada fue registrada correctamente con un cupo máximo de '
            . $cupoMaximo . ($cupoMaximo === 1 ? ' persona.' : ' personas.');
        if ($cupoMaximo < (int) $datosSalida['cupo_maximo']) {
            // RN-07: el cupo quedó limitado por el stock del equipamiento incluido
            $mensaje .= ' El cupo se ajustó al stock disponible del equipamiento incluido.';
        }

        return redirect()
            ->route('salidas.listar', ['excursion' => $excursion->id])
            ->with('exito', $mensaje);
    }

    // ========== CU 10 - MODIFICAR SALIDA PROGRAMADA ==========

    private const ERROR_SALIDA_NO_PROGRAMADA =
        'Solo pueden modificarse Salidas Programadas en estado “Programada”.';

    /** CU 10 - pasos 1 y 2: se muestran los datos actuales de la salida. RN-01. */
    public function mostrarFormularioModificacion(SalidaProgramada $salida)
    {
        if (! $salida->estaProgramada()) {
            return redirect()->route('salidas.listar')->with('error', self::ERROR_SALIDA_NO_PROGRAMADA);
        }

        return Inertia::render('Salidas/Modificar', [
            'salida' => $salida->getDatosParaModificar(),
        ]);
    }

    /** CU 10 -> CU 23: empleados disponibles en el período ingresado, sin contar esta salida. */
    public function listarEmpleadosParaModificar(Request $request, SalidaProgramada $salida)
    {
        $filtro = $request->validate([
            'categoria' => ['required', Rule::in(Empleado::CATEGORIAS)],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json([
            'empleados' => Empleado::listarDisponibles(
                $filtro['categoria'], $filtro['fecha_inicio'], $filtro['fecha_fin'], $salida->id),
        ]);
    }

    /** CU 10 -> CU 25: equipamiento con stock en el período ingresado, sin contar esta salida. */
    public function listarEquipamientoParaModificar(Request $request, SalidaProgramada $salida)
    {
        $filtro = $request->validate([
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json([
            'equipamientos' => Equipamiento::listarConStock(
                $filtro['fecha_inicio'], $filtro['fecha_fin'], $salida->id),
        ]);
    }

    /**
     * DSS CU 10: modificarSalida(idSalida, datosSalida, idGuia, idsAsistentes, idsEquipamiento).
     * Paso 4 (revalidación del CU 8), pasos 5 a 12 en el modelo y paso 13 (mensaje).
     */
    public function modificarSalida(Request $request, SalidaProgramada $salida)
    {
        if (! $salida->estaProgramada()) {
            return redirect()->route('salidas.listar')->with('error', self::ERROR_SALIDA_NO_PROGRAMADA);
        }

        $datosSalida = $salida->excursion->validarDatosSalida($request->only([
            'fecha_inicio', 'fecha_fin', 'hora_de_salida', 'cupo_maximo', 'precio_base',
        ]));
        $seleccion = $this->validarSeleccion($request);

        $guia = Empleado::buscar((int) $seleccion['id_guia']);
        $asistentes = Empleado::buscar($seleccion['ids_asistentes'] ?? []);
        $equipamientos = Equipamiento::buscar($seleccion['ids_equipamiento'] ?? []);

        $this->verificarSeleccion($datosSalida, $guia, $asistentes, $equipamientos, $salida->id);

        ['cupoMaximo' => $cupoMaximo, 'clientesNotificados' => $clientesNotificados] =
            $salida->modificar($datosSalida, $guia, $asistentes, $equipamientos);

        $mensaje = 'La modificación de la Salida Programada fue registrada correctamente. Cupo máximo: '
            . $cupoMaximo . ($cupoMaximo === 1 ? ' persona.' : ' personas.');
        if ($cupoMaximo < (int) $datosSalida['cupo_maximo']) {
            $mensaje .= ' El cupo se ajustó al stock disponible del equipamiento incluido.';
        }
        if ($clientesNotificados > 0) {
            // *a (RN-06): notificación simulada
            $mensaje .= " Se notificó el cambio a {$clientesNotificados} "
                . ($clientesNotificados === 1 ? 'cliente' : 'clientes') . ' con reservas activas.';
        }

        return redirect()->route('salidas.listar')->with('exito', $mensaje);
    }

    /** Guía, asistentes y equipamiento elegidos (CU 8 y CU 10). */
    private function validarSeleccion(Request $request): array
    {
        return $request->validate([
            'id_guia' => ['required', 'integer'],
            'ids_asistentes' => ['array'],
            'ids_asistentes.*' => ['integer', 'distinct'],
            'ids_equipamiento' => ['array'],
            'ids_equipamiento.*' => ['integer', 'distinct'],
        ], [
            'id_guia.required' => 'Seleccioná el guía responsable de la salida.',
        ]);
    }

    /** Datos validados en ingresarDatosSalida, o null si todavía no se ingresaron. */
    private function datosSalidaEnCurso(Request $request): ?array
    {
        return $request->session()->get(self::SESION_DATOS_SALIDA);
    }

    /**
     * RN-04 y RN-05: guía obligatorio; guía y asistentes activos, de su categoría y sin otra
     * salida en el período. RN-06: equipamiento con stock disponible mayor a cero.
     */
    private function verificarSeleccion(array $datosSalida, Empleado $guia, Collection $asistentes, Collection $equipamientos, ?int $idSalidaExcluida = null): void
    {
        ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin] = $datosSalida;
        $errores = [];

        if (! $guia->esAsignable(Empleado::CATEGORIA_GUIA, $fechaInicio, $fechaFin, $idSalidaExcluida)) {
            $errores['id_guia'] = 'El guía seleccionado ya no está disponible para el período.';
        }

        $noDisponibles = $asistentes->reject(fn (Empleado $asistente) =>
            $asistente->esAsignable(Empleado::CATEGORIA_ASISTENTE, $fechaInicio, $fechaFin, $idSalidaExcluida));
        if ($noDisponibles->isNotEmpty()) {
            $errores['ids_asistentes'] = 'Ya no están disponibles para el período: '
                . $noDisponibles->map->nombreCompleto()->join(', ') . '.';
        }

        $sinStock = $equipamientos->filter(fn (Equipamiento $equipamiento) =>
            $equipamiento->stockDisponible($fechaInicio, $fechaFin, $idSalidaExcluida) === 0);
        if ($sinStock->isNotEmpty()) {
            $errores['ids_equipamiento'] = 'Ya no tienen stock disponible en el período: '
                . $sinStock->pluck('nombre')->join(', ') . '.';
        }

        if ($errores) {
            throw ValidationException::withMessages($errores);
        }
    }
}