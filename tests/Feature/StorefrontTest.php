<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_complete_a_manual_payment_order(): void
    {
        Setting::create(['clave' => 'pago_transferencia', 'valor' => '1']);
        Setting::create(['clave' => 'pago_transferencia_datos', 'valor' => 'CLABE de prueba']);
        $product = Producto::create([
            'nombre' => 'Producto de prueba', 'url_original' => 'https://example.com/producto',
            'precio_original' => 100, 'activo' => true,
        ]);

        $this->get('/')->assertOk()->assertSee('Producto de prueba');
        $this->get(route('producto.show', $product))->assertOk();
        $this->post(route('carrito.store'), ['producto_id' => $product->id, 'cantidad' => 2])
            ->assertRedirect(route('carrito.index'));
        $this->get(route('checkout.create'))->assertOk();
        $this->post(route('checkout.store'), [
            'nombre' => 'Cliente de prueba', 'email' => 'cliente@example.com', 'telefono' => '5551234567',
            'direccion' => 'Calle 1', 'ciudad' => 'CDMX', 'estado_envio' => 'CDMX', 'codigo_postal' => '01000',
            'metodo_pago' => 'transferencia',
        ])->assertRedirect(route('pedido.confirmado'));

        $this->assertDatabaseHas('pedidos', ['cliente_email' => 'cliente@example.com', 'metodo_pago' => 'Transferencia bancaria']);
        $this->get(route('pedido.confirmado'))->assertOk()->assertSee('DS-00001');
    }
}
