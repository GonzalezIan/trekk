"use client"

import { paquetes, type Excursion, type SalidaProgramada } from "@/lib/data"
import { formatFecha, useStore } from "@/lib/store"
import { Button } from "@/components/ui/button"
import { Modal } from "@/components/trekk/modal"
import { Tabla } from "@/components/trekk/tabla"
import { EstadoBadge } from "@/components/trekk/estado-badge"

/** CU 21 – Listar participantes (administrativo): nómina de las reservas "Confirmada" de la Salida Programada. */
export function ParticipantesModal({ excursion, salida, onCerrar }: { excursion: Excursion; salida: SalidaProgramada; onCerrar: () => void }) {
  const { reservas } = useStore()
  const idsPaquetes = paquetes.filter((p) => p.salidaId === salida.id).map((p) => p.id)
  const nomina = reservas
    .filter((r) => idsPaquetes.includes(r.paqueteId) && r.estado === "Confirmada")
    .flatMap((r) => r.participantes.map((p) => ({ ...p, reserva: r.id })))

  return (
    <Modal
      abierto
      onCerrar={onCerrar}
      titulo="Participantes de la salida"
      descripcion={`${excursion.nombre} · ${formatFecha(salida.fechaInicio)} · reservas confirmadas`}
      pie={<Button onClick={onCerrar}>Cerrar</Button>}
    >
      {nomina.length === 0 ? (
        <p className="rounded-xl border border-dashed p-6 text-center text-muted-foreground">
          No hay participantes asociados a la Salida Programada aún.
        </p>
      ) : (
        <Tabla headers={["Nombre", "Apellido", "DNI", "Reserva", "Requisito médico"]}>
          {nomina.map((p) => (
            <tr key={`${p.reserva}-${p.dni}`}>
              <td className="font-medium">{p.nombre}</td>
              <td>{p.apellido}</td>
              <td className="font-mono text-xs">{p.dni}</td>
              <td className="font-mono text-xs">{p.reserva}</td>
              <td>
                <EstadoBadge valor={p.requisitoMedico} />
              </td>
            </tr>
          ))}
        </Tabla>
      )}
    </Modal>
  )
}
