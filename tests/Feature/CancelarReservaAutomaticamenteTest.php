<?php

namespace Tests\Feature;

use App\Http\Controllers\ControladorReservas;
use App\Models\Cliente;
use App\Models\Convenio;
use App\Models\Equipamiento;
use App\Models\Excursion;
use App\Models\Pago;
use App\Models\Paquete;
use App\Models\Participante;
use App\Models\Refugio;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use App\Notifications\ReservaCancelada;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Pruebas del RF-17 - Cancelar reserva automáticamente.
 * Correr con: php artisan test --filter=CancelarReservaAutomaticamenteTest
 */
class CancelarReservaAutomaticamenteTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;
    private SalidaProgramada $salida;
    private Paquete $paquete;
    private Convenio $convenio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeSecond();

        $this->cliente = Cliente::factory()->create();
        $this->salida = SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create()->id,
            'fecha_inicio' => now()->addDays(10)->toDateString(),
            'hora_de_salida' => '08:00',
            'fecha_fin' => now()->addDays(12)->toDateString(),
            'cupo_maximo' => 10,
            'cupo_disponible' => 4,
        ]);

        $refugio = Refugio::factory()->create();
        $this->convenio = $refugio->convenios()->create([
            'nombre_convenio' => 'Convenio temporada',
            'fecha_inicio' => now()->subMonth()->toDateString(),
            'fecha_fin' => now()->addMonths(2)->toDateString(),
            'camas_totales' => 20,
            'camas_disponibles' => 10,
            'precio_cama' => 100,
        ]);
        $this->paquete = Paquete::factory()->create([
            'salida_programada_id' => $this->salida->id,
            'refugio_id' => $refugio->id,
            'estado' => 'DISPONIBLE',
        ]);
    }

    /** Reserva con 3 participantes, que ocupan 3 cupos y 3 camas. */
    private function reserva(
        string $estado = Reserva::ESTADO_PENDIENTE,
        string $situacionDePago = Reserva::PAGO_PENDIENTE_DE_PAGO_TOTAL,
        $vencimiento = null,
        string $requisitoMedico = Participante::REQUISITO_NO_REQUERIDO,
    ): Reserva {
        $reserva = Reserva::factory()->create([
            'cliente_id' => $this->cliente->id,
            'paquete_id' => $this->paquete->id,
            'cantidad_participantes' => 3,
            'estado' => $estado,
            'situacion_de_pago' => $situacionDePago,
            'importe_total' => 1200,
            'motivo_cancelacion' => null,
            'fecha_hora_vencimiento' => $vencimiento,
        ]);

        foreach (range(1, 3) as $i) {
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

    /** La salida empieza dentro de esa cantidad de horas. */
    private function salidaDentroDe(int $horas): void
    {
        $inicio = now()->addHours($horas);
        $this->salida->update([
            'fecha_inicio' => $inicio->toDateString(),
            'hora_de_salida' => $inicio->format('H:i'),
            'fecha_fin' => $inicio->copy()->addDays(2)->toDateString(),
        ]);
    }

    private function correrProceso(): void
    {
        $this->artisan('reservas:cancelar-vencidas')->assertSuccessful();
    }

    private function assertCancelada(Reserva $reserva, string $motivo): void
    {
        $reserva->refresh();
        $this->assertSame(Reserva::ESTADO_CANCELADA, $reserva->estado);
        $this->assertSame($motivo, $reserva->motivo_cancelacion);
    }

    private function assertSinCambios(Reserva $reserva): void
    {
        $estado = $reserva->estado;
        $this->assertSame($estado, $reserva->fresh()->estado);
        $this->assertSame(4, $this->salida->fresh()->cupo_disponible);
    }

    // ---------- Reservas "En curso" (CU 14, RN-04) ----------

    public function test_la_reserva_en_curso_vencida_se_descarta_y_libera_cupos_y_camas(): void
    {
        $reserva = $this->reserva(Reserva::ESTADO_EN_CURSO, vencimiento: now()->subMinute());

        $this->correrProceso();

        $this->assertModelMissing($reserva);
        $this->assertSame(7, $this->salida->fresh()->cupo_disponible);
        $this->assertSame(13, $this->convenio->fresh()->camas_disponibles);
    }

    public function test_si_el_cliente_sigue_en_la_pantalla_se_le_informa_que_la_reserva_vencio(): void
    {
        $reserva = $this->reserva(Reserva::ESTADO_EN_CURSO, vencimiento: now()->subMinute());
        $this->correrProceso();

        $this->actingAs($this->cliente->user)
            ->postJson(route('reservas.registrar.continuar', $reserva))
            ->assertStatus(410)
            ->assertJsonPath('message', ControladorReservas::MENSAJE_RESERVA_VENCIDA);
    }

    public function test_la_reserva_en_curso_vigente_no_se_toca(): void
    {
        $reserva = $this->reserva(Reserva::ESTADO_EN_CURSO, vencimiento: now()->addMinutes(5));

        $this->correrProceso();

        $this->assertModelExists($reserva);
        $this->assertSinCambios($reserva);
    }

    // ---------- Seña sin saldo a las 24 horas (CU 14, 19.1) ----------

    public function test_la_sena_vencida_sin_saldo_cancela_la_reserva_y_libera_todo(): void
    {
        $carpa = Equipamiento::factory()->create(['stock' => 5]);
        $reserva = $this->reserva(vencimiento: now()->subMinute());
        $reserva->agregarEquipamiento($carpa, 2);
        $reserva->pagos()->create([
            'monto' => 600, 'moneda' => 'USD', 'medio_pago' => 'Mercado Pago', 'tipo_pago' => Pago::TIPO_SENA,
            'fecha_hora_pago' => now()->subDay(), 'resultado_transaccion' => Pago::RESULTADO_APROBADA,
        ]);

        $this->correrProceso();

        $this->assertCancelada($reserva, Reserva::MOTIVO_SALDO_NO_ABONADO);
        $this->assertSame(7, $this->salida->fresh()->cupo_disponible);
        $this->assertSame(13, $this->convenio->fresh()->camas_disponibles);
        $this->assertSame(5, $carpa->stockDisponible(...$this->salida->getFechas()));
        // Sin reembolso: el pago de la seña queda registrado
        $this->assertSame(1, $reserva->pagos()->count());
    }

    public function test_la_sena_dentro_de_las_24_horas_no_se_cancela(): void
    {
        $reserva = $this->reserva(vencimiento: now()->addHour());

        $this->correrProceso();

        $this->assertSinCambios($reserva);
    }

    public function test_con_el_pago_completo_no_se_cancela_aunque_haya_pasado_el_vencimiento(): void
    {
        $reserva = $this->reserva(situacionDePago: Reserva::PAGO_COMPLETO, vencimiento: now()->subDay());

        $this->correrProceso();

        $this->assertSinCambios($reserva);
    }

    // ---------- Requisito médico pendiente al límite de verificación (RN-02 del CU 19) ----------

    public function test_requisito_medico_pendiente_a_12_horas_de_la_salida_cancela_la_reserva(): void
    {
        $this->salidaDentroDe(12);
        $reserva = $this->reserva(situacionDePago: Reserva::PAGO_COMPLETO, requisitoMedico: Participante::REQUISITO_PENDIENTE);

        $this->correrProceso();

        $this->assertCancelada($reserva, Reserva::MOTIVO_REQUISITO_MEDICO_PENDIENTE);
        $this->assertSame(7, $this->salida->fresh()->cupo_disponible);
    }

    public function test_requisito_medico_pendiente_a_mas_de_12_horas_todavia_no_se_cancela(): void
    {
        $this->salidaDentroDe(13);
        $reserva = $this->reserva(situacionDePago: Reserva::PAGO_COMPLETO, requisitoMedico: Participante::REQUISITO_PENDIENTE);

        $this->correrProceso();

        $this->assertSinCambios($reserva);
    }

    public function test_con_los_requisitos_cumplidos_no_se_cancela(): void
    {
        $this->salidaDentroDe(6);
        $reserva = $this->reserva(situacionDePago: Reserva::PAGO_COMPLETO, requisitoMedico: Participante::REQUISITO_CUMPLIDO);

        $this->correrProceso();

        $this->assertSinCambios($reserva);
    }

    public function test_un_solo_participante_pendiente_alcanza_para_cancelar(): void
    {
        $this->salidaDentroDe(6);
        $reserva = $this->reserva(situacionDePago: Reserva::PAGO_COMPLETO, requisitoMedico: Participante::REQUISITO_CUMPLIDO);
        $reserva->participantes()->first()->setRequisitoMedico(Participante::REQUISITO_PENDIENTE);

        $this->correrProceso();

        $this->assertCancelada($reserva, Reserva::MOTIVO_REQUISITO_MEDICO_PENDIENTE);
    }

    // ---------- Reservas que no se tocan ----------

    public function test_las_reservas_confirmadas_canceladas_y_realizadas_no_se_tocan(): void
    {
        $this->salidaDentroDe(6);
        $reservas = collect([Reserva::ESTADO_CONFIRMADA, Reserva::ESTADO_CANCELADA, Reserva::ESTADO_REALIZADA])
            ->map(fn (string $estado) => $this->reserva($estado, vencimiento: now()->subDay(), requisitoMedico: Participante::REQUISITO_PENDIENTE));

        $this->correrProceso();

        $reservas->each(fn (Reserva $reserva) => $this->assertSinCambios($reserva));
    }

    public function test_correrlo_dos_veces_no_libera_el_cupo_dos_veces(): void
    {
        $this->reserva(vencimiento: now()->subMinute());

        $this->correrProceso();
        $this->correrProceso();

        $this->assertSame(7, $this->salida->fresh()->cupo_disponible);
    }

    // ---------- RF Notificar eventualidades ----------

    public function test_se_notifica_al_cliente_la_cancelacion_con_el_motivo(): void
    {
        Notification::fake();
        $reserva = $this->reserva(vencimiento: now()->subMinute());

        $this->correrProceso();

        Notification::assertSentTo($this->cliente->user, ReservaCancelada::class,
            fn (ReservaCancelada $aviso) => $aviso->reserva->is($reserva)
                && $aviso->origen === ReservaCancelada::ORIGEN_RESERVA
                && $aviso->reserva->motivo_cancelacion === Reserva::MOTIVO_SALDO_NO_ABONADO);
    }

    public function test_la_reserva_en_curso_descartada_no_se_notifica(): void
    {
        Notification::fake();
        $this->reserva(Reserva::ESTADO_EN_CURSO, vencimiento: now()->subMinute());

        $this->correrProceso();

        Notification::assertNothingSent();
    }

    public function test_el_proceso_esta_programado_cada_minuto(): void
    {
        $evento = collect(app(Schedule::class)->events())
            ->first(fn ($evento) => str_contains($evento->command, 'reservas:cancelar-vencidas'));

        $this->assertNotNull($evento);
        $this->assertSame('* * * * *', $evento->expression);
    }
}
