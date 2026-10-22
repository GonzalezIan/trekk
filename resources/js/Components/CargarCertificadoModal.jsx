import Modal from '@/Components/Modal';
import { useEffect, useState } from 'react';

// CU 17 - Cargar certificado médico.
// Se abre desde el CU 21 - Listar participantes, sobre un participante de una
// reserva propia con el requisito médico "Pendiente".
export default function CargarCertificadoModal({ reserva, participante, onCerrar, onCargado }) {
    const [formatos, setFormatos] = useState(null);
    const [archivo, setArchivo] = useState(null);
    const [error, setError] = useState('');
    const [enviando, setEnviando] = useState(false);

    // Pasos 1 y 2: el sistema solicita el archivo en los formatos permitidos (RN-01)
    useEffect(() => {
        window.axios
            .get(route('reservas.certificado.cargar', [reserva.id, participante.dni]))
            .then((respuesta) => setFormatos(respuesta.data.formatos))
            .catch((e) => setError(e.response?.data?.message || 'No se pudo iniciar la carga del certificado.'));
    }, [reserva.id, participante.dni]);

    // Pasos 3 a 5: el cliente adjunta el archivo y el sistema lo registra.
    // Si falta (3.1) o no tiene un formato permitido (3.2), el sistema lo informa
    // y el cliente puede volver a adjuntar.
    const registrar = (e) => {
        e.preventDefault();
        setError('');
        setEnviando(true);

        const datos = new FormData();
        if (archivo) datos.append('archivo', archivo);

        window.axios
            .post(route('reservas.certificado.registrar', [reserva.id, participante.dni]), datos)
            .then((respuesta) => onCargado(respuesta.data.mensaje, respuesta.data.participante))
            .catch((e) =>
                setError(
                    e.response?.data?.errors?.archivo?.[0] ||
                        e.response?.data?.message ||
                        'No se pudo cargar el certificado.',
                ),
            )
            .finally(() => setEnviando(false));
    };

    const listaFormatos = formatos?.map((f) => f.toUpperCase()).join(', ');

    return (
        <Modal show onClose={onCerrar} maxWidth="lg">
            <form onSubmit={registrar} noValidate className="flex flex-col">
                <div className="border-b border-gray-200 px-5 py-4">
                    <h2 className="text-xl font-semibold text-gray-900">Cargar certificado médico</h2>
                    <p className="text-sm text-gray-500">
                        {participante.nombre} {participante.apellido} · DNI {participante.dni}
                    </p>
                </div>

                <div className="flex flex-col gap-3 px-5 py-4">
                    {!formatos && !error && <p className="text-sm text-gray-500">Cargando…</p>}

                    {formatos && (
                        <div className="flex flex-col gap-1.5">
                            <label htmlFor="archivo-certificado" className="text-sm font-medium text-gray-900">
                                Archivo del certificado
                            </label>
                            <input
                                id="archivo-certificado"
                                type="file"
                                accept={formatos.map((f) => `.${f}`).join(',')}
                                aria-invalid={error ? true : undefined}
                                aria-describedby="formatos-certificado"
                                onChange={(e) => {
                                    setArchivo(e.target.files?.[0] ?? null);
                                    setError('');
                                }}
                                className="block w-full rounded-lg border border-gray-200 text-sm text-gray-700 file:mr-3 file:cursor-pointer file:border-0 file:border-r file:border-gray-200 file:bg-gray-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-100 aria-[invalid=true]:border-red-300"
                            />
                            <p id="formatos-certificado" className="text-xs text-gray-500">
                                Formatos permitidos: {listaFormatos}.
                            </p>
                            {/* RN-04: la nueva carga reemplaza a la anterior */}
                            {participante.estado_certificado && (
                                <p className="text-xs text-gray-500">
                                    El archivo reemplazará al certificado cargado anteriormente.
                                </p>
                            )}
                        </div>
                    )}

                    {error && (
                        <p role="alert" className="text-sm text-red-600">
                            {error}
                        </p>
                    )}
                </div>

                <div className="flex flex-wrap items-center justify-end gap-2 border-t border-gray-200 px-5 py-4">
                    <button
                        type="button"
                        onClick={onCerrar}
                        className="inline-flex items-center rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        disabled={!formatos || enviando}
                        className="inline-flex items-center rounded-lg bg-emerald-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-800/80 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {enviando ? 'Cargando…' : 'Cargar certificado'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
