"use client"

import { useState } from "react"
import { Star, Upload, Users, XCircle, CreditCard, MessageSquare } from "lucide-react"
import { equipamientos, USD_ARS, type Reserva } from "@/lib/data"
import { contextoPaquete, formatFecha, horasHasta, usd, useStore } from "@/lib/store"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { EstadoBadge } from "./estado-badge"
import { montoEnMoneda, PasarelaPagoCampos, procesarCobro } from "./pasarela-pago"
import { cn } from "@/lib/utils"

type Panel = null | "participantes" | "cancelar" | "pagar" | "conformidad"

export function ReservaCard({ reserva: r }: { reserva: Reserva }) {
  const { cancelarReserva, registrarPago, cargarCertificado, registrarConformidad, conformidades, certificados } = useStore()
  const { paquete, salida, excursion } = contextoPaquete(r.paqueteId)
  const [panel, setPanel] = useState<Panel>(null)
  const [motivo, setMotivo] = useState("")
  const [moneda, setMoneda] = useState<"USD" | "ARS">("USD")
  const [medio, setMedio] = useState("Tarjeta de crédito")
  const [simularRechazo, setSimularRechazo] = useState(false)
  const [procesando, setProcesando] = useState(false)
  const [valoracion, setValoracion] = useState(0)
  const [comentario, setComentario] = useState("")
  const [mensaje, setMensaje] = useState("")

  const abonado = r.pagos.reduce((a, p) => a + (p.moneda === "USD" ? p.monto : p.monto / USD_ARS), 0)
  const saldo = Math.max(0, r.importeTotal - abonado)
  const conformidad = conformidades.find((c) => c.reservaId === r.id)
  const puedeCargar = horasHasta(salida.fechaInicio, salida.horaSalida) > 24

  const toggle = (p: Panel) => {
    setMensaje("")
    setPanel(panel === p ? null : p)
  }

  return (
    <article className="overflow-hidden rounded-2xl border bg-card">
      <div className="flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between">
        <div className="flex flex-col gap-1">
          <div className="flex flex-wrap items-center gap-2">
            <span className="font-mono text-xs text-muted-foreground">{r.id}</span>
            <EstadoBadge valor={r.estado} />
            <EstadoBadge valor={r.situacionPago} />
          </div>
          <h2 className="font-display text-xl font-semibold">{excursion.nombre}</h2>
          <p className="text-sm text-muted-foreground">
            {formatFecha(salida.fechaInicio)} · Paquete {paquete.nombre} · {r.participantes.length}{" "}
            {r.participantes.length === 1 ? "participante" : "participantes"}
          </p>
          {r.equipamiento.length > 0 && (
            <p className="text-xs text-muted-foreground">
              Equipamiento alquilado:{" "}
              {r.equipamiento.map((e) => `${e.cantidad} × ${equipamientos.find((x) => x.codigo === e.codigo)?.nombre ?? e.codigo}`).join(", ")}
            </p>
          )}
        </div>
        <div className="flex flex-col items-start gap-0.5 md:items-end">
          <span className="font-display text-2xl font-semibold">{usd(r.importeTotal)}</span>
          {saldo > 0 && r.estado !== "Cancelada" && <span className="text-xs text-warning">Saldo pendiente {usd(saldo)}</span>}
        </div>
      </div>

      <div className="flex flex-wrap gap-2 border-t px-5 py-3">
        <Button variant={panel === "participantes" ? "secondary" : "ghost"} size="sm" onClick={() => toggle("participantes")}>
          <Users /> Participantes
        </Button>
        {r.estado === "Pendiente" && saldo > 0 && (
          <Button variant={panel === "pagar" ? "secondary" : "ghost"} size="sm" onClick={() => toggle("pagar")}>
            <CreditCard /> Registrar pago
          </Button>
        )}
        {r.estado === "Realizada" && !conformidad && (
          <Button variant={panel === "conformidad" ? "secondary" : "ghost"} size="sm" onClick={() => toggle("conformidad")}>
            <MessageSquare /> Registrar conformidad
          </Button>
        )}
        {(r.estado === "Pendiente" || r.estado === "Confirmada") && (
          <Button variant="ghost" size="sm" className="text-destructive" onClick={() => toggle("cancelar")}>
            <XCircle /> Cancelar reserva
          </Button>
        )}
      </div>

      {panel === "participantes" && (
        <div className="border-t bg-muted/40 p-5">
          <ul className="flex flex-col divide-y rounded-xl border bg-card">
            {r.participantes.map((p) => {
              const cert = certificados.find((c) => c.reservaId === r.id && c.participanteDni === p.dni)
              return (
                <li key={p.dni} className="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                  <div>
                    <p className="font-medium">
                      {p.nombre} {p.apellido}
                    </p>
                    <p className="text-xs text-muted-foreground">DNI {p.dni}</p>
                  </div>
                  <div className="flex flex-wrap items-center gap-2">
                    <EstadoBadge valor={p.requisitoMedico} />
                    {cert && p.requisitoMedico === "Pendiente" && <EstadoBadge valor={cert.estado} />}
                    {p.requisitoMedico === "Pendiente" && puedeCargar && r.estado !== "Cancelada" && (
                      <label className="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-medium hover:bg-muted">
                        <Upload className="size-3.5" aria-hidden />
                        {cert ? "Reemplazar certificado" : "Cargar certificado"}
                        <input
                          type="file"
                          accept=".pdf,.png,.jpg,.jpeg"
                          className="sr-only"
                          onChange={(e) => {
                            const f = e.target.files?.[0]
                            if (!f) return
                            if (!/\.(pdf|png|jpe?g)$/i.test(f.name)) return setMensaje("Solo se admiten archivos PDF, PNG, JPG o JPEG")
                            cargarCertificado(r.id, p.dni, f.name)
                            setMensaje(`Certificado de ${p.nombre} cargado. Queda pendiente de verificación.`)
                          }}
                        />
                      </label>
                    )}
                  </div>
                </li>
              )
            })}
          </ul>
          {mensaje && (
            <p role="status" className="mt-3 text-sm text-muted-foreground">
              {mensaje}
            </p>
          )}
        </div>
      )}

      {panel === "pagar" && (
        <div className="flex flex-col gap-4 border-t bg-muted/40 p-5">
          <div className="flex flex-col gap-1">
            <p className="text-sm text-muted-foreground">Saldo restante</p>
            <p className="font-display text-2xl font-semibold">{usd(saldo)}</p>
            <p className="text-xs text-muted-foreground">Debe abonarse dentro de las 24 hs del registro de la reserva.</p>
          </div>
          <PasarelaPagoCampos
            id={r.id}
            montoUsd={saldo}
            moneda={moneda}
            onMoneda={setMoneda}
            medio={medio}
            onMedio={(m) => {
              setMedio(m)
              setMensaje("")
            }}
            simularRechazo={simularRechazo}
            onSimularRechazo={setSimularRechazo}
          />
          {mensaje && (
            <p role="alert" className="text-sm text-destructive">
              {mensaje}
            </p>
          )}
          <div className="flex justify-end">
            <Button
              disabled={procesando}
              onClick={async () => {
                setMensaje("")
                setProcesando(true)
                const aprobada = await procesarCobro(simularRechazo)
                setProcesando(false)
                if (!aprobada) return setMensaje("La Pasarela de Pagos rechazó la transacción. Elegí otro medio de pago e intentá nuevamente.")
                registrarPago(r.id, { monto: montoEnMoneda(saldo, moneda), moneda, medio, tipo: "Saldo" })
                setPanel(null)
              }}
            >
              {procesando ? "Procesando pago…" : "Confirmar pago"}
            </Button>
          </div>
        </div>
      )}

      {panel === "cancelar" && (
        <div className="flex flex-col gap-3 border-t bg-destructive/5 p-5">
          <Label htmlFor={`motivo-${r.id}`}>Motivo de la cancelación</Label>
          <Textarea id={`motivo-${r.id}`} value={motivo} onChange={(e) => setMotivo(e.target.value)} />
          <p className="text-xs text-muted-foreground">La cancelación no genera reintegro de los importes abonados.</p>
          <div className="flex justify-end">
            <Button variant="destructive" disabled={!motivo.trim()} onClick={() => cancelarReserva(r.id, motivo)}>
              Confirmar cancelación
            </Button>
          </div>
        </div>
      )}

      {panel === "conformidad" && (
        <div className="flex flex-col gap-3 border-t bg-muted/40 p-5">
          <fieldset className="flex flex-col gap-2">
            <legend className="text-sm font-medium">Valoración</legend>
            <div className="flex gap-1">
              {[1, 2, 3, 4, 5].map((n) => (
                <button key={n} type="button" aria-label={`${n} de 5`} aria-pressed={valoracion === n} onClick={() => setValoracion(n)}>
                  <Star className={cn("size-7 text-muted-foreground", n <= valoracion && "fill-accent text-accent")} />
                </button>
              ))}
            </div>
          </fieldset>
          <Label htmlFor={`com-${r.id}`}>Comentario (opcional)</Label>
          <Textarea id={`com-${r.id}`} value={comentario} onChange={(e) => setComentario(e.target.value)} aria-invalid={comentario.length > 500 || undefined} />
          <p className={cn("text-xs text-muted-foreground", comentario.length > 500 && "text-destructive")}>{comentario.length}/500</p>
          <div className="flex justify-end">
            <Button
              disabled={valoracion === 0 || comentario.length > 500}
              onClick={() => {
                registrarConformidad({ reservaId: r.id, valoracion, comentario })
                setPanel(null)
              }}
            >
              Enviar
            </Button>
          </div>
        </div>
      )}

      {conformidad && (
        <div className="flex items-center gap-2 border-t px-5 py-3 text-sm text-muted-foreground">
          <Star className="size-4 fill-accent text-accent" aria-hidden />
          Tu valoración: {conformidad.valoracion}/5 {conformidad.comentario && `— “${conformidad.comentario}”`}
        </div>
      )}
      {r.motivoCancelacion && <p className="border-t px-5 py-3 text-sm text-muted-foreground">Motivo: {r.motivoCancelacion}</p>}
    </article>
  )
}
