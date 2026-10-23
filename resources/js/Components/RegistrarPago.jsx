import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

// CU 22 - Registrar pago de reserva.
//   Desde el CU 15: <PanelRegistrarPago> dentro de la tarjeta de la reserva (total o saldo restante).
//   Desde el CU 14: el mismo panel; si la reserva está "En curso" ofrece abonar solo la seña (1.1).

const formatUsd = (monto) => `USD ${monto.toLocaleString('es-AR')}`;
const formatArs = (monto) => `ARS ${monto.toLocaleString('es-AR')}`;

const campo = 'mt-1 block w-full rounded-lg border-gray-200 text-sm';

export function PanelRegistrarPago({ reserva, onPagado }) {
    const [datos, setDatos] = useState(null);
    const [sena, setSena] = useState(false);
    const [moneda, setMoneda] = useState('USD');
    const [medio, setMedio] = useState('');
    const [simularRechazo, setSimularRechazo] = useState(false);
    const [error, setError] = useState('');
    const [procesando, setProcesando] = useState(false);

    // Pasos 1 y 2: el sistema muestra el importe pendiente, en USD
    useEffect(() => {
        window.axios
            .get(route('reservas.pago', reserva.id))
            .then((respuesta) => {
                setDatos(respuesta.data);
                setMedio(respuesta.data.medios_pago[0]);
            })
            .catch((e) => setError(e.response?.data?.message || 'No se pudo iniciar el pago.'));
    }, [reserva.id]);

    // Pasos 3 a 7: la pasarela procesa el cobro y, si lo aprueba, se registra el pago.
    // Si lo rechaza (5.1), se informa y el cliente puede elegir otro medio de pago.
    const confirmar = (e) => {
        e.preventDefault();
        setError('');
        setProcesando(true);

        window.axios
            .post(route('reservas.pago.confirmar', reserva.id), {
                medio_pago: medio,
                moneda,
                sena,
                simular_rechazo: simularRechazo,
            })
            .then((respuesta) =>
                // Se vuelve a consultar el listado para ver la nueva situación de pago
                router.reload({ only: ['reservas'], onFinish: () => onPagado(respuesta.data.mensaje) }),
            )
            .catch((e) => {
                setError(e.response?.data?.message || 'No se pudo registrar el pago.');
                setProcesando(false);
            });
    };

    if (!datos) {
        return (
            <div className="border-t border-gray-200 bg-gray-50 p-5 text-sm">
                {error ? <AvisoError mensaje={error} /> : <p className="text-gray-500">Cargando…</p>}
            </div>
        );
    }

    const importeUsd = sena ? datos.importe_sena : datos.importe_pendiente;
    const tipo = sena
        ? 'Seña (50% del total)'
        : reserva.situacion_de_pago === 'PENDIENTE_DE_PAGO_TOTAL'
          ? 'Saldo restante'
          : 'Importe total';

    return (
        <form onSubmit={confirmar} noValidate className="flex flex-col gap-4 border-t border-gray-200 bg-gray-50 p-5">
            <div className="flex items-baseline justify-between">
                <span className="text-sm text-gray-600">{tipo}</span>
                <span className="text-lg font-semibold text-gray-900">{formatUsd(importeUsd)}</span>
            </div>

            {/* Alternativo 1.1: solo al registrar la reserva se puede abonar la seña */}
            {datos.importe_sena !== null && (
                <div className="flex gap-6 text-sm">
                    <label className="flex items-center gap-2">
                        <input type="radio" checked={!sena} onChange={() => setSena(false)} />
                        Pago total
                    </label>
                    <label className="flex items-center gap-2">
                        <input type="radio" checked={sena} onChange={() => setSena(true)} />
                        Solo la seña
                    </label>
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2">
                <label className="text-sm font-medium text-gray-900">
                    Moneda
                    <select value={moneda} onChange={(e) => setMoneda(e.target.value)} className={campo}>
                        <option value="USD">Dólares (USD)</option>
                        <option value="ARS">Pesos argentinos (ARS)</option>
                    </select>
                </label>
                <label className="text-sm font-medium text-gray-900">
                    Medio de pago
                    <select
                        value={medio}
                        onChange={(e) => {
                            setMedio(e.target.value);
                            setError('');
                        }}
                        className={campo}
                    >
                        {datos.medios_pago.map((m) => (
                            <option key={m}>{m}</option>
                        ))}
                    </select>
                </label>
            </div>

            <p className="rounded-lg bg-white px-4 py-3 text-sm text-gray-700">
                Se cobrarán{' '}
                <strong>
                    {moneda === 'ARS'
                        ? formatArs(importeUsd * datos.tasa_usd_ars)
                        : formatUsd(importeUsd)}
                </strong>
                {moneda === 'ARS' && ' (tasa fija 1 USD = 1.500 ARS)'} mediante la Pasarela de Pagos.
            </p>

            <label className="flex items-center gap-2 text-xs text-gray-500">
                <input
                    type="checkbox"
                    checked={simularRechazo}
                    onChange={(e) => setSimularRechazo(e.target.checked)}
                />
                Demo: simular que la pasarela rechaza la transacción
            </label>

            <AvisoError mensaje={error} />

            <div className="flex justify-end">
                <button
                    type="submit"
                    disabled={procesando}
                    className="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700 disabled:opacity-50"
                >
                    {procesando ? 'Procesando pago…' : 'Confirmar pago'}
                </button>
            </div>
        </form>
    );
}

function AvisoError({ mensaje }) {
    if (!mensaje) return null;
    return (
        <p role="alert" className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {mensaje}
        </p>
    );
}