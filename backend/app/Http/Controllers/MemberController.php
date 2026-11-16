<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberRenewal;
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
            'no_member'         => $member->no_member,
            'nama'              => $member->nama,
            'instansi'          => $member->instansi,
            'daftar_plat'       => $member->daftar_plat,
            'daftar_plat_motor' => $member->daftar_plat_motor,
            'daftar_plat_mobil' => $member->daftar_plat_mobil,
        ]);
    }

    // POST /api/member
    public function store(Request $request)
    {
        $request->validate([
            'nama'          => 'required|string|max:100',
            'instansi'      => 'required|string|max:150',
            'email'         => 'required|email|unique:members,email',
            'telepon'       => 'nullable|string|max:20',
            'motor_1'       => 'nullable|string|max:15',
            'motor_2'       => 'nullable|string|max:15',
            'motor_3'       => 'nullable|string|max:15',
            'mobil_1'       => 'nullable|string|max:15',
            'mobil_2'       => 'nullable|string|max:15',
            'mobil_3'       => 'nullable|string|max:15',
            'nominal_bayar' => 'nullable|numeric|min:150000',
        ], [
            'email.unique'      => 'Email sudah terdaftar sebagai member.',
            'nominal_bayar.min' => 'Pembayaran cash minimal Rp 150.000.',
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

        // Kirim kartu member via email (abaikan jika gagal)
        $emailError = null;
        try {
            $this->kirimKartuMember($member);
        } catch (\Throwable $e) {
            $emailError = 'Email gagal dikirim: ' . $e->getMessage();
        }

        return response()->json([
            'message'      => "Member '{$member->nama}' berhasil didaftarkan." . ($emailError ? ' ' . $emailError : ' Kartu member dikirim ke email.'),
            'data'         => $member,
            'email_status' => $emailError ? 'failed' : 'sent',
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
            'nama'     => 'required|string|max:100',
            'instansi' => 'required|string|max:150',
            'email'    => 'required|email|unique:members,email,' . $id,
            'telepon'  => 'nullable|string|max:20',
            'motor_1'  => 'nullable|string|max:15',
            'motor_2'  => 'nullable|string|max:15',
            'motor_3'  => 'nullable|string|max:15',
            'mobil_1'  => 'nullable|string|max:15',
            'mobil_2'  => 'nullable|string|max:15',
            'mobil_3'  => 'nullable|string|max:15',
            'status'   => 'nullable|in:aktif,nonaktif',
        ]);

        $member->update([
            'nama'     => $request->nama,
            'instansi' => $request->instansi,
            'email'    => $request->email,
            'telepon'  => $request->telepon,
            'motor_1'  => $request->motor_1 ? strtoupper(trim($request->motor_1)) : null,
            'motor_2'  => $request->motor_2 ? strtoupper(trim($request->motor_2)) : null,
            'motor_3'  => $request->motor_3 ? strtoupper(trim($request->motor_3)) : null,
            'mobil_1'  => $request->mobil_1 ? strtoupper(trim($request->mobil_1)) : null,
            'mobil_2'  => $request->mobil_2 ? strtoupper(trim($request->mobil_2)) : null,
            'mobil_3'  => $request->mobil_3 ? strtoupper(trim($request->mobil_3)) : null,
            'status'   => $request->status ?? $member->status,
        ]);

        return response()->json(['message' => 'Member berhasil diperbarui', 'data' => $member]);
    }

    // POST /api/member/{id}/perpanjang
    public function perpanjang($id)
    {
        $member = Member::find($id);
        if (!$member) {
            return response()->json(['message' => 'Member tidak ditemukan'], 404);
        }

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

        // Log riwayat perpanjang member
        MemberRenewal::create([
            'member_id'       => $member->id,
            'no_member'       => $member->no_member,
            'nama_member'     => $member->nama,
            'instansi'        => $member->instansi,
            'masa_aktif_lama' => $currentMasaAktif ? $currentMasaAktif->toDateString() : null,
            'masa_aktif_baru' => $newMasaAktif->toDateString(),
            'nominal_bayar'   => 150000,
        ]);

        // Kirim email kartu member yang baru diperpanjang (abaikan jika gagal)
        $emailError = null;
        try {
            $this->kirimKartuMember($member);
        } catch (\Throwable $e) {
            $emailError = 'Email gagal dikirim: ' . $e->getMessage();
        }

        return response()->json([
            'message'      => "Member '{$member->nama}' berhasil diperpanjang hingga " . $newMasaAktif->format('d/m/Y') . ($emailError ? '. ' . $emailError : '. Kartu baru dikirim ke email.'),
            'data'         => $member,
            'email_status' => $emailError ? 'failed' : 'sent',
        ]);
    }

    // GET /api/member-renewals
    public function renewalIndex(Request $request)
    {
        $query = MemberRenewal::latest();
        if ($request->tanggal_dari) {
            $query->whereDate('created_at', '>=', $request->tanggal_dari);
        }
        if ($request->tanggal_sampai) {
            $query->whereDate('created_at', '<=', $request->tanggal_sampai);
        }
        return response()->json($query->get());
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

        $emailError = null;
        try {
            $this->kirimKartuMember($member);
        } catch (\Throwable $e) {
            $emailError = $e->getMessage();
        }

        if ($emailError) {
            return response()->json(['message' => 'Gagal mengirim email: ' . $emailError], 500);
        }

        return response()->json(['message' => 'Kartu member berhasil dikirim ulang ke ' . $member->email]);
    }

    // ========================
    // POST /api/member/{id}/kirim-reminder
    // Kirim email pengingat perpanjang ke satu member yang expired
    // ========================
    public function kirimReminder($id)
    {
        $member = Member::find($id);
        if (!$member) {
            return response()->json(['message' => 'Member tidak ditemukan'], 404);
        }

        $emailError = null;
        try {
            $this->kirimEmailReminder($member);
        } catch (\Throwable $e) {
            $emailError = $e->getMessage();
        }

        if ($emailError) {
            return response()->json([
                'message'      => 'Gagal mengirim reminder: ' . $emailError,
                'email_status' => 'failed',
            ], 500);
        }

        return response()->json([
            'message'      => "Reminder perpanjang berhasil dikirim ke {$member->email}.",
            'email_status' => 'sent',
        ]);
    }

    // ========================
    // POST /api/member/kirim-reminder-semua
    // Kirim reminder ke SEMUA member yang sudah expired
    // ========================
    public function kirimReminderSemua()
    {
        $expired = Member::where('status', 'aktif')
            ->where('masa_aktif', '<', now()->toDateString())
            ->get();

        if ($expired->isEmpty()) {
            return response()->json(['message' => 'Tidak ada member expired saat ini.', 'sent' => 0]);
        }

        $berhasil = 0;
        $gagal    = 0;
        foreach ($expired as $member) {
            try {
                $this->kirimEmailReminder($member);
                $berhasil++;
            } catch (\Throwable $e) {
                $gagal++;
            }
        }

        return response()->json([
            'message' => "Reminder terkirim ke {$berhasil} member. Gagal: {$gagal}.",
            'sent'    => $berhasil,
            'failed'  => $gagal,
        ]);
    }

    // ========================
    // HELPER: Generate nomor member unik
    // ========================
    private function generateNoMember(): string
    {
        $prefix = 'MBR-' . now()->format('Ymd') . '-';
        $last   = Member::where('no_member', 'like', $prefix . '%')->orderByDesc('no_member')->first();
        $seq    = $last ? ((int) substr($last->no_member, -3)) + 1 : 1;
        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    // ========================
    // HELPER: Kirim email kartu member aktif (dengan QR Code inline)
    // ========================
    private function kirimKartuMember(Member $member): void
    {
        $pngBinary = $this->generateQrPngBinary($member->no_member);

        Mail::send([], [], function ($message) use ($member, $pngBinary) {
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
    // HELPER: Generate QR PNG Binary dari string
    // ========================
    private function generateQrPngBinary(string $text): string
    {
        $encoder    = new Encoder();
        $ecLevel    = ErrorCorrectionLevel::M();
        $qrCode     = $encoder->encode($text, $ecLevel, 'UTF-8');
        $matrix     = $qrCode->getMatrix();
        $size       = $matrix->getWidth();
        $scale      = 8;
        $padding    = 24;
        $dim        = $size * $scale + $padding * 2;
        $img        = imagecreatetruecolor($dim, $dim);
        $white      = imagecolorallocate($img, 255, 255, 255);
        $black      = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if ($matrix->get($col, $row) === 1) {
                    $x1 = $col * $scale + $padding;
                    $y1 = $row * $scale + $padding;
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
    // HELPER: Template HTML kartu member (dengan QR CID)
    // ========================
    private function templateKartu(Member $member, string $qrCid): string
    {
        $masaAktif  = $member->masa_aktif ? $member->masa_aktif->format('d/m/Y') : '—';
        $motorPlats = array_filter([$member->motor_1, $member->motor_2, $member->motor_3]);
        $mobilPlats = array_filter([$member->mobil_1, $member->mobil_2, $member->mobil_3]);
        $motorHtml  = $motorPlats ? implode(', ', $motorPlats) : '—';
        $mobilHtml  = $mobilPlats ? implode(', ', $mobilPlats) : '—';

        return <<<HTML
        <!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"></head>
        <body style="margin:0;padding:0;background:#0f172a;font-family:'Segoe UI',Arial,sans-serif;">
          <div style="max-width:520px;margin:32px auto;background:#1e293b;border-radius:20px;border:1px solid #334155;overflow:hidden;">
            <div style="background:linear-gradient(135deg,#1d4ed8 0%,#1e40af 100%);padding:28px 32px;text-align:center;">
              <div style="color:#fff;font-size:22px;font-weight:800;">E-PARKIR Plaza Andalas</div>
              <div style="color:#bfdbfe;font-size:13px;margin-top:4px;">Kartu Member Aktif</div>
            </div>
            <div style="padding:28px 32px;">
              <p style="color:#e2e8f0;font-size:15px;margin:0 0 20px;">Halo <strong style="color:#f8fafc;">{$member->nama}</strong>,</p>
              <p style="color:#cbd5e1;font-size:14px;margin:0 0 20px;">Kartu member Anda telah berhasil dibuat/diperpanjang. Gunakan QR Code di bawah untuk parkir gratis.</p>
              <div style="text-align:center;background:#fff;border-radius:16px;padding:20px;margin-bottom:20px;">
                <img src="cid:{$qrCid}" alt="QR Member" style="width:160px;height:160px;display:block;margin:0 auto;">
                <div style="font-family:monospace;font-size:16px;font-weight:800;color:#1e293b;margin-top:12px;">{$member->no_member}</div>
              </div>
              <table style="width:100%;font-size:13px;color:#cbd5e1;border-collapse:collapse;margin-bottom:16px;">
                <tr><td style="padding:4px 0;color:#94a3b8;">Nama</td><td style="text-align:right;font-weight:600;color:#f8fafc;">{$member->nama}</td></tr>
                <tr><td style="padding:4px 0;color:#94a3b8;">Instansi</td><td style="text-align:right;color:#f8fafc;">{$member->instansi}</td></tr>
                <tr><td style="padding:4px 0;color:#94a3b8;">Masa Aktif</td><td style="text-align:right;font-weight:700;color:#34d399;">{$masaAktif}</td></tr>
                <tr><td style="padding:4px 0;color:#94a3b8;">Motor</td><td style="text-align:right;font-family:monospace;color:#38bdf8;">{$motorHtml}</td></tr>
                <tr><td style="padding:4px 0;color:#94a3b8;">Mobil</td><td style="text-align:right;font-family:monospace;color:#38bdf8;">{$mobilHtml}</td></tr>
              </table>
              <p style="color:#94a3b8;font-size:11px;margin:0;">Email ini dikirim otomatis oleh Sistem E-Parkir Plaza Andalas.</p>
            </div>
          </div>
        </body></html>
        HTML;
    }

    // ========================
    // HELPER: Template email reminder perpanjang kartu expired
    // ========================
    private function kirimEmailReminder(Member $member): void
    {
        $masaAktif   = $member->masa_aktif ? $member->masa_aktif->format('d/m/Y') : '—';
        $hariExpired = $member->masa_aktif
            ? (int) now()->diffInDays($member->masa_aktif, false)
            : 0;
        $labelHari   = $hariExpired < 0
            ? abs($hariExpired) . ' hari yang lalu'
            : 'hari ini';

        $html = <<<HTML
        <!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"></head>
        <body style="margin:0;padding:0;background:#0f172a;font-family:'Segoe UI',Arial,sans-serif;">
          <div style="max-width:520px;margin:32px auto;background:#1e293b;border-radius:20px;border:1px solid #334155;overflow:hidden;">
            <div style="background:linear-gradient(135deg,#dc2626 0%,#b91c1c 100%);padding:28px 32px;text-align:center;">
              <div style="color:#fff;font-size:20px;font-weight:800;">Kartu Member Anda Telah Expired</div>
              <div style="color:#fca5a5;font-size:13px;margin-top:4px;">E-Parkir Plaza Andalas</div>
            </div>
            <div style="padding:28px 32px;">
              <p style="color:#e2e8f0;font-size:15px;margin:0 0 20px;">Halo <strong style="color:#f8fafc;">{$member->nama}</strong>,</p>
              <p style="color:#cbd5e1;font-size:14px;line-height:1.7;margin:0 0 20px;">
                Kartu member Anda <strong style="color:#38bdf8;font-family:monospace;">{$member->no_member}</strong>
                telah <strong style="color:#f87171;">EXPIRED</strong> sejak <strong style="color:#f8fafc;">{$masaAktif}</strong> ({$labelHari}).
              </p>
              <div style="background:#0f172a;border:1px solid #ef4444;border-left:4px solid #ef4444;border-radius:12px;padding:16px 20px;margin-bottom:20px;">
                <div style="color:#f87171;font-weight:700;font-size:13px;margin-bottom:8px;">Detail Kartu</div>
                <table style="width:100%;font-size:13px;color:#cbd5e1;border-collapse:collapse;">
                  <tr><td style="padding:3px 0;color:#94a3b8;">No. Member</td><td style="text-align:right;font-weight:700;color:#38bdf8;font-family:monospace;">{$member->no_member}</td></tr>
                  <tr><td style="padding:3px 0;color:#94a3b8;">Nama</td><td style="text-align:right;color:#f8fafc;">{$member->nama}</td></tr>
                  <tr><td style="padding:3px 0;color:#94a3b8;">Masa Aktif Lama</td><td style="text-align:right;font-weight:700;color:#f87171;">{$masaAktif}</td></tr>
                </table>
              </div>
              <div style="background:#1e3a5f;border:1px solid #1d4ed8;border-radius:12px;padding:16px 20px;margin-bottom:20px;">
                <div style="color:#60a5fa;font-weight:700;font-size:13px;margin-bottom:8px;">Cara Memperpanjang</div>
                <ol style="margin:0;padding-left:18px;color:#bfdbfe;font-size:13px;line-height:1.9;">
                  <li>Kunjungi loket petugas E-Parkir Plaza Andalas</li>
                  <li>Tunjukkan nomor member: <strong style="font-family:monospace;color:#38bdf8;">{$member->no_member}</strong></li>
                  <li>Bayar perpanjangan <strong>Rp 150.000</strong> (30 hari)</li>
                </ol>
              </div>
              <p style="color:#94a3b8;font-size:11px;margin:0;">Email ini dikirim otomatis oleh Sistem E-Parkir Plaza Andalas.</p>
            </div>
          </div>
        </body></html>
        HTML;

        Mail::send([], [], function ($message) use ($member, $html) {
            $message->to($member->email)
                    ->subject("PENTING: Kartu Member E-Parkir Anda Expired — {$member->no_member}")
                    ->html($html);
        });
    }

}
