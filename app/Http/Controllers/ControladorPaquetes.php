<?php

namespace App\Http\Controllers;

use App\Models\Refugio;
use App\Models\SalidaProgramada;
use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

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

         // Precondición: el Cliente eligió una salida reservable en el CU 9 (RN-01 del CU 9)
        if (! $esAdministrativo && ! $salida->esReservable()) {
            abort(404);
        }

        return Inertia::render('Paquetes/Listar', [
            'paquetes' => $esAdministrativo
                ? $salida->getPaquetes()
                : $salida->getPaquetesDisponibles(),
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
}