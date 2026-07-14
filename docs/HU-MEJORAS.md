# Historias de Usuario — Mejoras y correcciones detectadas en revisión de código

> Fecha de revisión: 2026-07-13
> Alcance: `backend/` (Laravel + Firestore) y `frontend/` (Next.js).
> Cada HU incluye el contexto del problema encontrado, referencias al código y criterios de aceptación.

## Resumen priorizado

| ID | Carpeta | Título | Prioridad |
|----|---------|--------|-----------|
| HU-B01 | backend | Restringir el panel `/admin` por rol (hoy entra cualquier usuario autenticado) | 🔴 Crítica |
| HU-B02 | backend | Endurecer el login con Google (cualquier Gmail crea usuario en `users`) | 🔴 Crítica |
| HU-B03 | backend | Refresh token real: hoy cualquier access token se renueva infinitamente | 🔴 Alta |
| HU-B04 | backend | Descuento y reposición de stock consistente (efectivo / cancelaciones) | 🔴 Alta |
| HU-B05 | backend | Unificar vocabulario de estados de orden y pago entre panel, API y tienda | 🔴 Alta |
| HU-B06 | backend | Validar las operaciones del carrito (cantidades negativas, productos inexistentes) | 🟠 Media |
| HU-B07 | backend | Registro: email duplicado entre `users` y `clients` + normalización | 🟠 Media |
| HU-B08 | backend | Webhook MP: firma obligatoria en producción y comparación de montos con tolerancia | 🟠 Media |
| HU-B09 | backend | Rate limiting específico para login/registro | 🟠 Media |
| HU-B10 | backend | Fix: handler de `DecryptException` nunca se ejecuta (falta el import) | 🟠 Media |
| HU-B11 | backend | Cron de expiración: no cancelar órdenes en efectivo válidas y paginar | 🟡 Baja |
| HU-B12 | backend | Endpoint `GET /auth/me` para revalidar sesión | 🟡 Baja |
| HU-F01 | frontend | Login con Google en la tienda | 🔴 Alta |
| HU-F02 | frontend | Fix open redirect en `/login?redirect=` | 🔴 Alta |
| HU-F03 | frontend | Revalidar la sesión guardada en localStorage contra el backend | 🟠 Media |
| HU-F04 | frontend | Manejo consistente de expiración de sesión (401) y refresh de token | 🟠 Media |
| HU-F05 | frontend | Control de stock en carrito y botón "agregar al carrito" | 🟠 Media |
| HU-F06 | frontend | Reintentar el pago desde el detalle de la orden | 🟠 Media |
| HU-F07 | frontend | Robustecer el checkout ante fallas parciales (orden creada, pago no iniciado) | 🟡 Baja |
| HU-F08 | frontend | Carrito enriquecido: deduplicar requests y distinguir "producto no disponible" | 🟡 Baja |
| HU-F09 | frontend | Web App Manifest para instalación en Android/Chrome (requisito PWA de cátedra) | 🔴 Alta |
| HU-F10 | frontend | Íconos de la app (192/512, maskable y apple-touch-icon) | 🔴 Alta |
| HU-F11 | frontend | Metadata iOS y theme-color para "Agregar a inicio" en Safari | 🟠 Media |
| HU-F12 | frontend | Service worker con fallback offline básico | 🟠 Media |

---

## Backend (`backend/`)

### HU-B01 — Restringir el panel `/admin` por rol

**Como** administrador del sistema, **quiero** que solo usuarios con rol `admin` o `editor` puedan acceder a cualquier ruta de `/admin`, **para** que un cliente autenticado no pueda ver información interna del negocio.

**Problema encontrado**
- El grupo `/admin` en `routes/web.php:32` solo usa el middleware `auth`. Un usuario con rol `cliente` (por ejemplo, alguien que entró con **cualquier cuenta de Gmail** vía Google, ver HU-B02) puede abrir:
  - `GET /admin` → `DashboardController@index` no tiene ningún chequeo de rol y muestra KPIs de facturación, clientes y órdenes.
  - `GET /admin/settings` → `SettingController@index` tampoco chequea rol (solo el `update` tiene middleware `admin`).
  - `GET /admin/export/{entity}` (CSV de clientes, órdenes, etc.) está bajo `auth` solamente (`routes/web.php:120-124`).
- Los CRUD (categorías, productos, etc.) se salvan porque sus policies exigen `admin|editor`, pero la protección queda librada a que cada controller llame a `authorize`.

**Criterios de aceptación**
- [x] Existe un middleware de rol (ej. `role:admin,editor`) aplicado a TODO el grupo `/admin`; un `cliente` autenticado recibe 403 en cualquier ruta del panel, incluidos dashboard, settings y exportaciones CSV.
- [x] Las rutas que hoy usan `admin` (solo admin) mantienen su restricción más estricta.
- [x] Test de feature que verifica que un usuario `cliente` recibe 403 en `/admin`, `/admin/settings` y `/admin/export/orders`.

---

### HU-B02 — Endurecer el login con Google del panel

**Como** dueño del negocio, **quiero** que el login con Google no le dé acceso ni cree usuarios internos a cualquier persona con una cuenta de Gmail, **para** reducir la superficie de ataque del panel.

**Problema encontrado**
- `AuthController::handleGoogleCallback` (`backend/app/Http/Controllers/AuthController.php:69-152`): si el email no existe, **crea un documento en la colección `users`** (la de usuarios internos del panel) con rol `cliente` y `active: true`. Combinado con HU-B01, esa persona entra al dashboard.
- `allowed_domains` es opcional: si no está configurado, no hay ninguna restricción de dominio.
- Los clientes del e-commerce viven en la colección `clients`, pero el alta por Google los mete en `users`: quedan mezclados usuarios internos con desconocidos.

**Criterios de aceptación**
- [x] Primer login con Google de un email desconocido NO crea documentos en `users`. **Decisión: opción (a)** — se rechaza el acceso salvo que el email ya exista en `users` (o esté en `admin_emails`).
- [x] La promoción a `admin` vía `services.google.admin_emails` sigue funcionando.
- [x] Si `allowed_domains` está vacío en producción, se loguea un warning (en cada callback de Google).
- [ ] Nota de coordinación: esta HU define el backend del login con Google **de la tienda** (HU-F01): hace falta un endpoint que complete el flujo OAuth y devuelva un token de API (`api_tokens`) como el de `/auth/login`, en lugar de una sesión web.

---

### HU-B03 — Refresh token real con expiración y rotación

**Como** responsable de seguridad, **quiero** que un token robado no pueda renovarse para siempre, **para** limitar el impacto de una filtración.

**Problema encontrado** (`backend/app/Http/Controllers/Api/V1/AuthApiController.php:241-276`)
- `POST /auth/refresh` acepta **el propio access token** como `refresh_token` (busca en la misma colección `api_tokens` por el mismo hash).
- No verifica `expires_at`: un token vencido (o robado) se puede "refrescar" indefinidamente, extendiendo su vida 30 días cada vez.

**Criterios de aceptación**
- [ ] El login/registro emite un par access token (TTL corto) + refresh token (TTL más largo), guardados como registros distintos o con campo `type`.
- [ ] `POST /auth/refresh` solo acepta refresh tokens, rechaza los vencidos y rota el refresh token usado (el viejo queda inválido).
- [ ] Un access token no puede usarse como refresh token, y viceversa.
- [ ] El frontend recibe ambos tokens en la respuesta de login/registro (coordinar con HU-F04).

---

### HU-B04 — Stock consistente: efectivo y cancelaciones

**Como** administrador, **quiero** que el stock refleje las ventas por cualquier medio de pago y se reponga al cancelar, **para** no vender productos que no tengo ni bloquear stock fantasma.

**Problema encontrado**
- El stock se descuenta **únicamente** cuando el webhook de MP acredita un pago (`PaymentWebhookController.php:131-145`, con la bandera `stock_decremented`).
- Las órdenes en **efectivo** nunca descuentan stock: ni al crearse (`OrderApiController::store`), ni cuando el admin cambia el estado (`OrderController::updateStatus` en `backend/app/Http/Controllers/OrderController.php:182-212` solo escribe `status`).
- La **cancelación** (`OrderApiController::cancel`, cron `expireOrders`, y el panel) no repone stock aunque `stock_decremented = true`; una orden MP pagada y luego cancelada deja el stock descontado y sin reembolso registrado.

**Criterios de aceptación**
- [x] **Decisión: al confirmar desde el panel.** El stock de órdenes no-MP se descuenta al pasar a `confirmed` (o cualquier estado aceptado posterior), con la lógica idempotente compartida en `App\Services\StockService` (`stock_decremented`). Para MP lo sigue haciendo el webhook.
- [x] Cancelar una orden con `stock_decremented = true` repone el stock (y limpia la bandera), desde la API, el panel y el cron.
- [x] **Decisión: cliente bloqueado, panel sí.** El cliente recibe 422 al cancelar una orden con pago aprobado; el admin puede cancelarla desde el panel y queda marcada `refund_pending = true` para gestión manual del reembolso.
- [x] Tests: efectivo confirmada descuenta una sola vez (`StockFlowTest`); cancelación repone (panel, API y cron); doble webhook no descuenta dos veces (se mantiene verde).

---

### HU-B05 — Unificar estados de orden y pago

**Como** administrador y como cliente, **quiero** ver los mismos estados de orden y pago en el panel y en la tienda, **para** que los filtros, badges y reportes no muestren datos incoherentes.

**Problema encontrado** — hay **tres vocabularios distintos**:
- Panel admin (`OrderController::statuses()`, línea 78): `pending, confirmed, in_process, completed, cancelled`; estados de pago `pending, paid, overdue`.
- API / webhook: escribe `payment_status` con `approved, rejected, refunded, pending` (`PaymentWebhookController::mapStatus`).
- Tienda (`frontend/src/lib/order-status.ts`): espera `processing, shipped, delivered` (que el panel nunca setea) y pagos `approved, completed, failed`.

Consecuencias concretas:
- Una orden pagada por MP queda con `payment_status: approved`, que el panel no sabe mostrar ni filtrar (espera `paid`).
- El dashboard (`DashboardController@index`) calcula "facturación mensual" con `status in (completed, in_process) || payment == 'paid'`: **nunca cuenta las ventas por Mercado Pago**, no filtra por mes, y usa un fallback `paymentStatus` camelCase que sugiere datos legacy inconsistentes.
- Si el admin pone `in_process`/`completed`, la tienda muestra el string crudo (no tiene label).

**Criterios de aceptación**
- [x] Enum único en `App\Support\OrderStatus`: `status` = `pending, confirmed, processing, shipped, delivered, cancelled`; `payment_status` = `pending, approved, rejected, refunded`.
- [x] Panel, API, webhook y frontend usan el enum. Los datos legacy (`in_process`, `completed`, `paid`, `overdue`, `paymentStatus` camelCase) se mapean al leer vía `OrderStatus::normalize()/normalizePayment()`; el panel dejó de escribir `paymentStatus` camelCase (ahora `payment_status`).
- [x] La facturación del dashboard cuenta `payment_status = approved` + efectivo aceptado, del mes corriente (`DashboardControllerTest`).
- [x] Selects/filtros del panel (`_form_fields.blade.php`, filtros del índice, modal JS) toman las opciones de `OrderStatus`.

---

### HU-B06 — Validar operaciones del carrito

**Como** backend, **quiero** rechazar operaciones de carrito inválidas, **para** que el carrito no acumule datos basura que después rompen el checkout.

**Problema encontrado** (`backend/app/Http/Controllers/Api/V1/CartApiController.php:57-124`)
- No hay ninguna validación: `product_id` puede ser `null`/vacío, `quantity` negativa o cero, `action` cualquier string (cae fuera del switch y guarda el carrito igual).
- No se verifica que el producto exista, esté activo, ni que la cantidad no supere el stock.
- `update` con `quantity <= 0` deja el ítem con cantidad 0 en vez de eliminarlo.

**Criterios de aceptación**
- [ ] FormRequest con reglas: `action in (add, update, remove, clear)`, `product_id` requerido (salvo `clear`), `quantity` entera `min:1` para `add`/`update`.
- [ ] `add`/`update` verifican producto existente y activo (422 si no), y capean la cantidad al stock disponible (o devuelven 422, decidir en refinamiento).
- [ ] `update` con cantidad 0 elimina el ítem.
- [ ] Tests de los casos inválidos.

---

### HU-B07 — Registro: emails duplicados entre colecciones y normalización

**Como** cliente, **quiero** que mi registro falle claramente si mi email ya existe en el sistema, **para** no crear una cuenta con la que después no puedo iniciar sesión.

**Problema encontrado** (`AuthApiController::register`, línea 146)
- El chequeo de duplicados solo mira `clients`. Si el email existe en `users`, el registro tiene éxito, pero `authenticateUser` (línea 315) busca primero en `users`: ese cliente **nunca va a poder loguearse** con su contraseña.
- El email no se normaliza a minúsculas en registro/login (el flujo de Google sí lo hace), así que `Juan@mail.com` y `juan@mail.com` pueden coexistir.

**Criterios de aceptación**
- [ ] El registro rechaza emails presentes en `users` **o** `clients` (comparación case-insensitive).
- [ ] Registro y login normalizan el email (`strtolower`/trim) antes de consultar y persistir.
- [ ] Test: registrar un email existente en `users` devuelve 422.

---

### HU-B08 — Webhook MP: firma estricta en producción y comparación de montos con tolerancia

**Como** responsable del negocio, **quiero** que el webhook de pagos sea estricto en producción, **para** minimizar la ventana de conciliaciones espurias.

**Problema encontrado** (`PaymentWebhookController.php`)
- Si la firma `x-signature` no valida, hoy solo se loguea un warning y **se procesa igual** (líneas 78-82; el propio comentario dice "para prod estricto, convertir en return 400"). La verificación contra la API de MP mitiga, pero la decisión quedó pendiente.
- La validación de monto usa igualdad estricta de floats: `(float) $order['total_amount'] !== (float) $paidAmount` (línea 114); una diferencia de redondeo de centavos rechaza un pago legítimo.

**Criterios de aceptación**
- [ ] Config `services.mercadopago.strict_webhook` (env): en `true`, firma inválida ⇒ 400 sin procesar. Activada en producción, apagable en sandbox.
- [ ] Comparación de montos con tolerancia (ej. `abs($a - $b) > 0.01`).
- [ ] Test del rechazo por firma en modo estricto y de la tolerancia de centavos.

---

### HU-B09 — Rate limiting para autenticación

**Como** responsable de seguridad, **quiero** limitar los intentos de login/registro por IP y por email, **para** frenar fuerza bruta y registros masivos.

**Problema encontrado**
- `/auth/login`, `/auth/register` y `/auth/refresh` solo tienen el `throttle:api` genérico del grupo (`routes/api.php:33`). El login web (`routes/web.php:26`) no tiene throttle propio ni lockout.

**Criterios de aceptación**
- [ ] Limiter específico (ej. `throttle:5,1` por IP+email) en login API y web, y algo más laxo en registro/refresh.
- [ ] Respuesta 429 con mensaje claro; test que verifica el 429 tras exceder intentos.

---

### HU-B10 — Fix: handler de `DecryptException` muerto

**Como** usuario del panel, **quiero** que rotar la `APP_KEY` no me deje viendo un error 500, **para** poder volver a loguearme sin borrar cookies a mano.

**Problema encontrado** (`backend/bootstrap/app.php:61`)
- El closure `renderable` tipa `DecryptException` **sin `use`**: referencia `\DecryptException` (clase inexistente), así que el handler jamás matchea y el 500 que pretendía arreglar sigue ocurriendo. Es exactamente el tipo de bug silencioso que no falla al deployar.

**Criterios de aceptación**
- [ ] Se importa `Illuminate\Contracts\Encryption\DecryptException` y el flujo descrito en el comentario funciona: cookie corrupta ⇒ redirect con cookies limpias.
- [ ] Test (o verificación manual documentada) simulando una cookie cifrada con otra key.

---

### HU-B11 — Cron de expiración: respetar efectivo y paginar

**Como** cliente que eligió pagar en efectivo, **quiero** que mi orden no se cancele sola a las 48 h, **para** poder coordinar la entrega/pago con el vendedor.

**Problema encontrado** (`CronController::expireOrders`)
- Cancela toda orden `pending/pending` con más de 48 h **sin distinguir método de pago**: una orden en efectivo legítima (que se paga al retirar) se cancela sola.
- Lee un máximo de 500 documentos ordenados por `created_at` sin paginar: con historial grande, órdenes viejas pueden quedar fuera del corte para siempre.

**Criterios de aceptación**
- [ ] Solo expiran órdenes cuyo método de pago requiere pago online (`mercado_pago`), o el plazo para efectivo es configurable y distinto.
- [ ] El job pagina hasta procesar todas las órdenes pendientes del rango.

---

### HU-B12 — Endpoint `GET /auth/me`

**Como** frontend, **quiero** un endpoint que devuelva el usuario del token actual, **para** revalidar la sesión al cargar la app (ver HU-F03).

**Criterios de aceptación**
- [ ] `GET /api/v1/auth/me` (protegido con `auth.api`) devuelve `id, name, email, role, active` frescos desde Firestore.
- [ ] 401 si el token es inválido/vencido; documentado en OpenAPI como el resto.

---

## Frontend (`frontend/`)

### HU-F01 — Login con Google en la tienda

**Como** cliente, **quiero** iniciar sesión en la tienda con mi cuenta de Google, **para** comprar sin crear otra contraseña.

**Problema encontrado**
- El login con Google existe **solo en el panel Blade del backend** (`/auth/google`), que crea sesión web, no un token de API. La tienda Next.js (`frontend/src/app/login/page.tsx`) solo ofrece email/contraseña; no hay ningún rastro de Google en `frontend/src`.

**Criterios de aceptación**
- [ ] Botón "Continuar con Google" en `/login` y `/registro`.
- [ ] Al completar el flujo OAuth, el frontend termina con un token de API + user en el mismo formato que `login()` (persistido vía `AuthProvider.persist`), y el usuario queda en la colección `clients` con rol `cliente`.
- [ ] Maneja errores del flujo (popup cerrado, email sin permisos) con toasts claros.
- [ ] **Dependencia:** endpoint backend del flujo (HU-B02). Decidir en refinamiento la mecánica: redirect a `/auth/google` del backend con `?mode=api&redirect=...` que vuelva con token, o Google Identity Services en el cliente + endpoint que verifique el `id_token`.

---

### HU-F02 — Fix open redirect en `/login?redirect=`

**Como** usuario, **quiero** que el link de login no pueda mandarme a un sitio externo, **para** no caer en phishing tras autenticarme.

**Problema encontrado** (`frontend/src/app/login/page.tsx:24,37`)
- `router.push(params.get("redirect") || "/")` sin validar: `/login?redirect=https://evil.com` navega fuera del sitio después de un login exitoso.

**Criterios de aceptación**
- [x] Solo se acepta un `redirect` que sea path interno (empieza con `/` y no con `//`); cualquier otro valor cae a `/`.
- [x] Aplicado también en cualquier otro consumidor del param (verificado: `registro` no usa el param; `login/page.tsx` era el único consumidor).

---

### HU-F03 — Revalidar la sesión persistida

**Como** cliente, **quiero** que la app refleje mi estado real de cuenta al abrirla, **para** no operar con datos viejos.

**Problema encontrado** (`frontend/src/context/auth-context.tsx:36-49`)
- Al montar, el user se restaura **solo desde localStorage** y se confía por 30 días: si el admin desactivó la cuenta o cambió el rol/nombre, la UI no se entera hasta que un request devuelva 401.

**Criterios de aceptación**
- [ ] Al montar con token presente, se llama a `GET /auth/me` (HU-B12) y se actualiza/descarta el user según la respuesta.
- [ ] Mientras revalida, la UI usa el user cacheado (sin flash de logout).
- [ ] Cuenta inactiva ⇒ logout completo con mensaje.

---

### HU-F04 — Expiración de sesión y refresh consistentes

**Como** cliente, **quiero** que la sesión se renueve sola o me avise claramente que expiró, **para** no perder un checkout a mitad de camino.

**Problema encontrado**
- El token dura 30 días y el frontend **no usa** el endpoint de refresh: cuando vence, cualquier acción falla.
- Inconsistencia: `apiFetch` ante 401 limpia todo y emite `auth:expired` (`frontend/src/lib/api.ts:61-70`), pero `apiFetchRaw` solo limpia la cookie sin avisar al `AuthProvider` (líneas 114-117) — la UI puede quedar "logueada" con user en localStorage y sin token.

**Criterios de aceptación**
- [ ] `apiFetchRaw` dispara el mismo flujo `auth:expired` ante 401 (salvo en el propio login, donde 401 = credenciales inválidas).
- [ ] Con HU-B03 implementada: ante 401 se intenta un refresh transparente una vez antes de desloguear.
- [ ] Redirigir a `/login?redirect=<ruta actual>` preservando el destino.

---

### HU-F05 — Control de stock en la UI de carrito

**Como** cliente, **quiero** que la tienda no me deje pedir más unidades de las disponibles, **para** no descubrir el error recién al confirmar el pedido.

**Problema encontrado**
- Ni `add-to-cart-button.tsx` ni la página `carrito/` consideran `product.stock`: se puede agregar/incrementar sin tope y el error aparece recién como 422 del `POST /orders` ("Stock insuficiente"), con mensaje que muestra el `product_id` crudo.

**Criterios de aceptación**
- [ ] El botón de agregar y el stepper del carrito se limitan al stock del producto (deshabilitado + hint "Sin stock"/"Máx. N").
- [ ] Si igual llega un 422 de stock del backend, el mensaje muestra el **nombre** del producto y linkea al carrito para corregir.
- [ ] Coordinado con la validación server-side (HU-B06), que es la fuente de verdad.

---

### HU-F06 — Reintentar el pago desde el detalle de la orden

**Como** cliente que volvió de Mercado Pago sin pagar (o cuyo pago fue rechazado), **quiero** un botón "Pagar ahora" en el detalle de mi orden, **para** completar el pago sin crear otra orden.

**Problema encontrado**
- El backend ya lo soporta (`POST /orders/{id}/pay` acepta `payment_status` `pending|rejected`), y el flujo de MP deja la orden pendiente a propósito, pero `cuenta/ordenes/[id]/page.tsx` no ofrece ninguna forma de reintentar: el cliente queda con una orden pendiente sin salida (hasta que el cron la cancela a las 48 h).

**Criterios de aceptación**
- [ ] En el detalle de una orden `mercado_pago` con status `pending` y pago `pending|rejected`, aparece "Pagar ahora" que llama a `payOrder(id)` y abre el `init_point` (mismo patrón de pestaña nueva del checkout).
- [ ] El botón no aparece para órdenes pagadas, canceladas ni en efectivo.

---

### HU-F07 — Checkout robusto ante fallas parciales

**Como** cliente, **quiero** que si el pago no pudo iniciarse igual pueda ver mi orden y reintentar, **para** no quedar en un estado confuso.

**Problema encontrado** (`frontend/src/app/checkout/page.tsx:69-125`)
- Si `createOrder` tiene éxito pero `payOrder` falla, el `catch` genérico solo muestra un toast y cierra la pestaña: la orden quedó creada pero el usuario no lo sabe ni tiene link a ella.
- El cupón se borra de localStorage apenas se crea la orden (línea 94), incluso si el pago de MP nunca se inicia: al reintentar comprando de nuevo, el cupón "desapareció" (aunque la orden creada sí lo tiene aplicado — comportamiento a documentar o revisar).

**Criterios de aceptación**
- [ ] Si la orden se creó pero el inicio de pago falló, se redirige al detalle de la orden con un aviso "No pudimos iniciar el pago" + botón de reintento (HU-F06).
- [ ] Definir y documentar el comportamiento del cupón en ese escenario.

---

### HU-F08 — Carrito enriquecido: dedupe y productos no disponibles

**Como** cliente, **quiero** que el carrito distinga un producto sin datos de un error de red, **para** entender por qué un ítem aparece "—".

**Problema encontrado** (`frontend/src/hooks/use-enriched-cart.ts`)
- El `Map` de caché no deduplica de verdad: los `getProduct` corren en paralelo y el `cache.has` se evalúa antes de que ningún fetch termine.
- Un producto eliminado/desactivado queda como `product: null` y se renderiza "—" con precio 0, contaminando los totales silenciosamente.

**Criterios de aceptación**
- [ ] Un solo fetch por `product_id` (dedupe por promesa).
- [ ] Ítems cuyo producto ya no existe/está inactivo se marcan como "No disponible" con acción de quitar del carrito, y se excluyen del subtotal.

---

## PWA — App instalable desde el teléfono (`frontend/`)

> Requisito de cátedra: la parte cliente (React/Next.js) debe ser responsive (✅ cumplido) y **quedar instalable desde los teléfonos** (❌ pendiente — no existe manifest, íconos ni service worker en el proyecto). El deploy ya está en HTTPS (Vercel), prerequisito cubierto.
>
> **Orden sugerido:** HU-F10 (íconos) → HU-F09 (manifest, que los referencia) → HU-F11 → HU-F12. Las dos primeras alcanzan para que Chrome en Android ofrezca instalar; las otras dos completan iOS y el comportamiento offline.

### HU-F09 — Web App Manifest para instalación

**Como** cliente que navega la tienda desde el teléfono, **quiero** poder instalar MA Piscinas como una app en mi pantalla de inicio, **para** acceder directamente sin abrir el navegador y usarla en pantalla completa.

**Problema encontrado**
- No existe ningún manifest en el proyecto: `frontend/public/` solo contiene los SVGs default de Next.js (`file.svg`, `globe.svg`, `next.svg`, `vercel.svg`, `window.svg`).
- `frontend/src/app/layout.tsx` solo define `title` y `description` en `metadata`; el HTML servido no linkea ningún `manifest.webmanifest`, por lo que Chrome nunca ofrece el prompt de instalación.

**Criterios de aceptación**
- [ ] Existe `frontend/src/app/manifest.ts` (convención de App Router; Next genera y linkea `/manifest.webmanifest` automáticamente, sin plugins).
- [ ] El manifest define como mínimo: `name` ("MA Piscinas"), `short_name`, `description`, `start_url: "/"`, `display: "standalone"`, `background_color`, `theme_color` (coherente con la paleta de la tienda) y el array `icons` con los assets de HU-F10.
- [ ] `GET /manifest.webmanifest` responde 200 en producción y el `<link rel="manifest">` aparece en el HTML.
- [ ] Verificación: en Chrome DevTools → Application → Manifest no se muestran errores ni warnings de instalabilidad, y en un Android real aparece la opción "Instalar app" / "Agregar a pantalla de inicio".

---

### HU-F10 — Íconos de la app

**Como** cliente que instala la app, **quiero** ver el logo de MA Piscinas como ícono en mi pantalla de inicio, **para** identificarla como cualquier otra app y no ver un ícono genérico o roto.

**Problema encontrado**
- No hay ningún ícono de la aplicación en `frontend/public/` ni en `frontend/src/app/` (tampoco `favicon`/`icon` según la convención de App Router).
- Sin íconos de 192×192 y 512×512 el manifest de HU-F09 no pasa los criterios de instalabilidad de Chrome, aunque exista.

**Criterios de aceptación**
- [ ] A partir del logo de MA Piscinas se generan PNGs de **192×192** y **512×512** (propósito `any`) y una variante **maskable** de 512×512 con margen de seguridad (~10% de padding), ubicados en `frontend/public/icons/`.
- [ ] Se genera un **apple-touch-icon** de 180×180 (fondo sólido, sin transparencia) para HU-F11.
- [ ] Los íconos quedan declarados en el array `icons` del manifest con sus `sizes`, `type` y `purpose` correctos.
- [ ] Verificación: el ícono se ve correcto (no recortado ni pixelado) al instalar en un Android real y en el preview "maskable" de DevTools → Application → Manifest.

---

### HU-F11 — Metadata iOS y theme-color

**Como** cliente con iPhone, **quiero** que "Agregar a pantalla de inicio" desde Safari genere una app con el logo y colores correctos, **para** tener la misma experiencia que en Android.

**Problema encontrado**
- Safari/iOS no usa el manifest completo: requiere `apple-touch-icon` y meta tags propios (`apple-mobile-web-app-capable`, título, status bar). Nada de esto existe en `layout.tsx`.
- Tampoco está definido `theme-color`, por lo que la barra del navegador no toma el color de la marca en ninguna plataforma.
- Sin el ícono de Apple, iOS usa un screenshot de la página como ícono, con resultado poco profesional.

**Criterios de aceptación**
- [ ] `frontend/src/app/layout.tsx` exporta `viewport` con `themeColor` acorde a la paleta de la tienda.
- [ ] El `metadata` del layout incluye `appleWebApp` (capable, `title: "MA Piscinas"`, `statusBarStyle`) y referencia el `apple-touch-icon` de 180×180 (vía `icons.apple` o colocándolo como `frontend/src/app/apple-icon.png`, que App Router sirve automático).
- [ ] Verificación en iPhone real o simulador: "Agregar a pantalla de inicio" muestra el logo correcto y, al abrirse, la app corre en modo standalone (sin la barra de Safari).

---

### HU-F12 — Service worker con fallback offline

**Como** cliente con conectividad intermitente, **quiero** que la app instalada abra aunque no tenga señal, **para** ver al menos una pantalla clara de "sin conexión" en lugar del dinosaurio del navegador.

**Problema encontrado**
- No existe ningún service worker: no hay `sw.js` en `public/`, ni registro de SW en el código, ni dependencias tipo Serwist/`next-pwa`/Workbox en `package.json`.
- Chrome moderno ya no exige SW para permitir la instalación, pero sin él la "app" instalada es solo un acceso directo: sin cache, sin offline, y evaluando el requisito "instalable" como PWA queda incompleto.

**Criterios de aceptación**
- [ ] Decidir en refinamiento el enfoque: (a) **Serwist** (sucesor mantenido de `next-pwa`, precache automático de assets) o (b) **`public/sw.js` manual mínimo** (sin dependencias: cachea el shell y una página `/offline`). Para el alcance de la materia, (b) es suficiente.
- [ ] El SW se registra solo en producción (no interferir con HMR en `next dev`).
- [ ] Existe una página/ruta `offline` con estilo de la tienda, y el SW la sirve como fallback de navegación cuando no hay red.
- [ ] Los assets estáticos (íconos, fuentes, CSS/JS del shell) se sirven desde cache cuando no hay conexión. Las llamadas a la API **no** se cachean (los datos de stock, precios y órdenes deben ser siempre frescos — coordinar con HU-F03/HU-F04, que asumen requests reales al backend).
- [ ] Verificación: con la app instalada y el modo avión activado, abrirla muestra la página offline; en DevTools → Application → Service Workers el SW figura activo; Lighthouse (categoría PWA) pasa la auditoría de instalabilidad.

---

## Sugerencia de orden de trabajo

1. **Seguridad de acceso** (bloqueante): HU-B01 → HU-B02 → HU-F02.
2. **Integridad de negocio**: HU-B04 y HU-B05 juntas (tocan los mismos flujos), luego HU-B08 y HU-B11.
3. **Sesiones**: HU-B03 + HU-B12 en backend, después HU-F03 + HU-F04.
4. **Experiencia de compra**: HU-B06 + HU-F05, HU-F06 + HU-F07, HU-F08.
5. **Google en la tienda**: HU-F01 (depende de la decisión tomada en HU-B02).
6. **PWA instalable** (requisito de cátedra, independiente del resto — puede avanzar en paralelo): HU-F10 → HU-F09 (mínimo para el prompt de instalación en Android), luego HU-F11 + HU-F12.
