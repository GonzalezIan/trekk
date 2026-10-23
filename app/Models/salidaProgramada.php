<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SalidaProgramada extends Model
{
    use HasFactory;
    protected $table = 'salidas_programadas';

    protected $fillable = [
        'excursion_id',
        'fecha_inicio',
        'hora_de_salida',
        'fecha_fin',
        'cupo_maximo',
        'cupo_disponible',
        'precio_base',
        'estado',
        'motivo_cancelacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'hora_de_salida' => 'datetime:H:i',
        'fecha_fin' => 'date',
        'cupo_maximo' => 'integer',
        'cupo_disponible' => 'integer',
        'precio_base' => 'decimal:2',
        'estado' => 'string',
    ];

    // ========== RELACIONES ==========

    public function excursion():BelongsTo{
        return $this->belongsTo(Excursion::class);
    }

    public function empleados():BelongsToMany{
        return $this->belongsToMany(Empleado::class,'empleados_asignados')->withPivot('rol');
    }


    public function paquetes():HasMany{
        return $this->hasMany(Paquete::class);
    }

    public function equipoPrestado():BelongsToMany{
        return $this->belongsToMany(Equipamiento::class,'equipos_prestados')->withPivot('cantidad');
    }

        // ========== CU 9 - LISTAR SALIDAS PROGRAMADAS ==========

    public const ESTADO_PROGRAMADA = 'PROGRAMADA';
    public const ESTADOS = ['PROGRAMADA', 'CANCELADA', 'FINALIZADA'];
    public const HORAS_MINIMAS_ANTICIPACION = 48;

    /**
     * CU 9 (Administrativo) - DS: listar(filtros).
     * RN-02: cualquier estado. Filtros opcionales: excursion, estado, desde, hasta.
     */
    public static function listar(array $filtros): Collection
    {
        return self::query()
            ->with(['excursion:id,nombre', 'empleados', 'equipoPrestado'])
            ->withCount('paquetes')
            ->when($filtros['excursion'] ?? null,
                fn ($consulta, $idExcursion) => $consulta->where('excursion_id', $idExcursion))
            ->when($filtros['estado'] ?? null,
                fn ($consulta, $estado) => $consulta->where('estado', $estado))
            ->when($filtros['desde'] ?? null,
                fn ($consulta, $desde) => $consulta->whereDate('fecha_inicio', '>=', $desde))
            ->when($filtros['hasta'] ?? null,
                fn ($consulta, $hasta) => $consulta->whereDate('fecha_inicio', '<=', $hasta))
            ->orderBy('fecha_inicio')
            ->orderBy('hora_de_salida')
            ->get()
            ->map(fn (self $salida) => $salida->getDatos());
    }

    /**
     * CU 9 (Cliente) - DS: esReservable().
     * RN-01: está PROGRAMADA y faltan más de 48 hs para la salida (fecha + hora).
     */
    public function esReservable(): bool
    {
        return $this->estado === self::ESTADO_PROGRAMADA
            && $this->momentoDeSalida()->greaterThan(now()->addHours(self::HORAS_MINIMAS_ANTICIPACION));
    }

    /** CU 9 - DS: getDatos(). Datos de la salida para el listado (4.1 / 4.2). */
    public function getDatos(): array
    {
        return [
            'id' => $this->id,
            'excursion' => $this->excursion->nombre,
            'fecha_inicio' => $this->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $this->fecha_fin->format('Y-m-d'),
            'hora_de_salida' => $this->hora_de_salida->format('H:i'),
            'guia' => $this->nombresDeEmpleados('GUIA')->first(),
            'asistentes' => $this->nombresDeEmpleados('ASISTENTE'),
            'equipamiento' => $this->equipoPrestado
                ->map(fn ($equipo) => $equipo->nombre . ' x' . $equipo->pivot->cantidad),
            'cupo_maximo' => $this->cupo_maximo,
            'cupo_disponible' => $this->cupo_disponible,
            'precio_base' => $this->precio_base,
            'cantidad_paquetes' => $this->paquetes_count,
            'estado' => $this->estado,
        ];
    }
    // ========== CU 7 - LISTAR PAQUETES ==========

    /** DS CU 7: buscar(idSalida). */
    public static function buscar(int $idSalida): self
    {
        return self::findOrFail($idSalida);
    }

    /**
     * DS CU 7 (Administrativo): getPaquetes().
     * Todos los paquetes, cualquiera sea su estado, con la cantidad de reservas activas.
     */
    public function getPaquetes(): Collection
    {
        return $this->paquetes()
            ->with(['refugio:id,nombre', 'servicios'])
            ->orderBy('nombre')
            ->get()
            ->map(fn (Paquete $paquete) => [
                ...$paquete->getDatos(),
                'reservas_activas' => $paquete->cantidadReservasActivas(),
            ]);
    }

    /**
     * DS CU 7 (Cliente): getPaquetesDisponibles().
     * Solo los paquetes en estado DISPONIBLE, con su precio y disponibilidad.
     */
    public function getPaquetesDisponibles(): Collection
    {
        return $this->paquetes()
            ->with(['refugio.convenios', 'servicios'])
            ->orderBy('nombre')
            ->get()
            ->filter(fn (Paquete $paquete) => $paquete->estaDisponible())
            ->each(fn (Paquete $paquete) => $paquete->setRelation('salidaProgramada', $this))
            ->map(fn (Paquete $paquete) => [
                ...$paquete->getDatos(),
                ...$paquete->getDisponibilidad(),
            ])
            ->values();
    }

    /** DS CU 7: getCupoDisponible(). */
    public function getCupoDisponible(): int
    {
        return $this->cupo_disponible;
    }

    // ========== CU 16 - CANCELAR RESERVA ==========

    /**
     * DS CU 16: liberarCupo(cantidadParticipantes).
     * Suma la cantidad liberada a su propio cupo disponible, sin superar el cupo máximo.
     */
    public function liberarCupo(int $cantidad): void
    {
        $this->update([
            'cupo_disponible' => min($this->cupo_maximo, $this->cupo_disponible + $cantidad),
        ]);
    }

     // ========== CU 2 - BAJA DE EXCURSIÓN ==========

    public const ESTADO_CANCELADA = 'CANCELADA';

    /**
     * Cancela la salida (guardando el motivo) y sus paquetes que seguían disponibles.
     * La usa la baja de la excursión (CU 2).
     */
    public function cancelar(string $motivo): void
    {
        $this->update([
            'estado' => self::ESTADO_CANCELADA,
            'motivo_cancelacion' => $motivo,
        ]);

        $this->paquetes()
            ->where('estado', Paquete::ESTADO_DISPONIBLE)
            ->update(['estado' => Paquete::ESTADO_CANCELADO]);
    }

    /** Fecha de inicio + hora de salida, en un solo momento. */
    private function momentoDeSalida(): Carbon
    {
        return $this->fecha_inicio->copy()->setTimeFrom($this->hora_de_salida);
    }

    /** "Nombre Apellido" de los empleados asignados con ese rol (GUIA / ASISTENTE). */
    private function nombresDeEmpleados(string $rol): Collection
    {
        return $this->empleados
            ->filter(fn ($empleado) => $empleado->pivot->rol === $rol)
            ->map(fn ($empleado) => $empleado->nombre . ' ' . $empleado->apellido)
            ->values();
    }

}
