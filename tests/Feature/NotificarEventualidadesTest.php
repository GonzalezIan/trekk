<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Equipamiento;
use App\Models\Excursion;
use App\Models\Pago;
use App\Models\Paquete;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use App\Models\User;
use App\Notifications\NotificacionAlCliente;
use App\Notifications\RecordatorioSalida;
use App\Notifications\ReservaCancelada;
use App\Notifications\ReservaConfirmada;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Pruebas del RF Notificar eventualidades: recordatorio de salida y contenido de los correos.
 * Los avisos que disparan los CU 10, 16, 19, 22 y el RF-17 se prueban en los tests de cada uno;
 * los del CU 6, acá.
 * Correr con: php artisan test --filter=NotificarEventualidadesTest
 */
class NotificarEventualidadesTest extends TestCase
{
    use RefreshDatabase;

    private Excursion $excursion;
    private SalidaProgramada $salida;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo('2026-10-20 10:00:00');

        $this->excursion = Excursion::factory()->create([
            'nombre' => 'Laguna de los Tres',
            'punto_de_encuentro' => 'Terminal de El Chaltén',
            'equipamiento_obligatorio' => 'Botas de trekking y campera impermeable',
        ]);
        // Empieza dentro de N = 2 días (SalidaProgramada::DIAS_ANTICIPACION_RECORDATORIO)
        $this->salida = $this->salida('2026-10-22', '16:00');
    }

    private function salida(string $inicio, string $hora): SalidaProgramada
    {
        return SalidaProgramada::factory()->create([
            'excursion_id' => $this->excursion->id,
            'fecha_inicio' => $inicio,
            'fecha_fin' => '2026-10-23',
            'hora_de_salida' => $hora,
            'estado' => SalidaProgramada::ESTADO_PROGRAMADA,
        ]);
    }

    private function reserva(string $estado, ?SalidaProgramada $salida = null): Reserva
    {
        return Reserva::factory()->create([
            'cliente_id' => Cliente::factory()->create(['nombre' => 'Ana'])->id,
            'paquete_id' => Paquete::factory()->create([
                'salida_programada_id' => ($salida ?? $this->salida)->id,
                'nombre' => 'Paquete Full',
                'estado' => Paquete::ESTADO_DISPONIBLE,
            ])->id,
            'estado' => $estado,
            'motivo_cancelacion' => null,
        ]);
    }

    private function pago(Reserva $reserva, float $monto, string $moneda): void
    {
        $reserva->pagos()->create([
            'monto' => $monto,
            'moneda' => $moneda,
            'medio_pago' => 'Mercado Pago',
            'tipo_pago' => Pago::TIPO_TOTAL,
            'fecha_hora_pago' => now(),
            'resultado_transaccion' => Pago::RESULTADO_APROBADA,
        ]);
    }

    /** Texto del correo, para revisar su contenido. */
    private function texto(NotificacionAlCliente $aviso): string
    {
        $mail = $aviso->toMail($aviso->reserva->cliente->user);

        return $mail->subject . "\n" . implode("\n", $mail->introLines);
    }

    // ---------- Recordatorio de salida ----------

    public function test_el_recordatorio_se_envia_a_las_reservas_activas_de_las_salidas_que_empiezan_en_n_dias(): void
    {
        $pendiente = $this->reserva(Reserva::ESTADO_PENDIENTE);
        $confirmada = $this->reserva(Reserva::ESTADO_CONFIRMADA);
        $cancelada = $this->reserva(Reserva::ESTADO_CANCELADA);

        $this->artisan('salidas:recordatorios')
            ->expectsOutput('Recordatorios de salida enviados: 2.')
            ->assertSuccessful();

        Notification::assertSentTo($pendiente->cliente->user, RecordatorioSalida::class);
        Notification::assertSentTo($confirmada->cliente->user, RecordatorioSalida::class);
        Notification::assertNotSentTo($cancelada->cliente->user, RecordatorioSalida::class);
    }

    public function test_el_recordatorio_se_envia_un_solo_dia(): void
    {
        $this->reserva(Reserva::ESTADO_CONFIRMADA);

        $this->artisan('salidas:recordatorios');
        $this->travel(1)->days();
        $this->artisan('salidas:recordatorios')->expectsOutput('Recordatorios de salida enviados: 0.');

        Notification::assertSentTimes(RecordatorioSalida::class, 1);
    }

    public function test_no_se_envia_a_salidas_que_empiezan_otro_dia(): void
    {
        $this->reserva(Reserva::ESTADO_CONFIRMADA, $this->salida('2026-10-21', '16:00'));
        $this->reserva(Reserva::ESTADO_CONFIRMADA, $this->salida('2026-10-23', '16:00'));

        $this->artisan('salidas:recordatorios');

        Notification::assertNothingSent();
    }

    public function test_el_recordatorio_informa_fecha_punto_de_encuentro_y_equipamiento_obligatorio(): void
    {
        $texto = $this->texto(new RecordatorioSalida($this->reserva(Reserva::ESTADO_CONFIRMADA)));

        $this->assertStringContainsString('“Laguna de los Tres” del 22/10/2026 al 23/10/2026, con salida a las 16:00 hs', $texto);
        $this->assertStringContainsString('Punto de encuentro: Terminal de El Chaltén.', $texto);
        $this->assertStringContainsString('Botas de trekking y campera impermeable', $texto);
    }

    public function test_el_recordatorio_informa_el_equipamiento_incluido_y_el_alquilado_que_ya_cubren_lo_obligatorio(): void
    {
        $bastones = Equipamiento::factory()->create(['nombre' => 'Bastones']);
        $campera = Equipamiento::factory()->create(['nombre' => 'Campera impermeable']);
        $this->salida->equipoPrestado()->attach($bastones->id, ['cantidad' => 10]);
        $reserva = $this->reserva(Reserva::ESTADO_CONFIRMADA);
        $reserva->agregarEquipamiento($campera, 2);

        $texto = $this->texto(new RecordatorioSalida($reserva));

        $this->assertStringContainsString('La empresa provee sin costo: Bastones.', $texto);
        $this->assertStringContainsString('Alquilaste en tu reserva: Campera impermeable x2.', $texto);
        $this->assertStringContainsString('que no esté cubierto por lo anterior tenés que llevarlo vos', $texto);
    }

    public function test_sin_equipamiento_incluido_ni_alquilado_todo_lo_obligatorio_queda_pendiente(): void
    {
        $texto = $this->texto(new RecordatorioSalida($this->reserva(Reserva::ESTADO_CONFIRMADA)));

        $this->assertStringContainsString('Tenés que llevarlo vos', $texto);
        $this->assertStringNotContainsString('provee sin costo', $texto);
        $this->assertStringNotContainsString('Alquilaste', $texto);
    }

    public function test_sin_punto_de_encuentro_ni_equipamiento_se_aclara_que_la_empresa_lo_confirmara(): void
    {
        $this->excursion->update(['punto_de_encuentro' => null, 'equipamiento_obligatorio' => null]);

        $texto = $this->texto(new RecordatorioSalida($this->reserva(Reserva::ESTADO_CONFIRMADA)));

        $this->assertStringContainsString('la empresa te lo va a confirmar', $texto);
        $this->assertStringNotContainsString('Equipamiento obligatorio', $texto);
    }

    // ---------- Cancelación del paquete (CU 6, que no tiene tests propios) ----------

    public function test_al_cancelar_el_paquete_se_notifica_a_las_reservas_activas_con_reembolso_si_pagaron(): void
    {
        $pendiente = $this->reserva(Reserva::ESTADO_PENDIENTE);
        $confirmada = $this->reserva(Reserva::ESTADO_CONFIRMADA);
        $confirmada->update(['paquete_id' => $pendiente->paquete_id]);
        $this->pago($confirmada, 500, 'USD');
        $cancelada = $this->reserva(Reserva::ESTADO_CANCELADA);
        $cancelada->update(['paquete_id' => $pendiente->paquete_id]);

        $this->actingAs(User::factory()->create(['rol' => 'admin']))
            ->post(route('paquetes.cancelar.confirmar', $pendiente->paquete_id), ['motivo_cancelacion' => 'Refugio cerrado'])
            ->assertRedirect(route('paquetes.listar', $this->salida->id));

        Notification::assertSentTo($pendiente->cliente->user, ReservaCancelada::class,
            fn (ReservaCancelada $aviso) => $aviso->origen === ReservaCancelada::ORIGEN_PAQUETE);
        Notification::assertSentTo($confirmada->cliente->user, ReservaCancelada::class,
            fn (ReservaCancelada $aviso) => str_contains($this->texto($aviso), 'reembolso del 100%'));
        Notification::assertNotSentTo($cancelada->cliente->user, ReservaCancelada::class);
    }

    public function test_al_cancelar_el_paquete_la_reserva_en_curso_no_es_activa_y_no_se_toca(): void
    {
        $enCurso = $this->reserva(Reserva::ESTADO_EN_CURSO);

        $this->actingAs(User::factory()->create(['rol' => 'admin']))
            ->post(route('paquetes.cancelar.confirmar', $enCurso->paquete_id), ['motivo_cancelacion' => 'Refugio cerrado']);

        $this->assertSame(Reserva::ESTADO_EN_CURSO, $enCurso->fresh()->estado);
        Notification::assertNothingSent();
    }

    public function test_el_recordatorio_esta_programado_una_vez_por_dia(): void
    {
        $evento = collect(app(Schedule::class)->events())
            ->first(fn ($evento) => str_contains($evento->command, 'salidas:recordatorios'));

        $this->assertNotNull($evento);
        $this->assertSame('0 9 * * *', $evento->expression);
    }

    // ---------- Contenido de los demás correos ----------

    public function test_la_confirmacion_informa_paquete_fecha_y_monto_abonado_por_moneda(): void
    {
        $reserva = $this->reserva(Reserva::ESTADO_CONFIRMADA);
        $this->pago($reserva, 500, 'USD');
        $this->pago($reserva, 750000, 'ARS');

        $texto = $this->texto(new ReservaConfirmada($reserva));

        $this->assertStringContainsString('Paquete: Paquete Full.', $texto);
        $this->assertStringContainsString('del 22/10/2026 al 23/10/2026', $texto);
        $this->assertStringContainsString('USD 500,00', $texto);
        $this->assertStringContainsString('ARS 750.000,00', $texto);
    }

    public function test_la_cancelacion_por_la_salida_indica_gestionar_el_reembolso_si_hubo_pagos(): void
    {
        $reserva = $this->reserva(Reserva::ESTADO_CANCELADA);
        $reserva->update(['motivo_cancelacion' => 'Mal clima']);
        $this->pago($reserva, 500, 'USD');

        $texto = $this->texto(new ReservaCancelada($reserva, ReservaCancelada::ORIGEN_SALIDA));

        $this->assertStringContainsString('La empresa canceló la salida', $texto);
        $this->assertStringContainsString('Motivo: Mal clima', $texto);
        $this->assertStringContainsString('reembolso del 100%', $texto);
    }

    public function test_la_cancelacion_del_paquete_nombra_el_paquete(): void
    {
        $reserva = $this->reserva(Reserva::ESTADO_CANCELADA);
        $reserva->update(['motivo_cancelacion' => 'Refugio cerrado']);

        $texto = $this->texto(new ReservaCancelada($reserva, ReservaCancelada::ORIGEN_PAQUETE));

        $this->assertStringContainsString('canceló el paquete “Paquete Full”', $texto);
        $this->assertStringNotContainsString('reembolso', $texto); // no hubo pagos
    }

    public function test_la_cancelacion_de_la_reserva_aclara_que_no_hay_reintegro(): void
    {
        $reserva = $this->reserva(Reserva::ESTADO_CANCELADA);
        $reserva->update(['motivo_cancelacion' => 'Cambio de planes']);
        $this->pago($reserva, 500, 'USD');

        $texto = $this->texto(new ReservaCancelada($reserva, ReservaCancelada::ORIGEN_RESERVA));

        $this->assertStringContainsString('No corresponde el reintegro', $texto);
        $this->assertStringNotContainsString('reembolso', $texto);
    }
}
