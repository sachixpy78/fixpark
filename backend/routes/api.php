<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TiketController;
use App\Http\Controllers\ParkirController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\LaporanController;
use Illuminate\Support\Facades\Route;

// =========================
// AUTHENTICATION
// =========================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// Lupa Password (OTP via Gmail)
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::put('/reset-password', [AuthController::class, 'resetPassword']);

// Profil User & Daftar Akun
Route::get('/profile/{id}', [AdminController::class, 'getProfile']);
Route::post('/profile/{id}', [AdminController::class, 'updateProfile']);
Route::post('/update-profile', [AdminController::class, 'updateProfile']);
Route::get('/admin', [AdminController::class, 'allAccounts']);

// =========================
// KELOLA PETUGAS (Admin)
// =========================
Route::get('/petugas', [AdminController::class, 'index']);
Route::post('/petugas', [AdminController::class, 'store']);
Route::get('/petugas/{id}', [AdminController::class, 'show']);
Route::put('/petugas/{id}', [AdminController::class, 'update']);
Route::delete('/petugas/{id}', [AdminController::class, 'destroy']);
Route::post('/petugas/{id}/unblock', [AdminController::class, 'unblock']);

// =========================
// ADMIN DASHBOARD & LAPORAN
// =========================
Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
Route::get('/admin/laporan', [AdminController::class, 'laporanAdmin']);
Route::get('/admin/laporan/pdf', [AdminController::class, 'exportLaporanAdminPdf']);

// =========================
// TIKET PARKIR (Publik - tanpa auth)
// =========================
Route::post('/member/check', [MemberController::class, 'check']);
Route::post('/tiket/non-member', [TiketController::class, 'buatNonMember']);
Route::post('/tiket/member', [TiketController::class, 'buatMember']);
Route::get('/tiket/{barcode}', [TiketController::class, 'show']);
Route::post('/tiket/{barcode}/plat', [TiketController::class, 'inputPlat']);

// =========================
// PARKIR KELUAR (Petugas)
// =========================
Route::post('/parkir/scan-keluar', [ParkirController::class, 'scanKeluar']);
Route::post('/parkir/bayar', [ParkirController::class, 'bayar']);
Route::get('/parkir/struk/{tiketId}', [ParkirController::class, 'struk']);

// =========================
// KELOLA MEMBER (Petugas/Admin)
// =========================
Route::get('/member', [MemberController::class, 'index']);
Route::post('/member', [MemberController::class, 'store']);
Route::get('/member/{id}', [MemberController::class, 'show']);
Route::put('/member/{id}', [MemberController::class, 'update']);
Route::delete('/member/{id}', [MemberController::class, 'destroy']);
Route::post('/member/{id}/kirim-ulang', [MemberController::class, 'kirimUlang']);
Route::post('/member/{id}/perpanjang', [MemberController::class, 'perpanjang']);

// =========================
// TRANSAKSI & LAPORAN
// =========================
Route::get('/transaksi', [TransaksiController::class, 'index']);
Route::get('/laporan/ringkasan', [TransaksiController::class, 'ringkasan']);

// TAMBAHKAN ROUTE INI:
Route::get('/laporan/pdf', [LaporanController::class, 'cetakPdf']);