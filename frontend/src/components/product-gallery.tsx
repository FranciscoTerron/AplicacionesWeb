"use client";

import Image from "next/image";
import { useState } from "react";
import { cn } from "@/lib/utils";
import type { ProductImage } from "@/types/api";

export function ProductGallery({
  images,
  name,
}: {
  images: ProductImage[];
  name: string;
}) {
  const [active, setActive] = useState(0);

  if (!images || images.length === 0) {
    return (
      <div className="flex aspect-square items-center justify-center rounded-lg border bg-muted text-muted-foreground">
        Sin imagen
      </div>
    );
  }

  return (
    <div className="space-y-3">
      <div className="relative aspect-square overflow-hidden rounded-lg border bg-muted">
        <Image
          src={images[active].url}
          alt={name}
          fill
          sizes="(max-width: 768px) 100vw, 50vw"
          className="object-contain"
          priority
        />
      </div>
      {images.length > 1 && (
        <div className="flex gap-2 overflow-x-auto">
          {images.map((img, i) => (
            <button
              key={img.public_id ?? i}
              onClick={() => setActive(i)}
              className={cn(
                "relative size-16 shrink-0 overflow-hidden rounded-md border bg-muted",
                i === active && "ring-2 ring-primary"
              )}
            >
              <Image
                src={img.url}
                alt={`${name} ${i + 1}`}
                fill
                sizes="64px"
                className="object-cover"
              />
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
