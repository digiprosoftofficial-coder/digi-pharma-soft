import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.scss', 'resources/js/app.js'],
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
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: false,
            base: '/',
            buildBase: '/build/',
            includeAssets: [
                'icons/icon-192.png',
                'icons/icon-512.png',
                'icons/apple-touch-icon.png',
                'images/product-placeholder.png',
            ],
            manifest: {
                name: 'Pharmacy POS',
                short_name: 'Pharmacy',
                description: 'Pharmacy management and point of sale',
                theme_color: '#0d9488',
                background_color: '#f8f9fa',
                display: 'standalone',
                orientation: 'any',
                start_url: '/pos',
                scope: '/',
                id: '/',
                categories: ['business', 'productivity'],
                icons: [
                    {
                        src: '/icons/icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,ico,png,svg,woff2}'],
                navigateFallback: null,
                runtimeCaching: [
                    {
                        urlPattern: ({ request, url }) =>
                            request.mode === 'navigate' && url.pathname.startsWith('/pos'),
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'pos-pages',
                            networkTimeoutSeconds: 4,
                            expiration: {
                                maxEntries: 8,
                                maxAgeSeconds: 60 * 60 * 24,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        urlPattern: ({ url }) => url.pathname === '/pos/offline-catalog',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'pos-offline-catalog',
                            networkTimeoutSeconds: 5,
                            expiration: {
                                maxEntries: 4,
                                maxAgeSeconds: 60 * 60 * 12,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        urlPattern: ({ url }) => url.pathname === '/catalog/product-search',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'product-search',
                            networkTimeoutSeconds: 3,
                            expiration: {
                                maxEntries: 40,
                                maxAgeSeconds: 60 * 60,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                ],
            },
            devOptions: {
                enabled: false,
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
