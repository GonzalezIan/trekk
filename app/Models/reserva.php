<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = [
        'cliente_id',
        'paquete_id',
        'fecha_hora_registro',
        'fecha_hora_vencimiento',
        'estado',
        'situacion_de_pago',
        'importe_total',
        'motivo_cancelacion',
    ];

    protected $casts = [
        'fecha_hora_registro' => 'datetime',
        'fecha_hora_vencimiento' => 'datetime',
        'importe_total' => 'decimal:2',
        'estado' => 'string',
        'situacion_de_pago' => 'string',
    ];

    // =========== RELACIONES ==========

    public function cliente():BelongsTo{
        return $this->belongsTo(Cliente::class);
    }
    
    public function paquete():BelongsTo{
        return $this->belongsTo(Paquete::class);
    }

    public function participantes():HasMany{
        return $this->hasMany(Participante::class);
    }

    public function pagos():HasMany{
        return $this->hasMany(Pago::class);
    }

    public function equipos_alquilados():BelongsToMany{
        return $this->belongsToMany(Equipamiento::class,'equipos_aqluilados')->withPivot('cantidad');
    }

    public function conformidad():HasOne{
        return $this->hasOne(Conformidad::class);
    }
}
