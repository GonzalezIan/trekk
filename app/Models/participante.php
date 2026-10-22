<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Participante extends Model
{
    protected $table = 'participantes';

    protected $fillable = [
        'reserva_id',
        'dni',
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'estado_requisito_medico',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'estado_requisito_medico' => 'string',
    ];

    // =========== RELACIONES ==========

    public function reserva():BelongsTo{
        return $this->belongsTo(Reserva::class);
    }

    public function certificadoMedico():HasOne{
        return $this->hasOne(CertificadoMedico::class,'participante_id');
    }

    // Situaciones del requisito médico según el diagrama de clases
    public const REQUISITO_NO_REQUERIDO = 'NO_REQUERIDO';
    public const REQUISITO_PENDIENTE = 'PENDIENTE';
    public const REQUISITO_CUMPLIDO = 'CUMPLIDO';

    /**
     * CU 21 - flujo alternativo 3.2. Nómina de participantes de las reservas
     * "Confirmada" de una Salida Programada (vista del Administrativo).
     */
    public static function listarConfirmadosDeSalida(int $idSalidaProgramada): Collection
    {
        return self::query()
            ->whereHas('reserva', fn ($reserva) => $reserva
                ->where('estado', Reserva::ESTADO_CONFIRMADA)
                ->whereHas('paquete', fn ($paquete) => $paquete
                    ->where('salida_programada_id', $idSalidaProgramada)))
            ->with('certificadoMedico')
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * CU 21 - paso 4. Datos que se muestran de cada participante.
     * Incluye el estado del certificado cargado (CU 17), si lo hay.
     */
    public function datosParaListado(): array
    {
        return [
            'reserva_id' => $this->reserva_id,
            'dni' => $this->dni,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'estado_requisito_medico' => $this->estado_requisito_medico,
            'estado_certificado' => $this->certificadoMedico?->estado,
        ];
    }

    /**
     * CU 19 - DS paso 16: setRequisitoMedico(CUMPLIDO).
     */
    public function setRequisitoMedico(string $estado): void
    {
        $this->update(['estado_requisito_medico' => $estado]);
    }

    /**
     * CU 17 - Precondición. Se puede cargar el certificado si el requisito médico
     * está "Pendiente" y la reserva admite la carga ("Pendiente" y a más de 24 hs de la salida).
     */
    public function admiteCargaDeCertificado(): bool
    {
        return $this->estado_requisito_medico === self::REQUISITO_PENDIENTE
            && $this->reserva->admiteCargaDeCertificados();
    }

    /**
     * CU 17 - DS pasos 7 y 8. Registra el certificado en estado "Pendiente de verificación".
     * El requisito médico del participante sigue "Pendiente" hasta el CU 19.
     *  RN-04: si ya tenía un certificado cargado, el nuevo lo reemplaza sin conservar el anterior.
     */
    public function registrarCertificado(UploadedFile $archivo): CertificadoMedico
    {
        $rutaArchivo = $archivo->store('certificados');

        if ($anterior = $this->certificadoMedico) {
            Storage::delete($anterior->archivo);
            $anterior->delete();
        }

        return $this->certificadoMedico()->create([
            'archivo' => $rutaArchivo,
            'fecha_de_carga' => now(),
            'estado' => CertificadoMedico::ESTADO_PENDIENTE_DE_VERIFICACION,
        ]);
    }
}
