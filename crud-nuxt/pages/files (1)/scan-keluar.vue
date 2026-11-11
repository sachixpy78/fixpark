<script setup>
definePageMeta({ layout: 'petugas', middleware: 'auth-petugas' })

const config = useRuntimeConfig()
const router = useRouter()

const barcode = ref('')
const loading = ref(false)
const result = ref(null)
const popup = ref(null) // 'sudah_keluar' | 'sukses_member' | null
const scanInput = ref(null)
const nama = ref('')
const petugasId = ref(0)

onMounted(() => {
  nama.value = localStorage.getItem('user_name') || ''
  petugasId.value = parseInt(localStorage.getItem('user_id') || '0')
  scanInput.value?.focus()
})

const scan = async () => {
  const kode = barcode.value.trim()
  if (!kode) return
  try {
    loading.value = true
    const res = await $fetch('/parkir/scan-keluar', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: { barcode: kode },
    })
    result.value = res
    if (res.status === 'sudah_keluar') popup.value = 'sudah_keluar'
  } catch (err) {
    alert(err?.data?.message || 'QR tidak valid atau tidak ditemukan')
    barcode.value = ''
    nextTick(() => scanInput.value?.focus())
  } finally {
    loading.value = false
  }
}

const lanjutBayar = () => {
  const t = result.value.tiket
  const p = result.value.preview_keluar
  router.push({
    path: `/petugas/bayar/${t.id}`,
    query: { barcode: t.barcode, total: p.total_harga, menit: p.total_menit, invoice: t.no_invoice },
  })
}

const bayarMember = async () => {
  try {
    loading.value = true
    await $fetch('/parkir/bayar', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: { barcode: result.value.tiket.barcode, nominal_bayar: 0, petugas_id: petugasId.value },
    })
    popup.value = 'sukses_member'
  } catch (err) {
    alert(err?.data?.message || 'Gagal memproses')
  } finally {
    loading.value = false
  }
}

const reset = () => {
  result.value = null
  popup.value = null
  barcode.value = ''
  nextTick(() => scanInput.value?.focus())
}

const logout = () => {
  localStorage.clear()
  router.push('/login')
}

const formatRp = (n) => 'Rp ' + (n || 0).toLocaleString('id-ID')
const formatTgl = (d) => d ? new Date(d).toLocaleString('id-ID') : '—'
const formatDurasi = (prev) => {
  if (!prev) return '—'
  if (prev.durasi_teks) return prev.durasi_teks
  const sec = parseInt(prev.total_detik || 0) || Math.round(Number(prev.total_menit || 0) * 60)
  const h = Math.floor(sec / 3600)
  const m = Math.floor((sec % 3600) / 60)
  const s = sec % 60
  const parts = []
  if (h > 0) parts.push(`${h} jam`)
  if (m > 0 || h > 0) parts.push(`${m} menit`)
  parts.push(`${s} detik`)
  return parts.join(' ')
}
</script>

<template>
  <div>


    <!-- MAIN -->
    <div class="p-8">
      <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-black text-slate-100 mb-1">Scan Tiket Keluar</h1>
        <p class="text-slate-300 text-sm mb-7">Scan QR code pada tiket parkir menggunakan scanner USB</p>

        <!-- SCANNER INPUT BOX -->
        <div v-if="!result" class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-sm border border-white/10 p-8 text-center">
          <div class="text-6xl mb-4">📷</div>
          <h2 class="font-bold text-slate-200 mb-1">Siap Scan</h2>
          <p class="text-slate-400 text-sm mb-6">Arahkan scanner ke QR code tiket parkir</p>

          <div class="relative">
            <input
              ref="scanInput"
              v-model="barcode"
              type="text"
              placeholder="Scan QR atau ketik kode tiket..."
              class="w-full px-5 py-4 border-2 border-dashed border-white/20 rounded-2xl focus:border-white/50 outline-none text-center font-mono text-sm bg-white/5"
              @keydown.enter="scan"
              :disabled="loading"
              autofocus
            />
          </div>

          <button
            @click="scan"
            :disabled="loading || !barcode.trim()"
            class="mt-4 bg-white hover:bg-slate-200 disabled:opacity-50 text-slate-900 font-bold px-8 py-3 rounded-xl transition-all"
          >
            {{ loading ? '⏳ Memproses...' : '🔍 Proses Tiket' }}
          </button>
        </div>

        <!-- HASIL SCAN: AKTIF -->
        <div v-else-if="result.status === 'aktif'" class="space-y-4">

          <!-- Detail Tiket -->
          <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-sm border border-white/10 p-6">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h2 class="font-bold text-slate-100 text-lg">{{ result.tiket.no_invoice }}</h2>
                <span class="text-xs bg-emerald-400/20 text-emerald-300 font-semibold px-2 py-0.5 rounded-full">✅ Tiket Aktif</span>
              </div>
              <span class="text-3xl">{{ result.tiket.jenis_kendaraan === 'motor' ? '🏍️' : '🚗' }}</span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <p class="text-slate-400 text-xs uppercase tracking-wide">Kendaraan</p>
                <p class="font-semibold text-slate-100 capitalize">{{ result.tiket.jenis_kendaraan }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-xs uppercase tracking-wide">Plat Nomor</p>
                <p class="font-bold text-slate-100 font-mono">{{ result.tiket.plat_nomor || '—' }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-xs uppercase tracking-wide">Jenis</p>
                <span :class="result.tiket.jenis === 'member' ? 'bg-emerald-400/20 text-emerald-300' : 'bg-white/10 text-slate-300'"
                      class="text-xs font-semibold px-2 py-0.5 rounded-full">
                  {{ result.tiket.jenis === 'member' ? '💳 Member' : 'Non-Member' }}
                </span>
              </div>
              <div v-if="result.tiket.member">
                <p class="text-slate-400 text-xs uppercase tracking-wide">Member</p>
                <p class="font-semibold text-slate-100 text-xs">{{ result.tiket.member.nama }}</p>
              </div>
              <div class="col-span-2">
                <p class="text-slate-400 text-xs uppercase tracking-wide">Waktu Masuk</p>
                <p class="font-semibold text-slate-100">{{ formatTgl(result.tiket.waktu_masuk) }}</p>
              </div>
            </div>
          </div>

          <!-- Preview Harga -->
          <div class="border border-white/15 rounded-2xl p-6 bg-white/10 backdrop-blur-xl">
            <h3 class="font-bold text-slate-200 mb-3">📊 Kalkulasi Parkir</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-slate-300">Waktu Keluar (estimasi)</span>
                <span class="font-medium">{{ formatTgl(result.preview_keluar.waktu_keluar) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-300">Total Durasi</span>
                <span class="font-bold text-slate-100">{{ formatDurasi(result.preview_keluar) }}</span>
              </div>
              <div class="flex justify-between border-t border-white/15 pt-2 mt-2">
                <span class="font-bold text-slate-200">Total Tagihan</span>
                <span class="font-black text-xl text-rose-300">
                  {{ result.preview_keluar.is_member ? '✅ GRATIS' : formatRp(result.preview_keluar.total_harga) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Tombol Aksi -->
          <div class="flex gap-3">
            <button @click="reset" class="flex-1 py-3.5 border-2 border-white/15 text-slate-300 hover:border-white/30 font-semibold rounded-xl transition-all">
              ← Scan Lain
            </button>
            <button
              v-if="result.preview_keluar.is_member"
              @click="bayarMember"
              :disabled="loading"
              class="flex-2 flex-1 py-3.5 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-white font-bold rounded-xl transition-all"
            >
              {{ loading ? 'Memproses...' : '✅ Konfirmasi Keluar (Member)' }}
            </button>
            <button
              v-else
              @click="lanjutBayar"
              class="flex-2 flex-1 py-3.5 bg-white hover:bg-slate-200 text-slate-900 font-bold rounded-xl transition-all"
            >
              💳 Lanjut Pembayaran →
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- POPUP: Sudah Keluar -->
    <div v-if="popup === 'sudah_keluar'" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-8 max-w-sm w-full text-center shadow-2xl border-4 border-white/50">
        <div class="text-6xl mb-4">⚠️</div>
        <h2 class="text-xl font-black text-rose-300 mb-2">Kendaraan Sudah Keluar!</h2>
        <p class="text-slate-300 text-sm mb-4">Tiket ini sudah digunakan sebelumnya.</p>
        <div v-if="result?.tiket" class="bg-white/5 rounded-xl p-4 text-left text-sm mb-5">
          <p><span class="text-slate-400">Invoice:</span> <span class="font-mono font-bold">{{ result.tiket.no_invoice }}</span></p>
          <p><span class="text-slate-400">Kendaraan:</span> {{ result.tiket.jenis_kendaraan }}</p>
          <p><span class="text-slate-400">Plat:</span> <span class="font-bold">{{ result.tiket.plat_nomor || '—' }}</span></p>
          <p><span class="text-slate-400">Keluar:</span> {{ formatTgl(result.tiket.waktu_keluar) }}</p>
        </div>
        <button @click="reset" class="w-full bg-white hover:bg-slate-200 text-slate-900 font-bold py-3 rounded-xl transition-all">
          Tutup & Scan Lagi
        </button>
      </div>
    </div>

    <!-- POPUP: Sukses Member -->
    <div v-if="popup === 'sukses_member'" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-8 max-w-sm w-full text-center shadow-2xl border-4 border-emerald-400/60">
        <div class="text-6xl mb-4">🎉</div>
        <h2 class="text-2xl font-black text-emerald-300 mb-2">Berhasil Keluar!</h2>
        <p class="text-slate-300 text-sm mb-2">Kendaraan telah berhasil keluar dari area parkir.</p>
        <div class="bg-emerald-400/10 rounded-xl p-3 mb-5">
          <p class="text-emerald-300 font-semibold text-sm">💳 Member — Pembayaran Gratis</p>
        </div>
        <button @click="reset" class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 rounded-xl transition-all">
          ✅ Selesai
        </button>
      </div>
    </div>

  </div>
</template>

