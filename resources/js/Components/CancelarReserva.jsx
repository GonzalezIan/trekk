import Modal from '@/Components/Modal';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

// CU 16 - Cancelar reserva. Se invoca desde el CU 15 - Listar reservas.
//   Cliente: <PanelCancelarReserva> dentro de la tarjeta de la reserva.
//   Administrativo: <CancelarReservaModal> desde la barra de la reserva seleccionada.

const botonPeligro =
    'inline-flex items-center rounded-lg bg-red-600/10 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-600/20 disabled:cursor-not-allowed disabled:opacity-50';

function useCancelacion(reserva, onCancelada) {
    const [datos, setDatos] = useState(null);
    const [motivo, setMotivo] = useState('');
    const [error, setError] = useState('');
    const [enviando, setEnviando] = useState(false);

    // Pasos 1 a 3: el sistema obtiene los datos de la reserva y solicita el motivo
    useEffect(() => {
        window.axios
            .get(route('reservas.cancelar', reserva.id))
            .then((respuesta) => setDatos(respuesta.data.reserva))
            .catch((e) => setError(e.response?.data?.message || 'No se pudo iniciar la cancelación.'));
    }, [reserva.id]);

    // Pasos 4 a 7: registra el motivo, cancela y libera los recursos.
    // Si falta el motivo (4.1), el sistema lo informa y el usuario puede volver a ingresarlo.
    const confirmar = (e) => {
        e.preventDefault();
        setError('');
        setEnviando(true);

        window.axios
            .post(route('reservas.cancelar.confirmar', reserva.id), { motivo })
            .then((respuesta) =>
                // Se vuelve a consultar el listado para ver la reserva "Cancelada"
                router.reload({ only: ['reservas'], onFinish: () => onCancelada(respuesta.data.mensaje) }),
            )
            .catch((e) => {
                setError(
                    e.response?.data?.errors?.motivo?.[0] ||
                        e.response?.data?.message ||
                        'No se pudo cancelar la reserva.',
                );
                setEnviando(false);
            });
    };

    return { datos, motivo, setMotivo, error, enviando, confirmar };
}

function CampoMotivo({ id, motivo, setMotivo, error }) {
    return (
        <div className="flex flex-col gap-1.5">
            <label htmlFor={id} className="text-sm font-medium text-gray-900">
                Motivo de la cancelación
            </label>
            <textarea
                id={id}
                rows={3}
                value={motivo}
                aria-invalid={error ? true : undefined}
                onChange={(e) => setMotivo(e.target.value)}
                className="rounded-lg border-gray-200 text-sm aria-[invalid=true]:border-red-300"
            />
        </div>
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

// ---------- Cliente: panel dentro de la tarjeta (igual al prototipo) ----------

export function PanelCancelarReserva({ reserva, onCancelada }) {
    const { datos, motivo, setMotivo, error, enviando, confirmar } = useCancelacion(reserva, onCancelada);

    return (
        <form onSubmit={confirmar} noValidate className="flex flex-col gap-3 border-t border-gray-200 bg-red-50/50 p-5">
            {datos && (
                <>
                    <CampoMotivo id={`motivo-${reserva.id}`} motivo={motivo} setMotivo={setMotivo} error={error} />
                    <p className="text-xs text-gray-500">
                        La cancelación no genera reintegro de los importes abonados.
                    </p>
                </>
            )}
            <AvisoError mensaje={error} />
            <div className="flex justify-end">
                <button type="submit" disabled={!datos || enviando} className={botonPeligro}>
                    {enviando ? 'Cancelando…' : 'Confirmar cancelación'}
                </button>
            </div>
        </form>
    );
}

// ---------- Administrativo: modal (igual al prototipo) ----------

export function CancelarReservaModal({ reserva, onCerrar, onCancelada }) {
    const { datos, motivo, setMotivo, error, enviando, confirmar } = useCancelacion(reserva, onCancelada);

    return (
        <Modal show onClose={onCerrar} maxWidth="lg">
            <form onSubmit={confirmar} noValidate className="flex flex-col">
                <div className="border-b border-gray-200 px-5 py-4">
                    <h2 className="text-xl font-semibold text-gray-900">Cancelar reserva</h2>
                    <p className="text-sm text-gray-500">
                        R-{String(reserva.id).padStart(4, '0')} · {reserva.excursion}
                    </p>
                </div>

                <div className="flex flex-col gap-4 px-5 py-4">
                    {!datos && !error && <p className="text-sm text-gray-500">Cargando…</p>}
                    {datos && (
                        <>
                            <p className="text-sm text-gray-700">
                                La reserva pasará a estado “Cancelada” y se liberarán sus cupos, camas y
                                equipamiento de alquiler. No corresponde reintegro de los importes abonados.
                            </p>
                            <CampoMotivo id="motivo-cancelacion" motivo={motivo} setMotivo={setMotivo} error={error} />
                        </>
                    )}
                    <AvisoError mensaje={error} />
                </div>

                <div className="flex flex-wrap items-center justify-end gap-2 border-t border-gray-200 px-5 py-4">
                    <button
                        type="button"
                        onClick={onCerrar}
                        className="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Volver
                    </button>
                    <button type="submit" disabled={!datos || enviando} className={botonPeligro}>
                        {enviando ? 'Cancelando…' : 'Cancelar reserva'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
