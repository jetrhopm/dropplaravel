<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $table = 'productos';

    const CREATED_AT = 'creado_en';

    const UPDATED_AT = 'actualizado_en';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'variantes' => 'array', 'precio_original' => 'decimal:2', 'precio_manual' => 'decimal:2'];
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ProductoImagen::class, 'producto_id')->orderBy('orden');
    }

    public function precioFinal(): float
    {
        if ((float) $this->precio_manual > 0) {
            return (float) $this->precio_manual;
        }

        return round((float) $this->precio_original * (1 + ((float) Setting::value('porcentaje_ganancia', '80') / 100)) + (float) Setting::value('costo_fijo', '0'), 2);
    }

    public function imageUrl(): string
    {
        return $this->imagen_principal ? asset('storage/productos/'.$this->imagen_principal) : asset('images/placeholder.svg');
    }
}
