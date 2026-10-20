"use client"

import { useState } from "react"
import { ShieldCheck } from "lucide-react"
import { esActiva } from "@/lib/oferta"
import { contextoPaquete, formatFecha, horasHasta, useStore } from "@/lib/store"
import { PageHeader } from "@/components/trekk/page-header"
import { Tabla } from "@/components/trekk/tabla"
import { Button } from "@/components/ui/button"
import { AvisoExito } from "@/components/admin/avisos"
import { BarraAcciones, FilaSeleccionable } from "@/components/admin/seleccion"
import { VerificarCertificadoModal } from "@/components/admin/verificar-certificado"

/** CU 18 – Listar certificados médicos pendientes, extendido por CU 19 – Verificar certificado médico */
export default function CertificadosPage() {
  const { certificados, reservas } = useStore()
  const [sel, setSel] = useState<string | null>(null)
  const [verificando, setVerificando] = useState(false)
  const [aviso, setAviso] = useState("")

  const pendientes = certificados
    .filter((c) => c.estado === "Pendiente de verificación") // RN-01
    .map((c) => {
      const reserva = reservas.find((r) => r.id === c.reservaId)
      return reserva && esActiva(reserva) ? { c, reserva, ...contextoPaquete(reserva.paqueteId) } : null
    })
    .filter((x) => x !== null)
    .filter((x) => horasHasta(x.salida.fechaInicio, x.salida.horaSalida) > 12) // RN-02
    .sort((a, b) => a.salida.fechaInicio.localeCompare(b.salida.fechaInicio)) // RN-03

  const seleccionado = pendientes.find((x) => x.c.id === sel)

  return (
    <>
      <PageHeader
        eyebrow="Administración"
        title="Certificados médicos pendientes"
        description="Certificados cargados que esperan verificación, ordenados por la salida más próxima."
      />
      <AvisoExito mensaje={aviso} onCerrar={() => setAviso("")} />

      {pendientes.length === 0 ? (
        <p className="rounded-xl border border-dashed p-10 text-center text-muted-foreground">No se encontraron certificados médicos pendientes de verificación.</p>
      ) : (
        <div className="flex flex-col gap-3">
          <BarraAcciones
            seleccion={seleccionado ? `${seleccionado.reserva.participantes.find((p) => p.dni === seleccionado.c.participanteDni)?.nombre} · ${seleccionado.c.archivo}` : null}
            vacio="Seleccioná un certificado para verificarlo."
          >
            {seleccionado && (
              <Button
                size="sm"
                onClick={() => {
                  setAviso("")
                  setVerificando(true)
                }}
              >
                <ShieldCheck aria-hidden /> Verificar
              </Button>
            )}
          </BarraAcciones>

          <Tabla headers={["", "Nombre", "Apellido", "DNI", "Reserva", "Excursión", "Paquete", "Salida", "Fecha de carga"]}>
            {pendientes.map(({ c, reserva, excursion, paquete, salida }) => {
              const p = reserva.participantes.find((x) => x.dni === c.participanteDni)
              return (
                <FilaSeleccionable key={c.id} nombre="certificado" activa={sel === c.id} etiqueta={`${p?.nombre} ${p?.apellido}`} onSeleccionar={() => setSel(c.id)}>
                  <td className="font-medium">{p?.nombre}</td>
                  <td>{p?.apellido}</td>
                  <td className="font-mono text-xs">{c.participanteDni}</td>
                  <td className="font-mono text-xs">{reserva.id}</td>
                  <td>{excursion.nombre}</td>
                  <td>{paquete.nombre}</td>
                  <td className="whitespace-nowrap">{formatFecha(salida.fechaInicio)}</td>
                  <td className="whitespace-nowrap">{formatFecha(c.fechaCarga)}</td>
                </FilaSeleccionable>
              )
            })}
          </Tabla>
        </div>
      )}

      {verificando && seleccionado && (
        <VerificarCertificadoModal
          certificado={seleccionado.c}
          onCerrar={() => setVerificando(false)}
          onListo={(m) => {
            setVerificando(false)
            setSel(null)
            setAviso(m)
          }}
        />
      )}
    </>
  )
}
