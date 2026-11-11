<script setup>
const route = useRoute()
const config = useRuntimeConfig()

const tiket = ref(null)
const qrSvg = ref('')
const loading = ref(true)
const saving = ref(false)

const platNomor = ref('')
const fotoPlat = ref(null)
const fotoPreview = ref(null)

onMounted(async () => {
  try {
    const res = await $fetch(`/tiket/${route.params.barcode}`, {
      baseURL: config.public.apiBase,
      headers: { Accept: 'application/json' },
    })
    tiket.value = res.tiket
    qrSvg.value = res.qr_svg
    if (tiket.value.plat_nomor) {
      platNomor.value = tiket.value.plat_nomor
    }
  } catch {
    alert('Tiket tidak ditemukan atau QR tidak valid')
  } finally {
    loading.value = false
  }
})

const onFotoChange = (e) => {
  const file = e.target.files[0]
  if (!file) return
  fotoPlat.value = file
  fotoPreview.value = URL.createObjectURL(file)
}

const simpanPlat = async () => {
  const p = platNomor.value.trim()
  if (!p) return alert('Masukkan nomor plat kendaraan Anda')
  try {
    saving.value = true
    const fd = new FormData()
    fd.append('plat_nomor', p.toUpperCase())
    if (fotoPlat.value) fd.append('foto_plat', fotoPlat.value)

    const res = await $fetch(`/tiket/${route.params.barcode}/plat`, {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: fd,
    })
    tiket.value = res.tiket
    qrSvg.value = res.qr_svg
  } catch (err) {
    alert(err?.data?.message || 'Gagal menyimpan nomor plat')
  } finally {
    saving.value = false
  }
}

const cetakStruk = () => window.print()

const formatRp = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID')

const formatTgl = (d) => {
  if (!d) return '—'
  const normalized = d.includes('T') ? d : d.replace(' ', 'T')
  const dateObj = new Date(normalized)
  if (isNaN(dateObj.getTime())) return d
  
  const tgl = dateObj.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
  const jam = dateObj.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  }).replace('.', ':')

  return `${tgl} pukul ${jam}`
}

const isMotor = computed(() => tiket.value?.jenis_kendaraan === 'motor')
</script>

<template>
  <div class="min-h-screen bg-slate-950 bg-parkir bg-cover bg-center flex items-center justify-center p-4 relative overflow-hidden print:bg-white print:p-0">
    <!-- Background Overlay -->
    <div class="absolute inset-0 bg-gradient-to-br from-slate-900/90 via-slate-900/80 to-slate-950/90 print:hidden"></div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-20 relative z-10">
      <div class="w-16 h-16 mx-auto mb-4 relative">
        <div class="absolute inset-0 border-4 border-red-500/20 rounded-full"></div>
        <div class="absolute inset-0 border-4 border-red-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
      <p class="text-slate-300 font-medium text-lg">Memuat data tiket...</p>
    </div>

    <!-- TIKET VIEW -->
    <div v-else-if="tiket" class="w-full max-w-5xl relative z-10 print:max-w-none">
      
      <!-- Header -->
      <div class="bg-slate-800/80 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/10 p-6 mb-4 print:hidden">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center shadow-lg">
              <span class="text-white font-black text-2xl">P</span>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-white">E-PARKIR</h1>
              <p class="text-slate-400 text-sm">Plaza Andalas</p>
            </div>
          </div>
          <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
          </svg>
        </div>
      </div>

      <!-- Two Column Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 print:block">
        
        <!-- LEFT COLUMN - Struk Parkir -->
        <div id="struk" class="bg-slate-800/80 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/10 overflow-hidden print:shadow-none print:rounded-none print:border-none print:bg-white">
          <!-- Header Struk -->
          <div class="px-6 py-4 border-b border-white/10 print:border-gray-300">
            <div class="flex items-center gap-3">
              <svg class="w-6 h-6 text-blue-400 print:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <h2 class="text-lg font-bold text-white print:text-black">Struk Parkir</h2>
            </div>
          </div>

          <!-- Body Struk -->
          <div class="px-6 py-5 space-y-4 print:text-black">
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
                  <circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 10h4l2-4h6l2 3h2l2 2v2H9l-2-3H5"/>
                </svg>
                <svg v-else class="w-5 h-5 text-blue-400 print:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l2-5h12l2 5m-2 0v7a2 2 0 01-2 2H7a2 2 0 01-2-2v-7"/>
                </svg>
                {{ tiket.jenis_kendaraan === 'motor' ? 'Motor' : 'Mobil' }}
              </span>
            </div>

            <!-- Jenis -->
            <div class="flex justify-between items-center">
              <span class="text-sm text-slate-400 print:text-gray-600">Jenis</span>
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-700 text-slate-300 print:bg-gray-200 print:text-black">
                {{ tiket.jenis === 'member' ? 'Member' : 'Non-Member' }}
              </span>
            </div>

            <!-- Member Details -->
            <div v-if="tiket.member" class="bg-emerald-950/40 border border-emerald-500/30 rounded-xl p-3 print:bg-gray-100 print:border-gray-300">
              <p class="font-bold text-emerald-300 text-sm print:text-black">{{ tiket.member.nama }}</p>
              <p class="text-emerald-400/90 text-xs mt-0.5 print:text-gray-600">{{ tiket.member.instansi }}</p>
              <p class="text-emerald-400/90 text-xs font-mono print:text-gray-600">{{ tiket.member.no_member }}</p>
            </div>

            <!-- Plat Nomor -->
            <div class="flex justify-between items-center">
              <span class="text-sm text-slate-400 print:text-gray-600">Plat Nomor</span>
              <span class="font-bold text-white font-mono tracking-wider print:text-black">
                {{ tiket.plat_nomor || '—' }}
              </span>
            </div>

            <!-- Waktu Masuk -->
            <div class="flex justify-between items-start">
              <span class="text-sm text-slate-400 print:text-gray-600">Waktu Masuk</span>
              <span class="text-sm font-bold text-white text-right print:text-black">
                {{ formatTgl(tiket.waktu_masuk) }}
              </span>
            </div>

            <!-- Waktu Keluar -->
            <div class="flex justify-between items-start">
              <span class="text-sm text-slate-400 print:text-gray-600">Waktu Keluar</span>
              <span class="text-sm text-slate-500 italic print:text-gray-500">— (diisi saat keluar)</span>
            </div>

            <!-- Tarif -->
            <div class="flex justify-between items-center pt-3 border-t border-white/10 print:border-gray-300">
              <span class="text-sm text-slate-400 print:text-gray-600">Tarif</span>
              <span class="font-bold text-lg text-white print:text-black">
                {{ tiket.jenis === 'member' ? 'GRATIS' : formatRp(tiket.tarif_per_jam) + '/jam' }}
              </span>
            </div>

            <!-- QR Code -->
            <div class="flex flex-col items-center pt-4">
              <div class="bg-white p-3 rounded-xl shadow-lg">
                <img v-if="qrSvg" :src="`data:image/svg+xml;base64,${qrSvg}`" alt="QR Tiket" class="w-40 h-40 block" />
                <div v-else class="w-40 h-40 flex items-center justify-center text-slate-400">
                  <div class="w-8 h-8 border-4 border-slate-300 border-t-red-500 rounded-full animate-spin"></div>
                </div>
              </div>
              <p class="text-xs text-slate-400 mt-3 text-center print:text-gray-600">
                <span class="inline-flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  Tunjukkan QR ini kepada petugas saat keluar
                </span>
              </p>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN - Input Plat Nomor -->
        <div class="bg-slate-800/80 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/10 overflow-hidden print:hidden">
          <div class="px-6 py-4 border-b border-white/10">
            <div class="flex items-center gap-3">
              <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
              </svg>
              <h2 class="text-lg font-bold text-white">Input Plat Nomor</h2>
            </div>
          </div>

          <div class="px-6 py-5 space-y-5">
            <p class="text-sm text-slate-400">
              Masukkan nomor plat kendaraan Anda, lalu simpan & cetak struk.
            </p>

            <!-- Input Field -->
            <div>
              <div class="relative">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <input
                  v-model="platNomor"
                  type="text"
                  placeholder="Contoh : BA 1234 CD"
                  maxlength="15"
                  class="w-full pl-12 pr-4 py-3 bg-slate-700/50 border border-white/10 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 outline-none transition-all font-mono uppercase"
                  @input="platNomor = ($event.target.value || '').toUpperCase()"
                  autocomplete="off"
                />
              </div>
            </div>

            <!-- Divider -->
            <div class="flex items-center gap-4">
              <div class="flex-1 h-px bg-white/10"></div>
              <span class="text-xs text-slate-500 uppercase">atau</span>
              <div class="flex-1 h-px bg-white/10"></div>
            </div>

            <!-- Upload Section -->
            <div>
              <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 rounded-lg bg-slate-700 flex items-center justify-center">
                  <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-semibold text-white">Upload Foto Kendaraan</p>
                  <p class="text-xs text-slate-400">(Opsional, maks 5MB)</p>
                </div>
              </div>

              <label class="block w-full cursor-pointer">
                <div class="border-2 border-dashed border-white/20 rounded-xl p-6 text-center hover:border-blue-500/50 hover:bg-white/5 transition-all">
                  <div v-if="!fotoPreview" class="flex flex-col items-center gap-2">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-xs text-slate-400">Klik untuk mengunggah foto<br/>atau drag & drop</p>
                  </div>
                  <img v-else :src="fotoPreview" class="w-full max-h-32 object-cover rounded-lg shadow-md" alt="Preview" />
                </div>
                <input type="file" accept="image/*" class="hidden" @change="onFotoChange" />
              </label>
            </div>

            <!-- Submit Button -->
            <button
              v-if="!tiket.plat_nomor"
              @click="simpanPlat"
              :disabled="saving || !platNomor.trim()"
              class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-xl transition-all shadow-lg flex items-center justify-center gap-2"
            >
              <svg v-if="saving" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              <span>{{ saving ? 'Menyimpan...' : 'Simpan & Lanjut Cetak' }}</span>
            </button>

            <!-- Success State -->
            <div v-else class="space-y-3">
              <div class="bg-emerald-950/60 border border-emerald-500/40 rounded-xl p-4 text-center">
                <svg class="w-8 h-8 text-emerald-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-emerald-300 font-bold text-xs uppercase">Plat Nomor Tersimpan</p>
                <p class="text-white text-xl font-mono font-bold mt-1">{{ tiket.plat_nomor }}</p>
              </div>

              <button
                @click="cetakStruk"
                class="w-full bg-white hover:bg-slate-100 text-slate-900 font-bold py-4 rounded-xl transition-all flex items-center justify-center gap-2 shadow-xl"
              >
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Cetak Struk Parkir</span>
              </button>

              <NuxtLink to="/" class="block text-center text-slate-400 text-sm hover:text-white transition-colors">
                ← Kembali ke Halaman Utama
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Info -->
      <div class="mt-4 bg-slate-800/60 backdrop-blur-xl rounded-xl border border-white/10 p-4 print:hidden">
        <div class="flex items-center gap-3">
          <svg class="w-5 h-5 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-sm text-slate-400">Pastikan data yang Anda masukkan sudah benar.</p>
        </div>
      </div>

    </div>
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
    max-width: 400px;
    margin: 0 auto;
  }
  @page {
    size: auto;
    margin: 10mm;
  }
}
</style>