<script setup>
definePageMeta({ layout: 'petugas', middleware: 'auth-petugas' })

import { ref, reactive, computed, onMounted } from 'vue'

const config = useRuntimeConfig()
const router = useRouter()
const nama = ref('')

const today = new Date().toISOString().split('T')[0]
const filter = reactive({ tanggal_dari: today, tanggal_sampai: today })
const transaksi = ref([])
const loading = ref(false)
const hoveredIndex = ref(null)

const getToken = () => (import.meta.client ? localStorage.getItem('auth_token') || '' : '')

const fetchData = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filter.tanggal_dari) params.append('tanggal_dari', filter.tanggal_dari)
    if (filter.tanggal_sampai) params.append('tanggal_sampai', filter.tanggal_sampai)
    
    transaksi.value = await $fetch(`/transaksi?${params}`, {
      baseURL: config.public.apiBase,
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${getToken()}`,
      },
    })
  } catch (err) {
    console.error('Gagal mengambil data transaksi:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  nama.value = localStorage.getItem('user_name') || ''
  fetchData()
})

const stats = computed(() => ({
  total: transaksi.value.length,
  pendapatan: transaksi.value.reduce((s, t) => s + (Number(t.total_harga) || 0), 0),
  motor: transaksi.value.filter(t => t.jenis_kendaraan === 'motor').length,
  mobil: transaksi.value.filter(t => t.jenis_kendaraan === 'mobil').length,
  member: transaksi.value.filter(t => t.jenis_member === 'member').length,
}))

// DATA UNTUK DIAGRAM GARIS (LINE CHART)
const chartData = computed(() => {
  if (!transaksi.value.length) return []
  
  // Kelompokkan per tanggal / jam
  const groups = {}
  transaksi.value.forEach(t => {
    const rawDate = t.waktu_keluar || t.waktu_masuk || t.created_at
    const label = rawDate ? new Date(rawDate).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) : 'Hari ini'
    if (!groups[label]) {
      groups[label] = { label, total: 0, count: 0 }
    }
    groups[label].total += Number(t.total_harga) || 0
    groups[label].count += 1
  })

  const arr = Object.values(groups)
  return arr.length > 0 ? arr : [{ label: 'Hari ini', total: 0, count: 0 }]
})

const maxChartValue = computed(() => {
  if (!chartData.value.length) return 10000
  const max = Math.max(...chartData.value.map(c => c.total))
  return max > 0 ? max : 10000
})

// Koordinat SVG Path Line Chart
const chartPoints = computed(() => {
  const data = chartData.value
  if (!data.length) return []
  const width = 760
  const height = 180
  const padX = 40
  const padY = 25
  const usableW = width - padX * 2
  const usableH = height - padY * 2
  const max = maxChartValue.value

  return data.map((d, i) => {
    const x = data.length === 1 ? width / 2 : padX + (i / (data.length - 1)) * usableW
    const y = height - padY - (d.total / max) * usableH
    return { ...d, x, y }
  })
})

const polylinePoints = computed(() => {
  return chartPoints.value.map(p => `${p.x},${p.y}`).join(' ')
})

const areaPathD = computed(() => {
  const pts = chartPoints.value
  if (!pts.length) return ''
  const height = 180 - 25
  const first = pts[0]
  const last = pts[pts.length - 1]
  const linePart = pts.map(p => `L ${p.x} ${p.y}`).join(' ')
  return `M ${first.x} ${height} ${linePart} L ${last.x} ${height} Z`
})

const downloadPdf = () => {
  const params = new URLSearchParams()
  if (filter.tanggal_dari) params.append('tanggal_dari', filter.tanggal_dari)
  if (filter.tanggal_sampai) params.append('tanggal_sampai', filter.tanggal_sampai)
  window.open(`${config.public.apiBase}/laporan/pdf?${params}`, '_blank')
}

const logout = async () => {
  await $fetch('/logout', {
    baseURL: config.public.apiBase,
    method: 'POST',
    headers: { Authorization: `Bearer ${getToken()}` },
  }).catch(() => {})
  localStorage.clear()
  router.push('/login')
}

const formatRp = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID')
const formatTgl = (d) => d ? new Date(d).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'
const formatMenit = (m) => {
  if (!m && m !== 0) return '—'
  const h = Math.floor(m / 60)
  const min = m % 60
  return h > 0 ? `${h} jam ${min} menit` : `${min} menit`
}
</script>

<template>
  <div>
    

    <!-- KONTEN UTAMA -->
    <div class="flex flex-col">
      
      <!-- Header -->
      <header class="bg-white/10 backdrop-blur-xl border-b border-white/10 px-8 py-5 flex items-center justify-between shadow-xs">
        <div>
          <h1 class="text-xl font-bold text-rose-200">Laporan Transaksi Parkir</h1>
          <p class="text-sm text-slate-300">Rekapitulasi pendapatan dan statistik kendaraan parkir</p>
        </div>
        <button
          @click="downloadPdf"
          class="flex items-center gap-2 bg-white hover:bg-slate-200 text-slate-900 font-semibold px-4 py-2.5 rounded-xl transition shadow">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Download PDF
        </button>
      </header>

      <div class="p-8 space-y-6">

        <!-- Filter Tanggal -->
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-sm border border-white/10 p-5 flex flex-wrap gap-4 items-end">
          <div>
            <label class="block text-xs font-bold text-slate-200 mb-1.5">Dari Tanggal</label>
            <input v-model="filter.tanggal_dari" type="date"
              class="px-3.5 py-2 border border-white/15 rounded-xl text-sm outline-none focus:ring-2 focus:ring-white/30 bg-white/10 backdrop-blur-xl" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-200 mb-1.5">Sampai Tanggal</label>
            <input v-model="filter.tanggal_sampai" type="date"
              class="px-3.5 py-2 border border-white/15 rounded-xl text-sm outline-none focus:ring-2 focus:ring-white/30 bg-white/10 backdrop-blur-xl" />
          </div>
          <button @click="fetchData" :disabled="loading"
            class="bg-white hover:bg-slate-200 disabled:opacity-50 text-slate-900 font-bold px-5 py-2.5 rounded-xl text-sm transition flex items-center gap-2 shadow">
            <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <span>Tampilkan</span>
          </button>
        </div>

        <!-- ════ DIAGRAM GARIS (LINE CHART) TREN PENDAPATAN ════ -->
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-sm border border-white/10 p-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-base font-bold text-slate-100">Diagram Garis Pendapatan</h2>
              <p class="text-xs text-slate-300">Visualisasi tren pendapatan pada periode terpilih</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-semibold text-slate-300">
              <span class="inline-flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-rose-400 inline-block"></span>
                Pendapatan (Rp)
              </span>
            </div>
          </div>

          <!-- Area Grafik SVG -->
          <div class="relative w-full overflow-x-auto">
            <svg viewBox="0 0 760 180" class="w-full h-44 overflow-visible">
              <defs>
                <linearGradient id="petugasRedGradient" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#b91c1c" stop-opacity="0.25" />
                  <stop offset="100%" stop-color="#b91c1c" stop-opacity="0.0" />
                </linearGradient>
              </defs>

              <!-- Garis Grid Horizontal -->
              <line x1="40" y1="25" x2="720" y2="25" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="4" />
              <line x1="40" y1="80" x2="720" y2="80" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="4" />
              <line x1="40" y1="135" x2="720" y2="135" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="4" />
              <line x1="40" y1="155" x2="720" y2="155" stroke="#e2e8f0" stroke-width="1.5" />

              <!-- Label Nilai Y -->
              <text x="35" y="28" text-anchor="end" font-size="9" fill="#94a3b8">{{ formatRp(maxChartValue) }}</text>
              <text x="35" y="83" text-anchor="end" font-size="9" fill="#94a3b8">{{ formatRp(Math.round(maxChartValue / 2)) }}</text>
              <text x="35" y="157" text-anchor="end" font-size="9" fill="#94a3b8">Rp 0</text>

              <!-- Gradient Area di Bawah Garis -->
              <path v-if="areaPathD" :d="areaPathD" fill="url(#petugasRedGradient)" />

              <!-- Polyline Garis Utama -->
              <polyline
                v-if="polylinePoints"
                :points="polylinePoints"
                fill="none"
                stroke="#b91c1c"
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
              />

              <!-- Titik-Titik Data (Points) -->
              <g v-for="(p, idx) in chartPoints" :key="idx">
                <circle
                  :cx="p.x"
                  :cy="p.y"
                  r="5"
                  class="transition-all cursor-pointer"
                  :fill="hoveredIndex === idx ? '#7f1d1d' : '#b91c1c'"
                  stroke="#ffffff"
                  stroke-width="2"
                  @mouseenter="hoveredIndex = idx"
                  @mouseleave="hoveredIndex = null"
                />
                
                <!-- Label Tanggal Sumbu X -->
                <text
                  :x="p.x"
                  y="172"
                  text-anchor="middle"
                  font-size="10"
                  font-weight="600"
                  fill="#64748b"
                >
                  {{ p.label }}
                </text>

                <!-- Tooltip Hover Nilai -->
                <g v-if="hoveredIndex === idx">
                  <rect
                    :x="p.x - 55"
                    :y="p.y - 36"
                    width="110"
                    height="28"
                    rx="6"
                    fill="#1e293b"
                    opacity="0.95"
                  />
                  <text
                    :x="p.x"
                    :y="p.y - 18"
                    text-anchor="middle"
                    font-size="10"
                    font-weight="bold"
                    fill="#ffffff"
                  >
                    {{ formatRp(p.total) }} ({{ p.count }} tkt)
                  </text>
                </g>
              </g>
            </svg>
          </div>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
          <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/10 p-5 text-center shadow-xs">
            <p class="text-xs text-slate-300 uppercase tracking-wide font-semibold">Total Tiket</p>
            <p class="text-2xl font-black text-slate-100 mt-1">{{ stats.total }}</p>
          </div>
          <div class="text-white rounded-2xl p-5 text-center shadow col-span-2 bg-white/10 backdrop-blur-xl border border-white/15">
            <p class="text-xs text-slate-200 uppercase tracking-wide font-semibold">Total Pendapatan</p>
            <p class="text-2xl font-black mt-1">{{ formatRp(stats.pendapatan) }}</p>
          </div>
          <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/10 p-5 text-center shadow-xs">
            <p class="text-xs text-slate-300 uppercase tracking-wide font-semibold">Motor</p>
            <p class="text-2xl font-black text-rose-200 mt-1">{{ stats.motor }}</p>
          </div>
          <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/10 p-5 text-center shadow-xs">
            <p class="text-xs text-slate-300 uppercase tracking-wide font-semibold">Mobil</p>
            <p class="text-2xl font-black text-rose-200 mt-1">{{ stats.mobil }}</p>
          </div>
        </div>

        <!-- Tabel Transaksi -->
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-sm border border-white/10 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-white text-xs uppercase tracking-wider font-semibold bg-white/10 backdrop-blur-xl border border-white/15">
                <tr>
                  <th class="py-3.5 px-4">#</th>
                  <th class="py-3.5 px-4">Invoice</th>
                  <th class="py-3.5 px-4">Kendaraan</th>
                  <th class="py-3.5 px-4">Plat Nomor</th>
                  <th class="py-3.5 px-4">Waktu Masuk</th>
                  <th class="py-3.5 px-4">Waktu Keluar</th>
                  <th class="py-3.5 px-4">Durasi</th>
                  <th class="py-3.5 px-4">Metode</th>
                  <th class="py-3.5 px-5 text-right">Total</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/10 text-slate-200">
                <tr v-if="loading">
                  <td colspan="9" class="py-12 text-center text-slate-400 font-medium">Memuat data transaksi...</td>
                </tr>
                <tr v-else-if="transaksi.length === 0">
                  <td colspan="9" class="py-12 text-center text-slate-400 font-medium">Tidak ada transaksi pada periode ini.</td>
                </tr>
                <tr v-for="(t, i) in transaksi" :key="t.id" class="hover:bg-white/5 transition">
                  <td class="py-3.5 px-4 text-slate-400 text-xs">{{ i + 1 }}</td>
                  <td class="py-3.5 px-4 font-mono font-bold text-rose-200 text-xs">{{ t.no_invoice }}</td>
                  <td class="py-3.5 px-4 capitalize font-semibold text-slate-100">{{ t.jenis_kendaraan }}</td>
                  <td class="py-3.5 px-4 font-mono font-bold text-slate-100">{{ t.plat_nomor || '—' }}</td>
                  <td class="py-3.5 px-4 text-xs text-slate-300">{{ formatTgl(t.waktu_masuk) }}</td>
                  <td class="py-3.5 px-4 text-xs text-slate-300">{{ formatTgl(t.waktu_keluar) }}</td>
                  <td class="py-3.5 px-4 text-xs font-medium text-slate-200">{{ formatMenit(t.total_menit) }}</td>
                  <td class="py-3.5 px-4">
                    <span
                      class="text-xs font-bold px-2.5 py-1 rounded-full uppercase"
                      :class="t.metode === 'member' ? 'bg-emerald-400/20 text-emerald-300' : 'bg-rose-400/15 text-rose-200'"
                    >
                      {{ t.metode }}
                    </span>
                  </td>
                  <td class="py-3.5 px-5 text-right font-black text-slate-100">{{ formatRp(t.total_harga) }}</td>
                </tr>
              </tbody>
              <tfoot v-if="transaksi.length > 0" class="bg-white/5 font-bold border-t-2 border-white/15">
                <tr>
                  <td colspan="8" class="py-3.5 px-4 text-right text-rose-200 font-black">TOTAL PENDAPATAN</td>
                  <td class="py-3.5 px-5 text-right text-rose-200 text-base font-black">{{ formatRp(stats.pendapatan) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>
