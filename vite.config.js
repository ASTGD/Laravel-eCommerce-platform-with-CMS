import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    Object.assign(process.env, loadEnv(mode, process.cwd()));

    return {
        server: {
            host: process.env.VITE_HOST || '127.0.0.1',
            port: Number(process.env.VITE_PORT || 5173),
            cors: true,
        },

        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ],
    };
});
