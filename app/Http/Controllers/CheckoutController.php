<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Setting;
use App\Support\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    private function paymentMethods(): array
    {
        return collect(['transferencia' => 'Transferencia bancaria', 'oxxo' => 'Depósito OXXO', 'mercadopago' => 'Mercado Pago (enlace manual)', 'efectivo' => 'Efectivo'])
            ->filter(fn ($label, $key) => Setting::value('pago_'.$key, '0') === '1')
            ->map(fn ($label, $key) => ['label' => $label, 'instructions' => Setting::value('pago_'.$key.'_datos')])->all();
    }

    public function create()
    {
        $lines = Cart::lines();
        if ($lines->isEmpty()) {
            return to_route('carrito.index');
        }
        $methods = $this->paymentMethods();

        return view('store.checkout', compact('lines', 'methods'));
    }

    public function store(Request $request)
    {
        $lines = Cart::lines();
        if ($lines->isEmpty()) {
            return to_route('carrito.index');
        }
        $methods = $this->paymentMethods();
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
                'metodo_pago' => $methods[$data['metodo_pago']]['label'], 'total' => $total,
            ]);
            foreach ($lines as $line) {
                $order->items()->create(['producto_id' => $line->product->id, 'nombre_producto' => $line->product->nombre, 'variante' => $line->variant ?: null, 'cantidad' => $line->quantity, 'precio_unitario' => $line->price, 'url_original' => $line->product->url_original, 'plataforma' => $line->product->plataforma]);
            }

            return $order;
        });
        Cart::clear();
        session(['ultimo_pedido' => $order->id]);

        return to_route('pedido.confirmado');
    }

    public function confirmed()
    {
        $order = Pedido::findOrFail(session('ultimo_pedido'));
        $instructions = collect($this->paymentMethods())->firstWhere('label', $order->metodo_pago)['instructions'] ?? '';

        return view('store.confirmado', compact('order', 'instructions'));
    }
}
