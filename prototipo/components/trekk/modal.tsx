"use client"

import { useEffect, useRef, type ReactNode } from "react"
import { X } from "lucide-react"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"

/** Ventana modal sobre la pantalla actual (dialog nativo: fondo oscurecido, Esc para cerrar y foco contenido). */
export function Modal({
  abierto,
  onCerrar,
  titulo,
  descripcion,
  children,
  pie,
  className,
}: {
  abierto: boolean
  onCerrar: () => void
  titulo: string
  descripcion?: ReactNode
  children: ReactNode
  pie?: ReactNode
  className?: string
}) {
  const ref = useRef<HTMLDialogElement>(null)

  useEffect(() => {
    const d = ref.current
    if (!d) return
    if (abierto && !d.open) d.showModal()
    if (!abierto && d.open) d.close()
  }, [abierto])

  return (
    <dialog
      ref={ref}
      aria-labelledby="modal-titulo"
      onCancel={(e) => {
        e.preventDefault()
        onCerrar()
      }}
      onClick={(e) => e.target === e.currentTarget && onCerrar()}
      className={cn(
        "m-auto w-[calc(100%-2rem)] max-w-2xl rounded-2xl border bg-card p-0 text-card-foreground shadow-2xl backdrop:bg-black/50 backdrop:backdrop-blur-sm",
        className,
      )}
    >
      {abierto && (
        <div className="flex max-h-[90dvh] flex-col">
          <div className="flex items-start justify-between gap-3 border-b px-5 py-4">
            <div>
              <h2 id="modal-titulo" className="font-display text-xl font-semibold">
                {titulo}
              </h2>
              {descripcion && <div className="text-sm text-muted-foreground">{descripcion}</div>}
            </div>
            <Button variant="ghost" size="icon-sm" aria-label="Cerrar" onClick={onCerrar}>
              <X />
            </Button>
          </div>
          <div className="flex min-h-0 flex-col gap-4 overflow-y-auto px-5 py-4">{children}</div>
          {pie && <div className="flex flex-wrap items-center justify-end gap-2 border-t px-5 py-4">{pie}</div>}
        </div>
      )}
    </dialog>
  )
}

/** Campo de formulario con etiqueta y marca de error */
export function Campo({ id, label, error, children, className }: { id?: string; label: string; error?: boolean; children: ReactNode; className?: string }) {
  return (
    <div className={cn("flex flex-col gap-1.5", className)} data-error={error || undefined}>
      <label htmlFor={id} className={cn("text-sm font-medium", error && "text-destructive")}>
        {label}
      </label>
      {children}
    </div>
  )
}

export const selectCls = "h-9 w-full rounded-lg border bg-background px-3 text-sm aria-invalid:border-destructive"
