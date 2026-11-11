<script setup>
definePageMeta({ layout: 'petugas', middleware: 'auth-petugas' })

const config = useRuntimeConfig()
const router = useRouter()
const nama = ref('')

const filter = reactive({
  tanggal_dari: '',
  tanggal_sampai: '',
})
const transaksi = ref([])
const loading = ref(false)
const search = ref('')

// Pagination
const currentPage = ref(1)
const itemsPerPage = 8

onMounted(() => {
  nama.value = localStorage.getItem('user_name') || ''
  fetchData()
})

const fetchData = async () => {
  if (!import.meta.client) return
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filter.tanggal_dari) params.append('tanggal_dari', filter.tanggal_dari)
    if (filter.tanggal_sampai) params.append('tanggal_sampai', filter.tanggal_sampai)
    transaksi.value = await $fetch(`/transaksi?${params}`, {
      baseURL: config.public.apiBase,
      headers: { Accept: 'application/json' },
    })
    currentPage.value = 1
  } finally {
    loading.value = false
  }
}

const filtered = computed(() => {
  if (!search.value) return transaksi.value
  const q = search.value.toLowerCase()
  return transaksi.value.filter(t =>
    t.no_invoice?.toLowerCase().includes(q) ||
    t.plat_nomor?.toLowerCase().includes(q) ||
    t.member_nama?.toLowerCase().includes(q)
  )
})

// Reset ke halaman 1 saat hasil filter berubah
watch(filtered, () => { currentPage.value = 1 })

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / itemsPerPage)))

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filtered.value.slice(start, start + itemsPerPage)
})

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) currentPage.value = page
}

const totalPendapatan = computed(() => filtered.value.reduce((s, t) => s + t.total_harga, 0))
const memberCount = computed(() => filtered.value.filter(t => t.jenis_member === 'member').length)
const cashCount = computed(() => filtered.value.filter(t => t.jenis_member !== 'member').length)

const logout = () => { localStorage.clear(); router.push('/login') }
const formatRp = (n) => 'Rp ' + (n || 0).toLocaleString('id-ID')
const formatTgl = (d) => d ? new Date(d).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'
const formatMenit = (m) => {
  if (!m) return '—'
  const h = Math.floor(m / 60); const min = m % 60
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
        <div class="mb-8">
          <h1 class="text-3xl font-black text-white tracking-tight">Riwayat Transaksi</h1>
          <p class="text-slate-400 text-base mt-2">Kelola dan pantau seluruh transaksi pembayaran parkir</p>
        </div>

        <!-- Filter Card -->
        <div class="bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-6 mb-6">
          <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-blue-500/15 border border-blue-400/30 flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            </div>
            <div>
              <h3 class="text-lg font-bold text-white">Filter & Pencarian</h3>
              <p class="text-xs text-slate-400">Saring data berdasarkan tanggal atau kata kunci</p>
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
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wide">Cari Data</label>
              <div class="relative">
                <svg class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input v-model="search" type="text" placeholder="Invoice / Plat / Nama..." class="w-full pl-10 pr-3 py-3 bg-slate-950/60 border border-slate-700/50 rounded-xl text-sm text-slate-200 placeholder:text-slate-600 outline-none focus:border-blue-400/60 focus:bg-slate-950 transition-all" />
              </div>
            </div>
            <button @click="fetchData" :disabled="loading" class="h-[46px] bg-blue-500 hover:bg-blue-400 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold px-5 rounded-xl text-sm transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2">
              <svg v-if="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
              <span>{{ loading ? 'Memuat...' : 'Terapkan Filter' }}</span>
            </button>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="relative overflow-hidden bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Total Transaksi</p>
                <p class="text-4xl font-black text-white mt-2">{{ filtered.length }}</p>
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
                <p class="text-3xl font-black text-white mt-2">{{ formatRp(totalPendapatan) }}</p>
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
                <p class="text-4xl font-black text-white mt-2">{{ memberCount }}</p>
                <p class="text-xs text-slate-500 mt-2">pengguna member aktif</p>
              </div>
              <div class="w-14 h-14 rounded-xl bg-blue-500/15 border border-blue-400/30 flex items-center justify-center">
                <svg class="w-7 h-7 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
              </div>
            </div>
            <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl"></div>
          </div>
        </div>

        <!-- Table Card -->
        <div class="bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
          <div class="px-6 py-5 border-b border-slate-700/50 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-500/15 border border-blue-400/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
              </div>
              <div>
                <h3 class="text-lg font-bold text-white">Daftar Transaksi</h3>
                <p class="text-xs text-slate-400">Menampilkan {{ filtered.length }} data transaksi</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-500/15 border border-blue-400/30 text-blue-300 text-xs font-semibold">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"/></svg>
                {{ filtered.length }} Records
              </span>
            </div>
          </div>

          <!-- Tabel -->
          <table class="w-full text-sm" style="table-layout: fixed;">
            <colgroup>
              <col style="width: 13%" />
              <col style="width: 8%" />
              <col style="width: 11%" />
              <col style="width: 14%" />
              <col style="width: 14%" />
              <col style="width: 8%" />
              <col style="width: 10%" />
              <col style="width: 10%" />
              <col style="width: 12%" />
            </colgroup>
            <thead class="bg-slate-950/40">
              <tr>
                <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Invoice</th>
                <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Kendaraan</th>
                <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Plat Nomor</th>
                <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Waktu Masuk</th>
                <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Waktu Keluar</th>
                <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Durasi</th>
                <th class="text-left px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Metode</th>
                <th class="text-right px-4 py-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total</th>
                <th class="text-left px-4 py-3 pr-8 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Petugas</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/50">
              <tr v-for="t in paginatedData" :key="t.id" class="hover:bg-slate-800/30 transition-colors group">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-slate-800/60 border border-slate-700/50 flex items-center justify-center flex-shrink-0">
                      <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <span class="font-mono text-[11px] font-bold text-white break-words leading-tight">{{ t.no_invoice }}</span>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <!-- Tanpa icon, langsung tulisan -->
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
                  <span v-if="t.metode === 'member'" class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-blue-500/15 border border-blue-400/30 text-blue-300 text-[10px] font-semibold whitespace-nowrap">
                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
                    Member
                  </span>
                  <span v-else class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-slate-700/30 border border-slate-600/50 text-slate-300 text-[10px] font-semibold whitespace-nowrap">
                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/></svg>
                    Cash
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <span class="font-bold text-blue-300 text-xs whitespace-nowrap">{{ formatRp(t.total_harga) }}</span>
                </td>
                <td class="px-4 py-3 pr-8">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-blue-500/20 border border-blue-400/30 flex items-center justify-center text-[10px] font-bold text-blue-200 flex-shrink-0">
                      {{ (t.petugas || 'P').charAt(0).toUpperCase() }}
                    </div>
                    <span class="text-[11px] text-slate-300 font-medium capitalize break-words leading-tight">{{ t.petugas || '—' }}</span>
                  </div>
                </td>
              </tr>
              <tr v-if="paginatedData.length === 0">
                <td colspan="9" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center gap-4">
                    <div class="w-20 h-20 rounded-2xl bg-slate-800/60 border border-slate-700/50 flex items-center justify-center">
                      <svg class="w-10 h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div>
                      <p class="text-base font-bold text-white">{{ loading ? 'Memuat data...' : 'Belum ada transaksi' }}</p>
                      <p class="text-xs text-slate-500 mt-1">{{ loading ? 'Mohon tunggu sebentar' : 'Coba ubah filter atau kata kunci pencarian' }}</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div class="px-6 py-4 border-t border-slate-700/50 bg-slate-950/40 flex items-center justify-between">
            <div class="flex items-center gap-3 text-xs text-slate-400">
              <span>Menampilkan</span>
              <span class="font-bold text-white">{{ paginatedData.length }}</span>
              <span>dari</span>
              <span class="font-bold text-white">{{ filtered.length }}</span>
              <span>data</span>
              <span class="text-slate-600">•</span>
              <span>Halaman</span>
              <span class="font-bold text-white">{{ currentPage }}</span>
              <span>dari</span>
              <span class="font-bold text-white">{{ totalPages }}</span>
            </div>

            <div class="flex items-center gap-1.5">
              <!-- Prev -->
              <button
                @click="goToPage(currentPage - 1)"
                :disabled="currentPage === 1"
                class="h-8 px-3 rounded-lg bg-slate-800/60 border border-slate-700/50 text-slate-300 text-xs font-semibold hover:bg-blue-500/20 hover:border-blue-400/30 hover:text-blue-300 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-slate-800/60 disabled:hover:border-slate-700/50 disabled:hover:text-slate-300 transition-all flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Prev
              </button>

              <!-- Page numbers -->
              <button
                v-for="page in totalPages"
                :key="page"
                @click="goToPage(page)"
                :class="page === currentPage ? 'bg-blue-500 border-blue-400 text-white shadow-lg shadow-blue-500/20' : 'bg-slate-800/60 border-slate-700/50 text-slate-300 hover:bg-blue-500/20 hover:border-blue-400/30 hover:text-blue-300'"
                class="h-8 w-8 rounded-lg border text-xs font-bold transition-all">
                {{ page }}
              </button>

              <!-- Next -->
              <button
                @click="goToPage(currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="h-8 px-3 rounded-lg bg-slate-800/60 border border-slate-700/50 text-slate-300 text-xs font-semibold hover:bg-blue-500/20 hover:border-blue-400/30 hover:text-blue-300 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-slate-800/60 disabled:hover:border-slate-700/50 disabled:hover:text-slate-300 transition-all flex items-center gap-1.5">
                Next
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
</template>