const defaultTheme = require('tailwindcss/defaultTheme');
const forms = require('@tailwindcss/forms');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            colors: {
                ink: {
                    DEFAULT: '#0F1419',
                    soft: '#1A2332',
                    muted: '#5C6B7A',
                },
                surface: {
                    DEFAULT: '#F7F8FA',
                    card: '#FFFFFF',
                    border: '#E5E8EC',
                },
                accent: {
                    DEFAULT: '#0D9488',
                    hover: '#0F766E',
                    soft: '#CCFBF1',
                    muted: '#14B8A6',
                },
            },
            fontFamily: {
                sans: ['"DM Sans"', ...defaultTheme.fontFamily.sans],
                display: ['"Instrument Sans"', '"DM Sans"', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                soft: '0 1px 2px rgba(15, 20, 25, 0.04), 0 4px 12px rgba(15, 20, 25, 0.04)',
            },
        },
    },

    plugins: [forms],
};
