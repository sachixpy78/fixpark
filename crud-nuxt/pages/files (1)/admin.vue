<script setup>
const route = useRoute()
const router = useRouter()

const nama = ref('')
const foto = ref('')
const open = ref(false)

onMounted(() => {
  nama.value = localStorage.getItem('user_name') || 'Admin'
  foto.value = localStorage.getItem('user_foto') || ''
})

watch(() => route.path, () => (open.value = false))

const menu = [
  { to: '/admin/dashboard', label: 'Dashboard', icon: 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3v-6h6v6h3a1 1 0 001-1V10' },
  { to: '/admin/kelolatugas', label: 'Kelola Petugas', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
  { to: '/admin/kelolamember', label: 'Kelola Member', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
  { to: '/admin/laporanmin', label: 'Laporan', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
]

const aktif = (to) => route.path === to || route.path.startsWith(to + '/')

const logout = () => {
  localStorage.clear()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-parkir text-slate-100">
    <!-- TOPBAR MOBILE -->
    <header class="lg:hidden fixed top-0 inset-x-0 z-40 h-14 px-4 flex items-center justify-between bg-slate-900/70 backdrop-blur-xl border-b border-white/10 print:hidden">
      <div class="flex items-center gap-2 font-semibold">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 11l1.6-4.4A2 2 0 018.5 5.3h7a2 2 0 011.9 1.3L19 11M5 11h14a2 2 0 012 2v4a1 1 0 01-1 1h-2v-1H6v1H4a1 1 0 01-1-1v-4a2 2 0 012-2zM7.5 14.5h.01M16.5 14.5h.01"/></svg>
        Parkir Plaza Andalas
      </div>
      <button @click="open = !open" class="p-2 rounded-lg hover:bg-white/10" aria-label="Menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" viewBox="0 0 24 24"><path :d="open ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'"/></svg>
      </button>
    </header>

    <!-- OVERLAY MOBILE -->
    <div v-if="open" @click="open = false" class="lg:hidden fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm print:hidden"></div>

    <!-- SIDEBAR -->
    <aside
      class="fixed top-0 left-0 z-50 h-screen w-64 flex flex-col bg-slate-900/60 backdrop-blur-2xl border-r border-white/10 transition-transform duration-200 print:hidden"
      :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <div class="px-6 pt-8 pb-6 flex items-center gap-3">
        <svg class="w-11 h-11 text-slate-100 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 11l1.6-4.4A2 2 0 018.5 5.3h7a2 2 0 011.9 1.3L19 11M5 11h14a2 2 0 012 2v4a1 1 0 01-1 1h-2v-1H6v1H4a1 1 0 01-1-1v-4a2 2 0 012-2zM7.5 14.5h.01M16.5 14.5h.01"/></svg>
        <div class="leading-tight">
          <div class="font-semibold text-[17px]">Parkir Plaza</div>
          <div class="font-semibold text-[17px]">Andalas</div>
        </div>
      </div>

      <nav class="flex-1 px-4 space-y-1.5 overflow-y-auto">
        <NuxtLink
          v-for="m in menu"
          :key="m.to"
          :to="m.to"
          class="flex items-center gap-4 h-12 px-4 rounded-2xl text-[15px] transition"
          :class="aktif(m.to)
            ? 'bg-white/15 text-white ring-1 ring-white/10 shadow-inner'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path :d="m.icon"/></svg>
          {{ m.label }}
        </NuxtLink>

        <div class="my-4 mx-3 border-t border-white/15"></div>

        <button @click="logout" class="w-full flex items-center gap-4 h-12 px-4 rounded-2xl text-[15px] text-slate-300 hover:bg-white/10 hover:text-white transition">
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          Logout
        </button>
      </nav>

      <div class="p-5 flex items-center gap-3">
        <img v-if="foto" :src="foto" alt="" class="w-12 h-12 rounded-full object-cover border border-white/20" />
        <div v-else class="w-12 h-12 rounded-full bg-gradient-to-b from-slate-200 to-slate-500 grid place-items-center text-slate-600 font-bold">
          {{ (nama || 'A').charAt(0).toUpperCase() }}
        </div>
        <div class="min-w-0">
          <div class="font-semibold truncate">{{ nama }}</div>
          <div class="text-xs text-slate-400">Admin</div>
        </div>
      </div>
    </aside>

    <!-- KONTEN -->
    <div class="lg:pl-64 pt-14 lg:pt-0 min-h-screen print:pl-0 print:pt-0">
      <slot />
    </div>
  </div>
</template>
