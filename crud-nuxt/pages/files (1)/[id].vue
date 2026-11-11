<script setup>
definePageMeta({ layout: 'petugas', middleware: 'auth-petugas' })

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()

const totalHarga = computed(() => parseInt(route.query.total || 0))
const barcode = route.query.barcode
const noInvoice = route.query.invoice
const totalMenit = parseInt(route.query.menit || 0)
const petugasId = ref(0)

onMounted(() => {
  petugasId.value = parseInt(localStorage.getItem('user_id') || '0')
})

const nominal = ref('')
const nominalNum = computed(() => parseInt(nominal.value.replace(/\D/g, '')) || 0)
const kembalian = computed(() => Math.max(0, nominalNum.value - totalHarga.value))
const kurang = computed(() => nominalNum.value < totalHarga.value)

const loading = ref(false)
const sukses = ref(false)
const transaksiResult = ref(null)

const formatNominal = (e) => {
  const raw = e.target.value.replace(/\D/g, '')
  nominal.value = raw ? parseInt(raw).toLocaleString('id-ID') : ''
}

const bayar = async () => {
  if (kurang.value) return
  try {
    loading.value = true
    const res = await $fetch('/parkir/bayar', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: { barcode, nominal_bayar: nominalNum.value, petugas_id: petugasId.value },
    })
    transaksiResult.value = res
    sukses.value = true
  } catch (err) {
    alert(err?.data?.message || 'Pembayaran gagal')
  } finally {
    loading.value = false
  }
}

const cetakStruk = () => window.print()
const selesai = () => router.push('/petugas/scan-keluar')

const formatRp = (n) => 'Rp ' + (n || 0).toLocaleString('id-ID')
const formatMenit = (m) => {
  if (!m) return '—'
  const totalMin = Math.round(Number(m))
  const h = Math.floor(totalMin / 60); const min = totalMin % 60
  return h > 0 ? `${h} jam ${min} menit` : `${min} menit`
}
</script>

<template>
  <div>


    <!-- MAIN -->
    <div class="p-8 print:p-4">
      <div class="max-w-lg mx-auto">
        <button @click="router.back()" class="text-slate-400 hover:text-slate-100 text-sm mb-5 flex items-center gap-1 print:hidden">
          ← Kembali
        </button>

        <!-- STRUK (tampil saat print) -->
        <div id="struk-bayar" class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-sm border border-white/10 overflow-hidden mb-5">
          <div class="text-white px-6 py-5 text-center bg-white/10 backdrop-blur-xl border border-white/15">
            <div class="text-2xl mb-1">🅿️</div>
            <h1 class="text-lg font-black">E-PARKIR — STRUK PEMBAYARAN</h1>
            <p class="text-slate-300 text-xs">Plaza Andalas</p>
          </div>

          <div class="px-6 py-5 space-y-3">
            <div class="flex justify-between border-b border-dashed border-white/15 pb-3">
              <span class="text-xs text-slate-400 uppercase">No. Invoice</span>
              <span class="font-mono font-bold text-sm">{{ noInvoice }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-xs text-slate-400 uppercase">Durasi Parkir</span>
              <span class="font-semibold text-slate-200">{{ formatMenit(totalMenit) }}</span>
            </div>
            <div class="flex justify-between border-t border-dashed border-white/15 pt-3">
              <span class="font-bold text-slate-200">Total Tagihan</span>
              <span class="font-black text-rose-300 text-lg">{{ formatRp(totalHarga) }}</span>
            </div>
            <div v-if="sukses" class="flex justify-between">
              <span class="text-xs text-slate-400 uppercase">Dibayar</span>
              <span class="font-semibold">{{ formatRp(transaksiResult?.transaksi?.nominal_bayar) }}</span>
            </div>
            <div v-if="sukses" class="flex justify-between">
              <span class="text-xs text-slate-400 uppercase">Kembalian</span>
              <span class="font-black text-emerald-300 text-lg">{{ formatRp(transaksiResult?.transaksi?.kembalian) }}</span>
            </div>
          </div>
        </div>

        <!-- INPUT PEMBAYARAN -->
        <div v-if="!sukses" class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-sm border border-white/10 p-6 print:hidden">
          <h2 class="font-bold text-slate-100 mb-4">💵 Input Pembayaran</h2>

          <div class="bg-white/5 border border-white/15 rounded-xl p-4 mb-5 text-center">
            <p class="text-xs text-rose-300 uppercase tracking-wide mb-1">Total yang Harus Dibayar</p>
            <p class="text-3xl font-black text-rose-300">{{ formatRp(totalHarga) }}</p>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-semibold text-slate-200 mb-2">Nominal Diterima (Rp)</label>
            <input
              :value="nominal"
              @input="formatNominal"
              type="text"
              inputmode="numeric"
              placeholder="Masukkan nominal uang..."
              class="w-full px-4 py-4 border-2 rounded-xl focus:outline-none text-right text-xl font-bold font-mono transition-colors"
              :class="nominalNum > 0 && !kurang ? 'border-emerald-400/60 bg-emerald-400/10' : nominalNum > 0 ? 'border-white/40 bg-white/5' : 'border-white/15'"
            />
          </div>

          <div v-if="nominalNum > 0" class="mb-5 p-4 rounded-xl" :class="kurang ? 'bg-white/5 border border-white/15' : 'bg-emerald-400/10 border border-emerald-300/25'">
            <div v-if="kurang" class="text-center">
              <p class="text-rose-300 font-bold">⚠️ Kurang {{ formatRp(totalHarga - nominalNum) }}</p>
            </div>
            <div v-else class="flex justify-between items-center">
              <span class="text-emerald-300 font-semibold">Kembalian:</span>
              <span class="text-2xl font-black text-emerald-300">{{ formatRp(kembalian) }}</span>
            </div>
          </div>

          <button
            @click="bayar"
            :disabled="loading || kurang || nominalNum === 0"
            class="w-full py-4 font-black text-lg rounded-xl transition-all shadow-md"
            :class="!kurang && nominalNum >= totalHarga ? 'bg-white hover:bg-slate-200 text-slate-900' : 'bg-white/15 text-slate-400 cursor-not-allowed'"
          >
            {{ loading ? '⏳ Memproses...' : '✅ Konfirmasi Pembayaran' }}
          </button>
        </div>

        <!-- SUKSES -->
        <div v-if="sukses" class="print:hidden space-y-3">
          <div class="bg-emerald-400/10 border-2 border-emerald-400/60 rounded-2xl p-6 text-center">
            <div class="text-5xl mb-3">🎉</div>
            <h2 class="text-xl font-black text-emerald-300">Pembayaran Berhasil!</h2>
            <p class="text-emerald-300 text-sm mt-1">Kembalian: <span class="font-black text-lg">{{ formatRp(transaksiResult?.transaksi?.kembalian) }}</span></p>
          </div>
          <button @click="cetakStruk" class="w-full bg-white hover:bg-slate-200 text-slate-900 font-bold py-3.5 rounded-xl transition-all flex items-center justify-center gap-2">
            🖨️ Cetak Struk
          </button>
          <button @click="selesai" class="w-full bg-white hover:bg-slate-200 text-slate-900 font-bold py-3.5 rounded-xl transition-all">
            ✅ Selesai — Scan Tiket Lain
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
@media print {
  body * { visibility: hidden; }
  #struk-bayar, #struk-bayar * { visibility: visible; }
  #struk-bayar { position: fixed; top: 0; left: 0; width: 80mm; }
}
</style>
