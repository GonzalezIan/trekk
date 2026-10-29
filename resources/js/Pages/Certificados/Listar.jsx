import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
// Importamos el modal de Carolina
import VerificarCertificadoModal from '@/Components/VerificarCertificadoModal'; 

export default function ListarPendientes({ auth, certificados }) {
    const [seleccionado, setSeleccionado] = useState(null);
    const [mensajeExito, setMensajeExito] = useState('');

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Certificados Pendientes" />
            
            <main className="mx-auto max-w-5xl px-4 py-12">
                <div className="flex flex-col gap-2 pb-6">
                    <p className="text-xs font-semibold uppercase tracking-widest text-trekk-acento">Administración</p>
                    <h1 className="font-display text-3xl font-semibold text-trekk-texto md:text-4xl">Certificados Médicos</h1>
                    <p className="max-w-2xl text-gray-500">Verificá los certificados médicos de las reservas pendientes.</p>
                </div>

                {/* Aviso de éxito tras verificar */}
                {mensajeExito && (
                    <div className="mb-4 flex items-start gap-2 rounded-xl border border-trekk-primario/20 bg-trekk-secundario/60 px-4 py-3 text-sm text-trekk-primario-hover">
                        <span className="flex-1">{mensajeExito}</span>
                        <button onClick={() => setMensajeExito('')} className="text-trekk-primario/70 hover:text-trekk-primario-hover">✕</button>
                    </div>
                )}

                {certificados.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-gray-300 p-10 text-center">
                        <p className="text-gray-500">No hay certificados pendientes de verificación.</p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-2xl border border-gray-200 bg-trekk-tarjeta">
                        <table className="w-full text-sm">
                            <thead className="border-b border-gray-200 text-left text-gray-500">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Participante</th>
                                    <th className="px-4 py-3 font-medium">DNI</th>
                                    <th className="px-4 py-3 font-medium">Reserva</th>
                                    <th className="px-4 py-3 font-medium">Acción</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 [&_td]:px-4 [&_td]:py-3">
                                {certificados.map((cert) => (
                                    <tr key={cert.id}>
                                        <td className="font-medium text-gray-900">
                                            {cert.participante.nombre} {cert.participante.apellido}
                                        </td>
                                        <td>{cert.participante.dni}</td>
                                        <td className="font-mono text-xs">R-{String(cert.participante.reserva_id).padStart(4, '0')}</td>
                                        <td>
                                            {/* Botón que abre el modal */}
                                            <button
                                                onClick={() => setSeleccionado({
                                                    reserva_id: cert.participante.reserva_id,
                                                    dni: cert.participante.dni
                                                })}
                                                className="rounded-lg bg-trekk-primario px-3 py-1.5 text-sm font-medium text-trekk-primario-texto hover:bg-trekk-primario-hover"
                                            >
                                                Verificar
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </main>

            {/* Conexión con el Modal de Carolina */}
            {seleccionado && (
                <VerificarCertificadoModal
                    certificado={{ 
                        reserva_id: seleccionado.reserva_id, 
                        dni: seleccionado.dni 
                    }}
                    onCerrar={() => setSeleccionado(null)}
                    onVerificado={(mensaje) => {
                        setSeleccionado(null);
                        setMensajeExito(mensaje);
                        router.reload({ only: ['certificados'] });
                    }}
                />
            )}
        </AuthenticatedLayout>
    );
}