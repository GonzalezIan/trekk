/**
 * Etiqueta de formulario (portotipo: components/ui/label.tsx).
 */
export default function Etiqueta({ className = '', children, ...props }) {
    return (
        <label
            className={[
                'flex items-center gap-2 text-sm leading-none font-medium select-none',
                'peer-disabled:cursor-not-allowed peer-disabled:opacity-50',
                className,
            ].join(' ')}
            {...props}
        >
            {children}
        </label>
    );
}
