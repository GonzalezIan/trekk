import EstadoBadge, { etiquetaEstado } from '@/Components/EstadoBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

// CU 15 - Listar reservas

const codigo = (id) => `R-${String(id).padStart(4, '0')}`;

const usd = (monto) => `USD ${monto.toLocaleString('es-AR')}`;

const formatFecha = (fecha) =>
    new Date(`${fecha}T12:00:00`).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

// RN-01: la reserva podrá cancelarse si y sólo si se encuentra pendiente o confirmada
const sePuedeCancelar = (r) => r.estado === 'PENDIENTE' || r.estado === 'CONFIRMADA';

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

// Botón de una operación que habilita la reserva según su estado.
// Quedan deshabilitados hasta que se implemente el CU correspondiente.
function BotonAccion({ children, cu, peligro = false }) {
    return (
        <button
            type="button"
            disabled
            title={`Se habilita con el ${cu}`}
            className={
                'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 ' +
                (peligro ? 'text-red-600' : 'text-gray-700')
            }
        >
            {children}
        </button>
    );
}

// ---------- Vista del Cliente (3.1) ----------

function ReservasCliente({ reservas }) {
    return (
        <>
            <Encabezado
                eyebrow="Cliente"
                titulo="Mis reservas"
                descripcion="Consultá el estado de tus reservas, aboná saldos pendientes, cargá certificados médicos y calificá tus excursiones."
            />

            {reservas.length === 0 ? (
                <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-gray-300 p-10 text-center">
                    <p className="text-gray-500">No existen reservas todavía.</p>
                </div>
            ) : (
                <div className="flex flex-col gap-4">
                    {reservas.map((r) => (
                        <TarjetaReserva key={r.id} reserva={r} />
                    ))}
                </div>
            )}
        </>
    );
}

function TarjetaReserva({ reserva: r }) {
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
                <BotonAccion cu="CU 21">Participantes</BotonAccion>
                {r.estado === 'PENDIENTE' && <BotonAccion cu="CU 22">Registrar pago</BotonAccion>}
                {r.estado === 'REALIZADA' && !r.tiene_conformidad && (
                    <BotonAccion cu="CU 20">Registrar conformidad</BotonAccion>
                )}
                {sePuedeCancelar(r) && (
                    <BotonAccion cu="CU 16" peligro>
                        Cancelar reserva
                    </BotonAccion>
                )}
            </div>

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

            <div className="flex flex-col gap-3">
                <div className="flex flex-wrap items-center gap-2 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
                    {reserva ? (
                        <>
                            <span className="text-sm text-gray-500">Seleccionado:</span>
                            <span className="mr-2 text-sm font-medium">{codigo(reserva.id)}</span>
                            {sePuedeCancelar(reserva) && (
                                <BotonAccion cu="CU 16" peligro>
                                    Cancelar reserva
                                </BotonAccion>
                            )}
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
        </>
    );
}
