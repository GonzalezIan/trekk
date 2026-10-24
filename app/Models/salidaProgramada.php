<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalidaProgramada extends Model
{
    use HasFactory;
    protected $table = 'salidas_programadas';

    protected $fillable = [
        'excursion_id',
        'fecha_inicio',
        'hora_de_salida',
        'fecha_fin',
        'cupo_maximo',
        'cupo_disponible',
        'precio_base',
        'estado',
        'motivo_cancelacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'hora_de_salida' => 'datetime:H:i',
        'fecha_fin' => 'date',
        'cupo_maximo' => 'integer',
        'cupo_disponible' => 'integer',
        'precio_base' => 'decimal:2',
        'estado' => 'string',
    ];

    // ========== RELACIONES ==========

    public function excursion():BelongsTo{
        return $this->belongsTo(Excursion::class);
    }

    public function empleados():BelongsToMany{
        return $this->belongsToMany(Empleado::class,'empleados_asignados')->withPivot('rol');
    }


    public function paquetes():HasMany{
        return $this->hasMany(Paquete::class);
    }

    public function equipoPrestado():BelongsToMany{
        return $this->belongsToMany(Equipamiento::class,'equipos_prestados')->withPivot('cantidad');
    }

        // ========== CU 9 - LISTAR SALIDAS PROGRAMADAS ==========

    public const ESTADO_PROGRAMADA = 'PROGRAMADA';
    public const ESTADOS = ['PROGRAMADA', 'CANCELADA', 'FINALIZADA'];
    public const HORAS_MINIMAS_ANTICIPACION = 48;

    /**
     * CU 9 (Administrativo) - DS: listar(filtros).
     * RN-02: cualquier estado. Filtros opcionales: excursion, estado, desde, hasta.
     */
    public static function listar(array $filtros): Collection
    {
        return self::query()
            ->with(['excursion:id,nombre', 'empleados', 'equipoPrestado'])
            ->withCount('paquetes')
            ->when($filtros['excursion'] ?? null,
                fn ($consulta, $idExcursion) => $consulta->where('excursion_id', $idExcursion))
            ->when($filtros['estado'] ?? null,
                fn ($consulta, $estado) => $consulta->where('estado', $estado))
            ->when($filtros['desde'] ?? null,
                fn ($consulta, $desde) => $consulta->whereDate('fecha_inicio', '>=', $desde))
            ->when($filtros['hasta'] ?? null,
                fn ($consulta, $hasta) => $consulta->whereDate('fecha_inicio', '<=', $hasta))
            ->orderBy('fecha_inicio')
            ->orderBy('hora_de_salida')
            ->get()
            ->map(fn (self $salida) => $salida->getDatos());
    }

    /**
     * CU 9 (Cliente) - DS: esReservable().
     * RN-01: está PROGRAMADA y faltan más de 48 hs para la salida (fecha + hora).
     */
    public function esReservable(): bool
    {
        return $this->estado === self::ESTADO_PROGRAMADA
            && $this->momentoDeSalida()->greaterThan(now()->addHours(self::HORAS_MINIMAS_ANTICIPACION));
    }

    /** CU 9 - DS: getDatos(). Datos de la salida para el listado (4.1 / 4.2). */
    public function getDatos(): array
    {
        return [
            'id' => $this->id,
            'excursion' => $this->excursion->nombre,
            'fecha_inicio' => $this->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $this->fecha_fin->format('Y-m-d'),
            'hora_de_salida' => $this->hora_de_salida->format('H:i'),
            'guia' => $this->nombresDeEmpleados('GUIA')->first(),
            'asistentes' => $this->nombresDeEmpleados('ASISTENTE'),
            'equipamiento' => $this->equipoPrestado
                ->map(fn ($equipo) => $equipo->nombre . ' x' . $equipo->pivot->cantidad),
            'cupo_maximo' => $this->cupo_maximo,
            'cupo_disponible' => $this->cupo_disponible,
            'precio_base' => $this->precio_base,
            'cantidad_paquetes' => $this->paquetes_count,
            'estado' => $this->estado,
        ];
    }
    // ========== CU 7 - LISTAR PAQUETES ==========

    /** DS CU 7: buscar(idSalida). */
    public static function buscar(int $idSalida): self
    {
        return self::findOrFail($idSalida);
    }

    /**
     * DS CU 7 (Administrativo): getPaquetes().
     * Todos los paquetes, cualquiera sea su estado, con la cantidad de reservas activas.
     */
    public function getPaquetes(): Collection
    {
        return $this->paquetes()
            ->with(['refugio:id,nombre', 'servicios'])
            ->orderBy('nombre')
            ->get()
            ->map(fn (Paquete $paquete) => [
                ...$paquete->getDatos(),
                'reservas_activas' => $paquete->cantidadReservasActivas(),
            ]);
    }

    /**
     * DS CU 7 (Cliente): getPaquetesDisponibles().
     * Solo los paquetes en estado DISPONIBLE, con su precio y disponibilidad.
     */
    public function getPaquetesDisponibles(): Collection
    {
        return $this->paquetes()
            ->with(['refugio.convenios', 'servicios'])
            ->orderBy('nombre')
            ->get()
            ->filter(fn (Paquete $paquete) => $paquete->estaDisponible())
            ->each(fn (Paquete $paquete) => $paquete->setRelation('salidaProgramada', $this))
            ->map(fn (Paquete $paquete) => [
                ...$paquete->getDatos(),
                ...$paquete->getDisponibilidad(),
            ])
            ->values();
    }

    /** DS CU 7: getCupoDisponible(). */
    public function getCupoDisponible(): int
    {
        return $this->cupo_disponible;
    }

    // ========== CU 14 - REGISTRAR RESERVA ==========

    /** DS CU 14 paso 9: hayCupoDisponible(cantidad). */
    public function hayCupoDisponible(int $cantidad): bool
    {
        return $this->cupo_disponible >= $cantidad;
    }

    /**
     * DS CU 14 paso 11: retenerCupo(cantidad). RN-03: resta la cantidad del cupo disponible
     * mientras la reserva está "En curso"; si vence, se devuelve con liberarCupo().
     */
    public function retenerCupo(int $cantidad): void
    {
        $this->update(['cupo_disponible' => $this->cupo_disponible - $cantidad]);
    }

    /** DS CU 14 paso 20: getExcursion(). */
    public function getExcursion(): Excursion
    {
        return $this->excursion;
    }

    // ========== CU 16 - CANCELAR RESERVA ==========

    /**
     * DS CU 16: liberarCupo(cantidadParticipantes).
     * Suma la cantidad liberada a su propio cupo disponible, sin superar el cupo máximo.
     */
    public function liberarCupo(int $cantidad): void
    {
        $this->update([
            'cupo_disponible' => min($this->cupo_maximo, $this->cupo_disponible + $cantidad),
        ]);
    }

     // ========== CU 2 - BAJA DE EXCURSIÓN ==========

    public const ESTADO_CANCELADA = 'CANCELADA';

    /**
     * Cancela la salida (guardando el motivo) y sus paquetes que seguían disponibles.
     * La usa la baja de la excursión (CU 2).
     */
    public function cancelar(string $motivo): void
    {
        $this->update([
            'estado' => self::ESTADO_CANCELADA,
            'motivo_cancelacion' => $motivo,
        ]);

        $this->paquetes()
            ->where('estado', Paquete::ESTADO_DISPONIBLE)
            ->update(['estado' => Paquete::ESTADO_CANCELADO]);
    }

    // ========== CU 8 - REGISTRAR SALIDA PROGRAMADA ==========

    public const ROL_GUIA = 'GUIA';
    public const ROL_ASISTENTE = 'ASISTENTE';

    /**
     * DS CU 8: crear(datosSalida, guia, asistentes, eqs).
     * RN-07: el cupo máximo es el menor entre el ingresado y el stock de cada equipo incluido.
     * RN-10: queda PROGRAMADA, con su cupo disponible igual a su cupo máximo.
     * RN-08 (DS Reserva de equipamiento): de cada equipo se reserva una cantidad igual al cupo máximo.
     */
    public static function crear(array $datosSalida, Empleado $guia, Collection $asistentes, Collection $equipamientos): self
    {
        $salida = new self($datosSalida);
        $salida->cupo_maximo = $salida->calcularCupoMaximo($equipamientos);
        $salida->cupo_disponible = $salida->cupo_maximo;
        $salida->estado = self::ESTADO_PROGRAMADA;
        $salida->save();

        $salida->empleados()->attach($guia->id, ['rol' => self::ROL_GUIA]);
        $asistentes->each(fn (Empleado $asistente) =>
            $salida->empleados()->attach($asistente->id, ['rol' => self::ROL_ASISTENTE]));

        // EquipamientoIncluido: crear(s, eq, cupoMaximo)
        $equipamientos->each(fn (Equipamiento $equipamiento) =>
            $salida->equipoPrestado()->attach($equipamiento->id, ['cantidad' => $salida->cupo_maximo]));

        return $salida;
    }

    /** DS CU 8 (Reserva de equipamiento): calcularCupoMaximo(eqs). */
    public function calcularCupoMaximo(Collection $equipamientos): int
    {
        [$fechaInicio, $fechaFin] = $this->getFechas();

        return $equipamientos
            ->map(fn (Equipamiento $equipamiento) => $equipamiento->stockDisponible($fechaInicio, $fechaFin, $this->id))
            ->push($this->cupo_maximo)
            ->min();
    }

    /** DS CU 25: getFechas(). Período de la salida como 'Y-m-d'. */
    public function getFechas(): array
    {
        return [$this->fecha_inicio->format('Y-m-d'), $this->fecha_fin->format('Y-m-d')];
    }

    /**
     * Salidas PROGRAMADAS cuyo período se superpone con [fechaInicio, fechaFin].
     * Las usan el CU 23 (empleados disponibles) y el CU 25 (stock disponible).
     * CU 10: $idSalidaExcluida deja afuera a la salida que se está modificando.
     */
    public function scopeSuperpuestasCon(Builder $consulta, string $fechaInicio, string $fechaFin, ?int $idSalidaExcluida = null): void
    {
        $consulta->where('salidas_programadas.estado', self::ESTADO_PROGRAMADA)
            ->whereDate('salidas_programadas.fecha_inicio', '<=', $fechaFin)
            ->whereDate('salidas_programadas.fecha_fin', '>=', $fechaInicio)
            ->when($idSalidaExcluida, fn ($consulta) => $consulta->where('salidas_programadas.id', '!=', $idSalidaExcluida));
    }

    // ========== CU 5 - REGISTRAR PAQUETE ==========

    /** CU 5 (RN-01): solo se registran paquetes sobre salidas PROGRAMADAS. */
    public function estaProgramada(): bool
    {
        return $this->estado === self::ESTADO_PROGRAMADA;
    }

    /** CU 5 - paso 2. Datos que el paquete toma de la salida (RN-02 y RN-07). */
    public function getDatosParaPaquete(): array
    {
        $this->loadCount('paquetes');

        return [
            ...$this->getDatos(),
            'excursion_id' => $this->excursion_id,
            'noches' => $this->cantidadNoches(),
        ];
    }

    /** Noches del período: una excursión de 3 días tiene 2 noches. */
    public function cantidadNoches(): int
    {
        return (int) $this->fecha_inicio->diffInDays($this->fecha_fin);
    }

    /** DS CU 5: existePaquete(nombre). RN-06, sin distinguir mayúsculas. */
    public function existePaquete(?string $nombre): bool
    {
        return $this->paquetes()
            ->whereRaw('LOWER(nombre) = ?', [mb_strtolower(trim((string) $nombre))])
            ->exists();
    }

    /**
     * DS CU 5: crearPaquete(nombre, descripcion, servicios, ref): idPaquete, precio.
     * Todo o nada: el paquete y sus servicios incluidos.
     */
    public function crearPaquete(string $nombre, string $descripcion, Collection $servicios, ?Refugio $refugio): array
    {
        return DB::transaction(function () use ($nombre, $descripcion, $servicios, $refugio) {
            $paquete = Paquete::crear($this, $nombre, $descripcion, $servicios, $refugio);

            return ['idPaquete' => $paquete->id, 'precio' => (float) $paquete->precio_calculado];
        });
    }

    // ========== CU 10 - MODIFICAR SALIDA PROGRAMADA ==========

    /** CU 10 - paso 2. Datos actuales de la salida para precargar el formulario. */
    public function getDatosParaModificar(): array
    {
        $this->load(['excursion', 'empleados', 'equipoPrestado'])->loadCount('paquetes');

        return [
            ...$this->getDatos(),
            'duracion' => $this->excursion->duracion,
            'id_guia' => $this->idsDeEmpleados(self::ROL_GUIA)->first(),
            'ids_asistentes' => $this->idsDeEmpleados(self::ROL_ASISTENTE),
            'ids_equipamiento' => $this->equipoPrestado->pluck('id'),
            'participantes_activos' => $this->cantidadParticipantesActivos(),
        ];
    }

    /**
     * DS CU 10: modificar(datosSalida, guia, asistentes, eqs): cupoMaximo, clientesNotificados.
     * Pasos 5 a 12, todo o nada. Los datos ya vienen revalidados (paso 4).
     * Si algo falla, lanza una ValidationException con el alternativo que corresponda.
     */
    public function modificar(array $datosSalida, Empleado $guia, Collection $asistentes, Collection $equipamientos): array
    {
        return DB::transaction(function () use ($datosSalida, $guia, $asistentes, $equipamientos) {
            $idsEquipamientoAnterior = $this->equipoPrestado->pluck('id');
            $cambioPersonal = $this->idsDeEmpleados(self::ROL_GUIA)->first() !== $guia->id
                || $this->idsDeEmpleados(self::ROL_ASISTENTE)->sort()->values()->all()
                    !== $asistentes->pluck('id')->sort()->values()->all();
            $cambioEquipamiento = $idsEquipamientoAnterior->sort()->values()->all()
                !== $equipamientos->pluck('id')->sort()->values()->all();
            $ocupados = $this->cupo_maximo - $this->cupo_disponible;

            $this->fill($datosSalida);
            $cambioPeriodo = $this->isDirty(['fecha_inicio', 'fecha_fin']);

            // Paso 5 (RN-04)
            if ($cambioPeriodo) {
                $this->verificarConvenios();
            }
            // Paso 6: solo los elementos que se agregan a la salida
            $this->verificarStock($equipamientos->whereNotIn('id', $idsEquipamientoAnterior));
            // Paso 7 (RN-02)
            $this->cupo_maximo = $this->calcularCupoMaximo($equipamientos);
            // Paso 8 (RN-03)
            $this->verificarCupoMinimo();
            // Paso 9: se conservan los lugares ya ocupados
            $this->cupo_disponible = max(0, $this->cupo_maximo - $ocupados);
            // Paso 12
            $this->save();

            $this->empleados()->sync([
                $guia->id => ['rol' => self::ROL_GUIA],
                ...$asistentes->mapWithKeys(fn (Empleado $asistente) =>
                    [$asistente->id => ['rol' => self::ROL_ASISTENTE]])->all(),
            ]);
            $this->equipoPrestado()->sync($equipamientos->mapWithKeys(fn (Equipamiento $equipamiento) =>
                [$equipamiento->id => ['cantidad' => $this->cupo_maximo]])->all());

            // Paso 10 (RN-05)
            $this->recalcularPreciosDePaquetes();

            // *a (RN-06)
            $clientesNotificados = $cambioPeriodo || $cambioPersonal || $cambioEquipamiento
                ? $this->cantidadClientesConReservasActivas()
                : 0;

            return ['cupoMaximo' => $this->cupo_maximo, 'clientesNotificados' => $clientesNotificados];
        });
    }

    /** CU 10 - paso 8. Participantes registrados en reservas activas de la salida. */
    public function cantidadParticipantesActivos(): int
    {
        return Participante::whereHas('reserva', fn ($consulta) => $consulta
            ->whereIn('estado', Paquete::ESTADOS_RESERVA_ACTIVA)
            ->whereHas('paquete', fn ($consulta) => $consulta->where('salida_programada_id', $this->id)))
            ->count();
    }

    /** CU 10 - 5.1. Los refugios de los paquetes con alojamiento deben cubrir el nuevo período. */
    private function verificarConvenios(): void
    {
        $afectados = $this->paquetes()
            ->where('estado', Paquete::ESTADO_DISPONIBLE)
            ->whereNotNull('refugio_id')
            ->with('refugio.convenios')
            ->get()
            ->reject(fn (Paquete $paquete) =>
                $paquete->refugio->tieneConvenioQueCubre($this->fecha_inicio, $this->fecha_fin));

        if ($afectados->isNotEmpty()) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'Los convenios de estos refugios no cubren el nuevo período: '
                    . $afectados->map(fn (Paquete $paquete) =>
                        "paquete “{$paquete->nombre}” (refugio {$paquete->refugio->nombre})")->join(', ') . '.',
            ]);
        }
    }

    /** CU 10 - 6.1. Cada elemento nuevo debe tener stock suficiente para el cupo ingresado. */
    private function verificarStock(Collection $equipamientos): void
    {
        [$fechaInicio, $fechaFin] = $this->getFechas();

        $sinStock = $equipamientos
            ->map(fn (Equipamiento $equipamiento) => [
                'nombre' => $equipamiento->nombre,
                'stock' => $equipamiento->stockDisponible($fechaInicio, $fechaFin, $this->id),
            ])
            ->filter(fn (array $equipamiento) => $equipamiento['stock'] < $this->cupo_maximo);

        if ($sinStock->isNotEmpty()) {
            throw ValidationException::withMessages([
                'ids_equipamiento' => 'El stock disponible no alcanza para el cupo máximo ingresado: '
                    . $sinStock->map(fn (array $equipamiento) =>
                        "{$equipamiento['nombre']} (stock disponible: {$equipamiento['stock']})")->join(', ') . '.',
            ]);
        }
    }

    /** CU 10 - 8.1. */
    private function verificarCupoMinimo(): void
    {
        $participantes = $this->cantidadParticipantesActivos();

        if ($this->cupo_maximo < $participantes) {
            throw ValidationException::withMessages([
                'cupo_maximo' => "El cupo no puede reducirse por debajo de la cantidad ya reservada ({$participantes} "
                    . ($participantes === 1 ? 'participante' : 'participantes') . ').',
            ]);
        }
    }

    /** CU 10 - paso 10. Los paquetes disponibles toman el nuevo precio base y período. */
    private function recalcularPreciosDePaquetes(): void
    {
        $this->paquetes()
            ->where('estado', Paquete::ESTADO_DISPONIBLE)
            ->with(['refugio.convenios', 'servicios'])
            ->get()
            ->each(function (Paquete $paquete) {
                $paquete->setRelation('salidaProgramada', $this);
                $paquete->update(['precio_calculado' => $paquete->calcularPrecio()]);
            });
    }

    /** *a (RN-06). Clientes con reservas activas que reciben la notificación. */
    private function cantidadClientesConReservasActivas(): int
    {
        return Reserva::whereIn('estado', Paquete::ESTADOS_RESERVA_ACTIVA)
            ->whereHas('paquete', fn ($consulta) => $consulta->where('salida_programada_id', $this->id))
            ->distinct()
            ->count('cliente_id');
    }

    /** Ids de los empleados asignados con ese rol (GUIA / ASISTENTE). */
    private function idsDeEmpleados(string $rol): Collection
    {
        return $this->empleados
            ->filter(fn ($empleado) => $empleado->pivot->rol === $rol)
            ->pluck('id')
            ->values();
    }

    /** Fecha de inicio + hora de salida, en un solo momento. */
    private function momentoDeSalida(): Carbon
    {
        return $this->fecha_inicio->copy()->setTimeFrom($this->hora_de_salida);
    }

    /** "Nombre Apellido" de los empleados asignados con ese rol (GUIA / ASISTENTE). */
    private function nombresDeEmpleados(string $rol): Collection
    {
        return $this->empleados
            ->filter(fn ($empleado) => $empleado->pivot->rol === $rol)
            ->map(fn ($empleado) => $empleado->nombre . ' ' . $empleado->apellido)
            ->values();
    }

}
