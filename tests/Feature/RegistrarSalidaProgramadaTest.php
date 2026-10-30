<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Equipamiento;
use App\Models\Excursion;
use App\Models\Paquete;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Pagina;
use Tests\TestCase;

/**
 * Pruebas del CU 8 - Registrar Salida Programada (y de los CU 23 y CU 25 que invoca).
 * Correr con: php artisan test --filter=RegistrarSalidaProgramadaTest
 */
class RegistrarSalidaProgramadaTest extends TestCase
{
    use RefreshDatabase;

    private Excursion $excursion;
    private User $administrativo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado
        $this->travelTo('2026-10-20 10:00:00'); // "ahora" fijo para la regla del período futuro
        $this->excursion = Excursion::factory()->create(['estado' => 'ACTIVA', 'duracion' => 3]);
        $this->administrativo = User::factory()->create(['rol' => 'admin']);
    }

    /** Período válido para una excursión de 3 días. */
    private function datosValidos(array $cambios = []): array
    {
        return [
            'fecha_inicio' => '2026-11-10',
            'hora_de_salida' => '08:30',
            'cupo_maximo' => 12,
            'precio_base' => 45000,
            ...$cambios,
        ];
    }

    private function empleado(string $categoria, string $estado = 'ACTIVO'): Empleado
    {
        return Empleado::factory()->create(['categoria' => $categoria, 'estado' => $estado]);
    }

    /** Salida de otra excursión, con el empleado asignado y/o equipamiento incluido. */
    private function otraSalida(string $inicio, string $fin, string $estado = 'PROGRAMADA'): SalidaProgramada
    {
        return SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create(['estado' => 'ACTIVA'])->id,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'estado' => $estado,
        ]);
    }

    /** Pasos 1 a 4: deja los datos de la salida validados en la sesión. */
    private function ingresarDatos(array $cambios = [])
    {
        return $this->actingAs($this->administrativo)
            ->postJson(route('salidas.registrar.datos', $this->excursion), $this->datosValidos($cambios));
    }

    // ---------- Pasos 1 y 2: formulario ----------

    public function test_administrativo_ve_el_formulario_de_la_excursion_activa(): void
    {
        $this->actingAs($this->administrativo)
            ->get(route('salidas.registrar', $this->excursion))
            ->assertOk()
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->component('Salidas/Registrar')
                ->where('excursion.id', $this->excursion->id)
                ->where('excursion.duracion', 3));
    }

    public function test_el_cliente_no_puede_registrar_salidas(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'cliente']))
            ->get(route('salidas.registrar', $this->excursion))
            ->assertForbidden();
    }

    public function test_no_se_registran_salidas_sobre_excursiones_inactivas(): void
    {
        $inactiva = Excursion::factory()->create(['estado' => 'INACTIVA', 'duracion' => 3]);

        // RN-03
        $this->actingAs($this->administrativo)
            ->get(route('salidas.registrar', $inactiva))
            ->assertRedirect(route('excursiones.listar'))
            ->assertSessionHas('error');

        $this->postJson(route('salidas.registrar.datos', $inactiva), $this->datosValidos())
            ->assertStatus(422);
    }

    // ---------- Pasos 3 y 4: validación de los datos ----------

    public function test_datos_validos_quedan_guardados_para_los_pasos_siguientes(): void
    {
        $this->ingresarDatos()
            ->assertOk()
            ->assertJson(['datosValidos' => true]);

        $this->assertSame('2026-11-10', session('cu8.datosSalida.fecha_inicio'));
        $this->assertSame($this->excursion->id, session('cu8.datosSalida.excursion_id'));
    }

    public function test_periodo_no_futuro_no_es_valido(): void
    {
        // Alternativo 4.1
        $this->ingresarDatos(['fecha_inicio' => '2026-10-20'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_inicio']);
    }

    public function test_la_fecha_de_fin_se_calcula_con_la_duracion(): void
    {
        $this->ingresarDatos()->assertOk();

        $this->assertSame('2026-11-12', session('cu8.datosSalida.fecha_fin'));
    }

    public function test_se_ignora_la_fecha_de_fin_enviada(): void
    {
        $this->ingresarDatos(['fecha_fin' => '2026-12-25'])->assertOk();

        $this->assertSame('2026-11-12', session('cu8.datosSalida.fecha_fin'));
    }

    public function test_excursion_de_un_dia_empieza_y_termina_el_mismo_dia(): void
    {
        $this->excursion->update(['duracion' => 1]);

        $this->ingresarDatos(['fecha_inicio' => '2026-11-10'])->assertOk();

        $this->assertSame('2026-11-10', session('cu8.datosSalida.fecha_fin'));
    }

    public function test_cupo_y_precio_deben_ser_mayores_a_cero(): void
    {
        // Alternativo 4.3
        $this->ingresarDatos(['cupo_maximo' => 0, 'precio_base' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'cupo_maximo' => 'El cupo máximo y el precio base deben ser mayores a cero.',
                'precio_base' => 'El cupo máximo y el precio base deben ser mayores a cero.',
            ]);
    }

    // ---------- Paso 5: CU 23 - Listar empleados ----------

    public function test_lista_solo_empleados_activos_de_la_categoria_y_sin_otra_salida_en_el_periodo(): void
    {
        $libre = $this->empleado('GUIA');
        $conSalidaCancelada = $this->empleado('GUIA');
        $ocupado = $this->empleado('GUIA');
        $this->empleado('GUIA', 'INACTIVO');  // RN-01
        $this->empleado('ASISTENTE');         // otra categoría

        // RN-02: se superpone con 10/11 - 12/11
        $this->otraSalida('2026-11-12', '2026-11-14')->empleados()->attach($ocupado->id, ['rol' => 'GUIA']);
        // No se superpone, o está cancelada: no ocupan al empleado
        $this->otraSalida('2026-11-13', '2026-11-15')->empleados()->attach($libre->id, ['rol' => 'GUIA']);
        $this->otraSalida('2026-11-10', '2026-11-12', 'CANCELADA')
            ->empleados()->attach($conSalidaCancelada->id, ['rol' => 'GUIA']);

        $this->ingresarDatos();

        $ids = $this->getJson(route('salidas.registrar.empleados', ['categoria' => 'GUIA']))
            ->assertOk()
            ->json('empleados.*.id');

        $this->assertEqualsCanonicalizing([$libre->id, $conSalidaCancelada->id], $ids);
    }

    public function test_sin_guias_disponibles_el_listado_viene_vacio(): void
    {
        // Alternativo 5.1
        $this->ingresarDatos();

        $this->getJson(route('salidas.registrar.empleados', ['categoria' => 'GUIA']))
            ->assertOk()
            ->assertJsonCount(0, 'empleados');
    }

    public function test_no_se_listan_empleados_sin_haber_ingresado_los_datos(): void
    {
        $this->actingAs($this->administrativo)
            ->getJson(route('salidas.registrar.empleados', ['categoria' => 'GUIA']))
            ->assertStatus(409);
    }

    // ---------- Alternativo 6.2: CU 25 - Listar equipamientos ----------

    public function test_el_stock_disponible_descuenta_lo_comprometido_en_el_periodo(): void
    {
        $carpa = Equipamiento::factory()->create(['nombre' => 'Carpa', 'stock' => 10]);
        $agotado = Equipamiento::factory()->create(['nombre' => 'Bastón', 'stock' => 2]);

        // 4 carpas incluidas en una salida superpuesta (y 2 en una cancelada, que no cuentan)
        $superpuesta = $this->otraSalida('2026-11-11', '2026-11-13');
        $superpuesta->equipoPrestado()->attach($carpa->id, ['cantidad' => 4]);
        $superpuesta->equipoPrestado()->attach($agotado->id, ['cantidad' => 2]);
        $this->otraSalida('2026-11-10', '2026-11-12', 'CANCELADA')
            ->equipoPrestado()->attach($carpa->id, ['cantidad' => 2]);

        // 3 carpas alquiladas en una reserva de esa salida (y 1 en una reserva cancelada)
        $paquete = Paquete::factory()->create(['salida_programada_id' => $superpuesta->id]);
        foreach (['CONFIRMADA' => 3, 'CANCELADA' => 1] as $estado => $cantidad) {
            Reserva::factory()->create([
                'cliente_id' => Cliente::factory()->create()->id,
                'paquete_id' => $paquete->id,
                'estado' => $estado,
            ])->equipos_alquilados()->attach($carpa->id, ['cantidad' => $cantidad]);
        }

        $this->ingresarDatos();

        // RN-06: el bastón no tiene stock en el período
        $this->getJson(route('salidas.registrar.equipamiento'))
            ->assertOk()
            ->assertJsonCount(1, 'equipamientos')
            ->assertJsonPath('equipamientos.0.nombre', 'Carpa')
            ->assertJsonPath('equipamientos.0.stock_disponible', 3);
    }

    // ---------- Pasos 6 a 9: registro ----------

    public function test_registra_la_salida_con_guia_asistentes_y_equipamiento(): void
    {
        $guia = $this->empleado('GUIA');
        $asistente = $this->empleado('ASISTENTE');
        $carpa = Equipamiento::factory()->create(['stock' => 20]);
        $mochila = Equipamiento::factory()->create(['stock' => 8]);

        $this->ingresarDatos(['cupo_maximo' => 12]);

        $this->post(route('salidas.registrar.confirmar'), [
            'id_guia' => $guia->id,
            'ids_asistentes' => [$asistente->id],
            'ids_equipamiento' => [$carpa->id, $mochila->id],
        ])
            ->assertRedirect(route('salidas.listar', ['excursion' => $this->excursion->id]))
            ->assertSessionHas('exito');

        $salida = $this->excursion->salidasProgramadas()->sole();

        // RN-07: el cupo queda en el menor entre 12 y el stock de cada equipo (8). RN-10.
        $this->assertSame(8, $salida->cupo_maximo);
        $this->assertSame(8, $salida->cupo_disponible);
        $this->assertSame('PROGRAMADA', $salida->estado);
        $this->assertSame('2026-11-10', $salida->fecha_inicio->format('Y-m-d'));
        $this->assertSame('08:30', $salida->hora_de_salida->format('H:i'));

        // RN-04
        $this->assertDatabaseHas('empleados_asignados',
            ['salida_programada_id' => $salida->id, 'empleado_id' => $guia->id, 'rol' => 'GUIA']);
        $this->assertDatabaseHas('empleados_asignados',
            ['salida_programada_id' => $salida->id, 'empleado_id' => $asistente->id, 'rol' => 'ASISTENTE']);

        // RN-08: de cada equipo se reserva una cantidad igual al cupo máximo
        $this->assertDatabaseHas('equipos_prestados',
            ['salida_programada_id' => $salida->id, 'equipamiento_id' => $carpa->id, 'cantidad' => 8]);
        $this->assertDatabaseHas('equipos_prestados',
            ['salida_programada_id' => $salida->id, 'equipamiento_id' => $mochila->id, 'cantidad' => 8]);

        $this->assertNull(session('cu8.datosSalida'));
    }

    public function test_sin_asistentes_ni_equipamiento_se_respeta_el_cupo_ingresado(): void
    {
        $guia = $this->empleado('GUIA');
        $this->ingresarDatos(['cupo_maximo' => 12]);

        $this->post(route('salidas.registrar.confirmar'), ['id_guia' => $guia->id])
            ->assertSessionHasNoErrors();

        $salida = $this->excursion->salidasProgramadas()->sole();
        $this->assertSame(12, $salida->cupo_maximo);
        $this->assertSame(0, $salida->equipoPrestado()->count());
    }

    public function test_el_guia_es_obligatorio(): void
    {
        $this->ingresarDatos();

        $this->post(route('salidas.registrar.confirmar'), [])
            ->assertSessionHasErrors('id_guia');

        $this->assertSame(0, SalidaProgramada::count());
    }

    public function test_no_se_asigna_personal_ocupado_o_de_otra_categoria(): void
    {
        $guia = $this->empleado('GUIA');
        $asistenteComoGuia = $this->empleado('ASISTENTE');
        $asistenteOcupado = $this->empleado('ASISTENTE');
        $this->otraSalida('2026-11-09', '2026-11-10')
            ->empleados()->attach($asistenteOcupado->id, ['rol' => 'ASISTENTE']);

        $this->ingresarDatos();

        // RN-05
        $this->post(route('salidas.registrar.confirmar'), ['id_guia' => $asistenteComoGuia->id])
            ->assertSessionHasErrors('id_guia');

        $this->post(route('salidas.registrar.confirmar'), [
            'id_guia' => $guia->id,
            'ids_asistentes' => [$asistenteOcupado->id],
        ])->assertSessionHasErrors('ids_asistentes');

        $this->assertSame(0, $this->excursion->salidasProgramadas()->count());
    }

    public function test_no_se_incluye_equipamiento_sin_stock_en_el_periodo(): void
    {
        $guia = $this->empleado('GUIA');
        $baston = Equipamiento::factory()->create(['stock' => 2]);
        $this->otraSalida('2026-11-12', '2026-11-12')
            ->equipoPrestado()->attach($baston->id, ['cantidad' => 2]);

        $this->ingresarDatos();

        // RN-06
        $this->post(route('salidas.registrar.confirmar'), [
            'id_guia' => $guia->id,
            'ids_equipamiento' => [$baston->id],
        ])->assertSessionHasErrors('ids_equipamiento');

        $this->assertSame(0, $this->excursion->salidasProgramadas()->count());
    }

    public function test_sin_datos_ingresados_no_se_registra_nada(): void
    {
        $guia = $this->empleado('GUIA');

        $this->actingAs($this->administrativo)
            ->post(route('salidas.registrar.confirmar'), ['id_guia' => $guia->id])
            ->assertRedirect(route('excursiones.listar'))
            ->assertSessionHas('error');

        $this->assertSame(0, SalidaProgramada::count());
    }
}
