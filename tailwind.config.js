import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        // Add Filament paths
        './vendor/filament/**/*.blade.php',
        './app/Filament/**/*.php',
        // Shield Plugin
        './vendor/bezhansalleh/filament-shield/**/*.blade.php',
        // Quick Create Plugin
        './vendor/awcodes/filament-quick-create/resources/**/*.blade.php',
        // Sticky Header Plugin
        './vendor/awcodes/filament-sticky-header/resources/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter var', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                danger: colors.rose,
                primary: colors.blue,
                success: colors.emerald,
                warning: colors.orange,
                // Custom brand colors
                brand: {
                    50: '#f0f9ff',
                    100: '#e0f2fe',
                    200: '#bae6fd',
                    300: '#7dd3fc',
                    400: '#38bdf8',
                    500: '#0ea5e9',
                    600: '#0284c7',
                    700: '#0369a1',
                    800: '#075985',
                    900: '#0c4a6e',
                    950: '#082f49',
                },
            },
            maxWidth: {
                '8xl': '88rem',
            },
            spacing: {
                '18': '4.5rem',
            },
            borderRadius: {
                'xl': '1rem',
                '2xl': '2rem',
            },
            fontSize: {
                'xxs': '.625rem',
            },
            opacity: {
                '15': '.15',
                '35': '.35',
                '85': '.85',
            },
            zIndex: {
                '1': '1',
                '2': '2',
                '3': '3',
            },
            transitionProperty: {
                'max-height': 'max-height',
            },
            ringWidth: {
                '3': '3px',
            }
        },
    },

    plugins: [
        // Forms plugin for better form styling
        forms({
            strategy: 'class',
        }),
        // Typography plugin for rich text content
        typography,
        // Custom plugin for admin dashboard utilities
        function({ addComponents, addUtilities }) {
            addComponents({
                '.btn': {
                    '@apply inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150': {},
                },
                '.card': {
                    '@apply bg-white dark:bg-gray-800 rounded-lg shadow-sm': {},
                },
                '.input': {
                    '@apply block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500': {},
                },
            });

            addUtilities({
                '.scrollbar-hide': {
                    '-ms-overflow-style': 'none',
                    'scrollbar-width': 'none',
                    '&::-webkit-scrollbar': {
                        display: 'none',
                    },
                },
            });
        },
    ],

    // Filament-specific configurations
    safelist: [
        'sm:max-w-sm',
        'sm:max-w-md',
        'sm:max-w-lg',
        'sm:max-w-xl',
        'sm:max-w-2xl',
        'sm:max-w-3xl',
        'sm:max-w-4xl',
        'sm:max-w-5xl',
        'sm:max-w-6xl',
        'sm:max-w-7xl',
        'md:max-w-lg',
        'md:max-w-xl',
        'lg:max-w-2xl',
        'lg:max-w-3xl',
        'xl:max-w-4xl',
        'xl:max-w-5xl',
        '2xl:max-w-6xl',
        '2xl:max-w-7xl',
    ],
};