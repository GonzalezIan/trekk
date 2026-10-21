<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        return Validator::make($datosExcursion, [
            'nombre' => [
                'required', 'string', 'max:120',
                function ($atributo, $valor, $fallar) {
                    if (self::existeNombre($valor)) {
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
     * RN-03: ¿ya hay una excursión registrada con ese nombre? (sin distinguir mayúsculas)
     */
    private static function existeNombre(?string $nombre): bool
    {
        return self::whereRaw('LOWER(nombre) = ?', [mb_strtolower(trim((string) $nombre))])->exists();
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
}
