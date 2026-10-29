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
            },
        },
    },

    plugins: [forms],
};
