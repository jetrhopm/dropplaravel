<?php

namespace Tests\Feature;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenpayTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_is_redirected_to_openpay_checkout(): void
    {
        config(['services.openpay.enabled' => true, 'services.openpay.merchant_id' => 'merchant', 'services.openpay.private_key' => 'private', 'services.openpay.sandbox' => true]);
        $product = Producto::create(['nombre' => 'Producto de prueba', 'url_original' => 'https://example.com/producto', 'precio_original' => 100, 'activo' => true]);
        Http::fake(['https://sandbox-api.openpay.mx/v1/merchant/charges' => Http::response(['id' => 'charge-1', 'payment_method' => ['url' => 'https://sandbox.openpay.mx/checkout/test']], 201)]);

        $this->post(route('carrito.store'), ['producto_id' => $product->id, 'cantidad' => 1]);
        $this->post(route('checkout.store'), ['nombre' => 'Cliente', 'email' => 'cliente@example.com', 'telefono' => '5551234567', 'direccion' => 'Calle 1', 'ciudad' => 'CDMX', 'estado_envio' => 'CDMX', 'codigo_postal' => '01000', 'metodo_pago' => 'openpay_checkout'])
            ->assertRedirect('https://sandbox.openpay.mx/checkout/test');

        $this->assertDatabaseHas('pedidos', ['pago_gateway' => 'openpay', 'pago_referencia' => 'charge-1']);
    }
}
