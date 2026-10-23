import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

// "2026-10-27" -> "27/10/2026"
const fecha = (valor) => valor.split('-').reverse().join('/');
const precio = (valor) =>
    Number(valor).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
const esFecha = (valor) => /^\d{4}-\d{2}-\d{2}$/.test(valor);

// CU 10 - Modificar Salida Programada
export default function Modificar({ salida }) {
    // Paso 2: se muestran los datos actuales de la salida
    const { data, setData, put, processing, errors } = useForm({
        fecha_inicio: salida.fecha_inicio,
        fecha_fin: salida.fecha_fin,
        hora_de_salida: salida.hora_de_salida,
        cupo_maximo: salida.cupo_maximo,
        precio_base: salida.precio_base,
        id_guia: salida.id_guia,
        ids_asistentes: salida.ids_asistentes,
        ids_equipamiento: salida.ids_equipamiento,
    });

    // Personal y equipamiento disponibles en el período ingresado (sin contar esta salida).
    // Se vuelven a consultar cada vez que cambia el período.
    const periodo =
        esFecha(data.fecha_inicio) && esFecha(data.fecha_fin)
            ? { fecha_inicio: data.fecha_inicio, fecha_fin: data.fecha_fin }
            : null;
    const guias = useListado(
        periodo && route('salidas.modificar.empleados', { salida: salida.id, categoria: 'GUIA', ...periodo }),
        'empleados',
    );
    const asistentes = useListado(
        periodo && route('salidas.modificar.empleados', { salida: salida.id, categoria: 'ASISTENTE', ...periodo }),
        'empleados',
    );
    const equipamiento = useListado(
        periodo && route('salidas.modificar.equipamiento', { salida: salida.id, ...periodo }),
        'equipamientos',
    );

    const alternar = (campo, id) =>
        setData(
            campo,
            data[campo].includes(id) ? data[campo].filter((x) => x !== id) : [...data[campo], id],
        );

    // Paso 7 (RN-02): vista previa del cupo; el definitivo lo calcula el sistema al guardar
    const cupoFinal = Math.min(
        Number(data.cupo_maximo),
        ...(equipamiento.items ?? [])
            .filter((eq) => data.ids_equipamiento.includes(eq.id))
            .map((eq) => eq.stock_disponible),
    );

    // Pasos 11 a 13: se confirma la modificación. Si algo no es válido, el formulario
    // conserva lo ingresado y muestra el error (alternativos 4.1, 4.2, 5.1, 6.1 y 8.1).
    const confirmar = (e) => {
        e.preventDefault();
        put(route('salidas.actualizar', salida.id));
    };

    const campo = (nombre, etiqueta, props = {}) => (
        <div>
            <InputLabel htmlFor={nombre} value={etiqueta} />
            <TextInput
                id={nombre}
                name={nombre}
                className="mt-1 block w-full"
                value={data[nombre]}
                onChange={(e) => setData(nombre, e.target.value)}
                {...props}
            />
            <InputError message={errors[nombre]} className="mt-2" />
        </div>
    );

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Modificar salida de {salida.excursion}
                </h2>
            }
        >
            <Head title="Modificar salida" />

            <div className="py-12">
                <form onSubmit={confirmar} noValidate className="mx-auto max-w-3xl space-y-4 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap gap-x-6 gap-y-1 bg-white px-6 py-4 text-sm text-gray-600 shadow-sm sm:rounded-lg">
                        <span>
                            Excursión: <strong className="text-gray-900">{salida.excursion}</strong>
                        </span>
                        <span>
                            Duración: {salida.duracion} {salida.duracion === 1 ? 'día' : 'días'}
                        </span>
                        <span>Participantes en reservas activas: {salida.participantes_activos}</span>
                        <span>Paquetes: {salida.cantidad_paquetes}</span>
                    </div>

                    {Object.keys(errors).length > 0 && (
                        <div role="alert" className="rounded-md bg-red-50 p-4 text-sm text-red-700">
                            No se aplicó la modificación. Revisá los datos marcados en rojo.
                        </div>
                    )}

                    <div className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="grid gap-6 sm:grid-cols-3">
                            {campo('fecha_inicio', 'Fecha de inicio *', { type: 'date' })}
                            {campo('fecha_fin', 'Fecha de fin *', { type: 'date' })}
                            {campo('hora_de_salida', 'Hora de salida *', { type: 'time' })}
                        </div>
                        <div className="grid gap-6 sm:grid-cols-2">
                            {campo('cupo_maximo', 'Cupo máximo *', { type: 'number', min: '1' })}
                            {campo('precio_base', 'Precio base (ARS) *', { type: 'number', min: '0', step: '0.01' })}
                        </div>
                        <p className="text-xs text-gray-500">
                            El nuevo precio base recalcula el precio de los paquetes y rige solo para las
                            reservas posteriores a la modificación.
                        </p>
                    </div>

                    <Seccion titulo="Guía responsable *" error={errors.id_guia}>
                        {guias.items?.length === 0 ? (
                            <p className="text-sm text-gray-600">No hay guías disponibles para el período.</p>
                        ) : (
                            <ListaEmpleados
                                listado={guias}
                                tipo="radio"
                                seleccion={data.id_guia ? [data.id_guia] : []}
                                onElegir={(id) => setData('id_guia', id)}
                            />
                        )}
                    </Seccion>

                    <Seccion titulo="Asistentes (opcional)" error={errors.ids_asistentes}>
                        {asistentes.items?.length === 0 ? (
                            <p className="text-sm text-gray-600">No hay asistentes disponibles para el período.</p>
                        ) : (
                            <ListaEmpleados
                                listado={asistentes}
                                tipo="checkbox"
                                seleccion={data.ids_asistentes}
                                onElegir={(id) => alternar('ids_asistentes', id)}
                            />
                        )}
                    </Seccion>

                    <Seccion titulo="Equipamiento incluido (opcional)" error={errors.ids_equipamiento}>
                        {equipamiento.items?.length === 0 ? (
                            <p className="text-sm text-gray-600">
                                No hay equipamiento con stock disponible en el período.
                            </p>
                        ) : (
                            <ListaEquipamiento
                                listado={equipamiento}
                                seleccion={data.ids_equipamiento}
                                onElegir={(id) => alternar('ids_equipamiento', id)}
                            />
                        )}
                    </Seccion>

                    <div className="flex flex-wrap items-center justify-between gap-4 bg-white px-6 py-4 shadow-sm sm:rounded-lg">
                        <p className="text-sm text-gray-700">
                            Cupo máximo de la salida: <strong>{cupoFinal}</strong>
                            {cupoFinal < Number(data.cupo_maximo) && (
                                <span className="block text-xs text-gray-500">
                                    Limitado por el stock disponible del equipamiento incluido.
                                </span>
                            )}
                            {cupoFinal < salida.participantes_activos && (
                                <span className="block text-xs text-red-600">
                                    No puede ser menor a los {salida.participantes_activos} participantes ya
                                    reservados.
                                </span>
                            )}
                        </p>
                        <div className="flex items-center gap-4">
                            {/* Flujo alternativo: cancelar y conservar los valores originales */}
                            <Link
                                href={route('salidas.listar')}
                                className="text-sm text-gray-600 underline hover:text-gray-900"
                            >
                                Cancelar
                            </Link>
                            <PrimaryButton disabled={processing || !periodo || !data.id_guia}>
                                {processing ? 'Guardando…' : 'Guardar cambios'}
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}

// Consulta un listado JSON cada vez que cambia la URL (o sea, el período). Sin URL no consulta.
function useListado(url, clave) {
    const [estado, setEstado] = useState({ items: null, error: '' });

    useEffect(() => {
        if (!url) return;
        let vigente = true;
        setEstado({ items: null, error: '' });
        window.axios
            .get(url)
            .then((respuesta) => vigente && setEstado({ items: respuesta.data[clave], error: '' }))
            .catch((e) =>
                vigente &&
                setEstado({ items: null, error: e.response?.data?.message || 'No se pudo cargar el listado.' }),
            );
        // Si el período cambia antes de que llegue la respuesta, se descarta la respuesta vieja
        return () => {
            vigente = false;
        };
    }, [url, clave]);

    return url ? estado : { items: null, error: 'Ingresá un período válido para ver los disponibles.' };
}

function Seccion({ titulo, error, children }) {
    return (
        <div className="space-y-3 bg-white p-6 shadow-sm sm:rounded-lg">
            <h3 className="font-semibold text-gray-900">{titulo}</h3>
            {children}
            <InputError message={error} />
        </div>
    );
}

function Cargando({ listado }) {
    return listado.error ? (
        <p className="text-sm text-red-700">{listado.error}</p>
    ) : (
        <p className="text-sm text-gray-500">Cargando…</p>
    );
}

// CU 23: empleados disponibles en el período
function ListaEmpleados({ listado, tipo, seleccion, onElegir }) {
    if (!listado.items) return <Cargando listado={listado} />;

    return (
        <div className="max-h-72 overflow-y-auto">
            <table className="min-w-full divide-y divide-gray-200 text-sm">
                <thead className="sticky top-0 bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th className="w-8 px-3 py-2"></th>
                        <th className="px-3 py-2">Nombre</th>
                        <th className="px-3 py-2">Apellido</th>
                        <th className="px-3 py-2">DNI</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                    {listado.items.map((emp) => (
                        <tr
                            key={emp.id}
                            onClick={() => onElegir(emp.id)}
                            className={
                                'cursor-pointer ' +
                                (seleccion.includes(emp.id) ? 'bg-indigo-50' : 'hover:bg-gray-50')
                            }
                        >
                            <td className="px-3 py-2">
                                <input
                                    type={tipo}
                                    name={tipo === 'radio' ? 'guia' : undefined}
                                    checked={seleccion.includes(emp.id)}
                                    onChange={() => onElegir(emp.id)}
                                    onClick={(e) => e.stopPropagation()}
                                    aria-label={`Seleccionar a ${emp.nombre} ${emp.apellido}`}
                                    className={
                                        (tipo === 'checkbox' ? 'rounded ' : '') +
                                        'text-indigo-600 focus:ring-indigo-500'
                                    }
                                />
                            </td>
                            <td className="px-3 py-2 font-medium text-gray-900">{emp.nombre}</td>
                            <td className="px-3 py-2">{emp.apellido}</td>
                            <td className="px-3 py-2">{emp.dni}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

// CU 25 (perfil Administrativo): elementos con stock disponible en el período
function ListaEquipamiento({ listado, seleccion, onElegir }) {
    if (!listado.items) return <Cargando listado={listado} />;

    return (
        <div className="max-h-72 overflow-y-auto">
            <table className="min-w-full divide-y divide-gray-200 text-sm">
                <thead className="sticky top-0 bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th className="w-8 px-3 py-2"></th>
                        <th className="px-3 py-2">Equipamiento</th>
                        <th className="px-3 py-2">Stock disponible en el período</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                    {listado.items.map((eq) => (
                        <tr
                            key={eq.id}
                            onClick={() => onElegir(eq.id)}
                            className={
                                'cursor-pointer ' +
                                (seleccion.includes(eq.id) ? 'bg-indigo-50' : 'hover:bg-gray-50')
                            }
                        >
                            <td className="px-3 py-2">
                                <input
                                    type="checkbox"
                                    checked={seleccion.includes(eq.id)}
                                    onChange={() => onElegir(eq.id)}
                                    onClick={(e) => e.stopPropagation()}
                                    aria-label={`Incluir ${eq.nombre}`}
                                    className="rounded text-indigo-600 focus:ring-indigo-500"
                                />
                            </td>
                            <td className="px-3 py-2">
                                <span className="font-medium text-gray-900">{eq.nombre}</span>
                                {eq.descripcion && (
                                    <span className="block text-xs text-gray-500">{eq.descripcion}</span>
                                )}
                            </td>
                            <td className="px-3 py-2">{eq.stock_disponible}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
