import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig(({ command }) => ({
  // Only enable devtools in dev mode. Loading the devtools plugin during
  // production build can pull Vite/Rollup server-side internals into the
  // bundle and cause Node builtin externalization issues (e.g. node:fs,
  // node:path). Restricting it to the dev server avoids that.
  plugins: [
    vue(),
    command === 'serve' ? vueDevTools() : null,
  ].filter(Boolean),
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
      // Map Node builtin 'node:path' and 'path' to a browser-friendly polyfill
      // so Rollup internals that import `node:path` don't get externalized
      // to __vite-browser-external which lacks named exports like `basename`.
      'node:path': 'path-browserify',
      path: 'path-browserify',
    },
  },
  server: {
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        headers: {
          Accept: 'application/json',
          "Content-Type": 'application/json',
        },
      }
    }
  },
  build: {
    rollupOptions: {
      // Externalize modules that cause native/pkg resolution issues on Windows
      // (fsevents is macOS-only; lightningcss may reference a sibling `pkg` wasm bundle).
      external: ['fsevents', 'lightningcss', '../pkg']
    }
  },
  css: {
    lightningcss: false
  },
}))
