import { CancelarReservaModal, PanelCancelarReserva } from '@/Components/CancelarReserva';
import { PanelRegistrarPago } from '@/Components/RegistrarPago';
import EstadoBadge, { etiquetaEstado } from '@/Components/EstadoBadge';
import ParticipantesReserva from '@/Components/ParticipantesReserva';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

// CU 15 - Listar reservas, extendido por el CU 16 - Cancelar reserva

const codigo = (id) => `R-${String(id).padStart(4, '0')}`;

const usd = (monto) => `USD ${monto.toLocaleString('es-AR')}`;

const formatFecha = (fecha) =>
    new Date(`${fecha}T12:00:00`).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

export default function Listar({ reservas, esAdministrativo }) {
    return (
        <AuthenticatedLayout>
            <Head title={esAdministrativo ? 'Reservas' : 'Mis reservas'} />

            <main className="mx-auto max-w-5xl px-4 py-12">
                {esAdministrativo ? (
                    <ReservasAdministrativo reservas={reservas} />
                ) : (
                    <ReservasCliente reservas={reservas} />
                )}
            </main>
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

function ReservasCliente({ reservas }) {
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
                        <TarjetaReserva key={r.id} reserva={r} onAviso={setAviso} />
                    ))}
                </div>
            )}
        </>
    );
}

function TarjetaReserva({ reserva: r, onAviso }) {
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
                    <BotonAccion cu="CU 20">Registrar conformidad</BotonAccion>
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
