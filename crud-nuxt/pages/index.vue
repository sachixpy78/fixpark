<script setup>
definePageMeta({ layout: false })

import { ref } from 'vue'

const config = useRuntimeConfig()
const router = useRouter()

const loading = ref(null) // 'motor' | 'mobil' | null

const ambilTiket = async (jenis) => {
  try {
    loading.value = jenis
    const res = await $fetch('/tiket/non-member', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: { jenis_kendaraan: jenis },
    })
    router.push(`/tiket/${res.tiket.barcode}`)
  } catch (err) {
    alert(err?.data?.message || 'Gagal membuat tiket, coba lagi')
  } finally {
    loading.value = null
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden bg-parkir bg-cover bg-center">
    <!-- Background Image -->
    <div class="absolute inset-0 bg-parkir bg-cover bg-center bg-no-repeat"></div>
    
    <!-- Dark Overlay - lebih terang agar gambar mobil terlihat -->
    <div class="absolute inset-0 bg-gradient-to-br from-slate-900/70 via-slate-900/50 to-slate-900/70"></div>
    
    <!-- Decorative blue accents -->
    <div class="absolute top-0 left-0 w-1/2 h-full bg-gradient-to-br from-blue-600/10 to-transparent"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
    
    <!-- Decorative Elements -->
    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-blue-500/50 to-transparent"></div>
    <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-blue-500/50 to-transparent"></div>

    <!-- Main Container -->
    <div class="relative z-10 w-full max-w-3xl">
      <!-- Main Card -->
      <div class="bg-slate-800/40 backdrop-blur-2xl rounded-3xl shadow-2xl border border-white/10 p-8 md:p-12">
        
        <!-- Header -->
        <div class="text-center mb-10">
          <div class="flex items-center justify-center gap-2 mb-3">
            <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
            <span class="text-slate-300 text-xs font-semibold tracking-[0.2em] uppercase">Parkir Plaza Andalas</span>
          </div>
          <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight mb-2">
            AMBIL TIKET PARKIR
          </h1>
          <p class="text-slate-300 text-sm">Pilih jenis kendaraan Anda</p>
        </div>

        <!-- Vehicle Selection -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <!-- Motor -->
          <button
            @click="ambilTiket('motor')"
            :disabled="loading !== null"
            class="group relative flex flex-col items-center justify-center p-6 bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl hover:bg-white/10 hover:border-white/20 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed overflow-hidden"
          >
            <!-- Hover Effect Background -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/0 to-blue-600/0 group-hover:from-blue-500/10 group-hover:to-blue-600/10 transition-all duration-300"></div>
            
            <!-- Vehicle Image -->
            <div class="relative w-full h-40 mb-4 flex items-center justify-center">
              <img 
                src="/images/motor-vespa.png" 
                alt="Motor" 
                class="h-full object-contain drop-shadow-2xl group-hover:scale-110 group-hover:-translate-y-2 transition-transform duration-300"
              />
            </div>
            
            <!-- Info -->
            <div class="relative text-center">
              <h3 class="text-xl font-bold text-white mb-1">Motor</h3>
              <p class="text-slate-300 text-sm font-medium">Rp 3.000/jam</p>
            </div>

            <!-- Loading Overlay -->
            <div v-if="loading === 'motor'" class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center rounded-2xl">
              <div class="text-center">
                <div class="w-8 h-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                <span class="text-blue-400 text-sm font-semibold">Memproses...</span>
              </div>
            </div>
          </button>

          <!-- Mobil -->
          <button
            @click="ambilTiket('mobil')"
            :disabled="loading !== null"
            class="group relative flex flex-col items-center justify-center p-6 bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl hover:bg-white/10 hover:border-white/20 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed overflow-hidden"
          >
            <!-- Hover Effect Background -->
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/0 to-emerald-600/0 group-hover:from-emerald-500/10 group-hover:to-emerald-600/10 transition-all duration-300"></div>
            
            <!-- Vehicle Image -->
            <div class="relative w-full h-40 mb-4 flex items-center justify-center">
              <img 
                src="/images/mobil-porsche.png" 
                alt="Mobil" 
                class="h-full object-contain drop-shadow-2xl group-hover:scale-110 group-hover:-translate-y-2 transition-transform duration-300"
              />
            </div>
            
            <!-- Info -->
            <div class="relative text-center">
              <h3 class="text-xl font-bold text-white mb-1">Mobil</h3>
              <p class="text-slate-300 text-sm font-medium">Rp 5.000/jam</p>
            </div>

            <!-- Loading Overlay -->
            <div v-if="loading === 'mobil'" class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center rounded-2xl">
              <div class="text-center">
                <div class="w-8 h-8 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                <span class="text-emerald-400 text-sm font-semibold">Memproses...</span>
              </div>
            </div>
          </button>
        </div>

        <!-- Divider -->
        <div class="flex items-center gap-4 mb-6">
          <div class="flex-1 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
          <span class="text-slate-400 text-xs font-medium uppercase tracking-wider">atau</span>
          <div class="flex-1 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
        </div>

        <!-- Member Card -->
        <NuxtLink
          to="/tiket/member"
          class="group flex items-center justify-center gap-3 w-full py-4 bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/15 hover:border-white/30 rounded-xl transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]"
        >
          <svg class="w-5 h-5 text-slate-300 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
          </svg>
          <span class="text-white font-semibold">Punya Kartu Member?</span>
        </NuxtLink>
        
        <p class="text-center text-slate-400 text-xs mt-3">Scan kartu member untuk parkir gratis</p>
      </div>

      <!-- Staff Login -->
      <div class="mt-6 text-center">
        <NuxtLink 
          to="/login" 
          class="inline-flex items-center gap-2 text-slate-300 hover:text-white text-sm transition-colors duration-300 group"
        >
          <span>Login Petugas / Admin</span>
          <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
          </svg>
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
.border-3 {
  border-width: 3px;
}
</style>