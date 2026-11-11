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

onMounted(() => {
  nama.value = localStorage.getItem('user_name') || ''
  fetchData()
})

const fetchData = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filter.tanggal_dari) params.append('tanggal_dari', filter.tanggal_dari)
    if (filter.tanggal_sampai) params.append('tanggal_sampai', filter.tanggal_sampai)
    transaksi.value = await $fetch(`/transaksi?${params}`, {
      baseURL: config.public.apiBase,
      headers: { Accept: 'application/json' },
    })
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

const totalPendapatan = computed(() => filtered.value.reduce((s, t) => s + t.total_harga, 0))

const logout = () => { localStorage.clear(); router.push('/login') }
const formatRp = (n) => 'Rp ' + (n || 0).toLocaleString('id-ID')
const formatTgl = (d) => d ? new Date(d).toLocaleString('id-ID') : '—'
const formatMenit = (m) => {
  if (!m) return '—'
  const h = Math.floor(m / 60); const min = m % 60
  return h > 0 ? `${h}j ${min}m` : `${min}m`
}
</script>

<template>
  <div>

    <!-- MAIN -->
    <div class="p-8">
      <h1 class="text-2xl font-black text-slate-100 mb-1">Riwayat Transaksi</h1>
      <p class="text-slate-300 text-sm mb-6">Semua transaksi pembayaran parkir</p>

      <!-- Filter -->
      <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-sm border border-white/10 p-5 mb-5 flex flex-wrap gap-4 items-end">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Dari Tanggal</label>
          <input v-model="filter.tanggal_dari" type="date" class="px-3 py-2 border border-white/15 rounded-xl text-sm outline-none focus:border-white/50" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Sampai Tanggal</label>
          <input v-model="filter.tanggal_sampai" type="date" class="px-3 py-2 border border-white/15 rounded-xl text-sm outline-none focus:border-white/50" />
        </div>
        <div class="flex-1 min-w-48">
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Cari (Plat / Invoice / Nama)</label>
          <input v-model="search" type="text" placeholder="Cari..." class="w-full px-3 py-2 border border-white/15 rounded-xl text-sm outline-none focus:border-white/50" />
        </div>
        <button @click="fetchData" :disabled="loading" class="bg-white hover:bg-slate-200 disabled:opacity-50 text-slate-900 font-semibold px-5 py-2 rounded-xl text-sm transition-all">
          {{ loading ? '⏳' : '🔍 Filter' }}
        </button>
      </div>

      <!-- Summary -->
      <div class="grid grid-cols-3 gap-4 mb-5">
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/10 p-4 text-center shadow-sm">
          <p class="text-xs text-slate-400 uppercase tracking-wide">Total Transaksi</p>
          <p class="text-2xl font-black text-slate-100 mt-1">{{ filtered.length }}</p>
        </div>
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/10 p-4 text-center shadow-sm">
          <p class="text-xs text-slate-400 uppercase tracking-wide">Total Pendapatan</p>
          <p class="text-xl font-black text-emerald-300 mt-1">{{ formatRp(totalPendapatan) }}</p>
        </div>
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/10 p-4 text-center shadow-sm">
          <p class="text-xs text-slate-400 uppercase tracking-wide">Member</p>
          <p class="text-2xl font-black text-sky-300 mt-1">{{ filtered.filter(t => t.jenis_member === 'member').length }}</p>
        </div>
      </div>

      <!-- Tabel -->
      <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-sm border border-white/10 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-white/5 border-b border-white/10">
              <tr>
                <th class="text-left px-4 py-3 text-xs text-slate-400 font-semibold uppercase tracking-wide">Invoice</th>
                <th class="text-left px-4 py-3 text-xs text-slate-400 font-semibold uppercase tracking-wide">Kendaraan</th>
                <th class="text-left px-4 py-3 text-xs text-slate-400 font-semibold uppercase tracking-wide">Plat</th>
                <th class="text-left px-4 py-3 text-xs text-slate-400 font-semibold uppercase tracking-wide">Masuk</th>
                <th class="text-left px-4 py-3 text-xs text-slate-400 font-semibold uppercase tracking-wide">Keluar</th>
                <th class="text-left px-4 py-3 text-xs text-slate-400 font-semibold uppercase tracking-wide">Durasi</th>
                <th class="text-left px-4 py-3 text-xs text-slate-400 font-semibold uppercase tracking-wide">Metode</th>
                <th class="text-right px-4 py-3 text-xs text-slate-400 font-semibold uppercase tracking-wide">Total</th>
                <th class="text-left px-4 py-3 text-xs text-slate-400 font-semibold uppercase tracking-wide">Petugas</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
              <tr v-for="t in filtered" :key="t.id" class="hover:bg-white/5 transition-colors">
                <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-200">{{ t.no_invoice }}</td>
                <td class="px-4 py-3">
                  <span class="flex items-center gap-1 font-medium text-slate-200">
                    {{ t.jenis_kendaraan === 'motor' ? '🏍️' : '🚗' }} {{ t.jenis_kendaraan }}
                  </span>
                </td>
                <td class="px-4 py-3 font-mono font-bold text-slate-100">{{ t.plat_nomor || '—' }}</td>
                <td class="px-4 py-3 text-slate-300 text-xs">{{ formatTgl(t.waktu_masuk) }}</td>
                <td class="px-4 py-3 text-slate-300 text-xs">{{ formatTgl(t.waktu_keluar) }}</td>
                <td class="px-4 py-3 text-slate-300 text-xs">{{ formatMenit(t.total_menit) }}</td>
                <td class="px-4 py-3">
                  <span :class="t.metode === 'member' ? 'bg-emerald-400/20 text-emerald-300' : 'bg-orange-400/20 text-orange-300'"
                        class="text-xs font-semibold px-2 py-0.5 rounded-full capitalize">
                    {{ t.metode === 'member' ? '💳 Member' : '💵 Cash' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right font-bold text-slate-100">{{ formatRp(t.total_harga) }}</td>
                <td class="px-4 py-3 text-slate-300 text-xs">{{ t.petugas }}</td>
              </tr>
              <tr v-if="filtered.length === 0">
                <td colspan="9" class="px-4 py-10 text-center text-slate-400">
                  {{ loading ? '⏳ Memuat...' : 'Tidak ada data transaksi' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

