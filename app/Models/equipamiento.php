<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Equipamiento extends Model
{
    use HasFactory;
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

    // =========== RELACIONES ==========

    public function salidasProgramadas():BelongsToMany{
    return $this->belongsToMany(SalidaProgramada::class,'equipos_prestados')->withPivot('cantidad');
    }
    public function reservas():BelongsToMany{
        return $this->belongsToMany(Reserva::class,'equipos_alquilados')->withPivot('cantidad');
    }
    
}
