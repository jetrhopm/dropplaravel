<?php

namespace App\Services\Payments;

use App\Models\Pedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class ClipGateway
{
    public function isEnabled(): bool
    {
        return (bool) config('services.clip.enabled') && (string) config('services.clip.public_key') !== '' && (string) config('services.clip.secret_key') !== '' && (string) config('services.clip.webhook_token') !== '';
    }

    public function create(Pedido $order): array
    {
        $response = Http::withBasicAuth((string) config('services.clip.public_key'), (string) config('services.clip.secret_key'))
            ->acceptJson()
            ->post('https://api.payclip.com/v2/checkout', [
                'amount' => (int) round((float) $order->total),
                'currency' => config('app.currency', 'MXN'),
                'purchase_description' => str('Pedido '.$order->numero.' - '.config('app.name', 'Tienda'))->limit(127, ''),
                'redirection_url' => [
                    'success' => URL::signedRoute('pagos.retorno', ['pedido' => $order, 'gateway' => 'clip', 'result' => 'success']),
                    'error' => URL::signedRoute('pagos.retorno', ['pedido' => $order, 'gateway' => 'clip', 'result' => 'error']),
                    'default' => URL::signedRoute('pagos.retorno', ['pedido' => $order, 'gateway' => 'clip', 'result' => 'error']),
                ],
                'metadata' => ['external_reference' => $order->numero],
                'webhook_url' => route('webhooks.clip').'?token='.rawurlencode((string) config('services.clip.webhook_token')),
            ]);
        $url = $response->json('payment_request_url');
        $reference = $response->json('payment_request_id');
        if (! $response->successful() || ! $url || ! $reference) {
            throw new RuntimeException('No se pudo iniciar el pago con Clip.');
        }

        return ['url' => $url, 'reference' => (string) $reference];
    }
}
