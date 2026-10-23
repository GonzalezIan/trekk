<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Servicio extends Model
{
    use HasFactory;
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

    // ========== CU 24 - LISTAR SERVICIOS (desde el CU 5) ==========

    /** DS CU 24: listar(). Servicios registrados con su precio. */
    public static function listar(): Collection
    {
        return self::orderBy('nombre')->get(['id', 'nombre', 'descripcion', 'precio_servicio']);
    }

    /** DS CU 5: buscar(idsServicios). */
    public static function buscar(array $idsServicios): Collection
    {
        return self::findOrFail($idsServicios);
    }
}
