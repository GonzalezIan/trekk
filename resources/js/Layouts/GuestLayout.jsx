import Logo from '@/Components/Trekk/Logo';
import { Link } from '@inertiajs/react';

// Pantallas sin sesión (login, registro, recuperar contraseña), con el estilo del prototipo
export default function GuestLayout({ children, titulo = null, subtitulo = null }) {
    return (
        <div className="relative isolate flex min-h-screen flex-col items-center bg-trekk-fondo px-4 py-10 text-trekk-texto sm:justify-center">
            <img
                src="/images/hero.jpg"
                alt=""
                className="absolute inset-x-0 top-0 -z-10 h-80 w-full object-cover"
            />
            <div className="absolute inset-x-0 top-0 -z-10 h-80 bg-gradient-to-t from-trekk-fondo via-trekk-fondo/60 to-trekk-fondo/10" />

            <Link href="/" className="rounded-lg">
                <Logo tamano="lg" />
            </Link>

            <div className="mt-6 w-full rounded-2xl border border-trekk-borde bg-trekk-tarjeta px-6 py-6 shadow-xl sm:max-w-md">
                {titulo && (
                    <div className="mb-5 flex flex-col gap-1">
                        <h1 className="font-display text-2xl font-semibold">{titulo}</h1>
                        {subtitulo && <p className="text-sm text-trekk-texto-suave">{subtitulo}</p>}
                    </div>
                )}
                {children}
            </div>
        </div>
    );
}
