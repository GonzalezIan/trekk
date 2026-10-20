<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        ];
    }

    /**
     * Indica si la reserva está a nombre del cliente del usuario autenticado.
     */
    public function perteneceA(User $usuario): bool
    {
        return $this->cliente->user_id === $usuario->id;
    }

    /**
     * CU 21 - RN-01. Solo se puede cargar un certificado médico si faltan
     * más de 24 horas para el inicio de la Salida Programada.
     */
    public function faltanMasDe24HorasParaLaSalida(): bool
    {
        $salida = $this->paquete->salidaProgramada;
        $inicio = $salida->fecha_inicio->copy()->setTimeFrom($salida->hora_de_salida);

        return now()->addHours(24)->lt($inicio);
    }
}
