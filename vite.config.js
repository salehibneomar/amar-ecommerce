import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { fileURLToPath } from 'node:url';
import { defineConfig, lazyPlugins, loadEnv } from 'vite-plus';

const lanHost = loadEnv('development', process.cwd(), '').VITE_DEV_HOST;

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                    optimizedFallbacks: false,
                }),
            ],
        }),
        tailwindcss({ optimize: false }),
    ]),
    server: {
        host: lanHost ? '0.0.0.0' : '127.0.0.1',
        hmr: lanHost ? { host: lanHost } : undefined,
        cors: true,
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/storage/**',
                fileURLToPath(new URL('./vendor/**', import.meta.url)),
            ],
        },
    },
});
