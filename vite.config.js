import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/custom.css',
                'resources/css/hq-dashboard-v2.css',
                'resources/css/application-review-v2.css',
                'resources/js/hq-dashboard-v2.js',
                'resources/js/const-dashboard-v2.js',
            ],
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
            ],
        }),
    ],
    server: {
        host: '0.0.0.0',          // ← Crucial: listen on all interfaces
        port: 5173,
        strictPort: true,         // Optional but good
        hmr: {
            host: 'localhost',    // or your host machine IP if needed
        },
        watch: {
            usePolling: true,     // ← Fixes hot reload in Docker (very important)
            interval: 1000,       // Optional: tune if too CPU heavy
        },
    },
});
