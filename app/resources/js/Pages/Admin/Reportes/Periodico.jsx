import { Head, Link, useForm } from '@inertiajs/react';
import Boton from '@/Components/Boton';
import Campo from '@/Components/Campo';
import Etiqueta from '@/Components/Etiqueta';
import ErrorCampo from '@/Components/ErrorCampo';
import PageHeader from '@/Components/PageHeader';
import Tabla from '@/Components/Tabla';
import LayoutAdministrativo from '@/Layouts/LayoutAdministrativo';

/**
 * CU 27 - Generar reporte periódico (RN-01 a RN-04).
 */
export default function Periodico({ reporte = null, periodo = null }) {
    const form = useForm({
        fecha_inicio: periodo?.desde ?? '',
        fecha_fin: periodo?.hasta ?? '',
    });

    const generar = (e) => {
        e.preventDefault();
        form.get(route('admin.reportes.generar'), { preserveScroll: true });
    };

    const exportar = () => {
        const parametros = new URLSearchParams({
            fecha_inicio: form.fecha_inicio,
            fecha_fin: form.fecha_fin,
        });

        window.location.href = `${route('admin.reportes.exportar')}?${parametros.toString()}`;
    };

    return (
        <LayoutAdministrativo>
            <Head title="Reporte periódico | Trekk" />

            <PageHeader
                eyebrow="Reportes"
                titulo="Reporte periódico"
                descripcion="Clientes frecuentes, actividades frecuentes y conformidad."
            />

            <form
                onSubmit={generar}
                className="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border bg-card p-4"
            >
                <div className="flex flex-col gap-2">
                    <Etiqueta htmlFor="fecha_inicio">Fecha de inicio</Etiqueta>
                    <Campo
                        id="fecha_inicio"
                        type="date"
                        value={form.fecha_inicio}
                        onChange={(e) => form.setData('fecha_inicio', e.target.value)}
                    />
                    <ErrorCampo message={form.errors.fecha_inicio} className="mt-1" />
                </div>

                <div className="flex flex-col gap-2">
                    <Etiqueta htmlFor="fecha_fin">Fecha de fin</Etiqueta>
                    <Campo
                        id="fecha_fin"
                        type="date"
                        value={form.fecha_fin}
                        onChange={(e) => form.setData('fecha_fin', e.target.value)}
                    />
                    <ErrorCampo message={form.errors.fecha_fin} className="mt-1" />
                </div>

                <div className="flex gap-2">
                    <Boton type="submit" disabled={form.processing}>
                        Generar reporte
                    </Boton>
                    {reporte && (
                        <Boton variant="outline" onClick={exportar}>
                            Exportar CSV
                        </Boton>
                    )}
                </div>
            </form>

            {reporte && (
                <div className="flex flex-col gap-8">
                    <section>
                        <h2 className="mb-2 font-display text-xl font-semibold">
                            Reporte de clientes frecuentes
                        </h2>
                        <Tabla headers={['Nombre', 'Apellido', 'DNI', 'Excursiones realizadas']}>
                            {reporte.clientes_frecuentes.map((cliente) => (
                                <tr key={cliente.dni}>
                                    <td>{cliente.nombre}</td>
                                    <td>{cliente.apellido}</td>
                                    <td className="tabular-nums">{cliente.dni}</td>
                                    <td className="tabular-nums">
                                        {cliente.excursiones}
                                    </td>
                                </tr>
                            ))}

                            {reporte.clientes_frecuentes.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No se encontraron datos para el período
                                        seleccionado.
                                    </td>
                                </tr>
                            )}
                        </Tabla>
                    </section>

                    <section>
                        <h2 className="mb-2 font-display text-xl font-semibold">
                            Reporte de actividades frecuentes
                        </h2>
                        <Tabla headers={['Excursión', 'Participantes']}>
                            {reporte.actividades_frecuentes.map((actividad) => (
                                <tr key={actividad.excursion}>
                                    <td>{actividad.excursion}</td>
                                    <td className="tabular-nums">
                                        {actividad.participantes}
                                    </td>
                                </tr>
                            ))}

                            {reporte.actividades_frecuentes.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={2}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No se encontraron datos para el período
                                        seleccionado.
                                    </td>
                                </tr>
                            )}
                        </Tabla>
                    </section>

                    <section>
                        <h2 className="mb-2 font-display text-xl font-semibold">
                            Reporte de conformidad
                        </h2>
                        <Tabla
                            headers={[
                                'Excursión',
                                'Valoración promedio',
                                'Cantidad de valoraciones',
                                'Comentarios',
                            ]}
                        >
                            {reporte.conformidad.map((conformidad) => (
                                <tr key={conformidad.excursion}>
                                    <td>{conformidad.excursion}</td>
                                    <td className="tabular-nums">
                                        {conformidad.valoracion_promedio}
                                    </td>
                                    <td className="tabular-nums">
                                        {conformidad.valoraciones}
                                    </td>
                                    <td className="text-muted-foreground">
                                        {(conformidad.comentarios ?? [])
                                            .map(
                                                (comentario) =>
                                                    `${comentario.cliente}: ${comentario.comentario}`,
                                            )
                                            .join(' | ') || '—'}
                                    </td>
                                </tr>
                            ))}

                            {reporte.conformidad.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No se encontraron datos para el período
                                        seleccionado.
                                    </td>
                                </tr>
                            )}
                        </Tabla>
                    </section>
                </div>
            )}
        </LayoutAdministrativo>
    );
}
