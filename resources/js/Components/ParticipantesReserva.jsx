import CargarCertificadoModal from '@/Components/CargarCertificadoModal';
import EstadoBadge from '@/Components/EstadoBadge';
import { useEffect, useState } from 'react';

// CU 21 - Listar participantes (vista del Cliente).
// Panel que se despliega dentro de la tarjeta de una reserva propia (CU 15).
export default function ParticipantesReserva({ reserva }) {
    const [datos, setDatos] = useState(null);
    const [error, setError] = useState('');
    const [seleccionado, setSeleccionado] = useState(null);
    const [mensaje, setMensaje] = useState('');

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

    // 4.1 - Se puede cargar el certificado si el requisito está pendiente y la
    // reserva lo admite ("Pendiente" y a más de 24 hs de la salida, lo decide el servidor)
    const puedeCargar = (p) => p.estado_requisito_medico === 'PENDIENTE' && datos.puedeCargarCertificado;

    // CU 17 - paso 5: se informa la carga y se actualiza el participante en la lista
    const certificadoCargado = (texto, participante) => {
        setDatos({
            ...datos,
            participantes: datos.participantes.map((p) => (p.dni === participante.dni ? participante : p)),
        });
        setSeleccionado(null);
        setMensaje(texto);
    };

    return (
        <>
            <ul className="flex flex-col divide-y divide-gray-200 rounded-xl border border-gray-200 bg-trekk-tarjeta">
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
                            {p.estado_certificado && p.estado_requisito_medico === 'PENDIENTE' && (
                                <EstadoBadge valor={p.estado_certificado} />
                            )}
                            {puedeCargar(p) && (
                                // 4.1 - CU 17 Cargar certificado médico
                                <button
                                    type="button"
                                    onClick={() => {
                                        setMensaje('');
                                        setSeleccionado(p);
                                    }}
                                    className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium hover:bg-gray-100"
                                >
                                    {p.estado_certificado ? 'Reemplazar certificado' : 'Cargar certificado'}
                                </button>
                            )}
                        </div>
                    </li>
                ))}
            </ul>

            {mensaje && (
                <p role="status" className="mt-3 text-sm text-gray-500">
                    {mensaje}
                </p>
            )}

            {seleccionado && (
                <CargarCertificadoModal
                    reserva={reserva}
                    participante={seleccionado}
                    onCerrar={() => setSeleccionado(null)}
                    onCargado={certificadoCargado}
                />
            )}
        </>
    );
}
