"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { useState } from "react";
import { Search } from "lucide-react";
import { Input } from "@/components/ui/input";

export function SearchBar() {
  const router = useRouter();
  const params = useSearchParams();
  const [value, setValue] = useState(params.get("search") ?? "");

  function submit(e: React.FormEvent) {
    e.preventDefault();
    const q = value.trim();
    router.push(q ? `/productos?search=${encodeURIComponent(q)}` : "/productos");
  }

  return (
    <form onSubmit={submit} className="relative flex-1 max-w-2xl">
      <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
      <Input
        type="search"
        value={value}
        onChange={(e) => setValue(e.target.value)}
        placeholder="Buscar piscinas, accesorios..."
        className="h-10 bg-white pl-9 text-foreground"
        aria-label="Buscar productos"
      />
    </form>
  );
}
