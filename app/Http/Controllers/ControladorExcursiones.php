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

    /**
     * CU 3 - pasos 1 a 3: verifica que la excursión se pueda modificar
     * y despliega el formulario con sus datos actuales.
     */
    public function mostrarFormularioModificacion(Excursion $excursion)
    {
        if ($error = $this->impedimentoParaModificar($excursion)) {
            // Alternativo 2.1: se informa y el usuario vuelve al listado (paso 1)
            return redirect()->route('excursiones.listar')->with('error', $error);
        }

        return Inertia::render('Excursiones/Modificar', [
            'excursion' => $excursion->only([
                'id', 'nombre', 'descripcion', 'recorrido', 'etapas', 'dificultad', 'duracion',
                'experiencia_requerida', 'acepta_menores', 'equipamiento_obligatorio',
                'requiere_certificado_medico',
            ]),
            'dificultades' => Excursion::DIFICULTADES,
        ]);
    }

    /**
     * CU 3 - pasos 4 a 6: valida los datos modificados y los guarda.
     * Si la validación falla, vuelve al formulario con los errores (alternativo 5.1).
     */
    public function modificarExcursion(Request $request, Excursion $excursion)
    {
        // Se vuelve a verificar: pudo entrar una reserva mientras se completaba el formulario
        if ($error = $this->impedimentoParaModificar($excursion)) {
            return redirect()->route('excursiones.listar')->with('error', $error);
        }

        $excursion->modificar($excursion->validarModificacion($request->all()));

        return redirect()
            ->route('excursiones.listar')
            ->with('exito', 'Los datos de la excursión fueron actualizados correctamente.');
    }

    /**
     * CU 2 - Baja de excursión: si no tiene reservas activas, queda INACTIVA
     * y se cancelan sus salidas programadas y paquetes ewe.
     */
    public function darDeBajaExcursion(Excursion $excursion)
    {
        if (! $excursion->estaActiva()) {
            return redirect()->route('excursiones.listar')
                ->with('error', 'Solo pueden darse de baja excursiones en estado “Activa”.');
        }

        if ($excursion->tieneReservasActivas()) {
            return redirect()->route('excursiones.listar')
                ->with('error', 'La excursión no puede darse de baja porque tiene paquetes con reservas activas.');
        }

        $excursion->darDeBaja();

        return redirect()
            ->route('excursiones.listar')
            ->with('exito', 'La excursión fue dada de baja. Sus salidas programadas y paquetes quedaron cancelados.');
    }

    /** CU 3 - RN-03 y RN-01. Devuelve el motivo por el que no se puede modificar, o null :/ . */
    private function impedimentoParaModificar(Excursion $excursion): ?string
    {
        if (! $excursion->estaActiva()) {
            return 'Solo pueden modificarse excursiones en estado “Activa”.';
        }

        if ($excursion->tieneReservasActivas()) {
            return 'La excursión seleccionada no puede ser modificada ya que está asociada a paquetes con reservas activas.';
        }

        return null;
    }
}