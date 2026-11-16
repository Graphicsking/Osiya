import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [laravel({ input: ['src/main.js'], refresh: ['resources/views/**', 'routes/**'] }), vue({
    template: { transformAssetUrls: { base: null, includeAbsolute: false } },
  })],
  server: { proxy: { '/api': 'http://127.0.0.1:8000', '/admin': 'http://127.0.0.1:8000' } },
})
