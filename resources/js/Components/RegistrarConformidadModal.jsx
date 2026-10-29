import { useState } from 'react';
import { router } from '@inertiajs/react';

export default function RegistrarConformidadModal({ reserva, onCerrar }) {
    const [valoracion, setValoracion] = useState(0);
    const [hover, setHover] = useState(0);
    const [comentario, setComentario] = useState('');
    const [procesando, setProcesando] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        if (valoracion === 0) return alert('Por favor, seleccioná una puntuación.');
        
        setProcesando(true);
        router.post(route('reservas.conformidad.guardar', reserva.id), {
            valoracion,
            comentario
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
            <div className="w-full max-w-lg rounded-2xl border border-trekk-borde bg-trekk-tarjeta p-6 shadow-xl">
                <h2 className="font-display text-2xl font-semibold text-trekk-texto mb-1">Calificá tu experiencia</h2>
                <p className="text-gray-500 text-sm mb-6">
                    Contanos cómo te fue en: <strong>{reserva.excursion}</strong>
                </p>

                <form onSubmit={handleSubmit}>
                    {/* Estrellas */}
                    <div className="mb-6 flex justify-center gap-2">
                        {[1, 2, 3, 4, 5].map((estrella) => (
                            <button
                                key={estrella}
                                type="button"
                                onClick={() => setValoracion(estrella)}
                                onMouseEnter={() => setHover(estrella)}
                                onMouseLeave={() => setHover(0)}
                                className="text-4xl transition-transform hover:scale-110 focus:outline-none"
                            >
                                <span className={estrella <= (hover || valoracion) ? 'text-trekk-acento' : 'text-gray-300'}>
                                    ★
                                </span>
                            </button>
                        ))}
                    </div>

                    <div className="mb-6">
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Comentario (Opcional)
                        </label>
                        <textarea
                            className="w-full rounded-lg border-gray-300 shadow-sm focus:border-trekk-primario focus:ring-trekk-primario"
                            rows="4"
                            placeholder="¿Qué te pareció la excursión, el guía, el refugio...?"
                            value={comentario}
                            onChange={(e) => setComentario(e.target.value)}
                        />
                    </div>

                    <div className="flex justify-end gap-3">
                        <button type="button" onClick={onCerrar} className="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            disabled={procesando} 
                            className="px-6 py-2 text-sm font-medium bg-trekk-primario text-trekk-primario-texto rounded-lg hover:bg-trekk-primario-hover disabled:opacity-50"
                        >
                            {procesando ? 'Enviando...' : 'Enviar Calificación'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}