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
            // Paleta del prototipo (web-page-creation/app/globals.css), pasada a hex
            colors: {
                trekk: {
                    fondo: '#f9f6f1',
                    texto: '#0f1e18',
                    tarjeta: '#fffdfa',
                    primario: '#1f533f',
                    'primario-hover': '#124230',
                    'primario-texto': '#fbf8f2',
                    secundario: '#e1ebe3',
                    suave: '#f0ece5',
                    'texto-suave': '#596760',
                    acento: '#da7134',
                    error: '#d02c2a',
                    borde: '#dedad2',
                },
                // Las pantallas usan los grises e índigos de Breeze: se reemplazan por
                // grises cálidos y verdes de Trekk para que tomen la paleta sin tocarlas
                gray: {
                    50: '#f7f4ef',
                    100: '#f0ece5',
                    200: '#e5e1d9',
                    300: '#d3cec4',
                    400: '#a6a399',
                    500: '#77807a',
                    600: '#596760',
                    700: '#3f4d46',
                    800: '#26352e',
                    900: '#0f1e18',
                },
                indigo: {
                    50: '#eef4f0',
                    100: '#e1ebe3',
                    200: '#c4d8cb',
                    300: '#9dbfaa',
                    400: '#6c9c82',
                    500: '#3d7a5e',
                    600: '#1f533f',
                    700: '#124230',
                    800: '#0d3425',
                    900: '#08251a',
                },
            },
        },
    },

    plugins: [forms],
};
