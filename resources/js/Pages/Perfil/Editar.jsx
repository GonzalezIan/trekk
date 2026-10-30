import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm';
import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';

/**
 * CU 13 - Modificar datos personales (RF-13): pantalla «Mis datos».
 *
 * Solo son modificables el nombre, el apellido, el email y el teléfono.
 * El DNI y la fecha de nacimiento se muestran, pero no se modifican.
 *
 * Las dos tarjetas (datos personales y contraseña) comparten el mismo pie:
 * botón «Guardar» a la izquierda y confirmación «Guardado.» a su lado.
 */
export default function Editar({ cliente }) {
    const { data, setData, patch, processing, errors, recentlySuccessful } =
        useForm({
            nombre: cliente.nombre ?? '',
            apellido: cliente.apellido ?? '',
            email: cliente.email ?? '',
            telefono: cliente.telefono ?? '',
        });

    const enviar = (e) => {
        e.preventDefault();
        patch(route('perfil.update'), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="font-display text-2xl font-semibold text-trekk-texto">
                    Mis datos
                </h2>
            }
        >
            <Head title="Mis datos" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
                    <section className="bg-trekk-tarjeta p-4 shadow sm:rounded-lg sm:p-8">
                        <header>
                            <h2 className="text-lg font-medium text-gray-900">
                                Datos personales
                            </h2>
                            <p className="mt-1 text-sm text-gray-600">
                                Podés modificar tu nombre, apellido, correo
                                electrónico y teléfono. Tu DNI y tu fecha de
                                nacimiento no se pueden cambiar.
                            </p>
                        </header>

                        <dl className="mt-6 grid gap-4 rounded-md bg-gray-50 p-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    DNI
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {cliente.dni}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Fecha de nacimiento
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {cliente.fechaNacimiento}
                                </dd>
                            </div>
                        </dl>

                        <form onSubmit={enviar} className="mt-6 space-y-6">
                            <div className="grid gap-6 sm:grid-cols-2">
                                <div>
                                    <InputLabel htmlFor="nombre" value="Nombre" />
                                    <TextInput
                                        id="nombre"
                                        className="mt-1 block w-full"
                                        value={data.nombre}
                                        onChange={(e) =>
                                            setData('nombre', e.target.value)
                                        }
                                        required
                                        isFocused
                                        autoComplete="given-name"
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.nombre}
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        htmlFor="apellido"
                                        value="Apellido"
                                    />
                                    <TextInput
                                        id="apellido"
                                        className="mt-1 block w-full"
                                        value={data.apellido}
                                        onChange={(e) =>
                                            setData('apellido', e.target.value)
                                        }
                                        required
                                        autoComplete="family-name"
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.apellido}
                                    />
                                </div>
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="email"
                                    value="Correo electrónico"
                                />
                                <TextInput
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                    required
                                    autoComplete="email"
                                />
                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="telefono"
                                    value="Teléfono"
                                />
                                <TextInput
                                    id="telefono"
                                    type="tel"
                                    className="mt-1 block w-full"
                                    value={data.telefono}
                                    onChange={(e) =>
                                        setData('telefono', e.target.value)
                                    }
                                    required
                                    autoComplete="tel"
                                />
                                <InputError
                                    className="mt-2"
                                    message={errors.telefono}
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>
                                    Guardar
                                </PrimaryButton>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p
                                        role="status"
                                        className="text-sm text-gray-600"
                                    >
                                        Guardado.
                                    </p>
                                </Transition>
                            </div>
                        </form>
                    </section>

                    <section className="bg-trekk-tarjeta p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdatePasswordForm />
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
