<?php

namespace App\Http\Controllers;

use App\Models\SalidaProgramada;
use Illuminate\Http\Request;
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
}