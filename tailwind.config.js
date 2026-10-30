import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                // Títulos con el estilo del prototipo
                display: ['Fraunces', ...defaultTheme.fontFamily.serif],
            },
            // Paleta oscura del prototipo (web-page-creation/app/globals.css, bloque .dark), pasada a hex
            colors: {
                trekk: {
                    fondo: '#09120e',
                    texto: '#f1eee7',
                    tarjeta: '#111c18',
                    primario: '#89d0aa',
                    'primario-hover': '#a3e2bf',
                    'primario-texto': '#081410',
                    secundario: '#1a2a25',
                    suave: '#192520',
                    'texto-suave': '#9aa9a0',
                    acento: '#ee8545',
                    error: '#f75d59',
                    borde: '#27302d',
                },
                // Las pantallas usan los grises, índigos y colores de estado de Breeze: se
                // invierten las escalas (50 = oscuro, 900 = claro) para que tomen el tema
                // oscuro sin tocarlas
                gray: {
                    50: '#0f1815',
                    100: '#17201d',
                    200: '#242e2a',
                    300: '#36403c',
                    400: '#5b6662',
                    500: '#94928a',
                    600: '#acaba3',
                    700: '#c6c4bc',
                    800: '#dcdbd2',
                    900: '#f0efe6',
                },
                indigo: {
                    50: '#11271c',
                    100: '#153223',
                    200: '#1c4732',
                    300: '#2f6b4e',
                    400: '#519773',
                    500: '#70b691',
                    600: '#89d0aa',
                    700: '#a3e2bf',
                    800: '#bcebd1',
                    900: '#d5f4e2',
                },
                red: {
                    50: '#301715',
                    100: '#401d1b',
                    200: '#572825',
                    300: '#773733',
                    400: '#bb5752',
                    500: '#e3645e',
                    600: '#f97770',
                    700: '#ff9b93',
                    800: '#ffb8b0',
                    900: '#ffd6d1',
                },
                green: {
                    50: '#102515',
                    100: '#12301a',
                    200: '#194224',
                    300: '#225a31',
                    400: '#348f4f',
                    500: '#33ac5a',
                    600: '#4ac06c',
                    700: '#7cd591',
                    800: '#a1e4ae',
                    900: '#c9f1d0',
                },
                amber: {
                    50: '#2a1c07',
                    100: '#382404',
                    200: '#4d3202',
                    300: '#694500',
                    400: '#a66d00',
                    500: '#ca8000',
                    600: '#de9300',
                    700: '#eeb154',
                    800: '#f8c885',
                    900: '#fde0b9',
                },
                emerald: {
                    50: '#09251a',
                    100: '#053122',
                    200: '#03432f',
                    300: '#005c41',
                    400: '#009267',
                    500: '#00b079',
                    600: '#00c38b',
                    700: '#5ed8a9',
                    800: '#8fe6c1',
                    900: '#c0f2db',
                },
            },
        },
    },

    plugins: [forms],
};
