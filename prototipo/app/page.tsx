import Image from "next/image"
import { Mountain } from "lucide-react"
import { Catalogo, IngresoPortada } from "@/components/trekk/catalogo"

export default function HomePage() {
  return (
    <main>
      <section className="relative isolate overflow-hidden">
        <Image src="/images/hero.png" alt="" fill priority className="-z-10 object-cover" />
        <div className="absolute inset-0 -z-10 bg-gradient-to-t from-background via-background/60 to-background/10" />
        <div className="mx-auto grid max-w-6xl items-center gap-10 px-4 pb-16 pt-24 md:grid-cols-[1fr_minmax(0,24rem)] md:pt-32">
          <div className="flex flex-col gap-5">
            <p className="flex items-center gap-2 font-display text-2xl font-semibold">
              <span className="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                <Mountain className="size-5" aria-hidden />
              </span>
              Trekk
            </p>
            <h1 className="max-w-3xl font-display text-4xl font-semibold leading-tight text-balance md:text-6xl">
              Caminá los senderos del fin del mundo.
            </h1>
            <p className="max-w-xl text-lg text-muted-foreground text-pretty">
              Elegí una excursión, una salida programada y el paquete que mejor se ajuste a tu grupo. Reservá en minutos y
              gestioná pagos, participantes y certificados desde un solo lugar.
            </p>
          </div>
          <IngresoPortada />
        </div>
      </section>

      <Catalogo />

      <footer className="border-t">
        <div className="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-8 text-sm text-muted-foreground md:flex-row md:justify-between">
          <p>Trekk — Sistema de Gestión de Excursiones de Senderismo</p>
          <p>UNPSJB · Facultad de Ingeniería · Grupo 3</p>
        </div>
      </footer>
    </main>
  )
}
