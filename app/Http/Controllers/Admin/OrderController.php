<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Setting;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public const STATUSES = [
        'nuevo' => ['Nuevo', 'primary'],
        'pendiente_compra' => ['Pendiente de compra', 'warning'],
        'comprado_proveedor' => ['Comprado al proveedor', 'info'],
        'enviado' => ['Enviado', 'secondary'],
        'entregado' => ['Entregado', 'success'],
        'cancelado' => ['Cancelado', 'danger'],
    ];

    public function index(Request $request)
    {
        $status = (string) $request->query('estado');
        $orders = Pedido::query()->when(array_key_exists($status, self::STATUSES), fn ($query) => $query->where('estado', $status))->latest('id')->get();

        return view('admin.pedidos.index', compact('orders', 'status'));
    }

    public function show(Pedido $pedido)
    {
        $pedido->load('items');
        $whatsappNumber = preg_replace('/\D/', '', Setting::value('whatsapp_numero'));
        $whatsappLink = $whatsappNumber ? 'https://wa.me/'.$whatsappNumber.'?text='.rawurlencode($this->whatsappMessage($pedido)) : null;

        return view('admin.pedidos.show', compact('pedido', 'whatsappLink'));
    }

    public function update(Request $request, Pedido $pedido)
    {
        $data = $request->validate(['estado' => ['required', 'in:'.implode(',', array_keys(self::STATUSES))], 'notas' => ['nullable', 'string']]);
        $pedido->update($data);

        return to_route('admin.pedidos.show', $pedido)->with('status', 'Pedido actualizado.');
    }

    private function whatsappMessage(Pedido $pedido): string
    {
        return collect([$pedido->numero, 'Cliente: '.$pedido->cliente_nombre, 'Total: $'.number_format((float) $pedido->total, 2)])
            ->merge($pedido->items->map(fn ($item) => "- {$item->cantidad}x {$item->nombre_producto} | {$item->url_original}"))
            ->push('Envío: '.$pedido->direccion.', '.$pedido->ciudad.', '.$pedido->estado_envio.', CP '.$pedido->codigo_postal)
            ->implode("\n");
    }
}
