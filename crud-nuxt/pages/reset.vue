<script setup>
import { ref, onMounted } from 'vue'

const config = useRuntimeConfig()
const route = useRoute()
const router = useRouter()

const email = ref(route.query.email || '')
const password = ref('')
const showPassword = ref(false) // State untuk toggle password
const loading = ref(false)

// kalau belum lewat verifikasi OTP, balik ke awal
onMounted(() => {
  if (!email.value || !sessionStorage.getItem('reset_otp')) router.push('/forgot')
})

const savePassword = async () => {
  try {
    loading.value = true

    await $fetch('/reset-password', {
      baseURL: config.public.apiBase,
      method: 'PUT',
      headers: { Accept: 'application/json' },
      body: {
        email: email.value,
        otp: sessionStorage.getItem('reset_otp'),
        password: password.value,
      },
    })

    sessionStorage.removeItem('reset_otp')
    alert('Password berhasil diubah!')
    router.push('/login')
  } catch (err) {
    const errors = err?.data?.errors
    alert(errors ? Object.values(errors).flat().join(' ') : err?.data?.message || 'Gagal mengubah password.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-4 bg-parkir">
    <div class="w-full max-w-md bg-white/10 backdrop-blur-xl rounded-2xl shadow-lg p-8">
      <h1 class="text-2xl font-bold text-slate-100 mb-1">Reset Password</h1>
      <p class="text-slate-300 mb-6">Buat password baru untuk akun Anda</p>

      <form @submit.prevent="savePassword" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-slate-200 mb-2">Email</label>
          <div class="w-full px-4 py-3 border border-white/20 rounded-lg bg-white/5">
            <b class="text-slate-200">{{ email }}</b>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-200 mb-2">Password Baru</label>
          <div class="relative">
            <input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              minlength="8"
              placeholder="Minimal 8 karakter"
              required
              class="w-full px-4 py-3 pr-12 border border-white/20 rounded-lg focus:ring-2 focus:ring-white/30 outline-none text-slate-900"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200 transition"
            >
              <!-- Icon Mata Terbuka (show password) -->
              <svg 
                v-if="showPassword" 
                xmlns="http://www.w3.org/2000/svg" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor" 
                class="w-5 h-5"
              >
                <path 
                  stroke-linecap="round" 
                  stroke-linejoin="round" 
                  stroke-width="2" 
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" 
                />
                <path 
                  stroke-linecap="round" 
                  stroke-linejoin="round" 
                  stroke-width="2" 
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" 
                />
              </svg>
              <!-- Icon Mata Tertutup (hide password) -->
              <svg 
                v-else 
                xmlns="http://www.w3.org/2000/svg" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor" 
                class="w-5 h-5"
              >
                <path 
                  stroke-linecap="round" 
                  stroke-linejoin="round" 
                  stroke-width="2" 
                  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" 
                />
              </svg>
            </button>
          </div>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-white hover:bg-slate-200 text-slate-900 font-medium py-3 rounded-lg transition disabled:opacity-60"
        >
          {{ loading ? 'Menyimpan...' : 'Simpan Password' }}
        </button>
      </form>

      <div class="text-center mt-6 pt-6 border-t border-white/15">
        <NuxtLink to="/login" class="text-sm text-sky-300 hover:text-white font-medium hover:underline">
          ← Kembali ke Login
        </NuxtLink>
      </div>
    </div>
  </div>
</template>