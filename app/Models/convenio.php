<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class convenio extends Model
{
    protected $table = 'convenios';

    protected $fillable = [
        'nombre',
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
}
