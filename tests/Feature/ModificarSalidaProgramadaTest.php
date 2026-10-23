<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Convenio;
use App\Models\Empleado;
use App\Models\Equipamiento;
use App\Models\Excursion;
use App\Models\Paquete;
use App\Models\Refugio;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Pagina;
use Tests\TestCase;

/**
 * Pruebas del CU 10 - Modificar Salida Programada.
 * Correr con: php artisan test --filter=ModificarSalidaProgramadaTest
 */
class ModificarSalidaProgramadaTest extends TestCase
{
    use RefreshDatabase;

    private User $administrativo;
    private SalidaProgramada $salida;
    private Empleado $guia;
    private Equipamiento $carpa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado
        $this->travelTo('2026-10-20 10:00:00'); // "ahora" fijo para la regla del período futuro
        $this->administrativo = User::factory()->create(['rol' => 'admin']);

        // Salida de una excursión de 3 días, con guía y 10 carpas incluidas (stock total 30)
        $this->salida = SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create(['estado' => 'ACTIVA', 'duracion' => 3])->id,
            'fecha_inicio' => '2026-11-10',
            'fecha_fin' => '2026-11-12',
            'hora_de_salida' => '08:30',
            'cupo_maximo' => 10,
            'cupo_disponible' => 10,
            'precio_base' => 40000,
            'estado' => 'PROGRAMADA',
        ]);
        $this->guia = $this->empleado('GUIA');
        $this->salida->empleados()->attach($this->guia->id, ['rol' => 'GUIA']);
        $this->carpa = Equipamiento::factory()->create(['nombre' => 'Carpa', 'stock' => 30]);
        $this->salida->equipoPrestado()->attach($this->carpa->id, ['cantidad' => 10]);
    }

    private function empleado(string $categoria): Empleado
    {
        return Empleado::factory()->create(['categoria' => $categoria, 'estado' => 'ACTIVO']);
    }

    /** Los datos actuales de la salida, con los cambios indicados. */
    private function datos(array $cambios = []): array
    {
        return [
            'fecha_inicio' => '2026-11-10',
            'fecha_fin' => '2026-11-12',
            'hora_de_salida' => '08:30',
            'cupo_maximo' => 10,
            'precio_base' => 40000,
            'id_guia' => $this->guia->id,
            'ids_asistentes' => [],
            'ids_equipamiento' => [$this->carpa->id],
            ...$cambios,
        ];
    }

    private function modificar(array $cambios = [])
    {
        return $this->actingAs($this->administrativo)
            ->put(route('salidas.actualizar', $this->salida), $this->datos($cambios));
    }

    private function paquete(array $atributos = []): Paquete
    {
        return Paquete::factory()->create([
            'salida_programada_id' => $this->salida->id,
            'refugio_id' => null,
            'precio_calculado' => 40000,
            'estado' => 'DISPONIBLE',
            ...$atributos,
        ]);
    }

    /** Reserva de un cliente nuevo en un paquete de la salida, con esa cantidad de participantes. */
    private function reserva(Paquete $paquete, string $estado, int $participantes = 1): Reserva
    {
        $reserva = Reserva::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'paquete_id' => $paquete->id,
            'estado' => $estado,
            'importe_total' => 40000 * $participantes,
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

    // ---------- Pasos 1 y 2: formulario con los datos actuales ----------

    public function test_el_formulario_muestra_los_datos_actuales_de_la_salida(): void
    {
        $this->actingAs($this->administrativo)
            ->get(route('salidas.modificar', $this->salida))
            ->assertOk()
            ->assertInertia(fn (Pagina $pagina) => $pagina
                ->component('Salidas/Modificar')
                ->where('salida.id', $this->salida->id)
                ->where('salida.fecha_inicio', '2026-11-10')
                ->where('salida.hora_de_salida', '08:30')
                ->where('salida.cupo_maximo', 10)
                ->where('salida.duracion', 3)
                ->where('salida.id_guia', $this->guia->id)
                ->where('salida.ids_equipamiento', [$this->carpa->id])
                ->where('salida.participantes_activos', 0));
    }

    public function test_el_cliente_no_puede_modificar_salidas(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'cliente']))
            ->get(route('salidas.modificar', $this->salida))
            ->assertForbidden();
    }

    public function test_solo_se_modifican_salidas_programadas(): void
    {
        // RN-01
        $this->salida->update(['estado' => 'CANCELADA']);

        $this->actingAs($this->administrativo)
            ->get(route('salidas.modificar', $this->salida))
            ->assertRedirect(route('salidas.listar'))
            ->assertSessionHas('error');

        $this->modificar(['precio_base' => 50000])
            ->assertRedirect(route('salidas.listar'))
            ->assertSessionHas('error');
        $this->assertSame('40000.00', $this->salida->fresh()->precio_base);
    }

    // ---------- Pasos 3 a 13: caso feliz ----------

    public function test_modifica_la_salida_y_recalcula_cupo_y_precios(): void
    {
        $paquete = $this->paquete();
        $reserva = $this->reserva($paquete, 'PENDIENTE', 2);
        $this->salida->update(['cupo_disponible' => 8]); // 2 lugares ocupados

        $this->modificar([
            'fecha_inicio' => '2026-11-15',
            'fecha_fin' => '2026-11-17',
            'hora_de_salida' => '09:00',
            'cupo_maximo' => 12,
            'precio_base' => 50000,
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('salidas.listar'))
            ->assertSessionHas('exito');

        $salida = $this->salida->fresh();
        $this->assertSame('2026-11-15', $salida->fecha_inicio->format('Y-m-d'));
        $this->assertSame('2026-11-17', $salida->fecha_fin->format('Y-m-d'));
        $this->assertSame('09:00', $salida->hora_de_salida->format('H:i'));
        $this->assertSame(12, $salida->cupo_maximo);
        // Paso 9: se conservan los 2 lugares ocupados
        $this->assertSame(10, $salida->cupo_disponible);
        $this->assertSame('50000.00', $salida->precio_base);
        // Paso 9: el equipamiento incluido acompaña al nuevo cupo
        $this->assertSame(12, $salida->equipoPrestado()->sole()->pivot->cantidad);
        // Paso 10 (RN-05): el paquete toma el precio nuevo, la reserva ya hecha no cambia
        $this->assertSame('50000.00', $paquete->fresh()->precio_calculado);
        $this->assertSame('80000.00', $reserva->fresh()->importe_total);
    }

    public function test_reemplaza_el_personal_asignado(): void
    {
        $nuevoGuia = $this->empleado('GUIA');
        $asistente = $this->empleado('ASISTENTE');

        $this->modificar(['id_guia' => $nuevoGuia->id, 'ids_asistentes' => [$asistente->id]])
            ->assertSessionHasNoErrors();

        $asignados = $this->salida->empleados()->get()->mapWithKeys(fn ($e) => [$e->id => $e->pivot->rol]);
        $this->assertEquals([$nuevoGuia->id => 'GUIA', $asistente->id => 'ASISTENTE'], $asignados->all());
    }

    // ---------- La salida que se modifica no se cuenta a sí misma ----------

    public function test_el_personal_y_el_stock_propios_siguen_disponibles(): void
    {
        $this->actingAs($this->administrativo)
            ->getJson(route('salidas.modificar.empleados', [
                'salida' => $this->salida->id, 'categoria' => 'GUIA',
                'fecha_inicio' => '2026-11-10', 'fecha_fin' => '2026-11-12',
            ]))
            ->assertOk()
            ->assertJsonPath('empleados.0.id', $this->guia->id);

        // Las 10 carpas que ya tiene incluidas no se descuentan del stock
        $this->getJson(route('salidas.modificar.equipamiento', [
            'salida' => $this->salida->id, 'fecha_inicio' => '2026-11-10', 'fecha_fin' => '2026-11-12',
        ]))
            ->assertOk()
            ->assertJsonPath('equipamientos.0.stock_disponible', 30);

        // Guardar sin cambios no da el guía por ocupado
        $this->modificar()->assertSessionHasNoErrors();
    }

    public function test_el_personal_de_otra_salida_superpuesta_no_es_asignable(): void
    {
        $ocupado = $this->empleado('GUIA');
        SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create(['estado' => 'ACTIVA'])->id,
            'fecha_inicio' => '2026-11-11',
            'fecha_fin' => '2026-11-13',
            'estado' => 'PROGRAMADA',
        ])->empleados()->attach($ocupado->id, ['rol' => 'GUIA']);

        $this->modificar(['id_guia' => $ocupado->id])->assertSessionHasErrors('id_guia');
        $this->assertSame($this->guia->id, $this->salida->empleados()->sole()->id);
    }

    // ---------- Alternativos 4.1 y 4.2 ----------

    public function test_el_periodo_debe_corresponderse_con_la_duracion(): void
    {
        $this->modificar(['fecha_fin' => '2026-11-11'])->assertSessionHasErrors('fecha_fin');
        $this->modificar(['fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-03'])
            ->assertSessionHasErrors('fecha_inicio');

        $this->assertSame('2026-11-12', $this->salida->fresh()->fecha_fin->format('Y-m-d'));
    }

    public function test_cupo_y_precio_deben_ser_mayores_a_cero(): void
    {
        $this->modificar(['cupo_maximo' => 0, 'precio_base' => 0])
            ->assertSessionHasErrors(['cupo_maximo', 'precio_base']);

        $this->assertSame(10, $this->salida->fresh()->cupo_maximo);
    }

    // ---------- Alternativo 5.1 ----------

    public function test_los_convenios_deben_cubrir_el_nuevo_periodo(): void
    {
        $refugio = Refugio::factory()->create(['nombre' => 'Refugio Frey']);
        Convenio::create([
            'refugio_id' => $refugio->id,
            'nombre_convenio' => 'Temporada',
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-20',
            'camas_totales' => 20,
            'camas_disponibles' => 20,
            'precio_cama' => 5000,
        ]);
        $paquete = $this->paquete(['nombre' => 'Con refugio', 'refugio_id' => $refugio->id]);

        // Dentro del convenio: se acepta y el precio suma 2 noches de alojamiento
        $this->modificar(['fecha_inicio' => '2026-11-15', 'fecha_fin' => '2026-11-17'])
            ->assertSessionHasNoErrors();
        $this->assertSame('50000.00', $paquete->fresh()->precio_calculado);

        // Fuera del convenio: se informa el paquete y el refugio afectados
        $this->modificar(['fecha_inicio' => '2026-11-25', 'fecha_fin' => '2026-11-27'])
            ->assertSessionHasErrors([
                'fecha_inicio' => 'Los convenios de estos refugios no cubren el nuevo período: '
                    . 'paquete “Con refugio” (refugio Refugio Frey).',
            ]);
        $this->assertSame('2026-11-15', $this->salida->fresh()->fecha_inicio->format('Y-m-d'));
    }

    // ---------- Alternativo 6.1 y RN-02 ----------

    public function test_el_equipamiento_nuevo_debe_tener_stock_para_el_cupo(): void
    {
        $bastones = Equipamiento::factory()->create(['nombre' => 'Bastones', 'stock' => 5]);

        $this->modificar(['ids_equipamiento' => [$this->carpa->id, $bastones->id]])
            ->assertSessionHasErrors([
                'ids_equipamiento' => 'El stock disponible no alcanza para el cupo máximo ingresado: '
                    . 'Bastones (stock disponible: 5).',
            ]);

        $this->assertSame([$this->carpa->id], $this->salida->equipoPrestado()->pluck('equipamientos.id')->all());
    }

    public function test_el_cupo_se_ajusta_al_stock_del_equipamiento_ya_incluido(): void
    {
        // Otra salida superpuesta usa 20 de las 30 carpas: a esta le quedan 10
        SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create(['estado' => 'ACTIVA'])->id,
            'fecha_inicio' => '2026-11-11',
            'fecha_fin' => '2026-11-13',
            'estado' => 'PROGRAMADA',
        ])->equipoPrestado()->attach($this->carpa->id, ['cantidad' => 20]);

        $this->modificar(['cupo_maximo' => 15])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('exito', fn ($mensaje) => str_contains($mensaje, 'se ajustó al stock'));

        $salida = $this->salida->fresh();
        $this->assertSame(10, $salida->cupo_maximo);
        $this->assertSame(10, $salida->equipoPrestado()->sole()->pivot->cantidad);
    }

    // ---------- Alternativo 8.1 ----------

    public function test_el_cupo_no_puede_ser_menor_a_los_participantes_activos(): void
    {
        $paquete = $this->paquete();
        $this->reserva($paquete, 'PENDIENTE', 2);
        $this->reserva($paquete, 'CONFIRMADA', 1);
        $this->reserva($paquete, 'CANCELADA', 4); // no cuenta

        $this->modificar(['cupo_maximo' => 2])
            ->assertSessionHasErrors([
                'cupo_maximo' => 'El cupo no puede reducirse por debajo de la cantidad ya reservada (3 participantes).',
            ]);
        $this->assertSame(10, $this->salida->fresh()->cupo_maximo);

        $this->modificar(['cupo_maximo' => 3])->assertSessionHasNoErrors();
    }

    // ---------- *a (RN-06): notificación a clientes con reservas activas ----------

    public function test_se_notifica_a_los_clientes_si_cambia_el_personal(): void
    {
        $paquete = $this->paquete();
        $this->reserva($paquete, 'PENDIENTE');
        $this->reserva($paquete, 'CONFIRMADA');
        $this->reserva($paquete, 'CANCELADA'); // no se notifica

        $this->modificar(['id_guia' => $this->empleado('GUIA')->id])
            ->assertSessionHas('exito', fn ($mensaje) =>
                str_contains($mensaje, 'Se notificó el cambio a 2 clientes con reservas activas.'));
    }

    public function test_no_se_notifica_si_solo_cambia_el_precio(): void
    {
        $this->reserva($this->paquete(), 'PENDIENTE');

        $this->modificar(['precio_base' => 45000])
            ->assertSessionHas('exito', fn ($mensaje) => ! str_contains($mensaje, 'notificó'));
    }
}
