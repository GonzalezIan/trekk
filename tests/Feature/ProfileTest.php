<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pantalla de Breeze que quedó solo para que el Administrativo cambie su contraseña.
 * La cuenta no se edita ni se elimina desde acá (ERS 2.3); el cliente usa «Mis datos» (CU 13).
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrativo_ve_la_pantalla_de_cambiar_contrasena(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'admin']))
            ->get('/profile')
            ->assertOk();
    }

    public function test_el_cliente_usa_mis_datos_en_lugar_de_esta_pantalla(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'cliente']))
            ->get('/profile')
            ->assertForbidden();
    }

    public function test_la_cuenta_no_se_puede_modificar_ni_eliminar(): void
    {
        $user = User::factory()->create(['rol' => 'admin', 'email' => 'admin@trekk.com']);

        $this->actingAs($user)
            ->patch('/profile', ['name' => 'Otro', 'email' => 'otro@trekk.com'])
            ->assertMethodNotAllowed();
        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertMethodNotAllowed();

        $this->assertNotNull($user->fresh());
        $this->assertSame('admin@trekk.com', $user->fresh()->email);
    }
}
