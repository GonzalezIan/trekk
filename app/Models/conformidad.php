<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conformidad extends Model
{
    protected $table = 'conformidades';

    protected $fillable = [
        'reserva_id',
        'valoracion',
        'comentario',
    ];

    protected $casts = [
        'valoracion' => 'integer',
        'comentario' => 'string',
    ];

    // =========== RELACIONES ==========

    public function reserva():BelongsTo{
        return $this->belongsTo(Reserva::class);
    }
}
