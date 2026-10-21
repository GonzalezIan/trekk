<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Excursion;
use App\Models\Paquete;
use App\Models\Participante;
use App\Models\Refugio;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas del CU 21 - Listar participantes.
 * Correr con: php artisan test --filter=ListarParticipantesTest
 */
class ListarParticipantesTest extends TestCase
{
    use RefreshDatabase;

    private SalidaProgramada $salida;
    private Paquete $paquete;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado

        $this->salida = SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create()->id,
            'fecha_inicio' => now()->addDays(10)->toDateString(),
            'fecha_fin' => now()->addDays(12)->toDateString(),
            'hora_de_salida' => '08:00:00',
        ]);
        $this->paquete = $this->paqueteDe($this->salida);
    }

    private function paqueteDe(SalidaProgramada $salida): Paquete
    {
        return Paquete::factory()->create([
            'salida_programada_id' => $salida->id,
            'refugio_id' => Refugio::factory()->create()->id,
        ]);
    }

    private function reserva(Cliente $cliente, string $estado, ?Paquete $paquete = null): Reserva
    {
        return Reserva::factory()->create([
            'cliente_id' => $cliente->id,
            'paquete_id' => ($paquete ?? $this->paquete)->id,
            'estado' => $estado,
        ]);
    }

    private function participante(Reserva $reserva, string $dni, string $requisito = Participante::REQUISITO_NO_REQUERIDO): Participante
    {
        return $reserva->participantes()->create([
            'dni' => $dni,
            'nombre' => 'Nombre '.$dni,
            'apellido' => 'Apellido '.$dni,
            'fecha_nacimiento' => '1990-01-01',
            'estado_requisito_medico' => $requisito,
        ]);
    }

    private function administrativo(): User
    {
        return User::factory()->create(['rol' => 'admin']);
    }

    // ---------- Cliente (3.1) ----------

    public function test_3_1_el_cliente_ve_los_participantes_de_su_reserva(): void
    {
        $cliente = Cliente::factory()->create();
        $reserva = $this->reserva($cliente, Reserva::ESTADO_PENDIENTE);
        $this->participante($reserva, '30111222', Participante::REQUISITO_PENDIENTE);
        $this->participante($reserva, '30111333', Participante::REQUISITO_CUMPLIDO);

        $this->actingAs($cliente->user)
            ->getJson(route('reservas.participantes', $reserva))
            ->assertOk()
            ->assertJsonCount(2, 'participantes')
            ->assertJsonPath('participantes.0.dni', '30111222')
            ->assertJsonPath('participantes.0.estado_requisito_medico', 'PENDIENTE')
            ->assertJsonStructure(['participantes' => [['dni', 'nombre', 'apellido', 'estado_requisito_medico']]]);
    }

    public function test_el_cliente_no_puede_ver_participantes_de_reservas_ajenas(): void
    {
        $reservaAjena = $this->reserva(Cliente::factory()->create(), Reserva::ESTADO_PENDIENTE);

        $this->actingAs(Cliente::factory()->create()->user)
            ->getJson(route('reservas.participantes', $reservaAjena))
            ->assertForbidden();
    }

    public function test_4_1_se_puede_cargar_certificado_si_faltan_mas_de_24_horas(): void
    {
        $cliente = Cliente::factory()->create();
        $reserva = $this->reserva($cliente, Reserva::ESTADO_PENDIENTE);

        $this->actingAs($cliente->user)
            ->getJson(route('reservas.participantes', $reserva))
            ->assertJsonPath('puedeCargarCertificado', true);
    }

    public function test_rn01_no_se_puede_cargar_certificado_si_faltan_24_horas_o_menos(): void
    {
        $salidaProxima = SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create()->id,
            'fecha_inicio' => now()->addHours(20)->toDateString(),
            'fecha_fin' => now()->addDays(3)->toDateString(),
            'hora_de_salida' => now()->addHours(20)->format('H:i:s'),
        ]);
        $cliente = Cliente::factory()->create();
        $reserva = $this->reserva($cliente, Reserva::ESTADO_PENDIENTE, $this->paqueteDe($salidaProxima));

        $this->actingAs($cliente->user)
            ->getJson(route('reservas.participantes', $reserva))
            ->assertJsonPath('puedeCargarCertificado', false);
    }

    // ---------- Administrativo (3.2) ----------

    public function test_3_2_el_administrativo_ve_solo_participantes_de_reservas_confirmadas_de_la_salida(): void
    {
        $confirmada = $this->reserva(Cliente::factory()->create(), Reserva::ESTADO_CONFIRMADA);
        $pendiente = $this->reserva(Cliente::factory()->create(), Reserva::ESTADO_PENDIENTE);
        $this->participante($confirmada, '30111222');
        $this->participante($confirmada, '30111333');
        $this->participante($pendiente, '30111444');

        // Reserva confirmada de otra salida: no debe aparecer
        $otraSalida = SalidaProgramada::factory()->create(['excursion_id' => Excursion::factory()->create()->id]);
        $otra = $this->reserva(Cliente::factory()->create(), Reserva::ESTADO_CONFIRMADA, $this->paqueteDe($otraSalida));
        $this->participante($otra, '30111555');

        $this->actingAs($this->administrativo())
            ->getJson(route('salidas.participantes', $this->salida))
            ->assertOk()
            ->assertJsonCount(2, 'participantes')
            ->assertJsonPath('participantes.0.reserva_id', $confirmada->id);
    }

    public function test_3_3_salida_sin_participantes_devuelve_el_listado_vacio(): void
    {
        $this->actingAs($this->administrativo())
            ->getJson(route('salidas.participantes', $this->salida))
            ->assertOk()
            ->assertJsonCount(0, 'participantes');
    }

    public function test_el_cliente_no_puede_ver_la_nomina_de_una_salida(): void
    {
        $this->actingAs(Cliente::factory()->create()->user)
            ->getJson(route('salidas.participantes', $this->salida))
            ->assertForbidden();
    }
}
