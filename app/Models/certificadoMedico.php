<?php

namespace App\Models;

use Carbon\CarbonInterface;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;

class CertificadoMedico extends Model
{
    protected $table = 'certificados_medicos';

    protected $fillable = [
    'participante_id',
    'fecha_emision',
    'estado',
    'archivo',
    'fecha_de_carga',
    'fecha_de_vencimiento',
    'descripcion',
    'motivo_de_invalidez'];

    protected $casts = [
    'fecha_emision'=>'date',
    'estado'=>'string',
    'fecha_de_carga'=>'datetime',
    'fecha_de_vencimiento'=>'date'
    ];

    // =========== RELACIONES ==========
    public function participante():BelongsTo{
        return $this->belongsTo(Participante::class);
    }

    // Estados según el diagrama de clases
    public const ESTADO_PENDIENTE_DE_VERIFICACION = 'PENDIENTE_DE_VERIFICACION';
    public const ESTADO_VALIDO = 'VALIDO';
    public const ESTADO_INVALIDO = 'INVALIDO';

    // CU 17 - RN-01: formatos permitidos para cargar el certificado médico
    public const FORMATOS_PERMITIDOS = ['pdf', 'png', 'jpg', 'jpeg'];

    /**
     * CU 17 - paso 3. Valida el archivo adjunto y lo devuelve.
     * Si algo falla, lanza una ValidationException con el mensaje para el cliente.
     *  3.1: el archivo es obligatorio.
     *  3.2: el archivo debe estar en formato pdf, png, jpg o jpeg (RN-01).
     */
    public static function validarArchivo(array $datos): array
    {
        $formatos = implode(', ', self::FORMATOS_PERMITIDOS);

        return Validator::make($datos, [
            'archivo' => ['required', 'file', 'mimes:'.implode(',', self::FORMATOS_PERMITIDOS)],
        ], [
            'archivo.required' => 'El archivo del certificado médico es obligatorio.',
            'archivo.uploaded' => 'No se pudo subir el archivo. Intentá nuevamente.',
            'archivo.file' => "El archivo debe estar en formato {$formatos}.",
            'archivo.mimes' => "El archivo debe estar en formato {$formatos}.",
        ])->validate();
    }

    // ========== CU 19 - VERIFICAR CERTIFICADO MÉDICO ==========

    // RN-01: vigencia del certificado = fecha de emisión + 30 días
    public const DIAS_DE_VIGENCIA = 30;

    public function estaPendienteDeVerificacion(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE_DE_VERIFICACION;
    }

    /**
     * CU 19 - DS paso 8: mostrarDatosCertificado(). Archivo adjunto, participante,
     * reserva, excursión y salida asociados, y fecha de carga.
     */
    public function datosParaVerificar(): array
    {
        $participante = $this->participante;
        $reserva = $participante->reserva;
        $salida = $reserva->getSalidaProgramada();

        return [
            'archivo' => [
                'nombre' => basename($this->archivo),
                'es_pdf' => str_ends_with(strtolower($this->archivo), '.pdf'),
            ],
            'participante' => [
                'dni' => $participante->dni,
                'nombre' => $participante->nombre,
                'apellido' => $participante->apellido,
            ],
            'reserva_id' => $reserva->id,
            'excursion' => $salida->excursion->nombre,
            'paquete' => $reserva->paquete->nombre,
            'fecha_inicio' => $salida->fecha_inicio->toDateString(),
            'fecha_fin' => $salida->fecha_fin->toDateString(),
            'fecha_de_carga' => $this->fecha_de_carga?->toDateString(),
            'dias_de_vigencia' => self::DIAS_DE_VIGENCIA,
        ];
    }

    /**
     * CU 19 - paso 5. Valida la fecha de emisión ingresada y la devuelve.
     *  5.1: no puede ser una fecha futura ni un dato no válido.
     */
    public static function validarFechaEmision(array $datos): array
    {
        $mensaje = 'Debe ingresar una fecha de emisión válida (no puede ser futura).';

        return Validator::make($datos, [
            'fecha_emision' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'fecha_emision.required' => $mensaje,
            'fecha_emision.date' => $mensaje,
            'fecha_emision.before_or_equal' => $mensaje,
        ])->validate();
    }

    /**
     * CU 19 - 3.1. Valida el motivo de invalidez ingresado y lo devuelve.
     */
    public static function validarMotivoInvalidez(array $datos): array
    {
        return Validator::make($datos, [
            'motivo' => ['required', 'string'],
        ], [
            'motivo.required' => 'El motivo de invalidez es obligatorio.',
        ])->validate();
    }

    /**
     * CU 19 - DS pasos 10 y 11: calcularVigencia(fechaEmision).
     * Registra la fecha de emisión y devuelve la fecha de vencimiento (RN-01).
     */
    public function calcularVigencia(CarbonInterface $fechaEmision): CarbonInterface
    {
        $this->fecha_emision = $fechaEmision;
        $this->fecha_de_vencimiento = $fechaEmision->copy()->addDays(self::DIAS_DE_VIGENCIA);

        return $this->fecha_de_vencimiento;
    }

    /**
     * CU 19 - DS pasos 14 y 15: cubrePeriodo(unaSalidaProgramada).
     * RN-01: la vigencia debe cubrir el período completo de la salida.
     */
    public function cubrePeriodo(SalidaProgramada $salida): bool
    {
        return $this->fecha_de_vencimiento->gte($salida->fecha_fin);
    }

    /**
     * CU 19 - DS pasos 17, 19 y 22: setEstado(VALIDO | INVALIDO).
     * Guarda también la vigencia calculada, si la hay.
     */
    public function setEstado(string $estado, ?string $motivoInvalidez = null): void
    {
        $this->estado = $estado;
        $this->motivo_de_invalidez = $motivoInvalidez ? trim($motivoInvalidez) : null;
        $this->save();
    }
}
