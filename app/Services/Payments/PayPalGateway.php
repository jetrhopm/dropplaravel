<?php

namespace App\Services\Payments;

use App\Models\Pedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class PayPalGateway
{
    public function isEnabled(): bool
    {
        return (bool) config('services.paypal.enabled')
            && (string) config('services.paypal.client_id') !== ''
            && (string) config('services.paypal.secret') !== '';
    }

    public function create(Pedido $order): array
    {
        $response = Http::withToken($this->token())
            ->acceptJson()
            ->withHeader('PayPal-Request-Id', $order->numero)
            ->post($this->baseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->numero,
                    'description' => str('Pedido '.$order->numero.' - '.config('app.name', 'Tienda'))->limit(127, ''),
                    'amount' => ['currency_code' => config('app.currency', 'MXN'), 'value' => number_format((float) $order->total, 2, '.', '')],
                ]],
                'application_context' => [
                    'return_url' => $this->returnUrl($order, 'success'),
                    'cancel_url' => $this->returnUrl($order, 'cancel'),
                    'brand_name' => config('app.name', 'Tienda'),
                    'user_action' => 'PAY_NOW',
                ],
            ]);

        $approvalUrl = collect($response->json('links', []))->firstWhere('rel', 'approve')['href'] ?? null;
        if (! $response->successful() || ! $approvalUrl) {
            throw new RuntimeException('No se pudo iniciar el pago con PayPal.');
        }

        return ['url' => $approvalUrl, 'reference' => (string) $response->json('id')];
    }

    public function verify(Pedido $order, array $parameters): array
    {
        $orderId = (string) ($parameters['token'] ?? $order->pago_referencia);
        if (($parameters['result'] ?? '') === 'cancel' || $orderId === '') {
            return ['status' => 'fallido', 'reference' => $orderId];
        }

        $response = Http::withToken($this->token())
            ->acceptJson()
            ->withHeader('PayPal-Request-Id', $order->numero.'-capture')
            ->post($this->baseUrl().'/v2/checkout/orders/'.rawurlencode($orderId).'/capture', []);

        return match ($response->json('status')) {
            'COMPLETED' => ['status' => 'pagado', 'reference' => $orderId],
            'DECLINED', 'FAILED' => ['status' => 'fallido', 'reference' => $orderId],
            default => ['status' => 'pendiente', 'reference' => $orderId],
        };
    }

    private function token(): string
    {
        $response = Http::asForm()
            ->withBasicAuth((string) config('services.paypal.client_id'), (string) config('services.paypal.secret'))
            ->acceptJson()
            ->post($this->baseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);
        $token = (string) $response->json('access_token');
        if (! $response->successful() || $token === '') {
            throw new RuntimeException('No se pudo autenticar con PayPal.');
        }

        return $token;
    }

    private function baseUrl(): string
    {
        return config('services.paypal.sandbox') ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
    }

    private function returnUrl(Pedido $order, string $result): string
    {
        return URL::signedRoute('pagos.retorno', ['pedido' => $order, 'gateway' => 'paypal', 'result' => $result]);
    }
}
