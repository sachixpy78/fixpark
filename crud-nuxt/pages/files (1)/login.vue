<script setup>
import { ref, reactive } from 'vue'

const config = useRuntimeConfig()
const router = useRouter()

const loading = ref(false)
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
  <div class="min-h-screen flex items-center justify-center p-4 bg-parkir">
    <div class="w-full max-w-md bg-white/10 backdrop-blur-xl rounded-2xl shadow-lg p-8">
      <div class="text-center mb-6">
        <div class="text-4xl mb-2">🅿️</div>
        <h1 class="text-2xl font-bold text-slate-100 mb-1">Masuk ke Akun</h1>
        <p class="text-slate-300">E-Parkir Plaza Andalas</p>
      </div>

      <form @submit.prevent="login" class="space-y-4">
        <select
          v-model="form.level"
          class="w-full px-4 py-3 border border-white/20 rounded-lg focus:ring-2 focus:ring-white/30 outline-none"
        >
          <option value="petugas">Petugas</option>
          <option value="admin">Admin</option>
        </select>

        <input
          v-model="form.login"
          type="text"
          placeholder="Email / Username"
          required
          class="w-full px-4 py-3 border border-white/20 rounded-lg focus:ring-2 focus:ring-white/30 outline-none"
        />

        <input
          v-model="form.password"
          type="password"
          placeholder="Password"
          required
          class="w-full px-4 py-3 border border-white/20 rounded-lg focus:ring-2 focus:ring-white/30 outline-none"
        />

        <div class="text-right">
          <NuxtLink to="/forgot" class="text-sm text-sky-300 hover:text-white hover:underline">
            Lupa password?
          </NuxtLink>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-white hover:bg-slate-200 text-slate-900 font-medium py-3 rounded-lg transition disabled:opacity-60"
        >
          {{ loading ? 'Memproses...' : 'Login' }}
        </button>
      </form>
    </div>
  </div>
</template>

