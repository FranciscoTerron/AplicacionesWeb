# Auditoría IA v2 (re-auditoría de cierre) — MA Piscinas

> **Segunda corrida** del prompt obligatorio de la cátedra (`docs/ENUNCIADO_FINAL.md`),
> sobre el estado final del repo (branch `pancho/feat/final-2026`, post-correcciones).
> Auditor: Claude (Fable 5) · Fecha: 18/07/2026
> Primera corrida (16/07, pre-correcciones, 70 hallazgos): `docs/AUDITORIA_IA.md`

---

## Tabla resumen

| Categoría | Cantidad de hallazgos | Severidad máxima |
|---|---|---|
| 1. Seguridad | 3 | Media |
| 2. Arquitectura y organización | 2 | Media |
| 3. Calidad de código | 6 | Media |
| 4. Rendimiento | 4 | Media |
| 5. PWA | 2 | Baja |
| 6. Deuda técnica | 3 | Baja |
| **Total** | **20** | **Media** |

Sin hallazgos de severidad Crítica ni Alta. Comparativa contra la corrida del
16/07: 70 → 20 hallazgos; las 6 Críticas y todas las Altas de aquella corrida
están resueltas o justificadas por escrito.

---

## 1. Seguridad

**S-1 · Media — Refresh renueva sesiones indefinidamente (sliding session sin tope)**
- Archivo: `backend/app/Http/Controllers/Api/V1/AuthApiController.php:246-296` (ruta pública en `backend/routes/api.php:49-50`)
- Descripción: `refresh` acepta cualquier token vigente y le extiende `expires_at` otros 7 días, sin límite absoluto. Además el "refresh_token" es el mismo access token (no hay token de refresh separado).
- Impacto concreto: un token robado dentro de su ventana de 7 días puede mantenerse vivo para siempre renovándolo periódicamente — anula en la práctica la mitigación de "TTL corto" adoptada para S-3 de la auditoría v1.
- Sugerencia: guardar `session_started_at` en el doc del token y rechazar el refresh pasado un máximo absoluto (p. ej. 30 días), o emitir un refresh token de un solo uso distinto del access token.

**S-2 · Baja — Los tokens de sesiones anteriores se acumulan y siguen siendo válidos**
- Archivo: `backend/app/Http/Controllers/Api/V1/AuthApiController.php:85-92` y `184-191`
- Descripción: cada login/registro crea un doc nuevo en `api_tokens`; los anteriores no se revocan ni se limpian (solo se borran en logout explícito o al intentar un refresh vencido).
- Impacto concreto: N logins = N tokens válidos en paralelo durante 7 días cada uno (superficie de robo multiplicada), y la colección crece sin poda.
- Sugerencia: al hacer login, borrar los tokens vencidos del mismo `user_id` (una query + deletes); opcionalmente un límite de sesiones simultáneas.

**S-3 · Baja — Alta de suscripciones push anónima y sin verificación de origen del endpoint**
- Archivo: `backend/routes/api.php:87-90` · `backend/app/Http/Controllers/Api/V1/PushSubscriptionController.php`
- Descripción: `POST /push/subscribe` es público (solo `X-App-Key` + throttle 60/min) y acepta cualquier `endpoint` como string.
- Impacto concreto: un cliente hostil con la app key (extraíble del bundle) puede sembrar suscripciones basura; el costo real está acotado porque el id es `sha256(endpoint)` (idempotente) y el sender poda los endpoints muertos (404/410) al primer broadcast.
- Sugerencia: validar que `endpoint` sea URL `https` de un push service conocido (FCM/Mozilla/WNS) en `StorePushSubscriptionRequest`.

**Bien resuelto (para contexto, no son hallazgos):** tokens hasheados con SHA-256 en la base, `hash_equals` para comparaciones sensibles, bcrypt para passwords, rate limit estricto 5/min por email+IP en login/register, CORS con orígenes exactos + patrón acotado al proyecto (`config/cors.php`), API restringida por `X-App-Key` con límite documentado honestamente (`EnsureAppKey`), webhook de MP con verificación HMAC **más** reconsulta del pago contra la API oficial y validación de monto, security headers + HSTS en ambas apps, precios y totales calculados server-side.

## 2. Arquitectura y organización

**A-1 · Media — `FirestoreService` concentra transporte, codificación y política de paginación**
- Archivo: `backend/app/Services/FirestoreService.php` (555 líneas)
- Descripción: una sola clase resuelve autenticación OAuth, HTTP, encode/decode de tipos de Firestore y además la política de paginación por lotes (`fetchForPage`, líneas 342-380), que es lógica de aplicación.
- Impacto concreto: cualquier cambio de estrategia de paginación o de codificación pasa por el mismo archivo que el transporte; el archivo ya es el segundo más largo del backend.
- Sugerencia: extraer `fetchForPage` (y el par encode/decode) a colaboradores; no hace falta una capa de repositorios completa — la testeabilidad ya está resuelta vía `FakeFirestore` en el contenedor.

**A-2 · Baja — Dos controllers de catálogo con nombres casi idénticos y responsabilidad repartida**
- Archivo: `backend/app/Http/Controllers/Api/V1/CatalogController.php` y `.../CatalogApiController.php` (rutas en `backend/routes/api.php:53-66`)
- Descripción: `CatalogController` sirve listado y destacados; `CatalogApiController` sirve detalle y categorías. La partición no responde a un criterio visible y los nombres no la explican.
- Impacto concreto: para tocar "catálogo" hay que adivinar en cuál de los dos vive el endpoint; invita a duplicar helpers entre ambos.
- Sugerencia: unificarlos en un solo `CatalogController` (o renombrar por recurso: `ProductsController`/`CategoriesController`).

## 3. Calidad de código

**C-1 · Media — El handler de `DecryptException` referencia una clase inexistente: nunca se ejecuta**
- Archivo: `backend/bootstrap/app.php:68`
- Descripción: el closure tipa `DecryptException` sin `use`; en un archivo sin namespace eso resuelve a `\DecryptException`, clase que no existe. Laravel registra el renderable para ese nombre y ninguna excepción real (`Illuminate\Contracts\Encryption\DecryptException`) lo matchea jamás.
- Impacto concreto: el fix del 500 por cookies cifradas con una `APP_KEY` rotada — el propósito declarado del bloque — hoy no opera: esos usuarios siguen recibiendo 500 hasta borrar cookies. Pasa desapercibido porque PHPStan no analiza `bootstrap/`.
- Sugerencia: agregar `use Illuminate\Contracts\Encryption\DecryptException;` (1 línea) y sumar `bootstrap/app.php` a los paths de PHPStan.

**C-2 · Media — `query()` acepta varios filtros pero aplica solo el primero, en silencio**
- Archivo: `backend/app/Services/FirestoreService.php:163-164`
- Descripción: la firma recibe `array $fields`, pero el cuerpo toma `array_keys($fields)[0]` y descarta el resto sin error.
- Impacto concreto: un llamador futuro que filtre por `['user_id' => X, 'status' => Y]` recibiría resultados filtrados solo por `user_id` — con datos de más y sin ningún síntoma. Es una trampa de API interna (hoy todos los llamadores pasan un solo campo).
- Sugerencia: soportar filtro compuesto (el código ya existe en `countDocumentsWithQuery`, líneas 294-314) o cambiar la firma a `(string $field, mixed $value)`.

**C-3 · Media — `deleteDocument()` es la única operación que ignora fallos**
- Archivo: `backend/app/Services/FirestoreService.php:245-249`
- Descripción: get/create/update/query lanzan excepción ante respuesta fallida; delete no chequea `$response->failed()`.
- Impacto concreto: un logout que no llegó a revocar el token, o una suscripción push "podada" que sigue en la base, pasan en silencio total (ni log).
- Sugerencia: replicar el mismo chequeo `failed()` + log + excepción del resto de los métodos.

**C-4 · Baja — Patrón "buscar en users, si no en clients" duplicado en dos lugares**
- Archivo: `backend/app/Http/Controllers/Api/V1/AuthApiController.php:335-388` y `backend/app/Http/Middleware/Api/AuthenticateApiToken.php:60-65`
- Descripción: la resolución de identidad sobre dos colecciones (con el mismo armado de `User` vía `forceFill`) está copiada en el login y en el middleware.
- Impacto concreto: un cambio en la regla (p. ej. un campo nuevo en el perfil) hay que recordar hacerlo en ambos; ya hay una divergencia sutil (el login fija `role: 'cliente'` hardcodeado para clients, el middleware lo lee del doc).
- Sugerencia: extraer un `UserResolver`/método compartido que devuelva el `User` armado desde cualquiera de las dos colecciones.

**C-5 · Baja — `apiFetch` y `apiFetchRaw` duplican ~50 líneas casi idénticas**
- Archivo: `frontend/src/lib/api.ts:38-90` y `94-137`
- Descripción: headers, manejo de red, parseo y errores están copiados; solo difieren en el desempaquetado de `data` y en que la variante raw no dispara `auth:expired`.
- Impacto concreto: ya divergieron una vez (manejo de 401); el próximo fix de red hay que aplicarlo dos veces.
- Sugerencia: implementar `apiFetch` sobre `apiFetchRaw` (o un core privado compartido).

**C-6 · Baja — `refresh()` valida a mano en vez de usar FormRequest como el resto del módulo**
- Archivo: `backend/app/Http/Controllers/Api/V1/AuthApiController.php:248`
- Descripción: usa el helper global `request('refresh_token')` y chequeo manual, mientras login/register usan `LoginRequest`/`RegisterRequest`.
- Impacto concreto: inconsistencia menor; las reglas de validación del módulo auth no están todas en el mismo lugar.
- Sugerencia: un `RefreshRequest` con `refresh_token => required|string`.

## 4. Rendimiento

**R-1 · Media — Se pide un access token OAuth a Google en cada request que toca Firestore**
- Archivo: `backend/app/Services/FirestoreService.php:26` (constructor) con `fetchAccessToken()` en `55-131`; singleton en `backend/app/Providers/AppServiceProvider.php:21-23`
- Descripción: el singleton dura una request (serverless): cada invocación instancia el servicio y hace el round-trip JWT→token contra Google antes de la primera operación, aunque el token dura 1 hora.
- Impacto concreto: latencia extra (~100-300 ms) sumada a **todas** las requests de API y del panel, multiplicada por ser el paso previo a cualquier lectura.
- Sugerencia: `Cache::remember('firestore_access_token', now()->addMinutes(50), ...)` alrededor del fetch (el driver de cache ya está configurado).

**R-2 · Baja — Cada escritura paga una lectura extra para devolver el documento**
- Archivo: `backend/app/Services/FirestoreService.php:212`, `225` y `242`
- Descripción: `createDocument`, `createDocumentWithId` y `updateDocument` terminan con `getDocument()`, pero la respuesta del POST/PATCH de Firestore ya contiene el documento completo.
- Impacto concreto: toda escritura cuesta dos round-trips en lugar de uno (checkout, webhook y panel incluidos).
- Sugerencia: parsear `$response->json()` con el `parseDocument()` existente y eliminar la relectura.

**R-3 · Baja — `broadcast()` lee como máximo 500 suscripciones, sin paginar y sin aviso**
- Archivo: `backend/app/Services/WebPushSender.php:35`
- Descripción: `listDocuments(self::COLLECTION, 500, ...)` toma una sola página; si hubiera más suscriptores, el resto no recibe la notificación y no queda registro.
- Impacto concreto: hoy inalcanzable a la escala del negocio, pero es un tope silencioso: el día que se supere, el síntoma sería "a algunos no les llega" sin ningún log.
- Sugerencia: iterar con el cursor `lastDocumentId` que `listDocuments` ya devuelve, o al menos loggear cuando `hasMore` sea true.

**R-4 · Baja — `fetchForPage` sobre-lee hasta `perPage × 6` documentos para filtrar en memoria**
- Archivo: `backend/app/Services/FirestoreService.php:342-380`
- Descripción: la paginación con filtros client-side trae lotes de hasta 6× la página pedida. Decisión conocida y documentada por el equipo (v1: R-2..R-10, justificados por escala).
- Impacto concreto: costo de lectura lineal con el catálogo; irrelevante con decenas de productos, degradaría con miles.
- Sugerencia: mantener la justificación por escrito (ya existe) y revisar recién si el catálogo crece un orden de magnitud.

**Frontend: sin hallazgos relevantes.** Páginas de catálogo server-rendered, imágenes vía `next/image` con patrones remotos acotados, sin fetching client-side pesado ni re-renders estructurales; el bundle no incluye librerías de datos.

## 5. PWA

La categoría está bien cubierta: manifest completo con maskable icon (`frontend/src/app/manifest.ts`), SW versionado con precache + runtime caching y estrategia documentada por tipo de recurso (`frontend/public/sw.js`), navegación offline del catálogo (incluye el matiz de las navegaciones RSC del App Router), push end-to-end con `TTL`/`urgency` correctos y poda de suscripciones muertas. Quedan dos detalles menores:

**P-1 · Baja — Las páginas ya cacheadas no se refrescan hasta un bump de `VERSION`**
- Archivo: `frontend/public/sw.js:80` (`cachePage`: `if (await cache.match(pageUrl)) return;`)
- Descripción: el relleno disparado por fetches RSC no actualiza una página ya presente en `PAGES_CACHE`; solo la navegación dura exitosa la re-escribe (línea 108-114).
- Impacto concreto: en sesiones SPA puras, la copia offline de `/` y `/productos` puede quedar con precios/stock viejos hasta una navegación dura o una versión nueva del SW.
- Sugerencia: en `cachePage`, refrescar en background aunque exista copia (stale-while-revalidate) en vez de retornar temprano.

**P-2 · Baja — `notificationclick` no enfoca la pestaña si la URL trae query string**
- Archivo: `frontend/public/sw.js:208`
- Descripción: compara `new URL(client.url).pathname === url`; si la notificación apunta a `/productos?promo=x`, la igualdad falla siempre.
- Impacto concreto: con la tienda ya abierta en esa página, el click abre una segunda pestaña en vez de enfocar la existente.
- Sugerencia: comparar solo pathnames (`new URL(url, self.location.origin).pathname`).

## 6. Deuda técnica

**D-1 · Baja — Fallbacks a `config('firebase.*')` cuyo archivo de config no está versionado**
- Archivo: `backend/app/Services/FirestoreService.php:38-41` y `89-105`
- Descripción: dos ramas de resolución leen `config('firebase.projects.app.*')`, pero `config/firebase.php` no existe en el repo (quedó de la remoción de kreait): en cualquier deploy desde el repo esas ramas son código muerto.
- Impacto concreto: ruido al leer la cadena de resolución de credenciales; sugiere una fuente de configuración que no existe.
- Sugerencia: eliminar ambas ramas.

**D-2 · Baja — Cadena de resolución de credenciales con 6 pasos y uno repetido**
- Archivo: `backend/app/Services/FirestoreService.php:55-131`
- Descripción: los pasos 1 y 5 leen la misma config (`app.firestore_credentials_json`); con D-1 resuelto quedan igualmente más rutas de las que se usan (en Vercel siempre entra el paso 1, en local el archivo).
- Impacto concreto: más ramas que probar y leer para un comportamiento que en la práctica tiene dos fuentes.
- Sugerencia: reducir a dos fuentes explícitas: JSON por env o path a archivo.

**D-3 · Baja — Config de Sanctum conservada sin el paquete instalado**
- Archivo: `backend/config/sanctum.php` (consumida solo por `AuthApiController.php:396` para `token_prefix`)
- Descripción: `laravel/sanctum` no está en `composer.json`; el archivo de config completo se mantiene solo para leer un prefijo de token que además es `''` por defecto.
- Impacto concreto: sugiere que Sanctum participa de la autenticación cuando el sistema de tokens es propio (sobre Firestore) — confunde al lector nuevo.
- Sugerencia: borrar `config/sanctum.php` y reemplazar la lectura por una constante o `config('app.token_prefix')`.

**Sin otros hallazgos:** no hay TODOs/FIXMEs olvidados en `app/`, `routes/` ni en el frontend (grep limpio); las dependencias están actualizadas (Laravel 13, Next 16, React 19, Tailwind 4) y sin paquetes sin uso tras la limpieza de la v1 (kreait, cloud-firestore, sessions/UserFactory/seeder eliminados).

---

## Top 3 prioridades (impacto / esfuerzo)

1. **C-1 — Importar `DecryptException` en `bootstrap/app.php`**: una línea restaura un manejo de errores que hoy no se ejecuta nunca; mientras tanto existe un fix que el equipo cree activo y no lo está. Máximo retorno por esfuerzo de todo el informe.
2. **R-1 — Cachear el access token de Google (50 min)**: pocas líneas que eliminan un round-trip a Google de *cada* request del sitio y del panel; es la mejora de latencia más barata disponible.
3. **S-1 — Tope absoluto de sesión en `refresh`**: cierra la renovación infinita que hoy debilita la decisión documentada de acortar el TTL a 7 días; el cambio es un campo más en el doc del token y un chequeo.
