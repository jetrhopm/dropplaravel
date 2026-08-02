<?php

namespace Tests\Feature;

use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClipWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_clip_webhook_marks_a_pending_order_paid_only_once(): void
    {
        config(['services.clip.webhook_token' => 'webhook-token']);
        $order = Pedido::create([
            'numero' => 'DS-00001', 'cliente_nombre' => 'Cliente', 'cliente_email' => 'cliente@example.com', 'cliente_telefono' => '5551234567',
            'direccion' => 'Calle 1', 'ciudad' => 'CDMX', 'estado_envio' => 'CDMX', 'codigo_postal' => '01000',
            'metodo_pago' => 'Clip', 'pago_gateway' => 'clip', 'total' => 100,
        ]);
        $payload = ['status' => 'PAID', 'merch_inv_id' => 'DS-00001', 'id' => 'clip-payment-1', 'receipt_no' => 'receipt-1'];

        $this->postJson(route('webhooks.clip', ['token' => 'webhook-token']), $payload)->assertOk();
        $this->postJson(route('webhooks.clip', ['token' => 'webhook-token']), $payload)->assertOk();

        $this->assertDatabaseHas('pedidos', ['id' => $order->id, 'pago_estado' => 'pagado', 'pago_referencia' => 'clip-payment-1', 'clip_receipt_no' => 'receipt-1']);
    }
}
