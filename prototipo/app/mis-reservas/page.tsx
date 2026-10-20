"use client"

import Link from "next/link"
import { useStore } from "@/lib/store"
import { PageHeader, RequireRol } from "@/components/trekk/page-header"
import { ReservaCard } from "@/components/trekk/reserva-card"

export default function MisReservasPage() {
  const { reservas, cliente } = useStore()
  const mias = reservas.filter((r) => r.clienteDni === cliente.dni)
  return (
    <RequireRol roles={["cliente"]} mensaje="Tus reservas solo son visibles una vez que iniciás sesión con tu cuenta de cliente.">
      <main className="mx-auto max-w-5xl px-4 py-12">
        <PageHeader
          eyebrow="Cliente"
          title="Mis reservas"
          description="Consultá el estado de tus reservas, aboná saldos pendientes, cargá certificados médicos y calificá tus excursiones."
        />
        {mias.length === 0 ? (
          <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed p-10 text-center">
            <p className="text-muted-foreground">Todavía no tenés reservas registradas.</p>
            <Link href="/#excursiones" className="text-sm font-medium text-primary underline-offset-4 hover:underline">
              Explorar excursiones
            </Link>
          </div>
        ) : (
          <div className="flex flex-col gap-4">
            {mias.map((r) => (
              <ReservaCard key={r.id} reserva={r} />
            ))}
          </div>
        )}
      </main>
    </RequireRol>
  )
}
