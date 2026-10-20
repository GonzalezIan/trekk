<?php

namespace App\Http\Controllers;

use App\Models\Participante;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
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

    /**
     * CU 21 - Listar participantes (Cliente, flujo alternativo 3.1).
     * Participantes de una reserva propia. Se consulta desde el CU 15.
     */
    public function listarParticipantesDeReserva(Request $request, Reserva $reserva)
    {
        if (! $reserva->perteneceA($request->user())) {
            abort(403, 'Solo podés consultar los participantes de tus propias reservas.');
        }

        return response()->json([
            'participantes' => $reserva->participantes()
                ->orderBy('apellido')
                ->orderBy('nombre')
                ->get()
                ->map(fn (Participante $participante) => $participante->datosParaListado()),
            // 4.1: habilita la carga del certificado médico (CU 17)
            'puedeCargarCertificado' => $reserva->faltanMasDe24HorasParaLaSalida(),
        ]);
    }

    /**
     * CU 21 - Listar participantes (Administrativo, flujo alternativo 3.2).
     * Nómina de las reservas "Confirmada" de una Salida Programada. Se consulta desde el CU 9.
     */
    public function listarParticipantesDeSalida(SalidaProgramada $salida)
    {
        return response()->json([
            'participantes' => Participante::listarConfirmadosDeSalida($salida->id)
                ->map(fn (Participante $participante) => $participante->datosParaListado()),
        ]);
    }
}
