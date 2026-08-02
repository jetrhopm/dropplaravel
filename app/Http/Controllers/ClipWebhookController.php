<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;

class ClipWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(hash_equals((string) config('services.clip.webhook_token'), (string) $request->query('token')), 403);
        $data = $request->json()->all();
        $number = $data['merch_inv_id'] ?? $data['payment_detail']['merch_inv_id'] ?? $data['payment_request_detail']['merch_inv_id'] ?? $data['metadata']['external_reference'] ?? null;
        $status = strtoupper((string) ($data['status'] ?? $data['event_type'] ?? 'PING'));
        if (! $number || $status === 'PING' || in_array($status, ['INSERT', 'UPDATE'], true)) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $paymentStatus = match ($status) {
            'PAID', 'REQUEST_COMPLETED' => 'pagado',
            'DECLINED', 'CANCELLED', 'EXPIRED', 'REQUEST_DECLINED', 'REQUEST_CANCELLED', 'REQUEST_EXPIRED' => 'fallido',
            default => null,
        };
        if (! $paymentStatus) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }
        $order = Pedido::query()->where('numero', $number)->where('pago_gateway', 'clip')->where('pago_estado', 'pendiente')->first();
        if (! $order) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }
        $order->update(['pago_estado' => $paymentStatus, 'pago_referencia' => $order->pago_referencia ?: ($data['id'] ?? null), 'clip_receipt_no' => $data['receipt_no'] ?? $data['payment_detail']['receipt_no'] ?? null]);

        return response()->json(['ok' => true]);
    }
}
