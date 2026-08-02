<?php

namespace App\Services\Payments;

use App\Models\Pedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class MercadoPagoGateway
{
    public function isEnabled(): bool
    {
        return (bool) config('services.mercadopago.enabled') && (string) config('services.mercadopago.access_token') !== '';
    }

    public function create(Pedido $order): array
    {
        $response = Http::withToken((string) config('services.mercadopago.access_token'))
            ->acceptJson()
            ->post('https://api.mercadopago.com/checkout/preferences', [
                'items' => $order->items->map(fn ($item) => [
                    'title' => str($item->nombre_producto)->limit(250, ''),
                    'quantity' => $item->cantidad,
                    'unit_price' => (float) $item->precio_unitario,
                    'currency_id' => config('app.currency', 'MXN'),
                ])->all(),
                'external_reference' => $order->numero,
                'back_urls' => [
                    'success' => $this->returnUrl($order, 'success'),
                    'pending' => $this->returnUrl($order, 'pending'),
                    'failure' => $this->returnUrl($order, 'failure'),
                ],
                'auto_return' => 'approved',
                'statement_descriptor' => str(config('app.name', 'Tienda'))->limit(22, ''),
            ]);

        if (! $response->successful() || ! $response->json('init_point')) {
            throw new RuntimeException('No se pudo iniciar el pago con Mercado Pago.');
        }

        return ['url' => $response->json('init_point'), 'reference' => (string) $response->json('id')];
    }

    public function verify(Pedido $order, string $paymentId): array
    {
        $response = Http::withToken((string) config('services.mercadopago.access_token'))
            ->acceptJson()
            ->get('https://api.mercadopago.com/v1/payments/'.rawurlencode($paymentId));

        return match ($response->json('status')) {
            'approved' => ['status' => 'pagado', 'reference' => $paymentId],
            'rejected', 'cancelled' => ['status' => 'fallido', 'reference' => $paymentId],
            default => ['status' => 'pendiente', 'reference' => $paymentId ?: $order->pago_referencia],
        };
    }

    private function returnUrl(Pedido $order, string $result): string
    {
        return URL::signedRoute('pagos.retorno', ['pedido' => $order, 'gateway' => 'mercadopago', 'result' => $result]);
    }
}
