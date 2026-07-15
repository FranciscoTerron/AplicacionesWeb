// Service worker de MA Piscinas (HU-F12 + HU-F14).
//
// - Precachea la página /offline y los íconos.
// - Navegación: network-first; las páginas de catálogo visitadas quedan
//   cacheadas y se sirven sin red (requisito de la pauta final: parte del
//   catálogo debe verse offline). Sin copia cacheada, fallback a /offline.
// - Páginas con datos personales/volátiles (carrito, checkout, cuenta,
//   login/registro) nunca se cachean.
// - Imágenes (next/image sirve same-origin vía /_next/image): cache-first
//   con tope de entradas.
// - Assets estáticos: cache-first con relleno.
// - Nunca cachea llamadas a la API (stock/precios/órdenes siempre frescos).
//
// Al cambiar el shell o la estrategia, subir VERSION para invalidar lo viejo.
const VERSION = "v2";
const STATIC_CACHE = `ma-piscinas-static-${VERSION}`;
const PAGES_CACHE = `ma-piscinas-pages-${VERSION}`;
const IMAGES_CACHE = `ma-piscinas-images-${VERSION}`;
const ACTIVE_CACHES = [STATIC_CACHE, PAGES_CACHE, IMAGES_CACHE];

const OFFLINE_URL = "/offline";
const PRECACHE = [OFFLINE_URL, "/icons/icon-192.png", "/icons/icon-512.png"];

const PAGES_MAX_ENTRIES = 40;
const IMAGES_MAX_ENTRIES = 60;

// Rutas cuyo HTML nunca debe quedar en cache (datos del usuario o de sesión).
const PRIVATE_PATHS = [/^\/carrito/, /^\/checkout/, /^\/cuenta/, /^\/login/, /^\/registro/];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches
      .open(STATIC_CACHE)
      .then((cache) => cache.addAll(PRECACHE))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys
            .filter((k) => !ACTIVE_CACHES.includes(k))
            .map((k) => caches.delete(k))
        )
      )
      .then(() => self.clients.claim())
  );
});

// Recorte FIFO: cache.keys() devuelve las entradas en orden de inserción.
async function trimCache(name, maxEntries) {
  const cache = await caches.open(name);
  const keys = await cache.keys();
  if (keys.length <= maxEntries) return;
  await Promise.all(
    keys.slice(0, keys.length - maxEntries).map((key) => cache.delete(key))
  );
}

async function putAndTrim(cacheName, request, response, maxEntries) {
  const cache = await caches.open(cacheName);
  await cache.put(request, response);
  await trimCache(cacheName, maxEntries);
}

self.addEventListener("fetch", (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // Solo GET del propio origen. La API vive en otro origen (y por las dudas
  // también se excluye /api/): esos datos no se cachean nunca.
  if (request.method !== "GET" || url.origin !== self.location.origin) return;
  if (url.pathname.startsWith("/api/")) return;

  // Navegación: red primero. Las páginas públicas exitosas se cachean para
  // poder verlas offline; sin red se sirve la copia cacheada o /offline.
  if (request.mode === "navigate") {
    const isPrivate = PRIVATE_PATHS.some((re) => re.test(url.pathname));
    event.respondWith(
      fetch(request)
        .then((response) => {
          // No se cachean errores ni redirects (un redirect cacheado rompe
          // navegaciones futuras), ni las rutas privadas.
          if (!isPrivate && response.ok && !response.redirected) {
            const copy = response.clone();
            event.waitUntil(
              putAndTrim(PAGES_CACHE, request, copy, PAGES_MAX_ENTRIES)
            );
          }
          return response;
        })
        .catch(async () => {
          if (!isPrivate) {
            const cachedPage = await caches.match(request);
            if (cachedPage) return cachedPage;
          }
          const offline = await caches.match(OFFLINE_URL);
          return offline ?? Response.error();
        })
    );
    return;
  }

  // Imágenes (next/image las sirve same-origin vía /_next/image): cache-first
  // con tope, para que las fichas de producto visitadas se vean offline.
  const isImage =
    url.pathname.startsWith("/_next/image") || request.destination === "image";

  // Shell estático: cache-first, rellenando el cache en el primer hit.
  const isStatic =
    url.pathname.startsWith("/_next/static/") ||
    url.pathname.startsWith("/icons/") ||
    ["style", "script", "font"].includes(request.destination);

  if (isImage || isStatic) {
    const cacheName = isImage ? IMAGES_CACHE : STATIC_CACHE;
    const maxEntries = isImage ? IMAGES_MAX_ENTRIES : Infinity;
    event.respondWith(
      caches.match(request).then(
        (cached) =>
          cached ||
          fetch(request).then((response) => {
            if (response.ok) {
              const copy = response.clone();
              event.waitUntil(
                putAndTrim(cacheName, request, copy, maxEntries)
              );
            }
            return response;
          })
      )
    );
  }
});
