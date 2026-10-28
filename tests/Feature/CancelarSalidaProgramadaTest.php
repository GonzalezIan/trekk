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
use App\Notifications\ReservaCancelada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Pruebas del CU 11 - Cancelar Salida Programada.
 * Correr con: php artisan test --filter=CancelarSalidaProgramadaTest
 */
class CancelarSalidaProgramadaTest extends TestCase
{
    use RefreshDatabase;

    private const MOTIVO = 'Alerta meteorológica en la zona';

    private User $administrativo;
    private SalidaProgramada $salida;
    private Convenio $convenio;
    private Paquete $paquete;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado
        $this->travelTo('2026-10-20 10:00:00');
        Notification::fake();
        $this->administrativo = User::factory()->create(['rol' => 'admin']);

        // Salida de cupo 10 con un paquete con alojamiento en un refugio de 20 camas
        $this->salida = SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create(['estado' => 'ACTIVA', 'duracion' => 3])->id,
            'fecha_inicio' => '2026-11-10',
            'fecha_fin' => '2026-11-12',
            'cupo_maximo' => 10,
            'cupo_disponible' => 10,
            'estado' => 'PROGRAMADA',
        ]);
        $refugio = Refugio::factory()->create();
        $this->convenio = Convenio::create([
            'refugio_id' => $refugio->id,
            'nombre_convenio' => 'Temporada',
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-30',
            'camas_totales' => 20,
            'camas_disponibles' => 20,
            'precio_cama' => 5000,
        ]);
        $this->paquete = Paquete::factory()->create([
            'salida_programada_id' => $this->salida->id,
            'refugio_id' => $refugio->id,
            'estado' => 'DISPONIBLE',
        ]);
    }

    /**
     * Reserva de un cliente nuevo que retiene esa cantidad de cupos y camas.
     * Las "En curso" todavía no tienen los participantes cargados.
     */
    private function reserva(string $estado, int $participantes = 1, ?Paquete $paquete = null): Reserva
    {
        $paquete ??= $this->paquete;
        $reserva = Reserva::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'paquete_id' => $paquete->id,
            'estado' => $estado,
            'cantidad_participantes' => $participantes,
        ]);

        if ($estado !== 'EN_CURSO') {
            for ($i = 1; $i <= $participantes; $i++) {
                $reserva->participantes()->create([
                    'dni' => $reserva->id . '00' . $i,
                    'nombre' => 'Nombre ' . $i,
                    'apellido' => 'Apellido ' . $i,
                    'fecha_nacimiento' => '1990-01-01',
                    'estado_requisito_medico' => 'NO_REQUERIDO',
                ]);
            }
        }

        if (in_array($estado, ['PENDIENTE', 'CONFIRMADA', 'EN_CURSO'])) {
            $this->salida->decrement('cupo_disponible', $participantes);
            if ($paquete->refugio_id) {
                $this->convenio->decrement('camas_disponibles', $participantes);
            }
        }

        return $reserva;
    }

    private function cancelar(array $datos = ['motivo' => self::MOTIVO])
    {
        return $this->actingAs($this->administrativo)
            ->post(route('salidas.cancelar', $this->salida), $datos);
    }

    // ---------- Flujo normal (RN-02) ----------

    public function test_cancela_la_salida_sus_paquetes_y_reservas(): void
    {
        $pendiente = $this->reserva('PENDIENTE', 2);
        $confirmada = $this->reserva('CONFIRMADA');
        $yaCancelada = Reserva::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'paquete_id' => $this->paquete->id,
            'estado' => 'CANCELADA',
            'motivo_cancelacion' => 'La canceló el cliente',
        ]);

        $this->cancelar()
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('salidas.listar'))
            ->assertSessionHas('exito', 'La Salida Programada fue cancelada correctamente. Se notificó a 2 clientes.');

        $salida = $this->salida->fresh();
        $this->assertSame('CANCELADA', $salida->estado);
        $this->assertSame(self::MOTIVO, $salida->motivo_cancelacion);
        $this->assertSame('CANCELADO', $this->paquete->fresh()->estado);

        foreach ([$pendiente, $confirmada] as $reserva) {
            $this->assertSame('CANCELADA', $reserva->fresh()->estado);
            $this->assertSame(self::MOTIVO, $reserva->fresh()->motivo_cancelacion);
        }
        // La que ya estaba cancelada conserva su motivo
        $this->assertSame('La canceló el cliente', $yaCancelada->fresh()->motivo_cancelacion);
    }

    public function test_las_reservas_en_curso_tambien_se_cancelan(): void
    {
        // Un cliente estaba registrando una reserva de 3 (CU 14) cuando se canceló la salida
        $enCurso = $this->reserva('EN_CURSO', 3);

        $this->cancelar()->assertSessionHasNoErrors();

        $this->assertSame('CANCELADA', $enCurso->fresh()->estado);
        $this->assertSame(10, $this->salida->fresh()->cupo_disponible);
        $this->assertSame(20, $this->convenio->fresh()->camas_disponibles);
    }

    // ---------- RN-03: se liberan cupo, camas y equipamiento ----------

    public function test_libera_el_cupo_y_las_camas_del_convenio(): void
    {
        // Reservas en dos paquetes distintos: los cupos que devuelven no se pisan
        $sinAlojamiento = Paquete::factory()->create([
            'salida_programada_id' => $this->salida->id,
            'refugio_id' => null,
            'estado' => 'DISPONIBLE',
        ]);
        $this->reserva('PENDIENTE', 2);
        $this->reserva('CONFIRMADA', 3, $sinAlojamiento);
        $this->assertSame(5, $this->salida->fresh()->cupo_disponible);
        $this->assertSame(18, $this->convenio->fresh()->camas_disponibles);

        $this->cancelar()->assertSessionHasNoErrors();

        $this->assertSame(10, $this->salida->fresh()->cupo_disponible);
        $this->assertSame(20, $this->convenio->fresh()->camas_disponibles);
    }

    public function test_libera_el_equipamiento_incluido(): void
    {
        $carpa = Equipamiento::factory()->create(['stock' => 30]);
        $this->salida->equipoPrestado()->attach($carpa->id, ['cantidad' => 10]);
        $this->assertSame(20, $carpa->stockDisponible('2026-11-11', '2026-11-13'));

        $this->cancelar()->assertSessionHasNoErrors();

        // Otra salida superpuesta vuelve a contar con todo el stock
        $this->assertSame(30, $carpa->stockDisponible('2026-11-11', '2026-11-13'));
    }

    // ---------- RN-05: notificación a los clientes afectados ----------

    public function test_notifica_a_los_clientes_afectados(): void
    {
        $pendiente = $this->reserva('PENDIENTE');
        $enCurso = $this->reserva('EN_CURSO');
        $yaCancelada = Reserva::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'paquete_id' => $this->paquete->id,
            'estado' => 'CANCELADA',
        ]);

        $this->cancelar()->assertSessionHasNoErrors();

        foreach ([$pendiente, $enCurso] as $reserva) {
            Notification::assertSentTo($reserva->cliente->user, ReservaCancelada::class,
                fn (ReservaCancelada $notificacion) => $notificacion->origen === ReservaCancelada::ORIGEN_SALIDA
                    && $notificacion->reserva->is($reserva));
        }
        Notification::assertNotSentTo($yaCancelada->cliente->user, ReservaCancelada::class);
    }

    public function test_sin_reservas_no_se_notifica_a_nadie(): void
    {
        $this->cancelar()
            ->assertSessionHas('exito', 'La Salida Programada fue cancelada correctamente.');

        Notification::assertNothingSent();
    }

    // ---------- Alternativo 4.1 y RN-01 ----------

    public function test_el_motivo_es_obligatorio(): void
    {
        $this->cancelar(['motivo' => '   '])
            ->assertSessionHasErrors(['motivo' => 'El motivo de la cancelación es obligatorio.']);

        $this->assertSame('PROGRAMADA', $this->salida->fresh()->estado);
        $this->assertSame('DISPONIBLE', $this->paquete->fresh()->estado);
    }

    public function test_solo_se_cancelan_salidas_programadas(): void
    {
        $this->salida->update(['estado' => 'FINALIZADA']);

        $this->cancelar()
            ->assertRedirect(route('salidas.listar'))
            ->assertSessionHas('error');

        $this->assertSame('FINALIZADA', $this->salida->fresh()->estado);
        $this->assertNull($this->salida->fresh()->motivo_cancelacion);
    }

    public function test_el_cliente_no_puede_cancelar_salidas(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'cliente']))
            ->post(route('salidas.cancelar', $this->salida), ['motivo' => self::MOTIVO])
            ->assertForbidden();

        $this->assertSame('PROGRAMADA', $this->salida->fresh()->estado);
    }
}
