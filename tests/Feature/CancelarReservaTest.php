<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Convenio;
use App\Models\Equipamiento;
use App\Models\Excursion;
use App\Models\Paquete;
use App\Models\Refugio;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use App\Models\User;
use Carbon\CarbonInterface;
use App\Notifications\ReservaCancelada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Pruebas del CU 16 - Cancelar reserva.
 * Correr con: php artisan test --filter=CancelarReservaTest
 */
class CancelarReservaTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;
    private SalidaProgramada $salida;
    private Convenio $convenio;
    private Paquete $paquete;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado

        $this->cliente = Cliente::factory()->create();
        $this->salida = SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create()->id,
            'fecha_inicio' => now()->addDays(10)->toDateString(),
            'fecha_fin' => now()->addDays(12)->toDateString(),
            'cupo_maximo' => 10,
            'cupo_disponible' => 6,
        ]);

        $refugio = Refugio::factory()->create();
        $this->convenio = $this->convenio($refugio, now()->subMonth(), now()->addMonths(2), totales: 20, disponibles: 10);
        $this->paquete = Paquete::factory()->create([
            'salida_programada_id' => $this->salida->id,
            'refugio_id' => $refugio->id,
        ]);
    }

    private function convenio(Refugio $refugio, CarbonInterface $desde, CarbonInterface $hasta, int $totales, int $disponibles): Convenio
    {
        return $refugio->convenios()->create([
            'nombre_convenio' => 'Convenio '.$desde->format('Y-m-d'),
            'fecha_inicio' => $desde->toDateString(),
            'fecha_fin' => $hasta->toDateString(),
            'camas_totales' => $totales,
            'camas_disponibles' => $disponibles,
            'precio_cama' => 100,
        ]);
    }

    private function reserva(string $estado = Reserva::ESTADO_PENDIENTE, int $participantes = 3, ?Cliente $cliente = null): Reserva
    {
        $reserva = Reserva::factory()->create([
            'cliente_id' => ($cliente ?? $this->cliente)->id,
            'paquete_id' => $this->paquete->id,
            'estado' => $estado,
            'motivo_cancelacion' => null,
        ]);

        foreach (range(1, $participantes) as $i) {
            $reserva->participantes()->create([
                'dni' => '3011122'.$i,
                'nombre' => 'Nombre '.$i,
                'apellido' => 'Apellido '.$i,
                'fecha_nacimiento' => '1990-01-01',
                'estado_requisito_medico' => 'NO_REQUERIDO',
            ]);
        }

        return $reserva;
    }

    private function cancelar(Reserva $reserva, string $motivo = 'Cambio de planes', ?User $usuario = null)
    {
        return $this->actingAs($usuario ?? $this->cliente->user)
            ->postJson(route('reservas.cancelar.confirmar', $reserva), ['motivo' => $motivo]);
    }

    // ---------- Flujo normal ----------

    public function test_7_se_notifica_al_cliente_la_cancelacion_con_el_motivo(): void
    {
        Notification::fake();
        $reserva = $this->reserva();

        $this->cancelar($reserva, 'Me lesioné', User::factory()->create(['rol' => 'admin']))->assertOk();

        Notification::assertSentTo($this->cliente->user, ReservaCancelada::class,
            fn (ReservaCancelada $aviso) => $aviso->origen === ReservaCancelada::ORIGEN_RESERVA
                && $aviso->reserva->motivo_cancelacion === 'Me lesioné');
    }

    public function test_si_falta_el_motivo_no_se_notifica(): void
    {
        Notification::fake();

        $this->cancelar($this->reserva(), '')->assertUnprocessable();

        Notification::assertNothingSent();
    }

    public function test_1_3_el_sistema_obtiene_los_datos_y_solicita_el_motivo(): void
    {
        $reserva = $this->reserva();

        $this->actingAs($this->cliente->user)
            ->getJson(route('reservas.cancelar', $reserva))
            ->assertOk()
            ->assertJsonPath('reserva.id', $reserva->id)
            ->assertJsonPath('reserva.cantidad_participantes', 3);
    }

    public function test_5_registra_el_motivo_y_cambia_el_estado_a_cancelada(): void
    {
        $reserva = $this->reserva();

        $this->cancelar($reserva, '  No puedo viajar  ')
            ->assertOk()
            ->assertJsonPath('mensaje', 'La reserva fue cancelada. No corresponde reintegro de los importes abonados.');

        $reserva->refresh();
        $this->assertSame(Reserva::ESTADO_CANCELADA, $reserva->estado);
        $this->assertSame('No puedo viajar', $reserva->motivo_cancelacion);
    }

    public function test_6_libera_el_cupo_de_la_salida_programada(): void
    {
        $this->cancelar($this->reserva(participantes: 3))->assertOk();

        $this->assertSame(9, $this->salida->fresh()->cupo_disponible);
    }

    public function test_confirmada_tambien_se_puede_cancelar(): void
    {
        $this->cancelar($this->reserva(Reserva::ESTADO_CONFIRMADA))->assertOk();
    }

    // ---------- Flujos alternativos ----------

    public function test_4_1_el_motivo_es_obligatorio(): void
    {
        $reserva = $this->reserva();

        $this->cancelar($reserva, '   ')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['motivo' => 'El motivo de la cancelación es obligatorio.']);

        $this->assertSame(Reserva::ESTADO_PENDIENTE, $reserva->fresh()->estado);
        $this->assertSame(6, $this->salida->fresh()->cupo_disponible);
    }

    public function test_6_1_libera_las_camas_del_convenio_que_cubre_la_salida(): void
    {
        // Convenio del mismo refugio que no cubre las fechas de la salida: no se toca
        $otroConvenio = $this->convenio($this->paquete->refugio, now()->addMonths(3), now()->addMonths(5), totales: 20, disponibles: 5);

        $this->cancelar($this->reserva(participantes: 3))->assertOk();

        $this->assertSame(13, $this->convenio->fresh()->camas_disponibles);
        $this->assertSame(5, $otroConvenio->fresh()->camas_disponibles);
    }

    public function test_6_1_el_equipamiento_alquilado_queda_registrado_en_la_reserva_cancelada(): void
    {
        // El stock por período se calcula con las reservas activas: al quedar
        // "Cancelada", el equipo alquilado deja de contarse sin borrar el registro.
        $reserva = $this->reserva();
        $reserva->equipos_alquilados()->attach(Equipamiento::factory()->create()->id, ['cantidad' => 2]);

        $this->cancelar($reserva)->assertOk();

        $this->assertSame(1, $reserva->equipos_alquilados()->count());
        $this->assertSame(0, $this->paquete->cantidadReservasActivas());
    }

    // ---------- Reglas de negocio ----------

    public function test_rn02_no_supera_el_cupo_maximo_ni_las_camas_totales(): void
    {
        $this->salida->update(['cupo_disponible' => 9]);
        $this->convenio->update(['camas_disponibles' => 19]);

        $this->cancelar($this->reserva(participantes: 3))->assertOk();

        $this->assertSame(10, $this->salida->fresh()->cupo_disponible);
        $this->assertSame(20, $this->convenio->fresh()->camas_disponibles);
    }

    // ---------- Precondición ----------

    public function test_solo_se_cancelan_reservas_pendientes_o_confirmadas(): void
    {
        foreach ([Reserva::ESTADO_REALIZADA, Reserva::ESTADO_CANCELADA, Reserva::ESTADO_EN_CURSO] as $estado) {
            $reserva = $this->reserva($estado, participantes: 1);

            $this->actingAs($this->cliente->user)
                ->getJson(route('reservas.cancelar', $reserva))
                ->assertForbidden();
            $this->cancelar($reserva)->assertForbidden();
            $this->assertSame($estado, $reserva->fresh()->estado);
        }

        $this->assertSame(6, $this->salida->fresh()->cupo_disponible);
    }

    public function test_el_cliente_no_puede_cancelar_reservas_ajenas(): void
    {
        $ajena = $this->reserva(cliente: Cliente::factory()->create());

        $this->cancelar($ajena)->assertForbidden();
        $this->assertSame(Reserva::ESTADO_PENDIENTE, $ajena->fresh()->estado);
    }

    public function test_el_administrativo_puede_cancelar_cualquier_reserva(): void
    {
        $reserva = $this->reserva();

        $this->cancelar($reserva, 'Condiciones climáticas', User::factory()->create(['rol' => 'admin']))->assertOk();

        $this->assertSame(Reserva::ESTADO_CANCELADA, $reserva->fresh()->estado);
    }

    // ---------- Integración con el CU 15 ----------

    public function test_el_listado_indica_que_reservas_se_pueden_cancelar(): void
    {
        $this->reserva(Reserva::ESTADO_REALIZADA, participantes: 1);

        $this->actingAs($this->cliente->user)
            ->get(route('reservas.listar'))
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->where('reservas.0.se_puede_cancelar', false));
    }
}
