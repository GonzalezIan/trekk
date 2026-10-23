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

        $seleccion = $request->validate([
            'id_guia' => ['required', 'integer'],
            'ids_asistentes' => ['array'],
            'ids_asistentes.*' => ['integer', 'distinct'],
            'ids_equipamiento' => ['array'],
            'ids_equipamiento.*' => ['integer', 'distinct'],
        ], [
            'id_guia.required' => 'Seleccioná el guía responsable de la salida.',
        ]);

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

    /** Datos validados en ingresarDatosSalida, o null si todavía no se ingresaron. */
    private function datosSalidaEnCurso(Request $request): ?array
    {
        return $request->session()->get(self::SESION_DATOS_SALIDA);
    }

    /**
     * RN-04 y RN-05: guía obligatorio; guía y asistentes activos, de su categoría y sin otra
     * salida en el período. RN-06: equipamiento con stock disponible mayor a cero.
     */
    private function verificarSeleccion(array $datosSalida, Empleado $guia, Collection $asistentes, Collection $equipamientos): void
    {
        ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin] = $datosSalida;
        $errores = [];

        if (! $guia->esAsignable(Empleado::CATEGORIA_GUIA, $fechaInicio, $fechaFin)) {
            $errores['id_guia'] = 'El guía seleccionado ya no está disponible para el período.';
        }

        $noDisponibles = $asistentes->reject(fn (Empleado $asistente) =>
            $asistente->esAsignable(Empleado::CATEGORIA_ASISTENTE, $fechaInicio, $fechaFin));
        if ($noDisponibles->isNotEmpty()) {
            $errores['ids_asistentes'] = 'Ya no están disponibles para el período: '
                . $noDisponibles->map->nombreCompleto()->join(', ') . '.';
        }

        $sinStock = $equipamientos->filter(fn (Equipamiento $equipamiento) =>
            $equipamiento->stockDisponible($fechaInicio, $fechaFin) === 0);
        if ($sinStock->isNotEmpty()) {
            $errores['ids_equipamiento'] = 'Ya no tienen stock disponible en el período: '
                . $sinStock->pluck('nombre')->join(', ') . '.';
        }

        if ($errores) {
            throw ValidationException::withMessages($errores);
        }
    }
}