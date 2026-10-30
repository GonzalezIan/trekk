import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import ParticipantesSalidaModal from '@/Components/ParticipantesSalidaModal';
import {
    IconoCalendario,
    IconoCheck,
    IconoPersonas,
    IconoReloj,
    IconoUbicacion,
    IconoVolver,
} from '@/Components/Trekk/Iconos';
import { fotoExcursion } from '@/Components/Trekk/fotos';
import TarjetasPaquetes from '@/Components/Trekk/TarjetasPaquetes';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

// "2026-10-27" -> "27/10/2026"
const fecha = (valor) => valor.split('-').reverse().join('/');
const precio = (valor) =>
    Number(valor).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

// CU 9 - Listar Salidas Programadas
export default function Listar(props) {
    return props.esAdministrativo ? <ListarAdministrativo {...props} /> : <FichaExcursion {...props} />;
}

// Alternativo 4.2 - lo que ve el Administrativo
function ListarAdministrativo({ salidas, excursiones, estados, filtros }) {
    const { flash } = usePage().props;

    // Salida elegida por el Administrativo para operar sobre ella
    const [seleccionada, setSeleccionada] = useState(null);
    const salidaSeleccionada = salidas.find((s) => s.id === seleccionada);

    // Alternativo 4.8: ver los participantes de la salida seleccionada (CU 21)
    const [verParticipantes, setVerParticipantes] = useState(false);

    // CU 11 - Cancelar Salida Programada
    const [cancelando, setCancelando] = useState(null);
    const [motivo, setMotivo] = useState('');
    const [errorMotivo, setErrorMotivo] = useState(null); // alternativo 4.1
    const [procesando, setProcesando] = useState(false);

    const confirmarCancelacion = () => {
        router.post(
            route('salidas.cancelar', cancelando.id),
            { motivo },
            {
                preserveScroll: true,
                onStart: () => setProcesando(true),
                onFinish: () => setProcesando(false),
                onError: (errores) => setErrorMotivo(errores.motivo ?? null),
                onSuccess: () => setCancelando(null),
            },
        );
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-display text-2xl font-semibold text-trekk-texto">Salidas Programadas</h2>}
        >
            <Head title="Salidas Programadas" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                    {/* CU 8 - paso 9: se informa que la salida fue registrada */}
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

                    <Filtros filtros={filtros} excursiones={excursiones} estados={estados} />

                    {salidas.length > 0 && (
                        <BarraAcciones
                            salida={salidaSeleccionada}
                            onListarParticipantes={() => setVerParticipantes(true)}
                            onCancelar={() => {
                                setCancelando(salidaSeleccionada);
                                setMotivo('');
                                setErrorMotivo(null);
                            }}
                        />                    
                    )}

                    <div className="overflow-x-auto bg-trekk-tarjeta shadow-sm sm:rounded-lg">
                        {salidas.length === 0 ? (
                            // Alternativo 3.3
                            <p className="p-6 text-gray-600">
                                No existen Salidas Programadas que cumplan los filtros.
                            </p>
                        ) : (
                            <TablaAdministrativo
                                salidas={salidas}
                                seleccionada={seleccionada}
                                onSeleccionar={setSeleccionada}
                            />
                        )}
                    </div>
                </div>
            </div>
            {verParticipantes && salidaSeleccionada && (
                <ParticipantesSalidaModal
                    salida={{
                        ...salidaSeleccionada,
                        fecha_inicio: fecha(salidaSeleccionada.fecha_inicio),
                    }}
                    onCerrar={() => setVerParticipantes(false)}
                />
            )}
            {cancelando && (
                <Modal show={true} onClose={() => setCancelando(null)} maxWidth="md">
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-gray-900">
                            Cancelar salida programada
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            Indicá el motivo de la cancelación. Se cancelarán las
                            reservas asociadas y se notificará a los clientes.
                            Esta operación no se puede deshacer.
                        </p>
                        <textarea
                            value={motivo}
                            onChange={(e) => setMotivo(e.target.value)}
                            className="mt-4 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows={4}
                            maxLength={1000}
                        />
                        <InputError message={errorMotivo} className="mt-2" />
                        <div className="mt-4 flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setCancelando(null)}
                                className="rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                            >
                                Volver
                            </button>
                            <button
                                type="button"
                                onClick={confirmarCancelacion}
                                disabled={procesando}
                                className="rounded-lg bg-trekk-error px-4 py-2 text-sm font-medium text-trekk-primario-texto hover:bg-trekk-error/90 disabled:opacity-50"
                            >
                                {procesando ? 'Cancelando…' : 'Confirmar cancelación'}
                            </button>
                        </div>
                    </div>
                </Modal>
            )}
        </AuthenticatedLayout>
    );
}

// Filtros del Administrativo: excursión, estado, desde, hasta
function Filtros({ filtros, excursiones, estados }) {
    const [valores, setValores] = useState({
        excursion: filtros.excursion ?? '',
        estado: filtros.estado ?? '',
        desde: filtros.desde ?? '',
        hasta: filtros.hasta ?? '',
    });

    const cambiar = (e) => setValores({ ...valores, [e.target.name]: e.target.value });

    const filtrar = (e) => {
        e.preventDefault();
        // Solo mandamos los filtros completos
        const completos = Object.fromEntries(
            Object.entries(valores).filter(([, valor]) => valor !== ''),
        );
        router.get(route('salidas.listar'), completos, { preserveState: true });
    };

    const limpiar = () => {
        setValores({ excursion: '', estado: '', desde: '', hasta: '' });
        router.get(route('salidas.listar'));
    };

    const campo =
        'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

    return (
        <form
            onSubmit={filtrar}
            className="grid gap-4 bg-trekk-tarjeta p-4 shadow-sm sm:grid-cols-5 sm:rounded-lg"
        >
            <label className="text-sm text-gray-700">
                Excursión
                <select name="excursion" value={valores.excursion} onChange={cambiar} className={campo}>
                    <option value="">Todas</option>
                    {excursiones.map((e) => (
                        <option key={e.id} value={e.id}>
                            {e.nombre}
                        </option>
                    ))}
                </select>
            </label>
            <label className="text-sm text-gray-700">
                Estado
                <select name="estado" value={valores.estado} onChange={cambiar} className={campo}>
                    <option value="">Todos</option>
                    {estados.map((estado) => (
                        <option key={estado} value={estado}>
                            {estado}
                        </option>
                    ))}
                </select>
            </label>
            <label className="text-sm text-gray-700">
                Desde
                <input type="date" name="desde" value={valores.desde} onChange={cambiar} className={campo} />
            </label>
            <label className="text-sm text-gray-700">
                Hasta
                <input type="date" name="hasta" value={valores.hasta} onChange={cambiar} className={campo} />
            </label>
            <div className="flex items-end gap-2">
                <button
                    type="submit"
                    className="rounded-lg bg-trekk-primario px-4 py-2 text-sm font-medium text-trekk-primario-texto hover:bg-trekk-primario-hover"
                >
                    Filtrar
                </button>
                <button
                    type="button"
                    onClick={limpiar}
                    className="rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                >
                    Limpiar
                </button>
            </div>
        </form>
    );
}

// Operaciones que habilita la salida seleccionada por el Administrativo
function BarraAcciones({ salida, onListarParticipantes, onCancelar }) {
    return (
        <div className="flex flex-wrap items-center gap-3 bg-trekk-tarjeta px-4 py-3 text-sm shadow-sm sm:rounded-lg">
            {salida ? (
                <>
                    <span className="text-gray-600">Seleccionada:</span>
                    <span className="font-medium text-gray-900">
                        {salida.excursion} · {fecha(salida.fecha_inicio)}
                    </span>
                    {/* CU 7 - Listar paquetes de la salida */}
                    <Link
                        href={route('paquetes.listar', salida.id)}
                        className="rounded-lg bg-trekk-primario px-4 py-2 text-sm font-medium text-trekk-primario-texto hover:bg-trekk-primario-hover"
                    >
                        Listar paquetes
                    </Link>
                    {/* CU 5 - Registrar paquete (RN-01: solo sobre salidas PROGRAMADAS) */}
                    {salida.estado === 'PROGRAMADA' && (
                        <Link
                            href={route('paquetes.registrar', salida.id)}
                            className="rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                        >
                            Registrar paquete
                        </Link>
                    )}
                    {/* CU 10 - Modificar Salida Programada (RN-01: solo salidas PROGRAMADAS) */}
                    {salida.estado === 'PROGRAMADA' && (
                        <Link
                            href={route('salidas.modificar', salida.id)}
                            className="rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                        >
                            Modificar
                        </Link>
                    )}
                    {/* CU 21 - Listar participantes de la salida */}
                    <button
                        type="button"
                        onClick={onListarParticipantes}
                        className="rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                    >
                        Listar participantes
                    </button>
                    {/* CU 11 - Cancelar Salida Programada (RN-01: solo salidas PROGRAMADAS) */}
                    {salida.estado === 'PROGRAMADA' && (
                        <button
                            type="button"
                            onClick={onCancelar}
                            className="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50"
                        >
                            Cancelar
                        </button>
                    )}
                </>
            ) : (
                <span className="text-gray-600">
                    Seleccioná una salida para ver las operaciones disponibles.
                </span>
            )}
        </div>
    );
}

// Alternativo 4.1 - lo que ve el Cliente: la ficha de la excursión con sus salidas (CU 9)
// y los paquetes de la salida elegida (CU 7), en una sola pantalla como en el prototipo
// (web-page-creation/components/trekk/excursion-detalle.tsx y salidas-explorer.tsx)
function FichaExcursion({ excursion, salidas, paquetes }) {
    const [seleccion, setSeleccion] = useState(salidas[0]?.id ?? null);

    return (
        <AuthenticatedLayout>
            <Head title={excursion.nombre} />

            <section className="relative h-64 md:h-80">
                <img src={fotoExcursion(excursion.id)} alt="" className="h-full w-full object-cover" />
                <div className="absolute inset-0 bg-gradient-to-t from-trekk-fondo via-trekk-fondo/30 to-transparent" />
            </section>

            <div className="relative mx-auto -mt-24 max-w-6xl px-4 pb-16 sm:px-6 lg:px-8">
                <div className="flex flex-col gap-3">
                    <Link
                        href={route('excursiones.listar')}
                        className="flex w-fit items-center gap-1 rounded-full bg-trekk-fondo/90 px-3 py-1 text-sm text-trekk-texto-suave backdrop-blur hover:text-trekk-texto"
                    >
                        <IconoVolver className="h-4 w-4" /> Excursiones
                    </Link>
                    <h1 className="font-display text-4xl font-semibold text-trekk-texto md:text-5xl">{excursion.nombre}</h1>
                    <p className="flex items-start gap-1.5 text-trekk-texto-suave">
                        <IconoUbicacion className="mt-0.5 h-4 w-4 shrink-0" />
                        {excursion.recorrido}
                    </p>
                </div>

                <div className="mt-8 grid gap-8 lg:grid-cols-[1fr_320px]">
                    <div className="flex min-w-0 flex-col gap-10">
                        <p className="text-lg leading-relaxed">{excursion.descripcion}</p>

                        <section aria-labelledby="salidas-titulo" className="flex flex-col gap-4">
                            <div>
                                <h2 id="salidas-titulo" className="font-display text-2xl font-semibold">
                                    Salidas programadas
                                </h2>
                                <p className="text-sm text-trekk-texto-suave">
                                    Solo se muestran salidas con más de 48 horas de anticipación.
                                </p>
                            </div>
                            <SalidasCliente salidas={salidas} seleccion={seleccion} onElegir={setSeleccion} />
                        </section>

                        {seleccion && (
                            <section aria-labelledby="paquetes-titulo" className="flex flex-col gap-4">
                                <div>
                                    <h2 id="paquetes-titulo" className="font-display text-2xl font-semibold">
                                        Paquetes disponibles
                                    </h2>
                                    <p className="text-sm text-trekk-texto-suave">
                                        Precio por participante. Elegí la modalidad de contratación.
                                    </p>
                                </div>
                                <TarjetasPaquetes paquetes={paquetes[seleccion] ?? []} />
                            </section>
                        )}
                    </div>

                    <FichaTecnica excursion={excursion} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// Salidas reservables como opciones: al elegir una se muestran sus paquetes
function SalidasCliente({ salidas, seleccion, onElegir }) {
    if (salidas.length === 0) {
        // Alternativo 3.3
        return (
            <p className="rounded-xl border border-dashed border-trekk-borde p-6 text-center text-trekk-texto-suave">
                No existen Salidas Programadas para la excursión seleccionada.
            </p>
        );
    }

    return (
        <div role="radiogroup" aria-label="Salidas" className="grid gap-3 sm:grid-cols-2">
            {salidas.map((s) => {
                const activa = s.id === seleccion;
                return (
                    <button
                        key={s.id}
                        type="button"
                        role="radio"
                        aria-checked={activa}
                        onClick={() => onElegir(s.id)}
                        className={
                            'flex flex-col gap-3 rounded-xl border bg-trekk-tarjeta p-4 text-left transition-colors hover:border-trekk-primario/50 ' +
                            (activa ? 'border-trekk-primario ring-1 ring-trekk-primario' : 'border-trekk-borde')
                        }
                    >
                        <span className="flex items-center justify-between gap-2 font-medium">
                            <span className="flex items-center gap-2">
                                <IconoCalendario className="h-4 w-4 text-trekk-texto-suave" />
                                {fecha(s.fecha_inicio)}
                                {s.fecha_fin !== s.fecha_inicio && ` → ${fecha(s.fecha_fin)}`}
                            </span>
                            {activa && <IconoCheck className="h-4 w-4 text-trekk-primario" />}
                        </span>
                        <span className="flex flex-wrap gap-4 text-sm text-trekk-texto-suave">
                            <span className="flex items-center gap-1.5">
                                <IconoReloj className="h-3.5 w-3.5" /> {s.hora_de_salida} hs
                            </span>
                            <span className={'flex items-center gap-1.5 ' + (s.cupo_disponible <= 3 ? 'text-trekk-acento' : '')}>
                                <IconoPersonas className="h-3.5 w-3.5" />
                                {s.cupo_disponible} {s.cupo_disponible === 1 ? 'cupo libre' : 'cupos libres'}
                            </span>
                        </span>
                        <span className="flex items-end justify-between gap-2 border-t border-trekk-borde pt-3">
                            <span className="text-xs text-trekk-texto-suave">
                                Incluye: {s.equipamiento.join(', ') || 'sin equipamiento'}
                            </span>
                            <span className="shrink-0 text-sm">
                                desde <strong className="font-display text-lg">{precio(s.precio_base)}</strong>
                            </span>
                        </span>
                    </button>
                );
            })}
        </div>
    );
}

// Datos de la excursión cargados por el Administrativo (CU 1)
function FichaTecnica({ excursion }) {
    const datos = [
        ['Dificultad', excursion.dificultad.charAt(0) + excursion.dificultad.slice(1).toLowerCase()],
        ['Duración', `${excursion.duracion} ${excursion.duracion === 1 ? 'día' : 'días'}`],
        ['Acepta menores', excursion.acepta_menores ? 'Sí' : 'No'],
        ['Certificado médico', excursion.requiere_certificado_medico ? 'Requerido' : 'No requerido'],
    ];
    const equipamiento = (excursion.equipamiento_obligatorio ?? '')
        .split(',')
        .map((e) => e.trim())
        .filter(Boolean);

    return (
        <aside className="flex h-fit flex-col gap-5 rounded-2xl border border-trekk-borde bg-trekk-tarjeta p-5 lg:sticky lg:top-24">
            <h2 className="font-display text-lg font-semibold">Ficha técnica</h2>
            <dl className="grid grid-cols-2 gap-4 text-sm">
                {datos.map(([clave, valor]) => (
                    <div key={clave} className="flex flex-col gap-0.5">
                        <dt className="text-trekk-texto-suave">{clave}</dt>
                        <dd className="font-medium">{valor}</dd>
                    </div>
                ))}
            </dl>
            <div className="flex flex-col gap-1 border-t border-trekk-borde pt-4 text-sm">
                <h3 className="font-semibold">Etapas</h3>
                <p className="text-trekk-texto-suave">{excursion.etapas}</p>
            </div>
            <div className="flex flex-col gap-1 text-sm">
                <h3 className="font-semibold">Experiencia requerida</h3>
                <p className="text-trekk-texto-suave">{excursion.experiencia_requerida === 'SI' ? 'Sí' : 'No'}</p>
            </div>
            {excursion.punto_de_encuentro && (
                <div className="flex flex-col gap-1 text-sm">
                    <h3 className="font-semibold">Punto de encuentro</h3>
                    <p className="text-trekk-texto-suave">{excursion.punto_de_encuentro}</p>
                </div>
            )}
            {equipamiento.length > 0 && (
                <div className="flex flex-col gap-2 border-t border-trekk-borde pt-4">
                    <h3 className="text-sm font-semibold">Equipamiento obligatorio</h3>
                    <ul className="flex flex-wrap gap-1.5">
                        {equipamiento.map((e) => (
                            <li key={e} className="rounded-md bg-trekk-suave px-2 py-1 text-xs">
                                {e}
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </aside>
    );
}

// Alternativo 4.2 - lo que ve el Administrativo
function TablaAdministrativo({ salidas, seleccionada, onSeleccionar }) {
    return (
        <table className="min-w-full divide-y divide-gray-200 text-sm">
            <thead className="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th className="w-8 px-4 py-3"></th>
                    <th className="px-4 py-3">Excursión</th>
                    <th className="px-4 py-3">Inicio</th>
                    <th className="px-4 py-3">Fin</th>
                    <th className="px-4 py-3">Hora</th>
                    <th className="px-4 py-3">Guía</th>
                    <th className="px-4 py-3">Asistentes</th>
                    <th className="px-4 py-3">Equipamiento</th>
                    <th className="px-4 py-3">Cupo máx.</th>
                    <th className="px-4 py-3">Cupo disp.</th>
                    <th className="px-4 py-3">Precio base</th>
                    <th className="px-4 py-3">Paquetes</th>
                    <th className="px-4 py-3">Estado</th>
                </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
                {salidas.map((s) => (
                     <tr
                        key={s.id}
                        onClick={() => onSeleccionar(s.id)}
                        className={
                            'cursor-pointer ' +
                            (s.id === seleccionada ? 'bg-indigo-50' : 'hover:bg-gray-50')
                        }
                    >
                        <td className="px-4 py-3">
                            <input
                                type="radio"
                                name="salida"
                                checked={s.id === seleccionada}
                                onChange={() => onSeleccionar(s.id)}
                                aria-label={`Seleccionar salida de ${s.excursion} del ${fecha(s.fecha_inicio)}`}
                                className="text-indigo-600 focus:ring-indigo-500"
                            />
                        </td>
                        <td className="px-4 py-3 font-medium text-gray-900">{s.excursion}</td>
                        <td className="px-4 py-3">{fecha(s.fecha_inicio)}</td>
                        <td className="px-4 py-3">{fecha(s.fecha_fin)}</td>
                        <td className="px-4 py-3">{s.hora_de_salida}</td>
                        <td className="px-4 py-3">{s.guia ?? '—'}</td>
                        <td className="px-4 py-3">{s.asistentes.join(', ') || '—'}</td>
                        <td className="px-4 py-3">{s.equipamiento.join(', ') || '—'}</td>
                        <td className="px-4 py-3">{s.cupo_maximo}</td>
                        <td className="px-4 py-3">{s.cupo_disponible}</td>
                        <td className="px-4 py-3">{precio(s.precio_base)}</td>
                        <td className="px-4 py-3">{s.cantidad_paquetes}</td>
                        <td className="px-4 py-3">
                            <span
                                className={
                                    'rounded-full px-2 py-1 text-xs font-semibold ' +
                                    (s.estado === 'PROGRAMADA'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-gray-200 text-gray-700')
                                }
                            >
                                {s.estado}
                            </span>
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}
