# Plan de migración a Laravel

La versión PHP en `jetrhopm/dropp` se mantiene activa e intacta hasta que cada flujo equivalente esté verificado en Laravel.

## 1. Base y tienda pública

Estado: terminado.

- Migraciones compatibles con el modelo de datos existente.
- Catálogo, detalle, búsqueda, carrito, checkout manual y confirmación.
- Rutas con nombre para evitar dependencias de archivos `.php` y URLs relativas.

Criterio de cierre: compra manual cubierta por pruebas de integración.

## 2. Operación administrativa

Estado: terminado.

- Autenticación de administradores mediante el guard de Laravel.
- Dashboard, productos con imágenes, pedidos, configuración y cambio de contraseña.
- Acceso protegido por middleware y acciones mutables con métodos HTTP y CSRF.

Criterio de cierre: rutas sin acceso anónimo, creación de productos y cambio de contraseña cubiertos por pruebas.

## 3. Importación de productos

Estado: terminado.

- Extraer JSON-LD, Open Graph y datos basicos de AliExpress/Temu en un servicio aislado.
- Descargar y validar imagenes mediante `Storage`.
- Mostrar vista previa antes de persistir y conservar el enlace original.
- Bloquear destinos privados o reservados, validar cada redireccion y limitar tamano y tipo de descarga.

Criterio de cierre: vista previa, guardado e intento de URL privada cubiertos por pruebas de integracion.

## 4. Pagos y notificaciones

Estado: pendiente.

- Separar Mercado Pago, PayPal, Openpay y Clip en adaptadores de pasarela.
- Añadir retorno firmado, verificación servidor a servidor y webhook idempotente de Clip.
- Encolar notificaciones por correo y generar enlace de WhatsApp para pedidos.

Criterio de cierre: pruebas HTTP simuladas para pago aprobado, pendiente, fallido, retorno repetido y webhook repetido.

## 5. Corte a producción

Estado: pendiente.

- Importar o transformar datos del MySQL actual en un entorno de prueba.
- Comparar conteos de productos, imágenes, pedidos e importes antes de cambiar el dominio.
- Configurar Hostinger con document root en `public`, HTTPS, permisos de `storage`, variables `.env` y tareas de caché.
- Activar el webhook con la URL HTTPS final y ejecutar una compra de punta a punta.

Criterio de cierre: checklist de despliegue completo, respaldos verificables y sin rutas PHP expuestas en el dominio Laravel.
