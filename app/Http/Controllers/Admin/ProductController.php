<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\ProductoImagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $products = Producto::query()
            ->when($search !== '', fn ($query) => $query->where('nombre', 'like', "%{$search}%"))
            ->latest('id')
            ->get();

        return view('admin.productos.index', compact('products', 'search'));
    }

    public function create()
    {
        return view('admin.productos.form', ['product' => new Producto, 'variantsText' => '']);
    }

    public function store(Request $request)
    {
        $product = Producto::create($this->validatedData($request));
        $this->storeImages($request, $product);

        return to_route('admin.productos.edit', $product)->with('status', 'Producto creado.');
    }

    public function edit(Producto $producto)
    {
        $producto->load('imagenes');
        $variantsText = collect($producto->variantes ?? [])
            ->map(fn ($variant) => $variant['nombre'].': '.implode(', ', $variant['opciones']))
            ->implode("\n");

        return view('admin.productos.form', compact('product', 'variantsText'));
    }

    public function update(Request $request, Producto $producto)
    {
        $product->update($this->validatedData($request));
        $this->storeImages($request, $product);

        return to_route('admin.productos.edit', $product)->with('status', 'Cambios guardados.');
    }

    public function toggle(Producto $producto)
    {
        $producto->update(['activo' => ! $producto->activo]);

        return to_route('admin.productos.index')->with('status', 'Estado de publicación actualizado.');
    }

    public function destroy(Producto $producto)
    {
        foreach ($producto->imagenes as $image) {
            Storage::disk('public')->delete('productos/'.$image->archivo);
        }
        $productName = $producto->nombre;
        $producto->delete();

        return to_route('admin.productos.index')->with('status', "Producto {$productName} eliminado.");
    }

    public function makePrimary(Producto $producto, ProductoImagen $imagen)
    {
        abort_unless($imagen->producto_id === $producto->id, 404);
        $producto->update(['imagen_principal' => $imagen->archivo]);

        return to_route('admin.productos.edit', $producto)->with('status', 'Imagen principal actualizada.');
    }

    public function destroyImage(Producto $producto, ProductoImagen $imagen)
    {
        abort_unless($imagen->producto_id === $producto->id, 404);
        Storage::disk('public')->delete('productos/'.$imagen->archivo);
        $imagen->delete();
        if ($producto->imagen_principal === $imagen->archivo) {
            $producto->update(['imagen_principal' => $producto->imagenes()->value('archivo')]);
        }

        return to_route('admin.productos.edit', $producto)->with('status', 'Imagen eliminada.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'plataforma' => ['required', 'in:aliexpress,temu,otra'],
            'url_original' => ['required', 'url'],
            'precio_original' => ['required', 'numeric', 'min:0'],
            'precio_manual' => ['nullable', 'numeric', 'min:0'],
            'variantes' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
            'imagenes.*' => ['image', 'max:5120'],
        ]);
        $data['activo'] = $request->boolean('activo');
        $data['variantes'] = $this->parseVariants($data['variantes'] ?? '');
        unset($data['imagenes']);

        return $data;
    }

    private function parseVariants(string $text): ?array
    {
        $variants = collect(preg_split('/\r?\n/', trim($text)))
            ->filter(fn ($line) => str_contains($line, ':'))
            ->map(function ($line) {
                [$name, $options] = explode(':', $line, 2);

                return ['nombre' => trim($name), 'opciones' => collect(explode(',', $options))->map(fn ($option) => trim($option))->filter()->values()->all()];
            })
            ->filter(fn ($variant) => $variant['nombre'] !== '' && $variant['opciones'] !== [])
            ->values()
            ->all();

        return $variants ?: null;
    }

    private function storeImages(Request $request, Producto $product): void
    {
        $order = (int) $product->imagenes()->max('orden');
        foreach ($request->file('imagenes', []) as $image) {
            $path = $image->store('productos', 'public');
            $filename = basename($path);
            $product->imagenes()->create(['archivo' => $filename, 'orden' => ++$order]);
            if (! $product->imagen_principal) {
                $product->update(['imagen_principal' => $filename]);
            }
        }
    }
}
