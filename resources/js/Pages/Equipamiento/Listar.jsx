import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

// Helpers de formato (igual que en Reservas/Listar)
const usd = (monto) => `USD ${Number(monto).toLocaleString('es-AR')}`;

export default function ListarEquipamientos({ equipamientos, salidas, esAdministrativo }) {
    return (
        <AuthenticatedLayout>
            <Head title={esAdministrativo ? 'Gestión de Equipamientos' : 'Alquiler de Equipamientos'} />

            <main className="mx-auto max-w-5xl px-4 py-12">
                {esAdministrativo ? (
                    <ListarEquipamientoDisponiblePrestar equipamientos={equipamientos} salidas={salidas} />
                    
                ) : (
                    <ListarEquipamientoDisponibleAlquiler equipamientos={equipamientos} />
                )}
            </main>
        </AuthenticatedLayout>
    );
}

// ============ VISTA CLIENTE ============

function ListarEquipamientoDisponibleAlquiler({ equipamientos }) {
    const [seleccionados, setSeleccionados] = useState({});
    const [cantidades, setCantidades] = useState(
        equipamientos.reduce((acc, eq) => ({ ...acc, [eq.id]: 1 }), {})
    );

    const total = Object.keys(seleccionados).reduce((sum, id) => {
        const eq = equipamientos.find((e) => e.id === parseInt(id));
        return sum + eq.precio_alquiler * seleccionados[id];
    }, 0);

    const agregarAlCarrito = (equipo) => {
        const cant = cantidades[equipo.id] || 1;
        setSeleccionados((prev) => ({
            ...prev,
            [equipo.id]: (prev[equipo.id] || 0) + cant,
        }));
    };

    const quitarDelCarrito = (equipoId) => {
        setSeleccionados((prev) => {
            const nuevo = { ...prev };
            delete nuevo[equipoId];
            return nuevo;
        });
    };

    //const confirmarAlquiler = () => {
        // Acá se conecta con la reserva
        //router.post(route('equipamientos.alquiler.guardar'), {
        //    equipamientos: seleccionados
        //});
    //};

    const confirmarAlquiler = () => {
        if(Object.keys(seleccionados).length === 0) {
            alert("No seleccionaste ningún equipo.");
            return;
        }
        alert(`Seleccionaste: ${JSON.stringify(seleccionados)}. Total: USD ${total}.`);
    };

    return (
        <div className="flex flex-col gap-8 md:flex-row">
            {/* Lista de equipos */}
            <div className="flex-1">
                <div className="flex flex-col gap-2 pb-6">
                    <p className="text-xs font-semibold uppercase tracking-widest text-trekk-acento">
                        Cliente
                    </p>
                    <h1 className="font-display text-3xl font-semibold text-trekk-texto md:text-4xl">
                        Equipamiento para Alquilar
                    </h1>
                    <p className="max-w-2xl text-gray-500">
                        Indicá la cantidad y sumalo a tu selección para la reserva.
                    </p>
                </div>

                <div className="flex flex-col gap-4">
                    {equipamientos.map((eq) => (
                        <article key={eq.id} className="flex items-center justify-between rounded-2xl border border-gray-200 bg-trekk-tarjeta p-5">
                            <div>
                                <h2 className="font-display text-lg font-semibold text-trekk-texto">{eq.nombre}</h2>
                                <p className="text-sm text-gray-500">{eq.descripcion}</p>
                                <p className="text-xs text-gray-400 mt-1">{eq.stock} disponibles</p>
                            </div>
                            
                            <div className="flex items-center gap-4">
                                <span className="text-lg font-semibold text-gray-900">
                                    {usd(eq.precio_alquiler)}
                                </span>
                                <div className="flex items-center rounded-lg border border-gray-300">
                                    <input
                                        type="number"
                                        min="1"
                                        max={eq.stock}
                                        value={cantidades[eq.id] || 1}
                                        onChange={(e) => setCantidades(prev => ({ ...prev, [eq.id]: Math.max(1, parseInt(e.target.value) || 1) }))}
                                        className="w-12 border-none bg-transparent text-center text-sm focus:ring-0"
                                    />
                                    <button
                                        onClick={() => agregarAlCarrito(eq)}
                                        className="bg-trekk-primario px-3 py-2 text-trekk-primario-texto hover:bg-trekk-primario-hover rounded-r-lg transition-colors"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            </div>

            {/* Carrito */}
            <div className="w-full md:w-80">
                <div className="sticky top-24 rounded-2xl border border-gray-200 bg-gray-50 p-6">
                    <h3 className="font-display text-lg font-semibold text-trekk-texto mb-4">Vas a agregar</h3>
                    
                    {Object.keys(seleccionados).length === 0 ? (
                        <p className="text-sm text-gray-500">No has seleccionado nada aún.</p>
                    ) : (
                        <ul className="space-y-3 mb-6">
                            {Object.keys(seleccionados).map((id) => {
                                const eq = equipamientos.find((e) => e.id === parseInt(id));
                                return (
                                    <li key={id} className="flex justify-between items-center text-sm border-b border-gray-200 pb-2">
                                        <div>
                                            <p className="text-gray-900 font-medium">{eq.nombre} <span className="text-gray-500">×{seleccionados[id]}</span></p>
                                            <p className="text-gray-500">{usd(eq.precio_alquiler * seleccionados[id])}</p>
                                        </div>
                                        <button onClick={() => quitarDelCarrito(parseInt(id))} className="text-red-500 hover:text-red-700">
                                            ✕
                                        </button>
                                    </li>
                                );
                            })}
                        </ul>
                    )}

                    <div className="border-t border-gray-200 pt-4">
                        <div className="flex justify-between items-center mb-4">
                            <span className="text-gray-600">Total a agregar</span>
                            <span className="text-xl font-bold text-gray-900">{usd(total)}</span>
                        </div>
                        <button 
                            onClick={confirmarAlquiler}
                            className="w-full bg-trekk-primario text-trekk-primario-texto font-medium py-2 rounded-lg hover:bg-trekk-primario-hover transition-colors"
                        >
                            Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ============ VISTA ADMINISTRATIVO ============

function ListarEquipamientoDisponiblePrestar({ equipamientos, salida }) {
    // 1. ¡Hooks arriba de todo siempre!
    const [seleccionados, setSeleccionados] = useState({});

    // 2. Simulamos una salida si no viene por props (así podés ver el diseño ya mismo)
    const salidaActual = salida || { id: 1, cupo_maximo: 15 };
    const cupoRequerido = salidaActual.cupo_maximo;

    const toggleEquipo = (id, stock) => {
        // Validación: si el stock es menor al cupo, no lo deja seleccionar
        if (stock < cupoRequerido) {
            alert(`No podés asignar este equipo: solo hay ${stock} en stock, pero la salida tiene ${cupoRequerido} cupos.`);
            return;
        }

        setSeleccionados(prev => ({
            ...prev,
            [id]: !prev[id]
        }));
    };

    // 3. Si no hay salida real, mostramos un cartel (pero después del Hook)
    if (!salida) {
        return <p className="text-gray-500 p-6 text-center border rounded-xl">Seleccioná una salida programada para ver los equipos disponibles.</p>;
    }

    return (
        <div className="flex flex-col gap-6 max-w-4xl mx-auto bg-trekk-tarjeta rounded-2xl border border-gray-200 p-6 shadow-sm">
            <div className="flex flex-col gap-2">
                <h2 className="font-display text-xl font-semibold text-trekk-texto">Asistentes (opcional)</h2>
                <p className="text-sm text-gray-500">Ingresá el período para ver los asistentes disponibles.</p>
            </div>

            <div>
                <h3 className="font-display text-lg font-semibold text-trekk-texto mb-4">Equipamiento incluido sin costo (opcional)</h3>
                
                {/* Grilla de 2 columnas */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {equipamientos.map(eq => {
                        // ✅ Usamos 'cupoRequerido' en lugar de 'salida.cupo_maximo'
                        const stockSuficiente = eq.stock >= cupoRequerido;
                        
                        return (
                            <label 
                                key={eq.id} 
                                className={`flex items-center justify-between rounded-xl border p-4 transition-colors
                                    ${stockSuficiente 
                                        ? 'border-gray-200 bg-trekk-tarjeta hover:bg-gray-50 cursor-pointer' 
                                        : 'border-gray-100 bg-gray-50 opacity-50 cursor-not-allowed'}`}
                            >
                                <div className="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        disabled={!stockSuficiente}
                                        checked={!!seleccionados[eq.id]}
                                        onChange={() => toggleEquipo(eq.id, eq.stock)}
                                        className="h-4 w-4 rounded border-gray-300 text-trekk-primario focus:ring-trekk-primario"
                                    />
                                    <span className="text-sm font-medium text-gray-900">{eq.nombre}</span>
                                </div>
                                <span className="text-xs text-gray-500">stock {eq.stock}</span>
                            </label>
                        );
                    })}
                </div>
            </div>

            {/* Botones al pie */}
            <div className="flex justify-end gap-3 mt-6 border-t border-gray-200 pt-6">
                <button 
                    type="button"
                    onClick={() => window.history.back()}
                    className="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                >
                    Cancelar
                </button>
                <button 
                    type="button"
                    onClick={() => {
                        const cantidad = Object.values(seleccionados).filter(Boolean).length;
                        if (cantidad === 0) {
                            alert("No seleccionaste ningún equipo.");
                        } else {
                            alert(`¡Excelente! Seleccionaste ${cantidad} equipos. (Vista visual funcionando)`);
                        }
                    }}
                    className="px-4 py-2 text-sm font-medium bg-trekk-primario text-trekk-primario-texto rounded-lg hover:bg-trekk-primario-hover transition-colors"
                >
                    Registrar salida
                </button>
            </div>
        </div>
    );
}