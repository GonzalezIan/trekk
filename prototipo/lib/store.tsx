"use client"

import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from "react"
import {
  certificadosIniciales,
  clienteDemo,
  conformidadesIniciales,
  equipamientos,
  excursiones,
  HOY,
  paquetes,
  reservasIniciales,
  salidas,
  type Certificado,
  type Cliente,
  type Conformidad,
  type Pago,
  type Participante,
  type Reserva,
  type SalidaProgramada,
} from "./data"
import {
  cupoEfectivo,
  datosExcursion,
  empleadosDisponibles,
  esActiva,
  excursionTieneReservasActivas,
  nuevoId,
  precioPaquete,
  refugiosAdmisibles,
  validarExcursion,
  validarPaquete,
  validarSalida,
  type FormExcursion,
  type FormPaquete,
  type FormSalida,
  type Resultado,
} from "./oferta"

export type Rol = "invitado" | "cliente" | "admin"

// Excursiones, salidas y paquetes viven en los arrays de data.ts. El administrativo los modifica en el lugar
// (CU 1, 2, 3, 5, 6, 8, 10, 11) y el store incrementa `version` para que toda la interfaz se vuelva a dibujar.
export function getPaquete(id: string) {
  return paquetes.find((p) => p.id === id)
}
export function getSalida(id: string) {
  return salidas.find((s) => s.id === id)
}
export function getExcursion(id: string) {
  return excursiones.find((e) => e.id === id)
}
export function contextoPaquete(paqueteId: string) {
  const paquete = getPaquete(paqueteId)!
  const salida = getSalida(paquete.salidaId)!
  const excursion = getExcursion(salida.excursionId)!
  return { paquete, salida, excursion }
}

export function edad(fechaNacimiento: string, ref: Date = HOY) {
  const n = new Date(fechaNacimiento)
  let e = ref.getFullYear() - n.getFullYear()
  const m = ref.getMonth() - n.getMonth()
  if (m < 0 || (m === 0 && ref.getDate() < n.getDate())) e--
  return e
}

export function horasHasta(fecha: string, hora = "00:00") {
  return (new Date(`${fecha}T${hora}:00`).getTime() - HOY.getTime()) / 36e5
}

export function formatFecha(f: string) {
  return new Date(f.length === 10 ? `${f}T12:00:00` : f).toLocaleDateString("es-AR", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  })
}

export function usd(n: number) {
  return `USD ${n.toLocaleString("es-AR")}`
}

// Copia de las salidas precargadas: el stock del catálogo ya descuenta su equipamiento incluido
const salidasOriginales = new Map(salidas.map((s) => [s.id, { ...s, equipamientoIncluido: [...s.equipamientoIncluido] }]))
const consumoIncluido = (s: SalidaProgramada | undefined, nombre: string) =>
  s && s.estado === "Programada" && s.equipamientoIncluido.includes(nombre) ? s.cupoTotal : 0

function reemplazar<T>(destino: T[], origen: T[] | undefined) {
  if (Array.isArray(origen)) destino.splice(0, destino.length, ...origen)
}

interface NuevaReserva {
  paqueteId: string
  participantes: Omit<Participante, "requisitoMedico">[]
  equipamiento: { codigo: string; cantidad: number }[]
  importeTotal: number
  pago: Omit<Pago, "id" | "fecha">
}

interface Store {
  /** false hasta leer la sesión guardada; evita mostrar "Acceso restringido" por un instante al recargar */
  hidratado: boolean
  /** Cambia cada vez que el administrativo modifica la oferta */
  version: number
  rol: Rol
  cliente: Cliente
  reservas: Reserva[]
  certificados: Certificado[]
  conformidades: Conformidad[]
  cupoOcupado: (salidaId: string) => number
  stockDisponible: (codigo: string) => number
  /** Stock para declarar como incluido en una salida; al modificarla, suma lo que ella misma ya reserva */
  stockParaSalida: (codigo: string, salidaId?: string) => number
  iniciarSesion: (rol: Exclude<Rol, "invitado">) => void
  cerrarSesion: () => void
  actualizarCliente: (c: Partial<Cliente>) => void
  crearReserva: (r: NuevaReserva) => string
  cancelarReserva: (id: string, motivo: string) => void
  registrarPago: (id: string, pago: Omit<Pago, "id" | "fecha">) => void
  cargarCertificado: (reservaId: string, dni: string, archivo: string) => void
  verificarCertificado: (id: string, resultado: { fechaEmision?: string; motivoInvalidez?: string }) => "Válido" | "Inválido"
  registrarConformidad: (c: Omit<Conformidad, "fecha">) => void
  // Gestión de la oferta (administrativo)
  registrarExcursion: (f: FormExcursion) => Resultado
  puedeModificarExcursion: (id: string) => Resultado
  modificarExcursion: (id: string, f: FormExcursion) => Resultado
  bajaExcursion: (id: string) => Resultado
  registrarSalida: (excursionId: string, f: FormSalida) => Resultado
  modificarSalida: (id: string, f: FormSalida) => Resultado
  cancelarSalida: (id: string, motivo: string) => Resultado
  registrarPaquete: (salidaId: string, f: FormPaquete) => Resultado
  cancelarPaquete: (id: string, motivo: string) => Resultado
}

const StoreContext = createContext<Store | null>(null)

const CLAVE_SESION = "trekk-sesion-v2"

function recalcularEstado(r: Reserva): Reserva {
  if (r.estado === "Cancelada" || r.estado === "Realizada") return r
  const medicoOk = r.participantes.every((p) => p.requisitoMedico !== "Pendiente")
  const estado = r.situacionPago === "Pago completo" && medicoOk ? "Confirmada" : "Pendiente"
  return { ...r, estado }
}

const clientesAfectados = (rs: Reserva[]) => new Set(rs.map((r) => r.clienteDni)).size
const textoNotificados = (n: number) => (n === 1 ? "Se notificó a 1 cliente" : `Se notificó a ${n} clientes`)

export function StoreProvider({ children }: { children: ReactNode }) {
  const [rol, setRol] = useState<Rol>("invitado")
  const [cliente, setCliente] = useState<Cliente>(clienteDemo)
  const [reservas, setReservas] = useState<Reserva[]>(reservasIniciales)
  const [certificados, setCertificados] = useState<Certificado[]>(certificadosIniciales)
  const [conformidades, setConformidades] = useState<Conformidad[]>(conformidadesIniciales)
  const [hidratado, setHidratado] = useState(false)
  const [version, setVersion] = useState(0)
  const tocar = () => setVersion((v) => v + 1)

  useEffect(() => {
    try {
      const guardado = sessionStorage.getItem(CLAVE_SESION)
      if (guardado) {
        const d = JSON.parse(guardado)
        setRol(d.rol)
        setCliente(d.cliente)
        setReservas(d.reservas)
        setCertificados(d.certificados)
        setConformidades(d.conformidades)
        reemplazar(excursiones, d.oferta?.excursiones)
        reemplazar(salidas, d.oferta?.salidas)
        reemplazar(paquetes, d.oferta?.paquetes)
        setVersion((v) => v + 1)
      }
    } catch {}
    setHidratado(true)
  }, [])

  useEffect(() => {
    if (!hidratado) return
    try {
      sessionStorage.setItem(
        CLAVE_SESION,
        JSON.stringify({ rol, cliente, reservas, certificados, conformidades, oferta: { excursiones, salidas, paquetes } }),
      )
    } catch {}
  }, [hidratado, rol, cliente, reservas, certificados, conformidades, version])

  const cupoOcupado = useCallback(
    (salidaId: string) => {
      const base = salidasOriginales.get(salidaId)?.cupoOcupado ?? 0
      const nuevas = reservas
        .filter((r) => !reservasIniciales.some((ri) => ri.id === r.id) && r.estado !== "Cancelada")
        .filter((r) => getPaquete(r.paqueteId)?.salidaId === salidaId)
        .reduce((acc, r) => acc + r.participantes.length, 0)
      const liberadas = reservas
        .filter((r) => reservasIniciales.some((ri) => ri.id === r.id) && r.estado === "Cancelada")
        .filter((r) => getPaquete(r.paqueteId)?.salidaId === salidaId)
        .reduce((acc, r) => acc + r.participantes.length, 0)
      return Math.max(0, base + nuevas - liberadas)
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [reservas, version],
  )

  // El stock del catálogo ya descuenta lo precargado; acá se ajusta por las reservas nuevas (alquiler) y por el
  // equipamiento incluido que reservan las salidas registradas, modificadas o canceladas (RN-08 de CU 8, CU 10 y CU 11).
  const stockDisponible = useCallback(
    (codigo: string) => {
      const eq = equipamientos.find((e) => e.codigo === codigo)
      if (!eq) return 0
      const alquilado = reservas
        .filter((r) => !reservasIniciales.some((ri) => ri.id === r.id) && r.estado !== "Cancelada")
        .flatMap((r) => r.equipamiento)
        .filter((e) => e.codigo === codigo)
        .reduce((acc, e) => acc + e.cantidad, 0)
      const incluido = salidas.reduce(
        (acc, s) => acc + consumoIncluido(s, eq.nombre) - consumoIncluido(salidasOriginales.get(s.id), eq.nombre),
        0,
      )
      return Math.max(0, eq.stock - alquilado - incluido)
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [reservas, version],
  )

  const stockParaSalida = useCallback(
    (codigo: string, salidaId?: string) => {
      const eq = equipamientos.find((e) => e.codigo === codigo)
      const propia = salidaId && eq ? consumoIncluido(getSalida(salidaId), eq.nombre) : 0
      return stockDisponible(codigo) + propia
    },
    [stockDisponible],
  )

  const value = useMemo<Store>(() => {
    // Cancela en cascada las reservas activas de los paquetes indicados y devuelve las afectadas
    const cancelarReservasDe = (idsPaquetes: string[], motivo: string) => {
      const afectadas = reservas.filter((r) => idsPaquetes.includes(r.paqueteId) && esActiva(r))
      if (afectadas.length)
        setReservas((prev) =>
          prev.map((r) => (idsPaquetes.includes(r.paqueteId) && esActiva(r) ? { ...r, estado: "Cancelada", motivoCancelacion: motivo } : r)),
        )
      return afectadas
    }

    const validarPersonal = (f: FormSalida, periodo: { fechaInicio: string; fechaFin: string }, excluir?: string): Resultado => {
      const guias = empleadosDisponibles("Guía", periodo, salidas, excluir).map((e) => `${e.nombre} ${e.apellido}`)
      if (!guias.includes(f.guia)) return { ok: false, error: "El guía seleccionado no está disponible en el período ingresado.", campos: ["guia"] }
      const asist = empleadosDisponibles("Asistente", periodo, salidas, excluir).map((e) => `${e.nombre} ${e.apellido}`)
      const noDisp = f.asistentes.filter((a) => !asist.includes(a))
      if (noDisp.length) return { ok: false, error: `Asistentes no disponibles en el período: ${noDisp.join(", ")}.`, campos: ["asistentes"] }
      return { ok: true, mensaje: "" }
    }

    const cupoConStock = (f: FormSalida, salidaId?: string): Resultado<number> => {
      for (const nombre of f.equipamientoIncluido) {
        const eq = equipamientos.find((e) => e.nombre === nombre)
        if (eq && stockParaSalida(eq.codigo, salidaId) <= 0)
          return { ok: false, error: `${nombre} no tiene stock disponible para declararlo como incluido.`, campos: ["equipamientoIncluido"] }
      }
      const { cupo, limitante } = cupoEfectivo(Number(f.cupoMaximo), f.equipamientoIncluido, (c) => stockParaSalida(c, salidaId))
      return {
        ok: true,
        dato: cupo,
        mensaje: limitante ? ` El cupo máximo quedó en ${cupo} por el stock disponible de ${limitante}.` : ` Cupo máximo: ${cupo}.`,
      }
    }

    return {
      hidratado,
      version,
      rol,
      cliente,
      reservas,
      certificados,
      conformidades,
      cupoOcupado,
      stockDisponible,
      stockParaSalida,
      iniciarSesion: (r) => setRol(r),
      cerrarSesion: () => setRol("invitado"),
      actualizarCliente: (c) => setCliente((prev) => ({ ...prev, ...c })),
      crearReserva: (nueva) => {
        const { excursion } = contextoPaquete(nueva.paqueteId)
        const id = `RV-${String(Math.floor(Math.random() * 9000) + 1000)}`
        const pagoCompleto = nueva.pago.monto >= nueva.importeTotal
        const reserva = recalcularEstado({
          id,
          clienteDni: cliente.dni,
          paqueteId: nueva.paqueteId,
          participantes: nueva.participantes.map((p) => ({
            ...p,
            requisitoMedico: excursion.requiereCertificado ? "Pendiente" : "No requerido",
          })),
          equipamiento: nueva.equipamiento,
          importeTotal: nueva.importeTotal,
          pagos: [{ ...nueva.pago, id: `PG-${Date.now()}`, fecha: HOY.toISOString().slice(0, 16) }],
          estado: "Pendiente",
          situacionPago: pagoCompleto ? "Pago completo" : "Pendiente de pago total",
          fechaRegistro: HOY.toISOString().slice(0, 16),
        })
        setReservas((prev) => [reserva, ...prev])
        return id
      },
      cancelarReserva: (id, motivo) =>
        setReservas((prev) => prev.map((r) => (r.id === id ? { ...r, estado: "Cancelada", motivoCancelacion: motivo } : r))),
      registrarPago: (id, pago) =>
        setReservas((prev) =>
          prev.map((r) =>
            r.id === id
              ? recalcularEstado({
                  ...r,
                  pagos: [...r.pagos, { ...pago, id: `PG-${Date.now()}`, fecha: HOY.toISOString().slice(0, 16) }],
                  situacionPago: "Pago completo",
                })
              : r,
          ),
        ),
      cargarCertificado: (reservaId, dni, archivo) =>
        setCertificados((prev) => [
          {
            id: `CM-${Date.now().toString().slice(-4)}`,
            reservaId,
            participanteDni: dni,
            archivo,
            fechaCarga: HOY.toISOString().slice(0, 16),
            estado: "Pendiente de verificación",
          },
          ...prev.filter((c) => !(c.reservaId === reservaId && c.participanteDni === dni && c.estado === "Pendiente de verificación")),
        ]),
      verificarCertificado: (id, { fechaEmision, motivoInvalidez }) => {
        const cert = certificados.find((c) => c.id === id)!
        const reserva = reservas.find((r) => r.id === cert.reservaId)!
        const { salida } = contextoPaquete(reserva.paqueteId)
        let estado: "Válido" | "Inválido" = "Inválido"
        let motivo = motivoInvalidez
        if (fechaEmision) {
          const vigencia = new Date(`${fechaEmision}T12:00:00`)
          vigencia.setDate(vigencia.getDate() + 30)
          if (vigencia >= new Date(`${salida.fechaFin}T12:00:00`)) estado = "Válido"
          else motivo = "La vigencia del certificado no cubre el período completo de la salida"
        }
        setCertificados((prev) =>
          prev.map((c) => (c.id === id ? { ...c, estado, fechaEmision, motivoInvalidez: estado === "Inválido" ? motivo : undefined } : c)),
        )
        if (estado === "Válido") {
          setReservas((prev) =>
            prev.map((r) =>
              r.id === cert.reservaId
                ? recalcularEstado({
                    ...r,
                    participantes: r.participantes.map((p) =>
                      p.dni === cert.participanteDni ? { ...p, requisitoMedico: "Cumplido" } : p,
                    ),
                  })
                : r,
            ),
          )
        }
        return estado
      },
      registrarConformidad: (c) =>
        setConformidades((prev) => [...prev, { ...c, fecha: HOY.toISOString().slice(0, 10) }]),

      // CU 1 – Alta de excursión
      registrarExcursion: (f) => {
        const v = validarExcursion(f, excursiones)
        if (!v.ok) return v
        const d = datosExcursion(f)
        excursiones.push({ id: nuevoId("EX"), ...d, estado: "Activa", imagen: "/images/hero.png" })
        tocar()
        return { ok: true, mensaje: "La excursión fue registrada correctamente." }
      },

      // CU 3 – paso 2.1: se verifica al seleccionar la opción "modificar"
      puedeModificarExcursion: (id) => {
        const e = getExcursion(id)
        if (!e || e.estado !== "Activa") return { ok: false, error: "Solo pueden modificarse excursiones en estado “Activa”." }
        if (excursionTieneReservasActivas(id, salidas, paquetes, reservas))
          return { ok: false, error: "La excursión seleccionada no puede ser modificada ya que está asociada a paquetes con reservas activas." }
        return { ok: true, mensaje: "" }
      },

      // CU 3 – Modificar excursión
      modificarExcursion: (id, f) => {
        const e = getExcursion(id)
        if (!e) return { ok: false, error: "No se encontró la excursión." }
        const v = validarExcursion(f, excursiones, id)
        if (!v.ok) return v
        const d = datosExcursion(f)
        Object.assign(e, d)
        tocar()
        return { ok: true, mensaje: "Los datos de la excursión fueron actualizados correctamente." }
      },

      // CU 2 – Baja de excursión
      bajaExcursion: (id) => {
        const e = getExcursion(id)
        if (!e || e.estado !== "Activa") return { ok: false, error: "Solo pueden darse de baja excursiones en estado “Activa”." }
        if (excursionTieneReservasActivas(id, salidas, paquetes, reservas))
          return { ok: false, error: "La excursión no puede darse de baja porque tiene paquetes con reservas activas." }
        let nSalidas = 0
        for (const s of salidas.filter((x) => x.excursionId === id && x.estado === "Programada")) {
          s.estado = "Cancelada"
          s.motivoCancelacion = "Baja de la excursión"
          nSalidas++
          for (const p of paquetes.filter((x) => x.salidaId === s.id && x.estado === "Disponible")) {
            p.estado = "Cancelado"
            p.motivoCancelacion = "Baja de la excursión"
          }
        }
        e.estado = "Inactiva"
        tocar()
        return {
          ok: true,
          mensaje: `La excursión quedó en estado “Inactiva”.${nSalidas ? ` Se cancelaron ${nSalidas} salida(s) programada(s) y sus paquetes.` : ""}`,
        }
      },

      // CU 8 – Registrar Salida Programada
      registrarSalida: (excursionId, f) => {
        const e = getExcursion(excursionId)
        if (!e || e.estado !== "Activa") return { ok: false, error: "Solo pueden registrarse salidas sobre excursiones en estado “Activa”." }
        const v = validarSalida(f, e)
        if (!v.ok) return v
        const p = validarPersonal(f, f)
        if (!p.ok) return p
        const c = cupoConStock(f)
        if (!c.ok) return c
        salidas.push({
          id: nuevoId("S"),
          excursionId,
          fechaInicio: f.fechaInicio,
          fechaFin: f.fechaFin,
          horaSalida: f.horaSalida,
          equipamientoIncluido: [...f.equipamientoIncluido],
          precioBase: Number(f.precioBase),
          cupoTotal: c.dato!,
          cupoOcupado: 0,
          estado: "Programada",
          guia: f.guia,
          asistentes: [...f.asistentes],
        })
        tocar()
        return { ok: true, mensaje: `La Salida Programada fue registrada correctamente.${c.mensaje}` }
      },

      // CU 10 – Modificar Salida Programada
      modificarSalida: (id, f) => {
        const s = getSalida(id)
        if (!s || s.estado !== "Programada") return { ok: false, error: "Solo puede modificarse una Salida Programada en estado “Programada”." }
        const e = getExcursion(s.excursionId)!
        const v = validarSalida(f, e)
        if (!v.ok) return v
        const p = validarPersonal(f, f, id)
        if (!p.ok) return p
        const cambiaPeriodo = f.fechaInicio !== s.fechaInicio || f.fechaFin !== s.fechaFin
        // Paso 5: los convenios de los refugios de sus paquetes deben cubrir el nuevo período
        if (cambiaPeriodo) {
          const nueva = { ...s, fechaInicio: f.fechaInicio, fechaFin: f.fechaFin }
          for (const pq of paquetes.filter((x) => x.salidaId === id && x.estado === "Disponible" && x.refugioId)) {
            const ok = refugiosAdmisibles(nueva, salidas.filter((x) => x.id !== id), paquetes).some((r) => r.id === pq.refugioId)
            if (!ok)
              return {
                ok: false,
                error: `El convenio del refugio del paquete “${pq.nombre}” no cubre el nuevo período. No se aplicó la modificación.`,
                campos: ["fechaInicio", "fechaFin"],
              }
          }
        }
        const c = cupoConStock(f, id)
        if (!c.ok) return c
        // Paso 8 (RN-03): el cupo no puede quedar por debajo de los participantes ya reservados
        const ocupado = cupoOcupado(id)
        if (c.dato! < ocupado)
          return {
            ok: false,
            error: `El cupo máximo no puede reducirse por debajo de la cantidad ya reservada (${ocupado} participantes).`,
            campos: ["cupoMaximo"],
          }
        const cambiaPersonal = f.guia !== s.guia || f.asistentes.join() !== s.asistentes.join()
        const cambiaEquipo = [...f.equipamientoIncluido].sort().join() !== [...s.equipamientoIncluido].sort().join()
        Object.assign(s, {
          fechaInicio: f.fechaInicio,
          fechaFin: f.fechaFin,
          horaSalida: f.horaSalida,
          precioBase: Number(f.precioBase),
          cupoTotal: c.dato!,
          guia: f.guia,
          asistentes: [...f.asistentes],
          equipamientoIncluido: [...f.equipamientoIncluido],
        })
        // Paso 10 (RN-05): se recalcula el precio de todos sus paquetes
        for (const pq of paquetes.filter((x) => x.salidaId === id)) pq.precioTotal = precioPaquete(s, pq.servicios, pq.refugioId)
        tocar()
        // *a (RN-06): notificación a los clientes con reservas activas
        const idsPaquetes = paquetes.filter((x) => x.salidaId === id).map((x) => x.id)
        const activas = reservas.filter((r) => idsPaquetes.includes(r.paqueteId) && esActiva(r))
        const aviso = (cambiaPeriodo || cambiaPersonal || cambiaEquipo) && activas.length ? ` ${textoNotificados(clientesAfectados(activas))} con reservas activas.` : ""
        return { ok: true, mensaje: `La modificación fue registrada correctamente.${c.mensaje}${aviso}` }
      },

      // CU 11 – Cancelar Salida Programada
      cancelarSalida: (id, motivo) => {
        const s = getSalida(id)
        if (!s || s.estado !== "Programada") return { ok: false, error: "Solo puede cancelarse una Salida Programada en estado “Programada”." }
        if (!motivo.trim()) return { ok: false, error: "El motivo de la cancelación es obligatorio.", campos: ["motivo"] }
        s.estado = "Cancelada"
        s.motivoCancelacion = motivo.trim()
        const pqs = paquetes.filter((x) => x.salidaId === id)
        for (const pq of pqs) {
          if (pq.estado === "Disponible") {
            pq.estado = "Cancelado"
            pq.motivoCancelacion = `Cancelación de la salida: ${motivo.trim()}`
          }
        }
        const afectadas = cancelarReservasDe(
          pqs.map((x) => x.id),
          `Cancelación de la Salida Programada: ${motivo.trim()}`,
        )
        tocar()
        return {
          ok: true,
          mensaje: `La Salida Programada fue cancelada correctamente.${
            afectadas.length ? ` ${textoNotificados(clientesAfectados(afectadas))}, indicando el motivo y que deben gestionar el reembolso del 100% ante la empresa.` : ""
          }`,
        }
      },

      // CU 5 – Registrar paquete
      registrarPaquete: (salidaId, f) => {
        const s = getSalida(salidaId)
        if (!s || s.estado !== "Programada") return { ok: false, error: "Solo pueden registrarse paquetes sobre salidas en estado “Programada”." }
        const v = validarPaquete(f, salidaId, paquetes)
        if (!v.ok) return v
        const refugioId = f.incluyeAlojamiento ? f.refugioId : undefined
        if (refugioId && !refugiosAdmisibles(s, salidas, paquetes).some((r) => r.id === refugioId))
          return { ok: false, error: "El refugio seleccionado no tiene un convenio admisible para el período.", campos: ["refugioId"] }
        const precio = precioPaquete(s, f.servicios, refugioId)
        paquetes.push({
          id: nuevoId("P"),
          salidaId,
          nombre: f.nombre.trim(),
          descripcion: f.descripcion.trim(),
          servicios: [...f.servicios],
          refugioId,
          precioTotal: precio,
          estado: "Disponible",
        })
        tocar()
        return { ok: true, mensaje: `El paquete fue registrado correctamente. Precio calculado: ${usd(precio)} por participante.` }
      },

      // CU 6 – Cancelar paquete
      cancelarPaquete: (id, motivo) => {
        const pq = getPaquete(id)
        if (!pq || pq.estado !== "Disponible") return { ok: false, error: "Solo puede cancelarse un paquete en estado “Disponible”." }
        if (!motivo.trim()) return { ok: false, error: "El motivo de la cancelación es obligatorio.", campos: ["motivo"] }
        pq.estado = "Cancelado"
        pq.motivoCancelacion = motivo.trim()
        const afectadas = cancelarReservasDe([id], `Cancelación del paquete: ${motivo.trim()}`)
        tocar()
        return {
          ok: true,
          mensaje: `El paquete fue cancelado correctamente.${
            afectadas.length ? ` ${textoNotificados(clientesAfectados(afectadas))} con reservas afectadas.` : ""
          }`,
        }
      },
    }
  }, [hidratado, version, rol, cliente, reservas, certificados, conformidades, cupoOcupado, stockDisponible, stockParaSalida])

  return <StoreContext.Provider value={value}>{children}</StoreContext.Provider>
}

export function useStore() {
  const ctx = useContext(StoreContext)
  if (!ctx) throw new Error("useStore debe usarse dentro de StoreProvider")
  return ctx
}
