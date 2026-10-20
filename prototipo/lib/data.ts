export type Dificultad = "Baja" | "Media" | "Alta"
export type EstadoReserva = "Pendiente" | "Confirmada" | "Realizada" | "Cancelada"
export type SituacionPago = "Sin pago" | "Pendiente de pago total" | "Pago completo"
export type RequisitoMedico = "No requerido" | "Pendiente" | "Cumplido"
export type EstadoCertificado = "Pendiente de verificación" | "Válido" | "Inválido"

export const USD_ARS = 1500

export interface Excursion {
  id: string
  nombre: string
  recorrido: string
  descripcion: string
  etapas: string
  dificultad: Dificultad
  duracionDias: number
  experienciaRequerida: string
  aceptaMenores: boolean
  estado: "Activa" | "Inactiva"
  requiereCertificado: boolean
  imagen: string
  /** RN-04 (CU 1): descripción textual, no se vincula al catálogo de equipamientos */
  equipamientoObligatorio: string[]
}

export interface SalidaProgramada {
  id: string
  excursionId: string
  fechaInicio: string
  fechaFin: string
  horaSalida: string
  puntoEncuentro?: string
  equipamientoIncluido: string[]
  precioBase: number
  cupoTotal: number
  cupoOcupado: number
  estado: "Programada" | "Cancelada" | "Finalizada"
  guia: string
  asistentes: string[]
  motivoCancelacion?: string
}

export interface Paquete {
  id: string
  salidaId: string
  nombre: string
  descripcion: string
  servicios: string[]
  refugioId?: string
  precioTotal: number
  estado: "Disponible" | "Cancelado"
  motivoCancelacion?: string
}

export interface Refugio {
  id: string
  nombre: string
  descripcion: string
  ubicacion: string
  camasTotales: number
  camasDisponibles: number
  precioCama: number
  convenioDesde: string
  convenioHasta: string
}

export interface Equipamiento {
  codigo: string
  nombre: string
  descripcion: string
  stock: number
  precioAlquiler: number
}

export interface Empleado {
  dni: string
  nombre: string
  apellido: string
  categoria: "Guía" | "Asistente"
  estado: "Activo" | "Inactivo"
}

export interface Servicio {
  id: string
  tipo: string
  descripcion: string
  precio: number
}

export interface Participante {
  dni: string
  nombre: string
  apellido: string
  fechaNacimiento: string
  requisitoMedico: RequisitoMedico
}

export interface Pago {
  id: string
  monto: number
  moneda: "USD" | "ARS"
  medio: string
  fecha: string
  tipo: "Seña" | "Saldo" | "Total"
}

export interface Reserva {
  id: string
  clienteDni: string
  paqueteId: string
  participantes: Participante[]
  equipamiento: { codigo: string; cantidad: number }[]
  importeTotal: number
  pagos: Pago[]
  estado: EstadoReserva
  situacionPago: SituacionPago
  fechaRegistro: string
  motivoCancelacion?: string
}

export interface Certificado {
  id: string
  reservaId: string
  participanteDni: string
  archivo: string
  fechaCarga: string
  estado: EstadoCertificado
  fechaEmision?: string
  motivoInvalidez?: string
}

export interface Conformidad {
  reservaId: string
  valoracion: number
  comentario: string
  fecha: string
}

export interface Cliente {
  dni: string
  nombre: string
  apellido: string
  fechaNacimiento: string
  email: string
  telefono: string
  usuario: string
}

export const excursiones: Excursion[] = [
  {
    id: "laguna-de-los-tres",
    nombre: "Laguna de los Tres",
    recorrido: "El Chaltén → Campamento Poincenot → Laguna de los Tres",
    descripcion:
      "El clásico de El Chaltén. Ascenso final exigente hasta la laguna glaciar al pie del Fitz Roy, con vistas inolvidables al amanecer.",
    etapas: "Ascenso por el valle del río Blanco; noche en Campamento Poincenot; ascenso final a la laguna y regreso",
    dificultad: "Alta",
    duracionDias: 2,
    experienciaRequerida: "Experiencia previa en caminatas de montaña de más de 6 horas",
    aceptaMenores: true,
    estado: "Activa",
    requiereCertificado: true,
    imagen: "/images/laguna.png",
    equipamientoObligatorio: ["Calzado de trekking con buen agarre", "Campera impermeable o rompevientos", "Protección solar y anteojos"],
  },
  {
    id: "bosque-de-lengas",
    nombre: "Sendero Bosque de Lengas",
    recorrido: "Lago del Desierto → Chorrillo del Salto → Mirador del Bosque",
    descripcion:
      "Caminata tranquila entre lengas y ñires, cascadas y pasarelas de madera. Ideal para familias y primeras experiencias.",
    etapas: "Caminata por pasarelas del bosque; visita al Chorrillo del Salto; mirador y regreso",
    dificultad: "Baja",
    duracionDias: 1,
    experienciaRequerida: "No requiere experiencia previa",
    aceptaMenores: true,
    estado: "Activa",
    requiereCertificado: false,
    imagen: "/images/bosque.png",
    equipamientoObligatorio: ["Calzado cerrado", "Abrigo liviano", "Botella de agua"],
  },
  {
    id: "travesia-glaciar",
    nombre: "Travesía Glaciar Viedma",
    recorrido: "Bahía Túnel → Glaciar Viedma → Refugio Base",
    descripcion:
      "Caminata sobre hielo con grampones, grietas azules y noche en refugio. Experiencia técnica guiada en el glaciar más grande de Argentina.",
    etapas: "Navegación a Bahía Túnel; caminata sobre hielo con grampones; dos noches en refugio",
    dificultad: "Alta",
    duracionDias: 3,
    experienciaRequerida: "Buena condición física y experiencia en trekking de varios días",
    aceptaMenores: false,
    estado: "Activa",
    requiereCertificado: true,
    imagen: "/images/glaciar.png",
    equipamientoObligatorio: ["Botas rígidas de montaña", "Ropa térmica en capas", "Guantes y gorro", "Protección ocular para nieve"],
  },
  {
    id: "valle-refugio",
    nombre: "Valle del Refugio Piedra",
    recorrido: "Río Eléctrico → Valle Piedra del Fraile → Refugio",
    descripcion:
      "Travesía de dificultad media por valles glaciares con noche en refugio de montaña y cena caliente incluida.",
    etapas: "Ascenso por el valle del río Eléctrico; noche en refugio; regreso por el mismo sendero",
    dificultad: "Media",
    duracionDias: 2,
    experienciaRequerida: "Experiencia básica en trekking",
    aceptaMenores: true,
    estado: "Activa",
    requiereCertificado: false,
    imagen: "/images/refugio.png",
    equipamientoObligatorio: ["Calzado de trekking", "Bolsa de dormir", "Linterna frontal", "Abrigo para la noche"],
  },
  {
    id: "mirador-torre",
    nombre: "Mirador Laguna Torre",
    recorrido: "El Chaltén → Mirador del Torre → Laguna Torre",
    descripcion: "Caminata al pie del Cerro Torre. Dada de baja por reformulación del recorrido.",
    etapas: "Subida al mirador; bordeo del río Fitz Roy; laguna Torre y regreso",
    dificultad: "Media",
    duracionDias: 1,
    experienciaRequerida: "Experiencia básica en trekking",
    aceptaMenores: true,
    estado: "Inactiva",
    requiereCertificado: false,
    imagen: "/images/hero.png",
    equipamientoObligatorio: ["Calzado de trekking", "Abrigo"],
  },
]

export const salidas: SalidaProgramada[] = [
  { id: "S-101", excursionId: "laguna-de-los-tres", fechaInicio: "2026-10-12", fechaFin: "2026-10-13", horaSalida: "07:00", puntoEncuentro: "Centro de visitantes El Chaltén", equipamientoIncluido: ["Carpa", "Bastones"], precioBase: 180, cupoTotal: 12, cupoOcupado: 7, estado: "Programada", guia: "Martín Quiroga", asistentes: ["Lucía Paz"] },
  { id: "S-102", excursionId: "laguna-de-los-tres", fechaInicio: "2026-11-02", fechaFin: "2026-11-03", horaSalida: "06:30", puntoEncuentro: "Centro de visitantes El Chaltén", equipamientoIncluido: ["Carpa", "Bastones"], precioBase: 195, cupoTotal: 12, cupoOcupado: 2, estado: "Programada", guia: "Martín Quiroga", asistentes: [] },
  { id: "S-103", excursionId: "laguna-de-los-tres", fechaInicio: "2026-09-27", fechaFin: "2026-09-28", horaSalida: "07:00", puntoEncuentro: "Centro de visitantes El Chaltén", equipamientoIncluido: ["Carpa"], precioBase: 180, cupoTotal: 10, cupoOcupado: 4, estado: "Programada", guia: "Martín Quiroga", asistentes: [] },
  { id: "S-201", excursionId: "bosque-de-lengas", fechaInicio: "2026-10-05", fechaFin: "2026-10-05", horaSalida: "09:00", puntoEncuentro: "Muelle Lago del Desierto", equipamientoIncluido: ["Bastones"], precioBase: 65, cupoTotal: 20, cupoOcupado: 11, estado: "Programada", guia: "Sofía Ñancucheo", asistentes: [] },
  { id: "S-202", excursionId: "bosque-de-lengas", fechaInicio: "2026-10-19", fechaFin: "2026-10-19", horaSalida: "09:00", puntoEncuentro: "Muelle Lago del Desierto", equipamientoIncluido: ["Bastones"], precioBase: 65, cupoTotal: 20, cupoOcupado: 0, estado: "Programada", guia: "Sofía Ñancucheo", asistentes: ["Tomás Rivas"] },
  { id: "S-301", excursionId: "travesia-glaciar", fechaInicio: "2026-11-14", fechaFin: "2026-11-16", horaSalida: "06:00", puntoEncuentro: "Puerto Bahía Túnel", equipamientoIncluido: ["Grampones", "Arnés", "Casco"], precioBase: 420, cupoTotal: 8, cupoOcupado: 5, estado: "Programada", guia: "Diego Ferreyra", asistentes: ["Lucía Paz"] },
  { id: "S-401", excursionId: "valle-refugio", fechaInicio: "2026-10-24", fechaFin: "2026-10-25", horaSalida: "08:00", puntoEncuentro: "Puente Río Eléctrico", equipamientoIncluido: ["Bastones"], precioBase: 150, cupoTotal: 14, cupoOcupado: 6, estado: "Programada", guia: "Sofía Ñancucheo", asistentes: [] },
  { id: "S-203", excursionId: "bosque-de-lengas", fechaInicio: "2026-11-09", fechaFin: "2026-11-09", horaSalida: "09:00", puntoEncuentro: "Muelle Lago del Desierto", equipamientoIncluido: ["Bastones"], precioBase: 65, cupoTotal: 20, cupoOcupado: 0, estado: "Cancelada", guia: "Sofía Ñancucheo", asistentes: [] },
  { id: "S-099", excursionId: "bosque-de-lengas", fechaInicio: "2026-08-30", fechaFin: "2026-08-30", horaSalida: "09:00", puntoEncuentro: "Muelle Lago del Desierto", equipamientoIncluido: ["Bastones"], precioBase: 60, cupoTotal: 20, cupoOcupado: 14, estado: "Finalizada", guia: "Sofía Ñancucheo", asistentes: [] },
  { id: "S-098", excursionId: "laguna-de-los-tres", fechaInicio: "2026-09-06", fechaFin: "2026-09-07", horaSalida: "07:00", puntoEncuentro: "Centro de visitantes El Chaltén", equipamientoIncluido: ["Carpa"], precioBase: 175, cupoTotal: 12, cupoOcupado: 10, estado: "Finalizada", guia: "Martín Quiroga", asistentes: [] },
]

export const refugios: Refugio[] = [
  { id: "R-1", nombre: "Refugio Piedra del Fraile", descripcion: "Refugio de madera con cocina y 24 camas en literas.", ubicacion: "Valle Piedra del Fraile", camasTotales: 24, camasDisponibles: 15, precioCama: 45, convenioDesde: "2026-09-01", convenioHasta: "2027-04-30" },
  { id: "R-2", nombre: "Refugio Base Viedma", descripcion: "Base de montaña junto al glaciar, calefaccionada.", ubicacion: "Bahía Túnel", camasTotales: 12, camasDisponibles: 6, precioCama: 70, convenioDesde: "2026-10-01", convenioHasta: "2027-03-31" },
]

export const servicios: Servicio[] = [
  { id: "SV-1", tipo: "Traslado", descripcion: "Traslado ida y vuelta desde El Chaltén", precio: 25 },
  { id: "SV-2", tipo: "Gastronomía", descripcion: "Pensión completa de montaña", precio: 40 },
  { id: "SV-3", tipo: "Fotografía", descripcion: "Registro fotográfico profesional", precio: 30 },
  { id: "SV-4", tipo: "Seguro", descripcion: "Seguro de accidentes personales", precio: 15 },
]

export const paquetes: Paquete[] = [
  { id: "P-1011", salidaId: "S-101", nombre: "Esencial", descripcion: "Guiado y equipamiento incluido de la salida.", servicios: [], precioTotal: 180, estado: "Disponible" },
  { id: "P-1012", salidaId: "S-101", nombre: "Completo", descripcion: "Incluye traslados, pensión completa y seguro.", servicios: ["Traslado", "Gastronomía", "Seguro"], precioTotal: 260, estado: "Disponible" },
  { id: "P-1021", salidaId: "S-102", nombre: "Esencial", descripcion: "Guiado y equipamiento incluido de la salida.", servicios: [], precioTotal: 195, estado: "Disponible" },
  { id: "P-1022", salidaId: "S-102", nombre: "Foto Trek", descripcion: "Traslados y registro fotográfico al amanecer.", servicios: ["Traslado", "Fotografía"], precioTotal: 250, estado: "Disponible" },
  { id: "P-1023", salidaId: "S-102", nombre: "Refugio Poincenot", descripcion: "Noche en campamento con carpa y cena.", servicios: ["Gastronomía"], precioTotal: 240, estado: "Cancelado", motivoCancelacion: "Cierre temporal del área de acampe" },
  { id: "P-1031", salidaId: "S-103", nombre: "Esencial", descripcion: "Guiado y equipamiento incluido de la salida.", servicios: [], precioTotal: 180, estado: "Disponible" },
  { id: "P-2011", salidaId: "S-201", nombre: "Paseo", descripcion: "Caminata guiada con vianda.", servicios: [], precioTotal: 65, estado: "Disponible" },
  { id: "P-2012", salidaId: "S-201", nombre: "Paseo + Traslado", descripcion: "Incluye traslado desde El Chaltén.", servicios: ["Traslado"], precioTotal: 90, estado: "Disponible" },
  { id: "P-2021", salidaId: "S-202", nombre: "Paseo", descripcion: "Caminata guiada con vianda.", servicios: [], precioTotal: 65, estado: "Disponible" },
  { id: "P-3011", salidaId: "S-301", nombre: "Glaciar con Refugio", descripcion: "Dos noches en Refugio Base Viedma con pensión completa.", servicios: ["Gastronomía", "Seguro"], refugioId: "R-2", precioTotal: 615, estado: "Disponible" },
  { id: "P-4011", salidaId: "S-401", nombre: "Noche en Refugio", descripcion: "Cama en refugio y cena caliente.", servicios: ["Gastronomía"], refugioId: "R-1", precioTotal: 235, estado: "Disponible" },
  { id: "P-4012", salidaId: "S-401", nombre: "Refugio + Traslado", descripcion: "Cama en refugio, cena y traslados.", servicios: ["Gastronomía", "Traslado"], refugioId: "R-1", precioTotal: 260, estado: "Disponible" },
  { id: "P-0991", salidaId: "S-099", nombre: "Paseo", descripcion: "Caminata guiada con vianda.", servicios: [], precioTotal: 60, estado: "Disponible" },
  { id: "P-0981", salidaId: "S-098", nombre: "Esencial", descripcion: "Guiado y equipamiento incluido.", servicios: [], precioTotal: 175, estado: "Disponible" },
]

export const equipamientos: Equipamiento[] = [
  { codigo: "EQ-01", nombre: "Botas de trekking", descripcion: "Caña media, impermeables. Talles 35 a 46.", stock: 18, precioAlquiler: 12 },
  { codigo: "EQ-02", nombre: "Campera impermeable", descripcion: "Membrana respirable con capucha.", stock: 22, precioAlquiler: 10 },
  { codigo: "EQ-03", nombre: "Bastones", descripcion: "Par de bastones telescópicos de aluminio.", stock: 30, precioAlquiler: 5 },
  { codigo: "EQ-04", nombre: "Bolsa de dormir", descripcion: "Térmica, confort hasta -10 °C.", stock: 14, precioAlquiler: 15 },
  { codigo: "EQ-05", nombre: "Linterna frontal", descripcion: "LED recargable, 300 lúmenes.", stock: 25, precioAlquiler: 4 },
  { codigo: "EQ-06", nombre: "Botas rígidas", descripcion: "Suela rígida compatible con grampones.", stock: 8, precioAlquiler: 20 },
  { codigo: "EQ-07", nombre: "Grampones", descripcion: "Grampones de 12 puntas para hielo.", stock: 10, precioAlquiler: 18 },
  { codigo: "EQ-08", nombre: "Arnés", descripcion: "Arnés regulable de montaña.", stock: 10, precioAlquiler: 12 },
  { codigo: "EQ-09", nombre: "Casco", descripcion: "Casco de escalada certificado.", stock: 12, precioAlquiler: 8 },
  { codigo: "EQ-11", nombre: "Carpa", descripcion: "Carpa para 2 personas, tres estaciones.", stock: 40, precioAlquiler: 25 },
  { codigo: "EQ-10", nombre: "Polainas", descripcion: "Polainas impermeables para nieve.", stock: 0, precioAlquiler: 6 },
]

export const empleados: Empleado[] = [
  { dni: "28111222", nombre: "Martín", apellido: "Quiroga", categoria: "Guía", estado: "Activo" },
  { dni: "30222333", nombre: "Sofía", apellido: "Ñancucheo", categoria: "Guía", estado: "Activo" },
  { dni: "27333444", nombre: "Diego", apellido: "Ferreyra", categoria: "Guía", estado: "Activo" },
  { dni: "38444555", nombre: "Lucía", apellido: "Paz", categoria: "Asistente", estado: "Activo" },
  { dni: "40555666", nombre: "Tomás", apellido: "Rivas", categoria: "Asistente", estado: "Activo" },
  { dni: "25666777", nombre: "Ernesto", apellido: "Lagos", categoria: "Guía", estado: "Inactivo" },
]

export const clienteDemo: Cliente = {
  dni: "35123456",
  nombre: "Carla",
  apellido: "Méndez",
  fechaNacimiento: "1991-04-18",
  email: "carla.mendez@mail.com",
  telefono: "+54 280 455-1234",
  usuario: "carlam",
}

export const clientesExtra: Cliente[] = [
  { dni: "32987654", nombre: "Julián", apellido: "Torres", fechaNacimiento: "1987-01-03", email: "jtorres@mail.com", telefono: "+54 297 411-9988", usuario: "jtorres" },
  { dni: "39456123", nombre: "Paula", apellido: "Ibarra", fechaNacimiento: "1996-07-22", email: "pibarra@mail.com", telefono: "+54 280 466-3344", usuario: "pibarra" },
]

export const reservasIniciales: Reserva[] = [
  {
    id: "RV-0041",
    clienteDni: "35123456",
    paqueteId: "P-1012",
    participantes: [
      { dni: "35123456", nombre: "Carla", apellido: "Méndez", fechaNacimiento: "1991-04-18", requisitoMedico: "Cumplido" },
      { dni: "36222111", nombre: "Nicolás", apellido: "Méndez", fechaNacimiento: "1993-02-10", requisitoMedico: "Pendiente" },
    ],
    equipamiento: [{ codigo: "EQ-01", cantidad: 1 }],
    importeTotal: 532,
    pagos: [{ id: "PG-1", monto: 266, moneda: "USD", medio: "Tarjeta de crédito", fecha: "2026-09-25T10:14", tipo: "Seña" }],
    estado: "Pendiente",
    situacionPago: "Pendiente de pago total",
    fechaRegistro: "2026-09-25T10:14",
  },
  {
    id: "RV-0037",
    clienteDni: "35123456",
    paqueteId: "P-4011",
    participantes: [{ dni: "35123456", nombre: "Carla", apellido: "Méndez", fechaNacimiento: "1991-04-18", requisitoMedico: "No requerido" }],
    equipamiento: [],
    importeTotal: 235,
    pagos: [{ id: "PG-2", monto: 352500, moneda: "ARS", medio: "Mercado Pago", fecha: "2026-09-10T18:02", tipo: "Total" }],
    estado: "Confirmada",
    situacionPago: "Pago completo",
    fechaRegistro: "2026-09-10T18:02",
  },
  {
    id: "RV-0022",
    clienteDni: "35123456",
    paqueteId: "P-0991",
    participantes: [
      { dni: "35123456", nombre: "Carla", apellido: "Méndez", fechaNacimiento: "1991-04-18", requisitoMedico: "No requerido" },
      { dni: "50111222", nombre: "Emma", apellido: "Méndez", fechaNacimiento: "2014-11-01", requisitoMedico: "No requerido" },
    ],
    equipamiento: [],
    importeTotal: 120,
    pagos: [{ id: "PG-3", monto: 120, moneda: "USD", medio: "Tarjeta de débito", fecha: "2026-08-12T09:40", tipo: "Total" }],
    estado: "Realizada",
    situacionPago: "Pago completo",
    fechaRegistro: "2026-08-12T09:40",
  },
  {
    id: "RV-0019",
    clienteDni: "32987654",
    paqueteId: "P-0981",
    participantes: [
      { dni: "32987654", nombre: "Julián", apellido: "Torres", fechaNacimiento: "1987-01-03", requisitoMedico: "Cumplido" },
      { dni: "33111000", nombre: "Ana", apellido: "Gil", fechaNacimiento: "1989-05-05", requisitoMedico: "Cumplido" },
      { dni: "34222000", nombre: "Leo", apellido: "Gil", fechaNacimiento: "1990-06-06", requisitoMedico: "Cumplido" },
    ],
    equipamiento: [],
    importeTotal: 525,
    pagos: [{ id: "PG-4", monto: 525, moneda: "USD", medio: "Tarjeta de crédito", fecha: "2026-08-20T12:00", tipo: "Total" }],
    estado: "Realizada",
    situacionPago: "Pago completo",
    fechaRegistro: "2026-08-20T12:00",
  },
  {
    id: "RV-0044",
    clienteDni: "39456123",
    paqueteId: "P-3011",
    participantes: [{ dni: "39456123", nombre: "Paula", apellido: "Ibarra", fechaNacimiento: "1996-07-22", requisitoMedico: "Pendiente" }],
    equipamiento: [],
    importeTotal: 615,
    pagos: [{ id: "PG-5", monto: 615, moneda: "USD", medio: "Tarjeta de crédito", fecha: "2026-09-24T16:30", tipo: "Total" }],
    estado: "Pendiente",
    situacionPago: "Pago completo",
    fechaRegistro: "2026-09-24T16:30",
  },
  {
    id: "RV-0032",
    clienteDni: "32987654",
    paqueteId: "P-0991",
    participantes: [{ dni: "32987654", nombre: "Julián", apellido: "Torres", fechaNacimiento: "1987-01-03", requisitoMedico: "No requerido" }],
    equipamiento: [],
    importeTotal: 60,
    pagos: [{ id: "PG-6", monto: 60, moneda: "USD", medio: "Mercado Pago", fecha: "2026-08-15T10:00", tipo: "Total" }],
    estado: "Realizada",
    situacionPago: "Pago completo",
    fechaRegistro: "2026-08-15T10:00",
  },
]

export const certificadosIniciales: Certificado[] = [
  { id: "CM-12", reservaId: "RV-0044", participanteDni: "39456123", archivo: "certificado_ibarra.pdf", fechaCarga: "2026-09-25T09:12", estado: "Pendiente de verificación" },
  { id: "CM-11", reservaId: "RV-0041", participanteDni: "36222111", archivo: "apto_nicolas.jpg", fechaCarga: "2026-09-26T08:45", estado: "Pendiente de verificación" },
]

export const conformidadesIniciales: Conformidad[] = [
  { reservaId: "RV-0019", valoracion: 5, comentario: "Increíble amanecer en la laguna, el guía muy atento.", fecha: "2026-09-09" },
  { reservaId: "RV-0032", valoracion: 4, comentario: "Muy lindo paseo, la vianda podría mejorar.", fecha: "2026-09-01" },
]

export const HOY = new Date("2026-09-26T12:00:00")
