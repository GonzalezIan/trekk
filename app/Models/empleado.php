<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Abstract\Persona;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Empleado extends Persona
{
    use HasFactory;
    protected $table = 'empleados';

    protected $fillable = [
        'dni',
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'categoria',
        'estado',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'categoria' => 'string',
        'estado' => 'string',
    ];

    // ========== RELACIONES ==========

    public function SalidasProgramadas(): BelongsToMany{
        return $this->belongsToMany(SalidaProgramada::class,'empleados_asignados')
        ->withPivot('rol');
    }

    // ========== CU 23 - LISTAR EMPLEADOS (invocado desde el CU 8) ==========

    public const CATEGORIA_GUIA = 'GUIA';
    public const CATEGORIA_ASISTENTE = 'ASISTENTE';
    public const CATEGORIAS = [self::CATEGORIA_GUIA, self::CATEGORIA_ASISTENTE];
    public const ESTADO_ACTIVO = 'ACTIVO';

    /**
     * DS CU 23 / DS CU 8: listarDisponibles(categoria, fechaInicio, fechaFin).
     * RN-01: solo empleados ACTIVOS. RN-02: sin otra salida superpuesta en el período.
     */
    public static function listarDisponibles(string $categoria, string $fechaInicio, string $fechaFin, ?int $idSalidaExcluida = null): Collection
    {
        return self::query()
            ->where('categoria', $categoria)
            ->where('estado', self::ESTADO_ACTIVO)
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get()
            ->filter(fn (self $empleado) => $empleado->estaDisponible($fechaInicio, $fechaFin, $idSalidaExcluida))
            ->map(fn (self $empleado) => $empleado->getDatos())
            ->values();
    }

    /** DS CU 23: estaDisponible(fechaInicio, fechaFin). No tiene otra salida PROGRAMADA superpuesta. */
    public function estaDisponible(string $fechaInicio, string $fechaFin, ?int $idSalidaExcluida = null): bool
    {
        return ! $this->SalidasProgramadas()
            ->superpuestasCon($fechaInicio, $fechaFin, $idSalidaExcluida)
            ->exists();
    }

    /**
     * CU 8 - paso 7: se vuelve a verificar al confirmar, por si el empleado
     * fue asignado a otra salida mientras se completaba el registro.
     */
    public function esAsignable(string $categoria, string $fechaInicio, string $fechaFin, ?int $idSalidaExcluida = null): bool
    {
        return $this->categoria === $categoria
            && $this->estado === self::ESTADO_ACTIVO
            && $this->estaDisponible($fechaInicio, $fechaFin, $idSalidaExcluida);
    }

    /** DS CU 8: buscar(idEmpleado) / buscar(idsEmpleados). */
    public static function buscar(int|array $idEmpleados): self|Collection
    {
        return self::findOrFail($idEmpleados);
    }

    /** CU 23 - paso 2: Nombre, Apellido, DNI, categoría y estado. */
    public function getDatos(): array
    {
        return $this->only(['id', 'nombre', 'apellido', 'dni', 'categoria', 'estado']);
    }
}
