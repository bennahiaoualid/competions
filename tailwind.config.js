import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import colors from "tailwindcss/colors.js";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        "./resources/**/*.js",
        "./resources/**/*.vue",
        './app/Livewire/**/*Table.php',
        './vendor/power-components/livewire-powergrid/resources/views/**/*.php',
        './vendor/power-components/livewire-powergrid/src/Themes/Tailwind.php',
        'app/PowerGridThemes/*.php',
    ],
    darkMode:'false',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                arabic:['Cairo', 'sans-serif']
            },
            colors:{
                primary: {
                    DEFAULT: '#6366f1', // indigo-500
                    dark: '#4f46e5', // indigo-600
                },
                success: {
                    DEFAULT: '#10b981', // green-500
                    dark: '#059669', // green-600
                },
                warning: {
                    DEFAULT: '#f59e0b', // yellow-500
                    dark: '#d97706', // yellow-600
                },
                info: {
                    DEFAULT: '#3b82f6', // blue-500
                    dark: '#2563eb', // blue-600
                },
                danger: {
                    DEFAULT: '#ef4444', // red-500
                    dark: '#dc2626', // red-600
                },
                'pg-primary': colors.neutral,
                'pg-secondary': colors.blue,
            }
        },
    },

    plugins: [forms],
};
