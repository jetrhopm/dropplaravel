<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Setting;
use App\Services\Payments\PaymentManager;
use App\Support\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    private function paymentMethods(PaymentManager $payments): array
    {
        $manual = collect(['transferencia' => 'Transferencia bancaria', 'oxxo' => 'Depósito OXXO', 'mercadopago' => 'Mercado Pago (enlace manual)', 'efectivo' => 'Efectivo'])
            ->filter(fn ($label, $key) => Setting::value('pago_'.$key, '0') === '1')
            ->map(fn ($label, $key) => ['label' => $label, 'instructions' => Setting::value('pago_'.$key.'_datos'), 'gateway' => null]);

        return $manual->union($payments->methods())->all();
    }

    public function create(PaymentManager $payments)
    {
        $lines = Cart::lines();
        if ($lines->isEmpty()) {
            return to_route('carrito.index');
        }
        $methods = $this->paymentMethods($payments);

        return view('store.checkout', compact('lines', 'methods'));
    }

    public function store(Request $request, PaymentManager $payments)
    {
        $lines = Cart::lines();
        if ($lines->isEmpty()) {
            return to_route('carrito.index');
        }
        $methods = $this->paymentMethods($payments);
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:150'], 'telefono' => ['required', 'string', 'max:30'],
            'direccion' => ['required', 'string'], 'ciudad' => ['required', 'string', 'max:100'], 'estado_envio' => ['required', 'string', 'max:100'], 'codigo_postal' => ['required', 'string', 'max:10'], 'metodo_pago' => ['required', 'in:'.implode(',', array_keys($methods))],
        ]);
        $total = $lines->sum('subtotal');
        $order = DB::transaction(function () use ($data, $lines, $methods, $total) {
            $next = ((int) Pedido::max('id')) + 1;
            $order = Pedido::create([
                'numero' => 'DS-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT), 'cliente_nombre' => $data['nombre'], 'cliente_email' => $data['email'], 'cliente_telefono' => $data['telefono'],
                'direccion' => $data['direccion'], 'ciudad' => $data['ciudad'], 'estado_envio' => $data['estado_envio'], 'codigo_postal' => $data['codigo_postal'],
                'metodo_pago' => $methods[$data['metodo_pago']]['label'], 'pago_gateway' => $methods[$data['metodo_pago']]['gateway'], 'total' => $total,
            ]);
            foreach ($lines as $line) {
                $order->items()->create(['producto_id' => $line->product->id, 'nombre_producto' => $line->product->nombre, 'variante' => $line->variant ?: null, 'cantidad' => $line->quantity, 'precio_unitario' => $line->price, 'url_original' => $line->product->url_original, 'plataforma' => $line->product->plataforma]);
            }

            return $order;
        });
        if ($methods[$data['metodo_pago']]['gateway']) {
            try {
                $payment = $payments->create($data['metodo_pago'], $order->load('items'));
            } catch (\RuntimeException $exception) {
                $order->delete();

                return back()->withInput()->withErrors(['metodo_pago' => $exception->getMessage()]);
            }
            $order->update(['pago_referencia' => $payment['reference']]);
            Cart::clear();
            session(['ultimo_pedido' => $order->id]);

            return redirect()->away($payment['url']);
        }

        Cart::clear();
        session(['ultimo_pedido' => $order->id]);

        return to_route('pedido.confirmado');
    }

    public function confirmed(PaymentManager $payments)
    {
        $order = Pedido::findOrFail(session('ultimo_pedido'));
        $instructions = collect($this->paymentMethods($payments))->firstWhere('label', $order->metodo_pago)['instructions'] ?? '';

        return view('store.confirmado', compact('order', 'instructions'));
    }
}
