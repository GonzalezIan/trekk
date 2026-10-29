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

export const IconoReloj = (props) => (
    <Icono {...props}>
        <circle cx="12" cy="12" r="10" />
        <path d="M12 6v6l4 2" />
    </Icono>
);

export const IconoUbicacion = (props) => (
    <Icono {...props}>
        <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
        <circle cx="12" cy="10" r="3" />
    </Icono>
);

export const IconoTendencia = (props) => (
    <Icono {...props}>
        <path d="M16 7h6v6M22 7l-8.5 8.5-5-5L2 17" />
    </Icono>
);

export const IconoEstetoscopio = (props) => (
    <Icono {...props}>
        <path d="M11 2v2M5 2v2M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1" />
        <path d="M8 15a6 6 0 0 0 12 0v-3" />
        <circle cx="20" cy="10" r="2" />
    </Icono>
);

export const IconoPersonas = (props) => (
    <Icono {...props}>
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
        <circle cx="9" cy="7" r="4" />
        <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
    </Icono>
);

export const IconoCasa = (props) => (
    <Icono {...props}>
        <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
        <path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
    </Icono>
);

export const IconoCheck = (props) => (
    <Icono {...props}>
        <path d="M20 6 9 17l-5-5" />
    </Icono>
);

export const IconoVolver = (props) => (
    <Icono {...props}>
        <path d="m12 19-7-7 7-7M19 12H5" />
    </Icono>
);
