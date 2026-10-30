<?php

namespace Tests\Feature;

use App\Models\Excursion;
use App\Models\Paquete;
use App\Models\SalidaProgramada;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Pagina;
use Tests\TestCase;

/**
 * Pruebas del CU 9 - Listar Salidas Programadas.
 * Correr con: php artisan test --filter=ListarSalidasTest
 */
class ListarSalidasTest extends TestCase
{
    use RefreshDatabase;

    private Excursion $excursion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado
        $this->travelTo('2026-10-20 10:00:00'); // "ahora" fijo para la regla de 48 hs
        $this->excursion = Excursion::factory()->create();
    }

    private function salida(string $fecha, string $hora, string $estado = 'PROGRAMADA', ?Excursion $excursion = null): SalidaProgramada
    {
        return SalidaProgramada::factory()->create([
            'excursion_id' => ($excursion ?? $this->excursion)->id,
            'fecha_inicio' => $fecha,
            'fecha_fin' => $fecha,
            'hora_de_salida' => $hora,
            'estado' => $estado,
        ]);
    }

    private function administrativo(): User
    {
        return User::factory()->create(['rol' => 'admin']);
    }

    private function cliente(): User
    {
        return User::factory()->create(['rol' => 'cliente']);
    }

    // ---------- Cliente (alternativos 3.1 y 4.1) ----------

    public function test_cliente_ve_solo_las_salidas_reservables_de_la_excursion(): void
    {
        $dentroDe5Dias = $this->salida('2026-10-25', '08:00:00');
        $dentroDe49Hs = $this->salida('2026-10-22', '11:00:00');
        $this->salida('2026-10-22', '09:00:00');                // 47 hs: no se puede reservar
        $this->salida('2026-10-25', '08:00:00', 'CANCELADA');   // RN-01: no está PROGRAMADA
        $this->salida('2026-10-25', '08:00:00', 'PROGRAMADA', Excursion::factory()->create()); // otra excursión

        $this->actingAs($this->cliente())
            ->get(route('salidas.listar', ['excursion' => $this->excursion->id]))
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->component('Salidas/Listar')
                ->where('esAdministrativo', false)
                ->where('excursion.id', $this->excursion->id)
                ->has('salidas', 2)
                ->where('salidas.0.id', $dentroDe49Hs->id)   // ordenadas por fecha y hora
                ->where('salidas.1.id', $dentroDe5Dias->id));
    }

    public function test_cliente_recibe_la_ficha_de_la_excursion_y_los_paquetes_disponibles_de_cada_salida(): void
    {
        $salida = $this->salida('2026-10-25', '08:00:00');
        $disponible = Paquete::factory()->create(['salida_programada_id' => $salida->id, 'estado' => 'DISPONIBLE']);
        Paquete::factory()->create(['salida_programada_id' => $salida->id, 'estado' => 'CANCELADO']);

        $this->actingAs($this->cliente())
            ->get(route('salidas.listar', ['excursion' => $this->excursion->id]))
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->where('excursion.descripcion', $this->excursion->descripcion)
                ->where('excursion.etapas', $this->excursion->etapas)
                ->has("paquetes.{$salida->id}", 1)
                ->where("paquetes.{$salida->id}.0.id", $disponible->id));
    }

    public function test_cliente_sin_excursion_seleccionada_vuelve_al_listado_de_excursiones(): void
    {
        $this->actingAs($this->cliente())
            ->get(route('salidas.listar'))
            ->assertRedirect(route('excursiones.listar'));
    }

    public function test_cliente_con_excursion_inexistente_recibe_404(): void
    {
        $this->actingAs($this->cliente())
            ->get(route('salidas.listar', ['excursion' => 9999]))
            ->assertNotFound();
    }

    public function test_excursion_sin_salidas_devuelve_listado_vacio(): void
    {
        // Alternativo 3.3: la página informa que no hay salidas
        $this->actingAs($this->cliente())
            ->get(route('salidas.listar', ['excursion' => $this->excursion->id]))
            ->assertInertia(fn (Pagina $pagina) => $pagina->has('salidas', 0));
    }

    // ---------- Administrativo (alternativos 3.2 y 4.2) ----------

    public function test_administrativo_ve_todas_las_salidas_sin_importar_el_estado(): void
    {
        $this->salida('2026-10-25', '08:00:00');
        $this->salida('2026-10-21', '08:00:00');                // a menos de 48 hs: igual la ve
        $this->salida('2026-10-25', '08:00:00', 'CANCELADA');   // RN-02

        $this->actingAs($this->administrativo())
            ->get(route('salidas.listar'))
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->where('esAdministrativo', true)
                ->has('salidas', 3)
                ->has('salidas.0', fn (Pagina $salida) => $salida
                    ->hasAll(['id', 'excursion', 'fecha_inicio', 'fecha_fin', 'hora_de_salida',
                        'guia', 'asistentes', 'equipamiento', 'cupo_maximo', 'cupo_disponible',
                        'precio_base', 'cantidad_paquetes', 'estado'])));
    }

    public function test_administrativo_filtra_por_excursion_estado_y_fechas(): void
    {
        $otra = Excursion::factory()->create();
        $this->salida('2026-10-25', '08:00:00');
        $this->salida('2026-11-15', '08:00:00');
        $this->salida('2026-10-25', '08:00:00', 'CANCELADA');
        $this->salida('2026-10-25', '08:00:00', 'PROGRAMADA', $otra);

        $admin = $this->administrativo();
        $cantidad = fn (array $filtros) => $this->actingAs($admin)
            ->get(route('salidas.listar', $filtros))
            ->viewData('page')['props']['salidas'];

        $this->assertCount(3, $cantidad(['excursion' => $this->excursion->id]));
        $this->assertCount(1, $cantidad(['estado' => 'CANCELADA']));
        $this->assertCount(3, $cantidad(['desde' => '2026-10-01', 'hasta' => '2026-10-31']));
        $this->assertCount(1, $cantidad(['excursion' => $otra->id, 'estado' => 'PROGRAMADA']));
    }

    public function test_usuario_no_autenticado_va_al_login(): void
    {
        $this->get(route('salidas.listar'))->assertRedirect(route('login'));
    }
}
