import { ARGENTINA, PAISES, caracteristicaDe, separarTelefono } from '@/Components/Trekk/paises';
import { useState } from 'react';

const estilos =
    'rounded-lg border-trekk-borde bg-trekk-tarjeta text-trekk-texto shadow-sm focus:border-trekk-primario focus:ring-trekk-primario';

// Teléfono (CU 12 y CU 13): país con su característica y número solo con dígitos.
// `value` y `onChange` usan el teléfono completo, tal como se guarda ("+542974280000").
export default function CampoTelefono({ id = 'telefono', value, onChange }) {
    const [partes, setPartes] = useState(() => separarTelefono(value));
    const esArgentina = partes.pais === ARGENTINA;

    const actualizar = (cambios) => {
        const nuevas = { ...partes, ...cambios };

        setPartes(nuevas);
        onChange(nuevas.numero ? caracteristicaDe(nuevas.pais) + nuevas.numero : '');
    };

    return (
        <>
            <div className="mt-1 flex gap-2">
                <select
                    aria-label="País del teléfono"
                    autoComplete="tel-country-code"
                    value={partes.pais}
                    onChange={(e) => actualizar({ pais: e.target.value })}
                    className={`w-40 shrink-0 sm:w-56 ${estilos}`}
                >
                    {PAISES.map(([nombre, caracteristica]) => (
                        <option key={nombre} value={nombre}>
                            {nombre} ({caracteristica})
                        </option>
                    ))}
                </select>
                <input
                    id={id}
                    name={id}
                    type="tel"
                    inputMode="numeric"
                    autoComplete="tel-national"
                    maxLength={esArgentina ? 10 : 15 - (caracteristicaDe(partes.pais).length - 1)}
                    placeholder={esArgentina ? '2974280000' : 'Número sin la característica'}
                    value={partes.numero}
                    onChange={(e) => actualizar({ numero: e.target.value.replace(/\D/g, '') })}
                    required
                    className={`block w-full min-w-0 ${estilos}`}
                />
            </div>
            {esArgentina && (
                <p className="mt-1 text-xs text-gray-500">10 números, con código de área y sin 0 ni 15.</p>
            )}
        </>
    );
}
