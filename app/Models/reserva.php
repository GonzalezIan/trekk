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

class Reserva extends Model
{
    use HasFactory;
    protected $table = 'reservas';

    protected $fillable = [
        'cliente_id',
        'paquete_id',
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
}
