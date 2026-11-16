<script setup>
definePageMeta({ layout: 'petugas', middleware: 'auth-petugas' })

import { ref, reactive, computed, onMounted } from 'vue'

const config = useRuntimeConfig()
const router = useRouter()
const nama = ref('')

const today = new Date().toISOString().split('T')[0]
const filter = reactive({ tanggal_dari: today, tanggal_sampai: today })
const transaksi = ref([])
const renewals = ref([])
const loading = ref(false)
const loadingRenewals = ref(false)
const hoveredIndex = ref(null)
const activeTab = ref('tiket')

const getToken = () => (import.meta.client ? localStorage.getItem('auth_token') || '' : '')

const fetchData = async () => {
  if (!import.meta.client) return
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

const fetchRenewals = async () => {
  if (!import.meta.client) return
  loadingRenewals.value = true
  try {
    const params = new URLSearchParams()
    if (filter.tanggal_dari) params.append('tanggal_dari', filter.tanggal_dari)
    if (filter.tanggal_sampai) params.append('tanggal_sampai', filter.tanggal_sampai)

    renewals.value = await $fetch(`/member-renewals?${params}`, {
      baseURL: config.public.apiBase,
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${getToken()}`,
      },
    })
  } catch (err) {
    console.error('Gagal mengambil data perpanjang member:', err)
    renewals.value = []
  } finally {
    loadingRenewals.value = false
  }
}

const fetchAll = () => {
  fetchData()
  fetchRenewals()
}

onMounted(() => {
  nama.value = localStorage.getItem('user_name') || ''
  fetchAll()
})

const stats = computed(() => ({
  total: transaksi.value.length,
  pendapatan: transaksi.value.reduce((s, t) => s + (Number(t.total_harga) || 0), 0),
  motor: transaksi.value.filter(t => t.jenis_kendaraan === 'motor').length,
  mobil: transaksi.value.filter(t => t.jenis_kendaraan === 'mobil').length,
  member: transaksi.value.filter(t => t.jenis_member === 'member').length,
}))

const renewalStats = computed(() => ({
  total: renewals.value.length,
  nominal: renewals.value.reduce((s, r) => s + (Number(r.nominal_bayar) || 0), 0),
}))

// DATA UNTUK DIAGRAM GARIS (LINE CHART)
const chartData = computed(() => {
  if (!transaksi.value.length) return []
  
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
const formatTglShort = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
const formatMenit = (m) => {
  if (!m && m !== 0) return '—'
  const h = Math.floor(m / 60)
  const min = m % 60
  return h > 0 ? `${h}j ${min}m` : `${min}m`
}
</script>

<template>
   <!-- Background Image dengan Overlay -->
    <div class="absolute inset-0 z-0">
  
      <img 
        src="/bg-parkir.jpg" 
        alt="Background Car" 
        class="w-full h-full object-cover opacity-20"
      />
    </div>


    <!-- Content -->
    <div class="relative z-10">
      <div class="p-8 max-w-[1600px] mx-auto">

        <!-- Header -->
        <div class="mb-8 flex items-start justify-between">
          <div>
            <h1 class="text-3xl font-black text-white tracking-tight">Laporan Transaksi Parkir</h1>
            <p class="text-slate-400 text-base mt-2">Rekapitulasi pendapatan dan statistik kendaraan parkir</p>
          </div>
          <button
            @click="downloadPdf"
            class="flex items-center gap-2 bg-blue-500 hover:bg-blue-400 text-white font-bold px-5 py-2.5 rounded-xl transition shadow-lg shadow-blue-500/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Download PDF
          </button>
        </div>

        <!-- Filter Tanggal -->
        <div class="bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-6 mb-6">
          <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-blue-500/15 border border-blue-400/30 flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            </div>
            <div>
              <h3 class="text-lg font-bold text-white">Filter Periode</h3>
              <p class="text-xs text-slate-400">Saring data laporan berdasarkan rentang tanggal</p>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wide">Dari Tanggal</label>
              <div class="relative">
                <svg class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <input v-model="filter.tanggal_dari" type="date" class="w-full pl-10 pr-3 py-3 bg-slate-950/60 border border-slate-700/50 rounded-xl text-sm text-slate-200 outline-none focus:border-blue-400/60 focus:bg-slate-950 transition-all" />
              </div>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wide">Sampai Tanggal</label>
              <div class="relative">
                <svg class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <input v-model="filter.tanggal_sampai" type="date" class="w-full pl-10 pr-3 py-3 bg-slate-950/60 border border-slate-700/50 rounded-xl text-sm text-slate-200 outline-none focus:border-blue-400/60 focus:bg-slate-950 transition-all" />
              </div>
            </div>
            <div class="md:col-span-2">
              <button @click="fetchAll" :disabled="loading || loadingRenewals" class="h-[46px] w-full bg-blue-500 hover:bg-blue-400 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold px-5 rounded-xl text-sm transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2">
                <svg v-if="loading || loadingRenewals" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span>Tampilkan Laporan</span>
              </button>
            </div>
          </div>
        </div>

        <!-- ════ DIAGRAM GARIS (LINE CHART) TREN PENDAPATAN ════ -->
        <div class="bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-6 mb-6">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-500/15 border border-blue-400/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
              </div>
              <div>
                <h2 class="text-lg font-bold text-white">Diagram Garis Pendapatan</h2>
                <p class="text-xs text-slate-400">Visualisasi tren pendapatan pada periode terpilih</p>
              </div>
            </div>
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-500/15 border border-blue-400/30">
              <span class="w-2.5 h-2.5 rounded-full bg-blue-400 inline-block"></span>
              <span class="text-xs font-semibold text-blue-300">Pendapatan (Rp)</span>
            </div>
          </div>

          <!-- Area Grafik SVG -->
          <div class="relative w-full overflow-x-auto">
            <svg viewBox="0 0 760 180" class="w-full h-44 overflow-visible">
              <defs>
                <linearGradient id="petugasBlueGradient" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.25" />
                  <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.0" />
                </linearGradient>
              </defs>

              <!-- Garis Grid Horizontal -->
              <line x1="40" y1="25" x2="720" y2="25" stroke="#334155" stroke-width="1" stroke-dasharray="4" />
              <line x1="40" y1="80" x2="720" y2="80" stroke="#334155" stroke-width="1" stroke-dasharray="4" />
              <line x1="40" y1="135" x2="720" y2="135" stroke="#334155" stroke-width="1" stroke-dasharray="4" />
              <line x1="40" y1="155" x2="720" y2="155" stroke="#475569" stroke-width="1.5" />

              <!-- Label Nilai Y -->
              <text x="35" y="28" text-anchor="end" font-size="9" fill="#94a3b8">{{ formatRp(maxChartValue) }}</text>
              <text x="35" y="83" text-anchor="end" font-size="9" fill="#94a3b8">{{ formatRp(Math.round(maxChartValue / 2)) }}</text>
              <text x="35" y="157" text-anchor="end" font-size="9" fill="#94a3b8">Rp 0</text>

              <!-- Gradient Area di Bawah Garis -->
              <path v-if="areaPathD" :d="areaPathD" fill="url(#petugasBlueGradient)" />

              <!-- Polyline Garis Utama -->
              <polyline
                v-if="polylinePoints"
                :points="polylinePoints"
                fill="none"
                stroke="#3b82f6"
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
                  :fill="hoveredIndex === idx ? '#1e40af' : '#3b82f6'"
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

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="relative overflow-hidden bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Total Transaksi</p>
                <p class="text-4xl font-black text-white mt-2">{{ stats.total }}</p>
                <p class="text-xs text-slate-500 mt-2">transaksi ditemukan</p>
              </div>
              <div class="w-14 h-14 rounded-xl bg-blue-500/15 border border-blue-400/30 flex items-center justify-center">
                <svg class="w-7 h-7 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
              </div>
            </div>
            <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl"></div>
          </div>

          <div class="relative overflow-hidden bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Total Pendapatan</p>
                <p class="text-3xl font-black text-white mt-2">{{ formatRp(stats.pendapatan) }}</p>
                <p class="text-xs text-slate-500 mt-2">dari seluruh transaksi</p>
              </div>
              <div class="w-14 h-14 rounded-xl bg-blue-500/15 border border-blue-400/30 flex items-center justify-center">
                <svg class="w-7 h-7 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              </div>
            </div>
            <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl"></div>
          </div>

          <div class="relative overflow-hidden bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Transaksi Member</p>
                <p class="text-4xl font-black text-white mt-2">{{ stats.member }}</p>
                <p class="text-xs text-slate-500 mt-2">pengguna member aktif</p>
              </div>
              <div class="w-14 h-14 rounded-xl bg-blue-500/15 border border-blue-400/30 flex items-center justify-center">
                <svg class="w-7 h-7 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
              </div>
            </div>
            <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl"></div>
          </div>
        </div>

        <!-- Dropdown Tab Switcher -->
        <div class="bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-4 mb-4 flex items-center gap-4">
          <svg class="w-5 h-5 text-blue-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
          <div class="relative flex-shrink-0">
            <select v-model="activeTab" class="appearance-none bg-slate-800 border border-slate-600 text-white font-semibold text-sm pl-4 pr-10 py-2.5 rounded-xl outline-none focus:border-blue-400 transition-all cursor-pointer">
              <option value="tiket">Riwayat Tiket</option>
              <option value="perpanjang">Riwayat Perpanjang Member</option>
            </select>
            <svg class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>
          <span v-if="activeTab === 'tiket'" class="text-sm text-slate-400">{{ transaksi.length }} transaksi pada periode ini</span>
          <span v-else class="text-sm text-slate-400">{{ renewals.length }} perpanjangan pada periode ini</span>
        </div>

        <!-- TABEL RIWAYAT TIKET -->
        <div v-if="activeTab === 'tiket'" class="bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
          <div class="px-6 py-5 border-b border-slate-700/50 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-500/15 border border-blue-400/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
              </div>
              <div>
                <h3 class="text-lg font-bold text-white">Riwayat Tiket Parkir</h3>
                <p class="text-xs text-slate-400">Menampilkan {{ transaksi.length }} data transaksi</p>
              </div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-500/15 border border-blue-400/30 text-blue-300 text-xs font-semibold">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"/></svg>
              {{ transaksi.length }} Records
            </span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm" style="table-layout: fixed;">
              <colgroup>
                <col style="width: 5%" />
                <col style="width: 12%" />
                <col style="width: 8%" />
                <col style="width: 11%" />
                <col style="width: 14%" />
                <col style="width: 14%" />
                <col style="width: 9%" />
                <col style="width: 9%" />
                <col style="width: 18%" />
              </colgroup>
              <thead class="bg-slate-950/40">
                <tr>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">#</th>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Invoice</th>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Kendaraan</th>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Plat Nomor</th>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Waktu Masuk</th>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Waktu Keluar</th>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Durasi</th>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Metode</th>
                  <th class="text-right px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/50">
                <tr v-if="loading">
                  <td colspan="9" class="px-6 py-20 text-center">
                    <div class="flex flex-col items-center gap-4">
                      <div class="w-20 h-20 rounded-2xl bg-slate-800/60 border border-slate-700/50 flex items-center justify-center">
                        <svg class="w-10 h-10 text-slate-500 animate-spin" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                      </div>
                      <p class="text-base font-bold text-white">Memuat data transaksi...</p>
                    </div>
                  </td>
                </tr>
                <tr v-else-if="transaksi.length === 0">
                  <td colspan="9" class="px-6 py-20 text-center">
                    <div class="flex flex-col items-center gap-4">
                      <div class="w-20 h-20 rounded-2xl bg-slate-800/60 border border-slate-700/50 flex items-center justify-center">
                        <svg class="w-10 h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                      </div>
                      <div>
                        <p class="text-base font-bold text-white">Belum ada transaksi</p>
                        <p class="text-xs text-slate-500 mt-1">Tidak ada transaksi pada periode ini.</p>
                      </div>
                    </div>
                  </td>
                </tr>
                <tr v-for="(t, i) in transaksi" :key="t.id" class="hover:bg-slate-800/30 transition-colors group">
                  <td class="px-4 py-3 text-slate-400 text-xs">{{ i + 1 }}</td>
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <div class="w-8 h-8 rounded-lg bg-slate-800/60 border border-slate-700/50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                      </div>
                      <span class="font-mono text-[11px] font-bold text-white break-words leading-tight">{{ t.no_invoice }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <span class="font-semibold text-slate-200 capitalize text-xs">{{ t.jenis_kendaraan }}</span>
                  </td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-1 rounded-md bg-slate-800/60 border border-slate-700/50 font-mono text-[10px] font-bold text-white break-all">
                      {{ t.plat_nomor || '—' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-slate-300 text-[11px] break-words leading-tight">{{ formatTgl(t.waktu_masuk) }}</td>
                  <td class="px-4 py-3 text-slate-300 text-[11px] break-words leading-tight">{{ formatTgl(t.waktu_keluar) }}</td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-200 whitespace-nowrap">
                      <svg class="w-3 h-3 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                      {{ formatMenit(t.total_menit) }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <span
                      class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-semibold whitespace-nowrap"
                      :class="t.metode === 'member' ? 'bg-blue-500/15 border border-blue-400/30 text-blue-300' : 'bg-slate-700/30 border border-slate-600/50 text-slate-300'"
                    >
                      <svg v-if="t.metode === 'member'" class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
                      <svg v-else class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/></svg>
                      {{ t.metode }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <span class="font-bold text-blue-300 text-xs whitespace-nowrap">{{ formatRp(t.total_harga) }}</span>
                  </td>
                </tr>
              </tbody>
              <tfoot v-if="transaksi.length > 0" class="bg-slate-950/40 font-bold border-t border-slate-700/50">
                <tr>
                  <td colspan="8" class="py-3.5 px-4 text-right text-blue-300 font-black">TOTAL PENDAPATAN</td>
                  <td class="py-3.5 px-4 text-right text-blue-300 text-base font-black">{{ formatRp(stats.pendapatan) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>


        <!-- TABEL RIWAYAT PERPANJANG MEMBER -->
        <div v-else-if="activeTab === 'perpanjang'" class="bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
          <div class="px-6 py-5 border-b border-slate-700/50 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-400/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              </div>
              <div>
                <h3 class="text-lg font-bold text-white">Riwayat Perpanjang Member</h3>
                <p class="text-xs text-slate-400">Menampilkan {{ renewals.length }} data perpanjangan</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500/15 border border-emerald-400/30 text-emerald-300 text-xs font-semibold">Total: {{ formatRp(renewalStats.nominal) }}</span>
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500/15 border border-emerald-400/30 text-emerald-300 text-xs font-semibold">{{ renewals.length }} Records</span>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-950/40">
                <tr>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">#</th>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">No. Member</th>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Nama Member</th>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Instansi</th>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Masa Aktif Lama</th>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Masa Aktif Baru</th>
                  <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Tanggal Perpanjang</th>
                  <th class="text-right px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Nominal</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/50">
                <tr v-if="loadingRenewals">
                  <td colspan="8" class="px-6 py-20 text-center">
                    <div class="flex flex-col items-center gap-4">
                      <svg class="w-10 h-10 text-slate-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                      <p class="text-base font-bold text-white">Memuat data perpanjang...</p>
                    </div>
                  </td>
                </tr>
                <tr v-else-if="renewals.length === 0">
                  <td colspan="8" class="px-6 py-20 text-center">
                    <div class="flex flex-col items-center gap-4">
                      <svg class="w-10 h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                      <div>
                        <p class="text-base font-bold text-white">Belum ada riwayat perpanjang</p>
                        <p class="text-xs text-slate-500 mt-1">Tidak ada perpanjangan pada periode ini.</p>
                      </div>
                    </div>
                  </td>
                </tr>
                <tr v-for="(r, i) in renewals" :key="r.id" class="hover:bg-slate-800/30 transition-colors">
                  <td class="px-4 py-3 text-slate-400 text-xs">{{ i + 1 }}</td>
                  <td class="px-4 py-3"><span class="font-mono text-xs font-bold text-emerald-300">{{ r.no_member }}</span></td>
                  <td class="px-4 py-3"><span class="font-semibold text-white text-sm">{{ r.nama_member }}</span></td>
                  <td class="px-4 py-3 text-slate-300 text-xs">{{ r.instansi || '&#8212;' }}</td>
                  <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-1 rounded-md bg-red-500/10 border border-red-500/30 text-red-300 text-[10px] font-semibold">{{ r.masa_aktif_lama ? new Date(r.masa_aktif_lama).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '&#8212;' }}</span></td>
                  <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-1 rounded-md bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-[10px] font-semibold">{{ r.masa_aktif_baru ? new Date(r.masa_aktif_baru).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '&#8212;' }}</span></td>
                  <td class="px-4 py-3 text-slate-300 text-[11px]">{{ formatTgl(r.created_at) }}</td>
                  <td class="px-4 py-3 text-right"><span class="font-bold text-emerald-300 text-xs whitespace-nowrap">{{ formatRp(r.nominal_bayar) }}</span></td>
                </tr>
              </tbody>
              <tfoot v-if="renewals.length > 0" class="bg-slate-950/40 font-bold border-t border-slate-700/50">
                <tr>
                  <td colspan="7" class="py-3.5 px-4 text-right text-emerald-300 font-black">TOTAL NOMINAL PERPANJANG</td>
                  <td class="py-3.5 px-4 text-right text-emerald-300 text-base font-black">{{ formatRp(renewalStats.nominal) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
</template>