<?php

namespace App\Http\Controllers;

use App\Models\CertificadoMedico;
use App\Models\Participante;
use App\Models\Reserva;
use App\Models\Equipamiento;
use App\Models\SalidaProgramada;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use illuminate\Http\Requests;
use App\Models\Pago;
use App\Services\PasarelaDePagos;
use Illuminate\Validation\Rule;

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
     * CU 16 - Cancelar reserva, pasos 1 a 3. Se invoca desde el CU 15 (Cliente o Administrativo).
     * DS: cancelarReserva(idReserva) -> buscarReserva(idReserva) -> solicitarMotivoCancelacion().
     */
    public function cancelarReserva(Request $request, Reserva $reserva)
    {
        $this->verificarQueSePuedeCancelar($request, $reserva);

        // solicitarMotivoCancelacion(): datos de la reserva para pedir el motivo
        return response()->json([
            'reserva' => $reserva->loadCount('participantes')->datosParaListado(),
        ]);
    }

    /**
     * CU 16 - pasos 4 a 7. DS: ingresarMotivoCancelacion(motivo) -> reserva.cancelar(motivo)
     *     -> getPaquete() -> getSalidaProgramada() -> liberarCupo(cantidadParticipantes)
     *     -> confirmarCancelacionReserva().
     * Si falta el motivo, responde 422 con el mensaje para el usuario (4.1).
     */
    public function ingresarMotivoCancelacion(Request $request, Reserva $reserva)
    {
        $this->verificarQueSePuedeCancelar($request, $reserva);
        $motivo = Reserva::validarMotivoCancelacion($request->all())['motivo'];

        DB::transaction(function () use ($reserva, $motivo) {
            // Se vuelve a leer bloqueada, para no liberar dos veces si se cancela en simultáneo
            $reserva = Reserva::lockForUpdate()->findOrFail($reserva->id);
            if (! $reserva->sePuedeCancelar()) {
                abort(403, 'Solo pueden cancelarse reservas en estado "Pendiente" o "Confirmada".');
            }

            $cantidadParticipantes = $reserva->participantes()->count();

            $reserva->cancelar($motivo);
            $paquete = $reserva->paquete;
            $salida = $paquete->salidaProgramada;
            $salida->liberarCupo($cantidadParticipantes);

            // 6.1 / RN-02: si el paquete incluye alojamiento, se liberan las camas del convenio.
            // El equipamiento alquilado (EquipamientoAlquilado) no se toca: su stock por período
            // se calcula con las reservas activas, así que se libera al quedar "Cancelada".
            $paquete->refugio?->liberarCamas($salida->fecha_inicio, $salida->fecha_fin, $cantidadParticipantes);
        });

        // ref RF-29 Notificar eventualidades: la notificación al cliente (paso 7) queda a cargo del RF-29.

        // confirmarCancelacionReserva()
        return response()->json([
            'mensaje' => 'La reserva fue cancelada. No corresponde reintegro de los importes abonados.',
        ]);
    }

    /**
     * CU 16 - Precondición: la reserva está "Pendiente" o "Confirmada". El Cliente
     * solo puede cancelar sus propias reservas; el Administrativo, cualquiera.
     * La reserva ya llega buscada por su id (buscarReserva) gracias al route model binding.
     */
    private function verificarQueSePuedeCancelar(Request $request, Reserva $reserva): void
    {
        if (! $request->user()->esAdministrativo() && ! $reserva->perteneceA($request->user())) {
            abort(403, 'Solo podés cancelar tus propias reservas.');
        }

        if (! $reserva->sePuedeCancelar()) {
            abort(403, 'Solo pueden cancelarse reservas en estado "Pendiente" o "Confirmada".');
        }
    }

    /**
     * CU 19 - Verificar certificado médico, pasos 1 y 2. Se invoca desde el CU 18 (Administrativo).
     * DS: verificarCertificadoMedico(idReserva, dniParticipante, unCertificado) -> buscarReserva(idReserva)
     *     -> getParticipante(dniParticipante) -> getCertificadoMedico() -> getArchivo() -> mostrarDatosCertificado().
     */
    public function verificarCertificadoMedico(Reserva $reserva, string $dniParticipante)
    {
        $certificado = $this->certificadoQueAdmiteVerificacion($reserva, $dniParticipante);

        return response()->json([
            'certificado' => [
                ...$certificado->datosParaVerificar(),
                'url_archivo' => route('reservas.certificado.archivo', [$reserva, $dniParticipante]),
            ],
        ]);
    }

    /**
     * CU 19 - DS paso 7: getArchivo(). Devuelve el archivo adjunto para verlo en el navegador.
     * Los certificados se guardan en el disco privado: solo los ve el Administrativo.
     */
    public function mostrarArchivoCertificado(Reserva $reserva, string $dniParticipante)
    {
        $certificado = $reserva->getParticipante($dniParticipante)->certificadoMedico;

        abort_if(! $certificado || ! Storage::exists($certificado->archivo), 404);

        return Storage::response($certificado->archivo);
    }

    /**
     * CU 19 - pasos 3 a 9, cuando el archivo corresponde a un certificado válido.
     * DS: ingresarFechaEmision(fechaEmision) -> calcularVigencia(fechaEmision) -> getSalidaProgramada()
     *     -> cubrePeriodo(unaSalidaProgramada) -> setRequisitoMedico(CUMPLIDO) + setEstado(VALIDO)
     *     -> confirmarVerificacionCertificado(); si no cubre, setEstado(INVALIDO) (7.1).
     * Si la fecha es futura o no válida, responde 422 (5.1).
     */
    public function ingresarFechaEmision(Request $request, Reserva $reserva, string $dniParticipante)
    {
        $certificado = $this->certificadoQueAdmiteVerificacion($reserva, $dniParticipante);
        $fechaEmision = Carbon::parse(CertificadoMedico::validarFechaEmision($request->all())['fecha_emision']);

        $mensaje = DB::transaction(function () use ($reserva, $certificado, $fechaEmision) {
            $certificado->calcularVigencia($fechaEmision);
            $salida = $reserva->getSalidaProgramada();

            // 7.1: la vigencia no cubre el período de la Salida Programada
            if (! $certificado->cubrePeriodo($salida)) {
                $certificado->setEstado(
                    CertificadoMedico::ESTADO_INVALIDO,
                    'El certificado no cubre el período de la salida programada.',
                );
                // ref RF-29 Notificar eventualidades: la notificación al cliente queda a cargo del RF-29.

                return 'La vigencia del certificado no cubre el período de la salida: quedó inválido. El requisito médico sigue pendiente.';
            }

            $certificado->participante->setRequisitoMedico(Participante::REQUISITO_CUMPLIDO);
            $certificado->setEstado(CertificadoMedico::ESTADO_VALIDO);

            // Con el pago completo y todos los requisitos cumplidos, la reserva queda "Confirmada"
            $confirmada = $reserva->confirmarSiCorresponde();

            return 'El certificado fue verificado correctamente. El requisito médico del participante quedó cumplido.'
                .($confirmada ? ' La reserva quedó confirmada.' : '');
        });

        // confirmarVerificacionCertificado()
        return response()->json([
            'mensaje' => $mensaje,
            'estado' => $certificado->estado,
        ]);
    }

    /**
     * CU 19 - 3.1: el archivo no corresponde a un certificado médico válido.
     * DS: solicitarMotivoInvalidez() -> ingresarMotivoInvalidez(motivo) -> setEstado(INVALIDO).
     * El requisito médico del participante sigue "Pendiente" y no se pide fecha de emisión (RN-03).
     */
    public function ingresarMotivoInvalidez(Request $request, Reserva $reserva, string $dniParticipante)
    {
        $certificado = $this->certificadoQueAdmiteVerificacion($reserva, $dniParticipante);
        $motivo = CertificadoMedico::validarMotivoInvalidez($request->all())['motivo'];

        $certificado->setEstado(CertificadoMedico::ESTADO_INVALIDO, $motivo);
        // ref RF-29 Notificar eventualidades (motivo = motivo de invalidez): queda a cargo del RF-29.

        return response()->json([
            'mensaje' => 'El certificado fue marcado como inválido. El requisito médico sigue pendiente.',
            'estado' => $certificado->estado,
        ]);
    }

    /**
     * CU 19 - Precondición: el certificado del participante está "Pendiente de verificación",
     * la reserva está "Pendiente" y faltan más de 12 horas para la salida (RN-02).
     * La reserva ya llega buscada por su id (buscarReserva) gracias al route model binding.
     */
    private function certificadoQueAdmiteVerificacion(Reserva $reserva, string $dniParticipante): CertificadoMedico
    {
        // getParticipante(dniParticipante) -> getCertificadoMedico()
        $certificado = $reserva->getParticipante($dniParticipante)->certificadoMedico;

        abort_if(! $certificado, 404);

        if (! $certificado->estaPendienteDeVerificacion() || ! $reserva->admiteVerificacionDeCertificados()) {
            abort(403, 'Este certificado ya no puede verificarse.');
        }

        return $certificado;
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


    // CU 25 - Listar equipamientos
    public function listarEquipamientos(Request $request)
    {
        $equipamientos = Equipamiento::where('stock', '>', 0)->get();
        $esAdministrativo = $request->user()->esAdministrativo();

        if ($esAdministrativo) {
            $salidas = SalidaProgramada::where('estado', 'PROGRAMADA')->get();
            return Inertia::render('Equipamiento/Listar', [
                'equipamientos' => $equipamientos,
                'salidas' => $salidas,
                'esAdministrativo' => true,
            ]);
        }

        return Inertia::render('Equipamiento/Listar', [
            'equipamientos' => $equipamientos,
            'esAdministrativo' => false,
        ]);
    }

    // ========== CU 22 - REGISTRAR PAGO DE RESERVA ==========

    // Alternativo 3.1: tasa de conversión fija
    public const TASA_USD_ARS = 1500;

    /**
     * CU 22 - pasos 1 y 2. Se invoca desde el CU 15 (total o saldo) o desde el CU 14 (seña o total).
     * DS: registrarPagoReserva(idReserva) -> buscarReserva(idReserva) -> calcularImportePendiente()
     *     -> mostrarImporte(importePendiente).
     */
    public function registrarPagoReserva(Request $request, Reserva $reserva)
    {
        $this->verificarQueSePuedePagar($request, $reserva);

        return response()->json([
            'importe_pendiente' => $reserva->calcularImportePendiente(),
            // Alternativo 1.1: la seña solo se ofrece al registrar la reserva
            'importe_sena' => $reserva->admitePagoDeSena() ? $reserva->calcularSena() : null,
            'tasa_usd_ars' => self::TASA_USD_ARS,
            'medios_pago' => Pago::MEDIOS_PAGO,
        ]);
    }

    /**
     * CU 22 - pasos 3 a 7. DS: confirmarPago(medioPago) -> procesarCobro(monto, moneda, medioPago)
     *     -> [aprobada] agregarPago(monto, moneda, medioPago, tipoPago) -> actualizarSituacionPago()
     *        -> mostrarPagoRegistrado(situacionPago)
     *     -> [rechazada] informarPagoRechazado().
     */
    public function confirmarPago(Request $request, Reserva $reserva, PasarelaDePagos $pasarela)
    {
        $this->verificarQueSePuedePagar($request, $reserva);

        $datos = $request->validate([
            'medio_pago' => ['required', Rule::in(Pago::MEDIOS_PAGO)],
            'moneda' => ['required', Rule::in(Pago::MONEDAS)],
            'sena' => ['boolean'],
            'simular_rechazo' => ['boolean'],
        ], [
            'medio_pago.required' => 'Seleccioná un medio de pago.',
            'moneda.required' => 'Seleccioná la moneda.',
            'in' => 'El valor seleccionado no es válido.',
        ]);

        // Alternativo 1.1 (seleccionarPagoSeña) o el importe pendiente: total o saldo restante (1.2)
        $eligioSena = (bool) ($datos['sena'] ?? false);
        if ($eligioSena && ! $reserva->admitePagoDeSena()) {
            abort(403, 'La seña solo puede abonarse al registrar la reserva.');
        }
        $importe = $eligioSena ? $reserva->calcularSena() : $reserva->calcularImportePendiente();

        // Alternativo 3.1 (seleccionarMoneda("ARS"))
        $monto = $datos['moneda'] === 'ARS' ? $this->convertirAARS($importe) : $importe;

        $resultado = $pasarela->procesarCobro(
            $monto, $datos['moneda'], $datos['medio_pago'], (bool) ($datos['simular_rechazo'] ?? false),
        );

        // Alternativo 5.1 - informarPagoRechazado(): la reserva no cambia y se vuelve al paso 3
        if ($resultado !== PasarelaDePagos::APROBADA) {
            return response()->json([
                'message' => 'El pago no pudo procesarse: la pasarela de pagos rechazó la transacción. Elegí otro medio de pago e intentá nuevamente.',
            ], 422);
        }

        $reserva->agregarPago($monto, $datos['moneda'], $datos['medio_pago'], $reserva->tipoDePago($eligioSena));

        // Con el pago completo, la reserva se confirma si ya cumple los requisitos médicos (CU 19)
        $confirmada = $reserva->confirmarSiCorresponde();

        // mostrarPagoRegistrado(situacionPago)
        return response()->json([
            'mensaje' => $this->mensajePagoRegistrado($reserva, $confirmada),
            'situacion_de_pago' => $reserva->situacion_de_pago,
        ]);
    }

    /** DS CU 22: convertirAARS(importe). Tasa fija 1 USD = 1500 ARS. */
    private function convertirAARS(float $importe): float
    {
        return round($importe * self::TASA_USD_ARS, 2);
    }

    /**
     * CU 22 - Precondición: el cliente paga una reserva propia que tiene un importe pendiente.
     * La reserva ya llega buscada por su id (buscarReserva) gracias al route model binding.
     */
    private function verificarQueSePuedePagar(Request $request, Reserva $reserva): void
    {
        if (! $reserva->perteneceA($request->user())) {
            abort(403, 'Solo podés pagar tus propias reservas.');
        }

        if (! $reserva->admitePago()) {
            abort(403, 'Esta reserva no tiene pagos pendientes.');
        }
    }

    private function mensajePagoRegistrado(Reserva $reserva, bool $confirmada): string
    {
        if ($reserva->situacion_de_pago === Reserva::PAGO_PENDIENTE_DE_PAGO_TOTAL) {
            return 'Se registró el pago de la seña. La reserva queda pendiente de pago total.';
        }

        return 'El pago fue registrado. La reserva tiene el pago completo'
            . ($confirmada ? ' y quedó confirmada.' : '.');
    }
}

