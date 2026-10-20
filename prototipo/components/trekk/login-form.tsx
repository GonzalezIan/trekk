"use client"

import { useState, type FormEvent } from "react"
import Link from "next/link"
import { useRouter, useSearchParams } from "next/navigation"
import { Mountain } from "lucide-react"
import { useStore } from "@/lib/store"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"

/** "pagina": formulario de /login. "tarjeta": recuadro de ingreso de la portada. */
export function LoginForm({ variante = "pagina" }: { variante?: "pagina" | "tarjeta" }) {
  const { iniciarSesion } = useStore()
  const router = useRouter()
  const nextParam = useSearchParams().get("next")
  // Solo rutas internas, para no redirigir a sitios externos
  const next = nextParam?.startsWith("/") && !nextParam.startsWith("//") ? nextParam : null
  const [usuario, setUsuario] = useState("")
  const [clave, setClave] = useState("")
  const [error, setError] = useState("")

  function entrar(rol: "cliente" | "admin") {
    iniciarSesion(rol)
    router.push(rol === "admin" ? "/admin" : next ?? "/")
  }

  function onSubmit(e: FormEvent) {
    e.preventDefault()
    if (!usuario || !clave) return setError("Debe completar todos los campos obligatorios")
    if (usuario === "admin") return entrar("admin")
    if (usuario === "carlam") return entrar("cliente")
    setError("Usuario o contraseña incorrectos")
  }

  const linkRegistro = next ? `/registro?next=${encodeURIComponent(next)}` : "/registro"

  if (variante === "tarjeta") {
    return (
      <form onSubmit={onSubmit} noValidate aria-labelledby="bienvenida" className="flex flex-col gap-4 rounded-2xl border bg-card p-6 text-card-foreground shadow-xl">
        <div className="flex flex-col items-center gap-2 text-center">
          <span className="flex items-center gap-2 font-display text-xl font-semibold">
            <span className="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
              <Mountain className="size-4" aria-hidden />
            </span>
            Trekk
          </span>
          <h2 id="bienvenida" className="font-display text-2xl font-semibold">
            ¡Bienvenido/a!
          </h2>
          <p className="text-sm text-muted-foreground">Ingresá para ver excursiones, reservar y gestionar tus reservas.</p>
        </div>
        <div className="flex flex-col gap-2">
          <Label htmlFor="usuario">Usuario</Label>
          <Input id="usuario" autoComplete="username" value={usuario} onChange={(e) => setUsuario(e.target.value)} />
        </div>
        <div className="flex flex-col gap-2">
          <Label htmlFor="clave">Contraseña</Label>
          <Input id="clave" type="password" autoComplete="current-password" value={clave} onChange={(e) => setClave(e.target.value)} />
        </div>
        {error && (
          <p role="alert" className="text-sm text-destructive">
            {error}
          </p>
        )}
        <Button type="submit" size="lg">
          Ingresar
        </Button>
        <p className="text-center text-sm text-muted-foreground">
          ¿Primera vez?{" "}
          <Link href={linkRegistro} className="font-medium text-primary underline-offset-4 hover:underline">
            Registrate acá
          </Link>
        </p>
        <p className="border-t pt-3 text-center text-xs text-muted-foreground">
          Demo:{" "}
          <button type="button" className="font-medium text-primary underline-offset-4 hover:underline" onClick={() => entrar("cliente")}>
            entrar como cliente
          </button>{" "}
          ·{" "}
          <button type="button" className="font-medium text-primary underline-offset-4 hover:underline" onClick={() => entrar("admin")}>
            como admin
          </button>
        </p>
      </form>
    )
  }

  return (
    <div className="flex flex-col gap-6">
      <form onSubmit={onSubmit} className="flex flex-col gap-4 rounded-2xl border bg-card p-6" noValidate>
        <div className="flex flex-col gap-2">
          <Label htmlFor="usuario">Usuario</Label>
          <Input id="usuario" autoComplete="username" value={usuario} onChange={(e) => setUsuario(e.target.value)} />
        </div>
        <div className="flex flex-col gap-2">
          <Label htmlFor="clave">Contraseña</Label>
          <Input id="clave" type="password" autoComplete="current-password" value={clave} onChange={(e) => setClave(e.target.value)} />
        </div>
        {error && (
          <p role="alert" className="text-sm text-destructive">
            {error}
          </p>
        )}
        <Button type="submit" size="lg">
          Iniciar sesión
        </Button>
        <p className="text-center text-sm text-muted-foreground">
          ¿No tenés cuenta?{" "}
          <Link href={linkRegistro} className="font-medium text-primary underline-offset-4 hover:underline">
            Registrarse
          </Link>
        </p>
      </form>

      <div className="flex flex-col gap-3 rounded-2xl border border-dashed p-5">
        <p className="text-sm font-medium">Accesos de demostración</p>
        <p className="text-xs text-muted-foreground">
          Cliente: <code>carlam</code> · Administrativo: <code>admin</code> (cualquier contraseña)
        </p>
        <div className="grid grid-cols-2 gap-2">
          <Button variant="outline" onClick={() => entrar("cliente")}>
            Entrar como cliente
          </Button>
          <Button variant="outline" onClick={() => entrar("admin")}>
            Entrar como admin
          </Button>
        </div>
      </div>
    </div>
  )
}
