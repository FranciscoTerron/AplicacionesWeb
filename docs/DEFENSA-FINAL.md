# Defensa del final 2026 — Auditoría IA: proceso, resultados y postura

> Documento de presentación del entregable Auditoría IA (issue #16).
> Reporte v1: `docs/AUDITORIA_IA.md` (16/07, 70 hallazgos) · **Re-auditoría de cierre:
> `docs/AUDITORIA_IA_V2.md` (18/07, 20 hallazgos, sin Críticas ni Altas)** — el último
> resultado, que es el que se defiende. Prompt obligatorio: `docs/ENUNCIADO_FINAL.md`

---

## 1. El proceso (qué hicimos con la auditoría)

1. **Auditoría** con el prompt obligatorio de la cátedra → 70 hallazgos en 6 categorías
   (`docs/AUDITORIA_IA.md`: tabla resumen + detalle con referencias `archivo:línea` + Top 3).
2. **Desglose en issues** #10–#15 (uno por categoría) para trackear decisión por hallazgo.
3. **PRs correctivos** #18–#25 sobre los hallazgos priorizados, con **review cruzado**
   de cada PR contra su hallazgo (`docs/REVIEW_PRS_AUDITORIA.md`).
4. **Decisiones de no-fix por escrito** donde elegimos no resolver
   (`docs/DECISIONES_SEGURIDAD.md` y comentarios de cierre en cada issue).
5. **Verificación en producción y dispositivo real** (17/07): push con el navegador
   cerrado en Android, catálogo offline en modo avión, Lighthouse Accessibility 100
   en producción, API rechazando clientes sin app key.

## 2. Resultados por categoría

| Categoría | Hallazgos | Sev. máx | Resueltos | Justificados | Issue |
|---|---|---|---|---|---|
| PWA | 10 | Crítica | **10/10** (2 con desvío deliberado del plan sugerido) | — | #14 |
| Deuda técnica | 12 | Alta | **9/12** | D-12; D-1/D-5 en decisión | #15 |
| Seguridad | 7 | Media | **5/7** | S-3 mitigado, S-6 tradeoff — argumentados | #10 |
| Rendimiento | 10 | Crítica | **R-1 (el crítico)** + R-3/R-9 parciales | resto: escala irreal para esta tienda | #13 |
| Calidad | 18 | Alta | **C-4 y C-5** (los de impacto de usuario) | resto: refactor sin efecto observable | #12 |
| Arquitectura | 13 | Alta | **A-4** + A-3 parcial | resto: capas cuyo beneficio ya se obtiene por otros medios | #11 |

**Los 6 hallazgos Críticos quedaron resueltos** (5 de PWA + el N+1 de descuentos).
El **Top 3 del reporte** también: PWA completa ✅ · N+1 (R-1) ✅ · errores
silenciosos + estados de orden (C-4 + A-4) ✅.

Criterio general: **impacto sobre el usuario y el requisito del final primero**;
lo que es deuda interna sin efecto observable se documenta y se difiere — cambiar
8 controllers por consistencia a días del examen tiene más riesgo que beneficio.

## 3. Los tres parámetros de la defensa

### 3.1 Comprensión de hallazgos

Poder explicar con palabras propias qué señala cada hallazgo relevante y por qué
importa, **incluso los que decidimos no resolver**. Los dos que seguro preguntan:

- **S-3 — ¿por qué el token no es HttpOnly?** El SPA manda el token como `Bearer`
  en el header → necesita leerlo desde JS. La alternativa (cookie que viaja sola)
  es cross-domain entre front y back en Vercel → `SameSite=None` + cookies de
  terceros que los browsers bloquean. Mitigación elegida: TTL 30→7 días + security
  headers. Detalle: `docs/DECISIONES_SEGURIDAD.md`.
- **S-1/S-2 — API protegida** (requisito textual del enunciado): CORS con orígenes
  exactos + middleware `EnsureAppKey` (header `X-App-Key`). Demo en vivo: `curl` a
  la API → 403 "Cliente no autorizado"; la misma ruta desde la tienda → 200.
  Honestidad técnica: ninguna técnica browser-side impide el 100% del acceso
  directo (la clave vive en el bundle); el objetivo es que el uso normal solo sea
  posible desde la app.

### 3.2 Cosas nuevas aprendidas (no vistas en la cursada)

- **Web Push / VAPID**: protocolo estándar de push (par de claves VAPID, payload
  cifrado por suscripción, FCM/autopush como intermediarios). Aprendizaje fino:
  `urgency: high` + TTL — con los defaults, Android (Doze) difiere la entrega con
  el navegador cerrado y FCM descarta el mensaje a los 5 minutos; lo sufrimos y lo
  arreglamos.
- **Estrategias de caché del service worker**: cache-first vs network-first,
  precache vs runtime, y el detalle de que las navegaciones SPA del App Router son
  fetches RSC que **no** pasan por el handler `navigate` — sin manejarlo, offline
  solo se veía la página de entrada.
- **Verificación HMAC de webhooks**: cómo firma Mercado Pago y por qué igual se
  reconsulta el pago contra su API (defensa en profundidad).
- **Aggregation queries de Firestore por REST**: contar sin traer documentos; el
  endpoint corre sobre `documents`, no sobre la colección (bug real que
  produjimos y arreglamos en `countDocuments`).

### 3.3 Postura crítica frente a la IA

Hallazgos donde validamos contra el código en vez de aceptar a ciegas:

1. **A-1 (premisa falsa)**: la IA afirma que sin capa de repositorios el código
   "no es testeable sin red". Empíricamente falso: los **265 tests** del backend
   corren sin red — `FirestoreService` se reemplaza por `FakeFirestore` en el
   contenedor. El beneficio del patrón ya está capturado sin la capa extra.
2. **C-15 (mal fundamentado)**: el "docblock huérfano" documenta `sanitizeImages()`,
   que existe en la línea siguiente. Verificable en 10 segundos.
3. **PWA "100% ausente"**: la auditoría corrió sobre un estado viejo del repo; al
   momento de la defensa está implementada, verificada en producción y en
   dispositivo real. Además **"ausente" ≠ "roto"**: las 5 Críticas de PWA eran una
   ausencia esperada de un requisito nuevo, no bugs.
4. **Rendimiento a escala irreal**: R-2..R-10 asumen datasets que el negocio no
   tiene (caps de 200/500 nunca se alcanzan con un catálogo de decenas de
   productos). Técnicamente correctos, irrelevantes al tamaño real.
5. **La propia auditoría se auto-corrige** (lo destacamos a favor del método): el
   "logging temporal del webhook" sospechado no existía; reconoce que el front
   está bien resuelto en rendimiento; lista lo "bien resuelto" en seguridad
   (ownership, bcrypt, precios server-side, secretos por env).
6. **Desvíos deliberados del plan sugerido**: sin Serwist (SW vanilla de ~200
   líneas explicables completas, sin acoplar el build), sin push automático al
   crear producto (panel manual con plantillas: control editorial), API de
   Firestore sin cachear en el SW (stock/precios siempre frescos; el catálogo
   offline sale del HTML server-rendered).

## 4. Pendientes antes de la defensa

- [x] **Re-correr la auditoría** ✅ (18/07 → `docs/AUDITORIA_IA_V2.md`): 70 → 20
  hallazgos, **sin Críticas ni Altas**. Como se esperaba, PWA y deuda quedaron
  casi limpias y sobreviven los no-fix justificados (fetchForPage/escala,
  controllers grandes) — que es exactamente lo que se defiende. La v2 además
  encontró cosas nuevas y chicas (el mejor ejemplo: un `use` faltante en
  `bootstrap/app.php` que dejaba muerto el handler de `DecryptException`).
- [ ] (Recomendado por el enunciado) Correrla también con **otro modelo**
  (Gemini/GPT) y comparar: afina la postura crítica con ejemplos de divergencia.
- [ ] Cerrar #16 con el resultado de la re-auditoría.
- [ ] Decidir D-1/D-5 (Tailwind CDN del panel: compilar o justificar; conecta con
  el no-CSP de S-7).
- [ ] Coordinar día/hora con la cátedra — asisten **todos** los integrantes.

## 5. Mapa de documentos

| Documento | Qué tiene |
|---|---|
| `docs/AUDITORIA_IA.md` | Reporte v1 de la auditoría (16/07, 70 hallazgos, formato obligatorio) |
| `docs/AUDITORIA_IA_V2.md` | **Re-auditoría de cierre (18/07, 20 hallazgos, sin Críticas/Altas) — el resultado que se defiende** |
| `docs/ENUNCIADO_FINAL.md` | Enunciado + prompt obligatorio textual |
| `docs/DECISIONES_SEGURIDAD.md` | Resolución hallazgo por hallazgo de seguridad, con no-fixes argumentados |
| `docs/REVIEW_PRS_AUDITORIA.md` | Review cruzado de los PRs correctivos contra sus hallazgos |
| `docs/AUDITORIA_RESPONSIVE.md` | Postura crítica: el "déficit responsive" del enunciado no aplica al front actual |
| `docs/HU-MEJORAS.md` | Backlog completo + checklist contra las pautas + verificación final |
| Issues #10–#15 | Un issue por categoría, con comentarios de cierre justificando cada decisión |
