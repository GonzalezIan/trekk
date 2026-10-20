"use client"

import type { ReactNode } from "react"
import { cn } from "@/lib/utils"

/** Barra con las operaciones que habilita el registro seleccionado */
export function BarraAcciones({ seleccion, vacio, children, extra }: { seleccion: string | null; vacio: string; children?: ReactNode; extra?: ReactNode }) {
  return (
    <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-muted/50 px-4 py-3">
      <div className="flex min-w-0 flex-wrap items-center gap-2">
        {seleccion ? (
          <>
            <span className="text-sm text-muted-foreground">Seleccionado:</span>
            <span className="mr-2 text-sm font-medium">{seleccion}</span>
            {children}
          </>
        ) : (
          <span className="text-sm text-muted-foreground">{vacio}</span>
        )}
      </div>
      {extra}
    </div>
  )
}

/** Fila de tabla que se selecciona con un clic (o con el radio, para teclado) */
export function FilaSeleccionable({
  nombre,
  activa,
  etiqueta,
  onSeleccionar,
  children,
}: {
  nombre: string
  activa: boolean
  etiqueta: string
  onSeleccionar: () => void
  children: ReactNode
}) {
  return (
    <tr onClick={onSeleccionar} className={cn("cursor-pointer transition-colors hover:bg-muted/50", activa && "bg-primary/5 hover:bg-primary/10")}>
      <td className="w-8">
        <input type="radio" name={nombre} checked={activa} onChange={onSeleccionar} aria-label={`Seleccionar ${etiqueta}`} className="accent-current" />
      </td>
      {children}
    </tr>
  )
}
