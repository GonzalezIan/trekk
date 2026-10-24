<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Reserva extends Model
{
    use HasFactory;
    protected $table = 'reservas';

    protected $fillable = [
        'cliente_id',
        'paquete_id',
        'cantidad_participantes',
        'fecha_hora_registro',
        'fecha_hora_vencimiento',
        'estado',
        'situacion_de_pago',
        'importe_total',
        'motivo_cancelacion',
    ];

    protected $casts = [
        'fecha_hora_registro' => 'datetime',
        'fecha_hora_vencimiento' => 'datetime',
        'importe_total' => 'decimal:2',
        'cantidad_participantes' => 'integer',
        'estado' => 'string',
        'situacion_de_pago' => 'string',
    ];

    // =========== RELACIONES ==========

    public function cliente():BelongsTo{
        return $this->belongsTo(Cliente::class);
    }
    
    public function paquete():BelongsTo{
        return $this->belongsTo(Paquete::class);
    }

    public function participantes():HasMany{
        return $this->hasMany(Participante::class);
    }

    public function pagos():HasMany{
        return $this->hasMany(Pago::class);
    }

    public function equipos_alquilados():BelongsToMany{
        return $this->belongsToMany(Equipamiento::class,'equipos_alquilados')->withPivot('cantidad');
    }

    public function conformidad():HasOne{
        return $this->hasOne(Conformidad::class);
    }

    // Estados y situaciones de pago según el diagrama de clases
    public const ESTADO_EN_CURSO = 'EN_CURSO';
    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_CONFIRMADA = 'CONFIRMADA';
    public const ESTADO_REALIZADA = 'REALIZADA';
    public const ESTADO_CANCELADA = 'CANCELADA';

    public const PAGO_PENDIENTE_DE_PAGO_TOTAL = 'PENDIENTE_DE_PAGO_TOTAL';
    public const PAGO_COMPLETO = 'PAGO_COMPLETO';

    /**
     * CU 15 - paso 3. Consulta las reservas según el perfil del usuario,
     * exceptuando las que están "En curso" (todavía no fueron registradas).
     *  3.1: el Cliente solo ve las reservas a su nombre.
     *  3.2: el Administrativo ve todas, sin importar el cliente responsable.
     */
    public static function listarSegunPerfil(User $usuario): Collection
    {
        $consulta = self::query()
            ->where('estado', '!=', self::ESTADO_EN_CURSO)
            ->with(['paquete.salidaProgramada.excursion', 'cliente', 'conformidad'])
            ->withCount('participantes')
            ->latest('fecha_hora_registro');

        if (! $usuario->esAdministrativo()) {
            $cliente = Cliente::where('user_id', $usuario->id)->first();
            $consulta->where('cliente_id', $cliente?->id);
        }

        return $consulta->get();
    }

    /**
     * CU 15 - paso 4. Datos que se muestran de cada reserva en el listado.
     */
    public function datosParaListado(): array
    {
        $salida = $this->paquete->salidaProgramada;

        return [
            'id' => $this->id,
            'estado' => $this->estado,
            'situacion_de_pago' => $this->situacion_de_pago,
            'importe_total' => (float) $this->importe_total,
            'motivo_cancelacion' => $this->motivo_cancelacion,
            'cantidad_participantes' => $this->participantes_count,
            'paquete' => $this->paquete->nombre,
            'excursion' => $salida->excursion->nombre,
            'fecha_inicio' => $salida->fecha_inicio->toDateString(),
            'fecha_fin' => $salida->fecha_fin->toDateString(),
            'cliente' => [
                'dni' => $this->cliente->dni,
                'nombre' => $this->cliente->nombre,
                'apellido' => $this->cliente->apellido,
            ],
            'tiene_conformidad' => $this->conformidad !== null,
            'se_puede_cancelar' => $this->sePuedeCancelar(),
            'se_puede_pagar' => $this->admitePago(),
        ];
    }

    /**
     * CU 16 - Precondición y RN-01 del CU 15: solo se cancelan reservas
     * en estado "Pendiente" o "Confirmada".
     */
    public function sePuedeCancelar(): bool
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_CONFIRMADA]);
    }

    /**
     * CU 16 - paso 4. Valida el motivo ingresado y lo devuelve.
     *  4.1: el motivo de la cancelación es obligatorio.
     */
    public static function validarMotivoCancelacion(array $datos): array
    {
        return Validator::make($datos, [
            'motivo' => ['required', 'string'],
        ], [
            'motivo.required' => 'El motivo de la cancelación es obligatorio.',
        ])->validate();
    }

    /**
     * CU 16 - DS pasos 5 y 6. Registra el motivo y pasa la reserva a "Cancelada".
     */
    public function cancelar(string $motivo): void
    {
        $this->update([
            'estado' => self::ESTADO_CANCELADA,
            'motivo_cancelacion' => trim($motivo),
        ]);
    }

    /**
     * Indica si la reserva está a nombre del cliente del usuario autenticado.
     */
    public function perteneceA(User $usuario): bool
    {
        return $this->cliente->user_id === $usuario->id;
    }

    /**
     * CU 17 - DS paso 3. Participante de la reserva con ese DNI.
     * Si no pertenece a la reserva, responde 404.
     */
    public function getParticipante(string $dniParticipante): Participante
    {
        return $this->participantes()->where('dni', $dniParticipante)->firstOrFail();
    }

    /**
     * CU 17 - Precondición. Solo se cargan certificados en reservas "Pendiente"
     * y si faltan más de 24 horas para la salida (RN-03).
     * Una reserva "Confirmada" ya tiene todos los requisitos médicos cumplidos
     * (o no requeridos) y el pago completo, así que no le falta ningún certificado.
     */
    public function admiteCargaDeCertificados(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE
            && $this->faltanMasDe24HorasParaLaSalida();
    }

    /**
     * CU 21 - RN-01. Solo se puede cargar un certificado médico si faltan
     * más de 24 horas para el inicio de la Salida Programada.
     */
    public function faltanMasDe24HorasParaLaSalida(): bool
    {
        return $this->faltanMasDeHorasParaLaSalida(24);
    }

    /**
     * ¿Faltan más de esas horas para el inicio (fecha + hora) de la Salida Programada?
     */
    public function faltanMasDeHorasParaLaSalida(int $horas): bool
    {
        $salida = $this->getSalidaProgramada();
        $inicio = $salida->fecha_inicio->copy()->setTimeFrom($salida->hora_de_salida);

        return now()->addHours($horas)->lt($inicio);
    }

    /**
     * CU 19 - DS paso 12. Salida Programada de la reserva (Reserva -> Paquete -> SalidaProgramada).
     */
    public function getSalidaProgramada(): SalidaProgramada
    {
        return $this->paquete->salidaProgramada;
    }

    /**
     * CU 19 - Precondición. Solo se verifican certificados de reservas "Pendiente"
     * y si faltan más de 12 horas para el inicio de la salida (RN-02).
     */
    public function admiteVerificacionDeCertificados(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE
            && $this->faltanMasDeHorasParaLaSalida(12);
    }

    /**
     * CU 19 - Confirma la reserva si ya tiene el pago completo y ningún participante
     * con el requisito médico "Pendiente" (todos "Cumplido" o "No requerido").
     * Devuelve true si la reserva quedó "Confirmada".
     */
    public function confirmarSiCorresponde(): bool
    {
        $requisitosCumplidos = ! $this->participantes()
            ->where('estado_requisito_medico', Participante::REQUISITO_PENDIENTE)
            ->exists();

        if ($this->estado !== self::ESTADO_PENDIENTE
            || $this->situacion_de_pago !== self::PAGO_COMPLETO
            || ! $requisitosCumplidos) {
            return false;
        }

        $this->update(['estado' => self::ESTADO_CONFIRMADA]);

        return true;
    }

    // ========== CU 22 - REGISTRAR PAGO DE RESERVA ==========

    public const PORCENTAJE_SENA = 0.5;

    /**
     * CU 22 - Precondición. Se paga una reserva "En curso" (desde el CU 14)
     * o una "Pendiente" a la que todavía le falta pagar (desde el CU 15).
     */
    public function admitePago(): bool
    {
        return in_array($this->estado, [self::ESTADO_EN_CURSO, self::ESTADO_PENDIENTE])
            && $this->calcularImportePendiente() > 0;
    }

    /**
     * CU 22 - alternativo 1.1. Solo se puede abonar la seña al registrar
     * la reserva (CU 14), antes de cualquier otro pago.
     */
    public function admitePagoDeSena(): bool
    {
        return $this->estado === self::ESTADO_EN_CURSO && ! $this->pagos()->exists();
    }

    /**
     * DS CU 22: calcularImportePendiente(). En USD.
     * El importe total o, si ya se pagó la seña, el saldo restante (alternativo 1.2).
     */
    public function calcularImportePendiente(): float
    {
        if ($this->situacion_de_pago === self::PAGO_COMPLETO) {
            return 0;
        }

        return $this->tienePagoDeSena()
            ? round((float) $this->importe_total - $this->calcularSena(), 2)
            : (float) $this->importe_total;
    }

    /** DS CU 22: calcularSeña(). La seña es el 50% del importe total (alternativo 1.1). */
    public function calcularSena(): float
    {
        return round((float) $this->importe_total * self::PORCENTAJE_SENA, 2);
    }

    /** Tipo del próximo pago: Seña si el cliente la eligió, Saldo restante si ya la pagó, si no Total. */
    public function tipoDePago(bool $eligioSena): string
    {
        if ($eligioSena) {
            return Pago::TIPO_SENA;
        }

        return $this->tienePagoDeSena() ? Pago::TIPO_SALDO_RESTANTE : Pago::TIPO_TOTAL;
    }

    /**
     * DS CU 22: agregarPago(monto, moneda, medioPago, tipoPago).
     * Paso 6: registra el pago con fecha y hora. Paso 7: actualiza la situación de pago.
     */
    public function agregarPago(float $monto, string $moneda, string $medioPago, string $tipoPago): Pago
    {
        $pago = $this->pagos()->create([
            'monto' => $monto,
            'moneda' => $moneda,
            'medio_pago' => $medioPago,
            'tipo_pago' => $tipoPago,
            'fecha_hora_pago' => now(),
            'resultado_transaccion' => Pago::RESULTADO_APROBADA,
        ]);

        $this->actualizarSituacionPago();

        return $pago;
    }

    /**
     * DS CU 22: actualizarSituacionPago().
     * Con un pago Total o de Saldo restante queda "Pago completo"; solo con la seña,
     * "Pendiente de pago total".
     */
    private function actualizarSituacionPago(): void
    {
        $pagoCompleto = $this->pagos()
            ->whereIn('tipo_pago', [Pago::TIPO_TOTAL, Pago::TIPO_SALDO_RESTANTE])
            ->exists();

        $this->update([
            'situacion_de_pago' => $pagoCompleto ? self::PAGO_COMPLETO : self::PAGO_PENDIENTE_DE_PAGO_TOTAL,
        ]);
    }

    private function tienePagoDeSena(): bool
    {
        return $this->pagos()->where('tipo_pago', Pago::TIPO_SENA)->exists();
    }

    // ========== CU 14 - REGISTRAR RESERVA ==========

    // RN-03: los cupos se retienen 20 minutos mientras la reserva está "En curso"
    public const MINUTOS_RETENCION = 20;
    // 19.1: con la seña, el saldo se abona dentro de las 24 horas
    public const MINUTOS_PAGO_TOTAL = 24 * 60;

    /**
     * CU 14 - paso 4. Valida la cantidad de participantes y la devuelve.
     *  4.1: tiene que ser un número entero mayor o igual a 1.
     */
    public static function validarCantidadParticipantes(array $datos): array
    {
        $cantidadInvalida = 'Ingresá una cantidad válida de participantes (al menos 1).';

        return Validator::make($datos, [
            'cantidad' => ['required', 'integer', 'min:1', 'max:1000'],
        ], [
            'cantidad.required' => $cantidadInvalida,
            'cantidad.integer' => $cantidadInvalida,
            'cantidad.min' => $cantidadInvalida,
            'cantidad.max' => 'La cantidad ingresada supera el cupo de cualquier salida.',
        ])->validate();
    }

    /**
     * CU 14 - 12.1, paso 2. Valida los elementos y cantidades elegidos del catálogo.
     * Una selección vacía equivale a continuar sin equipamiento.
     */
    public static function validarSeleccionEquipamiento(array $datos): array
    {
        return Validator::make($datos, [
            'equipamientos' => ['present', 'array'],
            'equipamientos.*.id' => ['required', 'integer', 'distinct', 'exists:equipamientos,id'],
            'equipamientos.*.cantidad' => ['required', 'integer', 'min:1'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'equipamientos.*.id.exists' => 'El elemento seleccionado no existe.',
            'equipamientos.*.id.distinct' => 'Hay elementos repetidos en la selección.',
            'equipamientos.*.cantidad.integer' => 'La cantidad tiene que ser un número entero.',
            'equipamientos.*.cantidad.min' => 'La cantidad tiene que ser al menos 1.',
        ])->validate()['equipamientos'];
    }

    /**
     * DS CU 14 paso 6: crearReserva(unCliente, unPaquete).
     * Paso 5: la reserva arranca "En curso", con la cantidad de cupos retenidos y sin pagos.
     * El importe se vuelve a calcular en el paso 13, con el equipamiento elegido.
     */
    public static function crearReserva(Cliente $cliente, Paquete $paquete, int $cantidadParticipantes): self
    {
        $reserva = new self([
            'cliente_id' => $cliente->id,
            'paquete_id' => $paquete->id,
            'cantidad_participantes' => $cantidadParticipantes,
            'fecha_hora_registro' => now(),
            'estado' => self::ESTADO_EN_CURSO,
            'situacion_de_pago' => self::PAGO_PENDIENTE_DE_PAGO_TOTAL,
        ]);
        $reserva->setRelation('paquete', $paquete);
        $reserva->importe_total = $reserva->calcularImporteTotal();
        $reserva->save();

        return $reserva;
    }

    /** DS CU 14 paso 12: setFechaHoraVencimiento(minutos), a partir del momento actual. */
    public function setFechaHoraVencimiento(int $minutos): void
    {
        $this->update(['fecha_hora_vencimiento' => now()->addMinutes($minutos)]);
    }

    /** DS CU 14 paso 30: temporizadorVigente(). Sin vencimiento, siempre está vigente. */
    public function temporizadorVigente(): bool
    {
        return $this->fecha_hora_vencimiento === null || now()->lt($this->fecha_hora_vencimiento);
    }

    public function estaEnCurso(): bool
    {
        return $this->estado === self::ESTADO_EN_CURSO;
    }

    /**
     * CU 14 - paso 8. Valida el formulario de participantes y devuelve los datos limpios.
     *  8.1: todos los datos son obligatorios.
     *  Uno por cada cupo retenido en el paso 5 y sin DNI repetidos.
     */
    public function validarDatosParticipantes(array $datos): array
    {
        $participantes = is_array($datos['participantes'] ?? null) ? $datos['participantes'] : [];

        // El DNI se guarda solo con números, igual que el del cliente (CU 12)
        foreach ($participantes as $i => $participante) {
            if (is_array($participante) && isset($participante['dni'])) {
                $participantes[$i]['dni'] = preg_replace('/\D/', '', (string) $participante['dni']);
            }
        }

        return Validator::make(['participantes' => $participantes], [
            'participantes' => ['required', 'array', 'size:'.$this->cantidad_participantes],
            'participantes.*.dni' => ['required', 'digits_between:7,8', 'distinct'],
            'participantes.*.nombre' => ['required', 'string', 'max:80'],
            'participantes.*.apellido' => ['required', 'string', 'max:80'],
            'participantes.*.fecha_nacimiento' => ['required', 'date_format:Y-m-d', 'before:today'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'participantes.required' => 'Cargá los datos de los participantes.',
            'participantes.size' => 'Cargá los datos de los :size participantes.',
            'participantes.*.dni.digits_between' => 'El DNI debe tener 7 u 8 números.',
            'participantes.*.dni.distinct' => 'Hay participantes con el mismo DNI.',
            'participantes.*.fecha_nacimiento.date_format' => 'La fecha ingresada no es válida.',
            'participantes.*.fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'max' => 'No puede superar los :max caracteres.',
        ])->validate()['participantes'];
    }

    /**
     * DS CU 14 pasos 15 y 16: agregarParticipante(dni, nombre, apellido, fechaNacimiento)
     *     -> «create» crearParticipante(...). La Reserva crea a sus participantes.
     * Paso 10: si la excursión requiere certificado médico, el requisito arranca "Pendiente"
     * (se cumple con los CU 17 y 19); si no, queda "No requerido".
     */
    public function agregarParticipante(string $dni, string $nombre, string $apellido, string $fechaNacimiento): Participante
    {
        return $this->participantes()->create([
            'dni' => $dni,
            'nombre' => trim($nombre),
            'apellido' => trim($apellido),
            'fecha_nacimiento' => $fechaNacimiento,
            'estado_requisito_medico' => $this->getSalidaProgramada()->getExcursion()->requiere_certificado_medico
                ? Participante::REQUISITO_PENDIENTE
                : Participante::REQUISITO_NO_REQUERIDO,
        ]);
    }

    /**
     * Si el cliente vuelve al formulario y lo confirma otra vez, la nueva carga
     * reemplaza a la anterior.
     */
    public function quitarParticipantes(): void
    {
        $this->participantes()->delete();
    }

    /**
     * DS CU 14 paso 18: participantesValidos(). Paso 9.
     *  9.1: si la excursión no acepta menores, todos tienen que ser mayores de edad.
     *  9.2 (RN-02): al menos un participante mayor de 18 años.
     * La edad se toma a la fecha de inicio de la salida.
     * Si algo falla, lanza una ValidationException que señala a los participantes.
     */
    public function participantesValidos(): bool
    {
        $salida = $this->getSalidaProgramada();
        $participantes = $this->participantes()->orderBy('id')->get();
        $menores = $participantes->reject(
            fn (Participante $participante) => $participante->esMayorDeEdadAl($salida->fecha_inicio));

        if (! $salida->getExcursion()->acepta_menores && $menores->isNotEmpty()) {
            throw ValidationException::withMessages([
                'participantes' => 'La excursión no acepta menores de edad. Corregí los participantes señalados.',
                ...$menores->mapWithKeys(fn (Participante $participante, int $i) => [
                    "participantes.{$i}.fecha_nacimiento" => 'No cumple la edad mínima de la excursión ('
                        .Participante::EDAD_ADULTO.' años).',
                ])->all(),
            ]);
        }

        if ($menores->count() === $participantes->count()) {
            throw ValidationException::withMessages([
                'participantes' => 'La reserva debe incluir al menos un participante mayor de edad (+'
                    .Participante::EDAD_ADULTO.' años).',
            ]);
        }

        return true;
    }

    /**
     * DS CU 14 (12.1) pasos 5 y 6: agregarEquipamiento(unEquipamiento, cantidad)
     *     -> «create» crear(reserva, unEquipamiento, cantidad) (EquipamientoAlquilado).
     * La cantidad queda retenida para el período de la salida: stockDisponible() la descuenta
     * mientras la reserva no se cancele, y se libera si vence (RN-04).
     */
    public function agregarEquipamiento(Equipamiento $equipamiento, int $cantidad): void
    {
        $this->equipos_alquilados()->attach($equipamiento->id, ['cantidad' => $cantidad]);
    }

    /**
     * Si el cliente modifica la selección de equipamiento, la nueva reemplaza a la anterior
     * (y libera lo que había retenido antes de verificar el stock de nuevo).
     */
    public function quitarEquipamientos(): void
    {
        $this->equipos_alquilados()->detach();
    }

    /**
     * DS CU 14 paso 26: calcularImporteTotal(). RN-05:
     *  (precio del paquete x cantidad de participantes) + Σ costos del equipamiento alquilado.
     */
    public function calcularImporteTotal(): float
    {
        return round(
            (float) $this->paquete->precio_calculado * $this->cantidad_participantes
                + $this->equipos_alquilados()->get()->sum(
                    fn (Equipamiento $equipamiento) => (float) $equipamiento->precio_alquiler * $equipamiento->pivot->cantidad),
            2,
        );
    }

    /** CU 14 - paso 14. Detalle del importe total que se le muestra al cliente. */
    public function detalleImporte(): array
    {
        return [
            'precio_paquete' => (float) $this->paquete->precio_calculado,
            'cantidad_participantes' => $this->cantidad_participantes,
            'equipamiento' => $this->equipos_alquilados()->orderBy('nombre')->get()
                ->map(fn (Equipamiento $equipamiento) => [
                    'id' => $equipamiento->id,
                    'nombre' => $equipamiento->nombre,
                    'cantidad' => $equipamiento->pivot->cantidad,
                    'subtotal' => round((float) $equipamiento->precio_alquiler * $equipamiento->pivot->cantidad, 2),
                ])->values(),
            'importe_total' => (float) $this->importe_total,
        ];
    }

    /**
     * DS CU 14 paso 32: confirmarRegistroReserva(). Se llama después del pago (CU 22),
     * que ya actualizó la situación de pago (paso 18).
     *  19.1: solo la seña -> la reserva vence a las 24 horas (el saldo lo controla el RF-17).
     *  19.2: pago completo -> sin vencimiento.
     *  20.1: queda "Pendiente". La 20.2 la resuelve confirmarSiCorresponde(), que el CU 22
     *        llama a continuación: con el pago completo y ningún requisito médico pendiente,
     *        la reserva queda "Confirmada".
     * Paso 21: los cupos, camas y equipamiento retenidos quedan consolidados con la reserva.
     */
    public function confirmarRegistroReserva(): void
    {
        $this->update([
            'estado' => self::ESTADO_PENDIENTE,
            'fecha_hora_registro' => now(),
            'fecha_hora_vencimiento' => null,
        ]);

        if ($this->situacion_de_pago === self::PAGO_PENDIENTE_DE_PAGO_TOTAL) {
            $this->setFechaHoraVencimiento(self::MINUTOS_PAGO_TOTAL);
        }
    }

    /**
     * CU 14 - 17.1 / *a (RN-04): la reserva en curso vence o el cliente cancela antes de pagar.
     * Libera los cupos retenidos de la salida y las camas del convenio (si el paquete tiene
     * alojamiento), y descarta la reserva con sus participantes y el equipamiento retenido
     * (rollback): nunca llegó a registrarse.
     */
    public function anularRegistro(): void
    {
        $salida = $this->getSalidaProgramada();

        $salida->liberarCupo($this->cantidad_participantes);
        $this->paquete->refugio?->liberarCamas($salida->fecha_inicio, $salida->fecha_fin, $this->cantidad_participantes);

        $this->delete();
    }
}
