<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('tienda.index');
Route::get('/producto/{producto}', [StorefrontController::class, 'show'])->name('producto.show');
Route::get('/carrito', [CartController::class, 'index'])->name('carrito.index');
Route::post('/carrito', [CartController::class, 'store'])->name('carrito.store');
Route::patch('/carrito', [CartController::class, 'update'])->name('carrito.update');
Route::delete('/carrito', [CartController::class, 'destroy'])->name('carrito.destroy');
Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/pedido-confirmado', [CheckoutController::class, 'confirmed'])->name('pedido.confirmado');
