import Link from "next/link";
import { Suspense } from "react";
import { Waves } from "lucide-react";
import { SearchBar } from "./search-bar";
import { CartBadge } from "./cart-badge";
import { AccountMenu } from "./account-menu";

export function Header() {
  return (
    <header className="sticky top-0 z-40 border-b bg-white text-foreground shadow-sm">
      <div className="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-2.5 md:flex-row md:items-center md:gap-4">
        <Link
          href="/"
          className="flex shrink-0 items-center gap-2 text-lg font-extrabold tracking-tight text-primary"
        >
          <Waves className="size-6" />
          MA Piscinas
        </Link>
        <Suspense fallback={<div className="h-10 flex-1" />}>
          <SearchBar />
        </Suspense>
        <nav className="flex items-center gap-1">
          <Link
            href="/productos"
            className="rounded-md px-2 py-1.5 text-sm font-medium hover:bg-muted"
          >
            Productos
          </Link>
          <AccountMenu />
          <CartBadge />
        </nav>
      </div>
    </header>
  );
}
