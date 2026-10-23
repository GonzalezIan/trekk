<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

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

    // ========== CU 5 - REGISTRAR PAQUETE ==========

    /**
     * DS CU 5: crear(nombre, descripcion, servicios, ref).
     * RN-04: el precio se calcula antes de registrarlo. RN-10: queda DISPONIBLE.
     * RN-03: servicios y refugio son opcionales (colección vacía / null).
     */
    public static function crear(SalidaProgramada $salida, string $nombre, string $descripcion, Collection $servicios, ?Refugio $refugio): self
    {
        $paquete = new self([
            'salida_programada_id' => $salida->id,
            'refugio_id' => $refugio?->id,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'estado' => self::ESTADO_DISPONIBLE,
        ]);
        $paquete->setRelation('salidaProgramada', $salida);
        $paquete->setRelation('refugio', $refugio);
        $paquete->setRelation('servicios', $servicios);

        $paquete->precio_calculado = $paquete->calcularPrecio();
        $paquete->save();

        // ServicioIncluido: cada servicio se incluye una vez
        $paquete->servicios()->attach($servicios->pluck('id'));

        return $paquete;
    }

    /**
     * DS CU 5: calcularPrecio(). RN-04:
     *  precio base de la salida + suma del precio de los servicios
     *  + (precio por cama del convenio del refugio x noches de la salida), si tiene alojamiento.
     */
    public function calcularPrecio(): float
    {
        $salida = $this->salidaProgramada;

        $servicios = $this->servicios->sum(fn (Servicio $servicio) =>
            (float) $servicio->precio_servicio * ($servicio->pivot?->cantidad ?? 1));

        $alojamiento = $this->refugio
            ? $this->refugio->getPrecioCama($salida->fecha_inicio, $salida->fecha_fin) * $salida->cantidadNoches()
            : 0;

        return round((float) $salida->precio_base + $servicios + $alojamiento, 2);
    }
}