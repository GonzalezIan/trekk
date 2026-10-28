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
     * DS CU 10: modificar(datosSalida, guia, asistentes, eqs): cupoMaximo, cambios.
     * Pasos 5 a 12, todo o nada. Los datos ya vienen revalidados (paso 4).
     * Si algo falla, lanza una ValidationException con el alternativo que corresponda.
     * "cambios" son los datos que se notifican a los clientes (RN-08), con su valor anterior y el nuevo.
     */
    public function modificar(array $datosSalida, Empleado $guia, Collection $asistentes, Collection $equipamientos): array
    {
        return DB::transaction(function () use ($datosSalida, $guia, $asistentes, $equipamientos) {
            $datosAnteriores = $this->datosNotificables();
            $ocupados = $this->cupo_maximo - $this->cupo_disponible;

            $this->fill($datosSalida);
            $cambioPeriodo = $this->isDirty(['fecha_inicio', 'fecha_fin']);

            // Paso 5 (RN-04)
            if ($cambioPeriodo) {
                $this->verificarConvenios();
                $this->verificarRefugiosLibres();
            }
            // Pasos 6 y 7 (RN-02, RF-10): como en el CU 8, el cupo se ajusta al stock de todo el equipamiento incluido
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

            // *a (RN-06 / RN-08): qué cambió de lo que se notifica
            $this->unsetRelation('empleados')->unsetRelation('equipoPrestado');
            $datosNuevos = $this->datosNotificables();
            $cambios = collect($datosNuevos)
                ->filter(fn (string $valor, string $dato) => $valor !== $datosAnteriores[$dato])
                ->map(fn (string $valor, string $dato) =>
                    ['dato' => $dato, 'anterior' => $datosAnteriores[$dato], 'nuevo' => $valor])
                ->values()
                ->all();

            return ['cupoMaximo' => $this->cupo_maximo, 'cambios' => $cambios];
        });
    }

    /**
     * RN-08 del CU 10: período, hora de salida, personal asignado y equipamiento incluido,
     * como se le muestran al cliente. El cupo y el precio no se notifican.
     */
    private function datosNotificables(): array
    {
        return [
            'Fecha de inicio' => $this->fecha_inicio->format('d/m/Y'),
            'Fecha de fin' => $this->fecha_fin->format('d/m/Y'),
            'Hora de salida' => $this->hora_de_salida->format('H:i') . ' hs',
            'Guía responsable' => $this->nombresDeEmpleados(self::ROL_GUIA)->first() ?? 'Sin asignar',
            'Asistentes' => $this->nombresDeEmpleados(self::ROL_ASISTENTE)->sort()->join(', ') ?: 'Sin asistentes',
            'Equipamiento incluido' => $this->equipoPrestado->pluck('nombre')->sort()->join(', ') ?: 'Sin equipamiento',
        ];
    }

    /**
     * CU 10 - paso 8. Participantes registrados en reservas activas de la salida, más los cupos
     * que retienen las reservas "En curso" (CU 14) mientras el cliente termina de registrarlas.
     */
    public function cantidadParticipantesActivos(): int
    {
        $retenidos = Reserva::where('estado', Reserva::ESTADO_EN_CURSO)
            ->whereHas('paquete', fn ($consulta) => $consulta->where('salida_programada_id', $this->id))
            ->sum('cantidad_participantes');

        return Participante::whereHas('reserva', fn ($consulta) => $consulta
            ->whereIn('estado', Paquete::ESTADOS_RESERVA_ACTIVA)
            ->whereHas('paquete', fn ($consulta) => $consulta->where('salida_programada_id', $this->id)))
            ->count() + (int) $retenidos;
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

    /** CU 10 - paso 5. Como en el CU 5, el refugio no puede estar en otro paquete de una salida superpuesta. */
    private function verificarRefugiosLibres(): void
    {
        $afectados = $this->paquetes()
            ->where('estado', Paquete::ESTADO_DISPONIBLE)
            ->whereNotNull('refugio_id')
            ->with('refugio')
            ->get()
            ->filter(fn (Paquete $paquete) =>
                $paquete->refugio->tienePaqueteSolapado($this->fecha_inicio, $this->fecha_fin, $this->id));

        if ($afectados->isNotEmpty()) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'Estos refugios ya están asignados a otro paquete en el nuevo período: '
                    . $afectados->map(fn (Paquete $paquete) =>
                        "paquete “{$paquete->nombre}” (refugio {$paquete->refugio->nombre})")->join(', ') . '.',
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

    /** *a (RN-06) y RF Notificar eventualidades. Reservas activas de la salida, a las que se notifica. */
    public function reservasActivas(): Collection
    {
        return Reserva::whereIn('estado', Paquete::ESTADOS_RESERVA_ACTIVA)
            ->whereHas('paquete', fn ($consulta) => $consulta->where('salida_programada_id', $this->id))
            ->with(['cliente.user', 'paquete.salidaProgramada.excursion'])
            ->get();
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

    // ========== RF FINALIZAR SALIDA PROGRAMADA ==========

    public const ESTADO_FINALIZADA = 'FINALIZADA';
    /** Salidas "Programada" cuya fecha de fin ya transcurrió. */
    public static function listarParaFinalizar(): Collection
    {
        return self::where('estado', self::ESTADO_PROGRAMADA)
            ->whereDate('fecha_fin', '<', now()->toDateString())
            ->orderBy('fecha_fin')
            ->get();
    }

    /**
     * DS RF Finalizar: finalizar(). Todo o nada; si la salida ya no está "Programada" no hace nada.
     *  - La salida pasa a "Finalizada" y sus reservas "Confirmada" a "Realizada" (habilita el CU 20).
     *  - Se liberan las camas de los convenios reservadas por las reservas activas.
     *  - El equipamiento incluido y el de alquiler se liberan solos: su stock disponible
     *    se calcula solo con salidas "Programada" (scopeSuperpuestasCon).
     */
    public function finalizar(): void
    {
        DB::transaction(function () {
            $salida = self::lockForUpdate()->findOrFail($this->id);
            if (! $salida->estaProgramada()) {
                return;
            }

            $activas = Reserva::whereHas('paquete', fn ($consulta) => $consulta->where('salida_programada_id', $salida->id))
                ->whereIn('estado', Paquete::ESTADOS_RESERVA_ACTIVA)
                ->with('paquete.refugio.convenios')
                ->withCount('participantes')
                ->get();

            $salida->liberarCamas($activas);

            $activas->where('estado', Reserva::ESTADO_CONFIRMADA)
                ->each(fn (Reserva $reserva) => $reserva->marcarRealizada());

            $salida->update(['estado' => self::ESTADO_FINALIZADA]);
            $this->setRawAttributes($salida->getAttributes(), true);
        });
    }

    /** Devuelve a los convenios las camas que ocupaban las reservas, agrupadas por paquete con refugio. */
    private function liberarCamas(Collection $reservas): void
    {
        $reservas->filter(fn (Reserva $reserva) => $reserva->paquete->refugio_id)
            ->groupBy('paquete_id')
            ->each(fn (Collection $delPaquete) => $delPaquete->first()->paquete->refugio
                ->liberarCamas($this->fecha_inicio, $this->fecha_fin, $delPaquete->sum('participantes_count')));
    }

    // ========== RF NOTIFICAR EVENTUALIDADES - RECORDATORIO DE SALIDA ==========

    // A definir por el grupo: el RF no dice con cuánta anticipación se envía
    public const DIAS_ANTICIPACION_RECORDATORIO = 2;

    /**
     * Salidas "Programada" que empiezan dentro de N días. El recordatorio se envía una vez
     * por día, así cada salida aparece un solo día y no hace falta registrar el envío.
     */
    public static function listarParaRecordar(): Collection
    {
        return self::where('estado', self::ESTADO_PROGRAMADA)
            ->whereDate('fecha_inicio', now()->addDays(self::DIAS_ANTICIPACION_RECORDATORIO)->toDateString())
            ->get();
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
