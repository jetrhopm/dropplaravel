<?php

namespace App\Support;

use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class Cart
{
    public static function add(Producto $producto, array $variants, int $quantity): void
    {
        $variant = collect($variants)->filter()->map(fn ($value, $name) => "$name: $value")->implode(' | ');
        $key = $producto->id.'|'.md5($variant);
        $cart = Session::get('cart', []);
        $cart[$key] = [
            'producto_id' => $producto->id,
            'variante' => $variant,
            'cantidad' => min(99, ($cart[$key]['cantidad'] ?? 0) + $quantity),
        ];
        Session::put('cart', $cart);
    }

    public static function update(array $quantities): void
    {
        $cart = Session::get('cart', []);
        foreach ($quantities as $key => $quantity) {
            if (! isset($cart[$key])) {
                continue;
            }
            $quantity = (int) $quantity;
            if ($quantity <= 0) {
                unset($cart[$key]);
            } else {
                $cart[$key]['cantidad'] = min(99, $quantity);
            }
        }
        Session::put('cart', $cart);
    }

    public static function remove(string $key): void
    {
        $cart = Session::get('cart', []);
        unset($cart[$key]);
        Session::put('cart', $cart);
    }

    public static function clear(): void
    {
        Session::forget('cart');
    }

    public static function count(): int
    {
        return collect(Session::get('cart', []))->sum('cantidad');
    }

    public static function lines(): Collection
    {
        $cart = Session::get('cart', []);
        $products = Producto::query()->where('activo', true)->whereIn('id', collect($cart)->pluck('producto_id'))->get()->keyBy('id');
        $lines = collect();
        foreach ($cart as $key => $item) {
            $product = $products->get($item['producto_id']);
            if (! $product) {
                continue;
            }
            $price = $product->precioFinal();
            $lines->push((object) ['key' => $key, 'product' => $product, 'variant' => $item['variante'], 'quantity' => $item['cantidad'], 'price' => $price, 'subtotal' => $price * $item['cantidad']]);
        }

        return $lines;
    }
}
