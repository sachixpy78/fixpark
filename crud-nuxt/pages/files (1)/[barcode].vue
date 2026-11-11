<script setup>
const route = useRoute()
const config = useRuntimeConfig()

const tiket = ref(null)
const qrSvg = ref('')
const loading = ref(true)
const saving = ref(false)

const platNomor = ref('')
const fotoPlat = ref(null)
const fotoPreview = ref(null)

onMounted(async () => {
  try {
    const res = await $fetch(`/tiket/${route.params.barcode}`, {
      baseURL: config.public.apiBase,
      headers: { Accept: 'application/json' },
    })
    tiket.value = res.tiket
    qrSvg.value = res.qr_svg
    if (tiket.value.plat_nomor) platNomor.value = tiket.value.plat_nomor
  } catch {
    alert('Tiket tidak ditemukan')
  } finally {
    loading.value = false
  }
})

const onFotoChange = (e) => {
  const file = e.target.files[0]
  if (!file) return
  fotoPlat.value = file
  fotoPreview.value = URL.createObjectURL(file)
}

const simpanPlat = async () => {
  if (!platNomor.value.trim()) return alert('Masukkan nomor plat kendaraan')
  try {
    saving.value = true
    const fd = new FormData()
    fd.append('plat_nomor', platNomor.value.toUpperCase().trim())
    if (fotoPlat.value) fd.append('foto_plat', fotoPlat.value)

    const res = await $fetch(`/tiket/${route.params.barcode}/plat`, {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: fd,
    })
    tiket.value = res.tiket
    qrSvg.value = res.qr_svg
  } catch (err) {
    alert(err?.data?.message || 'Gagal menyimpan plat')
  } finally {
    saving.value = false
  }
}

const cetakStruk = () => window.print()

const formatRp = (n) => 'Rp ' + (n || 0).toLocaleString('id-ID')
const formatTgl = (d) => d ? new Date(d).toLocaleString('id-ID', {
  day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
}) : '—'
const ikonKendaraan = computed(() => tiket.value?.jenis_kendaraan === 'motor' ? '🏍️' : '🚗')
</script>

<template>
  <div class="min-h-screen bg-parkir flex items-center justify-center p-4 print:bg-white print:p-0">

    <div v-if="loading" class="text-center py-20">
      <div class="text-5xl mb-4">⏳</div>
      <p class="text-slate-300">Memuat tiket...</p>
    </div>

    <div v-else-if="tiket" class="w-full max-w-sm">

      <!-- STRUK RECEIPT -->
      <div id="struk" class="bg-white/10 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden print:shadow-none print:rounded-none">

        <!-- Header -->
        <div class="text-white px-6 py-5 text-center bg-white/10 backdrop-blur-xl border border-white/15">
          <div class="text-3xl mb-1">🅿️</div>
          <h1 class="text-xl font-black tracking-wide">E-PARKIR</h1>
          <p class="text-slate-300 text-xs mt-0.5">Plaza Andalas</p>
          <div class="mt-2 inline-block bg-white/20 rounded-full px-4 py-1 text-xs font-semibold tracking-widest">
            TIKET PARKIR
          </div>
        </div>

        <!-- Body -->
        <div class="px-6 py-5 space-y-3">

          <div class="flex justify-between items-center border-b border-dashed border-white/15 pb-3">
            <span class="text-xs text-slate-400 uppercase tracking-wide">No. Invoice</span>
            <span class="font-bold text-sm text-slate-100 font-mono">{{ tiket.no_invoice }}</span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-xs text-slate-400 uppercase tracking-wide">Kendaraan</span>
            <span class="font-semibold text-slate-100">{{ ikonKendaraan }} {{ tiket.jenis_kendaraan === 'motor' ? 'Motor' : 'Mobil' }}</span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-xs text-slate-400 uppercase tracking-wide">Jenis</span>
            <span :class="tiket.jenis === 'member' ? 'bg-emerald-400/20 text-emerald-300' : 'bg-white/10 text-slate-300'"
                  class="text-xs font-semibold px-2.5 py-1 rounded-full">
              {{ tiket.jenis === 'member' ? '💳 Member' : 'Non-Member' }}
            </span>
          </div>

          <div v-if="tiket.member" class="bg-emerald-400/10 border border-emerald-300/25 rounded-xl p-3 text-sm">
            <p class="font-semibold text-emerald-300">{{ tiket.member.nama }}</p>
            <p class="text-emerald-300 text-xs">{{ tiket.member.instansi }} · {{ tiket.member.no_member }}</p>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-xs text-slate-400 uppercase tracking-wide">Plat Nomor</span>
            <span class="font-bold text-slate-100 font-mono tracking-widest">{{ tiket.plat_nomor || '—' }}</span>
          </div>

          <div class="flex justify-between items-start">
            <span class="text-xs text-slate-400 uppercase tracking-wide">Waktu Masuk</span>
            <span class="text-sm text-slate-200 text-right font-medium">{{ formatTgl(tiket.waktu_masuk) }}</span>
          </div>

          <div class="flex justify-between items-start">
            <span class="text-xs text-slate-400 uppercase tracking-wide">Waktu Keluar</span>
            <span class="text-sm text-slate-400 italic">— (diisi saat keluar)</span>
          </div>

          <div class="flex justify-between items-center border-t border-dashed border-white/15 pt-3">
            <span class="text-xs text-slate-400 uppercase tracking-wide">Tarif</span>
            <span class="font-semibold text-slate-200">
              {{ tiket.jenis === 'member' ? '✅ Gratis (Member)' : formatRp(tiket.tarif_per_jam) + '/jam' }}
            </span>
          </div>

          <!-- QR CODE -->
          <div class="flex flex-col items-center py-4 border-t border-dashed border-white/15">
            <img v-if="qrSvg" :src="`data:image/svg+xml;base64,${qrSvg}`" alt="QR Tiket" class="w-40 h-40" />
            <p class="text-xs text-slate-400 mt-2 font-mono">{{ tiket.barcode?.substring(0, 16) }}...</p>
            <p class="text-xs text-slate-300 mt-1 text-center">Tunjukkan QR ini kepada petugas saat keluar</p>
          </div>
        </div>
      </div>

      <!-- INPUT PLAT (hanya layar) -->
      <div class="mt-5 print:hidden">

        <div v-if="!tiket.plat_nomor" class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-lg p-6">
          <h3 class="font-bold text-slate-100 mb-1">Input Plat Nomor</h3>
          <p class="text-slate-300 text-sm mb-4">
            {{ tiket.jenis === 'member' ? 'Pilih atau masukkan plat nomor yang terdaftar pada kartu member Anda' : 'Masukkan nomor plat kendaraan Anda, lalu cetak struk' }}
          </p>

          <!-- Pilihan cepat jika MEMBER -->
          <div v-if="tiket.jenis === 'member' && tiket.member?.daftar_plat?.length" class="mb-4">
            <div class="text-xs font-bold text-rose-300 uppercase tracking-wide mb-2">
              Plat Terdaftar Kartu Member:
            </div>
            <div class="grid grid-cols-1 gap-2">
              <button
                v-for="plat in tiket.member.daftar_plat"
                :key="plat"
                type="button"
                @click="platNomor = plat"
                :class="platNomor === plat ? 'bg-white text-slate-900 border-white shadow-sm' : 'bg-white/5 text-rose-200 border-white/15 hover:bg-white/10'"
                class="px-4 py-2.5 rounded-xl border text-sm font-mono font-bold tracking-wider transition text-center flex items-center justify-between"
              >
                <span>🚗 {{ plat }}</span>
                <span class="text-xs font-sans font-medium opacity-80">{{ platNomor === plat ? '✓ Dipilih' : 'Pilih' }}</span>
              </button>
            </div>
            <p class="text-[11px] text-slate-400 mt-2 italic">
              * Member hanya dapat mencetak tiket untuk plat yang telah terdaftar.
            </p>
          </div>

          <input
            v-model="platNomor"
            type="text"
            placeholder="Contoh: BA 1234 CD"
            maxlength="15"
            class="w-full px-4 py-3 border-2 border-white/15 rounded-xl focus:border-white/50 outline-none font-mono text-lg text-center uppercase tracking-widest mb-4"
            @input="platNomor = platNomor.toUpperCase()"
          />

          <label class="block w-full cursor-pointer mb-4">
            <div class="border-2 border-dashed border-white/20 rounded-xl p-4 text-center hover:border-white/40 transition-colors">
              <div v-if="!fotoPreview">
                <span class="text-3xl">📷</span>
                <p class="text-sm text-slate-300 mt-1">Upload foto plat kendaraan</p>
                <p class="text-xs text-slate-400">(opsional, max 5MB)</p>
              </div>
              <img v-else :src="fotoPreview" class="w-full max-h-32 object-cover rounded-lg" alt="Preview" />
            </div>
            <input type="file" accept="image/*" class="hidden" @change="onFotoChange" />
          </label>

          <button
            @click="simpanPlat"
            :disabled="saving || !platNomor.trim()"
            class="w-full bg-white hover:bg-slate-200 disabled:opacity-50 disabled:cursor-not-allowed text-slate-900 font-bold py-3.5 rounded-xl transition-all"
          >
            {{ saving ? 'Menyimpan...' : '💾 Simpan & Lanjut Cetak' }}
          </button>
        </div>

        <div v-else class="space-y-3">
          <div class="bg-emerald-400/10 border border-emerald-300/25 rounded-2xl p-4 text-center">
            <span class="text-2xl">✅</span>
            <p class="text-emerald-300 font-semibold mt-1">Plat nomor tersimpan</p>
            <p class="text-emerald-300 text-sm font-mono font-bold tracking-widest mt-0.5">{{ tiket.plat_nomor }}</p>
          </div>

          <button
            @click="cetakStruk"
            class="w-full bg-white hover:bg-slate-200 text-slate-900 font-bold py-4 rounded-2xl transition-all flex items-center justify-center gap-2 text-lg shadow-md"
          >
            🖨️ Cetak Struk Parkir
          </button>

          <NuxtLink to="/" class="block text-center text-slate-400 text-sm hover:text-slate-100 transition-colors">
            ← Kembali ke Beranda
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
@media print {
  body * { visibility: hidden; }
  #struk, #struk * { visibility: visible; }
  #struk { position: fixed; top: 0; left: 0; width: 80mm; }
}
</style>
