<?php

namespace App\Models;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}