<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class equipamiento extends Model
{
    protected $table = 'equipamientos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_alquiler',
        'stock_total',
    ];

    protected $casts = [
        'precio_alquiler' => 'decimal:2',
        'stock_total' => 'integer',
    ];
}
