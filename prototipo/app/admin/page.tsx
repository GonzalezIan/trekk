"use client"

import Link from "next/link"
import { ArrowRight } from "lucide-react"
import { salidas } from "@/lib/data"
import { contextoPaquete, formatFecha, getExcursion, horasHasta, usd, useStore } from "@/lib/store"
import { PageHeader } from "@/components/trekk/page-header"
import { EstadoBadge } from "@/components/trekk/estado-badge"

export default function AdminDashboard() {
  const { reservas, certificados, cupoOcupado } = useStore()
  const activas = reservas.filter((r) => r.estado === "Pendiente" || r.estado === "Confirmada")
  const pendientesCert = certificados.filter((c) => c.estado === "Pendiente de verificación")
  const facturado = reservas.filter((r) => r.estado !== "Cancelada").reduce((a, r) => a + r.importeTotal, 0)
  const proximas = salidas
    .filter((s) => s.estado === "Programada" && horasHasta(s.fechaInicio) > 0)
    .sort((a, b) => a.fechaInicio.localeCompare(b.fechaInicio))
    .slice(0, 5)

  const kpis = [
    { label: "Reservas activas", valor: activas.length, href: "/admin/reservas" },
    { label: "Pendientes de pago total", valor: activas.filter((r) => r.situacionPago !== "Pago completo").length, href: "/admin/reservas" },
    { label: "Certificados por verificar", valor: pendientesCert.length, href: "/admin/certificados" },
    { label: "Importe reservado", valor: usd(facturado), href: "/admin/reportes" },
  ]

  return (
    <>
      <PageHeader eyebrow="Administración" title="Panel general" description="Resumen operativo de salidas, reservas y verificaciones." />
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {kpis.map((k) => (
          <Link key={k.label} href={k.href} className="flex flex-col gap-2 rounded-2xl border bg-card p-5 transition-colors hover:border-primary/50">
            <span className="text-sm text-muted-foreground">{k.label}</span>
            <span className="font-display text-3xl font-semibold">{k.valor}</span>
          </Link>
        ))}
      </div>

      <div className="mt-8 grid gap-6 lg:grid-cols-2">
        <section className="flex flex-col gap-4 rounded-2xl border bg-card p-5">
          <div className="flex items-center justify-between">
            <h2 className="font-display text-xl font-semibold">Próximas salidas</h2>
            <Link href="/admin/oferta" className="flex items-center gap-1 text-sm text-primary">
              Ver oferta <ArrowRight className="size-3.5" aria-hidden />
            </Link>
          </div>
          <ul className="flex flex-col divide-y">
            {proximas.map((s) => {
              const ocupado = cupoOcupado(s.id)
              return (
                <li key={s.id} className="flex flex-col gap-2 py-3">
                  <div className="flex items-center justify-between gap-2">
                    <span className="font-medium">{getExcursion(s.excursionId)?.nombre}</span>
                    <span className="text-sm text-muted-foreground">{formatFecha(s.fechaInicio)}</span>
                  </div>
                  <div className="flex items-center gap-3">
                    <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-muted" aria-hidden>
                      <div className="h-full rounded-full bg-primary" style={{ width: `${(ocupado / s.cupoTotal) * 100}%` }} />
                    </div>
                    <span className="text-xs tabular-nums text-muted-foreground">
                      {ocupado}/{s.cupoTotal} cupos
                    </span>
                  </div>
                </li>
              )
            })}
          </ul>
        </section>

        <section className="flex flex-col gap-4 rounded-2xl border bg-card p-5">
          <div className="flex items-center justify-between">
            <h2 className="font-display text-xl font-semibold">Últimas reservas</h2>
            <Link href="/admin/reservas" className="flex items-center gap-1 text-sm text-primary">
              Ver todas <ArrowRight className="size-3.5" aria-hidden />
            </Link>
          </div>
          <ul className="flex flex-col divide-y">
            {reservas.slice(0, 5).map((r) => {
              const { excursion } = contextoPaquete(r.paqueteId)
              return (
                <li key={r.id} className="flex items-center justify-between gap-3 py-3">
                  <div>
                    <p className="font-medium">{excursion.nombre}</p>
                    <p className="text-xs text-muted-foreground">
                      {r.id} · {r.participantes.length} pax
                    </p>
                  </div>
                  <EstadoBadge valor={r.estado} />
                </li>
              )
            })}
          </ul>
        </section>
      </div>
    </>
  )
}
