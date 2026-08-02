<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;

class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, Pedido $pedido, PaymentManager $payments)
    {
        if ($pedido->pago_estado === 'pendiente') {
            $result = $payments->verify((string) $request->query('gateway'), $pedido, $request->query());
            $pedido->update(['pago_estado' => $result['status'], 'pago_referencia' => $result['reference'] ?: $pedido->pago_referencia]);
        }

        session(['ultimo_pedido' => $pedido->id]);

        return to_route('pedido.confirmado');
    }
}
