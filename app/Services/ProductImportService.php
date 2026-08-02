<?php

namespace App\Services;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProductImportService
{
    private const MAX_REDIRECTS = 3;

    private const MAX_HTML_BYTES = 2_000_000;

    private const MAX_IMAGE_BYTES = 5_000_000;

    public function preview(string $url): array
    {
        $url = trim($url);
        $result = ['ok' => false, 'mensaje' => '', 'nombre' => '', 'descripcion' => '', 'precio' => 0.0, 'imagenes' => [], 'variantes' => [], 'plataforma' => $this->platform($url), 'url' => $url];

        try {
            $download = $this->download($url, 'text/html');
        } catch (RuntimeException $exception) {
            $result['mensaje'] = $exception->getMessage();

            return $result;
        }

        if (strlen($download['body']) > self::MAX_HTML_BYTES) {
            $result['mensaje'] = 'La pagina del proveedor es demasiado grande para importarse.';

            return $result;
        }

        $html = $download['body'];
        $result['url'] = $download['url'];
        $result['plataforma'] = $this->platform($download['url']);
        $this->extractJsonLd($html, $result);
        if ($result['plataforma'] === 'aliexpress') {
            $this->extractAliExpress($html, $result);
        }
        if ($result['plataforma'] === 'temu') {
            $this->extractTemu($html, $result);
        }
        $this->extractMeta($html, $result);

        if ($result['nombre'] === '') {
            $result['nombre'] = $this->text($this->dom($html)?->getElementsByTagName('title')->item(0)?->textContent ?? '');
        }

        $result['imagenes'] = collect($result['imagenes'])
            ->filter(fn ($image) => filter_var($image, FILTER_VALIDATE_URL) && $this->isSafeUrl($image))
            ->unique()->take(10)->values()->all();

        if ($result['nombre'] !== '' || $result['imagenes'] !== []) {
            $result['ok'] = true;
            $missing = [];
            if ($result['nombre'] === '') {
                $missing[] = 'nombre';
            }
            if ($result['precio'] <= 0) {
                $missing[] = 'precio';
            }
            if ($result['imagenes'] === []) {
                $missing[] = 'imagenes';
            }
            if ($result['descripcion'] === '') {
                $missing[] = 'descripcion';
            }
            $result['mensaje'] = $missing === [] ? 'Datos extraidos. Revisa la vista previa antes de guardar.' : 'Extraccion parcial. Completa manualmente: '.implode(', ', $missing).'.';
        } else {
            $result['mensaje'] = 'El proveedor no permitio extraer datos. Puedes capturarlos manualmente desde Productos.';
        }

        return $result;
    }

    public function storeImage(string $url): ?string
    {
        try {
            $download = $this->download($url, 'image/');
        } catch (RuntimeException) {
            return null;
        }

        $body = $download['body'];
        if ($body === '' || strlen($body) > self::MAX_IMAGE_BYTES) {
            return null;
        }
        $info = @getimagesizefromstring($body);
        $extension = $info === false ? null : image_type_to_extension($info[2], false);
        if (! in_array($extension, ['jpeg', 'png', 'gif', 'webp'], true)) {
            return null;
        }

        $filename = sprintf('prod_%s_%s.%s', now()->format('Ymd_His'), str()->random(12), $extension === 'jpeg' ? 'jpg' : $extension);
        Storage::disk('public')->put('productos/'.$filename, $body);

        return $filename;
    }

    private function download(string $url, string $expectedContentType): array
    {
        for ($attempt = 0; $attempt <= self::MAX_REDIRECTS; $attempt++) {
            $this->assertSafeUrl($url);
            $response = Http::accept('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')
                ->withHeaders([
                    'Accept-Language' => 'es-MX,es;q=0.9,en;q=0.8',
                    'User-Agent' => 'Mozilla/5.0 (compatible; DroppImporter/1.0)',
                ])
                ->timeout(20)->connectTimeout(10)->withoutRedirecting()->get($url);

            if ($response->redirect()) {
                $location = $response->header('Location');
                if (! $location) {
                    throw new RuntimeException('El proveedor devolvio una redireccion sin destino.');
                }
                $url = (string) UriResolver::resolve(new Uri($url), new Uri($location));

                continue;
            }
            if (! $response->successful()) {
                throw new RuntimeException('No se pudo descargar la pagina del proveedor (HTTP '.$response->status().').');
            }
            if (! str_starts_with(strtolower((string) $response->header('Content-Type')), $expectedContentType)) {
                throw new RuntimeException('El proveedor devolvio un tipo de contenido no compatible.');
            }

            return ['url' => $url, 'body' => $response->body()];
        }

        throw new RuntimeException('El proveedor redirigio demasiadas veces.');
    }

    private function assertSafeUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';
        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('La URL debe usar HTTP o HTTPS y un dominio valido.');
        }
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if ($addresses === []) {
            throw new RuntimeException('No se pudo resolver el dominio del proveedor.');
        }
        foreach ($addresses as $address) {
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('La URL apunta a una red no permitida.');
            }
        }
    }

    private function isSafeUrl(string $url): bool
    {
        try {
            $this->assertSafeUrl($url);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    private function platform(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return str_contains($host, 'aliexpress') ? 'aliexpress' : (str_contains($host, 'temu') ? 'temu' : 'otra');
    }

    private function dom(string $html): ?\DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $dom : null;
    }

    private function extractJsonLd(string $html, array &$result): void
    {
        $dom = $this->dom($html);
        if (! $dom) {
            return;
        }
        foreach ((new \DOMXPath($dom))->query('//script[@type="application/ld+json"]') as $node) {
            $data = json_decode($node->textContent, true);
            if (! is_array($data)) {
                continue;
            }
            $items = $data['@graph'] ?? (array_is_list($data) ? $data : [$data]);
            foreach ($items as $product) {
                if (! in_array('Product', (array) ($product['@type'] ?? []), true)) {
                    continue;
                }
                $result['nombre'] = $result['nombre'] ?: $this->text($product['name'] ?? '');
                $result['descripcion'] = $result['descripcion'] ?: $this->text($product['description'] ?? '');
                foreach ((array) ($product['image'] ?? []) as $image) {
                    $result['imagenes'][] = $this->normaliseImageUrl(is_array($image) ? ($image['url'] ?? '') : $image);
                }
                $offers = $product['offers'] ?? [];
                $offers = is_array($offers) && array_is_list($offers) ? $offers : [$offers];
                foreach ($offers as $offer) {
                    $price = is_array($offer) ? ($offer['price'] ?? $offer['lowPrice'] ?? 0) : 0;
                    if ($result['precio'] <= 0 && (float) $price > 0) {
                        $result['precio'] = (float) $price;
                    }
                }
            }
        }
    }

    private function extractMeta(string $html, array &$result): void
    {
        $dom = $this->dom($html);
        if (! $dom) {
            return;
        }
        $meta = [];
        foreach ((new \DOMXPath($dom))->query('//meta[@content]') as $node) {
            $key = strtolower($node->getAttribute('property') ?: $node->getAttribute('name'));
            if ($key !== '' && ! isset($meta[$key])) {
                $meta[$key] = $node->getAttribute('content');
            }
        }
        $result['nombre'] = $result['nombre'] ?: $this->text($meta['og:title'] ?? $meta['twitter:title'] ?? '');
        $result['descripcion'] = $result['descripcion'] ?: $this->text($meta['og:description'] ?? $meta['description'] ?? '');
        foreach (['og:price:amount', 'product:price:amount', 'twitter:data1'] as $property) {
            $price = preg_replace('/[^\d.]/', '', $meta[$property] ?? '');
            if ($result['precio'] <= 0 && (float) $price > 0) {
                $result['precio'] = (float) $price;
            }
        }
        foreach (['og:image', 'og:image:secure_url', 'twitter:image'] as $property) {
            if (isset($meta[$property])) {
                $result['imagenes'][] = $this->normaliseImageUrl($meta[$property]);
            }
        }
    }

    private function extractAliExpress(string $html, array &$result): void
    {
        if ($result['nombre'] === '' && preg_match('/"subject"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/', $html, $matches)) {
            $result['nombre'] = $this->text(json_decode('"'.$matches[1].'"') ?: '');
        }
        if (preg_match('/"image(?:Path|Url)List"\s*:\s*\[(.*?)\]/s', $html, $matches) && preg_match_all('/"((?:https?:)?\\/\\/[^"\\s]+)"/', $matches[1], $images)) {
            foreach ($images[1] as $image) {
                $result['imagenes'][] = $this->normaliseImageUrl($image);
            }
        }
        if ($result['precio'] <= 0 && preg_match('/"(?:actMinPrice|minActivityAmount|minAmount|minPrice)"\s*:\s*\{[^}]*"value"\s*:\s*([\d.]+)/', $html, $matches)) {
            $result['precio'] = (float) $matches[1];
        }
    }

    private function extractTemu(string $html, array &$result): void
    {
        if ($result['nombre'] === '' && preg_match('/"(?:goodsName|title)"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/', $html, $matches)) {
            $result['nombre'] = $this->text(json_decode('"'.$matches[1].'"') ?: '');
        }
        if ($result['precio'] <= 0 && preg_match('/"(?:minOnSalePrice|salePrice|price)"\s*:\s*([\d]+)/', $html, $matches)) {
            $price = (float) $matches[1];
            $result['precio'] = $price > 1000 ? $price / 100 : $price;
        }
    }

    private function normaliseImageUrl(string $url): string
    {
        $url = trim(stripslashes($url));
        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }

        return (string) preg_replace('/_(?:\d+x\d+|50x50|220x220|640x640)[a-z]*\.(jpg|jpeg|png|webp)(?:_\.webp)?$/i', '.$1', $url);
    }

    private function text(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
