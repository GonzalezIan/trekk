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
            colors: {
                background: '#f9f6f1',
                foreground: '#0f1e18',
                primary: { DEFAULT: '#1f533f', foreground: '#fbf8f2' },
                muted: { foreground: '#596760' },
                card: {
                    DEFAULT: '#fffdfa',
                    foreground: '#0f1e18',
                },
                popover: {
                    DEFAULT: '#fffdfa',
                    foreground: '#0f1e18',
                },
                primary: {
                    DEFAULT: '#1f533f',
                    foreground: '#fbf8f2',
                },
                secondary: {
                    DEFAULT: '#e1ebe3',
                    foreground: '#193429',
                },
                muted: {
                    DEFAULT: '#f0ece5',
                    foreground: '#596760',
                },
                accent: {
                    DEFAULT: '#da7134',
                    foreground: '#fdfcf8',
                },
                destructive: {
                    DEFAULT: '#d02c2a',
                    foreground: '#fdfcf8',
                },
                success: '#258651',
                warning: '#d79628',
                border: '#dedad2',
                input: '#dedad2',
                ring: '#1f533f',
            },
            borderRadius: {
                sm: '0.45rem',
                md: '0.6rem',
                lg: '0.75rem',
                xl: '1.05rem',
                '2xl': '1.35rem',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Fraunces', ...defaultTheme.fontFamily.serif],
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Fraunces', ...defaultTheme.fontFamily.serif],
            },
        },
    },

    plugins: [forms],
};
