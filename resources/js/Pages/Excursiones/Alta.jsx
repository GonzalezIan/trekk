import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import EncabezadoFormulario from '@/Components/Trekk/EncabezadoFormulario';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

const textarea =
    'mt-1 block w-full rounded-lg border-trekk-borde bg-trekk-tarjeta shadow-sm focus:border-trekk-primario focus:ring-trekk-primario';

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

// CU 1 - Alta de excursión
export default function Alta({ dificultades }) {
    const { data, setData, post, processing, errors } = useForm({
        nombre: '',
        descripcion: '',
        recorrido: '',
        etapas: '',
        dificultad: '',
        duracion: '',
        experiencia_requerida: '',
        acepta_menores: '',
        equipamiento_obligatorio: '',
        punto_de_encuentro: '',
        requiere_certificado_medico: '',
    });

    const confirmar = (e) => {
        e.preventDefault();
        post(route('excursiones.registrar'));
    };

    return (
        <AuthenticatedLayout
            header={
                <EncabezadoFormulario
                    volverA={route('excursiones.listar')}
                    volverTexto="Excursiones"
                    titulo="Nueva excursión"
                    descripcion="Los campos marcados con * son obligatorios."
                />
            }
        >
            <Head title="Nueva excursión" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <form
                        onSubmit={confirmar}
                        className="space-y-6 border border-trekk-borde bg-trekk-tarjeta p-6 rounded-2xl"
                    >
                        {Object.keys(errors).length > 0 && (
                            <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-trekk-error">
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

                        <div className="flex items-center justify-end gap-4 border-t border-trekk-borde pt-6">
                            {/* Flujo alternativo: cancelar sin registrar nada */}
                            <Link
                                href={route('excursiones.listar')}
                                className="rounded-lg px-4 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                            >
                                Cancelar
                            </Link>
                            <PrimaryButton disabled={processing}>
                                Registrar excursión
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
