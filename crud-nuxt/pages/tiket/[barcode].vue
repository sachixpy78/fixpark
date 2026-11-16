<script setup>
definePageMeta({ layout: false })

const route = useRoute()
const config = useRuntimeConfig()

const tiket   = ref(null)
const qrSvg   = ref('')
const loading = ref(true)
const printed = ref(false)

onMounted(async () => {
  try {
    const res = await $fetch(`/tiket/${route.params.barcode}`, {
      baseURL: config.public.apiBase,
      headers: { Accept: 'application/json' },
    })
    tiket.value = res.tiket
    qrSvg.value = res.qr_svg
  } catch {
    alert('Tiket tidak ditemukan atau QR tidak valid')
  } finally {
    loading.value = false
  }
})

const showMasukPopup = ref(false)

const cetakStruk = () => {
  printed.value = true
  window.print()
  // Tampilkan popup "Silakan Masuk" setelah cetak
  setTimeout(() => { showMasukPopup.value = true }, 800)
}

const formatTgl = (d) => {
  if (!d) return '—'
  const normalized = d.includes('T') ? d : d.replace(' ', 'T')
  const dateObj = new Date(normalized)
  if (isNaN(dateObj.getTime())) return d
  const tgl = dateObj.toLocaleDateString('id-ID', {
    day: '2-digit', month: 'long', year: 'numeric',
  })
  const jam = dateObj.toLocaleTimeString('id-ID', {
    hour: '2-digit', minute: '2-digit', hour12: false,
  }).replace('.', ':')
  return `${tgl} pukul ${jam}`
}

const isMotor = computed(() => tiket.value?.jenis_kendaraan === 'motor')
const isMember = computed(() => tiket.value?.jenis === 'member')
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex items-center justify-center p-4 relative overflow-hidden print:bg-white print:p-0">
    <!-- Background Overlay -->
    <div class="absolute inset-0 bg-gradient-to-br from-slate-900/90 via-slate-900/80 to-slate-950/90 print:hidden"></div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-20 relative z-10">
      <div class="w-16 h-16 mx-auto mb-4 relative">
        <div class="absolute inset-0 border-4 border-blue-500/20 rounded-full"></div>
        <div class="absolute inset-0 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
      <p class="text-slate-300 font-medium text-lg">Memuat data tiket...</p>
    </div>

    <!-- TIKET VIEW -->
    <div v-else-if="tiket" class="w-full max-w-md relative z-10 print:max-w-none">

      <!-- Header (screen only) -->
      <div class="bg-slate-800/80 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/10 p-5 mb-4 print:hidden">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center shadow-lg">
              <span class="text-white font-black text-xl">P</span>
            </div>
            <div>
              <h1 class="text-lg font-bold text-white">E-PARKIR</h1>
              <p class="text-slate-400 text-xs">Plaza Andalas</p>
            </div>
          </div>
          <!-- Back arrow -->
          <NuxtLink to="/" class="text-slate-400 hover:text-white transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
          </NuxtLink>
        </div>
      </div>

      <!-- Struk Card -->
      <div id="struk" class="bg-slate-800/80 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/10 overflow-hidden print:shadow-none print:rounded-none print:border-none print:bg-white">

        <!-- Print Header (print only) -->
        <div class="hidden print:flex print:items-center print:justify-center print:py-4 print:border-b print:border-gray-200">
          <p class="text-black font-black text-xl">E-PARKIR — Plaza Andalas</p>
        </div>

        <!-- Header Struk -->
        <div class="px-6 py-4 border-b border-white/10 print:border-gray-200">
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-400 print:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h2 class="text-base font-bold text-white print:text-black">Struk Parkir</h2>
          </div>
        </div>

        <!-- Body Struk -->
        <div class="px-6 py-5 space-y-3.5 print:text-black">

          <!-- No Invoice -->
          <div class="flex justify-between items-center">
            <span class="text-sm text-slate-400 print:text-gray-600">No. Invoice</span>
            <span class="font-bold text-white font-mono print:text-black">{{ tiket.no_invoice }}</span>
          </div>

          <!-- Kendaraan -->
          <div class="flex justify-between items-center">
            <span class="text-sm text-slate-400 print:text-gray-600">Kendaraan</span>
            <span class="font-bold text-white flex items-center gap-2 print:text-black">
              <svg v-if="isMotor" class="w-5 h-5 text-blue-400 print:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="5.5" cy="17.5" r="3.5" stroke-width="2"/><circle cx="18.5" cy="17.5" r="3.5" stroke-width="2"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 10h4l2-4h6l2 3h2l2 2v2H9l-2-3H5"/>
              </svg>
              <svg v-else class="w-5 h-5 text-blue-400 print:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l2-5h12l2 5m-2 0v7a2 2 0 01-2 2H7a2 2 0 01-2-2v-7"/>
                <circle cx="8" cy="17" r="1.5" stroke-width="2"/><circle cx="16" cy="17" r="1.5" stroke-width="2"/>
              </svg>
              {{ isMotor ? 'Motor' : 'Mobil' }}
            </span>
          </div>

          <!-- Jenis -->
          <div class="flex justify-between items-center">
            <span class="text-sm text-slate-400 print:text-gray-600">Jenis</span>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 print:bg-gray-200 print:text-black">
              {{ isMember ? 'Member' : 'Non-Member' }}
            </span>
          </div>

          <!-- Member Details -->
          <div v-if="tiket.member" class="bg-emerald-950/40 border border-emerald-500/30 rounded-xl p-3 print:bg-gray-100 print:border-gray-300">
            <p class="font-bold text-emerald-300 text-sm print:text-black">{{ tiket.member.nama }}</p>
            <p class="text-emerald-400/90 text-xs mt-0.5 print:text-gray-600">{{ tiket.member.instansi }}</p>
            <p class="text-emerald-400/90 text-xs font-mono mt-0.5 print:text-gray-600">{{ tiket.member.no_member }}</p>
          </div>

          <!-- Plat Nomor (akan diisi saat keluar) -->
          <div class="flex justify-between items-center">
            <span class="text-sm text-slate-400 print:text-gray-600">Plat Nomor</span>
            <span class="text-sm text-slate-500 italic print:text-gray-500">— (diisi saat keluar)</span>
          </div>

          <!-- Waktu Masuk -->
          <div class="flex justify-between items-start">
            <span class="text-sm text-slate-400 print:text-gray-600">Waktu Masuk</span>
            <span class="text-sm font-bold text-white text-right print:text-black">{{ formatTgl(tiket.waktu_masuk) }}</span>
          </div>

          <!-- Waktu Keluar -->
          <div class="flex justify-between items-start">
            <span class="text-sm text-slate-400 print:text-gray-600">Waktu Keluar</span>
            <span class="text-sm text-slate-500 italic print:text-gray-500">— (diisi saat keluar)</span>
          </div>

          <!-- Tarif -->
          <div class="flex justify-between items-center pt-3 border-t border-white/10 print:border-gray-300">
            <span class="text-sm text-slate-400 print:text-gray-600">Tarif</span>
            <span class="font-bold text-lg print:text-black" :class="isMember ? 'text-emerald-400' : 'text-white'">
              {{ isMember ? 'GRATIS' : (tiket.tarif_per_jam ? 'Rp ' + Number(tiket.tarif_per_jam).toLocaleString('id-ID') + '/jam' : '—') }}
            </span>
          </div>

          <!-- QR Code -->
          <div class="flex flex-col items-center pt-4">
            <div class="bg-white p-3 rounded-xl shadow-lg">
              <img v-if="qrSvg" :src="`data:image/svg+xml;base64,${qrSvg}`" alt="QR Tiket" class="w-44 h-44 block" />
              <div v-else class="w-44 h-44 flex items-center justify-center">
                <div class="w-8 h-8 border-4 border-slate-300 border-t-blue-500 rounded-full animate-spin"></div>
              </div>
            </div>
            <p class="text-xs text-slate-400 mt-3 text-center print:text-gray-600">
              Tunjukkan QR ini kepada petugas saat keluar
            </p>
          </div>

          <!-- Info: plat diisi petugas -->
          <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-3 flex items-start gap-2.5 print:hidden">
            <svg class="w-4 h-4 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-blue-200 text-xs leading-relaxed">
              Plat nomor kendaraan dan waktu keluar akan dicatat oleh petugas saat Anda keluar parkir.
            </p>
          </div>
        </div>

        <!-- Action Buttons (screen only) -->
        <div class="px-6 pb-6 space-y-3 print:hidden">
          <!-- Cetak -->
          <button
            @click="cetakStruk"
            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-blue-900/40 flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-[0.98]"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            <span>Cetak Tiket</span>
          </button>

          <!-- Kembali -->
          <NuxtLink
            to="/"
            class="block text-center text-sm text-slate-400 hover:text-white transition-colors py-2"
          >
            ← Kembali ke Halaman Utama
          </NuxtLink>
        </div>
      </div>
    </div>
    <!-- POPUP SILAKAN MASUK -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0 scale-90"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-90"
    >
      <div v-if="showMasukPopup" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-50 p-4 print:hidden">
        <div class="bg-slate-800 border border-white/10 rounded-3xl shadow-2xl max-w-sm w-full text-center px-8 py-10 relative overflow-hidden">

          <!-- Lingkaran dekorasi latar -->
          <div class="absolute -top-10 -right-10 w-40 h-40 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
          <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-blue-500/10 rounded-full blur-2xl pointer-events-none"></div>

          <!-- Ikon palang terbuka / ceklis -->
          <div class="w-20 h-20 mx-auto mb-5 rounded-full bg-emerald-500/15 border-2 border-emerald-500/40 flex items-center justify-center">
            <svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>

          <!-- Judul -->
          <h2 class="text-2xl font-black text-white mb-2">Silakan Masuk!</h2>
          <p class="text-slate-400 text-sm leading-relaxed mb-2">
            Tiket parkir Anda telah berhasil dicetak.
          </p>
          <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-3 mb-6">
            <p class="text-emerald-300 text-sm font-semibold">
              Simpan tiket ini dan tunjukkan QR kepada petugas saat keluar.
            </p>
          </div>

          <!-- Info nomor invoice -->
          <div class="bg-slate-700/60 rounded-xl px-4 py-3 mb-6 font-mono text-sm text-slate-300 text-left space-y-1.5">
            <div class="flex justify-between">
              <span class="text-slate-500">No. Tiket</span>
              <span class="font-bold text-white">{{ tiket?.no_invoice }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Kendaraan</span>
              <span class="font-bold text-white capitalize">{{ tiket?.jenis_kendaraan }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Jenis</span>
              <span :class="isMember ? 'text-emerald-400' : 'text-blue-400'" class="font-bold">
                {{ isMember ? 'Member (Gratis)' : 'Non-Member' }}
              </span>
            </div>
          </div>

          <!-- Tombol tutup -->
          <button
            @click="showMasukPopup = false"
            class="w-full py-3.5 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-emerald-900/40 flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
            Mengerti, Silakan Masuk
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style>
@media print {
  body * { visibility: hidden; }
  #struk, #struk * { visibility: visible; }
  #struk {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    max-width: 380px;
    margin: 0 auto;
  }
  @page {
    size: auto;
    margin: 10mm;
  }
}
</style>
