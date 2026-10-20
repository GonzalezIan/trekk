"use client"

import { AlertTriangle, CheckCircle2, X } from "lucide-react"
import { Button } from "@/components/ui/button"

export function AvisoError({ mensaje }: { mensaje: string }) {
  if (!mensaje) return null
  return (
    <p role="alert" className="flex items-start gap-2 rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
      <AlertTriangle className="mt-0.5 size-4 shrink-0" aria-hidden /> {mensaje}
    </p>
  )
}

/** Mensaje de confirmación que informa el sistema al terminar un caso de uso */
export function AvisoExito({ mensaje, onCerrar }: { mensaje: string; onCerrar: () => void }) {
  if (!mensaje) return null
  return (
    <div role="status" className="mb-4 flex items-start gap-2 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm">
      <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-success" aria-hidden />
      <span className="flex-1">{mensaje}</span>
      <Button variant="ghost" size="icon-xs" aria-label="Cerrar aviso" onClick={onCerrar}>
        <X />
      </Button>
    </div>
  )
}
