import Modal from '@/Components/Modal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const botonOscuro =
    'rounded-lg bg-trekk-primario px-4 py-2 text-sm font-medium text-trekk-primario-texto hover:bg-trekk-primario-hover disabled:cursor-not-allowed disabled:opacity-50';
const botonClaro =
    'rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave disabled:cursor-not-allowed disabled:opacity-50';

// CU 4 - Listar excursiones
export default function Listar({ excursiones, esAdministrativo }) {
    const { flash } = usePage().props;

    // Excursión elegida por el Administrativo para operar sobre ella
    const [seleccionada, setSeleccionada] = useState(null);
    const excursionSeleccionada = excursiones.find((e) => e.id === seleccionada);

    // Alternativo 4.3: confirmar la baja de la excursión seleccionada (CU 2)
    const [confirmarBaja, setConfirmarBaja] = useState(false);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Excursiones
                    </h2>
                    {esAdministrativo && (
                        <Link href={route('excursiones.alta')} className={botonOscuro}>
                            Nueva excursión
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Excursiones" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
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

                    {esAdministrativo && excursiones.length > 0 && (
                        <BarraAcciones
                            excursion={excursionSeleccionada}
                            onDarDeBaja={() => setConfirmarBaja(true)}
                        />
                    )}

                    <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                        {excursiones.length === 0 ? (
                            <p className="p-6 text-gray-600">No hay excursiones registradas.</p>
                        ) : (
                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50 text-left text-gray-600">
                                    <tr>
                                        {esAdministrativo && <th className="w-8 px-4 py-3"></th>}
                                        <th className="px-4 py-3">Nombre</th>
                                        <th className="px-4 py-3">Recorrido</th>
                                        <th className="px-4 py-3">Dificultad</th>
                                        <th className="px-4 py-3">Duración</th>
                                        <th className="px-4 py-3">Estado</th>
                                        {!esAdministrativo && <th className="px-4 py-3"></th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {excursiones.map((e) => (
                                        <tr
                                            key={e.id}
                                            onClick={esAdministrativo ? () => setSeleccionada(e.id) : undefined}
                                            className={
                                                esAdministrativo
                                                    ? 'cursor-pointer ' +
                                                      (e.id === seleccionada ? 'bg-indigo-50' : 'hover:bg-gray-50')
                                                    : ''
                                            }
                                        >
                                            {esAdministrativo && (
                                                <td className="px-4 py-3">
                                                    <input
                                                        type="radio"
                                                        name="excursion"
                                                        checked={e.id === seleccionada}
                                                        onChange={() => setSeleccionada(e.id)}
                                                        aria-label={`Seleccionar la excursión ${e.nombre}`}
                                                        className="text-indigo-600 focus:ring-indigo-500"
                                                    />
                                                </td>
                                            )}
                                            <td className="px-4 py-3 font-medium text-gray-900">
                                                {e.nombre}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700">{e.recorrido}</td>
                                            <td className="px-4 py-3">{e.dificultad}</td>
                                            <td className="px-4 py-3">
                                                {e.duracion} {e.duracion === 1 ? 'día' : 'días'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span
                                                    className={
                                                        'rounded-full px-2 py-1 text-xs font-semibold ' +
                                                        (e.estado === 'ACTIVA'
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-gray-200 text-gray-700')
                                                    }
                                                >
                                                    {e.estado}
                                                </span>
                                            </td>
                                            {!esAdministrativo && (
                                                // CU 4 - alternativo 4.1: el Cliente elige la excursión -> CU 9
                                                <td className="px-4 py-3 text-right">
                                                    <Link
                                                        href={route('salidas.listar', { excursion: e.id })}
                                                        className="text-indigo-600 hover:underline"
                                                    >
                                                        Ver salidas
                                                    </Link>
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>
            </div>

            {confirmarBaja && excursionSeleccionada && (
                <BajaExcursionModal
                    excursion={excursionSeleccionada}
                    onCerrar={() => setConfirmarBaja(false)}
                />
            )}
        </AuthenticatedLayout>
    );
}

// Operaciones que habilita la excursión seleccionada por el Administrativo
function BarraAcciones({ excursion, onDarDeBaja }) {
    if (!excursion) {
        return (
            <div className="bg-white px-4 py-3 text-sm text-gray-600 shadow-sm sm:rounded-lg">
                Seleccioná una excursión para modificarla, darla de baja o programar una salida.
            </div>
        );
    }

    // CU 3 (RN-03) y CU 2: solo se opera sobre excursiones activas
    const activa = excursion.estado === 'ACTIVA';

    return (
        <div className="flex flex-wrap items-center gap-3 bg-white px-4 py-3 text-sm shadow-sm sm:rounded-lg">
            <span className="text-gray-600">Seleccionada:</span>
            <span className="font-medium text-gray-900">{excursion.nombre}</span>
            {/* Alternativo 4.2 -> CU 3 - Modificar excursión */}
            <button
                type="button"
                disabled={!activa}
                onClick={() => router.get(route('excursiones.modificar', excursion.id))}
                className={botonOscuro}
            >
                Modificar
            </button>
            {/* CU 8 - Registrar Salida Programada (RN-03: solo sobre excursiones activas) */}
            <button
                type="button"
                disabled={!activa}
                onClick={() => router.get(route('salidas.registrar', excursion.id))}
                className={botonOscuro}
            >
                Programar salida
            </button>
            {/* Alternativo 4.3 -> CU 2 - Baja de excursión */}
            <button type="button" disabled={!activa} onClick={onDarDeBaja} className={botonClaro}>
                Dar de baja
            </button>
            {!activa && (
                <span className="text-xs text-gray-500">
                    Solo pueden modificarse, darse de baja o programarse salidas de excursiones activas.
                </span>
            )}
        </div>
    );
}

// CU 2 - Baja de excursión: el Administrativo confirma antes de dar de baja
function BajaExcursionModal({ excursion, onCerrar }) {
    const [enviando, setEnviando] = useState(false);

    const confirmar = () => {
        setEnviando(true);
        router.patch(route('excursiones.baja', excursion.id), {}, { onFinish: onCerrar });
    };

    return (
        <Modal show onClose={onCerrar} maxWidth="lg">
            <div className="border-b border-gray-200 px-5 py-4">
                <h2 className="text-xl font-semibold text-gray-900">Dar de baja la excursión</h2>
                <p className="text-sm text-gray-500">{excursion.nombre}</p>
            </div>

            <p className="px-5 py-4 text-sm text-gray-700">
                La excursión pasará a estado <strong>“Inactiva”</strong> y se cancelarán sus salidas en
                estado “Programada” junto con sus paquetes. Esta operación no se puede deshacer.
            </p>

            <div className="flex justify-end gap-2 border-t border-gray-200 px-5 py-4">
                <button
                    type="button"
                    onClick={onCerrar}
                    className="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
                >
                    Volver
                </button>
                <button
                    type="button"
                    disabled={enviando}
                    onClick={confirmar}
                    className="rounded-lg bg-red-600/10 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-600/20 disabled:opacity-50"
                >
                    {enviando ? 'Dando de baja…' : 'Confirmar baja'}
                </button>
            </div>
        </Modal>
    );
}