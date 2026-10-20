"use client"

import { useState } from "react"
import { paquetes, salidas, servicios, type Excursion, type SalidaProgramada } from "@/lib/data"
import { formPaqueteVacio, noches, precioPaquete, refugiosAdmisibles, type FormPaquete } from "@/lib/oferta"
import { formatFecha, usd, useStore } from "@/lib/store"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Campo, Modal } from "@/components/trekk/modal"
import { cn } from "@/lib/utils"
import { AvisoError } from "./avisos"

/** CU 5 – Registrar paquete (extendido por CU 24 – Listar servicios y CU 26 – Listar refugios) */
export function FormPaqueteModal({
  excursion,
  salida,
  onCerrar,
  onListo,
}: {
  excursion: Excursion
  salida: SalidaProgramada
  onCerrar: () => void
  onListo: (mensaje: string) => void
}) {
  const { registrarPaquete } = useStore()
  const [f, setF] = useState<FormPaquete>(formPaqueteVacio)
  const [error, setError] = useState("")
  const [campos, setCampos] = useState<string[]>([])
  const set = <K extends keyof FormPaquete>(k: K, v: FormPaquete[K]) => {
    setF((prev) => ({ ...prev, [k]: v }))
    setError("")
  }
  const inv = (k: string) => campos.includes(k) || undefined

  const admisibles = refugiosAdmisibles(salida, salidas, paquetes)
  const precio = precioPaquete(salida, f.servicios, f.incluyeAlojamiento ? f.refugioId : undefined)

  function confirmar() {
    const r = registrarPaquete(salida.id, f)
    if (!r.ok) {
      setError(r.error)
      setCampos(r.campos ?? [])
      return
    }
    onListo(r.mensaje)
  }

  // Paso 2: datos que el paquete toma de la Salida Programada
  const datosSalida = [
    ["Excursión", excursion.nombre],
    ["Período", `${formatFecha(salida.fechaInicio)} → ${formatFecha(salida.fechaFin)}`],
    ["Guía responsable", salida.guia],
    ["Asistentes", salida.asistentes.join(", ") || "Sin asistentes"],
    ["Cupo máximo", String(salida.cupoTotal)],
    ["Equipamiento incluido", salida.equipamientoIncluido.join(", ") || "—"],
    ["Precio base", usd(salida.precioBase)],
  ]

  return (
    <Modal
      abierto
      onCerrar={onCerrar}
      className="max-w-3xl"
      titulo="Registrar paquete"
      descripcion="El paquete toma cupo, fechas, guía y precio base de la Salida Programada."
      pie={
        <>
          <span className="mr-auto text-sm">
            Precio del paquete: <strong className="font-display text-lg tabular-nums">{usd(precio)}</strong>
          </span>
          <Button variant="ghost" onClick={onCerrar}>
            Cancelar
          </Button>
          <Button onClick={confirmar}>Registrar paquete</Button>
        </>
      }
    >
      <dl className="grid gap-x-6 gap-y-2 rounded-xl bg-muted px-4 py-3 text-sm sm:grid-cols-2">
        {datosSalida.map(([k, v]) => (
          <div key={k} className="flex justify-between gap-3">
            <dt className="text-muted-foreground">{k}</dt>
            <dd className="text-right font-medium">{v}</dd>
          </div>
        ))}
      </dl>

      <div className="grid gap-4">
        <Campo id="pq-nombre" label="Nombre del paquete" error={inv("nombre")}>
          <Input id="pq-nombre" value={f.nombre} aria-invalid={inv("nombre")} onChange={(e) => set("nombre", e.target.value)} />
        </Campo>
        <Campo id="pq-desc" label="Descripción" error={inv("descripcion")}>
          <Textarea id="pq-desc" rows={2} value={f.descripcion} aria-invalid={inv("descripcion")} onChange={(e) => set("descripcion", e.target.value)} />
        </Campo>
      </div>

      {/* 3.1 – CU 24 Listar servicios */}
      <fieldset className="flex flex-col gap-2">
        <legend className="pb-1 text-sm font-medium">Servicios incluidos (opcional)</legend>
        {servicios.length === 0 ? (
          <p className="text-sm text-muted-foreground">No existen servicios registrados.</p>
        ) : (
          <div className="grid gap-2 sm:grid-cols-2">
            {servicios.map((s) => {
              const marcado = f.servicios.includes(s.tipo)
              return (
                <label key={s.id} className={cn("flex cursor-pointer items-center justify-between gap-2 rounded-lg border px-3 py-2 text-sm", marcado && "border-primary bg-primary/5")}>
                  <span className="flex items-center gap-2">
                    <input
                      type="checkbox"
                      checked={marcado}
                      className="accent-current"
                      onChange={() => set("servicios", marcado ? f.servicios.filter((x) => x !== s.tipo) : [...f.servicios, s.tipo])}
                    />
                    <span>
                      {s.tipo}
                      <span className="block text-xs text-muted-foreground">{s.descripcion}</span>
                    </span>
                  </span>
                  <span className="tabular-nums">{usd(s.precio)}</span>
                </label>
              )
            })}
          </div>
        )}
      </fieldset>

      {/* 3.2 – CU 26 Listar refugios */}
      <fieldset className="flex flex-col gap-2">
        <legend className="pb-1 text-sm font-medium">Alojamiento</legend>
        <label className="flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={f.incluyeAlojamiento}
            className="accent-current"
            onChange={(e) => setF((prev) => ({ ...prev, incluyeAlojamiento: e.target.checked, refugioId: "" }))}
          />
          El paquete incluye alojamiento en refugio
        </label>
        {f.incluyeAlojamiento &&
          (admisibles.length === 0 ? (
            <AvisoError mensaje="No hay refugios con convenio admisible para el período de la salida. Podés continuar sin alojamiento o cancelar." />
          ) : (
            <div role="radiogroup" aria-label="Refugio" className="flex flex-col gap-2" data-error={inv("refugioId")}>
              {admisibles.map((r) => (
                <label key={r.id} className={cn("flex cursor-pointer items-start justify-between gap-3 rounded-lg border px-3 py-2 text-sm", f.refugioId === r.id && "border-primary bg-primary/5", inv("refugioId") && "border-destructive")}>
                  <span className="flex items-start gap-2">
                    <input type="radio" name="refugio" checked={f.refugioId === r.id} onChange={() => set("refugioId", r.id)} className="mt-1 accent-current" />
                    <span>
                      {r.nombre}
                      <span className="block text-xs text-muted-foreground">
                        {r.ubicacion} · {r.camasDisponibles}/{r.camasTotales} camas libres · convenio {formatFecha(r.convenioDesde)} – {formatFecha(r.convenioHasta)}
                      </span>
                    </span>
                  </span>
                  <span className="shrink-0 text-right tabular-nums">
                    {usd(r.precioCama)} por cama
                    <span className="block text-xs text-muted-foreground">× {noches(salida)} noche(s)</span>
                  </span>
                </label>
              ))}
            </div>
          ))}
      </fieldset>
      <AvisoError mensaje={error} />
    </Modal>
  )
}
