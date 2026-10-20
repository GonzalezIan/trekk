"use client"

import { useState } from "react"
import { useRouter } from "next/navigation"
import { CalendarDays, Clock, Users, Home, Check } from "lucide-react"
import { paquetes, refugios, salidas } from "@/lib/data"
import { formatFecha, horasHasta, usd, useStore } from "@/lib/store"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"

export function SalidasExplorer({ excursionId }: { excursionId: string }) {
  const { cupoOcupado } = useStore()
  const router = useRouter()
  const disponibles = salidas.filter(
    (s) => s.excursionId === excursionId && s.estado === "Programada" && horasHasta(s.fechaInicio, s.horaSalida) > 48,
  )
  const [seleccion, setSeleccion] = useState<string | null>(disponibles[0]?.id ?? null)
  const paquetesSalida = paquetes.filter((p) => p.salidaId === seleccion && p.estado === "Disponible")
  const salidaSel = salidas.find((s) => s.id === seleccion)
  const cupoLibreSel = salidaSel ? salidaSel.cupoTotal - cupoOcupado(salidaSel.id) : 0

  return (
    <div className="flex flex-col gap-10">
      <section aria-labelledby="salidas-titulo" className="flex flex-col gap-4">
        <div>
          <h2 id="salidas-titulo" className="font-display text-2xl font-semibold">Salidas programadas</h2>
          <p className="text-sm text-muted-foreground">Solo se muestran salidas con más de 48 horas de anticipación.</p>
        </div>
        {disponibles.length === 0 ? (
          <p className="rounded-xl border border-dashed p-6 text-center text-muted-foreground">
            No hay salidas programadas disponibles para esta excursión.
          </p>
        ) : (
          <div role="radiogroup" aria-label="Salidas" className="grid gap-3 sm:grid-cols-2">
            {disponibles.map((s) => {
              const libre = s.cupoTotal - cupoOcupado(s.id)
              const activa = seleccion === s.id
              return (
                <button
                  key={s.id}
                  role="radio"
                  aria-checked={activa}
                  onClick={() => setSeleccion(s.id)}
                  className={cn(
                    "flex flex-col gap-3 rounded-xl border bg-card p-4 text-left transition-colors hover:border-primary/50",
                    activa && "border-primary ring-1 ring-primary",
                  )}
                >
                  <div className="flex items-center justify-between">
                    <span className="flex items-center gap-2 font-medium">
                      <CalendarDays className="size-4 text-muted-foreground" aria-hidden />
                      {formatFecha(s.fechaInicio)}
                      {s.fechaFin !== s.fechaInicio && ` → ${formatFecha(s.fechaFin)}`}
                    </span>
                    {activa && <Check className="size-4 text-primary" aria-hidden />}
                  </div>
                  <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                    <span className="flex items-center gap-1.5">
                      <Clock className="size-3.5" aria-hidden /> {s.horaSalida} hs
                    </span>
                    <span className={cn("flex items-center gap-1.5", libre <= 3 && "text-accent")}>
                      <Users className="size-3.5" aria-hidden /> {libre} cupos libres
                    </span>
                  </div>
                  <div className="flex items-end justify-between gap-2 border-t pt-3">
                    <span className="text-xs text-muted-foreground">Incluye: {s.equipamientoIncluido.join(", ")}</span>
                    <span className="shrink-0 text-sm">
                      desde <strong className="font-display text-lg">{usd(s.precioBase)}</strong>
                    </span>
                  </div>
                </button>
              )
            })}
          </div>
        )}
      </section>

      {seleccion && (
        <section aria-labelledby="paquetes-titulo" className="flex flex-col gap-4">
          <div>
            <h2 id="paquetes-titulo" className="font-display text-2xl font-semibold">Paquetes disponibles</h2>
            <p className="text-sm text-muted-foreground">Precio por participante. Elegí la modalidad de contratación.</p>
          </div>
          {paquetesSalida.length === 0 && (
            <p className="rounded-xl border border-dashed p-6 text-center text-muted-foreground">
              No existen paquetes para la salida programada seleccionada.
            </p>
          )}
          <div className="grid gap-4 md:grid-cols-2">
            {paquetesSalida.map((p) => {
              const refugio = refugios.find((r) => r.id === p.refugioId)
              // RN-03 (CU 7): con alojamiento, la disponibilidad es el menor entre cupo de la salida y camas del convenio
              const disponibilidad = refugio ? Math.min(cupoLibreSel, refugio.camasDisponibles) : cupoLibreSel
              return (
                <article key={p.id} className="flex flex-col gap-4 rounded-2xl border bg-card p-5">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <h3 className="font-display text-xl font-semibold">{p.nombre}</h3>
                      <p className="text-sm text-muted-foreground">{p.descripcion}</p>
                    </div>
                    <p className="font-display text-2xl font-semibold">{usd(p.precioTotal)}</p>
                  </div>
                  {p.servicios.length > 0 && (
                    <ul className="flex flex-col gap-1.5 text-sm">
                      {p.servicios.map((sv) => (
                        <li key={sv} className="flex items-center gap-2">
                          <Check className="size-4 text-success" aria-hidden /> {sv}
                        </li>
                      ))}
                    </ul>
                  )}
                  {refugio && (
                    <p className="flex items-center gap-2 rounded-lg bg-muted px-3 py-2 text-sm">
                      <Home className="size-4 text-muted-foreground" aria-hidden />
                      Alojamiento en {refugio.nombre}
                    </p>
                  )}
                  <p className={cn("flex items-center gap-2 text-sm text-muted-foreground", disponibilidad <= 3 && "text-accent")}>
                    <Users className="size-4" aria-hidden />
                    {disponibilidad > 0
                      ? `${disponibilidad} ${disponibilidad === 1 ? "lugar disponible" : "lugares disponibles"}`
                      : "Sin disponibilidad"}
                  </p>
                  <Button className="mt-auto" size="lg" disabled={disponibilidad === 0} onClick={() => router.push(`/reservar/${p.id}`)}>
                    Reservar este paquete
                  </Button>
                </article>
              )
            })}
          </div>
        </section>
      )}
    </div>
  )
}
