# Proyecto E-Commerce MA Piscinas

## Integrantes

| Nombre | Rol |
|--------|-----|
| Francisco Terron | Desarrollador Full Stack |
| Mauro San Pedro | Desarrollador Full Stack |

## Empresa Ficticia

**MA Piscinas** — E-commerce de venta de piscinas e insumos de mantenimiento.

## Enunciado del Proyecto 
Se propone el desarrollo de una aplicación web de tipo e-commerce orientada a la comercialización de piscinas e insumos asociados, tales como productos de mantenimiento, accesorios y repuestos.
El sistema estará compuesto por dos módulos principales: un panel administrativo y una interfaz de usuario para clientes. El panel administrativo permitirá la gestión integral del sistema, incluyendo la administración de categorías, subcategorías, productos, descuentos, usuarios, pedidos, envíos y dashboard con reportes (Panel estadístico con gráficos de ventas, productos más vendidos, análisis de negocio). Este módulo será desarrollado utilizando PHP con el framework Laravel y vistas renderizadas mediante Blade, aplicando el patrón de arquitectura MVC.
Por otro lado, la interfaz de usuario estará orientada a la experiencia de compra, permitiendo la navegación del catálogo, la selección de productos, la gestión de un carrito de compras y la realización de pedidos. Este módulo será implementado como una aplicación de una sola página (SPA) utilizando React, consumiendo una API REST provista por el backend.
La persistencia de datos será gestionada mediante Firestore, garantizando integridad y consistencia de la información. Para el procesamiento de pagos se integrará la pasarela Mercado Pago, permitiendo la gestión de transacciones de forma segura.
El entorno de desarrollo será contenerizado mediante Docker, facilitando la portabilidad y replicabilidad del sistema. Para el despliegue, la aplicación frontend será publicada en Vercel, mientras que el backend será alojado en un servidor compatible con PHP.
El desarrollo contemplará buenas prácticas en términos de seguridad, validación de datos, accesibilidad, usabilidad y organización del código, con el objetivo de construir una solución escalable, mantenible y alineada con estándares actuales de desarrollo web.

## Stack de tecnologias

### Backend
- Laravel (PHP)
- API REST + MVC (Blade para administrativos)

### Base de Datos
- Firestore

### Frontend Administrativo
- Blade (Laravel)
- Tailwind CSS

### Frontend Cliente (E-commerce)
- React
- Tailwind CSS

### Pagos
- Mercado Pago

### Infraestructura (Desarrollo)
- Docker

### Deploy
- Vercel

### Herramientas de desarrollo
- Visual Studio Code
- Git
- Node.js

---

## Estado del Proyecto y Avances

### Primer Entrega (PR) — 20 de Abril 2025
**Módulo**: Backend Laravel (Deploy inicial + Frontpage)

| Item | Estado | Observaciones |
|------|--------|---------------|
| Repositorio GitHub | ✅ | `FranciscoTerron/AplicacionesWeb` |
| Docker configurado | ✅ | Contenedores backend y nginx |
| Laravel 13 instalado | ✅ | Estructura base funcional |
| Landing page con datos | ✅ | Empresa "MA Piscinas" + integrantes |
| PR creado hacia `main` | ✅ | PR Aprobado |
| Deploy en Vercel | ✅ | Correcto |

**Conclusión**: Se cumplió con lo mínimo requerido para el 20/04.

---

### Segunda Entrega (PR) — 11 de Mayo 2026
**Módulo**: Backend Laravel (CRUDs parciales + Panel Admin + Firestore)

| Item | Estado | Observaciones |
|------|--------|---------------|
| **CRUD Categorías** | ✅ | **Completo y funcional** |
| **CRUD Subcategorías** | ✅ | **Completo y funcional** |
| **CRUD Productos** | ✅ | **Completo y funcional**|
| **CRUD Descuentos** | ✅ | **Completo y funcional**|
| **CRUD Usuarios** | ✅ | **Completo y funcional** |
| **CRUD Clientes** | ✅ | **Completo y funcional** |
| Configuración Firestore | ✅ | FirestoreService implementado, reglas y desplegados en Firebase |
| Login Administrativos | ✅ | AuthController con Firebase implementado; FirestoreUserProvider bloquea usuarios inactivos |
| Panel Administrativo | ✅ | Estructura base con sidebar, ruteo y vistas Blade |
| Dashboard con estadísticas | ✅ | **Completo y funcional** |
| Ruteo configurado | ✅ | Web routes definidas para admin y auth |

**Conclusión**: CRUD Usuarios 100% funcional con UX optimizada (modal único, AJAX inline) y código robusto (tests + PHPStan).

---

### Tercer Entrega (PR) — 25 de Mayo 2026
**Módulo**: Backend Laravel (funcionalidades adicionales + API REST + storage de imágenes)

| Item | Estado | Observaciones |
|------|--------|---------------|
| Paginación | ✅ | Cursor-based en todos los listados admin, selector de `per_page` (10/25/50/100) |
| Filtros | ✅ | Por categoría/subcategoría/estado/etc en todas las entidades |
| Búsqueda | ✅ | Por nombre, SKU, slug, email según entidad |
| Ordenamiento | ✅ | Headers de tabla clickeables (nombre, precio, stock, fecha) |
| Breadcrumbs | ✅ | Component reutilizable en todas las vistas admin |
| Accesibilidad y validación frontend | ✅ | `aria-label`, `aria-describedby`, focus trap en modales, validación inline |
| Importaciones CSV | ✅ | Productos, categorías y descuentos. Solo rol admin |
| Exportaciones CSV | ✅ | Todas las entidades del admin |
| API REST básica | ✅ | `/api/v1/health`, `/api/v1/catalog/*` con resource classes y middleware JSON |
| Health check endpoint | ✅ | `HealthCheckService` reporta status de Firestore + versión + entorno |
| **Integración Cloudinary** | ✅ | Upload Widget (browser → Cloudinary directo), shape Firestore `{url, public_id}`, cleanup automático al reemplazar/borrar, max 10MB |
| **UX Toast notifications** | ✅ | Bootstrap 5 Toasts (esquina superior derecha) reemplazan al alert clásico, auto-dismiss en success |
| **Thumbnails en listados** | ✅ | Productos, categorías y subcategorías muestran imagen al lado del nombre (transformación Cloudinary lazy) |

**Conclusión**: avance sobre funcionalidades + corrección en UX + integración completa con storage CDN.

---

### Cuarta Entrega (PR) — 7 de Junio 2026
**Módulo**: PR Final — Laravel + API Completa

| Item | Estado | Observaciones |
|------|--------|---------------|
| Endpoint health | ✅ | Listo |
| Endpoint Login | ✅ | Listo |
| Endpoint register | ✅ | Listo |
| Endpoint refresh token | ✅ | Listo |
| Endpoint obtener productos| ✅ | Listo |
| Endpoint obtener producto | ✅ | Listo |
| Endpoint obtener productos destacados | ✅ | Listo |
| Endpoint obtener categorías | ✅ | Listo |
| Endpoint validacion de descuento | ✅ | Listo |
| Endpoint obtener pedidos | ✅ | Listo |
| Endpoint obtener pedido | ✅ | Listo |
| Endpoint crear pedido | ✅ | Listo |
| Endpoint cancelar pedido | ✅ | Listo |
| Endpoint crear carrito | ✅ | Listo |
| Endpoint obtener carrito | ✅ | Listo |
| Rate limiting | ✅ | Listo |
| Payment webhook | ✅ | Listo |
| Search avanzado | ✅ | Listo |
| Filtros avanzados | ✅ | Listo |
| Sorting en productos | ✅ | Listo |
| Schema OpenAPI | ✅ | Listo (Scramble auto-generación) |
| Endpoint de wishlist | ✅ | Listo |

**Conclusión**: La API REST está 100% implementada y documentada. Todos los endpoints requeridos están disponibles: productos, categorías, carrito, wishlist, búsqueda avanzada, payment webhook con verificación HMAC de Mercado Pago, y rate limiting. La documentación OpenAPI está regenerada y disponible en `/docs/api` y en `backend/public/api-docs.json` para consumo desde Vercel.

---

### Quinta Entrega (PR) — 27 de Junio 2026
**Módulo**: Frontend cliente (Next.js) + Pagos Mercado Pago end-to-end

| Item | Estado | Observaciones |
|------|--------|---------------|
| Frontend cliente Next.js | ✅ | Catálogo, detalle, carrito, checkout, cuenta/órdenes |
| Pago con Mercado Pago (Checkout Pro) | ✅ | Preference + redirect + back_urls |
| Webhook concilia pago automático | ✅ | Verificación HMAC, en prod |
| Orden pasa a `confirmed` al aprobar | ✅ | Sin pisar estados de envío |
| Descuento de stock al aprobar pago | ✅ | Idempotente, nunca baja de 0 |
| Cupón por código aplicado a la orden | ✅ | Regla no-stack (gana el mayor) |
| Carrito se vacía al crear la orden | ✅ | Server-side, atómico |
| Comprobante auto-refresca tras pagar | ✅ | Polling hasta que el webhook confirma |
| Cron de órdenes vencidas | ✅ | Cancela pending sin pagar > 48h |

**Conclusión**: El flujo de compra quedó cerrado de punta a punta, con pagos reales de prueba aprobándose y conciliándose solos vía webhook.

---

## Cronograma de Entregas (PRs Obligatorios)

| Fecha Límite | Hito | Estado |
|--------------|------|--------|
| 20 de Abril | Deploy en Vercel (frontpage mínimo) | ✅ Completo PR |
| 11 de Mayo | Avance Laravel (CRUDs parciales) | ✅ Completo PR |
| 25 de Mayo | Avance Laravel (funcionalidades adicionales) | ✅ Completo PR |
| 7 de Junio | PR Final — Laravel + API Completa |✅ Completo PR |

*Las fechas corresponden al cronograma establecido por la catedra.*

---

## Funcionalidades del sistema (resumen)

### Panel administrativo
- **CRUDs completos**: Categorías, Subcategorías, Productos, Descuentos, Usuarios, Clientes, Órdenes, Envíos
- **Patrón único**: modales dinámicos con AJAX, validación inline, sin recarga
- **Búsqueda + filtros + ordenamiento + paginación** en todos los listados
- **Imágenes en Cloudinary**: galería única por producto (primera = principal), imagen única por categoría/subcategoría, cleanup automático al reemplazar
- **Importación/Exportación masiva** en CSV
- **Breadcrumbs** y **toasts** de feedback
- **Autorización por rol** (admin / editor) con Laravel Policies
- **Auditoría**: `created_by`, `updated_by`, `created_at`, `updated_at` en todos los documentos

### Autenticación
- Login con Google OAuth (Socialite)
- Provider custom `FirestoreUserProvider` que valida usuarios activos en Firestore
- Admin automático para emails en `GOOGLE_ADMIN_EMAILS`

### Persistencia
- **Firestore** vía `FirestoreService` (HTTP API directa, sin SDK pesado en Vercel)
- Service account credentials embebidas como JSON en env var para serverless

### API REST (`/api/v1`)
- `GET /api/v1/health` — health check con status de servicios
- `GET /api/v1/catalog/products` — listado público de productos activos
- `GET /api/v1/catalog/categories` — listado público de categorías
- Middleware `ForceJsonResponse` + `AcceptJsonHeader` para asegurar respuestas JSON
- Resource classes (`ApiResponseResource`) para shape consistente

### Flujo de compra (frontend cliente)
- **Catálogo** con filtros, orden y búsqueda; **stock visible** en card y detalle (lo trae del backend; "Sin stock" / "Últimas N unidades")
- **Carrito** con cantidades y validación de cupón
- **Checkout**: dirección + método de pago. Al crear la orden el **backend vacía el carrito** server-side (atómico, sin depender del cliente)
- **Comprobante** (`/cuenta/ordenes/{id}`): tras volver de Mercado Pago **auto-refresca** cada 3s hasta que el webhook confirma el pago (no hace falta recargar a mano)

### Pagos (Mercado Pago — Checkout Pro)
- `POST /orders/{id}/pay` crea una **preference** y devuelve el `init_point`; el front redirige al checkout de MP
- `back_urls` (success / failure / pending) construidas con `FRONTEND_URL`; `notification_url` con `APP_URL` (solo si es HTTPS)
- **Webhook** `POST /payments/webhook`: verifica la **firma HMAC** (`x-signature`) con `MERCADOPAGO_WEBHOOK_SECRET`, consulta el pago real en la API de MP (fuente de verdad del monto y `external_reference`) y **concilia la orden**
- Al aprobarse: orden `pending → confirmed` (no pisa `shipped`/`delivered`) y se **descuenta el stock** (idempotente vía bandera `stock_decremented`; si no alcanzaba, marca `oversold` para revisión)
- **Modo test**: credenciales de prueba + cuentas/tarjetas de prueba (el titular de la tarjeta define el resultado: `APRO`, `FUND`, `OTHE`, etc.)

### Descuentos y cupones
- **Descuentos automáticos** por producto/categoría: `DiscountService::bestForProduct` elige el que más baja el precio
- **Cupón por código**: el cliente lo valida en el carrito; viaja al checkout y se aplica server-side al crear la orden
- **Regla no-stack**: el cupón y el descuento automático **no se suman** — gana el que deja el total más bajo
- Un descuento vale solo si está **activo, vigente y con usos disponibles** (`isUsable`)

### Tareas programadas (Vercel Cron)
- `GET /cron/expire-orders` (diario, 04:00 UTC): **cancela** las órdenes que quedaron `pending` y sin pagar más de **48h** (no las borra: `status = cancelled`, `cancel_reason = expired_unpaid`)
- Protegido por `Authorization: Bearer CRON_SECRET`; sin el secreto el endpoint queda deshabilitado

### Storage de imágenes
- **Cloudinary** como CDN + storage (free tier 25GB)
- Upload directo browser → Cloudinary con Upload Widget (sin pasar por Laravel, evita límite 4.5MB de Vercel)
- `CloudinaryService` server-side solo para borrar assets cuando se reemplazan
- Transformaciones on-the-fly por URL (thumbs de 40px en listados, 220px en formularios)

### Calidad
- **Tests**: 225 tests, Feature tests por cada controller con mocks de FirestoreService y CloudinaryService (incluye flujo integral de compra/pago MP, cupones y cron)
- **PHPStan nivel 5**: verde
- **Laravel Pint**: formato consistente

---

## Setup Mercado Pago + Cron (variables de entorno)

El flujo de pago y las tareas programadas se configuran con estas env vars en el proyecto de Vercel del **backend** (`ma-piscinas`):

| Variable | Para qué | Notas |
|----------|----------|-------|
| `MERCADOPAGO_ACCESS_TOKEN` | Crear preferences y consultar pagos | Token de **prueba** o de **producción** según el entorno |
| `MERCADOPAGO_WEBHOOK_SECRET` | Verificar la firma del webhook | Debe ser **igual** a la "Clave secreta" del panel MP → Webhooks |
| `APP_URL` | Arma el `notification_url` del webhook | Debe ser **HTTPS** o el webhook no se registra |
| `FRONTEND_URL` | Arma los `back_urls` del checkout | URL pública del frontend cliente |
| `CRON_SECRET` | Autoriza los endpoints de cron | Vercel lo manda como `Bearer`; sin él el cron queda deshabilitado |

> El webhook (`/api/v1/payments/webhook`) y el cron (`/api/v1/cron/expire-orders`) están registrados en `backend/vercel.json` (el cron corre diario, 04:00 UTC).

### Probar pagos en modo test
1. Panel MP → **Cuentas de prueba**: crear un comprador y un vendedor de prueba.
2. Setear el `MERCADOPAGO_ACCESS_TOKEN` del **vendedor de prueba**.
3. Loguearse en `mercadopago.com.ar` como el **comprador de prueba** (no la cuenta real).
4. Comprar en la tienda y pagar con tarjeta de prueba; el **titular** define el resultado: `APRO` (aprobado), `FUND` (sin fondos), `OTHE` (rechazado), etc.

> ⚠️ Tarjeta de prueba **solo** funciona con credenciales de prueba. Con credenciales de producción se necesitan tarjeta y cuenta reales.

---

## Setup Cloudinary (imágenes)

Las imágenes se suben directo desde el navegador con el Upload Widget de Cloudinary y se guardan en Firestore como objetos `{url, public_id}`. El backend usa `App\Services\CloudinaryService` solo para borrar assets cuando se reemplaza o elimina una imagen.

### Modelo de datos

- **Product** → `images: [{url, public_id}, ...]` (array; la primera es la principal por convención)
- **Category** → `image: {url, public_id}` (objeto único o `null`)
- **Subcategory** → `image: {url, public_id}` (objeto único o `null`)

### 1. Cuenta y credenciales

1. Crear cuenta gratis en [cloudinary.com](https://cloudinary.com).
2. Dashboard → anotar **Cloud Name**, **API Key**, **API Secret**.
3. Settings → Upload → **Add upload preset**:
   - Signing Mode: **Unsigned**
   - Folder: `ma-piscinas`
   - Allowed formats: `jpg, png, webp`
   - **Max file size bytes**: `10000000` (10 MB) o vacío para sin límite del preset
4. Anotar el **nombre del preset**.

### 2. Variables de entorno

En `backend/.env` (local) y en Vercel (production + preview):

```
CLOUDINARY_CLOUD_NAME=tu-cloud-name
CLOUDINARY_API_KEY=tu-api-key
CLOUDINARY_API_SECRET=tu-api-secret   # Sensitive en Vercel
CLOUDINARY_UPLOAD_PRESET=tu-preset
```

Para Vercel:
```bash
vercel env add CLOUDINARY_CLOUD_NAME production
vercel env add CLOUDINARY_CLOUD_NAME preview
vercel env add CLOUDINARY_API_KEY production
vercel env add CLOUDINARY_API_KEY preview
vercel env add CLOUDINARY_API_SECRET production   # marcar Sensitive
vercel env add CLOUDINARY_API_SECRET preview      # marcar Sensitive
vercel env add CLOUDINARY_UPLOAD_PRESET production
vercel env add CLOUDINARY_UPLOAD_PRESET preview
vercel --prod
```

### 3. Cómo funciona

1. El admin abre el modal de Nuevo/Editar producto → ve un cuadro punteado "Click para subir imagen".
2. Click → abre el **Upload Widget de Cloudinary** (popup JS).
3. El usuario arrastra/elige el archivo → se sube **directo del navegador a Cloudinary** (no pasa por Laravel).
4. Cloudinary devuelve `{secure_url, public_id}` → el JS los guarda en hidden inputs del form.
5. Submit del form → Laravel valida el shape y guarda en Firestore.
6. Al **editar** y reemplazar una imagen, el controller detecta el `public_id` removido y llama a `CloudinaryService::deleteAsset()` para borrarlo de Cloudinary.

### 4. Verificar que funciona

- Subir una imagen → debería aparecer en Cloudinary → Media Library → carpeta `ma-piscinas/products/`
- Editar el producto y reemplazar la imagen → la vieja debería desaparecer de Cloudinary
- El thumbnail de 40px aparece al lado del nombre en el listado (transformación `c_thumb,w_40,h_40,g_auto` aplicada por URL)
