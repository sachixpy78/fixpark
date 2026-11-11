<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Common\ErrorCorrectionLevel;

class MemberController extends Controller
{
    // GET /api/member
    public function index(Request $request)
    {
        $query = Member::latest();
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%')
                  ->orWhere('no_member', 'like', '%' . $request->search . '%')
                  ->orWhere('instansi', 'like', '%' . $request->search . '%')
                  ->orWhere('motor_1', 'like', '%' . $request->search . '%')
                  ->orWhere('motor_2', 'like', '%' . $request->search . '%')
                  ->orWhere('motor_3', 'like', '%' . $request->search . '%')
                  ->orWhere('mobil_1', 'like', '%' . $request->search . '%')
                  ->orWhere('mobil_2', 'like', '%' . $request->search . '%')
                  ->orWhere('mobil_3', 'like', '%' . $request->search . '%');
            });
        }
        return response()->json($query->get());
    }

    // GET /api/member/{id}
    public function show($id)
    {
        $member = Member::find($id);
        if (!$member) {
            return response()->json(['message' => 'Member tidak ditemukan'], 404);
        }
        return response()->json($member);
    }

    // POST /api/member/check (publik untuk kiosk landing page scan member)
    public function check(Request $request)
    {
        $request->validate(['no_member' => 'required|string']);

        $no = trim($request->no_member);
        $member = Member::where('no_member', $no)->first();

        if (!$member) {
            return response()->json(['message' => 'Nomor kartu member tidak ditemukan.'], 404);
        }

        if ($member->status !== 'aktif') {
            return response()->json(['message' => 'Status kartu member tidak aktif.'], 422);
        }

        if ($member->is_expired) {
            return response()->json(['message' => 'Kartu member telah EXPIRED (masa aktif 30 hari telah habis). Silakan lakukan perpanjangan di loket petugas.'], 422);
        }

        return response()->json([
            'no_member'        => $member->no_member,
            'nama'             => $member->nama,
            'instansi'         => $member->instansi,
            'daftar_plat'      => $member->daftar_plat,
            'daftar_plat_motor' => $member->daftar_plat_motor,
            'daftar_plat_mobil' => $member->daftar_plat_mobil,
        ]);
    }

    // POST /api/member
    public function store(Request $request)
    {
        $request->validate([
            'nama'     => 'required|string|max:100',
            'instansi' => 'required|string|max:150',
            'email'    => 'required|email|unique:members,email',
            'telepon'  => 'nullable|string|max:20',
            'motor_1'  => 'nullable|string|max:15',
            'motor_2'  => 'nullable|string|max:15',
            'motor_3'  => 'nullable|string|max:15',
            'mobil_1'  => 'nullable|string|max:15',
            'mobil_2'  => 'nullable|string|max:15',
            'mobil_3'  => 'nullable|string|max:15',
        ], [
            'email.unique' => 'Email sudah terdaftar sebagai member.',
        ]);

        // Minimal harus ada 1 plat (motor atau mobil)
        $hasMotor = !empty($request->motor_1);
        $hasMobil = !empty($request->mobil_1);
        if (!$hasMotor && !$hasMobil) {
            return response()->json(['message' => 'Minimal satu plat nomor (motor atau mobil) wajib diisi.'], 422);
        }

        $noMember = $this->generateNoMember();

        $member = Member::create([
            'no_member'      => $noMember,
            'nama'           => $request->nama,
            'instansi'       => $request->instansi,
            'email'          => $request->email,
            'telepon'        => $request->telepon,
            'motor_1'        => $request->motor_1 ? strtoupper(trim($request->motor_1)) : null,
            'motor_2'        => $request->motor_2 ? strtoupper(trim($request->motor_2)) : null,
            'motor_3'        => $request->motor_3 ? strtoupper(trim($request->motor_3)) : null,
            'mobil_1'        => $request->mobil_1 ? strtoupper(trim($request->mobil_1)) : null,
            'mobil_2'        => $request->mobil_2 ? strtoupper(trim($request->mobil_2)) : null,
            'mobil_3'        => $request->mobil_3 ? strtoupper(trim($request->mobil_3)) : null,
            'tanggal_daftar' => now()->toDateString(),
            'masa_aktif'     => now()->addDays(30)->toDateString(),
            'status'         => 'aktif',
        ]);

        // Kirim kartu member via email
        $this->kirimKartuMember($member);

        return response()->json([
            'message' => 'Member berhasil didaftarkan! Kartu member telah dikirim ke email.',
            'data'    => $member,
        ], 201);
    }

    // PUT /api/member/{id}
    public function update(Request $request, $id)
    {
        $member = Member::find($id);
        if (!$member) {
            return response()->json(['message' => 'Member tidak ditemukan'], 404);
        }

        $request->validate([
            'nama'     => 'sometimes|string|max:100',
            'instansi' => 'sometimes|string|max:150',
            'email'    => 'sometimes|email|unique:members,email,' . $id,
            'telepon'  => 'nullable|string|max:20',
            'motor_1'  => 'nullable|string|max:15',
            'motor_2'  => 'nullable|string|max:15',
            'motor_3'  => 'nullable|string|max:15',
            'mobil_1'  => 'nullable|string|max:15',
            'mobil_2'  => 'nullable|string|max:15',
            'mobil_3'  => 'nullable|string|max:15',
            'status'   => 'sometimes|in:aktif,nonaktif',
        ]);

        $data = $request->only(['nama', 'instansi', 'email', 'telepon', 'status']);
        if ($request->has('motor_1')) $data['motor_1'] = $request->motor_1 ? strtoupper(trim($request->motor_1)) : null;
        if ($request->has('motor_2')) $data['motor_2'] = $request->motor_2 ? strtoupper(trim($request->motor_2)) : null;
        if ($request->has('motor_3')) $data['motor_3'] = $request->motor_3 ? strtoupper(trim($request->motor_3)) : null;
        if ($request->has('mobil_1')) $data['mobil_1'] = $request->mobil_1 ? strtoupper(trim($request->mobil_1)) : null;
        if ($request->has('mobil_2')) $data['mobil_2'] = $request->mobil_2 ? strtoupper(trim($request->mobil_2)) : null;
        if ($request->has('mobil_3')) $data['mobil_3'] = $request->mobil_3 ? strtoupper(trim($request->mobil_3)) : null;

        $member->update($data);

        return response()->json(['message' => 'Member berhasil diperbarui', 'data' => $member]);
    }

    // POST /api/member/{id}/perpanjang
    // Perpanjang masa aktif kartu member (+30 hari) dengan konfirmasi pembayaran
    public function perpanjang($id)
    {
        $member = Member::find($id);
        if (!$member) {
            return response()->json(['message' => 'Member tidak ditemukan'], 404);
        }

        // Jika masa aktif lama masih berlaku di masa depan, tambahkan 30 hari dari masa aktif lama
        // Jika sudah lewat (expired), hitung 30 hari dari sekarang
        $currentMasaAktif = $member->masa_aktif;
        if ($currentMasaAktif && $currentMasaAktif->gt(now())) {
            $newMasaAktif = $currentMasaAktif->copy()->addDays(30);
        } else {
            $newMasaAktif = now()->addDays(30);
        }

        $member->update([
            'masa_aktif' => $newMasaAktif->toDateString(),
            'status'     => 'aktif',
        ]);

        // Kirim email kartu member yang baru diperpanjang
        $this->kirimKartuMember($member);

        return response()->json([
            'message' => "Member '{$member->nama}' berhasil diperpanjang hingga " . $newMasaAktif->format('d/m/Y') . ". Kartu baru dikirim ke email.",
            'data'    => $member,
        ]);
    }

    // DELETE /api/member/{id}
    public function destroy($id)
    {
        $member = Member::find($id);
        if (!$member) {
            return response()->json(['message' => 'Member tidak ditemukan'], 404);
        }
        $member->delete();
        return response()->json(['message' => 'Member berhasil dihapus']);
    }

    // POST /api/member/{id}/kirim-ulang
    public function kirimUlang($id)
    {
        $member = Member::find($id);
        if (!$member) {
            return response()->json(['message' => 'Member tidak ditemukan'], 404);
        }
        $this->kirimKartuMember($member);
        return response()->json(['message' => 'Kartu member berhasil dikirim ulang ke ' . $member->email]);
    }

    // ========================
    // HELPER: Generate nomor member
    // ========================
    private function generateNoMember(): string
    {
        $prefix = 'MBR-' . now()->format('Ymd') . '-';
        $count  = Member::where('no_member', 'like', $prefix . '%')->count();
        return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    // ========================
    // HELPER: Generate QR PNG Binary via BaconQrCode + GD murni
    // Tidak butuh imagick, 100% kompatibel XAMPP
    // ========================
    private function generateQrPngBinary(string $text): string
    {
        $qr = Encoder::encode($text, ErrorCorrectionLevel::M(), 'UTF-8');
        $matrix = $qr->getMatrix();
        $width  = $matrix->getWidth();
        $height = $matrix->getHeight();

        $scale     = 6; // ukuran module pixel
        $border    = 2; // quiet zone
        $imgWidth  = ($width + 2 * $border) * $scale;
        $imgHeight = ($height + 2 * $border) * $scale;

        $img   = imagecreatetruecolor($imgWidth, $imgHeight);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);

        imagefilledrectangle($img, 0, 0, $imgWidth, $imgHeight, $white);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $x1 = ($x + $border) * $scale;
                    $y1 = ($y + $border) * $scale;
                    $x2 = $x1 + $scale - 1;
                    $y2 = $y1 + $scale - 1;
                    imagefilledrectangle($img, $x1, $y1, $x2, $y2, $black);
                }
            }
        }

        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();
        imagedestroy($img);

        return $pngData;
    }

    // ========================
    // HELPER: Kirim email kartu member dengan CID Inline Attachment
    // CID Embedding didukung 100% oleh Gmail Web & Gmail Mobile!
    // ========================
    private function kirimKartuMember(Member $member): void
    {
        $pngBinary = $this->generateQrPngBinary($member->no_member);

        Mail::send([], [], function ($message) use ($member, $pngBinary) {
            // Embed inline attachment menggunakan Content-ID (CID)
            $qrCid = $message->embedData($pngBinary, 'qrcode-member.png', 'image/png');

            $html = $this->templateKartu($member, $qrCid);

            $message->to($member->email)
                    ->subject('Kartu Member E-Parkir Plaza Andalas - ' . $member->no_member)
                    ->html($html)
                    ->attachData($pngBinary, "QR-Member-{$member->no_member}.png", [
                        'mime' => 'image/png',
                    ]);
        });
    }

    // ========================
    // HELPER: Template HTML kartu member — bentuk landscape kartu metallic grey
    // ========================
    private function templateKartu(Member $member, string $qrCid): string
    {
        $noMember = htmlspecialchars($member->no_member);
        $nama     = htmlspecialchars($member->nama);
        $instansi = htmlspecialchars($member->instansi);

        // Waktu buat (tanggal & jam)
        $tglBuat = $member->created_at ? $member->created_at->format('d/m/Y') : now()->format('d/m/Y');
        $jamBuat = $member->created_at ? $member->created_at->format('H:i') : now()->format('H:i');
        $aktif   = $member->masa_aktif ? $member->masa_aktif->format('d/m/Y') : now()->addDays(30)->format('d/m/Y');

        // Daftar plat motor & mobil
        $motorList = array_values(array_filter([
            $member->motor_1 ?: $member->plat_1,
            $member->motor_2 ?: $member->plat_2,
            $member->motor_3 ?: $member->plat_3,
        ]));
        $mobilList = array_values(array_filter([
            $member->mobil_1,
            $member->mobil_2,
            $member->mobil_3,
        ]));

        $motorHtml = count($motorList) > 0 ? implode('&nbsp;&nbsp;&bull;&nbsp;&nbsp;', array_map('htmlspecialchars', $motorList)) : '-';
        $mobilHtml = count($mobilList) > 0 ? implode('&nbsp;&nbsp;&bull;&nbsp;&nbsp;', array_map('htmlspecialchars', $mobilList)) : '-';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="id">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Kartu Member E-Parkir</title>
        </head>
        <body style="margin:0;padding:0;background:#0f172a;font-family:Arial,Helvetica,sans-serif;">
          <div style="max-width:680px;margin:30px auto;padding:0 16px;">

            <!-- ===== KARTU MEMBER (Design Metallic Graphite Credit Card) ===== -->
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="border-radius:24px;overflow:hidden;
                          background:#262f3a linear-gradient(145deg, #384351 0%, #252d37 55%, #181e26 100%);
                          color:#ffffff;box-shadow:0 20px 45px rgba(0,0,0,0.6);
                          border:2px solid #4a5568;border-collapse:separate;">
              <tr>
                <td style="padding:28px 24px;border:1px solid rgba(212,175,55,0.35);border-radius:22px;">

                  <!-- TOP ROW: Header Brand + Member Badge -->
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                    <tr>
                      <td style="vertical-align:top;">
                        <div style="font-size:10px;font-weight:700;letter-spacing:3px;color:#94a3b8;text-transform:uppercase;margin-bottom:2px;">
                          E-PARKIR
                        </div>
                        <div style="font-size:24px;font-weight:900;letter-spacing:0.5px;color:#ffffff;">
                          Plaza Andalas
                        </div>
                      </td>
                      <td align="right" style="vertical-align:top;">
                        <div style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);
                                    color:#451a03;font-size:11px;font-weight:900;letter-spacing:2px;
                                    padding:7px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(217,119,6,0.4);
                                    display:inline-block;text-transform:uppercase;">
                          MEMBER
                        </div>
                      </td>
                    </tr>
                  </table>

                  <!-- MAIN CONTENT: 2 Kolom (Kiri Info & Chip, Kanan QR Box) -->
                  <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                      <!-- KOLOM KIRI (Info & Nomor) -->
                      <td style="vertical-align:top;padding-right:20px;">

                        <!-- EMV Chip & Contactless Wave -->
                        <table cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
                          <tr>
                            <td style="vertical-align:middle;">
                              <!-- Metallic Gold EMV Chip Graphic -->
                              <div style="width:46px;height:34px;background:linear-gradient(135deg,#eab308 0%,#ca8a04 100%);
                                          border-radius:6px;border:1px solid #a16207;box-shadow:inset 0 1px 2px rgba(255,255,255,0.4),0 2px 6px rgba(0,0,0,0.4);position:relative;">
                                <table width="100%" height="34" cellpadding="0" cellspacing="0">
                                  <tr>
                                    <td style="border-right:1px solid #854d0e;border-bottom:1px solid #854d0e;width:15px;height:12px;"></td>
                                    <td style="border-right:1px solid #854d0e;border-bottom:1px solid #854d0e;width:16px;"></td>
                                    <td style="border-bottom:1px solid #854d0e;"></td>
                                  </tr>
                                  <tr>
                                    <td style="border-right:1px solid #854d0e;border-bottom:1px solid #854d0e;height:10px;"></td>
                                    <td style="border-right:1px solid #854d0e;border-bottom:1px solid #854d0e;background:rgba(255,255,255,0.25);border-radius:2px;"></td>
                                    <td style="border-bottom:1px solid #854d0e;"></td>
                                  </tr>
                                  <tr>
                                    <td style="border-right:1px solid #854d0e;height:12px;"></td>
                                    <td style="border-right:1px solid #854d0e;"></td>
                                    <td></td>
                                  </tr>
                                </table>
                              </div>
                            </td>
                            <td style="vertical-align:middle;padding-left:10px;">
                              <!-- Contactless Wave Icon -->
                              <span style="font-size:20px;font-weight:900;color:#94a3b8;letter-spacing:1px;line-height:1;">)))</span>
                            </td>
                          </tr>
                        </table>

                        <!-- Nomor Member (Embossed Gold Monospace) -->
                        <div style="font-family:'Courier New',Courier,monospace;font-size:21px;font-weight:900;
                                    letter-spacing:3.5px;color:#fde047;margin-bottom:14px;
                                    text-shadow:0 1px 2px rgba(0,0,0,0.9), 0 0 12px rgba(253,224,71,0.3);">
                          {$noMember}
                        </div>

                        <!-- Nama & Instansi -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                          <tr>
                            <td style="vertical-align:top;padding-right:12px;width:50%;">
                              <div style="font-size:8.5px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;font-weight:700;margin-bottom:2px;">
                                NAMA PEMILIK
                              </div>
                              <div style="font-size:15px;font-weight:800;color:#ffffff;line-height:1.2;">
                                {$nama}
                              </div>
                            </td>
                            <td style="vertical-align:top;width:50%;">
                              <div style="font-size:8.5px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;font-weight:700;margin-bottom:2px;">
                                INSTANSI / PERUSAHAAN
                              </div>
                              <div style="font-size:14px;font-weight:700;color:#cbd5e1;line-height:1.2;">
                                {$instansi}
                              </div>
                            </td>
                          </tr>
                        </table>

                        <!-- Box Plat Nomor Terdaftar (Motor & Mobil) -->
                        <div style="background:rgba(15,23,42,0.65);border:1px solid rgba(255,255,255,0.12);
                                    border-radius:10px;padding:9px 14px;margin-bottom:14px;">
                          <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                              <td style="vertical-align:top;padding-right:12px;border-right:1px solid rgba(255,255,255,0.1);">
                                <div style="font-size:8.5px;color:#34d399;text-transform:uppercase;letter-spacing:0.8px;font-weight:700;margin-bottom:2px;">
                                  MOTOR (MAKS. 3):
                                </div>
                                <div style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:800;color:#ffffff;letter-spacing:1px;">
                                  {$motorHtml}
                                </div>
                              </td>
                              <td style="vertical-align:top;padding-left:12px;">
                                <div style="font-size:8.5px;color:#60a5fa;text-transform:uppercase;letter-spacing:0.8px;font-weight:700;margin-bottom:2px;">
                                  MOBIL (MAKS. 3):
                                </div>
                                <div style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:800;color:#ffffff;letter-spacing:1px;">
                                  {$mobilHtml}
                                </div>
                              </td>
                            </tr>
                          </table>
                        </div>

                        <!-- Waktu Buat & Masa Aktif -->
                        <table width="100%" cellpadding="0" cellspacing="0">
                          <tr>
                            <td style="vertical-align:top;padding-right:14px;">
                              <div style="font-size:8.5px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.8px;font-weight:700;margin-bottom:2px;">
                                WAKTU BUAT
                              </div>
                              <div style="font-size:11px;font-weight:700;color:#ffffff;">
                                {$tglBuat}
                              </div>
                              <div style="font-size:11px;font-weight:600;color:#cbd5e1;">
                                {$jamBuat}
                              </div>
                            </td>
                            <td style="vertical-align:top;">
                              <div style="font-size:8.5px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.8px;font-weight:700;margin-bottom:2px;">
                                MASA AKTIF (30 HARI)
                              </div>
                              <div style="font-size:13px;font-weight:900;color:#fde047;letter-spacing:0.5px;">
                                {$aktif}
                              </div>
                            </td>
                          </tr>
                        </table>

                      </td>

                      <!-- KOLOM KANAN (White QR Container) -->
                      <td align="center" style="vertical-align:middle;width:170px;">
                        <div style="background:#ffffff;border-radius:16px;padding:12px;display:inline-block;
                                    box-shadow:0 8px 24px rgba(0,0,0,0.5);text-align:center;">
                          <img src="{$qrCid}"
                               width="140" height="140"
                               alt="QR Member"
                               style="display:block;border-radius:6px;margin:0 auto;border:0;" />
                          <div style="margin-top:6px;font-size:9.5px;color:#475569;font-family:'Courier New',monospace;font-weight:700;text-align:center;">
                            {$noMember}
                          </div>
                        </div>
                        <div style="margin-top:8px;font-size:10px;color:#cbd5e1;font-weight:600;letter-spacing:0.5px;text-align:center;">
                          Scan di Kiosk Masuk
                        </div>
                      </td>
                    </tr>
                  </table>

                </td>
              </tr>
            </table>
            <!-- ===== END KARTU ===== -->

            <!-- Panduan Penggunaan -->
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="margin-top:20px;background:#1e293b;border-radius:14px;
                          border:1px solid #334155;box-shadow:0 4px 14px rgba(0,0,0,0.25);">
              <tr>
                <td style="padding:20px 24px;">
                  <div style="font-size:14px;font-weight:700;color:#f8fafc;margin-bottom:10px;">
                    Ketentuan & Cara Menggunakan Kartu Member
                  </div>
                  <ol style="margin:0;padding-left:18px;color:#cbd5e1;font-size:12px;line-height:1.8;">
                    <li>Kartu member hanya dapat digunakan untuk <strong>plat nomor yang telah didaftarkan</strong> (Motor: {$motorHtml} | Mobil: {$mobilHtml}).</li>
                    <li>Pada kiosk parkir, pilih jenis kendaraan dan scan QR code pada kartu ini untuk mencetak tiket parkir.</li>
                    <li>Masa aktif kartu berlaku selama <strong>30 hari</strong> sejak tanggal pembuatan/perpanjangan.</li>
                    <li>Jika masa aktif telah habis, status kartu menjadi <strong>Expired</strong> dan dapat diperpanjang di loket petugas.</li>
                    <li>Sebagai member aktif, Anda berhak parkir <strong>GRATIS</strong> di Plaza Andalas!</li>
                  </ol>
                </td>
              </tr>
            </table>

            <!-- Footer -->
            <p style="text-align:center;font-size:11px;color:#64748b;margin-top:16px;">
              Email ini dikirim otomatis oleh Sistem E-Parkir Plaza Andalas.<br>
              QR Code kartu member juga telah dilampirkan sebagai file gambar pada email ini.
            </p>
          </div>
        </body>
        </html>
        HTML;
    }
}
