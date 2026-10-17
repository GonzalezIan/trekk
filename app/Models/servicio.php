<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Servicio extends Model
{
    protected $table='servicios';

    protected $fillable = [
    'nombre',
    'descripcion',
    'precio_servicio',
    ];

    protected $casts = [
        'precio_servicio' => 'decimal:2',
    ];

    // =========== REALCIONES ==========

    public function servicios():BelongsToMany{
        return $this->belongsToMany(Servicio::class,'servicios_incluidos')->withPivot('cantidad');
    }
}
