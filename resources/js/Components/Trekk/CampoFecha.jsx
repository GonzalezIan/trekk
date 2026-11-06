import { useEffect, useRef, useState } from 'react';
import { IconoCalendario } from './Iconos';
import { fechaIso, formatearFecha } from './fechas';

// "12102026" -> "12/10/2026" (las barras se agregan solas al escribir)
const conBarras = (texto) => {
    const digitos = texto.replace(/\D/g, '').slice(0, 8);
    let resultado = digitos.slice(0, 2);
    if (digitos.length > 2) resultado += '/' + digitos.slice(2, 4);
    if (digitos.length > 4) resultado += '/' + digitos.slice(4);
    return resultado;
};

/**
 * Reemplaza a <input type="date">: el navegador muestra ese campo en el formato de su idioma
 * (mm/dd/aaaa en inglés), y acá la fecha se ve y se escribe siempre como dd/mm/aaaa.
 *
 * `value` y el `onChange` siguen usando el formato del input nativo ("2026-10-27"), así el resto
 * del formulario no cambia. Mientras la fecha está incompleta o no existe, el valor es ''.
 * El botón del calendario abre el selector de fechas del navegador.
 */
export default function CampoFecha({
    value = '',
    onChange,
    name,
    min,
    max,
    disabled = false,
    className = '',
    classNameContenedor = 'relative block',
    type, // se ignora: se acepta para poder reemplazar a un input type="date"
    ...props
}) {
    const [texto, setTexto] = useState(formatearFecha(value));
    const selector = useRef(null);

    // Si el valor cambia desde afuera (por ejemplo, al limpiar un filtro), se actualiza el texto
    useEffect(() => {
        if ((value ?? '') !== fechaIso(texto)) setTexto(formatearFecha(value));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [value]);

    const avisar = (iso) => onChange?.({ target: { name, value: iso } });

    const escribir = (e) => {
        const nuevo = conBarras(e.target.value);
        setTexto(nuevo);
        const iso = fechaIso(nuevo);
        if (iso !== (value ?? '')) avisar(iso);
    };

    const elegir = (e) => {
        setTexto(formatearFecha(e.target.value));
        avisar(e.target.value);
    };

    const abrirCalendario = () => {
        const campo = selector.current;
        if (!campo) return;
        try {
            campo.showPicker();
        } catch {
            campo.focus();
            campo.click();
        }
    };

    return (
        <div className={classNameContenedor}>
            <input
                {...props}
                type="text"
                name={name}
                inputMode="numeric"
                autoComplete="off"
                placeholder="dd/mm/aaaa"
                maxLength={10}
                value={texto}
                onChange={escribir}
                disabled={disabled}
                className={className + ' pe-10'}
            />
            <button
                type="button"
                onClick={abrirCalendario}
                disabled={disabled}
                tabIndex={-1}
                aria-label="Abrir calendario"
                className="absolute bottom-0 right-0 flex h-[2.375rem] items-center px-3 text-gray-500 hover:text-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <IconoCalendario className="h-4 w-4" />
            </button>
            {/* Selector nativo, invisible: solo se usa para mostrar el calendario */}
            <input
                ref={selector}
                type="date"
                tabIndex={-1}
                aria-hidden="true"
                value={value ?? ''}
                min={min}
                max={max}
                onChange={elegir}
                disabled={disabled}
                className="pointer-events-none absolute bottom-0 left-0 h-px w-px border-0 p-0 opacity-0"
            />
        </div>
    );
}
