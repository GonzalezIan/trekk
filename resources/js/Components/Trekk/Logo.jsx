import { IconoMontana } from './Iconos';

// Marca de Trekk: montaña en un cuadrado verde + nombre (como en el prototipo)
export default function Logo({ tamano = 'md', className = '' }) {
    const grande = tamano === 'lg';

    return (
        <span className={`flex items-center gap-2 font-display font-semibold text-trekk-texto ${grande ? 'text-2xl' : 'text-xl'} ${className}`}>
            <span
                className={`flex items-center justify-center rounded-lg bg-trekk-primario text-trekk-primario-texto ${grande ? 'h-9 w-9' : 'h-8 w-8'}`}
            >
                <IconoMontana className={grande ? 'h-5 w-5' : 'h-4 w-4'} />
            </span>
            Trekk
        </span>
    );
}
