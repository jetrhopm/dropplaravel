<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Services\ProductImportService;
use Illuminate\Http\Request;

class ProductImportController extends Controller
{
    public function create()
    {
        return view('admin.importar.index');
    }

    public function preview(Request $request, ProductImportService $importer)
    {
        $data = $request->validate(['url' => ['required', 'url', 'max:2048']]);

        return view('admin.importar.index', ['extraction' => $importer->preview($data['url'])]);
    }

    public function store(Request $request, ProductImportService $importer)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'plataforma' => ['required', 'in:aliexpress,temu,otra'],
            'url_original' => ['required', 'url', 'max:2048'],
            'precio_original' => ['required', 'numeric', 'min:0'],
            'precio_manual' => ['nullable', 'numeric', 'min:0'],
            'variantes' => ['nullable', 'string'],
            'imagenes' => ['nullable', 'array', 'max:6'],
            'imagenes.*' => ['url', 'max:2048'],
            'activo' => ['nullable', 'boolean'],
        ]);
        $data['activo'] = $request->boolean('activo');
        $data['variantes'] = $this->parseVariants($data['variantes'] ?? '');
        unset($data['imagenes']);

        $product = Producto::create($data);
        $downloaded = 0;
        foreach ($request->input('imagenes', []) as $order => $url) {
            $filename = $importer->storeImage($url);
            if (! $filename) {
                continue;
            }
            $product->imagenes()->create(['archivo' => $filename, 'orden' => $order]);
            if (! $product->imagen_principal) {
                $product->update(['imagen_principal' => $filename]);
            }
            $downloaded++;
        }

        return to_route('admin.productos.edit', $product)->with('status', "Producto importado. Imagenes descargadas: {$downloaded}.");
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
}
