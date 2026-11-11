<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\TiketParkir;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TiketController extends Controller
{
    // ========================
    // BUAT TIKET NON-MEMBER
    // POST /api/tiket/non-member
    // ========================
    public function buatNonMember(Request $request)
    {
        $request->validate([
            'jenis_kendaraan' => 'required|in:motor,mobil',
        ]);

        $tiket = $this->buatTiket('non_member', $request->jenis_kendaraan);

        return response()->json([
            'message' => 'Tiket berhasil dibuat',
            'tiket'   => $this->formatTiket($tiket),
            'qr_svg'  => $this->generateQr($tiket->barcode),
        ], 201);
    }

    // ========================
    // BUAT TIKET MEMBER (scan kartu)
    // POST /api/tiket/member
    // ========================
    public function buatMember(Request $request)
    {
        $request->validate([
            'no_member'       => 'required|string',
            'jenis_kendaraan' => 'required|in:motor,mobil',
            'plat_nomor'      => 'required|string',
        ], [
            'plat_nomor.required' => 'Silakan pilih salah satu plat nomor kendaraan yang terdaftar.',
        ]);

        $member = Member::where('no_member', trim($request->no_member))
                        ->where('status', 'aktif')
                        ->first();

        if (!$member) {
            return response()->json(['message' => 'Kartu member tidak valid atau tidak aktif'], 404);
        }

        if ($member->is_expired) {
            return response()->json(['message' => 'Kartu member telah EXPIRED (masa aktif 30 hari telah berakhir). Silakan lakukan perpanjangan di loket petugas.'], 422);
        }

        // Validasi plat nomor harus salah satu dari plat yang didaftarkan
        $allowedPlats = $member->daftar_plat;
        $cleanInput   = strtoupper(str_replace(' ', '', $request->plat_nomor));
        $matchedPlat  = null;

        foreach ($allowedPlats as $p) {
            if (strtoupper(str_replace(' ', '', $p)) === $cleanInput) {
                $matchedPlat = $p;
                break;
            }
        }

        if (!$matchedPlat) {
            $strAllowed = implode(', ', $allowedPlats);
            return response()->json([
                'message' => "Plat nomor '{$request->plat_nomor}' tidak terdaftar pada kartu member ini. Pilihan plat Anda: [ {$strAllowed} ]"
            ], 422);
        }

        $tiket = $this->buatTiket('member', $request->jenis_kendaraan, $member->id, $matchedPlat);

        return response()->json([
            'message' => 'Tiket member berhasil dibuat',
            'member'  => [
                'no_member'   => $member->no_member,
                'nama'        => $member->nama,
                'instansi'    => $member->instansi,
                'daftar_plat' => $member->daftar_plat,
            ],
            'tiket'   => $this->formatTiket($tiket),
            'qr_svg'  => $this->generateQr($tiket->barcode),
        ], 201);
    }

    // ========================
    // INPUT PLAT + UPLOAD FOTO
    // POST /api/tiket/{barcode}/plat
    // ========================
    public function inputPlat(Request $request, string $barcode)
    {
        $request->validate([
            'plat_nomor' => 'required|string|max:15',
            'foto_plat'  => 'nullable|image|max:5120',
        ]);

        $tiket = TiketParkir::with('member')->where('barcode', $barcode)->where('status', 'aktif')->first();

        if (!$tiket) {
            return response()->json(['message' => 'Tiket tidak ditemukan atau sudah tidak aktif'], 404);
        }

        // Jika tiket member, plat HARUS cocok dengan salah satu dari 3 slot plat member
        if ($tiket->jenis === 'member' && $tiket->member) {
            $allowedPlats = $tiket->member->daftar_plat;
            $cleanInput = strtoupper(str_replace(' ', '', $request->plat_nomor));

            $matched = false;
            foreach ($allowedPlats as $p) {
                if (strtoupper(str_replace(' ', '', $p)) === $cleanInput) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $strAllowed = implode(', ', $allowedPlats);
                return response()->json([
                    'message' => "Nomor plat '{$request->plat_nomor}' tidak terdaftar pada kartu member ini. Plat yang diizinkan hanya: [ {$strAllowed} ]"
                ], 422);
            }
        }

        $fotoPath = $tiket->foto_plat;
        if ($request->hasFile('foto_plat')) {
            $file     = $request->file('foto_plat');
            $fotoPath = $file->storeAs(
                'plat',
                time() . '_' . Str::slug($request->plat_nomor) . '.' . $file->extension(),
                'public'
            );
        }

        $tiket->update([
            'plat_nomor' => strtoupper($request->plat_nomor),
            'foto_plat'  => $fotoPath,
        ]);

        return response()->json([
            'message' => 'Plat nomor berhasil disimpan',
            'tiket'   => $this->formatTiket($tiket->fresh()),
            'qr_svg'  => $this->generateQr($tiket->barcode),
        ]);
    }

    // ========================
    // GET DETAIL TIKET
    // GET /api/tiket/{barcode}
    // ========================
    public function show(string $barcode)
    {
        $tiket = TiketParkir::with('member')->where('barcode', $barcode)->first();

        if (!$tiket) {
            return response()->json(['message' => 'Tiket tidak ditemukan'], 404);
        }

        return response()->json([
            'tiket'  => $this->formatTiket($tiket),
            'qr_svg' => $this->generateQr($tiket->barcode),
        ]);
    }

    // ========================
    // HELPER: Buat tiket baru
    // ========================
    private function buatTiket(string $jenis, string $jenisKendaraan, ?int $memberId = null, ?string $platNomor = null): TiketParkir
    {
        $tarif     = $jenisKendaraan === 'motor' ? 3000 : 5000;
        $noInvoice = $this->generateNoInvoice();
        $barcode   = Str::uuid()->toString();

        return TiketParkir::create([
            'no_invoice'      => $noInvoice,
            'jenis'           => $jenis,
            'member_id'       => $memberId,
            'jenis_kendaraan' => $jenisKendaraan,
            'plat_nomor'      => $platNomor,
            'tarif_per_jam'   => $tarif,
            'barcode'         => $barcode,
            'waktu_masuk'     => now(),
            'status'          => 'aktif',
        ]);
    }

    // ========================
    // HELPER: Generate nomor invoice unik
    // ========================
    private function generateNoInvoice(): string
    {
        $prefix = 'INV-' . now()->format('Ymd') . '-';
        $count  = TiketParkir::where('no_invoice', 'like', $prefix . '%')->count();
        return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    // ========================
    // HELPER: Generate QR SVG (base64)
    // ========================
    private function generateQr(string $barcode): string
    {
        return base64_encode(QrCode::format('svg')->size(200)->generate($barcode));
    }

    // ========================
    // HELPER: Format tiket untuk response
    // ========================
    private function formatTiket(TiketParkir $tiket): array
    {
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
            'tarif_per_jam'   => $tiket->tarif_per_jam,
            'total_harga'     => $tiket->total_harga,
            'status'          => $tiket->status,
            'member'          => $tiket->member ? [
                'no_member'   => $tiket->member->no_member,
                'nama'        => $tiket->member->nama,
                'instansi'    => $tiket->member->instansi,
                'daftar_plat' => $tiket->member->daftar_plat,
            ] : null,
        ];
    }
}

