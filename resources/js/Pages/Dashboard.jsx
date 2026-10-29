import { IconoCalendario, IconoFlecha, IconoMontana, IconoTarjeta, IconoUsuario } from '@/Components/Trekk/Iconos';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

// Íconos que faltan en Iconos.jsx, solo para esta pantalla
const IconoDocumento = ({ className = 'h-5 w-5' }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
        <path d="M14 2v4a2 2 0 0 0 2 2h4M9 15l2 2 4-4" />
    </svg>
);
const IconoGrafico = ({ className = 'h-5 w-5' }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M3 3v18h18M18 17V9M13 17V5M8 17v-3" />
    </svg>
);

const accesosCliente = [
    {
        Icono: IconoMontana,
        titulo: 'Excursiones',
        texto: 'Mirá las excursiones disponibles, sus salidas programadas y paquetes.',
        ruta: 'excursiones.listar',
    },
    {
        Icono: IconoTarjeta,
        titulo: 'Mis reservas',
        texto: 'Consultá tus reservas, pagá el saldo, cargá certificados o cancelá.',
        ruta: 'reservas.listar',
    },
    {
        Icono: IconoUsuario,
        titulo: 'Mis datos',
        texto: 'Actualizá tu nombre, correo, teléfono o contraseña.',
        ruta: 'perfil.edit',
    },
];

const accesosAdministrativo = [
    {
        Icono: IconoMontana,
        titulo: 'Excursiones',
        texto: 'Alta, modificación y baja de excursiones.',
        ruta: 'excursiones.listar',
    },
    {
        Icono: IconoCalendario,
        titulo: 'Salidas Programadas',
        texto: 'Registrá, modificá o cancelá salidas y gestioná sus paquetes.',
        ruta: 'salidas.listar',
    },
    {
        Icono: IconoTarjeta,
        titulo: 'Reservas',
        texto: 'Consultá las reservas de los clientes y sus participantes.',
        ruta: 'reservas.listar',
    },
    {
        Icono: IconoDocumento,
        titulo: 'Certificados',
        texto: 'Verificá los certificados médicos pendientes.',
        ruta: 'certificados.pendientes',
    },
    {
        Icono: IconoGrafico,
        titulo: 'Reportes',
        texto: 'Generá el reporte periódico de reservas realizadas.',
        ruta: 'admin.reportes.index',
    },
];

// Inicio después de ingresar: accesos directos según el rol
export default function Dashboard() {
    const user = usePage().props.auth.user;
    const esAdministrativo = user.rol === 'admin';
    const accesos = esAdministrativo ? accesosAdministrativo : accesosCliente;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-1">
                    <p className="text-xs font-semibold uppercase tracking-wide text-trekk-acento">
                        {esAdministrativo ? 'Administración' : 'Tu cuenta'}
                    </p>
                    <h2 className="font-display text-3xl font-semibold text-trekk-texto">¡Hola, {user.name}!</h2>
                    <p className="text-trekk-texto-suave">
                        {esAdministrativo
                            ? 'Elegí con qué querés trabajar.'
                            : '¿A dónde vamos esta vez? Elegí por dónde seguir.'}
                    </p>
                </div>
            }
        >
            <Head title="Inicio" />

            <div className="py-10">
                <div className="mx-auto grid max-w-7xl gap-4 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-3 lg:px-8">
                    {accesos.map(({ Icono, titulo, texto, ruta }) => (
                        <Link
                            key={ruta}
                            href={route(ruta)}
                            className="group flex flex-col gap-3 rounded-2xl border border-trekk-borde bg-trekk-tarjeta p-5 transition hover:border-trekk-primario/50 hover:shadow-lg"
                        >
                            <span className="flex h-10 w-10 items-center justify-center rounded-full bg-trekk-primario/10 text-trekk-primario">
                                <Icono />
                            </span>
                            <h3 className="flex items-center justify-between font-display text-lg font-semibold">
                                {titulo}
                                <IconoFlecha className="h-4 w-4 text-trekk-texto-suave transition group-hover:translate-x-1 group-hover:text-trekk-primario" />
                            </h3>
                            <p className="text-sm text-trekk-texto-suave">{texto}</p>
                        </Link>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
