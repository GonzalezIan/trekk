import { Head, Link, useForm } from '@inertiajs/react';
import Boton from '@/Components/Boton';
import Campo from '@/Components/Campo';
import Etiqueta from '@/Components/Etiqueta';
import ErrorCampo from '@/Components/ErrorCampo';
import LayoutInvitado from '@/Layouts/LayoutInvitado';

/**
 * Pantalla de inicio de sesión con el diseño de Trekk.
 *
 * Mantiene el login de Breeze: correo + contraseña, enviado a la ruta `login`.
 * Prototipo: components/trekk/login-form.tsx.
 */
export default function Login({ status }) {
    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const enviar = (e) => {
        e.preventDefault();
        form.post(route('login'), {
            onFinish: () => form.reset('password'),
        });
    };

    return (
        <LayoutInvitado>
            <Head title="Iniciar sesión | Trekk" />

            <div className="flex flex-col gap-6">
                <form
                    onSubmit={enviar}
                    className="flex flex-col gap-4 rounded-2xl border bg-card p-6"
                    noValidate
                >
                    <div className="flex flex-col items-center gap-2 text-center">
                        <h1 className="font-display text-2xl font-semibold">¡Bienvenido/a!</h1>
                        <p className="text-sm text-muted-foreground">
                            Ingresá para ver excursiones, reservar y gestionar tus reservas.
                        </p>
                    </div>

                    {status && (
                        <div className="rounded-xl border border-success/25 bg-success/15 px-4 py-3 text-sm text-success">
                            {status}
                        </div>
                    )}

                    <div className="flex flex-col gap-2">
                        <Etiqueta htmlFor="email">Correo electrónico</Etiqueta>
                        <Campo
                            id="email"
                            type="email"
                            autoComplete="username"
                            value={form.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                            required
                        />
                        <ErrorCampo message={form.errors.email} className="mt-1" />
                    </div>

                    <div className="flex flex-col gap-2">
                        <Etiqueta htmlFor="password">Contraseña</Etiqueta>
                        <Campo
                            id="password"
                            type="password"
                            autoComplete="current-password"
                            value={form.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            required
                        />
                        <ErrorCampo message={form.errors.password} className="mt-1" />
                    </div>

                    <label className="flex items-center gap-2 text-sm text-muted-foreground">
                        <input
                            id="remember"
                            type="checkbox"
                            className="rounded border-input text-primary focus:ring-primary"
                            checked={form.remember}
                            onChange={(e) => form.setData('remember', e.target.checked)}
                        />
                        Recordarme
                    </label>

                    <Boton type="submit" size="lg" disabled={form.processing}>
                        Iniciar sesión
                    </Boton>

                    <p className="text-center text-sm text-muted-foreground">
                        ¿No tenés cuenta?{' '}
                        <Link
                            href={route('register')}
                            className="font-medium text-primary underline-offset-4 hover:underline"
                        >
                            Registrarse
                        </Link>
                    </p>
                </form>
            </div>
        </LayoutInvitado>
    );
}
