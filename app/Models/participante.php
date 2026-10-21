<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use illuminate\Database\Eloquent\Relations\HasOne;

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
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * CU 21 - paso 4. Datos que se muestran de cada participante.
     */
    public function datosParaListado(): array
    {
        return [
            'reserva_id' => $this->reserva_id,
            'dni' => $this->dni,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'estado_requisito_medico' => $this->estado_requisito_medico,
        ];
    }
}
