<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Convenio;
use App\Models\Excursion;
use App\Models\Paquete;
use App\Models\Refugio;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Pagina;
use Tests\TestCase;

/**
 * Pruebas del CU 7 - Listar paquetes.
 * Correr con: php artisan test --filter=ListarPaquetesTest
 */
class ListarPaquetesTest extends TestCase
{
    use RefreshDatabase;

    private SalidaProgramada $salida;
    private Refugio $refugio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado
        $this->travelTo('2026-10-20 10:00:00');

        $this->salida = SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create()->id,
            'fecha_inicio' => '2026-11-10',
            'fecha_fin' => '2026-11-12',
            'hora_de_salida' => '08:00:00',
            'cupo_maximo' => 12,
            'cupo_disponible' => 10,
        ]);
        $this->refugio = Refugio::factory()->create();
    }

    private function paquete(string $nombre, string $estado = 'DISPONIBLE', ?SalidaProgramada $salida = null): Paquete
    {
        return Paquete::factory()->create([
            'salida_programada_id' => ($salida ?? $this->salida)->id,
            'refugio_id' => $this->refugio->id,
            'nombre' => $nombre,
            'estado' => $estado,
        ]);
    }

    private function convenio(string $desde, string $hasta, int $camasDisponibles, string $nombre = 'Temporada'): Convenio
    {
        return Convenio::create([
            'refugio_id' => $this->refugio->id,
            'nombre_convenio' => $nombre,
            'fecha_inicio' => $desde,
            'fecha_fin' => $hasta,
            'camas_totales' => 20,
            'camas_disponibles' => $camasDisponibles,
            'precio_cama' => 5000,
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

    private function administrativo(): User
    {
        return User::factory()->create(['rol' => 'admin']);
    }

    private function cliente(): User
    {
        return User::factory()->create(['rol' => 'cliente']);
    }

    // ---------- Cliente ----------


    public function test_cliente_no_puede_ver_paquetes_de_una_salida_no_reservable(): void
    {
        // A menos de 48 hs: el CU 9 no se la muestra al Cliente
        $proxima = SalidaProgramada::factory()->create([
            'excursion_id' => $this->salida->excursion_id,
            'fecha_inicio' => '2026-10-21',
            'fecha_fin' => '2026-10-21',
            'hora_de_salida' => '08:00:00',
        ]);
        $this->paquete('Paquete de salida próxima', 'DISPONIBLE', $proxima);

        $this->actingAs($this->cliente())
            ->get(route('paquetes.listar', $proxima->id))
            ->assertNotFound();

        // El Administrativo sí la puede consultar
        $this->actingAs($this->administrativo())
            ->get(route('paquetes.listar', $proxima->id))
            ->assertOk();
    }

    public function test_cliente_ve_solo_los_paquetes_disponibles_de_la_salida(): void
    {
        $paquete = $this->paquete('A - Con refugio');
        $paquete->servicios()->attach(Servicio::factory()->create(['nombre' => 'Cena'])->id, ['cantidad' => 2]);
        $this->paquete('B - Cancelado', 'CANCELADO');
        $this->paquete('C - Otra salida', 'DISPONIBLE', SalidaProgramada::factory()->create([
            'excursion_id' => $this->salida->excursion_id,
        ]));

        $this->actingAs($this->cliente())
            ->get(route('paquetes.listar', $this->salida->id))
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->component('Paquetes/Listar')
                ->where('esAdministrativo', false)
                ->where('salida.id', $this->salida->id)
                ->has('paquetes', 1)
                ->where('paquetes.0.id', $paquete->id)
                ->where('paquetes.0.servicios', ['Cena x2'])
                ->has('paquetes.0', fn (Pagina $p) => $p
                    ->hasAll(['id', 'nombre', 'descripcion', 'refugio', 'servicios', 'precio', 'estado',
                        'cupo_disponible', 'camas_disponibles', 'lugares_disponibles'])));
    }

    public function test_la_disponibilidad_es_el_menor_entre_cupo_y_camas_del_refugio(): void
    {
        $this->paquete('Con refugio');
        $this->convenio('2026-10-01', '2026-12-31', 4);
        $this->convenio('2026-11-11', '2026-12-31', 50, 'No cubre la salida'); // empieza después

        $this->actingAs($this->cliente())
            ->get(route('paquetes.listar', $this->salida->id))
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->where('paquetes.0.cupo_disponible', 10)
                ->where('paquetes.0.camas_disponibles', 4)
                ->where('paquetes.0.lugares_disponibles', 4));
    }

    public function test_si_hay_mas_camas_que_cupo_manda_el_cupo_de_la_salida(): void
    {
        $this->paquete('Con refugio');
        $this->convenio('2026-11-01', '2026-11-30', 30);

        $this->actingAs($this->cliente())
            ->get(route('paquetes.listar', $this->salida->id))
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->where('paquetes.0.camas_disponibles', 30)
                ->where('paquetes.0.lugares_disponibles', 10));
    }

    public function test_salida_sin_paquetes_disponibles_devuelve_listado_vacio(): void
    {
        $this->paquete('Cancelado', 'CANCELADO');

        $this->actingAs($this->cliente())
            ->get(route('paquetes.listar', $this->salida->id))
            ->assertInertia(fn (Pagina $pagina) => $pagina->has('paquetes', 0));
    }

    // ---------- Administrativo ----------

    public function test_administrativo_ve_todos_los_paquetes_con_sus_reservas_activas(): void
    {
        $disponible = $this->paquete('A - Disponible');
        $this->paquete('B - Cancelado', 'CANCELADO');

        $this->reserva($disponible, 'PENDIENTE');
        $this->reserva($disponible, 'CONFIRMADA');
        $this->reserva($disponible, 'CANCELADA'); // no cuenta
        $this->reserva($disponible, 'EN_CURSO');  // todavía no se registró: no cuenta

        $this->actingAs($this->administrativo())
            ->get(route('paquetes.listar', $this->salida->id))
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->where('esAdministrativo', true)
                ->has('paquetes', 2)
                ->where('paquetes.0.id', $disponible->id)
                ->where('paquetes.0.reservas_activas', 2)
                ->where('paquetes.1.estado', 'CANCELADO')
                ->where('paquetes.1.reservas_activas', 0));
    }

    // ---------- Errores ----------

    public function test_salida_inexistente_recibe_404(): void
    {
        $this->actingAs($this->cliente())
            ->get(route('paquetes.listar', 9999))
            ->assertNotFound();
    }

    public function test_usuario_no_autenticado_va_al_login(): void
    {
        $this->get(route('paquetes.listar', $this->salida->id))->assertRedirect(route('login'));
    }
}