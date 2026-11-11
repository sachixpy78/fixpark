<script setup>
definePageMeta({ layout: 'admin', middleware: 'auth-admin' })

import { ref, onMounted } from 'vue'

const config = useRuntimeConfig()
const router = useRouter()

//  State ──
const list           = ref([])
const loading        = ref(false)
const modalOpen      = ref(false)
const saving         = ref(false)
const deleteLoading  = ref(false)
const confirmOpen    = ref(false)
const confirmPetugas = ref(null)
const unblockLoading = ref(null)
const showPassword   = ref(false)

const form = ref({ username: '', email: '', password: '' })
const formErr = ref('')

const getToken = () => (import.meta.client ? localStorage.getItem('auth_token') ?? '' : '')

const formatTgl = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' }) : '-'

const fetchList = async () => {
  if (!import.meta.client) return
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
  showPassword.value = false
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

// ── Buka Blokir Petugas ─
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
      <header class="bg-slate-800/80 backdrop-blur-xl border-b border-white/10 px-8 py-5 flex items-center justify-between shadow-xs">
        <div>
          <h1 class="text-xl font-bold text-blue-200">Kelola Petugas</h1>
          <p class="text-sm text-slate-300">Manajemen akun petugas, status pemblokiran sandi, dan retensi 30 hari</p>
        </div>
        <button @click="openModal"
          class="flex items-center gap-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition shadow-lg shadow-blue-900/40 hover:shadow-blue-500/50 hover:scale-[1.02] active:scale-[0.98]">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Tambah Petugas
        </button>
      </header>

      <div class="p-8">

        <!-- Loading -->
        <div v-if="loading" class="text-center py-16 text-blue-300 font-medium flex items-center justify-center gap-2">
          <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Memuat data petugas...
        </div>

        <!-- Table -->
        <div v-else class="bg-slate-800/60 backdrop-blur-xl rounded-2xl shadow-lg border border-white/10 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left bg-slate-700/50 text-slate-100 border-b border-white/10">
                  <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider">ID</th>
                  <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider">Username</th>
                  <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider">Email</th>
                  <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider">Dibuat</th>
                  <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider">Last Login</th>
                  <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider text-center">Status</th>
                  <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="list.length === 0">
                  <td colspan="7" class="text-center py-12 text-slate-400">
                    <div class="flex flex-col items-center gap-2">
                      <svg class="w-12 h-12 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                      </svg>
                      <span>Belum ada petugas terdaftar.</span>
                    </div>
                  </td>
                </tr>
                <tr v-for="(p, i) in list" :key="p.id"
                  :class="i % 2 === 0 ? 'bg-slate-800/40' : 'bg-slate-800/20'"
                  class="hover:bg-blue-500/10 transition border-b border-white/5 last:border-b-0">
                  <td class="px-6 py-4 font-mono text-slate-400 text-xs">#{{ p.id }}</td>
                  <td class="px-6 py-4 font-semibold text-slate-100">{{ p.username }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ p.email }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ formatTgl(p.created_at) }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ formatTgl(p.last_login_at) }}</td>

                  <!-- STATUS PETUGAS -->
                  <td class="px-6 py-4 text-center whitespace-nowrap">
                    <!-- DIBLOKIR -->
                    <span v-if="p.status_petugas === 'diblokir' || p.is_blocked"
                      class="inline-flex items-center gap-1.5 bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-bold px-3 py-1.5 rounded-full">
                      <span class="w-1.5 h-1.5 rounded-full bg-rose-400 inline-block animate-pulse"></span>
                      Diblokir
                    </span>

                    <!-- TIDAK AKTIF 30 HARI -->
                    <span v-else-if="p.status_petugas === 'tidak_aktif_30d' || p.is_inactive_30d"
                      class="inline-flex items-center gap-1.5 bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold px-3 py-1.5 rounded-full">
                      <span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span>
                      Tidak Aktif 30 Hari
                    </span>

                    <!-- AKTIF -->
                    <span v-else
                      class="inline-flex items-center gap-1.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-semibold px-3 py-1.5 rounded-full">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"></span>
                      Aktif
                    </span>
                  </td>

                  <!-- AKSI PETUGAS -->
                  <td class="px-6 py-4 text-center whitespace-nowrap">
                    <!-- BUKA BLOKIR -->
                    <button v-if="p.status_petugas === 'diblokir' || p.is_blocked"
                      @click="doUnblock(p)"
                      :disabled="unblockLoading === p.id"
                      class="inline-flex items-center gap-1.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-white text-xs font-bold px-3.5 py-2 rounded-lg transition shadow-lg shadow-amber-900/30 disabled:opacity-60 hover:scale-[1.05] active:scale-[0.95]">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                      </svg>
                      {{ unblockLoading === p.id ? 'Membuka...' : 'Buka Blokir' }}
                    </button>

                    <!-- HAPUS (Tidak Aktif) -->
                    <button v-else-if="p.status_petugas === 'tidak_aktif_30d' || p.is_inactive_30d"
                      @click="askDelete(p)"
                      class="inline-flex items-center gap-1.5 bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-400 hover:to-rose-500 text-white text-xs font-bold px-3.5 py-2 rounded-lg transition shadow-lg shadow-rose-900/30 hover:scale-[1.05] active:scale-[0.95]">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                      Hapus
                    </button>

                    <!-- HAPUS (Dinonaktifkan - Masih Aktif) -->
                    <button v-else
                      disabled
                      title="Petugas masih aktif. Akun hanya dapat dihapus jika tidak login selama minimal 30 hari."
                      class="inline-flex items-center gap-1.5 bg-slate-700/50 text-slate-400 cursor-not-allowed text-xs font-medium px-3.5 py-2 rounded-lg border border-white/10">
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
        </div>

        <!-- Keterangan Alur -->
        <div class="mt-6 p-5 bg-slate-800/60 backdrop-blur-xl rounded-2xl border border-white/10 text-xs text-slate-300">
          <div class="flex items-start gap-3 mb-3">
            <svg class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
              <p class="font-bold text-slate-100 mb-2">Ketentuan Status & Aksi Petugas:</p>
              <ul class="space-y-1.5">
                <li class="flex items-start gap-2">
                  <span class="text-emerald-400 font-semibold">• Aktif:</span>
                  <span class="text-slate-400">Petugas yang aktif login dalam 30 hari terakhir (tidak dapat dihapus demi keamanan).</span>
                </li>
                <li class="flex items-start gap-2">
                  <span class="text-rose-400 font-semibold">• Diblokir:</span>
                  <span class="text-slate-400">Terjadi saat petugas salah memasukkan sandi 3 kali di halaman login. Admin dapat membuka blokir dengan tombol <strong class="text-amber-400">"Buka Blokir"</strong>.</span>
                </li>
                <li class="flex items-start gap-2">
                  <span class="text-amber-400 font-semibold">• Tidak Aktif 30 Hari:</span>
                  <span class="text-slate-400">Petugas yang tidak pernah login selama minimal 30 hari terakhir. Tombol <strong class="text-rose-400">"Hapus"</strong> baru dapat digunakan pada kondisi ini.</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ MODAL TAMBAH PETUGAS ═══ -->
    <Teleport to="body">
      <div v-if="modalOpen"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="modalOpen = false">
        <div class="bg-slate-800/90 backdrop-blur-2xl rounded-3xl shadow-2xl w-full max-w-md p-7 border border-white/10">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center shadow-lg shadow-blue-500/30">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
              </div>
              <h2 class="text-lg font-bold text-white">Tambah Petugas Baru</h2>
            </div>
            <button @click="modalOpen = false" class="text-slate-400 hover:text-white transition-colors p-1 hover:bg-white/10 rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div v-if="formErr" class="bg-rose-500/20 border border-rose-500/30 text-rose-300 text-sm rounded-xl px-4 py-3 mb-5 flex items-start gap-2">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ formErr }}
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-semibold text-slate-300 mb-2">Username *</label>
              <input v-model="form.username" type="text" placeholder="username_petugas"
                class="w-full bg-slate-700/50 border-2 border-slate-600 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none text-white placeholder:text-slate-500 transition-all"/>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-300 mb-2">Email *</label>
              <input v-model="form.email" type="email" placeholder="email@example.com"
                class="w-full bg-slate-700/50 border-2 border-slate-600 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none text-white placeholder:text-slate-500 transition-all"/>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-300 mb-2">Password *</label>
              <div class="relative">
                <input 
                  v-model="form.password" 
                  :type="showPassword ? 'text' : 'password'" 
                  placeholder="Minimal 8 karakter"
                  class="w-full bg-slate-700/50 border-2 border-slate-600 rounded-xl px-4 py-3 pr-12 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none text-white placeholder:text-slate-500 transition-all"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-400 transition-colors p-1"
                  tabindex="-1"
                >
                  <!-- Eye Open Icon -->
                  <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <!-- Eye Closed Icon -->
                  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div class="flex gap-3 mt-7">
            <button @click="modalOpen = false"
              class="flex-1 border-2 border-slate-600 text-slate-300 hover:bg-slate-700/50 py-3 rounded-xl text-sm font-semibold transition">
              Batal
            </button>
            <button @click="simpan" :disabled="saving"
              class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white py-3 rounded-xl text-sm font-semibold transition shadow-lg shadow-blue-900/40 disabled:opacity-60 hover:shadow-blue-500/50">
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══ KONFIRMASI HAPUS ══ -->
    <Teleport to="body">
      <div v-if="confirmOpen"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="confirmOpen = false">
        <div class="bg-slate-800/90 backdrop-blur-2xl rounded-3xl shadow-2xl w-full max-w-sm p-7 text-center border border-white/10">
          <div class="w-16 h-16 bg-gradient-to-br from-rose-500/20 to-rose-600/20 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-rose-500/30">
            <svg class="w-8 h-8 text-rose-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
          </div>
          <h3 class="text-xl font-bold text-white mb-2">Hapus Petugas?</h3>
          <p class="text-sm text-slate-400 mb-6 leading-relaxed">
            Akun <strong class="text-rose-400">{{ confirmPetugas?.username }}</strong> yang telah tidak aktif selama lebih dari 30 hari akan dihapus permanen dari sistem.
          </p>
          <div class="flex gap-3">
            <button @click="confirmOpen = false"
              class="flex-1 border-2 border-slate-600 text-slate-300 hover:bg-slate-700/50 py-3 rounded-xl text-sm font-semibold transition">
              Batal
            </button>
            <button @click="doDelete" :disabled="deleteLoading"
              class="flex-1 bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-400 hover:to-rose-500 text-white py-3 rounded-xl text-sm font-semibold transition shadow-lg shadow-rose-900/40 disabled:opacity-60">
              {{ deleteLoading ? 'Menghapus...' : 'Ya, Hapus' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>