"use client"

import { useState } from "react"
import { CheckCircle2, FileText, XCircle } from "lucide-react"
import { HOY, type Certificado } from "@/lib/data"
import { contextoPaquete, formatFecha, useStore } from "@/lib/store"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Campo, Modal } from "@/components/trekk/modal"
import { cn } from "@/lib/utils"
import { AvisoError } from "./avisos"

const sumarDias = (f: string, dias: number) => {
  const d = new Date(`${f}T12:00:00`)
  d.setDate(d.getDate() + dias)
  return d.toISOString().slice(0, 10)
}

/** CU 19 – Verificar certificado médico (invocado desde CU 18) */
export function VerificarCertificadoModal({ certificado: c, onCerrar, onListo }: { certificado: Certificado; onCerrar: () => void; onListo: (m: string) => void }) {
  const { reservas, verificarCertificado } = useStore()
  const reserva = reservas.find((r) => r.id === c.reservaId)!
  const { excursion, salida, paquete } = contextoPaquete(reserva.paqueteId)
  const participante = reserva.participantes.find((p) => p.dni === c.participanteDni)

  const [decision, setDecision] = useState<"valido" | "invalido" | null>(null)
  const [fecha, setFecha] = useState("")
  const [motivo, setMotivo] = useState("")
  const [error, setError] = useState("")

  // Paso 6 y 7: vigencia = emisión + 30 días; debe cubrir todo el período de la salida (RN-01)
  const vigencia = fecha ? sumarDias(fecha, 30) : ""
  const cubre = !!vigencia && vigencia >= salida.fechaFin

  function confirmar() {
    setError("")
    if (decision === "invalido") {
      // 3.1: inválido por formato o contenido, sin fecha de emisión
      if (!motivo.trim()) return setError("Ingresá el motivo de invalidez.")
      verificarCertificado(c.id, { motivoInvalidez: motivo.trim() })
      return onListo(`Certificado marcado como inválido. El requisito médico sigue pendiente y se notificó al cliente el motivo.`)
    }
    // 5.1: fecha futura o no válida
    if (!fecha || new Date(`${fecha}T12:00:00`) > HOY) return setError("Debe ingresar una fecha de emisión válida (no puede ser futura).")
    const estado = verificarCertificado(c.id, { fechaEmision: fecha })
    onListo(
      estado === "Válido"
        ? `Certificado verificado correctamente: válido. El requisito médico de ${participante?.nombre} quedó cumplido.`
        : "La vigencia del certificado no cubre el período de la salida: quedó inválido. El requisito médico sigue pendiente y se notificó al cliente.",
    )
  }

  const datos = [
    ["Participante", `${participante?.nombre} ${participante?.apellido} · DNI ${c.participanteDni}`],
    ["Reserva", reserva.id],
    ["Excursión", `${excursion.nombre} · ${paquete.nombre}`],
    ["Salida", `${formatFecha(salida.fechaInicio)} → ${formatFecha(salida.fechaFin)}`],
    ["Fecha de carga", formatFecha(c.fechaCarga)],
  ]

  return (
    <Modal
      abierto
      onCerrar={onCerrar}
      className="max-w-5xl"
      titulo="Verificar certificado médico"
      descripcion={c.archivo}
      pie={
        <>
          <Button variant="ghost" onClick={onCerrar}>
            Cancelar
          </Button>
          <Button variant={decision === "invalido" ? "destructive" : "default"} disabled={!decision} onClick={confirmar}>
            {decision === "invalido" ? "Marcar como inválido" : "Registrar verificación"}
          </Button>
        </>
      }
    >
      <div className="grid gap-5 md:grid-cols-[1fr_22rem]">
        {/* Paso 2: archivo adjunto */}
        <figure className="flex flex-col gap-2">
          <div className="flex aspect-[3/4] max-h-[60dvh] flex-col gap-4 overflow-hidden rounded-xl border bg-white p-6 text-neutral-800 shadow-inner">
            <div className="flex items-center justify-between border-b border-neutral-300 pb-3">
              <span className="text-xs font-semibold uppercase tracking-widest text-neutral-500">Certificado médico</span>
              <FileText className="size-5 text-neutral-400" aria-hidden />
            </div>
            <p className="text-sm leading-relaxed">
              Certifico que <strong>{participante?.nombre} {participante?.apellido}</strong>, DNI {c.participanteDni}, se encuentra en condiciones físicas
              aptas para realizar actividades de trekking de montaña.
            </p>
            <div className="mt-2 flex flex-col gap-2">
              <span className="h-2 w-5/6 rounded bg-neutral-200" />
              <span className="h-2 w-4/6 rounded bg-neutral-200" />
              <span className="h-2 w-3/4 rounded bg-neutral-200" />
            </div>
            <div className="mt-auto flex items-end justify-between text-xs text-neutral-500">
              <span>Fecha: ____ / ____ / ______</span>
              <span className="border-t border-neutral-400 px-6 pt-1">Firma y sello</span>
            </div>
          </div>
          <figcaption className="text-xs text-muted-foreground">Archivo adjunto: {c.archivo} (vista previa del prototipo)</figcaption>
        </figure>

        <div className="flex flex-col gap-4">
          <dl className="flex flex-col gap-2 rounded-xl bg-muted px-4 py-3 text-sm">
            {datos.map(([k, v]) => (
              <div key={k} className="flex flex-col">
                <dt className="text-xs text-muted-foreground">{k}</dt>
                <dd className="font-medium">{v}</dd>
              </div>
            ))}
          </dl>

          {/* Paso 3: el administrativo revisa el archivo y decide */}
          <div role="radiogroup" aria-label="Resultado de la revisión" className="flex flex-col gap-2">
            {(
              [
                ["valido", CheckCircle2, "Es un certificado médico válido", "Ingresar la fecha de emisión para calcular la vigencia."],
                ["invalido", XCircle, "No corresponde a un certificado válido", "Ilegible, formato incorrecto o de otra persona."],
              ] as const
            ).map(([k, Icono, titulo, texto]) => (
              <button
                key={k}
                role="radio"
                aria-checked={decision === k}
                onClick={() => {
                  setDecision(k)
                  setError("")
                }}
                className={cn("flex items-start gap-3 rounded-xl border p-3 text-left transition-colors hover:border-primary/50", decision === k && "border-primary ring-1 ring-primary")}
              >
                <Icono className={cn("mt-0.5 size-5 shrink-0", k === "valido" ? "text-success" : "text-destructive")} aria-hidden />
                <span>
                  <span className="block text-sm font-medium">{titulo}</span>
                  <span className="block text-xs text-muted-foreground">{texto}</span>
                </span>
              </button>
            ))}
          </div>

          {decision === "valido" && (
            <div className="flex flex-col gap-3">
              <Campo id="cm-fecha" label="Fecha de emisión" error={!!error}>
                <Input id="cm-fecha" type="date" value={fecha} aria-invalid={!!error || undefined} onChange={(e) => { setFecha(e.target.value); setError("") }} />
              </Campo>
              {fecha && (
                <p className={cn("rounded-lg px-3 py-2 text-sm", cubre ? "bg-success/10 text-success" : "bg-destructive/10 text-destructive")}>
                  Vigencia hasta el {formatFecha(vigencia)} (emisión + 30 días).{" "}
                  {cubre ? "Cubre el período de la salida." : "No cubre el período completo de la salida."}
                </p>
              )}
            </div>
          )}
          {decision === "invalido" && (
            <Campo id="cm-motivo" label="Motivo de invalidez" error={!!error}>
              <Textarea id="cm-motivo" rows={3} value={motivo} aria-invalid={!!error || undefined} onChange={(e) => { setMotivo(e.target.value); setError("") }} />
            </Campo>
          )}
          <AvisoError mensaje={error} />
        </div>
      </div>
    </Modal>
  )
}
