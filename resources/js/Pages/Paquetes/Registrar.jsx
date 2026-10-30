import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import EncabezadoFormulario from '@/Components/Trekk/EncabezadoFormulario';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const textarea =
    'mt-1 block w-full rounded-lg border-trekk-borde bg-trekk-tarjeta shadow-sm focus:border-trekk-primario focus:ring-trekk-primario';

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
    // CU 26 se muestra en una ventana emergente al activar el alojamiento
    const [verRefugios, setVerRefugios] = useState(false);
    const alternarAlojamiento = () => {
        if (refugios.activo) setData('id_refugio', null);
        setVerRefugios(!refugios.activo);
        refugios.alternar();
    };

    return (
        <AuthenticatedLayout
            header={
                <EncabezadoFormulario
                    volverA={route('paquetes.listar', salida.id)}
                    volverTexto="Paquetes de la salida"
                    titulo="Registrar paquete"
                    descripcion={salida.excursion}
                />
            }
        >
            <Head title="Registrar paquete" />

            <div className="py-12">
                <form onSubmit={confirmar} noValidate className="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
                    <DatosSalida salida={salida} />

                    <div className="space-y-6 border border-trekk-borde bg-trekk-tarjeta p-6 rounded-2xl">
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

                    {/* Una salida de un solo día no tiene noches de alojamiento */}
                    {salida.noches > 0 && (
                        <Seccion
                            titulo="Alojamiento (opcional)"
                            error={errors.id_refugio}
                            activo={refugios.activo}
                            onActivar={alternarAlojamiento}
                            textoActivar="Incluir alojamiento"
                        >
                            <RefugioElegido
                                refugio={refugioElegido}
                                noches={salida.noches}
                                onCambiar={() => setVerRefugios(true)}
                            />
                        </Seccion>
                    )}

                    <div className="flex flex-wrap items-center justify-between gap-4 border border-trekk-borde bg-trekk-tarjeta px-6 py-4 rounded-2xl">
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
                                className="rounded-lg px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
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

            {verRefugios && (
                <ListarRefugiosModal
                    listado={refugios}
                    salida={salida}
                    seleccion={data.id_refugio}
                    onElegir={(id) => {
                        setData('id_refugio', id);
                        setVerRefugios(false);
                    }}
                    onCerrar={() => setVerRefugios(false)}
                />
            )}
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
        <dl className="grid gap-4 rounded-2xl border border-trekk-borde bg-trekk-tarjeta p-6 text-sm sm:grid-cols-3">
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
        <div className="space-y-3 border border-trekk-borde bg-trekk-tarjeta p-6 rounded-2xl">
            <div className="flex items-center justify-between gap-3">
                <h3 className="font-display text-lg font-semibold text-trekk-texto">{titulo}</h3>
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

// Refugio elegido en el CU 26, con el botón para volver a abrir el listado
function RefugioElegido({ refugio, noches, onCambiar }) {
    const boton =
        'rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave';

    if (!refugio) {
        return (
            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-gray-600">Todavía no elegiste un refugio.</p>
                <button type="button" onClick={onCambiar} className={boton}>
                    Elegir refugio
                </button>
            </div>
        );
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm">
            <div>
                <span className="font-medium text-gray-900">{refugio.nombre}</span>
                <span className="block text-xs text-gray-600">
                    {refugio.ubicacion} · {precio(refugio.precio_cama)} por cama ×{' '}
                    {noches} {noches === 1 ? 'noche' : 'noches'} = {precio(refugio.precio_cama * noches)}
                </span>
            </div>
            <button type="button" onClick={onCambiar} className={boton}>
                Cambiar refugio
            </button>
        </div>
    );
}

// CU 26 - Listar refugios, en una ventana emergente
function ListarRefugiosModal({ listado, salida, seleccion, onElegir, onCerrar }) {
    // Se confirma con "Seleccionar refugio"; cerrar la ventana no cambia lo elegido
    const [marcado, setMarcado] = useState(seleccion);

    return (
        <Modal show onClose={onCerrar} maxWidth="5xl">
            <div className="border-b border-gray-200 px-5 py-4">
                <h2 className="font-display text-xl font-semibold text-trekk-texto">Elegir refugio</h2>
                <p className="text-sm text-gray-500">
                    Refugios con convenio vigente del {fecha(salida.fecha_inicio)} al{' '}
                    {fecha(salida.fecha_fin)}
                </p>
            </div>

            <div className="px-5 py-4">
                {listado.items?.length === 0 ? (
                    // Alternativo 2.1
                    <p className="text-sm text-gray-600">
                        No existen refugios con convenio vigente para el período de la salida. Podés
                        registrar el paquete sin alojamiento o cancelar la operación.
                    </p>
                ) : (
                    <ListaRefugios
                        listado={listado}
                        noches={salida.noches}
                        seleccion={marcado}
                        onElegir={setMarcado}
                    />
                )}
            </div>

            <div className="flex items-center justify-end gap-2 border-t border-gray-200 px-5 py-4">
                <button
                    type="button"
                    onClick={onCerrar}
                    className="rounded-md px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                >
                    Volver
                </button>
                <PrimaryButton type="button" disabled={!marcado} onClick={() => onElegir(marcado)}>
                    Seleccionar refugio
                </PrimaryButton>
            </div>
        </Modal>
    );
}

// CU 26: refugios con convenio admisible para el período de la salida, en tarjetas
// (como el listado de equipamientos), con scroll cuando son muchos
function ListaRefugios({ listado, noches, seleccion, onElegir }) {
    if (!listado.items) return <Cargando listado={listado} />;

    return (
        <div className="grid max-h-[60vh] grid-cols-1 gap-3 overflow-y-auto p-1 md:grid-cols-2">
            {listado.items.map((r) => (
                <label
                    key={r.id}
                    className={
                        'flex cursor-pointer flex-col gap-2 rounded-xl border p-4 text-sm transition-colors ' +
                        (seleccion === r.id
                            ? 'border-indigo-400 bg-indigo-50'
                            : 'border-gray-200 bg-trekk-tarjeta hover:bg-gray-50')
                    }
                >
                    <div className="flex items-start gap-3">
                        <input
                            type="radio"
                            name="refugio"
                            checked={seleccion === r.id}
                            onChange={() => onElegir(r.id)}
                            className="mt-1 text-indigo-600 focus:ring-indigo-500"
                        />
                        <div className="min-w-0 flex-1">
                            <span className="font-semibold text-gray-900">{r.nombre}</span>
                            {r.ubicacion && <span className="block text-xs text-gray-500">{r.ubicacion}</span>}
                        </div>
                        <span className="shrink-0 text-right font-semibold text-gray-900">
                            {precio(r.precio_cama)}
                            <span className="block text-xs font-normal text-gray-500">por cama</span>
                        </span>
                    </div>
                    {r.descripcion && <p className="text-xs text-gray-600">{r.descripcion}</p>}
                    <div className="flex flex-wrap justify-between gap-x-4 gap-y-1 border-t border-gray-100 pt-2 text-xs text-gray-500">
                        <span>
                            {r.camas_disponibles} / {r.camas_totales} camas libres
                        </span>
                        <span>
                            Convenio {fecha(r.vigencia_desde)} al {fecha(r.vigencia_hasta)}
                        </span>
                        <span className="font-medium text-gray-700">
                            Alojamiento ({noches} {noches === 1 ? 'noche' : 'noches'}): {precio(r.precio_cama * noches)}
                        </span>
                    </div>
                </label>
            ))}
        </div>
    );
}
