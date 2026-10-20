"use client"

import { useState, type FormEvent } from "react"
import { Printer, Star } from "lucide-react"
import { clienteDemo, clientesExtra } from "@/lib/data"
import { contextoPaquete, formatFecha, useStore } from "@/lib/store"
import { PageHeader } from "@/components/trekk/page-header"
import { Tabla } from "@/components/trekk/tabla"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { AvisoError } from "@/components/admin/avisos"

interface Reporte {
  desde: string
  hasta: string
  clientes: { dni: string; nombre: string; apellido: string; cantidad: number }[]
  actividades: { excursion: string; participantes: number }[]
  conformidad: { excursion: string; promedio: number; cantidad: number; comentarios: string[] }[]
}

/** CU 27 – Generar reporte periódico */
export default function ReportesPage() {
  const { reservas, conformidades, cliente } = useStore()
  const [desde, setDesde] = useState("")
  const [hasta, setHasta] = useState("")
  const [error, setError] = useState("")
  const [reporte, setReporte] = useState<Reporte | null>(null)

  function generar(e: FormEvent) {
    e.preventDefault()
    setReporte(null)
    // 3.1 y 3.2
    if (!desde || !hasta) return setError("Debe ingresar ambas fechas del período a consultar.")
    if (desde > hasta) return setError("La fecha de inicio debe ser anterior o igual a la fecha de fin.")
    // Paso 5 (RN-01): solo reservas "Realizada" cuya salida termina dentro del período
    const realizadas = reservas.filter((r) => {
      if (r.estado !== "Realizada") return false
      const { salida } = contextoPaquete(r.paqueteId)
      return salida.fechaFin >= desde && salida.fechaFin <= hasta
    })
    // 3.3
    if (realizadas.length === 0) return setError("No se encontraron datos para el período seleccionado.")
    setError("")

    const personas = [clienteDemo, ...clientesExtra, cliente]
    const excursionDe = (paqueteId: string) => contextoPaquete(paqueteId).excursion.nombre

    // Paso 6 (RN-02): clientes frecuentes
    const porCliente = new Map<string, number>()
    for (const r of realizadas) porCliente.set(r.clienteDni, (porCliente.get(r.clienteDni) ?? 0) + 1)
    const clientes = [...porCliente]
      .map(([dni, cantidad]) => {
        const p = personas.find((x) => x.dni === dni)
        return { dni, nombre: p?.nombre ?? "—", apellido: p?.apellido ?? "—", cantidad }
      })
      .sort((a, b) => b.cantidad - a.cantidad)

    // Paso 7 (RN-03): actividades frecuentes
    const porExcursion = new Map<string, number>()
    for (const r of realizadas) porExcursion.set(excursionDe(r.paqueteId), (porExcursion.get(excursionDe(r.paqueteId)) ?? 0) + r.participantes.length)
    const actividades = [...porExcursion].map(([excursion, participantes]) => ({ excursion, participantes })).sort((a, b) => b.participantes - a.participantes)

    // Paso 8 (RN-04): conformidad
    const porConformidad = new Map<string, { suma: number; cantidad: number; comentarios: string[] }>()
    for (const c of conformidades) {
      const r = realizadas.find((x) => x.id === c.reservaId)
      if (!r) continue
      const nombre = excursionDe(r.paqueteId)
      const acc = porConformidad.get(nombre) ?? { suma: 0, cantidad: 0, comentarios: [] }
      acc.suma += c.valoracion
      acc.cantidad++
      if (c.comentario.trim()) acc.comentarios.push(c.comentario.trim())
      porConformidad.set(nombre, acc)
    }
    const conformidad = [...porConformidad]
      .map(([excursion, x]) => ({ excursion, promedio: x.suma / x.cantidad, cantidad: x.cantidad, comentarios: x.comentarios }))
      .sort((a, b) => b.promedio - a.promedio)

    setReporte({ desde, hasta, clientes, actividades, conformidad })
  }

  return (
    <>
      <PageHeader eyebrow="Administración" title="Generar reporte periódico" description="Clientes frecuentes, actividades frecuentes y conformidad, sobre las reservas realizadas en el período." />

      <form onSubmit={generar} noValidate className="flex flex-wrap items-end gap-3 rounded-2xl border bg-card p-4 print:hidden">
        <div className="flex flex-col gap-1.5">
          <Label htmlFor="rp-desde">Fecha de inicio</Label>
          <Input id="rp-desde" type="date" value={desde} onChange={(e) => setDesde(e.target.value)} className="w-44" />
        </div>
        <div className="flex flex-col gap-1.5">
          <Label htmlFor="rp-hasta">Fecha de fin</Label>
          <Input id="rp-hasta" type="date" value={hasta} onChange={(e) => setHasta(e.target.value)} className="w-44" />
        </div>
        <Button type="submit">Generar reporte</Button>
        {reporte && (
          <Button type="button" variant="outline" onClick={() => window.print()}>
            <Printer aria-hidden /> Exportar / imprimir
          </Button>
        )}
      </form>
      <div className="mt-3 print:hidden">
        <AvisoError mensaje={error} />
      </div>

      {reporte && (
        <div className="mt-6 flex flex-col gap-6">
          <p className="text-sm text-muted-foreground">
            Período: <strong className="text-foreground">{formatFecha(reporte.desde)}</strong> al <strong className="text-foreground">{formatFecha(reporte.hasta)}</strong> · reservas en estado
            “Realizada”
          </p>

          <section className="flex flex-col gap-3">
            <h2 className="font-display text-xl font-semibold">Clientes frecuentes</h2>
            <Tabla headers={["Nombre", "Apellido", "DNI", "Excursiones realizadas"]}>
              {reporte.clientes.map((c) => (
                <tr key={c.dni}>
                  <td className="font-medium">{c.nombre}</td>
                  <td>{c.apellido}</td>
                  <td className="font-mono text-xs">{c.dni}</td>
                  <td className="tabular-nums">{c.cantidad}</td>
                </tr>
              ))}
            </Tabla>
          </section>

          <section className="flex flex-col gap-3">
            <h2 className="font-display text-xl font-semibold">Actividades frecuentes</h2>
            <Tabla headers={["Excursión", "Participantes"]}>
              {reporte.actividades.map((a) => (
                <tr key={a.excursion}>
                  <td className="font-medium">{a.excursion}</td>
                  <td className="tabular-nums">{a.participantes}</td>
                </tr>
              ))}
            </Tabla>
          </section>

          <section className="flex flex-col gap-3">
            <h2 className="font-display text-xl font-semibold">Conformidad</h2>
            {reporte.conformidad.length === 0 ? (
              <p className="rounded-xl border border-dashed p-6 text-center text-muted-foreground">No hay valoraciones registradas en el período.</p>
            ) : (
              <Tabla headers={["Excursión", "Valoración promedio", "Valoraciones", "Comentarios"]}>
                {reporte.conformidad.map((c) => (
                  <tr key={c.excursion}>
                    <td className="font-medium">{c.excursion}</td>
                    <td className="whitespace-nowrap tabular-nums">
                      <span className="inline-flex items-center gap-1">
                        <Star className="size-3.5 fill-accent text-accent" aria-hidden /> {c.promedio.toFixed(1)} / 5
                      </span>
                    </td>
                    <td className="tabular-nums">{c.cantidad}</td>
                    <td className="text-muted-foreground">
                      {c.comentarios.length ? (
                        <ul className="flex flex-col gap-1">
                          {c.comentarios.map((t, i) => (
                            <li key={i}>“{t}”</li>
                          ))}
                        </ul>
                      ) : (
                        "—"
                      )}
                    </td>
                  </tr>
                ))}
              </Tabla>
            )}
          </section>
        </div>
      )}
    </>
  )
}
