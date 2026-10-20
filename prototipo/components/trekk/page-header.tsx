"use client"

import type { ReactNode } from "react"
import Link from "next/link"
import { usePathname } from "next/navigation"
import { Lock } from "lucide-react"
import { useStore, type Rol } from "@/lib/store"
import { Button } from "@/components/ui/button"

export function PageHeader({ eyebrow, title, description, actions }: { eyebrow?: string; title: string; description?: string; actions?: ReactNode }) {
  return (
    <div className="flex flex-col gap-4 pb-8 md:flex-row md:items-end md:justify-between">
      <div className="flex flex-col gap-2">
        {eyebrow && <p className="text-xs font-semibold uppercase tracking-widest text-accent">{eyebrow}</p>}
        <h1 className="font-display text-3xl font-semibold text-balance md:text-4xl">{title}</h1>
        {description && <p className="max-w-2xl text-muted-foreground text-pretty">{description}</p>}
      </div>
      {actions}
    </div>
  )
}

/** Muestra el contenido solo si el usuario autenticado tiene alguno de los roles indicados (RNF-05). */
export function RequireRol({ roles, children, mensaje }: { roles: Exclude<Rol, "invitado">[]; children: ReactNode; mensaje: string }) {
  const { hidratado, rol } = useStore()
  const pathname = usePathname()

  if (!hidratado) return <main className="min-h-[60vh]" aria-busy="true" />
  if (rol !== "invitado" && roles.includes(rol)) return <>{children}</>

  const invitado = rol === "invitado"
  return (
    <main className="mx-auto flex max-w-md flex-col items-center gap-3 px-4 py-24 text-center">
      <span className="flex size-12 items-center justify-center rounded-full bg-muted">
        <Lock className="size-5 text-muted-foreground" aria-hidden />
      </span>
      <h1 className="font-display text-2xl font-semibold">{invitado ? "Iniciá sesión para continuar" : "Acceso restringido"}</h1>
      <p className="text-muted-foreground text-pretty">
        {invitado ? mensaje : "Esta sección no está disponible para tu perfil de usuario."}
      </p>
      {invitado ? (
        <div className="mt-2 flex gap-2">
          <Button nativeButton={false} render={<Link href={`/login?next=${encodeURIComponent(pathname)}`} />}>
            Iniciar sesión
          </Button>
          <Button variant="outline" nativeButton={false} render={<Link href={`/registro?next=${encodeURIComponent(pathname)}`} />}>
            Crear cuenta
          </Button>
        </div>
      ) : (
        <Button className="mt-2" nativeButton={false} render={<Link href={rol === "admin" ? "/admin" : "/"} />}>
          Ir a mi inicio
        </Button>
      )}
    </main>
  )
}
