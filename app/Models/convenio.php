<?php

namespace App\Models;

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
}