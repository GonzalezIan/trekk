<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        'punto_de_encuentro',
        'imagen',
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
     * Las salidas sin paquetes disponibles no se muestran: el cliente reserva sobre un paquete.
     */
    public function getSalidasReservables(): Collection
    {
        return $this->salidasProgramadas()
            ->whereHas('paquetes', fn ($consulta) => $consulta->where('estado', Paquete::ESTADO_DISPONIBLE))
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

    // CU 1 y CU 3: formatos y tamaño máximo (en KB) de la imagen de la excursión
    public const FORMATOS_IMAGEN = ['jpg', 'jpeg', 'png', 'webp'];
    public const TAMANO_MAXIMO_IMAGEN = 2048;

     /**
     * CU 1 - paso 4. Valida los datos del alta y devuelve los datos limpios.
     * Si algo falla, lanza una ValidationException y Laravel vuelve al formulario.
     *  RN-01: todo es obligatorio salvo el equipamiento obligatorio (y el punto de encuentro y la imagen).
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
     * La duración no puede cambiar si tiene salidas programadas: su fecha de fin se calculó
     * con la duración actual (CU 8) y su personal, equipamiento y refugios están asignados a ese período.
     */
    public function validarModificacion(array $datosExcursion): array
    {
        $datosValidos = self::validar($datosExcursion, $this->id);

        if ((int) $datosValidos['duracion'] !== $this->duracion && $this->tieneSalidasProgramadas()) {
            throw ValidationException::withMessages([
                'duracion' => 'No se puede cambiar la duración porque la excursión tiene salidas programadas. '
                    . 'Cancelalas o esperá a que finalicen para modificarla.',
            ]);
        }

        return $datosValidos;
    }

    private function tieneSalidasProgramadas(): bool
    {
        return $this->salidasProgramadas()->where('estado', SalidaProgramada::ESTADO_PROGRAMADA)->exists();
    }

    /** Reglas comunes al alta (CU 1) y a la modificación (CU 3). */
    private static function validar(array $datosExcursion, ?int $idExcluido = null): array
    {
        return Validator::make($datosExcursion, [
            'nombre' => [
                'required', 'string', 'max:120','regex:/^[\pL\s\-]+$/u',
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
            // Se informa en el recordatorio de salida (RF Notificar eventualidades)
            'punto_de_encuentro' => ['nullable', 'string', 'max:255'],
            'imagen' => ['nullable', 'file', 'mimes:' . implode(',', self::FORMATOS_IMAGEN), 'max:' . self::TAMANO_MAXIMO_IMAGEN],
            'requiere_certificado_medico' => ['required', 'boolean'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'duracion.integer' => 'La duración debe ser un número entero de días.',
            'duracion.min' => 'La duración de la excursión debe ser de al menos un día.',
            'duracion.max' => 'La duración no puede superar los 365 días.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
            'nombre.regex' => 'El nombre de la excursión solo puede contener letras, espacios y guiones.',
            'punto_de_encuentro.max' => 'El punto de encuentro no puede superar los 255 caracteres.',
            'imagen.file' => 'El archivo seleccionado no es válido.',
            'imagen.uploaded' => 'No se pudo subir la imagen.',
            'imagen.mimes' => 'La imagen debe estar en formato JPG, PNG o WEBP.',
            'imagen.max' => 'La imagen no puede superar los 2 MB.',
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
            'imagen' => self::guardarImagen($datosExcursion['imagen'] ?? null),
            'estado' => self::ESTADO_ACTIVA,
        ]);
    }

    /** Guarda el archivo en el disco privado y devuelve su ruta, o null si no se eligió ninguno. */
    private static function guardarImagen(?UploadedFile $imagen): ?string
    {
        return $imagen?->store('excursiones');
    }

    // ========== CU 3 - MODIFICAR EXCURSIÓN y CU 2 - BAJA DE EXCURSIÓN ==========
 
    public const MOTIVO_BAJA = 'Baja de la excursión';

    // CU 8 y CU 10: precio base máximo de una salida, en USD
    public const PRECIO_BASE_MAXIMO = 100000;

    /** CU 3 (RN-03) y CU 2: solo se opera sobre excursiones activas. */
    public function estaActiva(): bool
    {
        return $this->estado === self::ESTADO_ACTIVA;
    }

    /**
     * CU 3 (RN-01) y CU 2: ¿alguna de sus salidas tiene paquetes con reservas activas
     * (PENDIENTE o CONFIRMADA)?
     */
    public function tieneReservasActivas(): bool
    {
        return Reserva::whereIn('estado', Paquete::ESTADOS_RESERVA_ACTIVA)
            ->whereHas('paquete.salidaProgramada',
                fn ($consulta) => $consulta->where('excursion_id', $this->id))
            ->exists();
    }

    /**
     * CU 3 y CU 2: ¿algún cliente está registrando una reserva (CU 14, "En curso") en una de sus salidas?
     * Esas reservas retienen cupos y vencen solas en pocos minutos.
     */
    public function tieneReservasEnCurso(): bool
    {
        return Reserva::where('estado', Reserva::ESTADO_EN_CURSO)
            ->whereHas('paquete.salidaProgramada',
                fn ($consulta) => $consulta->where('excursion_id', $this->id))
            ->exists();
    }

    /**
     * CU 3 - paso 6. Guarda los datos actualizados de la excursión.
     * Si se eligió una imagen nueva reemplaza a la anterior; si no, se conserva la que tenía.
     */
    public function modificar(array $datosExcursion): void
    {
        $imagenAnterior = $this->imagen;
        $imagenNueva = self::guardarImagen($datosExcursion['imagen'] ?? null);

        $this->update([
            ...$datosExcursion,
            'nombre' => trim($datosExcursion['nombre']),
            'imagen' => $imagenNueva ?? $imagenAnterior,
        ]);

        if ($imagenNueva && $imagenAnterior) {
            Storage::delete($imagenAnterior);
        }
    }

    /**
     * CU 2. La excursión pasa a INACTIVA y se cancelan sus salidas PROGRAMADAS
     * junto con los paquetes de esas salidas. Todo o nada (transacción).
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
     *  RN-01 (4.1): período futuro. La fecha de fin no se ingresa: se calcula con la duración de la excursión.
     *  RN-02 (4.3): cupo máximo y precio base mayores a cero.
     */
    public function validarDatosSalida(array $datosSalida): array
    {
        $noPositivos = 'El cupo máximo y el precio base deben ser mayores a cero.';
        
        // 1. Calculamos la antelación mínima según la excursión
        $diasMinimos = $this->requiere_certificado_medico ? 20 : 7;
        $fechaMinima = now()->addDays($diasMinimos)->format('Y-m-d');

        $datosValidos = Validator::make($datosSalida, [
            'fecha_inicio' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:today'.$fechaMinima],
            'hora_de_salida' => ['required', 'date_format:H:i'],
            'cupo_maximo' => ['required', 'integer', 'min:1', 'max:1000'],
            // Tope en USD: con servicios y alojamiento, el precio del paquete debe entrar en decimal(10,2)
            'precio_base' => ['bail', 'required', 'numeric', 'gt:0', 'decimal:0,2', 'max:'.self::PRECIO_BASE_MAXIMO],
        ], [
            'required' => 'Este campo es obligatorio.',
            'date_format' => 'El formato ingresado no es válido.',
            'fecha_inicio.after_or_equal' => "La fecha ingresada no es válida. Por seguridad la fecha de inicio debe ser al menos {$diasMinimos} dias a partir de hoy",
            'cupo_maximo.integer' => 'El cupo máximo debe ser un número entero.',
            'cupo_maximo.min' => $noPositivos,
            'cupo_maximo.max' => 'El cupo máximo no puede superar las 1000 personas.',
            'precio_base.numeric' => 'El precio base debe ser un número.',
            'precio_base.gt' => $noPositivos,
            'precio_base.decimal' => 'El precio base puede tener como máximo 2 decimales.',
            'precio_base.max' => 'El precio base no puede superar USD 100.000.',
        ])->validate();

        return [...$datosValidos, 'fecha_fin' => $this->calcularFechaFin($datosValidos['fecha_inicio'])];
    }

    /** Se cuentan ambos extremos: una excursión de 1 día empieza y termina el mismo día. */
    private function calcularFechaFin(string $fechaInicio): string
    {
        return Carbon::parse($fechaInicio)->addDays($this->duracion - 1)->format('Y-m-d');
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
