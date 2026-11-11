// nuxt.config.ts
import { defineNuxtConfig } from 'nuxt/config'

export default defineNuxtConfig({
  devtools: { enabled: true },

  css: [
    '~/assets/css/tailwind.css',
    '~/assets/css/theme.css',   // ← tambahkan baris ini
  ],

  postcss: {
    plugins: {
      tailwindcss: {},
      autoprefixer: {},
    },
  },
  compatibilityDate: '2025-11-13',

  runtimeConfig: {
    public: {
      apiBase: 'http://127.0.0.1:8000/api',
    },
  },
})