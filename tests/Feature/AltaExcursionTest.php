<?php

namespace Tests\Feature;

use App\Models\Excursion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas del CU 1 - Alta de excursión y CU 4 - Listar excursiones.
 * Correr con: php artisan test --filter=AltaExcursionTest
 */
class AltaExcursionTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado
    }

    private function administrativo(): User
    {
        return User::factory()->create(['rol' => 'admin']);
    }

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Laguna de los Témpanos',
            'descripcion' => 'Travesía hasta la laguna glaciar.',
            'recorrido' => 'Refugio base - Laguna - Refugio base',
            'etapas' => 'Aproximación, ascenso, regreso',
            'dificultad' => 'MEDIO',
            'duracion' => 2,
            'experiencia_requerida' => 'NO',
            'acepta_menores' => true,
            'equipamiento_obligatorio' => '',
            'requiere_certificado_medico' => false,
        ], $cambios);
    }

    public function test_el_administrativo_registra_una_excursion_en_estado_activa(): void
    {
        $this->actingAs($this->administrativo())
            ->post(route('excursiones.registrar'), $this->datosValidos())
            ->assertRedirect(route('excursiones.listar'))
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('excursiones', [
            'nombre' => 'Laguna de los Témpanos',
            'estado' => 'ACTIVA',
            'duracion' => 2,
        ]);
    }

    public function test_el_punto_de_encuentro_es_opcional_y_se_guarda(): void
    {
        $this->actingAs($this->administrativo())
            ->post(route('excursiones.registrar'), $this->datosValidos([
                'punto_de_encuentro' => 'Terminal de El Chaltén, andén 3',
            ]))
            ->assertSessionHasNoErrors();
        $this->actingAs($this->administrativo())
            ->post(route('excursiones.registrar'), $this->datosValidos(['nombre' => 'Otra', 'punto_de_encuentro' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Terminal de El Chaltén, andén 3',
            Excursion::where('nombre', 'Laguna de los Témpanos')->value('punto_de_encuentro'));
        $this->assertNull(Excursion::where('nombre', 'Otra')->value('punto_de_encuentro'));
    }

    public function test_rn01_los_datos_obligatorios_deben_completarse(): void
    {
        $this->actingAs($this->administrativo())
            ->post(route('excursiones.registrar'), [])
            ->assertSessionHasErrors([
                'nombre', 'descripcion', 'recorrido', 'etapas', 'dificultad', 'duracion',
                'experiencia_requerida', 'acepta_menores', 'requiere_certificado_medico',
            ])
            ->assertSessionDoesntHaveErrors('equipamiento_obligatorio');

        $this->assertDatabaseCount('excursiones', 0);
    }

    public function test_rn02_la_duracion_no_puede_ser_menor_a_un_dia(): void
    {
        $this->actingAs($this->administrativo())
            ->post(route('excursiones.registrar'), $this->datosValidos(['duracion' => 0]))
            ->assertSessionHasErrors('duracion');

        $this->assertDatabaseCount('excursiones', 0);
    }

    public function test_rn03_el_nombre_no_se_puede_repetir(): void
    {
        Excursion::factory()->create(['nombre' => 'Laguna de los Témpanos']);

        $this->actingAs($this->administrativo())
            ->post(route('excursiones.registrar'), $this->datosValidos(['nombre' => 'laguna de los témpanos ']))
            ->assertSessionHasErrors('nombre');

        $this->assertDatabaseCount('excursiones', 1);
    }

    public function test_un_cliente_no_puede_dar_de_alta_excursiones(): void
    {
        $cliente = User::factory()->create(['rol' => 'cliente']);

        $this->actingAs($cliente)->get(route('excursiones.alta'))->assertForbidden();
        $this->actingAs($cliente)
            ->post(route('excursiones.registrar'), $this->datosValidos())
            ->assertForbidden();
    }

    public function test_cu4_el_cliente_solo_ve_excursiones_activas(): void
    {
        Excursion::factory()->create(['nombre' => 'Activa', 'estado' => 'ACTIVA']);
        Excursion::factory()->create(['nombre' => 'Dada de baja', 'estado' => 'INACTIVA']);

        $cliente = User::factory()->create(['rol' => 'cliente']);

        $this->actingAs($cliente)->get(route('excursiones.listar'))
            ->assertInertia(fn ($pagina) => $pagina
                ->component('Excursiones/Listar')
                ->has('excursiones', 1)
                ->where('excursiones.0.nombre', 'Activa'));

        $this->actingAs($this->administrativo())->get(route('excursiones.listar'))
            ->assertInertia(fn ($pagina) => $pagina->has('excursiones', 2));
    }
}
