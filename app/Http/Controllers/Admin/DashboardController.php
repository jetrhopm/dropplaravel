<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Producto;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $metrics = [
            'productos' => Producto::count(),
            'activos' => Producto::where('activo', true)->count(),
            'nuevos' => Pedido::where('estado', 'nuevo')->count(),
            'ventas' => Pedido::where('estado', '!=', 'cancelado')->sum('total'),
        ];
        $orders = Pedido::latest('id')->limit(8)->get();

        return view('admin.dashboard', compact('metrics', 'orders'));
    }
}
