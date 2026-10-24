<?php

namespace Tests\Feature;

use App\Models\CertificadoMedico;
use App\Models\Cliente;
use App\Models\Excursion;
use App\Models\Paquete;
use App\Models\Participante;
use App\Models\Refugio;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use App\Models\User;
use Carbon\CarbonInterface;
use App\Notifications\CertificadoInvalido;
use App\Notifications\ReservaConfirmada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pruebas del CU 19 - Verificar certificado médico.
 * Correr con: php artisan test --filter=VerificarCertificadoMedicoTest
 */
class VerificarCertificadoMedicoTest extends TestCase
{
    use RefreshDatabase;

    private User $administrativo;
    private Reserva $reserva;
    private Participante $participante;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado
        Storage::fake();

        $this->administrativo = User::factory()->create(['rol' => 'admin']);
        // Salida: empieza en 10 días y termina en 12
        $this->reserva = $this->reserva(now()->addDays(10));
        $this->participante = $this->participanteConCertificado($this->reserva, '30111222');
    }

    private function reserva(CarbonInterface $inicioSalida, string $estado = Reserva::ESTADO_PENDIENTE, string $pago = Reserva::PAGO_PENDIENTE_DE_PAGO_TOTAL): Reserva
    {
        $salida = SalidaProgramada::factory()->create([
            'excursion_id' => Excursion::factory()->create()->id,
            'fecha_inicio' => $inicioSalida->toDateString(),
            'fecha_fin' => $inicioSalida->copy()->addDays(2)->toDateString(),
            'hora_de_salida' => $inicioSalida->format('H:i:s'),
        ]);
        $paquete = Paquete::factory()->create([
            'salida_programada_id' => $salida->id,
            'refugio_id' => Refugio::factory()->create()->id,
        ]);

        return Reserva::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
            'paquete_id' => $paquete->id,
            'estado' => $estado,
            'situacion_de_pago' => $pago,
        ]);
    }

    private function participanteConCertificado(Reserva $reserva, string $dni, string $estadoCertificado = CertificadoMedico::ESTADO_PENDIENTE_DE_VERIFICACION): Participante
    {
        $participante = $reserva->participantes()->create([
            'dni' => $dni,
            'nombre' => 'Nombre '.$dni,
            'apellido' => 'Apellido '.$dni,
            'fecha_nacimiento' => '1990-01-01',
            'estado_requisito_medico' => Participante::REQUISITO_PENDIENTE,
        ]);

        Storage::put("certificados/{$dni}.pdf", 'contenido');
        $participante->certificadoMedico()->create([
            'archivo' => "certificados/{$dni}.pdf",
            'fecha_de_carga' => now()->subDay(),
            'estado' => $estadoCertificado,
        ]);

        return $participante;
    }

    private function ingresarFecha(Participante $participante, string $fecha, ?User $usuario = null)
    {
        return $this->actingAs($usuario ?? $this->administrativo)->postJson(
            route('reservas.certificado.fecha-emision', [$participante->reserva_id, $participante->dni]),
            ['fecha_emision' => $fecha],
        );
    }

    private function certificado(): CertificadoMedico
    {
        return $this->participante->certificadoMedico()->first();
    }

    // ---------- Flujo normal ----------

    public function test_1_2_muestra_los_datos_del_certificado(): void
    {
        $this->actingAs($this->administrativo)
            ->getJson(route('reservas.certificado.verificar', [$this->reserva, '30111222']))
            ->assertOk()
            ->assertJsonPath('certificado.participante.dni', '30111222')
            ->assertJsonPath('certificado.reserva_id', $this->reserva->id)
            ->assertJsonPath('certificado.archivo.es_pdf', true)
            ->assertJsonPath('certificado.dias_de_vigencia', 30)
            ->assertJsonStructure(['certificado' => ['excursion', 'paquete', 'fecha_inicio', 'fecha_fin', 'fecha_de_carga', 'url_archivo']]);
    }

    public function test_2_muestra_el_archivo_adjunto(): void
    {
        $this->actingAs($this->administrativo)
            ->get(route('reservas.certificado.archivo', [$this->reserva, '30111222']))
            ->assertOk();
    }

    public function test_4_8_certificado_vigente_queda_valido_y_el_requisito_cumplido(): void
    {
        // Emitido hace 5 días: vence en 25, la salida termina en 12
        $this->ingresarFecha($this->participante, now()->subDays(5)->toDateString())
            ->assertOk()
            ->assertJsonPath('estado', CertificadoMedico::ESTADO_VALIDO)
            ->assertJsonPath('mensaje', 'El certificado fue verificado correctamente. El requisito médico del participante quedó cumplido.');

        $certificado = $this->certificado();
        $this->assertSame(CertificadoMedico::ESTADO_VALIDO, $certificado->estado);
        $this->assertSame(now()->subDays(5)->toDateString(), $certificado->fecha_emision->toDateString());
        $this->assertSame(now()->addDays(25)->toDateString(), $certificado->fecha_de_vencimiento->toDateString());
        $this->assertSame(Participante::REQUISITO_CUMPLIDO, $this->participante->fresh()->estado_requisito_medico);
    }

    // ---------- RF Notificar eventualidades ----------

    public function test_3_1_se_notifica_al_cliente_el_certificado_invalido_con_el_motivo(): void
    {
        Notification::fake();

        $this->actingAs($this->administrativo)
            ->postJson(route('reservas.certificado.invalidez', [$this->reserva, '30111222']), ['motivo' => 'Ilegible'])
            ->assertOk();

        Notification::assertSentTo($this->reserva->cliente->user, CertificadoInvalido::class,
            fn (CertificadoInvalido $aviso) => $aviso->certificado->motivo_de_invalidez === 'Ilegible');
    }

    public function test_7_1_se_notifica_al_cliente_si_la_vigencia_no_cubre_la_salida(): void
    {
        Notification::fake();

        // Emitido hace 25 días: vence en 5, antes de que termine la salida
        $this->ingresarFecha($this->participante, now()->subDays(25)->toDateString())->assertOk();

        Notification::assertSentTo($this->reserva->cliente->user, CertificadoInvalido::class);
    }

    public function test_si_la_reserva_queda_confirmada_se_notifica_la_confirmacion(): void
    {
        Notification::fake();
        $this->reserva->update(['situacion_de_pago' => Reserva::PAGO_COMPLETO]);

        $this->ingresarFecha($this->participante, now()->subDays(5)->toDateString())->assertOk();

        Notification::assertSentTo($this->reserva->cliente->user, ReservaConfirmada::class);
        Notification::assertNotSentTo($this->reserva->cliente->user, CertificadoInvalido::class);
    }

    public function test_un_certificado_valido_sin_confirmar_la_reserva_no_notifica(): void
    {
        Notification::fake();

        $this->ingresarFecha($this->participante, now()->subDays(5)->toDateString())->assertOk();

        Notification::assertNothingSent();
    }

    // ---------- Flujos alternativos ----------

    public function test_3_1_certificado_invalido_con_motivo_sin_fecha_de_emision(): void
    {
        $this->actingAs($this->administrativo)
            ->postJson(route('reservas.certificado.invalidez', [$this->reserva, '30111222']), ['motivo' => 'Ilegible'])
            ->assertOk()
            ->assertJsonPath('estado', CertificadoMedico::ESTADO_INVALIDO);

        $certificado = $this->certificado();
        $this->assertSame('Ilegible', $certificado->motivo_de_invalidez);
        $this->assertNull($certificado->fecha_emision);
        $this->assertSame(Participante::REQUISITO_PENDIENTE, $this->participante->fresh()->estado_requisito_medico);
    }

    public function test_3_1_el_motivo_de_invalidez_es_obligatorio(): void
    {
        $this->actingAs($this->administrativo)
            ->postJson(route('reservas.certificado.invalidez', [$this->reserva, '30111222']), ['motivo' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['motivo' => 'El motivo de invalidez es obligatorio.']);

        $this->assertTrue($this->certificado()->estaPendienteDeVerificacion());
    }

    public function test_5_1_la_fecha_de_emision_no_puede_ser_futura_ni_invalida(): void
    {
        foreach ([now()->addDay()->toDateString(), 'no-es-una-fecha', ''] as $fecha) {
            $this->ingresarFecha($this->participante, $fecha)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['fecha_emision' => 'Debe ingresar una fecha de emisión válida (no puede ser futura).']);
        }

        $this->assertTrue($this->certificado()->estaPendienteDeVerificacion());
    }

    public function test_7_1_la_vigencia_no_cubre_la_salida_y_queda_invalido(): void
    {
        // Emitido hace 25 días: vence en 5, la salida termina en 12
        $this->ingresarFecha($this->participante, now()->subDays(25)->toDateString())
            ->assertOk()
            ->assertJsonPath('estado', CertificadoMedico::ESTADO_INVALIDO);

        $certificado = $this->certificado();
        $this->assertSame('El certificado no cubre el período de la salida programada.', $certificado->motivo_de_invalidez);
        $this->assertSame(Participante::REQUISITO_PENDIENTE, $this->participante->fresh()->estado_requisito_medico);
    }

    public function test_rn01_la_vigencia_debe_cubrir_hasta_el_ultimo_dia_de_la_salida(): void
    {
        // Vence justo el día en que termina la salida (en 12 días): cubre
        $this->ingresarFecha($this->participante, now()->subDays(18)->toDateString())
            ->assertJsonPath('estado', CertificadoMedico::ESTADO_VALIDO);
    }

    // ---------- Confirmación de la reserva ----------

    public function test_confirma_la_reserva_con_pago_completo_y_todos_los_requisitos_cumplidos(): void
    {
        $reserva = $this->reserva(now()->addDays(10), pago: Reserva::PAGO_COMPLETO);
        $participante = $this->participanteConCertificado($reserva, '30111333');
        $reserva->participantes()->create([
            'dni' => '30111444', 'nombre' => 'Otro', 'apellido' => 'Ya verificado',
            'fecha_nacimiento' => '1990-01-01', 'estado_requisito_medico' => Participante::REQUISITO_CUMPLIDO,
        ]);

        $this->ingresarFecha($participante, now()->subDays(5)->toDateString())
            ->assertOk()
            ->assertJsonPath('mensaje', 'El certificado fue verificado correctamente. El requisito médico del participante quedó cumplido. La reserva quedó confirmada.');

        $this->assertSame(Reserva::ESTADO_CONFIRMADA, $reserva->fresh()->estado);
    }

    public function test_no_confirma_la_reserva_si_falta_el_pago_total(): void
    {
        $this->ingresarFecha($this->participante, now()->subDays(5)->toDateString())->assertOk();

        $this->assertSame(Reserva::ESTADO_PENDIENTE, $this->reserva->fresh()->estado);
    }

    public function test_no_confirma_la_reserva_si_otro_participante_sigue_pendiente(): void
    {
        $reserva = $this->reserva(now()->addDays(10), pago: Reserva::PAGO_COMPLETO);
        $participante = $this->participanteConCertificado($reserva, '30111333');
        $this->participanteConCertificado($reserva, '30111444');

        $this->ingresarFecha($participante, now()->subDays(5)->toDateString())->assertOk();

        $this->assertSame(Reserva::ESTADO_PENDIENTE, $reserva->fresh()->estado);
    }

    // ---------- Precondición ----------

    public function test_rn02_no_se_verifica_si_faltan_12_horas_o_menos(): void
    {
        $participante = $this->participanteConCertificado($this->reserva(now()->addHours(10)), '30111333');

        $this->ingresarFecha($participante, now()->subDays(5)->toDateString())->assertForbidden();
        $this->assertTrue($participante->certificadoMedico()->first()->estaPendienteDeVerificacion());
    }

    public function test_solo_se_verifican_certificados_pendientes_de_verificacion(): void
    {
        $participante = $this->participanteConCertificado($this->reserva, '30111333', CertificadoMedico::ESTADO_INVALIDO);

        $this->actingAs($this->administrativo)
            ->getJson(route('reservas.certificado.verificar', [$this->reserva, '30111333']))
            ->assertForbidden();
        $this->ingresarFecha($participante, now()->subDays(5)->toDateString())->assertForbidden();
    }

    public function test_solo_se_verifican_certificados_de_reservas_pendientes(): void
    {
        $participante = $this->participanteConCertificado($this->reserva(now()->addDays(10), Reserva::ESTADO_CANCELADA), '30111333');

        $this->ingresarFecha($participante, now()->subDays(5)->toDateString())->assertForbidden();
    }

    public function test_el_participante_debe_tener_un_certificado_cargado(): void
    {
        $this->reserva->participantes()->create([
            'dni' => '30111555', 'nombre' => 'Sin', 'apellido' => 'Certificado',
            'fecha_nacimiento' => '1990-01-01', 'estado_requisito_medico' => Participante::REQUISITO_PENDIENTE,
        ]);

        $this->actingAs($this->administrativo)
            ->getJson(route('reservas.certificado.verificar', [$this->reserva, '30111555']))
            ->assertNotFound();
    }

    public function test_el_cliente_no_puede_verificar_ni_ver_el_archivo(): void
    {
        $cliente = $this->reserva->cliente->user;

        $this->ingresarFecha($this->participante, now()->subDays(5)->toDateString(), $cliente)->assertForbidden();
        $this->actingAs($cliente)
            ->get(route('reservas.certificado.archivo', [$this->reserva, '30111222']))
            ->assertForbidden();
    }
}
