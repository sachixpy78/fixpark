<script setup>
import { ref, reactive } from 'vue'

const config = useRuntimeConfig()
const router = useRouter()

const loading = ref(false)
const showPassword = ref(false)
const form = reactive({ level: 'petugas', login: '', password: '' })

const login = async () => {
  try {
    loading.value = true

    const res = await $fetch('/login', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: form,
    })

    localStorage.setItem('auth_token', res.token)
    localStorage.setItem('user_id', res.admin.id)
    localStorage.setItem('user_name', res.admin.username)
    localStorage.setItem('user_email', res.admin.email)
    localStorage.setItem('user_level', res.admin.level)
    localStorage.setItem('user_foto', res.admin.gambar ?? '')

    if (res.admin.level === 'admin') {
      router.push('/admin/dashboard')
    } else {
      router.push('/petugas/scan-keluar')
    }
  } catch (err) {
    alert(err?.data?.message || 'Login gagal!')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex relative overflow-hidden">
    <!-- Background Image -->
    <div class="absolute inset-0 bg-parkir bg-cover bg-center"></div>
    <!-- Dark Overlay -->
    <div class="absolute inset-0 bg-gradient-to-r from-slate-900/90 via-slate-900/70 to-slate-900/50"></div>
    <!-- Decorative blue accents -->
    <div class="absolute top-0 left-0 w-1/2 h-full bg-gradient-to-br from-blue-600/10 to-transparent"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>

    <!-- LEFT: Branding Section -->
    <div class="hidden lg:flex lg:w-1/2 relative z-10 flex-col justify-center px-16 xl:px-24">
      <!-- Logo -->
      <div class="mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl shadow-2xl shadow-blue-500/40">
          <span class="text-4xl font-black text-white tracking-tighter">P</span>
        </div>
      </div>

      <!-- Title -->
      <h1 class="text-4xl xl:text-5xl font-bold text-white mb-3 tracking-tight leading-tight">
        E-Parkir<br />Plaza Andalas
      </h1>

      <!-- Tagline -->
      <p class="text-lg text-slate-300 mb-6 max-w-md">
        Sistem Parkir Modern, Praktis dan Terpercaya
      </p>

      <!-- Accent Line -->
      <div class="w-16 h-1 bg-gradient-to-r from-blue-500 to-blue-400 rounded-full"></div>

      <!-- Extra info -->
      <div class="mt-12 flex items-center gap-3 text-slate-400 text-sm">
        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        <span>Akses terbatas untuk petugas yang berwenang</span>
      </div>
    </div>

    <!-- RIGHT: Login Card Section -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 lg:p-12 relative z-10">
      <div class="w-full max-w-md">
        <!-- Glass Card -->
        <div class="bg-white/5 backdrop-blur-2xl rounded-3xl shadow-2xl shadow-black/40 border border-white/10 p-8 lg:p-10">
          <!-- Card Header -->
          <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-white/10 backdrop-blur-sm rounded-full border border-white/20 mb-4">
              <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-white mb-1 tracking-tight">Masuk ke Akun</h2>
            <p class="text-slate-400 text-sm">Silakan login untuk melanjutkan</p>
          </div>

          <form @submit.prevent="login" class="space-y-4">
            <!-- Level Select -->
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <select
                v-model="form.level"
                class="w-full pl-12 pr-10 py-3.5 bg-white/5 border border-white/10 rounded-xl text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 outline-none transition-all appearance-none cursor-pointer placeholder-slate-400"
              >
                <option value="petugas" class="bg-slate-800 text-white">Petugas</option>
                <option value="admin" class="bg-slate-800 text-white">Admin</option>
              </select>
              <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>

            <!-- Email / Username -->
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              </div>
              <input
                v-model="form.login"
                type="text"
                placeholder="Email / Username"
                required
                class="w-full pl-12 pr-4 py-3.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 outline-none transition-all"
              />
            </div>

            <!-- Password with toggle -->
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Password"
                required
                class="w-full pl-12 pr-12 py-3.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 outline-none transition-all"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-white transition-colors cursor-pointer"
                tabindex="-1"
              >
                <!-- Eye Open -->
                <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <!-- Eye Closed -->
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>

            <!-- Forgot Password -->
            <div class="text-right pt-1">
              <NuxtLink to="/forgot" class="text-sm text-blue-400 hover:text-blue-300 hover:underline transition-colors">
                Lupa password?
              </NuxtLink>
            </div>

            <!-- Login Button -->
            <button
              type="submit"
              :disabled="loading"
              class="w-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-semibold py-3.5 rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 transition-all duration-300 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2 mt-2"
            >
              <svg v-if="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
              </svg>
              <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ loading ? 'Memproses...' : 'Login' }}
            </button>

            <!-- Divider -->
            <div class="flex items-center gap-3 my-2">
              <div class="flex-1 h-px bg-white/10"></div>
              <span class="text-xs text-slate-500">atau</span>
              <div class="flex-1 h-px bg-white/10"></div>
            </div>

            <!-- Footer Security Note -->
            <div class="flex items-center justify-center gap-2 text-slate-500 text-xs pt-1">
              <svg class="w-4 h-4 text-blue-400/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <span>Akses terbatas untuk petugas yang berwenang</span>
            </div>
          </form>
        </div>

        <!-- Mobile Branding (shown only on small screens) -->
        <div class="lg:hidden text-center mt-8">
          <div class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl shadow-lg shadow-blue-500/30 mb-3">
            <span class="text-2xl font-black text-white">P</span>
          </div>
          <h2 class="text-xl font-bold text-white">E-Parkir Plaza Andalas</h2>
          <p class="text-slate-400 text-sm mt-1">Sistem Parkir Modern, Praktis dan Terpercaya</p>
        </div>
      </div>
    </div>
  </div>
</template>