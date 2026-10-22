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
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pruebas del CU 17 - Cargar certificado médico.
 * Correr con: php artisan test --filter=CargarCertificadoMedicoTest
 */
class CargarCertificadoMedicoTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;
    private Reserva $reserva;
    private Participante $participante;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado
        Storage::fake();

        $this->cliente = Cliente::factory()->create();
        $this->reserva = $this->reserva(now()->addDays(10));
        $this->participante = $this->participante($this->reserva, '30111222');
    }

    private function reserva(CarbonInterface $inicioSalida, string $estado = Reserva::ESTADO_PENDIENTE): Reserva
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
            'cliente_id' => $this->cliente->id,
            'paquete_id' => $paquete->id,
            'estado' => $estado,
        ]);
    }

    private function participante(Reserva $reserva, string $dni, string $requisito = Participante::REQUISITO_PENDIENTE): Participante
    {
        return $reserva->participantes()->create([
            'dni' => $dni,
            'nombre' => 'Nombre '.$dni,
            'apellido' => 'Apellido '.$dni,
            'fecha_nacimiento' => '1990-01-01',
            'estado_requisito_medico' => $requisito,
        ]);
    }

    private function cargar(Participante $participante, ?UploadedFile $archivo)
    {
        return $this->actingAs($this->cliente->user)->post(
            route('reservas.certificado.registrar', [$participante->reserva_id, $participante->dni]),
            $archivo ? ['archivo' => $archivo] : [],
            ['Accept' => 'application/json'],
        );
    }

    // ---------- Flujo normal ----------

    public function test_1_2_el_sistema_solicita_el_archivo_en_los_formatos_permitidos(): void
    {
        $this->actingAs($this->cliente->user)
            ->getJson(route('reservas.certificado.cargar', [$this->reserva, $this->participante->dni]))
            ->assertOk()
            ->assertJsonPath('participante.dni', '30111222')
            ->assertJsonPath('formatos', ['pdf', 'png', 'jpg', 'jpeg']);
    }

    public function test_3_4_registra_el_certificado_pendiente_de_verificacion(): void
    {
        $this->cargar($this->participante, UploadedFile::fake()->create('certificado.pdf', 100))
            ->assertOk()
            ->assertJsonPath('participante.estado_certificado', CertificadoMedico::ESTADO_PENDIENTE_DE_VERIFICACION)
            ->assertJsonPath('mensaje', 'El certificado fue cargado correctamente y se encuentra a la espera de verificación por parte del administrativo.');

        $certificado = $this->participante->certificadoMedico()->first();
        $this->assertSame(CertificadoMedico::ESTADO_PENDIENTE_DE_VERIFICACION, $certificado->estado);
        $this->assertNotNull($certificado->fecha_de_carga);
        Storage::assertExists($certificado->archivo);

        // El requisito médico permanece "Pendiente" hasta el CU 19
        $this->assertSame(Participante::REQUISITO_PENDIENTE, $this->participante->fresh()->estado_requisito_medico);
    }

    public function test_rn01_acepta_imagenes_png_jpg_y_jpeg(): void
    {
        foreach (['certificado.png', 'certificado.jpg', 'certificado.jpeg'] as $nombre) {
            $this->cargar($this->participante, UploadedFile::fake()->create($nombre, 100))->assertOk();
        }
    }

    // ---------- Flujos alternativos ----------

    public function test_3_1_el_archivo_es_obligatorio(): void
    {
        $this->cargar($this->participante, null)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['archivo' => 'El archivo del certificado médico es obligatorio.']);

        $this->assertDatabaseCount('certificados_medicos', 0);
    }

    public function test_3_2_el_archivo_debe_estar_en_un_formato_permitido(): void
    {
        $this->cargar($this->participante, UploadedFile::fake()->create('certificado.docx', 100))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['archivo' => 'El archivo debe estar en formato pdf, png, jpg, jpeg.']);

        $this->assertDatabaseCount('certificados_medicos', 0);
    }

    // ---------- Reglas de negocio ----------

    public function test_rn04_la_nueva_carga_reemplaza_al_certificado_anterior(): void
    {
        $this->cargar($this->participante, UploadedFile::fake()->create('primero.pdf', 100));
        $anterior = $this->participante->certificadoMedico()->first();

        $this->cargar($this->participante, UploadedFile::fake()->create('segundo.pdf', 100))->assertOk();

        $this->assertDatabaseCount('certificados_medicos', 1);
        Storage::assertMissing($anterior->archivo);
        Storage::assertExists($this->participante->certificadoMedico()->first()->archivo);
    }

    public function test_rn03_no_se_puede_cargar_si_faltan_24_horas_o_menos(): void
    {
        $participante = $this->participante($this->reserva(now()->addHours(20)), '30111333');

        $this->cargar($participante, UploadedFile::fake()->create('certificado.pdf', 100))->assertForbidden();
        $this->assertDatabaseCount('certificados_medicos', 0);
    }

    // ---------- Precondición ----------

    public function test_no_se_puede_cargar_si_el_requisito_medico_no_esta_pendiente(): void
    {
        $participante = $this->participante($this->reserva, '30111444', Participante::REQUISITO_CUMPLIDO);

        $this->cargar($participante, UploadedFile::fake()->create('certificado.pdf', 100))->assertForbidden();
    }

    public function test_solo_se_puede_cargar_en_una_reserva_pendiente(): void
    {
        $estados = [Reserva::ESTADO_CONFIRMADA, Reserva::ESTADO_CANCELADA, Reserva::ESTADO_REALIZADA];

        foreach ($estados as $i => $estado) {
            $participante = $this->participante($this->reserva(now()->addDays(10), $estado), '3011155'.$i);

            $this->cargar($participante, UploadedFile::fake()->create('certificado.pdf', 100))->assertForbidden();
        }

        $this->assertDatabaseCount('certificados_medicos', 0);
    }

    public function test_el_cliente_no_puede_cargar_certificados_de_reservas_ajenas(): void
    {
        $this->actingAs(Cliente::factory()->create()->user)
            ->post(
                route('reservas.certificado.registrar', [$this->reserva, $this->participante->dni]),
                ['archivo' => UploadedFile::fake()->create('certificado.pdf', 100)],
                ['Accept' => 'application/json'],
            )
            ->assertForbidden();
    }

    public function test_el_participante_debe_pertenecer_a_la_reserva(): void
    {
        $this->actingAs($this->cliente->user)
            ->getJson(route('reservas.certificado.cargar', [$this->reserva, '99999999']))
            ->assertNotFound();
    }

    // ---------- Integración con el CU 21 ----------

    public function test_el_listado_de_participantes_muestra_el_estado_del_certificado(): void
    {
        $this->cargar($this->participante, UploadedFile::fake()->create('certificado.pdf', 100));

        $this->actingAs($this->cliente->user)
            ->getJson(route('reservas.participantes', $this->reserva))
            ->assertJsonPath('participantes.0.estado_certificado', CertificadoMedico::ESTADO_PENDIENTE_DE_VERIFICACION);
    }
}
