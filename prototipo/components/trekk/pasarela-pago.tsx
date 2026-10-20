"use client"

import { USD_ARS } from "@/lib/data"
import { usd } from "@/lib/store"
import { Label } from "@/components/ui/label"

export const mediosPago = ["Tarjeta de crédito", "Tarjeta de débito", "Mercado Pago"]

/** Simula la Pasarela de Pagos externa (CU 22 · procesarCobro). Devuelve si la transacción fue aprobada. */
export async function procesarCobro(simularRechazo: boolean) {
  await new Promise((r) => setTimeout(r, 1200))
  return !simularRechazo
}

export function montoEnMoneda(montoUsd: number, moneda: "USD" | "ARS") {
  return moneda === "USD" ? montoUsd : montoUsd * USD_ARS
}

export function formatMonto(montoUsd: number, moneda: "USD" | "ARS") {
  return moneda === "USD" ? usd(montoUsd) : `ARS ${(montoUsd * USD_ARS).toLocaleString("es-AR")}`
}

interface Props {
  id: string
  montoUsd: number
  moneda: "USD" | "ARS"
  onMoneda: (m: "USD" | "ARS") => void
  medio: string
  onMedio: (m: string) => void
  simularRechazo: boolean
  onSimularRechazo: (v: boolean) => void
}

export function PasarelaPagoCampos({ id, montoUsd, moneda, onMoneda, medio, onMedio, simularRechazo, onSimularRechazo }: Props) {
  return (
    <div className="flex flex-col gap-4">
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="flex flex-col gap-2">
          <Label htmlFor={`moneda-${id}`}>Moneda</Label>
          <select
            id={`moneda-${id}`}
            value={moneda}
            onChange={(e) => onMoneda(e.target.value as "USD" | "ARS")}
            className="h-9 rounded-lg border bg-background px-3 text-sm"
          >
            <option value="USD">Dólares (USD)</option>
            <option value="ARS">Pesos argentinos (ARS)</option>
          </select>
        </div>
        <div className="flex flex-col gap-2">
          <Label htmlFor={`medio-${id}`}>Medio de pago</Label>
          <select id={`medio-${id}`} value={medio} onChange={(e) => onMedio(e.target.value)} className="h-9 rounded-lg border bg-background px-3 text-sm">
            {mediosPago.map((m) => (
              <option key={m}>{m}</option>
            ))}
          </select>
        </div>
      </div>
      <p className="rounded-lg bg-muted px-4 py-3 text-sm">
        Se cobrarán <strong>{formatMonto(montoUsd, moneda)}</strong>
        {moneda === "ARS" && " (tasa fija 1 USD = 1.500 ARS)"} mediante la Pasarela de Pagos.
      </p>
      <label className="flex items-center gap-2 text-xs text-muted-foreground">
        <input type="checkbox" checked={simularRechazo} onChange={(e) => onSimularRechazo(e.target.checked)} className="size-3.5 accent-current" />
        Demo: simular que la pasarela rechaza la transacción
      </label>
    </div>
  )
}
