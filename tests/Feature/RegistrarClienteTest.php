<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas del CU 12 - Registrar cliente (RF-12).
 * Correr con: php artisan test --filter=RegistrarClienteTest
 */
class RegistrarClienteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado
        $this->travelTo('2026-10-23 12:00:00'); // "hoy" fijo para la regla de los 18 años
    }

    private function datos(array $cambios = []): array
    {
        return [
            'dni' => '30123456',
            'nombre' => 'Ana',
            'apellido' => 'Gómez',
            'fecha_nacimiento' => '1990-05-15',
            'telefono' => '2804 123456',
            'email' => 'ana@test.com',
            'usuario' => 'anagomez',
            'password' => 'password',
            'password_confirmation' => 'password',
            ...$cambios,
        ];
    }

    private function registrar(array $cambios = [])
    {
        return $this->post(route('register'), $this->datos($cambios));
    }

    public function test_crea_la_cuenta_y_los_datos_del_cliente(): void
    {
        $this->registrar()
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('usuario', 'anagomez')->sole();
        $this->assertSame('Ana Gómez', $user->name);
        $this->assertSame('cliente', $user->rol);
        $this->assertSame('ana@test.com', $user->email);
        $this->assertAuthenticatedAs($user);

        $cliente = $user->cliente;
        $this->assertNotNull($cliente);
        $this->assertSame('30123456', $cliente->dni);
        $this->assertSame('Ana', $cliente->nombre);
        $this->assertSame('Gómez', $cliente->apellido);
        $this->assertSame('1990-05-15', $cliente->fecha_nacimiento->format('Y-m-d'));
        $this->assertSame('2804 123456', $cliente->telefono);
    }

    public function test_el_cliente_registrado_puede_ver_sus_reservas(): void
    {
        // Listar reservas busca el Cliente del usuario: ahora existe
        $this->registrar();

        $this->get(route('reservas.listar'))->assertOk();
    }

    public function test_todos_los_datos_son_obligatorios(): void
    {
        $this->post(route('register'), [])
            ->assertSessionHasErrors([
                'dni', 'nombre', 'apellido', 'fecha_nacimiento',
                'telefono', 'email', 'usuario', 'password',
            ]);

        $this->assertSame(0, User::count());
        $this->assertSame(0, Cliente::count());
    }

    public function test_hay_que_ser_mayor_de_18(): void
    {
        $this->registrar(['fecha_nacimiento' => '2008-10-24']) // cumple 18 mañana
            ->assertSessionHasErrors(['fecha_nacimiento' => 'Tenés que ser mayor de 18 años para registrarte.']);
        $this->assertSame(0, Cliente::count());

        $this->registrar(['fecha_nacimiento' => '2008-10-23']) // cumple 18 hoy
            ->assertSessionHasNoErrors();
    }

    public function test_el_dni_acepta_puntos_y_se_guarda_solo_con_numeros(): void
    {
        $this->registrar(['dni' => '30.123.456'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clientes', ['dni' => '30123456']);
    }

    public function test_el_dni_debe_tener_7_u_8_numeros(): void
    {
        $this->registrar(['dni' => '123456'])
            ->assertSessionHasErrors(['dni' => 'El DNI debe tener 7 u 8 números.']);
        $this->registrar(['dni' => '123456789'])->assertSessionHasErrors('dni');
        $this->registrar(['dni' => 'abc'])->assertSessionHasErrors('dni');
    }

    public function test_no_se_repite_el_dni(): void
    {
        Cliente::factory()->create(['dni' => '30123456']);

        $this->registrar()
            ->assertSessionHasErrors(['dni' => 'Ya existe un cliente registrado con ese DNI.']);
    }

    public function test_el_correo_se_guarda_en_minusculas_y_no_se_repite(): void
    {
        $this->registrar(['email' => 'Ana@Test.COM'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'ana@test.com']);
        $this->post(route('logout'));

        $this->registrar(['dni' => '30999999', 'usuario' => 'otraana', 'email' => 'ANA@test.com'])
            ->assertSessionHasErrors(['email' => 'Ya existe una cuenta con ese correo electrónico.']);
    }

    public function test_el_telefono_debe_ser_valido(): void
    {
        $this->registrar(['telefono' => 'llamame'])
            ->assertSessionHasErrors(['telefono' => 'El teléfono solo puede tener números, espacios, +, - y paréntesis.']);
    }

    public function test_las_contrasenas_deben_coincidir(): void
    {
        $this->registrar(['password_confirmation' => 'otracosa'])
            ->assertSessionHasErrors(['password' => 'Las contraseñas no coinciden.']);
    }

    public function test_si_falla_el_alta_del_cliente_no_queda_la_cuenta(): void
    {
        // Todo o nada: el DNI repetido hace fallar el alta del cliente después de crear el usuario
        Cliente::factory()->create(['dni' => '30123456']);
        $usuariosAntes = User::count();

        try {
            Cliente::registrar($this->datos());
            $this->fail('Se esperaba un error por DNI repetido.');
        } catch (QueryException) {
            // esperado
        }

        $this->assertSame($usuariosAntes, User::count());
        $this->assertDatabaseMissing('users', ['usuario' => 'anagomez']);
    }
}
