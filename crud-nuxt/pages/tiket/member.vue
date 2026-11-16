<script setup>
definePageMeta({ layout: false })

import { ref, onMounted, nextTick } from 'vue'

const config = useRuntimeConfig()
const router = useRouter()

const noMember       = ref('')
const jenisKendaraan = ref('motor')
const memberData     = ref(null)
const loading        = ref(false)
const checking       = ref(false)
const inputRef       = ref(null)

onMounted(() => inputRef.value?.focus())

// Cek data kartu member
const cekKartu = async () => {
  const no = noMember.value.trim()
  if (!no) return
  try {
    checking.value = true
    const res = await $fetch('/member/check', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: { no_member: no },
    })
    memberData.value = res
  } catch (err) {
    alert(err?.data?.message || 'Kartu member tidak valid, expired, atau tidak aktif!')
    memberData.value = null
    noMember.value = ''
    nextTick(() => inputRef.value?.focus())
  } finally {
    checking.value = false
  }
}

// Buat tiket member — plat diisi nanti saat keluar oleh petugas
const submitTiket = async () => {
  if (!noMember.value.trim()) return
  try {
    loading.value = true
    const res = await $fetch('/tiket/member', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: {
        no_member: noMember.value.trim(),
        jenis_kendaraan: jenisKendaraan.value,
      },
    })
    router.push(`/tiket/${res.tiket.barcode}`)
  } catch (err) {
    alert(err?.data?.message || 'Gagal membuat tiket member')
  } finally {
    loading.value = false
  }
}

const gantiKartu = () => {
  memberData.value = null
  noMember.value = ''
  nextTick(() => inputRef.value?.focus())
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 relative overflow-hidden">
    <!-- Background Overlay -->
    <div class="absolute inset-0 bg-gradient-to-br from-slate-900/95 via-slate-900/90 to-slate-950/95"></div>

    <!-- Top Header Bar -->
    <div class="relative z-20 bg-slate-800/80 backdrop-blur-xl border-b border-white/10">
      <div class="max-w-7xl mx-auto px-6 py-4">
        <div class="flex items-center justify-between">
          <!-- Logo & Title -->
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
              <span class="text-white font-black text-2xl">P</span>
            </div>
            <div>
              <h1 class="text-xl font-bold text-white">E-PARKIR</h1>
              <p class="text-slate-400 text-xs">Plaza Andalas</p>
            </div>
          </div>
          <!-- Back Button -->
          <NuxtLink to="/" class="flex items-center gap-2 text-slate-400 hover:text-white text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Kiosk Utama
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 flex items-center justify-center min-h-[calc(100vh-73px)] px-6 py-8">
      <div class="w-full max-w-md">
        <div class="bg-slate-800/80 backdrop-blur-xl rounded-3xl border border-white/10 p-8">

          <!-- TAHAP 1: SCAN KARTU MEMBER -->
          <div v-if="!memberData" class="space-y-6">
            <div class="text-center mb-6">
              <div class="w-16 h-16 rounded-full bg-slate-700/50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
              </div>
              <h3 class="text-xl font-bold text-white mb-1">Tiket Parkir Member</h3>
              <p class="text-slate-400 text-sm">Scan atau ketik nomor kartu member Anda</p>
            </div>

            <!-- Input Field -->
            <div class="relative">
              <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
              </svg>
              <input
                ref="inputRef"
                v-model="noMember"
                type="text"
                placeholder="Scan QR atau ketik no. member..."
                class="w-full pl-12 pr-4 py-4 bg-slate-700/50 border-2 border-slate-600 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 outline-none text-white placeholder:text-slate-500 transition-all"
                @keydown.enter="cekKartu"
                :disabled="checking"
              />
            </div>

            <button
              @click="cekKartu"
              :disabled="checking || !noMember.trim()"
              class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-blue-900/40 flex items-center justify-center gap-2"
            >
              <svg v-if="checking" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span>{{ checking ? 'Memverifikasi...' : 'Verifikasi Kartu Member' }}</span>
            </button>
          </div>

          <!-- TAHAP 2: KARTU TERVERIFIKASI — Pilih Jenis & Cetak -->
          <div v-else class="space-y-5">
            <!-- Member Info -->
            <div class="bg-gradient-to-r from-emerald-950/60 to-emerald-900/40 border border-emerald-500/30 rounded-2xl p-5">
              <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-2">
                  <div class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div>
                  <span class="text-[10px] bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 rounded-full px-2.5 py-1 font-bold uppercase tracking-wider">
                    Member Aktif
                  </span>
                </div>
                <button @click="gantiKartu" class="text-xs text-slate-400 hover:text-white transition-colors underline underline-offset-2">
                  Ganti Kartu
                </button>
              </div>
              <div class="text-xl font-bold text-white mb-1">{{ memberData.nama }}</div>
              <div class="text-sm text-slate-400">{{ memberData.instansi }}</div>
              <div class="text-xs font-mono text-emerald-400 mt-2">{{ memberData.no_member }}</div>
            </div>

            <!-- Info: plat diisi saat keluar -->
            <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-3.5 flex items-start gap-3">
              <svg class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <p class="text-blue-200 text-xs leading-relaxed">
                Plat nomor kendaraan akan dicatat oleh petugas saat Anda keluar. Cukup pilih jenis kendaraan dan cetak tiket.
              </p>
            </div>

            <!-- Pilih Jenis Kendaraan -->
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                Pilih Jenis Kendaraan
              </label>
              <div class="grid grid-cols-2 gap-3">
                <button
                  type="button"
                  @click="jenisKendaraan = 'motor'"
                  :class="jenisKendaraan === 'motor'
                    ? 'bg-gradient-to-br from-blue-600 to-blue-700 text-white border-blue-500 shadow-lg shadow-blue-500/30'
                    : 'bg-slate-700/50 text-slate-300 border-slate-600 hover:border-slate-500 hover:bg-slate-700'"
                  class="flex flex-col items-center justify-center gap-2 py-5 border-2 rounded-xl transition-all duration-300"
                >
                  <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="5.5" cy="17.5" r="3.5" stroke-width="2"/>
                    <circle cx="18.5" cy="17.5" r="3.5" stroke-width="2"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 10h4l2-4h6l2 3h2l2 2v2H9l-2-3H5"/>
                  </svg>
                  <span class="font-bold text-sm">Motor</span>
                  <span class="text-xs opacity-70">GRATIS</span>
                </button>
                <button
                  type="button"
                  @click="jenisKendaraan = 'mobil'"
                  :class="jenisKendaraan === 'mobil'
                    ? 'bg-gradient-to-br from-blue-600 to-blue-700 text-white border-blue-500 shadow-lg shadow-blue-500/30'
                    : 'bg-slate-700/50 text-slate-300 border-slate-600 hover:border-slate-500 hover:bg-slate-700'"
                  class="flex flex-col items-center justify-center gap-2 py-5 border-2 rounded-xl transition-all duration-300"
                >
                  <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l2-5h12l2 5m-2 0v7a2 2 0 01-2 2H7a2 2 0 01-2-2v-7"/>
                    <circle cx="8" cy="17" r="1.5" stroke-width="2"/>
                    <circle cx="16" cy="17" r="1.5" stroke-width="2"/>
                  </svg>
                  <span class="font-bold text-sm">Mobil</span>
                  <span class="text-xs opacity-70">GRATIS</span>
                </button>
              </div>
            </div>

            <!-- Tarif GRATIS badge -->
            <div class="flex items-center justify-between bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-3">
              <span class="text-sm text-slate-300">Tarif Parkir Member</span>
              <span class="text-emerald-400 font-black text-lg">GRATIS</span>
            </div>

            <!-- Submit Button -->
            <button
              @click="submitTiket"
              :disabled="loading"
              class="w-full bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-emerald-900/40 flex items-center justify-center gap-2 text-base hover:shadow-emerald-500/50 hover:scale-[1.02] active:scale-[0.98]"
            >
              <svg v-if="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
              </svg>
              <span>{{ loading ? 'Mencetak Tiket...' : 'Cetak Tiket Parkir Member' }}</span>
            </button>
          </div>

        </div>

        <!-- Footer -->
        <p class="text-center text-sm text-slate-400 mt-6">
          Pengunjung non-member?
          <NuxtLink to="/" class="text-blue-400 hover:text-blue-300 font-semibold transition-colors underline underline-offset-2">
            Ambil tiket biasa di sini
          </NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>
