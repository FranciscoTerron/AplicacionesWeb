# Review de los PRs de la Auditoría IA

> Revisión (`/review`) de cada sub-PR contra los hallazgos de `docs/AUDITORIA_IA.md`.
> Objetivo: confirmar que cada PR **se condice** con el hallazgo que dice resolver y **lo soluciona** de verdad.
> Fecha: 2026-07-16 · Revisor: Claude Opus 4.8.

## Resumen

| PR | Fix | Hallazgo | ¿Se condice? | ¿Lo soluciona? | Veredicto |
|---|---|---|---|---|---|
| [#18](https://github.com/FranciscoTerron/AplicacionesWeb/pull/18) | N+1 descuentos | R-1 (Rend. Crítica) | ✅ | ✅ | Aprobado |
| [#19](https://github.com/FranciscoTerron/AplicacionesWeb/pull/19) | Estados de orden front/back | A-4 (Arq. Alta) | ✅ | ✅ | Aprobado |
| [#20](https://github.com/FranciscoTerron/AplicacionesWeb/pull/20) | `getDocument` falla ruidoso | C-4 (Calidad Alta) | ✅ | ✅ | Aprobado (sugerencia: test) |
| [#21](https://github.com/FranciscoTerron/AplicacionesWeb/pull/21) | Cupón unificado | C-5 (Calidad Alta) | ✅ | ✅ | Aprobado |
| [#22](https://github.com/FranciscoTerron/AplicacionesWeb/pull/22) | API protegida | S-1, S-2, S-5 (Seguridad) | ✅ | ✅ (3 de 5) | Aprobado con ajuste |

**Conclusión**: los 5 PRs resuelven correctamente los hallazgos que declaran. Único ajuste real detectado: el PR #22 cerraba de más el issue #10 (corregido `Closes` → `Refs`).

---

## PR #18 — N+1 de descuentos (R-1)

- **Hallazgo (AUDITORIA_IA.md §R-1)**: `DiscountService::decorate()` hacía `getDocument('discounts', $id)` por cada producto → N requests HTTP a Firestore por listado. Sugerencia: map `id => discount` en memoria desde `activeDiscounts()`.
- **Qué hace el PR**: agrega `activeDiscountsById()` (mapa cacheado) y `discountForProduct()` resuelve por lookup O(1). Exactamente la sugerencia.
- **Correctitud**: comportamiento preservado — `activeDiscounts()` ya filtra por `isUsable`; tests `ignores inactive`/`ignores used up` verdes.
- **Riesgo menor (preexistente)**: `activeDiscounts()` usa `listDocuments(200, orderBy='name')`; un descuento sin `name` o con >200 usables no resolvería. Ya existía en `activeDiscounts`, irreal para esta tienda.
- **Veredicto**: ✅ soluciona R-1, sin cambio de comportamiento observable.

## PR #19 — Estados de orden front/back (A-4)

- **Hallazgo (§A-4)**: el front definía `processing/shipped/delivered`, el backend valida/escribe `in_process/completed`. Una orden `in_process` se mostraba como texto crudo.
- **Qué hace el PR**: alinea `OrderStatus` + `ORDER_STATUS_LABELS` al vocabulario del backend; `orderStatusVariant` usa `completed`.
- **Correctitud**: `shipped/delivered` bien identificados como estados de `Shipment` (no de Order). `CANCELABLE_STATUSES` sigue coincidiendo con el backend. Sin refs colgadas. Sin migración (el back nunca escribió los estados viejos).
- **Veredicto**: ✅ soluciona A-4, quirúrgico.

## PR #20 — `getDocument` falla ruidoso (C-4)

- **Hallazgo (§C-4)**: `getDocument` solo manejaba 404; ante 403/500/timeout devolvía `['id'=>...]` como doc vacío → fallos de Firestore enmascarados y datos que "desaparecen" sin log.
- **Qué hace el PR**: agrega `if ($response->failed()) { Log::error(...); throw }` tras el chequeo de 404. Igual que `listDocuments`/`create`/`update`/`query`.
- **Correctitud**: `failed()` cubre 4xx/5xx; el 404 ya retornó antes. Cambio de propagación intencional (ahora 500 visible en vez de pérdida silenciosa). Re-fetch internos son 200.
- **Sugerencia (no bloquea)**: agregar test con `Http::fake` que mockee un 500 y asserte el throw.
- **Veredicto**: ✅ soluciona C-4.

## PR #21 — Cupón unificado (C-5)

- **Hallazgo (§C-5)**: cálculo del cupón + regla "gana el mayor descuento" duplicado verbatim en carrito y checkout (3ª copia de `DiscountService::applyValue`). Si divergen, el total mostrado ≠ total del server.
- **Qué hace el PR**: extrae `couponAmount()` y `bestDiscount()` a `lib/discount.ts`; ambas páginas los reusan.
- **Correctitud**: extracción verbatim. Verificado que el clamp fijo `Math.min(value, subtotal)` coincide con la semántica del backend (`applyValue` fixed).
- **Matiz (postura crítica)**: unifica las dos copias del **front**, pero el helper TS sigue duplicando la lógica **PHP** — inevitable con dos lenguajes. El total mostrado no puede sobrecobrar: el cargo real lo calcula el server.
- **Sugerencia (no bloquea)**: sin test unitario (el front no tiene setup de tests).
- **Veredicto**: ✅ soluciona C-5.

## PR #22 — API protegida (S-1, S-2, S-5)

- **Hallazgos (§Seguridad)**:
  - **S-1**: CORS con comodines `*.vercel.app`/`localhost:*`.
  - **S-2**: sin restricción server-side "solo el front".
  - **S-5**: sin throttle anti-fuerza-bruta en login/register.
- **Qué hace el PR**:
  - Middleware `EnsureAppKey` (`app.client`): exige `X-App-Key` = `APP_PUBLIC_KEY` (opt-in). Webhook/cron exentos. 403 (no 401).
  - CORS a orígenes exactos.
  - Rate limiter `auth` (5/min por email+IP), bypass en `testing`.
  - Front manda `X-App-Key`; `AppKeyTest` (5 casos).
- **Calidad**: `hash_equals` timing-safe, guards correctos, exenciones correctas.
- **⚠️ Hallazgo del review (corregido)**: el PR declaraba `Closes #10`, pero #10 agrupa 5 hallazgos y este PR resuelve 3. Al mergear habría auto-cerrado el issue perdiendo **S-3** (token en cookie no-HttpOnly, robable por XSS) y **S-4** (`/auth/refresh` sin validar `expires_at`). **Corregido a `Refs #10`** para dejar el issue abierto.
- **Límite conocido (para la defensa)**: la clave viaja en el bundle del front (SPA pública) → sube la barrera contra bots/curl, no es secreto criptográficamente fuerte. No hay forma fuerte de garantizar "solo el React" en un front público.
- **Veredicto**: ✅ soluciona S-1/S-2/S-5. **Pendientes bajo #10: S-3 y S-4.**

---

## Acciones pendientes tras el review

- [ ] (Opcional) PR #20: agregar test `Http::fake` del throw de `getDocument`.
- [ ] (Opcional) PR #21: test unitario de `lib/discount.ts`.
- [ ] **#10 sigue abierto**: resolver o documentar **S-3** (cookie HttpOnly) y **S-4** (refresh con `expires_at`).
- [ ] Mergear los 5 sub-PRs a la integradora `#17` y luego `#17` → `main`.
