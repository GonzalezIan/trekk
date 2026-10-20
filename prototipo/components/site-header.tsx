"use client"

import Link from "next/link"
import { usePathname, useRouter } from "next/navigation"
import { Mountain, LogOut } from "lucide-react"
import { useStore } from "@/lib/store"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"

const navCliente = [
  { href: "/", label: "Excursiones" },
  { href: "/mis-reservas", label: "Mis reservas" },
  { href: "/perfil", label: "Mis datos" },
]

const navAdmin = [
  { href: "/admin", label: "Panel" },
  { href: "/admin/oferta", label: "Oferta" },
  { href: "/admin/reservas", label: "Reservas" },
  { href: "/admin/certificados", label: "Certificados" },
  { href: "/admin/reportes", label: "Reportes" },
]

export function SiteHeader() {
  const { hidratado, rol, cliente, cerrarSesion } = useStore()
  const pathname = usePathname()
  const router = useRouter()
  // El catálogo y los listados requieren sesión (CU 4, 7, 9 y 15), así que el invitado no tiene menú
  const nav = rol === "admin" ? navAdmin : rol === "cliente" ? navCliente : []

  return (
    <header className="sticky top-0 z-40 border-b bg-background/85 backdrop-blur">
      <div className="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4">
        <Link href={rol === "admin" ? "/admin" : "/"} className="flex items-center gap-2 font-display text-xl font-semibold">
          <span className="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
            <Mountain className="size-4" aria-hidden />
          </span>
          Trekk
          {rol === "admin" && (
            <span className="rounded-md bg-accent px-1.5 py-0.5 font-sans text-[10px] font-semibold uppercase tracking-wide text-accent-foreground">
              Admin
            </span>
          )}
        </Link>

        <nav aria-label="Principal" className="hidden flex-1 items-center gap-1 md:flex">
          {nav.map((item) => {
            const active = item.href === pathname || (item.href !== "/" && item.href !== "/admin" && pathname.startsWith(item.href))
            return (
              <Link
                key={item.href}
                href={item.href}
                aria-current={active ? "page" : undefined}
                className={cn(
                  "rounded-md px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground",
                  active && "bg-muted font-medium text-foreground",
                )}
              >
                {item.label}
              </Link>
            )
          })}
        </nav>

        <div className="ml-auto flex items-center gap-2">
          {!hidratado ? null : rol === "invitado" ? (
            <>
              <Button variant="ghost" size="sm" nativeButton={false} render={<Link href={["/", "/login", "/registro"].includes(pathname) ? "/login" : `/login?next=${encodeURIComponent(pathname)}`} />}>
                Iniciar sesión
              </Button>
              <Button size="sm" nativeButton={false} render={<Link href="/registro" />}>
                Registrarse
              </Button>
            </>
          ) : (
            <>
              <span className="hidden text-sm text-muted-foreground sm:inline">
                {rol === "admin" ? "Personal administrativo" : `${cliente.nombre} ${cliente.apellido}`}
              </span>
              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  cerrarSesion()
                  router.push("/")
                }}
              >
                <LogOut className="size-4" aria-hidden />
                Salir
              </Button>
            </>
          )}
        </div>
      </div>
      {nav.length > 1 && (
        <nav aria-label="Principal móvil" className="flex gap-1 overflow-x-auto border-t px-4 py-2 md:hidden">
          {nav.map((item) => (
            <Link key={item.href} href={item.href} className="shrink-0 rounded-md px-3 py-1 text-sm text-muted-foreground hover:text-foreground">
              {item.label}
            </Link>
          ))}
        </nav>
      )}
    </header>
  )
}
