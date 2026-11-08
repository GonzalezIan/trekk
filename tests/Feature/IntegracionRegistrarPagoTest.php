<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Convenio;
use App\Models\Equipamiento;
use App\Models\Excursion;
use App\Models\Pago;
use App\Models\Paquete;
use App\Models\Participante;
use App\Models\Refugio;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use App\Services\Notificador;
use App\Services\PasarelaDePagos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Prueba de Integración basada en hilos - CU 22 Registrar pago de reserva.
 *
 * Cada hilo integra las clases que responden a un evento del sistema (una operación del DSS)
 * y se recorre mensaje por mensaje según el diagrama de secuencia (DS) del CU 22:
 *   ControladorReservas -> Reserva -> PasarelaDePagos -> Reserva.agregarPago() -> Pago
 *   -> Reserva.actualizarSituacionPago() -> [CU 14] confirmarRegistroReserva()
 *   -> confirmarSiCorresponde() -> Notificador.notificarConfirmacion()
 *
 * La pasarela y el notificador se reemplazan por espías que llaman al código real y además
 * registran con qué parámetros los invocaron y en qué estado estaba la base en ese momento.
 * Las escrituras en la base se registran en orden para comparar la secuencia con el DS.
 *
 * Correr con: php artisan test --filter=IntegracionRegistrarPagoTest
 */
class IntegracionRegistrarPagoTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;
    private SalidaProgramada $salida;
    private Paquete $paquete;
    private PasarelaEspia $pasarela;
    private NotificadorEspia $notificador;
    /** @var array<int, string> escrituras en la base, en orden */
    private array $escrituras = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Carbon::setTestNow('2026-11-07 10:00:00'); // resultados repetibles

        $this->cliente = Cliente::factory()->create();
        $excursion = Excursion::factory()->create([
            'estado' => 'ACTIVA',
            'acepta_menores' => true,
            'requiere_certificado_medico' => false,
        ]);
        $this->salida = SalidaProgramada::factory()->create([
            'excursion_id' => $excursion->id,
            'fecha_inicio' => '2026-11-17',
            'fecha_fin' => '2026-11-19',
            'cupo_maximo' => 10,
            'cupo_disponible' => 10,
            'estado' => 'PROGRAMADA',
        ]);
        $this->paquete = Paquete::factory()->create([
            'salida_programada_id' => $this->salida->id,
            'refugio_id' => null,
            'precio_calculado' => 500,
            'estado' => 'DISPONIBLE',
        ]);

        // Espías de los colaboradores externos del hilo
        $this->pasarela = new PasarelaEspia();
        $this->notificador = new NotificadorEspia();
        $this->app->instance(PasarelaDePagos::class, $this->pasarela);
        $this->app->instance(Notificador::class, $this->notificador);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ------------------------------------------------------------------ helpers

    /** Empieza a registrar las escrituras (INSERT / UPDATE / DELETE) sobre las tablas del hilo. */
    private function registrarEscrituras(): void
    {
        $this->escrituras = [];
        DB::listen(function ($query) {
            if (preg_match('/^(insert into|update|delete from) "(\w+)"/i', $query->sql, $m)) {
                $columnas = '';
                if (stripos($m[1], 'update') === 0 && preg_match('/set (.+?) where/i', $query->sql, $set)) {
                    preg_match_all('/"(\w+)" = \?/', $set[1], $cols);
                    $columnas = array_values(array_diff($cols[1], ['updated_at']));
                    sort($columnas); // el orden de las columnas dentro del UPDATE no importa
                    $columnas = ' ('.implode(', ', $columnas).')';
                }
                $this->escrituras[] = strtoupper(strtok($m[1], ' ')).' '.$m[2].$columnas;
            }
        });
    }

    private function conRefugio(int $camasDisponibles): Convenio
    {
        $refugio = Refugio::factory()->create();
        $this->paquete->update(['refugio_id' => $refugio->id]);

        return $refugio->convenios()->create([
            'nombre_convenio' => 'Convenio temporada',
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2027-03-31',
            'camas_totales' => 20,
            'camas_disponibles' => $camasDisponibles,
            'precio_cama' => 100,
        ]);
    }

    /** CU 14 pasos 1 a 14 (hilo previo, ya probado): reserva "En curso" de 2 adultos, lista para pagar. */
    private function reservaEnCursoListaParaPagar(): Reserva
    {
        $usuario = $this->cliente->user;
        $id = $this->actingAs($usuario)
            ->postJson(route('reservas.registrar.cantidad', $this->paquete), ['cantidad' => 2])
            ->assertOk()->json('reserva.id');
        $reserva = Reserva::findOrFail($id);

        $this->actingAs($usuario)->postJson(route('reservas.registrar.participantes', $reserva), ['participantes' => [
            ['dni' => '30111222', 'nombre' => 'Ana', 'apellido' => 'Pérez', 'fecha_nacimiento' => '1990-05-10'],
            ['dni' => '30111223', 'nombre' => 'Luis', 'apellido' => 'Pérez', 'fecha_nacimiento' => '1988-03-02'],
        ]])->assertOk();
        $this->actingAs($usuario)->postJson(route('reservas.registrar.continuar', $reserva))->assertOk();

        return $reserva->fresh();
    }

    /** Reserva "Pendiente" (llega desde el CU 15) de USD 1200 con 2 participantes. */
    private function reservaPendiente(string $requisitoMedico = Participante::REQUISITO_NO_REQUERIDO, bool $conSena = false): Reserva
    {
        $reserva = Reserva::factory()->create([
            'cliente_id' => $this->cliente->id,
            'paquete_id' => $this->paquete->id,
            'estado' => Reserva::ESTADO_PENDIENTE,
            'situacion_de_pago' => Reserva::PAGO_PENDIENTE_DE_PAGO_TOTAL,
            'importe_total' => 1200,
            'motivo_cancelacion' => null,
            'fecha_hora_vencimiento' => null,
        ]);
        foreach ([2, 3] as $i) {
            $reserva->participantes()->create([
                'dni' => '3011122'.$i, 'nombre' => 'Nombre '.$i, 'apellido' => 'Apellido',
                'fecha_nacimiento' => '1990-01-01', 'estado_requisito_medico' => $requisitoMedico,
            ]);
        }
        if ($conSena) {
            $reserva->pagos()->create([
                'monto' => 600, 'moneda' => 'USD', 'medio_pago' => 'Mercado Pago', 'tipo_pago' => Pago::TIPO_SENA,
                'fecha_hora_pago' => now()->subDay(), 'resultado_transaccion' => Pago::RESULTADO_APROBADA,
            ]);
        }

        return $reserva;
    }

    private function solicitarImporte(Reserva $reserva)
    {
        return $this->actingAs($this->cliente->user)->getJson(route('reservas.pago', $reserva));
    }

    private function confirmarPago(Reserva $reserva, array $datos = [])
    {
        return $this->actingAs($this->cliente->user)->postJson(route('reservas.pago.confirmar', $reserva),
            array_merge(['medio_pago' => 'Tarjeta de crédito', 'moneda' => 'USD'], $datos));
    }

    // ================================================================== HILO 1
    // Evento: registrarPagoReserva(idReserva) -> buscarReserva -> perteneceA -> temporizadorVigente
    //         -> admitePago -> calcularImportePendiente / calcularSeña -> mostrarImporte

    public function test_HI_01_registrar_pago_reserva_devuelve_el_importe_sin_modificar_la_base(): void
    {
        $reserva = $this->reservaEnCursoListaParaPagar();

        $this->registrarEscrituras();
        $this->solicitarImporte($reserva)->assertOk()->assertJson([
            'importe_pendiente' => 1000, // 2 x USD 500 (calcularImportePendiente)
            'importe_sena' => 500,       // 50 % (calcularSeña), solo desde el CU 14
            'tasa_usd_ars' => 1500,
            'medios_pago' => Pago::MEDIOS_PAGO,
        ]);

        $this->assertSame([], $this->escrituras, 'Mostrar el importe no debe escribir en la base.');
        $this->assertSame([], $this->pasarela->llamadas, 'Todavía no se debe invocar a la pasarela.');
    }

    public function test_HI_02_reserva_en_curso_vencida_se_anula_y_libera_cupos_y_camas(): void
    {
        $convenio = $this->conRefugio(camasDisponibles: 20);
        $reserva = $this->reservaEnCursoListaParaPagar();
        $this->assertSame(8, $this->salida->fresh()->cupo_disponible);
        $this->assertSame(18, $convenio->fresh()->camas_disponibles);

        Carbon::setTestNow(now()->addMinutes(Reserva::MINUTOS_RETENCION + 1)); // venció el temporizador

        $this->solicitarImporte($reserva)->assertStatus(410);

        $this->assertNull(Reserva::find($reserva->id), 'La reserva en curso vencida se descarta.');
        $this->assertSame(10, $this->salida->fresh()->cupo_disponible, 'SalidaProgramada.liberarCupo()');
        $this->assertSame(20, $convenio->fresh()->camas_disponibles, 'Refugio/Convenio.liberarCamas()');
        $this->assertSame([], $this->pasarela->llamadas);
    }

    // ================================================================== HILO 2
    // Evento: confirmarPago(medioPago) - pago total en USD de una reserva "Pendiente" (desde el CU 15)

    public function test_HI_03_pago_total_recorre_el_ds_en_orden_y_confirma_la_reserva(): void
    {
        $reserva = $this->reservaPendiente();

        $this->registrarEscrituras();
        $this->confirmarPago($reserva)->assertOk()
            ->assertJsonPath('situacion_de_pago', Reserva::PAGO_COMPLETO)
            ->assertJsonPath('estado', Reserva::ESTADO_CONFIRMADA);

        // procesarCobro(monto, moneda, medioPago): una vez, con los parámetros correctos y ANTES de registrar el pago
        $this->assertCount(1, $this->pasarela->llamadas);
        $this->assertEquals([
            'monto' => 1200.0, 'moneda' => 'USD', 'medio_pago' => 'Tarjeta de crédito', 'pagos_previos' => 0,
        ], $this->pasarela->llamadas[0]);

        // agregarPago -> Pago.create -> actualizarSituacionPago -> confirmarSiCorresponde
        $this->assertSame([
            'INSERT pagos',
            'UPDATE reservas (situacion_de_pago)',
            'UPDATE reservas (estado)',
        ], $this->escrituras);

        $this->assertDatabaseHas('pagos', [
            'reserva_id' => $reserva->id, 'monto' => 1200, 'moneda' => 'USD',
            'tipo_pago' => Pago::TIPO_TOTAL, 'resultado_transaccion' => Pago::RESULTADO_APROBADA,
        ]);

        // notificarConfirmacion(reserva): una vez, con la reserva ya confirmada en la base
        $this->assertEquals([[$reserva->id, Reserva::ESTADO_CONFIRMADA]], $this->notificador->confirmaciones);
    }

    // ================================================================== HILO 3
    // Evento: confirmarPago(medioPago) con seleccionarPagoSeña() desde el CU 14 (reserva "En curso")

    public function test_HI_04_sena_desde_el_cu14_registra_la_reserva_y_mantiene_cupos_y_camas(): void
    {
        $convenio = $this->conRefugio(camasDisponibles: 20);
        $reserva = $this->reservaEnCursoListaParaPagar();

        Carbon::setTestNow(now()->addMinutes(5)); // el cliente paga 5 minutos después, dentro de la retención
        $this->registrarEscrituras();
        $this->confirmarPago($reserva, ['sena' => true])->assertOk()
            ->assertJsonPath('situacion_de_pago', Reserva::PAGO_PENDIENTE_DE_PAGO_TOTAL)
            ->assertJsonPath('estado', Reserva::ESTADO_PENDIENTE);

        $this->assertEquals(['monto' => 500.0, 'moneda' => 'USD', 'medio_pago' => 'Tarjeta de crédito', 'pagos_previos' => 0],
            $this->pasarela->llamadas[0], 'procesarCobro recibe el 50 % del importe total.');

        // agregarPago(SEÑA) -> actualizarSituacionPago -> CU 14 confirmarRegistroReserva -> setFechaHoraVencimiento(24 h).
        // actualizarSituacionPago() se ejecuta, pero la reserva en curso ya estaba "Pendiente de pago total":
        // como el valor no cambia, Eloquent no genera el UPDATE de situacion_de_pago.
        $this->assertSame([
            'INSERT pagos',
            'UPDATE reservas (estado, fecha_hora_registro, fecha_hora_vencimiento)',
            'UPDATE reservas (fecha_hora_vencimiento)',
        ], $this->escrituras);

        $reserva->refresh();
        $this->assertSame(Pago::TIPO_SENA, $reserva->pagos()->first()->tipo_pago);
        $this->assertEquals(now()->addMinutes(Reserva::MINUTOS_PAGO_TOTAL), $reserva->fecha_hora_vencimiento);

        // Los recursos retenidos en el CU 14 siguen retenidos: no se liberan ni se vuelven a retener
        $this->assertSame(8, $this->salida->fresh()->cupo_disponible);
        $this->assertSame(18, $convenio->fresh()->camas_disponibles);
        $this->assertSame([], $this->notificador->confirmaciones, 'Solo con seña la reserva no se confirma.');
    }

    // ================================================================== HILO 4
    // Evento: confirmarPago(medioPago) del saldo restante (alternativo 1.2, desde el CU 15)

    public function test_HI_05_saldo_restante_completa_el_pago_y_confirma(): void
    {
        $reserva = $this->reservaPendiente(conSena: true);

        $this->solicitarImporte($reserva)->assertJsonPath('importe_pendiente', 600);
        $this->confirmarPago($reserva, ['medio_pago' => 'Mercado Pago'])->assertOk();

        $this->assertEquals(['monto' => 600.0, 'moneda' => 'USD', 'medio_pago' => 'Mercado Pago', 'pagos_previos' => 1],
            $this->pasarela->llamadas[0]);
        $this->assertSame([Pago::TIPO_SENA, Pago::TIPO_SALDO_RESTANTE],
            $reserva->pagos()->orderBy('id')->pluck('tipo_pago')->all());
        $this->assertSame(Reserva::PAGO_COMPLETO, $reserva->fresh()->situacion_de_pago);
        $this->assertEquals([[$reserva->id, Reserva::ESTADO_CONFIRMADA]], $this->notificador->confirmaciones);
    }

    // ================================================================== HILO 5
    // Evento: confirmarPago(medioPago) con seleccionarMoneda("ARS") -> convertirAARS(importe)

    public function test_HI_06_pago_en_pesos_pasa_el_monto_convertido_a_la_pasarela_y_al_pago(): void
    {
        $reserva = $this->reservaPendiente();

        $this->confirmarPago($reserva, ['moneda' => 'ARS'])->assertOk();

        $this->assertEquals(['monto' => 1800000.0, 'moneda' => 'ARS', 'medio_pago' => 'Tarjeta de crédito', 'pagos_previos' => 0],
            $this->pasarela->llamadas[0], '1200 USD x 1500 = 1.800.000 ARS');
        $this->assertDatabaseHas('pagos', ['reserva_id' => $reserva->id, 'monto' => 1800000, 'moneda' => 'ARS']);
        $this->assertSame(Reserva::PAGO_COMPLETO, $reserva->fresh()->situacion_de_pago);
    }

    // ================================================================== HILO 6
    // Evento: confirmarPago(medioPago) con la transacción rechazada (alternativo 5.1)

    public function test_HI_07_pago_rechazado_no_registra_nada_ni_libera_recursos(): void
    {
        $convenio = $this->conRefugio(camasDisponibles: 20);
        $reserva = $this->reservaEnCursoListaParaPagar();

        $this->registrarEscrituras();
        $this->confirmarPago($reserva, ['simular_rechazo' => true])->assertStatus(422);

        $this->assertCount(1, $this->pasarela->llamadas, 'Se consulta a la pasarela una sola vez.');
        $this->assertSame([], $this->escrituras, 'Rechazada: no se invoca agregarPago ni se cambia la reserva.');
        $this->assertSame(Reserva::ESTADO_EN_CURSO, $reserva->fresh()->estado, 'Se vuelve al paso 3.');
        $this->assertSame(8, $this->salida->fresh()->cupo_disponible, 'El rechazo no libera cupos.');
        $this->assertSame(18, $convenio->fresh()->camas_disponibles, 'El rechazo no libera camas.');
        $this->assertSame([], $this->notificador->confirmaciones);

        // El cliente reintenta con otro medio y el hilo se completa
        $this->confirmarPago($reserva, ['medio_pago' => 'Tarjeta de débito'])->assertOk();
        $this->assertSame(1, $reserva->pagos()->count());
    }

    // ================================================================== HILO 7
    // Evento: confirmarPago(medioPago) con una falla después de crear el pago (actualización parcial)

    public function test_HI_08_falla_al_actualizar_la_reserva_deshace_el_pago(): void
    {
        $reserva = $this->reservaPendiente();

        // Se fuerza una falla en actualizarSituacionPago(), después del INSERT del pago
        Reserva::updating(function (Reserva $r) {
            if ($r->isDirty('situacion_de_pago')) {
                throw new RuntimeException('Falla forzada al actualizar la situación de pago');
            }
        });

        $this->confirmarPago($reserva)->assertStatus(500);

        $this->assertDatabaseCount('pagos', 0);
        $this->assertSame(Reserva::PAGO_PENDIENTE_DE_PAGO_TOTAL, $reserva->fresh()->situacion_de_pago);
        $this->assertSame(Reserva::ESTADO_PENDIENTE, $reserva->fresh()->estado);
        $this->assertSame([], $this->notificador->confirmaciones, 'No se avisa de un pago que se deshizo.');
    }

    // ================================================================== HILO 8
    // Evento: confirmarPago(medioPago) con requisitos médicos pendientes (Reserva <-> Participante)

    public function test_HI_09_pago_completo_con_certificados_pendientes_no_confirma(): void
    {
        $reserva = $this->reservaPendiente(requisitoMedico: Participante::REQUISITO_PENDIENTE);

        $this->registrarEscrituras();
        $this->confirmarPago($reserva)->assertOk()
            ->assertJsonPath('situacion_de_pago', Reserva::PAGO_COMPLETO)
            ->assertJsonPath('estado', Reserva::ESTADO_PENDIENTE);

        $this->assertSame(['INSERT pagos', 'UPDATE reservas (situacion_de_pago)'], $this->escrituras,
            'confirmarSiCorresponde() no cambia el estado si hay requisitos pendientes.');
        $this->assertSame([], $this->notificador->confirmaciones);
    }

    // ================================================================== HILO 9
    // Evento: confirmarPago(medioPago) fuera de orden: la reserva en curso todavía no tiene
    // sus participantes (se salteó ingresarDatosParticipantes del CU 14). RN-01 del CU 14.

    public function test_HI_10_pago_de_una_reserva_sin_participantes_se_rechaza_antes_de_cobrar(): void
    {
        $id = $this->actingAs($this->cliente->user)
            ->postJson(route('reservas.registrar.cantidad', $this->paquete), ['cantidad' => 2])
            ->assertOk()->json('reserva.id');
        $reserva = Reserva::findOrFail($id);

        $this->registrarEscrituras();
        $this->confirmarPago($reserva)->assertStatus(422);

        $this->assertSame([], $this->pasarela->llamadas, 'No se debe invocar procesarCobro.');
        $this->assertSame([], $this->escrituras, 'No se debe invocar agregarPago ni cambiar la reserva.');
        $this->assertSame(Reserva::ESTADO_EN_CURSO, $reserva->fresh()->estado);
        $this->assertSame(8, $this->salida->fresh()->cupo_disponible, 'Los cupos siguen retenidos.');
    }

    // ================================================================== HILO 10
    // Evento: confirmarPago(medioPago) fuera de orden: después de agregarEquipamiento (CU 14, 13.1)
    // se paga sin pasar por continuar(). El monto de procesarCobro tiene que incluir el alquiler.

    public function test_HI_11_pago_despues_de_agregar_equipamiento_cobra_el_importe_con_el_alquiler(): void
    {
        $carpa = Equipamiento::factory()->create(['nombre' => 'Carpa', 'stock' => 4, 'precio_alquiler' => 30]);
        $usuario = $this->cliente->user;
        $reserva = Reserva::findOrFail($this->actingAs($usuario)
            ->postJson(route('reservas.registrar.cantidad', $this->paquete), ['cantidad' => 2])
            ->assertOk()->json('reserva.id'));
        $this->actingAs($usuario)->postJson(route('reservas.registrar.participantes', $reserva), ['participantes' => [
            ['dni' => '30111222', 'nombre' => 'Ana', 'apellido' => 'Pérez', 'fecha_nacimiento' => '1990-05-10'],
            ['dni' => '30111223', 'nombre' => 'Luis', 'apellido' => 'Pérez', 'fecha_nacimiento' => '1988-03-02'],
        ]])->assertOk();
        $this->actingAs($usuario)->postJson(route('reservas.registrar.equipamiento.agregar', $reserva), [
            'equipamientos' => [['id' => $carpa->id, 'cantidad' => 3]],
        ])->assertOk();

        $this->confirmarPago($reserva)->assertOk();

        // (2 x USD 500) + (3 x USD 30) = USD 1090
        $this->assertEquals(['monto' => 1090.0, 'moneda' => 'USD', 'medio_pago' => 'Tarjeta de crédito', 'pagos_previos' => 0],
            $this->pasarela->llamadas[0], 'procesarCobro recibe el importe con el equipamiento alquilado.');
        $this->assertDatabaseHas('pagos', ['reserva_id' => $reserva->id, 'monto' => 1090, 'tipo_pago' => Pago::TIPO_TOTAL]);
        $this->assertEquals(1090, $reserva->fresh()->importe_total);
    }
}

/** Espía de la Pasarela de Pagos: registra cada procesarCobro y delega en la pasarela real. */
class PasarelaEspia extends PasarelaDePagos
{
    public array $llamadas = [];

    public function procesarCobro(float $monto, string $moneda, string $medioPago, bool $simularRechazo = false): string
    {
        $this->llamadas[] = [
            'monto' => $monto,
            'moneda' => $moneda,
            'medio_pago' => $medioPago,
            'pagos_previos' => Pago::count(), // para verificar que se llama antes de agregarPago()
        ];

        return parent::procesarCobro($monto, $moneda, $medioPago, $simularRechazo);
    }
}

/** Espía del Notificador: registra qué reserva se notificó y su estado en la base en ese momento. */
class NotificadorEspia extends Notificador
{
    public array $confirmaciones = [];

    public function notificarConfirmacion(Reserva $reserva): void
    {
        $this->confirmaciones[] = [$reserva->id, Reserva::find($reserva->id)?->estado];
        parent::notificarConfirmacion($reserva);
    }
}
