<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private const TEXT_FIELDS = [
        'nombre_tienda', 'porcentaje_ganancia', 'costo_fijo', 'moneda', 'email_notificaciones',
        'whatsapp_numero', 'contacto_telefono', 'contacto_email',
        'pago_transferencia_datos', 'pago_oxxo_datos', 'pago_mercadopago_datos', 'pago_efectivo_datos',
    ];

    public function edit()
    {
        $settings = Setting::query()->pluck('valor', 'clave');

        return view('admin.configuracion', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'nombre_tienda' => ['required', 'string', 'max:100'],
            'porcentaje_ganancia' => ['required', 'numeric', 'min:0', 'max:1000'],
            'costo_fijo' => ['required', 'numeric', 'min:0'],
            'moneda' => ['required', 'string', 'max:10'],
            'email_notificaciones' => ['nullable', 'email'],
            'whatsapp_numero' => ['nullable', 'string', 'max:30'],
            'contacto_telefono' => ['nullable', 'string', 'max:30'],
            'contacto_email' => ['nullable', 'email'],
            'pago_transferencia_datos' => ['nullable', 'string'],
            'pago_oxxo_datos' => ['nullable', 'string'],
            'pago_mercadopago_datos' => ['nullable', 'string'],
            'pago_efectivo_datos' => ['nullable', 'string'],
        ]);

        foreach (self::TEXT_FIELDS as $field) {
            Setting::put($field, (string) ($data[$field] ?? ''));
        }
        foreach (['transferencia', 'oxxo', 'mercadopago', 'efectivo'] as $method) {
            Setting::put('pago_'.$method, $request->boolean('pago_'.$method) ? '1' : '0');
        }
        if ($request->filled('password')) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'confirmed', 'min:10'],
            ]);
            $request->user()->update(['password' => $request->string('password')->toString()]);
        }

        return to_route('admin.configuracion.edit')->with('status', 'Configuración guardada.');
    }
}
