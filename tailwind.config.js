import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'system-ui', ...defaultTheme.fontFamily.sans],
                // Fraunces carries the financial personality on large numbers
                // and hero headlines; body copy stays on Inter.
                display: ['Fraunces', 'Georgia', ...defaultTheme.fontFamily.serif],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Brand: "Confiança serena" — deep evergreen for trust/growth.
                primary: {
                    50: '#ecfdf3',
                    100: '#d1fadf',
                    200: '#a6f4c5',
                    300: '#6ce9a6',
                    400: '#32d583',
                    500: '#12b76a',
                    600: '#039855',
                    700: '#027a48',
                    800: '#05603a',
                    900: '#054f31',
                    950: '#032b1c',
                },
                // Supporting teal-green (secondary actions, subtle accents).
                secondary: {
                    50: '#effefb',
                    100: '#c8fff0',
                    200: '#92ffe3',
                    300: '#53f7d3',
                    400: '#1ee3bf',
                    500: '#05c7a6',
                    600: '#00a189',
                    700: '#04806f',
                    800: '#0a655a',
                    900: '#0d534b',
                    950: '#00312e',
                },
                // Accent: warm amber/gold — reserved for CTAs and highlights.
                accent: {
                    50: '#fffaeb',
                    100: '#fef0c7',
                    200: '#fedf89',
                    300: '#fec84b',
                    400: '#fdb022',
                    500: '#f79009',
                    600: '#dc6803',
                    700: '#b54708',
                    800: '#93370d',
                    900: '#7a2e0e',
                    950: '#4e1d09',
                },
                // Surfaces: warm greenish-charcoal instead of cold blue-grey.
                surface: {
                    50: '#f7f6f2',
                    100: '#eceae2',
                    200: '#d9d6ca',
                    300: '#b9b5a5',
                    400: '#918d7d',
                    500: '#6f6c5e',
                    600: '#565448',
                    700: '#3d3c34',
                    800: '#232821',
                    900: '#161c18',
                    950: '#0c1512',
                },
            },
            boxShadow: {
                'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
                'card': '0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.03)',
                'card-hover': '0 4px 12px -2px rgba(0, 0, 0, 0.08), 0 2px 6px -1px rgba(0, 0, 0, 0.04)',
                'elevated': '0 10px 40px -10px rgba(0, 0, 0, 0.1), 0 2px 10px -2px rgba(0, 0, 0, 0.04)',
                'glow': '0 0 24px rgba(3, 152, 85, 0.18)',
                'glow-green': '0 0 24px rgba(18, 183, 106, 0.18)',
                'glow-amber': '0 0 24px rgba(247, 144, 9, 0.20)',
                'inner-soft': 'inset 0 2px 4px 0 rgba(0, 0, 0, 0.04)',
                'float': '0 20px 60px -15px rgba(0, 0, 0, 0.12), 0 8px 20px -8px rgba(0, 0, 0, 0.06)',
            },
            borderRadius: {
                // '2xl' (1rem) and '3xl' (1.5rem) match Tailwind defaults; only '4xl' is a real addition.
                '4xl': '2rem',
            },
            spacing: {
                '18': '4.5rem',
                '88': '22rem',
                '128': '32rem',
            },
            transitionTimingFunction: {
                // Smooth, slightly springy easing used across interactive elements.
                'out-expo': 'cubic-bezier(0.16, 1, 0.3, 1)',
                'out-back': 'cubic-bezier(0.34, 1.56, 0.64, 1)',
            },
            animation: {
                'fade-in': 'fadeIn 0.4s ease-out',
                'fade-in-up': 'fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1)',
                'slide-up': 'slideUp 0.3s ease-out',
                'slide-down': 'slideDown 0.2s ease-out',
                'slide-in-right': 'slideInRight 0.3s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'spin-slow': 'spin 2s linear infinite',
                'bounce-soft': 'bounceSoft 2s infinite',
                'float': 'float 6s ease-in-out infinite',
                'shimmer': 'shimmer 2s linear infinite',
                'count-up': 'fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1)',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                fadeInUp: {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideDown: {
                    '0%': { opacity: '0', transform: 'translateY(-10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideInRight: {
                    '0%': { opacity: '0', transform: 'translateX(20px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                scaleIn: {
                    '0%': { opacity: '0', transform: 'scale(0.95)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                bounceSoft: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-5px)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%': { transform: 'translateY(-10px)' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
            },
            backgroundImage: {
                'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                'gradient-conic': 'conic-gradient(from 180deg at 50% 50%, var(--tw-gradient-stops))',
            },
        },
    },
    plugins: [forms],
};
