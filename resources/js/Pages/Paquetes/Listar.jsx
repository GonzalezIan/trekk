import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Cancelar from '@/Components/Cancelar';

// "2026-10-27" -> "27/10/2026"
const fecha = (valor) => valor.split('-').reverse().join('/');
const precio = (valor) =>
    Number(valor).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

// CU 7 - Listar paquetes
export default function Listar({ paquetes, salida, esAdministrativo }) {
    const { flash } = usePage().props;
    const [paqueteACancelar, setPaqueteACancelar] = useState(null);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Paquetes de {salida.excursion}
                        </h2>
                        <p className="text-sm text-gray-600">
                            Salida del {fecha(salida.fecha_inicio)} al {fecha(salida.fecha_fin)}
                        </p>
                    </div>
                    <div className="flex items-center gap-4">
                        {/* CU 5 - Registrar paquete (RN-01: solo sobre salidas PROGRAMADAS) */}
                        {esAdministrativo && salida.estado === 'PROGRAMADA' && (
                            <Link
                                href={route('paquetes.registrar', salida.id)}
                                className="rounded-lg bg-trekk-primario px-4 py-2 text-sm font-medium text-trekk-primario-texto hover:bg-trekk-primario-hover"
                            >
                                Registrar paquete
                            </Link>
                        )}
                        <Link
                            href={
                                esAdministrativo
                                    ? route('salidas.listar')
                                    : route('salidas.listar', { excursion: salida.excursion_id })
                            }
                            className="text-sm text-gray-600 underline hover:text-gray-900"
                        >
                            Volver a salidas
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Paquetes" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    {/* CU 5 - paso 6: se informa que el paquete fue registrado y su precio */}
                    {flash.exito && (
                        <div className="rounded-md bg-green-50 p-6 mb-4 shadow-sm border border-green-200">
                            <h3 className="text-2xl font-bold text-green-800 mb-1">
                                {flash.exito}
                            </h3>
                            {flash.subtexto_exito && (
                                <p className="text-green-700 text-sm">
                                    {flash.subtexto_exito}
                                </p>
                            )}
                        </div>
                    )}

                    <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                        {paquetes.length === 0 ? (
                            <p className="p-6 text-gray-600">
                                {esAdministrativo
                                    ? 'La Salida Programada no tiene paquetes registrados.'
                                    : 'No hay paquetes disponibles para la salida seleccionada.'}
                            </p>
                        ) : esAdministrativo ? (
                            <TablaAdministrativo paquetes={paquetes} onCancelar={setPaqueteACancelar} />
                        ) : (
                            <TablaCliente paquetes={paquetes} />
                        )}
                    </div>
                </div>
            </div>
            {paqueteACancelar && (
                <Cancelar
                    paquete={paqueteACancelar}
                    onCerrar={() => setPaqueteACancelar(null)}
                />
            )}
        </AuthenticatedLayout>
    );
}

// Lo que ve el Cliente: solo paquetes DISPONIBLE, con precio y disponibilidad
function TablaCliente({ paquetes }) {
    return (
        <table className="min-w-full divide-y divide-gray-200 text-sm">
            <thead className="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th className="px-4 py-3">Paquete</th>
                    <th className="px-4 py-3">Refugio</th>
                    <th className="px-4 py-3">Servicios incluidos</th>
                    <th className="px-4 py-3">Precio</th>
                    <th className="px-4 py-3">Lugares disponibles</th>
                    <th className="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
                {paquetes.map((p) => (
                    <tr key={p.id}>
                        <td className="px-4 py-3">
                            <div className="font-medium text-gray-900">{p.nombre}</div>
                            {p.descripcion && <div className="text-gray-600">{p.descripcion}</div>}
                        </td>
                        <td className="px-4 py-3">{p.refugio ?? '—'}</td>
                        <td className="px-4 py-3">{p.servicios.join(', ') || '—'}</td>
                        <td className="px-4 py-3">{precio(p.precio)}</td>
                        <td className="px-4 py-3">
                            {p.lugares_disponibles}
                            {p.camas_disponibles !== null && (
                                <div className="text-xs text-gray-500">
                                    Cupo {p.cupo_disponible} · Camas {p.camas_disponibles}
                                </div>
                            )}
                        </td>
                        <td className="px-4 py-3 text-right">
                            {/* CU 14 - Registrar reserva para el paquete seleccionado */}
                            {p.lugares_disponibles > 0 ? (
                                <Link
                                    href={route('reservas.registrar', p.id)}
                                    className="inline-flex items-center rounded-lg bg-emerald-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-800/80"
                                >
                                    Reservar
                                </Link>
                            ) : (
                                <span className="text-xs text-gray-500">Sin lugares</span>
                            )}
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

// Lo que ve el Administrativo: todos los paquetes con la cantidad de reservas activas
function TablaAdministrativo({ paquetes, onCancelar }) {
    return (
        <table className="min-w-full divide-y divide-gray-200 text-sm">
            <thead className="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th className="px-4 py-3">Paquete</th>
                    <th className="px-4 py-3">Refugio</th>
                    <th className="px-4 py-3">Servicios incluidos</th>
                    <th className="px-4 py-3">Precio</th>
                    <th className="px-4 py-3">Reservas activas</th>
                    <th className="px-4 py-3">Estado</th>
                    <th className="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
                {paquetes.map((p) => (
                    <tr key={p.id}>
                        <td className="px-4 py-3">
                            <div className="font-medium text-gray-900">{p.nombre}</div>
                            {p.descripcion && <div className="text-gray-600">{p.descripcion}</div>}
                        </td>
                        <td className="px-4 py-3">{p.refugio ?? '—'}</td>
                        <td className="px-4 py-3">{p.servicios.join(', ') || '—'}</td>
                        <td className="px-4 py-3">{precio(p.precio)}</td>
                        <td className="px-4 py-3">{p.reservas_activas}</td>
                        <td className="px-4 py-3">
                            <span
                                className={
                                    'rounded-full px-2 py-1 text-xs font-semibold ' +
                                    (p.estado === 'DISPONIBLE'
                                        ? 'bg-green-100 text-green-800'
                                        : p.estado === 'CANCELADO' 
                                        ? 'bg-red-100 text-red-800'
                                        : 'bg-gray-200 text-gray-700')
                                }
                            >
                                {p.estado}
                            </span>
                        </td>
                        <td className="px-4 py-3 text-right">
                            {p.estado === 'DISPONIBLE' && (
                                <button
                                    onClick={() => onCancelar(p)}
                                    className="text-sm font-medium text-red-600 hover:text-red-900 hover:underline"
                                >
                                    Cancelar
                                </button>
                            )}
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}