"use client";

import { useEffect, useState } from "react";
import { Bell, BellOff, BellRing } from "lucide-react";
import { toast } from "sonner";
import {
  getPushPublicKey,
  subscribePush,
  unsubscribePush,
} from "@/lib/endpoints";

// pushManager.subscribe espera la clave VAPID como bytes, no base64url.
function urlBase64ToUint8Array(base64: string): Uint8Array<ArrayBuffer> {
  const padding = "=".repeat((4 - (base64.length % 4)) % 4);
  const raw = atob((base64 + padding).replace(/-/g, "+").replace(/_/g, "/"));
  const bytes = new Uint8Array(raw.length);
  for (let i = 0; i < raw.length; i++) bytes[i] = raw.charCodeAt(i);
  return bytes;
}

type PushState = "hidden" | "off" | "on" | "denied" | "busy";

// Activa/desactiva las notificaciones push de promos (HU-F13). Suscripción
// anónima: solo se manda el endpoint del navegador al backend. No pide el
// permiso al cargar — únicamente cuando el usuario lo toca.
export function PushToggle() {
  const [state, setState] = useState<PushState>("hidden");

  useEffect(() => {
    // El SW se registra solo en producción; en dev el toggle no aparece.
    if (process.env.NODE_ENV !== "production") return;
    if (
      !("serviceWorker" in navigator) ||
      !("PushManager" in window) ||
      !("Notification" in window)
    ) {
      return;
    }

    if (Notification.permission === "denied") {
      setState("denied");
      return;
    }

    navigator.serviceWorker
      .getRegistration()
      .then((reg) => reg?.pushManager.getSubscription())
      .then((sub) => setState(sub ? "on" : "off"))
      .catch(() => setState("off"));
  }, []);

  async function enable() {
    setState("busy");
    try {
      const permission = await Notification.requestPermission();
      if (permission !== "granted") {
        setState(permission === "denied" ? "denied" : "off");
        return;
      }

      const reg =
        (await navigator.serviceWorker.getRegistration()) ??
        (await navigator.serviceWorker.register("/sw.js"));
      const publicKey = await getPushPublicKey();
      const sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(publicKey),
      });
      await subscribePush(sub.toJSON());

      setState("on");
      toast.success("Notificaciones activadas", {
        description: "Te vamos a avisar de promos y productos nuevos.",
      });
    } catch {
      setState("off");
      toast.error("No pudimos activar las notificaciones");
    }
  }

  async function disable() {
    setState("busy");
    try {
      const reg = await navigator.serviceWorker.getRegistration();
      const sub = await reg?.pushManager.getSubscription();
      if (sub) {
        await unsubscribePush(sub.endpoint);
        await sub.unsubscribe();
      }
      setState("off");
      toast("Notificaciones desactivadas");
    } catch {
      setState("on");
      toast.error("No pudimos desactivar las notificaciones");
    }
  }

  if (state === "hidden") return null;

  if (state === "denied") {
    return (
      <p className="flex items-center gap-2 text-xs text-slate-500">
        <BellOff className="size-4 shrink-0" />
        Notificaciones bloqueadas en el navegador.
      </p>
    );
  }

  const on = state === "on";

  return (
    <button
      type="button"
      onClick={on ? disable : enable}
      disabled={state === "busy"}
      className="inline-flex items-center gap-2 rounded-md border border-white/20 px-3 py-2 text-sm text-slate-300 transition hover:border-sky-400 hover:text-sky-400 disabled:opacity-50"
    >
      {on ? (
        <>
          <BellRing className="size-4" /> Notificaciones activadas
        </>
      ) : (
        <>
          <Bell className="size-4" /> Recibir ofertas y novedades
        </>
      )}
    </button>
  );
}
