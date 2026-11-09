<?php

namespace Database\Seeders;

use App\Models\CertificadoMedico;
use App\Models\Cliente;
use App\Models\Conformidad;
use App\Models\Convenio;
use App\Models\Empleado;
use App\Models\Equipamiento;
use App\Models\Excursion;
use App\Models\Pago;
use App\Models\Paquete;
use App\Models\Participante;
use App\Models\Refugio;
use App\Models\Reserva;
use App\Models\SalidaProgramada;
use App\Models\Servicio;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de demostración para la entrega: respetan las reglas de negocio y cada CU tiene algo que mostrar.
 * Se carga con: php artisan migrate:fresh --seed --seeder=EntregaSeeder
 *
 * Usuarios: admin@trekk.com / admin123 y juan@test.com / password123.
 * El resto de los clientes usa la contraseña password123.
 * Todas las personas son ficticias. Los importes están en USD.
 */
class EntregaSeeder extends Seeder
{
    /** @var array<string, Cliente> */
    private array $clientes = [];

    /** @var array<string, Empleado> */
    private array $empleados = [];

    /** @var array<string, Equipamiento> */
    private array $equipos = [];

    /** @var array<string, Servicio> */
    private array $servicios = [];

    /** @var array<string, Refugio> */
    private array $refugios = [];

    /** @var array<string, Excursion> */
    private array $excursiones = [];

    public function run(): void
    {
        $this->crearUsuarios();
        $this->crearEmpleados();
        $this->crearCatalogos();
        $this->crearExcursiones();
        $this->crearSalidasFinalizadas();
        $this->crearSalidasProximas();
    }

    private function crearUsuarios(): void
    {
        User::create([
            'name' => 'Admin Trekk',
            'usuario' => 'admin',
            'email' => 'admin@trekk.com',
            'password' => Hash::make('admin123'),
            'rol' => 'admin',
            'email_verified_at' => now(),
        ]);

        $contrasena = Hash::make('password123');

        $personas = [
            ['juan', 'juan@test.com', '11223344', 'Juan', 'Pérez', '1990-05-15', '1122334455'],
            ['ana', 'ana.fernandez@example.com', '30111222', 'Ana', 'Fernández', '1985-03-22', '1155550101'],
            ['lucas', 'lucas.romero@example.com', '33222333', 'Lucas', 'Romero', '1992-08-09', '1155550102'],
            ['sofia', 'sofia.herrera@example.com', '35333444', 'Sofía', 'Herrera', '1994-11-30', '1155550103'],
            ['martin', 'martin.acosta@example.com', '28444555', 'Martín', 'Acosta', '1981-01-17', '1155550104'],
            ['valentina', 'valentina.rios@example.com', '37555666', 'Valentina', 'Ríos', '1996-06-05', '1155550105'],
            ['diego', 'diego.molina@example.com', '31666777', 'Diego', 'Molina', '1987-09-12', '1155550106'],
            ['camila', 'camila.sosa@example.com', '39777888', 'Camila', 'Sosa', '1999-02-25', '1155550107'],
        ];

        foreach ($personas as [$usuario, $email, $dni, $nombre, $apellido, $nacimiento, $telefono]) {
            $user = User::create([
                'name' => "$nombre $apellido",
                'usuario' => $usuario,
                'email' => $email,
                'password' => $contrasena,
                'rol' => Cliente::ROL_CLIENTE,
                'email_verified_at' => now(),
            ]);

            $this->clientes[$usuario] = Cliente::create([
                'user_id' => $user->id,
                'dni' => $dni,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'fecha_nacimiento' => $nacimiento,
                'telefono' => '+54' . $telefono,
            ]);
        }
    }

    private function crearEmpleados(): void
    {
        $empleados = [
            ['pedro', '25100200', 'GUIA', 'Pedro', 'Quiroga', 'ACTIVO'],
            ['elena', '27200300', 'GUIA', 'Elena', 'Paz', 'ACTIVO'],
            ['tomas', '29300400', 'GUIA', 'Tomás', 'Benítez', 'ACTIVO'],
            ['rocio', '32400500', 'GUIA', 'Rocío', 'Vega', 'ACTIVO'],
            ['nicolas', '34500600', 'ASISTENTE', 'Nicolás', 'Ibarra', 'ACTIVO'],
            ['julieta', '36600700', 'ASISTENTE', 'Julieta', 'Campos', 'ACTIVO'],
            ['ramiro', '26700800', 'GUIA', 'Ramiro', 'Luna', 'INACTIVO'],
        ];

        foreach ($empleados as [$clave, $dni, $categoria, $nombre, $apellido, $estado]) {
            $this->empleados[$clave] = Empleado::create([
                'dni' => $dni,
                'categoria' => $categoria,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'fecha_nacimiento' => '1985-01-01',
                'estado' => $estado,
            ]);
        }
    }

    private function crearCatalogos(): void
    {
        $equipos = [
            ['carpa', 'Carpa para dos personas', 'Carpa de alta montaña, doble techo.', 18, 60],
            ['bolsa', 'Bolsa de dormir', 'Temperatura de confort hasta -10 °C.', 9, 60],
            ['bastones', 'Bastones de trekking', 'Par de bastones telescópicos.', 5, 60],
            ['crampones', 'Crampones', 'Crampones de acero de doce puntas.', 12, 40],
            ['casco', 'Casco de montaña', 'Casco homologado para escalada.', 6, 40],
            ['mochila', 'Mochila de 60 litros', 'Mochila con armazón interno.', 8, 40],
        ];

        foreach ($equipos as [$clave, $nombre, $descripcion, $precio, $stock]) {
            $this->equipos[$clave] = Equipamiento::create([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio_alquiler' => $precio,
                'stock' => $stock,
            ]);
        }

        $servicios = [
            ['traslado', 'Traslado desde la ciudad', 'Ida y vuelta hasta el inicio del sendero.', 25],
            ['almuerzo', 'Almuerzo de montaña', 'Vianda caliente durante la caminata.', 15],
            ['seguro', 'Seguro de actividad', 'Cobertura médica durante la excursión.', 20],
            ['fotos', 'Registro fotográfico', 'Fotos de la jornada en formato digital.', 30],
        ];

        foreach ($servicios as [$clave, $nombre, $descripcion, $precio]) {
            $this->servicios[$clave] = Servicio::create([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio_servicio' => $precio,
            ]);
        }

        $refugios = [
            ['frey', 'Refugio Frey', 'Refugio de montaña a orillas de la laguna Toncek.', 'Cerro Catedral, Río Negro', 45, 24],
            ['meiling', 'Refugio Otto Meiling', 'Refugio entre los glaciares del Tronador.', 'Pampa Linda, Río Negro', 60, 20],
            ['lanin', 'Refugio Base Lanín', 'Refugio en la ruta normal del volcán.', 'Tromen, Neuquén', 50, 16],
            ['mulas', 'Refugio Plaza de Mulas', 'Campamento base del Aconcagua.', 'Las Heras, Mendoza', 90, 20],
        ];

        foreach ($refugios as [$clave, $nombre, $descripcion, $ubicacion, $precioCama, $camas]) {
            $this->refugios[$clave] = Refugio::create([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'ubicacion' => $ubicacion,
            ]);

            Convenio::create([
                'refugio_id' => $this->refugios[$clave]->id,
                'nombre_convenio' => "Convenio temporada 2026 - $nombre",
                'fecha_inicio' => '2026-09-01',
                'fecha_fin' => '2027-04-30',
                'camas_totales' => $camas,
                'camas_disponibles' => $camas,
                'precio_cama' => $precioCama,
            ]);
        }
    }

    private function crearExcursiones(): void
    {
        $excursiones = [
            ['tres', 'Laguna de los Tres', 'Caminata clásica hasta el mirador del Fitz Roy.', 'MEDIO', 1,
                'El Chaltén - Río Blanco - Laguna de los Tres', 'Ascenso por el bosque, cruce del río y tramo final de piedra.', 'NO', true, false,
                'Calzado de trekking, abrigo impermeable y dos litros de agua.', 'Centro de visitantes de El Chaltén'],
            ['frey', 'Travesía al Refugio Frey', 'Dos días por el cordón del Cerro Catedral con noche en refugio.', 'MEDIO', 2,
                'Villa Catedral - Arroyo Van Titter - Refugio Frey', 'Día uno: ascenso al refugio. Día dos: laguna Schmoll y regreso.', 'NO', true, false,
                'Bolsa de dormir, linterna frontal y abrigo.', 'Base del Cerro Catedral, Bariloche'],
            ['lanin', 'Ascenso al Volcán Lanín', 'Ascenso técnico a la cumbre del volcán por la ruta normal.', 'ALTA', 3,
                'Tromen - Espina de Pescado - Refugio Base - Cumbre', 'Aclimatación, noche en altura e intento de cumbre.', 'SI', false, true,
                'Botas rígidas, piquetas y ropa de alta montaña.', 'Paso Tromen, Neuquén'],
            ['tronador', 'Travesía Cerro Tronador', 'Tres días entre glaciares con noches en el Refugio Otto Meiling.', 'ALTA', 3,
                'Pampa Linda - Paso de las Nubes - Refugio Otto Meiling', 'Ascenso al refugio, escuela de hielo y regreso.', 'SI', false, true,
                'Botas rígidas, guantes y anteojos de glaciar.', 'Guardaparque de Pampa Linda'],
            ['esmeralda', 'Laguna Esmeralda', 'Sendero corto entre turberas con vista al valle de Tierra Mayor.', 'BAJA', 1,
                'Valle de Lasifashaj - Turbal - Laguna Esmeralda', 'Ida y vuelta por el mismo sendero.', 'NO', true, false,
                'Botas impermeables y abrigo.', 'Ruta 3, kilómetro 3030, Ushuaia'],
            ['uquia', 'Quebrada de las Señoritas', 'Paseo por los cañones rojizos de la Quebrada de Humahuaca.', 'BAJA', 1,
                'Uquía - Cañón Principal - Mirador', 'Recorrido circular por el cañón.', 'NO', true, false,
                'Protector solar, sombrero y agua.', 'Plaza de Uquía, Jujuy'],
            ['aconcagua', 'Trekking a Plaza de Mulas', 'Cinco días por la quebrada de Horcones hasta el campamento base.', 'ALTA', 5,
                'Horcones - Confluencia - Plaza de Mulas', 'Aclimatación en Confluencia y ascenso al campamento base.', 'SI', false, true,
                'Ropa de alta montaña y bolsa de dormir de -20 °C.', 'Portada de Horcones, Mendoza'],
            ['atuel', 'Cañón del Atuel', 'Caminata por el cañón. Fuera de temporada.', 'BAJA', 1,
                'Valle Grande - Cañón del Atuel', 'Recorrido lineal por la costa del río.', 'NO', true, false,
                'Calzado cómodo y agua.', 'Valle Grande, San Rafael'],
        ];

        foreach ($excursiones as [$clave, $nombre, $descripcion, $dificultad, $duracion, $recorrido, $etapas, $experiencia, $menores, $certificado, $equipamiento, $encuentro]) {
            $this->excursiones[$clave] = Excursion::create([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'dificultad' => $dificultad,
                'duracion' => $duracion,
                'recorrido' => $recorrido,
                'etapas' => $etapas,
                'experiencia_requerida' => $experiencia,
                'acepta_menores' => $menores,
                'requiere_certificado_medico' => $certificado,
                'equipamiento_obligatorio' => $equipamiento,
                'punto_de_encuentro' => $encuentro,
                'estado' => $clave === 'atuel' ? Excursion::ESTADO_INACTIVA : Excursion::ESTADO_ACTIVA,
            ]);
        }
    }

    // ---------- Salidas ya realizadas: alimentan el CU 20 y el CU 27 ----------

    private function crearSalidasFinalizadas(): void
    {
        $salida = $this->salida('tres', '2026-10-10', '07:30:00', 12, 90, 'pedro', [], ['bastones']);
        $paquete = $this->paquete($salida, 'Laguna de los Tres esencial', ['almuerzo']);
        $this->finalizar($salida);

        $this->conformidad($this->reserva('juan', $paquete, Reserva::ESTADO_REALIZADA, '2026-09-25 18:10:00', [
            $this->titular('juan'),
            $this->acompanante('41000111', 'Laura', 'Pérez', '1992-07-20'),
        ]), 5, 'Paisajes increíbles y el guía estuvo atento todo el recorrido.');
        $this->conformidad($this->reserva('ana', $paquete, Reserva::ESTADO_REALIZADA, '2026-09-28 10:45:00', [
            $this->titular('ana'),
        ]), 4, 'Muy linda experiencia, aunque el último tramo es exigente.');
        $this->conformidad($this->reserva('lucas', $paquete, Reserva::ESTADO_REALIZADA, '2026-10-01 21:20:00', [
            $this->titular('lucas'),
        ]), 5, 'La mejor caminata que hice en el sur.');

        $salida = $this->salida('frey', '2026-10-17', '08:00:00', 10, 110, 'elena', ['nicolas'], ['bolsa']);
        $paquete = $this->paquete($salida, 'Frey con noche en refugio', ['seguro'], 'frey');
        $this->finalizar($salida);

        // Queda sin conformidad para registrarla con el CU 20
        $this->reserva('juan', $paquete, Reserva::ESTADO_REALIZADA, '2026-10-02 12:30:00', [$this->titular('juan')]);
        $this->conformidad($this->reserva('sofia', $paquete, Reserva::ESTADO_REALIZADA, '2026-10-03 09:15:00', [
            $this->titular('sofia'),
            $this->acompanante('42000222', 'Bruno', 'Herrera', '1991-04-11'),
        ]), 3, 'El refugio estaba lleno y la cena se demoró bastante.');
        $this->conformidad($this->reserva('martin', $paquete, Reserva::ESTADO_REALIZADA, '2026-10-05 16:00:00', [
            $this->titular('martin'),
        ]), 4, 'Buena organización y grupo muy agradable.');

        $salida = $this->salida('lanin', '2026-10-30', '06:30:00', 8, 260, 'tomas', ['julieta'], ['crampones', 'casco']);
        $paquete = $this->paquete($salida, 'Lanín con base en refugio', ['seguro'], 'lanin');
        $this->finalizar($salida);

        $certificado = ['estado' => CertificadoMedico::ESTADO_VALIDO, 'emision' => '2026-10-12'];
        $this->conformidad($this->reserva('valentina', $paquete, Reserva::ESTADO_REALIZADA, '2026-10-12 19:40:00', [
            $this->titular('valentina', $certificado),
            $this->acompanante('43000333', 'Ignacio', 'Ríos', '1993-12-02', $certificado),
        ]), 4, 'Hicimos cumbre con buen clima. Muy recomendable.');
        $this->conformidad($this->reserva('diego', $paquete, Reserva::ESTADO_REALIZADA, '2026-10-14 08:25:00', [
            $this->titular('diego', $certificado),
        ]), 5, 'Guías muy profesionales en todo momento.');
    }

    // ---------- Salidas de fines de noviembre y diciembre ----------

    private function crearSalidasProximas(): void
    {
        // Cancelada antes de salir (CU 11): su reserva quedó cancelada con el motivo
        $salida = $this->salida('uquia', '2026-11-14', '09:00:00', 15, 55, 'rocio', [], []);
        $paquete = $this->paquete($salida, 'Señoritas clásico', ['almuerzo']);
        $motivo = 'Alerta meteorológica por viento Zonda en la zona.';
        $this->reserva('lucas', $paquete, Reserva::ESTADO_CANCELADA, '2026-11-02 11:05:00', [$this->titular('lucas')], motivo: $motivo);
        $salida->cancelar($motivo);

        $salida = $this->salida('tres', '2026-11-21', '07:30:00', 12, 95, 'pedro', ['nicolas'], ['bastones']);
        $esencial = $this->paquete($salida, 'Tres esencial', ['almuerzo']);
        $traslado = $this->paquete($salida, 'Tres con traslado', ['almuerzo', 'traslado']);
        $this->reserva('juan', $traslado, Reserva::ESTADO_CONFIRMADA, '2026-11-03 20:15:00', [
            $this->titular('juan'),
            $this->acompanante('41000111', 'Laura', 'Pérez', '1992-07-20'),
            $this->acompanante('58000444', 'Tomás', 'Pérez', '2012-03-14'),
        ], alquiler: ['bastones' => 2]);
        $this->reserva('ana', $esencial, Reserva::ESTADO_CONFIRMADA, '2026-11-04 13:50:00', [
            $this->titular('ana'),
            $this->acompanante('44000555', 'Pablo', 'Fernández', '1983-10-08'),
        ], moneda: 'ARS');
        $this->reserva('diego', $esencial, Reserva::ESTADO_CONFIRMADA, '2026-11-05 17:30:00', [$this->titular('diego')]);

        $salida = $this->salida('esmeralda', '2026-11-24', '09:30:00', 15, 60, 'pedro', [], []);
        $paquete = $this->paquete($salida, 'Esmeralda con traslado', ['traslado']);
        $this->reserva('juan', $paquete, Reserva::ESTADO_CANCELADA, '2026-11-01 15:00:00', [$this->titular('juan')],
            motivo: 'No puedo viajar en esa fecha.');
        $this->reserva('camila', $paquete, Reserva::ESTADO_CONFIRMADA, '2026-11-06 10:20:00', [
            $this->titular('camila'),
            $this->acompanante('45000666', 'Federico', 'Sosa', '1998-05-19'),
        ]);

        // Sin lugares: alternativo 5.1 del CU 14
        $salida = $this->salida('uquia', '2026-11-26', '09:00:00', 4, 55, 'elena', [], []);
        $paquete = $this->paquete($salida, 'Señoritas con almuerzo', ['almuerzo']);
        $this->reserva('martin', $paquete, Reserva::ESTADO_CONFIRMADA, '2026-11-02 18:45:00', [
            $this->titular('martin'),
            $this->acompanante('46000777', 'Paula', 'Acosta', '1983-02-14'),
            $this->acompanante('46000778', 'Hernán', 'Acosta', '1979-11-03'),
            $this->acompanante('46000779', 'Carla', 'Acosta', '1986-08-27'),
        ]);

        $salida = $this->salida('lanin', '2026-11-27', '06:30:00', 8, 270, 'tomas', ['julieta'], ['crampones', 'casco']);
        $paquete = $this->paquete($salida, 'Lanín cumbre', ['seguro'], 'lanin');
        // Juan carga su certificado con el CU 17 y el de Laura se verifica con el CU 19
        $this->reserva('juan', $paquete, Reserva::ESTADO_PENDIENTE, '2026-11-06 21:00:00', [
            $this->titular('juan', []),
            $this->acompanante('41000111', 'Laura', 'Pérez', '1992-07-20', ['estado' => CertificadoMedico::ESTADO_PENDIENTE_DE_VERIFICACION]),
        ]);
        $this->reserva('martin', $paquete, Reserva::ESTADO_CONFIRMADA, '2026-11-03 09:10:00', [
            $this->titular('martin', ['estado' => CertificadoMedico::ESTADO_VALIDO, 'emision' => '2026-11-05']),
        ]);
        $this->reserva('lucas', $paquete, Reserva::ESTADO_PENDIENTE, '2026-11-05 14:35:00', [
            $this->titular('lucas', ['estado' => CertificadoMedico::ESTADO_INVALIDO, 'motivo' => 'El archivo no permite leer la firma del médico.']),
        ]);

        // Reserva con seña: el saldo se abona con el CU 22 dentro de las 24 horas (RF-17)
        $salida = $this->salida('frey', '2026-11-28', '08:00:00', 10, 115, 'pedro', ['nicolas'], ['bolsa']);
        $paquete = $this->paquete($salida, 'Frey con refugio', ['seguro'], 'frey');
        $this->reserva('juan', $paquete, Reserva::ESTADO_PENDIENTE, now()->toDateTimeString(), [
            $this->titular('juan'),
            $this->acompanante('41000111', 'Laura', 'Pérez', '1992-07-20'),
        ], tipoPago: Pago::TIPO_SENA);
        $this->reserva('sofia', $paquete, Reserva::ESTADO_CONFIRMADA, '2026-11-04 19:05:00', [
            $this->titular('sofia'),
            $this->acompanante('42000222', 'Bruno', 'Herrera', '1991-04-11'),
        ]);

        $salida = $this->salida('tronador', '2026-12-04', '07:00:00', 8, 290, 'elena', ['julieta'], ['crampones', 'casco']);
        $paquete = $this->paquete($salida, 'Tronador con refugio', ['seguro'], 'meiling');
        $pendiente = ['estado' => CertificadoMedico::ESTADO_PENDIENTE_DE_VERIFICACION];
        $this->reserva('valentina', $paquete, Reserva::ESTADO_PENDIENTE, '2026-11-07 12:00:00', [$this->titular('valentina', $pendiente)]);
        $this->reserva('diego', $paquete, Reserva::ESTADO_PENDIENTE, '2026-11-07 22:40:00', [$this->titular('diego', $pendiente)]);

        // Un paquete cancelado (CU 6): la reserva muestra el aviso de cancelación
        $salida = $this->salida('esmeralda', '2026-12-05', '09:30:00', 15, 60, 'rocio', [], []);
        $this->paquete($salida, 'Esmeralda esencial', ['almuerzo']);
        $paquete = $this->paquete($salida, 'Esmeralda con fotos', ['fotos']);
        $this->reserva('ana', $paquete, Reserva::ESTADO_CANCELADA, '2026-11-05 11:30:00', [$this->titular('ana')],
            motivo: 'Cancelación del paquete: el servicio de fotografía no está disponible en esa fecha.');
        $paquete->update(['estado' => Paquete::ESTADO_CANCELADO]);

        // Sin reservas: se puede modificar o cancelar sin afectar a nadie
        $salida = $this->salida('aconcagua', '2026-12-12', '06:00:00', 6, 620, 'rocio', ['nicolas'], ['bolsa', 'mochila']);
        $this->paquete($salida, 'Plaza de Mulas con base', ['seguro', 'traslado'], 'mulas');
        $this->paquete($salida, 'Plaza de Mulas sin alojamiento', ['seguro']);

        // Sin paquetes: lista para registrar uno con el CU 5
        $this->salida('frey', '2026-12-19', '08:00:00', 10, 120, 'pedro', [], ['bolsa']);
    }

    // ---------- Ayudas ----------

    /** La fecha de fin sale de la duración de la excursión y el cupo se ajusta al stock (CU 8, RN-07). */
    private function salida(string $excursion, string $inicio, string $hora, int $cupo, float $precioBase, string $guia, array $asistentes, array $equipos): SalidaProgramada
    {
        $unaExcursion = $this->excursiones[$excursion];

        return SalidaProgramada::crear([
            'excursion_id' => $unaExcursion->id,
            'fecha_inicio' => $inicio,
            'fecha_fin' => Carbon::parse($inicio)->addDays($unaExcursion->duracion - 1)->toDateString(),
            'hora_de_salida' => $hora,
            'cupo_maximo' => $cupo,
            'precio_base' => $precioBase,
        ],
            $this->empleados[$guia],
            collect($asistentes)->map(fn ($clave) => $this->empleados[$clave]),
            collect($equipos)->map(fn ($clave) => $this->equipos[$clave]),
        );
    }

    private function finalizar(SalidaProgramada $salida): void
    {
        $salida->update(['estado' => SalidaProgramada::ESTADO_FINALIZADA]);
    }

    /** El precio se calcula con la regla del CU 5 (RN-04). */
    private function paquete(SalidaProgramada $salida, string $nombre, array $servicios, ?string $refugio = null): Paquete
    {
        return Paquete::crear(
            $salida,
            $nombre,
            'Incluye guía, seguimiento durante la excursión y los servicios detallados.',
            new Collection(array_map(fn ($clave) => $this->servicios[$clave], $servicios)),
            $refugio ? $this->refugios[$refugio]->load('convenios') : null,
        );
    }

    /** @param array<string, mixed>|null $certificado */
    private function titular(string $cliente, ?array $certificado = null): array
    {
        $unCliente = $this->clientes[$cliente];

        return $this->acompanante($unCliente->dni, $unCliente->nombre, $unCliente->apellido, $unCliente->fecha_nacimiento->toDateString(), $certificado);
    }

    /** @param array<string, mixed>|null $certificado */
    private function acompanante(string $dni, string $nombre, string $apellido, string $nacimiento, ?array $certificado = null): array
    {
        return compact('dni', 'nombre', 'apellido', 'nacimiento', 'certificado');
    }

    /**
     * Crea la reserva con sus participantes, alquileres y pago, y retiene cupo y camas si sigue ocupándolos.
     *
     * @param array<int, array<string, mixed>> $participantes
     * @param array<string, int> $alquiler
     */
    private function reserva(string $cliente, Paquete $paquete, string $estado, string $registro, array $participantes, array $alquiler = [], string $tipoPago = Pago::TIPO_TOTAL, string $moneda = 'USD', ?string $motivo = null): Reserva
    {
        $esSena = $tipoPago === Pago::TIPO_SENA;

        $reserva = Reserva::create([
            'cliente_id' => $this->clientes[$cliente]->id,
            'paquete_id' => $paquete->id,
            'cantidad_participantes' => count($participantes),
            'fecha_hora_registro' => $registro,
            'estado' => $estado,
            'situacion_de_pago' => $esSena ? Reserva::PAGO_PENDIENTE_DE_PAGO_TOTAL : Reserva::PAGO_COMPLETO,
            'importe_total' => 0,
            'motivo_cancelacion' => $motivo,
            'fecha_hora_vencimiento' => $esSena ? now()->addMinutes(Reserva::MINUTOS_PAGO_TOTAL) : null,
        ]);

        foreach ($alquiler as $equipo => $cantidad) {
            $reserva->equipos_alquilados()->attach($this->equipos[$equipo]->id, ['cantidad' => $cantidad]);
        }

        $reserva->update(['importe_total' => $reserva->calcularImporteTotal()]);

        foreach ($participantes as $datos) {
            $this->participante($reserva, $paquete, $datos, $registro);
        }

        $this->pago($reserva, $tipoPago, $moneda, $registro);

        if ($estado !== Reserva::ESTADO_CANCELADA) {
            $this->retenerRecursos($paquete, count($participantes), $estado);
        }

        return $reserva;
    }

    private function participante(Reserva $reserva, Paquete $paquete, array $datos, string $registro): void
    {
        $requiere = $paquete->salidaProgramada->excursion->requiere_certificado_medico;
        $certificado = $datos['certificado'];

        $requisito = Participante::REQUISITO_NO_REQUERIDO;
        if ($requiere) {
            $requisito = ($certificado['estado'] ?? null) === CertificadoMedico::ESTADO_VALIDO
                ? Participante::REQUISITO_CUMPLIDO
                : Participante::REQUISITO_PENDIENTE;
        }

        $participante = Participante::create([
            'reserva_id' => $reserva->id,
            'dni' => $datos['dni'],
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'fecha_nacimiento' => $datos['nacimiento'],
            'estado_requisito_medico' => $requisito,
        ]);

        if (! empty($certificado)) {
            $this->certificado($participante, $certificado, $registro);
        }
    }

    /** El archivo se guarda en el mismo disco privado que usa el CU 17. */
    private function certificado(Participante $participante, array $datos, string $registro): void
    {
        $ruta = "certificados/entrega-{$participante->id}.pdf";
        $emision = isset($datos['emision']) ? Carbon::parse($datos['emision']) : null;

        Storage::put($ruta, Pdf::loadHTML(
            '<h2>Certificado médico de ejemplo</h2>'
            . "<p>Paciente: {$participante->nombre} {$participante->apellido}, DNI {$participante->dni}.</p>"
            . '<p>Apto para actividad física de montaña.</p>'
            . '<p><em>Documento ficticio, generado para la demostración de Trekk.</em></p>'
        )->output());

        $participante->certificadoMedico()->create([
            'archivo' => $ruta,
            'estado' => $datos['estado'],
            'fecha_de_carga' => $registro,
            'fecha_emision' => $emision,
            'fecha_de_vencimiento' => $emision?->copy()->addDays(CertificadoMedico::DIAS_DE_VIGENCIA),
            'motivo_de_invalidez' => $datos['motivo'] ?? null,
        ]);
    }

    private function pago(Reserva $reserva, string $tipoPago, string $moneda, string $fecha): void
    {
        $importe = (float) $reserva->importe_total;
        $monto = $tipoPago === Pago::TIPO_SENA ? round($importe * Reserva::PORCENTAJE_SENA, 2) : $importe;

        Pago::create([
            'reserva_id' => $reserva->id,
            'monto' => $moneda === 'ARS' ? $monto * 1500 : $monto,
            'moneda' => $moneda,
            'medio_pago' => $moneda === 'ARS' ? 'Mercado Pago' : 'Tarjeta de crédito',
            'fecha_hora_pago' => $fecha,
            'tipo_pago' => $tipoPago,
            'resultado_transaccion' => Pago::RESULTADO_APROBADA,
        ]);
    }

    /** Las salidas finalizadas ya devolvieron las camas a los convenios (RF Finalizar). */
    private function retenerRecursos(Paquete $paquete, int $cantidad, string $estado): void
    {
        $salida = $paquete->salidaProgramada;
        $salida->retenerCupo($cantidad);

        if ($paquete->refugio && $estado !== Reserva::ESTADO_REALIZADA) {
            $paquete->refugio->retenerCamas($salida->fecha_inicio, $salida->fecha_fin, $cantidad);
        }
    }

    private function conformidad(Reserva $reserva, int $valoracion, string $comentario): void
    {
        Conformidad::create([
            'reserva_id' => $reserva->id,
            'valoracion' => $valoracion,
            'comentario' => $comentario,
        ]);
    }
}
