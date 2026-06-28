"use client";

import { useEffect } from "react";
import { TriangleAlert } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    console.error(error);
  }, [error]);

  return (
    <div className="mx-auto flex max-w-md flex-col items-center px-4 py-24 text-center">
      <TriangleAlert className="size-12 text-destructive" />
      <h1 className="mt-4 text-2xl font-bold">Algo salió mal</h1>
      <p className="mt-1 text-muted-foreground">
        No pudimos cargar esta página. Puede ser un problema de conexión con el
        servidor.
      </p>
      <Button onClick={reset} className="mt-6">
        Reintentar
      </Button>
    </div>
  );
}
