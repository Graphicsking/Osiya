import { cpSync } from 'node:fs'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

const base = process.env.PAGES_BASE_PATH || '/Osiya/'

export default defineConfig({
  base,
  publicDir: false,
  define: { 'import.meta.env.VITE_SITE_BASE_PATH': JSON.stringify(base) },
  plugins: [
    vue({ template: { transformAssetUrls: { base: null, includeAbsolute: false } } }),
    {
      name: 'copy-public-website-assets',
      closeBundle() {
        for (const path of ['images', 'favicon.svg', 'robots.txt']) {
          cpSync(`public/${path}`, `dist/${path}`, { recursive: true })
        }
      },
    },
  ],
})
