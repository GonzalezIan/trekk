import EstadoBadge from '@/Components/EstadoBadge';
import { useEffect, useState } from 'react';

// CU 21 - Listar participantes (vista del Cliente).
// Panel que se despliega dentro de la tarjeta de una reserva propia (CU 15).
export default function ParticipantesReserva({ reserva }) {
    const [datos, setDatos] = useState(null);
    const [error, setError] = useState('');

    useEffect(() => {
        window.axios
            .get(route('reservas.participantes', reserva.id))
            .then((respuesta) => setDatos(respuesta.data))
            .catch(() => setError('No se pudieron consultar los participantes.'));
    }, [reserva.id]);

    if (error) return <p className="text-sm text-red-600">{error}</p>;
    if (!datos) return <p className="text-sm text-gray-500">Cargando participantes…</p>;

    // 3.3 - No hay participantes aún
    if (datos.participantes.length === 0) {
        return <p className="text-sm text-gray-500">La reserva no tiene participantes aún.</p>;
    }

    // 4.1 - Se puede cargar el certificado si el requisito está pendiente,
    // faltan más de 24 hs para la salida y la reserva no está cancelada
    const puedeCargar = (p) =>
        p.estado_requisito_medico === 'PENDIENTE' &&
        datos.puedeCargarCertificado &&
        reserva.estado !== 'CANCELADA';

    return (
        <ul className="flex flex-col divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
            {datos.participantes.map((p) => (
                <li
                    key={p.dni}
                    className="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p className="font-medium text-gray-900">
                            {p.nombre} {p.apellido}
                        </p>
                        <p className="text-xs text-gray-500">DNI {p.dni}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <EstadoBadge valor={p.estado_requisito_medico ?? 'NO_REQUERIDO'} />
                        {puedeCargar(p) && (
                            <button
                                type="button"
                                disabled
                                title="Se habilita con el CU 17"
                                className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Cargar certificado
                            </button>
                        )}
                    </div>
                </li>
            ))}
        </ul>
    );
}
