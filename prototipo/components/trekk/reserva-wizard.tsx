"use client"

import { useEffect, useRef, useState } from "react"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { Timer, Minus, Plus, CheckCircle2, AlertTriangle, Backpack, X, Trash2 } from "lucide-react"
import { equipamientos, refugios, type Equipamiento } from "@/lib/data"
import { contextoPaquete, edad, formatFecha, getPaquete, getSalida, usd, useStore } from "@/lib/store"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { RequireRol } from "./page-header"
import { EstadoBadge } from "./estado-badge"
import { formatMonto, montoEnMoneda, PasarelaPagoCampos, procesarCobro } from "./pasarela-pago"
import { cn } from "@/lib/utils"

type P = { dni: string; nombre: string; apellido: string; fechaNacimiento: string }

const pasos = ["Participantes", "Datos", "Equipamiento", "Resumen", "Pago"]
const RETENCION_SEG = 20 * 60

export function ReservaWizard({ paqueteId }: { paqueteId: string }) {
  return (
    <RequireRol roles={["cliente"]} mensaje="Tenés que iniciar sesión como cliente para registrar una reserva.">
      <PaqueteReservable paqueteId={paqueteId} />
    </RequireRol>
  )
}

/** Solo se reservan paquetes "Disponible" de salidas "Programada" (el administrativo puede haberlos cancelado) */
function PaqueteReservable({ paqueteId }: { paqueteId: string }) {
  useStore()
  const p = getPaquete(paqueteId)
  const s = p && getSalida(p.salidaId)
  if (!p || !s || p.estado !== "Disponible" || s.estado !== "Programada") {
    return (
      <main className="mx-auto flex max-w-md flex-col items-center gap-3 px-4 py-24 text-center">
        <h1 className="font-display text-2xl font-semibold">Paquete no disponible</h1>
        <p className="text-muted-foreground">El paquete no existe o ya no admite reservas.</p>
        <Link href="/#excursiones" className="text-sm font-medium text-primary underline-offset-4 hover:underline">
          Volver al catálogo
        </Link>
      </main>
    )
  }
  return <Wizard paqueteId={paqueteId} />
}

function Wizard({ paqueteId }: { paqueteId: string }) {
  const { cupoOcupado, stockDisponible, crearReserva, cliente } = useStore()
  const router = useRouter()
  const { paquete, salida, excursion } = contextoPaquete(paqueteId)
  const refugio = refugios.find((r) => r.id === paquete.refugioId)
  const cupoLibre = salida.cupoTotal - cupoOcupado(salida.id)

  const [paso, setPaso] = useState(0)
  const [cantidad, setCantidad] = useState(1)
  const [restante, setRestante] = useState<number | null>(null)
  const [participantes, setParticipantes] = useState<P[]>([])
  const [quiereAlquilar, setQuiereAlquilar] = useState<boolean | null>(null)
  const [alquiler, setAlquiler] = useState<Record<string, number>>({})
  const [avisoStock, setAvisoStock] = useState("")
  // Catálogo de alquiler (CU 25) en ventana modal; se guarda la selección previa para poder cancelar
  const catalogoRef = useRef<HTMLDialogElement>(null)
  const [cantAgregar, setCantAgregar] = useState<Record<string, number>>({})
  const [respaldo, setRespaldo] = useState<{ alquiler: Record<string, number>; quiereAlquilar: boolean | null } | null>(null)
  const [modalidad, setModalidad] = useState<"seña" | "total">("seña")
  const [moneda, setMoneda] = useState<"USD" | "ARS">("USD")
  const [medio, setMedio] = useState("Tarjeta de crédito")
  const [simularRechazo, setSimularRechazo] = useState(false)
  const [error, setError] = useState("")
  const [procesando, setProcesando] = useState(false)
  const [creada, setCreada] = useState<string | null>(null)

  useEffect(() => {
    if (restante === null || creada) return
    if (restante <= 0) return
    const t = setTimeout(() => setRestante((r) => (r ?? 0) - 1), 1000)
    return () => clearTimeout(t)
  }, [restante, creada])

  // RN-01 (CU 25): solo se lista el equipamiento con stock mayor a 0
  const catalogo = equipamientos.filter((e) => stockDisponible(e.codigo) > 0)

  const alquilados = Object.entries(alquiler)
    .filter(([, n]) => n > 0)
    .map(([codigo, n]) => ({ eq: equipamientos.find((e) => e.codigo === codigo)!, cantidad: n }))

  const costoEquipo = alquilados.reduce((acc, a) => acc + a.eq.precioAlquiler * a.cantidad, 0)
  const importeTotal = paquete.precioTotal * cantidad + costoEquipo
  const aPagar = modalidad === "seña" ? importeTotal * 0.5 : importeTotal
  const expirado = restante !== null && restante <= 0 && !creada

  function confirmarCantidad() {
    setError("")
    if (!Number.isInteger(cantidad) || cantidad < 1) return setError("Debe ingresar una cantidad válida de participantes")
    if (cantidad > cupoLibre) return setError(`No hay cupos suficientes para el paquete. Cupos libres: ${cupoLibre}`)
    if (refugio && cantidad > refugio.camasDisponibles)
      return setError(`No hay camas suficientes en ${refugio.nombre}. Camas libres: ${refugio.camasDisponibles}`)
    setParticipantes(
      Array.from({ length: cantidad }, () => ({ dni: "", nombre: "", apellido: "", fechaNacimiento: "" })),
    )
    setRestante(RETENCION_SEG)
    setPaso(1)
  }

  function validarParticipantes() {
    setError("")
    if (participantes.some((p) => !p.dni || !p.nombre || !p.apellido || !p.fechaNacimiento))
      return setError("Debe completar los datos de todos los participantes")
    if (new Set(participantes.map((p) => p.dni)).size !== participantes.length)
      return setError("Hay participantes con DNI repetido")
    const menor = participantes.find((p) => edad(p.fechaNacimiento) < 18)
    if (!excursion.aceptaMenores && menor) return setError(`La excursión no acepta menores de edad: ${menor.nombre} es menor de 18 años`)
    if (!participantes.some((p) => edad(p.fechaNacimiento) >= 18))
      return setError("La reserva debe incluir al menos un participante mayor de 18 años")
    setPaso(2)
  }

  function cambiarAlquiler(eq: Equipamiento, n: number) {
    const stock = stockDisponible(eq.codigo)
    // CU 14 · 13.1.1: no se pueden cargar más elementos que el stock disponible
    if (n > stock) {
      setAvisoStock(`No hay stock suficiente de ${eq.nombre}: quedan ${stock} unidades disponibles.`)
      n = stock
    } else {
      setAvisoStock("")
    }
    setAlquiler((prev) => ({ ...prev, [eq.codigo]: Math.max(0, n) }))
  }

  function abrirCatalogo() {
    setRespaldo({ alquiler, quiereAlquilar })
    setQuiereAlquilar(true)
    setAvisoStock("")
    catalogoRef.current?.showModal()
  }

  function agregarAlResumen(eq: Equipamiento) {
    const n = Math.max(1, Math.floor(cantAgregar[eq.codigo] || 1))
    cambiarAlquiler(eq, (alquiler[eq.codigo] ?? 0) + n)
    setCantAgregar((prev) => ({ ...prev, [eq.codigo]: 1 }))
  }

  function cancelarCatalogo() {
    if (respaldo) {
      setAlquiler(respaldo.alquiler)
      setQuiereAlquilar(respaldo.quiereAlquilar)
    }
    setAvisoStock("")
    catalogoRef.current?.close()
  }

  function confirmarCatalogo() {
    // Si confirma sin elegir nada, equivale a continuar sin alquilar
    if (alquilados.length === 0) setQuiereAlquilar(false)
    setAvisoStock("")
    catalogoRef.current?.close()
  }

  function confirmarEquipamiento() {
    if (!quiereAlquilar) setAlquiler({})
    setAvisoStock("")
    setPaso(3)
  }

  async function pagar() {
    setError("")
    setProcesando(true)
    const aprobada = await procesarCobro(simularRechazo)
    setProcesando(false)
    if (!aprobada) {
      // CU 22 · transacción rechazada: la reserva no se registra y se vuelve a pedir el medio de pago
      return setError("La Pasarela de Pagos rechazó la transacción. Elegí otro medio de pago e intentá nuevamente; tus cupos siguen retenidos.")
    }
    const id = crearReserva({
      paqueteId,
      participantes,
      equipamiento: alquilados.map((a) => ({ codigo: a.eq.codigo, cantidad: a.cantidad })),
      importeTotal,
      pago: {
        monto: montoEnMoneda(aPagar, moneda),
        moneda,
        medio,
        tipo: modalidad === "seña" ? "Seña" : "Total",
      },
    })
    setCreada(id)
  }

  if (creada) {
    return (
      <main className="mx-auto flex max-w-xl flex-col items-center gap-4 px-4 py-20 text-center">
        <CheckCircle2 className="size-14 text-success" aria-hidden />
        <h1 className="font-display text-3xl font-semibold">Reserva {creada} registrada</h1>
        <p className="text-muted-foreground text-pretty">
          {modalidad === "seña"
            ? "Abonaste la seña del 50%. Recordá saldar el importe restante dentro de las próximas 24 horas."
            : "Abonaste el importe total."}{" "}
          {excursion.requiereCertificado &&
            "Esta excursión requiere certificado médico: cargalo para cada participante desde Mis reservas."}
        </p>
        {alquilados.length > 0 && (
          <p className="text-sm text-muted-foreground text-pretty">
            Equipamiento alquilado: {alquilados.map((a) => `${a.cantidad} × ${a.eq.nombre}`).join(", ")}. Debe devolverse al finalizar la salida.
          </p>
        )}
        <p className="text-sm text-muted-foreground">Te enviamos la confirmación a {cliente.email}.</p>
        <Button size="lg" onClick={() => router.push("/mis-reservas")}>
          Ir a mis reservas
        </Button>
      </main>
    )
  }

  const mm = restante !== null ? String(Math.floor(restante / 60)).padStart(2, "0") : ""
  const ss = restante !== null ? String(restante % 60).padStart(2, "0") : ""
  const retenido = ["cupos", refugio && "camas", alquilados.length > 0 && "equipamiento"].filter(Boolean) as string[]

  return (
    <main className="mx-auto grid max-w-6xl gap-8 px-4 py-10 lg:grid-cols-[1fr_340px]">
      <div className="flex min-w-0 flex-col gap-6">
        <div className="flex flex-col gap-1">
          <Link href={`/excursiones/${excursion.id}`} className="text-sm text-muted-foreground hover:text-foreground">
            ← Volver a {excursion.nombre}
          </Link>
          <h1 className="font-display text-3xl font-semibold">Registrar reserva</h1>
        </div>

        <ol className="grid grid-cols-5 gap-2" aria-label="Pasos">
          {pasos.map((p, i) => (
            <li key={p} aria-current={i === paso ? "step" : undefined} className="flex flex-col gap-1.5">
              <span className={cn("h-1 rounded-full bg-muted", i <= paso && "bg-primary")} />
              <span className={cn("text-xs text-muted-foreground", i === paso ? "font-medium text-foreground" : "hidden sm:inline")}>
                {i + 1}. {p}
              </span>
            </li>
          ))}
        </ol>

        {restante !== null && (
          <div
            role="status"
            className={cn(
              "flex items-center gap-2 rounded-xl border px-4 py-3 text-sm",
              expirado ? "border-destructive/40 bg-destructive/10 text-destructive" : "bg-muted",
            )}
          >
            <Timer className="size-4 shrink-0" aria-hidden />
            {expirado ? (
              <span>El tiempo de retención expiró. Los cupos y el equipamiento fueron liberados; volvé a intentar la reserva.</span>
            ) : (
              <span>
                {retenido.join(", ").replace(/, ([^,]*)$/, " y $1").replace(/^./, (c) => c.toUpperCase())} retenidos por{" "}
                <strong className="tabular-nums">
                  {mm}:{ss}
                </strong>
              </span>
            )}
          </div>
        )}

        <section className="flex flex-col gap-5 rounded-2xl border bg-card p-4 sm:p-6">
          {paso === 0 && (
            <>
              <div>
                <h2 className="font-display text-xl font-semibold">¿Cuántas personas realizarán la excursión?</h2>
                <p className="text-sm text-muted-foreground">
                  Cupos libres: {cupoLibre}
                  {refugio && ` · Camas libres en refugio: ${refugio.camasDisponibles}`}
                </p>
              </div>
              <div className="flex items-center gap-3">
                <Button variant="outline" size="icon-lg" aria-label="Quitar participante" onClick={() => setCantidad((c) => Math.max(1, c - 1))}>
                  <Minus />
                </Button>
                <output aria-live="polite" className="w-12 text-center font-display text-3xl font-semibold tabular-nums">
                  {cantidad}
                </output>
                <Button variant="outline" size="icon-lg" aria-label="Agregar participante" onClick={() => setCantidad((c) => c + 1)}>
                  <Plus />
                </Button>
              </div>
            </>
          )}

          {paso === 1 && (
            <>
              <h2 className="font-display text-xl font-semibold">Datos de los participantes</h2>
              {participantes.map((p, i) => {
                const cambiar = (campo: keyof P, valor: string) =>
                  setParticipantes((prev) => prev.map((x, j) => (j === i ? { ...x, [campo]: valor } : x)))
                return (
                  <fieldset key={i} className="grid gap-3 rounded-xl border p-4 sm:grid-cols-4">
                    <legend className="px-1 text-sm font-medium">Participante {i + 1}</legend>
                    <div className="flex flex-col gap-1.5">
                      <Label htmlFor={`dni-${i}`}>DNI</Label>
                      <Input id={`dni-${i}`} inputMode="numeric" autoComplete="off" value={p.dni} onChange={(e) => cambiar("dni", e.target.value)} />
                    </div>
                    {(["nombre", "apellido"] as const).map((k) => (
                      <div key={k} className="flex flex-col gap-1.5">
                        <Label htmlFor={`${k}-${i}`} className="capitalize">
                          {k}
                        </Label>
                        <Input
                          id={`${k}-${i}`}
                          autoComplete="off"
                          value={p[k]}
                          onChange={(e) => cambiar(k, e.target.value)}
                        />
                      </div>
                    ))}
                    <div className="flex flex-col gap-1.5">
                      <Label htmlFor={`fn-${i}`}>Nacimiento</Label>
                      <Input
                        id={`fn-${i}`}
                        type="date"
                        value={p.fechaNacimiento}
                        onChange={(e) => cambiar("fechaNacimiento", e.target.value)}
                      />
                    </div>
                  </fieldset>
                )
              })}
            </>
          )}

          {paso === 2 && (
            <>
              <h2 className="font-display text-xl font-semibold">Equipamiento</h2>

              {/* El equipamiento obligatorio es texto informativo de la excursión: no está vinculado al catálogo de alquiler */}
              <div role="note" className="flex flex-col gap-2 rounded-xl border border-warning/30 bg-warning/10 p-4">
                <p className="flex items-center gap-2 font-medium">
                  <Backpack className="size-5 shrink-0 text-warning" aria-hidden /> Equipamiento obligatorio
                </p>
                <p className="text-sm">Cada participante debe contar con el siguiente equipamiento para realizar la excursión:</p>
                <ul className="list-disc space-y-0.5 pl-6 text-sm">
                  {excursion.equipamientoObligatorio.map((q) => (
                    <li key={q}>{q}</li>
                  ))}
                </ul>
              </div>

              {salida.equipamientoIncluido.length > 0 && (
                <p className="text-sm text-muted-foreground">
                  La salida incluye sin costo: <span className="text-foreground">{salida.equipamientoIncluido.join(", ")}</span>.
                </p>
              )}

              <div className="flex flex-col gap-3 border-t pt-5">
                <h3 className="font-display text-lg font-semibold">¿Querés agregar equipamiento de alquiler a la reserva?</h3>
                <div role="radiogroup" aria-label="Equipamiento de alquiler" className="grid gap-3 sm:grid-cols-2">
                  {(
                    [
                      [false, "No, continuar sin alquilar", "Cada participante trae su propio equipo."],
                      [true, "Sí, quiero alquilar", "Abrí el catálogo y elegí los elementos."],
                    ] as const
                  ).map(([v, titulo, texto]) => (
                    <button
                      key={String(v)}
                      role="radio"
                      aria-checked={quiereAlquilar === v}
                      onClick={() => {
                        if (v) return abrirCatalogo()
                        setQuiereAlquilar(false)
                        setAlquiler({})
                        setAvisoStock("")
                      }}
                      className={cn(
                        "flex flex-col gap-1 rounded-xl border p-4 text-left transition-colors hover:border-primary/50",
                        quiereAlquilar === v && "border-primary ring-1 ring-primary",
                      )}
                    >
                      <span className="font-medium">{titulo}</span>
                      <span className="text-sm text-muted-foreground">{texto}</span>
                    </button>
                  ))}
                </div>
              </div>

              {alquilados.length > 0 && (
                <div className="flex flex-col gap-3 rounded-xl border bg-primary/5 p-4">
                  <div className="flex items-center justify-between gap-3">
                    <h3 className="text-sm font-semibold">Equipamiento de alquiler seleccionado</h3>
                    <Button variant="outline" size="sm" onClick={abrirCatalogo}>
                      Modificar selección
                    </Button>
                  </div>
                  <ul className="flex flex-col gap-1 text-sm">
                    {alquilados.map((a) => (
                      <li key={a.eq.codigo} className="flex justify-between gap-3">
                        <span>
                          {a.cantidad} × {a.eq.nombre}
                        </span>
                        <span className="tabular-nums">{usd(a.eq.precioAlquiler * a.cantidad)}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              <dialog
                ref={catalogoRef}
                aria-labelledby="catalogo-alquiler"
                onCancel={(e) => {
                  e.preventDefault()
                  cancelarCatalogo()
                }}
                onClick={(e) => e.target === e.currentTarget && cancelarCatalogo()}
                className="m-auto w-[calc(100%-2rem)] max-w-4xl rounded-2xl border bg-card p-0 text-card-foreground shadow-2xl backdrop:bg-black/50 backdrop:backdrop-blur-sm"
              >
                <div className="flex h-[min(40rem,90dvh)] flex-col">
                  <div className="flex items-start justify-between gap-3 border-b px-5 py-4">
                    <div>
                      <h2 id="catalogo-alquiler" className="font-display text-xl font-semibold">
                        Equipamiento disponible para alquiler
                      </h2>
                      <p className="text-sm text-muted-foreground">Indicá la cantidad y tocá + para sumarlo a tu selección.</p>
                    </div>
                    <Button variant="ghost" size="icon-sm" aria-label="Cerrar" onClick={cancelarCatalogo}>
                      <X />
                    </Button>
                  </div>

                  <div className="grid min-h-0 flex-1 grid-rows-[1fr_auto] md:grid-cols-[1fr_18rem] md:grid-rows-1">
                    {/* Catálogo de alquiler (CU 25) */}
                    <div className="flex min-h-0 flex-col gap-3 overflow-y-auto p-4">
                      {catalogo.length === 0 ? (
                        <p className="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                          Actualmente no hay equipamiento disponible para alquiler.
                        </p>
                      ) : (
                        <ul className="flex flex-col gap-2">
                          {catalogo.map((eq) => {
                            const enSeleccion = alquiler[eq.codigo] ?? 0
                            const stock = stockDisponible(eq.codigo)
                            return (
                              <li key={eq.codigo} className="flex items-center justify-between gap-3 rounded-xl border px-4 py-3">
                                <div className="min-w-0">
                                  <p className="flex flex-wrap items-center gap-x-2 gap-y-1 font-medium">
                                    {eq.nombre}
                                    {salida.equipamientoIncluido.includes(eq.nombre) && (
                                      <span className="rounded-full border border-success/30 bg-success/15 px-2 py-0.5 text-[11px] font-medium text-success">
                                        Ya incluido en la salida
                                      </span>
                                    )}
                                  </p>
                                  <p className="text-xs text-muted-foreground">{eq.descripcion}</p>
                                  <p className="text-xs text-muted-foreground">
                                    <span className="font-medium text-foreground sm:hidden">{usd(eq.precioAlquiler)} · </span>
                                    {stock} disponibles
                                    {enSeleccion > 0 && <span className="font-medium text-primary"> · {enSeleccion} en tu selección</span>}
                                  </p>
                                </div>
                                <div className="flex shrink-0 items-center gap-2">
                                  <span className="hidden text-sm font-medium tabular-nums sm:inline">{usd(eq.precioAlquiler)}</span>
                                  <Input
                                    type="number"
                                    inputMode="numeric"
                                    aria-label={`Cantidad de ${eq.nombre}`}
                                    min={1}
                                    max={stock}
                                    value={cantAgregar[eq.codigo] ?? 1}
                                    onChange={(e) => setCantAgregar((prev) => ({ ...prev, [eq.codigo]: Number(e.target.value) }))}
                                    className="h-8 w-14 text-center tabular-nums"
                                  />
                                  <Button size="icon-sm" aria-label={`Agregar ${eq.nombre}`} onClick={() => agregarAlResumen(eq)}>
                                    <Plus />
                                  </Button>
                                </div>
                              </li>
                            )
                          })}
                        </ul>
                      )}
                    </div>

                    {/* Resumen de lo que se va a agregar a la reserva */}
                    <aside aria-label="Equipamiento a agregar" className="flex min-h-0 flex-col gap-3 border-t bg-muted/40 p-4 md:border-l md:border-t-0">
                      <h3 className="text-sm font-semibold">Vas a agregar</h3>
                      {alquilados.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Todavía no agregaste equipamiento.</p>
                      ) : (
                        <ul className="flex max-h-28 flex-col gap-2 overflow-y-auto md:max-h-none md:flex-1">
                          {alquilados.map((a) => (
                            <li key={a.eq.codigo} className="flex items-start justify-between gap-2 text-sm">
                              <div>
                                <p className="font-medium">
                                  {a.eq.nombre} ×{a.cantidad}
                                </p>
                                <p className="tabular-nums text-muted-foreground">{usd(a.eq.precioAlquiler * a.cantidad)}</p>
                              </div>
                              <Button variant="ghost" size="icon-sm" aria-label={`Quitar ${a.eq.nombre}`} onClick={() => cambiarAlquiler(a.eq, 0)}>
                                <Trash2 />
                              </Button>
                            </li>
                          ))}
                        </ul>
                      )}
                      {avisoStock && (
                        <p role="alert" className="flex items-start gap-2 text-xs text-warning">
                          <AlertTriangle className="mt-0.5 size-3.5 shrink-0" aria-hidden /> {avisoStock}
                        </p>
                      )}
                      <div className="mt-auto flex flex-col gap-2 border-t pt-3">
                        <p className="flex items-baseline justify-between text-sm">
                          Total a agregar <strong className="font-display text-xl tabular-nums">{usd(costoEquipo)}</strong>
                        </p>
                        <Button size="lg" onClick={confirmarCatalogo}>
                          Confirmar
                        </Button>
                        <Button variant="ghost" size="sm" onClick={cancelarCatalogo}>
                          Cancelar
                        </Button>
                      </div>
                    </aside>
                  </div>
                </div>
              </dialog>

            </>
          )}

          {paso === 3 && (
            <>
              <div>
                <h2 className="font-display text-xl font-semibold">Resumen de la reserva</h2>
                <p className="text-sm text-muted-foreground">Revisá los datos antes de confirmar y pagar.</p>
              </div>

              <div className="flex flex-col gap-2">
                <h3 className="text-sm font-semibold">Participantes</h3>
                <ul className="flex flex-col divide-y rounded-xl border">
                  {participantes.map((p) => (
                    <li key={p.dni} className="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5">
                      <div>
                        <p className="font-medium">
                          {p.nombre} {p.apellido}
                        </p>
                        <p className="text-xs text-muted-foreground">
                          DNI {p.dni} · {edad(p.fechaNacimiento)} años
                        </p>
                      </div>
                      <EstadoBadge valor={excursion.requiereCertificado ? "Pendiente" : "No requerido"} />
                    </li>
                  ))}
                </ul>
                {excursion.requiereCertificado && (
                  <p className="text-xs text-muted-foreground">
                    Requisito médico pendiente: después de reservar, cargá el certificado de cada participante desde Mis reservas.
                  </p>
                )}
              </div>

              <div className="flex flex-col gap-2">
                <h3 className="text-sm font-semibold">Equipamiento</h3>
                <dl className="flex flex-col gap-1.5 rounded-xl border px-4 py-3 text-sm">
                  <div className="flex justify-between gap-3">
                    <dt className="text-muted-foreground">Incluido por la salida</dt>
                    <dd className="text-right">{salida.equipamientoIncluido.join(", ") || "—"}</dd>
                  </div>
                  <div className="flex justify-between gap-3">
                    <dt className="text-muted-foreground">Alquilado</dt>
                    <dd className="text-right">{alquilados.map((a) => `${a.cantidad} × ${a.eq.nombre}`).join(", ") || "Ninguno"}</dd>
                  </div>
                  <div className="flex justify-between gap-3">
                    <dt className="text-muted-foreground">Obligatorio por participante</dt>
                    <dd className="text-right">{excursion.equipamientoObligatorio.join(", ")}</dd>
                  </div>
                </dl>
              </div>

              <div className="flex flex-col gap-1 rounded-xl bg-muted px-4 py-3 text-sm">
                <p className="text-muted-foreground">
                  Importe total = ({usd(paquete.precioTotal)} × {cantidad} {cantidad === 1 ? "participante" : "participantes"})
                  {costoEquipo > 0 && ` + ${usd(costoEquipo)} de equipamiento de alquiler`}
                </p>
                <p className="font-display text-2xl font-semibold">{usd(importeTotal)}</p>
              </div>
            </>
          )}

          {paso === 4 && (
            <>
              <h2 className="font-display text-xl font-semibold">Pago</h2>
              <div role="radiogroup" aria-label="Modalidad de pago" className="grid gap-3 sm:grid-cols-2">
                {(
                  [
                    ["seña", "Seña 50%", importeTotal * 0.5, "El saldo se abona dentro de las 24 hs."],
                    ["total", "Importe total", importeTotal, "La reserva queda con pago completo."],
                  ] as const
                ).map(([k, l, m, d]) => (
                  <button
                    key={k}
                    role="radio"
                    aria-checked={modalidad === k}
                    onClick={() => setModalidad(k)}
                    className={cn("flex flex-col gap-1 rounded-xl border p-4 text-left", modalidad === k && "border-primary ring-1 ring-primary")}
                  >
                    <span className="text-sm text-muted-foreground">{l}</span>
                    <span className="font-display text-2xl font-semibold">{formatMonto(m, moneda)}</span>
                    <span className="text-xs text-muted-foreground">{d}</span>
                  </button>
                ))}
              </div>
              <PasarelaPagoCampos
                id="reserva"
                montoUsd={aPagar}
                moneda={moneda}
                onMoneda={setMoneda}
                medio={medio}
                onMedio={(m) => {
                  setMedio(m)
                  setError("")
                }}
                simularRechazo={simularRechazo}
                onSimularRechazo={setSimularRechazo}
              />
            </>
          )}

          {error && (
            <p role="alert" className="flex items-start gap-2 text-sm text-destructive">
              <AlertTriangle className="mt-0.5 size-4 shrink-0" aria-hidden /> {error}
            </p>
          )}

          <div className="flex flex-wrap justify-between gap-2 border-t pt-5">
            <Button variant="ghost" onClick={() => router.push(`/excursiones/${excursion.id}`)}>
              Cancelar
            </Button>
            <div className="flex gap-2">
              {paso > 1 && (
                <Button
                  variant="outline"
                  disabled={procesando}
                  onClick={() => {
                    setError("")
                    setPaso(paso - 1)
                  }}
                >
                  Atrás
                </Button>
              )}
              {paso === 0 && <Button onClick={confirmarCantidad}>Retener cupos</Button>}
              {paso === 1 && (
                <Button disabled={expirado} onClick={validarParticipantes}>
                  Validar participantes
                </Button>
              )}
              {paso === 2 && (
                <Button disabled={expirado || quiereAlquilar === null} onClick={confirmarEquipamiento}>
                  {quiereAlquilar === null ? "Elegí una opción" : quiereAlquilar && alquilados.length > 0 ? "Confirmar equipamiento" : "Continuar sin alquilar"}
                </Button>
              )}
              {paso === 3 && (
                <Button disabled={expirado} onClick={() => setPaso(4)}>
                  Confirmar y pagar
                </Button>
              )}
              {paso === 4 && (
                <Button disabled={expirado || procesando} onClick={pagar}>
                  {procesando ? "Procesando pago…" : `Pagar ${formatMonto(aPagar, moneda)}`}
                </Button>
              )}
            </div>
          </div>
        </section>
      </div>

      <aside className="flex h-fit flex-col gap-4 rounded-2xl border bg-card p-5 lg:sticky lg:top-24">
        <p className="text-xs font-semibold uppercase tracking-widest text-accent">Resumen</p>
        <div>
          <h2 className="font-display text-xl font-semibold">{excursion.nombre}</h2>
          <p className="text-sm text-muted-foreground">
            {formatFecha(salida.fechaInicio)} · {salida.horaSalida} hs · Paquete {paquete.nombre}
          </p>
        </div>
        <dl className="flex flex-col gap-2 border-t pt-4 text-sm">
          <div className="flex justify-between gap-3">
            <dt className="text-muted-foreground">
              {cantidad} × {usd(paquete.precioTotal)}
            </dt>
            <dd>{usd(paquete.precioTotal * cantidad)}</dd>
          </div>
          {alquilados.length === 0 ? (
            <div className="flex justify-between gap-3">
              <dt className="text-muted-foreground">Equipamiento de alquiler</dt>
              <dd>{usd(0)}</dd>
            </div>
          ) : (
            alquilados.map((a) => (
              <div key={a.eq.codigo} className="flex justify-between gap-3">
                <dt className="text-muted-foreground">
                  {a.cantidad} × {a.eq.nombre}
                </dt>
                <dd>{usd(a.eq.precioAlquiler * a.cantidad)}</dd>
              </div>
            ))
          )}
          <div className="flex justify-between border-t pt-3 text-base font-semibold">
            <dt>Importe total</dt>
            <dd>{usd(importeTotal)}</dd>
          </div>
        </dl>
        {excursion.requiereCertificado && (
          <p className="rounded-lg bg-warning/10 px-3 py-2 text-xs text-warning">
            Requiere certificado médico vigente por participante, hasta 24 hs antes de la salida.
          </p>
        )}
      </aside>
    </main>
  )
}
