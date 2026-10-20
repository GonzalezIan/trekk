"use client"

import { useState, type ReactNode } from "react"
import type { Resultado } from "@/lib/oferta"
import { Button } from "@/components/ui/button"
import { Textarea } from "@/components/ui/textarea"
import { Campo, Modal } from "@/components/trekk/modal"
import { AvisoError } from "./avisos"

/** Confirmación con motivo obligatorio: CU 11 (cancelar salida), CU 6 (cancelar paquete) y CU 16 (cancelar reserva). */
export function MotivoModal({
  titulo,
  descripcion,
  detalle,
  textoConfirmar,
  pedirMotivo = true,
  onConfirmar,
  onCerrar,
  onListo,
}: {
  titulo: string
  descripcion?: ReactNode
  detalle?: ReactNode
  textoConfirmar: string
  pedirMotivo?: boolean
  onConfirmar: (motivo: string) => Resultado
  onCerrar: () => void
  onListo: (mensaje: string) => void
}) {
  const [motivo, setMotivo] = useState("")
  const [error, setError] = useState("")

  function confirmar() {
    if (pedirMotivo && !motivo.trim()) return setError("El motivo de la cancelación es obligatorio.")
    const r = onConfirmar(motivo)
    if (!r.ok) return setError(r.error)
    onListo(r.mensaje)
  }

  return (
    <Modal
      abierto
      onCerrar={onCerrar}
      className="max-w-lg"
      titulo={titulo}
      descripcion={descripcion}
      pie={
        <>
          <Button variant="ghost" onClick={onCerrar}>
            Volver
          </Button>
          <Button variant="destructive" onClick={confirmar}>
            {textoConfirmar}
          </Button>
        </>
      }
    >
      {detalle}
      {pedirMotivo && (
        <Campo id="motivo" label="Motivo de la cancelación" error={!!error && !motivo.trim()}>
          <Textarea id="motivo" rows={3} value={motivo} aria-invalid={(!!error && !motivo.trim()) || undefined} onChange={(e) => setMotivo(e.target.value)} />
        </Campo>
      )}
      <AvisoError mensaje={error} />
    </Modal>
  )
}
