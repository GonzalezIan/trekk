import { Link, router, usePage } from '@inertiajs/react';
import Boton from '@/Components/Boton';

const NAV_CLIENTE = [
    { label: 'Excursiones', ruta: 'cliente.excursiones.index' },
    { label: 'Mis reservas', ruta: 'cliente.reservas.index' },
    { label: 'Mis datos', ruta: 'perfil.edit' },
];

const NAV_ADMIN = [
    { label: 'Panel', ruta: 'admin.inicio' },
    { label: 'Oferta', ruta: 'admin.excursiones.index' },
    { label: 'Reservas', ruta: 'admin.reservas.index' },
    { label: 'Certificados', ruta: 'admin.certificados.index' },
    { label: 'Reportes', ruta: 'admin.reportes.index' },
];

/**
 * Encabezado del sitio (portotipo: components/site-header.tsx).
 *
 * El catálogo y los listados requieren sesión (CU 4, 7, 9 y 15), así que el
 * invitado no tiene menú.
 */
export default function EncabezadoSitio() {
    const { auth } = usePage().props;

    const esAdmin = Boolean(auth?.esAdministrativo);
    const esCliente = Boolean(auth?.esCliente);

    const navegacion = esAdmin ? NAV_ADMIN : esCliente ? NAV_CLIENTE : [];

    return (
        <header className="sticky top-0 z-40 border-b bg-background/85 backdrop-blur">
            <div className="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4">
                <Link
                    href={esAdmin ? route('admin.inicio') : '/'}
                    className="flex items-center gap-2 font-display text-xl font-semibold"
                >
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
                    {esAdmin && (
                        <span className="rounded-md bg-accent px-1.5 py-0.5 font-sans text-[10px] font-semibold uppercase tracking-wide text-accent-foreground">
                            Admin
                        </span>
                    )}
                </Link>

                <nav aria-label="Principal" className="hidden flex-1 items-center gap-1 md:flex">
                    {navegacion.map((item) => (
                        <Link
                            key={item.ruta}
                            href={route(item.ruta)}
                            className="rounded-md px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>

                <div className="ml-auto flex items-center gap-2">
                    {!esAdmin && !esCliente ? (
                        <>
                            <Link href="/login">
                                <Boton variant="ghost" size="sm">
                                    Iniciar sesión
                                </Boton>
                            </Link>
                            <Link href="/register">
                                <Boton size="sm">Registrarse</Boton>
                            </Link>
                        </>
                    ) : (
                        <>
                            <span className="hidden text-sm text-muted-foreground sm:inline">
                                {esAdmin
                                    ? 'Personal administrativo'
                                    : `${auth?.user?.nombre_usuario ?? ''}`}
                            </span>
                            <Boton
                                variant="outline"
                                size="sm"
                                onClick={() => router.post(route('logout'))}
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
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
                                </svg>
                                Salir
                            </Boton>
                        </>
                    )}
                </div>
            </div>

            {navegacion.length > 1 && (
                <nav
                    aria-label="Principal móvil"
                    className="flex gap-1 overflow-x-auto border-t px-4 py-2 md:hidden"
                >
                    {navegacion.map((item) => (
                        <Link
                            key={item.ruta}
                            href={route(item.ruta)}
                            className="shrink-0 rounded-md px-3 py-1 text-sm text-muted-foreground hover:text-foreground"
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>
            )}
        </header>
    );
}
