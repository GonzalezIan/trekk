<?php

namespace App\Http\Controllers;

use App\Models\Excursion;
use App\Models\SalidaProgramada;
use Illuminate\Http\Request;
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
}