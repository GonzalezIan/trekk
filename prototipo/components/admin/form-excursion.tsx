"use client"

import { useState } from "react"
import type { Excursion } from "@/lib/data"
import { formDesdeExcursion, formExcursionVacio, type FormExcursion } from "@/lib/oferta"
import { useStore } from "@/lib/store"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Campo, Modal, selectCls } from "@/components/trekk/modal"
import { AvisoError } from "./avisos"

/** CU 1 – Alta de excursión / CU 3 – Modificar excursión */
export function FormExcursionModal({
  excursion,
  onCerrar,
  onListo,
}: {
  /** Si viene, es una modificación (CU 3); si no, un alta (CU 1) */
  excursion?: Excursion
  onCerrar: () => void
  onListo: (mensaje: string) => void
}) {
  const { registrarExcursion, modificarExcursion } = useStore()
  const [f, setF] = useState<FormExcursion>(excursion ? formDesdeExcursion(excursion) : formExcursionVacio)
  const [error, setError] = useState("")
  const [campos, setCampos] = useState<string[]>([])

  const set = <K extends keyof FormExcursion>(k: K, v: FormExcursion[K]) => {
    setF((prev) => ({ ...prev, [k]: v }))
    setError("")
  }
  const inv = (k: keyof FormExcursion) => campos.includes(k) || undefined

  function confirmar() {
    const r = excursion ? modificarExcursion(excursion.id, f) : registrarExcursion(f)
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
      titulo={excursion ? "Modificar excursión" : "Registrar excursión"}
      descripcion={excursion ? excursion.nombre : "Todos los campos son obligatorios, salvo el equipamiento obligatorio."}
      pie={
        <>
          <Button variant="ghost" onClick={onCerrar}>
            Cancelar
          </Button>
          <Button onClick={confirmar}>{excursion ? "Guardar cambios" : "Registrar excursión"}</Button>
        </>
      }
    >
      <div className="grid gap-4 sm:grid-cols-2">
        <Campo id="ex-nombre" label="Nombre" error={inv("nombre")} className="sm:col-span-2">
          <Input id="ex-nombre" value={f.nombre} aria-invalid={inv("nombre")} onChange={(e) => set("nombre", e.target.value)} />
        </Campo>
        <Campo id="ex-desc" label="Descripción" error={inv("descripcion")} className="sm:col-span-2">
          <Textarea id="ex-desc" rows={2} value={f.descripcion} aria-invalid={inv("descripcion")} onChange={(e) => set("descripcion", e.target.value)} />
        </Campo>
        <Campo id="ex-recorrido" label="Recorrido" error={inv("recorrido")} className="sm:col-span-2">
          <Input id="ex-recorrido" value={f.recorrido} aria-invalid={inv("recorrido")} onChange={(e) => set("recorrido", e.target.value)} />
        </Campo>
        <Campo id="ex-etapas" label="Etapas" error={inv("etapas")} className="sm:col-span-2">
          <Textarea id="ex-etapas" rows={2} value={f.etapas} aria-invalid={inv("etapas")} onChange={(e) => set("etapas", e.target.value)} />
        </Campo>
        <Campo id="ex-dif" label="Dificultad" error={inv("dificultad")}>
          <select id="ex-dif" className={selectCls} value={f.dificultad} aria-invalid={inv("dificultad")} onChange={(e) => set("dificultad", e.target.value as FormExcursion["dificultad"])}>
            <option value="">Seleccionar…</option>
            <option>Baja</option>
            <option>Media</option>
            <option>Alta</option>
          </select>
        </Campo>
        <Campo id="ex-dur" label="Duración (días)" error={inv("duracionDias")}>
          <Input id="ex-dur" type="number" min={1} value={f.duracionDias} aria-invalid={inv("duracionDias")} onChange={(e) => set("duracionDias", e.target.value)} />
        </Campo>
        <Campo id="ex-exp" label="Experiencia requerida" error={inv("experienciaRequerida")} className="sm:col-span-2">
          <Input id="ex-exp" value={f.experienciaRequerida} aria-invalid={inv("experienciaRequerida")} onChange={(e) => set("experienciaRequerida", e.target.value)} />
        </Campo>
        <Campo id="ex-menores" label="¿Acepta menores de edad?" error={inv("aceptaMenores")}>
          <select id="ex-menores" className={selectCls} value={f.aceptaMenores} aria-invalid={inv("aceptaMenores")} onChange={(e) => set("aceptaMenores", e.target.value as FormExcursion["aceptaMenores"])}>
            <option value="">Seleccionar…</option>
            <option value="si">Sí</option>
            <option value="no">No</option>
          </select>
        </Campo>
        <Campo id="ex-cert" label="¿Requiere certificado médico?" error={inv("requiereCertificado")}>
          <select id="ex-cert" className={selectCls} value={f.requiereCertificado} aria-invalid={inv("requiereCertificado")} onChange={(e) => set("requiereCertificado", e.target.value as FormExcursion["requiereCertificado"])}>
            <option value="">Seleccionar…</option>
            <option value="si">Sí</option>
            <option value="no">No</option>
          </select>
        </Campo>
        <Campo id="ex-equipo" label="Equipamiento obligatorio (opcional, uno por línea)" className="sm:col-span-2">
          <Textarea
            id="ex-equipo"
            rows={3}
            placeholder={"Calzado adecuado\nProtección ocular"}
            value={f.equipamientoObligatorio}
            onChange={(e) => set("equipamientoObligatorio", e.target.value)}
          />
        </Campo>
      </div>
      <AvisoError mensaje={error} />
    </Modal>
  )
}
