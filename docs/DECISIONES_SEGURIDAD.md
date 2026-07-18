# Decisiones de seguridad — cierre del issue #10

> Registro de cómo se resolvió cada hallazgo de seguridad de la auditoría IA (`docs/AUDITORIA_IA.md` §1), incluidas las **decisiones de no-fix justificadas** para la defensa.

| Hallazgo | Severidad | Estado | PR |
|---|---|---|---|
| S-1 CORS con comodines | Media | ✅ Resuelto | #22 |
| S-2 API no restringida al front | Media | ✅ Resuelto | #22 |
| S-3 token legible por JS | Media | ⚠️ Mitigado (ver abajo) | #23 |
| S-4 refresh sin validar expiración | Media | ✅ Resuelto | #23 |
| S-5 sin throttle en login/register | Media | ✅ Resuelto | #22 |
| S-6 firma webhook best-effort | Baja | 🟡 Tradeoff aceptado (ver abajo) | — |
| S-7 sin security headers | Baja | ✅ Resuelto | #23 |

---

## S-3 — Token legible por JS: por qué se **mitiga** y no se elimina

**El hallazgo**: el token de sesión (`ma_token`) se guarda con `document.cookie` → no es `HttpOnly` → un XSS en el front podría leerlo.

**Por qué no se hace `HttpOnly`**: el token se usa como **Bearer** en el header `Authorization`, así que el front (SPA) **necesita leerlo desde JS**. Una cookie `HttpOnly` rompe ese flujo. La alternativa (auth por cookie que el browser manda solo) es **cross-domain** — front (`aplicaciones-web-tienda.vercel.app`) y back (`aplicaciones-web-one.vercel.app`) son dominios distintos → requiere `SameSite=None` + cookies de terceros (que los browsers bloquean progresivamente) + `CORS credentials`. Es un rework grande y frágil que puede romper login y el flujo de Mercado Pago.

**Mitigación adoptada**:
1. **TTL corto**: token de 30 → **7 días** (back `AuthApiController::TOKEN_TTL_DAYS` + front `cookies.ts`). Reduce la ventana en que un token robado sirve.
2. **Security headers en el front** (`next.config.ts`): `X-Frame-Options: DENY`, `X-Content-Type-Options`, `Referrer-Policy`, `HSTS`, `Permissions-Policy` → reducen la superficie de XSS.
3. **Riesgo residual documentado**: sigue siendo posible que un XSS lea el token. La defensa real contra eso es una **CSP estricta con nonces** (follow-up: requiere setup en el middleware de Next) y compilar Tailwind local en el admin (deuda D-5) para poder aplicar CSP también en Laravel.

**Postura crítica**: no existe forma criptográficamente fuerte de garantizar que un token en un front público no sea robable por XSS. Se sube la barrera y se acota el impacto; no se elimina.

## S-4 — Refresh que revivía tokens vencidos: **resuelto**

**El hallazgo**: `/auth/refresh` rotaba el token sin mirar `expires_at`, y el doc del token no se borra al expirar (solo en logout) → un token filtrado se "resucitaba" indefinidamente.

**Fix**: `refresh()` ahora valida `expires_at` **antes** de rotar. Si está vencido, **borra el doc** (para que no quede resucitable) y responde `401 Refresh token expirado`. Cubierto por test `test_refresh_rejects_expired_token`.

## S-1 (auditoría v2) — Refresh sin tope absoluto de sesión: **resuelto**

**El hallazgo** (re-auditoría 18/07, `docs/AUDITORIA_IA_V2.md`): `refresh` extendía
`expires_at` +7 días sin límite → un token robado podía renovarse para siempre,
anulando en la práctica la mitigación de "TTL corto" adoptada para S-3.

**Análisis previo**: el frontend **no consume** `/auth/refresh` (cero llamadas en
`frontend/src`), así que se evaluó eliminar el endpoint. Se decidió conservarlo
por completitud de la API documentada y **caparlo**.

**Fix**: tope absoluto de sesión de **30 días desde `created_at`** (que se fija en
el login y el refresh no pisa). Pasado el tope, el refresh borra el doc del token
y responde `401 Sesión expirada`. Un token robado pasa de "renovable infinito" a
30 días máximo desde el login original. Tokens legacy sin `created_at` no se
capean: igual mueren a los 7 días por `expires_at`. Tests:
`test_refresh_rejects_session_older_than_absolute_cap` y
`test_refresh_allows_session_within_absolute_cap`.

**Pendiente futuro (HU-B03)**: separar access/refresh token como registros
distintos; el tope actual acota el riesgo mientras tanto.

## S-6 — Firma del webhook MP best-effort: **tradeoff aceptado**

**El hallazgo**: si la firma `x-signature` de Mercado Pago no valida, se loguea un warning y se continúa (no `return 400`).

**Decisión: se deja como está, a propósito.** Razones:
- La firma se hizo best-effort tras una saga real de HMAC con MP: el **modo test** de MP firma las notificaciones con un secret distinto al de producción, y exigir la firma rompía el flujo de pruebas.
- Está **mitigado en profundidad**: el webhook, ante cualquier notificación, **reconsulta el pago directamente a la API de MP** con el `access_token` y valida `external_reference` + monto (`PaymentWebhookController` ~:106-113). Un webhook forjado no pasa esa segunda verificación.

**Riesgo residual**: se pierde la primera línea de defensa (rechazo temprano) contra webhooks forjados/replays. Aceptable dado el mitigante y la severidad Baja.

**Follow-up posible**: env-gate para exigir la firma solo en producción (`return 400`), sin afectar el modo test. No se hace ahora para no re-testear todo el flujo de MP antes del final.

## S-7 — Sin security headers: **resuelto**

Middleware `SecurityHeaders` (Laravel) en los grupos `web` + `api`: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, `HSTS` (solo en HTTPS). Protege el panel admin Blade de clickjacking y sniffing. La CSP queda pendiente hasta compilar Tailwind local (deuda D-5), porque el Tailwind Play CDN exige directivas permisivas que la volverían inútil.
