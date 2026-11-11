<script setup>
definePageMeta({ layout: 'admin', middleware: 'auth-admin' })

import { ref, onMounted } from 'vue'

const config = useRuntimeConfig()
const router = useRouter()

// ── State ──
const list           = ref([])
const loading        = ref(false)
const modalOpen      = ref(false)
const saving         = ref(false)
const deleteLoading  = ref(false)
const confirmOpen    = ref(false)
const confirmPetugas = ref(null)
const unblockLoading = ref(null)

const form = ref({ username: '', email: '', password: '' })
const formErr = ref('')

const getToken = () => (import.meta.client ? localStorage.getItem('auth_token') ?? '' : '')

const formatTgl = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' }) : '-'

const fetchList = async () => {
  try {
    loading.value = true
    list.value = await $fetch('/petugas', {
      baseURL: config.public.apiBase,
      headers: { Authorization: `Bearer ${getToken()}` },
    })
  } finally {
    loading.value = false
  }
}

// ── Tambah Petugas ──
const openModal = () => {
  form.value = { username: '', email: '', password: '' }
  formErr.value = ''
  modalOpen.value = true
}

const simpan = async () => {
  if (!form.value.username || !form.value.email || !form.value.password) {
    formErr.value = 'Semua field wajib diisi.'
    return
  }
  try {
    saving.value = true
    formErr.value = ''
    await $fetch('/petugas', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Authorization: `Bearer ${getToken()}`, Accept: 'application/json' },
      body: form.value,
    })
    modalOpen.value = false
    await fetchList()
  } catch (err) {
    const errs = err?.data?.errors
    if (errs) {
      formErr.value = Object.values(errs).flat().join(' ')
    } else {
      formErr.value = err?.data?.message || 'Gagal menyimpan.'
    }
  } finally {
    saving.value = false
  }
}

// ── Buka Blokir Petugas ──
const doUnblock = async (p) => {
  if (!confirm(`Buka blokir untuk akun petugas "${p.username}"?`)) return
  try {
    unblockLoading.value = p.id
    const res = await $fetch(`/petugas/${p.id}/unblock`, {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Authorization: `Bearer ${getToken()}` },
    })
    alert(res.message || 'Blokir berhasil dibuka!')
    await fetchList()
  } catch (err) {
    alert(err?.data?.message || 'Gagal membuka blokir')
  } finally {
    unblockLoading.value = null
  }
}

// ── Hapus Petugas ──
const askDelete = (p) => {
  confirmPetugas.value = p
  confirmOpen.value = true
}

const doDelete = async () => {
  try {
    deleteLoading.value = true
    await $fetch(`/petugas/${confirmPetugas.value.id}`, {
      baseURL: config.public.apiBase,
      method: 'DELETE',
      headers: { Authorization: `Bearer ${getToken()}` },
    })
    confirmOpen.value = false
    await fetchList()
  } catch (err) {
    alert(err?.data?.message || 'Gagal menghapus petugas.')
    confirmOpen.value = false
  } finally {
    deleteLoading.value = false
  }
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

onMounted(fetchList)
</script>

<template>
  <div>


    <!-- ═══ MAIN ═══ -->
    <div class="flex flex-col">

      <!-- Header -->
      <header class="bg-white/10 backdrop-blur-xl border-b border-white/10 px-8 py-4 flex items-center justify-between shadow-sm">
        <div>
          <h1 class="text-xl font-bold text-rose-200">Kelola Petugas</h1>
          <p class="text-sm text-slate-300">Manajemen akun petugas, status pemblokiran sandi, dan retensi 30 hari</p>
        </div>
        <button @click="openModal"
          class="flex items-center gap-2 bg-white hover:bg-slate-200 text-slate-900 px-4 py-2 rounded-lg text-sm font-medium transition shadow">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Tambah Petugas
        </button>
      </header>

      <div class="p-8">

        <!-- Loading -->
        <div v-if="loading" class="text-center py-16 text-rose-300 font-medium">Memuat data petugas...</div>

        <!-- Table -->
        <div v-else class="bg-white/10 backdrop-blur-xl rounded-2xl shadow border border-white/10 overflow-hidden">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left bg-white/10 text-slate-100">
                <th class="px-5 py-3.5 font-semibold">ID</th>
                <th class="px-5 py-3.5 font-semibold">Username</th>
                <th class="px-5 py-3.5 font-semibold">Email</th>
                <th class="px-5 py-3.5 font-semibold">Dibuat</th>
                <th class="px-5 py-3.5 font-semibold">Last Login</th>
                <th class="px-5 py-3.5 font-semibold text-center">Status</th>
                <th class="px-5 py-3.5 font-semibold text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="list.length === 0">
                <td colspan="7" class="text-center py-12 text-slate-400">Belum ada petugas terdaftar.</td>
              </tr>
              <tr v-for="(p, i) in list" :key="p.id"
                :class="i % 2 === 0 ? 'bg-white/10 backdrop-blur-xl' : 'bg-white/5'"
                class="hover:bg-white/5 transition border-b border-white/10">
                <td class="px-5 py-3.5 font-mono text-slate-300 text-xs">#{{ p.id }}</td>
                <td class="px-5 py-3.5 font-semibold text-slate-100">{{ p.username }}</td>
                <td class="px-5 py-3.5 text-slate-300">{{ p.email }}</td>
                <td class="px-5 py-3.5 text-slate-300">{{ formatTgl(p.created_at) }}</td>
                <td class="px-5 py-3.5 text-slate-300">{{ formatTgl(p.last_login_at) }}</td>

                <!-- STATUS PETUGAS (Aktif, Diblokir, Tidak Aktif 30 Hari) -->
                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                  <!-- DIBLOKIR -->
                  <span v-if="p.status_petugas === 'diblokir' || p.is_blocked"
                    class="inline-flex items-center gap-1.5 bg-rose-400/15 text-rose-300 border border-white/15 text-xs font-bold px-2.5 py-1 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400 inline-block animate-pulse"></span>
                    Diblokir (Salah sandi 3x)
                  </span>

                  <!-- TIDAK AKTIF 30 HARI -->
                  <span v-else-if="p.status_petugas === 'tidak_aktif_30d' || p.is_inactive_30d"
                    class="inline-flex items-center gap-1.5 bg-amber-400/20 text-amber-300 border border-amber-300/30 text-xs font-bold px-2.5 py-1 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span>
                    Tidak Aktif 30 Hari
                  </span>

                  <!-- AKTIF -->
                  <span v-else
                    class="inline-flex items-center gap-1.5 bg-emerald-400/20 text-emerald-300 border border-emerald-300/25 text-xs font-semibold px-2.5 py-1 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"></span>
                    Aktif
                  </span>
                </td>

                <!-- AKSI PETUGAS -->
                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                  <!-- JIKA DIBLOKIR: BISA BUKA BLOKIR -->
                  <button v-if="p.status_petugas === 'diblokir' || p.is_blocked"
                    @click="doUnblock(p)"
                    :disabled="unblockLoading === p.id"
                    class="inline-flex items-center gap-1.5 bg-amber-400 hover:bg-amber-300 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition shadow-xs disabled:opacity-60">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    </svg>
                    {{ unblockLoading === p.id ? 'Membuka...' : 'Buka Blokir' }}
                  </button>

                  <!-- JIKA TIDAK AKTIF 30 HARI: BISA DIHAPUS -->
                  <button v-else-if="p.status_petugas === 'tidak_aktif_30d' || p.is_inactive_30d"
                    @click="askDelete(p)"
                    class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-200 text-slate-900 text-xs font-medium px-3 py-1.5 rounded-lg transition shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Hapus
                  </button>

                  <!-- JIKA MASIH AKTIF: TOMBOL HAPUS DINONAKTIFKAN -->
                  <button v-else
                    disabled
                    title="Petugas masih aktif. Akun hanya dapat dihapus jika tidak login selama minimal 30 hari."
                    class="inline-flex items-center gap-1.5 bg-white/10 text-slate-400 cursor-not-allowed text-xs font-medium px-3 py-1.5 rounded-lg border border-white/15">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Hapus (Aktif)
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Keterangan Alur -->
        <div class="mt-4 p-3.5 bg-white/10 backdrop-blur-xl rounded-xl border border-white/10 text-xs text-slate-300 space-y-1">
          <p><strong>Ketentuan Status & Aksi Petugas:</strong></p>
          <ul class="list-disc pl-5 space-y-0.5">
            <li><span class="text-emerald-300 font-semibold">Aktif:</span> Petugas yang aktif login dalam 30 hari terakhir (tidak dapat dihapus demi keamanan).</li>
            <li><span class="text-rose-300 font-semibold">Diblokir:</span> Terjadi saat petugas salah memasukkan sandi 3 kali di halaman login. Admin dapat membuka blokir dengan tombol <strong>"Buka Blokir"</strong>.</li>
            <li><span class="text-amber-300 font-semibold">Tidak Aktif 30 Hari:</span> Petugas yang tidak pernah login selama minimal 30 hari terakhir. Tombol <strong>"Hapus"</strong> baru dapat digunakan pada kondisi ini.</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- ═══ MODAL TAMBAH PETUGAS ═══ -->
    <Teleport to="body">
      <div v-if="modalOpen"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="modalOpen = false">
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-2xl w-full max-w-md p-6">
          <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-bold text-rose-200">Tambah Petugas Baru</h2>
            <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-100">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div v-if="formErr" class="bg-white/5 border border-white/15 text-rose-300 text-sm rounded-lg px-4 py-3 mb-4">
            {{ formErr }}
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-200 mb-1.5">Username *</label>
              <input v-model="form.username" type="text" placeholder="username_petugas"
                class="w-full border border-white/20 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-white/30 outline-none"/>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-200 mb-1.5">Email *</label>
              <input v-model="form.email" type="email" placeholder="email@example.com"
                class="w-full border border-white/20 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-white/30 outline-none"/>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-200 mb-1.5">Password *</label>
              <input v-model="form.password" type="password" placeholder="Minimal 8 karakter"
                class="w-full border border-white/20 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-white/30 outline-none"/>
            </div>
          </div>

          <div class="flex gap-3 mt-6">
            <button @click="modalOpen = false"
              class="flex-1 border border-white/20 text-slate-300 hover:bg-white/5 py-2.5 rounded-lg text-sm font-medium transition">
              Batal
            </button>
            <button @click="simpan" :disabled="saving"
              class="flex-1 bg-white hover:bg-slate-200 text-slate-900 py-2.5 rounded-lg text-sm font-medium transition disabled:opacity-60">
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══ KONFIRMASI HAPUS ═══ -->
    <Teleport to="body">
      <div v-if="confirmOpen"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="confirmOpen = false">
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
          <div class="w-14 h-14 bg-rose-400/15 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-rose-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
          </div>
          <h3 class="text-lg font-bold text-slate-100 mb-1">Hapus Petugas?</h3>
          <p class="text-sm text-slate-300 mb-6">
            Akun <strong class="text-rose-300">{{ confirmPetugas?.username }}</strong> yang telah tidak aktif selama lebih dari 30 hari akan dihapus permanen dari sistem.
          </p>
          <div class="flex gap-3">
            <button @click="confirmOpen = false"
              class="flex-1 border border-white/20 text-slate-300 hover:bg-white/5 py-2.5 rounded-lg text-sm font-medium transition">
              Batal
            </button>
            <button @click="doDelete" :disabled="deleteLoading"
              class="flex-1 bg-white hover:bg-slate-200 text-slate-900 py-2.5 rounded-lg text-sm font-medium transition disabled:opacity-60">
              {{ deleteLoading ? 'Menghapus...' : 'Ya, Hapus' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>
