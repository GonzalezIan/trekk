<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas del CU 28 - Login usuario (RF-30) y del nombre de usuario en el registro (RF-12).
 * Correr con: php artisan test --filter=LoginUsuarioTest
 */
class LoginUsuarioTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // las pruebas no necesitan el frontend compilado
        $this->usuario = User::factory()->create([
            'usuario' => 'juan',
            'email' => 'juan@test.com',
        ]); // contraseña de la factory: "password"
    }

    private function registrar(array $cambios = [])
    {
        return $this->post(route('register'), [
            'name' => 'Ana Gómez',
            'usuario' => 'anagomez',
            'email' => 'ana@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            ...$cambios,
        ]);
    }

    // ---------- Login (CU 28 / RF-30) ----------

    public function test_ingresa_con_el_nombre_de_usuario(): void
    {
        $this->post(route('login'), ['login' => 'juan', 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($this->usuario);
    }

    public function test_ingresa_con_el_correo_electronico(): void
    {
        $this->post(route('login'), ['login' => 'juan@test.com', 'password' => 'password']);

        $this->assertAuthenticatedAs($this->usuario);
    }

    public function test_no_distingue_mayusculas_ni_espacios(): void
    {
        $this->post(route('login'), ['login' => '  Juan ', 'password' => 'password']);

        $this->assertAuthenticatedAs($this->usuario);
    }

    public function test_con_contrasena_incorrecta_no_ingresa(): void
    {
        $this->post(route('login'), ['login' => 'juan', 'password' => 'otra'])
            ->assertSessionHasErrors(['login' => 'El usuario o la contraseña no son correctos.']);

        $this->assertGuest();
    }

    public function test_un_usuario_inexistente_recibe_el_mismo_mensaje(): void
    {
        // No se revela si el usuario existe o no
        $this->post(route('login'), ['login' => 'noexiste', 'password' => 'password'])
            ->assertSessionHasErrors(['login' => 'El usuario o la contraseña no son correctos.']);

        $this->assertGuest();
    }

    public function test_los_datos_son_obligatorios(): void
    {
        $this->post(route('login'), ['login' => '', 'password' => ''])
            ->assertSessionHasErrors([
                'login' => 'Ingresá tu correo electrónico o nombre de usuario.',
                'password' => 'Ingresá tu contraseña.',
            ]);

        $this->assertGuest();
    }

    public function test_bloquea_despues_de_cinco_intentos_fallidos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), ['login' => 'juan', 'password' => 'otra']);
        }

        // Al sexto intento, aunque la contraseña sea correcta, sigue bloqueado
        $this->post(route('login'), ['login' => 'juan', 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    // ---------- Registro (RF-12): nombre de usuario ----------

    public function test_el_registro_guarda_el_usuario_en_minusculas(): void
    {
        $this->registrar(['usuario' => ' AnaGomez '])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'ana@test.com', 'usuario' => 'anagomez']);
    }

    public function test_el_usuario_es_obligatorio(): void
    {
        $this->registrar(['usuario' => ''])
            ->assertSessionHasErrors(['usuario' => 'Ingresá un nombre de usuario.']);

        $this->assertDatabaseMissing('users', ['email' => 'ana@test.com']);
    }

    public function test_el_usuario_no_se_puede_repetir_aunque_cambien_las_mayusculas(): void
    {
        $this->registrar(['usuario' => 'JUAN'])
            ->assertSessionHasErrors(['usuario' => 'Ese nombre de usuario ya está en uso.']);
    }

    public function test_el_usuario_no_admite_espacios_ni_tildes(): void
    {
        $this->registrar(['usuario' => 'ana gomez'])->assertSessionHasErrors('usuario');
        $this->registrar(['usuario' => 'josé'])->assertSessionHasErrors('usuario');
        $this->registrar(['usuario' => 'ab'])->assertSessionHasErrors('usuario'); // menos de 3

        $this->assertDatabaseMissing('users', ['email' => 'ana@test.com']);
    }

    public function test_se_puede_ingresar_con_el_usuario_recien_registrado(): void
    {
        $this->registrar();
        $this->post(route('logout'));

        $this->post(route('login'), ['login' => 'anagomez', 'password' => 'password']);

        $this->assertAuthenticated();
    }
}
