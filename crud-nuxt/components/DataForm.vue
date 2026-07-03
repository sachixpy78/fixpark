<template>
  <div class="max-w-lg mx-auto bg-gradient-to-br from-[#1a0000] via-[#2b0e0e] to-[#3b0a0a] p-8 rounded-2xl shadow-2xl mt-10 text-[#f5f2e8] border border-[#d4af37]/20">
    <h2 class="text-2xl font-bold text-[#d4af37] mb-6 text-center drop-shadow-sm">
      {{ id ? 'Edit Data' : 'Tambah Data' }}
    </h2>

    <form @submit.prevent="submitForm" class="space-y-5">
      <!-- NIK -->
      <div>
        <label class="block text-sm font-semibold text-[#e5d5a3] mb-1">NIK</label>
        <input
          v-model="form.nik"
          type="text"
          class="input-style"
          placeholder="Masukkan NIK"
          required
        />
        <p v-if="errors.nik" class="text-red-500 text-sm mt-1">{{ errors.nik[0] }}</p>
      </div>

      <!-- Nama -->
      <div>
        <label class="block text-sm font-semibold text-[#e5d5a3] mb-1">Nama</label>
        <input
          v-model="form.nama"
          type="text"
          class="input-style"
          placeholder="Masukkan Nama"
          required
        />
        <p v-if="errors.nama" class="text-red-500 text-sm mt-1">{{ errors.nama[0] }}</p>
      </div>

      <!-- Alamat -->
      <div>
        <label class="block text-sm font-semibold text-[#e5d5a3] mb-1">Alamat</label>
        <textarea
          v-model="form.alamat"
          class="input-style"
          rows="3"
          placeholder="Masukkan Alamat"
        ></textarea>
      </div>

      <!-- No Telepon -->
      <div>
        <label class="block text-sm font-semibold text-[#e5d5a3] mb-1">No. Telepon</label>
        <input
          v-model="form.no_telp"
          type="text"
          class="input-style"
          placeholder="Masukkan No. Telepon"
        />
      </div>

      <!-- Tombol Simpan / Update -->
      <button
        type="submit"
        :disabled="loading"
        class="w-full py-3 rounded-lg font-semibold shadow-md transform transition-all duration-200"
        :class="loading
          ? 'bg-[#5e1a1a]/70 cursor-not-allowed text-[#e5d5a3]'
          : 'bg-gradient-to-r from-[#7b1113] via-[#9e2622] to-[#b8392b] hover:from-[#a02b2b] hover:to-[#b8422d] text-[#fff8e7] hover:shadow-[0_0_15px_#d4af37]/40 hover:scale-[1.02]'
        "
      >
        <span v-if="loading" class="flex justify-center items-center gap-2">
          <svg
            class="animate-spin h-5 w-5 text-[#fff8e7]"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
            ></path>
          </svg>
          Menyimpan...
        </span>
        <span v-else>{{ id ? 'Update' : 'Simpan' }}</span>
      </button>

      <!-- Tombol Kembali -->
      <button
        type="button"
        @click="router.push('/')"
        class="w-full py-3 rounded-lg font-semibold bg-[#2b0e0e] border border-[#d4af37]/40 text-[#e5d5a3] hover:bg-[#3c1414] hover:text-[#fff1c2] hover:border-[#d4af37] shadow-sm transition-all duration-200"
      >
        ← Kembali
      </button>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import axios from 'axios'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()
const id = route.params.id

const form = reactive({
  nik: '',
  nama: '',
  alamat: '',
  no_telp: '',
})

const errors = reactive({})
const loading = ref(false)

onMounted(async () => {
  if (id) {
    try {
      const res = await axios.get(`http://localhost:8000/api/data/${id}`)
      Object.assign(form, res.data)
    } catch (e) {
      alert('Gagal memuat data')
    }
  }
})

const submitForm = async () => {
  loading.value = true
  Object.keys(errors).forEach(k => delete errors[k])

  try {
    if (id) {
      await axios.put(`http://localhost:8000/api/data/${id}`, form)
      alert('Data berhasil diupdate!')
    } else {
      await axios.post('http://localhost:8000/api/data', form)
      alert('Data berhasil disimpan!')
    }
    router.push('/')
  } catch (error) {
    if (error.response && error.response.status === 422) {
      Object.assign(errors, error.response.data.errors)
    } else {
      alert('Terjadi kesalahan pada server')
    }
  } finally {
    loading.value = false
  }
}
</script>

<style lang="postcss" scoped>
.input-style {
  @apply w-full border border-[#d4af37]/40 bg-[#2b0e0e] text-[#f5f2e8] placeholder-gray-400 focus:ring-2 focus:ring-[#d4af37]/60 rounded-lg p-3 outline-none transition;
}
</style>
