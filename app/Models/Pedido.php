<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $table = 'pedidos';

    const CREATED_AT = 'creado_en';

    const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['total' => 'decimal:2'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class, 'pedido_id');
    }
}
