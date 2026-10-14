<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class conformidad extends Model
{
    protected $table = 'conformidades';

    protected $fillable = [
        'reserva_id',
        'cliente_id',
        'valoracion',
        'comentario',
    ];

    protected $casts = [
        'valoracion' => 'integer',
        'comentario' => 'string',
    ];
}
