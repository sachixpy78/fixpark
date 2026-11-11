<script setup>
definePageMeta({ layout: 'petugas', middleware: 'auth-petugas' })

import { ref, reactive, computed, onMounted } from 'vue'

const config = useRuntimeConfig()
const router = useRouter()

// Aman SSR: nama user diambil saat onMounted
const namaUser = ref('')

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
  plat_1: '',
  plat_2: '',
  plat_3: '',
})
const formError = ref('')

const getToken = () => (import.meta.client ? localStorage.getItem('auth_token') ?? '' : '')

const fetchMembers = async () => {
  loading.value = true
  try {
    members.value = await $fetch('/member', {
      baseURL: config.public.apiBase,
      headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  namaUser.value = localStorage.getItem('user_name') || ''
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
    plat_1: '',
    plat_2: '',
    plat_3: '',
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
    plat_1: m.plat_1 || '',
    plat_2: m.plat_2 || '',
    plat_3: m.plat_3 || '',
  })
  formError.value = ''
  showModal.value = true
}

const openDetail = (m) => {
  detailData.value = m
  showDetailModal.value = true
}

const simpan = async () => {
  if (!form.nama || !form.instansi || !form.email || !form.plat_1) {
    formError.value = 'Nama, instansi, email, dan minimal Plat Nomor Slot 1 wajib diisi!'
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

const toggleStatus = async (m) => {
  const newStatus = m.status === 'aktif' ? 'nonaktif' : 'aktif'
  if (!confirm(`Ubah status ${m.nama} menjadi ${newStatus}?`)) return
  try {
    await $fetch(`/member/${m.id}`, {
      baseURL: config.public.apiBase,
      method: 'PUT',
      headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: { status: newStatus },
    })
    await fetchMembers()
  } catch {
    alert('Gagal mengubah status')
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


    <!-- MAIN -->
    <div class="flex flex-col">
      <!-- Header -->
      <header class="bg-white/10 backdrop-blur-xl border-b border-white/10 px-8 py-4 flex items-center justify-between shadow-sm">
        <div>
          <h1 class="text-xl font-bold text-rose-200">Kelola Member</h1>
          <p class="text-sm text-slate-300">Manajemen keanggotaan parkir (Masa aktif 30 hari & maks. 3 plat nomor)</p>
        </div>
        <button @click="openTambah"
          class="flex items-center gap-2 bg-white hover:bg-slate-200 text-slate-900 px-4 py-2 rounded-lg text-sm font-medium transition shadow">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Tambah Member
        </button>
      </header>

      <div class="p-8 space-y-5">
        <!-- Search -->
        <div class="flex items-center gap-3">
          <div class="relative flex-1 max-w-sm">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
              stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input v-model="search" type="text" placeholder="Cari nama, no member, instansi, plat..."
              class="w-full pl-9 pr-4 py-2.5 border border-white/15 rounded-lg text-sm focus:ring-2 focus:ring-white/30 outline-none bg-white/10 backdrop-blur-xl"/>
          </div>
        </div>

        <!-- Table -->
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow border border-white/10 overflow-hidden">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left bg-white/10 text-slate-100">
                <th class="px-4 py-3.5 font-semibold">No. Member</th>
                <th class="px-4 py-3.5 font-semibold">Nama & Instansi</th>
                <th class="px-4 py-3.5 font-semibold">Plat Terdaftar (3 Slot)</th>
                <th class="px-4 py-3.5 font-semibold">Waktu Buat</th>
                <th class="px-4 py-3.5 font-semibold">Masa Aktif</th>
                <th class="px-4 py-3.5 font-semibold text-center">Status</th>
                <th class="px-4 py-3.5 font-semibold text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="7" class="text-center py-12 text-rose-300 font-medium">Memuat data member...</td>
              </tr>
              <tr v-else-if="filtered.length === 0">
                <td colspan="7" class="text-center py-12 text-slate-400">
                  <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                  Belum ada member terdaftar.
                </td>
              </tr>
              <tr v-for="(m, i) in filtered" :key="m.id"
                :class="i % 2 === 0 ? 'bg-white/10 backdrop-blur-xl' : 'bg-white/5'"
                class="hover:bg-white/5 transition border-b border-white/10">
                <td class="px-4 py-3 font-mono text-xs text-rose-300 font-bold whitespace-nowrap">{{ m.no_member }}</td>
                <td class="px-4 py-3">
                  <div class="font-bold text-slate-100">{{ m.nama }}</div>
                  <div class="text-xs text-slate-300">{{ m.instansi }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex flex-wrap gap-1 font-mono text-xs">
                    <span v-if="m.plat_1" class="bg-white/10 text-slate-100 px-2 py-0.5 rounded border border-white/15 font-bold">{{ m.plat_1 }}</span>
                    <span v-if="m.plat_2" class="bg-white/10 text-slate-100 px-2 py-0.5 rounded border border-white/15 font-bold">{{ m.plat_2 }}</span>
                    <span v-if="m.plat_3" class="bg-white/10 text-slate-100 px-2 py-0.5 rounded border border-white/15 font-bold">{{ m.plat_3 }}</span>
                    <span v-if="!m.plat_1 && !m.plat_2 && !m.plat_3" class="text-slate-400 italic text-xs">-</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-slate-300 text-xs whitespace-nowrap">
                  {{ formatWaktuLengkap(m.created_at || m.tanggal_daftar) }}
                </td>
                <td class="px-4 py-3 text-xs whitespace-nowrap">
                  <div class="font-semibold text-slate-100">{{ formatTgl(m.masa_aktif) }}</div>
                  <div class="text-slate-400 text-[10px]">30 hari</div>
                </td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                  <!-- Status Label -->
                  <span v-if="m.is_expired || m.status_label === 'expired'"
                    class="bg-rose-400/15 text-rose-300 border border-white/15 text-xs font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                    Expired
                  </span>
                  <button v-else @click="toggleStatus(m)"
                    :class="m.status === 'aktif'
                      ? 'bg-emerald-400/20 text-emerald-300 hover:bg-emerald-400/30'
                      : 'bg-white/10 text-slate-300 hover:bg-white/15'"
                    class="text-xs font-semibold px-2.5 py-1 rounded-full transition capitalize inline-flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full" :class="m.status === 'aktif' ? 'bg-emerald-500' : 'bg-slate-500'"></span>
                    {{ m.status === 'aktif' ? 'Aktif' : 'Nonaktif' }}
                  </button>
                </td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">

                    <!-- DETAIL BUTTON -->
                    <button @click="openDetail(m)"
                      class="inline-flex items-center gap-1 text-xs bg-white/10 hover:bg-white/15 text-slate-200 font-semibold px-2.5 py-1.5 rounded-lg transition shadow-xs"
                      title="Lihat Detail Member">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </svg>
                      Detail
                    </button>

                    <!-- TOMBOL PERPANJANG MEMBER (Hanya jika expired) -->
                    <button v-if="m.is_expired || m.status_label === 'expired'"
                      @click="bukaModalPerpanjang(m)"
                      :disabled="perpanjangLoading === m.id"
                      class="inline-flex items-center gap-1 text-xs bg-emerald-500 hover:bg-emerald-400 text-white font-bold px-2.5 py-1.5 rounded-lg transition shadow-xs disabled:opacity-60"
                      title="Perpanjang Kartu Member (Input Nominal Pembayaran)">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                      </svg>
                      {{ perpanjangLoading === m.id ? 'Memproses...' : 'Perpanjang' }}
                    </button>

                    <!-- Edit -->
                    <button @click="openEdit(m)"
                      class="inline-flex items-center gap-1 text-xs bg-sky-400/10 hover:bg-sky-400/25 text-sky-300 font-medium px-2 py-1.5 rounded-lg transition"
                      title="Edit Data">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                      </svg>
                    </button>

                    <!-- Kirim Ulang -->
                    <button @click="kirimUlang(m)" :disabled="kirimLoading === m.id"
                      class="inline-flex items-center gap-1 text-xs bg-amber-400/10 hover:bg-amber-400/25 text-amber-300 font-medium px-2 py-1.5 rounded-lg transition disabled:opacity-50"
                      title="Kirim Ulang Kartu ke Email">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                    </button>

                    <!-- Hapus -->
                    <button @click="hapus(m)"
                      class="inline-flex items-center gap-1 text-xs bg-white/5 hover:bg-white/10 text-rose-300 font-medium px-2 py-1.5 rounded-lg transition"
                      title="Hapus Member">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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

    <!-- MODAL DETAIL MEMBER -->
    <Teleport to="body">
      <div v-if="showDetailModal && detailData"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click.self="showDetailModal = false">
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 max-w-lg w-full shadow-2xl border border-white/10">
          <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-4">
            <div>
              <h2 class="text-lg font-bold text-rose-200">Detail Informasi Member</h2>
              <p class="text-xs text-slate-300 font-mono">{{ detailData.no_member }}</p>
            </div>
            <button @click="showDetailModal = false" class="text-slate-400 hover:text-slate-100">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Status Banner -->
          <div class="mb-4 p-3 rounded-xl flex items-center justify-between"
               :class="detailData.is_expired ? 'bg-white/5 border border-white/15' : 'bg-emerald-400/10 border border-emerald-300/25'">
            <div>
              <div class="text-xs text-slate-300">Status Keanggotaan</div>
              <div class="text-sm font-bold capitalize" :class="detailData.is_expired ? 'text-rose-300' : 'text-emerald-300'">
                {{ detailData.is_expired ? 'EXPIRED (Masa aktif 30 hari telah habis)' : 'AKTIF' }}
              </div>
            </div>
            <button v-if="detailData.is_expired"
              @click="perpanjangMember(detailData); showDetailModal = false"
              class="bg-emerald-500 hover:bg-emerald-400 text-white font-bold text-xs px-3 py-2 rounded-lg shadow-sm">
              Perpanjang Sekarang
            </button>
          </div>

          <div class="grid grid-cols-2 gap-4 text-sm mb-5">
            <div>
              <div class="text-xs text-slate-400">Nama Pemilik</div>
              <div class="font-bold text-slate-100">{{ detailData.nama }}</div>
            </div>
            <div>
              <div class="text-xs text-slate-400">Instansi / Perusahaan</div>
              <div class="font-semibold text-slate-200">{{ detailData.instansi }}</div>
            </div>
            <div>
              <div class="text-xs text-slate-400">Email</div>
              <div class="font-medium text-slate-100">{{ detailData.email }}</div>
            </div>
            <div>
              <div class="text-xs text-slate-400">No. Telepon</div>
              <div class="font-medium text-slate-100">{{ detailData.telepon || '-' }}</div>
            </div>
            <div>
              <div class="text-xs text-slate-400">Waktu Pembuatan</div>
              <div class="font-medium text-slate-100">{{ formatWaktuLengkap(detailData.created_at || detailData.tanggal_daftar) }}</div>
            </div>
            <div>
              <div class="text-xs text-slate-400">Masa Aktif Berakhir</div>
              <div class="font-bold text-rose-300">{{ formatTgl(detailData.masa_aktif) }}</div>
            </div>
          </div>

          <!-- 3 Slot Plat Nomor -->
          <div class="bg-white/5 p-4 rounded-xl border border-white/15 mb-6">
            <div class="text-xs font-bold text-slate-200 uppercase tracking-wider mb-2">
              Plat Nomor Kendaraan Terdaftar (Maks. 3 Slot):
            </div>
            <div class="grid grid-cols-3 gap-2">
              <div class="bg-white/10 backdrop-blur-xl p-2.5 rounded-lg border border-white/15 text-center">
                <div class="text-[10px] text-slate-400 font-semibold mb-0.5">SLOT 1 (Utama)</div>
                <div class="font-mono font-bold text-sm text-slate-100">{{ detailData.plat_1 || '-' }}</div>
              </div>
              <div class="bg-white/10 backdrop-blur-xl p-2.5 rounded-lg border border-white/15 text-center">
                <div class="text-[10px] text-slate-400 font-semibold mb-0.5">SLOT 2</div>
                <div class="font-mono font-bold text-sm text-slate-100">{{ detailData.plat_2 || '-' }}</div>
              </div>
              <div class="bg-white/10 backdrop-blur-xl p-2.5 rounded-lg border border-white/15 text-center">
                <div class="text-[10px] text-slate-400 font-semibold mb-0.5">SLOT 3</div>
                <div class="font-mono font-bold text-sm text-slate-100">{{ detailData.plat_3 || '-' }}</div>
              </div>
            </div>
            <p class="text-[11px] text-slate-300 mt-2 italic">
              * Member hanya dapat memperoleh tiket parkir gratis jika menggunakan salah satu dari 3 plat nomor di atas.
            </p>
          </div>

          <div class="flex justify-end gap-2">
            <button @click="kirimUlang(detailData)"
              class="border border-amber-300/40 text-amber-300 hover:bg-amber-400/20 px-4 py-2 rounded-lg text-sm font-semibold transition">
              Kirim Ulang Kartu
            </button>
            <button @click="showDetailModal = false"
              class="bg-white hover:bg-slate-200 text-slate-900 px-5 py-2 rounded-lg text-sm font-semibold transition">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- MODAL TAMBAH/EDIT MEMBER (DENGAN 3 SLOT PLAT NOMOR) -->
    <Teleport to="body">
      <div v-if="showModal"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click.self="showModal = false">
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-7 max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-lg font-bold text-rose-200">
                {{ editMode ? 'Edit Member' : 'Tambah Member Baru' }}
              </h2>
              <p class="text-xs text-slate-300">Masa aktif kartu 30 hari & 3 slot plat nomor</p>
            </div>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-100">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Info pembayaran cash 150 ribu -->
          <div v-if="!editMode"
            class="text-sm text-amber-300 bg-amber-400/10 border border-amber-300/30 rounded-lg p-3.5 mb-4">
            <div class="font-bold">Konfirmasi Pembayaran Cash Rp 150.000</div>
            <div class="text-xs text-amber-300 mt-0.5">
              Member baru aktif selama <strong>30 hari</strong> sejak tanggal pendaftaran dan otomatis dikirim kartu ke email.
            </div>
          </div>

          <div v-if="formError" class="bg-white/5 text-rose-300 text-sm rounded-lg px-4 py-3 mb-4 border border-white/15">
            {{ formError }}
          </div>

          <div class="space-y-3.5">
            <div>
              <label class="block text-xs font-semibold text-slate-200 mb-1">Nama Lengkap *</label>
              <input v-model="form.nama" type="text" placeholder="Nama pemilik kartu"
                class="w-full px-3.5 py-2 border border-white/20 rounded-lg text-sm focus:ring-2 focus:ring-white/30 outline-none"/>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-200 mb-1">Instansi / Perusahaan *</label>
              <input v-model="form.instansi" type="text" placeholder="Nama instansi atau perusahaan"
                class="w-full px-3.5 py-2 border border-white/20 rounded-lg text-sm focus:ring-2 focus:ring-white/30 outline-none"/>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-200 mb-1">Email *</label>
              <input v-model="form.email" type="email" placeholder="email@contoh.com"
                :disabled="editMode"
                class="w-full px-3.5 py-2 border border-white/20 rounded-lg text-sm focus:ring-2 focus:ring-white/30 outline-none disabled:bg-white/5"/>
              <p v-if="!editMode" class="text-[11px] text-slate-400 mt-1">Kartu member ber-QR code dikirim ke email ini</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-200 mb-1">No. Telepon</label>
              <input v-model="form.telepon" type="tel" placeholder="08xxxxxxxx (opsional)"
                class="w-full px-3.5 py-2 border border-white/20 rounded-lg text-sm focus:ring-2 focus:ring-white/30 outline-none"/>
            </div>

            <!-- BAGIAN 3 SLOT PLAT NOMOR -->
            <div class="pt-2 border-t border-white/15">
              <label class="block text-xs font-bold text-slate-100 mb-1">
                Daftar Plat Nomor Kendaraan (Maksimal 3 Slot):
              </label>
              <p class="text-[11px] text-slate-300 mb-2.5">
                Member hanya bisa mendapatkan tiket parkir untuk plat yang didaftarkan di sini.
              </p>

              <div class="space-y-2">
                <div>
                  <div class="text-[11px] font-semibold text-rose-300 mb-0.5">Slot 1 (Wajib) *</div>
                  <input v-model="form.plat_1" type="text" placeholder="Contoh: BA 1234 CD"
                    @input="form.plat_1 = form.plat_1.toUpperCase()"
                    class="w-full px-3.5 py-2 border border-white/20 rounded-lg text-sm font-mono uppercase focus:ring-2 focus:ring-white/30 outline-none bg-white/5"/>
                </div>
                <div>
                  <div class="text-[11px] font-semibold text-slate-300 mb-0.5">Slot 2 (Opsional)</div>
                  <input v-model="form.plat_2" type="text" placeholder="Contoh: BA 5678 EF (opsional)"
                    @input="form.plat_2 = form.plat_2.toUpperCase()"
                    class="w-full px-3.5 py-2 border border-white/20 rounded-lg text-sm font-mono uppercase focus:ring-2 focus:ring-white/30 outline-none"/>
                </div>
                <div>
                  <div class="text-[11px] font-semibold text-slate-300 mb-0.5">Slot 3 (Opsional)</div>
                  <input v-model="form.plat_3" type="text" placeholder="Contoh: BA 9012 GH (opsional)"
                    @input="form.plat_3 = form.plat_3.toUpperCase()"
                    class="w-full px-3.5 py-2 border border-white/20 rounded-lg text-sm font-mono uppercase focus:ring-2 focus:ring-white/30 outline-none"/>
                </div>
              </div>
            </div>
          </div>

          <div class="flex gap-3 mt-6">
            <button @click="showModal = false"
              class="flex-1 py-2.5 border border-white/20 text-slate-300 hover:bg-white/5 font-medium rounded-lg transition">
              Batal
            </button>
            <button @click="simpan" :disabled="saving"
              class="flex-1 py-2.5 bg-white hover:bg-slate-200 disabled:opacity-60 text-slate-900 font-bold rounded-lg transition">
              {{ saving ? 'Menyimpan...' : (editMode ? 'Simpan Perubahan' : 'Daftarkan Member') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- MODAL FORM PERPANJANG MEMBER (INPUT NOMINAL CASH) -->
    <Teleport to="body">
      <div v-if="showPerpanjangModal && perpanjangTarget"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm backdrop-blur-xs flex items-center justify-center z-50 p-4"
        @click.self="showPerpanjangModal = false">
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-7 max-w-md w-full shadow-2xl border border-white/10 animate-in fade-in zoom-in duration-200">
          
          <!-- Header Modal -->
          <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-400/20 text-emerald-300 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-bold text-slate-100 leading-tight">Perpanjang Masa Aktif</h3>
                <p class="text-xs text-slate-300">Input nominal pembayaran cash</p>
              </div>
            </div>
            <button @click="showPerpanjangModal = false" class="text-slate-400 hover:text-slate-100">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Info Member Card -->
          <div class="bg-white/5 border border-white/15 rounded-xl p-4 mb-4">
            <div class="flex justify-between items-start mb-2">
              <div>
                <div class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Member</div>
                <div class="font-bold text-slate-100 text-sm">{{ perpanjangTarget.nama }}</div>
                <div class="text-xs text-slate-300">{{ perpanjangTarget.instansi }}</div>
              </div>
              <span class="font-mono text-xs font-bold px-2.5 py-1 bg-rose-400/15 text-rose-200 rounded-md">
                {{ perpanjangTarget.no_member }}
              </span>
            </div>
            <div class="text-[11px] text-slate-300 flex justify-between border-t border-white/15 pt-2 mt-2">
              <span>Masa Aktif Saat Ini:</span>
              <span class="font-semibold text-slate-200">{{ formatTgl(perpanjangTarget.masa_aktif) }}</span>
            </div>
          </div>

          <!-- Total Biaya -->
          <div class="bg-white/5 border border-white/15 rounded-xl p-4 mb-4 text-center">
            <div class="text-xs text-rose-300 font-semibold uppercase tracking-wider">Total Biaya Perpanjangan (30 Hari)</div>
            <div class="text-2xl font-black text-rose-200 mt-1">Rp 150.000</div>
          </div>

          <!-- Quick Select Nominal Cash -->
          <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-300 mb-2">Pilihan Nominal Uang Cepat:</label>
            <div class="grid grid-cols-4 gap-2">
              <button
                v-for="uang in [150000, 200000, 300000, 500000]"
                :key="uang"
                type="button"
                @click="setNominal(uang)"
                class="py-1.5 px-2 text-xs font-semibold rounded-lg border transition text-center"
                :class="nominalNumber === uang ? 'bg-white text-slate-900 border-white' : 'bg-white/5 text-slate-200 hover:bg-white/10 border-white/20'"
              >
                {{ uang === 150000 ? 'Uang Pas' : Number(uang/1000) + 'rb' }}
              </button>
            </div>
          </div>

          <!-- Input Nominal Cash -->
          <div class="mb-4">
            <label class="block text-xs font-bold text-slate-200 mb-1.5">Nominal Uang Diterima (Rp) *</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-slate-300 text-sm">Rp</span>
              <input
                v-model.number="nominalBayar"
                type="number"
                placeholder="Contoh: 150000"
                class="w-full pl-11 pr-4 py-3 border rounded-xl text-base font-bold outline-none transition"
                :class="!nominalBayar ? 'border-white/20 focus:border-white/50' : (isNominalCukup ? 'border-emerald-400/60 bg-emerald-400/10' : 'border-amber-300/50 bg-amber-400/10')"
              />
            </div>
          </div>

          <!-- Status Kembalian / Sisa Kurang -->
          <div v-if="nominalBayar" class="mb-5">
            <div v-if="isNominalCukup" class="p-3 bg-emerald-400/10 border border-emerald-300/25 rounded-xl text-emerald-300 text-xs flex justify-between items-center">
              <div>
                <div class="font-bold">Uang Pembayaran Cukup</div>
                <div class="text-[11px] text-emerald-300">Perpanjangan dapat diproses sekarang</div>
              </div>
              <div class="text-right">
                <div class="text-[10px] uppercase font-bold text-emerald-300">Kembalian:</div>
                <div class="text-base font-black text-emerald-300">{{ formatRupiah(kembalian) }}</div>
              </div>
            </div>

            <div v-else class="p-3 bg-white/5 border border-white/15 rounded-xl text-rose-300 text-xs flex justify-between items-center">
              <div>
                <div class="font-bold">Uang Masih Kurang</div>
                <div class="text-[11px] text-rose-300">Minimal pembayaran Rp 150.000</div>
              </div>
              <div class="text-right">
                <div class="text-[10px] uppercase font-bold text-rose-300">Kurang:</div>
                <div class="text-sm font-black text-rose-200">{{ formatRupiah(sisaKurang) }}</div>
              </div>
            </div>
          </div>

          <!-- Aksi Tombol -->
          <div class="flex gap-3">
            <button
              type="button"
              @click="showPerpanjangModal = false"
              class="flex-1 py-3 border border-white/20 text-slate-200 hover:bg-white/5 font-semibold rounded-xl text-sm transition"
            >
              Batal
            </button>
            <button
              type="button"
              @click="prosesPerpanjang"
              :disabled="!isNominalCukup || perpanjangLoading === perpanjangTarget.id"
              class="flex-1 py-3 bg-emerald-500 hover:bg-emerald-400 disabled:bg-white/10 disabled:cursor-not-allowed text-white font-bold rounded-xl text-sm transition shadow-md flex items-center justify-center gap-2"
            >
              <svg v-if="perpanjangLoading === perpanjangTarget.id" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
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
