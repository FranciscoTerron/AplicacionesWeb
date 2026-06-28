"use client";

import { SlidersHorizontal } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { FiltersSidebar } from "./filters-sidebar";
import type { Category } from "@/types/api";

export function MobileFilters({
  categories,
  className,
}: {
  categories: Category[];
  className?: string;
}) {
  return (
    <Sheet>
      <SheetTrigger asChild>
        <Button variant="outline" size="sm" className={className}>
          <SlidersHorizontal className="size-4" />
          Filtros
        </Button>
      </SheetTrigger>
      <SheetContent side="left" className="w-72 overflow-y-auto p-5">
        <SheetHeader className="px-0">
          <SheetTitle>Filtros</SheetTitle>
        </SheetHeader>
        <FiltersSidebar categories={categories} />
      </SheetContent>
    </Sheet>
  );
}
