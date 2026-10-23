<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Pruebas del CU 13 - Modificar datos personales («Mis datos»).
 * Correr con: php artisan test --filter=ModificarDatosPersonalesTest
 */
class ModificarDatosPersonalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite(); // las pruebas no necesitan el frontend compilado
    }

    /** @return array<string, string> */
    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Carolina',
            'apellido' => 'Méndez Soto',
            'email' => 'carolina.mendez@mail.com',
            'telefono' => '+54 280 555-0000',
        ], $cambios);
    }

    public function test_el_cliente_ve_la_pantalla_mis_datos_con_su_informacion(): void
    {
        $cliente = Cliente::factory()->create([
            'dni' => '35123456',
            'nombre' => 'Carla',
            'apellido' => 'Méndez',
            'fecha_nacimiento' => '1991-04-18',
            'telefono' => '+54 280 455-1234',
        ]);

        $this->actingAs($cliente->user)
            ->get(route('perfil.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Perfil/Editar')
                ->where('cliente.dni', '35123456')
                ->where('cliente.nombre', 'Carla')
                ->where('cliente.apellido', 'Méndez')
                ->where('cliente.fechaNacimiento', '18/04/1991')
                ->where('cliente.telefono', '+54 280 455-1234')
                ->where('cliente.email', $cliente->user->email));
    }

    public function test_un_invitado_es_redirigido_al_inicio_de_sesion(): void
    {
        $this->get(route('perfil.edit'))->assertRedirect(route('login'));
        $this->patch(route('perfil.update'), $this->datosValidos())->assertRedirect(route('login'));
    }

    public function test_el_administrativo_no_tiene_ficha_de_cliente(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->assertNull($admin->cliente);

        $this->actingAs($admin)->get(route('perfil.edit'))->assertForbidden();
        $this->actingAs($admin)->patch(route('perfil.update'), $this->datosValidos())->assertForbidden();
    }

    public function test_modifica_email_telefono_nombre_y_apellido(): void
    {
        $cliente = Cliente::factory()->create([
            'nombre' => 'Carla',
            'apellido' => 'Méndez',
            'telefono' => '+54 280 455-1234',
        ]);

        $this->actingAs($cliente->user)
            ->patch(route('perfil.update'), $this->datosValidos())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('exito', 'Tus datos personales fueron actualizados correctamente.');

        $cliente->refresh();
        $this->assertSame('Carolina', $cliente->nombre);
        $this->assertSame('Méndez Soto', $cliente->apellido);
        $this->assertSame('+54 280 555-0000', $cliente->telefono);
        $this->assertSame('carolina.mendez@mail.com', $cliente->user->email);
    }

    public function test_actualiza_el_nombre_que_muestra_el_menu(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($cliente->user)->patch(route('perfil.update'), $this->datosValidos());

        $this->assertSame('Carolina Méndez Soto', $cliente->user->refresh()->name);
    }

    public function test_no_modifica_el_dni_ni_la_fecha_de_nacimiento(): void
    {
        $cliente = Cliente::factory()->create([
            'dni' => '35123456',
            'fecha_nacimiento' => '1991-04-18',
        ]);

        $this->actingAs($cliente->user)->patch(route('perfil.update'), $this->datosValidos([
            'dni' => '99999999',
            'fecha_nacimiento' => '2000-01-01',
        ]));

        $cliente->refresh();
        $this->assertSame('35123456', $cliente->dni);
        $this->assertSame('1991-04-18', $cliente->fecha_nacimiento->format('Y-m-d'));
    }

    public function test_rechaza_un_email_que_ya_usa_otra_cuenta(): void
    {
        $otro = User::factory()->create(['email' => 'otro@mail.com']);
        $cliente = Cliente::factory()->create();
        $emailOriginal = $cliente->user->email;

        $this->actingAs($cliente->user)
            ->patch(route('perfil.update'), $this->datosValidos(['email' => $otro->email]))
            ->assertSessionHasErrors(['email' => 'Ese correo electrónico ya está en uso por otra cuenta.']);

        $this->assertSame($emailOriginal, $cliente->user->refresh()->email);
    }

    public function test_permite_conservar_el_propio_email(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($cliente->user)
            ->patch(route('perfil.update'), $this->datosValidos(['email' => $cliente->user->email]))
            ->assertSessionHasNoErrors();
    }

    public function test_exige_todos_los_campos(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Carla']);

        $this->actingAs($cliente->user)
            ->patch(route('perfil.update'), [])
            ->assertSessionHasErrors([
                'nombre' => 'Completá el campo nombre.',
                'apellido' => 'Completá el campo apellido.',
                'email' => 'Completá el campo correo electrónico.',
                'telefono' => 'Completá el campo teléfono.',
            ]);

        $this->assertSame('Carla', $cliente->refresh()->nombre);
    }

    public function test_rechaza_un_email_con_formato_invalido(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($cliente->user)
            ->patch(route('perfil.update'), $this->datosValidos(['email' => 'no-es-un-email']))
            ->assertSessionHasErrors(['email' => 'Ingresá un correo electrónico válido.']);
    }

    public function test_solo_modifica_los_datos_del_cliente_autenticado(): void
    {
        $propio = Cliente::factory()->create(['nombre' => 'Carla']);
        $ajeno = Cliente::factory()->create(['nombre' => 'Otro']);

        $this->actingAs($propio->user)->patch(route('perfil.update'), $this->datosValidos([
            'nombre' => 'Modificado',
        ]));

        $this->assertSame('Modificado', $propio->refresh()->nombre);
        $this->assertSame('Otro', $ajeno->refresh()->nombre);
    }
}
