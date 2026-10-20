import Image from "next/image"
import Link from "next/link"
import { Clock, MapPin, TrendingUp, Stethoscope } from "lucide-react"
import type { Excursion } from "@/lib/data"
import { EstadoBadge } from "./estado-badge"

export function ExcursionCard({ excursion: e }: { excursion: Excursion }) {
  return (
    <Link
      href={`/excursiones/${e.id}`}
      className="group flex flex-col overflow-hidden rounded-2xl border bg-card transition-shadow hover:shadow-lg focus-visible:ring-3 focus-visible:ring-ring/50"
    >
      <div className="relative aspect-[16/10] overflow-hidden">
        <Image
          src={e.imagen || "/placeholder.svg"}
          alt={e.nombre}
          fill
          sizes="(min-width: 640px) 50vw, 100vw"
          className="object-cover transition-transform duration-500 group-hover:scale-105"
        />
        <div className="absolute left-3 top-3 flex gap-2">
          <EstadoBadge valor={e.dificultad} className="bg-background/90 backdrop-blur" />
          {e.requiereCertificado && (
            <span className="inline-flex items-center gap-1 rounded-full border border-border bg-background/90 px-2 py-0.5 text-xs backdrop-blur">
              <Stethoscope className="size-3" aria-hidden /> Certificado médico
            </span>
          )}
        </div>
      </div>
      <div className="flex flex-1 flex-col gap-3 p-5">
        <div className="flex items-start justify-between gap-3">
          <h3 className="font-display text-xl font-semibold">{e.nombre}</h3>
          <EstadoBadge valor={e.estado} />
        </div>
        <p className="flex items-start gap-1.5 text-sm text-muted-foreground">
          <MapPin className="mt-0.5 size-4 shrink-0" aria-hidden />
          {e.recorrido}
        </p>
        <div className="mt-auto flex flex-wrap gap-4 pt-2 text-sm">
          <span className="flex items-center gap-1.5">
            <Clock className="size-4 text-muted-foreground" aria-hidden />
            {e.duracionDias} {e.duracionDias === 1 ? "día" : "días"}
          </span>
          <span className="flex items-center gap-1.5">
            <TrendingUp className="size-4 text-muted-foreground" aria-hidden />
            Dificultad {e.dificultad.toLowerCase()}
          </span>
        </div>
      </div>
    </Link>
  )
}
