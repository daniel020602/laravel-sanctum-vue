import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: './tests/setupTests.js',
    alias: {
      '@': path.resolve(__dirname, 'src')
    }
    ,
    coverage: {
      // use the v8 provider (requires @vitest/coverage-v8 which is installed)
      provider: 'v8',
      reporter: ['text', 'lcov'],
      reportsDirectory: 'coverage'
    }
  },
})
