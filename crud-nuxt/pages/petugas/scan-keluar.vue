<script setup>
definePageMeta({ layout: 'petugas', middleware: 'auth-petugas' })

const config = useRuntimeConfig()
const router = useRouter()

const barcode = ref('')
const loading = ref(false)
const result = ref(null)
const popup = ref(null)
const scanInput = ref(null)
const nama = ref('')
const petugasId = ref(0)

const nominalBayar = ref('')
const bayarSuccess = ref(false)

onMounted(() => {
  nama.value = localStorage.getItem('user_name') || ''
  petugasId.value = parseInt(localStorage.getItem('user_id') || '0')
  nextTick(() => scanInput.value?.focus())
})

const scan = async () => {
  const kode = barcode.value.trim()
  if (!kode) return
  try {
    loading.value = true
    result.value = null
    popup.value = null
    bayarSuccess.value = false
    nominalBayar.value = ''
    const res = await $fetch('/parkir/scan-keluar', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: { barcode: kode },
    })
    result.value = res
    if (res.status === 'sudah_keluar') popup.value = 'sudah_keluar'
  } catch (err) {
    alert(err?.data?.message || 'Tiket tidak ditemukan. Pastikan kode QR atau nomor invoice benar.')
    barcode.value = ''
    nextTick(() => scanInput.value?.focus())
  } finally {
    loading.value = false
  }
}

const totalTagihan = computed(() => {
  if (!result.value?.preview_keluar) return 0
  return Number(result.value.preview_keluar.total_harga) || 0
})

const nominalNumber = computed(() => Number(nominalBayar.value) || 0)
const kembalian = computed(() => Math.max(0, nominalNumber.value - totalTagihan.value))
const sisaKurang = computed(() => Math.max(0, totalTagihan.value - nominalNumber.value))
const isNominalCukup = computed(() => nominalNumber.value >= totalTagihan.value)
const isMember = computed(() => result.value?.preview_keluar?.is_member === true)

const prosesBayar = async () => {
  if (!result.value) return
  if (!isMember.value && !isNominalCukup.value) return

  try {
    loading.value = true
    const bayar = isMember.value ? 0 : nominalNumber.value
    await $fetch('/parkir/bayar', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: {
        barcode: result.value.tiket.barcode,
        nominal_bayar: bayar,
        petugas_id: petugasId.value,
      },
    })
    bayarSuccess.value = true
  } catch (err) {
    alert(err?.data?.message || 'Gagal memproses pembayaran')
  } finally {
    loading.value = false
  }
}

const reset = () => {
  result.value = null
  popup.value = null
  bayarSuccess.value = false
  nominalBayar.value = ''
  barcode.value = ''
  nextTick(() => scanInput.value?.focus())
}

const logout = () => {
  localStorage.clear()
  router.push('/login')
}

const formatRp = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID')

const formatTgl = (d) => {
  if (!d) return '—'
  const normalized = d.includes('T') ? d : d.replace(' ', 'T')
  const dateObj = new Date(normalized)
  if (isNaN(dateObj.getTime())) return d
  return dateObj.toLocaleString('id-ID', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit',
    hour12: false,
  })
}

const formatDurasi = (prev) => {
  if (!prev) return '—'
  if (prev.durasi_teks) return prev.durasi_teks
  const sec = parseInt(prev.total_detik || 0) || Math.round(Number(prev.total_menit || 0) * 60)
  const h = Math.floor(sec / 3600)
  const m = Math.floor((sec % 3600) / 60)
  const s = sec % 60
  const parts = []
  if (h > 0) parts.push(`${h} jam`)
  if (m > 0 || h > 0) parts.push(`${m} menit`)
  parts.push(`${s} detik`)
  return parts.join(' ')
}

const cetakInvoice = () => {
  if (!result.value) return

  const tiket = result.value.tiket
  const preview = result.value.preview_keluar

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Invoice ${tiket.no_invoice}</title>
      <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 24px; color: #1e293b; }
        .invoice { max-width: 420px; margin: 0 auto; border: 2px solid #1e293b; border-radius: 12px; padding: 24px; }
        .header { text-align: center; border-bottom: 2px dashed #cbd5e1; padding-bottom: 12px; margin-bottom: 12px; }
        .header h1 { font-size: 20px; margin-bottom: 4px; }
        .header p { font-size: 12px; color: #64748b; }
        .row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .row .label { color: #64748b; }
        .row .value { font-weight: 600; text-align: right; }
        .divider { border-top: 2px dashed #cbd5e1; margin: 10px 0; }
        .total { font-size: 18px; font-weight: 800; }
        .footer { text-align: center; margin-top: 16px; font-size: 11px; color: #64748b; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .badge-member { background: #dcfce7; color: #166534; }
        .badge-non { background: #dbeafe; color: #1e40af; }
        @media print { body { padding: 0; } .invoice { border: 2px solid #000; } }
      </style>
    </head>
    <body>
      <div class="invoice">
        <div class="header">
          <h1>INVOICE PARKIR</h1>
          <p>${tiket.no_invoice}</p>
          <p style="margin-top:4px;">Petugas: ${nama.value}</p>
        </div>

        <div class="row"><span class="label">Jenis Tiket</span><span class="value"><span class="badge ${tiket.jenis === 'member' ? 'badge-member' : 'badge-non'}">${tiket.jenis === 'member' ? 'Member' : 'Non-Member'}</span></span></div>
        <div class="row"><span class="label">Kendaraan</span><span class="value" style="text-transform:capitalize;">${tiket.jenis_kendaraan || '—'}</span></div>
        <div class="row"><span class="label">Plat Nomor</span><span class="value">${tiket.plat_nomor || '—'}</span></div>
        ${tiket.member ? `<div class="row"><span class="label">Nama Member</span><span class="value">${tiket.member.nama}</span></div>` : ''}
        <div class="divider"></div>
        <div class="row"><span class="label">Waktu Masuk</span><span class="value">${formatTgl(tiket.waktu_masuk)}</span></div>
        <div class="row"><span class="label">Waktu Keluar</span><span class="value">${formatTgl(preview.waktu_keluar)}</span></div>
        <div class="row"><span class="label">Durasi</span><span class="value">${formatDurasi(preview)}</span></div>
        <div class="divider"></div>
        <div class="row"><span class="label">Total Tarif</span><span class="value">${formatRp(totalTagihan.value)}</span></div>
        <div class="row"><span class="label">Uang Bayar</span><span class="value">${formatRp(nominalNumber.value)}</span></div>
        <div class="divider"></div>
        <div class="row total"><span>Kembalian</span><span>${formatRp(kembalian.value)}</span></div>

        <div class="footer">
          <p>Terima kasih telah menggunakan layanan parkir kami.</p>
          <p style="margin-top:4px;">Dicetak: ${new Date().toLocaleString('id-ID')}</p>
        </div>
      </div>
      <script>
        window.onload = () => { window.print(); };
      <\/script>
    </body>
    </html>
  `

  const win = window.open('', '_blank', 'width=480,height=640')
  if (win) {
    win.document.write(html)
    win.document.close()
  } else {
    alert('Gagal membuka jendela cetak. Izinkan popup untuk situs ini.')
  }
}
</script>

<template>
  <div>
    <div class="p-6 md:p-8">
      <div class="max-w-5xl mx-auto">
        <div class="mb-8">
          <h1 class="text-3xl font-black text-white mb-2">Scan Keluar</h1>
          <p class="text-slate-400">Scan QR tiket atau kartu member untuk memproses kendaraan keluar</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2">
            <div class="bg-slate-800/60 backdrop-blur-xl rounded-3xl border border-white/10 p-6 md:p-8">
              <div class="flex items-center gap-3 mb-6">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                <h2 class="text-xl font-bold text-white">Scan QR Tiket / Kartu Member</h2>
              </div>

              <div v-if="!result" class="relative mb-6">
                <div class="relative bg-slate-950 rounded-2xl overflow-hidden border-2 border-white/10">
                  <div class="absolute top-4 left-4 z-10 flex items-center gap-2 bg-slate-900/80 backdrop-blur-sm px-3 py-1.5 rounded-full border border-white/10">
                    <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                    <span class="text-xs font-medium text-white">Kamera aktif</span>
                  </div>

                  <div class="aspect-video flex items-center justify-center relative">
                    <div class="absolute top-8 left-8 w-16 h-16 border-l-4 border-t-4 border-white/60 rounded-tl-2xl"></div>
                    <div class="absolute top-8 right-8 w-16 h-16 border-r-4 border-t-4 border-white/60 rounded-tr-2xl"></div>
                    <div class="absolute bottom-8 left-8 w-16 h-16 border-l-4 border-b-4 border-white/60 rounded-bl-2xl"></div>
                    <div class="absolute bottom-8 right-8 w-16 h-16 border-r-4 border-b-4 border-white/60 rounded-br-2xl"></div>

                    <div class="absolute inset-0 flex items-center justify-center">
                      <div class="w-64 h-0.5 bg-gradient-to-r from-transparent via-cyan-400 to-transparent shadow-lg shadow-cyan-400/50 animate-pulse"></div>
                    </div>

                    <div class="absolute inset-0 flex items-center justify-center gap-16">
                      <div class="w-8 h-8 border-2 border-white/20 rounded-lg"></div>
                      <div class="w-8 h-8 border-2 border-white/20 rounded-lg"></div>
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center">
                      <div class="w-8 h-8 border-2 border-white/20 rounded-lg mt-16"></div>
                    </div>
                  </div>

                  <p class="text-center text-slate-400 text-sm py-4">Arahkan QR ke dalam bingkai</p>
                </div>
              </div>

              <div v-if="!result" class="flex gap-3">
                <div class="relative flex-1">
                  <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0016.803 15.803z"/>
                  </svg>
                  <input
                    ref="scanInput"
                    v-model="barcode"
                    type="text"
                    placeholder="Atau ketik kode tiket / kartu member"
                    class="w-full pl-12 pr-4 py-3.5 bg-slate-700/50 border-2 border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none text-white placeholder:text-slate-500 transition-all"
                    @keydown.enter="scan"
                    :disabled="loading"
                    autofocus
                  />
                </div>
                <button
                  @click="scan"
                  :disabled="loading || !barcode.trim()"
                  class="bg-white hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed text-slate-900 font-bold px-6 py-3.5 rounded-xl transition-all shadow-lg flex items-center gap-2"
                >
                  <svg v-if="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                  </svg>
                  <span>{{ loading ? 'Memproses...' : 'Cari Tiket' }}</span>
                </button>
              </div>

              <div v-else-if="result.status === 'aktif'" class="space-y-4">
                <div class="bg-slate-700/30 rounded-2xl border border-white/10 p-5">
                  <div class="flex justify-between items-start mb-4">
                    <div>
                      <h3 class="font-bold text-white text-lg font-mono">{{ result.tiket.no_invoice }}</h3>
                      <span class="text-xs bg-emerald-500/20 text-emerald-300 font-semibold px-2.5 py-1 rounded-full border border-emerald-500/30 inline-flex items-center gap-1.5 mt-2">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Tiket Aktif
                      </span>
                    </div>
                    <span :class="result.tiket.jenis === 'member' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-blue-500/20 text-blue-300 border-blue-500/30'" class="text-xs font-bold px-3 py-1.5 rounded-full border">
                      {{ result.tiket.jenis === 'member' ? 'Member' : 'Non-Member' }}
                    </span>
                  </div>

                  <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <p class="text-slate-400 text-xs uppercase tracking-wide mb-1">Kendaraan</p>
                      <p class="font-semibold text-white capitalize">{{ result.tiket.jenis_kendaraan }}</p>
                    </div>
                    <div>
                      <p class="text-slate-400 text-xs uppercase tracking-wide mb-1">Plat Nomor</p>
                      <p class="font-bold text-white font-mono">{{ result.tiket.plat_nomor || '—' }}</p>
                    </div>
                    <div v-if="result.tiket.member" class="col-span-2">
                      <p class="text-slate-400 text-xs uppercase tracking-wide mb-1">Nama Member</p>
                      <p class="font-semibold text-white">{{ result.tiket.member.nama }}</p>
                    </div>
                    <div class="col-span-2">
                      <p class="text-slate-400 text-xs uppercase tracking-wide mb-1">Waktu Masuk</p>
                      <p class="font-semibold text-white">{{ formatTgl(result.tiket.waktu_masuk) }}</p>
                    </div>
                  </div>
                </div>

                <div class="bg-slate-700/30 rounded-2xl border border-white/10 p-5">
                  <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="font-bold text-white">Kalkulasi Parkir</h3>
                  </div>
                  <div class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                      <span class="text-slate-300">Waktu Keluar</span>
                      <span class="font-medium text-white">{{ formatTgl(result.preview_keluar.waktu_keluar) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-300">Total Durasi</span>
                      <span class="font-bold text-white">{{ formatDurasi(result.preview_keluar) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-white/10 pt-2.5 mt-2">
                      <span class="font-bold text-white">Total Tagihan</span>
                      <span v-if="isMember" class="font-black text-xl text-emerald-400">GRATIS</span>
                      <span v-else class="font-black text-xl text-white">{{ formatRp(totalTagihan) }}</span>
                    </div>
                  </div>
                </div>

                <div class="pt-2">
                  <button @click="reset" class="w-full py-3.5 border-2 border-slate-600 text-slate-300 hover:border-white/30 hover:text-white font-semibold rounded-xl transition-all">
                    ← Scan Tiket Lain
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div>
            <div v-if="!result" class="space-y-4">
              <div class="bg-slate-800/60 backdrop-blur-xl rounded-2xl border border-white/10 p-6">
                <h3 class="font-bold text-white mb-4">Alur Keluar</h3>
                <div class="space-y-4">
                  <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-white text-slate-900 flex items-center justify-center font-bold text-sm flex-shrink-0">1</div>
                    <div>
                      <p class="font-semibold text-white text-sm">Scan QR</p>
                      <p class="text-xs text-slate-400">Tiket atau kartu member</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-700 text-slate-300 flex items-center justify-center font-bold text-sm flex-shrink-0">2</div>
                    <div>
                      <p class="font-semibold text-white text-sm">Cek Detail</p>
                      <p class="text-xs text-slate-400">Durasi parkir & jenis tiket</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-700 text-slate-300 flex items-center justify-center font-bold text-sm flex-shrink-0">3</div>
                    <div>
                      <p class="font-semibold text-white text-sm">Pembayaran</p>
                      <p class="text-xs text-slate-400">Tarif, uang bayar, kembalian</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-700 text-slate-300 flex items-center justify-center font-bold text-sm flex-shrink-0">4</div>
                    <div>
                      <p class="font-semibold text-white text-sm">Palang Terbuka</p>
                      <p class="text-xs text-slate-400">Kendaraan keluar & invoice</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-slate-800/60 backdrop-blur-xl rounded-2xl border border-white/10 p-6">
                <h3 class="font-bold text-white mb-4">Scan Terakhir</h3>
                <div class="space-y-3">
                  <div class="flex items-center justify-between p-3 bg-slate-700/30 rounded-xl border border-white/5">
                    <div>
                      <p class="text-sm font-mono font-bold text-white">TKT-0141 · BA 2210 CD</p>
                      <p class="text-xs text-slate-400 mt-0.5">Non-Member</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Selesai</span>
                  </div>
                  <div class="flex items-center justify-between p-3 bg-slate-700/30 rounded-xl border border-white/5">
                    <div>
                      <p class="text-sm font-mono font-bold text-white">MBR003 · Salsabila Putri</p>
                      <p class="text-xs text-slate-400 mt-0.5">Member</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30">Member</span>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="bg-slate-800/60 backdrop-blur-xl rounded-2xl border border-white/10 p-6 sticky top-6">
              <div class="flex items-center gap-3 mb-5">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h3 class="font-bold text-white text-lg">Pembayaran</h3>
              </div>

              <div class="bg-slate-700/50 rounded-xl p-4 mb-4 border border-white/10">
                <p class="text-xs text-slate-400 uppercase tracking-wide mb-1">Total yang Harus Dibayar</p>
                <p v-if="!result" class="text-3xl font-black text-white">Rp 0</p>
                <p v-else-if="isMember" class="text-3xl font-black text-emerald-400">Rp 0</p>
                <p v-else class="text-3xl font-black text-white">{{ formatRp(totalTagihan) }}</p>
              </div>

              <div v-if="isMember" class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4 mb-4">
                <div class="flex items-start gap-2">
                  <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <div>
                    <p class="text-emerald-300 text-sm font-semibold">Member aktif tidak membayar parkir.</p>
                    <p v-if="result?.tiket?.member?.masa_aktif" class="text-emerald-300/80 text-xs mt-0.5">Masa aktif sampai {{ formatTgl(result.tiket.member.masa_aktif) }}.</p>
                  </div>
                </div>
              </div>

              <div v-if="result" class="space-y-3 mb-4">
                <div class="flex justify-between items-center py-2 border-b border-white/10">
                  <span class="text-sm text-slate-300">Status Member</span>
                  <span :class="isMember ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-slate-700 text-slate-300 border-slate-600'" class="text-xs font-semibold px-2.5 py-1 rounded-full border">
                    {{ isMember ? 'Aktif' : 'Non-Member' }}
                  </span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-white/10">
                  <span class="text-sm text-slate-300">Metode</span>
                  <span class="text-sm font-semibold text-white">Iuran Bulanan</span>
                </div>
              </div>

              <div v-if="result && !isMember" class="space-y-3 mb-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-2">Nominal Uang Diterima (Rp)</label>
                  <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-sm">Rp</span>
                    <input
                      v-model.number="nominalBayar"
                      type="number"
                      placeholder="Masukkan nominal uang"
                      class="w-full pl-12 pr-4 py-3.5 bg-slate-700/50 border-2 border-slate-600 rounded-xl text-base font-bold outline-none transition-all text-white placeholder:text-slate-500"
                      :class="nominalBayar ? (isNominalCukup ? 'border-emerald-500/50 bg-emerald-500/10' : 'border-amber-500/50 bg-amber-500/10') : ''"
                    />
                  </div>
                </div>

                <div class="grid grid-cols-4 gap-1.5">
                  <button
                    v-for="uang in [totalTagihan, 5000, 10000, 20000, 50000, 100000]"
                    :key="uang"
                    type="button"
                    @click="nominalBayar = uang"
                    class="py-1.5 text-[10px] font-semibold rounded-lg border transition text-center"
                    :class="nominalNumber === uang ? 'bg-blue-600 text-white border-blue-500' : 'bg-slate-700/50 text-slate-300 hover:bg-slate-700 border-slate-600'"
                  >
                    {{ uang === totalTagihan ? 'Pas' : Number(uang/1000) + 'rb' }}
                  </button>
                </div>

                <div v-if="nominalBayar" class="p-3 rounded-xl text-xs">
                  <div v-if="isNominalCukup" class="bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 flex justify-between items-center">
                    <div>
                      <p class="font-bold">Uang Cukup</p>
                      <p class="text-[10px] text-emerald-300/80 mt-0.5">Pembayaran dapat diproses</p>
                    </div>
                    <div class="text-right">
                      <p class="text-[10px] uppercase font-bold">Kembalian</p>
                      <p class="text-lg font-black">{{ formatRp(kembalian) }}</p>
                    </div>
                  </div>
                  <div v-else class="bg-rose-500/20 border border-rose-500/30 text-rose-300 flex justify-between items-center">
                    <div>
                      <p class="font-bold">Uang Kurang</p>
                      <p class="text-[10px] text-rose-300/80 mt-0.5">Minimal {{ formatRp(totalTagihan) }}</p>
                    </div>
                    <div class="text-right">
                      <p class="text-[10px] uppercase font-bold">Kurang</p>
                      <p class="text-base font-black">{{ formatRp(sisaKurang) }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <button
                v-if="result"
                @click="prosesBayar"
                :disabled="loading || (!isMember && !isNominalCukup)"
                class="w-full py-3.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl transition-all flex items-center justify-center gap-2 shadow-lg shadow-blue-900/40"
              >
                <svg v-if="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ loading ? 'Memproses...' : (isMember ? 'Konfirmasi Keluar' : 'Konfirmasi Bayar') }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="popup === 'sudah_keluar'" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-slate-800 rounded-3xl p-8 max-w-sm w-full text-center shadow-2xl border-2 border-orange-500/40">
        <div class="flex justify-center mb-4">
          <div class="w-16 h-16 rounded-full bg-orange-500/20 flex items-center justify-center">
            <svg class="w-9 h-9 text-orange-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
          </div>
        </div>
        <h2 class="text-xl font-black text-orange-400 mb-2">Kendaraan Sudah Keluar!</h2>
        <p class="text-slate-300 text-sm mb-4">Tiket ini sudah diproses sebelumnya dan tidak dapat digunakan kembali.</p>
        <div v-if="result?.tiket" class="bg-slate-900/60 rounded-xl p-4 text-left text-sm mb-5 space-y-1.5">
          <p><span class="text-slate-400">Invoice:</span> <span class="font-mono font-bold text-white">{{ result.tiket.no_invoice }}</span></p>
          <p><span class="text-slate-400">Kendaraan:</span> <span class="text-white">{{ result.tiket.jenis_kendaraan }}</span></p>
          <p><span class="text-slate-400">Plat:</span> <span class="font-bold text-white">{{ result.tiket.plat_nomor || '—' }}</span></p>
          <p><span class="text-slate-400">Keluar:</span> <span class="text-white text-xs">{{ formatTgl(result.tiket.waktu_keluar) }}</span></p>
        </div>
        <button @click="reset" class="w-full bg-white hover:bg-slate-200 text-slate-900 font-bold py-3 rounded-xl transition-all">
          Tutup & Scan Lagi
        </button>
      </div>
    </div>

    <div v-if="popup === 'sukses_member'" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-slate-800 rounded-3xl p-8 max-w-sm w-full text-center shadow-2xl border-2 border-emerald-500/40">
        <div class="flex justify-center mb-4">
          <div class="w-16 h-16 rounded-full bg-emerald-500/20 flex items-center justify-center">
            <svg class="w-9 h-9 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <h2 class="text-2xl font-black text-emerald-400 mb-2">Berhasil Keluar!</h2>
        <p class="text-slate-300 text-sm mb-3">Kendaraan telah berhasil keluar dari area parkir.</p>
        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-3 mb-5">
          <div class="flex items-center justify-center gap-2">
            <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            <p class="text-emerald-300 font-bold text-sm">Member — Pembayaran Gratis</p>
          </div>
        </div>
        <button @click="reset" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl transition-all">
          Selesai
        </button>
      </div>
    </div>

    <!-- POPUP: Sukses Pembayaran dengan Video Animasi Palang -->
    <div v-if="bayarSuccess" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-slate-800/95 backdrop-blur-xl rounded-2xl max-w-2xl w-full shadow-2xl border-2 border-emerald-500/40 overflow-hidden">
        
        <!-- Video Animasi Palang Pintu (Full Width, No Padding) -->
        <div class="relative w-full bg-slate-900">
          <video
            autoplay
            loop
            muted
            playsinline
            class="w-full h-auto block"
          >
            <source src="/palang-keluar-hd.mp4" type="video/mp4" />
            Browser Anda tidak mendukung video.
          </video>
        </div>

        <!-- Content -->
        <div class="p-5">
          <!-- Success Message -->
          <div class="text-center mb-4">
            <div class="flex items-center justify-center gap-2 mb-1">
              <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <h2 class="text-xl font-black text-emerald-400">Silakan Keluar</h2>
            </div>
            <p class="text-slate-300 text-sm">Pembayaran berhasil. Palang parkir terbuka.</p>
          </div>

          <!-- Payment Details -->
          <div class="bg-slate-700/50 rounded-xl p-3.5 mb-4 border border-white/10 space-y-1.5">
            <div class="flex justify-between text-sm">
              <span class="text-slate-300">Total Tarif:</span>
              <span class="font-bold text-white">{{ formatRp(totalTagihan) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-slate-300">Uang Bayar:</span>
              <span class="font-bold text-white">{{ formatRp(nominalNumber) }}</span>
            </div>
            <div class="flex justify-between text-sm border-t border-white/10 pt-1.5">
              <span class="text-slate-300">Kembalian:</span>
              <span class="font-bold text-emerald-400">{{ formatRp(kembalian) }}</span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex gap-2.5">
            <button @click="reset" class="flex-1 py-2.5 border-2 border-slate-600 text-slate-300 hover:border-white/30 hover:text-white font-semibold rounded-xl transition-all text-sm">
              Scan Berikutnya
            </button>
            <button
              @click="cetakInvoice"
              class="flex-1 py-2.5 bg-white hover:bg-slate-100 text-slate-900 font-bold rounded-xl transition-all flex items-center justify-center gap-2 text-sm"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
              </svg>
              Cetak Invoice
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>