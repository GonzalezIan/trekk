<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipamiento extends Model
{
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
}
