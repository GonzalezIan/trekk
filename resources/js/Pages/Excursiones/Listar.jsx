import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

// CU 4 - Listar excursiones
export default function Listar({ excursiones, esAdministrativo }) {
    const { flash } = usePage().props;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Excursiones
                    </h2>
                    {esAdministrativo && (
                        <Link
                            href={route('excursiones.alta')}
                            className="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
                        >
                            Nueva excursión
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Excursiones" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {flash.exito && (
                        <div className="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">
                            {flash.exito}
                        </div>
                    )}

                    <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                        {excursiones.length === 0 ? (
                            <p className="p-6 text-gray-600">
                                No hay excursiones registradas.
                            </p>
                        ) : (
                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50 text-left text-gray-600">
                                    <tr>
                                        <th className="px-4 py-3">Nombre</th>
                                        <th className="px-4 py-3">Recorrido</th>
                                        <th className="px-4 py-3">Dificultad</th>
                                        <th className="px-4 py-3">Duración</th>
                                        <th className="px-4 py-3">Estado</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {excursiones.map((e) => (
                                        <tr key={e.id}>
                                            <td className="px-4 py-3 font-medium text-gray-900">
                                                {e.nombre}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700">
                                                {e.recorrido}
                                            </td>
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
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
