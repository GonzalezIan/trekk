import { Link } from '@inertiajs/react';
import { IconoVolver } from './Iconos';

// Encabezado de los formularios del Administrativo: volver, título y una línea de contexto
export default function EncabezadoFormulario({ volverA, volverTexto, titulo, descripcion }) {
    return (
        <div className="flex flex-col gap-1">
            <Link
                href={volverA}
                className="flex w-fit items-center gap-1 text-sm text-trekk-texto-suave hover:text-trekk-texto"
            >
                <IconoVolver className="h-4 w-4" /> {volverTexto}
            </Link>
            <h2 className="font-display text-2xl font-semibold text-trekk-texto">{titulo}</h2>
            {descripcion && <p className="text-sm text-trekk-texto-suave">{descripcion}</p>}
        </div>
    );
}
