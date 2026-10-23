/**
 * Campo de texto (portotipo: components/ui/input.tsx).
 */
export default function Campo({ className = '', type = 'text', ...props }) {
    return (
        <input
            type={type}
            className={[
                'h-9 w-full min-w-0 rounded-lg border border-input bg-background px-2.5 py-1 text-base transition-colors outline-none',
                'placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50',
                'disabled:cursor-not-allowed disabled:bg-input/50 disabled:opacity-50',
                'aria-invalid:border-destructive aria-invalid:ring-2 aria-invalid:ring-destructive/20',
                'md:text-sm',
                className,
            ].join(' ')}
            {...props}
        />
    );
}
