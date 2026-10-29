export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center justify-center gap-1.5 rounded-lg border border-transparent bg-trekk-primario px-4 py-2 text-sm font-medium text-trekk-primario-texto transition duration-150 ease-in-out hover:bg-trekk-primario-hover focus:outline-none focus:ring-2 focus:ring-trekk-primario/40 focus:ring-offset-2 ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
