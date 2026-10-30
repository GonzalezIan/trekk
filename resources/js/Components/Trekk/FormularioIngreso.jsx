import InputError from '@/Components/InputError';
import { Link, useForm } from '@inertiajs/react';

const estiloCampo =
    'mt-1 block w-full rounded-lg border-trekk-borde bg-trekk-tarjeta text-trekk-texto shadow-sm focus:border-trekk-primario focus:ring-trekk-primario';

/**
 * CU 28 - Login usuario: formulario de ingreso con usuario o correo.
 * Lo usan la portada (recuadro sobre la foto) y la página de login.
 * La lógica es la de siempre: POST a route('login') con login, password y remember.
 */
export default function FormularioIngreso({ canResetPassword = false, enfocar = false }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        login: '',
        password: '',
        remember: false,
    });

    const enviar = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <form onSubmit={enviar} className="flex flex-col gap-4">
            <div>
                <label htmlFor="login" className="block text-sm font-medium text-trekk-texto">
                    Usuario o correo electrónico
                </label>
                <input
                    id="login"
                    type="text"
                    name="login"
                    value={data.login}
                    className={estiloCampo}
                    autoComplete="username"
                    autoFocus={enfocar}
                    onChange={(e) => setData('login', e.target.value)}
                />
                <InputError message={errors.login} className="mt-2" />
            </div>

            <div>
                <label htmlFor="password" className="block text-sm font-medium text-trekk-texto">
                    Contraseña
                </label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    value={data.password}
                    className={estiloCampo}
                    autoComplete="current-password"
                    onChange={(e) => setData('password', e.target.value)}
                />
                <InputError message={errors.password} className="mt-2" />
            </div>

            <div className="flex items-center justify-between gap-3 text-sm">
                <label className="flex items-center gap-2 text-trekk-texto-suave">
                    <input
                        type="checkbox"
                        name="remember"
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                        className="rounded border-trekk-borde text-trekk-primario shadow-sm focus:ring-trekk-primario"
                    />
                    Recordarme
                </label>

                {canResetPassword && (
                    <Link
                        href={route('password.request')}
                        className="font-medium text-trekk-primario underline-offset-4 hover:underline"
                    >
                        ¿Olvidaste tu contraseña?
                    </Link>
                )}
            </div>

            <button
                type="submit"
                disabled={processing}
                className="rounded-lg bg-trekk-primario px-4 py-2.5 font-medium text-trekk-primario-texto transition hover:bg-trekk-primario-hover focus:outline-none focus:ring-2 focus:ring-trekk-primario focus:ring-offset-2 disabled:opacity-50"
            >
                {processing ? 'Ingresando…' : 'Ingresar'}
            </button>

            <p className="text-center text-sm text-trekk-texto-suave">
                ¿Primera vez?{' '}
                <Link
                    href={route('register')}
                    className="font-medium text-trekk-primario underline-offset-4 hover:underline"
                >
                    Registrate acá
                </Link>
            </p>
        </form>
    );
}
