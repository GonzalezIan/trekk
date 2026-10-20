"use client"

import { Suspense } from "react"
import Link from "next/link"
import { ArrowRight, CalendarDays, CreditCard, UserRound } from "lucide-react"
import { excursiones } from "@/lib/data"
import { useStore } from "@/lib/store"
import { Button } from "@/components/ui/button"
import { ExcursionCard } from "./excursion-card"
import { LoginForm } from "./login-form"

const pasos = [
  { icono: UserRound, titulo: "Creá tu cuenta", texto: "Registrate como cliente con tu DNI. Solo necesitás hacerlo una vez." },
  { icono: CalendarDays, titulo: "Elegí salida y paquete", texto: "Mirá las salidas programadas de cada excursión y el paquete que mejor te quede." },
  { icono: CreditCard, titulo: "Reservá y pagá", texto: "Cargá los participantes, sumá equipamiento de alquiler si te falta y aboná la seña o el total." },
]

/** Recuadro de ingreso de la portada: solo se muestra si no hay sesión iniciada. */
export function IngresoPortada() {
  const { hidratado, rol } = useStore()
  if (!hidratado || rol !== "invitado") return null
  return (
    <div className="w-full max-w-sm justify-self-center md:justify-self-end">
      <Suspense>
        <LoginForm variante="tarjeta" />
      </Suspense>
    </div>
  )
}

/** Listado de excursiones (CU 4): requiere un usuario autenticado. */
export function Catalogo() {
  const { hidratado, rol } = useStore()

  if (!hidratado) return <section className="min-h-96" aria-busy="true" />

  if (rol === "invitado") {
    return (
      <section id="excursiones" aria-labelledby="como-funciona" className="mx-auto max-w-6xl px-4 py-16">
        <div className="flex flex-col gap-2 pb-8">
          <h2 id="como-funciona" className="font-display text-3xl font-semibold">Cómo funciona</h2>
          <p className="text-muted-foreground">Para ver el catálogo de excursiones, sus salidas y paquetes, ingresá con tu cuenta de cliente desde el recuadro de arriba.</p>
        </div>
        <ol className="grid gap-4 md:grid-cols-3">
          {pasos.map((p, i) => (
            <li key={p.titulo} className="flex flex-col gap-3 rounded-2xl border bg-card p-5">
              <span className="flex size-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                <p.icono className="size-5" aria-hidden />
              </span>
              <h3 className="font-display text-lg font-semibold">
                {i + 1}. {p.titulo}
              </h3>
              <p className="text-sm text-muted-foreground">{p.texto}</p>
            </li>
          ))}
        </ol>
      </section>
    )
  }

  if (rol === "admin") {
    return (
      <section id="excursiones" className="mx-auto max-w-6xl px-4 py-16">
        <div className="flex flex-col items-start gap-3 rounded-2xl border bg-card p-6">
          <h2 className="font-display text-2xl font-semibold">Estás como personal administrativo</h2>
          <p className="text-muted-foreground">Consultá todas las excursiones, salidas programadas y paquetes, sin importar su estado, desde Oferta.</p>
          <Button nativeButton={false} render={<Link href="/admin/oferta" />}>
            Ir a Oferta <ArrowRight aria-hidden />
          </Button>
        </div>
      </section>
    )
  }

  const activas = excursiones.filter((e) => e.estado === "Activa")
  return (
    <section id="excursiones" aria-labelledby="catalogo" className="mx-auto max-w-6xl px-4 py-16">
      <div className="flex flex-col gap-2 pb-8">
        <h2 id="catalogo" className="font-display text-3xl font-semibold">Catálogo de excursiones</h2>
        <p className="text-muted-foreground">Seleccioná una excursión para ver sus salidas programadas.</p>
      </div>
      {activas.length === 0 ? (
        <p className="rounded-xl border border-dashed p-6 text-center text-muted-foreground">No hay excursiones disponibles por el momento.</p>
      ) : (
        <div className="grid gap-6 sm:grid-cols-2">
          {activas.map((e) => (
            <ExcursionCard key={e.id} excursion={e} />
          ))}
        </div>
      )}
    </section>
  )
}
