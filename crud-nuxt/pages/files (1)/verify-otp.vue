<script setup>
import { ref, onMounted } from 'vue'

const config = useRuntimeConfig()
const route = useRoute()
const router = useRouter()

const email = ref(route.query.email || '')
const otp = ref('')
const loading = ref(false)

onMounted(() => {
  if (!email.value) router.push('/forgot')
})

const verifyOtp = async () => {
  try {
    loading.value = true

    await $fetch('/verify-otp', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: { email: email.value, otp: otp.value },
    })

    // OTP dibawa ke halaman reset, backend memverifikasi ulang di sana
    sessionStorage.setItem('reset_otp', otp.value)
    router.push(`/reset?email=${encodeURIComponent(email.value)}`)
  } catch (err) {
    alert(err?.data?.message || 'OTP tidak valid atau sudah kadaluarsa.')
  } finally {
    loading.value = false
  }
}

const resendOtp = async () => {
  try {
    loading.value = true

    await $fetch('/forgot-password', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: { email: email.value },
    })

    alert('OTP baru berhasil dikirim. OTP sebelumnya tidak berlaku lagi.')
  } catch (err) {
    alert(err?.data?.message || 'Gagal mengirim ulang OTP!')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-4 bg-parkir">
    <div class="w-full max-w-md bg-white/10 backdrop-blur-xl rounded-2xl shadow-lg p-8">
      <h1 class="text-2xl font-bold text-slate-100 mb-1">Verifikasi OTP</h1>
      <p class="text-slate-300 mb-6">Masukkan kode OTP yang telah dikirim ke email Anda</p>

      <form @submit.prevent="verifyOtp" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-slate-200 mb-2">Email</label>
          <div class="w-full px-4 py-3 border border-white/20 rounded-lg bg-white/5">
            <b class="text-slate-200">{{ email }}</b>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-200 mb-2">Kode OTP</label>
          <input
            v-model="otp"
            type="text"
            inputmode="numeric"
            maxlength="6"
            placeholder="6 digit kode OTP"
            required
            class="w-full px-4 py-3 border border-white/20 rounded-lg focus:ring-2 focus:ring-white/30 outline-none tracking-widest text-center"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-white hover:bg-slate-200 text-slate-900 font-medium py-3 rounded-lg transition disabled:opacity-60"
        >
          {{ loading ? 'Memverifikasi...' : 'Verifikasi OTP' }}
        </button>

        <button
          type="button"
          @click="resendOtp"
          :disabled="loading"
          class="w-full text-rose-300 hover:text-white font-medium py-2 hover:underline disabled:opacity-50"
        >
          Kirim Ulang OTP
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