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
| Exportaciones Excel | ✅ | Con `maatwebsite/excel`, todas las entidades |
| API REST básica | ✅ | `/api/v1/health`, `/api/v1/catalog/*` con resource classes y middleware JSON |
| Health check endpoint | ✅ | `HealthCheckService` reporta status de Firestore + versión + entorno |
| **Integración Cloudinary** | ✅ | Upload Widget (browser → Cloudinary directo), shape Firestore `{url, public_id}`, cleanup automático al reemplazar/borrar, max 10MB |
| **UX Toast notifications** | ✅ | Bootstrap 5 Toasts (esquina superior derecha) reemplazan al alert clásico, auto-dismiss en success |
| **Thumbnails en listados** | ✅ | Productos, categorías y subcategorías muestran imagen al lado del nombre (transformación Cloudinary lazy) |

**Conclusión**: avance sobre funcionalidades + corrección en UX + integración completa con storage CDN.

---

## Cronograma de Entregas (PRs Obligatorios)

| Fecha Límite | Hito | Estado |
|--------------|------|--------|
| 20 de Abril | Deploy en Vercel (frontpage mínimo) | ✅ Completo PR |
| 11 de Mayo | Avance Laravel (CRUDs parciales) | ✅ Completo PR |
| 25 de Mayo | Avance Laravel (funcionalidades adicionales) | ✅ Completo PR |
| 7 de Junio | PR Final — Laravel + API Completa | ⏳ Pendiente |

*Las fechas corresponden al cronograma establecido por la catedra.*

---

## Funcionalidades del sistema (resumen)

### Panel administrativo
- **CRUDs completos**: Categorías, Subcategorías, Productos, Descuentos, Usuarios, Clientes, Órdenes, Envíos
- **Patrón único**: modales dinámicos con AJAX, validación inline, sin recarga
- **Búsqueda + filtros + ordenamiento + paginación** en todos los listados
- **Imágenes en Cloudinary**: galería única por producto (primera = principal), imagen única por categoría/subcategoría, cleanup automático al reemplazar
- **Importación/Exportación masiva** en CSV y Excel
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

### Storage de imágenes
- **Cloudinary** como CDN + storage (free tier 25GB)
- Upload directo browser → Cloudinary con Upload Widget (sin pasar por Laravel, evita límite 4.5MB de Vercel)
- `CloudinaryService` server-side solo para borrar assets cuando se reemplazan
- Transformaciones on-the-fly por URL (thumbs de 40px en listados, 220px en formularios)

### Calidad
- **Tests**: 112 tests, Feature tests por cada controller con mocks de FirestoreService y CloudinaryService
- **PHPStan nivel 5**: verde
- **Laravel Pint**: formato consistente

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
