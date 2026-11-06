import { useMemo, useState } from 'react';

// Búsqueda y orden de los listados del Administrativo, sobre los datos ya cargados.

// "Café" -> "cafe": la búsqueda no distingue mayúsculas ni acentos
export const normalizar = (valor) =>
    String(valor ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase();

const comparar = (a, b) => {
    if (a == null || a === '') return b == null || b === '' ? 0 : 1;
    if (b == null || b === '') return -1;
    if (typeof a === 'number' && typeof b === 'number') return a - b;
    return String(a).localeCompare(String(b), 'es', { numeric: true, sensitivity: 'base' });
};

/**
 * items:    elementos del listado
 * buscarEn: (item) => [textos en los que se busca]
 * columnas: { clave: { etiqueta, valor: (item) => valor por el que se ordena } }
 * inicial:  clave de la columna por la que se ordena al entrar
 */
export function useBusquedaYOrden(items, { buscarEn, columnas, inicial }) {
    const [busqueda, setBusqueda] = useState('');
    const [orden, setOrden] = useState({ clave: inicial, ascendente: true });

    const resultado = useMemo(() => {
        const termino = normalizar(busqueda.trim());
        const filtrados = termino
            ? items.filter((item) => buscarEn(item).some((texto) => normalizar(texto).includes(termino)))
            : items;

        const columna = columnas[orden.clave];
        if (!columna) return filtrados;

        // Los vacíos quedan siempre al final, en cualquier dirección
        return [...filtrados].sort((x, y) => {
            const a = columna.valor(x);
            const b = columna.valor(y);
            const vacioA = a == null || a === '';
            const vacioB = b == null || b === '';
            if (vacioA || vacioB) return comparar(a, b);
            return orden.ascendente ? comparar(a, b) : comparar(b, a);
        });
    }, [items, busqueda, orden, buscarEn, columnas]);

    return { busqueda, setBusqueda, orden, setOrden, resultado, columnas };
}

const campo =
    'rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

/**
 * Campos de búsqueda y orden. `children` permite sumar controles propios del listado
 * (por ejemplo, el check para mostrar los inactivos).
 */
export default function BarraListado({ listado, placeholder = 'Buscar…', children }) {
    const { busqueda, setBusqueda, orden, setOrden, columnas } = listado;

    return (
        <div className="flex flex-col gap-3 bg-trekk-tarjeta px-4 py-3 text-sm shadow-sm sm:flex-row sm:flex-wrap sm:items-center sm:rounded-lg">
            <input
                type="search"
                aria-label="Buscar"
                placeholder={placeholder}
                value={busqueda}
                onChange={(e) => setBusqueda(e.target.value)}
                className={campo + ' w-full sm:w-72'}
            />
            <div className="flex items-center gap-2">
                <label htmlFor="ordenar-por" className="whitespace-nowrap text-gray-600">
                    Ordenar por
                </label>
                <select
                    id="ordenar-por"
                    value={orden.clave}
                    onChange={(e) => setOrden({ ...orden, clave: e.target.value })}
                    className={campo}
                >
                    {Object.entries(columnas).map(([clave, { etiqueta }]) => (
                        <option key={clave} value={clave}>
                            {etiqueta}
                        </option>
                    ))}
                </select>
                <button
                    type="button"
                    onClick={() => setOrden({ ...orden, ascendente: !orden.ascendente })}
                    aria-label={orden.ascendente ? 'Orden ascendente' : 'Orden descendente'}
                    title={orden.ascendente ? 'Ascendente' : 'Descendente'}
                    className="rounded-lg border border-trekk-borde bg-trekk-tarjeta px-3 py-2 text-sm font-medium text-trekk-texto hover:bg-trekk-suave"
                >
                    {orden.ascendente ? '↑ Asc.' : '↓ Desc.'}
                </button>
            </div>
            {children}
        </div>
    );
}

// Check para ver también los registros inactivos, que por defecto no se muestran
export function CheckInactivos({ checked, onChange, etiqueta = 'Mostrar inactivos' }) {
    return (
        <label className="flex items-center gap-2 text-gray-700 sm:ms-auto">
            <input
                type="checkbox"
                checked={checked}
                onChange={(e) => onChange(e.target.checked)}
                className="rounded text-indigo-600 focus:ring-indigo-500"
            />
            {etiqueta}
        </label>
    );
}
