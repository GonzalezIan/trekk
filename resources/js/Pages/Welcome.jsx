import FormularioIngreso from '@/Components/Trekk/FormularioIngreso';
import { IconoCalendario, IconoFlecha, IconoTarjeta, IconoUsuario } from '@/Components/Trekk/Iconos';
import Logo from '@/Components/Trekk/Logo';
import { Head, Link } from '@inertiajs/react';

const pasos = [
    {
        Icono: IconoUsuario,
        titulo: 'Creá tu cuenta',
        texto: 'Registrate como cliente con tu DNI. Solo necesitás hacerlo una vez.',
    },
    {
        Icono: IconoCalendario,
        titulo: 'Elegí salida y paquete',
        texto: 'Mirá las salidas programadas de cada excursión y el paquete que mejor te quede.',
    },
    {
        Icono: IconoTarjeta,
        titulo: 'Reservá y pagá',
        texto: 'Cargá los participantes, sumá equipamiento de alquiler si te falta y aboná la seña o el total.',
    },
];

const estiloBotonPrimario =
    'inline-flex items-center gap-2 rounded-lg bg-trekk-primario px-4 py-2.5 font-medium text-trekk-primario-texto transition hover:bg-trekk-primario-hover';
const estiloBotonSecundario =
    'inline-flex items-center gap-2 rounded-lg border border-trekk-borde bg-trekk-tarjeta px-4 py-2.5 font-medium text-trekk-texto transition hover:bg-trekk-suave';

// Portada (prototipo: web-page-creation/app/page.tsx)
export default function Welcome({ auth }) {
    const usuario = auth.user;

    return (
        <>
            <Head title="Excursiones de senderismo en la Patagonia" />

            <div className="min-h-screen bg-trekk-fondo text-trekk-texto">
                <section className="relative isolate overflow-hidden">
                    <img
                        src="/images/hero.jpg"
                        alt=""
                        className="absolute inset-0 -z-10 h-full w-full object-cover"
                    />
                    <div className="absolute inset-0 -z-10 bg-gradient-to-t from-trekk-fondo via-trekk-fondo/60 to-trekk-fondo/10" />

                    <header className="mx-auto flex max-w-6xl items-center justify-between px-4 py-5">
                        <Link href="/">
                            <Logo />
                        </Link>
                        <nav className="flex items-center gap-2 text-sm">
                            {usuario ? (
                                <Link href={route('dashboard')} className={estiloBotonSecundario}>
                                    Ir a mi cuenta
                                </Link>
                            ) : (
                                <Link href={route('register')} className={estiloBotonSecundario}>
                                    Registrarse
                                </Link>
                            )}
                        </nav>
                    </header>

                    <div className="mx-auto grid max-w-6xl items-center gap-10 px-4 pb-16 pt-12 md:grid-cols-[1fr_minmax(0,24rem)] md:pt-20">
                        <div className="flex flex-col gap-5">
                            <h1 className="max-w-3xl font-display text-4xl font-semibold leading-tight md:text-6xl">
                                Caminá los senderos del fin del mundo.
                            </h1>
                            <p className="max-w-xl text-lg text-trekk-texto-suave">
                                Elegí una excursión, una salida programada y el paquete que mejor se ajuste a tu
                                grupo. Reservá en minutos y gestioná pagos, participantes y certificados desde un
                                solo lugar.
                            </p>
                        </div>

                        <div className="w-full max-w-sm justify-self-center md:justify-self-end">
                            {usuario ? <Bienvenida usuario={usuario} /> : <RecuadroIngreso />}
                        </div>
                    </div>
                </section>

                <section aria-labelledby="como-funciona" className="mx-auto max-w-6xl px-4 py-16">
                    <div className="flex flex-col gap-2 pb-8">
                        <h2 id="como-funciona" className="font-display text-3xl font-semibold">
                            Cómo funciona
                        </h2>
                        <p className="text-trekk-texto-suave">
                            {usuario
                                ? 'Desde tu cuenta podés ver las excursiones, sus salidas y paquetes.'
                                : 'Para ver el catálogo de excursiones, sus salidas y paquetes, ingresá con tu cuenta de cliente desde el recuadro de arriba.'}
                        </p>
                    </div>
                    <ol className="grid gap-4 md:grid-cols-3">
                        {pasos.map(({ Icono, titulo, texto }, i) => (
                            <li
                                key={titulo}
                                className="flex flex-col gap-3 rounded-2xl border border-trekk-borde bg-trekk-tarjeta p-5"
                            >
                                <span className="flex h-10 w-10 items-center justify-center rounded-full bg-trekk-primario/10 text-trekk-primario">
                                    <Icono />
                                </span>
                                <h3 className="font-display text-lg font-semibold">
                                    {i + 1}. {titulo}
                                </h3>
                                <p className="text-sm text-trekk-texto-suave">{texto}</p>
                            </li>
                        ))}
                    </ol>
                </section>

                <footer className="border-t border-trekk-borde">
                    <div className="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-8 text-sm text-trekk-texto-suave md:flex-row md:justify-between">
                        <p>Trekk — Sistema de Gestión de Excursiones de Senderismo</p>
                        <p>UNPSJB · Facultad de Ingeniería · Grupo 3</p>
                    </div>
                </footer>
            </div>
        </>
    );
}

// Sin sesión: recuadro de ingreso sobre la foto (CU 28)
function RecuadroIngreso() {
    return (
        <div className="flex flex-col gap-4 rounded-2xl border border-trekk-borde bg-trekk-tarjeta p-6 shadow-xl">
            <div className="flex flex-col items-center gap-2 text-center">
                <Logo />
                <h2 className="font-display text-2xl font-semibold">¡Bienvenido/a!</h2>
                <p className="text-sm text-trekk-texto-suave">
                    Ingresá para ver excursiones, reservar y gestionar tus reservas.
                </p>
            </div>
            <FormularioIngreso canResetPassword={route().has('password.request')} />
        </div>
    );
}

// Con sesión: accesos directos según el rol
function Bienvenida({ usuario }) {
    const esAdministrativo = usuario.rol === 'admin';

    return (
        <div className="flex flex-col gap-4 rounded-2xl border border-trekk-borde bg-trekk-tarjeta p-6 shadow-xl">
            <div className="flex flex-col gap-1">
                <h2 className="font-display text-2xl font-semibold">¡Hola, {usuario.name}!</h2>
                <p className="text-sm text-trekk-texto-suave">
                    {esAdministrativo
                        ? 'Estás como personal administrativo.'
                        : '¿A dónde vamos esta vez?'}
                </p>
            </div>
            <div className="flex flex-col gap-2">
                {esAdministrativo ? (
                    <Link href={route('salidas.listar')} className={`${estiloBotonPrimario} justify-center`}>
                        Ir a Salidas Programadas <IconoFlecha className="h-4 w-4" />
                    </Link>
                ) : (
                    <Link href={route('excursiones.listar')} className={`${estiloBotonPrimario} justify-center`}>
                        Ver excursiones <IconoFlecha className="h-4 w-4" />
                    </Link>
                )}
                <Link href={route('reservas.listar')} className={`${estiloBotonSecundario} justify-center`}>
                    {esAdministrativo ? 'Reservas' : 'Mis reservas'}
                </Link>
            </div>
        </div>
    );
}
