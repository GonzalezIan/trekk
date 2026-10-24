<?php

namespace App\Http\Controllers;

use App\Models\Refugio;
use App\Models\SalidaProgramada;
use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Models\Paquete;
use Illuminate\Support\Facades\DB;

class ControladorPaquetes extends Controller
{
    /**
     * CU 7 - Listar paquetes.
     * Cliente        -> DS: listarPaquetes(idSalida) -> getPaquetesDisponibles().
     * Administrativo -> DS: listarPaquetes(idSalida) -> getPaquetes().
     */
        public function listarPaquetes(Request $request, int $idSalida)
    {
        $esAdministrativo = $request->user()->esAdministrativo();
        $salida = SalidaProgramada::buscar($idSalida);

        if (! $esAdministrativo && ! $salida->esReservable()) {
            abort(404);
        }

        $paquetes = $esAdministrativo
            ? $salida->getPaquetes()
            : $salida->getPaquetesDisponibles();

        // Convertimos a array de forma segura. Si ya es array, lo deja igual.
        $paquetes = is_array($paquetes) ? $paquetes : $paquetes->toArray();

        if ($esAdministrativo) {
            // Usamos array_map que es una función nativa de PHP y no da problemas en VS Code
            $paquetes = array_map(function ($paquete) {
                // Buscamos el paquete directo en la base de datos para contar las reservas
                $cantidad = \App\Models\Paquete::find($paquete['id'])
                    ->reservas()
                    ->whereIn('estado', ['PENDIENTE', 'CONFIRMADA', 'EN_CURSO'])
                    ->count();

                // Agregamos el dato al array
                $paquete['reservas_activas'] = $cantidad;

                return $paquete;
            }, $paquetes);
        }

        return Inertia::render('Paquetes/Listar', [
            'paquetes' => $paquetes,
            'salida' => [
                'id' => $salida->id,
                'excursion_id' => $salida->excursion_id,
                'excursion' => $salida->excursion->nombre,
                'fecha_inicio' => $salida->fecha_inicio->format('Y-m-d'),
                'fecha_fin' => $salida->fecha_fin->format('Y-m-d'),
                'estado' => $salida->estado,
            ],
            'esAdministrativo' => $esAdministrativo,
        ]);
    }
    // ========== CU 5 - REGISTRAR PAQUETE ==========

    private const ERROR_SALIDA_NO_PROGRAMADA =
        'Solo pueden registrarse paquetes sobre Salidas Programadas en estado “Programada”.';

    /**
     * DSS CU 5 - 1: solicitarRegistroPaquete(idSalida): datosSalida.
     * Pasos 1 y 2: se muestran los datos que el paquete toma de la salida y el formulario.
     */
    public function solicitarRegistroPaquete(int $idSalida)
    {
        $salida = SalidaProgramada::buscar($idSalida);

        // RN-01
        if (! $salida->estaProgramada()) {
            return redirect()->route('salidas.listar')->with('error', self::ERROR_SALIDA_NO_PROGRAMADA);
        }

        return Inertia::render('Paquetes/Registrar', [
            'salida' => $salida->getDatosParaPaquete(),
        ]);
    }

    /** DSS CU 5 - 3: listarServicios(). Alternativo 3.1 -> CU 24 - Listar servicios. */
    public function listarServicios()
    {
        return response()->json(['servicios' => Servicio::listar()]);
    }

    /** DSS CU 5 - 5: listarRefugiosAdmisibles(). Alternativo 3.2 -> CU 26 - Listar refugios. */
    public function listarRefugiosAdmisibles(int $idSalida)
    {
        $salida = SalidaProgramada::buscar($idSalida);
        abort_unless($salida->estaProgramada(), 422, self::ERROR_SALIDA_NO_PROGRAMADA);

        return response()->json([
            'refugios' => Refugio::listarAdmisibles($salida->fecha_inicio, $salida->fecha_fin),
        ]);
    }

    /**
     * DSS CU 5 - 7: registrarPaquete(nombre, descripcion, idsServicios, idRefugio): idPaquete, precio.
     * Pasos 3 a 6. Si la validación falla se vuelve al formulario conservando lo ingresado
     * (alternativos 4.1 y 4.2). El refugio se vuelve a verificar por si dejó de ser admisible.
     */
    public function registrarPaquete(Request $request, int $idSalida)
    {
        $salida = SalidaProgramada::buscar($idSalida);
        if (! $salida->estaProgramada()) {
            return redirect()->route('salidas.listar')->with('error', self::ERROR_SALIDA_NO_PROGRAMADA);
        }

        $datos = $request->validate([
            'nombre' => [
                'required', 'string', 'max:100',
                function ($atributo, $valor, $fallar) use ($salida) {
                    // Alternativo 4.2 (RN-06)
                    if ($salida->existePaquete($valor)) {
                        $fallar('Ya existe un paquete con ese nombre en esta Salida Programada. Ingresá un nombre distinto.');
                    }
                },
            ],
            'descripcion' => ['required', 'string', 'max:2000'],
            'ids_servicios' => ['array'],
            'ids_servicios.*' => ['integer', 'distinct'],
            'id_refugio' => ['nullable', 'integer'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'descripcion.max' => 'La descripción no puede superar los 2000 caracteres.',
        ]);

        $servicios = Servicio::buscar($datos['ids_servicios'] ?? []);
        $refugio = isset($datos['id_refugio']) ? Refugio::buscar((int) $datos['id_refugio']) : null;

        if ($refugio && ! $refugio->esAdmisible($salida->fecha_inicio, $salida->fecha_fin)) {
            throw ValidationException::withMessages([
                'id_refugio' => 'El refugio seleccionado ya no tiene un convenio admisible para el período de la salida.',
            ]);
        }

        $nombre = trim($datos['nombre']);
        ['precio' => $precio] = $salida->crearPaquete($nombre, trim($datos['descripcion']), $servicios, $refugio);

        return redirect()
            ->route('paquetes.listar', $salida->id)
            ->with('exito', "El paquete “{$nombre}” fue registrado correctamente con un precio de $ "
                . number_format($precio, 2, ',', '.') . '.');
    }
        // ========== CU - CANCELAR PAQUETE ==========

    /**
     * Muestra la información para confirmar la cancelación.
     */
    public function solicitarCancelacion(int $idPaquete)
    {
        $paquete = Paquete::findOrFail($idPaquete);

        if ($paquete->estado === 'CANCELADO') {
            return redirect()->back()->with('error', 'Este paquete ya se encuentra cancelado.');
        }

        $reservasActivas = $paquete->reservas()
            ->whereIn('estado', ['PENDIENTE', 'CONFIRMADA', 'EN_CURSO'])
            ->count();

        return response()->json([
            'paquete' => $paquete,
            'reservas_afectadas' => $reservasActivas
        ]);
    }

    /**
     * Confirma la cancelación y hace la cascada.
     */
    public function confirmarCancelacion(Request $request, int $idPaquete)
    {
        $paquete = Paquete::findOrFail($idPaquete);

        if ($paquete->estado === 'CANCELADO') {
            return redirect()->back()->with('error', 'Este paquete ya se encuentra cancelado.');
        }

        // 1. Validar Justificación (Obligatoria)
        $datos = $request->validate([
            'motivo_cancelacion' => ['required', 'string', 'min:5', 'max:500']
        ], [
            'motivo_cancelacion.required' => 'Debés ingresar un motivo para cancelar el paquete.',
            'motivo_cancelacion.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        // 2. Realizar baja lógica y cancelación en cascada
        DB::transaction(function () use ($paquete, $datos) {
            
            // Baja lógica del paquete
            $paquete->estado = 'CANCELADO';
            $paquete->save();

            // Buscamos las reservas activas
            $reservasActivas = $paquete->reservas()
                ->whereIn('estado', ['PENDIENTE', 'CONFIRMADA', 'EN_CURSO'])
                ->get();

            // Si hay reservas, hacemos la cascada
            if ($reservasActivas->isNotEmpty()) {
                foreach ($reservasActivas as $reserva) {
                    $reserva->estado = 'CANCELADA';
                    $reserva->motivo_cancelacion = "Cancelación del paquete: " . $datos['motivo_cancelacion'];
                    $reserva->save();

                    $cantidadParticipantes = $reserva->participantes()->count();
                    $salida = $paquete->salidaProgramada;

                    if ($salida) {
                        $salida->liberarCupo($cantidadParticipantes);
                    }

                    if ($paquete->refugio && $salida) {
                        $paquete->refugio->liberarCamas(
                            $salida->fecha_inicio, 
                            $salida->fecha_fin, 
                            $cantidadParticipantes
                        );
                    }
                }
            }
        });

        // 3. Mostrar mensaje de éxito
        return redirect()->route('paquetes.listar', $paquete->salida_programada_id)
            ->with('exito', '¡Baja realizada con éxito!')
            ->with('subtexto_exito', 'Los clientes que hayan reservado serán notificados a la brevedad.');
    }
}