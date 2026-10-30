import Dropdown from '@/Components/Dropdown';
import Logo from '@/Components/Trekk/Logo';
import { Link, usePage } from '@inertiajs/react';

// Secciones del menú según el rol (prototipo: web-page-creation/components/site-header.tsx)
const secciones = (esAdministrativo) => [
    { nombre: 'Inicio', ruta: 'dashboard', activa: 'dashboard' },
    // El Cliente sigue en «Excursiones» mientras ve la ficha, sus salidas y paquetes
    {
        nombre: 'Excursiones',
        ruta: 'excursiones.listar',
        activa: esAdministrativo ? ['excursiones.*'] : ['excursiones.*', 'salidas.listar', 'paquetes.listar'],
    },
    esAdministrativo && { nombre: 'Salidas', ruta: 'salidas.listar', activa: 'salidas.*' },
    { nombre: esAdministrativo ? 'Reservas' : 'Mis reservas', ruta: 'reservas.listar', activa: 'reservas.*' },
    ! esAdministrativo && { nombre: 'Mis datos', ruta: 'perfil.edit', activa: 'perfil.*' },
    esAdministrativo && { nombre: 'Certificados', ruta: 'certificados.pendientes', activa: 'certificados.pendientes' },
    esAdministrativo && { nombre: 'Reportes', ruta: 'admin.reportes.index', activa: 'admin.reportes.*' },
].filter(Boolean);

function Seccion({ seccion, className = '' }) {
    const activa = [].concat(seccion.activa).some((patron) => route().current(patron));

    return (
        <Link
            href={route(seccion.ruta)}
            aria-current={activa ? 'page' : undefined}
            className={`shrink-0 rounded-md px-3 py-1.5 text-sm transition-colors ${
                activa
                    ? 'bg-trekk-suave font-medium text-trekk-texto'
                    : 'text-trekk-texto-suave hover:text-trekk-texto'
            } ${className}`}
        >
            {seccion.nombre}
        </Link>
    );
}

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;
    const esAdministrativo = user.rol === 'admin';
    const menu = secciones(esAdministrativo);

    return (
        <div className="min-h-screen bg-trekk-fondo text-trekk-texto">
            <nav className="sticky top-0 z-40 border-b border-trekk-borde bg-trekk-fondo/85 backdrop-blur">
                <div className="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8">
                    <Link href="/" className="flex shrink-0 items-center gap-2 rounded-lg">
                        <Logo />
                        {esAdministrativo && (
                            <span className="rounded-md bg-trekk-acento px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">
                                Admin
                            </span>
                        )}
                    </Link>

                    <div className="hidden flex-1 items-center gap-1 md:flex">
                        {menu.map((seccion) => (
                            <Seccion key={seccion.ruta} seccion={seccion} />
                        ))}
                    </div>

                    <div className="ms-auto flex items-center gap-2">
                        {esAdministrativo ? (
                            // El cliente cambia su contraseña desde «Mis datos» (CU 13)
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <button
                                        type="button"
                                        className="hidden items-center gap-1 rounded-md px-2 py-1.5 text-sm text-trekk-texto-suave transition hover:text-trekk-texto sm:inline-flex"
                                    >
                                        {user.name}
                                        <svg className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path
                                                fillRule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clipRule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </Dropdown.Trigger>
                                <Dropdown.Content>
                                    <Dropdown.Link href={route('profile.edit')}>Cambiar contraseña</Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        ) : (
                            <span className="hidden text-sm text-trekk-texto-suave sm:inline">{user.name}</span>
                        )}

                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="inline-flex items-center gap-1.5 rounded-lg border border-trekk-borde bg-trekk-tarjeta px-3 py-1.5 text-sm font-medium text-trekk-texto transition hover:bg-trekk-suave"
                        >
                            <svg
                                className="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
                            </svg>
                            Salir
                        </Link>
                    </div>
                </div>

                {/* En celular, las secciones en una fila que se desliza */}
                <div className="flex gap-1 overflow-x-auto border-t border-trekk-borde px-4 py-2 md:hidden">
                    {menu.map((seccion) => (
                        <Seccion key={seccion.ruta} seccion={seccion} className="py-1" />
                    ))}
                    {esAdministrativo && (
                        <Seccion
                            seccion={{ nombre: 'Cambiar contraseña', ruta: 'profile.edit', activa: 'profile.edit' }}
                            className="py-1"
                        />
                    )}
                </div>
            </nav>

            {header && (
                <header className="border-b border-trekk-borde">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{header}</div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
