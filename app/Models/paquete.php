<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class paquete extends Model
{
    protected $table = 'paquetes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_calculado',
        'estado',
        'salida_programada_id',
        'refugio_id',
    ];

    protected $casts = [
        'precio_calculado' => 'decimal:2',
        'estado' => 'string',
    ];
}
