import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/pos.css', 'resources/css/errors.css', 'resources/js/app.js', 'resources/js/pos-bluetooth.js', 'resources/js/pos-date-range-picker.js', 'resources/js/server-clock.js', 'resources/js/pos.js', 'resources/css/filament/merchant/theme.css', 'resources/css/filament/admin/theme.css'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
            assets: [
                'resources/illustrations/**/*.svg',
                'resources/images/**/*',
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
