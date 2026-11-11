<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'

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

// Plat tersedia disaring berdasarkan jenis kendaraan yang dipilih
const platTersedia = computed(() => {
  if (!memberData.value) return []
  if (jenisKendaraan.value === 'motor') {
    const list = memberData.value.daftar_plat_motor || []
    if (list.length > 0) return list
    // Fallback jika data lawas
    return memberData.value.daftar_plat || []
  } else {
    return memberData.value.daftar_plat_mobil || []
  }
})

// Auto select plat saat jenis kendaraan diubah
watch(jenisKendaraan, () => {
  if (platTersedia.value && platTersedia.value.length > 0) {
    selectedPlat.value = platTersedia.value[0]
  } else {
    selectedPlat.value = ''
  }
})

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
    // Default pilih plat pertama sesuai jenis kendaraan yang aktif
    if (platTersedia.value && platTersedia.value.length > 0) {
      selectedPlat.value = platTersedia.value[0]
    } else {
      selectedPlat.value = ''
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
  <div class="min-h-screen bg-slate-950 bg-parkir bg-cover bg-center relative overflow-hidden">
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
          <NuxtLink to="/" class="flex items-center gap-2 text-slate-400 hover:text-white text-sm font-medium transition-colors group">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Kembali ke Kiosk Utama
          </NuxtLink>

          <!-- Car Icon -->
          <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
          </svg>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 max-w-7xl mx-auto px-6 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- LEFT COLUMN - Branding & Info -->
        <div class="bg-slate-800/60 backdrop-blur-xl rounded-3xl border border-white/10 overflow-hidden relative">
          <!-- Background Image -->
          <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1506521781263-d8422e82f27a?w=800&h=600&fit=crop')] bg-cover bg-center opacity-40"></div>
          <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/80 to-transparent"></div>
          
          <div class="relative z-10 p-8 h-full flex flex-col justify-between min-h-[500px]">
            <div>
              <h2 class="text-3xl font-bold text-white mb-2">Tiket Parkir Member</h2>
              <p class="text-slate-300 text-sm mb-6">Scan kartu member Anda dan pilih plat kendaraan</p>
              
              <!-- Info Box -->
              <div class="bg-slate-900/60 backdrop-blur-sm border border-white/10 rounded-2xl p-4 mb-8">
                <div class="flex items-start gap-3">
                  <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                  </div>
                  <p class="text-slate-300 text-sm">Gunakan barcode scanner USB atau ketik nomor member di bawah.</p>
                </div>
              </div>
            </div>

            <!-- Features -->
            <div class="grid grid-cols-3 gap-4">
              <div class="text-center">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center mx-auto mb-2">
                  <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                  </svg>
                </div>
                <p class="text-white text-xs font-semibold">Aman & Terpercaya</p>
              </div>
              <div class="text-center">
                <div class="w-12 h-12 rounded-xl bg-yellow-500/20 flex items-center justify-center mx-auto mb-2">
                  <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                  </svg>
                </div>
                <p class="text-white text-xs font-semibold">Proses Cepat</p>
              </div>
              <div class="text-center">
                <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center mx-auto mb-2">
                  <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                  </svg>
                </div>
                <p class="text-white text-xs font-semibold">Dukungan 24/7</p>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN - Form -->
        <div class="bg-slate-800/80 backdrop-blur-xl rounded-3xl border border-white/10 p-8">
          
          <!-- TAHAP 1: SCAN KARTU MEMBER -->
          <div v-if="!memberData" class="space-y-6">
            <!-- Icon & Title -->
            <div class="text-center mb-6">
              <div class="w-16 h-16 rounded-full bg-slate-700/50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
              </div>
              <h3 class="text-xl font-bold text-white mb-1">Arahkan Scanner ke QR Kartu Member</h3>
              <p class="text-slate-400 text-sm">Gunakan barcode scanner USB atau ketik nomor member di bawah.</p>
            </div>

            <!-- Input Field -->
            <div>
              <label class="flex items-center gap-2 text-sm font-medium text-slate-300 mb-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Nomor Kartu Member
              </label>
              <div class="relative">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <input
                  ref="inputRef"
                  v-model="noMember"
                  type="text"
                  placeholder="Masukkan nomor member..."
                  class="w-full pl-12 pr-4 py-4 bg-slate-700/50 border-2 border-slate-600 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 outline-none text-white placeholder:text-slate-500 transition-all"
                  @keydown.enter="cekKartu"
                  :disabled="checking"
                />
              </div>
            </div>

            <!-- Submit Button -->
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
              <span>{{ checking ? 'Memverifikasi Kartu...' : 'Verifikasi Kartu Member' }}</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
              </svg>
            </button>

            <!-- Divider -->
            <div class="flex items-center gap-4">
              <div class="flex-1 h-px bg-white/10"></div>
              <span class="text-xs text-slate-500 uppercase">atau</span>
              <div class="flex-1 h-px bg-white/10"></div>
            </div>

            <!-- Upload Section -->
            <div class="border-2 border-dashed border-slate-600 rounded-2xl p-6 text-center hover:border-blue-500/50 hover:bg-white/5 transition-all cursor-pointer">
              <svg class="w-8 h-8 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              <p class="text-white text-sm font-semibold">Upload Foto Kendaraan</p>
              <p class="text-slate-400 text-xs mt-1">(Opsional, maks 5MB)</p>
            </div>
          </div>

          <!-- TAHAP 2: KARTU TERVERIFIKASI -->
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

            <!-- Pilih Jenis Kendaraan -->
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                1. Pilih Jenis Kendaraan
              </label>
              <div class="grid grid-cols-2 gap-3">
                <button
                  type="button"
                  @click="jenisKendaraan = 'motor'"
                  :class="jenisKendaraan === 'motor'
                    ? 'bg-gradient-to-br from-blue-600 to-blue-700 text-white border-blue-500 shadow-lg shadow-blue-500/30'
                    : 'bg-slate-700/50 text-slate-300 border-slate-600 hover:border-slate-500 hover:bg-slate-700'"
                  class="flex flex-col items-center justify-center gap-2 py-4 border-2 rounded-xl transition-all duration-300"
                >
                  <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="5.5" cy="17.5" r="3.5" stroke-width="2"/>
                    <circle cx="18.5" cy="17.5" r="3.5" stroke-width="2"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 10h4l2-4h6l2 3h2l2 2v2H9l-2-3H5"/>
                  </svg>
                  <span class="font-bold text-sm">Motor</span>
                </button>
                <button
                  type="button"
                  @click="jenisKendaraan = 'mobil'"
                  :class="jenisKendaraan === 'mobil'
                    ? 'bg-gradient-to-br from-blue-600 to-blue-700 text-white border-blue-500 shadow-lg shadow-blue-500/30'
                    : 'bg-slate-700/50 text-slate-300 border-slate-600 hover:border-slate-500 hover:bg-slate-700'"
                  class="flex flex-col items-center justify-center gap-2 py-4 border-2 rounded-xl transition-all duration-300"
                >
                  <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l2-5h12l2 5m-2 0v7a2 2 0 01-2 2H7a2 2 0 01-2-2v-7"/>
                  </svg>
                  <span class="font-bold text-sm">Mobil</span>
                </button>
              </div>
            </div>

            <!-- Pilih Plat Nomor -->
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                2. Pilih Plat Nomor Yang Sedang Digunakan ({{ jenisKendaraan.toUpperCase() }})
              </label>
              <p class="text-xs text-slate-500 mb-3">
                Pilih plat kendaraan Anda yang terdaftar khusus untuk {{ jenisKendaraan }}:
              </p>

              <div v-if="platTersedia.length > 0" class="space-y-2.5">
                <button
                  v-for="(plat, idx) in platTersedia"
                  :key="plat"
                  type="button"
                  @click="selectedPlat = plat"
                  :class="selectedPlat === plat
                    ? 'bg-gradient-to-r from-blue-600/30 to-blue-700/30 border-blue-500 shadow-lg shadow-blue-500/20'
                    : 'bg-slate-700/50 border-slate-600 hover:border-slate-500 hover:bg-slate-700'"
                  class="w-full px-5 py-4 border-2 rounded-xl flex items-center justify-between transition-all duration-300 group"
                >
                  <div class="flex items-center gap-3">
                    <span 
                      :class="selectedPlat === plat ? 'bg-blue-500 text-white' : 'bg-slate-600 text-slate-300'"
                      class="text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center"
                    >
                      {{ idx + 1 }}
                    </span>
                    <span class="font-mono text-lg font-bold text-white tracking-wider">{{ plat }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <svg 
                      v-if="selectedPlat === plat" 
                      class="w-5 h-5 text-blue-400" 
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span 
                      :class="selectedPlat === plat ? 'text-blue-400 font-semibold' : 'text-slate-500'"
                      class="text-sm"
                    >
                      {{ selectedPlat === plat ? 'Digunakan' : 'Pilih' }}
                    </span>
                  </div>
                </button>
              </div>

              <!-- Kosong untuk jenis kendaraan ini -->
              <div v-else class="p-4 bg-amber-500/10 border border-amber-500/30 rounded-xl text-amber-300 text-xs flex items-start gap-2.5">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                  <div class="font-bold">Tidak ada plat {{ jenisKendaraan }} terdaftar!</div>
                  <div class="text-slate-400 mt-0.5">
                    Kartu member ini tidak memiliki plat untuk {{ jenisKendaraan }}. Silakan ganti jenis kendaraan ke 
                    <strong>{{ jenisKendaraan === 'motor' ? 'Mobil' : 'Motor' }}</strong> atau daftarkan plat di loket petugas.
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <button
              @click="submitTiket"
              :disabled="loading || !selectedPlat"
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
</template>