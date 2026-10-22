<?php

namespace App\Models;

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
}
