import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        // The development server is intentionally loopback-only. Use an explicit,
        // reviewed proxy rather than exposing source files on a LAN or the Internet.
        host: '127.0.0.1',
        allowedHosts: ['localhost'],
        cors: {
            origin: /^https?:\/\/(?:localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$/,
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
