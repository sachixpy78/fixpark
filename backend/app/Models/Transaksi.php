<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';

    protected $fillable = [
        'tiket_id', 'total_harga', 'nominal_bayar', 'kembalian', 'metode', 'petugas_id',
    ];

    public function tiket()
    {
        return $this->belongsTo(TiketParkir::class, 'tiket_id');
    }

    public function petugas()
    {
        return $this->belongsTo(Admin::class, 'petugas_id');
    }
}

