<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Excursion;
use App\Models\Paquete;
use App\Models\Refugio;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Pruebas del CU 15 - Listar reservas.
 * Correr con: php artisan test --filter=ListarReservasTest
 */
class ListarReservasTest extends TestCase
{
    use RefreshDatabase;

    private Paquete $paquete;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado

        $salida = SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create()->id,
        ]);
        $this->paquete = Paquete::factory()->create([
            'salida_programada_id' => $salida->id,
            'refugio_id' => Refugio::factory()->create()->id,
        ]);
    }

    private function reserva(Cliente $cliente, string $estado): Reserva
    {
        return Reserva::factory()->create([
            'cliente_id' => $cliente->id,
            'paquete_id' => $this->paquete->id,
            'estado' => $estado,
        ]);
    }

    public function test_3_1_el_cliente_solo_ve_sus_reservas(): void
    {
        $cliente = Cliente::factory()->create();
        $otroCliente = Cliente::factory()->create();
        $propia = $this->reserva($cliente, Reserva::ESTADO_PENDIENTE);
        $this->reserva($otroCliente, Reserva::ESTADO_CONFIRMADA);

        $this->actingAs($cliente->user)
            ->get(route('reservas.listar'))
            ->assertOk()
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->component('Reservas/Listar')
                ->where('esAdministrativo', false)
                ->has('reservas', 1)
                ->where('reservas.0.id', $propia->id)
            );
    }

    public function test_3_2_el_administrativo_ve_todas_las_reservas(): void
    {
        $this->reserva(Cliente::factory()->create(), Reserva::ESTADO_PENDIENTE);
        $this->reserva(Cliente::factory()->create(), Reserva::ESTADO_CANCELADA);

        $this->actingAs(User::factory()->create(['rol' => 'admin']))
            ->get(route('reservas.listar'))
            ->assertOk()
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->where('esAdministrativo', true)
                ->has('reservas', 2)
            );
    }

    public function test_no_se_listan_las_reservas_en_curso(): void
    {
        $cliente = Cliente::factory()->create();
        $this->reserva($cliente, Reserva::ESTADO_EN_CURSO);
        $this->reserva($cliente, Reserva::ESTADO_REALIZADA);

        $this->actingAs($cliente->user)
            ->get(route('reservas.listar'))
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->has('reservas', 1)
                ->where('reservas.0.estado', Reserva::ESTADO_REALIZADA)
            );
    }

    public function test_se_muestran_los_datos_de_cada_reserva(): void
    {
        $cliente = Cliente::factory()->create();
        $reserva = $this->reserva($cliente, Reserva::ESTADO_CONFIRMADA);
        $reserva->participantes()->createMany([
            ['dni' => '30111222', 'nombre' => 'Ana', 'apellido' => 'Gómez', 'fecha_nacimiento' => '1990-01-01'],
            ['dni' => '30111333', 'nombre' => 'Luis', 'apellido' => 'Gómez', 'fecha_nacimiento' => '1992-01-01'],
        ]);

        $this->actingAs($cliente->user)
            ->get(route('reservas.listar'))
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->where('reservas.0.paquete', $this->paquete->nombre)
                ->where('reservas.0.excursion', $this->paquete->salidaProgramada->excursion->nombre)
                ->where('reservas.0.cantidad_participantes', 2)
                ->where('reservas.0.situacion_de_pago', $reserva->situacion_de_pago)
                ->has('reservas.0.importe_total')
            );
    }

    public function test_3_3_sin_reservas_se_devuelve_el_listado_vacio(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($cliente->user)
            ->get(route('reservas.listar'))
            ->assertInertia(fn (Assert $pagina) => $pagina->has('reservas', 0));
    }

    public function test_un_invitado_no_puede_listar_reservas(): void
    {
        $this->get(route('reservas.listar'))->assertRedirect(route('login'));
    }
}
