<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class Excursion extends Model
{
    use HasFactory;
    protected $table = 'excursiones';

    protected $fillable = [
        'nombre',
        'descripcion',
        'dificultad',
        'duracion',
        'recorrido',
        'etapas',
        'experiencia_requerida',
        'acepta_menores',
        'equipamiento_obligatorio',
        'requiere_certificado_medico',
        'estado',
    ];

    protected $casts = [
        'dificultad' => 'string',
        'duracion' => 'integer',
        'experiencia_requerida' => 'string',
        'acepta_menores' => 'boolean',
        'requiere_certificado_medico' => 'boolean',
        'estado' => 'string',
    ];
    // ========== RELACIONES ==========
    
    public function salidasProgramadas():HasMany{
        return $this->hasMany(SalidaProgramada::class);
    }

    /** DS CU 9 (Cliente): buscar(idExcursion). */
    public static function buscar(int $idExcursion): self
    {
        return self::findOrFail($idExcursion);
    }

    /**
     * DS CU 9 (Cliente): getSalidasReservables().
     * Recorre las salidas de la excursión y devuelve los datos de las reservables.
     */
    public function getSalidasReservables(): Collection
    {
        return $this->salidasProgramadas()
            ->with(['excursion:id,nombre', 'empleados', 'equipoPrestado'])
            ->withCount('paquetes')
            ->orderBy('fecha_inicio')
            ->orderBy('hora_de_salida')
            ->get()
            ->filter(fn (SalidaProgramada $salida) => $salida->esReservable())
            ->map(fn (SalidaProgramada $salida) => $salida->getDatos())
            ->values();
    }

    /**
     * DS CU 14 paso 22: getEquipamientoObligatorio(). Texto informativo que carga el
     * administrativo (CU 1): no está vinculado al catálogo de alquiler.
     */
    public function getEquipamientoObligatorio(): ?string
    {
        return $this->equipamiento_obligatorio;
    }

    // Estados y dificultades según el diagrama de clases
    public const ESTADO_ACTIVA = 'ACTIVA';
    public const ESTADO_INACTIVA = 'INACTIVA';
    public const DIFICULTADES = ['BAJA', 'MEDIO', 'ALTA'];

     /**
     * CU 1 - paso 4. Valida los datos del alta y devuelve los datos limpios.
     * Si algo falla, lanza una ValidationException y Laravel vuelve al formulario.
     *  RN-01: todo es obligatorio salvo el equipamiento obligatorio.
     *  RN-02: la duración no puede ser menor a un día.
     *  RN-03: el nombre debe ser único.
     */
    public static function validarAlta(array $datosExcursion): array
    {
        return self::validar($datosExcursion);
    }

    /**
     * CU 3 - paso 5. Mismas reglas que el alta, pero el nombre actual
     * de la excursión no cuenta como repetido (flujo alternativo 5.1).
     */
    public function validarModificacion(array $datosExcursion): array
    {
        return self::validar($datosExcursion, $this->id);
    }

    /** Reglas comunes al alta (CU 1) y a la modificación (CU 3). */
    private static function validar(array $datosExcursion, ?int $idExcluido = null): array
    {
        return Validator::make($datosExcursion, [
            'nombre' => [
                'required', 'string', 'max:120',
                function ($atributo, $valor, $fallar) use ($idExcluido) {
                    if (self::existeNombre($valor, $idExcluido)) {
                        $fallar('Ya existe una excursión registrada con ese nombre.');
                    }
                },
            ],
            'descripcion' => ['required', 'string'],
            'recorrido' => ['required', 'string'],
            'etapas' => ['required', 'string'],
            'dificultad' => ['required', Rule::in(self::DIFICULTADES)],
            'duracion' => ['required', 'integer', 'min:1', 'max:365'],
            'experiencia_requerida' => ['required', Rule::in(['SI', 'NO'])],
            'acepta_menores' => ['required', 'boolean'],
            'equipamiento_obligatorio' => ['nullable', 'string'],
            'requiere_certificado_medico' => ['required', 'boolean'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'duracion.integer' => 'La duración debe ser un número entero de días.',
            'duracion.min' => 'La duración de la excursión debe ser de al menos un día.',
            'duracion.max' => 'La duración no puede superar los 365 días.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
            'in' => 'El valor seleccionado no es válido.',
        ])->validate();
    }

    /**
     * RN-03: ¿ya hay otra excursión registrada con ese nombre? (sin distinguir mayúsculas)
     */
    private static function existeNombre(?string $nombre, ?int $idExcluido = null): bool
    {
        return self::whereRaw('LOWER(nombre) = ?', [mb_strtolower(trim((string) $nombre))])
            ->when($idExcluido, fn ($consulta) => $consulta->where('id', '!=', $idExcluido))
            ->exists();
    }

    /**
     * CU 1 - paso 5. Registra la excursión en estado ACTIVA.
     */
    public static function crear(array $datosExcursion): self
    {
        return self::create([
            ...$datosExcursion,
            'nombre' => trim($datosExcursion['nombre']),
            'estado' => self::ESTADO_ACTIVA,
        ]);
    }

    // ========== CU 3 - MODIFICAR EXCURSIÓN y CU 2 - BAJA DE EXCURSIÓN >:3 ==========
 
    public const MOTIVO_BAJA = 'Baja de la excursión';

    /** CU 3 (RN-03) y CU 2: solo se opera sobre excursiones activas. */
    public function estaActiva(): bool
    {
        return $this->estado === self::ESTADO_ACTIVA;
    }

    /**
     * CU 3 (RN-01) y CU 2: ¿alguna de sus salidas tiene paquetes con reservas activas
     * (PENDIENTE o CONFIRMADA) 7u7?
     */
    public function tieneReservasActivas(): bool
    {
        return Reserva::whereIn('estado', Paquete::ESTADOS_RESERVA_ACTIVA)
            ->whereHas('paquete.salidaProgramada',
                fn ($consulta) => $consulta->where('excursion_id', $this->id))
            ->exists();
    }

    /** CU 3 - paso 6. Guarda los datos actualizados de la excursión uwu. */
    public function modificar(array $datosExcursion): void
    {
        $this->update([
            ...$datosExcursion,
            'nombre' => trim($datosExcursion['nombre']),
        ]);
    }

    /**
     * CU 2. La excursión pasa a INACTIVA y se cancelan sus salidas PROGRAMADAS
     * junto con los paquetes de esas salidas. Todo o nada (transacción) owo.
     */
    public function darDeBaja(): void
    {
        DB::transaction(function () {
            $this->salidasProgramadas()
                ->where('estado', SalidaProgramada::ESTADO_PROGRAMADA)
                ->get()
                ->each(fn (SalidaProgramada $salida) => $salida->cancelar(self::MOTIVO_BAJA));

            $this->update(['estado' => self::ESTADO_INACTIVA]);
        });
    }

    // ========== CU 8 - REGISTRAR SALIDA PROGRAMADA ==========

    /**
     * DS CU 8: validarDatosSalida(datosSalida). Paso 4.
     * Si algo falla, lanza una ValidationException con el mensaje del alternativo que corresponda.
     *  RN-01 (4.1 y 4.2): período futuro, fin >= inicio y cantidad de días = duración de la excursión.
     *  RN-02 (4.3): cupo máximo y precio base mayores a cero.
     */
    public function validarDatosSalida(array $datosSalida): array
    {
        $periodoInvalido = 'El período ingresado no es válido.';
        $noPositivos = 'El cupo máximo y el precio base deben ser mayores a cero.';

        return Validator::make($datosSalida, [
            'fecha_inicio' => ['required', 'date_format:Y-m-d', 'after:today'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'hora_de_salida' => ['required', 'date_format:H:i'],
            'cupo_maximo' => ['required', 'integer', 'min:1', 'max:1000'],
            'precio_base' => ['required', 'numeric', 'gt:0', 'max:99999999'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'date_format' => 'El formato ingresado no es válido.',
            'fecha_inicio.after' => $periodoInvalido . ' La fecha de inicio debe ser futura.',
            'fecha_fin.after_or_equal' => $periodoInvalido . ' La fecha de fin no puede ser anterior a la de inicio.',
            'cupo_maximo.integer' => 'El cupo máximo debe ser un número entero.',
            'cupo_maximo.min' => $noPositivos,
            'cupo_maximo.max' => 'El cupo máximo no puede superar las 1000 personas.',
            'precio_base.numeric' => 'El precio base debe ser un número.',
            'precio_base.gt' => $noPositivos,
            'precio_base.max' => 'El precio base ingresado es demasiado alto.',
        ])->after(function ($validator) use ($datosSalida) {
            // Alternativo 4.2: solo tiene sentido si el período ya es válido (4.1)
            if ($validator->errors()->hasAny(['fecha_inicio', 'fecha_fin'])) {
                return;
            }

            // Se cuentan ambos extremos: una excursión de 1 día empieza y termina el mismo día
            $dias = (int) Carbon::parse($datosSalida['fecha_inicio'])
                ->diffInDays(Carbon::parse($datosSalida['fecha_fin'])) + 1;

            if ($dias !== $this->duracion) {
                $validator->errors()->add('fecha_fin',
                    "El período debe corresponderse con la duración de la excursión ({$this->duracion} "
                    . ($this->duracion === 1 ? 'día' : 'días') . ').');
            }
        })->validate();
    }

    /**
     * DS CU 8: crearSalida(datosSalida, guia, asistentes, eqs): idSalida, cupoMaximo.
     * Todo o nada: la salida, el personal asignado y el equipamiento incluido.
     */
    public function crearSalida(array $datosSalida, Empleado $guia, Collection $asistentes, Collection $equipamientos): array
    {
        return DB::transaction(function () use ($datosSalida, $guia, $asistentes, $equipamientos) {
            $salida = SalidaProgramada::crear(
                [...$datosSalida, 'excursion_id' => $this->id], $guia, $asistentes, $equipamientos);

            $this->agregar($salida);

            return ['idSalida' => $salida->id, 'cupoMaximo' => $salida->cupo_maximo];
        });
    }

    /** DS CU 8: agregar(s). La salida queda asociada a la excursión. */
    private function agregar(SalidaProgramada $salida): void
    {
        $this->salidasProgramadas()->save($salida);
    }
}
