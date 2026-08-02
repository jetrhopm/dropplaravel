# Dropshipping Laravel

Migración progresiva del MVP de PHP puro en `jetrhopm/dropp` a Laravel 12. El proyecto original se conserva sin cambios como referencia comparativa.

## Estado actual

- Catálogo público, búsqueda y página de producto.
- Carrito basado en sesión y checkout para pagos manuales.
- Pedidos, productos, imágenes, configuraciones y usuarios modelados con Eloquent.
- Panel administrativo protegido en `/admin`: acceso, dashboard, productos, importador por URL, pedidos, configuración y cambio de contraseña.
- Esquema compatible con las tablas de la versión PHP: `usuarios`, `configuracion`, `productos`, `producto_imagenes`, `pedidos` y `pedido_items`.
- Rutas públicas estables y con nombre: `/`, `/producto/{id}`, `/carrito`, `/checkout`, `/pedido-confirmado`.

Mercado Pago Checkout Pro, PayPal y Openpay estan disponibles mediante variables de entorno. Pendiente de portar: Clip.

Después de ejecutar el seeder local, entra en `/admin/login` con `admin@tienda.com` / `admin12345` y cambia la contraseña desde Configuración. No uses esas credenciales en producción.

## Desarrollo local

1. Copia `.env.example` a `.env` y configura MySQL.
2. Ejecuta `composer install` y `php artisan key:generate`.
3. Ejecuta `php artisan migrate --seed`.
4. Ejecuta `php artisan storage:link` para exponer las imágenes de productos.
5. Inicia con `php artisan serve` y abre la URL indicada.

## Hostinger

El dominio debe apuntar al directorio `public/`, nunca a la raíz del proyecto. En un hosting compartido se puede dejar el código fuera de `public_html` y copiar solo el contenido de `public/` a `public_html`, ajustando las rutas de `public/index.php`; si el plan permite cambiar el document root, apunta directamente a `<proyecto>/public`.

Configura las variables `APP_ENV=production`, `APP_DEBUG=false`, base de datos MySQL y `APP_URL=https://tu-dominio`. Después ejecuta `php artisan migrate --force`, `php artisan db:seed --force`, `php artisan storage:link`, `php artisan config:cache` y `php artisan route:cache` mediante SSH. Si no hay SSH, importa la base de datos desde phpMyAdmin y prepara el enlace de `storage` según las opciones del panel.

Nunca subas `.env`, `storage/logs` ni archivos de credenciales al repositorio.

Los comandos para publicar desde GitHub en Hostinger con los archivos publicos dentro de `public_html` estan en [docs/HOSTINGER_DEPLOY.md](docs/HOSTINGER_DEPLOY.md).

El plan detallado y los criterios de cierre están en [docs/MIGRATION_PLAN.md](docs/MIGRATION_PLAN.md).
