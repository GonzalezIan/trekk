<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Excursion;
use App\Models\Pago;
use App\Models\Paquete;
use App\Models\Participante;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Pagina;
use Tests\TestCase;

/**
 * Pruebas del CU 22 - Registrar pago de reserva.
 * Correr con: php artisan test --filter=RegistrarPagoTest
 */
class RegistrarPagoTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;
    private Paquete $paquete;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado

        $this->cliente = Cliente::factory()->create();
        $salida = SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create()->id,
            'fecha_inicio' => now()->addDays(10)->toDateString(),
            'fecha_fin' => now()->addDays(12)->toDateString(),
        ]);
        $this->paquete = Paquete::factory()->create(['salida_programada_id' => $salida->id]);
    }

    /** Reserva de USD 1200 con 2 participantes. */
    private function reserva(
        string $estado = Reserva::ESTADO_PENDIENTE,
        string $requisitoMedico = 'NO_REQUERIDO',
        ?Cliente $cliente = null,
    ): Reserva {
        $reserva = Reserva::factory()->create([
            'cliente_id' => ($cliente ?? $this->cliente)->id,
            'paquete_id' => $this->paquete->id,
            'estado' => $estado,
            'situacion_de_pago' => Reserva::PAGO_PENDIENTE_DE_PAGO_TOTAL,
            'importe_total' => 1200,
            'motivo_cancelacion' => null,
        ]);

        foreach ([1, 2] as $i) {
            $reserva->participantes()->create([
                'dni' => '3011122'.$i,
                'nombre' => 'Nombre '.$i,
                'apellido' => 'Apellido '.$i,
                'fecha_nacimiento' => '1990-01-01',
                'estado_requisito_medico' => $requisitoMedico,
            ]);
        }

        return $reserva;
    }

    private function conSenaPagada(Reserva $reserva): Reserva
    {
        $reserva->pagos()->create([
            'monto' => 600,
            'moneda' => 'USD',
            'medio_pago' => 'Mercado Pago',
            'tipo_pago' => Pago::TIPO_SENA,
            'fecha_hora_pago' => now(),
            'resultado_transaccion' => Pago::RESULTADO_APROBADA,
        ]);

        return $reserva;
    }

    private function pagar(Reserva $reserva, array $datos = [])
    {
        return $this->actingAs($this->cliente->user)
            ->postJson(route('reservas.pago.confirmar', $reserva), array_merge([
                'medio_pago' => 'Tarjeta de crédito',
                'moneda' => 'USD',
            ], $datos));
    }

    // ---------- Flujo normal: pago total ----------

    public function test_1_2_el_sistema_muestra_el_importe_pendiente_en_usd(): void
    {
        $reserva = $this->reserva();

        $this->actingAs($this->cliente->user)
            ->getJson(route('reservas.pago', $reserva))
            ->assertOk()
            ->assertJson([
                'importe_pendiente' => 1200,
                'importe_sena' => null, // desde el CU 15 no se ofrece la seña
                'tasa_usd_ars' => 1500,
            ]);
    }

    public function test_6_7_registra_el_pago_total_y_queda_pago_completo(): void
    {
        $reserva = $this->reserva();

        $this->pagar($reserva)->assertOk()->assertJsonPath('situacion_de_pago', Reserva::PAGO_COMPLETO);

        $this->assertDatabaseHas('pagos', [
            'reserva_id' => $reserva->id,
            'monto' => 1200,
            'moneda' => 'USD',
            'medio_pago' => 'Tarjeta de crédito',
            'tipo_pago' => Pago::TIPO_TOTAL,
        ]);
        $this->assertSame(Reserva::PAGO_COMPLETO, $reserva->fresh()->situacion_de_pago);
    }

    // ---------- Alternativos ----------

    public function test_1_1_desde_el_registro_de_la_reserva_se_puede_abonar_solo_la_sena(): void
    {
        $reserva = $this->reserva(Reserva::ESTADO_EN_CURSO);

        $this->actingAs($this->cliente->user)
            ->getJson(route('reservas.pago', $reserva))
            ->assertJsonPath('importe_sena', 600);

        $this->pagar($reserva, ['sena' => true])->assertOk();

        $this->assertDatabaseHas('pagos', [
            'reserva_id' => $reserva->id,
            'monto' => 600,
            'tipo_pago' => Pago::TIPO_SENA,
        ]);
        $this->assertSame(Reserva::PAGO_PENDIENTE_DE_PAGO_TOTAL, $reserva->fresh()->situacion_de_pago);
    }

    public function test_desde_el_listado_no_se_puede_elegir_la_sena(): void
    {
        $reserva = $this->reserva();

        $this->pagar($reserva, ['sena' => true])->assertForbidden();

        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_1_2_con_la_sena_pagada_se_abona_el_saldo_restante(): void
    {
        $reserva = $this->conSenaPagada($this->reserva());

        $this->actingAs($this->cliente->user)
            ->getJson(route('reservas.pago', $reserva))
            ->assertJsonPath('importe_pendiente', 600);

        $this->pagar($reserva)->assertOk();

        $this->assertDatabaseHas('pagos', [
            'reserva_id' => $reserva->id,
            'monto' => 600,
            'tipo_pago' => Pago::TIPO_SALDO_RESTANTE,
        ]);
        $this->assertSame(Reserva::PAGO_COMPLETO, $reserva->fresh()->situacion_de_pago);
    }

    public function test_3_1_en_pesos_se_convierte_con_la_tasa_fija(): void
    {
        $reserva = $this->reserva();

        $this->pagar($reserva, ['moneda' => 'ARS'])->assertOk();

        $this->assertDatabaseHas('pagos', [
            'reserva_id' => $reserva->id,
            'monto' => 1800000, // 1200 USD x 1500
            'moneda' => 'ARS',
        ]);
    }

    public function test_5_1_si_la_pasarela_rechaza_no_se_registra_nada(): void
    {
        $reserva = $this->reserva();

        $this->pagar($reserva, ['simular_rechazo' => true])
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($mensaje) => str_contains($mensaje, 'no pudo procesarse'));

        $this->assertDatabaseCount('pagos', 0);
        $this->assertSame(Reserva::PAGO_PENDIENTE_DE_PAGO_TOTAL, $reserva->fresh()->situacion_de_pago);
    }

    public function test_el_medio_de_pago_debe_ser_valido(): void
    {
        $reserva = $this->reserva();

        $this->pagar($reserva, ['medio_pago' => 'Bitcoin'])->assertJsonValidationErrors('medio_pago');
        $this->pagar($reserva, ['medio_pago' => ''])->assertJsonValidationErrors('medio_pago');

        $this->assertDatabaseCount('pagos', 0);
    }

    // ---------- Estado de la reserva ----------

    public function test_con_el_pago_completo_y_sin_certificados_pendientes_queda_confirmada(): void
    {
        $reserva = $this->reserva(requisitoMedico: 'NO_REQUERIDO');

        $this->pagar($reserva)->assertOk();

        $this->assertSame(Reserva::ESTADO_CONFIRMADA, $reserva->fresh()->estado);
    }

    public function test_con_certificados_pendientes_sigue_pendiente(): void
    {
        $reserva = $this->reserva(requisitoMedico: Participante::REQUISITO_PENDIENTE);

        $this->pagar($reserva)->assertOk();

        $this->assertSame(Reserva::ESTADO_PENDIENTE, $reserva->fresh()->estado);
        $this->assertSame(Reserva::PAGO_COMPLETO, $reserva->fresh()->situacion_de_pago);
    }

    // ---------- Precondición ----------

    public function test_solo_se_pagan_reservas_propias(): void
    {
        $ajena = $this->reserva(cliente: Cliente::factory()->create());

        $this->pagar($ajena)->assertForbidden();
    }

    public function test_no_se_paga_una_reserva_confirmada_ni_una_con_pago_completo(): void
    {
        $confirmada = $this->reserva(Reserva::ESTADO_CONFIRMADA);
        $pagada = $this->reserva();
        $pagada->update(['situacion_de_pago' => Reserva::PAGO_COMPLETO]);

        $this->pagar($confirmada)->assertForbidden();
        $this->pagar($pagada)->assertForbidden();

        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_el_listado_indica_que_reservas_se_pueden_pagar(): void
    {
        $this->reserva();

        $this->actingAs($this->cliente->user)
            ->get(route('reservas.listar'))
            ->assertInertia(fn (Pagina $pagina) => $pagina->where('reservas.0.se_puede_pagar', true));
    }
}