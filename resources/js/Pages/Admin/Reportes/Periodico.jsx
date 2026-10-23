import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, useForm } from '@inertiajs/react';

/**
 * CU 27 - Generar reporte periódico (RN-01 a RN-04).
 */
export default function Periodico({ reporte = null, periodo = null, error = null }) {
    const form = useForm({
        fecha_inicio: periodo?.desde ?? '',
        fecha_fin: periodo?.hasta ?? '',
    });


    const generar = (e) => {
        e.preventDefault();
        form.get(route('admin.reportes.generar'), { preserveScroll: true });
    };

    const urlExportar = `${route('admin.reportes.exportar')}?${new URLSearchParams({
        fecha_inicio: form.data.fecha_inicio,
        fecha_fin: form.data.fecha_fin,
    }).toString()}`;
    const inputClase =
        'rounded-lg border-gray-200 text-sm md:w-48';

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Reporte periódico
                </h2>
            }
        >
            <Head title="Reporte periódico | Trekk" />

            <main className="mx-auto max-w-5xl px-4 py-12">
                <div className="flex flex-col gap-2 pb-8">
                    <p className="text-xs font-semibold uppercase tracking-widest text-orange-600">
                        Administración
                    </p>
                    <h1 className="text-3xl font-semibold text-gray-900 md:text-4xl">
                        Reporte periódico
                    </h1>
                    <p className="max-w-2xl text-gray-500">
                        Clientes frecuentes, actividades frecuentes y conformidad.
                    </p>
                </div>

                <form
                    onSubmit={generar}
                    className="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-gray-200 bg-gray-50 p-4"
                >
                    <div className="flex flex-col gap-1">
                        <label
                            htmlFor="fecha_inicio"
                            className="text-sm font-medium text-gray-700"
                        >
                            Fecha de inicio
                        </label>
                        <input
                            id="fecha_inicio"
                            type="date"
                            value={form.data.fecha_inicio}
                            onChange={(e) =>
                                form.setData('fecha_inicio', e.target.value)
                            }
                            className={inputClase}
                        />
                        {form.errors.fecha_inicio && (
                            <span className="text-xs text-red-600">
                                {form.errors.fecha_inicio}
                            </span>
                        )}
                    </div>

                    <div className="flex flex-col gap-1">
                        <label
                            htmlFor="fecha_fin"
                            className="text-sm font-medium text-gray-700"
                        >
                            Fecha de fin
                        </label>
                        <input
                            id="fecha_fin"
                            type="date"
                            value={form.data.fecha_fin}
                            onChange={(e) =>
                                form.setData('fecha_fin', e.target.value)
                            }
                            className={inputClase}
                        />
                        {form.errors.fecha_fin && (
                            <span className="text-xs text-red-600">
                                {form.errors.fecha_fin}
                            </span>
                        )}
                    </div>

                    <div className="flex gap-2">
                        <PrimaryButton type="submit" disabled={form.processing}>
                            Generar reporte
                        </PrimaryButton>
                        {reporte && (
                            <a
                                href={urlExportar}
                                className="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-gray-900"
                            >
                                Exportar CSV
                            </a>
                        )}
                    </div>
                </form>

                {error && (
                    <div
                        role="alert"
                        className="mb-6 rounded-xl border border-red-300/50 bg-red-50 px-4 py-3 text-sm text-red-900"
                    >
                        {error}
                    </div>
                )}

                {reporte && (
                    <div className="flex flex-col gap-8">
                        <section>
                            <h2 className="mb-2 text-xl font-semibold text-gray-900">
                                Reporte de clientes frecuentes
                            </h2>
                            <div className="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
                                <table className="w-full text-sm">
                                    <thead className="border-b border-gray-200 text-left text-gray-500">
                                        <tr>
                                            {[
                                                'Nombre',
                                                'Apellido',
                                                'DNI',
                                                'Excursiones realizadas',
                                            ].map((h) => (
                                                <th
                                                    key={h}
                                                    scope="col"
                                                    className="px-4 py-3 font-medium"
                                                >
                                                    {h}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 [&_td]:px-4 [&_td]:py-3">
                                        {reporte.clientes_frecuentes.map(
                                            (cliente) => (
                                                <tr key={cliente.dni}>
                                                    <td>{cliente.nombre}</td>
                                                    <td>{cliente.apellido}</td>
                                                    <td className="tabular-nums">
                                                        {cliente.dni}
                                                    </td>
                                                    <td className="tabular-nums">
                                                        {cliente.excursiones}
                                                    </td>
                                                </tr>
                                            ),
                                        )}

                                        {reporte.clientes_frecuentes.length ===
                                            0 && (
                                            <tr>
                                                <td
                                                    colSpan={4}
                                                    className="py-6 text-center text-gray-500"
                                                >
                                                    No se encontraron datos para
                                                    el período seleccionado.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section>
                            <h2 className="mb-2 text-xl font-semibold text-gray-900">
                                Reporte de actividades frecuentes
                            </h2>
                            <div className="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
                                <table className="w-full text-sm">
                                    <thead className="border-b border-gray-200 text-left text-gray-500">
                                        <tr>
                                            {['Excursión', 'Participantes'].map(
                                                (h) => (
                                                    <th
                                                        key={h}
                                                        scope="col"
                                                        className="px-4 py-3 font-medium"
                                                    >
                                                        {h}
                                                    </th>
                                                ),
                                            )}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 [&_td]:px-4 [&_td]:py-3">
                                        {reporte.actividades_frecuentes.map(
                                            (actividad) => (
                                                <tr key={actividad.excursion}>
                                                    <td>{actividad.excursion}</td>
                                                    <td className="tabular-nums">
                                                        {actividad.participantes}
                                                    </td>
                                                </tr>
                                            ),
                                        )}

                                        {reporte.actividades_frecuentes
                                            .length === 0 && (
                                            <tr>
                                                <td
                                                    colSpan={2}
                                                    className="py-6 text-center text-gray-500"
                                                >
                                                    No se encontraron datos para
                                                    el período seleccionado.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section>
                            <h2 className="mb-2 text-xl font-semibold text-gray-900">
                                Reporte de conformidad
                            </h2>
                            <div className="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
                                <table className="w-full text-sm">
                                    <thead className="border-b border-gray-200 text-left text-gray-500">
                                        <tr>
                                            {[
                                                'Excursión',
                                                'Valoración promedio',
                                                'Cantidad de valoraciones',
                                                'Comentarios',
                                            ].map((h) => (
                                                <th
                                                    key={h}
                                                    scope="col"
                                                    className="px-4 py-3 font-medium"
                                                >
                                                    {h}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 [&_td]:px-4 [&_td]:py-3">
                                        {reporte.conformidad.map(
                                            (conformidad) => (
                                                <tr
                                                    key={conformidad.excursion}
                                                >
                                                    <td>
                                                        {conformidad.excursion}
                                                    </td>
                                                    <td className="tabular-nums">
                                                        {
                                                            conformidad.valoracion_promedio
                                                        }
                                                    </td>
                                                    <td className="tabular-nums">
                                                        {
                                                            conformidad.valoraciones
                                                        }
                                                    </td>
                                                    <td className="text-gray-500">
                                                        {(conformidad.comentarios ??
                                                            [])
                                                            .map(
                                                                (comentario) =>
                                                                    `${comentario.cliente}: ${comentario.comentario}`,
                                                            )
                                                            .join(' | ') ||
                                                            '—'}
                                                    </td>
                                                </tr>
                                            ),
                                        )}

                                        {reporte.conformidad.length === 0 && (
                                            <tr>
                                                <td
                                                    colSpan={4}
                                                    className="py-6 text-center text-gray-500"
                                                >
                                                    No se encontraron datos para
                                                    el período seleccionado.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                )}
            </main>
        </AuthenticatedLayout>
    );
}