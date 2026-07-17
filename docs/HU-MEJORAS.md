# Historias de Usuario — Mejoras y correcciones detectadas en revisión de código

> Fecha de revisión: 2026-07-13 · Actualizado 2026-07-15 con las **Pautas del examen final** (`docs/Pautas.Final.AW.2026.pdf`).
> Alcance: `backend/` (Laravel + Firestore) y `frontend/` (Next.js).
> Cada HU incluye el contexto del problema encontrado, referencias al código y criterios de aceptación.

## Checklist contra las pautas del final

| Requisito de las pautas | Estado | HU relacionadas |
|---|---|---|
| Déficits técnicos resueltos (flujo de compra, CRUD, stock, responsive) | 🟡 Parcial | B04 ✅ B05 ✅ · pendientes: B06, F05, F06, F07, F08 |
| PWA instalable | ✅ | F09–F12 (falta verificación iOS de F11) |
| PWA: **notificaciones push** de productos/promos | 🟡 Implementado | HU-B13 ✅ + HU-F13 ✅ (falta verificar en Android real) |
| PWA: **catálogo visible offline** (cacheado) | 🟡 Implementado | HU-F14 ✅ (falta verificar en Android real) |
| **Lighthouse Accesibilidad = 100** | ✅ | HU-F15 — 100 en las 7 páginas clave (15/07/2026, build de producción local) |
| API protegida para que **solo la app React** pueda usarla | ✅ | HU-B14 — app key `X-App-Key` + CORS estricto + throttle (PRs #22/#23, `docs/DECISIONES_SEGURIDAD.md`) |
| **Auditoría IA** con el prompt obligatorio de la cátedra | 🟡 Hecha 16/07 | HT-01 — `docs/AUDITORIA_IA.md` (70 hallazgos, issues #10–#16); re-correr al cierre: se defiende el **último** resultado |
| Defensa con todos los integrantes | — | Coordinar día y hora con la cátedra |

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
| HU-B13 | backend | Notificaciones push: suscripciones + envío con VAPID (pauta final) | 🔴 Alta |
| HU-B14 | backend | Proteger la API para que solo la app React pueda usarla (pauta final) | 🔴 Alta |
| HU-F13 | frontend | Notificaciones push en la tienda: permiso, suscripción y handler en el SW (pauta final) | 🔴 Alta |
| HU-F14 | frontend | Catálogo navegable offline: cachear páginas visitadas (pauta final) | 🔴 Alta |
| HU-F15 | frontend | Accesibilidad: 100 en Lighthouse (pauta final) | 🔴 Alta |
| HT-01 | ambas | Auditoría IA con el prompt obligatorio de la cátedra (pauta final) | 🔴 Alta |

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

### HU-B13 — Notificaciones push: suscripciones y envío (pauta final)

**Como** dueño del negocio, **quiero** poder enviarles notificaciones push a los clientes que instalaron la app (nueva promoción, producto recién incorporado), **para** cumplir el requisito de la pauta final y traer gente de vuelta a la tienda.

**Contexto**
- Requisito textual de las pautas: *"permitir notificaciones push sobre nuestros productos, como alguna promoción nueva o algún producto recién incorporado"*.
- Hoy no existe nada de push en el proyecto: ni almacenamiento de suscripciones, ni claves VAPID, ni librería de envío.
- Contraparte frontend: HU-F13 (suscripción del navegador + handler `push` en el SW).

**Criterios de aceptación**
- [x] Par de claves VAPID en config (`services.webpush`, env `VAPID_PUBLIC_KEY/PRIVATE_KEY/SUBJECT`); la pública se expone vía `GET /api/v1/push/public-key` (el frontend no necesita env var propia). ✅ Vars cargadas en Vercel — verificado 17/07: el endpoint productivo devuelve nuestra clave pública.
- [x] **Decisión: suscripciones anónimas** (broadcast de promos, sin atar al usuario). `POST /push/subscribe` guarda en `push_subscriptions` con `sha256(endpoint)` como doc id (idempotente); `POST /push/unsubscribe` la elimina.
- [x] Envío con `minishlink/web-push` (`App\Services\WebPushSender::broadcast`); las suscripciones vencidas (404/410) se eliminan automáticamente al enviar.
- [x] **Decisión: form en el panel** (`/admin/notifications`, solo admin, link "Notificaciones" en el sidebar): título, mensaje y ruta de destino; muestra cantidad de suscriptos y resultado del envío. Con **plantillas rápidas** (promo, producto nuevo, oferta puntual, envío gratis) que precargan el form y solo piden completar el dato entre corchetes.
- [x] **Fix entrega en Android (17/07)**: el envío ahora va con `urgency: high` y TTL 24 h — con los defaults (urgencia normal, TTL 5 min) FCM difería la notificación con el navegador cerrado (Doze) y la descartaba a los 5 minutos: llegaba solo con la app abierta.
- [x] Tests: `PushApiTest` (public-key, subscribe idempotente, validación, unsubscribe) y `NotificationPanelTest` (403 editor, broadcast con sender mockeado, validación).

---

### HU-B14 — Proteger la API para que solo la app React pueda usarla (pauta final)

**Como** responsable del negocio, **quiero** que la API del backend solo sea consumible desde nuestra aplicación React, **para** cumplir la pauta final ("la API del backend debe estar protegida apropiadamente") y evitar scraping o abuso directo.

**Problema encontrado**
- `backend/config/cors.php` permite `https://*.vercel.app` (cualquier app de Vercel del mundo, no solo la nuestra — además como entrada literal de `allowed_origins`, los wildcards van en `allowed_origins_patterns`) y `http://localhost:*`.
- Los endpoints públicos del catálogo (`/catalog/*`, `/auth/login`, `/auth/register`) son consumibles por cualquier cliente HTTP sin ninguna identificación de la app.

**Criterios de aceptación** *(resuelto por PRs #22 y #23 — ver `docs/DECISIONES_SEGURIDAD.md`)*
- [x] CORS restringido a los orígenes reales (`config/cors.php`): dominios exactos del front (`aplicaciones-web-tienda[-rho].vercel.app` + localhost) y patrón acotado a previews del proyecto en `allowed_origins_patterns`.
- [x] Los endpoints sensibles ya exigen `auth.api` (se mantiene); throttle en login/register (`throttle:auth`) y en toda la API (`throttle:api`).
- [x] **Decisión tomada: opción (a)** — middleware `EnsureAppKey` (alias `app.client`) exige header `X-App-Key` si `APP_PUBLIC_KEY` está seteada (prod); webhook de MP y cron quedan exentos (tienen su propia auth). El front lo manda desde `NEXT_PUBLIC_APP_KEY` en `lib/api.ts`. Documentado en `docs/DECISIONES_SEGURIDAD.md`.
- [x] Test: `tests/Feature/Api/AppKeyTest.php`. ✅ Vars en Vercel verificadas 17/07: la API productiva responde 403 sin `X-App-Key` y el front deployado consume el catálogo sin problema (las claves matchean).

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
- [x] Existe `frontend/src/app/manifest.ts` (convención de App Router; Next genera y linkea `/manifest.webmanifest` automáticamente, sin plugins).
- [x] El manifest define: `name` ("MA Piscinas"), `short_name`, `description`, `start_url: "/"`, `display: "standalone"`, `background_color: #ffffff`, `theme_color: #0284c7` (el `--primary` de la tienda) y el array `icons` con los assets de HU-F10.
- [x] `GET /manifest.webmanifest` responde 200 y el `<link rel="manifest">` aparece en el HTML (verificado con `next build` + `next start` local).
- [x] Verificado en Android real (14/07/2026): Chrome ofreció instalar y la app quedó en la pantalla de inicio.

---

### HU-F10 — Íconos de la app

**Como** cliente que instala la app, **quiero** ver el logo de MA Piscinas como ícono en mi pantalla de inicio, **para** identificarla como cualquier otra app y no ver un ícono genérico o roto.

**Problema encontrado**
- No hay ningún ícono de la aplicación en `frontend/public/` ni en `frontend/src/app/` (tampoco `favicon`/`icon` según la convención de App Router).
- Sin íconos de 192×192 y 512×512 el manifest de HU-F09 no pasa los criterios de instalabilidad de Chrome, aunque exista.

**Criterios de aceptación**
- [x] PNGs de **192×192** y **512×512** (`any`) y **maskable** 512×512 con margen de seguridad en `frontend/public/icons/`. Nota: no había logo fuente en el repo (la marca es el ícono Waves de lucide + texto), así que se generaron desde esa identidad: gradiente azul de la paleta, "MA" y ondas. Si aparece un logo oficial, regenerar.
- [x] **apple-touch-icon** de 180×180 con fondo sólido, sin transparencia.
- [x] Íconos declarados en el manifest con `sizes`, `type` y `purpose` correctos.
- [x] Verificado: instalada en Android real con el ícono correcto (14/07/2026).

---

### HU-F11 — Metadata iOS y theme-color

**Como** cliente con iPhone, **quiero** que "Agregar a pantalla de inicio" desde Safari genere una app con el logo y colores correctos, **para** tener la misma experiencia que en Android.

**Problema encontrado**
- Safari/iOS no usa el manifest completo: requiere `apple-touch-icon` y meta tags propios (`apple-mobile-web-app-capable`, título, status bar). Nada de esto existe en `layout.tsx`.
- Tampoco está definido `theme-color`, por lo que la barra del navegador no toma el color de la marca en ninguna plataforma.
- Sin el ícono de Apple, iOS usa un screenshot de la página como ícono, con resultado poco profesional.

**Criterios de aceptación**
- [x] `layout.tsx` exporta `viewport` con `themeColor: #0284c7`.
- [x] `metadata.appleWebApp` (capable, `title: "MA Piscinas"`, `statusBarStyle`) + `icons.apple` con el apple-touch-icon. Verificado en el HTML servido: `apple-mobile-web-app-title`, `apple-mobile-web-app-status-bar-style`, `mobile-web-app-capable` y el link del ícono.
- [ ] Verificación pendiente: iPhone real o simulador ("Agregar a pantalla de inicio" + modo standalone).

---

### HU-F12 — Service worker con fallback offline

**Como** cliente con conectividad intermitente, **quiero** que la app instalada abra aunque no tenga señal, **para** ver al menos una pantalla clara de "sin conexión" en lugar del dinosaurio del navegador.

**Problema encontrado**
- No existe ningún service worker: no hay `sw.js` en `public/`, ni registro de SW en el código, ni dependencias tipo Serwist/`next-pwa`/Workbox en `package.json`.
- Chrome moderno ya no exige SW para permitir la instalación, pero sin él la "app" instalada es solo un acceso directo: sin cache, sin offline, y evaluando el requisito "instalable" como PWA queda incompleto.

**Criterios de aceptación**
- [x] **Decisión: opción (b)** — `public/sw.js` manual mínimo, sin dependencias.
- [x] El SW se registra solo en producción (`ServiceWorkerRegister` en el layout chequea `NODE_ENV`).
- [x] Página `/offline` con estilo de la tienda; el SW la precachea y la sirve como fallback de navegación sin red.
- [x] Assets estáticos (`/_next/static/`, `/icons/`, css/js/fuentes/imágenes) cache-first; las llamadas a la API **no** se cachean (GET de otro origen y `/api/` quedan excluidos explícitamente).
- [x] Verificado en Android real (14/07/2026): con modo avión, la app instalada abre y muestra la página "Sin conexión". Nota: la categoría PWA de Lighthouse ya no existe (se eliminó en Lighthouse 12, 2024); el equivalente actual es DevTools → Application → Manifest sin warnings de instalabilidad.

---

### HU-F13 — Notificaciones push en la tienda (pauta final)

**Como** cliente que instaló la app, **quiero** recibir notificaciones de promociones y productos nuevos, **para** enterarme de ofertas sin abrir la tienda.

**Contexto**
- Contraparte de HU-B13 (backend guarda suscripciones y envía). El SW actual (`frontend/public/sw.js`) no tiene handlers `push` ni `notificationclick`.

**Criterios de aceptación**
- [x] Botón "Recibir ofertas y novedades" en el footer (`PushToggle`); el permiso se pide recién al tocarlo. Estados: no soportado/dev (oculto), denegado (aviso), activo/inactivo. Solo aparece en producción (igual que el registro del SW).
- [x] Al activar: `Notification.requestPermission()` → `pushManager.subscribe` con la VAPID pública traída de `/push/public-key` → `POST /push/subscribe`. Desactivar hace `unsubscribe()` + `POST /push/unsubscribe`.
- [x] `sw.js` maneja `push` (título, texto, ícono de la app, URL en `data`) y `notificationclick` (enfoca la pestaña si ya está abierta o abre la URL).
- [ ] Probado en Android real: activar desde el footer, enviar desde `/admin/notifications`, llega con la app cerrada y al tocarla abre la página indicada.

---

### HU-F14 — Catálogo navegable offline (pauta final)

**Como** cliente sin conexión, **quiero** poder ver los productos que ya visité, **para** consultar el catálogo aunque no tenga señal.

**Problema encontrado**
- Requisito textual de las pautas: *"permitir navegación offline en un grado razonable. Obviamente no se podrán hacer compras, pero al menos debe poder verse parte del catálogo de productos que ha sido cacheado correctamente"*.
- El SW actual (HU-F12) hace network-first en navegaciones con fallback directo a `/offline`: **ninguna página de contenido queda cacheada**, así que offline no se ve nada del catálogo.
- A favor: las páginas de catálogo (`/productos`, `/productos/[id]`) son server components — el HTML ya trae los datos, no hace falta cachear la API para verlas offline.

**Criterios de aceptación**
- [x] Navegaciones exitosas a páginas públicas se guardan en un cache de páginas (network-first, tope 40 entradas, sin cachear errores ni redirects). Sin red: se sirve la copia cacheada de esa URL; si no hay, `/offline`.
- [x] Las imágenes de productos se cachean cache-first con tope de 60 entradas. Nota: `next/image` las sirve same-origin vía `/_next/image`, así que no hizo falta cachear Cloudinary (otro origen sigue excluido).
- [x] Rutas con datos personales/volátiles (`/carrito`, `/checkout`, `/cuenta/*`, `/login`, `/registro`) **no** se cachean; la API sigue sin cachearse (stock/precios frescos cuando hay red).
- [x] Versión de cache subida a `v2`, separada en tres caches (`static`/`pages`/`images`); `activate` borra cualquier cache viejo.
- [x] **Mejora v3 (17/07)**: las navegaciones SPA del App Router no son `navigate` (son fetches RSC), así que las páginas navegadas con clicks no quedaban cacheadas — offline solo se veía la página de entrada. Ahora: (a) se precachean `/` y `/productos` al instalar (el catálogo se ve offline aunque nunca se haya visitado), y (b) cada fetch/prefetch RSC de una ruta pública dispara el cacheo en segundo plano de su HTML — con solo ver la grilla, las fichas visibles quedan disponibles offline.
- [ ] Verificado en Android real: visitar productos con red → modo avión → home, grilla y fichas vistas se ven; una página no cacheada muestra "Sin conexión".

---

### HU-F15 — Accesibilidad: 100 en Lighthouse (pauta final)

**Como** equipo, **queremos** que la tienda dé 100 en la categoría Accessibility de Lighthouse, **para** cumplir el requisito de la pauta final y que la tienda sea usable con lector de pantalla.

**Contexto**
- Requisito textual: *"La aplicación React debe dar 100 en el test de accesibilidad de Lighthouse o similar según el navegador usado"*.
- Nunca se corrió el audit sobre la tienda. Hallazgos típicos en este stack: botones icon-only sin `aria-label` (carrito, wishlist, menú), contraste insuficiente en `text-muted-foreground` sobre fondos claros, `alt` faltante o genérico en imágenes de productos, orden de headings, `aria-*` en el drawer/modal.

**Criterios de aceptación**
- [x] Auditado con Lighthouse (headless, build de producción local + backend real) el 15/07/2026: home, `/productos`, ficha de producto, `/carrito`*, `/login`, `/registro` y `/offline`. Baseline 87–91.
- [x] **100 en las 7 páginas auditadas** tras corregir: contraste (primary sky-600→sky-700 `#0369a1`, brand-accent cyan-500→cyan-700, `text-green-600`→`700`, `text-amber-600`→`700`, badge `bg-red-600`→`700`), nombre accesible del menú de cuenta en mobile (`max-sm:sr-only`) y del select de orden (`aria-label`), `alt=""` en imágenes decorativas de categorías, jerarquía de headings (h2 en footer y sidebar de filtros, h2 `sr-only` antes de la grilla), links de texto con subrayado permanente, y área táctil ≥24px en los links del footer (`inline-block py-1`).
- [x] `npx tsc --noEmit` y `npm run build` OK. Nota: `theme_color` (manifest y viewport) actualizado a `#0369a1` para mantener la marca coherente.
- [ ] *`/carrito` sin sesión redirige a login (eso es lo que auditó Lighthouse); re-verificar logueado las páginas con sesión (carrito con items, checkout, cuenta) — usan los mismos componentes y paleta, no se esperan diferencias.

---

## HT-01 — Auditoría IA con el prompt de la cátedra (pauta final)

**Como** equipo, **debemos** auditar ambas aplicaciones (Laravel y React) con una IA usando **exactamente el prompt obligatorio** de las pautas, **para** analizarlo en la defensa según los parámetros de la cátedra.

**Contexto (de las pautas)**
- El prompt está en la página 2 de `docs/Pautas.Final.AW.2026.pdf` y es obligatorio usarlo tal cual ("usar todos siempre el mismo prompt"). Cubre 6 categorías en orden: Seguridad, Arquitectura, Calidad de código, Rendimiento, PWA y Deuda técnica; formato markdown con tabla resumen + detalle + "Top 3 prioridades".
- En la defensa evalúan: **comprensión de cada hallazgo** (poder explicarlo con palabras propias, incluso los que no se van a resolver), **cosas nuevas aprendidas**, y **postura crítica frente a la IA** (detectar hallazgos mal fundamentados, exagerados o erróneos).
- Se analiza "el último resultado obtenido" ⇒ conviene correrla al final, cuando el resto de las HU estén cerradas, y guardar el resultado.

**Criterios de aceptación**
- [x] Correr la auditoría con el prompt textual sobre el repo (backend y frontend) y guardar el resultado completo con fecha y modelo → **`docs/AUDITORIA_IA.md`** (16/07/2026, Claude Opus 4.8, 70 hallazgos, prompt en `docs/ENUNCIADO_FINAL.md`).
- [x] Para cada hallazgo relevante: nota propia con explicación y decisión → issues #10–#16 por categoría; seguridad cerrada con decisiones (incluidos no-fixes justificados) en `docs/DECISIONES_SEGURIDAD.md`; PRs #18–#24 resuelven los hallazgos priorizados con review en `docs/REVIEW_PRS_AUDITORIA.md`.
- [ ] Identificar y documentar los hallazgos que consideremos mal fundamentados/exagerados/erróneos, con la justificación técnica (postura crítica) — hay material en `DECISIONES_SEGURIDAD.md` (S-3, S-6); falta hacerlo para el resto de categorías.
- [ ] Lista de "cosas nuevas que aprendimos" (temas no vistos en la cursada).
- [ ] **Re-correr la auditoría al cierre** (con push/offline/a11y ya mergeados) y guardar el resultado — en la defensa se analiza el **último** obtenido.

---

## Checklist de verificación final (pre-defensa)

Pasos concretos para confirmar que todo funciona de punta a punta, en orden:

1. [ ] Pushear el merge de `desarrollo` en `pancho/feat/final-2026` y esperar el redeploy de Vercel (front y back).
2. [ ] **Push en Android real**: abrir `https://aplicaciones-web-tienda-rho.vercel.app`, activar "Recibir novedades" en el footer → enviar una notificación desde `/admin/notifications` del panel → debe llegar **con el navegador cerrado**, y al tocarla abrir la ruta indicada.
3. [ ] **Offline en Android real**: navegar home + `/productos` + alguna ficha → modo avión → volver a abrirlas: deben renderizar desde caché (páginas no visitadas caen al fallback `/offline`).
4. [ ] **Lighthouse en producción**: DevTools → Lighthouse → Accessibility (modo incógnito) sobre home, `/productos` y una ficha del dominio real → debe repetir el 100.
5. [ ] Spot-check de accesibilidad logueado (carrito con items, checkout, cuenta) — misma paleta/componentes, no se esperan diferencias.
6. [ ] **iOS** (si hay iPhone disponible): "Agregar a pantalla de inicio" + modo standalone (HU-F11).
7. [ ] Cerrar HT-01: postura crítica de las categorías restantes (issues #11–#15) + lista de aprendizajes + **re-correr la auditoría** y guardar el último resultado.
8. [ ] Mergear PR #17 a `main` y coordinar defensa con la cátedra (todos los integrantes).

---

## Sugerencia de orden de trabajo

1. ~~**Seguridad de acceso** (bloqueante): HU-B01 → HU-B02 → HU-F02.~~ ✅
2. ~~**Integridad de negocio**: HU-B04 y HU-B05 juntas.~~ ✅ (quedan HU-B08 y HU-B11)
3. ~~**PWA instalable**: HU-F10 → HU-F09 → HU-F11 + HU-F12.~~ ✅ (falta verificación iOS)
4. ~~**PWA pauta final** (requisitos explícitos del examen): HU-F14 (catálogo offline) → HU-B13 + HU-F13 (push) → HU-F15 (accesibilidad 100) → HU-B14 (API protegida).~~ ✅ (faltan verificaciones en Android real + vars de entorno en Vercel)
5. **Sesiones**: HU-B03 + HU-B12 en backend, después HU-F03 + HU-F04.
6. **Experiencia de compra** (déficits que las pautas piden resueltos): HU-B06 + HU-F05, HU-F06 + HU-F07, HU-F08; sueltas: HU-B07, HU-B08, HU-B09, HU-B10, HU-B11.
7. **Google en la tienda**: HU-F01 (depende de la decisión tomada en HU-B02).
8. **Auditoría IA (HT-01)**: al final, con el resto cerrado — en la defensa se analiza el último resultado.
