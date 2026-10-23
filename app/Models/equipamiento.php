<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Equipamiento extends Model
{
    use HasFactory;
    protected $table = 'equipamientos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_alquiler',
        'stock',
    ];

    protected $casts = [
        'precio_alquiler' => 'decimal:2',
        'stock' => 'integer',
    ];

    // =========== RELACIONES ==========

    public function salidasProgramadas():BelongsToMany{
    return $this->belongsToMany(SalidaProgramada::class,'equipos_prestados')->withPivot('cantidad');
    }
    public function reservas():BelongsToMany{
        return $this->belongsToMany(Reserva::class,'equipos_alquilados')->withPivot('cantidad');
    }

    // ========== CU 25 - LISTAR EQUIPAMIENTOS (perfil Administrativo, desde el CU 8) ==========

    /**
     * DS CU 25 (Administrativo): listarConStock(fechaInicio, fechaFin).
     * Elementos con stock disponible mayor a cero en el período (CU 8 - RN-06).
     */
    public static function listarConStock(string $fechaInicio, string $fechaFin): Collection
    {
        return self::query()
            ->orderBy('nombre')
            ->get()
            ->map(fn (self $equipamiento) => [
                ...$equipamiento->only(['id', 'nombre', 'descripcion', 'precio_alquiler']),
                'stock_disponible' => $equipamiento->stockDisponible($fechaInicio, $fechaFin),
            ])
            ->filter(fn (array $equipamiento) => $equipamiento['stock_disponible'] > 0)
            ->values();
    }

    /**
     * DS CU 25 / DS CU 8: stockDisponible(fechaInicio, fechaFin).
     * Stock total menos lo comprometido en el período: lo incluido en otras salidas
     * PROGRAMADAS superpuestas y lo alquilado en reservas no canceladas de esas salidas.
     */
    public function stockDisponible(string $fechaInicio, string $fechaFin): int
    {
        $incluido = $this->salidasProgramadas()
            ->superpuestasCon($fechaInicio, $fechaFin)
            ->sum('equipos_prestados.cantidad');

        $alquilado = $this->reservas()
            ->where('reservas.estado', '!=', Reserva::ESTADO_CANCELADA)
            ->whereHas('paquete.salidaProgramada',
                fn ($consulta) => $consulta->superpuestasCon($fechaInicio, $fechaFin))
            ->sum('equipos_alquilados.cantidad');

        return max(0, $this->stock - (int) $incluido - (int) $alquilado);
    }

    /** DS CU 8: buscar(idsEquipamiento). */
    public static function buscar(array $idsEquipamiento): Collection
    {
        return self::findOrFail($idsEquipamiento);
    }
}
