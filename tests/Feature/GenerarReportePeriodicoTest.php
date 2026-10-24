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
 * Pruebas del CU 27 - Generar reporte periódico.
 * Correr con: php artisan test --filter=GenerarReportePeriodicoTest
 */
class GenerarReportePeriodicoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado

        $this->admin = User::factory()->create(['rol' => 'admin']);
    }

    /** Reserva de una salida que termina en $fin, con sus participantes y, si se indica, su conformidad. */
    private function reserva(
        Cliente $cliente,
        Excursion $excursion,
        string $fin,
        int $participantes = 1,
        ?int $valoracion = null,
        ?string $comentario = null,
        string $estado = Reserva::ESTADO_REALIZADA,
    ): Reserva {
        $salida = SalidaProgramada::factory()->create([
            'excursion_id' => $excursion->id,
            'fecha_inicio' => date('Y-m-d', strtotime($fin.' -2 days')),
            'fecha_fin' => $fin,
            'estado' => 'FINALIZADA',
        ]);
        $reserva = Reserva::factory()->create([
            'cliente_id' => $cliente->id,
            'paquete_id' => Paquete::factory()->create(['salida_programada_id' => $salida->id])->id,
            'estado' => $estado,
        ]);

        foreach (range(1, $participantes) as $i) {
            $reserva->participantes()->create([
                'dni' => '3000000'.$i,
                'nombre' => 'Nombre',
                'apellido' => 'Apellido',
                'fecha_nacimiento' => '1990-01-01',
                'estado_requisito_medico' => 'NO_REQUERIDO',
            ]);
        }

        if ($valoracion) {
            $reserva->conformidad()->create(['valoracion' => $valoracion, 'comentario' => $comentario]);
        }

        return $reserva;
    }

    private function generar(string $desde, string $hasta)
    {
        return $this->actingAs($this->admin)
            ->get(route('admin.reportes.generar', ['fecha_inicio' => $desde, 'fecha_fin' => $hasta]));
    }

    // ---------- Flujo normal ----------

    public function test_1_2_el_sistema_muestra_la_pantalla_que_solicita_el_periodo(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reportes.index'))
            ->assertOk()
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->component('Admin/Reportes/Periodico')
                ->where('reporte', null));
    }

    public function test_5_a_9_genera_los_tres_reportes_ordenados(): void
    {
        $ana = Cliente::factory()->create(['nombre' => 'Ana', 'apellido' => 'Paz', 'dni' => '30111222']);
        $beto = Cliente::factory()->create(['nombre' => 'Beto']);
        $lago = Excursion::factory()->create(['nombre' => 'Laguna Esmeralda']);
        $cerro = Excursion::factory()->create(['nombre' => 'Cerro Tronador']);

        $this->reserva($ana, $lago, '2026-03-10', participantes: 2, valoracion: 4, comentario: 'Muy lindo');
        $this->reserva($ana, $cerro, '2026-03-20', participantes: 1, valoracion: 5);
        $this->reserva($beto, $lago, '2026-03-15', participantes: 3, valoracion: 2, comentario: 'Hizo frío');

        $this->generar('2026-03-01', '2026-03-31')
            ->assertOk()
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->component('Admin/Reportes/Periodico')
                ->where('reporte.total_reservas', 3)
                // RN-02: Ana hizo 2 excursiones, Beto 1
                ->where('reporte.clientes_frecuentes.0', [
                    'nombre' => 'Ana', 'apellido' => 'Paz', 'dni' => '30111222', 'excursiones' => 2,
                ])
                ->where('reporte.clientes_frecuentes.1.nombre', 'Beto')
                // RN-03: Laguna 2 + 3 = 5 participantes, Cerro 1
                ->where('reporte.actividades_frecuentes', [
                    ['excursion' => 'Laguna Esmeralda', 'participantes' => 5],
                    ['excursion' => 'Cerro Tronador', 'participantes' => 1],
                ])
                // RN-04: Cerro 5, Laguna (4 + 2) / 2 = 3, con los comentarios
                ->where('reporte.conformidad.0.excursion', 'Cerro Tronador')
                ->where('reporte.conformidad.0.valoracion_promedio', 5)
                ->where('reporte.conformidad.1.valoracion_promedio', 3)
                ->where('reporte.conformidad.1.valoraciones', 2)
                ->has('reporte.conformidad.1.comentarios', 2));
    }

    public function test_rn_01_solo_cuentan_las_realizadas_con_fecha_de_fin_dentro_del_periodo(): void
    {
        $cliente = Cliente::factory()->create();
        $excursion = Excursion::factory()->create();

        $this->reserva($cliente, $excursion, '2026-03-01');  // fin justo en el inicio del período: entra
        $this->reserva($cliente, $excursion, '2026-03-31');  // fin justo en el fin del período: entra
        $this->reserva($cliente, $excursion, '2026-04-01');  // termina después: no entra
        $this->reserva($cliente, $excursion, '2026-02-28');  // termina antes: no entra
        $this->reserva($cliente, $excursion, '2026-03-15', estado: Reserva::ESTADO_CONFIRMADA);
        $this->reserva($cliente, $excursion, '2026-03-15', estado: Reserva::ESTADO_CANCELADA);

        $this->generar('2026-03-01', '2026-03-31')
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->where('reporte.total_reservas', 2)
                ->where('reporte.clientes_frecuentes.0.excursiones', 2));
    }

    public function test_rn_04_las_reservas_sin_conformidad_no_cuentan_en_el_promedio(): void
    {
        $cliente = Cliente::factory()->create();
        $excursion = Excursion::factory()->create();
        $this->reserva($cliente, $excursion, '2026-03-10', valoracion: 4);
        $this->reserva($cliente, $excursion, '2026-03-12');

        $this->generar('2026-03-01', '2026-03-31')
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->where('reporte.conformidad.0.valoracion_promedio', 4)
                ->where('reporte.conformidad.0.valoraciones', 1));
    }

    public function test_10_exporta_el_reporte_en_csv(): void
    {
        $this->reserva(Cliente::factory()->create(['nombre' => 'Ana']), Excursion::factory()->create(['nombre' => 'Laguna Esmeralda']), '2026-03-10', valoracion: 4);

        $respuesta = $this->actingAs($this->admin)
            ->get(route('admin.reportes.exportar', ['fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31']))
            ->assertOk()
            ->assertDownload('reporte-periodico-2026-03-01-al-2026-03-31.csv');

        $csv = $respuesta->streamedContent();
        $this->assertStringContainsString('Reporte de clientes frecuentes', $csv);
        $this->assertStringContainsString('Ana', $csv);
        $this->assertStringContainsString('Laguna Esmeralda', $csv);
    }

    // ---------- Alternativos ----------

    public function test_3_1_falta_alguna_de_las_fechas(): void
    {
        $this->generar('', '2026-03-31')
            ->assertSessionHasErrors(['fecha_inicio' => 'Debe ingresar ambas fechas del período a consultar.']);

        $this->generar('2026-03-01', '')
            ->assertSessionHasErrors(['fecha_fin' => 'Debe ingresar ambas fechas del período a consultar.']);
    }

    public function test_3_2_la_fecha_de_inicio_es_posterior_a_la_de_fin(): void
    {
        $this->generar('2026-04-01', '2026-03-01')
            ->assertSessionHasErrors(['fecha_fin' => 'La fecha de inicio debe ser anterior o igual a la fecha de fin.']);
    }

    public function test_3_3_no_hay_reservas_realizadas_en_el_periodo(): void
    {
        $this->generar('2026-03-01', '2026-03-31')
            ->assertOk()
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->where('reporte', null)
                ->where('error', 'No se encontraron datos para el período seleccionado.'));
    }

    // ---------- Precondición ----------

    public function test_el_cliente_no_puede_generar_reportes(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($cliente->user)->get(route('admin.reportes.index'))->assertForbidden();
        $this->actingAs($cliente->user)
            ->get(route('admin.reportes.generar', ['fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31']))
            ->assertForbidden();
    }
}
