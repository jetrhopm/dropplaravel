# Despliegue en Hostinger

Esta guia usa SSH y deja el codigo Laravel fuera del directorio publico. Solo los archivos de `public/` se copian a `public_html`, que es el directorio servido por el dominio. Asi `.env`, `vendor/` y el resto del codigo no quedan expuestos por HTTP.

Antes de empezar, crea en hPanel la base MySQL y un usuario con acceso total a esa base. Activa SSH y confirma en el Administrador de archivos la ruta real de `public_html` para el dominio.

## Primera instalacion

Sustituye `USUARIO`, `DOMINIO`, `DB_*` y la ruta de `WEB_ROOT` por los datos de Hostinger. Ejecuta desde tu computadora:

```bash
ssh USUARIO@IP_O_HOST_DE_HOSTINGER
```

Una vez dentro de SSH, ejecuta estos comandos. `APP_HOME` debe permanecer fuera de `public_html`.

```bash
export APP_HOME="$HOME/apps/dropplaravel"
export WEB_ROOT="$HOME/domains/DOMINIO/public_html"

mkdir -p "$HOME/apps"
git clone https://github.com/jetrhopm/dropplaravel.git "$APP_HOME"
cd "$APP_HOME"

composer install --no-dev --prefer-dist --optimize-autoloader
cp .env.example .env
nano .env
```

En `.env` configura como minimo lo siguiente. No subas este archivo a GitHub:

```dotenv
APP_NAME="Mi Tienda"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://DOMINIO

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=DB_NOMBRE
DB_USERNAME=DB_USUARIO
DB_PASSWORD="DB_PASSWORD"

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
MAIL_MAILER=log
```

Continua con la inicializacion, publica solo el directorio correcto y crea el enlace para imagenes:

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize:clear

mkdir -p "$WEB_ROOT"
if [ -f "$WEB_ROOT/index.php" ]; then
  cp -a "$WEB_ROOT" "${WEB_ROOT}.backup.$(date +%Y%m%d_%H%M%S)"
fi
cp -a "$APP_HOME/public/." "$WEB_ROOT/"
rm -rf "$WEB_ROOT/storage"
ln -s "$APP_HOME/storage/app/public" "$WEB_ROOT/storage"
sed -i "s|__DIR__.'/../|$APP_HOME/|g" "$WEB_ROOT/index.php"

chmod -R ug+rwX "$APP_HOME/storage" "$APP_HOME/bootstrap/cache"
php artisan config:cache
php artisan route:cache
```

La sustitucion de `index.php` hace que el archivo en `public_html` cargue `vendor`, `bootstrap` y `storage` desde `APP_HOME`. Verifica que `WEB_ROOT` sea la ruta exacta mostrada por Hostinger antes de ejecutar los comandos de copia.

El seeder crea `admin@tienda.com` con la clave inicial `admin12345` solo si ese usuario no existe. Entra a `https://DOMINIO/admin/login` y cambia la clave inmediatamente.

## Actualizacion desde GitHub

Activa mantenimiento, trae exclusivamente avances lineales de `main`, instala dependencias y vuelve a publicar el contenido de `public/`:

```bash
export APP_HOME="$HOME/apps/dropplaravel"
export WEB_ROOT="$HOME/domains/DOMINIO/public_html"

cd "$APP_HOME"
php artisan down --render="errors::503"
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear

cp -a "$APP_HOME/public/." "$WEB_ROOT/"
rm -rf "$WEB_ROOT/storage"
ln -s "$APP_HOME/storage/app/public" "$WEB_ROOT/storage"
sed -i "s|__DIR__.'/../|$APP_HOME/|g" "$WEB_ROOT/index.php"

php artisan config:cache
php artisan route:cache
php artisan up
```

No ejecutes `db:seed` en una actualizacion normal. Solo es necesario al crear una instalacion nueva.

## Verificacion posterior

```bash
cd "$APP_HOME"
php artisan about --only=environment
php artisan route:list --path=admin
ls -la "$WEB_ROOT/storage"
```

Comprueba el catalogo, inicio de sesion de administrador, carga de una imagen y una compra manual. Antes de conectar pagos reales, configura HTTPS y las URLs publicas de retorno/webhook con el dominio definitivo.
