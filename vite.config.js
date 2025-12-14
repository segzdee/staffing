import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/sass/app.scss',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '~bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    // Build configuration
    build: {
        // Output to public/build directory
        outDir: 'public/build',
        // Generate manifest for Laravel in the expected location
        manifest: 'manifest.json',
        // Rollup options
        rollupOptions: {
            output: {
                // Clean asset filenames
                assetFileNames: 'assets/[name]-[hash][extname]',
                chunkFileNames: 'assets/[name]-[hash].js',
                entryFileNames: 'assets/[name]-[hash].js',
            },
            external: [
                // Don't bundle these - they'll be loaded via CDN or separate script tags
            ],
        },
        commonjsOptions: {
            include: [/node_modules/],
            transformMixedEsModules: true,
        },
    },
    // Server configuration for development
    server: {
        host: '127.0.0.1',
        port: 5173,
        // Hot Module Replacement
        hmr: {
            host: '127.0.0.1',
        },
    },
    // CSS configuration
    css: {
        preprocessorOptions: {
            scss: {
                // Modern API for Sass
                api: 'modern-compiler',
            },
        },
    },
});
