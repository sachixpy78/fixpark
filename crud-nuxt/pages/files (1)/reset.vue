<script setup>
import { ref, onMounted } from 'vue'

const config = useRuntimeConfig()
const route = useRoute()
const router = useRouter()

const email = ref(route.query.email || '')
const password = ref('')
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
          <input
            v-model="password"
            type="password"
            minlength="8"
            placeholder="Minimal 8 karakter"
            required
            class="w-full px-4 py-3 border border-white/20 rounded-lg focus:ring-2 focus:ring-white/30 outline-none"
          />
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

