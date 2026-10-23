import { Link } from '@inertiajs/react';

/**
 * Layout de las pantallas públicas (login y registro).
 *
 * El prototipo usa un encabezado propio en esas pantallas; acá se mantiene el
 * mismo lenguaje visual con el logo de Trekk y un enlace para volver a la portada.
 */
export default function LayoutInvitado({ children }) {
    return (
        <div className="flex min-h-dvh flex-col items-center bg-background px-4 py-10">
            <Link href="/" className="mb-8 flex items-center gap-2 font-display text-xl font-semibold">
                <span className="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                    <svg
                        className="size-4"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        aria-hidden
                    >
                        <path d="m8 3 4 8 5-5 5 15H2L8 3z" />
                    </svg>
                </span>
                Trekk
            </Link>

            <div className="w-full max-w-md">
                <Link
                    href="/"
                    className="mb-3 inline-flex items-center gap-1.5 rounded-md text-sm text-muted-foreground outline-none transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/50"
                >
                    <svg
                        className="size-4"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        aria-hidden
                    >
                        <path d="m12 19-7-7 7-7" />
                        <path d="M19 12H5" />
                    </svg>
                    Volver al inicio
                </Link>

                <div className="w-full rounded-2xl border bg-card p-6 text-card-foreground shadow-sm ring-1 ring-foreground/10">
                    {children}
                </div>
            </div>
        </div>
    );
}
