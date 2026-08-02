<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_routes_require_authentication(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_sign_in_and_manage_a_product(): void
    {
        $admin = User::create(['nombre' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password-segura']);

        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'password-segura'])
            ->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Dashboard');

        $this->post(route('admin.productos.store'), [
            'nombre' => 'Producto de administración', 'descripcion' => 'Descripción', 'plataforma' => 'otra',
            'url_original' => 'https://example.com/proveedor', 'precio_original' => 55, 'activo' => '1',
            'variantes' => "Color: Rojo, Azul\nTalla: M, G",
        ])->assertRedirect();

        $product = Producto::firstOrFail();
        $this->assertTrue($product->activo);
        $this->assertSame('Rojo', $product->variantes[0]['opciones'][0]);

        $this->patch(route('admin.productos.toggle', $product))->assertRedirect(route('admin.productos.index'));
        $this->assertFalse($product->fresh()->activo);
    }

    public function test_admin_can_change_own_password(): void
    {
        $admin = User::create(['nombre' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password-segura']);

        $this->actingAs($admin)->put(route('admin.configuracion.update'), [
            'nombre_tienda' => 'Mi Tienda', 'porcentaje_ganancia' => 80, 'costo_fijo' => 0, 'moneda' => 'MXN',
            'password' => 'nueva-password-segura', 'password_confirmation' => 'nueva-password-segura', 'current_password' => 'password-segura',
        ])->assertRedirect(route('admin.configuracion.edit'));

        $this->assertTrue(Hash::check('nueva-password-segura', $admin->fresh()->password));
    }
}
