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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                plannia: {
                    navy: '#1a2744',
                    'navy-hover': '#243352',
                    blue: '#2563eb',
                    'blue-hover': '#1d4ed8',
                    bg: '#eef1f6',
                    border: '#e2e8f0',
                },
            },
        },
    },

    plugins: [forms],
};
