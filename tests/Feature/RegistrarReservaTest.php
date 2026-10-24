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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Pagina;
use Tests\TestCase;

/**
 * Pruebas del CU 14 - Registrar reserva.
 * Correr con: php artisan test --filter=RegistrarReservaTest
 */
class RegistrarReservaTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;
    private Excursion $excursion;
    private SalidaProgramada $salida;
    private Paquete $paquete;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado

        $this->cliente = Cliente::factory()->create();
        $this->excursion = Excursion::factory()->create([
            'estado' => 'ACTIVA',
            'acepta_menores' => true,
            'requiere_certificado_medico' => false,
            'equipamiento_obligatorio' => 'Botas de trekking, campera impermeable',
        ]);
        $this->salida = SalidaProgramada::factory()->create([
            'excursion_id' => $this->excursion->id,
            'fecha_inicio' => now()->addDays(10)->toDateString(),
            'fecha_fin' => now()->addDays(12)->toDateString(),
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
    }

    private function conRefugio(int $camasDisponibles): Convenio
    {
        $refugio = Refugio::factory()->create();
        $this->paquete->update(['refugio_id' => $refugio->id]);

        return $refugio->convenios()->create([
            'nombre_convenio' => 'Convenio temporada',
            'fecha_inicio' => now()->subMonth()->toDateString(),
            'fecha_fin' => now()->addMonths(2)->toDateString(),
            'camas_totales' => 20,
            'camas_disponibles' => $camasDisponibles,
            'precio_cama' => 100,
        ]);
    }

    private function ingresarCantidad(int|string $cantidad)
    {
        return $this->actingAs($this->cliente->user)
            ->postJson(route('reservas.registrar.cantidad', $this->paquete), ['cantidad' => $cantidad]);
    }

    /** Pasos 1 a 6: reserva "En curso" con los cupos retenidos. */
    private function reservaEnCurso(int $cantidad = 2): Reserva
    {
        $id = $this->ingresarCantidad($cantidad)->assertOk()->json('reserva.id');

        return Reserva::findOrFail($id);
    }

    private function participante(string $dni, string $fechaNacimiento = '1990-05-10'): array
    {
        return ['dni' => $dni, 'nombre' => 'Nombre '.$dni, 'apellido' => 'Apellido', 'fecha_nacimiento' => $fechaNacimiento];
    }

    private function cargarParticipantes(Reserva $reserva, array $participantes)
    {
        return $this->actingAs($this->cliente->user)
            ->postJson(route('reservas.registrar.participantes', $reserva), ['participantes' => $participantes]);
    }

    /** Pasos 1 a 14 con dos adultos: lista para pagar. */
    private function reservaListaParaPagar(): Reserva
    {
        $reserva = $this->reservaEnCurso(2);
        $this->cargarParticipantes($reserva, [$this->participante('30111222'), $this->participante('30111223')])->assertOk();
        $this->actingAs($this->cliente->user)->postJson(route('reservas.registrar.continuar', $reserva))->assertOk();

        return $reserva->fresh();
    }

    private function pagar(Reserva $reserva, bool $sena)
    {
        return $this->actingAs($this->cliente->user)
            ->postJson(route('reservas.pago.confirmar', $reserva), [
                'medio_pago' => 'Tarjeta de crédito',
                'moneda' => 'USD',
                'sena' => $sena,
            ]);
    }

    // ---------- Flujo normal ----------

    public function test_1_3_el_sistema_solicita_la_cantidad_de_participantes(): void
    {
        $this->actingAs($this->cliente->user)
            ->get(route('reservas.registrar', $this->paquete))
            ->assertOk()
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->component('Reservas/Registrar')
                ->where('paquete.id', $this->paquete->id)
                ->where('paquete.lugares_disponibles', 10)
                ->where('minutosRetencion', 20));
    }

    public function test_5_retiene_los_cupos_y_la_reserva_queda_en_curso_por_20_minutos(): void
    {
        $this->freezeSecond();

        $respuesta = $this->ingresarCantidad(3)->assertOk()
            ->assertJsonPath('reserva.cantidad_participantes', 3)
            ->assertJsonPath('reserva.segundos_restantes', 20 * 60);

        $reserva = Reserva::findOrFail($respuesta->json('reserva.id'));
        $this->assertSame(Reserva::ESTADO_EN_CURSO, $reserva->estado);
        $this->assertSame($this->cliente->id, $reserva->cliente_id); // paso 1
        $this->assertTrue($reserva->fecha_hora_vencimiento->equalTo(now()->addMinutes(20)));
        $this->assertSame(7, $this->salida->fresh()->cupo_disponible);
    }

    public function test_10_registra_los_participantes_y_determina_el_requisito_medico(): void
    {
        $this->excursion->update(['requiere_certificado_medico' => true]);
        $reserva = $this->reservaEnCurso(2);

        $this->cargarParticipantes($reserva, [
            $this->participante('30.111.222'),
            $this->participante('45111222', now()->subYears(12)->toDateString()),
        ])
            ->assertOk()
            ->assertJsonPath('participantes.0.estado_requisito_medico', Participante::REQUISITO_PENDIENTE)
            ->assertJsonPath('equipamiento_obligatorio', 'Botas de trekking, campera impermeable');

        $this->assertDatabaseHas('participantes', ['reserva_id' => $reserva->id, 'dni' => '30111222']);
        $this->assertSame(2, $reserva->participantes()->count());
    }

    public function test_10_sin_certificado_requerido_los_participantes_quedan_no_requerido(): void
    {
        $reserva = $this->reservaEnCurso(1);

        $this->cargarParticipantes($reserva, [$this->participante('30111222')])
            ->assertOk()
            ->assertJsonPath('participantes.0.estado_requisito_medico', Participante::REQUISITO_NO_REQUERIDO);
    }

    public function test_volver_a_confirmar_los_participantes_reemplaza_la_carga_anterior(): void
    {
        $reserva = $this->reservaEnCurso(1);
        $this->cargarParticipantes($reserva, [$this->participante('30111222')])->assertOk();

        $this->cargarParticipantes($reserva, [$this->participante('30999888')])->assertOk();

        $this->assertSame(['30999888'], $reserva->participantes()->pluck('dni')->all());
    }

    public function test_13_14_sin_equipamiento_el_importe_es_precio_por_cantidad(): void
    {
        $reserva = $this->reservaEnCurso(2);
        $this->cargarParticipantes($reserva, [$this->participante('30111222'), $this->participante('30111223')]);

        $this->actingAs($this->cliente->user)
            ->postJson(route('reservas.registrar.continuar', $reserva))
            ->assertOk()
            ->assertJsonPath('importe.importe_total', 1000)
            ->assertJsonPath('importe.equipamiento', []);

        $this->assertEquals(1000, $reserva->fresh()->importe_total);
    }

    public function test_15_confirmar_la_reserva_vigente_habilita_el_pago(): void
    {
        $reserva = $this->reservaListaParaPagar();

        $this->actingAs($this->cliente->user)
            ->postJson(route('reservas.registrar.confirmar', $reserva))
            ->assertOk()
            ->assertJsonPath('reserva.id', $reserva->id);
    }

    // ---------- Alternativos ----------

    public function test_4_1_cantidad_invalida(): void
    {
        foreach ([0, -2, 'dos', ''] as $cantidad) {
            $this->ingresarCantidad($cantidad)->assertStatus(422)->assertJsonValidationErrors('cantidad');
        }

        $this->assertSame(0, Reserva::count());
        $this->assertSame(10, $this->salida->fresh()->cupo_disponible);
    }

    public function test_5_1_no_hay_cupo_suficiente_en_la_salida(): void
    {
        $this->salida->update(['cupo_disponible' => 2]);

        $this->ingresarCantidad(3)
            ->assertStatus(422)
            ->assertJsonPath('errors.cantidad.0', 'No hay cupos suficientes para el paquete. Lugares disponibles: 2.');

        $this->assertSame(0, Reserva::count());
    }

    public function test_5_1_no_hay_camas_suficientes_en_el_refugio(): void
    {
        $convenio = $this->conRefugio(camasDisponibles: 1);

        $this->ingresarCantidad(2)->assertStatus(422)->assertJsonValidationErrors('cantidad');

        $this->assertSame(1, $convenio->fresh()->camas_disponibles);
        $this->assertSame(10, $this->salida->fresh()->cupo_disponible);
    }

    public function test_5_2_con_alojamiento_tambien_retiene_las_camas(): void
    {
        $convenio = $this->conRefugio(camasDisponibles: 5);

        $this->reservaEnCurso(2);

        $this->assertSame(3, $convenio->fresh()->camas_disponibles);
        $this->assertSame(8, $this->salida->fresh()->cupo_disponible);
    }

    public function test_8_1_no_se_puede_confirmar_con_datos_faltantes(): void
    {
        $reserva = $this->reservaEnCurso(2);

        $this->cargarParticipantes($reserva, [
            $this->participante('30111222'),
            ['dni' => '30111223', 'nombre' => '', 'apellido' => 'Apellido', 'fecha_nacimiento' => ''],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['participantes.1.nombre', 'participantes.1.fecha_nacimiento']);

        $this->assertSame(0, $reserva->participantes()->count());
    }

    public function test_8_hay_que_cargar_un_participante_por_cupo_y_sin_dni_repetidos(): void
    {
        $reserva = $this->reservaEnCurso(2);

        $this->cargarParticipantes($reserva, [$this->participante('30111222')])
            ->assertStatus(422)->assertJsonValidationErrors('participantes');

        $this->cargarParticipantes($reserva, [$this->participante('30111222'), $this->participante('30111222')])
            ->assertStatus(422)->assertJsonValidationErrors('participantes.0.dni');
    }

    public function test_9_1_la_excursion_no_acepta_menores(): void
    {
        $this->excursion->update(['acepta_menores' => false]);
        $reserva = $this->reservaEnCurso(2);

        $this->cargarParticipantes($reserva, [
            $this->participante('30111222'),
            $this->participante('45111222', now()->subYears(15)->toDateString()),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['participantes', 'participantes.1.fecha_nacimiento'])
            ->assertJsonMissingValidationErrors('participantes.0.fecha_nacimiento');

        // No continúa con el registro: no queda ningún participante guardado
        $this->assertSame(0, $reserva->participantes()->count());
    }

    public function test_9_1_la_edad_se_toma_a_la_fecha_de_la_salida(): void
    {
        $this->excursion->update(['acepta_menores' => false]);
        $reserva = $this->reservaEnCurso(1);

        // Hoy tiene 17, pero cumple 18 antes de la salida
        $this->cargarParticipantes($reserva, [
            $this->participante('45111222', now()->subYears(18)->addDays(5)->toDateString()),
        ])->assertOk();
    }

    public function test_9_2_ningun_participante_es_mayor_de_edad(): void
    {
        $reserva = $this->reservaEnCurso(2);

        $this->cargarParticipantes($reserva, [
            $this->participante('45111222', now()->subYears(10)->toDateString()),
            $this->participante('45111223', now()->subYears(12)->toDateString()),
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.participantes.0', 'La reserva debe incluir al menos un participante mayor de edad (+18 años).');

        $this->assertSame(0, $reserva->participantes()->count());
    }

    public function test_12_1_lista_el_equipamiento_con_stock_en_el_periodo_de_la_salida(): void
    {
        $carpa = Equipamiento::factory()->create(['nombre' => 'Carpa', 'stock' => 4]);
        Equipamiento::factory()->create(['nombre' => 'Bastón', 'stock' => 0]);
        $reserva = $this->reservaEnCurso(1);

        $this->actingAs($this->cliente->user)
            ->getJson(route('reservas.registrar.equipamiento', $reserva))
            ->assertOk()
            ->assertJsonCount(1, 'equipamientos')
            ->assertJsonPath('equipamientos.0.id', $carpa->id)
            ->assertJsonPath('equipamientos.0.stock_disponible', 4);
    }

    public function test_12_1_agrega_el_equipamiento_lo_retiene_y_suma_al_importe(): void
    {
        $carpa = Equipamiento::factory()->create(['nombre' => 'Carpa', 'stock' => 4, 'precio_alquiler' => 30]);
        $reserva = $this->reservaEnCurso(2);
        $this->cargarParticipantes($reserva, [$this->participante('30111222'), $this->participante('30111223')]);

        $this->actingAs($this->cliente->user)
            ->postJson(route('reservas.registrar.equipamiento.agregar', $reserva), [
                'equipamientos' => [['id' => $carpa->id, 'cantidad' => 3]],
            ])
            ->assertOk()
            ->assertJsonPath('equipamiento.0.cantidad', 3);

        // Retenido para el período de la salida
        $this->assertSame(1, $carpa->stockDisponible(...$this->salida->getFechas()));

        // 12.1 paso 5: (500 x 2) + (30 x 3)
        $this->actingAs($this->cliente->user)
            ->postJson(route('reservas.registrar.continuar', $reserva))
            ->assertJsonPath('importe.importe_total', 1090);
    }

    public function test_12_1_modificar_la_seleccion_reemplaza_la_anterior(): void
    {
        $carpa = Equipamiento::factory()->create(['nombre' => 'Carpa', 'stock' => 4]);
        $reserva = $this->reservaEnCurso(1);
        $agregar = fn (array $equipamientos) => $this->actingAs($this->cliente->user)
            ->postJson(route('reservas.registrar.equipamiento.agregar', $reserva), ['equipamientos' => $equipamientos]);

        $agregar([['id' => $carpa->id, 'cantidad' => 4]])->assertOk();
        // Lo que retiene la propia reserva cuenta como disponible para ella
        $agregar([['id' => $carpa->id, 'cantidad' => 2]])->assertOk();
        $this->assertSame(2, $carpa->stockDisponible(...$this->salida->getFechas()));

        // Selección vacía: continúa sin equipamiento
        $agregar([])->assertOk();
        $this->assertSame(4, $carpa->stockDisponible(...$this->salida->getFechas()));
    }

    public function test_12_1_1_no_hay_stock_suficiente_y_se_conserva_la_seleccion_anterior(): void
    {
        $carpa = Equipamiento::factory()->create(['nombre' => 'Carpa', 'stock' => 4]);
        $baston = Equipamiento::factory()->create(['nombre' => 'Bastón', 'stock' => 1]);
        $reserva = $this->reservaEnCurso(1);
        $agregar = fn (array $equipamientos) => $this->actingAs($this->cliente->user)
            ->postJson(route('reservas.registrar.equipamiento.agregar', $reserva), ['equipamientos' => $equipamientos]);
        $agregar([['id' => $carpa->id, 'cantidad' => 1]])->assertOk();

        $agregar([['id' => $carpa->id, 'cantidad' => 2], ['id' => $baston->id, 'cantidad' => 3]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['equipamientos.1.cantidad' => 'No hay stock suficiente de Bastón: quedan 1 disponibles.']);

        $this->assertSame([$carpa->id => 1], $reserva->equipos_alquilados()->pluck('equipos_alquilados.cantidad', 'equipamientos.id')->all());
    }

    public function test_19_1_con_la_sena_queda_pendiente_de_pago_total_y_vence_en_24_horas(): void
    {
        $this->freezeSecond();
        $reserva = $this->reservaListaParaPagar();

        $this->pagar($reserva, sena: true)
            ->assertOk()
            ->assertJsonPath('estado', Reserva::ESTADO_PENDIENTE)
            ->assertJsonPath('situacion_de_pago', Reserva::PAGO_PENDIENTE_DE_PAGO_TOTAL);

        $reserva->refresh();
        $this->assertSame(Reserva::ESTADO_PENDIENTE, $reserva->estado);
        $this->assertTrue($reserva->fecha_hora_vencimiento->equalTo(now()->addHours(24)));
        $this->assertDatabaseHas('pagos', ['reserva_id' => $reserva->id, 'monto' => 500, 'tipo_pago' => Pago::TIPO_SENA]);
        // Paso 21: los cupos quedan consolidados
        $this->assertSame(8, $this->salida->fresh()->cupo_disponible);
    }

    public function test_19_2_20_2_pago_total_sin_requisito_medico_queda_confirmada_sin_vencimiento(): void
    {
        $reserva = $this->reservaListaParaPagar();

        $this->pagar($reserva, sena: false)
            ->assertOk()
            ->assertJsonPath('estado', Reserva::ESTADO_CONFIRMADA);

        $reserva->refresh();
        $this->assertSame(Reserva::PAGO_COMPLETO, $reserva->situacion_de_pago);
        $this->assertNull($reserva->fecha_hora_vencimiento);
    }

    public function test_20_1_pago_total_con_requisito_medico_pendiente_queda_pendiente(): void
    {
        $this->excursion->update(['requiere_certificado_medico' => true]);
        $reserva = $this->reservaListaParaPagar();

        $this->pagar($reserva, sena: false)
            ->assertOk()
            ->assertJsonPath('estado', Reserva::ESTADO_PENDIENTE);

        $this->assertNull($reserva->fresh()->fecha_hora_vencimiento);
    }

    public function test_17_1_el_cliente_cancela_antes_de_pagar_y_se_libera_todo(): void
    {
        $convenio = $this->conRefugio(camasDisponibles: 5);
        $carpa = Equipamiento::factory()->create(['stock' => 4]);
        $reserva = $this->reservaEnCurso(2);
        $this->actingAs($this->cliente->user)
            ->postJson(route('reservas.registrar.equipamiento.agregar', $reserva), [
                'equipamientos' => [['id' => $carpa->id, 'cantidad' => 2]],
            ]);

        $this->actingAs($this->cliente->user)
            ->postJson(route('reservas.registrar.cancelar', $reserva))
            ->assertOk();

        $this->assertModelMissing($reserva);
        $this->assertSame(10, $this->salida->fresh()->cupo_disponible);
        $this->assertSame(5, $convenio->fresh()->camas_disponibles);
        $this->assertSame(4, $carpa->stockDisponible(...$this->salida->getFechas()));
    }

    public function test_a_la_reserva_en_curso_vencida_libera_los_cupos_y_termina(): void
    {
        $reserva = $this->reservaEnCurso(3);
        $this->travel(21)->minutes();

        $this->cargarParticipantes($reserva, [$this->participante('30111222')])->assertStatus(410);

        $this->assertModelMissing($reserva);
        $this->assertSame(10, $this->salida->fresh()->cupo_disponible);
    }

    public function test_15_no_se_puede_pagar_una_reserva_en_curso_vencida(): void
    {
        $reserva = $this->reservaListaParaPagar();
        $this->travel(21)->minutes();

        $this->pagar($reserva, sena: false)->assertStatus(410);

        $this->assertSame(0, Pago::count());
        $this->assertModelMissing($reserva);
    }

    // ---------- Precondiciones ----------

    public function test_el_administrativo_no_registra_reservas(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($admin)->get(route('reservas.registrar', $this->paquete))->assertForbidden();
    }

    public function test_un_paquete_cancelado_no_admite_reservas(): void
    {
        $this->paquete->update(['estado' => Paquete::ESTADO_CANCELADO]);

        $this->ingresarCantidad(1)->assertForbidden();
    }

    public function test_no_se_puede_seguir_la_reserva_en_curso_de_otro_cliente(): void
    {
        $reserva = $this->reservaEnCurso(1);
        $otro = Cliente::factory()->create();

        $this->actingAs($otro->user)
            ->postJson(route('reservas.registrar.participantes', $reserva), ['participantes' => [$this->participante('30111222')]])
            ->assertForbidden();
        $this->actingAs($otro->user)->postJson(route('reservas.registrar.cancelar', $reserva))->assertForbidden();
    }

    public function test_la_reserva_en_curso_no_aparece_en_el_listado(): void
    {
        $this->reservaEnCurso(1);

        $this->actingAs($this->cliente->user)
            ->get(route('reservas.listar'))
            ->assertInertia(fn (Pagina $pagina) => $pagina->has('reservas', 0));
    }
}
