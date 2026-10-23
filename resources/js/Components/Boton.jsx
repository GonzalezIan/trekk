/**
 * Botón (portotipo: components/ui/button.tsx).
 *
 * Variantes: default, outline, secondary, ghost, destructive, link.
 * Tamaños: default, sm, lg, icon.
 */
const VARIANTES = {
    default:
        'bg-primary text-primary-foreground hover:bg-primary/80 focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50',
    outline:
        'border-border bg-background hover:bg-muted hover:text-foreground focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50',
    secondary:
        'bg-secondary text-secondary-foreground hover:bg-secondary/80 focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50',
    ghost:
        'hover:bg-muted hover:text-foreground focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50',
    destructive:
        'bg-destructive/10 text-destructive hover:bg-destructive/20 focus-visible:border-destructive/40 focus-visible:ring-2 focus-visible:ring-destructive/20',
    link: 'text-primary underline-offset-4 hover:underline',
};

const TAMANOS = {
    default: 'h-9 gap-1.5 px-3',
    sm: 'h-8 gap-1 rounded-md px-2.5 text-[0.8rem]',
    lg: 'h-10 gap-2 px-4',
    icon: 'size-9',
};

export default function Boton({
    className = '',
    variant = 'default',
    size = 'default',
    type = 'button',
    children,
    ...props
}) {
    return (
        <button
            type={type}
            className={[
                'group/button inline-flex shrink-0 items-center justify-center rounded-lg border border-transparent bg-clip-padding text-sm font-medium whitespace-nowrap transition-all outline-none select-none',
                'focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50',
                'aria-invalid:border-destructive aria-invalid:ring-2 aria-invalid:ring-destructive/20',
                VARIANTES[variant] ?? VARIANTES.default,
                TAMANOS[size] ?? TAMANOS.default,
                className,
            ].join(' ')}
            {...props}
        >
            {children}
        </button>
    );
}
