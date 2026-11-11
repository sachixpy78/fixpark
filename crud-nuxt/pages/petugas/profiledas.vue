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

const previewImage = computed(() => {
  if (localPreview.value) return localPreview.value
  if (!profile.value.gambar) return null
  if (profile.value.gambar.startsWith('data:') || profile.value.gambar.startsWith('http')) {
    return profile.value.gambar
  }
  const storageBase = config.public.apiBase.replace(/\/api\/?$/, '')
  return `${storageBase}/storage/${profile.value.gambar}`
})

const initial = computed(() => (form.value.username || profile.value.username || 'P').charAt(0).toUpperCase())

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
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    profile.value = res
    form.value.email = res.email
    form.value.username = res.username
    form.value.password = ''
  } catch (err) {
    console.error('Gagal memuat profil:', err)
  }
}

const fetchAllUsers = async () => {
  try {
    const token = getToken()
    const data = await $fetch('/admin', {
      baseURL: config.public.apiBase,
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    allUsers.value = data || []
  } catch (err) {
    console.warn('Gagal memuat daftar user:', err)
  }
}

const handleImageUpload = (e) => {
  const file = e.target.files[0]
  if (!file) return
  if (file.size > 2 * 1024 * 1024) {
    errorMessage.value = 'Ukuran file foto maksimal 2MB!'
    return
  }
  errorMessage.value = ''
  uploadedFileName.value = file.name
  imageFile = file
  const reader = new FileReader()
  reader.onload = (event) => { localPreview.value = event.target.result }
  reader.readAsDataURL(file)
}

const saveProfile = async () => {
  errorMessage.value = ''
  successMessage.value = ''
  const token = getToken()
  const currentUserId = getUserId()

  if (!form.value.email || !form.value.username) {
    errorMessage.value = 'Email dan username wajib diisi!'
    return
  }
  if (form.value.password && form.value.password.length < 8) {
    errorMessage.value = 'Password baru minimal 8 karakter!'
    return
  }

  const emailExists = allUsers.value.some(user =>
    String(user.id) !== String(currentUserId) && user.email?.toLowerCase() === form.value.email.toLowerCase()
  )
  if (emailExists) {
    errorMessage.value = 'Email sudah digunakan akun lain!'
    return
  }

  const usernameExists = allUsers.value.some(user =>
    String(user.id) !== String(currentUserId) && user.username?.toLowerCase() === form.value.username.toLowerCase()
  )
  if (usernameExists) {
    errorMessage.value = 'Username sudah digunakan akun lain!'
    return
  }

  isSaving.value = true
  try {
    const formData = new FormData()
    formData.append('id', currentUserId)
    formData.append('email', form.value.email)
    formData.append('username', form.value.username)
    if (form.value.password) formData.append('password', form.value.password)
    if (imageFile) formData.append('gambar', imageFile)

    const res = await $fetch('/update-profile', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
      body: formData,
    })

    successMessage.value = res.message || 'Profil berhasil diperbarui!'
    await fetchProfile()

    if (import.meta.client) {
      localStorage.setItem('user_name', form.value.username)
      localStorage.setItem('user_email', form.value.email)
      if (res?.data?.gambar) localStorage.setItem('user_foto', res.data.gambar)
      window.dispatchEvent(new Event('storage'))
    }
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (err) {
    const errs = err?.data?.errors
    errorMessage.value = errs ? Object.values(errs).flat().join(' ') : (err?.data?.message || 'Terjadi kesalahan')
  } finally {
    isSaving.value = false
  }
}

onMounted(() => {
  fetchProfile()
  fetchAllUsers()
})
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
    <div class="relative z-10 p-6 md:p-12">
      <div class="max-w-6xl mx-auto">
        
        <!-- Header Section -->
        <div class="mb-10">
          <div class="flex items-center gap-4 mb-2">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg shadow-blue-500/30">
              <img v-if="previewImage" :src="previewImage" alt="Profile" class="w-full h-full object-cover rounded-2xl" />
              <span v-else class="text-3xl font-bold text-white">{{ initial }}</span>
            </div>
            <div>
              <p class="text-sm text-slate-400 uppercase tracking-wider font-semibold">E-Parkir Plaza Andalas</p>
              <h1 class="text-4xl font-bold text-white">Profil Petugas</h1>
            </div>
          </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          
          <!-- Profile Summary Card -->
          <div class="lg:col-span-1">
            <div class="bg-slate-800/50 backdrop-blur-xl rounded-3xl border border-slate-700/50 p-6 sticky top-6">
              <div class="text-center">
                <div class="w-32 h-32 mx-auto rounded-2xl overflow-hidden border-4 border-slate-700/50 bg-slate-700 mb-4 shadow-xl">
                  <img v-if="previewImage" :src="previewImage" alt="Profile" class="w-full h-full object-cover" />
                  <div v-else class="w-full h-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
                    <span class="text-5xl font-bold text-white">{{ initial }}</span>
                  </div>
                </div>
                <h2 class="text-xl font-bold text-white mb-1">{{ form.username || '—' }}</h2>
                <p class="text-sm text-slate-400 mb-4">{{ form.email || '—' }}</p>
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/10 border border-blue-400/30 text-blue-400 text-sm font-semibold">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                  </svg>
                  Petugas Aktif
                </span>
              </div>

              <div class="mt-8 pt-6 border-t border-slate-700/50 space-y-3">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-slate-400">ID Petugas</span>
                  <span class="text-white font-mono">{{ getUserId() }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-slate-400">Email</span>
                  <span class="text-white text-right truncate max-w-[150px]">{{ form.email }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Section -->
          <div class="lg:col-span-2">
            <div class="bg-slate-800/50 backdrop-blur-xl rounded-3xl border border-slate-700/50 p-8">
              
              <!-- Form Header -->
              <div class="flex items-center gap-3 mb-8 pb-6 border-b border-slate-700/50">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500/20 to-cyan-500/20 border border-blue-400/30 flex items-center justify-center">
                  <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </div>
                <div>
                  <h2 class="text-2xl font-bold text-white">Edit Informasi Akun</h2>
                  <p class="text-sm text-slate-400">Perbarui data profil dan kredensial akun Anda</p>
                </div>
              </div>

              <form @submit.prevent="saveProfile" class="space-y-6">
                
                <!-- Upload Foto Section -->
                <div class="bg-slate-900/50 rounded-2xl p-6 border border-slate-700/30">
                  <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Foto Profil
                  </h3>
                  
                  <div class="relative">
                    <input type="file" accept="image/*" @change="handleImageUpload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                    <div class="flex items-center gap-4 p-4 border-2 border-dashed border-slate-600 rounded-xl hover:border-cyan-400/50 hover:bg-cyan-500/5 transition-all bg-slate-800/50">
                      <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500/20 to-blue-500/20 border border-cyan-400/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-white truncate">{{ uploadedFileName || 'Klik atau seret file gambar ke sini' }}</p>
                        <p class="text-xs text-slate-400 mt-1">Format: JPG, PNG, WEBP • Maksimal 2MB</p>
                      </div>
                      <span class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-lg text-xs font-bold text-white shadow-lg shadow-cyan-500/25">
                        Pilih Foto
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Email & Username Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="group">
                    <label class="block text-sm font-semibold text-slate-300 mb-2 uppercase tracking-wider">
                      <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Email Gmail
                      </span>
                    </label>
                    <div class="relative">
                      <input v-model="form.email" type="email" required placeholder="nama@gmail.com" 
                        class="w-full pl-11 pr-4 py-3.5 bg-slate-900/60 border border-slate-600 rounded-xl text-white text-sm outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20 transition-all placeholder:text-slate-500" />
                      <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                        </svg>
                      </div>
                    </div>
                  </div>

                  <div class="group">
                    <label class="block text-sm font-semibold text-slate-300 mb-2 uppercase tracking-wider">
                      <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Username
                      </span>
                    </label>
                    <div class="relative">
                      <input v-model="form.username" type="text" required placeholder="Username petugas" 
                        class="w-full pl-11 pr-4 py-3.5 bg-slate-900/60 border border-slate-600 rounded-xl text-white text-sm outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20 transition-all placeholder:text-slate-500" />
                      <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Password Section -->
                <div class="bg-slate-900/50 rounded-2xl p-6 border border-slate-700/30">
                  <label class="block text-sm font-semibold text-slate-300 mb-2 uppercase tracking-wider">
                    <span class="flex items-center gap-2">
                      <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                      </svg>
                      Password Baru <span class="text-slate-500 font-normal">(Opsional)</span>
                    </span>
                  </label>
                  <div class="relative">
                    <input v-model="form.password" type="password" placeholder="Kosongkan jika tidak ingin mengubah" 
                      class="w-full pl-11 pr-4 py-3.5 bg-slate-800/60 border border-slate-600 rounded-xl text-white text-sm outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20 transition-all placeholder:text-slate-500" />
                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                      </svg>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 mt-3">
                    <div class="w-1.5 h-1.5 rounded-full bg-cyan-400 shadow-lg shadow-cyan-400/50"></div>
                    <p class="text-xs text-slate-400">Minimal 8 karakter jika ingin mengganti password</p>
                  </div>
                </div>

                <!-- Alerts -->
                <div v-if="errorMessage" class="p-4 bg-red-500/10 border border-red-400/30 rounded-xl text-red-300 text-sm flex items-center gap-3 animate-pulse">
                  <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <span>{{ errorMessage }}</span>
                </div>

                <div v-if="successMessage" class="p-4 bg-emerald-500/10 border border-emerald-400/30 rounded-xl text-emerald-300 text-sm flex items-center gap-3">
                  <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <span>{{ successMessage }}</span>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-slate-700/50">
                  <button type="submit" :disabled="isSaving" 
                    class="flex-1 sm:flex-none px-8 py-4 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl transition-all shadow-lg shadow-cyan-500/25 hover:shadow-cyan-500/40 hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2">
                    <svg v-if="isSaving" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}
                  </button>
                  
                  <button type="button" @click="router.back()" 
                    class="flex-1 sm:flex-none px-8 py-4 bg-slate-800/60 border border-slate-600 text-slate-300 font-semibold rounded-xl hover:bg-slate-800 hover:text-white hover:border-slate-500 transition-all">
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