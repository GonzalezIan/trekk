export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-trekk-borde text-trekk-primario shadow-sm focus:ring-trekk-primario ' +
                className
            }
        />
    );
}
