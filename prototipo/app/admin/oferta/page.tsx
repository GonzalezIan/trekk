"use client"

import { useState } from "react"
import { ArrowLeft, Ban, CalendarPlus, List, PackagePlus, Pencil, Plus, Users, X, XCircle } from "lucide-react"
import { excursiones, paquetes, refugios, salidas, type Excursion, type SalidaProgramada } from "@/lib/data"
import { esActiva } from "@/lib/oferta"
import { formatFecha, getExcursion, getPaquete, getSalida, usd, useStore } from "@/lib/store"
import { PageHeader } from "@/components/trekk/page-header"
import { EstadoBadge } from "@/components/trekk/estado-badge"
import { Tabla } from "@/components/trekk/tabla"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { AvisoError, AvisoExito } from "@/components/admin/avisos"
import { FormExcursionModal } from "@/components/admin/form-excursion"
import { FormSalidaModal } from "@/components/admin/form-salida"
import { FormPaqueteModal } from "@/components/admin/form-paquete"
import { MotivoModal } from "@/components/admin/motivo-modal"
import { ParticipantesModal } from "@/components/admin/participantes-modal"
import { BarraAcciones, FilaSeleccionable } from "@/components/admin/seleccion"
import { cn } from "@/lib/utils"

const tabs = ["Excursiones", "Salidas programadas"] as const
type Tab = (typeof tabs)[number]
const estadosSalida = ["Todos", "Programada", "Cancelada", "Finalizada"] as const
const selectCls = "h-8 rounded-lg border bg-background px-2.5 text-sm"

type ModalAbierto =
  | { tipo: "altaExcursion" }
  | { tipo: "modificarExcursion"; excursion: Excursion }
  | { tipo: "bajaExcursion"; excursion: Excursion }
  | { tipo: "registrarSalida"; excursion: Excursion }
  | { tipo: "modificarSalida"; salida: SalidaProgramada }
  | { tipo: "cancelarSalida"; salida: SalidaProgramada }
  | { tipo: "registrarPaquete"; salida: SalidaProgramada }
  | { tipo: "participantes"; salida: SalidaProgramada }
  | { tipo: "cancelarPaquete"; paqueteId: string }
  | null

/**
 * Oferta del administrativo, siguiendo el diagrama de casos de uso:
 * CU 4 Listar excursiones ← CU 1 Alta, CU 2 Baja, CU 3 Modificar, CU 8 Registrar salida
 * CU 9 Listar salidas ← CU 10 Modificar, CU 11 Cancelar, CU 5 Registrar paquete, CU 7 Listar paquetes (← CU 6), CU 21 Listar participantes
 */
export default function OfertaPage() {
  const store = useStore()
  const { cupoOcupado, reservas } = store
  const [tab, setTab] = useState<Tab>("Excursiones")
  const [aviso, setAviso] = useState("")
  const [errorAccion, setErrorAccion] = useState("")
  const [modal, setModal] = useState<ModalAbierto>(null)

  const [selExcursion, setSelExcursion] = useState<string | null>(null)
  const [selSalida, setSelSalida] = useState<string | null>(null)
  const [paquetesDe, setPaquetesDe] = useState<string | null>(null)
  const [selPaquete, setSelPaquete] = useState<string | null>(null)

  // Filtros de CU 9: excursión, rango de fechas y estado
  const [fExcursion, setFExcursion] = useState("")
  const [fDesde, setFDesde] = useState("")
  const [fHasta, setFHasta] = useState("")
  const [fEstado, setFEstado] = useState<(typeof estadosSalida)[number]>("Todos")

  const excursion = selExcursion ? getExcursion(selExcursion) : undefined
  const salida = selSalida ? getSalida(selSalida) : undefined
  const salidaPaquetes = paquetesDe ? getSalida(paquetesDe) : undefined

  const salidasFiltradas = salidas
    .filter((s) => !fExcursion || s.excursionId === fExcursion)
    .filter((s) => !fDesde || s.fechaInicio >= fDesde)
    .filter((s) => !fHasta || s.fechaInicio <= fHasta)
    .filter((s) => fEstado === "Todos" || s.estado === fEstado)
    .sort((a, b) => a.fechaInicio.localeCompare(b.fechaInicio))
  const hayFiltros = fExcursion || fDesde || fHasta || fEstado !== "Todos"

  const reservasActivas = (paqueteId: string) => reservas.filter((r) => r.paqueteId === paqueteId && esActiva(r)).length

  const listo = (mensaje: string) => {
    setModal(null)
    setErrorAccion("")
    setAviso(mensaje)
  }
  const abrir = (m: ModalAbierto) => {
    setErrorAccion("")
    setAviso("")
    setModal(m)
  }
  const cambiarTab = (t: Tab) => {
    setTab(t)
    setPaquetesDe(null)
    setErrorAccion("")
  }

  return (
    <>
      <PageHeader eyebrow="Administración" title="Oferta de excursiones" description="Seleccioná un registro para ver las operaciones disponibles." />

      <div role="tablist" aria-label="Oferta" className="mb-4 flex flex-wrap gap-1">
        {tabs.map((t) => (
          <button
            key={t}
            role="tab"
            aria-selected={tab === t}
            onClick={() => cambiarTab(t)}
            className={cn("rounded-md px-3 py-1.5 text-sm text-muted-foreground hover:text-foreground", tab === t && "bg-muted font-medium text-foreground")}
          >
            {t}
          </button>
        ))}
      </div>

      <AvisoExito mensaje={aviso} onCerrar={() => setAviso("")} />

      {/* ───────── CU 4 – Listar excursiones ───────── */}
      {tab === "Excursiones" && (
        <div className="flex flex-col gap-3">
          <BarraAcciones
            seleccion={excursion ? excursion.nombre : null}
            vacio="Seleccioná una excursión para modificarla, darla de baja o registrar una salida."
            extra={
              <Button onClick={() => abrir({ tipo: "altaExcursion" })}>
                <Plus aria-hidden /> Registrar excursión
              </Button>
            }
          >
            {excursion && (
              <>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={excursion.estado !== "Activa"}
                  onClick={() => {
                    const r = store.puedeModificarExcursion(excursion.id)
                    if (!r.ok) return setErrorAccion(r.error)
                    abrir({ tipo: "modificarExcursion", excursion })
                  }}
                >
                  <Pencil aria-hidden /> Modificar
                </Button>
                <Button variant="outline" size="sm" disabled={excursion.estado !== "Activa"} onClick={() => abrir({ tipo: "bajaExcursion", excursion })}>
                  <Ban aria-hidden /> Dar de baja
                </Button>
                <Button size="sm" disabled={excursion.estado !== "Activa"} onClick={() => abrir({ tipo: "registrarSalida", excursion })}>
                  <CalendarPlus aria-hidden /> Registrar salida
                </Button>
              </>
            )}
          </BarraAcciones>
          <AvisoError mensaje={errorAccion} />

          <Tabla headers={["", "Excursión", "Recorrido", "Dificultad", "Duración", "Estado"]}>
            {excursiones.map((e) => (
              <FilaSeleccionable
                key={e.id}
                nombre="excursion"
                activa={selExcursion === e.id}
                etiqueta={e.nombre}
                onSeleccionar={() => {
                  setSelExcursion(e.id)
                  setErrorAccion("")
                }}
              >
                <td className="font-medium">{e.nombre}</td>
                <td className="text-muted-foreground">{e.recorrido}</td>
                <td>
                  <EstadoBadge valor={e.dificultad} />
                </td>
                <td className="whitespace-nowrap">
                  {e.duracionDias} {e.duracionDias === 1 ? "día" : "días"}
                </td>
                <td>
                  <EstadoBadge valor={e.estado} />
                </td>
              </FilaSeleccionable>
            ))}
          </Tabla>
        </div>
      )}

      {/* ───────── CU 9 – Listar Salidas Programadas ───────── */}
      {tab === "Salidas programadas" && !salidaPaquetes && (
        <div className="flex flex-col gap-3">
          <div className="flex flex-wrap items-end gap-3 rounded-2xl border bg-card p-4">
            <div className="flex flex-col gap-1.5">
              <Label htmlFor="f-excursion">Excursión</Label>
              <select id="f-excursion" value={fExcursion} onChange={(e) => setFExcursion(e.target.value)} className={selectCls}>
                <option value="">Todas</option>
                {excursiones.map((e) => (
                  <option key={e.id} value={e.id}>
                    {e.nombre}
                  </option>
                ))}
              </select>
            </div>
            <div className="flex flex-col gap-1.5">
              <Label htmlFor="f-desde">Desde</Label>
              <Input id="f-desde" type="date" value={fDesde} onChange={(e) => setFDesde(e.target.value)} className="h-8 w-40" />
            </div>
            <div className="flex flex-col gap-1.5">
              <Label htmlFor="f-hasta">Hasta</Label>
              <Input id="f-hasta" type="date" value={fHasta} onChange={(e) => setFHasta(e.target.value)} className="h-8 w-40" />
            </div>
            <div className="flex flex-col gap-1.5">
              <Label htmlFor="f-estado">Estado</Label>
              <select id="f-estado" value={fEstado} onChange={(e) => setFEstado(e.target.value as (typeof estadosSalida)[number])} className={selectCls}>
                {estadosSalida.map((e) => (
                  <option key={e}>{e}</option>
                ))}
              </select>
            </div>
            {hayFiltros && (
              <Button
                variant="ghost"
                size="sm"
                onClick={() => {
                  setFExcursion("")
                  setFDesde("")
                  setFHasta("")
                  setFEstado("Todos")
                }}
              >
                <X aria-hidden /> Limpiar filtros
              </Button>
            )}
          </div>

          <BarraAcciones
            seleccion={salida ? `${getExcursion(salida.excursionId)?.nombre} · ${formatFecha(salida.fechaInicio)}` : null}
            vacio="Seleccioná una salida para modificarla, cancelarla, registrar un paquete o listar sus paquetes y participantes."
          >
            {salida && (
              <>
                <Button variant="outline" size="sm" disabled={salida.estado !== "Programada"} onClick={() => abrir({ tipo: "modificarSalida", salida })}>
                  <Pencil aria-hidden /> Modificar salida
                </Button>
                <Button variant="outline" size="sm" disabled={salida.estado !== "Programada"} onClick={() => abrir({ tipo: "cancelarSalida", salida })}>
                  <XCircle aria-hidden /> Cancelar salida
                </Button>
                <Button size="sm" disabled={salida.estado !== "Programada"} onClick={() => abrir({ tipo: "registrarPaquete", salida })}>
                  <PackagePlus aria-hidden /> Registrar paquete
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => {
                    setPaquetesDe(salida.id)
                    setSelPaquete(null)
                    setAviso("")
                  }}
                >
                  <List aria-hidden /> Listar paquetes
                </Button>
                <Button variant="outline" size="sm" onClick={() => abrir({ tipo: "participantes", salida })}>
                  <Users aria-hidden /> Listar participantes
                </Button>
              </>
            )}
          </BarraAcciones>

          {salidasFiltradas.length === 0 ? (
            <p className="rounded-xl border border-dashed p-6 text-center text-muted-foreground">
              No existen Salidas Programadas para los filtros seleccionados.
            </p>
          ) : (
            <Tabla headers={["", "Excursión", "Fechas", "Guía / asistentes", "Equipamiento incluido", "Cupo máx.", "Cupo disp.", "Precio base", "Paquetes", "Estado"]}>
              {salidasFiltradas.map((s) => (
                <FilaSeleccionable
                  key={s.id}
                  nombre="salida"
                  activa={selSalida === s.id}
                  etiqueta={`${getExcursion(s.excursionId)?.nombre} ${formatFecha(s.fechaInicio)}`}
                  onSeleccionar={() => setSelSalida(s.id)}
                >
                  <td className="min-w-36 font-medium">{getExcursion(s.excursionId)?.nombre}</td>
                  <td className="whitespace-nowrap">
                    {formatFecha(s.fechaInicio)}
                    {s.fechaFin !== s.fechaInicio && ` → ${formatFecha(s.fechaFin)}`}
                    <span className="block text-xs text-muted-foreground">{s.horaSalida} hs</span>
                  </td>
                  <td className="whitespace-nowrap">
                    {s.guia}
                    <span className="block text-xs text-muted-foreground">{s.asistentes.join(", ") || "Sin asistentes"}</span>
                  </td>
                  <td className="text-muted-foreground">{s.equipamientoIncluido.join(", ") || "—"}</td>
                  <td className="tabular-nums">{s.cupoTotal}</td>
                  <td className="tabular-nums">{s.estado === "Programada" ? s.cupoTotal - cupoOcupado(s.id) : "—"}</td>
                  <td className="whitespace-nowrap tabular-nums">{usd(s.precioBase)}</td>
                  <td className="tabular-nums">{paquetes.filter((p) => p.salidaId === s.id).length}</td>
                  <td>
                    <EstadoBadge valor={s.estado} />
                  </td>
                </FilaSeleccionable>
              ))}
            </Tabla>
          )}
        </div>
      )}

      {/* ───────── CU 7 – Listar paquetes (desde la salida seleccionada) ───────── */}
      {tab === "Salidas programadas" && salidaPaquetes && (
        <div className="flex flex-col gap-3">
          <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-card p-4">
            <div>
              <Button variant="ghost" size="sm" className="-ml-2" onClick={() => setPaquetesDe(null)}>
                <ArrowLeft aria-hidden /> Volver a salidas programadas
              </Button>
              <h2 className="font-display text-xl font-semibold">
                Paquetes · {getExcursion(salidaPaquetes.excursionId)?.nombre} · {formatFecha(salidaPaquetes.fechaInicio)}
              </h2>
            </div>
            <div className="flex items-center gap-3 text-sm text-muted-foreground">
              <EstadoBadge valor={salidaPaquetes.estado} />
              Cupo disponible: <strong className="text-foreground">{salidaPaquetes.cupoTotal - cupoOcupado(salidaPaquetes.id)}</strong>
            </div>
          </div>

          <BarraAcciones seleccion={selPaquete ? (getPaquete(selPaquete)?.nombre ?? null) : null} vacio="Seleccioná un paquete en estado “Disponible” para cancelarlo.">
            {selPaquete && (
              <Button
                variant="outline"
                size="sm"
                disabled={getPaquete(selPaquete)?.estado !== "Disponible"}
                onClick={() => abrir({ tipo: "cancelarPaquete", paqueteId: selPaquete })}
              >
                <XCircle aria-hidden /> Cancelar paquete
              </Button>
            )}
          </BarraAcciones>

          {paquetes.filter((p) => p.salidaId === salidaPaquetes.id).length === 0 ? (
            <p className="rounded-xl border border-dashed p-6 text-center text-muted-foreground">
              No existen paquetes para la Salida Programada seleccionada.
            </p>
          ) : (
            <Tabla headers={["", "Paquete", "Servicios", "Refugio", "Precio", "Reservas activas", "Estado"]}>
              {paquetes
                .filter((p) => p.salidaId === salidaPaquetes.id)
                .map((p) => (
                  <FilaSeleccionable key={p.id} nombre="paquete" activa={selPaquete === p.id} etiqueta={p.nombre} onSeleccionar={() => setSelPaquete(p.id)}>
                    <td>
                      <span className="font-medium">{p.nombre}</span>
                      <span className="block text-xs text-muted-foreground">{p.descripcion}</span>
                    </td>
                    <td className="text-muted-foreground">{p.servicios.join(", ") || "—"}</td>
                    <td className="text-muted-foreground">{refugios.find((r) => r.id === p.refugioId)?.nombre ?? "Sin alojamiento"}</td>
                    <td className="whitespace-nowrap tabular-nums">{usd(p.precioTotal)}</td>
                    <td className="tabular-nums">{reservasActivas(p.id)}</td>
                    <td>
                      <EstadoBadge valor={p.estado} />
                      {p.motivoCancelacion && <span className="block pt-1 text-xs text-muted-foreground">{p.motivoCancelacion}</span>}
                    </td>
                  </FilaSeleccionable>
                ))}
            </Tabla>
          )}
        </div>
      )}

      {/* ───────── Ventanas de cada caso de uso ───────── */}
      {modal?.tipo === "altaExcursion" && (
        <FormExcursionModal
          onCerrar={() => setModal(null)}
          onListo={(m) => {
            listo(m)
            setSelExcursion(excursiones[excursiones.length - 1]?.id ?? null)
          }}
        />
      )}
      {modal?.tipo === "modificarExcursion" && <FormExcursionModal excursion={modal.excursion} onCerrar={() => setModal(null)} onListo={listo} />}
      {modal?.tipo === "bajaExcursion" && (
        <MotivoModal
          titulo="Dar de baja la excursión"
          descripcion={modal.excursion.nombre}
          pedirMotivo={false}
          detalle={
            <p className="text-sm">
              La excursión pasará a estado <strong>“Inactiva”</strong> y sus salidas en estado “Programada” y sus paquetes se cancelarán. La baja es
              definitiva: para volver a ofrecer la actividad habrá que registrar una nueva excursión.
            </p>
          }
          textoConfirmar="Confirmar baja"
          onConfirmar={() => store.bajaExcursion(modal.excursion.id)}
          onCerrar={() => setModal(null)}
          onListo={listo}
        />
      )}
      {modal?.tipo === "registrarSalida" && (
        <FormSalidaModal
          excursion={modal.excursion}
          onCerrar={() => setModal(null)}
          onListo={(m) => {
            listo(m)
            // Se muestra la salida nueva en el listado de salidas, filtrado por su excursión
            setFExcursion(modal.excursion.id)
            setFDesde("")
            setFHasta("")
            setFEstado("Todos")
            setSelSalida(salidas[salidas.length - 1]?.id ?? null)
            setTab("Salidas programadas")
          }}
        />
      )}
      {modal?.tipo === "modificarSalida" && (
        <FormSalidaModal excursion={getExcursion(modal.salida.excursionId)!} salida={modal.salida} onCerrar={() => setModal(null)} onListo={listo} />
      )}
      {modal?.tipo === "cancelarSalida" && (
        <MotivoModal
          titulo="Cancelar Salida Programada"
          descripcion={`${getExcursion(modal.salida.excursionId)?.nombre} · ${formatFecha(modal.salida.fechaInicio)}`}
          detalle={
            <p className="text-sm">
              Se cancelarán en cascada <strong>todos sus paquetes y reservas</strong>, se liberarán los cupos, las camas y el equipamiento comprometido, y se
              notificará a los clientes afectados (reembolso del 100%, a gestionar ante la empresa).
            </p>
          }
          textoConfirmar="Cancelar salida"
          onConfirmar={(motivo) => store.cancelarSalida(modal.salida.id, motivo)}
          onCerrar={() => setModal(null)}
          onListo={listo}
        />
      )}
      {modal?.tipo === "registrarPaquete" && (
        <FormPaqueteModal
          excursion={getExcursion(modal.salida.excursionId)!}
          salida={modal.salida}
          onCerrar={() => setModal(null)}
          onListo={(m) => {
            listo(m)
            setPaquetesDe(modal.salida.id)
            setSelPaquete(null)
          }}
        />
      )}
      {modal?.tipo === "participantes" && (
        <ParticipantesModal excursion={getExcursion(modal.salida.excursionId)!} salida={modal.salida} onCerrar={() => setModal(null)} />
      )}
      {modal?.tipo === "cancelarPaquete" && (
        <MotivoModal
          titulo="Cancelar paquete"
          descripcion={getPaquete(modal.paqueteId)?.nombre}
          detalle={
            <p className="text-sm">
              El paquete pasará a estado “Cancelado” y no admitirá nuevas reservas. Sus reservas activas se cancelarán y se notificará a los clientes.
            </p>
          }
          textoConfirmar="Cancelar paquete"
          onConfirmar={(motivo) => store.cancelarPaquete(modal.paqueteId, motivo)}
          onCerrar={() => setModal(null)}
          onListo={listo}
        />
      )}
    </>
  )
}
