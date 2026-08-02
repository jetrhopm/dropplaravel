<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion', function (Blueprint $table) {
            $table->string('clave', 50)->primary();
            $table->text('valor')->nullable();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('plataforma', 30)->default('otra');
            $table->text('url_original');
            $table->decimal('precio_original', 10, 2)->default(0);
            $table->decimal('precio_manual', 10, 2)->nullable();
            $table->string('imagen_principal')->nullable();
            $table->json('variantes')->nullable();
            $table->boolean('activo')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('archivo');
            $table->unsignedInteger('orden')->default(0);
        });

        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->string('cliente_nombre', 150);
            $table->string('cliente_email', 150);
            $table->string('cliente_telefono', 30);
            $table->text('direccion');
            $table->string('ciudad', 100);
            $table->string('estado_envio', 100);
            $table->string('codigo_postal', 10);
            $table->string('metodo_pago', 50);
            $table->string('pago_gateway', 30)->nullable();
            $table->enum('pago_estado', ['pendiente', 'pagado', 'fallido'])->default('pendiente');
            $table->string('pago_referencia', 120)->nullable();
            $table->string('clip_receipt_no', 120)->nullable();
            $table->decimal('total', 10, 2);
            $table->enum('estado', ['nuevo', 'pendiente_compra', 'comprado_proveedor', 'enviado', 'entregado', 'cancelado'])->default('nuevo');
            $table->text('notas')->nullable();
            $table->timestamp('creado_en')->useCurrent();
        });

        Schema::create('pedido_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('nombre_producto');
            $table->string('variante')->nullable();
            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('precio_unitario', 10, 2);
            $table->text('url_original');
            $table->string('plataforma', 30)->default('otra');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_items');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('producto_imagenes');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('configuracion');
    }
};
