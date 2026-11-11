<template>
  <div>
    
    <!-- BANNER DENGAN GRADIENT MERAH PLAZA ANDALAS -->
    <div class="relative w-full h-72 md:h-80 overflow-hidden bg-white/10 backdrop-blur-xl border border-white/15">
      <!-- Pattern Overlay -->
      <div class="absolute inset-0 opacity-[0.04]" 
           style="background-image: radial-gradient(#F8FAFC 1px, transparent 1px); background-size: 24px 24px;"></div>
      
      <!-- Decorative Elements -->
      <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
      <div class="absolute bottom-0 left-0 w-64 h-64 bg-amber-400/10 rounded-full blur-2xl translate-y-1/2 -translate-x-1/2"></div>
      
      <div class="absolute top-8 left-6 md:left-12 lg:left-16 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-white font-black text-xl shadow-inner">
          P
        </div>
        <div>
          <p class="text-[10px] uppercase tracking-[0.35em] text-slate-300 font-semibold">E-Parkir Plaza Andalas</p>
          <h1 class="font-serif text-3xl md:text-4xl text-white mt-1 font-normal tracking-wide">Profil Petugas</h1>
        </div>
      </div>
    </div>

    <!-- CONTAINER UTAMA -->
    <div class="max-w-7xl mx-auto px-6 md:px-12 lg:px-16">
      <div class="flex flex-col lg:flex-row gap-10 lg:gap-14">


        <!-- KONTEN UTAMA: FORM EDIT -->
        <div class="pb-20">
          <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-[0_10px_35px_rgba(0,0,0,0.06)] border border-white/10 p-8 md:p-12">
            
            <!-- Header Form -->
            <div class="flex items-center gap-4 mb-8">
              <div class="h-px flex-1 bg-white/20"></div>
              <h3 class="font-serif text-2xl text-rose-200 font-bold tracking-tight">Edit Informasi Akun</h3>
              <div class="h-px flex-1 bg-white/20"></div>
            </div>

            <form @submit.prevent="saveProfile" class="space-y-8">

              <!-- UPLOAD FOTO -->
              <div>
                <label class="block text-[11px] uppercase tracking-[0.2em] text-rose-200 font-bold mb-3">Foto Profil Baru</label>
                <div class="relative">
                  <input
                    type="file"
                    accept="image/*"
                    @change="handleImageUpload"
                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                  />
                  <div class="flex items-center gap-4 p-5 border-2 border-dashed border-white/15 rounded-xl hover:border-white/40 transition-all duration-300 bg-white/5">
                    <div class="w-12 h-12 rounded-xl bg-rose-400/15 text-rose-300 flex items-center justify-center flex-shrink-0">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                      </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm text-slate-100 font-semibold truncate">{{ uploadedFileName || 'Klik atau seret file gambar ke sini' }}</p>
                      <p class="text-xs text-slate-300 mt-0.5">Mendukung format JPG, PNG atau WEBP (Maksimal 2MB)</p>
                    </div>
                    <span class="px-4 py-2 bg-white/10 backdrop-blur-xl border border-white/15 rounded-lg text-xs font-semibold text-rose-200 shadow-sm flex-shrink-0">
                      Pilih Foto
                    </span>
                  </div>
                </div>
              </div>

              <!-- GRID EMAIL & USERNAME -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-[11px] uppercase tracking-[0.2em] text-rose-200 font-bold mb-2">Alamat Email Gmail</label>
                  <div class="relative">
                    <input
                      v-model="form.email"
                      type="email"
                      required
                      placeholder="nama@gmail.com"
                      class="w-full pl-4 pr-11 py-3.5 bg-white/5 border border-white/20 rounded-xl text-slate-100 text-sm focus:outline-none focus:border-white/50 focus:bg-white/15 focus:ring-2 focus:ring-white/20 transition"
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-slate-400">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                      </svg>
                    </div>
                  </div>
                </div>

                <div>
                  <label class="block text-[11px] uppercase tracking-[0.2em] text-rose-200 font-bold mb-2">Username Petugas</label>
                  <div class="relative">
                    <input
                      v-model="form.username"
                      type="text"
                      required
                      placeholder="Username petugas"
                      class="w-full pl-4 pr-11 py-3.5 bg-white/5 border border-white/20 rounded-xl text-slate-100 text-sm focus:outline-none focus:border-white/50 focus:bg-white/15 focus:ring-2 focus:ring-white/20 transition"
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-slate-400">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                      </svg>
                    </div>
                  </div>
                </div>
              </div>

              <!-- PASSWORD BARU -->
              <div>
                <label class="block text-[11px] uppercase tracking-[0.2em] text-rose-200 font-bold mb-2">Password Baru (Opsional)</label>
                <div class="relative">
                  <input
                    v-model="form.password"
                    type="password"
                    placeholder="Kosongkan jika tidak ingin mengubah password saat ini"
                    class="w-full pl-4 pr-11 py-3.5 bg-white/5 border border-white/20 rounded-xl text-slate-100 text-sm focus:outline-none focus:border-white/50 focus:bg-white/15 focus:ring-2 focus:ring-white/20 transition"
                  />
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                  </div>
                </div>
                <div class="flex items-center gap-2 mt-2">
                  <div class="w-1.5 h-1.5 rounded-full bg-rose-400"></div>
                  <p class="text-xs text-slate-300">Minimal 8 karakter jika ingin mengganti password</p>
                </div>
              </div>

              <!-- Alert error jika ada -->
              <div v-if="errorMessage" class="p-4 bg-white/5 border border-white/15 rounded-xl text-rose-300 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span>{{ errorMessage }}</span>
              </div>

              <!-- Alert sukses jika ada -->
              <div v-if="successMessage" class="p-4 bg-emerald-400/10 border border-emerald-300/25 rounded-xl text-emerald-300 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>{{ successMessage }}</span>
              </div>

              <!-- Divider -->
              <div class="h-px bg-white/20"></div>

              <!-- TOMBOL SIMPAN -->
              <div class="flex items-center gap-4">
                <button
                  type="submit"
                  :disabled="isSaving"
                  class="group relative px-8 py-3.5 text-xs font-bold uppercase tracking-wider rounded-xl focus:outline-none focus:ring-4 focus:ring-white/20 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed shadow-md hover:shadow-lg hover:-translate-y-0.5 bg-white/10 backdrop-blur-xl text-slate-900 hover:bg-white/15"
                >
                  <span class="flex items-center gap-2">
                    <svg v-if="isSaving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}
                  </span>
                </button>

                <button
                  type="button"
                  @click="router.back()"
                  class="px-6 py-3.5 text-slate-300 text-xs font-semibold uppercase tracking-wider hover:text-white hover:bg-white/10 rounded-xl transition"
                >
                  Batal
                </button>
              </div>

            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({ layout: 'petugas', middleware: 'auth-petugas' })

import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'

const config = useRuntimeConfig()
const router = useRouter()

const profile = ref({})
const form = ref({ email: '', username: '', password: '' })
const allUsers = ref([])
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const uploadedFileName = ref('')
const localPreview = ref(null)
let imageFile = null

const getToken = () => (import.meta.client ? localStorage.getItem('auth_token') || '' : '')
const getUserId = () => (import.meta.client ? localStorage.getItem('user_id') || '' : '')

// PREVIEW GAMBAR
const previewImage = computed(() => {
  if (localPreview.value) return localPreview.value
  if (!profile.value.gambar) return null
  if (profile.value.gambar.startsWith('data:') || profile.value.gambar.startsWith('http')) {
    return profile.value.gambar
  }
  const storageBase = config.public.apiBase.replace(/\/api\/?$/, '')
  return `${storageBase}/storage/${profile.value.gambar}`
})

// FETCH PROFILE
const fetchProfile = async () => {
  try {
    const token = getToken()
    const userId = getUserId()

    if (!token || !userId) {
      router.push('/login')
      return
    }

    const res = await $fetch(`/profile/${userId}`, {
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    })

    profile.value = res
    form.value.email = res.email
    form.value.username = res.username
    form.value.password = ''
  } catch (err) {
    console.error('Gagal memuat profil:', err)
  }
}

// FETCH ALL USERS UNTUK CEK DUPLIKAT
const fetchAllUsers = async () => {
  try {
    const token = getToken()
    const data = await $fetch('/admin', {
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    })
    allUsers.value = data || []
  } catch (err) {
    console.warn('Gagal memuat daftar user:', err)
  }
}

// UPLOAD GAMBAR
const handleImageUpload = (e) => {
  const file = e.target.files[0]
  if (!file) return

  if (file.size > 2 * 1024 * 1024) {
    errorMessage.value = 'Ukuran file foto maksimal 2MB!'
    return
  }

  uploadedFileName.value = file.name
  imageFile = file
  const reader = new FileReader()
  reader.onload = (event) => {
    localPreview.value = event.target.result
  }
  reader.readAsDataURL(file)
}

// SIMPAN PROFIL
const saveProfile = async () => {
  errorMessage.value = ''
  successMessage.value = ''
  const token = getToken()
  const currentUserId = getUserId()

  // Validasi format
  if (!form.value.email || !form.value.username) {
    errorMessage.value = 'Email dan username wajib diisi!'
    return
  }
  if (form.value.password && form.value.password.length < 8) {
    errorMessage.value = 'Password baru minimal harus 8 karakter!'
    return
  }

  // Cek duplikat email
  const emailExists = allUsers.value.some(user =>
    String(user.id) !== String(currentUserId) &&
    user.email?.toLowerCase() === form.value.email.toLowerCase()
  )
  if (emailExists) {
    errorMessage.value = 'Alamat email sudah digunakan oleh akun lain!'
    return
  }

  // Cek duplikat username
  const usernameExists = allUsers.value.some(user =>
    String(user.id) !== String(currentUserId) &&
    user.username?.toLowerCase() === form.value.username.toLowerCase()
  )
  if (usernameExists) {
    errorMessage.value = 'Username sudah digunakan oleh akun lain!'
    return
  }

  isSaving.value = true

  try {
    const formData = new FormData()
    formData.append('id', currentUserId)
    formData.append('email', form.value.email)
    formData.append('username', form.value.username)
    if (form.value.password) {
      formData.append('password', form.value.password)
    }
    if (imageFile) {
      formData.append('gambar', imageFile)
    }

    const res = await $fetch('/update-profile', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
      body: formData,
    })

    successMessage.value = res.message || 'Profil berhasil diperbarui!'
    await fetchProfile()

    if (import.meta.client) {
      localStorage.setItem('user_name', form.value.username)
      localStorage.setItem('user_email', form.value.email)
      if (res?.data?.gambar) {
        localStorage.setItem('user_foto', res.data.gambar)
      }
    }

    setTimeout(() => {
      successMessage.value = ''
    }, 4000)
  } catch (err) {
    console.error('Error simpan profil:', err)
    const errs = err?.data?.errors
    errorMessage.value = errs ? Object.values(errs).flat().join(' ') : (err?.data?.message || 'Terjadi kesalahan saat menyimpan.')
  } finally {
    isSaving.value = false
  }
}

onMounted(() => {
  fetchProfile()
  fetchAllUsers()
})
</script>

<style scoped>
.font-serif {
  font-family: 'Georgia', serif;
}
</style>

