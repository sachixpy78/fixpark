<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    // ========================
    // LOGIN ADMIN / PETUGAS
    // ========================
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required',                 // email atau username
            'password' => 'required',
            'level'    => 'required|in:admin,petugas',
        ]);

        $admin = Admin::where('level', $request->level)
            ->where(function ($q) use ($request) {
                $q->where('email', $request->login)
                  ->orWhere('username', $request->login);
            })
            ->first();

        if (!$admin) {
            return response()->json(['message' => 'Akun tidak ditemukan'], 404);
        }

        if ($admin->login_attempts >= 3) {
            return response()->json(['message' => 'Akun terblokir karena salah memasukkan password sebanyak 3 kali. Hubungi Admin untuk membuka blokir.'], 403);
        }

        if (!Hash::check($request->password, $admin->password)) {
            $admin->increment('login_attempts');
            if ($admin->login_attempts >= 3) {
                return response()->json(['message' => 'Akun Anda telah terblokir karena 3 kali salah memasukkan password. Hubungi Admin.'], 403);
            }
            $sisa = 3 - $admin->login_attempts;
            return response()->json(['message' => "Password salah. Sisa percobaan: {$sisa}"], 401);
        }

        $admin->update([
            'login_attempts' => 0,
            'last_login_at'  => now(),
        ]);

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token'   => $token,
            'admin'   => [
                'id'       => $admin->id,
                'email'    => $admin->email,
                'username' => $admin->username,
                'level'    => $admin->level,
                'gambar'   => $admin->gambar,
            ],
        ]);
    }

    // ========================
    // LOGOUT
    // ========================
    public function logout(Request $request)
    {
        $token = PersonalAccessToken::findToken($request->bearerToken() ?? '');
        $token?->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }

    // ========================
    // LUPA PASSWORD -> KIRIM OTP KE GMAIL
    // ========================
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $admin = Admin::where('email', $request->email)->first();

        if ($admin) {
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // OTP baru menimpa OTP lama, jadi OTP lama otomatis tidak bisa dipakai lagi
            $admin->update([
                'otp'            => Hash::make($otp),
                'otp_expires_at' => now()->addMinutes(5),
                'otp_attempts'   => 0,
            ]);

            Mail::html(
                "<h2>Kode OTP Anda: <span style='color:#d9534f;'>{$otp}</span></h2>
                 <p>Berlaku selama 5 menit. Jangan berikan ke siapa pun.</p>",
                function ($message) use ($admin) {
                    $message->to($admin->email)->subject('Kode OTP Reset Password - E-Parkir');
                }
            );
        }

        // Respons sama walau email tidak terdaftar
        return response()->json(['message' => 'Jika email terdaftar, kode OTP telah dikirim.']);
    }

    // ========================
    // VERIFIKASI OTP (belum menghanguskan OTP)
    // ========================
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !$this->checkOtp($admin, $request->otp)) {
            return response()->json(['message' => 'OTP tidak valid atau sudah kadaluarsa.'], 400);
        }

        return response()->json(['message' => 'OTP valid']);
    }

    // ========================
    // RESET PASSWORD (OTP diverifikasi ulang, lalu hangus)
    // ========================
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'otp'      => 'required|digits:6',
            'password' => 'required|min:8',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !$this->checkOtp($admin, $request->otp)) {
            return response()->json(['message' => 'OTP tidak valid atau sudah kadaluarsa.'], 400);
        }

        $admin->update([
            'password'       => $request->password,   // otomatis ter-hash
            'otp'            => null,                 // OTP hangus setelah dipakai
            'otp_expires_at' => null,
            'otp_attempts'   => 0,
            'login_attempts' => 0,
        ]);

        $admin->tokens()->delete();                   // paksa logout sesi lama

        return response()->json(['message' => 'Password berhasil diubah!']);
    }

    // ========================
    // CEK OTP (maks 5 kali salah, lalu OTP hangus)
    // ========================
    private function checkOtp(Admin $admin, string $otp): bool
    {
        if (!$admin->otp || !$admin->otp_expires_at || $admin->otp_expires_at->isPast()) {
            return false;
        }

        if (!Hash::check($otp, $admin->otp)) {
            $admin->increment('otp_attempts');

            if ($admin->otp_attempts >= 5) {
                $admin->update(['otp' => null, 'otp_expires_at' => null, 'otp_attempts' => 0]);
            }
            return false;
        }

        return true;
    }
}