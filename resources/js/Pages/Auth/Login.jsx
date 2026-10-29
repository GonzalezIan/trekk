import FormularioIngreso from '@/Components/Trekk/FormularioIngreso';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head } from '@inertiajs/react';

// CU 28 - Login usuario (prototipo: web-page-creation/app/login/page.tsx)
export default function Login({ status, canResetPassword }) {
    return (
        <GuestLayout titulo="Iniciar sesión" subtitulo="Ingresá con tu usuario o correo y tu contraseña.">
            <Head title="Iniciar sesión" />

            {status && (
                <div className="mb-4 rounded-lg bg-trekk-secundario px-3 py-2 text-sm font-medium text-trekk-primario">
                    {status}
                </div>
            )}

            <FormularioIngreso canResetPassword={canResetPassword} enfocar />
        </GuestLayout>
    );
}
