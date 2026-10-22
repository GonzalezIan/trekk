<?php

namespace App\Http\Controllers;

use App\Models\CertificadoMedico;
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
                ->with('certificadoMedico')
                ->orderBy('apellido')
                ->orderBy('nombre')
                ->get()
                ->map(fn (Participante $participante) => $participante->datosParaListado()),
            // 4.1: habilita la carga del certificado médico (CU 17)
            'puedeCargarCertificado' => $reserva->admiteCargaDeCertificados(),
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

    /**
     * CU 17 - Cargar certificado médico, pasos 1 y 2. Se invoca desde el CU 21.
     * DS: cargarCertificadoMedico(idReserva, dniParticipante) -> buscarReserva(idReserva)
     *     -> getParticipante(dniParticipante) -> solicitarArchivoCertificado().
     */
    public function cargarCertificadoMedico(Request $request, Reserva $reserva, string $dniParticipante)
    {
        $participante = $this->participanteQueAdmiteCertificado($request, $reserva, $dniParticipante);

        // solicitarArchivoCertificado(): pide el archivo en los formatos de la RN-01
        return response()->json([
            'participante' => $participante->datosParaListado(),
            'formatos' => CertificadoMedico::FORMATOS_PERMITIDOS,
        ]);
    }

    /**
     * CU 17 - pasos 3 a 5. DS: registrarCertificado(archivo) -> unParticipante.registrarCertificado(archivo)
     *     -> confirmarCargaCertificado().
     * Si el archivo falta o no tiene un formato permitido, responde 422 con el
     * mensaje para el cliente (flujos alternativos 3.1 y 3.2).
     */
    public function registrarCertificado(Request $request, Reserva $reserva, string $dniParticipante)
    {
        $participante = $this->participanteQueAdmiteCertificado($request, $reserva, $dniParticipante);
        $archivo = CertificadoMedico::validarArchivo($request->all())['archivo'];

        $participante->registrarCertificado($archivo);

        // confirmarCargaCertificado()
        return response()->json([
            'mensaje' => 'El certificado fue cargado correctamente y se encuentra a la espera de verificación por parte del administrativo.',
            'participante' => $participante->load('certificadoMedico')->datosParaListado(),
        ]);
    }

    /**
     * CU 17 - Precondición: participante de una reserva propia, con el requisito
     * médico "Pendiente" y a más de 24 horas del inicio de la salida.
     * La reserva ya llega buscada por su id (buscarReserva) gracias al route model binding.
     */
    private function participanteQueAdmiteCertificado(Request $request, Reserva $reserva, string $dniParticipante): Participante
    {
        if (! $reserva->perteneceA($request->user())) {
            abort(403, 'Solo podés cargar certificados de los participantes de tus propias reservas.');
        }

        $participante = $reserva->getParticipante($dniParticipante);

        if (! $participante->admiteCargaDeCertificado()) {
            abort(403, 'Ya no es posible cargar el certificado médico de este participante.');
        }

        return $participante;
    }
}
