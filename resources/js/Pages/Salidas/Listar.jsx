import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
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
     // Salida elegida por el Administrativo para operar sobre ella
    const [seleccionada, setSeleccionada] = useState(null);
    const salidaSeleccionada = salidas.find((s) => s.id === seleccionada);
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
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
                    {esAdministrativo && (
                        <Filtros
                            filtros={filtros}
                            excursiones={excursiones}
                            estados={estados}
                        />
                    )}

                    {esAdministrativo && salidas.length > 0 && (
                        <BarraAcciones salida={salidaSeleccionada} />
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
                    className="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
                >
                    Filtrar
                </button>
                <button
                    type="button"
                    onClick={limpiar}
                    className="rounded-md border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50"
                >
                    Limpiar
                </button>
            </div>
        </form>
    );
}

// Operaciones que habilita la salida seleccionada por el Administrativo
function BarraAcciones({ salida }) {
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
                        className="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
                    >
                        Listar paquetes
                    </Link>
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
