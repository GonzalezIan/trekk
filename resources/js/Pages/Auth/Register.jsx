import { Head, Link, useForm } from '@inertiajs/react';
import Boton from '@/Components/Boton';
import Campo from '@/Components/Campo';
import Etiqueta from '@/Components/Etiqueta';
import ErrorCampo from '@/Components/ErrorCampo';
import LayoutInvitado from '@/Layouts/LayoutInvitado';

/**
 * Pantalla de registro con el diseño de Trekk.
 *
 * Mantiene el registro de Breeze: nombre, correo y contraseña, enviados a la
 * ruta `register`. Prototipo: app/registro/page.tsx.
 */
export default function Register() {
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const enviar = (e) => {
        e.preventDefault();
        form.post(route('register'), {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    };

    const campo = (nombre, etiqueta, tipo = 'text', extra = {}) => (
        <div className="flex flex-col gap-2">
            <Etiqueta htmlFor={nombre}>{etiqueta}</Etiqueta>
            <Campo
                id={nombre}
                type={tipo}
                value={form.data[nombre]}
                onChange={(e) => form.setData(nombre, e.target.value)}
                required
                {...extra}
            />
            <ErrorCampo message={form.errors[nombre]} className="mt-1" />
        </div>
    );

    return (
        <LayoutInvitado>
            <Head title="Registrarse | Trekk" />

            <div className="flex flex-col gap-2 text-center">
                <h1 className="font-display text-2xl font-semibold">Creá tu cuenta</h1>
                <p className="text-sm text-muted-foreground">
                    Registrate para ver excursiones, reservar y gestionar tus reservas.
                </p>
            </div>

            <form onSubmit={enviar} className="mt-6 flex flex-col gap-4" noValidate>
                {campo('name', 'Nombre completo', 'text', { autoComplete: 'name' })}

                {campo('email', 'Correo electrónico', 'email', { autoComplete: 'username' })}

                <div className="grid gap-4 sm:grid-cols-2">
                    {campo('password', 'Contraseña', 'password', {
                        autoComplete: 'new-password',
                    })}
                    {campo('password_confirmation', 'Confirmar contraseña', 'password', {
                        autoComplete: 'new-password',
                    })}
                </div>

                <Boton type="submit" size="lg" disabled={form.processing}>
                    Crear mi cuenta
                </Boton>

                <p className="text-center text-sm text-muted-foreground">
                    ¿Ya tenés cuenta?{' '}
                    <Link
                        href={route('login')}
                        className="font-medium text-primary underline-offset-4 hover:underline"
                    >
                        Iniciá sesión
                    </Link>
                </p>
            </form>
        </LayoutInvitado>
    );
}
