import InputError from '@/Components/InputError';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

// RF-12: hay que ser mayor de 18 años (fecha más reciente que se puede elegir)
const hace18Anios = () => {
    const fecha = new Date();
    fecha.setFullYear(fecha.getFullYear() - 18);
    return fecha.toISOString().slice(0, 10);
};

// CU 12 - Registrar cliente
export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        dni: '',
        nombre: '',
        apellido: '',
        fecha_nacimiento: '',
        telefono: '',
        email: '',
        usuario: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const campo = (nombre, etiqueta, props = {}) => (
        <div>
            <label htmlFor={nombre} className="block text-sm font-medium text-trekk-texto">
                {etiqueta}
            </label>
            <input
                id={nombre}
                name={nombre}
                value={data[nombre]}
                className="mt-1 block w-full rounded-lg border-trekk-borde bg-white text-trekk-texto shadow-sm focus:border-trekk-primario focus:ring-trekk-primario"
                onChange={(e) => setData(nombre, e.target.value)}
                required
                {...props}
            />
            <InputError message={errors[nombre]} className="mt-2" />
        </div>
    );

    return (
        <GuestLayout titulo="Creá tu cuenta" subtitulo="Registrate como cliente. Solo necesitás hacerlo una vez.">
            <Head title="Registrarse" />

            <form onSubmit={submit} className="space-y-4">
                <h2 className="font-display text-lg font-semibold text-trekk-texto">Datos personales</h2>

                <div className="grid gap-4 sm:grid-cols-2">
                    {campo('nombre', 'Nombre', { autoComplete: 'given-name', autoFocus: true, maxLength: 80 })}
                    {campo('apellido', 'Apellido', { autoComplete: 'family-name', maxLength: 80 })}
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    {campo('dni', 'DNI', { inputMode: 'numeric', maxLength: 10 })}
                    {campo('fecha_nacimiento', 'Fecha de nacimiento', { type: 'date', max: hace18Anios() })}
                </div>

                {campo('telefono', 'Teléfono', { type: 'tel', autoComplete: 'tel', maxLength: 30 })}

                <h2 className="pt-2 font-display text-lg font-semibold text-trekk-texto">Datos de acceso</h2>

                {campo('email', 'Correo electrónico', { type: 'email', autoComplete: 'email' })}
                {campo('usuario', 'Nombre de usuario', { autoComplete: 'username', maxLength: 30 })}
                {campo('password', 'Contraseña', { type: 'password', autoComplete: 'new-password' })}
                {campo('password_confirmation', 'Confirmar contraseña', { type: 'password', autoComplete: 'new-password' })}

                <div className="flex flex-col gap-3 pt-2">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-lg bg-trekk-primario px-4 py-2.5 font-medium text-trekk-primario-texto transition hover:bg-trekk-primario-hover focus:outline-none focus:ring-2 focus:ring-trekk-primario focus:ring-offset-2 disabled:opacity-50"
                    >
                        {processing ? 'Registrando…' : 'Registrarme'}
                    </button>

                    <p className="text-center text-sm text-trekk-texto-suave">
                        ¿Ya tenés cuenta?{' '}
                        <Link
                            href={route('login')}
                            className="font-medium text-trekk-primario underline-offset-4 hover:underline"
                        >
                            Iniciá sesión
                        </Link>
                    </p>
                </div>
            </form>
        </GuestLayout>
    );
}
