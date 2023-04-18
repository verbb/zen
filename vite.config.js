import path from 'path';

// Vite Plugins
import VuePlugin from '@vitejs/plugin-vue';
import EslintPlugin from 'vite-plugin-eslint';
import CompressionPlugin from 'vite-plugin-compression';

// Rollup Plugins
import { nodeResolve } from '@rollup/plugin-node-resolve';
import AnalyzePlugin from 'rollup-plugin-analyzer';

export default ({ command }) => ({
    // Set the root to our source folder
    root: './src/web/assets',

    // When building update the destination base
    base: command === 'serve' ? '' : '/dist/',

    build: {
        outDir: 'app/dist',
        emptyOutDir: true,
        manifest: true,
        sourcemap: true,
        rollupOptions: {
            input: {
                zen: '/app/src/js/zen.js',
            },
        },
    },

    server: {
        origin: 'http://localhost:4030',

        hmr: {
            // Using the default `wss` doesn't work on https
            protocol: 'ws',
        },
    },

    plugins: [
        // Keep JS looking good with eslint
        // https://github.com/gxmari007/vite-plugin-eslint
        EslintPlugin({
            cache: false,
            fix: true,
            include: './src/web/assets/**/*.{js,vue}',
            exclude: './src/web/assets/app/src/js/vendor/**/*.{js,vue}',
        }),

        // Vue 3 support
        // https://github.com/vitejs/vite/tree/main/packages/plugin-vue
        VuePlugin(),

        // Analyze bundle size
        // https://github.com/doesdev/rollup-plugin-analyzer
        AnalyzePlugin({
            summaryOnly: true,
            limit: 15,
        }),

        // Gzip assets
        // https://github.com/vbenjs/vite-plugin-compression
        CompressionPlugin({
            filter: /\.(js|mjs|json|css|map)$/i,
        }),
    ],

    resolve: {
        alias: {
            // // Allow us to use `@/` in JS, CSS and Twig for ease of development.
            '@': path.resolve('./src/web/assets/app/src'),

            // Allow us to use `@utils/` in JS for misc utilities.
            '@utils': path.resolve('./src/web/assets/app/src/js/utils'),

            // Allow us to use `@components/` in Vue components.
            '@components': path.resolve('./src/web/assets/app/src/js/components'),

            // Vue 3 doesn't support the template compiler out of the box
            'vue': 'vue/dist/vue.esm-bundler.js',
        },
    },

    // Add in any components to optimise them early.
    optimizeDeps: {
        include: [
            'lodash-es',
            'vue',
        ],
    },
});
