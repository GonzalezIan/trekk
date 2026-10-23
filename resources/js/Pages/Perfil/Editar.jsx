import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';

/**
 * CU 13 - Modificar datos personales (RF-13): pantalla «Mis datos».
 *
 * Solo son modificables el nombre, el apellido, el email y el teléfono.
 * El DNI y la fecha de nacimiento se muestran, pero no se modifican.
 */
export default function Editar({ cliente }) {
    const { flash } = usePage().props;

    const { data, setData, patch, processing, errors } = useForm({
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
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Mis datos
                </h2>
            }
        >
            <Head title="Mis datos" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                    {flash.exito && (
                        <div
                            role="status"
                            className="rounded-md bg-green-50 p-4 text-sm text-green-800"
                        >
                            {flash.exito}
                        </div>
                    )}

                    <section className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <header>
                            <h3 className="text-lg font-medium text-gray-900">
                                Datos personales
                            </h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Podés modificar tu nombre, apellido, correo
                                electrónico y teléfono. Tu DNI y tu fecha de
                                nacimiento no se modifican.
                            </p>
                        </header>

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

                            <dl className="rounded-md bg-gray-50 p-4 text-sm text-gray-600">
                                <div className="flex gap-2">
                                    <dt className="font-medium text-gray-900">
                                        DNI:
                                    </dt>
                                    <dd>{cliente.dni}</dd>
                                </div>
                                <div className="mt-1 flex gap-2">
                                    <dt className="font-medium text-gray-900">
                                        Fecha de nacimiento:
                                    </dt>
                                    <dd>{cliente.fechaNacimiento}</dd>
                                </div>
                            </dl>

                            <div className="flex items-center justify-end">
                                <PrimaryButton disabled={processing}>
                                    Guardar cambios
                                </PrimaryButton>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
