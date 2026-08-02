<?php

namespace App\Services\Payments;

use App\Models\Pedido;
use RuntimeException;

class PaymentManager
{
    public function __construct(private MercadoPagoGateway $mercadoPago) {}

    public function methods(): array
    {
        return $this->mercadoPago->isEnabled()
            ? ['mercadopago_checkout' => ['label' => 'Mercado Pago', 'instructions' => 'Tarjetas, OXXO y dinero en cuenta.', 'gateway' => 'mercadopago']]
            : [];
    }

    public function create(string $method, Pedido $order): array
    {
        return match ($method) {
            'mercadopago_checkout' => $this->mercadoPago->create($order),
            default => throw new RuntimeException('Pasarela de pago no disponible.'),
        };
    }

    public function verify(string $gateway, Pedido $order, array $parameters): array
    {
        $paymentId = (string) ($parameters['payment_id'] ?? $parameters['collection_id'] ?? '');

        return $gateway === 'mercadopago'
            ? $this->mercadoPago->verify($order, $paymentId)
            : ['status' => 'pendiente', 'reference' => $order->pago_referencia];
    }
}
