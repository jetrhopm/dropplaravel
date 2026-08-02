<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Support\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $lines = Cart::lines();

        return view('store.carrito', compact('lines'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['producto_id' => ['required', 'integer'], 'cantidad' => ['required', 'integer', 'min:1', 'max:99'], 'variante' => ['array']]);
        $product = Producto::query()->where('activo', true)->findOrFail($data['producto_id']);
        Cart::add($product, $data['variante'] ?? [], $data['cantidad']);

        return to_route('carrito.index')->with('status', 'Producto agregado al carrito.');
    }

    public function update(Request $request)
    {
        Cart::update($request->validate(['cantidad' => ['required', 'array']])['cantidad']);

        return to_route('carrito.index');
    }

    public function destroy(Request $request)
    {
        Cart::remove($request->validate(['clave' => ['required', 'string']])['clave']);

        return to_route('carrito.index');
    }
}
