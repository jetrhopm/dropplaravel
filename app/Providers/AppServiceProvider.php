<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\Cart;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.store', 'store.*'], function ($view) {
            $view->with('storeName', Setting::value('nombre_tienda', 'Mi Tienda'))
                ->with('currency', Setting::value('moneda', 'MXN'))
                ->with('cartCount', Cart::count())
                ->with('whatsappNumber', preg_replace('/\D/', '', Setting::value('whatsapp_numero')))
                ->with('contactPhone', Setting::value('contacto_telefono'))
                ->with('contactEmail', Setting::value('contacto_email'));
        });
    }
}
