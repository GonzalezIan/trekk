<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Abstract\Persona;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

    // ========== CU 12 - REGISTRAR CLIENTE ==========

    public const ROL_CLIENTE = 'cliente';
    public const EDAD_MINIMA = 18;

    /**
     * CU 12 / RF-12: crea la cuenta (usuario con rol cliente) y los datos personales, todo o nada.
     * users.name queda como "Nombre Apellido" para mostrar en el menú.
     */
    public static function registrar(array $datos): self
    {
        return DB::transaction(function () use ($datos) {
            $user = User::create([
                'name' => trim($datos['nombre']) . ' ' . trim($datos['apellido']),
                'usuario' => $datos['usuario'],
                'email' => $datos['email'],
                'password' => Hash::make($datos['password']),
                'rol' => self::ROL_CLIENTE,
            ]);

            return self::create([
                'user_id' => $user->id,
                ...Arr::only($datos, ['dni', 'nombre', 'apellido', 'fecha_nacimiento', 'telefono']),
            ]);
        });
    }
}