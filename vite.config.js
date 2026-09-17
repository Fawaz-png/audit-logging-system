import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    // Manually force Vite to read the raw .env file from the project root
    const env = loadEnv(mode, process.cwd(), '');

    // Fallback directly to your Codespace domain if the env variable isn't found
    const devServerUrl = env.VITE_DEV_SERVER_URL || 'https://symmetrical-zebra-vwr6gg9vxwr2xjrx-5173.app.github.dev';

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
                // This forces Vite to write the secure proxy URL into public/hot
                devServerUrl: devServerUrl,
                fonts: [
                    bunny('Instrument Sans', {
                        weights: [400, 500, 600, 700],
                    }),
                ],
            }),
            tailwindcss(),
            vue(),
        ],
        server: {
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
            cors: true,
            hmr: {
                host: 'symmetrical-zebra-vwr6gg9vxwr2xjrx-5173.app.github.dev',
                protocol: 'wss',
                clientPort: 443
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
