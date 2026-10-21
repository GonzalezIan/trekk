// Etiqueta de estado (reservas, pagos, requisitos médicos, etc.), igual al prototipo.
// Recibe el valor tal como se guarda en la BD (ej.: PENDIENTE_DE_PAGO_TOTAL).

const etiquetas = {
    EN_CURSO: 'En curso',
    PENDIENTE: 'Pendiente',
    CONFIRMADA: 'Confirmada',
    REALIZADA: 'Realizada',
    CANCELADA: 'Cancelada',
    PENDIENTE_DE_PAGO_TOTAL: 'Pendiente de pago total',
    PAGO_COMPLETO: 'Pago completo',
    NO_REQUERIDO: 'No requerido',
    CUMPLIDO: 'Cumplido',
    PENDIENTE_DE_VERIFICACION: 'Pendiente de verificación',
    VALIDO: 'Válido',
    INVALIDO: 'Inválido',
};

const advertencia = 'border-amber-300 bg-amber-50 text-amber-700';
const exito = 'border-emerald-300 bg-emerald-50 text-emerald-700';
const peligro = 'border-red-200 bg-red-50 text-red-700';
const neutro = 'border-gray-200 bg-gray-100 text-gray-600';

const tonos = {
    PENDIENTE: advertencia,
    PENDIENTE_DE_PAGO_TOTAL: advertencia,
    PENDIENTE_DE_VERIFICACION: advertencia,
    CONFIRMADA: exito,
    PAGO_COMPLETO: exito,
    CUMPLIDO: exito,
    VALIDO: exito,
    REALIZADA: 'border-gray-200 bg-emerald-50/50 text-emerald-900',
    CANCELADA: peligro,
    INVALIDO: peligro,
};

export function etiquetaEstado(valor) {
    return etiquetas[valor] ?? valor;
}

export default function EstadoBadge({ valor, className = '' }) {
    return (
        <span
            className={
                'inline-flex items-center whitespace-nowrap rounded-full border px-2 py-0.5 text-xs font-medium ' +
                (tonos[valor] ?? neutro) +
                ' ' +
                className
            }
        >
            {etiquetaEstado(valor)}
        </span>
    );
}
