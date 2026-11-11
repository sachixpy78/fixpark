<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Model
{
    use HasApiTokens, HasFactory;

    protected $table = 'admin';

    protected $fillable = [
        'email',
        'username',
        'password',
        'gambar',
        'level',
        'login_attempts',
        'last_login_at',
        'otp',
        'otp_expires_at',
        'otp_attempts',
    ];

    protected $hidden = ['password', 'otp'];

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'password'       => 'hashed',   // otomatis di-hash saat diisi
            'last_login_at'  => 'datetime',
            'otp_expires_at' => 'datetime',
        ];
    }
}