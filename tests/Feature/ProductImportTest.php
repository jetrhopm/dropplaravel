<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_and_save_an_imported_product(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        Http::fake([
            'https://example.com/producto' => Http::response('<html><head><script type="application/ld+json">{"@context":"https://schema.org","@type":"Product","name":"Producto importado","description":"Descripcion importada","image":"https://example.com/imagen.png","offers":{"@type":"Offer","price":"199.50"}}</script></head></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']),
            'https://example.com/imagen.png' => Http::response($image, 200, ['Content-Type' => 'image/png']),
        ]);

        $this->actingAs($admin)->post(route('admin.importar.preview'), ['url' => 'https://example.com/producto'])->assertOk()->assertSee('Producto importado')->assertSee('199.50');
        $this->post(route('admin.importar.store'), ['nombre' => 'Producto importado', 'descripcion' => 'Descripcion importada', 'plataforma' => 'otra', 'url_original' => 'https://example.com/producto', 'precio_original' => 199.50, 'variantes' => 'Color: Negro, Blanco', 'imagenes' => ['https://example.com/imagen.png'], 'activo' => '1'])->assertRedirect();

        $product = Producto::firstOrFail();
        $this->assertTrue($product->activo);
        $this->assertSame('Negro', $product->variantes[0]['opciones'][0]);
        Storage::disk('public')->assertExists('productos/'.$product->imagen_principal);
    }

    public function test_importer_rejects_private_network_urls_before_requesting_them(): void
    {
        $admin = User::factory()->create();
        Http::fake();

        $this->actingAs($admin)
            ->post(route('admin.importar.preview'), ['url' => 'http://127.0.0.1/producto'])
            ->assertOk()
            ->assertSee('red no permitida');

        Http::assertNothingSent();
    }
}
