import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

const textarea =
    'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

// Grupo de opciones Sí / No. Arranca sin elegir, para obligar a completarlo (RN-01).
function SiNo({ nombre, valor, onChange, valorSi = true, valorNo = false }) {
    return (
        <div className="mt-2 flex gap-6">
            {[
                ['Sí', valorSi],
                ['No', valorNo],
            ].map(([texto, v]) => (
                <label key={texto} className="flex items-center gap-2 text-sm">
                    <input
                        type="radio"
                        name={nombre}
                        checked={valor === v}
                        onChange={() => onChange(v)}
                        className="text-indigo-600 focus:ring-indigo-500"
                    />
                    {texto}
                </label>
            ))}
        </div>
    );
}

// CU 3 - Modificar excursión
export default function Modificar({ excursion, dificultades }) {
    // Paso 3: el formulario arranca con los datos actuales de la excursión
    const { data, setData, put, processing, errors } = useForm({
        nombre: excursion.nombre,
        descripcion: excursion.descripcion,
        recorrido: excursion.recorrido,
        etapas: excursion.etapas,
        dificultad: excursion.dificultad,
        duracion: excursion.duracion,
        experiencia_requerida: excursion.experiencia_requerida,
        acepta_menores: excursion.acepta_menores,
        equipamiento_obligatorio: excursion.equipamiento_obligatorio ?? '',
        punto_de_encuentro: excursion.punto_de_encuentro ?? '',
        requiere_certificado_medico: excursion.requiere_certificado_medico,
    });

    const confirmar = (e) => {
        e.preventDefault();
        put(route('excursiones.actualizar', excursion.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="font-display text-2xl font-semibold text-trekk-texto">
                    Modificar excursión
                </h2>
            }
        >
            <Head title="Modificar excursión" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={confirmar}
                        className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg"
                    >
                        {Object.keys(errors).length > 0 && (
                            <div className="rounded-md bg-red-50 p-4 text-sm text-red-700">
                                Revisá los campos marcados en rojo.
                            </div>
                        )}

                        <div>
                            <InputLabel htmlFor="nombre" value="Nombre *" />
                            <TextInput
                                id="nombre"
                                className="mt-1 block w-full"
                                value={data.nombre}
                                onChange={(e) => setData('nombre', e.target.value)}
                                isFocused
                            />
                            <InputError message={errors.nombre} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="descripcion" value="Descripción *" />
                            <textarea
                                id="descripcion"
                                rows={3}
                                className={textarea}
                                value={data.descripcion}
                                onChange={(e) => setData('descripcion', e.target.value)}
                            />
                            <InputError message={errors.descripcion} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="recorrido" value="Recorrido *" />
                            <textarea
                                id="recorrido"
                                rows={2}
                                className={textarea}
                                value={data.recorrido}
                                onChange={(e) => setData('recorrido', e.target.value)}
                            />
                            <InputError message={errors.recorrido} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="etapas" value="Etapas *" />
                            <textarea
                                id="etapas"
                                rows={2}
                                className={textarea}
                                value={data.etapas}
                                onChange={(e) => setData('etapas', e.target.value)}
                            />
                            <InputError message={errors.etapas} className="mt-2" />
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="dificultad" value="Dificultad *" />
                                <select
                                    id="dificultad"
                                    className={textarea}
                                    value={data.dificultad}
                                    onChange={(e) => setData('dificultad', e.target.value)}
                                >
                                    <option value="">Seleccionar…</option>
                                    {dificultades.map((d) => (
                                        <option key={d} value={d}>
                                            {d}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.dificultad} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="duracion" value="Duración (días) *" />
                                <TextInput
                                    id="duracion"
                                    type="number"
                                    min="1"
                                    className="mt-1 block w-full"
                                    value={data.duracion}
                                    onChange={(e) => setData('duracion', e.target.value)}
                                />
                                <InputError message={errors.duracion} className="mt-2" />
                            </div>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-3">
                            <div>
                                <InputLabel value="¿Requiere experiencia? *" />
                                <SiNo
                                    nombre="experiencia_requerida"
                                    valor={data.experiencia_requerida}
                                    valorSi="SI"
                                    valorNo="NO"
                                    onChange={(v) => setData('experiencia_requerida', v)}
                                />
                                <InputError message={errors.experiencia_requerida} className="mt-2" />
                            </div>
                            <div>
                                <InputLabel value="¿Acepta menores? *" />
                                <SiNo
                                    nombre="acepta_menores"
                                    valor={data.acepta_menores}
                                    onChange={(v) => setData('acepta_menores', v)}
                                />
                                <InputError message={errors.acepta_menores} className="mt-2" />
                            </div>
                            <div>
                                <InputLabel value="¿Requiere certificado médico? *" />
                                <SiNo
                                    nombre="requiere_certificado_medico"
                                    valor={data.requiere_certificado_medico}
                                    onChange={(v) => setData('requiere_certificado_medico', v)}
                                />
                                <InputError
                                    message={errors.requiere_certificado_medico}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="equipamiento_obligatorio"
                                value="Equipamiento obligatorio (opcional)"
                            />
                            <textarea
                                id="equipamiento_obligatorio"
                                rows={2}
                                placeholder="Ej.: botas de trekking, campera impermeable, bastones"
                                className={textarea}
                                value={data.equipamiento_obligatorio}
                                onChange={(e) =>
                                    setData('equipamiento_obligatorio', e.target.value)
                                }
                            />
                            <InputError
                                message={errors.equipamiento_obligatorio}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="punto_de_encuentro"
                                value="Punto de encuentro (opcional)"
                            />
                            <TextInput
                                id="punto_de_encuentro"
                                className="mt-1 block w-full"
                                placeholder="Ej.: Terminal de ómnibus de El Chaltén, andén 3"
                                value={data.punto_de_encuentro}
                                onChange={(e) =>
                                    setData('punto_de_encuentro', e.target.value)
                                }
                            />
                            <p className="mt-1 text-xs text-gray-500">
                                Se informa a los clientes en el recordatorio de la salida.
                            </p>
                            <InputError
                                message={errors.punto_de_encuentro}
                                className="mt-2"
                            />
                        </div>

                        <div className="flex items-center justify-end gap-4 border-t pt-6">
                            {/* Flujo alternativo: cancelar sin registrar nada */}
                            <Link
                                href={route('excursiones.listar')}
                                className="text-sm text-gray-600 underline hover:text-gray-900"
                            >
                                Cancelar
                            </Link>
                            <PrimaryButton disabled={processing}>
                                Guardar cambios
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
