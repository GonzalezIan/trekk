import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import EncabezadoFormulario from '@/Components/Trekk/EncabezadoFormulario';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const botonClaro =
    'rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave disabled:cursor-not-allowed disabled:opacity-50';

// "2026-10-27" -> "27/10/2026"
const fecha = (valor) => valor.split('-').reverse().join('/');
const precio = (valor) =>
    Number(valor).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

// Laravel responde { errors: { campo: [mensaje] } } -> { campo: mensaje }
const primerosErrores = (errores = {}) =>
    Object.fromEntries(Object.entries(errores).map(([campo, mensajes]) => [campo, mensajes[0]]));

// CU 8 - Registrar Salida Programada
export default function Registrar({ excursion }) {
    // Paso 1: datos de la salida. Paso 2: personal y equipamiento incluido.
    const [paso, setPaso] = useState(1);
    const [datos, setDatos] = useState({
        fecha_inicio: '',
        fecha_fin: '',
        hora_de_salida: '',
        cupo_maximo: '',
        precio_base: '',
    });
    const [errores, setErrores] = useState({});
    const [procesando, setProcesando] = useState(false);

    const cambiar = (e) => setDatos({ ...datos, [e.target.name]: e.target.value });

    // Pasos 3 y 4 (DSS 1: ingresarDatosSalida). Si los datos no son válidos se informa
    // y se vuelve al paso 3 conservando lo ingresado (alternativos 4.1, 4.2 y 4.3).
    const ingresarDatos = (e) => {
        e.preventDefault();
        setErrores({});
        setProcesando(true);

        window.axios
            .post(route('salidas.registrar.datos', excursion.id), datos)
            .then(() => setPaso(2))
            .catch((e) =>
                setErrores(
                    e.response?.data?.errors
                        ? primerosErrores(e.response.data.errors)
                        : { general: e.response?.data?.message || 'No se pudieron validar los datos.' },
                ),
            )
            .finally(() => setProcesando(false));
    };

    return (
        <AuthenticatedLayout
            header={
                <EncabezadoFormulario
                    volverA={route('excursiones.listar')}
                    volverTexto="Excursiones"
                    titulo="Programar salida"
                    descripcion={excursion.nombre}
                />
            }
        >
            <Head title="Programar salida" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap gap-x-6 gap-y-1 border border-trekk-borde bg-trekk-tarjeta px-6 py-4 text-sm text-gray-600 rounded-2xl">
                        <span>
                            Excursión: <strong className="text-gray-900">{excursion.nombre}</strong>
                        </span>
                        <span>Dificultad: {excursion.dificultad}</span>
                        <span>
                            Duración: {excursion.duracion} {excursion.duracion === 1 ? 'día' : 'días'}
                        </span>
                    </div>

                    {paso === 1 ? (
                        <FormularioDatos
                            datos={datos}
                            errores={errores}
                            procesando={procesando}
                            onCambiar={cambiar}
                            onConfirmar={ingresarDatos}
                        />
                    ) : (
                        <PersonalYEquipamiento datos={datos} onModificarDatos={() => setPaso(1)} />
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// Pasos 2 y 3: fecha de inicio, fecha de fin, hora de salida, cupo máximo y precio base
function FormularioDatos({ datos, errores, procesando, onCambiar, onConfirmar }) {
    const campo = (nombre, etiqueta, props = {}) => (
        <div>
            <InputLabel htmlFor={nombre} value={etiqueta} />
            <TextInput
                id={nombre}
                name={nombre}
                className="mt-1 block w-full"
                value={datos[nombre]}
                onChange={onCambiar}
                {...props}
            />
            <InputError message={errores[nombre]} className="mt-2" />
        </div>
    );

    return (
        <form onSubmit={onConfirmar} noValidate className="space-y-6 border border-trekk-borde bg-trekk-tarjeta p-6 rounded-2xl">
            {errores.general && (
                <div role="alert" className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-trekk-error">
                    {errores.general}
                </div>
            )}

            <div className="grid gap-6 sm:grid-cols-3">
                {campo('fecha_inicio', 'Fecha de inicio *', { type: 'date' })}
                {campo('fecha_fin', 'Fecha de fin *', { type: 'date' })}
                {campo('hora_de_salida', 'Hora de salida *', { type: 'time' })}
            </div>

            <div className="grid gap-6 sm:grid-cols-2">
                {campo('cupo_maximo', 'Cupo máximo *', { type: 'number', min: '1' })}
                {campo('precio_base', 'Precio base (ARS) *', { type: 'number', min: '0', step: '0.01' })}
            </div>

            <div className="flex items-center justify-end gap-4 border-t border-trekk-borde pt-6">
                {/* Flujo alternativo: cancelar sin registrar ningún dato */}
                <Link
                    href={route('excursiones.listar')}
                    className="rounded-lg px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                >
                    Cancelar
                </Link>
                <PrimaryButton disabled={procesando}>
                    {procesando ? 'Verificando…' : 'Continuar'}
                </PrimaryButton>
            </div>
        </form>
    );
}

// Pasos 5 a 9: guía responsable, asistentes (6.1) y equipamiento incluido (6.2)
function PersonalYEquipamiento({ datos, onModificarDatos }) {
    const [idGuia, setIdGuia] = useState(null);
    const [idsAsistentes, setIdsAsistentes] = useState([]);
    const [idsEquipamiento, setIdsEquipamiento] = useState([]);

    // Paso 5 -> CU 23: los guías se consultan siempre
    const guias = useListado(route('salidas.registrar.empleados', { categoria: 'GUIA' }), 'empleados', true);
    // Alternativo 6.1 -> CU 23 con categoría "Asistente"
    const asistentes = useListado(route('salidas.registrar.empleados', { categoria: 'ASISTENTE' }), 'empleados');
    // Alternativo 6.2 -> CU 25 (perfil Administrativo)
    const equipamiento = useListado(route('salidas.registrar.equipamiento'), 'equipamientos');

    const [errores, setErrores] = useState({});
    const [procesando, setProcesando] = useState(false);

    const alternar = (lista, setLista, id) =>
        setLista(lista.includes(id) ? lista.filter((x) => x !== id) : [...lista, id]);

    // RN-07: el cupo máximo será el menor entre el ingresado y el stock de cada equipo incluido
    const cupoFinal = Math.min(
        Number(datos.cupo_maximo),
        ...(equipamiento.activo ? (equipamiento.items ?? []) : [])
            .filter((eq) => idsEquipamiento.includes(eq.id))
            .map((eq) => eq.stock_disponible),
    );

    // Pasos 6 a 9 (DSS 9: registrarSalida)
    const confirmar = () => {
        setErrores({});
        setProcesando(true);
        router.post(
            route('salidas.registrar.confirmar'),
            {
                id_guia: idGuia,
                ids_asistentes: asistentes.activo ? idsAsistentes : [],
                ids_equipamiento: equipamiento.activo ? idsEquipamiento : [],
            },
            { onError: setErrores, onFinish: () => setProcesando(false) },
        );
    };

    // Alternativo 5.1: no hay guías disponibles en el período ingresado
    if (guias.items && guias.items.length === 0) {
        return (
            <div className="space-y-4 border border-trekk-borde bg-trekk-tarjeta p-6 rounded-2xl">
                <div role="alert" className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    No hay guías disponibles para el período del {fecha(datos.fecha_inicio)} al{' '}
                    {fecha(datos.fecha_fin)}.
                </div>
                <div className="flex items-center justify-end gap-4">
                    <Link
                        href={route('excursiones.listar')}
                        className="rounded-lg px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                    >
                        Cancelar
                    </Link>
                    <button type="button" onClick={onModificarDatos} className={botonClaro}>
                        Modificar período
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3 border border-trekk-borde bg-trekk-tarjeta px-6 py-4 text-sm rounded-2xl">
                <div className="flex flex-wrap gap-x-6 gap-y-1 text-gray-700">
                    <span>
                        {fecha(datos.fecha_inicio)} al {fecha(datos.fecha_fin)} · {datos.hora_de_salida} hs
                    </span>
                    <span>Cupo ingresado: {datos.cupo_maximo}</span>
                    <span>Precio base: {precio(datos.precio_base)}</span>
                </div>
                <button type="button" onClick={onModificarDatos} className={botonClaro}>
                    Modificar datos
                </button>
            </div>

            <Seccion titulo="Guía responsable *" error={errores.id_guia}>
                <ListaEmpleados
                    listado={guias}
                    tipo="radio"
                    seleccion={idGuia ? [idGuia] : []}
                    onElegir={setIdGuia}
                />
            </Seccion>

            <Seccion
                titulo="Asistentes (opcional)"
                error={errores.ids_asistentes}
                activo={asistentes.activo}
                onActivar={asistentes.alternar}
                textoActivar="Asignar asistentes"
            >
                {asistentes.items?.length === 0 ? (
                    <p className="text-sm text-gray-600">
                        No hay asistentes disponibles para el período.
                    </p>
                ) : (
                    <ListaEmpleados
                        listado={asistentes}
                        tipo="checkbox"
                        seleccion={idsAsistentes}
                        onElegir={(id) => alternar(idsAsistentes, setIdsAsistentes, id)}
                    />
                )}
            </Seccion>

            <Seccion
                titulo="Equipamiento incluido (opcional)"
                error={errores.ids_equipamiento}
                activo={equipamiento.activo}
                onActivar={equipamiento.alternar}
                textoActivar="Declarar equipamiento incluido"
            >
                {equipamiento.items?.length === 0 ? (
                    // Alternativo 6.2.1
                    <p className="text-sm text-gray-600">
                        No hay equipamiento con stock disponible en el período. La salida se
                        registrará sin equipamiento incluido.
                    </p>
                ) : (
                    <ListaEquipamiento
                        listado={equipamiento}
                        seleccion={idsEquipamiento}
                        onElegir={(id) => alternar(idsEquipamiento, setIdsEquipamiento, id)}
                    />
                )}
            </Seccion>

            <div className="flex flex-wrap items-center justify-between gap-4 border border-trekk-borde bg-trekk-tarjeta px-6 py-4 rounded-2xl">
                <p className="text-sm text-gray-700">
                    Cupo máximo de la salida: <strong>{cupoFinal}</strong>
                    {cupoFinal < Number(datos.cupo_maximo) && (
                        <span className="block text-xs text-gray-500">
                            Limitado por el stock disponible del equipamiento incluido.
                        </span>
                    )}
                </p>
                <div className="flex items-center gap-4">
                    <Link
                        href={route('excursiones.listar')}
                        className="rounded-lg px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                    >
                        Cancelar
                    </Link>
                    <PrimaryButton type="button" disabled={!idGuia || procesando} onClick={confirmar}>
                        {procesando ? 'Registrando…' : 'Confirmar registro'}
                    </PrimaryButton>
                </div>
            </div>
        </div>
    );
}

// Consulta un listado JSON. Los opcionales se consultan recién cuando el administrativo los activa.
function useListado(url, clave, activoAlInicio = false) {
    const [activo, setActivo] = useState(activoAlInicio);
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

function Seccion({ titulo, error, activo = true, onActivar, textoActivar, children }) {
    return (
        <div className="space-y-3 border border-trekk-borde bg-trekk-tarjeta p-6 rounded-2xl">
            <div className="flex items-center justify-between gap-3">
                <h3 className="font-display text-lg font-semibold text-trekk-texto">{titulo}</h3>
                {onActivar && (
                    <label className="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            checked={activo}
                            onChange={onActivar}
                            className="rounded text-indigo-600 focus:ring-indigo-500"
                        />
                        {textoActivar}
                    </label>
                )}
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

// CU 23 - paso 2: Nombre, Apellido, DNI, categoría y estado
function ListaEmpleados({ listado, tipo, seleccion, onElegir }) {
    if (!listado.items) return <Cargando listado={listado} />;

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200 text-sm">
                <thead className="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th className="w-8 px-3 py-2"></th>
                        <th className="px-3 py-2">Nombre</th>
                        <th className="px-3 py-2">Apellido</th>
                        <th className="px-3 py-2">DNI</th>
                        <th className="px-3 py-2">Categoría</th>
                        <th className="px-3 py-2">Estado</th>
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
                            <td className="px-3 py-2">{emp.categoria}</td>
                            <td className="px-3 py-2">{emp.estado}</td>
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
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200 text-sm">
                <thead className="bg-gray-50 text-left text-gray-600">
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
