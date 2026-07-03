<?php

use App\Http\Controllers\Api\DataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Default user route (jangan dihapus)a
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API CRUD Data
Route::resource('/data', DataController::class);

// ====================== LOGIN ADMIN ======================
Route::post('/login', function (Request $request) {
    $email = $request->input('email');
    $password = $request->input('password');

    $admin = DB::table('admin')
        ->where('email', $email)
        ->where('password', $password)
        ->first();

    if ($admin) {
        return response()->json([
            'success' => true,
            'admin' => [
                'id' => $admin->id,
                'email' => $admin->email,
                'username' => $admin->username,
            ],
        ]);
    } else {
        return response()->json([
            'success' => false,
            'message' => 'Email atau password salah!',
        ]);
    }
});

// ====================== LUPA PASSWORD ======================
Route::post('/forgot-password', function (Request $request) {
    $email = $request->input('email');
    $newPass = $request->input('new_password');

    $admin = DB::table('admin')->where('email', $email)->first();

    if (!$admin) {
        return response()->json([
            'success' => false,
            'message' => 'Email tidak ditemukan!',
        ]);
    }

    DB::table('admin')->where('email', $email)->update(['password' => $newPass]);

    return response()->json([
        'success' => true,
        'message' => 'Password berhasil diubah!',
    ]);
});
