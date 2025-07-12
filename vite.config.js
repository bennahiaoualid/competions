import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin-sidebar.css',
                'resources/js/app.js',
                'resources/js/sidebar.js',
                'resources/js/notifications/NotificationManager.js',
                'resources/js/notifications/init.js',
            ],
            refresh: true,
        }),
    ],
});
