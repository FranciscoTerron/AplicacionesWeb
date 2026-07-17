# Auditoría responsive — frontend React

> El enunciado del final menciona *"pobre visualización responsiva"* como déficit a corregir. Se auditó el frontend Next.js completo (mobile ~375px + tablet).

## Conclusión

**El frontend React ya está bien construido en responsive.** No se encontraron anti-patrones que rompan el layout en mobile ni scroll horizontal del body. El déficit que menciona el enunciado **no aplica** a este código — está resuelto.

### Evidencia (por qué está bien)
- **Grids con breakpoints**: `grid-cols-2 sm:grid-cols-3 lg:grid-cols-4` (product-grid), `grid-cols-2 … md:grid-cols-6` (categorías/footer).
- **Header colapsa** a columna en mobile (`flex flex-col … md:flex-row`); labels de nav con `hidden sm:inline`.
- **Filtros**: sidebar desktop `hidden md:block` + `MobileFilters` en `Sheet` (`md:hidden`).
- **Sin tablas**: las órdenes usan cards flex → no hay desborde horizontal.
- **Layouts de 2 columnas colapsan**: `grid-cols-1 lg:grid-cols-[1fr_320px]` (carrito/checkout), `grid-cols-1 md:grid-cols-2` (detalle producto).
- **Imágenes** con `next/image` `fill` + `sizes`; galería con thumbnails en `overflow-x-auto`.
- **Modales**: `Dialog` `max-w-[calc(100%-2rem)] sm:max-w-sm`; `Sheet` `w-3/4 sm:max-w-sm` — nunca ancho fijo que desborde.
- **Tipografía escala**: `text-3xl md:text-5xl` (hero), `text-2xl md:text-3xl` (títulos).

## Único hallazgo (Baja, cosmético) — corregido

- **`components/sort-select.tsx:34`**: único ancho fijo en px de toda la app (`w-[160px]`). No generaba overflow (vive en un `flex-wrap`), pero era rigidez innecesaria. **Fix aplicado**: `w-full sm:w-40` para que respire en pantallas muy chicas (320px).

## Nota para la defensa (postura crítica)

El "déficit de visualización responsiva" del enunciado es un ejemplo genérico de deuda de la cursada; en el estado actual del proyecto, el **front React está resuelto**. Si hubiera un déficit responsive real, estaría en el **panel admin (Laravel Blade)**, que es otra app y usa Tailwind por CDN (deuda D-5). Ese panel no forma parte del requisito "aplicación frontend en React" del enunciado.
