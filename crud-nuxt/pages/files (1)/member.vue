<script setup>
import { ref, onMounted, nextTick } from 'vue'

const config = useRuntimeConfig()
const router = useRouter()

const noMember       = ref('')
const jenisKendaraan = ref('motor')
const selectedPlat   = ref('')
const memberData     = ref(null) // Data dari /api/member/check
const loading        = ref(false)
const checking       = ref(false)
const inputRef       = ref(null)

onMounted(() => inputRef.value?.focus())

// Cek data kartu member (bisa dari enter scanner atau klik tombol)
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
    // Default pilih plat pertama jika ada
    if (res.daftar_plat && res.daftar_plat.length > 0) {
      selectedPlat.value = res.daftar_plat[0]
    }
  } catch (err) {
    alert(err?.data?.message || 'Kartu member tidak valid, expired, atau tidak aktif!')
    memberData.value = null
    noMember.value = ''
    nextTick(() => inputRef.value?.focus())
  } finally {
    checking.value = false
  }
}

// Buat tiket parkir member dengan plat yang sudah dipilih
const submitTiket = async () => {
  if (!noMember.value.trim()) return
  if (!selectedPlat.value) {
    alert('Silakan pilih salah satu plat nomor kendaraan yang terdaftar!')
    return
  }

  try {
    loading.value = true
    const res = await $fetch('/tiket/member', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: {
        no_member: noMember.value.trim(),
        jenis_kendaraan: jenisKendaraan.value,
        plat_nomor: selectedPlat.value,
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
  selectedPlat.value = ''
  noMember.value = ''
  nextTick(() => inputRef.value?.focus())
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden font-sans bg-parkir">

    <!-- Back Button -->
    <NuxtLink to="/" class="absolute top-5 left-5 text-white/80 hover:text-white flex items-center gap-1.5 text-sm font-semibold transition-colors z-20">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
      </svg>
      Kembali ke Kiosk Utama
    </NuxtLink>

    <div class="bg-white/10 backdrop-blur-xl rounded-3xl shadow-2xl w-full max-w-md p-8 relative z-10 border border-white/10">

      <!-- Header -->
      <div class="text-center mb-6">
        <div class="w-14 h-14 bg-rose-400/15 rounded-2xl flex items-center justify-center mx-auto mb-3">
          <svg viewBox="0 0 44 44" class="w-9 h-9" fill="none">
            <rect width="44" height="44" rx="10" fill="#dc2626"/>
            <text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle"
                  fill="white" font-size="24" font-weight="900" font-family="Arial,sans-serif">P</text>
          </svg>
        </div>
        <h1 class="text-2xl font-black text-slate-100">Tiket Parkir Member</h1>
        <p class="text-slate-300 text-xs mt-0.5">Scan kartu member Anda dan pilih plat kendaraan</p>
      </div>

      <!-- TAHAP 1: SCAN KARTU MEMBER JIKA BELUM ADA DATA -->
      <div v-if="!memberData" class="space-y-4">
        <!-- Instruksi scanner -->
        <div class="bg-white/5 border border-white/15 rounded-2xl p-4 text-center">
          <div class="flex items-center justify-center gap-2 text-rose-300 text-sm font-bold mb-0.5">
            <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6.364 1.636l-.707.707M20 12h-1M17.657 17.657l-.707-.707M12 19v1M6.343 17.657l-.707.707M5 12H4M6.343 6.343l.707.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
            </svg>
            Arahkan Scanner ke QR Kartu Member
          </div>
          <p class="text-rose-300 text-xs">Gunakan barcode scanner USB atau ketikkan nomor member di bawah.</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-200 mb-1.5">Nomor Kartu Member</label>
          <input
            ref="inputRef"
            v-model="noMember"
            type="text"
            placeholder="MBR-2026xxxx-xxx"
            class="w-full px-4 py-3.5 border-2 border-white/15 rounded-xl focus:border-white/50 outline-none font-mono text-center tracking-wider text-slate-100 text-sm uppercase"
            @keydown.enter="cekKartu"
            :disabled="checking"
          />
        </div>

        <button
          @click="cekKartu"
          :disabled="checking || !noMember.trim()"
          class="w-full bg-white hover:bg-slate-200 disabled:opacity-50 text-slate-900 font-bold py-3.5 rounded-xl transition shadow-md flex items-center justify-center gap-2 text-sm"
        >
          <svg v-if="checking" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span>{{ checking ? 'Memverifikasi Kartu...' : 'Verifikasi Kartu Member →' }}</span>
        </button>
      </div>

      <!-- TAHAP 2: KARTU TERVERIFIKASI -> PILIH JENIS KENDARAAN & PILIH SALAH SATU PLAT -->
      <div v-else class="space-y-5">

        <!-- Info Member Terverifikasi -->
        <div class="text-white rounded-2xl p-4 shadow-sm border border-white/20 bg-white/10 backdrop-blur-xl border-white/15">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] bg-white/20 border border-white/30 rounded px-2 py-0.5 font-bold uppercase tracking-wider">
              MEMBER AKTIF
            </span>
            <button @click="gantiKartu" class="text-xs text-slate-300 hover:text-white underline">
              Ganti Kartu
            </button>
          </div>
          <div class="text-lg font-bold">{{ memberData.nama }}</div>
          <div class="text-xs text-slate-300">{{ memberData.instansi }} &bull; <span class="font-mono text-yellow-300">{{ memberData.no_member }}</span></div>
        </div>

        <!-- Pilih Jenis Kendaraan -->
        <div>
          <label class="block text-xs font-bold text-slate-200 mb-2 uppercase tracking-wide">
            1. Pilih Jenis Kendaraan
          </label>
          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              @click="jenisKendaraan = 'motor'"
              :class="jenisKendaraan === 'motor'
                ? 'bg-white text-slate-900 border-white ring-2 ring-white/30 font-bold'
                : 'bg-white/10 backdrop-blur-xl text-slate-200 border-white/15 hover:border-white/40 font-medium'"
              class="flex items-center justify-center gap-2 py-3 border-2 rounded-xl transition text-sm"
            >
              <span>🏍️</span> Motor
            </button>
            <button
              type="button"
              @click="jenisKendaraan = 'mobil'"
              :class="jenisKendaraan === 'mobil'
                ? 'bg-white text-slate-900 border-white ring-2 ring-white/30 font-bold'
                : 'bg-white/10 backdrop-blur-xl text-slate-200 border-white/15 hover:border-white/40 font-medium'"
              class="flex items-center justify-center gap-2 py-3 border-2 rounded-xl transition text-sm"
            >
              <span>🚗</span> Mobil
            </button>
          </div>
        </div>

        <!-- PILIH PLAT NOMOR DARI 3 SLOT TERDAFTAR -->
        <div>
          <label class="block text-xs font-bold text-slate-200 mb-1.5 uppercase tracking-wide">
            2. Pilih Plat Nomor Yang Sedang Digunakan
          </label>
          <p class="text-[11px] text-slate-300 mb-2.5">
            Pilih plat kendaraan Anda yang terdaftar pada kartu member:
          </p>

          <div class="space-y-2">
            <button
              v-for="(plat, idx) in memberData.daftar_plat"
              :key="plat"
              type="button"
              @click="selectedPlat = plat"
              :class="selectedPlat === plat
                ? 'bg-white/5 border-white text-rose-200 ring-2 ring-white/30 font-bold shadow-xs'
                : 'bg-white/10 backdrop-blur-xl border-white/15 text-slate-200 hover:border-white/40 font-medium'"
              class="w-full px-4 py-3 border-2 rounded-xl flex items-center justify-between text-left transition"
            >
              <div class="flex items-center gap-2.5">
                <span class="text-xs font-mono font-bold w-5 h-5 rounded-full bg-rose-400/15 text-rose-300 flex items-center justify-center text-[10px]">
                  {{ idx + 1 }}
                </span>
                <span class="font-mono text-base tracking-wider font-bold">{{ plat }}</span>
              </div>
              <span v-if="selectedPlat === plat" class="text-xs bg-white text-slate-900 font-bold px-2 py-0.5 rounded-md">
                ✓ Digunakan
              </span>
              <span v-else class="text-xs text-slate-400">Pilih</span>
            </button>
          </div>
        </div>

        <!-- Tombol Submit Ambil Tiket -->
        <button
          @click="submitTiket"
          :disabled="loading || !selectedPlat"
          class="w-full bg-white hover:bg-slate-200 disabled:opacity-50 text-slate-900 font-bold py-4 rounded-xl transition shadow-lg flex items-center justify-center gap-2 text-base"
        >
          <svg v-if="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span>{{ loading ? 'Mencetak Tiket...' : '🖨️ Cetak Tiket Parkir Member' }}</span>
        </button>

      </div>

      <p class="text-center text-xs text-slate-400 mt-6">
        Pengunjung non-member?
        <NuxtLink to="/" class="text-sky-300 hover:underline font-semibold">Ambil tiket biasa di sini</NuxtLink>
      </p>

    </div>
  </div>
</template>
