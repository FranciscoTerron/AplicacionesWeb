import Link from "next/link";
import { Waves } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function NotFound() {
  return (
    <div className="mx-auto flex max-w-md flex-col items-center px-4 py-24 text-center">
      <Waves className="size-14 text-primary/40" />
      <h1 className="mt-4 text-3xl font-extrabold">404</h1>
      <p className="mt-1 text-muted-foreground">
        No encontramos lo que buscabas. Quizás se fue al fondo de la pileta.
      </p>
      <div className="mt-6 flex gap-3">
        <Button asChild>
          <Link href="/">Ir al inicio</Link>
        </Button>
        <Button asChild variant="outline">
          <Link href="/productos">Ver productos</Link>
        </Button>
      </div>
    </div>
  );
}
