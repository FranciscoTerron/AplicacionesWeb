import { WifiOff } from "lucide-react";

export const metadata = {
  title: "Sin conexión — MA Piscinas",
};

// Fallback de navegación que sirve el service worker cuando no hay red.
export default function OfflinePage() {
  return (
    <div className="mx-auto flex max-w-md flex-col items-center gap-4 px-4 py-24 text-center">
      <div className="flex size-16 items-center justify-center rounded-full bg-muted">
        <WifiOff className="size-8 text-muted-foreground" />
      </div>
      <h1 className="text-2xl font-bold">Sin conexión</h1>
      <p className="text-muted-foreground">
        No pudimos conectarnos a internet. Revisá tu conexión y volvé a
        intentar.
      </p>
      <a
        href="/"
        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
      >
        Reintentar
      </a>
    </div>
  );
}
