# Escabeche Don Edgar — Ecommerce local

Proyecto completo en **PHP 8.2+ / MySQL / HTML / CSS / JavaScript**, pensado para hosting compartido como Hostinger. No usa WordPress ni WooCommerce.

## Qué incluye

- Home responsive y orientada a conversión.
- 4 sabores: Clásico, Palmito, Champiñones y Repollito.
- Carrito persistente con `localStorage`.
- Checkout con retiro o delivery, dirección, geolocalización y foto opcional de fachada.
- QR de demostración reemplazable desde administración.
- Subida segura de comprobante.
- Registro real de pedidos en MySQL.
- Seguimiento público mediante token aleatorio.
- Panel administrador responsive.
- Estados de pedido y enlace de WhatsApp.
- Productos/precio/stock editables.
- Tarifas de delivery editables.
- Exportación CSV.
- Instalador web.

## Instalación en Hostinger

1. Crea una base de datos MySQL/MariaDB desde hPanel.
2. Sube `ESCABECHE-DON-EDGAR.zip` a `public_html` y extráelo.
3. Verifica que PHP sea 8.2 o superior.
4. Abre `https://tu-dominio.com/install/`.
5. Ingresa host, base, usuario y contraseña MySQL.
6. Crea el usuario y contraseña de administración.
7. Entra a `/admin/`.
8. Ve a **Configuración** y reemplaza:
   - WhatsApp de demostración.
   - QR de demostración.
   - Dirección exacta de retiro.
   - Latitud/longitud del negocio si deseas usarlas.
9. Ve a **Delivery** y confirma/ajusta las zonas y tarifas.
10. Desactiva **Modo demo** cuando la configuración real esté completa.

## Datos iniciales

- Todos los sabores: **Bs 60**.
- Retiro personal: **Bs 0**.
- Hasta 4.º anillo: **Bs 15**.
- Zona central / 1.er anillo: **Bs 25**.
- Otras zonas: **precio por confirmar**.

No se inventan otras tarifas.

## WhatsApp

La web usa un enlace `wa.me` con mensaje prellenado. Un navegador normal no puede adjuntar automáticamente el comprobante a WhatsApp. El comprobante queda guardado en el pedido y WhatsApp se utiliza como canal de contacto. Para automatización real, integra posteriormente WhatsApp Cloud API.

## Seguridad

- PDO + prepared statements.
- CSRF.
- `password_hash()` / `password_verify()`.
- Sesiones HttpOnly / SameSite.
- Uploads con validación MIME, extensión, tamaño y nombre aleatorio.
- Bloqueo de ejecución PHP en `storage/uploads`.
- IDs públicos no secuenciales.

## Copias de seguridad

Respalda periódicamente:

- La base MySQL completa.
- La carpeta `storage/uploads/`.
- `config/config.php` (guárdala de forma segura, contiene credenciales de base de datos).

No coloques backups dentro de una carpeta pública accesible por web.

## Nota sobre mapas

El checkout utiliza Leaflet/OpenStreetMap desde CDN para mostrar la ubicación. Si deseas funcionamiento completamente offline, descarga y sirve Leaflet localmente.

## Antes de producción

- Cambia el QR demo.
- Cambia el WhatsApp demo.
- Confirma ubicación de retiro.
- Revisa tarifas de delivery.
- Activa HTTPS.
- Realiza un pedido completo de prueba en móvil y PC.
