<?php

namespace App\Http\Controllers;

use App\Models\TiketParkir;
use App\Models\Transaksi;
use Illuminate\Http\Request;

class ParkirController extends Controller
{
    // ========================
    // SCAN KELUAR (by petugas)
    // POST /api/parkir/scan-keluar
    // ========================
    public function scanKeluar(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string',
        ]);

        $query = trim($request->barcode);

        $tiket = TiketParkir::with(['member', 'transaksi'])
                             ->where(function ($q) use ($query) {
                                 $q->where('barcode', $query)
                                   ->orWhere('no_invoice', $query);
                             })
                             ->first();

        if (!$tiket) {
            return response()->json(['message' => 'Tiket tidak ditemukan'], 404);
        }

        // Tiket sudah selesai (pernah discan & dibayar)
        if ($tiket->status === 'selesai') {
            return response()->json([
                'status'  => 'sudah_keluar',
                'message' => 'Kendaraan ini sudah keluar sebelumnya',
                'tiket'   => $this->formatTiket($tiket),
            ], 200);
        }

        // Hitung preview total (tanpa simpan)
        $waktuKeluar = now();
        $totalDetik  = max(1, (int) $tiket->waktu_masuk->diffInSeconds($waktuKeluar));
        $jamDurasi   = (int) floor($totalDetik / 3600);
        $sisaMenit   = (int) floor(($totalDetik % 3600) / 60);
        $sisaDetik   = (int) ($totalDetik % 60);

        // Format teks durasi presisi (jam, menit, detik)
        $parts = [];
        if ($jamDurasi > 0) $parts[] = "{$jamDurasi} jam";
        if ($sisaMenit > 0) $parts[] = "{$sisaMenit} menit";
        $parts[] = "{$sisaDetik} detik";
        $durasiTeks = implode(' ', $parts);

        // Tarif dihitung per jam (dibulatkan ke atas, minimal 1 jam untuk non-member)
        $jamTarif   = max(1, (int) ceil($totalDetik / 3600));
        $totalHarga = $tiket->jenis === 'member' ? 0 : ($jamTarif * $tiket->tarif_per_jam);

        return response()->json([
            'status' => 'aktif',
            'tiket'  => $this->formatTiket($tiket),
            'preview_keluar' => [
                'waktu_keluar' => $waktuKeluar->format('Y-m-d H:i:s'),
                'total_detik'  => $totalDetik,
                'total_menit'  => (int) floor($totalDetik / 60),
                'total_jam'    => $jamTarif,
                'durasi_teks'  => $durasiTeks,
                'durasi_jam'   => $jamDurasi,
                'durasi_menit' => $sisaMenit,
                'durasi_detik' => $sisaDetik,
                'total_harga'  => $totalHarga,
                'is_member'    => $tiket->jenis === 'member',
            ],
        ]);
    }

    // ========================
    // KONFIRMASI BAYAR
    // POST /api/parkir/bayar
    // ========================
    public function bayar(Request $request)
    {
        $request->validate([
            'barcode'       => 'required|string',
            'nominal_bayar' => 'required|integer|min:0',
            'petugas_id'    => 'required|integer',
        ]);

        $code = trim($request->barcode);
        $tiket = TiketParkir::with(['member', 'transaksi'])
                             ->where(function ($q) use ($code) {
                                 $q->where('barcode', $code)
                                   ->orWhere('no_invoice', $code);
                             })
                             ->where('status', 'aktif')
                             ->first();

        if (!$tiket) {
            return response()->json(['message' => 'Tiket tidak valid atau sudah diproses'], 404);
        }

        // Hitung final
        $waktuKeluar = now();
        $totalDetik  = max(1, (int) $tiket->waktu_masuk->diffInSeconds($waktuKeluar));
        $jamTarif    = max(1, (int) ceil($totalDetik / 3600));
        $totalHarga  = $tiket->jenis === 'member' ? 0 : ($jamTarif * $tiket->tarif_per_jam);
        $metode      = $tiket->jenis === 'member' ? 'member' : 'cash';

        // Validasi nominal bayar (hanya untuk non-member)
        if ($metode === 'cash' && $request->nominal_bayar < $totalHarga) {
            return response()->json(['message' => 'Nominal pembayaran kurang'], 422);
        }

        $kembalian = max(0, $request->nominal_bayar - $totalHarga);

        // Update tiket
        $tiket->update([
            'waktu_keluar' => $waktuKeluar,
            'total_menit'  => (int) floor($totalDetik / 60),
            'total_harga'  => $totalHarga,
            'status'       => 'selesai',
            'petugas_id'   => $request->petugas_id,
        ]);

        // Simpan transaksi
        $transaksi = Transaksi::create([
            'tiket_id'      => $tiket->id,
            'total_harga'   => $totalHarga,
            'nominal_bayar' => $request->nominal_bayar,
            'kembalian'     => $kembalian,
            'metode'        => $metode,
            'petugas_id'    => $request->petugas_id,
        ]);

        return response()->json([
            'message'   => 'Pembayaran berhasil',
            'tiket'     => $this->formatTiket($tiket->fresh(['member'])),
            'transaksi' => [
                'id'            => $transaksi->id,
                'total_harga'   => $transaksi->total_harga,
                'nominal_bayar' => $transaksi->nominal_bayar,
                'kembalian'     => $transaksi->kembalian,
                'metode'        => $transaksi->metode,
            ],
        ]);
    }

    // ========================
    // GET STRUK KELUAR
    // GET /api/parkir/struk/{tiketId}
    // ========================
    public function struk(int $tiketId)
    {
        $tiket = TiketParkir::with(['member', 'transaksi', 'petugas'])->find($tiketId);

        if (!$tiket) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json([
            'tiket'     => $this->formatTiket($tiket),
            'transaksi' => $tiket->transaksi ? [
                'id'            => $tiket->transaksi->id,
                'total_harga'   => $tiket->transaksi->total_harga,
                'nominal_bayar' => $tiket->transaksi->nominal_bayar,
                'kembalian'     => $tiket->transaksi->kembalian,
                'metode'        => $tiket->transaksi->metode,
            ] : null,
        ]);
    }

    // ========================
    // HELPER
    // ========================
    private function formatTiket(TiketParkir $tiket): array
    {
        $durasiTeks = null;
        if ($tiket->waktu_masuk) {
            $end = $tiket->waktu_keluar ?? now();
            $detik = max(1, (int) $tiket->waktu_masuk->diffInSeconds($end));
            $j = (int) floor($detik / 3600);
            $m = (int) floor(($detik % 3600) / 60);
            $s = (int) ($detik % 60);
            $parts = [];
            if ($j > 0) $parts[] = "{$j} jam";
            if ($m > 0) $parts[] = "{$m} menit";
            $parts[] = "{$s} detik";
            $durasiTeks = implode(' ', $parts);
        }

        return [
            'id'              => $tiket->id,
            'no_invoice'      => $tiket->no_invoice,
            'jenis'           => $tiket->jenis,
            'jenis_kendaraan' => $tiket->jenis_kendaraan,
            'plat_nomor'      => $tiket->plat_nomor,
            'foto_plat'       => $tiket->foto_plat ? asset('storage/' . $tiket->foto_plat) : null,
            'barcode'         => $tiket->barcode,
            'waktu_masuk'     => $tiket->waktu_masuk?->format('Y-m-d H:i:s'),
            'waktu_keluar'    => $tiket->waktu_keluar?->format('Y-m-d H:i:s'),
            'total_menit'     => $tiket->total_menit,
            'durasi_teks'     => $durasiTeks,
            'tarif_per_jam'   => $tiket->tarif_per_jam,
            'total_harga'     => $tiket->total_harga,
            'status'          => $tiket->status,
            'petugas'         => $tiket->petugas?->username,
            'member'          => $tiket->member ? [
                'no_member'   => $tiket->member->no_member,
                'nama'        => $tiket->member->nama,
                'instansi'    => $tiket->member->instansi,
                'daftar_plat' => $tiket->member->daftar_plat,
            ] : null,
        ];
    }
}

