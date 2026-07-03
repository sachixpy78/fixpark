// nuxt.config.ts
import { defineNuxtConfig } from 'nuxt/config' // ✅ ini penting biar gak merah

export default defineNuxtConfig({
  devtools: { enabled: true },

  // ✅ Import file CSS utama kamu (Tailwind)
   css: ['~/assets/css/tailwind.css'],
  postcss: {
    plugins: {
      tailwindcss: {},
      autoprefixer: {},
    },
  },
  compatibilityDate: '2025-11-13',
})