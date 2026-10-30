import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

// CU 27 - Generar reporte periódico (RN-01 a RN-04)

const formatFecha = (fecha) =>
    new Date(`${fecha}T12:00:00`).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

const botonSecundario =
    'inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100';

export default function Periodico({ reporte = null, error = null }) {
    const form = useForm({
        fecha_inicio: reporte?.periodo.desde ?? '',
        fecha_fin: reporte?.periodo.hasta ?? '',
    });

    // Pasos 3 y 4: el servidor valida el período (3.1 y 3.2) y genera los reportes
    const generar = (e) => {
        e.preventDefault();
        form.get(route('admin.reportes.generar'), { preserveState: true, preserveScroll: true });
    };

    // Paso 10: exportar el reporte generado
    const urlExportar = reporte
        ? route('admin.reportes.exportar', {
              fecha_inicio: reporte.periodo.desde,
              fecha_fin: reporte.periodo.hasta,
          })
        : null;

    const mensajeError = form.errors.fecha_inicio || form.errors.fecha_fin || error;

    return (
        <AuthenticatedLayout>
            <Head title="Generar reporte periódico" />

            <main className="mx-auto max-w-5xl px-4 py-12">
                <div className="flex flex-col gap-2 pb-8">
                    <p className="text-xs font-semibold uppercase tracking-widest text-trekk-acento">Administración</p>
                    <h1 className="font-display text-3xl font-semibold text-trekk-texto md:text-4xl">Generar reporte periódico</h1>
                    <p className="max-w-2xl text-gray-500">
                        Clientes frecuentes, actividades frecuentes y conformidad, sobre las reservas realizadas en el
                        período.
                    </p>
                </div>

                <form
                    onSubmit={generar}
                    noValidate
                    className="flex flex-wrap items-end gap-3 rounded-2xl border border-gray-200 bg-trekk-tarjeta p-4 print:hidden"
                >
                    <label className="flex flex-col gap-1.5 text-sm font-medium text-gray-900">
                        Fecha de inicio
                        <input
                            type="date"
                            value={form.data.fecha_inicio}
                            onChange={(e) => form.setData('fecha_inicio', e.target.value)}
                            className="w-44 rounded-lg border-gray-200 text-sm focus:border-trekk-primario focus:ring-trekk-primario"
                        />
                    </label>
                    <label className="flex flex-col gap-1.5 text-sm font-medium text-gray-900">
                        Fecha de fin
                        <input
                            type="date"
                            value={form.data.fecha_fin}
                            onChange={(e) => form.setData('fecha_fin', e.target.value)}
                            className="w-44 rounded-lg border-gray-200 text-sm focus:border-trekk-primario focus:ring-trekk-primario"
                        />
                    </label>
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="inline-flex items-center rounded-lg bg-trekk-primario px-4 py-2 text-sm font-medium text-trekk-primario-texto hover:bg-trekk-primario-hover disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {form.processing ? 'Generando…' : 'Generar reporte'}
                    </button>
                    {reporte && (
                        <>
                            <button type="button" onClick={() => window.print()} className={botonSecundario}>
                                Imprimir
                            </button>
                            <a href={urlExportar} className={botonSecundario}>
                                Exportar CSV
                            </a>
                        </>
                    )}
                </form>

                {mensajeError && (
                    <p
                        role="alert"
                        className="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 print:hidden"
                    >
                        {mensajeError}
                    </p>
                )}

                {reporte && !mensajeError && (
                    <div className="mt-6 flex flex-col gap-6">
                        <p className="text-sm text-gray-500">
                            Período: <strong className="text-gray-900">{formatFecha(reporte.periodo.desde)}</strong> al{' '}
                            <strong className="text-gray-900">{formatFecha(reporte.periodo.hasta)}</strong> · reservas en
                            estado “Realizada”
                        </p>

                        {/* Paso 6 - RN-02 */}
                        <section className="flex flex-col gap-3">
                            <h2 className="font-display text-xl font-semibold text-trekk-texto">Clientes frecuentes</h2>
                            <Tabla encabezados={['Nombre', 'Apellido', 'DNI', 'Reservas realizadas']}>
                                {reporte.clientes_frecuentes.map((c) => (
                                    <tr key={c.dni}>
                                        <td className="font-medium">{c.nombre}</td>
                                        <td>{c.apellido}</td>
                                        <td className="font-mono text-xs">{c.dni}</td>
                                        <td className="tabular-nums">{c.reservas}</td>
                                    </tr>
                                ))}
                            </Tabla>
                        </section>

                        {/* Paso 7 - RN-03 */}
                        <section className="flex flex-col gap-3">
                            <h2 className="font-display text-xl font-semibold text-trekk-texto">Actividades frecuentes</h2>
                            <Tabla encabezados={['Excursión', 'Participantes']}>
                                {reporte.actividades_frecuentes.map((a) => (
                                    <tr key={a.excursion}>
                                        <td className="font-medium">{a.excursion}</td>
                                        <td className="tabular-nums">{a.participantes}</td>
                                    </tr>
                                ))}
                            </Tabla>
                        </section>

                        {/* Paso 8 - RN-04 */}
                        <section className="flex flex-col gap-3">
                            <h2 className="font-display text-xl font-semibold text-trekk-texto">Conformidad</h2>
                            {reporte.conformidad.length === 0 ? (
                                <p className="rounded-xl border border-dashed border-gray-300 p-6 text-center text-gray-500">
                                    No hay valoraciones registradas en el período.
                                </p>
                            ) : (
                                <Tabla encabezados={['Excursión', 'Valoración promedio', 'Valoraciones', 'Comentarios']}>
                                    {reporte.conformidad.map((c) => (
                                        <tr key={c.excursion}>
                                            <td className="font-medium">{c.excursion}</td>
                                            <td className="whitespace-nowrap tabular-nums">
                                                <span className="inline-flex items-center gap-1">
                                                    <IconoEstrella /> {Number(c.valoracion_promedio).toFixed(1)} / 5
                                                </span>
                                            </td>
                                            <td className="tabular-nums">{c.valoraciones}</td>
                                            <td className="text-gray-500">
                                                {c.comentarios.length ? (
                                                    <ul className="flex flex-col gap-1">
                                                        {c.comentarios.map((comentario, i) => (
                                                            <li key={i}>
                                                                “{comentario.comentario}”{' '}
                                                                <span className="text-xs text-gray-400">— {comentario.cliente}</span>
                                                            </li>
                                                        ))}
                                                    </ul>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </Tabla>
                            )}
                        </section>
                    </div>
                )}
            </main>
        </AuthenticatedLayout>
    );
}

function Tabla({ encabezados, children }) {
    return (
        <div className="overflow-x-auto rounded-2xl border border-gray-200 bg-trekk-tarjeta">
            <table className="w-full text-sm">
                <thead className="border-b border-gray-200 text-left text-gray-500">
                    <tr>
                        {encabezados.map((h) => (
                            <th key={h} scope="col" className="px-4 py-3 font-medium">
                                {h}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 [&_td]:px-4 [&_td]:py-3">{children}</tbody>
            </table>
        </div>
    );
}

function IconoEstrella() {
    return (
        <svg aria-hidden viewBox="0 0 24 24" className="size-3.5 fill-orange-500 text-trekk-acento">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
        </svg>
    );
}
