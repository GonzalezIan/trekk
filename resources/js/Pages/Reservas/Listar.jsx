import { CancelarReservaModal, PanelCancelarReserva } from '@/Components/CancelarReserva';
import { PanelRegistrarPago } from '@/Components/RegistrarPago';
import EstadoBadge, { etiquetaEstado } from '@/Components/EstadoBadge';
import ParticipantesReserva from '@/Components/ParticipantesReserva';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import RegistrarConformidadModal from '@/Components/RegistrarConformidadModal';
import {
    IconoAlerta,
    IconoCancelar,
    IconoEstrella,
    IconoFlecha,
    IconoPersonas,
    IconoTarjeta,
} from '@/Components/Trekk/Iconos';
import { Head, Link } from '@inertiajs/react';
import { useState,useEffect } from 'react';

// CU 15 - Listar reservas, extendido por el CU 16 - Cancelar reserva

const codigo = (id) => `R-${String(id).padStart(4, '0')}`;

const usd = (monto) => `USD ${monto.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const formatFecha = (fecha) =>
    new Date(`${fecha}T12:00:00`).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

export default function Listar({ reservas, esAdministrativo, notificacion_cancelacion }) {
    const [mostrarPopUp, setMostrarPopUp] = useState(false);
    const [reservaACalificar, setReservaACalificar] = useState(null);

    useEffect(() => {
        if (notificacion_cancelacion) {
            // Buscamos en el navegador si ya vimos esta reserva
            const vistas = JSON.parse(localStorage.getItem('notificaciones_vistas') || '[]');
            
            // Si el ID de esta reserva NO está en las vistas, mostramos el cartel
            if (!vistas.includes(notificacion_cancelacion.id)) {
                setMostrarPopUp(true);
            }
        }
    }, [notificacion_cancelacion]);

    // 3. Función al tocar "Entendido"
    const cerrarPopUp = () => {
        // Guardamos el ID en la lista de vistas
        const vistas = JSON.parse(localStorage.getItem('notificaciones_vistas') || '[]');
        vistas.push(notificacion_cancelacion.id);
        localStorage.setItem('notificaciones_vistas', JSON.stringify(vistas));
        
        // Ocultamos el cartel
        setMostrarPopUp(false);
    };

    return (
        <AuthenticatedLayout>
            <Head title={esAdministrativo ? 'Reservas' : 'Mis reservas'} />
            {!esAdministrativo && mostrarPopUp && (
                <div
                    role="alertdialog"
                    aria-labelledby="aviso-cancelacion"
                    className="fixed inset-x-4 bottom-4 z-50 overflow-hidden rounded-2xl border border-trekk-borde bg-trekk-tarjeta shadow-2xl sm:inset-x-auto sm:bottom-6 sm:right-6 sm:w-full sm:max-w-md"
                >
                    <div className="flex items-start gap-3 p-5">
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-trekk-error/10 text-trekk-error">
                            <IconoAlerta />
                        </span>
                        <div className="w-full">
                            <h3 id="aviso-cancelacion" className="font-display text-lg font-semibold">
                                Reserva cancelada
                            </h3>
                            <div className="mt-2 text-sm text-trekk-texto-suave">
                                <p className="mb-2">
                                    Tu reserva <strong className="text-trekk-texto">{codigo(notificacion_cancelacion.id)}</strong> fue
                                    cancelada por la administración.
                                </p>
                                <p className="mb-3 rounded-lg bg-trekk-error/5 px-3 py-2 text-xs text-trekk-error">
                                    <strong>Motivo:</strong> {notificacion_cancelacion.motivo_cancelacion}
                                </p>
                                <p className="font-medium text-trekk-texto">Contactate para gestionar tu reembolso:</p>
                                <ul className="mt-1 list-disc space-y-1 pl-4">
                                    <li>recepcion@trekk.com</li>
                                    <li>+54 297 1234567</li>
                                    <li>O acercate a nuestras oficinas.</li>
                                </ul>
                            </div>
                            <button
                                onClick={cerrarPopUp}
                                className="mt-4 w-full rounded-lg bg-trekk-primario py-2 text-sm font-medium text-trekk-primario-texto transition hover:bg-trekk-primario-hover"
                            >
                                Entendido
                            </button>
                        </div>
                    </div>
                </div>
            )}

            <main className="mx-auto max-w-5xl px-4 py-12">
                {esAdministrativo ? (
                    <ReservasAdministrativo reservas={reservas} />
                ) : (
                    <ReservasCliente reservas={reservas}
                    onCalificar={setReservaACalificar}/>
                )}
            </main>
            {reservaACalificar && (
                <RegistrarConformidadModal
                    reserva={reservaACalificar}
                    onCerrar={() => setReservaACalificar(null)}
                />
            )}
        </AuthenticatedLayout>
    );
}

function Encabezado({ eyebrow, titulo, descripcion }) {
    return (
        <div className="flex flex-col gap-2 pb-8">
            <p className="text-xs font-semibold uppercase tracking-widest text-trekk-acento">
                {eyebrow}
            </p>
            <h1 className="font-display text-3xl font-semibold text-trekk-texto md:text-4xl">{titulo}</h1>
            <p className="max-w-2xl text-trekk-texto-suave">{descripcion}</p>
        </div>
    );
}

// Mensaje que informa el sistema al terminar un caso de uso (igual al prototipo)
function AvisoExito({ mensaje, onCerrar }) {
    if (!mensaje) return null;
    return (
        <div
            role="status"
            className="mb-4 flex items-start gap-2 rounded-xl border border-emerald-300/50 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
        >
            <span className="flex-1">{mensaje}</span>
            <button
                type="button"
                aria-label="Cerrar aviso"
                onClick={onCerrar}
                className="rounded-md px-1.5 text-emerald-900/70 hover:bg-emerald-100"
            >
                ×
            </button>
        </div>
    );
}

// Botón de una operación que habilita la reserva según su estado.
// Quedan deshabilitados hasta que se implemente el CU correspondiente.
function BotonAccion({ children, cu }) {
    return (
        <button
            type="button"
            disabled
            title={`Se habilita con el ${cu}`}
            className="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
        >
            {children}
        </button>
    );
}

// ---------- Vista del Cliente (3.1) ----------

function ReservasCliente({ reservas,onCalificar }) {
    const [aviso, setAviso] = useState('');

    return (
        <>
            <Encabezado
                eyebrow="Cliente"
                titulo="Mis reservas"
                descripcion="Consultá el estado de tus reservas, aboná saldos pendientes, cargá certificados médicos y calificá tus excursiones."
            />

            <AvisoExito mensaje={aviso} onCerrar={() => setAviso('')} />

            {reservas.length === 0 ? (
                <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-trekk-borde p-10 text-center">
                    <p className="text-trekk-texto-suave">No existen reservas todavía.</p>
                    <Link
                        href={route('excursiones.listar')}
                        className="flex items-center gap-1 text-sm font-medium text-trekk-primario hover:underline"
                    >
                        Explorar excursiones <IconoFlecha className="h-4 w-4" />
                    </Link>
                </div>
            ) : (
                <div className="flex flex-col gap-4">
                    {reservas.map((r) => (
                        <TarjetaReserva 
                            key={r.id} 
                            reserva={r} 
                            onAviso={setAviso} 
                            onCalificar={onCalificar} // 👈 ACÁ LE PASAMOS LA FUNCIÓN
                        />
                    ))}
                </div>
            )}
        </>
    );
}

function TarjetaReserva({ reserva: r, onAviso,onCalificar }) {
    // Panel desplegado debajo de la tarjeta: null | 'participantes' | 'cancelar' | 'pagar'
    const [panel, setPanel] = useState(null);

    const alternar = (p) => {
        onAviso('');
        setPanel(panel === p ? null : p);
    };

    return (
        <article className="overflow-hidden rounded-2xl border border-trekk-borde bg-trekk-tarjeta">
            <div className="flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between">
                <div className="flex flex-col gap-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="font-mono text-xs text-trekk-texto-suave">{codigo(r.id)}</span>
                        <EstadoBadge valor={r.estado} />
                        <EstadoBadge valor={r.situacion_de_pago} />
                    </div>
                    <h2 className="font-display text-xl font-semibold">{r.excursion}</h2>
                    <p className="text-sm text-trekk-texto-suave">
                        {formatFecha(r.fecha_inicio)} · Paquete {r.paquete} ·{' '}
                        {r.cantidad_participantes}{' '}
                        {r.cantidad_participantes === 1 ? 'participante' : 'participantes'}
                    </p>
                </div>
                <div className="flex flex-col items-start gap-0.5 md:items-end">
                    <span className="font-display text-2xl font-semibold">{usd(r.importe_total)}</span>
                    {r.se_puede_pagar && <span className="text-xs text-amber-700">Tenés un pago pendiente</span>}
                </div>
            </div>

            <div className="flex flex-wrap gap-2 border-t border-trekk-borde px-5 py-3">
                {/* 4.1 - CU 21 Listar participantes */}
                <button
                    type="button"
                    aria-expanded={panel === 'participantes'}
                    onClick={() => alternar('participantes')}
                    className={
                        'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-trekk-texto hover:bg-trekk-suave ' +
                        (panel === 'participantes' ? 'bg-trekk-suave' : '')
                    }
                >
                    <IconoPersonas className="h-4 w-4" /> Participantes
                </button>
                {/* 4.2 - CU 22 Registrar pago (solo si la reserva tiene un importe pendiente) */}
                {r.se_puede_pagar && (
                    <button
                        type="button"
                        aria-expanded={panel === 'pagar'}
                        onClick={() => alternar('pagar')}
                        className={
                            'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-trekk-texto hover:bg-trekk-suave ' +
                            (panel === 'pagar' ? 'bg-trekk-suave' : '')
                        }
                    >
                        <IconoTarjeta className="h-4 w-4" /> Registrar pago
                    </button>
                )}
                {r.estado === 'REALIZADA' && !r.tiene_conformidad && (
                    <button
                        type="button"
                        onClick={() => onCalificar(r)}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-trekk-secundario px-3 py-1.5 text-sm font-medium text-trekk-primario hover:bg-trekk-secundario/70"
                    >
                        <IconoEstrella className="h-4 w-4" /> Registrar conformidad
                    </button>
                )}
                {/* CU 16 - Cancelar reserva (solo "Pendiente" o "Confirmada", lo decide el servidor) */}
                {r.se_puede_cancelar && (
                    <button
                        type="button"
                        aria-expanded={panel === 'cancelar'}
                        onClick={() => alternar('cancelar')}
                        className="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-trekk-error hover:bg-trekk-error/5"
                    >
                        <IconoCancelar className="h-4 w-4" /> Cancelar reserva
                    </button>
                )}
            </div>

            {panel === 'participantes' && (
                <div className="border-t border-trekk-borde bg-trekk-suave/40 p-5">
                    <ParticipantesReserva reserva={r} />
                </div>
            )}

            {panel === 'pagar' && (
                <PanelRegistrarPago
                    reserva={r}
                    onPagado={(mensaje) => {
                        setPanel(null);
                        onAviso(mensaje);
                    }}
                />
            )}

            {panel === 'cancelar' && (
                <PanelCancelarReserva
                    reserva={r}
                    onCancelada={(mensaje) => {
                        setPanel(null);
                        onAviso(mensaje);
                    }}
                />
            )}

            {r.motivo_cancelacion && (
                <p className="border-t border-trekk-borde px-5 py-3 text-sm text-trekk-texto-suave">
                    Motivo: {r.motivo_cancelacion}
                </p>
            )}
        </article>
    );
}

// ---------- Vista del Administrativo (3.2) ----------

const filtros = ['TODAS', 'PENDIENTE', 'CONFIRMADA', 'REALIZADA', 'CANCELADA'];

function ReservasAdministrativo({ reservas }) {
    const [filtro, setFiltro] = useState('TODAS');
    const [busqueda, setBusqueda] = useState('');
    const [seleccionada, setSeleccionada] = useState(null);
    const [cancelando, setCancelando] = useState(false);
    const [aviso, setAviso] = useState('');

    const lista = reservas.filter((r) => {
        if (filtro !== 'TODAS' && r.estado !== filtro) return false;
        if (!busqueda) return true;
        const texto =
            `${codigo(r.id)} ${r.cliente.dni} ${r.cliente.nombre} ${r.cliente.apellido} ${r.excursion}`.toLowerCase();
        return texto.includes(busqueda.toLowerCase());
    });
    const reserva = reservas.find((r) => r.id === seleccionada);

    return (
        <>
            <Encabezado
                eyebrow="Administración"
                titulo="Reservas"
                descripcion="Consultá todas las reservas registradas y su situación de pago."
            />

            <div className="flex flex-col gap-3 pb-4 md:flex-row md:items-center md:justify-between">
                <div role="tablist" aria-label="Filtrar por estado" className="flex flex-wrap gap-1">
                    {filtros.map((f) => (
                        <button
                            key={f}
                            role="tab"
                            aria-selected={filtro === f}
                            onClick={() => setFiltro(f)}
                            className={
                                'rounded-md px-3 py-1.5 text-sm hover:text-gray-900 ' +
                                (filtro === f
                                    ? 'bg-gray-100 font-medium text-gray-900'
                                    : 'text-gray-500')
                            }
                        >
                            {f === 'TODAS' ? 'Todas' : etiquetaEstado(f)}
                        </button>
                    ))}
                </div>
                <input
                    type="search"
                    aria-label="Buscar"
                    placeholder="Buscar por código, cliente o excursión"
                    value={busqueda}
                    onChange={(e) => setBusqueda(e.target.value)}
                    className="rounded-lg border-gray-200 text-sm md:w-72"
                />
            </div>

            <AvisoExito mensaje={aviso} onCerrar={() => setAviso('')} />

            <div className="flex flex-col gap-3">
                <div className="flex flex-wrap items-center gap-2 rounded-2xl border border-trekk-borde bg-trekk-tarjeta px-4 py-3">
                    {reserva ? (
                        <>
                            <span className="text-sm text-gray-500">Seleccionado:</span>
                            <span className="mr-2 text-sm font-medium">{codigo(reserva.id)}</span>
                            {/* CU 16 - Cancelar reserva (solo "Pendiente" o "Confirmada") */}
                            <button
                                type="button"
                                disabled={!reserva.se_puede_cancelar}
                                title={
                                    reserva.se_puede_cancelar
                                        ? undefined
                                        : 'Solo pueden cancelarse reservas “Pendiente” o “Confirmada”'
                                }
                                onClick={() => {
                                    setAviso('');
                                    setCancelando(true);
                                }}
                                className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Cancelar reserva
                            </button>
                        </>
                    ) : (
                        <span className="text-sm text-gray-500">
                            Seleccioná una reserva en estado “Pendiente” o “Confirmada” para cancelarla.
                        </span>
                    )}
                </div>

                {lista.length === 0 ? (
                    <p className="rounded-xl border border-dashed border-gray-300 p-6 text-center text-gray-500">
                        {reservas.length === 0
                            ? 'No existen reservas todavía.'
                            : 'No se encontraron reservas.'}
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-2xl border border-trekk-borde bg-trekk-tarjeta">
                        <table className="w-full text-sm">
                            <thead className="border-b border-gray-200 text-left text-gray-500">
                                <tr>
                                    {['', 'Código', 'Cliente', 'Excursión / salida', 'Pax', 'Importe', 'Pago', 'Estado'].map(
                                        (h) => (
                                            <th key={h} scope="col" className="px-4 py-3 font-medium">
                                                {h}
                                            </th>
                                        ),
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 [&_td]:px-4 [&_td]:py-3">
                                {lista.map((r) => (
                                    <tr
                                        key={r.id}
                                        onClick={() => setSeleccionada(r.id)}
                                        className={
                                            'cursor-pointer transition-colors hover:bg-gray-50 ' +
                                            (seleccionada === r.id ? 'bg-trekk-secundario/60 hover:bg-trekk-secundario' : '')
                                        }
                                    >
                                        <td className="w-8">
                                            <input
                                                type="radio"
                                                name="reserva"
                                                checked={seleccionada === r.id}
                                                onChange={() => setSeleccionada(r.id)}
                                                aria-label={`Seleccionar ${codigo(r.id)}`}
                                                className="text-trekk-primario focus:ring-trekk-primario"
                                            />
                                        </td>
                                        <td className="font-mono text-xs">{codigo(r.id)}</td>
                                        <td>
                                            {r.cliente.nombre} {r.cliente.apellido}
                                            <span className="block text-xs text-gray-500">
                                                DNI {r.cliente.dni}
                                            </span>
                                        </td>
                                        <td>
                                            {r.excursion}
                                            <span className="block text-xs text-gray-500">
                                                {formatFecha(r.fecha_inicio)} · {r.paquete}
                                            </span>
                                        </td>
                                        <td className="tabular-nums">{r.cantidad_participantes}</td>
                                        <td className="whitespace-nowrap tabular-nums">{usd(r.importe_total)}</td>
                                        <td>
                                            <EstadoBadge valor={r.situacion_de_pago} />
                                        </td>
                                        <td>
                                            <EstadoBadge valor={r.estado} />
                                            {r.motivo_cancelacion && (
                                                <span className="block pt-1 text-xs text-gray-500">
                                                    {r.motivo_cancelacion}
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {cancelando && reserva && (
                <CancelarReservaModal
                    reserva={reserva}
                    onCerrar={() => setCancelando(false)}
                    onCancelada={(mensaje) => {
                        setCancelando(false);
                        setAviso(`${codigo(reserva.id)}: ${mensaje}`);
                    }}
                />
            )}
        </>
    );
}
