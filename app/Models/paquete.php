<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Paquete extends Model
{
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
        return $this->belongsToMany(Servicio::class, 'paquete_servicio')
                    ->withPivot('cantidad')
                    ->withTimestamps();
    }
}