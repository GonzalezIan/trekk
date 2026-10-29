import { CancelarReservaModal, PanelCancelarReserva } from '@/Components/CancelarReserva';
import { PanelRegistrarPago } from '@/Components/RegistrarPago';
import EstadoBadge, { etiquetaEstado } from '@/Components/EstadoBadge';
import ParticipantesReserva from '@/Components/ParticipantesReserva';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import RegistrarConformidadModal from '@/Components/RegistrarConformidadModal';
import { Head } from '@inertiajs/react';
import { useState,useEffect } from 'react';

// CU 15 - Listar reservas, extendido por el CU 16 - Cancelar reserva

const codigo = (id) => `R-${String(id).padStart(4, '0')}`;

const usd = (monto) => `USD ${monto.toLocaleString('es-AR')}`;

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
            {!esAdministrativo&&mostrarPopUp && (
                <div className="fixed bottom-6 right-6 z-50 max-w-md w-full bg-white shadow-2xl rounded-xl border-l-4 border-red-500 overflow-hidden">
                    <div className="p-5">
                        <div className="flex items-start">
                            <div className="flex-shrink-0">
                                <span className="text-3xl">⚠️</span>
                            </div>
                            <div className="ml-3 w-full">
                                <h3 className="text-lg font-bold text-gray-900">
                                    Reserva Cancelada
                                </h3>
                                <div className="mt-2 text-sm text-gray-600">
                                    <p className="mb-2">
                                        Tu reserva <strong>R-{String(notificacion_cancelacion.id).padStart(4, '0')}</strong> ha sido cancelada por la administración.
                                    </p>
                                    <p className="mb-3 p-2 bg-red-50 text-red-700 rounded text-xs">
                                        <strong>Motivo:</strong> {notificacion_cancelacion.motivo_cancelacion}
                                    </p>
                                    <p className="font-semibold text-gray-800">Contactate para gestionar tu reembolso:</p>
                                    <ul className="list-disc pl-4 mt-1 space-y-1">
                                        <li>📧 recepcion@trekk.com</li>
                                        <li>📞 +54 297 1234567</li>
                                        <li>📍 Acercate a nuestras oficinas.</li>
                                    </ul>
                                </div>
                                {/* 👇 ACÁ LLAMAMOS A LA FUNCIÓN DE CERRAR 👇 */}
                                <button 
                                    onClick={cerrarPopUp}
                                    className="mt-4 w-full bg-trekk-primario text-trekk-primario-texto text-sm font-medium py-2 rounded-lg hover:bg-trekk-primario-hover transition"
                                >
                                    Entendido
                                </button>
                            </div>
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
            <p className="text-xs font-semibold uppercase tracking-widest text-orange-600">
                {eyebrow}
            </p>
            <h1 className="text-3xl font-semibold text-gray-900 md:text-4xl">{titulo}</h1>
            <p className="max-w-2xl text-gray-500">{descripcion}</p>
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
                <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-gray-300 p-10 text-center">
                    <p className="text-gray-500">No existen reservas todavía.</p>
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
        <article className="overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <div className="flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between">
                <div className="flex flex-col gap-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="font-mono text-xs text-gray-500">{codigo(r.id)}</span>
                        <EstadoBadge valor={r.estado} />
                        <EstadoBadge valor={r.situacion_de_pago} />
                    </div>
                    <h2 className="text-xl font-semibold text-gray-900">{r.excursion}</h2>
                    <p className="text-sm text-gray-500">
                        {formatFecha(r.fecha_inicio)} · Paquete {r.paquete} ·{' '}
                        {r.cantidad_participantes}{' '}
                        {r.cantidad_participantes === 1 ? 'participante' : 'participantes'}
                    </p>
                </div>
                <span className="text-2xl font-semibold text-gray-900">
                    {usd(r.importe_total)}
                </span>
            </div>

            <div className="flex flex-wrap gap-2 border-t border-gray-200 px-5 py-3">
                {/* 4.1 - CU 21 Listar participantes */}
                <button
                    type="button"
                    aria-expanded={panel === 'participantes'}
                    onClick={() => alternar('participantes')}
                    className={
                        'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 ' +
                        (panel === 'participantes' ? 'bg-gray-100' : '')
                    }
                >
                    Participantes
                </button>
                {/* 4.2 - CU 22 Registrar pago (solo si la reserva tiene un importe pendiente) */}
                {r.se_puede_pagar && (
                    <button
                        type="button"
                        aria-expanded={panel === 'pagar'}
                        onClick={() => alternar('pagar')}
                        className={
                            'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 ' +
                            (panel === 'pagar' ? 'bg-gray-100' : '')
                        }
                    >
                        Registrar pago
                    </button>
                )}
                {r.estado === 'REALIZADA' && !r.tiene_conformidad && (
                <button
                    type="button"
                    onClick={() => onCalificar(r)} // 👈 Le pasamos la reserva
                    className="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100"
                >
                    ⭐ Registrar conformidad
                </button>
                )}
                {/* CU 16 - Cancelar reserva (solo "Pendiente" o "Confirmada", lo decide el servidor) */}
                {r.se_puede_cancelar && (
                    <button
                        type="button"
                        aria-expanded={panel === 'cancelar'}
                        onClick={() => alternar('cancelar')}
                        className="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-gray-100"
                    >
                        Cancelar reserva
                    </button>
                )}
            </div>

            {panel === 'participantes' && (
                <div className="border-t border-gray-200 bg-gray-50 p-5">
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
                <p className="border-t border-gray-200 px-5 py-3 text-sm text-gray-500">
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
                <div className="flex flex-wrap items-center gap-2 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
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
                    <div className="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
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
                                            (seleccionada === r.id ? 'bg-emerald-50 hover:bg-emerald-100' : '')
                                        }
                                    >
                                        <td className="w-8">
                                            <input
                                                type="radio"
                                                name="reserva"
                                                checked={seleccionada === r.id}
                                                onChange={() => setSeleccionada(r.id)}
                                                aria-label={`Seleccionar ${codigo(r.id)}`}
                                                className="text-emerald-700"
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
