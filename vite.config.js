import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '127.0.0.1',
        port: 5173,
        headers: {
          'Access-Control-Allow-Origin': '*',
        },
      },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin-sidebar.css',
                'resources/css/user-sidebar.css',
                'resources/js/app.js',
                'resources/js/sidebar.js',
                'resources/js/notifications/NotificationManager.js',
                'resources/js/notifications/init.js',
                'resources/js/competition-coins-calculation.js',
            ],
            refresh: true,
        }),
    ],
});
