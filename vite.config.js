import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/portal.css', 'resources/css/portal-consistency.css', 'resources/js/portal.js',
            'resources/css/employee/payments/index.css',
            'resources/css/employee/payments/show.css',
            'resources/css/employee/payments/collect.css',
            'resources/js/employee/payments/collect.js',
            'resources/css/employee.css',
            'resources/js/employee.js',
            'resources/css/doctor-examination.css',
            'resources/js/doctor-examination.js',
            'resources/js/print-document.js',
            'resources/css/employee-payments.css',
            'resources/css/payment-receipt.css',
            'resources/css/doctors-index.css',
            'resources/css/doctors-show.css',
            'resources/css/specialties-show.css',
            'resources/css/appointments-create.css',
            'resources/css/appointments-index.css',
            'resources/css/appointments-show.css',
            'resources/css/queues-index.css',
            'resources/css/payments-index.css',
            'resources/js/portal/payments-sync.js',
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
