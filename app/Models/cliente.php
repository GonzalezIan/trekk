<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Abstract\Persona;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Persona
{
    use HasFactory;
    protected $table = 'clientes';

    protected $fillable = [
        'user_id',
        'dni',
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'telefono',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }
}