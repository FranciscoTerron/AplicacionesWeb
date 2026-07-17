"use client";

import { useEffect } from "react";

// Registra el service worker solo en producción: en `next dev` interferiría
// con HMR y cachearía builds intermedios (HU-F12).
export function ServiceWorkerRegister() {
  useEffect(() => {
    if (process.env.NODE_ENV !== "production") return;
    if (!("serviceWorker" in navigator)) return;

    navigator.serviceWorker.register("/sw.js").catch(() => {
      // Sin SW la tienda funciona igual; no hay nada que mostrarle al usuario.
    });
  }, []);

  return null;
}
