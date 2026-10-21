<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Paquete extends Model
{
    use HasFactory;
    protected $table = 'paquetes';

    protected $fillable = [
        'salida_programada_id',
        'refugio_id',
        'nombre',
        'descripcion',
        'precio_calculado',
        'estado',
    ];

    protected $casts = [
        'precio_calculado' => 'decimal:2',
        'estado' => 'string',
    ];

    // ========== RELACIONES ==========

    public function salidaProgramada(): BelongsTo
    {
        return $this->belongsTo(SalidaProgramada::class);
    }

    public function refugio(): BelongsTo
    {
        return $this->belongsTo(Refugio::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'servicios_incluidos')
                    ->withPivot('cantidad');
    }

    // ========== CU 7 - LISTAR PAQUETES ==========

    // Estados según el diagrama de clases
    public const ESTADO_DISPONIBLE = 'DISPONIBLE';
    public const ESTADO_CANCELADO = 'CANCELADO';

    // Reservas que siguen vigentes (no canceladas, realizadas ni en curso de registro)
    public const ESTADOS_RESERVA_ACTIVA = ['PENDIENTE', 'CONFIRMADA'];

    /** DS CU 7 (Cliente): estaDisponible(). */
    public function estaDisponible(): bool
    {
        return $this->estado === self::ESTADO_DISPONIBLE;
    }

    /** DS CU 7: getDatos(). Datos del paquete para el listado. */
    public function getDatos(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'refugio' => $this->refugio?->nombre,
            'servicios' => $this->servicios
                ->map(fn ($servicio) => $servicio->nombre . ' x' . $servicio->pivot->cantidad)
                ->values(),
            'precio' => $this->precio_calculado,
            'estado' => $this->estado,
        ];
    }

    /**
     * DS CU 7 (Cliente): getDisponibilidad().
     * Cupo disponible de la salida y, si el paquete tiene refugio, las camas
     * disponibles para las fechas de la salida. Los lugares son el menor de los dos.
     */
    public function getDisponibilidad(): array
    {
        $salida = $this->salidaProgramada;
        $cupoDisponible = $salida->getCupoDisponible();

        $camasDisponibles = $this->refugio?->getCamasDisponibles($salida->fecha_inicio, $salida->fecha_fin);

        return [
            'cupo_disponible' => $cupoDisponible,
            'camas_disponibles' => $camasDisponibles,
            'lugares_disponibles' => $camasDisponibles === null
                ? $cupoDisponible
                : min($cupoDisponible, $camasDisponibles),
        ];
    }

    /** DS CU 7 (Administrativo): cantidadReservasActivas(). */
    public function cantidadReservasActivas(): int
    {
        return $this->reservas()->whereIn('estado', self::ESTADOS_RESERVA_ACTIVA)->count();
    }
}