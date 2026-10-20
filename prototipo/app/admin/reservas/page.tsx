"use client"

import { useState } from "react"
import { Search, XCircle } from "lucide-react"
import { clienteDemo, clientesExtra, type EstadoReserva } from "@/lib/data"
import { esActiva } from "@/lib/oferta"
import { contextoPaquete, formatFecha, usd, useStore } from "@/lib/store"
import { PageHeader } from "@/components/trekk/page-header"
import { EstadoBadge } from "@/components/trekk/estado-badge"
import { Tabla } from "@/components/trekk/tabla"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { AvisoExito } from "@/components/admin/avisos"
import { MotivoModal } from "@/components/admin/motivo-modal"
import { BarraAcciones, FilaSeleccionable } from "@/components/admin/seleccion"
import { cn } from "@/lib/utils"

const filtros: ("Todas" | EstadoReserva)[] = ["Todas", "Pendiente", "Confirmada", "Realizada", "Cancelada"]
const clientes = [clienteDemo, ...clientesExtra]

/** CU 15 – Listar reservas (administrativo), extendido por CU 16 – Cancelar reserva */
export default function AdminReservasPage() {
  const { reservas, cancelarReserva } = useStore()
  const [filtro, setFiltro] = useState<(typeof filtros)[number]>("Todas")
  const [q, setQ] = useState("")
  const [sel, setSel] = useState<string | null>(null)
  const [cancelando, setCancelando] = useState(false)
  const [aviso, setAviso] = useState("")

  const lista = reservas.filter((r) => {
    if (filtro !== "Todas" && r.estado !== filtro) return false
    if (!q) return true
    const c = clientes.find((x) => x.dni === r.clienteDni)
    const texto = `${r.id} ${r.clienteDni} ${c?.nombre} ${c?.apellido} ${contextoPaquete(r.paqueteId).excursion.nombre}`.toLowerCase()
    return texto.includes(q.toLowerCase())
  })
  const reserva = reservas.find((r) => r.id === sel)

  return (
    <>
      <PageHeader eyebrow="Administración" title="Reservas" description="Consultá todas las reservas registradas y su situación de pago." />
      <div className="flex flex-col gap-3 pb-4 md:flex-row md:items-center md:justify-between">
        <div role="tablist" aria-label="Filtrar por estado" className="flex flex-wrap gap-1">
          {filtros.map((f) => (
            <button
              key={f}
              role="tab"
              aria-selected={filtro === f}
              onClick={() => setFiltro(f)}
              className={cn("rounded-md px-3 py-1.5 text-sm text-muted-foreground hover:text-foreground", filtro === f && "bg-muted font-medium text-foreground")}
            >
              {f}
            </button>
          ))}
        </div>
        <div className="relative md:w-72">
          <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden />
          <Input aria-label="Buscar" placeholder="Buscar por código, cliente o excursión" className="pl-8" value={q} onChange={(e) => setQ(e.target.value)} />
        </div>
      </div>

      <AvisoExito mensaje={aviso} onCerrar={() => setAviso("")} />

      <div className="flex flex-col gap-3">
        <BarraAcciones seleccion={reserva ? reserva.id : null} vacio="Seleccioná una reserva en estado “Pendiente” o “Confirmada” para cancelarla.">
          {reserva && (
            <Button
              variant="outline"
              size="sm"
              disabled={!esActiva(reserva)}
              onClick={() => {
                setAviso("")
                setCancelando(true)
              }}
            >
              <XCircle aria-hidden /> Cancelar reserva
            </Button>
          )}
        </BarraAcciones>

        {lista.length === 0 ? (
          <p className="rounded-xl border border-dashed p-6 text-center text-muted-foreground">No se encontraron reservas.</p>
        ) : (
          <Tabla headers={["", "Código", "Cliente", "Excursión / salida", "Pax", "Importe", "Pago", "Estado"]}>
            {lista.map((r) => {
              const { excursion, salida, paquete } = contextoPaquete(r.paqueteId)
              const c = clientes.find((x) => x.dni === r.clienteDni)
              return (
                <FilaSeleccionable key={r.id} nombre="reserva" activa={sel === r.id} etiqueta={r.id} onSeleccionar={() => setSel(r.id)}>
                  <td className="font-mono text-xs">{r.id}</td>
                  <td>
                    {c ? `${c.nombre} ${c.apellido}` : r.clienteDni}
                    <span className="block text-xs text-muted-foreground">DNI {r.clienteDni}</span>
                  </td>
                  <td>
                    {excursion.nombre}
                    <span className="block text-xs text-muted-foreground">
                      {formatFecha(salida.fechaInicio)} · {paquete.nombre}
                    </span>
                  </td>
                  <td className="tabular-nums">{r.participantes.length}</td>
                  <td className="tabular-nums">{usd(r.importeTotal)}</td>
                  <td>
                    <EstadoBadge valor={r.situacionPago} />
                  </td>
                  <td>
                    <EstadoBadge valor={r.estado} />
                    {r.motivoCancelacion && <span className="block pt-1 text-xs text-muted-foreground">{r.motivoCancelacion}</span>}
                  </td>
                </FilaSeleccionable>
              )
            })}
          </Tabla>
        )}
      </div>

      {cancelando && reserva && (
        <MotivoModal
          titulo="Cancelar reserva"
          descripcion={`${reserva.id} · ${contextoPaquete(reserva.paqueteId).excursion.nombre}`}
          detalle={
            <p className="text-sm">
              La reserva pasará a estado “Cancelada” y se liberarán sus cupos, camas y equipamiento de alquiler. No corresponde reintegro de los importes
              abonados. Se notificará al cliente.
            </p>
          }
          textoConfirmar="Cancelar reserva"
          onConfirmar={(motivo) => {
            if (!esActiva(reserva)) return { ok: false, error: "Solo pueden cancelarse reservas en estado “Pendiente” o “Confirmada”." }
            cancelarReserva(reserva.id, motivo.trim())
            return { ok: true, mensaje: `La reserva ${reserva.id} fue cancelada. Se notificó al cliente.` }
          }}
          onCerrar={() => setCancelando(false)}
          onListo={(m) => {
            setCancelando(false)
            setAviso(m)
          }}
        />
      )}
    </>
  )
}
