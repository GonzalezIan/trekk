import EstadoBadge from '@/Components/EstadoBadge';
import Modal from '@/Components/Modal';
import { PanelRegistrarPago } from '@/Components/RegistrarPago';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

// CU 14 - Registrar reserva. Se abre desde el CU 7 al elegir un paquete.
// El servidor decide cupos, validaciones, stock e importe; la pantalla solo guía los pasos.

const pasos = ['Participantes', 'Datos', 'Equipamiento', 'Resumen', 'Pago'];

const codigo = (id) => `R-${String(id).padStart(4, '0')}`;

const usd = (monto) =>
    `USD ${Number(monto).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const formatFecha = (fecha) =>
    new Date(`${fecha}T12:00:00`).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

// El equipamiento obligatorio es un texto libre de la excursión (CU 1): se muestra como lista
const listarObligatorio = (texto) =>
    (texto ?? '')
        .split(/[,;\n]/)
        .map((item) => item.trim())
        .filter(Boolean);

const participanteVacio = { dni: '', nombre: '', apellido: '', fecha_nacimiento: '' };

const botonPrimario =
    'inline-flex items-center justify-center rounded-lg bg-trekk-primario px-4 py-2 text-sm font-medium text-trekk-primario-texto hover:bg-trekk-primario-hover disabled:cursor-not-allowed disabled:opacity-50';
const botonSecundario =
    'inline-flex items-center justify-center rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave disabled:cursor-not-allowed disabled:opacity-50';
const botonFantasma =
    'inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave disabled:cursor-not-allowed disabled:opacity-50';
const campo =
    'mt-1 block w-full rounded-lg border-trekk-borde bg-trekk-tarjeta text-sm focus:border-trekk-primario focus:ring-trekk-primario';

export default function Registrar({ paquete, salida, excursion, minutosRetencion }) {
    const [paso, setPaso] = useState(0);
    const [cantidad, setCantidad] = useState(1);
    // Reserva "En curso" que devuelve el servidor en el paso 5
    const [reserva, setReserva] = useState(null);
    const [restante, setRestante] = useState(null);
    const [participantes, setParticipantes] = useState([]);
    const [cargados, setCargados] = useState(null);
    const [quiereAlquilar, setQuiereAlquilar] = useState(null);
    const [seleccion, setSeleccion] = useState([]);
    const [catalogoAbierto, setCatalogoAbierto] = useState(false);
    const [importe, setImporte] = useState(null);
    const [registrada, setRegistrada] = useState(null);
    const [errores, setErrores] = useState({});
    const [error, setError] = useState('');
    const [vencida, setVencida] = useState(false);
    const [procesando, setProcesando] = useState(false);

    const expirado = vencida || (restante !== null && restante <= 0 && !registrada);
    const enCurso = reserva !== null && !registrada && !expirado;

    // Cuenta regresiva de la retención (RN-03)
    useEffect(() => {
        if (restante === null || registrada || restante <= 0) return;
        const t = setTimeout(() => setRestante((r) => r - 1), 1000);
        return () => clearTimeout(t);
    }, [restante, registrada]);

    // Avisa antes de salir de la página con lugares retenidos
    useEffect(() => {
        if (!enCurso) return;
        const avisar = (e) => e.preventDefault();
        window.addEventListener('beforeunload', avisar);
        return () => window.removeEventListener('beforeunload', avisar);
    }, [enCurso]);

    // Respuesta de error del servidor: 422 con errores por campo, 410 si la reserva venció (*a)
    const manejarError = (e, porDefecto) => {
        setProcesando(false);
        if (e.response?.status === 410) {
            setVencida(true);
            setError(e.response.data.message);
            return;
        }
        if (e.response?.status === 422) {
            const lista = e.response.data.errors ?? {};
            setErrores(lista);
            setError(Object.keys(lista).length ? '' : e.response.data.message);
            return;
        }
        setError(e.response?.data?.message || porDefecto);
    };

    const limpiarErrores = () => {
        setErrores({});
        setError('');
    };

    // Pasos 4 y 5: retiene los cupos y la reserva queda "En curso" por 20 minutos
    const retenerCupos = () => {
        limpiarErrores();
        setProcesando(true);
        window.axios
            .post(route('reservas.registrar.cantidad', paquete.id), { cantidad })
            .then(({ data }) => {
                setReserva(data.reserva);
                setRestante(data.reserva.segundos_restantes);
                setParticipantes(Array.from({ length: data.reserva.cantidad_participantes }, () => ({ ...participanteVacio })));
                setProcesando(false);
                setPaso(1);
            })
            .catch((e) => manejarError(e, 'No se pudieron retener los cupos.'));
    };

    // Pasos 7 a 11: valida los participantes y muestra el equipamiento necesario
    const validarParticipantes = () => {
        limpiarErrores();
        setProcesando(true);
        window.axios
            .post(route('reservas.registrar.participantes', reserva.id), { participantes })
            .then(({ data }) => {
                setCargados(data);
                setProcesando(false);
                setPaso(2);
            })
            .catch((e) => manejarError(e, 'No se pudieron validar los participantes.'));
    };

    // Pasos 12 a 14: continúa (con o sin equipamiento) y el servidor calcula el importe total
    const continuar = async () => {
        limpiarErrores();
        setProcesando(true);
        try {
            // Si eligió no alquilar después de haber elegido elementos, se libera lo retenido
            if (!quiereAlquilar && seleccion.length > 0) {
                await window.axios.post(route('reservas.registrar.equipamiento.agregar', reserva.id), {
                    equipamientos: [],
                });
                setSeleccion([]);
            }
            const { data } = await window.axios.post(route('reservas.registrar.continuar', reserva.id));
            setImporte(data.importe);
            setProcesando(false);
            setPaso(3);
        } catch (e) {
            manejarError(e, 'No se pudo calcular el importe.');
        }
    };

    // Pasos 15 y 16: verifica que la reserva siga vigente e invoca al CU 22
    const confirmarReserva = () => {
        limpiarErrores();
        setProcesando(true);
        window.axios
            .post(route('reservas.registrar.confirmar', reserva.id))
            .then(({ data }) => {
                setReserva(data.reserva);
                setProcesando(false);
                setPaso(4);
            })
            .catch((e) => manejarError(e, 'No se pudo confirmar la reserva.'));
    };

    // 17.1: el cliente cancela antes de efectuar el pago
    const cancelar = () => {
        const volver = () => router.visit(route('paquetes.listar', salida.id));
        if (!enCurso) return volver();
        setProcesando(true);
        window.axios
            .post(route('reservas.registrar.cancelar', reserva.id))
            .then(volver)
            .catch(volver);
    };

    const atras = () => {
        limpiarErrores();
        setPaso(paso - 1);
    };

    if (registrada) {
        return (
            <ReservaRegistrada
                reserva={reserva}
                registrada={registrada}
                excursion={excursion}
                seleccion={seleccion}
            />
        );
    }

    const costoEquipamiento = seleccion.reduce((total, e) => total + e.subtotal, 0);
    const cantidadParticipantes = reserva?.cantidad_participantes ?? cantidad;
    const importeTotal = importe?.importe_total ?? paquete.precio * cantidadParticipantes + costoEquipamiento;

    return (
        <AuthenticatedLayout>
            <Head title="Registrar reserva" />

            <main className="mx-auto grid max-w-6xl gap-8 px-4 py-10 lg:grid-cols-[1fr_340px]">
                <div className="flex min-w-0 flex-col gap-6">
                    <div className="flex flex-col gap-1">
                        <button
                            type="button"
                            onClick={cancelar}
                            className="self-start text-sm text-trekk-texto-suave hover:text-trekk-texto"
                        >
                            ← Volver a los paquetes de {excursion.nombre}
                        </button>
                        <h1 className="font-display text-3xl font-semibold text-trekk-texto">Registrar reserva</h1>
                    </div>

                    <ol className="grid grid-cols-5 gap-2" aria-label="Pasos">
                        {pasos.map((p, i) => (
                            <li key={p} aria-current={i === paso ? 'step' : undefined} className="flex flex-col gap-1.5">
                                <span className={'h-1 rounded-full ' + (i <= paso ? 'bg-trekk-primario' : 'bg-trekk-borde')} />
                                <span
                                    className={
                                        'text-xs ' +
                                        (i === paso ? 'whitespace-nowrap font-medium text-trekk-texto' : 'hidden text-trekk-texto-suave sm:inline')
                                    }
                                >
                                    {i + 1}. {p}
                                </span>
                            </li>
                        ))}
                    </ol>

                    {reserva && (
                        <Temporizador
                            restante={restante}
                            expirado={expirado}
                            conCamas={paquete.camas_disponibles !== null}
                            conEquipamiento={seleccion.length > 0}
                        />
                    )}

                    <section className="flex flex-col gap-5 rounded-2xl border border-trekk-borde bg-trekk-tarjeta p-4 sm:p-6">
                        {paso === 0 && (
                            <PasoCantidad
                                cantidad={cantidad}
                                onCantidad={(n) => {
                                    limpiarErrores();
                                    setCantidad(n);
                                }}
                                paquete={paquete}
                                minutosRetencion={minutosRetencion}
                                error={errores.cantidad?.[0]}
                            />
                        )}

                        {paso === 1 && (
                            <PasoDatos
                                participantes={participantes}
                                onCambiar={(i, campo, valor) =>
                                    setParticipantes((prev) =>
                                        prev.map((p, j) => (j === i ? { ...p, [campo]: valor } : p)),
                                    )
                                }
                                errores={errores}
                            />
                        )}

                        {paso === 2 && cargados && (
                            <PasoEquipamiento
                                cargados={cargados}
                                quiereAlquilar={quiereAlquilar}
                                onNoAlquilar={() => setQuiereAlquilar(false)}
                                onAbrirCatalogo={() => setCatalogoAbierto(true)}
                                seleccion={seleccion}
                            />
                        )}

                        {paso === 3 && importe && (
                            <PasoResumen cargados={cargados} importe={importe} />
                        )}

                        {paso === 4 && (
                            <div className="-mx-4 -mb-4 flex flex-col gap-3 sm:-mx-6 sm:-mb-6">
                                <h2 className="px-4 font-display text-xl font-semibold text-trekk-texto sm:px-6">Pago</h2>
                                <p className="px-4 text-sm text-gray-500 sm:px-6">
                                    Para registrar la reserva tenés que abonar al menos la seña del 50% del importe total.
                                </p>
                                {/* Paso 16 - CU 22 Registrar pago de reserva */}
                                {!expirado && (
                                    <PanelRegistrarPago
                                        reserva={reserva}
                                        onPagado={(mensaje, datos) => setRegistrada({ mensaje, ...datos })}
                                    />
                                )}
                            </div>
                        )}

                        {(error || errores.participantes) && (
                            <p role="alert" className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                                {error || errores.participantes[0]}
                            </p>
                        )}

                        <div className="flex flex-wrap justify-between gap-2 border-t border-gray-200 pt-5">
                            {expirado ? (
                                <button
                                    type="button"
                                    onClick={() => router.visit(route('reservas.registrar', paquete.id))}
                                    className={botonPrimario}
                                >
                                    Volver a empezar
                                </button>
                            ) : (
                                <button type="button" disabled={procesando} onClick={cancelar} className={botonFantasma}>
                                    Cancelar
                                </button>
                            )}
                            <div className="flex gap-2">
                                {paso > 1 && (
                                    <button
                                        type="button"
                                        disabled={procesando || expirado}
                                        onClick={atras}
                                        className={botonSecundario}
                                    >
                                        Atrás
                                    </button>
                                )}
                                {paso === 0 && (
                                    <button type="button" disabled={procesando} onClick={retenerCupos} className={botonPrimario}>
                                        {procesando ? 'Verificando cupos…' : 'Retener cupos'}
                                    </button>
                                )}
                                {paso === 1 && (
                                    <button
                                        type="button"
                                        disabled={procesando || expirado}
                                        onClick={validarParticipantes}
                                        className={botonPrimario}
                                    >
                                        Validar participantes
                                    </button>
                                )}
                                {paso === 2 && (
                                    <button
                                        type="button"
                                        disabled={procesando || expirado || quiereAlquilar === null}
                                        onClick={continuar}
                                        className={botonPrimario}
                                    >
                                        {quiereAlquilar === null
                                            ? 'Elegí una opción'
                                            : quiereAlquilar && seleccion.length > 0
                                              ? 'Confirmar equipamiento'
                                              : 'Continuar sin alquilar'}
                                    </button>
                                )}
                                {paso === 3 && (
                                    <button
                                        type="button"
                                        disabled={procesando || expirado}
                                        onClick={confirmarReserva}
                                        className={botonPrimario}
                                    >
                                        Confirmar y pagar
                                    </button>
                                )}
                            </div>
                        </div>
                    </section>
                </div>

                <Resumen
                    paquete={paquete}
                    salida={salida}
                    excursion={excursion}
                    cantidad={cantidadParticipantes}
                    seleccion={seleccion}
                    importeTotal={importeTotal}
                />
            </main>

            {catalogoAbierto && (
                <CatalogoAlquiler
                    reserva={reserva}
                    seleccion={seleccion}
                    onCerrar={() => setCatalogoAbierto(false)}
                    onVencida={(mensaje) => {
                        setCatalogoAbierto(false);
                        setVencida(true);
                        setError(mensaje);
                    }}
                    onConfirmado={(equipamiento) => {
                        setSeleccion(equipamiento);
                        // Si confirma sin elegir nada, equivale a continuar sin alquilar
                        setQuiereAlquilar(equipamiento.length > 0);
                        setCatalogoAbierto(false);
                    }}
                />
            )}
        </AuthenticatedLayout>
    );
}

function Temporizador({ restante, expirado, conCamas, conEquipamiento }) {
    if (expirado) {
        return (
            <div role="status" className="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <IconoReloj />
                <span>
                    El tiempo para completar la reserva venció. Los cupos y el equipamiento retenidos se liberan: volvé a
                    iniciar la reserva.
                </span>
            </div>
        );
    }

    const retenido = ['cupos', conCamas && 'camas', conEquipamiento && 'equipamiento'].filter(Boolean);
    const texto = retenido.join(', ').replace(/, ([^,]*)$/, ' y $1');
    const mm = String(Math.floor(restante / 60)).padStart(2, '0');
    const ss = String(restante % 60).padStart(2, '0');

    return (
        <div role="status" className="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-100 px-4 py-3 text-sm text-gray-700">
            <IconoReloj />
            <span>
                {texto.charAt(0).toUpperCase() + texto.slice(1)} retenidos por{' '}
                <strong className="tabular-nums">
                    {mm}:{ss}
                </strong>
            </span>
        </div>
    );
}

// ---------- Pasos 2 a 5: cantidad de participantes ----------

function PasoCantidad({ cantidad, onCantidad, paquete, minutosRetencion, error }) {
    return (
        <>
            <div>
                <h2 className="font-display text-xl font-semibold text-trekk-texto">¿Cuántas personas realizarán la excursión?</h2>
                <p className="text-sm text-gray-500">
                    Cupos libres: {paquete.cupo_disponible}
                    {paquete.camas_disponibles !== null && ` · Camas libres en ${paquete.refugio}: ${paquete.camas_disponibles}`}
                </p>
            </div>
            <div className="flex items-center gap-3">
                <button
                    type="button"
                    aria-label="Quitar participante"
                    onClick={() => onCantidad(Math.max(1, cantidad - 1))}
                    className="flex size-10 items-center justify-center rounded-lg border border-gray-200 text-lg hover:bg-gray-100"
                >
                    −
                </button>
                <input
                    type="number"
                    min="1"
                    inputMode="numeric"
                    aria-label="Cantidad de participantes"
                    value={cantidad}
                    onChange={(e) => onCantidad(e.target.value === '' ? '' : Number(e.target.value))}
                    className="w-20 rounded-lg border-gray-200 text-center text-3xl font-semibold tabular-nums focus:border-trekk-primario focus:ring-trekk-primario"
                />
                <button
                    type="button"
                    aria-label="Agregar participante"
                    onClick={() => onCantidad((Number(cantidad) || 0) + 1)}
                    className="flex size-10 items-center justify-center rounded-lg border border-gray-200 text-lg hover:bg-gray-100"
                >
                    +
                </button>
            </div>
            {error && <p className="text-sm text-red-600">{error}</p>}
            <p className="text-xs text-gray-500">
                Al continuar, los lugares quedan retenidos {minutosRetencion} minutos para que completes la reserva.
            </p>
        </>
    );
}

// ---------- Pasos 6 a 9: datos de los participantes ----------

function PasoDatos({ participantes, onCambiar, errores }) {
    const errorDe = (i, campo) => errores[`participantes.${i}.${campo}`]?.[0];

    return (
        <>
            <div>
                <h2 className="font-display text-xl font-semibold text-trekk-texto">Datos de los participantes</h2>
                <p className="text-sm text-gray-500">Debe viajar al menos un participante mayor de 18 años.</p>
            </div>
            {participantes.map((p, i) => (
                <fieldset key={i} className="grid gap-3 rounded-xl border border-gray-200 p-4 sm:grid-cols-4">
                    <legend className="px-1 text-sm font-medium text-gray-900">Participante {i + 1}</legend>
                    <label className="text-sm font-medium text-gray-900">
                        DNI
                        <input
                            inputMode="numeric"
                            autoComplete="off"
                            value={p.dni}
                            onChange={(e) => onCambiar(i, 'dni', e.target.value)}
                            className={campo}
                        />
                        <ErrorCampo mensaje={errorDe(i, 'dni')} />
                    </label>
                    <label className="text-sm font-medium text-gray-900">
                        Nombre
                        <input
                            autoComplete="off"
                            value={p.nombre}
                            onChange={(e) => onCambiar(i, 'nombre', e.target.value)}
                            className={campo}
                        />
                        <ErrorCampo mensaje={errorDe(i, 'nombre')} />
                    </label>
                    <label className="text-sm font-medium text-gray-900">
                        Apellido
                        <input
                            autoComplete="off"
                            value={p.apellido}
                            onChange={(e) => onCambiar(i, 'apellido', e.target.value)}
                            className={campo}
                        />
                        <ErrorCampo mensaje={errorDe(i, 'apellido')} />
                    </label>
                    <label className="text-sm font-medium text-gray-900">
                        Nacimiento
                        <input
                            type="date"
                            value={p.fecha_nacimiento}
                            onChange={(e) => onCambiar(i, 'fecha_nacimiento', e.target.value)}
                            className={campo}
                        />
                        <ErrorCampo mensaje={errorDe(i, 'fecha_nacimiento')} />
                    </label>
                </fieldset>
            ))}
        </>
    );
}

function ErrorCampo({ mensaje }) {
    if (!mensaje) return null;
    return <span className="mt-1 block text-xs font-normal text-red-600">{mensaje}</span>;
}

// ---------- Pasos 11 y 12: equipamiento obligatorio y alquiler ----------

function PasoEquipamiento({ cargados, quiereAlquilar, onNoAlquilar, onAbrirCatalogo, seleccion }) {
    const obligatorio = listarObligatorio(cargados.equipamiento_obligatorio);

    return (
        <>
            <h2 className="font-display text-xl font-semibold text-trekk-texto">Equipamiento</h2>

            {/* El equipamiento obligatorio es texto informativo de la excursión: no está vinculado al catálogo de alquiler */}
            {obligatorio.length > 0 && (
                <div role="note" className="flex flex-col gap-2 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900">
                    <p className="font-medium">Equipamiento obligatorio</p>
                    <p className="text-sm">Cada participante debe contar con el siguiente equipamiento para realizar la excursión:</p>
                    <ul className="list-disc space-y-0.5 pl-6 text-sm">
                        {obligatorio.map((item) => (
                            <li key={item}>{item}</li>
                        ))}
                    </ul>
                </div>
            )}

            {cargados.equipamiento_incluido.length > 0 && (
                <p className="text-sm text-gray-500">
                    La salida incluye sin costo:{' '}
                    <span className="text-gray-900">{cargados.equipamiento_incluido.join(', ')}</span>.
                </p>
            )}

            <div className="flex flex-col gap-3 border-t border-gray-200 pt-5">
                <h3 className="font-display text-lg font-semibold text-trekk-texto">¿Querés agregar equipamiento de alquiler a la reserva?</h3>
                <div role="radiogroup" aria-label="Equipamiento de alquiler" className="grid gap-3 sm:grid-cols-2">
                    <OpcionTarjeta
                        elegida={quiereAlquilar === false}
                        onClick={onNoAlquilar}
                        titulo="No, continuar sin alquilar"
                        texto="Cada participante trae su propio equipo."
                    />
                    <OpcionTarjeta
                        elegida={quiereAlquilar === true}
                        onClick={onAbrirCatalogo}
                        titulo="Sí, quiero alquilar"
                        texto="Abrí el catálogo y elegí los elementos."
                    />
                </div>
            </div>

            {quiereAlquilar && seleccion.length > 0 && (
                <div className="flex flex-col gap-3 rounded-xl border border-trekk-borde bg-trekk-secundario/50 p-4">
                    <div className="flex items-center justify-between gap-3">
                        <h3 className="text-sm font-semibold text-gray-900">Equipamiento de alquiler seleccionado</h3>
                        <button type="button" onClick={onAbrirCatalogo} className={botonSecundario + ' !px-3 !py-1.5'}>
                            Modificar selección
                        </button>
                    </div>
                    <ul className="flex flex-col gap-1 text-sm">
                        {seleccion.map((e) => (
                            <li key={e.id} className="flex justify-between gap-3">
                                <span>
                                    {e.cantidad} × {e.nombre}
                                </span>
                                <span className="tabular-nums">{usd(e.subtotal)}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </>
    );
}

function OpcionTarjeta({ elegida, onClick, titulo, texto }) {
    return (
        <button
            type="button"
            role="radio"
            aria-checked={elegida}
            onClick={onClick}
            className={
                'flex flex-col gap-1 rounded-xl border p-4 text-left transition-colors hover:border-trekk-primario/50 ' +
                (elegida ? 'border-trekk-primario ring-1 ring-trekk-primario' : 'border-trekk-borde')
            }
        >
            <span className="font-medium text-gray-900">{titulo}</span>
            <span className="text-sm text-gray-500">{texto}</span>
        </button>
    );
}

// ---------- 12.1: catálogo de alquiler (CU 25 - Listar equipamientos) ----------

function CatalogoAlquiler({ reserva, seleccion, onCerrar, onConfirmado, onVencida }) {
    const [catalogo, setCatalogo] = useState(null);
    // Selección de esta ventana: se manda al servidor recién al confirmar
    const [elegidos, setElegidos] = useState(seleccion.map((e) => ({ id: e.id, nombre: e.nombre, cantidad: e.cantidad })));
    const [cantidades, setCantidades] = useState({});
    const [aviso, setAviso] = useState('');
    const [errores, setErrores] = useState({});
    const [procesando, setProcesando] = useState(false);

    useEffect(() => {
        window.axios
            .get(route('reservas.registrar.equipamiento', reserva.id))
            .then(({ data }) => setCatalogo(data.equipamientos))
            .catch((e) =>
                e.response?.status === 410
                    ? onVencida(e.response.data.message)
                    : setAviso('No se pudo consultar el equipamiento disponible.'),
            );
    }, [reserva.id]);

    const precio = (id) => Number(catalogo?.find((e) => e.id === id)?.precio_alquiler ?? 0);
    const total = elegidos.reduce((suma, e) => suma + precio(e.id) * e.cantidad, 0);

    const agregar = (equipamiento) => {
        setErrores({});
        const n = Math.max(1, Math.floor(Number(cantidades[equipamiento.id]) || 1));
        const actual = elegidos.find((e) => e.id === equipamiento.id)?.cantidad ?? 0;
        let nueva = actual + n;
        // 12.1.1: no se pueden elegir más elementos que el stock disponible
        if (nueva > equipamiento.stock_disponible) {
            setAviso(`No hay stock suficiente de ${equipamiento.nombre}: quedan ${equipamiento.stock_disponible} disponibles.`);
            nueva = equipamiento.stock_disponible;
        } else {
            setAviso('');
        }
        setElegidos((prev) =>
            actual
                ? prev.map((e) => (e.id === equipamiento.id ? { ...e, cantidad: nueva } : e))
                : [...prev, { id: equipamiento.id, nombre: equipamiento.nombre, cantidad: nueva }],
        );
        setCantidades((prev) => ({ ...prev, [equipamiento.id]: 1 }));
    };

    const quitar = (id) => {
        setErrores({});
        setAviso('');
        setElegidos((prev) => prev.filter((e) => e.id !== id));
    };

    // Pasos 2 a 4 del 12.1: el servidor verifica el stock y retiene lo elegido
    const confirmar = () => {
        setProcesando(true);
        setErrores({});
        window.axios
            .post(route('reservas.registrar.equipamiento.agregar', reserva.id), {
                equipamientos: elegidos.map(({ id, cantidad }) => ({ id, cantidad })),
            })
            .then(({ data }) => onConfirmado(data.equipamiento))
            .catch((e) => {
                setProcesando(false);
                if (e.response?.status === 410) return onVencida(e.response.data.message);
                setErrores(e.response?.data?.errors ?? {});
                setAviso(e.response?.status === 422 ? '' : e.response?.data?.message || 'No se pudo agregar el equipamiento.');
            });
    };

    return (
        <Modal show maxWidth="5xl" onClose={onCerrar}>
            <div className="flex h-[min(40rem,90dvh)] flex-col">
                <div className="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <div>
                        <h2 className="font-display text-xl font-semibold text-trekk-texto">Equipamiento disponible para alquiler</h2>
                        <p className="text-sm text-gray-500">Indicá la cantidad y tocá + para sumarlo a tu selección.</p>
                    </div>
                    <button
                        type="button"
                        aria-label="Cerrar"
                        onClick={onCerrar}
                        className="rounded-md px-2 text-xl leading-none text-gray-500 hover:bg-gray-100"
                    >
                        ×
                    </button>
                </div>

                <div className="grid min-h-0 flex-1 grid-rows-[1fr_auto] md:grid-cols-[1fr_18rem] md:grid-rows-1">
                    <div className="flex min-h-0 flex-col gap-3 overflow-y-auto p-4">
                        {!catalogo && !aviso && <p className="text-sm text-gray-500">Cargando equipamiento…</p>}
                        {catalogo?.length === 0 && (
                            <p className="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                                Actualmente no hay equipamiento disponible para alquiler.
                            </p>
                        )}
                        {catalogo?.length > 0 && (
                            <ul className="flex flex-col gap-2">
                                {catalogo.map((eq) => {
                                    const enSeleccion = elegidos.find((e) => e.id === eq.id)?.cantidad ?? 0;
                                    return (
                                        <li
                                            key={eq.id}
                                            className="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-3"
                                        >
                                            <div className="min-w-0">
                                                <p className="font-medium text-gray-900">{eq.nombre}</p>
                                                <p className="text-xs text-gray-500">{eq.descripcion}</p>
                                                <p className="text-xs text-gray-500">
                                                    <span className="font-medium text-gray-900 sm:hidden">
                                                        {usd(eq.precio_alquiler)} ·{' '}
                                                    </span>
                                                    {eq.stock_disponible} disponibles
                                                    {enSeleccion > 0 && (
                                                        <span className="font-medium text-trekk-primario">
                                                            {' '}
                                                            · {enSeleccion} en tu selección
                                                        </span>
                                                    )}
                                                </p>
                                            </div>
                                            <div className="flex shrink-0 items-center gap-2">
                                                <span className="hidden text-sm font-medium tabular-nums sm:inline">
                                                    {usd(eq.precio_alquiler)}
                                                </span>
                                                <input
                                                    type="number"
                                                    inputMode="numeric"
                                                    aria-label={`Cantidad de ${eq.nombre}`}
                                                    min={1}
                                                    max={eq.stock_disponible}
                                                    value={cantidades[eq.id] ?? 1}
                                                    onChange={(e) =>
                                                        setCantidades((prev) => ({ ...prev, [eq.id]: e.target.value }))
                                                    }
                                                    className="h-8 w-14 rounded-lg border-gray-200 px-1 text-center text-sm tabular-nums"
                                                />
                                                <button
                                                    type="button"
                                                    aria-label={`Agregar ${eq.nombre}`}
                                                    onClick={() => agregar(eq)}
                                                    className="flex size-8 items-center justify-center rounded-lg bg-trekk-primario text-trekk-primario-texto hover:bg-trekk-primario-hover"
                                                >
                                                    +
                                                </button>
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </div>

                    <aside
                        aria-label="Equipamiento a agregar"
                        className="flex min-h-0 flex-col gap-3 border-t border-gray-200 bg-gray-50 p-4 md:border-l md:border-t-0"
                    >
                        <h3 className="text-sm font-semibold text-gray-900">Vas a agregar</h3>
                        {elegidos.length === 0 ? (
                            <p className="text-sm text-gray-500">Todavía no agregaste equipamiento.</p>
                        ) : (
                            <ul className="flex max-h-28 flex-col gap-2 overflow-y-auto md:max-h-none md:flex-1">
                                {elegidos.map((e, i) => (
                                    <li key={e.id} className="flex items-start justify-between gap-2 text-sm">
                                        <div>
                                            <p className="font-medium text-gray-900">
                                                {e.nombre} ×{e.cantidad}
                                            </p>
                                            <p className="tabular-nums text-gray-500">{usd(precio(e.id) * e.cantidad)}</p>
                                            {/* 12.1.1: el servidor señala los elementos sin stock suficiente */}
                                            {errores[`equipamientos.${i}.cantidad`] && (
                                                <p className="text-xs text-red-600">{errores[`equipamientos.${i}.cantidad`][0]}</p>
                                            )}
                                        </div>
                                        <button
                                            type="button"
                                            aria-label={`Quitar ${e.nombre}`}
                                            onClick={() => quitar(e.id)}
                                            className="rounded-md px-1.5 text-gray-500 hover:bg-gray-100 hover:text-red-600"
                                        >
                                            ✕
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                        {aviso && (
                            <p role="alert" className="text-xs text-amber-700">
                                {aviso}
                            </p>
                        )}
                        <div className="mt-auto flex flex-col gap-2 border-t border-gray-200 pt-3">
                            <p className="flex items-baseline justify-between text-sm text-gray-700">
                                Total a agregar <strong className="text-xl tabular-nums text-gray-900">{usd(total)}</strong>
                            </p>
                            <button type="button" disabled={procesando || !catalogo} onClick={confirmar} className={botonPrimario}>
                                {procesando ? 'Verificando stock…' : 'Confirmar'}
                            </button>
                            <button type="button" onClick={onCerrar} className={botonFantasma + ' !py-1.5'}>
                                Cancelar
                            </button>
                        </div>
                    </aside>
                </div>
            </div>
        </Modal>
    );
}

// ---------- Pasos 13 y 14: resumen e importe total ----------

function PasoResumen({ cargados, importe }) {
    const obligatorio = listarObligatorio(cargados.equipamiento_obligatorio);
    const requierePendiente = cargados.participantes.some((p) => p.estado_requisito_medico === 'PENDIENTE');

    return (
        <>
            <div>
                <h2 className="font-display text-xl font-semibold text-trekk-texto">Resumen de la reserva</h2>
                <p className="text-sm text-gray-500">Revisá los datos antes de confirmar y pagar.</p>
            </div>

            <div className="flex flex-col gap-2">
                <h3 className="text-sm font-semibold text-gray-900">Participantes</h3>
                <ul className="flex flex-col divide-y divide-gray-200 rounded-xl border border-gray-200">
                    {cargados.participantes.map((p) => (
                        <li key={p.dni} className="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5">
                            <div>
                                <p className="font-medium text-gray-900">
                                    {p.nombre} {p.apellido}
                                </p>
                                <p className="text-xs text-gray-500">
                                    DNI {p.dni} · {p.edad} años
                                </p>
                            </div>
                            {/* Paso 10: situación del requisito médico */}
                            <EstadoBadge valor={p.estado_requisito_medico} />
                        </li>
                    ))}
                </ul>
                {requierePendiente && (
                    <p className="text-xs text-gray-500">
                        Requisito médico pendiente: después de reservar, cargá el certificado de cada participante desde Mis reservas.
                    </p>
                )}
            </div>

            <div className="flex flex-col gap-2">
                <h3 className="text-sm font-semibold text-gray-900">Equipamiento</h3>
                <dl className="flex flex-col gap-1.5 rounded-xl border border-gray-200 px-4 py-3 text-sm">
                    <div className="flex justify-between gap-3">
                        <dt className="text-gray-500">Incluido por la salida</dt>
                        <dd className="text-right">{cargados.equipamiento_incluido.join(', ') || '—'}</dd>
                    </div>
                    <div className="flex justify-between gap-3">
                        <dt className="text-gray-500">Alquilado</dt>
                        <dd className="text-right">
                            {importe.equipamiento.map((e) => `${e.cantidad} × ${e.nombre}`).join(', ') || 'Ninguno'}
                        </dd>
                    </div>
                    <div className="flex justify-between gap-3">
                        <dt className="text-gray-500">Obligatorio por participante</dt>
                        <dd className="text-right">{obligatorio.join(', ') || '—'}</dd>
                    </div>
                </dl>
            </div>

            <div className="flex flex-col gap-1 rounded-xl bg-trekk-suave px-4 py-3 text-sm">
                <p className="text-gray-500">
                    Importe total = ({usd(importe.precio_paquete)} × {importe.cantidad_participantes}{' '}
                    {importe.cantidad_participantes === 1 ? 'participante' : 'participantes'})
                    {importe.equipamiento.length > 0 &&
                        ` + ${usd(importe.equipamiento.reduce((t, e) => t + e.subtotal, 0))} de equipamiento de alquiler`}
                </p>
                <p className="font-display text-2xl font-semibold">{usd(importe.importe_total)}</p>
            </div>
        </>
    );
}

// ---------- Resumen lateral ----------

function Resumen({ paquete, salida, excursion, cantidad, seleccion, importeTotal }) {
    const cantidadValida = Number(cantidad) || 0;

    return (
        <aside className="flex h-fit flex-col gap-4 rounded-2xl border border-trekk-borde bg-trekk-tarjeta p-5 lg:sticky lg:top-24">
            <p className="text-xs font-semibold uppercase tracking-widest text-trekk-acento">Resumen</p>
            <div>
                <h2 className="font-display text-xl font-semibold text-trekk-texto">{excursion.nombre}</h2>
                <p className="text-sm text-gray-500">
                    {formatFecha(salida.fecha_inicio)} · {salida.hora_de_salida} hs · Paquete {paquete.nombre}
                </p>
                {paquete.refugio && <p className="text-sm text-gray-500">Alojamiento en {paquete.refugio}</p>}
            </div>
            <dl className="flex flex-col gap-2 border-t border-gray-200 pt-4 text-sm">
                <div className="flex justify-between gap-3">
                    <dt className="text-gray-500">
                        {cantidadValida} × {usd(paquete.precio)}
                    </dt>
                    <dd>{usd(paquete.precio * cantidadValida)}</dd>
                </div>
                {seleccion.length === 0 ? (
                    <div className="flex justify-between gap-3">
                        <dt className="text-gray-500">Equipamiento de alquiler</dt>
                        <dd>{usd(0)}</dd>
                    </div>
                ) : (
                    seleccion.map((e) => (
                        <div key={e.id} className="flex justify-between gap-3">
                            <dt className="text-gray-500">
                                {e.cantidad} × {e.nombre}
                            </dt>
                            <dd>{usd(e.subtotal)}</dd>
                        </div>
                    ))
                )}
                <div className="flex justify-between border-t border-gray-200 pt-3 text-base font-semibold text-gray-900">
                    <dt>Importe total</dt>
                    <dd>{usd(importeTotal || 0)}</dd>
                </div>
            </dl>
            {excursion.requiere_certificado_medico && (
                <p className="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                    Requiere certificado médico vigente por participante, hasta 24 hs antes de la salida.
                </p>
            )}
        </aside>
    );
}

// ---------- Paso 21: la reserva fue registrada ----------

function ReservaRegistrada({ reserva, registrada, excursion, seleccion }) {
    return (
        <AuthenticatedLayout>
            <Head title="Reserva registrada" />

            <main className="mx-auto flex max-w-xl flex-col items-center gap-4 px-4 py-20 text-center">
                <svg
                    aria-hidden
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    className="size-14 text-trekk-primario"
                >
                    <circle cx="12" cy="12" r="10" />
                    <path d="m9 12 2 2 4-4" />
                </svg>
                <h1 className="font-display text-3xl font-semibold text-trekk-texto">Reserva {codigo(reserva.id)} registrada</h1>
                <div className="flex flex-wrap justify-center gap-2">
                    <EstadoBadge valor={registrada.estado} />
                    <EstadoBadge valor={registrada.situacion_de_pago} />
                </div>
                <p className="text-pretty text-gray-500">{registrada.mensaje}</p>
                <p className="text-sm text-gray-500">{excursion.nombre}</p>
                {seleccion.length > 0 && (
                    <p className="text-pretty text-sm text-gray-500">
                        Equipamiento alquilado: {seleccion.map((e) => `${e.cantidad} × ${e.nombre}`).join(', ')}. Debe
                        devolverse al finalizar la salida.
                    </p>
                )}
                <Link href={route('reservas.listar')} className={botonPrimario + ' !px-6 !py-2.5'}>
                    Ir a mis reservas
                </Link>
            </main>
        </AuthenticatedLayout>
    );
}

function IconoReloj() {
    return (
        <svg
            aria-hidden
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            className="size-4 shrink-0"
        >
            <line x1="10" x2="14" y1="2" y2="2" />
            <line x1="12" x2="15" y1="14" y2="11" />
            <circle cx="12" cy="14" r="8" />
        </svg>
    );
}
