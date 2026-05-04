import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    /** public/vendor summernote + DataTables use require("jquery") / define(["jquery","datatables.net"]) */
    optimizeDeps: {
        include: ['jquery', 'datatables.net'],
    },
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/js/caller-page.js',
                'resources/js/receiver-page.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
