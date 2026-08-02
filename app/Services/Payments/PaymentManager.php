<?php

namespace App\Services\Payments;

use App\Models\Pedido;
use RuntimeException;

class PaymentManager
{
    public function __construct(private ClipGateway $clip, private MercadoPagoGateway $mercadoPago, private OpenpayGateway $openpay, private PayPalGateway $payPal) {}

    public function methods(): array
    {
        return array_filter([
            'clip_checkout' => $this->clip->isEnabled() ? ['label' => 'Clip', 'instructions' => 'Link de pago con tarjeta.', 'gateway' => 'clip'] : null,
            'mercadopago_checkout' => $this->mercadoPago->isEnabled() ? ['label' => 'Mercado Pago', 'instructions' => 'Tarjetas, OXXO y dinero en cuenta.', 'gateway' => 'mercadopago'] : null,
            'paypal_checkout' => $this->payPal->isEnabled() ? ['label' => 'PayPal', 'instructions' => 'Cuenta PayPal o tarjeta.', 'gateway' => 'paypal'] : null,
            'openpay_checkout' => $this->openpay->isEnabled() ? ['label' => 'Openpay', 'instructions' => 'Tarjeta de credito o debito.', 'gateway' => 'openpay'] : null,
        ]);
    }

    public function create(string $method, Pedido $order): array
    {
        return match ($method) {
            'clip_checkout' => $this->clip->create($order),
            'mercadopago_checkout' => $this->mercadoPago->create($order),
            'paypal_checkout' => $this->payPal->create($order),
            'openpay_checkout' => $this->openpay->create($order),
            default => throw new RuntimeException('Pasarela de pago no disponible.'),
        };
    }

    public function verify(string $gateway, Pedido $order, array $parameters): array
    {
        return match ($gateway) {
            'mercadopago' => $this->mercadoPago->verify($order, (string) ($parameters['payment_id'] ?? $parameters['collection_id'] ?? '')),
            'paypal' => $this->payPal->verify($order, $parameters),
            'openpay' => $this->openpay->verify($order, $parameters),
            default => ['status' => 'pendiente', 'reference' => $order->pago_referencia],
        };
    }
}
