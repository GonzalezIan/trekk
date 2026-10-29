// Íconos de línea (de lucide.dev, como en el prototipo), sin agregar dependencias
function Icono({ className = 'h-5 w-5', children }) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
            aria-hidden="true"
        >
            {children}
        </svg>
    );
}

export const IconoMontana = (props) => (
    <Icono {...props}>
        <path d="m8 3 4 8 5-5 5 15H2L8 3z" />
    </Icono>
);

export const IconoUsuario = (props) => (
    <Icono {...props}>
        <circle cx="12" cy="8" r="5" />
        <path d="M20 21a8 8 0 0 0-16 0" />
    </Icono>
);

export const IconoCalendario = (props) => (
    <Icono {...props}>
        <rect width="18" height="18" x="3" y="4" rx="2" />
        <path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01" />
    </Icono>
);

export const IconoTarjeta = (props) => (
    <Icono {...props}>
        <rect width="20" height="14" x="2" y="5" rx="2" />
        <path d="M2 10h20" />
    </Icono>
);

export const IconoFlecha = (props) => (
    <Icono {...props}>
        <path d="M5 12h14M12 5l7 7-7 7" />
    </Icono>
);
