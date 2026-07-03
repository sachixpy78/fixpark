<template>
  <div class="p-6 min-h-screen bg-gradient-to-br from-[#1a0000] via-[#2b0e0e] to-[#4b2b00]">
    <div class="max-w-4xl mx-auto bg-[#1e0d0d]/95 rounded-xl shadow-2xl p-6 text-white border border-[#a67c00]/30">
      <!-- Header dan tombol -->
      <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-3">
        <h1 class="text-3xl font-bold text-[#d4af37] drop-shadow-lg">Daftar Data</h1>

        <div class="flex gap-3 items-center w-full md:w-auto">
          <!-- Input Pencarian -->
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nama atau NIK..."
            class="border border-[#a67c00]/40 bg-[#2b0e0e] text-white rounded-lg px-3 py-2 w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-[#d4af37]"
          />

          <!-- Tombol Tambah -->
          <NuxtLink
            to="/create"
            class="bg-gradient-to-r from-[#b22222] to-[#a67c00] hover:from-[#d43f3f] hover:to-[#e0b84a] text-white px-4 py-2 rounded-lg shadow-md transition-transform hover:scale-105"
          >
            + Tambah Data
          </NuxtLink>
        </div>
      </div>

      <!-- Tabel Data -->
      <table class="w-full border-collapse text-white">
        <thead>
          <tr class="bg-gradient-to-r from-[#4a0e0e] to-[#5a2a00] text-[#f8e4b7]">
            <th class="border border-[#a67c00]/40 p-2">ID</th>
            <th class="border border-[#a67c00]/40 p-2">NIK</th>
            <th class="border border-[#a67c00]/40 p-2">Nama</th>
            <th class="border border-[#a67c00]/40 p-2">Alamat</th>
            <th class="border border-[#a67c00]/40 p-2">No. Telp</th>
            <th class="border border-[#a67c00]/40 p-2">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(item, index) in paginatedData"
            :key="item.id"
            class="hover:bg-gradient-to-r hover:from-[#3d0b0b] hover:to-[#3f2600] transition"
          >
            <td class="border border-[#5e1a1a] p-2 text-center">{{ startIndex + index + 1 }}</td>
            <td class="border border-[#5e1a1a] p-2">{{ item.nik }}</td>
            <td class="border border-[#5e1a1a] p-2">{{ item.nama }}</td>
            <td class="border border-[#5e1a1a] p-2">{{ item.alamat }}</td>
            <td class="border border-[#5e1a1a] p-2">{{ item.no_telp }}</td>
            <td class="border border-[#5e1a1a] p-2 text-center">
              <NuxtLink
                :to="`/${item.id}`"
                class="text-[#ffd700] hover:text-[#fff4b3] font-semibold mr-2"
              >
                Edit
              </NuxtLink>
              <button
                @click="hapusData(item.id)"
                class="text-[#ff6666] hover:text-[#ff9999] font-semibold"
              >
                Hapus
              </button>
            </td>
          </tr>

          <!-- Jika tidak ada hasil -->
          <tr v-if="paginatedData.length === 0">
            <td colspan="6" class="text-center text-gray-400 py-4">
              Tidak ada data ditemukan.
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Paginasi -->
      <div
        v-if="totalPages > 1"
        class="flex justify-center items-center gap-2 mt-6"
      >
        <button
          @click="prevPage"
          :disabled="currentPage === 1"
          class="px-3 py-1 bg-[#5e1a1a] rounded-lg disabled:opacity-50 hover:bg-[#7a2222] transition"
        >
          ‹
        </button>

        <button
          v-for="page in totalPages"
          :key="page"
          @click="currentPage = page"
          :class="[ 
            'px-3 py-1 rounded-lg transition font-semibold',
            currentPage === page
              ? 'bg-gradient-to-r from-[#b22222] to-[#a67c00] text-white shadow-md'
              : 'bg-[#2b0e0e] hover:bg-[#4a1515]'
          ]"
        >
          {{ page }}
        </button>

        <button
          @click="nextPage"
          :disabled="currentPage === totalPages"
          class="px-3 py-1 bg-[#5e1a1a] rounded-lg disabled:opacity-50 hover:bg-[#7a2222] transition"
        >
          ›
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { ref, computed, onMounted } from 'vue'

const dataList = ref([])
const searchQuery = ref('')

// Pagination
const currentPage = ref(1)
const itemsPerPage = 5

// Ambil data dari API
const getData = async () => {
  const res = await axios.get('http://localhost:8000/api/data')
  dataList.value = res.data
}

// Fitur hapus data
const hapusData = async (id) => {
  if (confirm('Yakin ingin menghapus data ini?')) {
    await axios.delete(`http://localhost:8000/api/data/${id}`)
    alert('Data berhasil dihapus!')
    getData()
  }
}

// Filter data berdasarkan pencarian
const filteredData = computed(() => {
  if (!searchQuery.value) return dataList.value
  return dataList.value.filter(item =>
    item.nama.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    item.nik.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

// Hitung total halaman
const totalPages = computed(() =>
  Math.ceil(filteredData.value.length / itemsPerPage)
)

// Ambil data sesuai halaman aktif
const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredData.value.slice(start, end)
})

const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage)

// Navigasi halaman
const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++
}
const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--
}

onMounted(() => {
  getData()
})
</script>
