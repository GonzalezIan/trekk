<?php

namespace App\Http\Controllers;

use App\Models\CertificadoMedico;
use App\Models\Cliente;
use App\Models\Paquete;
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
use Illuminate\Validation\ValidationException;

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

        $reservaCancelada = $reservas->first(function ($reserva) {
            return $reserva['estado'] === 'CANCELADA' && 
                str_contains($reserva['motivo_cancelacion'] ?? '', 'Cancelación del paquete');
        });

        return Inertia::render('Reservas/Listar', [
            'reservas' => $reservas,
            'esAdministrativo' => $request->user()->esAdministrativo(),
            'notificacion_cancelacion' => $reservaCancelada, // ✅ Le pasamos la reserva (o null)
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

    // ========== CU 14 - REGISTRAR RESERVA ==========

    public const MENSAJE_RESERVA_VENCIDA =
        'El tiempo para completar la reserva venció. Liberamos los cupos y el equipamiento retenidos: volvé a iniciar la reserva.';

    /**
     * CU 14 - pasos 1 a 3. Se invoca desde el CU 7, al elegir un paquete (Cliente).
     * DS: registrarReserva(idPaquete) -> buscar(idPaquete) -> getSalidaProgramada()
     *     -> solicitarCantidadParticipantes().
     * El paquete ya llega buscado por su id gracias al route model binding.
     */
    public function registrarReserva(Request $request, Paquete $paquete)
    {
        $this->clienteAutenticado($request);
        $this->verificarQueSePuedeReservar($paquete);

        $salida = $paquete->salidaProgramada;
        $excursion = $salida->getExcursion();

        return Inertia::render('Reservas/Registrar', [
            'paquete' => [...$paquete->getDatos(), ...$paquete->getDisponibilidad()],
            'salida' => [
                'id' => $salida->id,
                'fecha_inicio' => $salida->fecha_inicio->format('Y-m-d'),
                'fecha_fin' => $salida->fecha_fin->format('Y-m-d'),
                'hora_de_salida' => $salida->hora_de_salida->format('H:i'),
            ],
            'excursion' => [
                'id' => $excursion->id,
                'nombre' => $excursion->nombre,
                'requiere_certificado_medico' => $excursion->requiere_certificado_medico,
            ],
            'minutosRetencion' => Reserva::MINUTOS_RETENCION,
        ]);
    }

    /**
     * CU 14 - pasos 4 a 6. DS: ingresarCantidadParticipantes(cantidad) -> hayCupoDisponible(cantidad)
     *     -> crearReserva(unCliente, unPaquete) -> retenerCupo(cantidad) -> setFechaHoraVencimiento(20)
     *     -> mostrarFormularioParticipantes(cantidad).
     * La reserva se crea con la cantidad, que es cuando queda "En curso" con los cupos retenidos (paso 5).
     * Si la cantidad no es válida (4.1) o no hay lugar (5.1), responde 422 con el mensaje para el cliente.
     */
    public function ingresarCantidadParticipantes(Request $request, Paquete $paquete)
    {
        // Paso 1: el cliente autenticado queda como responsable de la reserva
        $cliente = $this->clienteAutenticado($request);
        $this->verificarQueSePuedeReservar($paquete);
        $cantidad = (int) Reserva::validarCantidadParticipantes($request->all())['cantidad'];

        $reserva = DB::transaction(function () use ($cliente, $paquete, $cantidad) {
            // Se vuelven a leer bloqueados, para que dos clientes no retengan el mismo lugar
            $salida = SalidaProgramada::lockForUpdate()->findOrFail($paquete->salida_programada_id);
            $refugio = $paquete->refugio?->load(['convenios' => fn ($consulta) => $consulta->lockForUpdate()]);
            $camasDisponibles = $refugio?->getCamasDisponibles($salida->fecha_inicio, $salida->fecha_fin);

            // 5.1: no hay cupo en la salida y/o camas en el refugio (5.2)
            if (! $salida->hayCupoDisponible($cantidad) || ($refugio && $camasDisponibles < $cantidad)) {
                $lugares = $refugio ? min($salida->cupo_disponible, $camasDisponibles) : $salida->cupo_disponible;

                throw ValidationException::withMessages([
                    'cantidad' => "No hay cupos suficientes para el paquete. Lugares disponibles: {$lugares}.",
                ]);
            }

            $reserva = Reserva::crearReserva($cliente, $paquete, $cantidad);
            $salida->retenerCupo($cantidad);
            // 5.2: el paquete incluye alojamiento
            $refugio?->retenerCamas($salida->fecha_inicio, $salida->fecha_fin, $cantidad);
            // RN-03: los lugares quedan retenidos 20 minutos
            $reserva->setFechaHoraVencimiento(Reserva::MINUTOS_RETENCION);

            return $reserva;
        });

        // mostrarFormularioParticipantes(cantidad)
        return response()->json([
            'reserva' => $this->datosReservaEnCurso($reserva),
        ]);
    }

    /**
     * CU 14 - pasos 7 a 11. El formulario manda los datos y confirma la carga en un solo envío.
     * DS: ingresarDatosParticipantes(datosParticipantes[]) -> loop agregarParticipante(...)
     *     -> confirmarCargaParticipantes() -> participantesValidos() -> getExcursion()
     *     -> getEquipamientoObligatorio() -> mostrar equipamiento necesario para la excursión.
     * Si falta algún dato (8.1) o algún participante no cumple las condiciones (9.1 / 9.2),
     * responde 422 y no se guarda ningún participante.
     */
    public function ingresarDatosParticipantes(Request $request, Reserva $reserva)
    {
        $this->verificarReservaEnCurso($request, $reserva);
        $datosParticipantes = $reserva->validarDatosParticipantes($request->all());

        DB::transaction(function () use ($reserva, $datosParticipantes) {
            $reserva->quitarParticipantes();

            foreach ($datosParticipantes as $participante) {
                $reserva->agregarParticipante(
                    $participante['dni'],
                    $participante['nombre'],
                    $participante['apellido'],
                    $participante['fecha_nacimiento'],
                );
            }

            // confirmarCargaParticipantes(): si no son válidos, se deshace la carga
            $reserva->participantesValidos();
        });

        $salida = $reserva->getSalidaProgramada();
        $excursion = $salida->getExcursion();

        return response()->json([
            // Paso 10: situación del requisito médico de cada participante
            'participantes' => $reserva->participantes()->orderBy('id')->get()
                ->map(fn (Participante $participante) => $participante->datosParaRegistro($salida->fecha_inicio)),
            // Paso 11: elementos obligatorios y necesarios para la excursión
            'equipamiento_obligatorio' => $excursion->getEquipamientoObligatorio(),
            'equipamiento_incluido' => $salida->equipoPrestado->pluck('nombre'),
        ]);
    }

    /**
     * CU 14 - 12.1, paso 1: invoca al CU 25 - Listar equipamientos.
     * DS CU 25: listarConStock(fechaInicio, fechaFin) para el período de la salida.
     * Al stock de cada elemento se le suma lo que esta misma reserva ya tiene retenido,
     * así el cliente puede modificar su selección.
     */
    public function listarEquipamientosParaReserva(Request $request, Reserva $reserva)
    {
        $this->verificarReservaEnCurso($request, $reserva);
        [$fechaInicio, $fechaFin] = $reserva->getSalidaProgramada()->getFechas();
        $retenido = $reserva->equipos_alquilados()->pluck('equipos_alquilados.cantidad', 'equipamientos.id');

        return response()->json([
            'equipamientos' => Equipamiento::listarConStock($fechaInicio, $fechaFin)
                ->map(fn (array $equipamiento) => [
                    ...$equipamiento,
                    'stock_disponible' => $equipamiento['stock_disponible'] + ($retenido[$equipamiento['id']] ?? 0),
                ]),
        ]);
    }

    /**
     * CU 14 - 12.1, pasos 2 a 4. DS: loop agregarEquipamiento(unEquipamiento, cantidad)
     *     -> stockDisponible(fechaInicio, fechaFin) -> [alcanza] reserva.agregarEquipamiento(...)
     *     -> confirmarEquipamientoAgregado().
     * La selección reemplaza a la anterior. Si algún elemento no tiene stock suficiente (12.1.1),
     * responde 422, señala cuáles y la reserva conserva la selección que tenía.
     */
    public function agregarEquipamiento(Request $request, Reserva $reserva)
    {
        $this->verificarReservaEnCurso($request, $reserva);
        $seleccion = Reserva::validarSeleccionEquipamiento($request->all());
        [$fechaInicio, $fechaFin] = $reserva->getSalidaProgramada()->getFechas();

        DB::transaction(function () use ($reserva, $seleccion, $fechaInicio, $fechaFin) {
            $reserva->quitarEquipamientos();
            $sinStock = [];

            foreach ($seleccion as $i => $elemento) {
                // Bloqueado, para que dos reservas no tomen el mismo stock
                $equipamiento = Equipamiento::lockForUpdate()->findOrFail($elemento['id']);
                $cantidadDisponible = $equipamiento->stockDisponible($fechaInicio, $fechaFin);

                if ($elemento['cantidad'] <= $cantidadDisponible) {
                    $reserva->agregarEquipamiento($equipamiento, $elemento['cantidad']);
                } else {
                    // 12.1.1: informar stock insuficiente para el equipamiento seleccionado
                    $sinStock["equipamientos.{$i}.cantidad"] =
                        "No hay stock suficiente de {$equipamiento->nombre}: quedan {$cantidadDisponible} disponibles.";
                }
            }

            if ($sinStock) {
                throw ValidationException::withMessages($sinStock);
            }
        });

        // confirmarEquipamientoAgregado()
        return response()->json([
            'equipamiento' => $reserva->detalleImporte()['equipamiento'],
        ]);
    }

    /**
     * CU 14 - pasos 12 a 14. DS: continuar() -> calcularImporteTotal() -> mostrar importe.
     */
    public function continuar(Request $request, Reserva $reserva)
    {
        $this->verificarReservaEnCurso($request, $reserva);

        $reserva->update(['importe_total' => $reserva->calcularImporteTotal()]);

        return response()->json([
            'importe' => $reserva->detalleImporte(),
        ]);
    }

    /**
     * CU 14 - pasos 15 y 16. DS: confirmarReserva() -> temporizadorVigente().
     * Si sigue "En curso" y vigente, la pantalla invoca al CU 22 - Registrar pago de reserva,
     * que al aprobarse el pago registra la reserva (confirmarRegistroReserva, pasos 17 a 21).
     */
    public function confirmarReserva(Request $request, Reserva $reserva)
    {
        $this->verificarReservaEnCurso($request, $reserva);

        return response()->json([
            'reserva' => $this->datosReservaEnCurso($reserva),
        ]);
    }

    /**
     * CU 14 - 17.1: el cliente cancela antes de efectuar el pago.
     * Se liberan los cupos, camas y equipamiento retenidos, y la reserva se descarta.
     */
    public function cancelarRegistroReserva(Request $request, Reserva $reserva)
    {
        if (! $reserva->perteneceA($request->user())) {
            abort(403, 'Solo podés cancelar tus propias reservas.');
        }

        if (! $reserva->estaEnCurso()) {
            abort(403, 'Esta reserva ya no está en curso.');
        }

        $this->anularRegistroEnCurso($reserva);

        return response()->json([
            'mensaje' => 'Cancelaste la reserva. Los cupos retenidos volvieron a estar disponibles.',
        ]);
    }

    /**
     * CU 14 - Precondición: el paquete está "Disponible" y su salida es reservable
     * (PROGRAMADA y a más de 48 hs, RN-01 del CU 9).
     */
    private function verificarQueSePuedeReservar(Paquete $paquete): void
    {
        if (! $paquete->estaDisponible() || ! $paquete->salidaProgramada->esReservable()) {
            abort(403, 'El paquete ya no admite reservas.');
        }
    }

    /**
     * CU 14 - paso 1. Cliente del usuario autenticado, responsable de la reserva.
     * El Administrativo no registra reservas.
     */
    private function clienteAutenticado(Request $request): Cliente
    {
        $cliente = Cliente::where('user_id', $request->user()->id)->first();

        abort_if(! $cliente, 403, 'Solo los clientes pueden registrar reservas.');

        return $cliente;
    }

    /**
     * CU 14 - el cliente sigue registrando su propia reserva "En curso".
     * DS paso 30, temporizadorVigente(): si superó su vencimiento (*a), se liberan los cupos,
     * camas y equipamiento retenidos y el caso de uso termina (responde 410).
     * La reserva ya llega buscada por su id gracias al route model binding.
     */
    private function verificarReservaEnCurso(Request $request, Reserva $reserva): void
    {
        if (! $reserva->perteneceA($request->user())) {
            abort(403, 'Solo podés registrar tus propias reservas.');
        }

        if (! $reserva->estaEnCurso()) {
            abort(403, 'Esta reserva ya no está en curso.');
        }

        if (! $reserva->temporizadorVigente()) {
            $this->anularRegistroEnCurso($reserva);
            abort(410, self::MENSAJE_RESERVA_VENCIDA);
        }
    }

    /**
     * CU 14 - 17.1 / *a. Se vuelve a leer bloqueada, para no liberar dos veces
     * si llegan dos pedidos a la vez.
     */
    private function anularRegistroEnCurso(Reserva $reserva): void
    {
        DB::transaction(function () use ($reserva) {
            $reserva = Reserva::lockForUpdate()->find($reserva->id);

            if ($reserva?->estaEnCurso()) {
                $reserva->anularRegistro();
            }
        });
    }

    /** Datos de la reserva en curso para la pantalla: cupos retenidos y tiempo que le queda. */
    private function datosReservaEnCurso(Reserva $reserva): array
    {
        return [
            'id' => $reserva->id,
            'estado' => $reserva->estado,
            'situacion_de_pago' => $reserva->situacion_de_pago,
            'cantidad_participantes' => $reserva->cantidad_participantes,
            'segundos_restantes' => max(0, (int) now()->diffInSeconds($reserva->fecha_hora_vencimiento, false)),
        ];
    }

    /** CU 14 - paso 21. Situación de pago y de requisito médico de la reserva registrada. */
    private function mensajeReservaRegistrada(Reserva $reserva): string
    {
        $mensaje = 'La reserva fue registrada correctamente. ';

        $mensaje .= $reserva->situacion_de_pago === Reserva::PAGO_COMPLETO
            ? 'Abonaste el importe total.'
            : 'Abonaste la seña: tenés 24 horas para pagar el saldo restante desde Mis reservas.';

        if ($reserva->participantes()->where('estado_requisito_medico', Participante::REQUISITO_PENDIENTE)->exists()) {
            $mensaje .= ' La excursión requiere certificado médico: cargalo para cada participante desde Mis reservas.';
        }

        return $mensaje;
    }

    // ========== RF-17 - CANCELAR RESERVA AUTOMÁTICAMENTE ==========

    /**
     * RF-17. Lo ejecuta el sistema cada minuto (comando reservas:cancelar-vencidas, routes/console.php).
     *  - Reservas "En curso" vencidas (CU 14, RN-04 / *a): se liberan los recursos retenidos y se descartan.
     *  - Reservas "Pendiente" con la seña vencida o con el requisito médico pendiente al llegar la fecha
     *    límite de verificación: pasan a "Cancelada" y se liberan el cupo, las camas y el equipamiento.
     * Devuelve cuántas reservas canceló.
     */
    public function cancelarReservasAutomaticamente(): int
    {
        $canceladas = 0;

        foreach (Reserva::enCursoVencidas() as $reserva) {
            $this->anularRegistroEnCurso($reserva);
            $canceladas++;
        }

        foreach (Reserva::candidatasACancelacionAutomatica() as $reserva) {
            if ($this->cancelarAutomaticamente($reserva)) {
                $canceladas++;
            }
        }

        return $canceladas;
    }

    /**
     * RF-17. Se vuelve a leer bloqueada y se revisa otra vez si corresponde, por si el cliente
     * pagó el saldo o el administrativo la canceló mientras tanto.
     */
    private function cancelarAutomaticamente(Reserva $reserva): bool
    {
        return DB::transaction(function () use ($reserva) {
            $reserva = Reserva::lockForUpdate()->find($reserva->id);
            $motivo = $reserva?->motivoCancelacionAutomatica();

            if (! $motivo) {
                return false;
            }

            $reserva->cancelarAutomaticamente($motivo);
            // ref RF-29 Notificar eventualidades: la notificación al cliente (motivo, sin reintegro) queda a cargo del RF-29.

            return true;
        });
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

        // CU 14 - paso 16: el pago se invocó desde el registro de la reserva
        $registraReserva = $reserva->estaEnCurso();

        $confirmada = DB::transaction(function () use ($reserva, $monto, $datos, $eligioSena, $registraReserva) {
            $reserva->agregarPago($monto, $datos['moneda'], $datos['medio_pago'], $reserva->tipoDePago($eligioSena));

            // CU 14 - pasos 17 a 20.1: confirmarRegistroReserva()
            if ($registraReserva) {
                $reserva->confirmarRegistroReserva();
            }

            // Con el pago completo, la reserva se confirma si ya cumple los requisitos médicos (CU 19 / CU 14 20.2)
            return $reserva->confirmarSiCorresponde();
        });

        // mostrarPagoRegistrado(situacionPago)
        return response()->json([
            'mensaje' => $registraReserva
                ? $this->mensajeReservaRegistrada($reserva)
                : $this->mensajePagoRegistrado($reserva, $confirmada),
            'situacion_de_pago' => $reserva->situacion_de_pago,
            'estado' => $reserva->estado,
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

        // CU 14 - paso 15: la reserva en curso no superó su fecha y hora de vencimiento (*a)
        if ($reserva->estaEnCurso() && ! $reserva->temporizadorVigente()) {
            $this->anularRegistroEnCurso($reserva);
            abort(410, self::MENSAJE_RESERVA_VENCIDA);
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

        // ========== CU 18 - LISTAR CERTIFICADOS PENDIENTES ==========
    public function listarCertificadosPendientes()
    {
        $certificados = CertificadoMedico::with(['participante.reserva'])
            ->where('estado', 'PENDIENTE_DE_VERIFICACION') 
            ->get()
            ->filter(function ($certificado) {
                $reserva = $certificado->participante->reserva;
                return $reserva && $reserva->admiteVerificacionDeCertificados();
            })
            ->values(); 

        return Inertia::render('Certificados/Listar', [
            'certificados' => $certificados,
        ]);
    }
        // ========== CU 20 - REGISTRAR CONFORMIDAD ==========

    /**
     * Muestra el modal para registrar la conformidad de una reserva realizada.
     */
    public function solicitarConformidad(Reserva $reserva)
    {
        // Validaciones (RN)
        if ($reserva->estado !== 'REALIZADA') {
            return response()->json(['error' => 'Solo podés calificar reservas que hayan sido realizadas.'], 403);
        }

        if ($reserva->conformidad()->exists()) {
            return response()->json(['error' => 'Esta reserva ya tiene una conformidad registrada.'], 403);
        }

        return response()->json([
            'reserva_id' => $reserva->id,
            'excursion' => $reserva->paquete->salidaProgramada->excursion->nombre,
        ]);
    }

    /**
     * Guarda la conformidad en la base de datos.
     */
    public function guardarConformidad(Request $request, Reserva $reserva)
    {
        if ($reserva->estado !== 'REALIZADA' || $reserva->conformidad()->exists()) {
            return redirect()->back()->with('error', 'No se puede registrar la conformidad en este momento.');
        }

        $datos = $request->validate([
            'valoracion' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:1000',
        ], [
            'valoracion.required' => 'Debés seleccionar una puntuación.',
            'valoracion.min' => 'La puntuación mínima es 1.',
            'valoracion.max' => 'La puntuación máxima es 5.',
        ]);

        // Creamos la conformidad asociada a la reserva
        $reserva->conformidad()->create([
            'valoracion' => $datos['valoracion'],
            'comentario' => $datos['comentario'] ?? null,
        ]);

        return redirect()->back()->with('exito', '¡Gracias por tu opinión! Tu conformidad ha sido registrada.');
    }
}

