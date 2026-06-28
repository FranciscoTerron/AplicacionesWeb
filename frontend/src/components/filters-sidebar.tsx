"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import type { Category } from "@/types/api";

export function FiltersSidebar({ categories }: { categories: Category[] }) {
  const router = useRouter();
  const params = useSearchParams();

  const [minPrice, setMinPrice] = useState(params.get("min_price") ?? "");
  const [maxPrice, setMaxPrice] = useState(params.get("max_price") ?? "");
  const activeCategory = params.get("category") ?? "";

  function applyParam(updates: Record<string, string | null>) {
    const sp = new URLSearchParams(params.toString());
    for (const [k, v] of Object.entries(updates)) {
      if (v === null || v === "") sp.delete(k);
      else sp.set(k, v);
    }
    sp.delete("page"); // resetear paginación al filtrar
    router.push(`/productos?${sp.toString()}`);
  }

  return (
    <aside className="space-y-6">
      <div>
        <h3 className="mb-2 text-sm font-semibold">Categorías</h3>
        <ul className="space-y-1 text-sm">
          <li>
            <button
              onClick={() => applyParam({ category: null })}
              className={`hover:text-primary ${
                !activeCategory ? "font-semibold text-primary" : ""
              }`}
            >
              Todas
            </button>
          </li>
          {categories.map((c) => (
            <li key={c.id}>
              <button
                onClick={() => applyParam({ category: c.id })}
                className={`text-left hover:text-primary ${
                  activeCategory === c.id ? "font-semibold text-primary" : ""
                }`}
              >
                {c.name}
              </button>
            </li>
          ))}
        </ul>
      </div>

      <div>
        <h3 className="mb-2 text-sm font-semibold">Precio</h3>
        <div className="flex items-center gap-2">
          <Input
            type="number"
            placeholder="Mín"
            value={minPrice}
            onChange={(e) => setMinPrice(e.target.value)}
            className="h-9"
          />
          <span className="text-muted-foreground">-</span>
          <Input
            type="number"
            placeholder="Máx"
            value={maxPrice}
            onChange={(e) => setMaxPrice(e.target.value)}
            className="h-9"
          />
        </div>
        <Button
          size="sm"
          className="mt-2 w-full"
          onClick={() =>
            applyParam({
              min_price: minPrice || null,
              max_price: maxPrice || null,
            })
          }
        >
          Aplicar
        </Button>
      </div>

      <Button
        variant="ghost"
        size="sm"
        className="w-full"
        onClick={() => router.push("/productos")}
      >
        Limpiar filtros
      </Button>
    </aside>
  );
}
