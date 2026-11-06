// Formato de fechas de la aplicación: dd/mm/aaaa

// "2026-10-27" o "2026-10-27T14:30:00.000000Z" -> "27/10/2026"
export const formatearFecha = (valor) => {
    if (!valor) return '';
    const [anio, mes, dia] = String(valor).slice(0, 10).split('-');
    return dia && mes && anio ? `${dia}/${mes}/${anio}` : String(valor);
};

// "27/10/2026" -> "2026-10-27". Devuelve '' si la fecha está incompleta o no existe.
export const fechaIso = (texto) => {
    const partes = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(texto ?? '');
    if (!partes) return '';
    const [, dia, mes, anio] = partes;
    const fecha = new Date(Date.UTC(Number(anio), Number(mes) - 1, Number(dia)));
    const valida =
        fecha.getUTCFullYear() === Number(anio) &&
        fecha.getUTCMonth() === Number(mes) - 1 &&
        fecha.getUTCDate() === Number(dia);
    return valida ? `${anio}-${mes}-${dia}` : '';
};
