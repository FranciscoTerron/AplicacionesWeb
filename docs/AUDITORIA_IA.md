# Auditoría IA — MA Piscinas (monorepo)

> **Entregable del examen final 2026 — Aplicaciones Web.**
> Auditoría realizada con el prompt obligatorio del enunciado (ver `docs/ENUNCIADO_FINAL.md`, líneas 37-68).

| | |
|---|---|
| **Auditor (modelo)** | Claude Opus 4.8 |
| **Fecha** | 2026-07-16 |
| **Alcance** | Monorepo completo: `backend/` (Laravel 13 + PHP 8.3 + Firestore) + `frontend/` (Next.js 16 + React 19) |
| **Método** | Análisis estático del código, referencias verificadas archivo:línea. No se ejecutó la app (Lighthouse pendiente). |

---

## Tabla resumen

| Categoría | Cantidad de hallazgos | Severidad máxima |
|---|---|---|
| 1. Seguridad | 7 | Media |
| 2. Arquitectura y organización | 13 | Alta |
| 3. Calidad de código | 18 | Alta |
| 4. Rendimiento | 10 | **Crítica** |
| 5. PWA | 10 | **Crítica** |
| 6. Deuda técnica | 12 | Alta |
| **TOTAL** | **70** | **Crítica** |

Distribución por severidad: Crítica 6 · Alta 12 · Media 30 · Baja 22.

---

## 1. Seguridad

> Cubre: inyección, auth/authz, exposición de datos, CORS, validación de inputs, protección de la API.

**S-1 — `backend/config/cors.php:12-13` · Media**
CORS acepta comodines `https://*.vercel.app` y `http://localhost:*` (php-cors los convierte a regex `.*`).
*Impacto:* cualquier subdominio `*.vercel.app` (gratis, cualquiera lo despliega) puede hacer requests cross-origin y leer respuestas de los endpoints públicos (catálogo, login, register).
*Sugerencia:* dejar solo orígenes exactos del front (prod + localhost del dev), quitar los comodines.

**S-2 — `backend/routes/api.php:33-115` + `config/cors.php` · Media**
No hay mecanismo server-side que restrinja la API a "solo el front" (requisito explícito del enunciado, línea 24). CORS es control de navegador, no de servidor.
*Impacto:* cualquier cliente no-browser (curl/Postman/bot) llama libremente login, register (cuentas masivas) y catálogo.
*Sugerencia:* middleware con secreto compartido/clave de app o firma para endpoints públicos, o rate-limit estricto + captcha en register.

**S-3 — `frontend/src/lib/cookies.ts:24-33` · Media**
El token de sesión (`ma_token`, TTL 30 días) se guarda con `document.cookie` → NO es HttpOnly, legible por JS.
*Impacto:* un XSS (o dependencia comprometida) lee el token y opera la cuenta 30 días. `SameSite=Lax` no protege del robo por XSS.
*Sugerencia:* cookie `HttpOnly` seteada por el backend, o TTL corto + refresh, y CSP.

**S-4 — `backend/app/Http/Controllers/Api/V1/AuthApiController.php:241-273` (`refresh`) · Media**
`refresh` rota el token sin verificar `expires_at` (el middleware sí lo verifica) y los docs de `api_tokens` no se borran al expirar. El "refresh token" es el mismo access token.
*Impacto:* un token filtrado/vencido (>30 días) se "resucita" indefinidamente llamando `/auth/refresh`.
*Sugerencia:* validar `expires_at` antes de rotar; refresh tokens separados con su propia vida; purgar tokens expirados.

**S-5 — `backend/app/Providers/AppServiceProvider.php:40-41` · Media**
Único rate limiter `api` = 60/min global, sin `by()` dedicado. Login y register comparten ese cupo.
*Impacto:* hasta 60 intentos de credenciales por minuto por IP contra `/auth/login` sin bloqueo específico anti-fuerza-bruta.
*Sugerencia:* `throttle` estricto keyed por email+IP en login/register (ej. `Limit::perMinute(5)->by($email.$ip)`).

**S-6 — `backend/app/Http/Controllers/Api/V1/PaymentWebhookController.php:78-82` · Baja**
Si `verifySignature` falla, solo loguea warning y continúa (no retorna 400). Mitigado porque luego reconsulta el pago a MP y valida `external_reference` + monto.
*Impacto:* sin firma se pierde la primera línea de defensa contra webhooks forjados/replays.
*Sugerencia:* rechazar con 400 cuando la firma no valida en producción.

**S-7 — `backend/bootstrap/app.php:41-48` · Baja**
Faltan headers de seguridad (`X-Content-Type-Options`, `X-Frame-Options`/CSP, `HSTS`, `Referrer-Policy`) en API y panel admin Blade.
*Impacto:* panel `/admin/*` expuesto a clickjacking; sin HSTS ni CSP que limite el impacto de un XSS.
*Sugerencia:* middleware que setee esos headers.

> **Bien resuelto (para no sobre-reportar):** ownership verificado en órdenes y wishlist; precios resueltos server-side (el cliente no fija precio); passwords con bcrypt; tokens hasheados sha256; `register` fuerza `role='cliente'`; secretos por env y no commiteados; `APP_DEBUG` false; docs protegidas; cron protegido por `hash_equals`.

---

## 2. Arquitectura y organización

**A-1 — Ausencia de capa de repositorios (`backend/app/`, no existe `app/Repositories`) · Alta**
Los 25+ controllers llaman directo a `FirestoreService`. Infraestructura (Firestore REST) acoplada a presentación; testear sin red es inviable.
*Sugerencia:* `ProductRepository`, `OrderRepository`, etc. sobre `FirestoreService`; controllers dependen de la interfaz.

**A-2 — `Api/V1/OrderApiController.php:78-194` (`store`) · Alta**
Motor de pricing (precios server-side, stock, descuento auto vs cupón, regla "gana el que más baja") dentro del método HTTP, ~110 líneas de dominio.
*Sugerencia:* extraer a `OrderPricingService` / caso de uso `PlaceOrder`.

**A-3 — `Api/V1/PaymentWebhookController.php:123-213` · Alta**
Fulfillment (confirmar orden, descontar stock, oversold, vaciar carrito) embebido en el controller del webhook.
*Sugerencia:* mover a `ConfirmPaidOrder` / `InventoryService`; el webhook solo verifica firma y delega.

**A-4 — Contrato de estados de orden inconsistente front/back · Alta**
`frontend/src/types/api.ts:136-142` + `lib/order-status.ts:3-10` define `processing/shipped/delivered`; `backend/OrderController.php:79-88` define `in_process/completed`. No coinciden.
*Impacto:* una orden `in_process`/`completed` cae al fallback y se muestra al cliente como texto crudo. Sin fuente única de verdad.
*Sugerencia:* enum de estados en un solo lugar (backend), derivar labels del front.

**A-5 — Método muerto + operationId duplicado — `Api/V1/CatalogApiController.php:55-87` · Media**
La ruta `/catalog/products` apunta a `CatalogController::products`, no a `CatalogApiController::products` (código muerto). Ambos declaran el mismo `operationId:'listProducts'` → colisión OpenAPI.
*Sugerencia:* borrar el método muerto; unificar catálogo en un controller.

**A-6 — Filtrado de productos duplicado en 4 controllers · Media**
`CatalogController:126-157`, `CatalogApiController:67-81`, `SearchController:66-116`, `CatalogController web`. Ya divergen (`stripos` vs `mb_stripos`, unos decoran descuento, otros no).
*Sugerencia:* `ProductQueryService::filter($products, $criteria)`.

**A-7 — Métodos privados duplicados entre controllers · Media**
`normalizeProduct` (`CatalogController:220` + `CatalogApiController:183`); `clearCartForUser` (`OrderApiController:200` + `PaymentWebhookController:162`).
*Sugerencia:* `clearCartForUser`→`CartService`; `normalizeProduct`→`DiscountService`.

**A-8 — `CrudActionsTrait.php:168-177, 240-249` · Media**
El trait CRUD genérico tiene hardcodeado `if getCollectionName() === 'subcategories'`. Viola open/closed: el mecanismo genérico "conoce" un recurso concreto.
*Sugerencia:* hook `validateBeforeSave()` sobreescribible, sin `if` de colección.

**A-9 — Modelos anémicos sobre Eloquent (`backend/app/Models/*.php`) · Media**
Extienden `Eloquent\Model` (ORM SQL) pero la persistencia es Firestore por HTTP. `find()`/`save()` no operan; se usan solo para casts y Policies.
*Impacto:* arquitectura engañosa; los casts (`decimal:2`) se bypassean → de ahí `price: number|string` en el front.
*Sugerencia:* DTOs/entidades planas, o documentar que son "shells" para Policies.

**A-10 — Tipos de API mantenidos a mano — `frontend/src/types/api.ts:39` · Media**
`price: number | string`, `total_amount` mezclando tipos: delatan serialización inconsistente del back (Firestore devuelve strings).
*Sugerencia:* serializar tipos numéricos consistentes en el back; a futuro generar tipos desde el OpenAPI existente.

**A-11 — Filtrado/paginación en memoria como patrón de acceso a datos · Media**
`CatalogController:115-165`, `ProductController:124-211`, `OrderApiController:264-291`: `listDocuments(1000/200)` + filtrar en PHP. Límites mágicos (1000, 200) esparcidos.
*Sugerencia:* repositorio con query tipadas que empujen filtros a Firestore.

**A-12 — `CrudActionsTrait.php:156-165` (`store`) · Baja**
El FormRequest se re-resuelve con `app($requestClass)` en vez de inyectarse; workaround del patrón.
*Sugerencia:* aceptable; documentar o type-hintear el request por controller.

**A-13 — Manejo de 401 acoplado en la capa de fetch — `frontend/src/lib/api.ts` · Baja**
`window.dispatchEvent('auth:expired')` (side-effect global) desde un utilitario. *(El front, por lo demás, tiene buena separación: `api.ts`/`endpoints.ts`/`types` — es el lado bien resuelto.)*
*Sugerencia:* mover el side-effect a un interceptor del auth-context.

---

## 3. Calidad de código

**C-1 — `index()` duplicado en 8 controllers · Alta**
Product/Category/Discount/Subcategory/Client/User/Order/Shipment reimplementan ~40 líneas de paginación idénticas. El `CrudActionsTrait::index()` existe pero nadie lo usa; ya divergen.
*Sugerencia:* `paginateCollection()` parametrizado en el trait.

**C-2 — `activate()` copiado literal en 7 controllers · Alta**
Mismo `updateDocument(['active'=>true])` + try/catch, solo cambia el mensaje. El trait tiene `destroy()` pero no su simétrico.
*Sugerencia:* mover `activate()` al `CrudActionsTrait`.

**C-3 — Respuestas de API inconsistentes · Alta**
`OrderApiController` (:346,417,500), `AuthApiController` (todo), `PaymentWebhookController` (:85,115) mezclan `ApiResponse::success/error` con `response()->json` crudo. AuthApi: 0 helper / 8 crudos.
*Impacto:* la forma del JSON de error depende del método; el front asume `{success,message,data}` uniforme.
*Sugerencia:* forzar `ApiResponse::` en todos los Api/V1.

**C-4 — `FirestoreService.php:133-142` (`getDocument`) · Alta**
Solo maneja `status()===404`, no `failed()`. Ante 403/500/timeout parsea el body de error → devuelve `['id'=>...]` como si el doc existiera.
*Impacto:* fallos reales de Firestore se enmascaran como "documento vacío" y se propagan silenciosos (órdenes/productos que "desaparecen").
*Sugerencia:* `if ($response->failed() && $response->status() !== 404) throw`.

**C-5 — Cálculo de cupón duplicado front + back · Alta**
`carrito/page.tsx:34-40` y `checkout/page.tsx:61-67` repiten verbatim la regla `Math.max(autoDiscount, couponAmount)`, tercera copia de `DiscountService::applyValue` (back).
*Impacto:* regla "gana el mayor, no se apila" en 3 lugares; el total mostrado puede diferir del total del server.
*Sugerencia:* `computeBestDiscount()` en `lib/utils.ts`.

**C-6 — `FirestoreService.php:55-131` (`fetchAccessToken`) · Media**
~76 líneas, 6 fallbacks; el paso 1 (:61) y el paso 5 (:109) leen la MISMA clave → paso 5 inalcanzable.
*Sugerencia:* colapsar la resolución de credenciales; eliminar el paso muerto.

**C-7 — `FirestoreService.php:230-234` (`deleteDocument`) · Media**
`Http::delete()` sin chequeo de resultado (fire-and-forget), a diferencia de create/update/query.
*Impacto:* un delete fallido (ej. logout que revoca token) se reporta exitoso; token queda vivo.
*Sugerencia:* chequear `failed()` y lanzar/loguear.

**C-8 — `CrudActionsTrait.php:168, 240` (subcategories hardcodeado) · Media** *(ver A-8)*

**C-9 — String mágico `"discount_code"` en localStorage · Media**
`carrito/page.tsx:49`, `checkout/page.tsx:47,53,83,94` (5 ocurrencias), mientras `auth-context` sí usa constante `USER_KEY`.
*Sugerencia:* `const DISCOUNT_CODE_KEY` en storage-keys.

**C-10 — `api.ts:34-85` vs `:89-131` (`apiFetch`/`apiFetchRaw`) · Media**
~90% compartido; ya divergió el manejo de 401 (`apiFetch` dispara `auth:expired`, `apiFetchRaw` no).
*Sugerencia:* `request()` interno común + dos wrappers finos.

**C-11 — `OrderApiController.php:78-194` (`store`, ~116 líneas) · Media**
Múltiples responsabilidades + guard 404/ownership repetido literal 4 veces (show/cancel/pay) con status inconsistentes (404 vs 403).
*Sugerencia:* `resolveOwnedOrder()`; mover pricing a service.

**C-12 — `CategoryController.php:144` (`store`) · Media**
Autoriza con `'viewAny'` en vez de `'create'`; sus hermanos usan `'create'`.
*Impacto:* chequeo de permiso incorrecto (quien ve puede crear).
*Sugerencia:* cambiar a `'create'`.

**C-13 — `[10, 25, 50, 100]` hardcodeado en 8 controllers · Media**
*Sugerencia:* constante `ALLOWED_PER_PAGE`.

**C-14 — `FirestoreService.php:392,403` (`\Log::`) · Baja**
Facade en namespace global mientras el resto importa facades.
*Sugerencia:* `use Illuminate\Support\Facades\Log;`.

**C-15 — `ProductController.php:34-46` · Baja**
Docblock huérfano describiendo otro método, pegado encima de `sanitizeImages()`.
*Sugerencia:* borrar/mover el docblock.

**C-16 — `ProductController.php:172-204` · Baja**
Bloque `map(...)` que reconstruye `id` desde `_document_path` copiado 3 veces.
*Sugerencia:* helper `withResolvedIds()`.

**C-17 — `cuenta/ordenes/[id]/page.tsx:52,58,62` · Baja**
Números mágicos en polling: `tries >= 5`, `3000` ms sin nombre.
*Sugerencia:* `MAX_POLL_TRIES`, `POLL_INTERVAL_MS`.

**C-18 — Duplicación de normalización de ids en `ProductController::index` · Baja** *(relacionado con C-16)*

---

## 4. Rendimiento

**R-1 — `backend/app/Services/DiscountService.php:89` (`discountForProduct` vía `decorate()`) · Crítica**
`decorate()` hace `getDocument('discounts', $id)` por CADA producto. Se ejecuta en `CatalogController::products`, `CatalogApiController::products`, `SearchController`, `featured`. Existe `activeDiscounts()` con caché en memoria pero `decorate()` NO lo usa.
*Impacto:* N+1 clásico. Listar N productos con descuento dispara N GETs HTTP secuenciales extra a Firestore.
*Sugerencia:* cargar `activeDiscounts()` una vez, armar map `id=>discount`, resolver por lookup en memoria. **Fix barato, alto impacto.**

**R-2 — `Api/V1/CatalogController.php:115-163` · Alta**
`listDocuments('products', 1000)` + filtrar/paginar en memoria. Es el endpoint que consume el front.
*Impacto:* lee hasta 1000 docs aunque la página muestre 20; la paginación no reduce lecturas.
*Sugerencia:* `structuredQuery` (where + orderBy + limit + cursor) en Firestore.

**R-3 — `CatalogApiController.php:60` + `SearchController.php:62` · Alta**
`listDocuments('products', 200)`, filtran en memoria y devuelven SIN paginar (hasta 200 productos), más el N+1 de descuentos.
*Sugerencia:* paginar respuesta + query Firestore + aplicar fix R-1.

**R-4 — `DashboardController.php:15-18, 60-64` · Media**
4 colecciones completas (200 docs c/u) en 4 llamadas secuenciales para contar/sumar. `countDocuments`/aggregation ya existen y no se usan. Cap de 200 rompe `monthlyRevenue` con >200 órdenes.
*Sugerencia:* aggregation queries para counts, query filtrado para revenue.

**R-5 — `FirestoreService.php:338-356` (`fetchForPage`) · Media**
`do/while` con hasta 6 `listDocuments` HTTP secuenciales + filtrado en memoria.
*Sugerencia:* query único con filtros nativos + cursor real.

**R-6 — `ProductController.php:124-205` + `OrderController.php:120-180` · Media**
Tras `fetchForPage`, filtran en memoria + 3/2 `listDocuments` extra (dropdowns) sin caché en cada render admin.
*Sugerencia:* cachear datasets de dropdowns; filtrar/paginar en Firestore.

**R-7 — `Api/V1/CronController.php:39` · Media**
`listDocuments('orders', 500)` + filtra `pending` en memoria. Cap de 500 deja órdenes viejas sin expirar al crecer el volumen.
*Sugerencia:* query por `status='pending'` server-side.

**R-8 — `frontend/src/hooks/use-enriched-cart.ts:25-36` · Baja**
Un `getProduct()` por ítem del carrito (N round-trips, en paralelo).
*Sugerencia:* endpoint batch por lista de ids.

**R-9 — `PaymentWebhookController.php:196-208` · Baja**
Loop por ítem con `getDocument`+`updateDocument` secuenciales para stock (bloquea la respuesta del webhook).
*Sugerencia:* aceptable por volumen; si crece, batch write/transaction.

**R-10 — `ShipmentController.php:76, 123` · Baja**
`listDocuments('orders', 100)` para selects sin caché ni filtro.
*Sugerencia:* filtrar órdenes elegibles en query.

> **Bien resuelto:** el front usa Server Components con `Promise.all`, `next/image` con `sizes`/`fill`, `revalidate` en catálogo, `key={p.id}`. No se detectaron re-renders inútiles ni imports pesados sin lazy.

---

## 5. PWA

> El enunciado (líneas 10-24) **exige** PWA: manifest completo, service worker, notificaciones push, offline razonable, instalable, Lighthouse a11y 100. **Estado actual: infraestructura PWA totalmente ausente.**

**P-1 — Web App Manifest AUSENTE · Crítica**
No existe `frontend/public/manifest.json` ni `src/app/manifest.ts`. `public/` solo tiene los SVG default de Next.
*Impacto:* la app no es instalable; falla el criterio "installable" de Lighthouse.
*Sugerencia:* `src/app/manifest.ts` (API nativa de Next 16) con name/short_name/icons/start_url/display/theme_color/background_color.

**P-2 — Iconos PWA AUSENTES · Crítica**
No hay PNG 192/512 ni maskable; solo `favicon.ico`.
*Impacto:* sin iconos ≥192 y ≥512 el navegador no ofrece "Instalar app".
*Sugerencia:* generar iconos 192/512 normal + maskable.

**P-3 — Service Worker AUSENTE · Crítica**
No hay `sw.js`/`sw.ts` ni `navigator.serviceWorker.register`.
*Impacto:* sin SW no hay caché, ni offline, ni push (pilar de PWA).
*Sugerencia:* **Serwist** (`@serwist/next`, sucesor de next-pwa compatible con Next 16 / App Router / Turbopack) + `src/app/sw.ts`.

**P-4 — Dependencia PWA AUSENTE en package.json · Crítica**
Sin `next-pwa`/`@serwist/next`/`workbox-*`.
*Sugerencia:* `pnpm add @serwist/next && pnpm add -D serwist`.

**P-5 — Estrategia de caché AUSENTE · Crítica**
Sin SW no hay runtime caching (cache-first assets / network-first datos).
*Sugerencia:* en `sw.ts`: `CacheFirst` para imágenes/estáticos (incluir `res.cloudinary.com`), `NetworkFirst`/`StaleWhileRevalidate` para endpoints de productos.

**P-6 — Navegación offline AUSENTE · Alta**
Sin página `/offline` de fallback ni precache de rutas del catálogo.
*Impacto:* sin conexión la app queda inutilizable; incumple "ver catálogo cacheado".
*Sugerencia:* precache app-shell + `NetworkFirst` con fallback a `/offline` y datos cacheados.

**P-7 — Notificaciones push (cliente) AUSENTES · Alta**
Sin `pushManager.subscribe`, `Notification.requestPermission` ni handlers `push`/`notificationclick`.
*Sugerencia:* tras registrar SW, `requestPermission` + `pushManager.subscribe({applicationServerKey})` + enviar subscription al backend; listeners en `sw.ts`.

**P-8 — Backend push / VAPID AUSENTE · Alta**
Sin `web-push`, claves VAPID, ni endpoint de suscripción.
*Impacto:* aun con cliente suscripto, no hay quién dispare las push.
*Sugerencia:* `web-push`, par VAPID, `POST /push/subscribe`, disparar notificación al crear producto/promo.

**P-9 — Metadata PWA en layout incompleta — `src/app/layout.tsx:18-21` · Media**
`metadata` solo tiene title/description; falta `manifest`, `themeColor`, `appleWebApp`.
*Sugerencia:* añadir `manifest`, `appleWebApp` en `metadata` y `themeColor` en `export const viewport`.

**P-10 — Accesibilidad Lighthouse 100 NO verificada · Media**
Sin evidencia de auditoría a11y (no se pudo ejecutar Lighthouse en análisis estático). El enunciado exige score 100.
*Sugerencia:* correr Lighthouse sobre el build, corregir contraste/labels/alt/roles.

---

## 6. Deuda técnica

**D-1 — Pipeline Vite/Tailwind muerto — `backend/vite.config.js` + `resources/css`, `resources/js` · Alta**
No hay ninguna directiva `@vite` en ningún Blade; el admin carga Tailwind por CDN. Build system entero que nunca se ejecuta. Arrastra devDeps inútiles (`vite`, `laravel-vite-plugin`, `@tailwindcss/vite`, `tailwindcss`, `axios`, `concurrently`).
*Sugerencia:* decidir: adoptar `@vite(...)` en layouts, o eliminar `vite.config.js`/`resources/*`/esos devDeps.

**D-2 — `FirebaseAuthService.php` (archivo completo, muerto) · Media**
0 referencias en el codebase; único consumidor de `Kreait\Firebase\Factory`. El auth real es `FirestoreUserProvider` + Socialite.
*Sugerencia:* borrar archivo + dependencia kreait juntos.

**D-3 — `composer.json:12` `kreait/laravel-firebase` sin uso · Media**
Solo lo usa `FirebaseAuthService` (muerto).
*Sugerencia:* `composer remove kreait/laravel-firebase` tras borrar el service.

**D-4 — `composer.json:11` `google/cloud-firestore` sin uso · Media**
SDK pesado (grpc/protobuf) nunca usado; Firestore se accede por HTTP crudo. Solo se usa `ServiceAccountCredentials` de `google/auth` (transitiva).
*Sugerencia:* requerir `google/auth` directo y quitar `google/cloud-firestore`.

**D-5 — `layouts/admin.blade.php:14` Tailwind Play CDN · Media**
`<script src="cdn.tailwindcss.com">` (no apto para producción, emite warning).
*Impacto:* dependencia de red externa en runtime, FOUC, sin purge.
*Sugerencia:* compilar Tailwind vía Vite y servir CSS local (resuelve también D-1).

**D-6 — Migración `2026_04_22_..._create_sessions_table.php` inaplicable · Media**
Tabla `sessions` (driver database) pero `SESSION_DRIVER=file` y `DB_CONNECTION=firestore`. No hay motor SQL.
*Sugerencia:* eliminar la migración o documentar el motor SQL real.

**D-7 — `DatabaseSeeder.php:18` + `UserFactory.php` inoperantes · Baja**
Seeder/factory Eloquent que no persisten nada (usuarios viven en Firestore). Línea comentada residual.
*Sugerencia:* borrar o reescribir contra Firestore.

**D-8 — `config/mail.php` + `MAIL_*` sin consumidor · Baja**
Ni un `Mail::send`/Mailable en toda la app.
*Sugerencia:* quitar `MAIL_*` del `.env.example` si no habrá emails.

**D-9 — `.env.example` con vars muertas · Baja**
`VITE_APP_NAME`, `AWS_*`, `MEMCACHED_HOST`, `REDIS_*`, `BROADCAST_CONNECTION` sin adopción.
*Sugerencia:* podar a las vars reales (Firestore, Cloudinary, Google, MercadoPago, Session).

**D-10 — `backend/api.json` (1838 líneas) commiteado · Baja**
Spec OpenAPI generada por Scramble en git (artefacto generado).
*Sugerencia:* gitignorar y generar on-demand, o documentar que es snapshot intencional.

**D-11 — `frontend/src/components/ui/dialog.tsx` huérfano · Baja**
Componente shadcn sin importadores.
*Sugerencia:* eliminarlo (re-agregable con la CLI de shadcn).

**D-12 — Dos controllers de catálogo (`CatalogController` + `CatalogApiController`) · Baja**
Split arbitrario de endpoints `/catalog/*`; smell de organización (no código muerto).
*Sugerencia:* unificar en un `CatalogApiController`.

> **Nota de postura crítica:** el "logging temporal del webhook MP" que se sospechaba **NO existe** — el único `logger()->warning` (`PaymentWebhookController:79`) es legítimo (firma inválida), y el `\Log::debug` de `FirestoreService:392` está guardado tras `config('app.debug')`. Ejemplo de hallazgo que la intuición marcaría pero el código desmiente.

---

## Top 3 prioridades (impacto / esfuerzo)

1. **PWA completa (P-1…P-8) — Crítica, esfuerzo medio.** Es requisito **obligatorio y calificable** del final y hoy está 100% ausente. Sin esto no se aprueba el agregado. Ruta clara y estándar: Serwist para el SW/caché/offline + manifest + iconos + flujo push con VAPID en el backend. Máxima prioridad porque bloquea la nota, no solo la calidad.

2. **N+1 de descuentos en catálogo (R-1) — Crítica, esfuerzo bajo.** Un solo método (`DiscountService::decorate`) dispara N requests HTTP a Firestore por cada listado que ve el cliente. El fix es reusar el `activeDiscounts()` cacheado que **ya existe** — pocas líneas, alto impacto en latencia y costo de lecturas Firestore. Mejor relación impacto/esfuerzo del reporte.

3. **Errores silenciosos + contrato de estados roto (C-4 + A-4) — Alta, esfuerzo bajo.** `getDocument` enmascara fallos de Firestore como "doc vacío" (datos que "desaparecen" sin log) y los estados de orden no coinciden entre front y back (el cliente ve `in_process` como texto crudo). Dos bugs de correctitud visibles al usuario, ambos con fix acotado; encajan directo con los "déficits técnicos" que el enunciado pide resolver.

---

## Cómo usar este reporte en la defensa (guía)

El enunciado califica tres cosas (líneas 32-36):

- **Comprensión del hallazgo:** para cada uno, poder explicar con palabras propias qué señala y por qué importa. Este reporte da la referencia archivo:línea + impacto concreto para estudiarlo.
- **Cosas nuevas aprendidas:** temas que probablemente no se vieron en la cursada — Serwist/service workers, VAPID/web-push, aggregation queries de Firestore, N+1 sobre REST, CORS con comodines, HttpOnly vs XSS, headers de seguridad (CSP/HSTS/X-Frame-Options).
- **Postura crítica frente a la IA:** ejemplos donde el reporte se auto-corrige (logging temporal inexistente, front bien resuelto, lista de "bien resuelto" en seguridad/rendimiento) — mostrar que se validó cada hallazgo contra el código y no se aceptó a ciegas.
