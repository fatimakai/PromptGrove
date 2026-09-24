import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['IBM Plex Sans', ...defaultTheme.fontFamily.sans],
                display: ['Barlow Condensed', ...defaultTheme.fontFamily.sans],
                mono: ['IBM Plex Mono', ...defaultTheme.fontFamily.mono],
            },
        },
    },

    safelist: [
        // Indigo colors
        'bg-indigo-100',
        'dark:bg-indigo-900',
        'text-indigo-700',
        'dark:text-indigo-300',
        // Blue colors
        'bg-blue-100',
        'dark:bg-blue-900',
        'text-blue-700',
        'dark:text-blue-300',
        'bg-blue-600',
        'hover:bg-blue-700',
        // Green colors
        'bg-green-100',
        'dark:bg-green-900',
        'text-green-700',
        'dark:text-green-300',
        // Gray colors
        'bg-gray-100',
        'dark:bg-gray-700',
        'text-gray-700',
        'dark:text-gray-300',
        'bg-gray-900',
        'dark:bg-gray-900',
        'bg-gray-800',
        'dark:bg-gray-800',
        'bg-gray-50',
        'dark:bg-gray-900',
        'bg-white',
        'dark:bg-gray-800',
        'text-gray-800',
        'dark:text-gray-200',
        'text-gray-600',
        'dark:text-gray-300',
        'text-gray-500',
        'dark:text-gray-400',
        'text-gray-400',
        'dark:text-gray-500',
        'text-gray-100',
        'dark:text-gray-100',
        'border-gray-100',
        'dark:border-gray-700',
        'border-gray-200',
        'dark:border-gray-700',
        'border-gray-300',
        'dark:border-gray-700',
        'border-gray-600',
        'dark:border-gray-600',
        'border-gray-700',
        'dark:border-gray-700',
        // Yellow
        'bg-yellow-500',
        'hover:bg-yellow-600',
        // Red
        'bg-red-600',
        'hover:bg-red-700',
        // Gray
        'bg-gray-600',
        'hover:bg-gray-700',
        'bg-gray-700',
        'hover:bg-gray-800',
        'bg-gray-800',
        'hover:bg-gray-900',
        // Indigo
        'bg-indigo-500',
        'hover:bg-indigo-600',
        // Rose
        'bg-rose-600',
        'hover:bg-rose-700',
        'text-white',
        'text-sm',
        'px-3',
        // Other utilities
        'px-2',
        'py-1',
        'px-3',
        'px-4',
        'py-2',
        'text-xs',
        'text-sm',
        'font-medium',
        'rounded-full',
        'flex-wrap',
        'justify-end',
    ],

    plugins: [forms],
};
