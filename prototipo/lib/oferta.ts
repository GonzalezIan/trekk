// Reglas de negocio de la gestión de la oferta (CU 1, 2, 3, 5, 6, 8, 10, 11).
// Funciones puras: reciben los datos y devuelven el resultado o el error a informar.

import {
  empleados,
  equipamientos,
  HOY,
  refugios,
  servicios,
  type Dificultad,
  type Excursion,
  type Paquete,
  type Reserva,
  type SalidaProgramada,
} from "./data"

export type Resultado<T = undefined> = { ok: true; mensaje: string; dato?: T } | { ok: false; error: string; campos?: string[] }

const fecha = (f: string) => new Date(`${f}T12:00:00`)
export const diasPeriodo = (inicio: string, fin: string) => Math.round((fecha(fin).getTime() - fecha(inicio).getTime()) / 864e5) + 1
export const noches = (s: Pick<SalidaProgramada, "fechaInicio" | "fechaFin">) => Math.max(0, diasPeriodo(s.fechaInicio, s.fechaFin) - 1)
const seSolapan = (a: { fechaInicio: string; fechaFin: string }, b: { fechaInicio: string; fechaFin: string }) =>
  a.fechaInicio <= b.fechaFin && b.fechaInicio <= a.fechaFin

export const esActiva = (r: Reserva) => r.estado === "Pendiente" || r.estado === "Confirmada"

/* ───────────── Excursiones (CU 1, 2, 3) ───────────── */

export interface FormExcursion {
  nombre: string
  descripcion: string
  recorrido: string
  etapas: string
  dificultad: Dificultad | ""
  duracionDias: string
  experienciaRequerida: string
  aceptaMenores: "si" | "no" | ""
  equipamientoObligatorio: string
  requiereCertificado: "si" | "no" | ""
}

export const formExcursionVacio: FormExcursion = {
  nombre: "",
  descripcion: "",
  recorrido: "",
  etapas: "",
  dificultad: "",
  duracionDias: "",
  experienciaRequerida: "",
  aceptaMenores: "",
  equipamientoObligatorio: "",
  requiereCertificado: "",
}

export function formDesdeExcursion(e: Excursion): FormExcursion {
  return {
    nombre: e.nombre,
    descripcion: e.descripcion,
    recorrido: e.recorrido,
    etapas: e.etapas,
    dificultad: e.dificultad,
    duracionDias: String(e.duracionDias),
    experienciaRequerida: e.experienciaRequerida,
    aceptaMenores: e.aceptaMenores ? "si" : "no",
    equipamientoObligatorio: e.equipamientoObligatorio.join("\n"),
    requiereCertificado: e.requiereCertificado ? "si" : "no",
  }
}

/** CU 1 paso 4 / CU 3 paso 5: datos obligatorios (RN-01), duración ≥ 1 (RN-02) y nombre único (RN-03). */
export function validarExcursion(f: FormExcursion, todas: Excursion[], idPropio?: string): Resultado {
  // RN-01 (CU 1): todo es obligatorio salvo el equipamiento obligatorio
  const obligatorios = (Object.keys(f) as (keyof FormExcursion)[]).filter((k) => k !== "equipamientoObligatorio")
  const faltantes = obligatorios.filter((k) => !String(f[k]).trim())
  if (faltantes.length) return { ok: false, error: "Deben completarse todos los campos obligatorios.", campos: faltantes }
  const dur = Number(f.duracionDias)
  if (!Number.isInteger(dur) || dur < 1)
    return { ok: false, error: "La duración de la excursión debe ser de al menos un día.", campos: ["duracionDias"] }
  const nombre = f.nombre.trim().toLowerCase()
  if (todas.some((e) => e.id !== idPropio && e.nombre.trim().toLowerCase() === nombre))
    return { ok: false, error: "Ya existe una excursión registrada con ese nombre.", campos: ["nombre"] }
  return { ok: true, mensaje: "" }
}

export function datosExcursion(f: FormExcursion) {
  const aceptaMenores = f.aceptaMenores === "si"
  return {
    nombre: f.nombre.trim(),
    descripcion: f.descripcion.trim(),
    recorrido: f.recorrido.trim(),
    etapas: f.etapas.trim(),
    dificultad: f.dificultad as Dificultad,
    duracionDias: Number(f.duracionDias),
    experienciaRequerida: f.experienciaRequerida.trim(),
    aceptaMenores,
    requiereCertificado: f.requiereCertificado === "si",
    equipamientoObligatorio: f.equipamientoObligatorio
      .split(/\n|,/)
      .map((x) => x.trim())
      .filter(Boolean),
  }
}

/** CU 2 y CU 3 (RN-01): ningún paquete de sus salidas "Programada" puede tener reservas activas. */
export function excursionTieneReservasActivas(excursionId: string, salidas: SalidaProgramada[], paquetes: Paquete[], reservas: Reserva[]) {
  const idsSalidas = salidas.filter((s) => s.excursionId === excursionId && s.estado === "Programada").map((s) => s.id)
  const idsPaquetes = paquetes.filter((p) => idsSalidas.includes(p.salidaId)).map((p) => p.id)
  return reservas.some((r) => idsPaquetes.includes(r.paqueteId) && esActiva(r))
}

/* ───────────── Salidas Programadas (CU 8, 10, 11) ───────────── */

export interface FormSalida {
  fechaInicio: string
  fechaFin: string
  horaSalida: string
  cupoMaximo: string
  precioBase: string
  guia: string
  asistentes: string[]
  equipamientoIncluido: string[]
}

export const formSalidaVacio: FormSalida = {
  fechaInicio: "",
  fechaFin: "",
  horaSalida: "",
  cupoMaximo: "",
  precioBase: "",
  guia: "",
  asistentes: [],
  equipamientoIncluido: [],
}

export function formDesdeSalida(s: SalidaProgramada): FormSalida {
  return {
    fechaInicio: s.fechaInicio,
    fechaFin: s.fechaFin,
    horaSalida: s.horaSalida,
    cupoMaximo: String(s.cupoTotal),
    precioBase: String(s.precioBase),
    guia: s.guia,
    asistentes: [...s.asistentes],
    equipamientoIncluido: [...s.equipamientoIncluido],
  }
}

export const nombreEmpleado = (e: { nombre: string; apellido: string }) => `${e.nombre} ${e.apellido}`

/** CU 23 invocado desde CU 8 / CU 10: empleados activos de la categoría, sin otra salida programada en el período (RN-05). */
export function empleadosDisponibles(
  categoria: "Guía" | "Asistente",
  periodo: { fechaInicio: string; fechaFin: string },
  salidas: SalidaProgramada[],
  excluirSalidaId?: string,
) {
  if (!periodo.fechaInicio || !periodo.fechaFin) return []
  const ocupados = new Set(
    salidas
      .filter((s) => s.id !== excluirSalidaId && s.estado === "Programada" && seSolapan(s, periodo))
      .flatMap((s) => [s.guia, ...s.asistentes]),
  )
  return empleados.filter((e) => e.categoria === categoria && e.estado === "Activo" && !ocupados.has(nombreEmpleado(e)))
}

/** CU 8 paso 4 / CU 10 paso 4: período futuro y consistente con la duración (RN-01), cupo y precio > 0 (RN-02), guía (RN-04). */
export function validarSalida(f: FormSalida, excursion: Excursion): Resultado {
  const faltantes = (["fechaInicio", "fechaFin", "horaSalida", "cupoMaximo", "precioBase"] as const).filter((k) => !f[k].trim())
  if (faltantes.length) return { ok: false, error: "Deben completarse todos los campos obligatorios.", campos: faltantes }
  if (fecha(f.fechaInicio) <= HOY || f.fechaFin < f.fechaInicio)
    return { ok: false, error: "El período ingresado no es válido: debe ser futuro y la fecha de fin no puede ser anterior a la de inicio.", campos: ["fechaInicio", "fechaFin"] }
  if (diasPeriodo(f.fechaInicio, f.fechaFin) !== excursion.duracionDias)
    return {
      ok: false,
      error: `El período debe corresponderse con la duración de la excursión (${excursion.duracionDias} ${excursion.duracionDias === 1 ? "día" : "días"}).`,
      campos: ["fechaInicio", "fechaFin"],
    }
  if (!(Number(f.cupoMaximo) > 0) || !(Number(f.precioBase) > 0))
    return { ok: false, error: "El cupo máximo y el precio base deben ser mayores a cero.", campos: ["cupoMaximo", "precioBase"] }
  if (!f.guia) return { ok: false, error: "Debe asignarse un guía responsable.", campos: ["guia"] }
  return { ok: true, mensaje: "" }
}

/** RN-07 (CU 8) / paso 7 (CU 10): el cupo máximo es el menor entre el ingresado y el stock de cada elemento incluido. */
export function cupoEfectivo(cupoIngresado: number, incluidos: string[], stockPara: (codigo: string) => number) {
  let cupo = cupoIngresado
  let limitante: string | null = null
  for (const nombre of incluidos) {
    const eq = equipamientos.find((e) => e.nombre === nombre)
    if (!eq) continue
    const stock = stockPara(eq.codigo)
    if (stock < cupo) {
      cupo = stock
      limitante = nombre
    }
  }
  return { cupo, limitante }
}

/* ───────────── Paquetes (CU 5, 6) ───────────── */

export interface FormPaquete {
  nombre: string
  descripcion: string
  servicios: string[]
  incluyeAlojamiento: boolean
  refugioId: string
}

export const formPaqueteVacio: FormPaquete = { nombre: "", descripcion: "", servicios: [], incluyeAlojamiento: false, refugioId: "" }

/** RN-04 (CU 5): precio base + servicios (+ precio por cama × noches si tiene alojamiento). */
export function precioPaquete(salida: SalidaProgramada, nombresServicios: string[], refugioId?: string) {
  const totalServicios = nombresServicios.reduce((acc, n) => acc + (servicios.find((s) => s.tipo === n)?.precio ?? 0), 0)
  const refugio = refugios.find((r) => r.id === refugioId)
  return salida.precioBase + totalServicios + (refugio ? refugio.precioCama * noches(salida) : 0)
}

/** CU 26 invocado desde CU 5: convenio que cubre el período y refugio sin otro paquete disponible en una salida solapada. */
export function refugiosAdmisibles(salida: SalidaProgramada, salidas: SalidaProgramada[], paquetes: Paquete[]) {
  return refugios.filter((r) => {
    if (!(r.convenioDesde <= salida.fechaInicio && salida.fechaFin <= r.convenioHasta)) return false
    return !paquetes.some((p) => {
      if (p.refugioId !== r.id || p.estado !== "Disponible") return false
      const otra = salidas.find((s) => s.id === p.salidaId)
      return !!otra && otra.estado === "Programada" && seSolapan(otra, salida)
    })
  })
}

/** CU 5 paso 4: datos obligatorios y nombre único entre los paquetes de la salida (RN-06). */
export function validarPaquete(f: FormPaquete, salidaId: string, paquetes: Paquete[]): Resultado {
  const faltantes = (["nombre", "descripcion"] as const).filter((k) => !f[k].trim())
  if (f.incluyeAlojamiento && !f.refugioId) faltantes.push("refugioId" as never)
  if (faltantes.length) return { ok: false, error: "Deben completarse todos los campos obligatorios.", campos: faltantes }
  const nombre = f.nombre.trim().toLowerCase()
  if (paquetes.some((p) => p.salidaId === salidaId && p.nombre.trim().toLowerCase() === nombre))
    return { ok: false, error: "Ya existe un paquete con ese nombre en esta Salida Programada. Ingresá un nombre distinto.", campos: ["nombre"] }
  return { ok: true, mensaje: "" }
}

export const nuevoId = (prefijo: string) => `${prefijo}-${Date.now().toString(36).toUpperCase()}`
