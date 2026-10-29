import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';

// Cambiar contraseña del Administrativo. El cliente lo hace desde «Mis datos» (CU 13).
export default function Edit() {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="font-display text-2xl font-semibold text-trekk-texto">
                    Cambiar contraseña
                </h2>
            }
        >
            <Head title="Cambiar contraseña" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
