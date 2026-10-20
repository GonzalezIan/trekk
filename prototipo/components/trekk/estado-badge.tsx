import { cn } from "@/lib/utils"

const tonos: Record<string, string> = {
  Pendiente: "bg-warning/15 text-warning border-warning/30",
  "Pendiente de pago total": "bg-warning/15 text-warning border-warning/30",
  "Pendiente de verificación": "bg-warning/15 text-warning border-warning/30",
  Confirmada: "bg-success/15 text-success border-success/30",
  "Pago completo": "bg-success/15 text-success border-success/30",
  Cumplido: "bg-success/15 text-success border-success/30",
  Válido: "bg-success/15 text-success border-success/30",
  Activo: "bg-success/15 text-success border-success/30",
  Activa: "bg-success/15 text-success border-success/30",
  Programada: "bg-primary/10 text-primary border-primary/25",
  Disponible: "bg-primary/10 text-primary border-primary/25",
  Realizada: "bg-secondary text-secondary-foreground border-border",
  Finalizada: "bg-secondary text-secondary-foreground border-border",
  "No requerido": "bg-muted text-muted-foreground border-border",
  Cancelada: "bg-destructive/10 text-destructive border-destructive/25",
  Cancelado: "bg-destructive/10 text-destructive border-destructive/25",
  Inactiva: "bg-muted text-muted-foreground border-border",
  Inválido: "bg-destructive/10 text-destructive border-destructive/25",
  Inactivo: "bg-muted text-muted-foreground border-border",
  Baja: "bg-success/15 text-success border-success/30",
  Media: "bg-warning/15 text-warning border-warning/30",
  Alta: "bg-destructive/10 text-destructive border-destructive/25",
}

export function EstadoBadge({ valor, className }: { valor: string; className?: string }) {
  return (
    <span
      className={cn(
        "inline-flex items-center whitespace-nowrap rounded-full border px-2 py-0.5 text-xs font-medium",
        tonos[valor] ?? "bg-muted text-muted-foreground border-border",
        className,
      )}
    >
      {valor}
    </span>
  )
}
