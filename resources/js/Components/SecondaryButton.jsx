export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={
                `inline-flex items-center justify-center gap-1.5 rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2 text-sm font-medium text-trekk-texto transition duration-150 ease-in-out hover:bg-trekk-suave focus:outline-none focus:ring-2 focus:ring-trekk-primario/40 focus:ring-offset-2 disabled:opacity-25 ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
