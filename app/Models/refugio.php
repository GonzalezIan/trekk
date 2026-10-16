<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refugio extends Model
{
protected $table = 'refugios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'ubicacion',
        'capacidad_total',
        'precio_base',
    ];

    protected $casts = [
        'capacidad_total' => 'integer',
        'precio_base' => 'decimal:2',
    ];
}
