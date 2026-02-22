import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            // ============================================
            // STUDAI CAREER DESIGN SYSTEM - GOOGLE INSPIRED
            // Material Design 4.0 + Linear/Superhuman Minimalism
            // ============================================
            
            fontFamily: {
                // Primary: Google Sans / Inter for clean, modern look
                sans: ['Inter', 'Google Sans', ...defaultTheme.fontFamily.sans],
                display: ['Google Sans Display', 'Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', 'Fira Code', ...defaultTheme.fontFamily.mono],
            },

            fontSize: {
                // Refined typography scale
                'xs': ['0.75rem', { lineHeight: '1rem', letterSpacing: '0.01em' }],
                'sm': ['0.875rem', { lineHeight: '1.25rem', letterSpacing: '0.005em' }],
                'base': ['1rem', { lineHeight: '1.5rem', letterSpacing: '0' }],
                'lg': ['1.125rem', { lineHeight: '1.75rem', letterSpacing: '-0.005em' }],
                'xl': ['1.25rem', { lineHeight: '1.75rem', letterSpacing: '-0.01em' }],
                '2xl': ['1.5rem', { lineHeight: '2rem', letterSpacing: '-0.015em' }],
                '3xl': ['1.875rem', { lineHeight: '2.25rem', letterSpacing: '-0.02em' }],
                '4xl': ['2.25rem', { lineHeight: '2.5rem', letterSpacing: '-0.025em' }],
                '5xl': ['3rem', { lineHeight: '1.1', letterSpacing: '-0.03em' }],
                '6xl': ['3.75rem', { lineHeight: '1.05', letterSpacing: '-0.035em' }],
                '7xl': ['4.5rem', { lineHeight: '1', letterSpacing: '-0.04em' }],
            },

            colors: {
                // ========================================
                // GOOGLE-INSPIRED COLOR SYSTEM
                // ========================================
                
                // Primary Brand Colors
                'google': {
                    'blue': {
                        DEFAULT: '#1A73E8',
                        50: '#E8F0FE',
                        100: '#D2E3FC',
                        200: '#AECBFA',
                        300: '#8AB4F8',
                        400: '#669DF6',
                        500: '#4285F4',
                        600: '#1A73E8',
                        700: '#1967D2',
                        800: '#185ABC',
                        900: '#174EA6',
                    },
                    'red': {
                        DEFAULT: '#EA4335',
                        50: '#FCE8E6',
                        100: '#FAD2CF',
                        200: '#F6AEA9',
                        300: '#F28B82',
                        400: '#EE675C',
                        500: '#EA4335',
                        600: '#D93025',
                        700: '#C5221F',
                        800: '#B31412',
                        900: '#A50E0E',
                    },
                    'yellow': {
                        DEFAULT: '#FBBC05',
                        50: '#FEF7E0',
                        100: '#FEEFC3',
                        200: '#FDE293',
                        300: '#FDD663',
                        400: '#FCC934',
                        500: '#FBBC05',
                        600: '#F9AB00',
                        700: '#F29900',
                        800: '#EA8600',
                        900: '#E37400',
                    },
                    'green': {
                        DEFAULT: '#34A853',
                        50: '#E6F4EA',
                        100: '#CEEAD6',
                        200: '#A8DAB5',
                        300: '#81C995',
                        400: '#5BB974',
                        500: '#34A853',
                        600: '#1E8E3E',
                        700: '#188038',
                        800: '#137333',
                        900: '#0D652D',
                    },
                },

                // Neutral Grays (Google Material)
                'surface': {
                    DEFAULT: '#FFFFFF',
                    50: '#FAFAFA',
                    100: '#F5F5F5',
                    200: '#EEEEEE',
                    300: '#E0E0E0',
                    400: '#BDBDBD',
                    500: '#9E9E9E',
                    600: '#757575',
                    700: '#616161',
                    800: '#424242',
                    900: '#212121',
                },

                // Background variants
                'canvas': {
                    DEFAULT: '#FFFFFF',
                    light: '#FAFBFC',
                    subtle: '#F8F9FA',
                    muted: '#F1F3F4',
                    elevated: '#FFFFFF',
                },

                // Text colors
                'ink': {
                    DEFAULT: '#202124',
                    primary: '#202124',
                    secondary: '#5F6368',
                    tertiary: '#80868B',
                    disabled: '#9AA0A6',
                    inverse: '#FFFFFF',
                },

                // Semantic colors
                'status': {
                    'success': {
                        light: '#E6F4EA',
                        DEFAULT: '#34A853',
                        dark: '#137333',
                    },
                    'warning': {
                        light: '#FEF7E0',
                        DEFAULT: '#FBBC05',
                        dark: '#EA8600',
                    },
                    'error': {
                        light: '#FCE8E6',
                        DEFAULT: '#EA4335',
                        dark: '#B31412',
                    },
                    'info': {
                        light: '#E8F0FE',
                        DEFAULT: '#1A73E8',
                        dark: '#174EA6',
                    },
                },

                // AI-specific accent colors
                'ai': {
                    'purple': {
                        DEFAULT: '#A855F7',
                        light: '#F3E8FF',
                        dark: '#7C3AED',
                    },
                    'gradient-start': '#1A73E8',
                    'gradient-end': '#A855F7',
                },

                // Legacy StudAI colors (preserved for compatibility)
                'studai': {
                    'pink': {
                        DEFAULT: '#ec4899',
                        50: '#fdf2f8',
                        100: '#fce7f3',
                        200: '#fbcfe8',
                        300: '#f9a8d4',
                        400: '#f472b6',
                        500: '#ec4899',
                        600: '#db2777',
                        700: '#be185d',
                        800: '#9d174d',
                        900: '#831843',
                    },
                    'green': {
                        DEFAULT: '#10b981',
                        50: '#ecfdf5',
                        100: '#d1fae5',
                        200: '#a7f3d0',
                        300: '#6ee7b7',
                        400: '#34d399',
                        500: '#10b981',
                        600: '#059669',
                        700: '#047857',
                        800: '#065f46',
                        900: '#064e3b',
                    },
                    'blue': {
                        DEFAULT: '#1A73E8',
                        50: '#E8F0FE',
                        100: '#D2E3FC',
                        200: '#AECBFA',
                        300: '#8AB4F8',
                        400: '#669DF6',
                        500: '#4285F4',
                        600: '#1A73E8',
                        700: '#1967D2',
                        800: '#185ABC',
                        900: '#174EA6',
                    },
                    'yellow': {
                        DEFAULT: '#FBBC05',
                        50: '#FEF7E0',
                        100: '#FEEFC3',
                        200: '#FDE293',
                        300: '#FDD663',
                        400: '#FCC934',
                        500: '#FBBC05',
                        600: '#F9AB00',
                        700: '#F29900',
                        800: '#EA8600',
                        900: '#E37400',
                    },
                },
            },

            // ========================================
            // SPACING & LAYOUT SYSTEM
            // ========================================
            spacing: {
                '4.5': '1.125rem',
                '13': '3.25rem',
                '15': '3.75rem',
                '18': '4.5rem',
                '22': '5.5rem',
                '26': '6.5rem',
                '30': '7.5rem',
                '34': '8.5rem',
                '38': '9.5rem',
                '42': '10.5rem',
                '46': '11.5rem',
                '50': '12.5rem',
                '54': '13.5rem',
                '58': '14.5rem',
                '62': '15.5rem',
                '66': '16.5rem',
                '70': '17.5rem',
                '74': '18.5rem',
                '78': '19.5rem',
                '82': '20.5rem',
                '86': '21.5rem',
                '90': '22.5rem',
                '94': '23.5rem',
                '98': '24.5rem',
            },

            // ========================================
            // BORDER RADIUS (Material Design inspired)
            // ========================================
            borderRadius: {
                'none': '0',
                'sm': '4px',
                'DEFAULT': '8px',
                'md': '10px',
                'lg': '12px',
                'xl': '16px',
                '2xl': '20px',
                '3xl': '24px',
                '4xl': '32px',
                'full': '9999px',
                // Component-specific
                'card': '16px',
                'button': '8px',
                'input': '10px',
                'chip': '20px',
                'modal': '24px',
                'panel': '16px',
            },

            // ========================================
            // SHADOWS (Soft, Google-style)
            // ========================================
            boxShadow: {
                // Elevation system (Material Design inspired)
                'elevation-0': 'none',
                'elevation-1': '0 1px 2px 0 rgba(60, 64, 67, 0.3), 0 1px 3px 1px rgba(60, 64, 67, 0.15)',
                'elevation-2': '0 1px 2px 0 rgba(60, 64, 67, 0.3), 0 2px 6px 2px rgba(60, 64, 67, 0.15)',
                'elevation-3': '0 1px 3px 0 rgba(60, 64, 67, 0.3), 0 4px 8px 3px rgba(60, 64, 67, 0.15)',
                'elevation-4': '0 2px 3px 0 rgba(60, 64, 67, 0.3), 0 6px 10px 4px rgba(60, 64, 67, 0.15)',
                'elevation-5': '0 4px 4px 0 rgba(60, 64, 67, 0.3), 0 8px 12px 6px rgba(60, 64, 67, 0.15)',
                
                // Component shadows
                'card': '0 1px 2px 0 rgba(60, 64, 67, 0.3), 0 1px 3px 1px rgba(60, 64, 67, 0.15)',
                'card-hover': '0 1px 3px 0 rgba(60, 64, 67, 0.3), 0 4px 8px 3px rgba(60, 64, 67, 0.15)',
                'dropdown': '0 2px 6px 2px rgba(60, 64, 67, 0.15)',
                'modal': '0 8px 10px -6px rgba(0, 0, 0, 0.1), 0 20px 25px -5px rgba(0, 0, 0, 0.1)',
                'button': '0 1px 2px 0 rgba(60, 64, 67, 0.3), 0 1px 3px 1px rgba(60, 64, 67, 0.15)',
                'button-hover': '0 1px 3px 0 rgba(60, 64, 67, 0.3), 0 4px 8px 3px rgba(60, 64, 67, 0.15)',
                'input-focus': '0 0 0 2px rgba(26, 115, 232, 0.2)',
                'fab': '0 3px 5px -1px rgba(0, 0, 0, 0.2), 0 6px 10px 0 rgba(0, 0, 0, 0.14), 0 1px 18px 0 rgba(0, 0, 0, 0.12)',
                
                // Soft shadows (Apple-inspired)
                'soft-sm': '0 2px 8px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04)',
                'soft': '0 4px 12px rgba(0, 0, 0, 0.08), 0 2px 4px rgba(0, 0, 0, 0.04)',
                'soft-lg': '0 8px 24px rgba(0, 0, 0, 0.1), 0 4px 8px rgba(0, 0, 0, 0.05)',
                'soft-xl': '0 16px 48px rgba(0, 0, 0, 0.12), 0 8px 16px rgba(0, 0, 0, 0.06)',
                
                // Colored shadows
                'blue': '0 4px 14px 0 rgba(26, 115, 232, 0.39)',
                'green': '0 4px 14px 0 rgba(52, 168, 83, 0.39)',
                'red': '0 4px 14px 0 rgba(234, 67, 53, 0.39)',
                'purple': '0 4px 14px 0 rgba(168, 85, 247, 0.39)',
                
                // Inner shadows
                'inner-soft': 'inset 0 2px 4px 0 rgba(0, 0, 0, 0.05)',
            },

            // ========================================
            // GRADIENTS
            // ========================================
            backgroundImage: {
                // Brand gradients
                'gradient-studai': 'linear-gradient(135deg, #1A73E8 0%, #A855F7 100%)',
                'gradient-studai-green': 'linear-gradient(135deg, #34A853 0%, #1A73E8 100%)',
                'gradient-primary': 'linear-gradient(135deg, #1A73E8 0%, #0B57D0 100%)',
                'gradient-ai': 'linear-gradient(135deg, #1A73E8 0%, #A855F7 50%, #EA4335 100%)',
                
                // Subtle gradients for backgrounds
                'gradient-subtle': 'linear-gradient(180deg, #FFFFFF 0%, #F8F9FA 100%)',
                'gradient-canvas': 'linear-gradient(135deg, #F8F9FA 0%, #FFFFFF 50%, #F8F9FA 100%)',
                
                // Glass effect backgrounds
                'glass': 'linear-gradient(135deg, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0.1) 100%)',
                
                // Mesh gradients for hero sections
                'mesh-blue': 'radial-gradient(at 40% 20%, rgba(26, 115, 232, 0.1) 0px, transparent 50%), radial-gradient(at 80% 0%, rgba(168, 85, 247, 0.1) 0px, transparent 50%), radial-gradient(at 0% 50%, rgba(52, 168, 83, 0.1) 0px, transparent 50%)',
            },

            // ========================================
            // ANIMATIONS & TRANSITIONS
            // ========================================
            animation: {
                'fade-in': 'fadeIn 0.3s ease-out',
                'fade-in-up': 'fadeInUp 0.4s ease-out',
                'fade-in-down': 'fadeInDown 0.4s ease-out',
                'slide-up': 'slideUp 0.4s ease-out',
                'slide-down': 'slideDown 0.4s ease-out',
                'slide-left': 'slideLeft 0.4s ease-out',
                'slide-right': 'slideRight 0.4s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'pulse-soft': 'pulseSoft 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'shimmer': 'shimmer 2s linear infinite',
                'spin-slow': 'spin 3s linear infinite',
                'bounce-soft': 'bounceSoft 1s ease-in-out infinite',
                'float': 'float 6s ease-in-out infinite',
                'glow': 'glow 2s ease-in-out infinite alternate',
                'progress': 'progress 1.5s ease-in-out infinite',
            },

            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                fadeInUp: {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                fadeInDown: {
                    '0%': { opacity: '0', transform: 'translateY(-10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideUp: {
                    '0%': { transform: 'translateY(20px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                slideDown: {
                    '0%': { transform: 'translateY(-20px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                slideLeft: {
                    '0%': { transform: 'translateX(20px)', opacity: '0' },
                    '100%': { transform: 'translateX(0)', opacity: '1' },
                },
                slideRight: {
                    '0%': { transform: 'translateX(-20px)', opacity: '0' },
                    '100%': { transform: 'translateX(0)', opacity: '1' },
                },
                scaleIn: {
                    '0%': { transform: 'scale(0.95)', opacity: '0' },
                    '100%': { transform: 'scale(1)', opacity: '1' },
                },
                pulseSoft: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.7' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
                bounceSoft: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-5px)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-10px)' },
                },
                glow: {
                    '0%': { boxShadow: '0 0 5px rgba(26, 115, 232, 0.5)' },
                    '100%': { boxShadow: '0 0 20px rgba(26, 115, 232, 0.8)' },
                },
                progress: {
                    '0%': { width: '0%' },
                    '100%': { width: '100%' },
                },
            },

            // ========================================
            // TRANSITIONS
            // ========================================
            transitionDuration: {
                '0': '0ms',
                '75': '75ms',
                '100': '100ms',
                '150': '150ms',
                '200': '200ms',
                '250': '250ms',
                '300': '300ms',
                '400': '400ms',
                '500': '500ms',
            },

            transitionTimingFunction: {
                'smooth': 'cubic-bezier(0.4, 0, 0.2, 1)',
                'bounce': 'cubic-bezier(0.68, -0.55, 0.265, 1.55)',
                'spring': 'cubic-bezier(0.175, 0.885, 0.32, 1.275)',
            },

            // ========================================
            // Z-INDEX SCALE
            // ========================================
            zIndex: {
                'behind': '-1',
                'base': '0',
                'raised': '1',
                'dropdown': '1000',
                'sticky': '1020',
                'fixed': '1030',
                'modal-backdrop': '1040',
                'modal': '1050',
                'popover': '1060',
                'tooltip': '1070',
                'toast': '1080',
                'max': '9999',
            },

            // ========================================
            // CONTAINER CONFIG
            // ========================================
            container: {
                center: true,
                padding: {
                    DEFAULT: '1rem',
                    sm: '1.5rem',
                    lg: '2rem',
                    xl: '3rem',
                    '2xl': '4rem',
                },
            },

            // ========================================
            // GRID SYSTEM
            // ========================================
            gridTemplateColumns: {
                'sidebar': '280px 1fr',
                'sidebar-collapsed': '72px 1fr',
                'dashboard': '280px 1fr 320px',
                'job-search': '280px 1fr 400px',
                'ats': '300px 1fr 380px',
            },

            // ========================================
            // WIDTH & HEIGHT
            // ========================================
            width: {
                'sidebar': '280px',
                'sidebar-collapsed': '72px',
                'panel-sm': '320px',
                'panel-md': '380px',
                'panel-lg': '440px',
                'modal-sm': '400px',
                'modal-md': '560px',
                'modal-lg': '720px',
                'modal-xl': '900px',
            },

            minWidth: {
                'sidebar': '280px',
                'panel': '320px',
            },

            maxWidth: {
                'prose': '65ch',
                'content': '1280px',
                'wide': '1440px',
                'full': '100%',
            },

            // ========================================
            // BACKDROP BLUR
            // ========================================
            backdropBlur: {
                xs: '2px',
            },

            // ========================================
            // ASPECT RATIOS
            // ========================================
            aspectRatio: {
                'card': '4 / 3',
                'hero': '16 / 9',
                'square': '1 / 1',
                'video': '16 / 9',
                'portrait': '3 / 4',
            },
        },
    },

    plugins: [
        forms,
        // Custom plugin for component utilities
        function({ addComponents, addUtilities, theme }) {
            // ========================================
            // CARD COMPONENTS
            // ========================================
            addComponents({
                '.card': {
                    backgroundColor: theme('colors.canvas.DEFAULT'),
                    borderRadius: theme('borderRadius.card'),
                    boxShadow: theme('boxShadow.card'),
                    border: '1px solid ' + theme('colors.surface.200'),
                    transition: 'all 0.2s ease',
                    '&:hover': {
                        boxShadow: theme('boxShadow.card-hover'),
                    },
                },
                '.card-elevated': {
                    backgroundColor: theme('colors.canvas.DEFAULT'),
                    borderRadius: theme('borderRadius.card'),
                    boxShadow: theme('boxShadow.elevation-2'),
                    transition: 'all 0.2s ease',
                    '&:hover': {
                        boxShadow: theme('boxShadow.elevation-3'),
                        transform: 'translateY(-2px)',
                    },
                },
                '.card-glass': {
                    backgroundColor: 'rgba(255, 255, 255, 0.8)',
                    backdropFilter: 'blur(12px)',
                    borderRadius: theme('borderRadius.card'),
                    border: '1px solid rgba(255, 255, 255, 0.3)',
                    boxShadow: theme('boxShadow.soft'),
                },
            });

            // ========================================
            // BUTTON COMPONENTS
            // ========================================
            addComponents({
                '.btn': {
                    display: 'inline-flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontWeight: '500',
                    borderRadius: theme('borderRadius.button'),
                    transition: 'all 0.15s ease',
                    cursor: 'pointer',
                    '&:disabled': {
                        opacity: '0.5',
                        cursor: 'not-allowed',
                    },
                },
                '.btn-primary': {
                    backgroundColor: theme('colors.google.blue.DEFAULT'),
                    color: '#FFFFFF',
                    boxShadow: theme('boxShadow.button'),
                    '&:hover:not(:disabled)': {
                        backgroundColor: theme('colors.google.blue.700'),
                        boxShadow: theme('boxShadow.button-hover'),
                    },
                    '&:active:not(:disabled)': {
                        transform: 'scale(0.98)',
                    },
                },
                '.btn-secondary': {
                    backgroundColor: theme('colors.canvas.subtle'),
                    color: theme('colors.google.blue.DEFAULT'),
                    border: '1px solid ' + theme('colors.surface.300'),
                    '&:hover:not(:disabled)': {
                        backgroundColor: theme('colors.google.blue.50'),
                        borderColor: theme('colors.google.blue.DEFAULT'),
                    },
                },
                '.btn-ghost': {
                    backgroundColor: 'transparent',
                    color: theme('colors.ink.secondary'),
                    '&:hover:not(:disabled)': {
                        backgroundColor: theme('colors.surface.100'),
                    },
                },
            });

            // ========================================
            // INPUT COMPONENTS
            // ========================================
            addComponents({
                '.input': {
                    width: '100%',
                    borderRadius: theme('borderRadius.input'),
                    border: '1px solid ' + theme('colors.surface.300'),
                    backgroundColor: theme('colors.canvas.DEFAULT'),
                    padding: '0.625rem 0.875rem',
                    fontSize: theme('fontSize.sm')[0],
                    transition: 'all 0.15s ease',
                    '&:focus': {
                        outline: 'none',
                        borderColor: theme('colors.google.blue.DEFAULT'),
                        boxShadow: theme('boxShadow.input-focus'),
                    },
                    '&::placeholder': {
                        color: theme('colors.ink.tertiary'),
                    },
                },
                '.input-search': {
                    paddingLeft: '2.75rem',
                    borderRadius: theme('borderRadius.full'),
                },
            });

            // ========================================
            // CHIP/TAG COMPONENTS
            // ========================================
            addComponents({
                '.chip': {
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '0.25rem',
                    padding: '0.25rem 0.75rem',
                    fontSize: theme('fontSize.xs')[0],
                    fontWeight: '500',
                    borderRadius: theme('borderRadius.chip'),
                    transition: 'all 0.15s ease',
                },
                '.chip-default': {
                    backgroundColor: theme('colors.surface.100'),
                    color: theme('colors.ink.secondary'),
                },
                '.chip-primary': {
                    backgroundColor: theme('colors.google.blue.50'),
                    color: theme('colors.google.blue.DEFAULT'),
                },
                '.chip-success': {
                    backgroundColor: theme('colors.google.green.50'),
                    color: theme('colors.google.green.DEFAULT'),
                },
                '.chip-warning': {
                    backgroundColor: theme('colors.google.yellow.50'),
                    color: theme('colors.google.yellow.700'),
                },
                '.chip-error': {
                    backgroundColor: theme('colors.google.red.50'),
                    color: theme('colors.google.red.DEFAULT'),
                },
            });

            // ========================================
            // UTILITY CLASSES
            // ========================================
            addUtilities({
                '.text-gradient': {
                    'background': 'linear-gradient(135deg, #1A73E8 0%, #A855F7 100%)',
                    '-webkit-background-clip': 'text',
                    '-webkit-text-fill-color': 'transparent',
                    'background-clip': 'text',
                },
                '.text-gradient-ai': {
                    'background': 'linear-gradient(135deg, #1A73E8 0%, #A855F7 50%, #EA4335 100%)',
                    '-webkit-background-clip': 'text',
                    '-webkit-text-fill-color': 'transparent',
                    'background-clip': 'text',
                },
                '.scrollbar-hide': {
                    '-ms-overflow-style': 'none',
                    'scrollbar-width': 'none',
                    '&::-webkit-scrollbar': {
                        display: 'none',
                    },
                },
                '.scrollbar-thin': {
                    'scrollbar-width': 'thin',
                    '&::-webkit-scrollbar': {
                        width: '6px',
                        height: '6px',
                    },
                    '&::-webkit-scrollbar-track': {
                        background: 'transparent',
                    },
                    '&::-webkit-scrollbar-thumb': {
                        background: theme('colors.surface.300'),
                        borderRadius: '3px',
                    },
                    '&::-webkit-scrollbar-thumb:hover': {
                        background: theme('colors.surface.400'),
                    },
                },
                '.focus-ring': {
                    '&:focus': {
                        outline: 'none',
                        boxShadow: '0 0 0 2px ' + theme('colors.google.blue.100') + ', 0 0 0 4px ' + theme('colors.google.blue.DEFAULT'),
                    },
                },
            });
        },
    ],
};
