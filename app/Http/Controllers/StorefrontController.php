<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->query('q'));
        $products = Producto::query()->where('activo', true)->when($query !== '', fn ($builder) => $builder->where('nombre', 'like', "%{$query}%"))->latest('id')->get();

        return view('store.index', compact('products', 'query'));
    }

    public function show(Producto $producto)
    {
        abort_unless($producto->activo, 404);
        $producto->load('imagenes');

        return view('store.producto', compact('producto'));
    }
}
