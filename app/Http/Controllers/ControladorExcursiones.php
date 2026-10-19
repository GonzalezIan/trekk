<?php

namespace App\Http\Controllers;

use App\Models\Excursion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ControladorExcursiones extends Controller
{
    /**
     * CU 4 - Listar excursiones.
     * Cliente: solo las excursiones en estado ACTIVA (RN-01).
     * Administrativo: todas, sin importar el estado (RN-02).
     */
    public function listarExcursiones(Request $request)
    {
        $consulta = Excursion::query()->orderBy('nombre');

        if (! $request->user()->esAdministrativo()) {
            $consulta->where('estado', Excursion::ESTADO_ACTIVA);
        }

        return Inertia::render('Excursiones/Listar', [
            'excursiones' => $consulta->get([
                'id', 'nombre', 'recorrido', 'dificultad', 'duracion', 'estado',
            ]),
            'esAdministrativo' => $request->user()->esAdministrativo(),
        ]);
    }

    /**
     * CU 1 - pasos 1 y 2: el sistema despliega el formulario de alta.
     */
    public function mostrarFormularioAlta()
    {
        return Inertia::render('Excursiones/Alta', [
            'dificultades' => Excursion::DIFICULTADES,
        ]);
    }

    /**
     * CU 1 - pasos 3 a 6: valida, registra la excursión en estado ACTIVA e informa.
     * Si la validación falla, Laravel vuelve al formulario con los errores
     * y conservando los datos ingresados (flujos alternativos 4.1, 4.2 y 4.3).
     */
    public function registrarExcursion(Request $request)
    {
        $datosExcursion = Excursion::validarAlta($request->all());

        Excursion::crear($datosExcursion);

        return redirect()
            ->route('excursiones.listar')
            ->with('exito', 'La excursión fue registrada correctamente.');
    }
}
