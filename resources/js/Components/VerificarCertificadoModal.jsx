import Modal from '@/Components/Modal';
import { useEffect, useState } from 'react';

// CU 19 - Verificar certificado médico (vista del Administrativo).
// Se abre desde el CU 18 - Listar certificados médicos pendientes, por ejemplo:
//   <VerificarCertificadoModal
//       certificado={{ reserva_id, dni }}
//       onCerrar={() => ...}
//       onVerificado={(mensaje) => ...}   // por ejemplo, recargar la lista y mostrar el mensaje
//   />

const formatFecha = (fecha) =>
    new Date(`${fecha}T12:00:00`).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

const sumarDias = (fecha, dias) => {
    const d = new Date(`${fecha}T12:00:00`);
    d.setDate(d.getDate() + dias);
    return d.toISOString().slice(0, 10);
};

const decisiones = [
    ['VALIDO', 'Es un certificado médico válido', 'Ingresar la fecha de emisión para calcular la vigencia.'],
    ['INVALIDO', 'No corresponde a un certificado válido', 'Ilegible, formato incorrecto o de otra persona.'],
];

export default function VerificarCertificadoModal({ certificado: { reserva_id, dni }, onCerrar, onVerificado }) {
    const [datos, setDatos] = useState(null);
    const [decision, setDecision] = useState(null);
    const [fecha, setFecha] = useState('');
    const [motivo, setMotivo] = useState('');
    const [error, setError] = useState('');
    const [enviando, setEnviando] = useState(false);

    // Pasos 1 y 2: el sistema muestra los datos del certificado y el archivo adjunto
    useEffect(() => {
        window.axios
            .get(route('reservas.certificado.verificar', [reserva_id, dni]))
            .then((respuesta) => setDatos(respuesta.data.certificado))
            .catch((e) => setError(e.response?.data?.message || 'No se pudo consultar el certificado.'));
    }, [reserva_id, dni]);

    // Vista previa de la vigencia (RN-01). La verificación definitiva la hace el servidor.
    const vigencia = datos && fecha ? sumarDias(fecha, datos.dias_de_vigencia) : '';
    const cubre = vigencia && vigencia >= datos.fecha_fin;

    // Paso 3: el administrativo decide. Válido -> pasos 4 a 9; inválido -> 3.1
    const confirmar = (e) => {
        e.preventDefault();
        setError('');
        setEnviando(true);

        const pedido =
            decision === 'VALIDO'
                ? window.axios.post(route('reservas.certificado.fecha-emision', [reserva_id, dni]), {
                      fecha_emision: fecha,
                  })
                : window.axios.post(route('reservas.certificado.invalidez', [reserva_id, dni]), { motivo });

        pedido
            .then((respuesta) => onVerificado(respuesta.data.mensaje))
            .catch((e) => {
                const errores = e.response?.data?.errors;
                setError(
                    errores?.fecha_emision?.[0] ||
                        errores?.motivo?.[0] ||
                        e.response?.data?.message ||
                        'No se pudo registrar la verificación.',
                );
                setEnviando(false);
            });
    };

    const filas = datos && [
        ['Participante', `${datos.participante.nombre} ${datos.participante.apellido} · DNI ${datos.participante.dni}`],
        ['Reserva', `R-${String(datos.reserva_id).padStart(4, '0')}`],
        ['Excursión', `${datos.excursion} · ${datos.paquete}`],
        ['Salida', `${formatFecha(datos.fecha_inicio)} → ${formatFecha(datos.fecha_fin)}`],
        ['Fecha de carga', datos.fecha_de_carga ? formatFecha(datos.fecha_de_carga) : '—'],
    ];

    return (
        <Modal show onClose={onCerrar} maxWidth="5xl">
            <form onSubmit={confirmar} noValidate className="flex max-h-[90dvh] flex-col">
                <div className="border-b border-gray-200 px-5 py-4">
                    <h2 className="text-xl font-semibold text-gray-900">Verificar certificado médico</h2>
                    {datos && <p className="text-sm text-gray-500">{datos.archivo.nombre}</p>}
                </div>

                <div className="min-h-0 overflow-y-auto px-5 py-4">
                    {!datos && !error && <p className="text-sm text-gray-500">Cargando…</p>}
                    {!datos && error && (
                        <p role="alert" className="text-sm text-red-600">
                            {error}
                        </p>
                    )}

                    {datos && (
                        <div className="grid gap-5 md:grid-cols-[1fr_22rem]">
                            {/* Paso 2: archivo adjunto */}
                            <figure className="flex flex-col gap-2">
                                <div className="aspect-[3/4] max-h-[60dvh] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-inner">
                                    {datos.archivo.es_pdf ? (
                                        <iframe
                                            src={datos.url_archivo}
                                            title="Certificado médico"
                                            className="h-full w-full"
                                        />
                                    ) : (
                                        <img
                                            src={datos.url_archivo}
                                            alt="Certificado médico adjunto"
                                            className="h-full w-full object-contain"
                                        />
                                    )}
                                </div>
                                <figcaption className="text-xs text-gray-500">
                                    Archivo adjunto:{' '}
                                    <a
                                        href={datos.url_archivo}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="underline hover:text-gray-700"
                                    >
                                        abrir en otra pestaña
                                    </a>
                                </figcaption>
                            </figure>

                            <div className="flex flex-col gap-4">
                                <dl className="flex flex-col gap-2 rounded-xl bg-gray-100 px-4 py-3 text-sm">
                                    {filas.map(([k, v]) => (
                                        <div key={k} className="flex flex-col">
                                            <dt className="text-xs text-gray-500">{k}</dt>
                                            <dd className="font-medium text-gray-900">{v}</dd>
                                        </div>
                                    ))}
                                </dl>

                                {/* Paso 3: el administrativo revisa el archivo y decide */}
                                <div role="radiogroup" aria-label="Resultado de la revisión" className="flex flex-col gap-2">
                                    {decisiones.map(([valor, titulo, texto]) => (
                                        <button
                                            key={valor}
                                            type="button"
                                            role="radio"
                                            aria-checked={decision === valor}
                                            onClick={() => {
                                                setDecision(valor);
                                                setError('');
                                            }}
                                            className={
                                                'flex items-start gap-3 rounded-xl border p-3 text-left transition-colors hover:border-emerald-800/50 ' +
                                                (decision === valor
                                                    ? 'border-emerald-800 ring-1 ring-emerald-800'
                                                    : 'border-gray-200')
                                            }
                                        >
                                            <span
                                                aria-hidden
                                                className={
                                                    'mt-0.5 text-base leading-none ' +
                                                    (valor === 'VALIDO' ? 'text-emerald-700' : 'text-red-600')
                                                }
                                            >
                                                {valor === 'VALIDO' ? '✓' : '✕'}
                                            </span>
                                            <span>
                                                <span className="block text-sm font-medium text-gray-900">{titulo}</span>
                                                <span className="block text-xs text-gray-500">{texto}</span>
                                            </span>
                                        </button>
                                    ))}
                                </div>

                                {/* Pasos 4 y 5: fecha de emisión */}
                                {decision === 'VALIDO' && (
                                    <div className="flex flex-col gap-3">
                                        <div className="flex flex-col gap-1.5">
                                            <label htmlFor="cm-fecha" className="text-sm font-medium text-gray-900">
                                                Fecha de emisión
                                            </label>
                                            <input
                                                id="cm-fecha"
                                                type="date"
                                                value={fecha}
                                                aria-invalid={error ? true : undefined}
                                                onChange={(e) => {
                                                    setFecha(e.target.value);
                                                    setError('');
                                                }}
                                                className="rounded-lg border-gray-200 text-sm aria-[invalid=true]:border-red-300"
                                            />
                                        </div>
                                        {vigencia && (
                                            <p
                                                className={
                                                    'rounded-lg px-3 py-2 text-sm ' +
                                                    (cubre ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-700')
                                                }
                                            >
                                                Vigencia hasta el {formatFecha(vigencia)} (emisión +{' '}
                                                {datos.dias_de_vigencia} días).{' '}
                                                {cubre
                                                    ? 'Cubre el período de la salida.'
                                                    : 'No cubre el período completo de la salida.'}
                                            </p>
                                        )}
                                    </div>
                                )}

                                {/* 3.1: motivo de invalidez */}
                                {decision === 'INVALIDO' && (
                                    <div className="flex flex-col gap-1.5">
                                        <label htmlFor="cm-motivo" className="text-sm font-medium text-gray-900">
                                            Motivo de invalidez
                                        </label>
                                        <textarea
                                            id="cm-motivo"
                                            rows={3}
                                            value={motivo}
                                            aria-invalid={error ? true : undefined}
                                            onChange={(e) => {
                                                setMotivo(e.target.value);
                                                setError('');
                                            }}
                                            className="rounded-lg border-gray-200 text-sm aria-[invalid=true]:border-red-300"
                                        />
                                    </div>
                                )}

                                {error && (
                                    <p
                                        role="alert"
                                        className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
                                    >
                                        {error}
                                    </p>
                                )}
                            </div>
                        </div>
                    )}
                </div>

                <div className="flex flex-wrap items-center justify-end gap-2 border-t border-gray-200 px-5 py-4">
                    <button
                        type="button"
                        onClick={onCerrar}
                        className="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        disabled={!decision || enviando}
                        className={
                            'inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-50 ' +
                            (decision === 'INVALIDO'
                                ? 'bg-red-600/10 text-red-600 hover:bg-red-600/20'
                                : 'bg-emerald-800 text-white hover:bg-emerald-800/80')
                        }
                    >
                        {enviando
                            ? 'Registrando…'
                            : decision === 'INVALIDO'
                              ? 'Marcar como inválido'
                              : 'Registrar verificación'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
