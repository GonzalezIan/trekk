import { useState } from 'react';
import { router } from '@inertiajs/react';

export default function Cancelar({ paquete, onCerrar }) {
    const [motivo, setMotivo] = useState('');
    const [procesando, setProcesando] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        setProcesando(true);
        
        router.post(route('paquetes.cancelar.confirmar', paquete.id), {
            motivo_cancelacion: motivo
        }, {
            onSuccess: () => {
                setProcesando(false);
                onCerrar();
            },
            onError: () => setProcesando(false)
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <h2 className="text-xl font-bold text-gray-900 mb-2">Cancelar Paquete</h2>
                
                <p className="text-gray-600 text-sm mb-4">
                    ¿Estás seguro de cancelar <strong>{paquete.nombre}</strong>?
                </p>

                {/* Alerta si tiene reservas activas */}
                {paquete.reservas_activas > 0 && (
                    <div className="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-md border border-red-100">
                        ⚠️ <strong>Atención:</strong> Este paquete tiene {paquete.reservas_activas} reserva(s) activa(s). Al cancelarlo, se cancelarán automáticamente y se notificará a los clientes.
                    </div>
                )}

                <form onSubmit={handleSubmit}>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Justificación (Obligatoria)
                    </label>
                    <textarea
                        className="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm"
                        rows="4"
                        placeholder="Explicá el motivo de la cancelación..."
                        value={motivo}
                        onChange={(e) => setMotivo(e.target.value)}
                        required
                    />

                    <div className="flex justify-end gap-3 mt-6">
                        <button type="button" onClick={onCerrar} className="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                            Volver
                        </button>
                        <button 
                            type="submit" 
                            disabled={procesando || motivo.trim().length < 5} 
                            className="px-4 py-2 text-sm font-medium bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {procesando ? 'Cancelando...' : 'Confirmar Cancelación'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}