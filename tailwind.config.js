/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './app/**/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './packages/theme-default/resources/views/**/*.blade.php',
        './packages/experience-cms/**/*.php',
    ],
    theme: {
        extend: {},
    },
    plugins: [],
};
