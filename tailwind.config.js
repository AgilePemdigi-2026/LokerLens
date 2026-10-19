import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'Inter', ...defaultTheme.fontFamily.sans],
                serif: ['Playfair Display', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                navy: {
                    800: '#1f2d3a',
                    900: '#0b1c2c',
                },
                teal: {
                    600: '#1f8a7a',
                    700: '#16695d',
                },
                steel: {
                    600: '#2f5d7c',
                    700: '#23465e',
                },
                cream: {
                    50: '#f8f4ee',
                    100: '#f1ede5',
                },
                risk: {
                    high: '#c0392b',
                    mid: '#d68910',
                    low: '#1e8449',
                },
                darktext: '#14202b',
                mutedtext: '#6b7a88',
            },
            borderRadius: {
                card: '12px',
                control: '8px',
            },
        },
    },

    plugins: [forms],
};
