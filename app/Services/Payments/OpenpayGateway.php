<?php

namespace App\Services\Payments;

use App\Models\Pedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class OpenpayGateway
{
    public function isEnabled(): bool
    {
        return (bool) config('services.openpay.enabled')
            && (string) config('services.openpay.merchant_id') !== ''
            && (string) config('services.openpay.private_key') !== '';
    }

    public function create(Pedido $order): array
    {
        $response = $this->request()->post($this->baseUrl().'/charges', [
            'method' => 'card',
            'amount' => round((float) $order->total, 2),
            'currency' => config('app.currency', 'MXN'),
            'description' => str('Pedido '.$order->numero.' - '.config('app.name', 'Tienda'))->limit(250, ''),
            'order_id' => $order->numero,
            'confirm' => false,
            'send_email' => false,
            'redirect_url' => URL::signedRoute('pagos.retorno', ['pedido' => $order, 'gateway' => 'openpay']),
            'customer' => ['name' => $order->cliente_nombre, 'email' => $order->cliente_email, 'phone_number' => $order->cliente_telefono],
        ]);
        $url = $response->json('payment_method.url');
        if (! $response->successful() || ! $url) {
            throw new RuntimeException('No se pudo iniciar el pago con Openpay.');
        }

        return ['url' => $url, 'reference' => (string) $response->json('id')];
    }

    public function verify(Pedido $order, array $parameters): array
    {
        $chargeId = (string) ($parameters['id'] ?? $order->pago_referencia);
        if ($chargeId === '') {
            return ['status' => 'pendiente', 'reference' => ''];
        }
        $response = $this->request()->get($this->baseUrl().'/charges/'.rawurlencode($chargeId));

        return match ($response->json('status')) {
            'completed' => ['status' => 'pagado', 'reference' => $chargeId],
            'failed', 'cancelled' => ['status' => 'fallido', 'reference' => $chargeId],
            default => ['status' => 'pendiente', 'reference' => $chargeId],
        };
    }

    private function request()
    {
        return Http::withBasicAuth((string) config('services.openpay.private_key'), '')->acceptJson();
    }

    private function baseUrl(): string
    {
        $host = config('services.openpay.sandbox') ? 'https://sandbox-api.openpay.mx' : 'https://api.openpay.mx';

        return $host.'/v1/'.config('services.openpay.merchant_id');
    }
}
