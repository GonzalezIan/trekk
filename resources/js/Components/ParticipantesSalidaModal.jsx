import EstadoBadge from '@/Components/EstadoBadge';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import { useEffect, useState } from 'react';

// CU 21 - Listar participantes (vista del Administrativo).
// Nómina de las reservas "Confirmada" de una Salida Programada.
// Se abre desde el CU 9 - Listar Salidas Programadas, por ejemplo:
//   <ParticipantesSalidaModal salida={{ id, excursion, fecha_inicio }} onCerrar={() => ...} />
export default function ParticipantesSalidaModal({ salida, onCerrar }) {
    const [participantes, setParticipantes] = useState(null);
    const [error, setError] = useState('');

    useEffect(() => {
        window.axios
            .get(route('salidas.participantes', salida.id))
            .then((respuesta) => setParticipantes(respuesta.data.participantes))
            .catch(() => setError('No se pudieron consultar los participantes.'));
    }, [salida.id]);

    return (
        <Modal show onClose={onCerrar}>
            <div className="flex flex-col gap-4 p-6">
                <div>
                    <h2 className="text-lg font-semibold text-gray-900">Participantes de la salida</h2>
                    <p className="text-sm text-gray-500">
                        {salida.excursion} · {salida.fecha_inicio} · reservas confirmadas
                    </p>
                </div>

                {error && <p className="text-sm text-red-600">{error}</p>}
                {!error && !participantes && <p className="text-sm text-gray-500">Cargando participantes…</p>}

                {participantes?.length === 0 && (
                    // 3.3 - No hay participantes aún
                    <p className="rounded-xl border border-dashed border-gray-300 p-6 text-center text-gray-500">
                        No hay participantes asociados a la Salida Programada aún.
                    </p>
                )}

                {participantes?.length > 0 && (
                    <div className="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
                        <table className="w-full text-sm">
                            <thead className="border-b border-gray-200 text-left text-gray-500">
                                <tr>
                                    {['Nombre', 'Apellido', 'DNI', 'Reserva', 'Requisito médico'].map((h) => (
                                        <th key={h} scope="col" className="px-4 py-3 font-medium">
                                            {h}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 [&_td]:px-4 [&_td]:py-3">
                                {participantes.map((p) => (
                                    <tr key={`${p.reserva_id}-${p.dni}`}>
                                        <td className="font-medium">{p.nombre}</td>
                                        <td>{p.apellido}</td>
                                        <td className="font-mono text-xs">{p.dni}</td>
                                        <td className="font-mono text-xs">
                                            R-{String(p.reserva_id).padStart(4, '0')}
                                        </td>
                                        <td>
                                            <EstadoBadge valor={p.estado_requisito_medico ?? 'NO_REQUERIDO'} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <div className="flex justify-end">
                    <SecondaryButton onClick={onCerrar}>Cerrar</SecondaryButton>
                </div>
            </div>
        </Modal>
    );
}
