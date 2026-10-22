<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Convenio extends Model
{
    protected $table = 'convenios';

    protected $fillable = [
        'refugio_id',
        'nombre_convenio',
        'fecha_inicio',
        'fecha_fin',
        'camas_totales',
        'camas_disponibles',
        'precio_cama',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'camas_totales' => 'integer',
        'camas_disponibles' => 'integer',
        'precio_cama' => 'decimal:2',
    ];
    // ========== RELACIONES ==========

    public function refugio(): BelongsTo {
        return $this->belongsTo(Refugio::class);
    }

    /**
     * Diagrama de clases: cubre(fechaInicio, fechaFin).
     * El convenio está vigente durante todo el período.
     */
    public function cubre(DateTimeInterface $fechaInicio, DateTimeInterface $fechaFin): bool
    {
        return $this->fecha_inicio->lte($fechaInicio) && $this->fecha_fin->gte($fechaFin);
    }

    /**
     * CU 16 - 6.1. Devuelve camas comprometidas, sin superar las camas totales.
     * Retorna cuántas pudo devolver.
     */
    public function liberarCamas(int $cantidad): int
    {
        $liberadas = min($cantidad, $this->camas_totales - $this->camas_disponibles);
        $this->update(['camas_disponibles' => $this->camas_disponibles + $liberadas]);

        return $liberadas;
    }
}