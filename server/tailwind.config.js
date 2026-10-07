import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

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
            },
            colors: {
                brand: {
                    950: '#05070d',
                    900: '#0a0e1a',
                    800: '#0f1524',
                },

                /*
                 * Цвета интерфейса.
                 *
                 * Значения лежат в app.css и меняются вместе с темой,
                 * поэтому в разметке не нужно писать «белое в тёмной,
                 * чёрное в светлой» — достаточно одного класса.
                 *
                 * ink — текст, surface — подложки, line — границы.
                 */
                ink: {
                    DEFAULT: 'rgb(var(--ink) / <alpha-value>)',
                    soft: 'rgb(var(--ink-soft) / <alpha-value>)',
                    faint: 'rgb(var(--ink-faint) / <alpha-value>)',
                    ghost: 'rgb(var(--ink-ghost) / <alpha-value>)',
                },
                surface: {
                    DEFAULT: 'rgb(var(--surface) / <alpha-value>)',
                    raised: 'rgb(var(--surface-raised) / <alpha-value>)',
                    sunken: 'rgb(var(--surface-sunken) / <alpha-value>)',
                    hover: 'rgb(var(--surface-hover) / <alpha-value>)',
                },
                line: {
                    DEFAULT: 'rgb(var(--line) / <alpha-value>)',
                    strong: 'rgb(var(--line-strong) / <alpha-value>)',
                },
                accent: {
                    DEFAULT: 'rgb(var(--accent) / <alpha-value>)',
                    ink: 'rgb(var(--accent-ink) / <alpha-value>)',
                },
            },
        },
    },

    plugins: [forms],
};
