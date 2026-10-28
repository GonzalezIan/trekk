<?php

namespace Tests\Feature;

use App\Http\Controllers\ControladorSalidas;
use App\Models\Cliente;
use App\Models\Convenio;
use App\Models\Equipamiento;
use App\Models\Excursion;
use App\Models\Paquete;
use App\Models\Refugio;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Pruebas del RF Finalizar Salida Programada.
 * Correr con: php artisan test --filter=FinalizarSalidaProgramadaTest
 */
class FinalizarSalidaProgramadaTest extends TestCase
{
    use RefreshDatabase;

    private SalidaProgramada $salida;
    private Paquete $paquete;
    private Convenio $convenio;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo('2026-10-20 10:00:00');

        // Salida que terminó ayer, con un paquete con alojamiento: el convenio tiene 10 de 20 camas ocupadas
        $this->salida = $this->salida('2026-10-16', '2026-10-19');
        $refugio = Refugio::factory()->create();
        $this->convenio = $refugio->convenios()->create([
            'nombre_convenio' => 'Temporada 2026',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-12-31',
            'camas_totales' => 20,
            'camas_disponibles' => 10,
            'precio_cama' => 100,
        ]);
        $this->paquete = Paquete::factory()->create([
            'salida_programada_id' => $this->salida->id,
            'refugio_id' => $refugio->id,
            'estado' => Paquete::ESTADO_DISPONIBLE,
        ]);
    }

    private function salida(string $inicio, string $fin, string $estado = SalidaProgramada::ESTADO_PROGRAMADA): SalidaProgramada
    {
        return SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create()->id,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'hora_de_salida' => '08:00',
            'estado' => $estado,
        ]);
    }

    private function reserva(string $estado, int $participantes = 1): Reserva
    {
        $reserva = Reserva::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'paquete_id' => $this->paquete->id,
            'estado' => $estado,
            'motivo_cancelacion' => null,
        ]);

        for ($i = 1; $i <= $participantes; $i++) {
            $reserva->participantes()->create([
                'dni' => $reserva->id . '00' . $i,
                'nombre' => 'Nombre ' . $i,
                'apellido' => 'Apellido ' . $i,
                'fecha_nacimiento' => '1990-01-01',
                'estado_requisito_medico' => 'NO_REQUERIDO',
            ]);
        }

        return $reserva;
    }

    private function finalizar(): int
    {
        return app()->call([app(ControladorSalidas::class), 'finalizarSalidaProgramada']);
    }

    // ---------- Qué salidas se finalizan ----------

    public function test_finaliza_las_salidas_programadas_cuya_fecha_de_fin_ya_transcurrio(): void
    {
        $terminaHoy = $this->salida('2026-10-18', '2026-10-20');
        $futura = $this->salida('2026-11-01', '2026-11-03');
        $cancelada = $this->salida('2026-10-01', '2026-10-03', SalidaProgramada::ESTADO_CANCELADA);

        $this->assertSame(1, $this->finalizar());

        $this->assertSame(SalidaProgramada::ESTADO_FINALIZADA, $this->salida->fresh()->estado);
        $this->assertSame(SalidaProgramada::ESTADO_PROGRAMADA, $terminaHoy->fresh()->estado);
        $this->assertSame(SalidaProgramada::ESTADO_PROGRAMADA, $futura->fresh()->estado);
        $this->assertSame(SalidaProgramada::ESTADO_CANCELADA, $cancelada->fresh()->estado);
    }

    public function test_los_paquetes_no_cambian_de_estado(): void
    {
        $this->finalizar();

        $this->assertSame(Paquete::ESTADO_DISPONIBLE, $this->paquete->fresh()->estado);
    }

    // ---------- Reservas ----------

    public function test_las_reservas_confirmadas_pasan_a_realizadas(): void
    {
        $confirmada = $this->reserva(Reserva::ESTADO_CONFIRMADA);

        $this->finalizar();

        $this->assertSame(Reserva::ESTADO_REALIZADA, $confirmada->fresh()->estado);
        Notification::assertNothingSent();
    }

    public function test_solo_cambian_las_reservas_confirmadas(): void
    {
        $pendiente = $this->reserva(Reserva::ESTADO_PENDIENTE);
        $enCurso = $this->reserva(Reserva::ESTADO_EN_CURSO);
        $cancelada = $this->reserva(Reserva::ESTADO_CANCELADA);

        $this->finalizar();

        $this->assertSame(Reserva::ESTADO_PENDIENTE, $pendiente->fresh()->estado);
        $this->assertSame(Reserva::ESTADO_EN_CURSO, $enCurso->fresh()->estado);
        $this->assertSame(Reserva::ESTADO_CANCELADA, $cancelada->fresh()->estado);
        Notification::assertNothingSent();
    }

    // ---------- Liberación de recursos ----------

    public function test_libera_las_camas_que_ocupaban_las_reservas_activas(): void
    {
        $this->reserva(Reserva::ESTADO_CONFIRMADA, participantes: 2);
        $this->reserva(Reserva::ESTADO_CONFIRMADA, participantes: 1);
        $this->reserva(Reserva::ESTADO_PENDIENTE, participantes: 1);
        $this->reserva(Reserva::ESTADO_CANCELADA, participantes: 3); // ya devolvió sus camas (CU 16)

        $this->finalizar();

        $this->assertSame(14, $this->convenio->fresh()->camas_disponibles);
    }

    public function test_las_camas_liberadas_no_superan_las_camas_totales(): void
    {
        $this->convenio->update(['camas_disponibles' => 19]);
        $this->reserva(Reserva::ESTADO_CONFIRMADA, participantes: 3);

        $this->finalizar();

        $this->assertSame(20, $this->convenio->fresh()->camas_disponibles);
    }

    public function test_libera_el_equipamiento_incluido_y_el_de_alquiler(): void
    {
        $carpa = Equipamiento::factory()->create(['stock' => 10]);
        $this->salida->equipoPrestado()->attach($carpa->id, ['cantidad' => 6]);
        $this->reserva(Reserva::ESTADO_CONFIRMADA)->equipos_alquilados()->attach($carpa->id, ['cantidad' => 2]);

        $this->assertSame(2, $carpa->stockDisponible('2026-10-16', '2026-10-19'));

        $this->finalizar();

        $this->assertSame(10, $carpa->stockDisponible('2026-10-16', '2026-10-19'));
    }

    public function test_correrlo_dos_veces_no_vuelve_a_liberar_las_camas(): void
    {
        $this->reserva(Reserva::ESTADO_CONFIRMADA, participantes: 2);
        $this->reserva(Reserva::ESTADO_PENDIENTE);

        $this->finalizar();
        $this->assertSame(0, $this->finalizar());

        $this->assertSame(13, $this->convenio->fresh()->camas_disponibles);
    }

    // ---------- Disparo automático ----------

    public function test_el_comando_finaliza_las_salidas(): void
    {
        $this->artisan('salidas:finalizar')
            ->expectsOutput('Salidas Programadas finalizadas: 1.')
            ->assertSuccessful();

        $this->assertSame(SalidaProgramada::ESTADO_FINALIZADA, $this->salida->fresh()->estado);
    }

    public function test_el_comando_esta_programado_todos_los_dias(): void
    {
        $evento = collect(app(Schedule::class)->events())
            ->first(fn ($evento) => str_contains($evento->command, 'salidas:finalizar'));

        $this->assertNotNull($evento);
        $this->assertSame('5 0 * * *', $evento->expression);
    }
}
