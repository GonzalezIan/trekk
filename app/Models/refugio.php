<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Refugio extends Model
{
    use HasFactory;
    protected $table = 'refugios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'ubicacion',
    ];

    // ========== RELACIONES ==========

    public function convenios(): HasMany
    {
        return $this->hasMany(Convenio::class);
    }

    
    public function paquetes(): HasMany
    {
        return $this->hasMany(Paquete::class);
    }
}