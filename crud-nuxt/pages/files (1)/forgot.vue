<script setup>
import { ref } from 'vue'

const config = useRuntimeConfig()
const router = useRouter()

const email = ref('')
const loading = ref(false)

const submitEmail = async () => {
  try {
    loading.value = true

    await $fetch('/forgot-password', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: { email: email.value },
    })

    router.push(`/verify-otp?email=${encodeURIComponent(email.value)}`)
  } catch (err) {
    alert(err?.data?.message || 'Gagal mengirim OTP!')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-4 bg-parkir">
    <div class="w-full max-w-md bg-white/10 backdrop-blur-xl rounded-2xl shadow-lg p-8">
      <h1 class="text-2xl font-bold text-slate-100 mb-1">Lupa Password</h1>
      <p class="text-slate-300 mb-6">Masukkan email Gmail Anda untuk menerima kode OTP</p>

      <form @submit.prevent="submitEmail" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-slate-200 mb-2">Email</label>
          <input
            v-model="email"
            type="email"
            placeholder="Masukkan email Anda"
            required
            class="w-full px-4 py-3 border border-white/20 rounded-lg focus:ring-2 focus:ring-white/30 outline-none"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-white hover:bg-slate-200 text-slate-900 font-medium py-3 rounded-lg transition disabled:opacity-60"
        >
          {{ loading ? 'Mengirim...' : 'Kirim Kode OTP' }}
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