import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/portal.css', 'resources/js/portal.js',
            'resources/css/doctors-index.css',
            'resources/css/doctors-show.css',
            'resources/css/specialties-show.css',
            'resources/css/appointments-create.css',
            'resources/css/appointments-index.css',
            'resources/css/appointments-show.css',
            'resources/css/queues-index.css',
            'resources/css/payments-index.css',
            'resources/css/appointments-cancel.css',
            'resources/css/appointments-reschedule.css',
        ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
