<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ControladorReservas extends Controller
{
    /**
     * CU 15 - Listar reservas.
     * Cliente: solo sus reservas (3.1). Administrativo: todas (3.2).
     * En ambos casos se excluyen las reservas "En curso".
     */
    public function listarReservas(Request $request)
    {
        $reservas = Reserva::listarSegunPerfil($request->user())
            ->map(fn (Reserva $reserva) => $reserva->datosParaListado());

        return Inertia::render('Reservas/Listar', [
            'reservas' => $reservas,
            'esAdministrativo' => $request->user()->esAdministrativo(),
        ]);
    }
}
