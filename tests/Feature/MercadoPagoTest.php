<?php

namespace Tests\Feature;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_is_redirected_to_mercado_pago_checkout(): void
    {
        config(['services.mercadopago.enabled' => true, 'services.mercadopago.access_token' => 'test-token']);
        $product = Producto::create(['nombre' => 'Producto de prueba', 'url_original' => 'https://example.com/producto', 'precio_original' => 100, 'activo' => true]);
        Http::fake(['https://api.mercadopago.com/checkout/preferences' => Http::response(['id' => 'preference-1', 'init_point' => 'https://www.mercadopago.com/checkout/test'], 201)]);

        $this->post(route('carrito.store'), ['producto_id' => $product->id, 'cantidad' => 1]);
        $this->post(route('checkout.store'), ['nombre' => 'Cliente', 'email' => 'cliente@example.com', 'telefono' => '5551234567', 'direccion' => 'Calle 1', 'ciudad' => 'CDMX', 'estado_envio' => 'CDMX', 'codigo_postal' => '01000', 'metodo_pago' => 'mercadopago_checkout'])
            ->assertRedirect('https://www.mercadopago.com/checkout/test');

        $this->assertDatabaseHas('pedidos', ['pago_gateway' => 'mercadopago', 'pago_referencia' => 'preference-1']);
    }
}
