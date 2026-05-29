import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { visualizer } from 'rollup-plugin-visualizer';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        vue(),
        // Bundle size analysis — only when ANALYZE=true
        process.env.ANALYZE && visualizer({ open: true, filename: 'dist/stats.html' }),
    ].filter(Boolean),

    publicDir: false,

    build: {
        rollupOptions: {
            input: {
                // CP Vue components — loaded by Statamic's CP
                cp: resolve(__dirname, 'resources/js/cp.js'),
                // Frontend vanilla JS — loaded on site pages
                beacon: resolve(__dirname, 'resources/js/beacon.js'),
            },
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: '[name]-[hash].js',
                assetFileNames: '[name].[ext]',
                // Keep beacon.js as an IIFE so it works without a module bundler
                format: 'es',
            },
        },
        outDir: 'public/vendor/beacon/build',
        emptyOutDir: true,
        manifest: true,
        // Target ES2017 for broad browser compatibility
        target: 'es2017',
        // Warn if beacon.js exceeds 8kb (minified)
        chunkSizeWarningLimit: 8,
    },
});
