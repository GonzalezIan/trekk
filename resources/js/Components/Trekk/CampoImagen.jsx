import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import { useEffect, useState } from 'react';

// Imagen de la excursión (CU 1 y CU 3), con vista previa del archivo elegido.
// `actual` es la imagen ya guardada, que se conserva si no se elige otra.
export default function CampoImagen({ archivo, actual = null, onChange, error }) {
    const [vistaPrevia, setVistaPrevia] = useState(null);

    useEffect(() => {
        if (!archivo) {
            setVistaPrevia(null);
            return undefined;
        }

        const url = URL.createObjectURL(archivo);
        setVistaPrevia(url);

        return () => URL.revokeObjectURL(url);
    }, [archivo]);

    const imagen = vistaPrevia ?? actual;

    return (
        <div>
            <InputLabel htmlFor="imagen" value="Imagen de la excursión (opcional)" />

            {imagen && (
                <img
                    src={imagen}
                    alt="Vista previa de la imagen de la excursión"
                    className="mt-2 aspect-[16/10] w-full max-w-sm rounded-lg object-cover"
                />
            )}

            <input
                id="imagen"
                type="file"
                accept=".jpg,.jpeg,.png,.webp"
                aria-invalid={error ? true : undefined}
                onChange={(e) => onChange(e.target.files?.[0] ?? null)}
                className="mt-2 block w-full rounded-lg border border-trekk-borde text-sm text-trekk-texto file:mr-3 file:cursor-pointer file:border-0 file:border-r file:border-trekk-borde file:bg-trekk-suave file:px-3 file:py-2 file:text-sm file:font-medium file:text-trekk-texto aria-[invalid=true]:border-red-300"
            />
            <p className="mt-1 text-xs text-gray-500">
                JPG, PNG o WEBP, hasta 2 MB.{' '}
                {actual
                    ? 'La imagen que elijas reemplazará a la actual.'
                    : 'Si no elegís una, se muestra una foto genérica.'}
            </p>
            <InputError message={error} className="mt-2" />
        </div>
    );
}
