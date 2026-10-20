"use client"

import Image from "next/image"
import Link from "next/link"
import { getExcursion, useStore } from "@/lib/store"
import { SalidasExplorer } from "./salidas-explorer"
import { EstadoBadge } from "./estado-badge"

/** Detalle de una excursión para el cliente, con sus salidas (CU 9) y paquetes (CU 7). */
export function ExcursionDetalle({ id }: { id: string }) {
  useStore() // se vuelve a dibujar cuando el administrativo modifica la oferta
  const e = getExcursion(id)

  // RN-01 (CU 4): al cliente solo se le muestran excursiones activas
  if (!e || e.estado !== "Activa") {
    return (
      <main className="mx-auto flex max-w-md flex-col items-center gap-3 px-4 py-24 text-center">
        <h1 className="font-display text-2xl font-semibold">Excursión no disponible</h1>
        <p className="text-muted-foreground">La excursión no existe o ya no se encuentra activa.</p>
        <Link href="/#excursiones" className="text-sm font-medium text-primary underline-offset-4 hover:underline">
          Volver al catálogo
        </Link>
      </main>
    )
  }

  const datos = [
    ["Dificultad", e.dificultad],
    ["Duración", `${e.duracionDias} ${e.duracionDias === 1 ? "día" : "días"}`],
    ["Acepta menores", e.aceptaMenores ? "Sí" : "No"],
    ["Certificado médico", e.requiereCertificado ? "Requerido" : "No requerido"],
  ]

  return (
    <main>
      <section className="relative h-72 md:h-96">
        <Image src={e.imagen || "/placeholder.svg"} alt={e.nombre} fill priority className="object-cover" />
        <div className="absolute inset-0 bg-gradient-to-t from-background via-background/30 to-transparent" />
      </section>
      <div className="mx-auto -mt-24 max-w-6xl px-4 pb-16">
        <div className="relative flex flex-col gap-3">
          <div className="flex gap-2">
            <EstadoBadge valor={e.estado} />
          </div>
          <h1 className="font-display text-4xl font-semibold text-balance md:text-5xl">{e.nombre}</h1>
          <p className="text-muted-foreground">{e.recorrido}</p>
        </div>

        <div className="mt-8 grid gap-8 lg:grid-cols-[1fr_320px]">
          <div className="flex flex-col gap-10">
            <p className="text-lg leading-relaxed text-pretty">{e.descripcion}</p>
            <SalidasExplorer excursionId={e.id} />
          </div>
          <aside className="flex h-fit flex-col gap-5 rounded-2xl border bg-card p-5 lg:sticky lg:top-24">
            <h2 className="font-display text-lg font-semibold">Ficha técnica</h2>
            <dl className="grid grid-cols-2 gap-4 text-sm">
              {datos.map(([k, v]) => (
                <div key={k} className="flex flex-col gap-0.5">
                  <dt className="text-muted-foreground">{k}</dt>
                  <dd className="font-medium">{v}</dd>
                </div>
              ))}
            </dl>
            <div className="flex flex-col gap-1 border-t pt-4 text-sm">
              <h3 className="font-semibold">Etapas</h3>
              <p className="text-muted-foreground">{e.etapas}</p>
            </div>
            <div className="flex flex-col gap-1 text-sm">
              <h3 className="font-semibold">Experiencia requerida</h3>
              <p className="text-muted-foreground">{e.experienciaRequerida}</p>
            </div>
            {e.equipamientoObligatorio.length > 0 && (
              <div className="flex flex-col gap-2 border-t pt-4">
                <h3 className="text-sm font-semibold">Equipamiento obligatorio</h3>
                <ul className="flex flex-wrap gap-1.5">
                  {e.equipamientoObligatorio.map((q) => (
                    <li key={q} className="rounded-md bg-muted px-2 py-1 text-xs">
                      {q}
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </aside>
        </div>
      </div>
    </main>
  )
}
