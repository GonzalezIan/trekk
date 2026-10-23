<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Excursion;
use App\Models\Paquete;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Pagina;
use Tests\TestCase;

/**
 * Pruebas del CU 3 - Modificar excursión y CU 2 - Baja de excursión.
 * Correr con: php artisan test --filter=ModificarBajaExcursionTest
 */
class ModificarBajaExcursionTest extends TestCase
{
    use RefreshDatabase;

    private Excursion $excursion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado

        $this->excursion = Excursion::factory()->create([
            'nombre' => 'Laguna de los Témpanos',
            'estado' => 'ACTIVA',
            'duracion' => 3, // fija: la factory la elige al azar y el 2.1 la intenta cambiar a 5
        ]);
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
            'dificultad' => 'ALTA',
            'duracion' => 3,
            'experiencia_requerida' => 'SI',
            'acepta_menores' => false,
            'equipamiento_obligatorio' => 'Botas de trekking',
            'requiere_certificado_medico' => true,
        ], $cambios);
    }

    private function salida(string $estado = 'PROGRAMADA'): SalidaProgramada
    {
        return SalidaProgramada::factory()->create([
            'excursion_id' => $this->excursion->id,
            'estado' => $estado,
        ]);
    }

    private function paquete(SalidaProgramada $salida, string $estado = 'DISPONIBLE'): Paquete
    {
        return Paquete::factory()->create([
            'salida_programada_id' => $salida->id,
            'estado' => $estado,
        ]);
    }

    private function reserva(Paquete $paquete, string $estado): Reserva
    {
        return Reserva::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'paquete_id' => $paquete->id,
            'estado' => $estado,
        ]);
    }

    // ---------- CU 3 - Modificar excursión ----------

    public function test_el_formulario_muestra_los_datos_actuales_de_la_excursion(): void
    {
        $this->actingAs($this->administrativo())
            ->get(route('excursiones.modificar', $this->excursion->id))
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->component('Excursiones/Modificar')
                ->where('excursion.id', $this->excursion->id)
                ->where('excursion.nombre', 'Laguna de los Témpanos'));
    }

    public function test_el_administrativo_modifica_la_excursion(): void
    {
        $this->actingAs($this->administrativo())
            ->put(route('excursiones.actualizar', $this->excursion->id), $this->datosValidos())
            ->assertRedirect(route('excursiones.listar'))
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('excursiones', [
            'id' => $this->excursion->id,
            'dificultad' => 'ALTA',
            'duracion' => 3,
            'estado' => 'ACTIVA',
        ]);
    }

    public function test_conservar_el_mismo_nombre_no_cuenta_como_repetido(): void
    {
        $this->actingAs($this->administrativo())
            ->put(route('excursiones.actualizar', $this->excursion->id), $this->datosValidos())
            ->assertSessionDoesntHaveErrors('nombre');
    }

    public function test_no_se_puede_usar_el_nombre_de_otra_excursion(): void
    {
        Excursion::factory()->create(['nombre' => 'Cerro Fitz Roy']);

        $this->actingAs($this->administrativo())
            ->put(route('excursiones.actualizar', $this->excursion->id),
                $this->datosValidos(['nombre' => 'cerro fitz roy']))
            ->assertSessionHasErrors('nombre');
    }

    public function test_5_1_datos_invalidos_vuelven_al_formulario(): void
    {
        $this->actingAs($this->administrativo())
            ->put(route('excursiones.actualizar', $this->excursion->id),
                $this->datosValidos(['nombre' => '', 'duracion' => 0]))
            ->assertSessionHasErrors(['nombre', 'duracion']);

        $this->assertDatabaseHas('excursiones', [
            'id' => $this->excursion->id,
            'nombre' => 'Laguna de los Témpanos', // no se guardó nada
        ]);
    }

    public function test_2_1_con_reservas_activas_no_se_puede_modificar(): void
    {
        $this->reserva($this->paquete($this->salida()), 'CONFIRMADA');

        $this->actingAs($this->administrativo())
            ->get(route('excursiones.modificar', $this->excursion->id))
            ->assertRedirect(route('excursiones.listar'))
            ->assertSessionHas('error');

        $this->actingAs($this->administrativo())
            ->put(route('excursiones.actualizar', $this->excursion->id),
                $this->datosValidos(['duracion' => 5]))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('excursiones', ['id' => $this->excursion->id, 'duracion' => 5]);
    }

    public function test_las_reservas_canceladas_no_impiden_modificar(): void
    {
        $this->reserva($this->paquete($this->salida()), 'CANCELADA');

        $this->actingAs($this->administrativo())
            ->get(route('excursiones.modificar', $this->excursion->id))
            ->assertOk();
    }

    public function test_rn03_una_excursion_inactiva_no_se_puede_modificar(): void
    {
        $this->excursion->update(['estado' => 'INACTIVA']);

        $this->actingAs($this->administrativo())
            ->get(route('excursiones.modificar', $this->excursion->id))
            ->assertRedirect(route('excursiones.listar'))
            ->assertSessionHas('error');
    }

    // ---------- CU 2 - Baja de excursión ----------

    public function test_la_baja_inactiva_la_excursion_y_cancela_salidas_y_paquetes(): void
    {
        $programada = $this->salida('PROGRAMADA');
        $paquete = $this->paquete($programada);
        $finalizada = $this->salida('FINALIZADA');

        $this->actingAs($this->administrativo())
            ->patch(route('excursiones.baja', $this->excursion->id))
            ->assertRedirect(route('excursiones.listar'))
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('excursiones', ['id' => $this->excursion->id, 'estado' => 'INACTIVA']);
        $this->assertDatabaseHas('salidas_programadas', [
            'id' => $programada->id,
            'estado' => 'CANCELADA',
            'motivo_cancelacion' => 'Baja de la excursión',
        ]);
        $this->assertDatabaseHas('paquetes', ['id' => $paquete->id, 'estado' => 'CANCELADO']);
        // Las salidas que ya pasaron no se tocan
        $this->assertDatabaseHas('salidas_programadas', ['id' => $finalizada->id, 'estado' => 'FINALIZADA']);
    }

    public function test_con_reservas_activas_no_se_puede_dar_de_baja(): void
    {
        $salida = $this->salida();
        $this->reserva($this->paquete($salida), 'PENDIENTE');

        $this->actingAs($this->administrativo())
            ->patch(route('excursiones.baja', $this->excursion->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('excursiones', ['id' => $this->excursion->id, 'estado' => 'ACTIVA']);
        $this->assertDatabaseHas('salidas_programadas', ['id' => $salida->id, 'estado' => 'PROGRAMADA']);
    }

    public function test_una_excursion_inactiva_no_se_puede_dar_de_baja(): void
    {
        $this->excursion->update(['estado' => 'INACTIVA']);

        $this->actingAs($this->administrativo())
            ->patch(route('excursiones.baja', $this->excursion->id))
            ->assertSessionHas('error');
    }

    // ---------- Permisos ----------

    public function test_el_cliente_no_puede_modificar_ni_dar_de_baja(): void
    {
        $cliente = User::factory()->create(['rol' => 'cliente']);

        $this->actingAs($cliente)
            ->get(route('excursiones.modificar', $this->excursion->id))
            ->assertForbidden();

        $this->actingAs($cliente)
            ->patch(route('excursiones.baja', $this->excursion->id))
            ->assertForbidden();
    }
}