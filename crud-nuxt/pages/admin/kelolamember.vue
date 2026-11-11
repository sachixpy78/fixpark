<script setup>
definePageMeta({ layout: 'admin', middleware: 'auth-admin' })

import { ref, reactive, computed, onMounted } from 'vue'

const config = useRuntimeConfig()
const router = useRouter()

const members           = ref([])
const loading           = ref(false)
const search            = ref('')
const showModal         = ref(false)
const editMode          = ref(false)
const selectedId        = ref(null)
const saving            = ref(false)
const kirimLoading      = ref(null)
const perpanjangLoading = ref(null)

// Detail modal
const showDetailModal = ref(false)
const detailData      = ref(null)

// Modal Perpanjang Member dengan Form Input Nominal
const showPerpanjangModal = ref(false)
const perpanjangTarget    = ref(null)
const nominalBayar        = ref('')
const biayaPerpanjang     = 150000

const nominalNumber = computed(() => Number(nominalBayar.value) || 0)
const kembalian     = computed(() => Math.max(0, nominalNumber.value - biayaPerpanjang))
const sisaKurang    = computed(() => Math.max(0, biayaPerpanjang - nominalNumber.value))
const isNominalCukup = computed(() => nominalNumber.value >= biayaPerpanjang)

const formatRupiah = (val) => 'Rp ' + Number(val || 0).toLocaleString('id-ID')

const bukaModalPerpanjang = (m) => {
  perpanjangTarget.value = m
  nominalBayar.value = ''
  showPerpanjangModal.value = true
}

const setNominal = (val) => {
  nominalBayar.value = val
}

const form = reactive({
  nama: '',
  instansi: '',
  email: '',
  telepon: '',
  motor_1: '',
  motor_2: '',
  motor_3: '',
  mobil_1: '',
  mobil_2: '',
  mobil_3: '',
})
const formError = ref('')

const getToken = () => (import.meta.client ? localStorage.getItem('auth_token') ?? '' : '')

const fetchMembers = async () => {
  if (!import.meta.client) return
  loading.value = true
  try {
    members.value = await $fetch('/member', {
      baseURL: config.public.apiBase,
      headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
    })
  } catch (err) {
    console.error('Gagal mengambil data member:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchMembers()
})

const filtered = computed(() => {
  if (!search.value) return members.value
  const q = search.value.toLowerCase()
  return members.value.filter(m =>
    m.nama?.toLowerCase().includes(q) ||
    m.no_member?.toLowerCase().includes(q) ||
    m.instansi?.toLowerCase().includes(q) ||
    m.email?.toLowerCase().includes(q) ||
    m.motor_1?.toLowerCase().includes(q) ||
    m.motor_2?.toLowerCase().includes(q) ||
    m.motor_3?.toLowerCase().includes(q) ||
    m.mobil_1?.toLowerCase().includes(q) ||
    m.mobil_2?.toLowerCase().includes(q) ||
    m.mobil_3?.toLowerCase().includes(q) ||
    m.plat_1?.toLowerCase().includes(q) ||
    m.plat_2?.toLowerCase().includes(q) ||
    m.plat_3?.toLowerCase().includes(q)
  )
})

const openTambah = () => {
  editMode.value = false
  selectedId.value = null
  Object.assign(form, {
    nama: '',
    instansi: '',
    email: '',
    telepon: '',
    motor_1: '',
    motor_2: '',
    motor_3: '',
    mobil_1: '',
    mobil_2: '',
    mobil_3: '',
  })
  formError.value = ''
  showModal.value = true
}

const openEdit = (m) => {
  editMode.value = true
  selectedId.value = m.id
  Object.assign(form, {
    nama: m.nama,
    instansi: m.instansi,
    email: m.email,
    telepon: m.telepon || '',
    motor_1: m.motor_1 || m.plat_1 || '',
    motor_2: m.motor_2 || m.plat_2 || '',
    motor_3: m.motor_3 || m.plat_3 || '',
    mobil_1: m.mobil_1 || '',
    mobil_2: m.mobil_2 || '',
    mobil_3: m.mobil_3 || '',
  })
  formError.value = ''
  showModal.value = true
}

const openDetail = (m) => {
  detailData.value = m
  showDetailModal.value = true
}

const simpan = async () => {
  if (!form.nama || !form.instansi || !form.email) {
    formError.value = 'Nama, instansi, dan email wajib diisi!'
    return
  }
  const hasMotor = !!(form.motor_1?.trim() || form.motor_2?.trim() || form.motor_3?.trim())
  const hasMobil = !!(form.mobil_1?.trim() || form.mobil_2?.trim() || form.mobil_3?.trim())
  if (!hasMotor && !hasMobil) {
    formError.value = 'Minimal isi satu plat nomor (plat motor atau plat mobil)!'
    return
  }
  try {
    saving.value = true
    formError.value = ''
    if (editMode.value) {
      await $fetch(`/member/${selectedId.value}`, {
        baseURL: config.public.apiBase,
        method: 'PUT',
        headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
        body: form,
      })
    } else {
      await $fetch('/member', {
        baseURL: config.public.apiBase,
        method: 'POST',
        headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
        body: form,
      })
    }
    showModal.value = false
    await fetchMembers()
    if (!editMode.value) {
      alert('Pendaftaran berhasil! Kartu member telah dikirim ke email.')
    }
  } catch (err) {
    const errs = err?.data?.errors
    formError.value = errs ? Object.values(errs).flat().join(' ') : (err?.data?.message || 'Gagal menyimpan data')
  } finally {
    saving.value = false
  }
}

const prosesPerpanjang = async () => {
  if (!perpanjangTarget.value || !isNominalCukup.value) return

  const m = perpanjangTarget.value
  try {
    perpanjangLoading.value = m.id
    const res = await $fetch(`/member/${m.id}/perpanjang`, {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
    })
    
    alert(
      `Pembayaran Cash Berhasil Diterima!\n\n` +
      `Member: ${m.nama} (${m.no_member})\n` +
      `Uang Diterima: ${formatRupiah(nominalNumber.value)}\n` +
      `Kembalian: ${formatRupiah(kembalian.value)}\n\n` +
      `Masa aktif berhasil diperpanjang 30 hari ke depan dan kartu member baru telah dikirim ke email ${m.email}.`
    )
    showPerpanjangModal.value = false
    await fetchMembers()
  } catch (err) {
    alert(err?.data?.message || 'Gagal memperpanjang member')
  } finally {
    perpanjangLoading.value = null
  }
}

const hapus = async (m) => {
  if (!confirm(`Hapus akun member "${m.nama}" (${m.no_member})?`)) return
  try {
    await $fetch(`/member/${m.id}`, {
      baseURL: config.public.apiBase,
      method: 'DELETE',
      headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
    })
    await fetchMembers()
  } catch (err) {
    alert(err?.data?.message || 'Gagal menghapus')
  }
}

const kirimUlang = async (m) => {
  if (!confirm(`Kirim ulang kartu member ke ${m.email}?`)) return
  try {
    kirimLoading.value = m.id
    await $fetch(`/member/${m.id}/kirim-ulang`, {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
    })
    alert('Kartu member berhasil dikirim ulang ke ' + m.email)
  } catch (err) {
    alert(err?.data?.message || 'Gagal mengirim')
  } finally {
    kirimLoading.value = null
  }
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

const formatTgl = (d) =>
  d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'

const formatWaktuLengkap = (d) =>
  d ? new Date(d).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-'
</script>

<template>
  <div>

    <!-- KONTEN UTAMA -->
    <div class="flex flex-col">
      <!-- Header -->
      <header class="bg-slate-800/80 backdrop-blur-xl border-b border-white/10 px-8 py-5 flex items-center justify-between shadow-xs">
        <div>
          <h1 class="text-xl font-bold text-blue-200">Kelola Member Parkir</h1>
          <p class="text-sm text-slate-300">Manajemen data member & aktivasi kartu berlangganan</p>
        </div>
        <button @click="openTambah"
          class="flex items-center gap-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white font-semibold px-5 py-2.5 rounded-xl shadow-lg shadow-blue-900/40 transition hover:shadow-blue-500/50 hover:scale-[1.02] active:scale-[0.98]">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Tambah Member
        </button>
      </header>

      <div class="p-8 space-y-6">

        <!-- Search Bar -->
        <div class="relative max-w-md">
          <input v-model="search" type="text" placeholder="Cari nama, no member, plat, atau email..."
            class="w-full pl-10 pr-4 py-3 bg-slate-700/50 border-2 border-slate-600 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none text-white placeholder:text-slate-500 transition-all shadow-sm"/>
          <svg class="w-5 h-5 absolute left-3.5 top-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>

        <!-- Tabel Member -->
        <div class="bg-slate-800/60 backdrop-blur-xl rounded-2xl shadow-lg border border-white/10 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
              <thead>
                <tr class="text-slate-100 text-xs uppercase tracking-wider font-semibold bg-slate-700/50 border-b border-white/10">
                  <th class="py-4 px-6">No. Member</th>
                  <th class="py-4 px-5">Nama & Instansi</th>
                  <th class="py-4 px-5">Plat Terdaftar (Motor / Mobil)</th>
                  <th class="py-4 px-5">Waktu Buat</th>
                  <th class="py-4 px-5">Masa Aktif</th>
                  <th class="py-4 px-5 text-center">Status</th>
                  <th class="py-4 px-6 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5 text-slate-200">
                <tr v-if="loading">
                  <td colspan="7" class="py-12 text-center text-blue-300 font-medium flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Memuat data member...
                  </td>
                </tr>
                <tr v-else-if="filtered.length === 0">
                  <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                    <div class="flex flex-col items-center gap-2">
                      <svg class="w-12 h-12 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                      </svg>
                      <span>Tidak ada member ditemukan.</span>
                    </div>
                  </td>
                </tr>
                <tr v-for="m in filtered" :key="m.id" 
                  class="hover:bg-blue-500/10 transition border-b border-white/5 last:border-b-0">
                  <td class="py-4 px-6 font-mono font-bold text-cyan-300 text-xs">
                    {{ m.no_member }}
                  </td>
                  <td class="py-4 px-5">
                    <div class="font-bold text-slate-100">{{ m.nama }}</div>
                    <div class="text-xs text-slate-400">{{ m.instansi }}</div>
                  </td>
                  <td class="py-4 px-5">
                    <div class="flex flex-col gap-1.5 max-w-[200px]">
                      <!-- Motor section -->
                      <div v-if="m.motor_1 || m.motor_2 || m.motor_3 || (!m.mobil_1 && !m.mobil_2 && !m.mobil_3 && (m.plat_1 || m.plat_2 || m.plat_3))" class="flex flex-wrap items-center gap-1">
                        <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">Motor:</span>
                        <span v-for="p in [m.motor_1 || m.plat_1, m.motor_2 || m.plat_2, m.motor_3 || m.plat_3].filter(Boolean)" :key="'mot-'+p"
                          class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                          {{ p }}
                        </span>
                      </div>
                      <!-- Mobil section -->
                      <div v-if="m.mobil_1 || m.mobil_2 || m.mobil_3" class="flex flex-wrap items-center gap-1">
                        <span class="text-[10px] font-bold text-blue-400 uppercase tracking-wider bg-blue-500/10 px-1.5 py-0.5 rounded border border-blue-500/20">Mobil:</span>
                        <span v-for="p in [m.mobil_1, m.mobil_2, m.mobil_3].filter(Boolean)" :key="'mob-'+p"
                          class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">
                          {{ p }}
                        </span>
                      </div>
                      <span v-if="!m.motor_1 && !m.motor_2 && !m.motor_3 && !m.mobil_1 && !m.mobil_2 && !m.mobil_3 && !m.plat_1 && !m.plat_2 && !m.plat_3" class="text-xs text-slate-500 italic">
                        Belum diisi
                      </span>
                    </div>
                  </td>
                  <td class="py-4 px-5 text-xs text-slate-400">
                    {{ formatWaktuLengkap(m.created_at) }}
                  </td>
                  <td class="py-4 px-5 text-xs">
                    <div class="font-medium text-slate-200">{{ formatTgl(m.masa_aktif) }}</div>
                    <div v-if="m.is_expired || m.status_label === 'expired'" class="text-[10px] text-rose-400 font-bold mt-0.5">
                      Lewat 30 hari
                    </div>
                    <div v-else class="text-[10px] text-emerald-400 font-semibold mt-0.5">
                      Aktif 30 hari
                    </div>
                  </td>
                  <td class="py-4 px-5 text-center">
                    <span v-if="m.is_expired || m.status_label === 'expired'"
                      class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                      Expired
                    </span>
                    <span v-else
                      class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                      Aktif
                    </span>
                  </td>
                  <td class="py-4 px-6 text-center">
                    <div class="inline-flex items-center gap-1.5">
                      <!-- Detail -->
                      <button @click="openDetail(m)"
                        class="inline-flex items-center gap-1.5 text-xs bg-slate-700/50 hover:bg-slate-700 text-slate-200 font-medium px-3 py-2 rounded-lg transition border border-white/10"
                        title="Detail Member">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Detail
                      </button>

                      <!-- Perpanjang (Jika expired) -->
                      <button v-if="m.is_expired || m.status_label === 'expired'"
                        @click="bukaModalPerpanjang(m)"
                        :disabled="perpanjangLoading === m.id"
                        class="inline-flex items-center gap-1.5 text-xs bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white font-bold px-3 py-2 rounded-lg transition shadow-lg shadow-emerald-900/30 disabled:opacity-60 hover:scale-105 active:scale-95"
                        title="Perpanjang Kartu Member">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Perpanjang
                      </button>

                      <!-- Edit -->
                      <button @click="openEdit(m)"
                        class="inline-flex items-center gap-1.5 text-xs bg-blue-500/20 hover:bg-blue-500/30 text-blue-300 font-medium px-3 py-2 rounded-lg transition border border-blue-500/30"
                        title="Edit Data">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                      </button>

                      <!-- Kirim Ulang -->
                      <button @click="kirimUlang(m)" :disabled="kirimLoading === m.id"
                        class="inline-flex items-center gap-1.5 text-xs bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 font-medium px-3 py-2 rounded-lg transition border border-amber-500/30 disabled:opacity-50"
                        title="Kirim Ulang Kartu ke Email">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                      </button>

                      <!-- Hapus -->
                      <button @click="hapus(m)"
                        class="inline-flex items-center gap-1.5 text-xs bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 font-medium px-3 py-2 rounded-lg transition border border-rose-500/30"
                        title="Hapus Member">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>

    <!-- MODAL DETAIL MEMBER -->
    <Teleport to="body">
      <div v-if="showDetailModal && detailData"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click.self="showDetailModal = false">
        <div class="bg-slate-800/90 backdrop-blur-2xl rounded-3xl p-7 max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto border border-white/10">
          <div class="flex items-center justify-between mb-6 border-b border-white/10 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center shadow-lg shadow-blue-500/30">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                </svg>
              </div>
              <div>
                <h2 class="text-lg font-bold text-white">Detail Informasi Member</h2>
                <p class="text-xs text-cyan-400 font-mono">{{ detailData.no_member }}</p>
              </div>
            </div>
            <button @click="showDetailModal = false" class="text-slate-400 hover:text-white transition-colors p-1 hover:bg-white/10 rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div class="space-y-4 mb-6 text-sm">
            <div class="grid grid-cols-2 gap-3 bg-slate-700/30 p-4 rounded-xl border border-white/10">
              <div>
                <div class="text-xs text-slate-400 mb-1">Nama Lengkap:</div>
                <div class="font-bold text-slate-100">{{ detailData.nama }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-400 mb-1">Instansi:</div>
                <div class="font-bold text-slate-100">{{ detailData.instansi }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-400 mb-1">Email:</div>
                <div class="font-medium text-slate-200">{{ detailData.email }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-400 mb-1">Telepon:</div>
                <div class="font-medium text-slate-200">{{ detailData.telepon || '-' }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-400 mb-1">Waktu Buat Akun:</div>
                <div class="font-medium text-slate-200">{{ formatWaktuLengkap(detailData.created_at) }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-400 mb-1">Masa Aktif:</div>
                <div class="font-medium text-slate-200">{{ formatTgl(detailData.masa_aktif) }}</div>
              </div>
            </div>

            <!-- Slot Plat Nomor Motor & Mobil Terdaftar -->
            <div class="bg-slate-700/30 p-4 rounded-xl border border-white/10 space-y-4">
              <div>
                <div class="text-xs font-bold text-emerald-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                  </svg>
                  Plat Motor Terdaftar (Maks. 3 Slot):
                </div>
                <div class="grid grid-cols-3 gap-2.5">
                  <div class="bg-slate-800/60 p-2.5 rounded-xl border border-emerald-500/20 text-center">
                    <div class="text-[10px] text-slate-400 font-semibold mb-0.5">Motor Slot 1</div>
                    <div class="font-mono font-bold text-xs text-emerald-300">{{ detailData.motor_1 || detailData.plat_1 || '-' }}</div>
                  </div>
                  <div class="bg-slate-800/60 p-2.5 rounded-xl border border-white/10 text-center">
                    <div class="text-[10px] text-slate-400 font-semibold mb-0.5">Motor Slot 2</div>
                    <div class="font-mono font-bold text-xs text-slate-300">{{ detailData.motor_2 || detailData.plat_2 || '-' }}</div>
                  </div>
                  <div class="bg-slate-800/60 p-2.5 rounded-xl border border-white/10 text-center">
                    <div class="text-[10px] text-slate-400 font-semibold mb-0.5">Motor Slot 3</div>
                    <div class="font-mono font-bold text-xs text-slate-300">{{ detailData.motor_3 || detailData.plat_3 || '-' }}</div>
                  </div>
                </div>
              </div>

              <div>
                <div class="text-xs font-bold text-blue-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                  </svg>
                  Plat Mobil Terdaftar (Maks. 3 Slot):
                </div>
                <div class="grid grid-cols-3 gap-2.5">
                  <div class="bg-slate-800/60 p-2.5 rounded-xl border border-blue-500/20 text-center">
                    <div class="text-[10px] text-slate-400 font-semibold mb-0.5">Mobil Slot 1</div>
                    <div class="font-mono font-bold text-xs text-blue-300">{{ detailData.mobil_1 || '-' }}</div>
                  </div>
                  <div class="bg-slate-800/60 p-2.5 rounded-xl border border-white/10 text-center">
                    <div class="text-[10px] text-slate-400 font-semibold mb-0.5">Mobil Slot 2</div>
                    <div class="font-mono font-bold text-xs text-slate-300">{{ detailData.mobil_2 || '-' }}</div>
                  </div>
                  <div class="bg-slate-800/60 p-2.5 rounded-xl border border-white/10 text-center">
                    <div class="text-[10px] text-slate-400 font-semibold mb-0.5">Mobil Slot 3</div>
                    <div class="font-mono font-bold text-xs text-slate-300">{{ detailData.mobil_3 || '-' }}</div>
                  </div>
                </div>
              </div>

              <p class="text-[11px] text-slate-400 italic flex items-start gap-1.5 pt-1 border-t border-white/5">
                <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Member dapat memilih plat sesuai jenis kendaraan (Motor atau Mobil) saat mengambil tiket di kiosk.
              </p>
            </div>
          </div>

          <div class="flex justify-end gap-3">
            <button @click="kirimUlang(detailData)"
              class="border-2 border-amber-500/40 text-amber-300 hover:bg-amber-500/20 px-4 py-2.5 rounded-xl text-sm font-semibold transition">
              Kirim Ulang Kartu
            </button>
            <button @click="showDetailModal = false"
              class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition shadow-lg shadow-blue-900/40">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- MODAL TAMBAH/EDIT MEMBER - RESPONSIVE LAYOUT -->
    <Teleport to="body">
      <div v-if="showModal"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click.self="showModal = false">
        <div class="bg-slate-800/90 backdrop-blur-2xl rounded-3xl p-6 md:p-7 max-w-5xl w-full shadow-2xl border border-white/10 max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center shadow-lg shadow-blue-500/30">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-lg font-bold text-white">
                  {{ editMode ? 'Edit Member' : 'Tambah Member Baru' }}
                </h2>
                <p class="text-xs text-slate-400">Masa aktif kartu 30 hari & 3 slot plat nomor</p>
              </div>
            </div>
            <button @click="showModal = false" class="text-slate-400 hover:text-white transition-colors p-1 hover:bg-white/10 rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div v-if="!editMode"
            class="text-sm text-amber-300 bg-amber-500/20 border border-amber-500/30 rounded-xl p-4 mb-5 flex items-start gap-2">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
              <div class="font-bold">Konfirmasi Pembayaran Cash Rp 150.000</div>
              <div class="text-xs text-amber-300/90 mt-0.5">
                Member baru aktif selama <strong>30 hari</strong> sejak tanggal pendaftaran dan otomatis dikirim kartu ke email.
              </div>
            </div>
          </div>

          <div v-if="formError" class="bg-rose-500/20 border border-rose-500/30 text-rose-300 text-sm rounded-xl px-4 py-3 mb-5 flex items-start gap-2">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ formError }}
          </div>

          <!-- FORM RESPONSIVE: 1 Kolom di Mobile, 2 Kolom di Desktop -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-6">
            <!-- Kolom Kiri -->
            <div class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Nama Lengkap *</label>
                <input v-model="form.nama" type="text" placeholder="Nama pemilik kartu"
                  class="w-full px-4 py-3 bg-slate-700/50 border-2 border-slate-600 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none text-white placeholder:text-slate-500 transition-all"/>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Instansi / Perusahaan *</label>
                <input v-model="form.instansi" type="text" placeholder="Nama instansi atau perusahaan"
                  class="w-full px-4 py-3 bg-slate-700/50 border-2 border-slate-600 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none text-white placeholder:text-slate-500 transition-all"/>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Email *</label>
                <input v-model="form.email" type="email" placeholder="email@contoh.com"
                  :disabled="editMode"
                  class="w-full px-4 py-3 bg-slate-700/50 border-2 border-slate-600 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none text-white placeholder:text-slate-500 transition-all disabled:opacity-50"/>
                <p v-if="!editMode" class="text-[11px] text-slate-400 mt-1.5">Kartu member ber-QR code dikirim ke email ini</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">No. Telepon</label>
                <input v-model="form.telepon" type="tel" placeholder="08xxxxxxxx (opsional)"
                  class="w-full px-4 py-3 bg-slate-700/50 border-2 border-slate-600 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none text-white placeholder:text-slate-500 transition-all"/>
              </div>
            </div>

            <!-- Kolom Kanan - Plat Nomor Motor & Mobil -->
            <div class="bg-slate-700/30 rounded-xl p-4 md:p-5 border border-white/10 space-y-4">
              <div>
                <label class="block text-xs font-bold text-emerald-400 mb-1 flex items-center gap-1.5 uppercase tracking-wide">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                  </svg>
                  Plat Nomor Motor (Maks. 3 Slot):
                </label>
                <p class="text-[11px] text-slate-400 mb-2">Plat motor yang didaftarkan:</p>
                <div class="space-y-2">
                  <input v-model="form.motor_1" type="text" placeholder="Motor Slot 1"
                    @input="form.motor_1 = form.motor_1.toUpperCase()"
                    class="w-full px-3 py-2 bg-slate-700/50 border border-slate-600 rounded-xl text-xs font-mono uppercase focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 outline-none text-white placeholder:text-slate-500 transition-all"/>
                  <input v-model="form.motor_2" type="text" placeholder="Motor Slot 2 (opsional)"
                    @input="form.motor_2 = form.motor_2.toUpperCase()"
                    class="w-full px-3 py-2 bg-slate-700/50 border border-slate-600 rounded-xl text-xs font-mono uppercase focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 outline-none text-white placeholder:text-slate-500 transition-all"/>
                  <input v-model="form.motor_3" type="text" placeholder="Motor Slot 3 (opsional)"
                    @input="form.motor_3 = form.motor_3.toUpperCase()"
                    class="w-full px-3 py-2 bg-slate-700/50 border border-slate-600 rounded-xl text-xs font-mono uppercase focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 outline-none text-white placeholder:text-slate-500 transition-all"/>
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-blue-400 mb-1 flex items-center gap-1.5 uppercase tracking-wide">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                  </svg>
                  Plat Nomor Mobil (Maks. 3 Slot):
                </label>
                <p class="text-[11px] text-slate-400 mb-2">Plat mobil yang didaftarkan:</p>
                <div class="space-y-2">
                  <input v-model="form.mobil_1" type="text" placeholder="Mobil Slot 1"
                    @input="form.mobil_1 = form.mobil_1.toUpperCase()"
                    class="w-full px-3 py-2 bg-slate-700/50 border border-slate-600 rounded-xl text-xs font-mono uppercase focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none text-white placeholder:text-slate-500 transition-all"/>
                  <input v-model="form.mobil_2" type="text" placeholder="Mobil Slot 2 (opsional)"
                    @input="form.mobil_2 = form.mobil_2.toUpperCase()"
                    class="w-full px-3 py-2 bg-slate-700/50 border border-slate-600 rounded-xl text-xs font-mono uppercase focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none text-white placeholder:text-slate-500 transition-all"/>
                  <input v-model="form.mobil_3" type="text" placeholder="Mobil Slot 3 (opsional)"
                    @input="form.mobil_3 = form.mobil_3.toUpperCase()"
                    class="w-full px-3 py-2 bg-slate-700/50 border border-slate-600 rounded-xl text-xs font-mono uppercase focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none text-white placeholder:text-slate-500 transition-all"/>
                </div>
              </div>

              <div class="p-2 bg-slate-700/30 border border-white/5 rounded-xl text-[11px] text-slate-400 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Minimal daftarkan 1 plat (motor atau mobil).</span>
              </div>
            </div>
          </div>

          <div class="flex gap-3 pt-4 border-t border-white/10">
            <button @click="showModal = false"
              class="flex-1 py-3 border-2 border-slate-600 text-slate-300 hover:bg-slate-700/50 font-semibold rounded-xl transition">
              Batal
            </button>
            <button @click="simpan" :disabled="saving"
              class="flex-1 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 disabled:opacity-60 text-white font-bold rounded-xl transition shadow-lg shadow-blue-900/40 hover:shadow-blue-500/50">
              {{ saving ? 'Menyimpan...' : (editMode ? 'Simpan Perubahan' : 'Daftarkan Member') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- MODAL FORM PERPANJANG MEMBER (INPUT NOMINAL CASH) -->
    <Teleport to="body">
      <div v-if="showPerpanjangModal && perpanjangTarget"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click.self="showPerpanjangModal = false">
        <div class="bg-slate-800/90 backdrop-blur-2xl rounded-3xl p-7 max-w-md w-full shadow-2xl border border-white/10">
          
          <!-- Header Modal -->
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-bold text-white leading-tight">Perpanjang Masa Aktif</h3>
                <p class="text-xs text-slate-400">Input nominal pembayaran cash</p>
              </div>
            </div>
            <button @click="showPerpanjangModal = false" class="text-slate-400 hover:text-white transition-colors p-1 hover:bg-white/10 rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Info Member Card -->
          <div class="bg-slate-700/30 border border-white/10 rounded-xl p-4 mb-4">
            <div class="flex justify-between items-start mb-3">
              <div>
                <div class="text-xs text-slate-400 uppercase tracking-wider font-semibold mb-0.5">Member</div>
                <div class="font-bold text-slate-100 text-sm">{{ perpanjangTarget.nama }}</div>
                <div class="text-xs text-slate-400">{{ perpanjangTarget.instansi }}</div>
              </div>
              <span class="font-mono text-xs font-bold px-2.5 py-1 bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 rounded-lg">
                {{ perpanjangTarget.no_member }}
              </span>
            </div>
            <div class="text-[11px] text-slate-400 flex justify-between border-t border-white/10 pt-2.5 mt-2.5">
              <span>Masa Aktif Saat Ini:</span>
              <span class="font-semibold text-slate-200">{{ formatTgl(perpanjangTarget.masa_aktif) }}</span>
            </div>
          </div>

          <!-- Total Biaya -->
          <div class="bg-gradient-to-r from-blue-900/40 to-blue-800/40 border border-blue-500/30 rounded-xl p-4 mb-5 text-center">
            <div class="text-xs text-blue-300 font-semibold uppercase tracking-wider">Total Biaya Perpanjangan (30 Hari)</div>
            <div class="text-2xl font-black text-blue-200 mt-1">Rp 150.000</div>
          </div>

          <!-- Quick Select Nominal Cash -->
          <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-300 mb-2.5">Pilihan Nominal Uang Cepat:</label>
            <div class="grid grid-cols-4 gap-2">
              <button
                v-for="uang in [150000, 200000, 300000, 500000]"
                :key="uang"
                type="button"
                @click="setNominal(uang)"
                class="py-2 px-2 text-xs font-semibold rounded-xl border-2 transition text-center"
                :class="nominalNumber === uang ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white border-blue-500 shadow-lg shadow-blue-900/40' : 'bg-slate-700/50 text-slate-300 hover:bg-slate-700 border-slate-600'"
              >
                {{ uang === 150000 ? 'Uang Pas' : Number(uang/1000) + 'rb' }}
              </button>
            </div>
          </div>

          <!-- Input Nominal Cash -->
          <div class="mb-4">
            <label class="block text-xs font-bold text-slate-300 mb-2">Nominal Uang Diterima (Rp) *</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-sm">Rp</span>
              <input
                v-model.number="nominalBayar"
                type="number"
                placeholder="Contoh: 150000"
                class="w-full pl-12 pr-4 py-3.5 border-2 rounded-xl text-base font-bold outline-none transition bg-slate-700/50"
                :class="!nominalBayar ? 'border-slate-600 focus:border-blue-500 text-white' : (isNominalCukup ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/50 bg-amber-500/10 text-amber-300')"
              />
            </div>
          </div>

          <!-- Status Kembalian / Sisa Kurang -->
          <div v-if="nominalBayar" class="mb-6">
            <div v-if="isNominalCukup" class="p-4 bg-emerald-500/20 border border-emerald-500/30 rounded-xl text-emerald-300 text-xs flex justify-between items-center">
              <div>
                <div class="font-bold flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  Uang Pembayaran Cukup
                </div>
                <div class="text-[11px] text-emerald-300/90 mt-0.5">Perpanjangan dapat diproses sekarang</div>
              </div>
              <div class="text-right">
                <div class="text-[10px] uppercase font-bold text-emerald-300">Kembalian:</div>
                <div class="text-lg font-black text-emerald-300">{{ formatRupiah(kembalian) }}</div>
              </div>
            </div>

            <div v-else class="p-4 bg-rose-500/20 border border-rose-500/30 rounded-xl text-rose-300 text-xs flex justify-between items-center">
              <div>
                <div class="font-bold flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  Uang Masih Kurang
                </div>
                <div class="text-[11px] text-rose-300/90 mt-0.5">Minimal pembayaran Rp 150.000</div>
              </div>
              <div class="text-right">
                <div class="text-[10px] uppercase font-bold text-rose-300">Kurang:</div>
                <div class="text-base font-black text-rose-300">{{ formatRupiah(sisaKurang) }}</div>
              </div>
            </div>
          </div>

          <!-- Aksi Tombol -->
          <div class="flex gap-3">
            <button
              type="button"
              @click="showPerpanjangModal = false"
              class="flex-1 py-3 border-2 border-slate-600 text-slate-300 hover:bg-slate-700/50 font-semibold rounded-xl transition"
            >
              Batal
            </button>
            <button
              type="button"
              @click="prosesPerpanjang"
              :disabled="!isNominalCukup || perpanjangLoading === perpanjangTarget.id"
              class="flex-1 py-3 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 disabled:bg-slate-700/50 disabled:cursor-not-allowed text-white font-bold rounded-xl transition shadow-lg shadow-emerald-900/40 flex items-center justify-center gap-2 disabled:shadow-none"
            >
              <svg v-if="perpanjangLoading === perpanjangTarget.id" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ perpanjangLoading === perpanjangTarget.id ? 'Memproses...' : 'Konfirmasi & Perpanjang' }}</span>
            </button>
          </div>

        </div>
      </div>
    </Teleport>

  </div>
</template>