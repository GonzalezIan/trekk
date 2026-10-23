<?php

namespace Tests\Feature;

use App\Models\Convenio;
use App\Models\Excursion;
use App\Models\Paquete;
use App\Models\Refugio;
use App\Models\SalidaProgramada;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Pagina;
use Tests\TestCase;

/**
 * Pruebas del CU 5 - Registrar paquete (y de los CU 24 y CU 26 que invoca).
 * Correr con: php artisan test --filter=RegistrarPaqueteTest
 */
class RegistrarPaqueteTest extends TestCase
{
    use RefreshDatabase;

    private SalidaProgramada $salida;
    private User $administrativo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado
        $this->travelTo('2026-10-20 10:00:00');
        $this->administrativo = User::factory()->create(['rol' => 'admin']);
        // 3 días -> 2 noches
        $this->salida = $this->salida('2026-11-10', '2026-11-12', ['precio_base' => 40000]);
    }

    private function salida(string $inicio, string $fin, array $cambios = []): SalidaProgramada
    {
        return SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create(['estado' => 'ACTIVA'])->id,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'estado' => 'PROGRAMADA',
            ...$cambios,
        ]);
    }

    /** Refugio con un convenio que va de $inicio a $fin. */
    private function refugio(string $inicio, string $fin, float $precioCama = 5000): Refugio
    {
        $refugio = Refugio::factory()->create();
        Convenio::create([
            'refugio_id' => $refugio->id,
            'nombre_convenio' => 'Temporada',
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'camas_totales' => 20,
            'camas_disponibles' => 20,
            'precio_cama' => $precioCama,
        ]);

        return $refugio;
    }

    private function registrar(array $datos, ?SalidaProgramada $salida = null)
    {
        return $this->actingAs($this->administrativo)
            ->post(route('paquetes.registrar.confirmar', ($salida ?? $this->salida)->id), [
                'nombre' => 'Clásico',
                'descripcion' => 'Travesía con guía',
                ...$datos,
            ]);
    }

    // ---------- Pasos 1 y 2: formulario con los datos de la salida ----------

    public function test_administrativo_ve_los_datos_que_el_paquete_toma_de_la_salida(): void
    {
        $this->actingAs($this->administrativo)
            ->get(route('paquetes.registrar', $this->salida->id))
            ->assertOk()
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->component('Paquetes/Registrar')
                ->where('salida.id', $this->salida->id)
                ->where('salida.precio_base', '40000.00')
                ->where('salida.noches', 2)
                ->has('salida.guia')
                ->has('salida.equipamiento'));
    }

    public function test_el_cliente_no_puede_registrar_paquetes(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'cliente']))
            ->get(route('paquetes.registrar', $this->salida->id))
            ->assertForbidden();
    }

    public function test_no_se_registran_paquetes_sobre_salidas_no_programadas(): void
    {
        $cancelada = $this->salida('2026-11-10', '2026-11-12', ['estado' => 'CANCELADA']);

        // RN-01
        $this->actingAs($this->administrativo)
            ->get(route('paquetes.registrar', $cancelada->id))
            ->assertRedirect(route('salidas.listar'))
            ->assertSessionHas('error');

        $this->registrar([], $cancelada)->assertRedirect(route('salidas.listar'));
        $this->assertSame(0, Paquete::count());
    }

    // ---------- Pasos 3 a 6: registro ----------

    public function test_sin_servicios_ni_alojamiento_el_precio_es_el_precio_base(): void
    {
        // RN-09
        $this->registrar([])
            ->assertRedirect(route('paquetes.listar', $this->salida->id))
            ->assertSessionHas('exito', 'El paquete “Clásico” fue registrado correctamente con un precio de $ 40.000,00.');

        $paquete = $this->salida->paquetes()->sole();
        $this->assertSame('Clásico', $paquete->nombre);
        $this->assertSame('Travesía con guía', $paquete->descripcion);
        $this->assertSame('40000.00', $paquete->precio_calculado);
        $this->assertSame('DISPONIBLE', $paquete->estado); // RN-10
        $this->assertNull($paquete->refugio_id);
    }

    public function test_el_precio_suma_servicios_y_alojamiento_por_noche(): void
    {
        $almuerzo = Servicio::factory()->create(['precio_servicio' => 1500]);
        $cena = Servicio::factory()->create(['precio_servicio' => 2500.50]);
        $refugio = $this->refugio('2026-11-01', '2026-11-30', 6000);

        // RN-04: 40000 + (1500 + 2500,50) + 6000 x 2 noches
        $this->registrar([
            'ids_servicios' => [$almuerzo->id, $cena->id],
            'id_refugio' => $refugio->id,
        ])->assertSessionHasNoErrors();

        $paquete = $this->salida->paquetes()->sole();
        $this->assertSame('56000.50', $paquete->precio_calculado);
        $this->assertSame($refugio->id, $paquete->refugio_id);
        $this->assertEqualsCanonicalizing([$almuerzo->id, $cena->id], $paquete->servicios->pluck('id')->all());
        $this->assertDatabaseHas('servicios_incluidos',
            ['paquete_id' => $paquete->id, 'servicio_id' => $almuerzo->id, 'cantidad' => 1]);
    }

    public function test_servicios_y_alojamiento_son_independientes(): void
    {
        // RN-03: solo alojamiento
        $refugio = $this->refugio('2026-11-01', '2026-11-30', 6000);
        $this->registrar(['nombre' => 'Con refugio', 'id_refugio' => $refugio->id])->assertSessionHasNoErrors();

        $this->assertSame('52000.00', $this->salida->paquetes()->where('nombre', 'Con refugio')->sole()->precio_calculado);
    }

    public function test_nombre_y_descripcion_son_obligatorios(): void
    {
        // Alternativo 4.1
        $this->registrar(['nombre' => '', 'descripcion' => ''])
            ->assertSessionHasErrors(['nombre', 'descripcion']);

        $this->assertSame(0, Paquete::count());
    }

    public function test_el_nombre_no_se_repite_en_la_misma_salida(): void
    {
        Paquete::factory()->create([
            'salida_programada_id' => $this->salida->id,
            'nombre' => 'Clásico',
            'estado' => 'DISPONIBLE',
        ]);

        // Alternativo 4.2 (RN-06), sin distinguir mayúsculas
        $this->registrar(['nombre' => '  CLÁSICO '])
            ->assertSessionHasErrors('nombre');
        $this->assertSame(1, Paquete::count());

        // En otra salida el mismo nombre está permitido
        $otra = $this->salida('2026-12-10', '2026-12-12');
        $this->registrar(['nombre' => 'Clásico'], $otra)->assertSessionHasNoErrors();
    }

    // ---------- Alternativo 3.1: CU 24 - Listar servicios ----------

    public function test_lista_los_servicios_con_su_precio(): void
    {
        Servicio::factory()->create(['nombre' => 'Desayuno', 'precio_servicio' => 1200]);

        $this->actingAs($this->administrativo)
            ->getJson(route('paquetes.registrar.servicios'))
            ->assertOk()
            ->assertJsonCount(1, 'servicios')
            ->assertJsonPath('servicios.0.nombre', 'Desayuno')
            ->assertJsonPath('servicios.0.precio_servicio', '1200.00');
    }

    // ---------- Alternativo 3.2: CU 26 - Listar refugios ----------

    public function test_lista_solo_refugios_admisibles_para_el_periodo(): void
    {
        $admisible = $this->refugio('2026-11-01', '2026-11-30', 6000);
        $this->refugio('2026-11-11', '2026-11-30');   // el convenio no cubre todo el período
        $ocupado = $this->refugio('2026-11-01', '2026-11-30');
        $conPaqueteCancelado = $this->refugio('2026-11-01', '2026-11-30');
        $conSalidaCancelada = $this->refugio('2026-11-01', '2026-11-30');

        // Ocupado por un paquete DISPONIBLE de una salida PROGRAMADA superpuesta
        Paquete::factory()->create([
            'salida_programada_id' => $this->salida('2026-11-12', '2026-11-14')->id,
            'refugio_id' => $ocupado->id,
            'estado' => 'DISPONIBLE',
        ]);
        // Un paquete cancelado o una salida cancelada no ocupan el refugio
        Paquete::factory()->create([
            'salida_programada_id' => $this->salida('2026-11-10', '2026-11-12')->id,
            'refugio_id' => $conPaqueteCancelado->id,
            'estado' => 'CANCELADO',
        ]);
        Paquete::factory()->create([
            'salida_programada_id' => $this->salida('2026-11-10', '2026-11-12', ['estado' => 'CANCELADA'])->id,
            'refugio_id' => $conSalidaCancelada->id,
            'estado' => 'DISPONIBLE',
        ]);

        $respuesta = $this->actingAs($this->administrativo)
            ->getJson(route('paquetes.registrar.refugios', $this->salida->id))
            ->assertOk();

        $this->assertEqualsCanonicalizing(
            [$admisible->id, $conPaqueteCancelado->id, $conSalidaCancelada->id],
            $respuesta->json('refugios.*.id'));

        $datos = collect($respuesta->json('refugios'))->firstWhere('id', $admisible->id);
        $this->assertEquals(6000, $datos['precio_cama']);
        $this->assertSame(20, $datos['camas_disponibles']);
    }

    public function test_sin_refugios_admisibles_el_listado_viene_vacio(): void
    {
        // Alternativo 3.2.1
        $this->actingAs($this->administrativo)
            ->getJson(route('paquetes.registrar.refugios', $this->salida->id))
            ->assertOk()
            ->assertJsonCount(0, 'refugios');
    }

    public function test_no_se_asocia_un_refugio_no_admisible(): void
    {
        $sinConvenio = $this->refugio('2026-11-11', '2026-11-30');

        $this->registrar(['id_refugio' => $sinConvenio->id])
            ->assertSessionHasErrors('id_refugio');

        $this->assertSame(0, Paquete::count());
    }
}
