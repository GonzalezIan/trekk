import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import ParticipantesSalidaModal from '@/Components/ParticipantesSalidaModal';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

// "2026-10-27" -> "27/10/2026"
const fecha = (valor) => valor.split('-').reverse().join('/');
const precio = (valor) =>
    Number(valor).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

// CU 9 - Listar Salidas Programadas
export default function Listar({
    salidas,
    esAdministrativo,
    excursion = null,
    excursiones = [],
    estados = [],
    filtros = {},
}) {
    const { flash } = usePage().props;

    // Salida elegida por el Administrativo para operar sobre ella
    const [seleccionada, setSeleccionada] = useState(null);
    const salidaSeleccionada = salidas.find((s) => s.id === seleccionada);

    // Alternativo 4.8: ver los participantes de la salida seleccionada (CU 21)
    const [verParticipantes, setVerParticipantes] = useState(false);

    // CU 11 - Cancelar Salida Programada
    const [cancelando, setCancelando] = useState(null);
    const [motivo, setMotivo] = useState('');
    const [errorMotivo, setErrorMotivo] = useState(null); // alternativo 4.1
    const [procesando, setProcesando] = useState(false);

    const confirmarCancelacion = () => {
        router.post(
            route('salidas.cancelar', cancelando.id),
            { motivo },
            {
                preserveScroll: true,
                onStart: () => setProcesando(true),
                onFinish: () => setProcesando(false),
                onError: (errores) => setErrorMotivo(errores.motivo ?? null),
                onSuccess: () => setCancelando(null),
            },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="font-display text-2xl font-semibold text-trekk-texto">
                        {esAdministrativo
                            ? 'Salidas Programadas'
                            : `Salidas de ${excursion.nombre}`}
                    </h2>
                    {!esAdministrativo && (
                        <Link
                            href={route('excursiones.listar')}
                            className="text-sm text-gray-600 underline hover:text-gray-900"
                        >
                            Volver a excursiones
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Salidas Programadas" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    {/* CU 8 - paso 9: se informa que la salida fue registrada */}
                    {flash.exito && (
                        <div className="rounded-md bg-green-50 p-4 text-sm text-green-800">
                            {flash.exito}
                        </div>
                    )}
                    {flash.error && (
                        <div role="alert" className="rounded-md bg-red-50 p-4 text-sm text-red-700">
                            {flash.error}
                        </div>
                    )}

                    {esAdministrativo && (
                        <Filtros
                            filtros={filtros}
                            excursiones={excursiones}
                            estados={estados}
                        />
                    )}

                    {esAdministrativo && salidas.length > 0 && (
                        <BarraAcciones
                            salida={salidaSeleccionada}
                            onListarParticipantes={() => setVerParticipantes(true)}
                            onCancelar={() => {
                                setCancelando(salidaSeleccionada);
                                setMotivo('');
                                setErrorMotivo(null);
                            }}
                        />                    
                    )}

                    <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                        {salidas.length === 0 ? (
                            // Alternativo 3.3
                            <p className="p-6 text-gray-600">
                                {esAdministrativo
                                    ? 'No existen Salidas Programadas que cumplan los filtros.'
                                    : 'No existen Salidas Programadas para la excursión seleccionada.'}
                            </p>
                        ) : esAdministrativo ? (
                            <TablaAdministrativo
                                salidas={salidas}
                                seleccionada={seleccionada}
                                onSeleccionar={setSeleccionada}
                            />
                        ) : (
                            <TablaCliente salidas={salidas} />
                        )}
                    </div>
                </div>
            </div>
            {verParticipantes && salidaSeleccionada && (
                <ParticipantesSalidaModal
                    salida={{
                        ...salidaSeleccionada,
                        fecha_inicio: fecha(salidaSeleccionada.fecha_inicio),
                    }}
                    onCerrar={() => setVerParticipantes(false)}
                />
            )}
            {cancelando && (
                <Modal show={true} onClose={() => setCancelando(null)} maxWidth="md">
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-gray-900">
                            Cancelar salida programada
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            Indicá el motivo de la cancelación. Se cancelarán las
                            reservas asociadas y se notificará a los clientes.
                            Esta operación no se puede deshacer.
                        </p>
                        <textarea
                            value={motivo}
                            onChange={(e) => setMotivo(e.target.value)}
                            className="mt-4 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows={4}
                            maxLength={1000}
                        />
                        <InputError message={errorMotivo} className="mt-2" />
                        <div className="mt-4 flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setCancelando(null)}
                                className="rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                            >
                                Volver
                            </button>
                            <button
                                type="button"
                                onClick={confirmarCancelacion}
                                disabled={procesando}
                                className="rounded-lg bg-trekk-error px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                            >
                                {procesando ? 'Cancelando…' : 'Confirmar cancelación'}
                            </button>
                        </div>
                    </div>
                </Modal>
            )}
        </AuthenticatedLayout>
    );
}

// Filtros del Administrativo: excursión, estado, desde, hasta
function Filtros({ filtros, excursiones, estados }) {
    const [valores, setValores] = useState({
        excursion: filtros.excursion ?? '',
        estado: filtros.estado ?? '',
        desde: filtros.desde ?? '',
        hasta: filtros.hasta ?? '',
    });

    const cambiar = (e) => setValores({ ...valores, [e.target.name]: e.target.value });

    const filtrar = (e) => {
        e.preventDefault();
        // Solo mandamos los filtros completos
        const completos = Object.fromEntries(
            Object.entries(valores).filter(([, valor]) => valor !== ''),
        );
        router.get(route('salidas.listar'), completos, { preserveState: true });
    };

    const limpiar = () => {
        setValores({ excursion: '', estado: '', desde: '', hasta: '' });
        router.get(route('salidas.listar'));
    };

    const campo =
        'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

    return (
        <form
            onSubmit={filtrar}
            className="grid gap-4 bg-white p-4 shadow-sm sm:grid-cols-5 sm:rounded-lg"
        >
            <label className="text-sm text-gray-700">
                Excursión
                <select name="excursion" value={valores.excursion} onChange={cambiar} className={campo}>
                    <option value="">Todas</option>
                    {excursiones.map((e) => (
                        <option key={e.id} value={e.id}>
                            {e.nombre}
                        </option>
                    ))}
                </select>
            </label>
            <label className="text-sm text-gray-700">
                Estado
                <select name="estado" value={valores.estado} onChange={cambiar} className={campo}>
                    <option value="">Todos</option>
                    {estados.map((estado) => (
                        <option key={estado} value={estado}>
                            {estado}
                        </option>
                    ))}
                </select>
            </label>
            <label className="text-sm text-gray-700">
                Desde
                <input type="date" name="desde" value={valores.desde} onChange={cambiar} className={campo} />
            </label>
            <label className="text-sm text-gray-700">
                Hasta
                <input type="date" name="hasta" value={valores.hasta} onChange={cambiar} className={campo} />
            </label>
            <div className="flex items-end gap-2">
                <button
                    type="submit"
                    className="rounded-lg bg-trekk-primario px-4 py-2 text-sm font-medium text-trekk-primario-texto hover:bg-trekk-primario-hover"
                >
                    Filtrar
                </button>
                <button
                    type="button"
                    onClick={limpiar}
                    className="rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                >
                    Limpiar
                </button>
            </div>
        </form>
    );
}

// Operaciones que habilita la salida seleccionada por el Administrativo
function BarraAcciones({ salida, onListarParticipantes, onCancelar }) {
    return (
        <div className="flex flex-wrap items-center gap-3 bg-white px-4 py-3 text-sm shadow-sm sm:rounded-lg">
            {salida ? (
                <>
                    <span className="text-gray-600">Seleccionada:</span>
                    <span className="font-medium text-gray-900">
                        {salida.excursion} · {fecha(salida.fecha_inicio)}
                    </span>
                    {/* CU 7 - Listar paquetes de la salida */}
                    <Link
                        href={route('paquetes.listar', salida.id)}
                        className="rounded-lg bg-trekk-primario px-4 py-2 text-sm font-medium text-trekk-primario-texto hover:bg-trekk-primario-hover"
                    >
                        Listar paquetes
                    </Link>
                    {/* CU 5 - Registrar paquete (RN-01: solo sobre salidas PROGRAMADAS) */}
                    {salida.estado === 'PROGRAMADA' && (
                        <Link
                            href={route('paquetes.registrar', salida.id)}
                            className="rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                        >
                            Registrar paquete
                        </Link>
                    )}
                    {/* CU 10 - Modificar Salida Programada (RN-01: solo salidas PROGRAMADAS) */}
                    {salida.estado === 'PROGRAMADA' && (
                        <Link
                            href={route('salidas.modificar', salida.id)}
                            className="rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                        >
                            Modificar
                        </Link>
                    )}
                    {/* CU 21 - Listar participantes de la salida */}
                    <button
                        type="button"
                        onClick={onListarParticipantes}
                        className="rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                    >
                        Listar participantes
                    </button>
                    {/* CU 11 - Cancelar Salida Programada (RN-01: solo salidas PROGRAMADAS) */}
                    {salida.estado === 'PROGRAMADA' && (
                        <button
                            type="button"
                            onClick={onCancelar}
                            className="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50"
                        >
                            Cancelar
                        </button>
                    )}
                </>
            ) : (
                <span className="text-gray-600">
                    Seleccioná una salida para ver las operaciones disponibles.
                </span>
            )}
        </div>
    );
}

// Alternativo 4.1 - lo que ve el Cliente
function TablaCliente({ salidas }) {
    return (
        <table className="min-w-full divide-y divide-gray-200 text-sm">
            <thead className="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th className="px-4 py-3">Inicio</th>
                    <th className="px-4 py-3">Fin</th>
                    <th className="px-4 py-3">Hora de salida</th>
                    <th className="px-4 py-3">Equipamiento incluido</th>
                    <th className="px-4 py-3">Precio base</th>
                    <th className="px-4 py-3">Cupo disponible</th>
                    <th className="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
                {salidas.map((s) => (
                    <tr key={s.id}>
                        <td className="px-4 py-3">{fecha(s.fecha_inicio)}</td>
                        <td className="px-4 py-3">{fecha(s.fecha_fin)}</td>
                        <td className="px-4 py-3">{s.hora_de_salida}</td>
                        <td className="px-4 py-3">{s.equipamiento.join(', ') || '—'}</td>
                        <td className="px-4 py-3">{precio(s.precio_base)}</td>
                        <td className="px-4 py-3">{s.cupo_disponible}</td>
                        {/* El Cliente elige la salida -> CU 7 */}
                        <td className="px-4 py-3 text-right">
                            <Link
                                href={route('paquetes.listar', s.id)}
                                className="text-indigo-600 hover:underline"
                            >
                                Ver paquetes
                            </Link>
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

// Alternativo 4.2 - lo que ve el Administrativo
function TablaAdministrativo({ salidas, seleccionada, onSeleccionar }) {
    return (
        <table className="min-w-full divide-y divide-gray-200 text-sm">
            <thead className="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th className="w-8 px-4 py-3"></th>
                    <th className="px-4 py-3">Excursión</th>
                    <th className="px-4 py-3">Inicio</th>
                    <th className="px-4 py-3">Fin</th>
                    <th className="px-4 py-3">Hora</th>
                    <th className="px-4 py-3">Guía</th>
                    <th className="px-4 py-3">Asistentes</th>
                    <th className="px-4 py-3">Equipamiento</th>
                    <th className="px-4 py-3">Cupo máx.</th>
                    <th className="px-4 py-3">Cupo disp.</th>
                    <th className="px-4 py-3">Precio base</th>
                    <th className="px-4 py-3">Paquetes</th>
                    <th className="px-4 py-3">Estado</th>
                </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
                {salidas.map((s) => (
                     <tr
                        key={s.id}
                        onClick={() => onSeleccionar(s.id)}
                        className={
                            'cursor-pointer ' +
                            (s.id === seleccionada ? 'bg-indigo-50' : 'hover:bg-gray-50')
                        }
                    >
                        <td className="px-4 py-3">
                            <input
                                type="radio"
                                name="salida"
                                checked={s.id === seleccionada}
                                onChange={() => onSeleccionar(s.id)}
                                aria-label={`Seleccionar salida de ${s.excursion} del ${fecha(s.fecha_inicio)}`}
                                className="text-indigo-600 focus:ring-indigo-500"
                            />
                        </td>
                        <td className="px-4 py-3 font-medium text-gray-900">{s.excursion}</td>
                        <td className="px-4 py-3">{fecha(s.fecha_inicio)}</td>
                        <td className="px-4 py-3">{fecha(s.fecha_fin)}</td>
                        <td className="px-4 py-3">{s.hora_de_salida}</td>
                        <td className="px-4 py-3">{s.guia ?? '—'}</td>
                        <td className="px-4 py-3">{s.asistentes.join(', ') || '—'}</td>
                        <td className="px-4 py-3">{s.equipamiento.join(', ') || '—'}</td>
                        <td className="px-4 py-3">{s.cupo_maximo}</td>
                        <td className="px-4 py-3">{s.cupo_disponible}</td>
                        <td className="px-4 py-3">{precio(s.precio_base)}</td>
                        <td className="px-4 py-3">{s.cantidad_paquetes}</td>
                        <td className="px-4 py-3">
                            <span
                                className={
                                    'rounded-full px-2 py-1 text-xs font-semibold ' +
                                    (s.estado === 'PROGRAMADA'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-gray-200 text-gray-700')
                                }
                            >
                                {s.estado}
                            </span>
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}
