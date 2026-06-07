"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { Button } from "@/components/ui/button";

export function Pagination({
  page,
  lastPage,
}: {
  page: number;
  lastPage: number;
}) {
  const router = useRouter();
  const params = useSearchParams();

  if (lastPage <= 1) return null;

  function goTo(p: number) {
    const sp = new URLSearchParams(params.toString());
    sp.set("page", String(p));
    router.push(`/productos?${sp.toString()}`);
  }

  return (
    <div className="mt-6 flex items-center justify-center gap-2">
      <Button
        variant="outline"
        size="sm"
        disabled={page <= 1}
        onClick={() => goTo(page - 1)}
      >
        <ChevronLeft /> Anterior
      </Button>
      <span className="px-2 text-sm text-muted-foreground">
        Página {page} de {lastPage}
      </span>
      <Button
        variant="outline"
        size="sm"
        disabled={page >= lastPage}
        onClick={() => goTo(page + 1)}
      >
        Siguiente <ChevronRight />
      </Button>
    </div>
  );
}
