import Link from "next/link";
import {
  Waves,
  Truck,
  ShieldCheck,
  Headset,
  CreditCard,
  Mail,
  Phone,
  MapPin,
} from "lucide-react";
import { PushToggle } from "@/components/push-toggle";

const TRUST = [
  { icon: Truck, title: "Envíos a todo el país", desc: "Despacho en 24/48 hs" },
  { icon: ShieldCheck, title: "Compra protegida", desc: "Pago 100% seguro" },
  { icon: Headset, title: "Soporte real", desc: "Te asesoramos siempre" },
  { icon: CreditCard, title: "Múltiples pagos", desc: "Mercado Pago, tarjeta y más" },
];

export function Footer() {
  return (
    <footer className="mt-16">
      {/* Trust strip */}
      <div className="border-y bg-sky-50/60">
        <div className="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-4 py-6 md:grid-cols-4">
          {TRUST.map((t) => (
            <div key={t.title} className="flex items-center gap-3">
              <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                <t.icon className="size-5" />
              </div>
              <div>
                <p className="text-sm font-semibold leading-tight">{t.title}</p>
                <p className="text-xs text-muted-foreground">{t.desc}</p>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Main */}
      <div className="bg-slate-900 text-slate-300">
        <div className="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <Link
              href="/"
              className="flex items-center gap-2 text-lg font-extrabold text-white"
            >
              <Waves className="size-6 text-sky-400" />
              MA Piscinas
            </Link>
            <p className="mt-3 text-sm text-slate-400">
              Todo para tu piscina: bombas, accesorios, productos de
              mantenimiento y más. Calidad y mejor precio.
            </p>
            <div className="mt-4">
              <PushToggle />
            </div>
          </div>

          <div>
            <h2 className="mb-3 text-sm font-semibold text-white">Comprar</h2>
            <ul className="space-y-0.5 text-sm">
              <li>
                <Link href="/productos" className="inline-block py-1 hover:text-sky-400">
                  Todos los productos
                </Link>
              </li>
              <li>
                <Link
                  href="/productos?featured=1"
                  className="inline-block py-1 hover:text-sky-400"
                >
                  Destacados
                </Link>
              </li>
              <li>
                <Link href="/carrito" className="inline-block py-1 hover:text-sky-400">
                  Mi carrito
                </Link>
              </li>
            </ul>
          </div>

          <div>
            <h2 className="mb-3 text-sm font-semibold text-white">Mi cuenta</h2>
            <ul className="space-y-0.5 text-sm">
              <li>
                <Link href="/login" className="inline-block py-1 hover:text-sky-400">
                  Iniciar sesión
                </Link>
              </li>
              <li>
                <Link href="/registro" className="inline-block py-1 hover:text-sky-400">
                  Crear cuenta
                </Link>
              </li>
              <li>
                <Link href="/cuenta/ordenes" className="inline-block py-1 hover:text-sky-400">
                  Mis pedidos
                </Link>
              </li>
            </ul>
          </div>

          <div>
            <h2 className="mb-3 text-sm font-semibold text-white">Contacto</h2>
            <ul className="space-y-0.5 text-sm">
              <li className="flex items-center gap-2">
                <Mail className="size-4 text-sky-400" /> ventas@mapiscinas.com
              </li>
              <li className="flex items-center gap-2">
                <Phone className="size-4 text-sky-400" /> +54 11 5555-5555
              </li>
              <li className="flex items-center gap-2">
                <MapPin className="size-4 text-sky-400" /> Gaiman, Chubut, Argentina
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom bar */}
        <div className="border-t border-white/10">
          <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-4 text-xs text-slate-400 sm:flex-row">
            <p>© {new Date().getFullYear()} MA Piscinas. Proyecto universitario.</p>
            <p>Hecho con 💧 para tu piscina.</p>
          </div>
        </div>
      </div>
    </footer>
  );
}
