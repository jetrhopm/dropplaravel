<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ClipWebhookController;
use App\Http\Controllers\PaymentReturnController;
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
Route::get('/pagos/{pedido}/retorno', PaymentReturnController::class)->middleware('signed')->name('pagos.retorno');
Route::post('/webhooks/clip', ClipWebhookController::class)->name('webhooks.clip');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/importar', [ProductImportController::class, 'create'])->name('importar.create');
        Route::post('/importar/vista-previa', [ProductImportController::class, 'preview'])->name('importar.preview');
        Route::post('/importar', [ProductImportController::class, 'store'])->name('importar.store');
        Route::get('/productos', [ProductController::class, 'index'])->name('productos.index');
        Route::get('/productos/nuevo', [ProductController::class, 'create'])->name('productos.create');
        Route::post('/productos', [ProductController::class, 'store'])->name('productos.store');
        Route::get('/productos/{producto}/editar', [ProductController::class, 'edit'])->name('productos.edit');
        Route::put('/productos/{producto}', [ProductController::class, 'update'])->name('productos.update');
        Route::patch('/productos/{producto}/publicacion', [ProductController::class, 'toggle'])->name('productos.toggle');
        Route::delete('/productos/{producto}', [ProductController::class, 'destroy'])->name('productos.destroy');
        Route::patch('/productos/{producto}/imagenes/{imagen}/principal', [ProductController::class, 'makePrimary'])->name('productos.imagenes.principal');
        Route::delete('/productos/{producto}/imagenes/{imagen}', [ProductController::class, 'destroyImage'])->name('productos.imagenes.destroy');
        Route::get('/pedidos', [OrderController::class, 'index'])->name('pedidos.index');
        Route::get('/pedidos/{pedido}', [OrderController::class, 'show'])->name('pedidos.show');
        Route::put('/pedidos/{pedido}', [OrderController::class, 'update'])->name('pedidos.update');
        Route::get('/configuracion', [SettingsController::class, 'edit'])->name('configuracion.edit');
        Route::put('/configuracion', [SettingsController::class, 'update'])->name('configuracion.update');
    });
});
