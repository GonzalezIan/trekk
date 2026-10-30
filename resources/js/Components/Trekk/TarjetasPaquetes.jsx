import { Link } from '@inertiajs/react';
import { IconoCasa, IconoCheck, IconoPersonas } from './Iconos';

const precio = (valor) =>
    Number(valor).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

// Lo que ve el Cliente: solo paquetes DISPONIBLE, con precio y disponibilidad (prototipo: salidas-explorer.tsx)
export default function TarjetasPaquetes({ paquetes }) {
    if (paquetes.length === 0) {
        return (
            <p className="rounded-xl border border-dashed border-trekk-borde p-6 text-center text-trekk-texto-suave">
                No hay paquetes disponibles para la salida seleccionada.
            </p>
        );
    }

    return (
        <div className="grid gap-4 md:grid-cols-2">
            {paquetes.map((p) => (
                <article key={p.id} className="flex flex-col gap-4 rounded-2xl border border-trekk-borde bg-trekk-tarjeta p-5">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <h3 className="font-display text-xl font-semibold">{p.nombre}</h3>
                            {p.descripcion && <p className="text-sm text-trekk-texto-suave">{p.descripcion}</p>}
                        </div>
                        <p className="shrink-0 text-right font-display text-2xl font-semibold">
                            {precio(p.precio)}
                            <span className="block font-sans text-xs font-normal text-trekk-texto-suave">por participante</span>
                        </p>
                    </div>
                    {p.servicios.length > 0 && (
                        <ul className="flex flex-col gap-1.5 text-sm">
                            {p.servicios.map((servicio) => (
                                <li key={servicio} className="flex items-center gap-2">
                                    <IconoCheck className="h-4 w-4 text-trekk-primario" /> {servicio}
                                </li>
                            ))}
                        </ul>
                    )}
                    {p.refugio && (
                        <p className="flex items-center gap-2 rounded-lg bg-trekk-suave px-3 py-2 text-sm">
                            <IconoCasa className="h-4 w-4 text-trekk-texto-suave" />
                            Alojamiento en {p.refugio}
                        </p>
                    )}
                    <p
                        className={
                            'flex flex-wrap items-center gap-2 text-sm ' +
                            (p.lugares_disponibles <= 3 ? 'text-trekk-acento' : 'text-trekk-texto-suave')
                        }
                    >
                        <IconoPersonas className="h-4 w-4" />
                        {p.lugares_disponibles > 0
                            ? `${p.lugares_disponibles} ${p.lugares_disponibles === 1 ? 'lugar disponible' : 'lugares disponibles'}`
                            : 'Sin disponibilidad'}
                        {/* RN-03 (CU 7): con alojamiento, la disponibilidad es el menor entre cupo y camas */}
                        {p.camas_disponibles !== null && (
                            <span className="text-xs text-trekk-texto-suave">
                                (cupo {p.cupo_disponible} · camas {p.camas_disponibles})
                            </span>
                        )}
                    </p>
                    {/* CU 14 - Registrar reserva para el paquete seleccionado */}
                    {p.lugares_disponibles > 0 ? (
                        <Link
                            href={route('reservas.registrar', p.id)}
                            className="mt-auto rounded-lg bg-trekk-primario px-4 py-2.5 text-center text-sm font-medium text-trekk-primario-texto hover:bg-trekk-primario-hover"
                        >
                            Reservar este paquete
                        </Link>
                    ) : (
                        <span className="mt-auto rounded-lg bg-trekk-suave px-4 py-2.5 text-center text-sm text-trekk-texto-suave">
                            Sin lugares
                        </span>
                    )}
                </article>
            ))}
        </div>
    );
}
