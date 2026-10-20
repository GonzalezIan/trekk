"use client"

import { useState } from "react"
import { equipamientos, salidas, type Excursion, type SalidaProgramada } from "@/lib/data"
import { cupoEfectivo, empleadosDisponibles, formDesdeSalida, formSalidaVacio, nombreEmpleado, type FormSalida } from "@/lib/oferta"
import { useStore } from "@/lib/store"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Campo, Modal, selectCls } from "@/components/trekk/modal"
import { cn } from "@/lib/utils"
import { AvisoError } from "./avisos"

/** CU 8 – Registrar Salida Programada / CU 10 – Modificar Salida Programada */
export function FormSalidaModal({
  excursion,
  salida,
  onCerrar,
  onListo,
}: {
  excursion: Excursion
  /** Si viene, es una modificación (CU 10); si no, un registro (CU 8) */
  salida?: SalidaProgramada
  onCerrar: () => void
  onListo: (mensaje: string) => void
}) {
  const { registrarSalida, modificarSalida, stockParaSalida, cupoOcupado } = useStore()
  const [f, setF] = useState<FormSalida>(salida ? formDesdeSalida(salida) : formSalidaVacio)
  const [error, setError] = useState("")
  const [campos, setCampos] = useState<string[]>([])

  const set = <K extends keyof FormSalida>(k: K, v: FormSalida[K]) => {
    setF((prev) => ({ ...prev, [k]: v }))
    setError("")
  }
  const inv = (k: keyof FormSalida) => campos.includes(k) || undefined
  const alternar = (k: "asistentes" | "equipamientoIncluido", valor: string) =>
    set(k, f[k].includes(valor) ? f[k].filter((x) => x !== valor) : [...f[k], valor])

  // CU 23 – Listar empleados: activos de la categoría y sin otra salida en el período
  const periodoCargado = !!f.fechaInicio && !!f.fechaFin && f.fechaFin >= f.fechaInicio
  const guias = empleadosDisponibles("Guía", f, salidas, salida?.id)
  const asistentes = empleadosDisponibles("Asistente", f, salidas, salida?.id)
  // CU 25 – Listar equipamientos: solo elementos con stock disponible (RN-06)
  const catalogo = equipamientos.map((e) => ({ ...e, disp: stockParaSalida(e.codigo, salida?.id) })).filter((e) => e.disp > 0 || f.equipamientoIncluido.includes(e.nombre))
  const cupo = Number(f.cupoMaximo) > 0 ? cupoEfectivo(Number(f.cupoMaximo), f.equipamientoIncluido, (c) => stockParaSalida(c, salida?.id)) : null

  function confirmar() {
    const r = salida ? modificarSalida(salida.id, f) : registrarSalida(excursion.id, f)
    if (!r.ok) {
      setError(r.error)
      setCampos(r.campos ?? [])
      return
    }
    onListo(r.mensaje)
  }

  return (
    <Modal
      abierto
      onCerrar={onCerrar}
      className="max-w-3xl"
      titulo={salida ? "Modificar Salida Programada" : "Registrar Salida Programada"}
      descripcion={`${excursion.nombre} · duración ${excursion.duracionDias} ${excursion.duracionDias === 1 ? "día" : "días"}`}
      pie={
        <>
          <Button variant="ghost" onClick={onCerrar}>
            Cancelar
          </Button>
          <Button onClick={confirmar}>{salida ? "Confirmar modificación" : "Registrar salida"}</Button>
        </>
      }
    >
      <div className="grid gap-4 sm:grid-cols-3">
        <Campo id="sa-ini" label="Fecha de inicio" error={inv("fechaInicio")}>
          <Input id="sa-ini" type="date" value={f.fechaInicio} aria-invalid={inv("fechaInicio")} onChange={(e) => set("fechaInicio", e.target.value)} />
        </Campo>
        <Campo id="sa-fin" label="Fecha de fin" error={inv("fechaFin")}>
          <Input id="sa-fin" type="date" value={f.fechaFin} aria-invalid={inv("fechaFin")} onChange={(e) => set("fechaFin", e.target.value)} />
        </Campo>
        <Campo id="sa-hora" label="Hora de salida" error={inv("horaSalida")}>
          <Input id="sa-hora" type="time" value={f.horaSalida} aria-invalid={inv("horaSalida")} onChange={(e) => set("horaSalida", e.target.value)} />
        </Campo>
        <Campo id="sa-cupo" label="Cupo máximo" error={inv("cupoMaximo")}>
          <Input id="sa-cupo" type="number" min={1} value={f.cupoMaximo} aria-invalid={inv("cupoMaximo")} onChange={(e) => set("cupoMaximo", e.target.value)} />
        </Campo>
        <Campo id="sa-precio" label="Precio base (USD)" error={inv("precioBase")}>
          <Input id="sa-precio" type="number" min={1} value={f.precioBase} aria-invalid={inv("precioBase")} onChange={(e) => set("precioBase", e.target.value)} />
        </Campo>
        <Campo id="sa-guia" label="Guía responsable" error={inv("guia")}>
          <select id="sa-guia" className={selectCls} value={f.guia} aria-invalid={inv("guia")} disabled={!periodoCargado} onChange={(e) => set("guia", e.target.value)}>
            <option value="">{periodoCargado ? "Seleccionar…" : "Ingresá el período"}</option>
            {guias.map((g) => (
              <option key={g.dni}>{nombreEmpleado(g)}</option>
            ))}
            {f.guia && !guias.some((g) => nombreEmpleado(g) === f.guia) && <option>{f.guia}</option>}
          </select>
        </Campo>
      </div>
      {periodoCargado && guias.length === 0 && <AvisoError mensaje="No hay guías disponibles para el período ingresado." />}

      <fieldset className="flex flex-col gap-2" data-error={inv("asistentes")}>
        <legend className={cn("pb-1 text-sm font-medium", inv("asistentes") && "text-destructive")}>Asistentes (opcional)</legend>
        {!periodoCargado ? (
          <p className="text-sm text-muted-foreground">Ingresá el período para ver los asistentes disponibles.</p>
        ) : asistentes.length === 0 && f.asistentes.length === 0 ? (
          <p className="text-sm text-muted-foreground">No hay asistentes disponibles en el período.</p>
        ) : (
          <div className="flex flex-wrap gap-2">
            {[...new Set([...asistentes.map(nombreEmpleado), ...f.asistentes])].map((a) => (
              <label key={a} className={cn("flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-1.5 text-sm", f.asistentes.includes(a) && "border-primary bg-primary/5")}>
                <input type="checkbox" checked={f.asistentes.includes(a)} onChange={() => alternar("asistentes", a)} className="accent-current" />
                {a}
              </label>
            ))}
          </div>
        )}
      </fieldset>

      <fieldset className="flex flex-col gap-2">
        <legend className={cn("pb-1 text-sm font-medium", inv("equipamientoIncluido") && "text-destructive")}>
          Equipamiento incluido sin costo (opcional)
        </legend>
        {catalogo.length === 0 ? (
          <p className="text-sm text-muted-foreground">No hay elementos de equipamiento con stock disponible.</p>
        ) : (
          <div className="grid gap-2 sm:grid-cols-2">
            {catalogo.map((e) => (
              <label key={e.codigo} className={cn("flex cursor-pointer items-center justify-between gap-2 rounded-lg border px-3 py-2 text-sm", f.equipamientoIncluido.includes(e.nombre) && "border-primary bg-primary/5")}>
                <span className="flex items-center gap-2">
                  <input type="checkbox" checked={f.equipamientoIncluido.includes(e.nombre)} onChange={() => alternar("equipamientoIncluido", e.nombre)} className="accent-current" />
                  {e.nombre}
                </span>
                <span className="text-xs text-muted-foreground tabular-nums">stock {e.disp}</span>
              </label>
            ))}
          </div>
        )}
      </fieldset>

      {cupo && (
        <p className="rounded-lg bg-muted px-4 py-3 text-sm">
          Cupo máximo resultante: <strong className="tabular-nums">{cupo.cupo}</strong>
          {cupo.limitante && <span className="text-muted-foreground"> (limitado por el stock de {cupo.limitante})</span>}
          {salida && <span className="text-muted-foreground"> · Participantes ya reservados: {cupoOcupado(salida.id)}</span>}
        </p>
      )}
      <AvisoError mensaje={error} />
    </Modal>
  )
}
