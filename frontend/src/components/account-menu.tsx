"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { User as UserIcon, Package, LogOut, LogIn } from "lucide-react";
import { useAuth } from "@/context/auth-context";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

export function AccountMenu() {
  const { user, isAuthenticated, logout } = useAuth();
  const router = useRouter();

  async function handleLogout() {
    await logout();
    router.push("/");
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger className="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm font-medium outline-none hover:bg-black/5">
        <UserIcon className="size-5" />
        {/* En mobile el texto queda sr-only: sin él, el botón no tiene nombre accesible (HU-F15). */}
        <span className="max-w-28 truncate max-sm:sr-only sm:inline">
          {isAuthenticated ? user?.name : "Mi cuenta"}
        </span>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-52">
        {isAuthenticated ? (
          <>
            <DropdownMenuLabel className="truncate">
              {user?.email}
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
              <Link href="/cuenta/ordenes">
                <Package className="size-4" /> Mis pedidos
              </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={handleLogout}>
              <LogOut className="size-4" /> Cerrar sesión
            </DropdownMenuItem>
          </>
        ) : (
          <>
            <DropdownMenuItem asChild>
              <Link href="/login">
                <LogIn className="size-4" /> Iniciar sesión
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <Link href="/registro">
                <UserIcon className="size-4" /> Crear cuenta
              </Link>
            </DropdownMenuItem>
          </>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
