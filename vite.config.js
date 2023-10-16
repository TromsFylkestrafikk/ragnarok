import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import vuetify from 'vite-plugin-vuetify';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
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
        vuetify({
            autoImport: true,
            styles: { configFile: 'resources/sass/settings.scss' },
        }),
    ],
    resolve: {
        alias: { '@': '/resources/js' },
    },
    build: {
        watch: {
            include: [
                'resources/js/**',
                'resources/sass/**',
            ],
            exclude: ['app', 'bootstrap', 'config', 'database', 'etc', 'node_modules', 'public', 'routes', 'scripts', 'storage', 'tests', 'vendor'],
        },
    },
});
