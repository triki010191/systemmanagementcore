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
            colors: {
                primary: '#004cca',
                'on-primary': '#ffffff',
                'primary-container': '#0062ff',
                'on-primary-container': '#f3f3ff',
                'on-primary-fixed-variant': '#003ea8',
                'primary-fixed': '#dbe1ff',
                secondary: '#565e74',
                'secondary-fixed': '#dae2fd',
                tertiary: '#48586d',
                'tertiary-fixed': '#d3e4fe',
                'tertiary-fixed-dim': '#b7c8e1',
                'on-tertiary': '#ffffff',
                'on-tertiary-container': '#eef3ff',
                'on-tertiary-fixed': '#0b1c30',
                'on-tertiary-fixed-variant': '#38485d',
                'on-surface': '#191c1e',
                'on-surface-variant': '#424656',
                surface: '#f7f9fb',
                'surface-container': '#eceef0',
                'surface-container-low': '#f2f4f6',
                'surface-container-lowest': '#ffffff',
                'surface-container-high': '#e6e8ea',
                'surface-container-highest': '#e0e3e5',
                outline: '#737687',
                'outline-variant': '#c2c6d9',
                error: '#ba1a1a',
                success: '#22c55e',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            fontSize: {
                'label-caps': ['11px', { lineHeight: '16px', letterSpacing: '0.05em', fontWeight: '700' }],
                'body-sm': ['13px', { lineHeight: '18px' }],
                'body-md': ['14px', { lineHeight: '20px' }],
                'headline-md': ['24px', { lineHeight: '32px', letterSpacing: '-0.01em', fontWeight: '600' }],
            },
            borderRadius: {
                lg: '0.25rem',
                xl: '0.5rem',
            },
            spacing: {
                xs: '4px',
                sm: '8px',
                md: '16px',
                lg: '24px',
                xl: '32px',
            },
        },
    },

    plugins: [forms],
};
