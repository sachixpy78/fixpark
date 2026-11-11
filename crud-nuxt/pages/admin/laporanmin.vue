<script setup>
definePageMeta({ layout: 'admin', middleware: 'auth-admin' })

import { ref, computed, onMounted } from 'vue'

const config = useRuntimeConfig()
const router = useRouter()

// ── Filter ──
const bulan = ref(new Date().getMonth() + 1)
const tahun = ref(new Date().getFullYear())
const petugasId = ref('')

const daftarBulan = [
  { v: 1, l: 'Januari' }, { v: 2, l: 'Februari' }, { v: 3, l: 'Maret' },
  { v: 4, l: 'April' },   { v: 5, l: 'Mei' },       { v: 6, l: 'Juni' },
  { v: 7, l: 'Juli' },    { v: 8, l: 'Agustus' },   { v: 9, l: 'September' },
  { v: 10, l: 'Oktober' },{ v: 11, l: 'November' },  { v: 12, l: 'Desember' },
]
const tahunList = computed(() => {
  const y = new Date().getFullYear()
  return Array.from({ length: 5 }, (_, i) => y - i)
})

// ── Data ──
const petugas = ref([])
const laporan = ref([])
const totalPendapatan = ref(0)
const loading = ref(false)
const hoveredIndex = ref(null)

const getToken = () => (import.meta.client ? localStorage.getItem('auth_token') ?? '' : '')

const formatRp = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID')

const fetchPetugas = async () => {
  try {
    const res = await $fetch('/petugas', {
      baseURL: config.public.apiBase,
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${getToken()}`,
      },
    })
    petugas.value = res || []
  } catch (err) {
    console.error('Gagal mengambil daftar petugas:', err)
  }
}

const fetchLaporan = async () => {
  if (!import.meta.client) return
  try {
    loading.value = true
    const params = { bulan: bulan.value, tahun: tahun.value }
    if (petugasId.value) params.petugas_id = petugasId.value

    const res = await $fetch('/admin/laporan', {
      baseURL: config.public.apiBase,
      params,
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${getToken()}`,
      },
    })
    laporan.value = res.data || []
    totalPendapatan.value = res.total_pendapatan || 0
  } catch (err) {
    console.error('Gagal mengambil laporan admin:', err)
    laporan.value = []
    totalPendapatan.value = 0
  } finally {
    loading.value = false
  }
}

// ── Perhitungan Diagram Garis (SVG Line Chart) Dari Data Laporan ──
const chartData = computed(() => {
  const jumlahHari = new Date(tahun.value, bulan.value, 0).getDate()
  const days = {}
  for (let d = 1; d <= jumlahHari; d++) {
    days[d] = { hari: d, total: 0, count: 0 }
  }

  laporan.value.forEach(item => {
    if (item.waktu_keluar) {
      // Format "05/11/2026 05:57" -> ambil hari
      const parts = item.waktu_keluar.split(' ')[0].split('/')
      const dayNum = parseInt(parts[0], 10)
      if (days[dayNum]) {
        days[dayNum].total += Number(item.total_harga) || 0
        days[dayNum].count += 1
      }
    }
  })

  return Object.values(days)
})

const maxChartValue = computed(() => {
  if (!chartData.value.length) return 10000
  const max = Math.max(...chartData.value.map(c => c.total))
  return max > 0 ? max : 10000
})

const chartPoints = computed(() => {
  const data = chartData.value
  if (!data.length) return []
  const width = 800
  const height = 190
  const padX = 40
  const padY = 25
  const usableW = width - padX * 2
  const usableH = height - padY * 2
  const max = maxChartValue.value

  return data.map((d, i) => {
    const x = padX + (i / (data.length - 1)) * usableW
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
  const height = 190 - 25
  const first = pts[0]
  const last = pts[pts.length - 1]
  const linePart = pts.map(p => `L ${p.x} ${p.y}`).join(' ')
  return `M ${first.x} ${height} ${linePart} L ${last.x} ${height} Z`
})

// ── PDF Download ──
const downloadPdf = () => {
  const base = config.public.apiBase
  const token = getToken()
  const params = new URLSearchParams({
    bulan: bulan.value,
    tahun: tahun.value,
    ...(petugasId.value ? { petugas_id: petugasId.value } : {}),
    _token: token,
  })
  window.open(`${base}/admin/laporan/pdf?${params}`, '_blank')
}

// ── Logout ──
const logout = async () => {
  await $fetch('/logout', {
    baseURL: config.public.apiBase,
    method: 'POST',
    headers: { Authorization: `Bearer ${getToken()}` },
  }).catch(() => {})
  localStorage.clear()
  router.push('/login')
}

onMounted(async () => {
  await fetchPetugas()
  await fetchLaporan()
})
</script>

<template>
  <div>

    <!-- ══ MAIN ═══ -->
    <div class="flex flex-col">

      <!-- Header -->
      <header class="bg-slate-800/80 backdrop-blur-xl border-b border-white/10 px-8 py-5 shadow-xs">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h1 class="text-xl font-bold text-blue-200">Laporan Transaksi Parkir</h1>
            <p class="text-sm text-slate-300">Rekapitulasi riwayat tiket parkir keluar per petugas</p>
          </div>
          <button @click="downloadPdf"
            class="flex items-center gap-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition shadow-lg shadow-blue-900/40 hover:shadow-blue-500/50 hover:scale-[1.02] active:scale-[0.98]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Unduh Laporan PDF
          </button>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <select v-model="petugasId" @change="fetchLaporan"
              class="border border-slate-600 rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 outline-none bg-slate-700/50 backdrop-blur-xl min-w-[170px] shadow-xs text-white">
              <option value="">Semua Petugas</option>
              <option v-for="p in petugas" :key="p.id" :value="p.id">{{ p.username }}</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <select v-model.number="bulan" @change="fetchLaporan"
              class="border border-slate-600 rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 outline-none bg-slate-700/50 backdrop-blur-xl shadow-xs text-white">
              <option v-for="b in daftarBulan" :key="b.v" :value="b.v">{{ b.l }}</option>
            </select>
            <select v-model.number="tahun" @change="fetchLaporan"
              class="border border-slate-600 rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 outline-none bg-slate-700/50 backdrop-blur-xl shadow-xs text-white">
              <option v-for="y in tahunList" :key="y" :value="y">{{ y }}</option>
            </select>
            <button @click="fetchLaporan"
              class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white px-5 py-2 rounded-xl text-sm font-semibold transition shadow-lg shadow-blue-900/40 hover:shadow-blue-500/50">
              Tampilkan
            </button>
          </div>
        </div>
      </header>

      <div class="p-8 space-y-6">

        <!-- ════ DIAGRAM GARIS (LINE CHART) PENDAPATAN BULANAN ═══ -->
        <div class="bg-slate-800/60 backdrop-blur-xl rounded-2xl shadow-lg border border-white/10 p-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="font-bold text-slate-100 text-base">Diagram Garis Pendapatan Harian</h3>
              <p class="text-xs text-slate-300">Visualisasi pemasukan harian berdasarkan filter petugas dan bulan</p>
            </div>
            <div class="flex items-center gap-3 text-xs font-semibold text-slate-300">
              <span class="inline-flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-cyan-400 inline-block"></span>
                Pendapatan (Rp)
              </span>
            </div>
          </div>

          <!-- Area Grafik SVG Garis -->
          <div class="relative w-full overflow-x-auto">
            <svg viewBox="0 0 800 190" class="w-full h-44 overflow-visible">
              <defs>
                <linearGradient id="laporanAdminBlueArea" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#22d3ee" stop-opacity="0.35" />
                  <stop offset="100%" stop-color="#22d3ee" stop-opacity="0.0" />
                </linearGradient>
              </defs>

              <!-- Garis Grid Horizontal -->
              <line x1="40" y1="25" x2="760" y2="25" stroke="rgba(255,255,255,0.08)" stroke-width="1" stroke-dasharray="4" />
              <line x1="40" y1="85" x2="760" y2="85" stroke="rgba(255,255,255,0.08)" stroke-width="1" stroke-dasharray="4" />
              <line x1="40" y1="140" x2="760" y2="140" stroke="rgba(255,255,255,0.08)" stroke-width="1" stroke-dasharray="4" />
              <line x1="40" y1="165" x2="760" y2="165" stroke="rgba(255,255,255,0.20)" stroke-width="1.5" />

              <!-- Label Sumbu Y -->
              <text x="35" y="28" text-anchor="end" font-size="9" fill="#94a3b8">{{ formatRp(maxChartValue) }}</text>
              <text x="35" y="88" text-anchor="end" font-size="9" fill="#94a3b8">{{ formatRp(Math.round(maxChartValue / 2)) }}</text>
              <text x="35" y="167" text-anchor="end" font-size="9" fill="#94a3b8">Rp 0</text>

              <!-- Gradient Fill Under Polyline -->
              <path v-if="areaPathD" :d="areaPathD" fill="url(#laporanAdminBlueArea)" />

              <!-- Polyline Line Curve -->
              <polyline
                v-if="polylinePoints"
                :points="polylinePoints"
                fill="none"
                stroke="#22d3ee"
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
              />

              <!-- Point Circles & Tooltip -->
              <g v-for="(p, idx) in chartPoints" :key="idx">
                <circle
                  :cx="p.x"
                  :cy="p.y"
                  :r="p.total > 0 ? 5 : 2.5"
                  class="transition-all cursor-pointer"
                  :fill="hoveredIndex === p.hari ? '#cffafe' : (p.total > 0 ? '#22d3ee' : '#64748b')"
                  stroke="#0f172a"
                  stroke-width="1.5"
                  @mouseenter="hoveredIndex = p.hari"
                  @mouseleave="hoveredIndex = null"
                />

                <!-- Sumbu X: Hari -->
                <text
                  v-if="p.hari === 1 || p.hari % 5 === 0 || p.hari === chartPoints.length || p.total > 0"
                  :x="p.x"
                  y="180"
                  text-anchor="middle"
                  font-size="9"
                  font-weight="600"
                  :fill="p.total > 0 ? '#22d3ee' : '#64748b'"
                >
                  Tgl {{ p.hari }}
                </text>

                <!-- Tooltip Hover Nilai -->
                <g v-if="hoveredIndex === p.hari">
                  <rect
                    :x="Math.min(700, Math.max(10, p.x - 55))"
                    :y="p.y - 36"
                    width="110"
                    height="28"
                    rx="6"
                    fill="#0f172a"
                    opacity="0.95"
                  />
                  <text
                    :x="Math.min(700, Math.max(10, p.x - 55)) + 55"
                    :y="p.y - 18"
                    text-anchor="middle"
                    font-size="10"
                    font-weight="bold"
                    fill="#ffffff"
                  >
                    Tgl {{ p.hari }}: {{ formatRp(p.total) }} ({{ p.count }} tkt)
                  </text>
                </g>
              </g>
            </svg>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <div class="text-white rounded-2xl p-5 shadow-lg bg-gradient-to-br from-blue-600/30 to-blue-700/30 backdrop-blur-xl border border-blue-500/30">
            <div class="text-xs text-blue-200 uppercase tracking-wide font-semibold mb-1">Total Pendapatan</div>
            <div class="text-2xl font-black">{{ formatRp(totalPendapatan) }}</div>
          </div>
          <div class="bg-slate-800/60 backdrop-blur-xl rounded-2xl p-5 shadow-xs border border-white/10">
            <div class="text-xs text-slate-300 uppercase tracking-wide font-semibold mb-1">Total Tiket Keluar</div>
            <div class="text-2xl font-black text-slate-100">{{ laporan.length }}</div>
          </div>
          <div class="bg-slate-800/60 backdrop-blur-xl rounded-2xl p-5 shadow-xs border border-white/10">
            <div class="text-xs text-slate-300 uppercase tracking-wide font-semibold mb-1">Rata-rata / Tiket</div>
            <div class="text-2xl font-black text-cyan-400">
              {{ laporan.length ? formatRp(Math.round(totalPendapatan / laporan.length)) : 'Rp 0' }}
            </div>
          </div>
        </div>

        <!-- Tabel Laporan -->
        <div class="bg-slate-800/60 backdrop-blur-xl rounded-2xl shadow-lg border border-white/10 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
              <thead>
                <tr class="text-slate-100 text-xs uppercase tracking-wider font-semibold bg-slate-700/50 border-b border-white/10">
                  <th class="py-4 px-4">#</th>
                  <th class="py-4 px-4">No. Invoice</th>
                  <th class="py-4 px-4">Plat Nomor</th>
                  <th class="py-4 px-4">Kendaraan</th>
                  <th class="py-4 px-4">Waktu Masuk</th>
                  <th class="py-4 px-4">Waktu Keluar</th>
                  <th class="py-4 px-4">Durasi</th>
                  <th class="py-4 px-4">Metode</th>
                  <th class="py-4 px-4">Petugas</th>
                  <th class="py-4 px-5 text-right">Total</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5 text-slate-200">
                <tr v-if="loading">
                  <td colspan="10" class="py-12 text-center text-blue-300 font-medium flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Memuat data laporan...
                  </td>
                </tr>
                <tr v-else-if="laporan.length === 0">
                  <td colspan="10" class="py-12 text-center text-slate-400 font-medium">
                    <div class="flex flex-col items-center gap-2">
                      <svg class="w-12 h-12 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                      </svg>
                      <span>Tidak ada data tiket keluar pada periode ini.</span>
                    </div>
                  </td>
                </tr>
                <tr v-for="(row, i) in laporan" :key="row.no_invoice" 
                  class="hover:bg-blue-500/10 transition border-b border-white/5 last:border-b-0">
                  <td class="py-3.5 px-4 text-xs text-slate-400">{{ i + 1 }}</td>
                  <td class="py-3.5 px-4 font-mono font-bold text-cyan-300 text-xs">{{ row.no_invoice }}</td>
                  <td class="py-3.5 px-4 font-mono font-bold text-slate-100">{{ row.plat_nomor || '—' }}</td>
                  <td class="py-3.5 px-4 capitalize font-semibold text-slate-100">{{ row.jenis_kendaraan }}</td>
                  <td class="py-3.5 px-4 text-xs text-slate-400">{{ row.waktu_masuk }}</td>
                  <td class="py-3.5 px-4 text-xs text-slate-400">{{ row.waktu_keluar }}</td>
                  <td class="py-3.5 px-4 text-xs font-medium text-slate-200">{{ row.durasi }}</td>
                  <td class="py-3.5 px-4">
                    <span class="text-xs font-bold px-3 py-1.5 rounded-full uppercase"
                      :class="row.metode === 'member' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-blue-500/20 text-blue-300 border border-blue-500/30'">
                      {{ row.metode }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-xs font-bold text-slate-200">{{ row.petugas }}</td>
                  <td class="py-3.5 px-5 text-right font-black text-slate-100">{{ formatRp(row.total_harga) }}</td>
                </tr>
              </tbody>
              <tfoot v-if="laporan.length > 0" class="bg-slate-700/50 font-bold border-t-2 border-white/10">
                <tr>
                  <td colspan="9" class="py-3.5 px-4 text-right text-blue-300 font-black">TOTAL PENDAPATAN</td>
                  <td class="py-3.5 px-5 text-right text-blue-200 text-base font-black">{{ formatRp(totalPendapatan) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>