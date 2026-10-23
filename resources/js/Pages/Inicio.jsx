import { Head, Link } from '@inertiajs/react';
import Boton from '@/Components/Boton';
import EncabezadoSitio from '@/Components/EncabezadoSitio';

/**
 * Portada pública (prototipo: app/page.tsx y components/trekk/catalogo.tsx).
 *
 * La imagen hero.jpg ocupa toda la sección y un degradado la funde con el color
 * de fondo de la página (de abajo hacia arriba: background, background/60 y
 * background/10), igual que en el prototipo.
 *
 * Cuando hay sesión, el catálogo de excursiones se muestra en
 * Pages/Excursiones/Index.jsx (CU 4).
 */
export default function Inicio() {
    return (
        <div className="min-h-dvh bg-background">
            <Head title="Trekk — Excursiones de senderismo en la Patagonia" />

            <EncabezadoSitio />

            <main>
                <section className="relative isolate overflow-hidden md:min-h-[39rem]">
                    <img
                        src="/images/hero.jpg"
                        alt=""
                        className="absolute inset-0 -z-10 h-full w-full object-cover"
                    />
                    <div className="absolute inset-0 -z-10 bg-gradient-to-t from-background via-background/60 to-background/10" />

                    <div className="mx-auto max-w-6xl px-4 pb-16 pt-32 md:pt-44">
                        <div className="flex flex-col gap-5">
                            <p className="text-xs font-semibold uppercase tracking-widest text-accent">
                                Patagonia · El Chaltén
                            </p>
                            <h1 className="max-w-3xl font-display text-4xl font-semibold leading-tight text-balance md:text-6xl">
                                Caminá los senderos del fin del mundo.
                            </h1>
                            <p className="max-w-xl text-lg text-muted-foreground text-pretty">
                                Consultá las salidas programadas de cada excursión, elegí el
                                paquete que mejor te convenga y reservá en minutos.
                            </p>
                        </div>
                    </div>
                </section>

                <section className="relative -mt-40 mx-auto max-w-6xl px-4">
                    <div className="flex flex-col gap-2 pb-8">
                        <h2 className="font-display text-3xl font-semibold">Cómo funciona:</h2>
                    </div>

                    <ol className="grid gap-4 md:grid-cols-3">
                        {[
                            {
                                titulo: 'Creá tu cuenta',
                                texto:
                                    'Registrate como cliente con tu DNI. Solo necesitás hacerlo una vez.',
                            },
                            {
                                titulo: 'Elegí salida y paquete',
                                texto:
                                    'Mirá las salidas programadas de cada excursión y el paquete que mejor te quede.',
                            },
                            {
                                titulo: 'Reservá y pagá',
                                texto:
                                    'Cargá los participantes, sumá equipamiento de alquiler si te falta y aboná la seña o el total.',
                            },
                        ].map((paso, indice) => (
                            <li
                                key={paso.titulo}
                                className="flex flex-col gap-3 rounded-2xl border bg-card p-5"
                            >
                                <span className="flex size-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                                    {indice + 1}
                                </span>
                                <h3 className="font-display text-lg font-semibold">
                                    {indice + 1}. {paso.titulo}
                                </h3>
                                <p className="text-sm text-muted-foreground">{paso.texto}</p>
                            </li>
                        ))
                        }
                    </ol>
                </section>
                <footer className="mt-4 border-t">
                    <div className="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-8 text-sm text-muted-foreground md:flex-row md:justify-between">
                        <p>Trekk — Sistema de Gestión de Excursiones de Senderismo</p>
                        <p>UNPSJB · Facultad de Ingeniería · Grupo 3</p>
                    </div>
                </footer>
            </main>
        </div>
    );
}
