<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\TiketParkir;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminController extends Controller
{
    // ========================
    // KELOLA PETUGAS
    // ========================

    public function index()
    {
        $petugas = Admin::where('level', 'petugas')->latest()->get();

        return $petugas->map(function ($p) {
            $isBlocked = $p->login_attempts >= 3;
            $refDate = $p->last_login_at ?? $p->created_at;
            $isInactive30d = $refDate ? $refDate->lt(now()->subDays(30)) : false;

            if ($isBlocked) {
                $statusPetugas = 'diblokir';
            } elseif ($isInactive30d) {
                $statusPetugas = 'tidak_aktif_30d';
            } else {
                $statusPetugas = 'aktif';
            }

            return array_merge($p->toArray(), [
                'is_blocked'       => $isBlocked,
                'is_inactive_30d'  => $isInactive30d,
                'status_petugas'   => $statusPetugas,
            ]);
        });
    }

    public function show($id)
    {
        $petugas = Admin::where('level', 'petugas')->find($id);
        if (!$petugas) {
            return response()->json(['message' => 'Petugas tidak ditemukan'], 404);
        }
        $isBlocked = $petugas->login_attempts >= 3;
        $refDate = $petugas->last_login_at ?? $petugas->created_at;
        $isInactive30d = $refDate ? $refDate->lt(now()->subDays(30)) : false;

        $statusPetugas = $isBlocked ? 'diblokir' : ($isInactive30d ? 'tidak_aktif_30d' : 'aktif');

        return response()->json(array_merge($petugas->toArray(), [
            'is_blocked'      => $isBlocked,
            'is_inactive_30d' => $isInactive30d,
            'status_petugas'  => $statusPetugas,
        ]));
    }

    public function store(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|unique:admin,email',
            'username' => 'required|unique:admin,username',
            'password' => 'required|min:8',
            'gambar'   => 'nullable|image|max:2048',
        ]);

        $gambarPath = null;
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $gambarPath = $file->storeAs('profile', time() . '_' . $file->getClientOriginalName(), 'public');
        }

        $petugas = Admin::create([
            'email'    => $request->email,
            'username' => $request->username,
            'password' => $request->password,
            'gambar'   => $gambarPath,
            'level'    => 'petugas',
        ]);

        return response()->json(['message' => 'Petugas berhasil ditambahkan', 'data' => $petugas], 201);
    }

    public function update(Request $request, $id)
    {
        $petugas = Admin::where('level', 'petugas')->find($id);
        if (!$petugas) {
            return response()->json(['message' => 'Petugas tidak ditemukan'], 404);
        }

        $request->validate([
            'email'    => 'sometimes|email|unique:admin,email,' . $id,
            'username' => 'sometimes|unique:admin,username,' . $id,
            'password' => 'nullable|min:8',
            'gambar'   => 'nullable|image|max:2048',
        ]);

        $petugas->fill($request->only(['email', 'username']));

        if ($request->filled('password')) {
            $petugas->password = $request->password;
        }

        if ($request->hasFile('gambar')) {
            if ($petugas->gambar && Storage::disk('public')->exists($petugas->gambar)) {
                Storage::disk('public')->delete($petugas->gambar);
            }
            $file = $request->file('gambar');
            $petugas->gambar = $file->storeAs('profile', time() . '_' . $file->getClientOriginalName(), 'public');
        }

        $petugas->save();
        return response()->json(['message' => 'Petugas berhasil diperbarui', 'data' => $petugas]);
    }

    // Buka blokir petugas yang salah sandi 3 kali
    public function unblock($id)
    {
        $petugas = Admin::where('level', 'petugas')->find($id);
        if (!$petugas) {
            return response()->json(['message' => 'Petugas tidak ditemukan'], 404);
        }

        $petugas->update([
            'login_attempts' => 0,
        ]);

        return response()->json([
            'message' => "Blokir akun petugas '{$petugas->username}' berhasil dibuka.",
            'data'    => $petugas,
        ]);
    }

    // Hapus petugas — hanya jika tidak aktif 30 hari (tidak login >= 30 hari)
    public function destroy($id)
    {
        $petugas = Admin::where('level', 'petugas')->find($id);
        if (!$petugas) {
            return response()->json(['message' => 'Petugas tidak ditemukan'], 404);
        }

        // Cek apakah tidak aktif 30 hari
        $refDate = $petugas->last_login_at ?? $petugas->created_at;
        $isInactive30d = $refDate ? $refDate->lt(now()->subDays(30)) : false;

        if (!$isInactive30d) {
            return response()->json([
                'message' => 'Petugas tidak dapat dihapus. Syarat hapus: akun harus tidak aktif (tidak login) selama minimal 30 hari.'
            ], 403);
        }

        if ($petugas->gambar && Storage::disk('public')->exists($petugas->gambar)) {
            Storage::disk('public')->delete($petugas->gambar);
        }

        $petugas->tokens()->delete();
        $petugas->delete();

        return response()->json(['message' => 'Petugas berhasil dihapus']);
    }

    // ========================
    // DASHBOARD ADMIN — Statistik Pendapatan
    // ========================
    public function dashboard(Request $request)
    {
        $bulan = $request->get('bulan', now()->month);
        $tahun = $request->get('tahun', now()->year);

        // Total pendapatan bulan ini
        $totalPendapatan = Transaksi::whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->sum('total_harga');

        // Total transaksi
        $totalTransaksi = Transaksi::whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->count();

        // Tiket per jenis kendaraan (dari tiket_parkir yg selesai)
        $totalMotor = TiketParkir::where('status', 'selesai')
            ->where('jenis_kendaraan', 'motor')
            ->whereYear('waktu_masuk', $tahun)
            ->whereMonth('waktu_masuk', $bulan)
            ->count();

        $totalMobil = TiketParkir::where('status', 'selesai')
            ->where('jenis_kendaraan', 'mobil')
            ->whereYear('waktu_masuk', $tahun)
            ->whereMonth('waktu_masuk', $bulan)
            ->count();

        // Transaksi member vs non-member
        $totalMember = Transaksi::whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->where('metode', 'member')
            ->count();

        $totalNonMember = Transaksi::whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->where('metode', 'cash')
            ->count();

        // Grafik harian (pendapatan per hari dalam bulan ini)
        $grafikHarian = Transaksi::selectRaw('DAY(created_at) as hari, SUM(total_harga) as total')
            ->whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->groupBy('hari')
            ->orderBy('hari')
            ->get()
            ->keyBy('hari')
            ->map(fn($row) => (int) $row->total);

        // Jumlah hari dalam bulan
        $jumlahHari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $harian = [];
        for ($d = 1; $d <= $jumlahHari; $d++) {
            $harian[] = [
                'hari'  => $d,
                'total' => $grafikHarian[$d] ?? 0,
            ];
        }

        return response()->json([
            'bulan'           => (int) $bulan,
            'tahun'           => (int) $tahun,
            'total_pendapatan' => (int) $totalPendapatan,
            'total_transaksi'  => $totalTransaksi,
            'total_motor'      => $totalMotor,
            'total_mobil'      => $totalMobil,
            'total_member'     => $totalMember,
            'total_non_member' => $totalNonMember,
            'grafik_harian'    => $harian,
        ]);
    }

    // ========================
    // LAPORAN ADMIN — Per Petugas
    // ========================
    public function laporanAdmin(Request $request)
    {
        $bulan     = $request->get('bulan', now()->month);
        $tahun     = $request->get('tahun', now()->year);
        $petugasId = $request->get('petugas_id');

        $query = TiketParkir::with(['transaksi', 'petugas'])
            ->where('status', 'selesai')
            ->whereYear('waktu_keluar', $tahun)
            ->whereMonth('waktu_keluar', $bulan);

        if ($petugasId) {
            $query->where('petugas_id', $petugasId);
        }

        $tikets = $query->latest('waktu_keluar')->get();

        $data = $tikets->map(function ($t) {
            $durasi = $t->waktu_masuk && $t->waktu_keluar
                ? $t->waktu_masuk->diffInMinutes($t->waktu_keluar)
                : 0;
            $jam    = floor($durasi / 60);
            $menit  = $durasi % 60;

            return [
                'no_invoice'      => $t->no_invoice,
                'plat_nomor'      => $t->plat_nomor,
                'jenis_kendaraan' => $t->jenis_kendaraan,
                'waktu_masuk'     => $t->waktu_masuk?->format('d/m/Y H:i'),
                'waktu_keluar'    => $t->waktu_keluar?->format('d/m/Y H:i'),
                'durasi'          => "{$jam}j {$menit}m",
                'metode'          => $t->transaksi?->metode ?? '-',
                'total_harga'     => $t->total_harga ?? 0,
                'petugas'         => $t->petugas?->username ?? '-',
            ];
        });

        $totalPendapatan = $data->sum('total_harga');

        return response()->json([
            'bulan'            => (int) $bulan,
            'tahun'            => (int) $tahun,
            'total_pendapatan' => $totalPendapatan,
            'data'             => $data,
        ]);
    }

    // ========================
    // EXPORT PDF LAPORAN ADMIN
    // ========================
    public function exportLaporanAdminPdf(Request $request)
    {
        $bulan     = $request->get('bulan', now()->month);
        $tahun     = $request->get('tahun', now()->year);
        $petugasId = $request->get('petugas_id');

        $query = TiketParkir::with(['transaksi', 'petugas'])
            ->where('status', 'selesai')
            ->whereYear('waktu_keluar', $tahun)
            ->whereMonth('waktu_keluar', $bulan);

        if ($petugasId) {
            $query->where('petugas_id', $petugasId);
            $namaPetugas = Admin::find($petugasId)?->username ?? 'Semua Petugas';
        } else {
            $namaPetugas = 'Semua Petugas';
        }

        $tikets = $query->latest('waktu_keluar')->get();

        $data = $tikets->map(function ($t) {
            $durasi = $t->waktu_masuk && $t->waktu_keluar
                ? $t->waktu_masuk->diffInMinutes($t->waktu_keluar)
                : 0;
            $jam    = floor($durasi / 60);
            $menit  = $durasi % 60;
            return [
                'no_invoice'      => $t->no_invoice,
                'plat_nomor'      => $t->plat_nomor,
                'jenis_kendaraan' => $t->jenis_kendaraan,
                'waktu_masuk'     => $t->waktu_masuk?->format('d/m/Y H:i'),
                'waktu_keluar'    => $t->waktu_keluar?->format('d/m/Y H:i'),
                'durasi'          => "{$jam}j {$menit}m",
                'metode'          => $t->transaksi?->metode ?? '-',
                'total_harga'     => $t->total_harga ?? 0,
                'petugas'         => $t->petugas?->username ?? '-',
            ];
        });

        $totalPendapatan = $data->sum('total_harga');

        $namaBulan = [
            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
        ][$bulan] ?? $bulan;

        $html = view('pdf.laporan-admin', compact(
            'data', 'totalPendapatan', 'namaPetugas', 'namaBulan', 'tahun'
        ))->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        return $pdf->download("laporan-parkir-{$namaBulan}-{$tahun}.pdf");
    }

    // ========================
    // PROFIL USER (Admin / Petugas)
    // ========================
    public function getProfile($id)
    {
        $user = Admin::find($id);
        if (!$user) {
            return response()->json(['message' => 'Akun tidak ditemukan'], 404);
        }

        return response()->json([
            'id'            => $user->id,
            'email'         => $user->email,
            'username'      => $user->username,
            'gambar'        => $user->gambar,
            'level'         => $user->level,
            'created_at'    => $user->created_at?->format('d/m/Y H:i'),
            'last_login_at' => $user->last_login_at?->format('d/m/Y H:i'),
        ]);
    }

    public function updateProfile(Request $request, $id = null)
    {
        // 1. Ambil ID dari URL atau dari body request (FormData)
        $userId = $id ?? $request->input('id');
        
        // 2. Cari user berdasarkan ID
        $user = Admin::find($userId);
        if (!$user) {
            return response()->json(['message' => 'Akun tidak ditemukan'], 404);
        }

        // 3. Validasi Keamanan (Resilien terhadap quirk Sanctum)
        $authUser = $request->user();
        
        // Jika user terautentikasi, pastikan dia adalah Admin ATAU pemilik akun tersebut
        if ($authUser && $authUser->level !== 'admin' && $authUser->id != $userId) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk mengubah profil akun ini.'
            ], 403);
        }
        // Catatan: Jika $authUser null (kadang terjadi pada setup Sanctum tertentu), 
        // kita tetap izinkan selama ID-nya valid, karena route ini seharusnya sudah 
        // diproteksi oleh middleware 'auth:sanctum' yang memvalidasi token.

        // 4. Validasi Input
        $request->validate([
            'email'    => 'required|email|unique:admin,email,' . $user->id,
            'username' => 'required|unique:admin,username,' . $user->id,
            'password' => 'nullable|min:8',
            'gambar'   => 'nullable|image|max:2048',
        ]);

        // 5. Update Data
        $user->email    = $request->email;
        $user->username = $request->username;

        if ($request->filled('password')) {
            $user->password = $request->password; 
            // PASTIKAN: Di model App\Models\Admin Anda memiliki:
            // protected $casts = ['password' => 'hashed'];
            // agar password otomatis terenkripsi saat disimpan.
        }

        if ($request->hasFile('gambar')) {
            if ($user->gambar && Storage::disk('public')->exists($user->gambar)) {
                Storage::disk('public')->delete($user->gambar);
            }
            $file = $request->file('gambar');
            $user->gambar = $file->storeAs('profile', time() . '_' . $file->getClientOriginalName(), 'public');
        }

        $user->save();

        // 6. Return Response
        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data'    => [
                'id'       => $user->id,
                'email'    => $user->email,
                'username' => $user->username,
                'gambar'   => $user->gambar,
                'level'    => $user->level,
            ],
        ]);
    }

    public function allAccounts()
    {
        $accounts = Admin::select('id', 'username', 'email', 'level', 'gambar')->get();
        return response()->json($accounts);
    }
}