import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const textarea =
    'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

// "2026-10-27" -> "27/10/2026"
const fecha = (valor) => valor.split('-').reverse().join('/');
const precio = (valor) =>
    Number(valor).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

// CU 5 - Registrar paquete
export default function Registrar({ salida }) {
    const { data, setData, post, processing, errors } = useForm({
        nombre: '',
        descripcion: '',
        ids_servicios: [],
        id_refugio: null,
    });

    // Alternativo 3.1 -> CU 24 - Listar servicios
    const servicios = useListado(route('paquetes.registrar.servicios'), 'servicios');
    // Alternativo 3.2 -> CU 26 - Listar refugios (solo los admisibles para el período)
    const refugios = useListado(route('paquetes.registrar.refugios', salida.id), 'refugios');

    const alternarServicio = (id) =>
        setData(
            'ids_servicios',
            data.ids_servicios.includes(id)
                ? data.ids_servicios.filter((x) => x !== id)
                : [...data.ids_servicios, id],
        );

    // RN-03: servicios y alojamiento son opcionales e independientes
    const serviciosElegidos = servicios.activo
        ? (servicios.items ?? []).filter((s) => data.ids_servicios.includes(s.id))
        : [];
    const refugioElegido = refugios.activo
        ? (refugios.items ?? []).find((r) => r.id === data.id_refugio)
        : null;

    // RN-04: vista previa del precio; el definitivo lo calcula el sistema al registrar
    const totalServicios = serviciosElegidos.reduce((t, s) => t + Number(s.precio_servicio), 0);
    const totalAlojamiento = refugioElegido ? refugioElegido.precio_cama * salida.noches : 0;
    const precioPaquete = Number(salida.precio_base) + totalServicios + totalAlojamiento;

    // Pasos 3 a 6 (DSS 7: registrarPaquete). Si falta algo o el nombre se repite,
    // el formulario conserva lo ingresado (alternativos 4.1 y 4.2).
    const confirmar = (e) => {
        e.preventDefault();
        post(route('paquetes.registrar.confirmar', salida.id));
    };

    // Al desactivar una sección se descarta lo elegido en ella
    const alternarServicios = () => {
        if (servicios.activo) setData('ids_servicios', []);
        servicios.alternar();
    };
    const alternarAlojamiento = () => {
        if (refugios.activo) setData('id_refugio', null);
        refugios.alternar();
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Registrar paquete de {salida.excursion}
                </h2>
            }
        >
            <Head title="Registrar paquete" />

            <div className="py-12">
                <form onSubmit={confirmar} noValidate className="mx-auto max-w-3xl space-y-4 sm:px-6 lg:px-8">
                    <DatosSalida salida={salida} />

                    <div className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg">
                        <div>
                            <InputLabel htmlFor="nombre" value="Nombre del paquete *" />
                            <TextInput
                                id="nombre"
                                className="mt-1 block w-full"
                                value={data.nombre}
                                maxLength={100}
                                onChange={(e) => setData('nombre', e.target.value)}
                            />
                            <InputError message={errors.nombre} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="descripcion" value="Descripción *" />
                            <textarea
                                id="descripcion"
                                rows={3}
                                className={textarea}
                                value={data.descripcion}
                                onChange={(e) => setData('descripcion', e.target.value)}
                            />
                            <InputError message={errors.descripcion} className="mt-2" />
                        </div>
                    </div>

                    <Seccion
                        titulo="Servicios incluidos (opcional)"
                        error={errors.ids_servicios}
                        activo={servicios.activo}
                        onActivar={alternarServicios}
                        textoActivar="Incorporar servicios"
                    >
                        {servicios.items?.length === 0 ? (
                            // Alternativo 3.1.1
                            <p className="text-sm text-gray-600">
                                No hay servicios registrados. El paquete se registrará sin servicios.
                            </p>
                        ) : (
                            <ListaServicios
                                listado={servicios}
                                seleccion={data.ids_servicios}
                                onElegir={alternarServicio}
                            />
                        )}
                    </Seccion>

                    <Seccion
                        titulo="Alojamiento (opcional)"
                        error={errors.id_refugio}
                        activo={refugios.activo}
                        onActivar={alternarAlojamiento}
                        textoActivar="Incluir alojamiento"
                    >
                        {refugios.items?.length === 0 ? (
                            // Alternativo 3.2.1
                            <p className="text-sm text-gray-600">
                                No hay refugios con convenio admisible para el período del{' '}
                                {fecha(salida.fecha_inicio)} al {fecha(salida.fecha_fin)}. Podés
                                registrar el paquete sin alojamiento o cancelar la operación.
                            </p>
                        ) : (
                            <ListaRefugios
                                listado={refugios}
                                noches={salida.noches}
                                seleccion={data.id_refugio}
                                onElegir={(id) => setData('id_refugio', id)}
                            />
                        )}
                    </Seccion>

                    <div className="flex flex-wrap items-center justify-between gap-4 bg-white px-6 py-4 shadow-sm sm:rounded-lg">
                        <div className="text-sm text-gray-700">
                            Precio del paquete: <strong>{precio(precioPaquete)}</strong>
                            <span className="block text-xs text-gray-500">
                                Base {precio(salida.precio_base)}
                                {totalServicios > 0 && ` + servicios ${precio(totalServicios)}`}
                                {refugioElegido &&
                                    ` + alojamiento ${precio(refugioElegido.precio_cama)} × ${salida.noches} ${
                                        salida.noches === 1 ? 'noche' : 'noches'
                                    }`}
                            </span>
                        </div>
                        <div className="flex items-center gap-4">
                            {/* Flujo alternativo: cancelar sin registrar ningún dato */}
                            <Link
                                href={route('salidas.listar')}
                                className="text-sm text-gray-600 underline hover:text-gray-900"
                            >
                                Cancelar
                            </Link>
                            <PrimaryButton
                                disabled={processing || (refugios.activo && !data.id_refugio && refugios.items?.length > 0)}
                            >
                                {processing ? 'Registrando…' : 'Confirmar registro'}
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}

// Paso 2: lo que el paquete toma de la salida (RN-02 y RN-07)
function DatosSalida({ salida }) {
    const dato = (etiqueta, valor) => (
        <div>
            <dt className="text-gray-500">{etiqueta}</dt>
            <dd className="font-medium text-gray-900">{valor}</dd>
        </div>
    );

    return (
        <dl className="grid gap-4 bg-white p-6 text-sm shadow-sm sm:grid-cols-3 sm:rounded-lg">
            {dato('Excursión', salida.excursion)}
            {dato(
                'Período',
                `${fecha(salida.fecha_inicio)} al ${fecha(salida.fecha_fin)} · ${salida.hora_de_salida} hs`,
            )}
            {dato('Precio base', precio(salida.precio_base))}
            {dato('Guía responsable', salida.guia ?? '—')}
            {dato('Asistentes', salida.asistentes.join(', ') || '—')}
            {dato('Cupo máximo', salida.cupo_maximo)}
            <div className="sm:col-span-3">
                <dt className="text-gray-500">Equipamiento incluido</dt>
                <dd className="font-medium text-gray-900">{salida.equipamiento.join(', ') || '—'}</dd>
            </div>
        </dl>
    );
}

// Consulta un listado JSON recién cuando el administrativo activa la sección
function useListado(url, clave) {
    const [activo, setActivo] = useState(false);
    const [items, setItems] = useState(null);
    const [error, setError] = useState('');
    const pedir = activo && items === null && !error;

    useEffect(() => {
        if (!pedir) return;
        window.axios
            .get(url)
            .then((respuesta) => setItems(respuesta.data[clave]))
            .catch((e) => setError(e.response?.data?.message || 'No se pudo cargar el listado.'));
    }, [pedir, url, clave]);

    return { activo, items, error, alternar: () => setActivo(!activo) };
}

function Seccion({ titulo, error, activo, onActivar, textoActivar, children }) {
    return (
        <div className="space-y-3 bg-white p-6 shadow-sm sm:rounded-lg">
            <div className="flex items-center justify-between gap-3">
                <h3 className="font-semibold text-gray-900">{titulo}</h3>
                <label className="flex items-center gap-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        checked={activo}
                        onChange={onActivar}
                        className="rounded text-indigo-600 focus:ring-indigo-500"
                    />
                    {textoActivar}
                </label>
            </div>
            {activo && children}
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

// CU 24: servicios registrados con su precio
function ListaServicios({ listado, seleccion, onElegir }) {
    if (!listado.items) return <Cargando listado={listado} />;

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200 text-sm">
                <thead className="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th className="w-8 px-3 py-2"></th>
                        <th className="px-3 py-2">Servicio</th>
                        <th className="px-3 py-2">Precio</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                    {listado.items.map((s) => (
                        <tr
                            key={s.id}
                            onClick={() => onElegir(s.id)}
                            className={
                                'cursor-pointer ' +
                                (seleccion.includes(s.id) ? 'bg-indigo-50' : 'hover:bg-gray-50')
                            }
                        >
                            <td className="px-3 py-2">
                                <input
                                    type="checkbox"
                                    checked={seleccion.includes(s.id)}
                                    onChange={() => onElegir(s.id)}
                                    onClick={(e) => e.stopPropagation()}
                                    aria-label={`Incluir ${s.nombre}`}
                                    className="rounded text-indigo-600 focus:ring-indigo-500"
                                />
                            </td>
                            <td className="px-3 py-2">
                                <span className="font-medium text-gray-900">{s.nombre}</span>
                                {s.descripcion && (
                                    <span className="block text-xs text-gray-500">{s.descripcion}</span>
                                )}
                            </td>
                            <td className="px-3 py-2">{precio(s.precio_servicio)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

// CU 26: refugios con convenio admisible para el período de la salida
function ListaRefugios({ listado, noches, seleccion, onElegir }) {
    if (!listado.items) return <Cargando listado={listado} />;

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200 text-sm">
                <thead className="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th className="w-8 px-3 py-2"></th>
                        <th className="px-3 py-2">Refugio</th>
                        <th className="px-3 py-2">Precio por cama</th>
                        <th className="px-3 py-2">Camas disponibles</th>
                        <th className="px-3 py-2">Alojamiento ({noches} {noches === 1 ? 'noche' : 'noches'})</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                    {listado.items.map((r) => (
                        <tr
                            key={r.id}
                            onClick={() => onElegir(r.id)}
                            className={
                                'cursor-pointer ' + (seleccion === r.id ? 'bg-indigo-50' : 'hover:bg-gray-50')
                            }
                        >
                            <td className="px-3 py-2">
                                <input
                                    type="radio"
                                    name="refugio"
                                    checked={seleccion === r.id}
                                    onChange={() => onElegir(r.id)}
                                    onClick={(e) => e.stopPropagation()}
                                    aria-label={`Elegir ${r.nombre}`}
                                    className="text-indigo-600 focus:ring-indigo-500"
                                />
                            </td>
                            <td className="px-3 py-2">
                                <span className="font-medium text-gray-900">{r.nombre}</span>
                                {r.ubicacion && <span className="block text-xs text-gray-500">{r.ubicacion}</span>}
                            </td>
                            <td className="px-3 py-2">{precio(r.precio_cama)}</td>
                            <td className="px-3 py-2">{r.camas_disponibles}</td>
                            <td className="px-3 py-2">{precio(r.precio_cama * noches)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
