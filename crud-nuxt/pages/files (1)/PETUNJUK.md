# Tema E-Parkir Plaza Andalas (dark glass)

## Cara pasang (3 langkah)
1. Salin folder ini ke root project Nuxt Anda dan timpa file dengan nama sama:
   - `assets/css/theme.css`         (baru)
   - `public/bg-parkir.jpg`         (baru, gambar latar)
   - `layouts/petugas.vue`          (baru, sidebar petugas)
   - `layouts/admin.vue`            (baru, sidebar admin)
   - `pages/...`                    (semua halaman, menimpa yang lama)
2. Daftarkan CSS di `nuxt.config.ts`:
   ```ts
   export default defineNuxtConfig({
     css: ['~/assets/css/theme.css'],
     // ...config lain tetap
   })
   ```
3. Jalankan ulang `npm run dev`.

## Yang berubah
- Sidebar tiap halaman dipindah ke `layouts/petugas.vue` dan `layouts/admin.vue`
  (halaman memakai `definePageMeta({ layout: 'petugas' | 'admin', middleware: ... })`).
- Semua class warna merah/putih diganti ke tema gelap kaca (Tailwind class saja).
- Grafik SVG di dashboard & laporan admin diganti ke warna biru muda.
- Logika (script) tidak diubah, kecuali `layout` pada `definePageMeta`.
- Saat dicetak (struk/invoice) halaman otomatis kembali putih-hitam.
