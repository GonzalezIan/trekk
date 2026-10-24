import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
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
            <InputLabel htmlFor={nombre} value={etiqueta} />
            <TextInput
                id={nombre}
                name={nombre}
                value={data[nombre]}
                className="mt-1 block w-full"
                onChange={(e) => setData(nombre, e.target.value)}
                required
                {...props}
            />
            <InputError message={errors[nombre]} className="mt-2" />
        </div>
    );

    return (
        <GuestLayout>
            <Head title="Registrarse" />

            <form onSubmit={submit} className="space-y-4">
                <h2 className="text-lg font-semibold text-gray-900">Datos personales</h2>

                <div className="grid gap-4 sm:grid-cols-2">
                    {campo('nombre', 'Nombre', { autoComplete: 'given-name', isFocused: true, maxLength: 80 })}
                    {campo('apellido', 'Apellido', { autoComplete: 'family-name', maxLength: 80 })}
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    {campo('dni', 'DNI', { inputMode: 'numeric', maxLength: 10 })}
                    {campo('fecha_nacimiento', 'Fecha de nacimiento', { type: 'date', max: hace18Anios() })}
                </div>

                {campo('telefono', 'Teléfono', { type: 'tel', autoComplete: 'tel', maxLength: 30 })}

                <h2 className="pt-2 text-lg font-semibold text-gray-900">Datos de acceso</h2>

                {campo('email', 'Correo electrónico', { type: 'email', autoComplete: 'email' })}
                {campo('usuario', 'Nombre de usuario', { autoComplete: 'username', maxLength: 30 })}
                {campo('password', 'Contraseña', { type: 'password', autoComplete: 'new-password' })}
                {campo('password_confirmation', 'Confirmar contraseña', { type: 'password', autoComplete: 'new-password' })}

                <div className="flex items-center justify-end pt-2">
                    <Link
                        href={route('login')}
                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        ¿Ya tenés cuenta?
                    </Link>

                    <PrimaryButton className="ms-4" disabled={processing}>
                        {processing ? 'Registrando…' : 'Registrarme'}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
