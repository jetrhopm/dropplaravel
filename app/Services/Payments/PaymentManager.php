<?php

namespace App\Services\Payments;

use App\Models\Pedido;
use RuntimeException;

class PaymentManager
{
    public function __construct(private MercadoPagoGateway $mercadoPago, private PayPalGateway $payPal) {}

    public function methods(): array
    {
        return array_filter([
            'mercadopago_checkout' => $this->mercadoPago->isEnabled() ? ['label' => 'Mercado Pago', 'instructions' => 'Tarjetas, OXXO y dinero en cuenta.', 'gateway' => 'mercadopago'] : null,
            'paypal_checkout' => $this->payPal->isEnabled() ? ['label' => 'PayPal', 'instructions' => 'Cuenta PayPal o tarjeta.', 'gateway' => 'paypal'] : null,
        ]);
    }

    public function create(string $method, Pedido $order): array
    {
        return match ($method) {
            'mercadopago_checkout' => $this->mercadoPago->create($order),
            'paypal_checkout' => $this->payPal->create($order),
            default => throw new RuntimeException('Pasarela de pago no disponible.'),
        };
    }

    public function verify(string $gateway, Pedido $order, array $parameters): array
    {
        return match ($gateway) {
            'mercadopago' => $this->mercadoPago->verify($order, (string) ($parameters['payment_id'] ?? $parameters['collection_id'] ?? '')),
            'paypal' => $this->payPal->verify($order, $parameters),
            default => ['status' => 'pendiente', 'reference' => $order->pago_referencia],
        };
    }
}
