import forms from '@tailwindcss/forms';

export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/**/*.php',
        './vendor/wireui/wireui/resources/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                warehouse: {
                    yellow: '#FDD07D',
                    orange: '#EB9800',
                    ink: '#333333',
                    soft: '#EDEDED',
                },
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [forms],
};
