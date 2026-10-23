<?php

namespace App\Models;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Refugio extends Model
{
    use HasFactory;
    protected $table = 'refugios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'ubicacion',
    ];

    // ========== RELACIONES ==========

    public function convenios(): HasMany
    {
        return $this->hasMany(Convenio::class);
    }

    
    public function paquetes(): HasMany
    {
        return $this->hasMany(Paquete::class);
    }

    /**
     * DS CU 7: getCamasDisponibles(fechaInicio, fechaFin).
     * Suma las camas disponibles de los convenios vigentes durante todo el período.
     */
    public function getCamasDisponibles(DateTimeInterface $fechaInicio, DateTimeInterface $fechaFin): int
    {
        return $this->convenios
            ->filter(fn (Convenio $convenio) => $convenio->fecha_inicio->lte($fechaInicio)
                && $convenio->fecha_fin->gte($fechaFin))
            ->sum('camas_disponibles');
    }

    /**
     * CU 16 - 6.1. Libera las camas comprometidas por una reserva, sumándolas a
     * las camas disponibles de los convenios que cubren el período de la salida.
     */
    public function liberarCamas(DateTimeInterface $fechaInicio, DateTimeInterface $fechaFin, int $cantidad): void
    {
        foreach ($this->convenios as $convenio) {
            if ($cantidad > 0 && $convenio->cubre($fechaInicio, $fechaFin)) {
                $cantidad -= $convenio->liberarCamas($cantidad);
            }
        }
    }

    // ========== CU 26 - LISTAR REFUGIOS (desde el CU 5) ==========

    /**
     * DS CU 26: listarAdmisibles(fechaInicio, fechaFin).
     * Alternativo 3.2 del CU 5: refugios con un convenio que cubre todo el período y
     * sin otro paquete DISPONIBLE en una salida PROGRAMADA superpuesta.
     */
    public static function listarAdmisibles(DateTimeInterface $fechaInicio, DateTimeInterface $fechaFin): Collection
    {
        return self::with('convenios')
            ->orderBy('nombre')
            ->get()
            ->filter(fn (self $refugio) => $refugio->esAdmisible($fechaInicio, $fechaFin))
            ->map(fn (self $refugio) => $refugio->getDatos($fechaInicio, $fechaFin))
            ->values();
    }

    /**
     * CU 26 - paso 3. Nombre, descripción, ubicación, camas totales, camas disponibles,
     * precio por cama y período de vigencia del convenio que cubre el período.
     */
    public function getDatos(DateTimeInterface $fechaInicio, DateTimeInterface $fechaFin): array
    {
        $convenio = $this->convenioQueCubre($fechaInicio, $fechaFin);

        return [
            ...$this->only(['id', 'nombre', 'descripcion', 'ubicacion']),
            'precio_cama' => $this->getPrecioCama($fechaInicio, $fechaFin),
            'camas_totales' => $this->getCamasTotales($fechaInicio, $fechaFin),
            'camas_disponibles' => $this->getCamasDisponibles($fechaInicio, $fechaFin),
            'vigencia_desde' => $convenio?->fecha_inicio->format('Y-m-d'),
            'vigencia_hasta' => $convenio?->fecha_fin->format('Y-m-d'),
        ];
    }

    /** CU 26 - paso 3. Camas totales de los convenios vigentes durante todo el período. */
    public function getCamasTotales(DateTimeInterface $fechaInicio, DateTimeInterface $fechaFin): int
    {
        return $this->convenios
            ->filter(fn (Convenio $convenio) => $convenio->cubre($fechaInicio, $fechaFin))
            ->sum('camas_totales');
    }

    /** DS CU 5: buscar(idRefugio). */
    public static function buscar(int $idRefugio): self
    {
        return self::findOrFail($idRefugio);
    }

    /** CU 5 - 3.2: ¿se puede asociar a un paquete de una salida en ese período? */
    public function esAdmisible(DateTimeInterface $fechaInicio, DateTimeInterface $fechaFin): bool
    {
        return $this->convenioQueCubre($fechaInicio, $fechaFin) !== null
            && ! $this->tienePaqueteSolapado($fechaInicio, $fechaFin);
    }

    /** DS CU 5: getPrecioCama(fechaInicio, fechaFin). Precio del convenio que cubre el período. */
    public function getPrecioCama(DateTimeInterface $fechaInicio, DateTimeInterface $fechaFin): float
    {
        return (float) $this->convenioQueCubre($fechaInicio, $fechaFin)?->precio_cama;
    }

    /** Si hay más de un convenio que cubre el período, se toma el que empezó primero. */
    private function convenioQueCubre(DateTimeInterface $fechaInicio, DateTimeInterface $fechaFin): ?Convenio
    {
        return $this->convenios
            ->sortBy('fecha_inicio')
            ->first(fn (Convenio $convenio) => $convenio->cubre($fechaInicio, $fechaFin));
    }

    /** DS CU 5: tienePaqueteSolapado(fechaInicio, fechaFin). */
    private function tienePaqueteSolapado(DateTimeInterface $fechaInicio, DateTimeInterface $fechaFin): bool
    {
        return $this->paquetes()
            ->where('estado', Paquete::ESTADO_DISPONIBLE)
            ->whereHas('salidaProgramada', fn ($consulta) => $consulta->superpuestasCon(
                $fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')))
            ->exists();
    }
}