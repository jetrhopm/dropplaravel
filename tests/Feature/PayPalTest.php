<?php

namespace Tests\Feature;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_is_redirected_to_paypal_checkout(): void
    {
        config(['services.paypal.enabled' => true, 'services.paypal.client_id' => 'client', 'services.paypal.secret' => 'secret', 'services.paypal.sandbox' => true]);
        $product = Producto::create(['nombre' => 'Producto de prueba', 'url_original' => 'https://example.com/producto', 'precio_original' => 100, 'activo' => true]);
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'token'], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response(['id' => 'PAYPAL-1', 'links' => [['rel' => 'approve', 'href' => 'https://www.paypal.com/checkout/test']]], 201),
        ]);

        $this->post(route('carrito.store'), ['producto_id' => $product->id, 'cantidad' => 1]);
        $this->post(route('checkout.store'), ['nombre' => 'Cliente', 'email' => 'cliente@example.com', 'telefono' => '5551234567', 'direccion' => 'Calle 1', 'ciudad' => 'CDMX', 'estado_envio' => 'CDMX', 'codigo_postal' => '01000', 'metodo_pago' => 'paypal_checkout'])
            ->assertRedirect('https://www.paypal.com/checkout/test');

        $this->assertDatabaseHas('pedidos', ['pago_gateway' => 'paypal', 'pago_referencia' => 'PAYPAL-1']);
    }
}
