<script setup>
definePageMeta({ layout: 'admin', middleware: 'auth-admin' })

import { ref, computed, onMounted } from 'vue'

const config  = useRuntimeConfig()
const router  = useRouter()

// ── Filter bulan & tahun ──
const bulan = ref(new Date().getMonth() + 1)
const tahun = ref(new Date().getFullYear())

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
const stats = ref(null)
const loading = ref(false)
const hoveredDay = ref(null)

const getToken = () => (import.meta.client ? localStorage.getItem('auth_token') ?? '' : '')

const fetchDashboard = async () => {
  try {
    loading.value = true
    const headers = { Accept: 'application/json' }
    const token = getToken()
    if (token) headers['Authorization'] = `Bearer ${token}`

    const data = await $fetch('/admin/dashboard', {
      baseURL: config.public.apiBase,
      params:  { bulan: bulan.value, tahun: tahun.value },
      headers,
    })
    stats.value = data
  } catch (err) {
    console.error('Fetch dashboard error:', err)
  } finally {
    loading.value = false
  }
}

// ── Perhitungan Diagram Garis (SVG Line Chart) ──
const maxChartValue = computed(() => {
  if (!stats.value?.grafik_harian?.length) return 10000
  const max = Math.max(...stats.value.grafik_harian.map(d => Number(d.total) || 0))
  return max > 0 ? max : 10000
})

const chartPoints = computed(() => {
  const data = stats.value?.grafik_harian || []
  if (!data.length) return []
  const width = 800
  const height = 200
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
  const height = 200 - 25
  const first = pts[0]
  const last = pts[pts.length - 1]
  const linePart = pts.map(p => `L ${p.x} ${p.y}`).join(' ')
  return `M ${first.x} ${height} ${linePart} L ${last.x} ${height} Z`
})

const formatRp = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID')

// Logout
const logout = async () => {
  await $fetch('/logout', {
    baseURL: config.public.apiBase,
    method:  'POST',
    headers: { Authorization: `Bearer ${getToken()}` },
  }).catch(() => {})
  localStorage.clear()
  router.push('/login')
}

onMounted(fetchDashboard)
</script>

<template>
  <div>


    <!-- ═══ MAIN ═══ -->
    <div class="flex flex-col">

      <!-- Header -->
      <header class="bg-white/10 backdrop-blur-xl border-b border-white/10 px-8 py-5 flex items-center justify-between shadow-xs">
        <div>
          <h1 class="text-xl font-bold text-rose-200">Dashboard E-Parkir</h1>
          <p class="text-sm text-slate-300">Statistik real-time pendapatan dan aktivitas parkir Plaza Andalas</p>
        </div>
        <!-- Filter -->
        <div class="flex items-center gap-3">
          <select v-model.number="bulan" @change="fetchDashboard"
            class="border border-white/15 rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-white/30 outline-none bg-white/10 backdrop-blur-xl shadow-xs">
            <option v-for="b in daftarBulan" :key="b.v" :value="b.v">{{ b.l }}</option>
          </select>
          <select v-model.number="tahun" @change="fetchDashboard"
            class="border border-white/15 rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-white/30 outline-none bg-white/10 backdrop-blur-xl shadow-xs">
            <option v-for="y in tahunList" :key="y" :value="y">{{ y }}</option>
          </select>
          <button @click="fetchDashboard"
            class="bg-white hover:bg-slate-200 text-slate-900 px-4 py-2 rounded-xl text-sm font-semibold transition shadow">
            Tampilkan
          </button>
        </div>
      </header>

      <div class="p-8 space-y-6">

        <!-- Loading -->
        <div v-if="loading" class="text-center py-16 text-rose-300 font-semibold flex items-center justify-center gap-2">
          <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Memuat data statistik...
        </div>

        <template v-else-if="stats">

          <!-- ── Stat Cards ── -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">

            <!-- Total Pendapatan -->
            <div class="text-white rounded-2xl p-5 shadow-md bg-white/10 backdrop-blur-xl border border-white/15">
              <div class="flex items-start justify-between mb-3">
                <svg class="w-7 h-7 opacity-85" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div class="text-2xl font-black leading-tight">{{ formatRp(stats.total_pendapatan) }}</div>
              <div class="text-slate-200 text-xs mt-1 font-medium">Total Pendapatan Bulan Ini</div>
            </div>

            <!-- Total Transaksi -->
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-5 shadow-xs border border-white/10">
              <div class="flex items-start justify-between mb-3">
                <svg class="w-7 h-7 text-rose-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
              </div>
              <div class="text-2xl font-black text-slate-100">{{ stats.total_transaksi }}</div>
              <div class="text-slate-400 text-xs mt-1 font-medium">Total Tiket Selesai</div>
            </div>

            <!-- Motor -->
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-5 shadow-xs border border-white/10">
              <div class="flex items-start justify-between mb-3">
                <svg class="w-7 h-7 text-rose-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
              </div>
              <div class="text-2xl font-black text-slate-100">{{ stats.total_motor }}</div>
              <div class="text-slate-400 text-xs mt-1 font-medium">Kendaraan Motor</div>
            </div>

            <!-- Mobil -->
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-5 shadow-xs border border-white/10">
              <div class="flex items-start justify-between mb-3">
                <svg class="w-7 h-7 text-rose-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M8 17a2 2 0 100-4 2 2 0 000 4zm8 0a2 2 0 100-4 2 2 0 000 4zM3 10l1.5-5h15L21 10M3 10h18M3 10l-1 5h20l-1-5"/>
                </svg>
              </div>
              <div class="text-2xl font-black text-slate-100">{{ stats.total_mobil }}</div>
              <div class="text-slate-400 text-xs mt-1 font-medium">Kendaraan Mobil</div>
            </div>
          </div>

          <!-- ════ DIAGRAM GARIS (LINE CHART) PENDAPATAN HARIAN ════ -->
          <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-sm border border-white/10 p-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="font-bold text-slate-100 text-base">Diagram Garis Pendapatan Harian</h3>
                <p class="text-xs text-slate-300">Tren pemasukan parkir setiap tanggal pada bulan yang dipilih</p>
              </div>
              <div class="flex items-center gap-3 text-xs font-semibold text-slate-300">
                <span class="inline-flex items-center gap-1.5">
                  <span class="w-3 h-3 rounded-full bg-rose-400 inline-block"></span>
                  Pemasukan (Rp)
                </span>
              </div>
            </div>

            <!-- Area Grafik SVG Garis -->
            <div class="relative w-full overflow-x-auto">
              <svg viewBox="0 0 800 200" class="w-full h-48 overflow-visible">
                <defs>
                  <linearGradient id="adminRedArea" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#7dd3fc" stop-opacity="0.28" />
                    <stop offset="100%" stop-color="#7dd3fc" stop-opacity="0.0" />
                  </linearGradient>
                </defs>

                <!-- Grid Horizontal Lines -->
                <line x1="40" y1="25" x2="760" y2="25" stroke="rgba(255,255,255,0.12)" stroke-width="1" stroke-dasharray="4" />
                <line x1="40" y1="85" x2="760" y2="85" stroke="rgba(255,255,255,0.12)" stroke-width="1" stroke-dasharray="4" />
                <line x1="40" y1="145" x2="760" y2="145" stroke="rgba(255,255,255,0.12)" stroke-width="1" stroke-dasharray="4" />
                <line x1="40" y1="175" x2="760" y2="175" stroke="rgba(255,255,255,0.25)" stroke-width="1.5" />

                <!-- Label Sumbu Y -->
                <text x="35" y="28" text-anchor="end" font-size="9" fill="#94a3b8">{{ formatRp(maxChartValue) }}</text>
                <text x="35" y="88" text-anchor="end" font-size="9" fill="#94a3b8">{{ formatRp(Math.round(maxChartValue / 2)) }}</text>
                <text x="35" y="177" text-anchor="end" font-size="9" fill="#94a3b8">Rp 0</text>

                <!-- Gradient Fill Under Polyline -->
                <path v-if="areaPathD" :d="areaPathD" fill="url(#adminRedArea)" />

                <!-- Polyline Line Curve -->
                <polyline
                  v-if="polylinePoints"
                  :points="polylinePoints"
                  fill="none"
                  stroke="#7dd3fc"
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
                    :fill="hoveredDay === p.hari ? '#e0f2fe' : (p.total > 0 ? '#7dd3fc' : '#64748b')"
                    stroke="#1e293b"
                    stroke-width="1.5"
                    @mouseenter="hoveredDay = p.hari"
                    @mouseleave="hoveredDay = null"
                  />

                  <!-- Sumbu X: Hari (hanya tampilkan per kelipatan atau tanggal aktif) -->
                  <text
                    v-if="p.hari === 1 || p.hari % 5 === 0 || p.hari === chartPoints.length || p.total > 0"
                    :x="p.x"
                    y="192"
                    text-anchor="middle"
                    font-size="9"
                    font-weight="600"
                    :fill="p.total > 0 ? '#7dd3fc' : '#64748b'"
                  >
                    Tgl {{ p.hari }}
                  </text>

                  <!-- Tooltip Hover Nilai -->
                  <g v-if="hoveredDay === p.hari">
                    <rect
                      :x="Math.min(700, Math.max(10, p.x - 55))"
                      :y="p.y - 36"
                      width="110"
                      height="28"
                      rx="6"
                      fill="#1e293b"
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
                      Tgl {{ p.hari }}: {{ formatRp(p.total) }}
                    </text>
                  </g>
                </g>
              </svg>
            </div>
          </div>

          <!-- ── Baris ke-2: Member vs Non-member breakdown ── -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <!-- Member breakdown -->
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-xs border border-white/10 p-5">
              <h3 class="font-bold text-slate-100 mb-4 text-sm">Metode Tiket Masuk</h3>
              <div class="space-y-4">
                <div>
                  <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
                    <span class="text-slate-300">Cash / Non-Member</span>
                    <span class="font-bold text-slate-100">{{ stats.total_non_member }} Tiket</span>
                  </div>
                  <div class="w-full bg-rose-400/15 rounded-full h-2.5">
                    <div class="bg-rose-400 h-2.5 rounded-full transition-all"
                      :style="`width:${stats.total_transaksi > 0 ? Math.round((stats.total_non_member / stats.total_transaksi) * 100) : 0}%`"></div>
                  </div>
                </div>

                <div>
                  <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
                    <span class="text-slate-300">Pelanggan Member</span>
                    <span class="font-bold text-slate-100">{{ stats.total_member }} Tiket</span>
                  </div>
                  <div class="w-full bg-rose-400/15 rounded-full h-2.5">
                    <div class="bg-rose-500 h-2.5 rounded-full transition-all"
                      :style="`width:${stats.total_transaksi > 0 ? Math.round((stats.total_member / stats.total_transaksi) * 100) : 0}%`"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Ringkasan Info Sistem -->
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-xs border border-white/10 p-5">
              <h3 class="font-bold text-slate-100 mb-4 text-sm">Status Operasional</h3>
              <div class="space-y-3 text-xs">
                <div class="flex justify-between items-center py-2 border-b border-white/10">
                  <span class="text-slate-300">Sistem Parkir:</span>
                  <span class="font-bold text-emerald-300 bg-emerald-400/10 px-2 py-0.5 rounded-md">Online & Aktif</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-white/10">
                  <span class="text-slate-300">Tarif Dasar Parkir:</span>
                  <span class="font-bold text-slate-100">Motor Rp 2.000 / Mobil Rp 4.000</span>
                </div>
                <div class="flex justify-between items-center py-2">
                  <span class="text-slate-300">Biaya Member 30 Hari:</span>
                  <span class="font-bold text-rose-200">Rp 150.000 / akun (3 Plat Slot)</span>
                </div>
              </div>
            </div>
          </div>

        </template>

        <!-- No data / Error -->
        <div v-else class="text-center py-16 bg-white/10 backdrop-blur-xl rounded-2xl border border-white/10">
          <svg class="w-16 h-16 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
          <p class="text-slate-300 font-semibold">Gagal memuat data dari database.</p>
          <p class="text-xs text-slate-400 mt-1">Silakan coba klik tombol Tampilkan atau periksa koneksi backend.</p>
        </div>

      </div>
    </div>
  </div>
</template>
