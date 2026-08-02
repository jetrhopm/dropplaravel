<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $storeName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/tienda.css') }}" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-tienda sticky-top py-2"><div class="container"><a class="navbar-brand marca" href="{{ route('tienda.index') }}"><i class="bi bi-bag-heart-fill me-1"></i>{{ $storeName }}</a><div class="d-flex align-items-center gap-2"><form class="d-none d-md-flex" action="{{ route('tienda.index') }}" method="get"><div class="input-group"><input type="search" name="q" class="form-control" style="border-radius:999px 0 0 999px;min-width:240px" placeholder="¿Qué estás buscando?" value="{{ request('q') }}"><button class="btn btn-brand" style="border-radius:0 999px 999px 0;box-shadow:none;padding:.45rem 1rem"><i class="bi bi-search"></i></button></div></form><a href="{{ route('carrito.index') }}" class="btn-carrito text-decoration-none position-relative"><i class="bi bi-bag me-1"></i><span class="d-none d-sm-inline">Carrito</span>@if($cartCount)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill" style="background:var(--grad)">{{ $cartCount }}</span>@endif</a></div></div></nav>
<main class="container py-4">@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif @yield('content')</main>
<footer class="footer-tienda pt-5 pb-4"><div class="container"><div class="row g-4"><div class="col-12 col-md-4"><h6><i class="bi bi-bag-heart-fill me-1"></i>{{ $storeName }}</h6><p class="small mb-0">Los mejores productos, seleccionados para ti, con envío a todo México y atención personalizada.</p></div><div class="col-12 col-md-4"><h6>Contacto</h6>@if($contactPhone)<p class="small mb-1"><i class="bi bi-telephone me-2"></i>{{ $contactPhone }}</p>@endif @if($contactEmail)<p class="small mb-1"><i class="bi bi-envelope me-2"></i>{{ $contactEmail }}</p>@endif @if($whatsappNumber)<p class="small mb-1"><i class="bi bi-whatsapp me-2"></i>WhatsApp disponible</p>@endif</div><div class="col-12 col-md-4"><h6>Compra con confianza</h6><div class="d-flex flex-wrap gap-2 mt-2"><span class="sello-pago"><i class="bi bi-shield-lock"></i> Pago seguro</span><span class="sello-pago"><i class="bi bi-credit-card"></i> Tarjetas</span><span class="sello-pago"><i class="bi bi-shop"></i> OXXO</span><span class="sello-pago"><i class="bi bi-truck"></i> Envíos MX</span></div></div></div><hr class="border-secondary my-4"><p class="small text-center mb-0">&copy; {{ now()->year }} {{ $storeName }} · Todos los derechos reservados.</p></div></footer>
@if($whatsappNumber)<a class="whatsapp-flotante" target="_blank" rel="noopener" title="Escríbenos por WhatsApp" href="https://wa.me/{{ $whatsappNumber }}?text={{ rawurlencode('Hola, vengo de la tienda '.$storeName.' y tengo una duda.') }}"><i class="bi bi-whatsapp"></i></a>@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>@stack('scripts')</body>
</html>
