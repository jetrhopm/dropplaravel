<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            'nombre_tienda' => 'Mi Tienda', 'porcentaje_ganancia' => '80', 'costo_fijo' => '0', 'moneda' => 'MXN',
            'pago_transferencia' => '1', 'pago_transferencia_datos' => "Banco:\nCuenta CLABE:\nTitular:",
            'pago_oxxo' => '1', 'pago_oxxo_datos' => 'Número de tarjeta para depósito OXXO:',
            'pago_mercadopago' => '0', 'pago_mercadopago_datos' => 'Enlace de pago:', 'pago_efectivo' => '0',
        ] as $key => $value) {
            Setting::updateOrCreate(['clave' => $key], ['valor' => $value]);
        }

        User::updateOrCreate(
            ['email' => 'admin@tienda.com'],
            ['nombre' => 'Administrador', 'password' => 'admin12345'],
        );
    }
}
